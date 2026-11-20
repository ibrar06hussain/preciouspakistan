<?php

namespace Tests\Feature\Phase1;

use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_receives_verification_email(): void
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' => 'Zainab Tariq',
            'email' => 'zainab@example.com',
            'phone' => '+923001234567',
            'password' => 'TravelPak2026!',
            'password_confirmation' => 'TravelPak2026!',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'zainab@example.com')->firstOrFail();
        $this->assertSame(AccountType::Customer, $user->account_type);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('Customer'));
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unverified_customer_cannot_access_dashboard_until_verified(): void
    {
        $customer = User::factory()->unverified()->create();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('verification.notice'));

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $customer->id, 'hash' => sha1($customer->email)]
        );

        $this->actingAs($customer)
            ->get($verificationUrl)
            ->assertRedirect(route('customer.dashboard'));

        $this->assertTrue($customer->fresh()->hasVerifiedEmail());

        $this->actingAs($customer->fresh())
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee($customer->name);
    }

    public function test_deactivated_customer_cannot_log_in(): void
    {
        $customer = User::factory()->inactive()->create([
            'password' => Hash::make('ValidPass2026!'),
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'ValidPass2026!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_endpoint_is_rate_limited_after_repeated_failures(): void
    {
        $customer = User::factory()->create([
            'password' => Hash::make('ValidPass2026!'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.store'), [
                'email' => $customer->email,
                'password' => 'WrongPassword99',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => $customer->email,
            'password' => 'WrongPassword99',
        ])->assertStatus(429);
    }

    public function test_customer_can_request_and_complete_password_reset(): void
    {
        Notification::fake();

        $customer = User::factory()->create();

        $this->post(route('password.email'), [
            'email' => $customer->email,
        ])->assertSessionHas('status');

        Notification::assertSentTo($customer, ResetPassword::class);

        $token = Password::createToken($customer);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $customer->email,
            'password' => 'NewSecurePass2026!',
            'password_confirmation' => 'NewSecurePass2026!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewSecurePass2026!', $customer->fresh()->password));
    }
}

<?php

namespace Tests\Feature\Phase1;

use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Identity\Notifications\AdminInvitationNotification;
use App\Modules\Identity\Services\AdminInvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invited_staff_member_can_accept_invitation_and_access_admin_panel(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->staff()->create();
        $superAdmin->assignRole(Role::findOrCreate('Super Admin', 'web'));

        $service = app(AdminInvitationService::class);
        $result = $service->createInvitation($superAdmin, 'Bilal Ahmed', 'bilal@preciouspakistan.com', 'Booking Officer');

        Notification::assertSentOnDemand(AdminInvitationNotification::class);

        $token = $result['plain_token'];

        $this->get(route('admin.invitations.show', ['token' => $token]))
            ->assertOk()
            ->assertSee('Booking Officer');

        $this->post(route('admin.invitations.accept', ['token' => $token]), [
            'phone' => '+923009876543',
            'password' => 'Booking!Officer2026#',
            'password_confirmation' => 'Booking!Officer2026#',
        ])->assertRedirect('/admin');

        $staff = User::query()->where('email', 'bilal@preciouspakistan.com')->firstOrFail();
        $this->assertSame(AccountType::Staff, $staff->account_type);
        $this->assertTrue($staff->is_active);
        $this->assertTrue($staff->hasVerifiedEmail());
        $this->assertTrue($staff->hasRole('Booking Officer'));
        $this->assertNotNull($result['invitation']->fresh()->accepted_at);

        // Reusing the same token as a guest must fail with 404.
        auth()->logout();
        $this->get(route('admin.invitations.show', ['token' => $token]))
            ->assertNotFound();
    }

    public function test_expired_invitation_is_rejected(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->staff()->create();
        $superAdmin->assignRole(Role::findOrCreate('Super Admin', 'web'));

        $result = app(AdminInvitationService::class)->createInvitation(
            $superAdmin,
            'Expired Staff',
            'expired@preciouspakistan.com',
            'Tour Manager'
        );

        $result['invitation']->forceFill(['expires_at' => now()->subHour()])->save();

        $this->get(route('admin.invitations.show', ['token' => $result['plain_token']]))
            ->assertNotFound();
    }

    public function test_hotel_partner_role_invitation_is_rejected(): void
    {
        $superAdmin = User::factory()->staff()->create();

        $this->expectException(InvalidArgumentException::class);

        app(AdminInvitationService::class)->createInvitation(
            $superAdmin,
            'External Hotel',
            'hotel@example.com',
            'Hotel Partner'
        );
    }
}

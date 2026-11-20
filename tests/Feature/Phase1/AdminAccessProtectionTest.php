<?php

namespace Tests\Feature\Phase1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_authenticated_customer_is_forbidden_from_admin_panel(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('Customer', 'web'));

        $this->actingAs($customer)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_staff_user_without_staff_role_is_forbidden_from_admin_panel(): void
    {
        $staffWithoutRole = User::factory()->staff()->create();

        $this->actingAs($staffWithoutRole)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_deactivated_staff_user_is_blocked_from_admin_panel(): void
    {
        $staff = User::factory()->staff()->inactive()->create();
        $staff->assignRole(Role::findOrCreate('Super Admin', 'web'));

        $this->actingAs($staff)
            ->get('/admin')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_active_verified_staff_with_role_can_access_admin_dashboard_and_not_customer_dashboard(): void
    {
        $staff = User::factory()->staff()->create();
        $staff->assignRole(Role::findOrCreate('Super Admin', 'web'));

        $this->actingAs($staff)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Precious Pakistan Admin');

        $this->actingAs($staff)
            ->get(route('customer.dashboard'))
            ->assertForbidden();
    }
}

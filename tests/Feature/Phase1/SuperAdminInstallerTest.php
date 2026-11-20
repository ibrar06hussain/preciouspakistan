<?php

namespace Tests\Feature\Phase1;

use App\Models\User;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Identity\Services\SuperAdminInstallerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class SuperAdminInstallerTest extends TestCase
{
    use RefreshDatabase;

    public function test_interactive_cli_command_creates_verified_super_admin(): void
    {
        $this->artisan('precious:install-super-admin')
            ->expectsQuestion('Super Admin full name', 'Ibrar Hussain')
            ->expectsQuestion('Super Admin email address', 'owner@preciouspakistan.com')
            ->expectsQuestion('Super Admin password (min 12 chars, upper/lower/number/symbol)', 'Karakoram!2026#Peak')
            ->expectsQuestion('Confirm Super Admin password', 'Karakoram!2026#Peak')
            ->assertSuccessful();

        $admin = User::query()->where('email', 'owner@preciouspakistan.com')->firstOrFail();

        $this->assertSame(AccountType::Staff, $admin->account_type);
        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->hasVerifiedEmail());
        $this->assertTrue($admin->hasRole('Super Admin'));
    }

    public function test_installer_rejects_weak_or_common_passwords(): void
    {
        $service = app(SuperAdminInstallerService::class);

        $this->expectException(InvalidArgumentException::class);

        $service->install('Weak Admin', 'weak@preciouspakistan.com', 'password123');
    }

    public function test_installer_refuses_second_run_when_super_admin_already_exists(): void
    {
        app(SuperAdminInstallerService::class)->install(
            'Primary Owner',
            'owner@preciouspakistan.com',
            'Karakoram!2026#Peak'
        );

        $this->artisan('precious:install-super-admin')
            ->assertFailed();
    }
}

<?php

namespace Tests\Feature\Phase7;

use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Services\FleetCatalogService;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleFleetSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FleetPublicPrivacyAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleFleetSeeder::class);
    }

    public function test_public_fleet_page_shows_included_driver_and_hides_private_registration_and_driver_phone(): void
    {
        $this->get('/fleet')
            ->assertOk()
            ->assertSee('Toyota Land Cruiser Prado 4x4 (Sample)')
            ->assertSee('Driver: INCLUDED')
            ->assertDontSee('ICT-PP-401')
            ->assertDontSee('+923000000401');
    }

    public function test_fleet_manager_accesses_fleet_admin_while_finance_officer_is_forbidden_and_rate_edits_require_rates_permission(): void
    {
        $fleetManager = User::factory()->staff()->create();
        $fleetManager->assignRole(RbacCatalog::ROLE_FLEET_MANAGER);

        $finance = User::factory()->staff()->create();
        $finance->assignRole(RbacCatalog::ROLE_FINANCE_OFFICER);

        $this->actingAs($fleetManager)->get('/admin/vehicles')->assertOk();
        $this->actingAs($fleetManager)->get('/admin/vehicle-reservations')->assertOk();
        $this->actingAs($finance)->get('/admin/vehicles')->assertForbidden();

        // Staff with vehicles.manage ONLY (no vehicles.rates.manage) must be blocked from editing seasonal rates.
        $noRatesRole = Role::findOrCreate('Fleet Ops No Rates', 'web');
        $noRatesRole->syncPermissions(['vehicles.view', 'vehicles.manage']);
        $opsUser = User::factory()->staff()->create();
        $opsUser->assignRole($noRatesRole);

        $prado = Vehicle::query()->where('code', 'FLT-PRADO-01')->firstOrFail();

        $this->expectException(AuthorizationException::class);

        app(FleetCatalogService::class)->saveVehicle($opsUser, $prado, [
            'name' => $prado->name,
            'seasonal_rates' => [],
        ]);
    }
}

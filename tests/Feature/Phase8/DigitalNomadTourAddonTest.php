<?php

namespace Tests\Feature\Phase8;

use App\Models\User;
use App\Modules\Nomads\Models\NomadBlackout;
use App\Modules\Nomads\Models\NomadLocation;
use App\Modules\Nomads\Services\NomadPricingService;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Modules\Tours\Models\Tour;
use Carbon\CarbonImmutable;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleNomadsSeeder;
use Database\Seeders\SampleToursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DigitalNomadTourAddonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleToursSeeder::class);
        $this->seed(SampleNomadsSeeder::class);
    }

    public function test_standalone_workspace_checkout_is_disabled_and_non_nomad_tour_is_rejected(): void
    {
        $hunzaLoc = NomadLocation::query()->where('slug', 'karimabad-mountain-cowork-hunza-sample')->with('facilities')->firstOrFail();
        $desk = $hunzaLoc->facilities->firstWhere('code', 'HOT_DESK');
        $pricing = app(NomadPricingService::class);

        // Standalone booking without a tour MUST fail.
        try {
            $pricing->quoteTourAddon(null, $desk, CarbonImmutable::create(now()->year, 6, 10), 2, 1, 'day', 1, 'PKR');
            $this->fail('Expected standalone nomad booking to be disabled.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Standalone Digital Nomad workspace checkout is disabled', $e->getMessage());
        }

        // Swat tour has supports_nomad_addon = false -> must fail.
        $swatTour = Tour::query()->where('slug', 'swat-malam-jabba-couples-retreat')->firstOrFail();
        $this->expectException(InvalidArgumentException::class);
        $pricing->quoteTourAddon($swatTour, $desk, CarbonImmutable::create(now()->year, 6, 10), 2, 1, 'day', 1, 'PKR');
    }

    public function test_tour_addon_quotes_daily_and_hourly_rates_and_respects_blackouts_and_honesty_notices(): void
    {
        $hunzaTour = Tour::query()->where('slug', 'hunza-attabad-passu-scenic-expedition')->firstOrFail();
        $hunzaLoc = NomadLocation::query()->where('slug', 'karimabad-mountain-cowork-hunza-sample')->with('facilities')->firstOrFail();
        $desk = $hunzaLoc->facilities->firstWhere('code', 'HOT_DESK');
        $pricing = app(NomadPricingService::class);

        // 2 seats x 3 days x 3,500 PKR = 21,000.00 PKR
        $daily = $pricing->quoteTourAddon($hunzaTour, $desk, CarbonImmutable::create(now()->year, 6, 10), 3, 2, 'day', 1, 'PKR');
        $this->assertSame('21000.00', $daily['total_amount']);
        $this->assertTrue($daily['requires_manual_confirmation']);
        $this->assertStringContainsString('not guaranteed', strtolower($daily['honesty_notice']));

        // Hourly: 1 seat x 4 hrs/day x 2 days x 700 PKR = 5,600.00 PKR
        $hourly = $pricing->quoteTourAddon($hunzaTour, $desk, CarbonImmutable::create(now()->year, 6, 10), 2, 1, 'hour', 4, 'PKR');
        $this->assertSame('5600.00', $hourly['total_amount']);

        // Blackout blocks booking.
        NomadBlackout::query()->create([
            'nomad_location_id' => $hunzaLoc->id,
            'start_date' => now()->year.'-06-11',
            'end_date' => now()->year.'-06-12',
            'reason' => 'Solar Upgrade',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $pricing->quoteTourAddon($hunzaTour, $desk, CarbonImmutable::create(now()->year, 6, 10), 3, 1, 'day', 1, 'PKR');
    }

    public function test_public_nomads_hub_and_nomad_manager_permissions(): void
    {
        $this->get('/digital-nomads')
            ->assertOk()
            ->assertSee('Karimabad Mountain Cowork Hub (Sample)')
            ->assertSee('Standalone Workspace Checkout Disabled')
            ->assertSee('Connectivity &amp; Power Disclosure', false);

        $nomadManager = User::factory()->staff()->create();
        $nomadManager->assignRole(RbacCatalog::ROLE_NOMAD_MANAGER);

        $finance = User::factory()->staff()->create();
        $finance->assignRole(RbacCatalog::ROLE_FINANCE_OFFICER);

        $this->actingAs($nomadManager)->get('/admin/nomad-locations')->assertOk();
        $this->actingAs($finance)->get('/admin/nomad-locations')->assertForbidden();
    }
}

<?php

namespace Tests\Feature\Phase4;

use App\Models\User;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\District;
use App\Modules\Destinations\Models\Region;
use App\Modules\Destinations\Models\UrlRedirect;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class DestinationHierarchyAndRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
    }

    public function test_destination_rejects_district_belonging_to_a_different_region(): void
    {
        $tourManager = User::factory()->staff()->create();
        $tourManager->assignRole(RbacCatalog::ROLE_TOUR_MANAGER);

        $punjab = Region::query()->where('slug', 'punjab')->firstOrFail();
        $hunzaDistrict = District::query()->where('name', 'Hunza')->firstOrFail();

        $this->expectException(InvalidArgumentException::class);

        app(DestinationHierarchyService::class)->saveDestination($tourManager, null, [
            'region_id' => $punjab->id,
            'district_id' => $hunzaDistrict->id,
            'name' => 'Invalid Mismatched Destination',
            'slug' => 'invalid-mismatched',
        ]);
    }

    public function test_changing_destination_and_region_slugs_issues_301_redirects_and_collapses_chains(): void
    {
        $tourManager = User::factory()->staff()->create();
        $tourManager->assignRole(RbacCatalog::ROLE_TOUR_MANAGER);

        $service = app(DestinationHierarchyService::class);
        $hunza = Destination::query()->where('slug', 'hunza')->firstOrFail();

        // First rename: /destinations/hunza -> /destinations/hunza-valley
        $hunza = $service->saveDestination($tourManager, $hunza, [
            'region_id' => $hunza->region_id,
            'district_id' => $hunza->district_id,
            'name' => 'Hunza Valley',
            'slug' => 'hunza-valley',
        ]);

        $this->get('/destinations/hunza')
            ->assertStatus(301)
            ->assertRedirect('/destinations/hunza-valley');

        $this->assertSame(1, UrlRedirect::query()->where('source_path', '/destinations/hunza')->value('hits'));

        // Second rename: /destinations/hunza-valley -> /destinations/karakoram-hunza
        // Original redirect must collapse directly to /destinations/karakoram-hunza (no chain).
        $service->saveDestination($tourManager, $hunza, [
            'region_id' => $hunza->region_id,
            'district_id' => $hunza->district_id,
            'name' => 'Karakoram Hunza',
            'slug' => 'karakoram-hunza',
        ]);

        $this->get('/destinations/hunza')
            ->assertStatus(301)
            ->assertRedirect('/destinations/karakoram-hunza');

        $this->get('/destinations/karakoram-hunza')->assertOk()->assertSee('Karakoram Hunza');
    }

    public function test_changing_region_slug_issues_301_redirect(): void
    {
        $tourManager = User::factory()->staff()->create();
        $tourManager->assignRole(RbacCatalog::ROLE_TOUR_MANAGER);

        $gb = Region::query()->where('slug', 'gilgit-baltistan')->firstOrFail();

        app(DestinationHierarchyService::class)->saveRegion($tourManager, $gb, [
            'country_id' => $gb->country_id,
            'name' => $gb->name,
            'region_type' => $gb->region_type,
            'slug' => 'gilgit-baltistan-pakistan',
        ]);

        $this->get('/regions/gilgit-baltistan')
            ->assertStatus(301)
            ->assertRedirect('/regions/gilgit-baltistan-pakistan');
    }
}

<?php

namespace Tests\Feature\Phase5;

use App\Models\User;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Services\TourCatalogService;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TourPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
    }

    public function test_tour_manager_can_access_tours_admin_and_finance_officer_is_forbidden(): void
    {
        $tourManager = User::factory()->staff()->create();
        $tourManager->assignRole(RbacCatalog::ROLE_TOUR_MANAGER);

        $finance = User::factory()->staff()->create();
        $finance->assignRole(RbacCatalog::ROLE_FINANCE_OFFICER);

        $this->actingAs($tourManager)->get('/admin/tours')->assertOk();
        $this->actingAs($tourManager)->get('/admin/tours/create')->assertOk();

        $this->actingAs($finance)->get('/admin/tours')->assertForbidden();
    }

    public function test_editor_without_publish_permission_cannot_publish_tour(): void
    {
        $role = Role::findOrCreate('Junior Tour Editor', 'web');
        $role->syncPermissions(['tours.view', 'tours.create', 'tours.update']);

        $editor = User::factory()->staff()->create();
        $editor->assignRole($role);

        $hunza = Destination::query()->where('slug', 'hunza')->firstOrFail();
        $service = app(TourCatalogService::class);

        $tour = $service->saveTour($editor, null, [
            'title' => 'Junior Draft Tour',
            'slug' => 'junior-draft-tour',
            'duration_days' => 1,
            'duration_nights' => 0,
            'summary' => 'Day trip',
            'description_html' => '<p>Day trip details</p>',
            'pricing_model' => Tour::PRICING_CUSTOM_QUOTE,
            'destination_ids' => [$hunza->id],
            'itinerary_days' => [
                ['day_number' => 1, 'title' => 'Full Day Hunza', 'description_html' => '<p>Sightseeing</p>'],
            ],
        ]);

        $this->expectException(AuthorizationException::class);
        $service->publishTour($editor, $tour);
    }
}

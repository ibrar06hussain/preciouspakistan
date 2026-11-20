<?php

namespace Tests\Feature\Phase4;

use App\Models\User;
use App\Modules\Destinations\Models\Region;
use App\Modules\Destinations\Services\DestinationHierarchyService;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
    }

    public function test_tour_manager_can_access_all_destination_admin_resources(): void
    {
        $tourManager = User::factory()->staff()->create();
        $tourManager->assignRole(RbacCatalog::ROLE_TOUR_MANAGER);

        $this->actingAs($tourManager)->get('/admin/regions')->assertOk();
        $this->actingAs($tourManager)->get('/admin/districts')->assertOk();
        $this->actingAs($tourManager)->get('/admin/destinations')->assertOk();
        $this->actingAs($tourManager)->get('/admin/attractions')->assertOk();
    }

    public function test_finance_officer_is_forbidden_from_destination_admin_resources(): void
    {
        $finance = User::factory()->staff()->create();
        $finance->assignRole(RbacCatalog::ROLE_FINANCE_OFFICER);

        $this->actingAs($finance)->get('/admin/regions')->assertForbidden();
        $this->actingAs($finance)->get('/admin/destinations')->assertForbidden();
    }

    public function test_booking_officer_can_view_destinations_but_cannot_mutate_hierarchy(): void
    {
        $bookingOfficer = User::factory()->staff()->create();
        $bookingOfficer->assignRole(RbacCatalog::ROLE_BOOKING_OFFICER);

        $this->actingAs($bookingOfficer)->get('/admin/destinations')->assertOk();
        $this->actingAs($bookingOfficer)->get('/admin/destinations/create')->assertForbidden();

        $punjab = Region::query()->where('slug', 'punjab')->firstOrFail();

        $this->expectException(AuthorizationException::class);

        app(DestinationHierarchyService::class)->saveDestination($bookingOfficer, null, [
            'region_id' => $punjab->id,
            'name' => 'Unauthorized Dest',
            'slug' => 'unauthorized-dest',
        ]);
    }
}

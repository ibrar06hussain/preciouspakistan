<?php

namespace Tests\Feature\Phase6;

use App\Models\User;
use App\Modules\Rbac\Support\RbacCatalog;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleHotelsSeeder;
use Database\Seeders\SampleToursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelPublicVisibilityAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleToursSeeder::class);
        $this->seed(SampleHotelsSeeder::class);
    }

    public function test_tour_page_and_informational_hotel_page_show_tour_only_options_without_standalone_checkout(): void
    {
        $this->get('/tours/hunza-attabad-passu-scenic-expedition')
            ->assertOk()
            ->assertSee('Karakoram View Lodge (Sample)')
            ->assertSee('Hunza Heritage Terrace Retreat (Sample)')
            ->assertSee('External Verification Required');

        $this->get('/hotels/karakoram-view-lodge-hunza-sample')
            ->assertOk()
            ->assertSee('Offered Only Within Precious Pakistan Tours')
            ->assertSee('Standalone hotel bookings are not available')
            ->assertSee('Hunza, Attabad &amp; Passu Scenic Expedition', false);

        // Confirm no standalone hotel checkout route exists.
        $this->get('/hotels/karakoram-view-lodge-hunza-sample/book')->assertNotFound();
    }

    public function test_hotel_manager_can_access_hotels_admin_while_finance_officer_is_forbidden(): void
    {
        $hotelManager = User::factory()->staff()->create();
        $hotelManager->assignRole(RbacCatalog::ROLE_HOTEL_MANAGER);

        $finance = User::factory()->staff()->create();
        $finance->assignRole(RbacCatalog::ROLE_FINANCE_OFFICER);

        $this->actingAs($hotelManager)->get('/admin/hotels')->assertOk();
        $this->actingAs($hotelManager)->get('/admin/hotel-verifications')->assertOk();

        $this->actingAs($finance)->get('/admin/hotels')->assertForbidden();
    }
}

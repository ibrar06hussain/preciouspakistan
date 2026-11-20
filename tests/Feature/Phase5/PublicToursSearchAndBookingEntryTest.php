<?php

namespace Tests\Feature\Phase5;

use App\Modules\Tours\Models\Tour;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\SampleToursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicToursSearchAndBookingEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleToursSeeder::class);
    }

    public function test_public_tours_catalog_filters_by_category_region_nomad_and_displays_request_quote_never_zero(): void
    {
        $this->get('/tours')
            ->assertOk()
            ->assertSee('Hunza, Attabad &amp; Passu Scenic Expedition', false)
            ->assertSee('Request Quote')
            ->assertDontSee('$0');

        $this->get('/tours?category=trekking')
            ->assertOk()
            ->assertSee('Skardu, Shigar Fort &amp; Deosai Highland Trek', false)
            ->assertDontSee('/tours/swat-malam-jabba-couples-retreat');

        $this->get('/tours?nomad=1')
            ->assertOk()
            ->assertSee('Hunza, Attabad &amp; Passu Scenic Expedition', false)
            ->assertDontSee('/tours/swat-malam-jabba-couples-retreat');

        // Destination page now automatically lists live related published tours.
        $this->get('/destinations/hunza')
            ->assertOk()
            ->assertSee('Hunza, Attabad &amp; Passu Scenic Expedition', false);
    }

    public function test_booking_entry_enforces_guest_caps_lead_time_and_departure_capacity(): void
    {
        $tour = Tour::query()->where('slug', 'hunza-attabad-passu-scenic-expedition')->firstOrFail();

        // Guest count below min_guests (2) must fail.
        $this->from($tour->publicUrl())
            ->post(route('tours.booking-entry', ['slug' => $tour->slug]), [
                'start_date' => now()->addDays(15)->toDateString(),
                'guests' => 1,
                'currency' => 'PKR',
            ])
            ->assertSessionHasErrors('booking_entry');

        // Start date violating booking_lead_days (3) must fail.
        $this->from($tour->publicUrl())
            ->post(route('tours.booking-entry', ['slug' => $tour->slug]), [
                'start_date' => now()->toDateString(),
                'guests' => 3,
                'currency' => 'PKR',
            ])
            ->assertSessionHasErrors('booking_entry');

        // Valid request succeeds and stores validated entry in session.
        $this->from($tour->publicUrl())
            ->post(route('tours.booking-entry', ['slug' => $tour->slug]), [
                'start_date' => now()->addDays(15)->toDateString(),
                'guests' => 4,
                'currency' => 'USD',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('booking_entry_status')
            ->assertSessionHas('precious.booking_entry.guests', 4)
            ->assertSessionHas('precious.booking_entry.currency', 'USD');
    }
}

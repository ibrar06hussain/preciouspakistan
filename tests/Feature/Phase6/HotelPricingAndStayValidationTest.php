<?php

namespace Tests\Feature\Phase6;

use App\Models\User;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelRoomRate;
use App\Modules\Hotels\Services\HotelCatalogService;
use App\Modules\Hotels\Services\HotelPricingService;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Modules\Tours\Models\Tour;
use Carbon\CarbonImmutable;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleHotelsSeeder;
use Database\Seeders\SampleToursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class HotelPricingAndStayValidationTest extends TestCase
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

    public function test_unrelated_hotel_not_attached_to_tour_stay_is_rejected(): void
    {
        $hunzaTour = Tour::query()->where('slug', 'hunza-attabad-passu-scenic-expedition')->with('stays.destination')->firstOrFail();
        $hunzaStay = $hunzaTour->stays->first(fn ($s): bool => $s->destination?->slug === 'hunza');
        $skarduHotel = Hotel::query()->where('slug', 'skardu-indus-vista-chalet-sample')->with('roomTypes')->firstOrFail();

        $this->expectException(InvalidArgumentException::class);

        app(HotelPricingService::class)->quoteStaySelection(
            tour: $hunzaTour,
            stay: $hunzaStay,
            hotel: $skarduHotel,
            roomType: $skarduHotel->roomTypes->first(),
            checkInDate: CarbonImmutable::create(now()->year, 5, 30),
            roomsCount: 1,
            guestsCount: 2,
            currency: 'PKR',
        );
    }

    public function test_impossible_occupancy_is_rejected(): void
    {
        $hunzaTour = Tour::query()->where('slug', 'hunza-attabad-passu-scenic-expedition')->with('stays.destination')->firstOrFail();
        $hunzaStay = $hunzaTour->stays->first(fn ($s): bool => $s->destination?->slug === 'hunza');
        $hotel = Hotel::query()->where('slug', 'karakoram-view-lodge-hunza-sample')->with('roomTypes')->firstOrFail();
        $stdTwin = $hotel->roomTypes->firstWhere('code', 'STD_TWIN'); // max_occupancy = 2

        $this->expectException(InvalidArgumentException::class);

        app(HotelPricingService::class)->quoteStaySelection(
            tour: $hunzaTour,
            stay: $hunzaStay,
            hotel: $hotel,
            roomType: $stdTwin,
            checkInDate: CarbonImmutable::create(now()->year, 5, 30),
            roomsCount: 1,
            guestsCount: 3, // Impossible for 1 room with max_occupancy 2
            currency: 'PKR',
        );
    }

    public function test_missing_nightly_rate_for_any_date_in_stay_is_rejected(): void
    {
        $hunzaTour = Tour::query()->where('slug', 'hunza-attabad-passu-scenic-expedition')->with('stays.destination')->firstOrFail();
        $hunzaStay = $hunzaTour->stays->first(fn ($s): bool => $s->destination?->slug === 'hunza');
        $hotel = Hotel::query()->where('slug', 'karakoram-view-lodge-hunza-sample')->with('roomTypes')->firstOrFail();
        $stdTwin = $hotel->roomTypes->firstWhere('code', 'STD_TWIN');

        $this->expectException(InvalidArgumentException::class);

        app(HotelPricingService::class)->quoteStaySelection(
            tour: $hunzaTour,
            stay: $hunzaStay,
            hotel: $hotel,
            roomType: $stdTwin,
            checkInDate: CarbonImmutable::create(now()->year + 5, 7, 1), // No rates defined 5 years out
            roomsCount: 1,
            guestsCount: 2,
            currency: 'PKR',
        );
    }

    public function test_cross_season_stay_prorates_each_night_accurately_using_decimal_math(): void
    {
        $year = now()->year;
        $hunzaTour = Tour::query()->where('slug', 'hunza-attabad-passu-scenic-expedition')->with('stays.destination')->firstOrFail();
        $hunzaStay = $hunzaTour->stays->first(fn ($s): bool => $s->destination?->slug === 'hunza'); // 2 nights
        $hotel = Hotel::query()->where('slug', 'karakoram-view-lodge-hunza-sample')->with('roomTypes')->firstOrFail();
        $stdTwin = $hotel->roomTypes->firstWhere('code', 'STD_TWIN');

        // May 31 = Standard (18,000 PKR), June 1 = Peak Summer (24,000 PKR) => 1 room = 42,000; 2 rooms = 84,000.
        $quote = app(HotelPricingService::class)->quoteStaySelection(
            tour: $hunzaTour,
            stay: $hunzaStay,
            hotel: $hotel,
            roomType: $stdTwin,
            checkInDate: CarbonImmutable::create($year, 5, 31),
            roomsCount: 2,
            guestsCount: 4,
            currency: 'PKR',
        );

        $this->assertSame('84000.00', $quote['selected_full_amount']);
        $this->assertSame('84000.00', $quote['chargeable_amount']);
        $this->assertCount(2, $quote['nightly_breakdown']);
        $this->assertSame('18000.00', $quote['nightly_breakdown'][0]['unit_nightly_rate']);
        $this->assertSame('24000.00', $quote['nightly_breakdown'][1]['unit_nightly_rate']);
    }

    public function test_base_included_lodging_charges_only_upgrade_difference_never_duplicate_full_rate(): void
    {
        $year = now()->year;
        // Swat couples retreat has includes_base_accommodation = true, 2 nights.
        $swatTour = Tour::query()->where('slug', 'swat-malam-jabba-couples-retreat')->with('stays.destination')->firstOrFail();
        $swatStay = $swatTour->stays->first();
        $hotel = Hotel::query()->where('slug', 'swat-pine-valley-chalet-sample')->with('roomTypes')->firstOrFail();
        $stdRoom = $hotel->roomTypes->firstWhere('code', 'STD_RIVER');     // 15,000 PKR/night => 30,000 for 2 nights
        $luxSuite = $hotel->roomTypes->firstWhere('code', 'LUX_HONEYMOON'); // 28,000 PKR/night => 56,000 for 2 nights

        $pricing = app(HotelPricingService::class);

        // Selecting included default room charges 0.00 extra.
        $defaultQuote = $pricing->quoteStaySelection($swatTour, $swatStay, $hotel, $stdRoom, CarbonImmutable::create($year, 4, 10), 1, 2, 'PKR');
        $this->assertSame('30000.00', $defaultQuote['selected_full_amount']);
        $this->assertSame('30000.00', $defaultQuote['base_included_credit']);
        $this->assertSame('0.00', $defaultQuote['chargeable_amount']);

        // Upgrading to Honeymoon Suite charges ONLY the difference: 56,000 - 30,000 = 26,000.00!
        $upgradeQuote = $pricing->quoteStaySelection($swatTour, $swatStay, $hotel, $luxSuite, CarbonImmutable::create($year, 4, 10), 1, 2, 'PKR');
        $this->assertSame('56000.00', $upgradeQuote['selected_full_amount']);
        $this->assertSame('30000.00', $upgradeQuote['base_included_credit']);
        $this->assertSame('26000.00', $upgradeQuote['chargeable_amount']);
    }

    public function test_equal_priority_overlapping_room_rates_are_rejected_and_copy_to_next_year_works(): void
    {
        $manager = User::factory()->staff()->create();
        $manager->assignRole(RbacCatalog::ROLE_HOTEL_MANAGER);

        $hunza = Destination::query()->where('slug', 'hunza')->firstOrFail();
        $catalog = app(HotelCatalogService::class);

        try {
            $catalog->saveHotel($manager, null, [
                'name' => 'Overlap Hotel',
                'slug' => 'overlap-hotel',
                'destination_id' => $hunza->id,
                'room_types' => [
                    [
                        'name' => 'Double',
                        'code' => 'DBL',
                        'max_occupancy' => 2,
                        'rates' => [
                            ['rate_name' => 'A', 'rate_tier' => HotelRoomRate::TIER_SEASONAL_YEARLY, 'valid_from' => '2027-06-01', 'valid_to' => '2027-07-15', 'priority' => 20, 'nightly_rate_pkr' => 20000],
                            ['rate_name' => 'B', 'rate_tier' => HotelRoomRate::TIER_SEASONAL_YEARLY, 'valid_from' => '2027-07-01', 'valid_to' => '2027-08-01', 'priority' => 20, 'nightly_rate_pkr' => 25000],
                        ],
                    ],
                ],
            ]);
            $this->fail('Expected overlapping equal-priority rates to fail.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Overlapping equal-priority', $e->getMessage());
        }

        $hotel = Hotel::query()->where('slug', 'karakoram-view-lodge-hunza-sample')->firstOrFail();
        $copied = $catalog->copyRatesToYear($manager, $hotel, now()->year, now()->year + 2);
        $this->assertGreaterThan(0, $copied);
    }
}

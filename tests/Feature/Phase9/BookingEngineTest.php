<?php

namespace Tests\Feature\Phase9;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingWorkflowService;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Models\VehicleReservation;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelAvailabilityVerification;
use App\Modules\Hotels\Services\HotelVerificationService;
use App\Modules\Identity\Enums\AccountType;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Modules\Tours\Models\Tour;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleFleetSeeder;
use Database\Seeders\SampleHotelsSeeder;
use Database\Seeders\SampleNomadsSeeder;
use Database\Seeders\SampleToursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BookingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(PakistanDestinationsSeeder::class);
        $this->seed(SampleToursSeeder::class);
        $this->seed(SampleHotelsSeeder::class);
        $this->seed(SampleFleetSeeder::class);
        $this->seed(SampleNomadsSeeder::class);
    }

    public function test_idempotent_booking_submission_prevents_duplicates_and_preserves_immutable_snapshot_after_catalog_price_edit(): void
    {
        $tour = Tour::query()->where('code', 'PP-GB-HUNZA-05')->with('stays.stayHotels.hotel.roomTypes')->firstOrFail();
        $stay = $tour->stays->first(fn ($s) => $s->stayHotels->isNotEmpty());
        $this->assertNotNull($stay);
        $hotel = $stay->stayHotels->firstOrFail()->hotel;
        $roomType = $hotel->roomTypes->firstOrFail();
        $vehicle = Vehicle::query()->where('code', 'FLT-PRADO-01')->firstOrFail();

        $workflow = app(BookingWorkflowService::class);
        $year = now()->year;
        $payload = [
            'start_date' => "{$year}-06-05",
            'guests_count' => 2,
            'currency' => 'PKR',
            'vehicle' => ['vehicle_id' => $vehicle->id, 'fuel_mode' => 'with_fuel'],
            'hotel_stays' => [[
                'tour_stay_id' => $stay->id,
                'hotel_id' => $hotel->id,
                'room_type_id' => $roomType->id,
                'rooms_count' => 1,
            ]],
        ];

        $customerInput = [
            'customer_name' => 'Amina Khan',
            'customer_email' => 'amina@example.com',
            'customer_phone' => '+923001234567',
            'idempotency_key' => 'checkout-idem-001',
        ];

        $first = $workflow->submitBookingRequest($tour, $payload, $customerInput);
        $second = $workflow->submitBookingRequest($tour, $payload, $customerInput);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Booking::query()->count());
        $this->assertSame(Booking::STATUS_PENDING_VERIFICATION, $first->booking_status);
        $this->assertSame(Booking::PAYMENT_UNPAID, $first->payment_status);

        $originalTotal = (string) $first->total_amount;

        // Mutate tour base price AFTER booking submission — existing booking snapshot MUST remain immutable.
        $tour->forceFill(['base_price_pkr' => '999999.00'])->save();
        $this->assertSame($originalTotal, (string) $first->fresh()->total_amount);

        // Concurrent booking attempt for the same vehicle on overlapping dates MUST fail safely.
        $this->expectException(RuntimeException::class);
        $workflow->submitBookingRequest($tour, $payload, [
            'customer_name' => 'Other Guest',
            'customer_email' => 'other@example.com',
            'customer_phone' => '+923009876543',
            'idempotency_key' => 'checkout-idem-002',
        ]);
    }

    public function test_unverified_hotel_blocks_confirmation_and_verified_hotel_allows_idempotent_confirmation(): void
    {
        $officer = User::factory()->create(['account_type' => AccountType::Staff, 'is_active' => true]);
        $officer->assignRole(RbacCatalog::ROLE_BOOKING_OFFICER);

        $tour = Tour::query()->where('code', 'PP-GB-HUNZA-05')->with('stays.stayHotels.hotel.roomTypes')->firstOrFail();
        $stay = $tour->stays->first(fn ($s) => $s->stayHotels->isNotEmpty());
        $this->assertNotNull($stay);
        $hotel = $stay->stayHotels->firstOrFail()->hotel;
        $roomType = $hotel->roomTypes->firstOrFail();
        $vehicle = Vehicle::query()->where('code', 'FLT-PRADO-01')->firstOrFail();

        $workflow = app(BookingWorkflowService::class);
        $year = now()->year;
        $booking = $workflow->submitBookingRequest($tour, [
            'start_date' => "{$year}-06-15",
            'guests_count' => 2,
            'currency' => 'PKR',
            'vehicle' => ['vehicle_id' => $vehicle->id, 'fuel_mode' => 'without_fuel'],
            'hotel_stays' => [[
                'tour_stay_id' => $stay->id,
                'hotel_id' => $hotel->id,
                'room_type_id' => $roomType->id,
                'rooms_count' => 1,
            ]],
        ], [
            'customer_name' => 'Bilal Ahmed',
            'customer_email' => 'bilal@example.com',
            'customer_phone' => '+923005551111',
        ]);

        // Unverified hotel MUST block booking confirmation.
        try {
            $workflow->confirmBooking($officer, $booking);
            $this->fail('Expected unverified hotel to block confirmation.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('lacks confirmed external availability', $e->getMessage());
        }

        // Externally verify the hotel via phone.
        $verification = $booking->hotelVerifications->firstOrFail();
        app(HotelVerificationService::class)->recordExternalVerification(
            officer: $officer,
            verification: $verification,
            status: HotelAvailabilityVerification::STATUS_VERIFIED_AVAILABLE,
            method: 'phone',
            externalReference: 'Spoke with front desk manager Tariq',
        );

        // Confirm twice — second call is idempotent and keeps payment_status independent (still unpaid!).
        $confirmed1 = $workflow->confirmBooking($officer, $booking);
        $confirmed2 = $workflow->confirmBooking($officer, $confirmed1);

        $this->assertSame(Booking::STATUS_CONFIRMED, $confirmed2->booking_status);
        $this->assertSame(Booking::PAYMENT_UNPAID, $confirmed2->payment_status);
        $this->assertSame(VehicleReservation::STATUS_CONFIRMED, $confirmed2->vehicleReservation->status);
    }

    public function test_hotel_unavailable_after_payment_marks_refund_required_and_releases_vehicle_hold(): void
    {
        $officer = User::factory()->create(['account_type' => AccountType::Staff, 'is_active' => true]);
        $officer->assignRole(RbacCatalog::ROLE_BOOKING_OFFICER);

        $tour = Tour::query()->where('code', 'PP-GB-HUNZA-05')->firstOrFail();
        $vehicle = Vehicle::query()->where('code', 'FLT-PRADO-01')->firstOrFail();

        $workflow = app(BookingWorkflowService::class);
        $year = now()->year;
        $booking = $workflow->submitBookingRequest($tour, [
            'start_date' => "{$year}-07-02",
            'guests_count' => 2,
            'currency' => 'PKR',
            'vehicle' => ['vehicle_id' => $vehicle->id, 'fuel_mode' => 'with_fuel'],
        ], [
            'customer_name' => 'Zara Tariq',
            'customer_email' => 'zara@example.com',
            'customer_phone' => '+923215551234',
        ]);

        // Simulate partial/full payment recorded before external accommodation fallback failed.
        $booking->forceFill(['payment_status' => Booking::PAYMENT_PAID])->save();

        $refundedState = $workflow->cancelBooking($officer, $booking, 'Hotel sold out on peak dates; no acceptable alternative', true);

        $this->assertSame(Booking::STATUS_REFUND_REQUIRED, $refundedState->booking_status);
        $this->assertSame(Booking::PAYMENT_REFUND_PENDING, $refundedState->payment_status);
        $this->assertSame(VehicleReservation::STATUS_RELEASED, $refundedState->vehicleReservation->fresh()->status);
    }

    public function test_public_booking_request_form_creates_booking_and_redirects_to_confirmation(): void
    {
        $tour = Tour::query()->where('code', 'PP-GB-HUNZA-05')->firstOrFail();

        $this->get(route('bookings.create', ['slug' => $tour->slug]))
            ->assertOk()
            ->assertSee('Request Tour Booking');

        $response = $this->post(route('bookings.store', ['slug' => $tour->slug]), [
            'idempotency_key' => 'web-form-idem-001',
            'start_date' => now()->addDays(20)->toDateString(),
            'guests_count' => 2,
            'currency' => 'PKR',
            'customer_name' => 'Hassan Raza',
            'customer_email' => 'hassan@example.com',
            'customer_phone' => '+923001112233',
        ]);

        $booking = Booking::query()->where('idempotency_key', 'web-form-idem-001')->firstOrFail();
        $response->assertRedirect(route('bookings.confirmation', ['reference' => $booking->booking_reference]));
        $this->get(route('bookings.confirmation', ['reference' => $booking->booking_reference]))
            ->assertOk()
            ->assertSee($booking->booking_reference);
    }
}

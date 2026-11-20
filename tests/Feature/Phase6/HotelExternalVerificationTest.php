<?php

namespace Tests\Feature\Phase6;

use App\Models\User;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelAvailabilityVerification;
use App\Modules\Hotels\Services\HotelVerificationService;
use App\Modules\Rbac\Support\RbacCatalog;
use App\Modules\Tours\Models\Tour;
use Carbon\CarbonImmutable;
use Database\Seeders\PakistanDestinationsSeeder;
use Database\Seeders\RbacSeeder;
use Database\Seeders\SampleHotelsSeeder;
use Database\Seeders\SampleToursSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class HotelExternalVerificationTest extends TestCase
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

    public function test_booking_confirmation_fails_without_external_hotel_verification_and_succeeds_after_logging(): void
    {
        $officer = User::factory()->staff()->create();
        $officer->assignRole(RbacCatalog::ROLE_BOOKING_OFFICER);

        $hunzaTour = Tour::query()->where('slug', 'hunza-attabad-passu-scenic-expedition')->with('stays.destination')->firstOrFail();
        $hunzaStay = $hunzaTour->stays->first(fn ($s): bool => $s->destination?->slug === 'hunza');
        $hotel = Hotel::query()->where('slug', 'karakoram-view-lodge-hunza-sample')->with('roomTypes')->firstOrFail();

        $service = app(HotelVerificationService::class);
        $verification = $service->requestVerification(
            tour: $hunzaTour,
            stay: $hunzaStay,
            requestedHotel: $hotel,
            requestedRoomType: $hotel->roomTypes->first(),
            checkInDate: CarbonImmutable::create(now()->year, 6, 10),
            roomsCount: 1,
            guestsCount: 2,
        );

        // Unverified pending record MUST block confirmation.
        try {
            $service->assertExternallyVerifiedForConfirmation($verification);
            $this->fail('Expected unverified hotel to block confirmation.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('lacks confirmed external availability', $e->getMessage());
        }

        // Booking officer records external WhatsApp verification.
        $verified = $service->recordExternalVerification(
            officer: $officer,
            verification: $verification,
            status: HotelAvailabilityVerification::STATUS_VERIFIED_AVAILABLE,
            method: 'whatsapp',
            externalReference: 'Manager Ali +92300-WA-8821',
            officerNotes: 'Confirmed 1 twin room held until 6 PM PKT.',
        );

        $this->assertTrue($verified->isExternallyConfirmed());
        $service->assertExternallyVerifiedForConfirmation($verified);
        $this->assertDatabaseHas('audit_logs', ['event' => 'hotel.external_verification_recorded', 'actor_id' => $officer->id]);
    }

    public function test_alternative_hotel_selection_flow_validates_stay_eligibility(): void
    {
        $officer = User::factory()->staff()->create();
        $officer->assignRole(RbacCatalog::ROLE_BOOKING_OFFICER);

        $hunzaTour = Tour::query()->where('slug', 'hunza-attabad-passu-scenic-expedition')->with('stays.destination')->firstOrFail();
        $hunzaStay = $hunzaTour->stays->first(fn ($s): bool => $s->destination?->slug === 'hunza');
        $requestedHotel = Hotel::query()->where('slug', 'karakoram-view-lodge-hunza-sample')->with('roomTypes')->firstOrFail();
        $eligibleAlt = Hotel::query()->where('slug', 'hunza-heritage-palace-retreat-sample')->with('roomTypes')->firstOrFail();
        $unrelatedHotel = Hotel::query()->where('slug', 'skardu-indus-vista-chalet-sample')->with('roomTypes')->firstOrFail();

        $service = app(HotelVerificationService::class);
        $verification = $service->requestVerification(
            tour: $hunzaTour,
            stay: $hunzaStay,
            requestedHotel: $requestedHotel,
            requestedRoomType: $requestedHotel->roomTypes->first(),
            checkInDate: CarbonImmutable::create(now()->year, 6, 10),
            roomsCount: 1,
            guestsCount: 2,
        );

        // Proposing an unrelated hotel from Skardu MUST fail.
        try {
            $service->recordExternalVerification(
                officer: $officer,
                verification: $verification,
                status: HotelAvailabilityVerification::STATUS_CONFIRMED_ALTERNATIVE,
                method: 'phone',
                externalReference: 'Call #441',
                alternativeHotel: $unrelatedHotel,
                alternativeRoomType: $unrelatedHotel->roomTypes->first(),
            );
            $this->fail('Expected unrelated alternative hotel to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('eligible option for the same tour overnight stay', $e->getMessage());
        }

        // Proposing & confirming an eligible alternative in Hunza succeeds.
        $confirmedAlt = $service->recordExternalVerification(
            officer: $officer,
            verification: $verification,
            status: HotelAvailabilityVerification::STATUS_CONFIRMED_ALTERNATIVE,
            method: 'phone',
            externalReference: 'Front Desk Karim #991',
            alternativeHotel: $eligibleAlt,
            alternativeRoomType: $eligibleAlt->roomTypes->first(),
        );

        $this->assertTrue($confirmedAlt->isExternallyConfirmed());
        $this->assertSame($eligibleAlt->id, $confirmedAlt->confirmed_hotel_id);
    }
}

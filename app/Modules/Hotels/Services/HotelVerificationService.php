<?php

namespace App\Modules\Hotels\Services;

use App\Models\User;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelAvailabilityVerification;
use App\Modules\Hotels\Models\HotelRoomType;
use App\Modules\Hotels\Models\TourStayHotel;
use App\Modules\Rbac\Services\AuditLogger;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Models\TourStay;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class HotelVerificationService
{
    public function __construct(
        private readonly AuditLogger $audit,
    ) {}

    public function requestVerification(
        Tour $tour,
        TourStay $stay,
        Hotel $requestedHotel,
        HotelRoomType $requestedRoomType,
        CarbonInterface $checkInDate,
        int $roomsCount,
        int $guestsCount,
        ?int $bookingId = null,
    ): HotelAvailabilityVerification {
        $isEligible = TourStayHotel::query()
            ->where('tour_stay_id', $stay->id)
            ->where('hotel_id', $requestedHotel->id)
            ->where('is_active', true)
            ->exists();

        if (! $isEligible) {
            throw new InvalidArgumentException("Hotel [{$requestedHotel->name}] is not attached to this tour overnight stay.");
        }

        if ($requestedRoomType->hotel_id !== $requestedHotel->id) {
            throw new InvalidArgumentException('Requested room type does not belong to the requested hotel.');
        }

        if (($roomsCount * $requestedRoomType->max_occupancy) < $guestsCount) {
            throw new InvalidArgumentException('Requested rooms cannot accommodate the guest count.');
        }

        return HotelAvailabilityVerification::query()->create([
            'reference_code' => 'HV-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
            'tour_id' => $tour->id,
            'tour_stay_id' => $stay->id,
            'booking_id' => $bookingId,
            'requested_hotel_id' => $requestedHotel->id,
            'requested_room_type_id' => $requestedRoomType->id,
            'check_in_date' => $checkInDate->toDateString(),
            'nights_count' => $stay->nights_count,
            'rooms_count' => $roomsCount,
            'guests_count' => $guestsCount,
            'status' => HotelAvailabilityVerification::STATUS_PENDING,
        ]);
    }

    /**
     * Log external verification outcome (phone/email/WhatsApp/in-person) by an authorized booking officer.
     *
     * @throws AuthorizationException
     */
    public function recordExternalVerification(
        User $officer,
        HotelAvailabilityVerification $verification,
        string $status,
        string $method,
        string $externalReference,
        ?string $officerNotes = null,
        ?Hotel $alternativeHotel = null,
        ?HotelRoomType $alternativeRoomType = null,
    ): HotelAvailabilityVerification {
        if (! ($officer->can('bookings.confirm') || $officer->can('hotels.manage'))) {
            throw new AuthorizationException('You do not have permission to record external hotel verifications.');
        }

        $allowedStatuses = [
            HotelAvailabilityVerification::STATUS_VERIFIED_AVAILABLE,
            HotelAvailabilityVerification::STATUS_UNAVAILABLE_ALTERNATIVE_PROPOSED,
            HotelAvailabilityVerification::STATUS_CONFIRMED_ALTERNATIVE,
            HotelAvailabilityVerification::STATUS_REJECTED_UNAVAILABLE,
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            throw new InvalidArgumentException("Invalid verification status [{$status}].");
        }

        if (! array_key_exists($method, HotelAvailabilityVerification::METHODS)) {
            throw new InvalidArgumentException("Invalid external verification method [{$method}].");
        }

        $externalReference = trim($externalReference);
        if ($externalReference === '') {
            throw new InvalidArgumentException('An external verification reference (contact person, message ID, or email subject) is required.');
        }

        $confirmedHotelId = null;
        $confirmedRoomTypeId = null;

        if ($status === HotelAvailabilityVerification::STATUS_VERIFIED_AVAILABLE) {
            $confirmedHotelId = $verification->requested_hotel_id;
            $confirmedRoomTypeId = $verification->requested_room_type_id;
        } elseif (in_array($status, [
            HotelAvailabilityVerification::STATUS_UNAVAILABLE_ALTERNATIVE_PROPOSED,
            HotelAvailabilityVerification::STATUS_CONFIRMED_ALTERNATIVE,
        ], true)) {
            if ($alternativeHotel === null || $alternativeRoomType === null) {
                throw new InvalidArgumentException('Alternative hotel and room type are required when proposing or confirming an alternative.');
            }

            $eligibleAlt = TourStayHotel::query()
                ->where('tour_stay_id', $verification->tour_stay_id)
                ->where('hotel_id', $alternativeHotel->id)
                ->where('is_active', true)
                ->exists();

            if (! $eligibleAlt) {
                throw new InvalidArgumentException('Alternative hotel must be an eligible option for the same tour overnight stay.');
            }

            if ($alternativeRoomType->hotel_id !== $alternativeHotel->id) {
                throw new InvalidArgumentException('Alternative room type does not belong to the alternative hotel.');
            }

            if (($verification->rooms_count * $alternativeRoomType->max_occupancy) < $verification->guests_count) {
                throw new InvalidArgumentException('Alternative room type cannot accommodate the booking guest count.');
            }

            $confirmedHotelId = $alternativeHotel->id;
            $confirmedRoomTypeId = $alternativeRoomType->id;
        }

        return DB::transaction(function () use (
            $officer,
            $verification,
            $status,
            $method,
            $externalReference,
            $officerNotes,
            $confirmedHotelId,
            $confirmedRoomTypeId,
        ): HotelAvailabilityVerification {
            $locked = HotelAvailabilityVerification::query()->lockForUpdate()->findOrFail($verification->id);
            $beforeStatus = $locked->status;

            $locked->forceFill([
                'status' => $status,
                'verification_method' => $method,
                'external_reference' => mb_substr($externalReference, 0, 120),
                'confirmed_hotel_id' => $confirmedHotelId,
                'confirmed_room_type_id' => $confirmedRoomTypeId,
                'verified_by' => $officer->id,
                'verified_at' => now(),
                'officer_notes' => $officerNotes,
            ])->save();

            $this->audit->record(
                actor: $officer,
                event: 'hotel.external_verification_recorded',
                summary: "Hotel verification [{$locked->reference_code}] marked {$status} via {$method}",
                auditable: $locked,
                beforeState: ['status' => $beforeStatus],
                afterState: [
                    'status' => $status,
                    'method' => $method,
                    'external_reference' => $locked->external_reference,
                    'confirmed_hotel_id' => $confirmedHotelId,
                ],
            );

            return $locked->fresh();
        });
    }

    /**
     * Authoritative gate invoked before final booking confirmation.
     * Fails safely whenever external availability has NOT been manually verified and confirmed.
     */
    public function assertExternallyVerifiedForConfirmation(HotelAvailabilityVerification $verification): void
    {
        if (! $verification->isExternallyConfirmed()) {
            throw new RuntimeException(
                "Booking cannot be confirmed: hotel verification [{$verification->reference_code}] has status [{$verification->status}] and lacks confirmed external availability."
            );
        }
    }
}

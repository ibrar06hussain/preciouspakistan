<?php

namespace App\Modules\Booking\Services;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingEvent;
use App\Modules\Booking\Models\BookingItem;
use App\Modules\Booking\Models\BookingQuote;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Services\VehicleAvailabilityService;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelRoomType;
use App\Modules\Hotels\Services\HotelVerificationService;
use App\Modules\Rbac\Services\AuditLogger;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Models\TourStay;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * Authoritative transactional booking workflow service.
 * Enforces idempotency, vehicle reservation locking, mandatory external hotel verification before confirmation,
 * immutable pricing snapshots, and strict separation of booking_status vs payment_status.
 */
final class BookingWorkflowService
{
    public function __construct(
        private readonly CentralPricingService $pricing,
        private readonly VehicleAvailabilityService $vehicleAvailability,
        private readonly HotelVerificationService $hotelVerification,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Submit a customer booking request from an authoritative quote calculation.
     * Re-submitting the same idempotency key returns the original booking cleanly (zero duplicate holds/rows).
     *
     * @param  array<string, mixed>  $pricingInput
     * @param  array{customer_name: string, customer_email: string, customer_phone: string, customer_notes?: ?string, idempotency_key?: ?string}  $customerInput
     */
    public function submitBookingRequest(
        Tour $tour,
        array $pricingInput,
        array $customerInput,
        ?User $customer = null,
    ): Booking {
        $idempotencyKey = isset($customerInput['idempotency_key']) && trim((string) $customerInput['idempotency_key']) !== ''
            ? mb_substr(trim((string) $customerInput['idempotency_key']), 0, 120)
            : null;

        if ($idempotencyKey !== null) {
            $existing = Booking::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing->load(['items', 'vehicleReservation', 'hotelVerifications']);
            }
        }

        $name = trim((string) ($customerInput['customer_name'] ?? ''));
        $email = strtolower(trim((string) ($customerInput['customer_email'] ?? '')));
        $phone = trim((string) ($customerInput['customer_phone'] ?? ''));

        if ($name === '' || $email === '' || $phone === '') {
            throw new InvalidArgumentException('Customer name, email, and phone are required to submit a booking request.');
        }

        return DB::transaction(function () use ($tour, $pricingInput, $customerInput, $customer, $idempotencyKey, $name, $email, $phone): Booking {
            if ($idempotencyKey !== null) {
                $existingLocked = Booking::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($existingLocked !== null) {
                    return $existingLocked->load(['items', 'vehicleReservation', 'hotelVerifications']);
                }
            }

            // Compute authoritative server-side quote (ignores any client-manipulated pricing).
            $quote = $this->pricing->createQuote($tour, $pricingInput, $customer, true);
            $snapshot = $quote->snapshot;

            $hasHotelSelections = ! empty($snapshot['hotel_quotes']);
            $initialStatus = $hasHotelSelections
                ? Booking::STATUS_PENDING_VERIFICATION
                : Booking::STATUS_REQUESTED;

            $booking = Booking::query()->create([
                'booking_reference' => 'PP-BK-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'idempotency_key' => $idempotencyKey,
                'quote_id' => $quote->id,
                'user_id' => $customer?->id,
                'tour_id' => $tour->id,
                'departure_id' => ! empty($pricingInput['departure_id']) ? (int) $pricingInput['departure_id'] : null,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'start_date' => $quote->start_date,
                'end_date' => $quote->end_date,
                'guests_count' => $quote->guests_count,
                'currency' => $quote->currency,
                'booking_status' => $initialStatus,
                'payment_status' => Booking::PAYMENT_UNPAID,
                'subtotal' => $quote->subtotal,
                'discount_amount' => $quote->discount_amount,
                'tax_rate_percent' => $quote->tax_rate_percent,
                'tax_amount' => $quote->tax_amount,
                'total_amount' => $quote->total_amount,
                'pricing_snapshot' => $snapshot,
                'customer_notes' => isset($customerInput['customer_notes']) ? trim((string) $customerInput['customer_notes']) : null,
                'is_sample_content' => (bool) $tour->is_sample_content,
            ]);

            foreach ($snapshot['lines'] ?? [] as $line) {
                BookingItem::query()->create([
                    'booking_id' => $booking->id,
                    'item_type' => $line['item_type'],
                    'reference_type' => $line['reference_type'] ?? null,
                    'reference_id' => $line['reference_id'] ?? null,
                    'label' => $line['label'],
                    'charge_unit' => $line['charge_unit'],
                    'quantity' => (int) $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                    'is_included' => (bool) ($line['is_included'] ?? false),
                    'meta' => $line['meta'] ?? null,
                ]);
            }

            // Place concurrency-locked vehicle hold if a company vehicle was selected.
            if (! empty($snapshot['vehicle_quote'])) {
                $vq = $snapshot['vehicle_quote'];
                $vehicle = Vehicle::query()->findOrFail((int) $vq['vehicle_id']);

                $reservation = $this->vehicleAvailability->createHold(
                    vehicle: $vehicle,
                    startDate: CarbonImmutable::parse($vq['start_date']),
                    endDate: CarbonImmutable::parse($vq['end_date']),
                    fuelMode: $vq['fuel_mode'],
                    currency: $vq['currency'],
                    quotedTotal: $vq['total_amount'],
                    tour: $tour,
                    bookingId: $booking->id,
                    actor: $customer,
                    holdMinutes: (int) config('precious.booking.vehicle_hold_minutes', 45),
                );

                $booking->forceFill(['vehicle_reservation_id' => $reservation->id])->save();
            }

            // Create external hotel availability verification requests for every selected tour hotel stay.
            foreach ($snapshot['hotel_quotes'] ?? [] as $hq) {
                $stay = TourStay::query()->findOrFail((int) $hq['tour_stay_id']);
                $hotel = Hotel::query()->findOrFail((int) $hq['hotel_id']);
                $roomType = HotelRoomType::query()->findOrFail((int) $hq['room_type_id']);

                $this->hotelVerification->requestVerification(
                    tour: $tour,
                    stay: $stay,
                    requestedHotel: $hotel,
                    requestedRoomType: $roomType,
                    checkInDate: CarbonImmutable::parse($hq['check_in_date']),
                    roomsCount: (int) $hq['rooms_count'],
                    guestsCount: (int) $hq['guests_count'],
                    bookingId: $booking->id,
                );
            }

            $quote->forceFill(['status' => BookingQuote::STATUS_CONVERTED])->save();

            BookingEvent::query()->create([
                'booking_id' => $booking->id,
                'actor_id' => $customer?->id,
                'event_type' => 'booking.requested',
                'summary' => "Booking request [{$booking->booking_reference}] submitted ({$booking->currency} {$booking->total_amount})",
                'payload' => ['booking_status' => $booking->booking_status, 'payment_status' => $booking->payment_status],
            ]);

            return $booking->fresh(['items', 'vehicleReservation', 'hotelVerifications']);
        });
    }

    /**
     * Officer adjustment to discount/notes before final invoice lock.
     *
     * @throws AuthorizationException
     */
    public function adjustDiscount(User $officer, Booking $booking, string $discountAmount, string $reason): Booking
    {
        if (! $officer->can('bookings.update')) {
            throw new AuthorizationException('You do not have permission to adjust bookings.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Adjustment reason is required for audit trail.');
        }

        if (! is_numeric($discountAmount) || bccomp($discountAmount, '0.00', 2) < 0) {
            throw new InvalidArgumentException('Discount must be a non-negative decimal amount.');
        }

        return DB::transaction(function () use ($officer, $booking, $discountAmount, $reason): Booking {
            $locked = Booking::query()->lockForUpdate()->findOrFail($booking->id);

            $subtotal = (string) $locked->subtotal;
            $discount = number_format((float) $discountAmount, 2, '.', '');
            if (bccomp($discount, $subtotal, 2) > 0) {
                $discount = $subtotal;
            }

            $taxable = bcsub($subtotal, $discount, 2);
            $taxRate = (string) $locked->tax_rate_percent;
            $taxAmount = bccomp($taxRate, '0.000', 3) > 0
                ? bcdiv(bcmul($taxable, $taxRate, 4), '100', 2)
                : '0.00';
            $total = bcadd($taxable, $taxAmount, 2);

            $beforeTotal = (string) $locked->total_amount;
            $snapshot = $locked->pricing_snapshot;
            $snapshot['totals'] = [
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_rate_percent' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
            ];

            $locked->forceFill([
                'discount_amount' => $discount,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'pricing_snapshot' => $snapshot,
            ])->save();

            BookingEvent::query()->create([
                'booking_id' => $locked->id,
                'actor_id' => $officer->id,
                'event_type' => 'booking.quote_adjusted',
                'summary' => "Officer adjusted discount to {$locked->currency} {$discount} ({$reason})",
                'payload' => ['before_total' => $beforeTotal, 'after_total' => $total, 'reason' => $reason],
            ]);

            $this->audit->record(
                actor: $officer,
                event: 'booking.quote_adjusted',
                summary: "Adjusted booking [{$locked->booking_reference}] total {$beforeTotal} -> {$total}",
                auditable: $locked,
                beforeState: ['total_amount' => $beforeTotal],
                afterState: ['total_amount' => $total, 'discount_amount' => $discount, 'reason' => $reason],
            );

            return $locked->fresh();
        });
    }

    /**
     * Authoritative booking confirmation by a Booking Officer.
     * - Idempotent if already confirmed.
     * - Strictly verifies ALL attached hotel availability checks are externally confirmed!
     * - Confirms the linked vehicle reservation inside the same DB transaction.
     * - Never conflates booking_status with payment_status.
     *
     * @throws AuthorizationException
     */
    public function confirmBooking(User $officer, Booking $booking, ?string $officerNotes = null): Booking
    {
        if (! $officer->can('bookings.confirm')) {
            throw new AuthorizationException('You do not have permission to confirm bookings.');
        }

        return DB::transaction(function () use ($officer, $booking, $officerNotes): Booking {
            $locked = Booking::query()->lockForUpdate()->with(['hotelVerifications', 'vehicleReservation'])->findOrFail($booking->id);

            if ($locked->booking_status === Booking::STATUS_CONFIRMED) {
                return $locked;
            }

            if (in_array($locked->booking_status, [Booking::STATUS_CANCELLED, Booking::STATUS_REFUND_REQUIRED], true)) {
                throw new RuntimeException("Cannot confirm booking [{$locked->booking_reference}] in status [{$locked->booking_status}].");
            }

            // Mandatory gate: every requested tour-only hotel MUST be externally verified first.
            foreach ($locked->hotelVerifications as $verification) {
                $this->hotelVerification->assertExternallyVerifiedForConfirmation($verification);
            }

            // Confirm vehicle reservation lock if present.
            if ($locked->vehicleReservation !== null) {
                $this->vehicleAvailability->confirmReservation($locked->vehicleReservation, $officer);
            }

            $beforeStatus = $locked->booking_status;

            $locked->forceFill([
                'booking_status' => Booking::STATUS_CONFIRMED,
                'confirmed_by' => $officer->id,
                'confirmed_at' => now(),
                'officer_notes' => $officerNotes ?? $locked->officer_notes,
            ])->save();

            BookingEvent::query()->create([
                'booking_id' => $locked->id,
                'actor_id' => $officer->id,
                'event_type' => 'booking.confirmed',
                'summary' => "Booking [{$locked->booking_reference}] confirmed by {$officer->name}",
                'payload' => ['previous_status' => $beforeStatus, 'payment_status' => $locked->payment_status],
            ]);

            $this->audit->record(
                actor: $officer,
                event: 'booking.confirmed',
                summary: "Confirmed booking [{$locked->booking_reference}]",
                auditable: $locked,
                beforeState: ['booking_status' => $beforeStatus],
                afterState: ['booking_status' => Booking::STATUS_CONFIRMED, 'payment_status' => $locked->payment_status],
            );

            return $locked->fresh(['items', 'vehicleReservation', 'hotelVerifications']);
        });
    }

    /**
     * Cancel booking or transition to refund_required if payment was already collected and hotel became unavailable.
     *
     * @throws AuthorizationException
     */
    public function cancelBooking(User $officer, Booking $booking, string $reason, bool $hotelUnavailableAfterPayment = false): Booking
    {
        if (! $officer->can('bookings.cancel')) {
            throw new AuthorizationException('You do not have permission to cancel bookings.');
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('Cancellation reason is required.');
        }

        return DB::transaction(function () use ($officer, $booking, $reason, $hotelUnavailableAfterPayment): Booking {
            $locked = Booking::query()->lockForUpdate()->with('vehicleReservation')->findOrFail($booking->id);

            if ($locked->vehicleReservation !== null) {
                $this->vehicleAvailability->releaseReservation($locked->vehicleReservation, $officer);
            }

            $hasCollectedPayment = in_array($locked->payment_status, [Booking::PAYMENT_PARTIAL, Booking::PAYMENT_PAID], true);
            $newBookingStatus = ($hotelUnavailableAfterPayment && $hasCollectedPayment)
                ? Booking::STATUS_REFUND_REQUIRED
                : Booking::STATUS_CANCELLED;
            $newPaymentStatus = $hasCollectedPayment
                ? Booking::PAYMENT_REFUND_PENDING
                : $locked->payment_status;

            $locked->forceFill([
                'booking_status' => $newBookingStatus,
                'payment_status' => $newPaymentStatus,
                'cancelled_by' => $officer->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ])->save();

            BookingEvent::query()->create([
                'booking_id' => $locked->id,
                'actor_id' => $officer->id,
                'event_type' => $newBookingStatus === Booking::STATUS_REFUND_REQUIRED ? 'booking.refund_required' : 'booking.cancelled',
                'summary' => "Booking [{$locked->booking_reference}] marked {$newBookingStatus}: {$reason}",
                'payload' => ['payment_status' => $newPaymentStatus],
            ]);

            $this->audit->record(
                actor: $officer,
                event: 'booking.cancelled',
                summary: "Cancelled booking [{$locked->booking_reference}] ({$newBookingStatus})",
                auditable: $locked,
                afterState: ['booking_status' => $newBookingStatus, 'payment_status' => $newPaymentStatus, 'reason' => $reason],
            );

            return $locked->fresh();
        });
    }
}

<?php

namespace App\Modules\Booking\Services;

use App\Models\User;
use App\Modules\Booking\Models\BookingItem;
use App\Modules\Booking\Models\BookingQuote;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Services\VehiclePricingService;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelRoomType;
use App\Modules\Hotels\Services\HotelPricingService;
use App\Modules\Nomads\Models\NomadFacility;
use App\Modules\Nomads\Services\NomadPricingService;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Models\TourDeparture;
use App\Modules\Tours\Models\TourStay;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Authoritative central DECIMAL pricing engine orchestrating Tour + Fleet + Tour-Only Hotels + Digital Nomad Add-ons + Extras.
 * Never uses floating-point arithmetic for monetary totals; never trusts client-submitted prices.
 */
final class CentralPricingService
{
    public function __construct(
        private readonly VehiclePricingService $vehiclePricing,
        private readonly HotelPricingService $hotelPricing,
        private readonly NomadPricingService $nomadPricing,
    ) {}

    /**
     * Build and optionally persist an authoritative, immutable pricing quote.
     *
     * @param  array{
     *     start_date: string|CarbonInterface,
     *     guests_count: int,
     *     currency?: string,
     *     departure_id?: ?int,
     *     vehicle?: ?array{vehicle_id: int, fuel_mode: string, end_date?: ?string, region_id?: ?int, destination_id?: ?int},
     *     hotel_stays?: list<array{tour_stay_id: int, hotel_id: int, room_type_id: int, rooms_count: int}>,
     *     nomad_addons?: list<array{facility_id: int, start_date?: ?string, days_count: int, quantity: int, unit?: string, hours_per_day?: int}>,
     *     extras?: list<array{label: string, charge_unit: string, quantity: int, unit_price: string}>,
     *     discount_amount?: string,
     *     tax_rate_percent?: ?string,
     *     idempotency_key?: ?string
     * }  $input
     */
    public function createQuote(Tour $tour, array $input, ?User $user = null, bool $persist = true): BookingQuote
    {
        if (! $tour->isPublished()) {
            throw new InvalidArgumentException("Tour [{$tour->title}] is not currently published for booking.");
        }

        $idempotencyKey = isset($input['idempotency_key']) && trim((string) $input['idempotency_key']) !== ''
            ? mb_substr(trim((string) $input['idempotency_key']), 0, 120)
            : null;

        if ($persist && $idempotencyKey !== null) {
            $existing = BookingQuote::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing !== null) {
                return $existing;
            }
        }

        $currency = strtoupper(trim((string) ($input['currency'] ?? 'PKR')));
        if (! in_array($currency, ['PKR', 'USD'], true)) {
            throw new InvalidArgumentException("Unsupported currency [{$currency}].");
        }

        $guestsCount = (int) ($input['guests_count'] ?? 0);
        if ($guestsCount < $tour->min_guests || $guestsCount > $tour->max_guests) {
            throw new InvalidArgumentException("Guest count must be between {$tour->min_guests} and {$tour->max_guests} for this tour.");
        }

        $startDate = $input['start_date'] instanceof CarbonInterface
            ? CarbonImmutable::instance($input['start_date'])->startOfDay()
            : CarbonImmutable::parse((string) $input['start_date'])->startOfDay();

        $durationDays = max(1, (int) $tour->duration_days);
        $endDate = $startDate->addDays($durationDays - 1);

        $departure = null;
        if (! empty($input['departure_id'])) {
            $departure = TourDeparture::query()
                ->where('id', (int) $input['departure_id'])
                ->where('tour_id', $tour->id)
                ->first();

            if ($departure === null || $departure->status !== TourDeparture::STATUS_OPEN) {
                throw new InvalidArgumentException('Selected tour departure is not open for booking.');
            }

            if ($departure->seatsRemaining() < $guestsCount) {
                throw new InvalidArgumentException('Selected departure does not have enough seats remaining.');
            }

            $startDate = CarbonImmutable::instance($departure->start_date)->startOfDay();
            $endDate = CarbonImmutable::instance($departure->end_date)->startOfDay();
        }

        $lines = [];
        $subtotal = '0.00';

        // 1. Tour Base Line
        $tourBaseUnit = $this->resolveTourBaseUnit($tour, $departure, $currency);
        if ($tour->pricing_model === Tour::PRICING_CUSTOM_QUOTE) {
            $lines[] = [
                'item_type' => BookingItem::TYPE_TOUR_BASE,
                'reference_type' => Tour::class,
                'reference_id' => $tour->id,
                'label' => "{$tour->title} — Custom Quotation Request ({$guestsCount} guest(s))",
                'charge_unit' => 'fixed',
                'quantity' => 1,
                'unit_price' => '0.00',
                'line_total' => '0.00',
                'is_included' => false,
                'meta' => ['pricing_model' => Tour::PRICING_CUSTOM_QUOTE, 'requires_officer_quote' => true],
            ];
        } else {
            $isPerPerson = $tour->pricing_model === Tour::PRICING_PER_PERSON;
            $qty = $isPerPerson ? $guestsCount : 1;
            $baseTotal = bcmul($tourBaseUnit, (string) $qty, 2);
            $subtotal = bcadd($subtotal, $baseTotal, 2);

            $lines[] = [
                'item_type' => BookingItem::TYPE_TOUR_BASE,
                'reference_type' => Tour::class,
                'reference_id' => $tour->id,
                'label' => $isPerPerson
                    ? "{$tour->title} — Tour Package ({$guestsCount} guests)"
                    : "{$tour->title} — Fixed Tour Package",
                'charge_unit' => $isPerPerson ? 'person' : 'fixed',
                'quantity' => $qty,
                'unit_price' => $tourBaseUnit,
                'line_total' => $baseTotal,
                'is_included' => false,
                'meta' => [
                    'tour_code' => $tour->code,
                    'pricing_model' => $tour->pricing_model,
                    'includes_base_accommodation' => (bool) $tour->includes_base_accommodation,
                ],
            ];
        }

        // 2. Company Fleet Selection (Vehicle Days are explicitly distinct from Hotel Nights)
        $vehicleDays = 0;
        $vehicleQuoteSnapshot = null;
        if (! empty($input['vehicle'])) {
            $vInput = $input['vehicle'];
            $vehicle = Vehicle::query()->findOrFail((int) $vInput['vehicle_id']);
            $vehicleEnd = ! empty($vInput['end_date'])
                ? CarbonImmutable::parse((string) $vInput['end_date'])->startOfDay()
                : $endDate;

            $primaryDestination = $tour->destinations()->orderByDesc('tour_destinations.is_primary')->first();
            $destinationId = isset($vInput['destination_id']) ? (int) $vInput['destination_id'] : $primaryDestination?->id;
            $regionId = isset($vInput['region_id']) ? (int) $vInput['region_id'] : $primaryDestination?->region_id;

            $vehicleQuote = $this->vehiclePricing->quote(
                vehicle: $vehicle,
                startDate: $startDate,
                endDate: $vehicleEnd,
                fuelMode: (string) ($vInput['fuel_mode'] ?? 'with_fuel'),
                currency: $currency,
                regionId: $regionId,
                destinationId: $destinationId,
                partySize: $guestsCount,
            );

            $vehicleDays = $vehicleQuote['rental_days'];
            $vehicleQuoteSnapshot = $vehicleQuote;
            $subtotal = bcadd($subtotal, $vehicleQuote['total_amount'], 2);

            foreach ($vehicleQuote['quote_lines'] as $qLine) {
                $itemType = match ($qLine['code']) {
                    'DRIVER_INCLUDED' => BookingItem::TYPE_DRIVER_INCLUDED,
                    'ROUTE_FUEL_ADJUSTMENT' => BookingItem::TYPE_ROUTE_ADJUSTMENT,
                    default => BookingItem::TYPE_VEHICLE,
                };

                $lines[] = [
                    'item_type' => $itemType,
                    'reference_type' => Vehicle::class,
                    'reference_id' => $vehicle->id,
                    'label' => $qLine['label'],
                    'charge_unit' => $qLine['unit'],
                    'quantity' => (int) $qLine['quantity'],
                    'unit_price' => $qLine['unit_price'],
                    'line_total' => $qLine['line_total'],
                    'is_included' => (bool) $qLine['included'],
                    'meta' => ['fuel_mode' => $vehicleQuote['fuel_mode'], 'vehicle_code' => $vehicle->code],
                ];
            }
        }

        // 3. Tour-Only Hotel Stay Preferences (Hotel Nights counted distinctly from Vehicle Days)
        $hotelNights = 0;
        $hotelSnapshots = [];
        foreach ($input['hotel_stays'] ?? [] as $hInput) {
            $stay = TourStay::query()->where('tour_id', $tour->id)->findOrFail((int) $hInput['tour_stay_id']);
            $hotel = Hotel::query()->findOrFail((int) $hInput['hotel_id']);
            $roomType = HotelRoomType::query()->findOrFail((int) $hInput['room_type_id']);
            $roomsCount = max(1, (int) ($hInput['rooms_count'] ?? 1));
            $checkInDate = $startDate->addDays(max(0, $stay->check_in_day - 1));

            $stayQuote = $this->hotelPricing->quoteStaySelection(
                tour: $tour,
                stay: $stay,
                hotel: $hotel,
                roomType: $roomType,
                checkInDate: $checkInDate,
                roomsCount: $roomsCount,
                guestsCount: $guestsCount,
                currency: $currency,
            );

            $hotelNights += $stayQuote['nights_count'];
            $hotelSnapshots[] = $stayQuote;
            $subtotal = bcadd($subtotal, $stayQuote['chargeable_amount'], 2);

            $unitNightlyAvg = $stayQuote['nights_count'] > 0
                ? bcdiv($stayQuote['chargeable_amount'], (string) ($stayQuote['nights_count'] * $roomsCount), 2)
                : $stayQuote['chargeable_amount'];

            $lines[] = [
                'item_type' => BookingItem::TYPE_HOTEL_STAY,
                'reference_type' => Hotel::class,
                'reference_id' => $hotel->id,
                'label' => $stayQuote['includes_base_accommodation']
                    ? "Hotel Upgrade Preference: {$hotel->name} — {$roomType->name} ({$roomsCount} rm × {$stayQuote['nights_count']} nights; subject to external verification)"
                    : "Hotel Preference: {$hotel->name} — {$roomType->name} ({$roomsCount} rm × {$stayQuote['nights_count']} nights; subject to external verification)",
                'charge_unit' => 'night',
                'quantity' => $stayQuote['nights_count'] * $roomsCount,
                'unit_price' => $unitNightlyAvg,
                'line_total' => $stayQuote['chargeable_amount'],
                'is_included' => bccomp($stayQuote['chargeable_amount'], '0.00', 2) === 0,
                'meta' => $stayQuote,
            ];
        }

        // 4. Digital Nomad Tour Add-Ons
        $nomadSnapshots = [];
        foreach ($input['nomad_addons'] ?? [] as $nInput) {
            $facility = NomadFacility::query()->with('location')->findOrFail((int) $nInput['facility_id']);
            $addonStart = ! empty($nInput['start_date'])
                ? CarbonImmutable::parse((string) $nInput['start_date'])->startOfDay()
                : $startDate;

            $nomadQuote = $this->nomadPricing->quoteTourAddon(
                tour: $tour,
                facility: $facility,
                startDate: $addonStart,
                daysCount: (int) ($nInput['days_count'] ?? 1),
                quantity: (int) ($nInput['quantity'] ?? 1),
                unit: (string) ($nInput['unit'] ?? 'day'),
                hoursPerDay: (int) ($nInput['hours_per_day'] ?? 1),
                currency: $currency,
            );

            $nomadSnapshots[] = $nomadQuote;
            $subtotal = bcadd($subtotal, $nomadQuote['total_amount'], 2);

            $totalUnits = $nomadQuote['unit'] === 'hour'
                ? ($nomadQuote['days_count'] * $nomadQuote['quantity'] * $nomadQuote['units_per_day'])
                : ($nomadQuote['days_count'] * $nomadQuote['quantity']);

            $unitPrice = $totalUnits > 0 ? bcdiv($nomadQuote['total_amount'], (string) $totalUnits, 2) : $nomadQuote['total_amount'];

            $lines[] = [
                'item_type' => BookingItem::TYPE_NOMAD_WORKSPACE,
                'reference_type' => NomadFacility::class,
                'reference_id' => $facility->id,
                'label' => "Digital Nomad Add-On: {$nomadQuote['location_name']} — {$nomadQuote['facility_name']}",
                'charge_unit' => $nomadQuote['unit'],
                'quantity' => max(1, $totalUnits),
                'unit_price' => $unitPrice,
                'line_total' => $nomadQuote['total_amount'],
                'is_included' => false,
                'meta' => $nomadQuote,
            ];
        }

        // 5. Configurable Extras (guides, permits, airport transfers, etc.)
        foreach ($input['extras'] ?? [] as $extra) {
            $label = trim((string) ($extra['label'] ?? ''));
            $qty = max(1, (int) ($extra['quantity'] ?? 1));
            $unitPrice = $this->normalizeDecimal($extra['unit_price'] ?? '0.00');
            if ($label === '' || bccomp($unitPrice, '0.00', 2) < 0) {
                throw new InvalidArgumentException('Invalid booking extra item.');
            }
            $lineTotal = bcmul($unitPrice, (string) $qty, 2);
            $subtotal = bcadd($subtotal, $lineTotal, 2);

            $lines[] = [
                'item_type' => BookingItem::TYPE_EXTRA,
                'reference_type' => null,
                'reference_id' => null,
                'label' => $label,
                'charge_unit' => (string) ($extra['charge_unit'] ?? 'fixed'),
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
                'is_included' => false,
                'meta' => null,
            ];
        }

        // 6. Discount & Configurable Tax (NEVER hardcoded GST)
        $discount = $this->normalizeDecimal($input['discount_amount'] ?? '0.00');
        if (bccomp($discount, '0.00', 2) < 0) {
            throw new InvalidArgumentException('Discount cannot be negative.');
        }
        if (bccomp($discount, $subtotal, 2) > 0) {
            $discount = $subtotal;
        }

        $taxableAmount = bcsub($subtotal, $discount, 2);
        $taxRate = $this->normalizeDecimal(
            $input['tax_rate_percent'] ?? config('precious.booking.default_tax_rate_percent', '0.00'),
            3,
        );
        if (bccomp($taxRate, '0.000', 3) < 0 || bccomp($taxRate, '100.000', 3) > 0) {
            throw new InvalidArgumentException('Tax rate percent must be between 0 and 100.');
        }

        $taxAmount = bccomp($taxRate, '0.000', 3) > 0
            ? bcdiv(bcmul($taxableAmount, $taxRate, 4), '100', 2)
            : '0.00';

        $totalAmount = bcadd($taxableAmount, $taxAmount, 2);

        $snapshot = [
            'generated_at_utc' => now('UTC')->toIso8601String(),
            'display_timezone' => config('precious.display_timezone', 'Asia/Karachi'),
            'tour' => [
                'id' => $tour->id,
                'code' => $tour->code,
                'title' => $tour->title,
                'pricing_model' => $tour->pricing_model,
            ],
            'dates' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'vehicle_days' => $vehicleDays,
                'hotel_nights' => $hotelNights,
                'vehicle_cutoff_hour_pkt' => (int) config('precious.booking.vehicle_rental_cutoff_hour_pkt', 18),
                'vehicle_grace_period_minutes' => (int) config('precious.booking.vehicle_grace_period_minutes', 60),
            ],
            'currency' => $currency,
            'guests_count' => $guestsCount,
            'vehicle_quote' => $vehicleQuoteSnapshot,
            'hotel_quotes' => $hotelSnapshots,
            'nomad_quotes' => $nomadSnapshots,
            'lines' => $lines,
            'totals' => [
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_rate_percent' => $taxRate,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
            ],
        ];

        $quoteAttributes = [
            'quote_token' => 'PPQ-'.now()->format('Ymd').'-'.strtoupper(Str::random(10)),
            'idempotency_key' => $idempotencyKey,
            'tour_id' => $tour->id,
            'user_id' => $user?->id,
            'currency' => $currency,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'guests_count' => $guestsCount,
            'vehicle_days' => $vehicleDays,
            'hotel_nights' => $hotelNights,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'tax_rate_percent' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'status' => BookingQuote::STATUS_ACTIVE,
            'expires_at' => now()->addMinutes((int) config('precious.booking.quote_expiry_minutes', 60)),
            'snapshot' => $snapshot,
        ];

        if (! $persist) {
            return new BookingQuote($quoteAttributes);
        }

        return BookingQuote::query()->create($quoteAttributes);
    }

    private function resolveTourBaseUnit(Tour $tour, ?TourDeparture $departure, string $currency): string
    {
        if ($tour->pricing_model === Tour::PRICING_CUSTOM_QUOTE) {
            return '0.00';
        }

        $override = $currency === 'USD' ? $departure?->price_override_usd : $departure?->price_override_pkr;
        $raw = $override ?? ($currency === 'USD' ? $tour->base_price_usd : $tour->base_price_pkr);

        if ($raw === null || bccomp((string) $raw, '0.00', 2) <= 0) {
            throw new InvalidArgumentException("Tour [{$tour->title}] does not have an active {$currency} base rate.");
        }

        return $this->normalizeDecimal($raw);
    }

    private function normalizeDecimal(mixed $value, int $scale = 2): string
    {
        $str = trim((string) $value);
        if (! is_numeric($str)) {
            throw new InvalidArgumentException("Invalid numeric amount [{$str}].");
        }

        return number_format((float) $str, $scale, '.', '');
    }
}

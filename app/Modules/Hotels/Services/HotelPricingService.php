<?php

namespace App\Modules\Hotels\Services;

use App\Modules\Hotels\Models\Hotel;
use App\Modules\Hotels\Models\HotelRoomRate;
use App\Modules\Hotels\Models\HotelRoomType;
use App\Modules\Hotels\Models\TourStayHotel;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Models\TourStay;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class HotelPricingService
{
    /**
     * Compute an exact, immutable DECIMAL quote for a customer's preferred hotel/room on a tour stay.
     *
     * Rules enforced:
     * - Hotel must be actively associated with the tour stay (no unrelated hotels).
     * - Rooms * roomType.max_occupancy >= guestsCount (no impossible occupancy).
     * - Every chargeable night must resolve a valid seasonal/annual rate in requested currency.
     * - If Tour includes base accommodation, charge ONLY the upgrade difference (selected - default), never duplicate full rate.
     *
     * @return array{
     *     tour_stay_id: int,
     *     hotel_id: int,
     *     hotel_name: string,
     *     room_type_id: int,
     *     room_type_name: string,
     *     check_in_date: string,
     *     nights_count: int,
     *     rooms_count: int,
     *     guests_count: int,
     *     currency: string,
     *     includes_base_accommodation: bool,
     *     selected_full_amount: string,
     *     base_included_credit: string,
     *     chargeable_amount: string,
     *     nightly_breakdown: list<array{date: string, rate_id: int, rate_name: string, rate_tier: string, unit_nightly_rate: string, rooms_count: int, line_total: string}>
     * }
     */
    public function quoteStaySelection(
        Tour $tour,
        TourStay $stay,
        Hotel $hotel,
        HotelRoomType $roomType,
        CarbonInterface $checkInDate,
        int $roomsCount,
        int $guestsCount,
        string $currency = 'PKR',
    ): array {
        $currency = strtoupper(trim($currency));
        if (! in_array($currency, ['PKR', 'USD'], true)) {
            throw new InvalidArgumentException("Unsupported currency [{$currency}].");
        }

        if ($stay->tour_id !== $tour->id) {
            throw new InvalidArgumentException('Selected overnight stay does not belong to the given tour.');
        }

        $stayLink = TourStayHotel::query()
            ->where('tour_stay_id', $stay->id)
            ->where('hotel_id', $hotel->id)
            ->where('is_active', true)
            ->first();

        if ($stayLink === null || $hotel->status !== Hotel::STATUS_ACTIVE) {
            throw new InvalidArgumentException("Hotel [{$hotel->name}] is not an eligible option for this tour overnight stay.");
        }

        if ($roomType->hotel_id !== $hotel->id || ! $roomType->is_active) {
            throw new InvalidArgumentException("Room type [{$roomType->name}] is not active for hotel [{$hotel->name}].");
        }

        if ($roomsCount < 1 || $guestsCount < 1) {
            throw new InvalidArgumentException('Rooms count and guests count must both be at least 1.');
        }

        $maxCapacity = $roomsCount * $roomType->max_occupancy;
        if ($maxCapacity < $guestsCount) {
            throw new InvalidArgumentException(
                "Impossible occupancy: {$roomsCount}x {$roomType->name} (max {$roomType->max_occupancy}/room = {$maxCapacity}) cannot accommodate {$guestsCount} guest(s)."
            );
        }

        [$selectedFullAmount, $nightlyBreakdown] = $this->calculateRoomRangeTotal(
            roomType: $roomType,
            checkInDate: $checkInDate,
            nightsCount: $stay->nights_count,
            roomsCount: $roomsCount,
            currency: $currency,
        );

        $baseIncludedCredit = '0.00';
        $chargeableAmount = $selectedFullAmount;

        if ($tour->includes_base_accommodation) {
            $defaultLink = TourStayHotel::query()
                ->where('tour_stay_id', $stay->id)
                ->where('option_type', TourStayHotel::OPTION_DEFAULT)
                ->where('is_active', true)
                ->with(['defaultRoomType', 'hotel.roomTypes'])
                ->first();

            $baselineRoomType = $defaultLink?->defaultRoomType ?? $defaultLink?->hotel?->roomTypes?->where('is_active', true)->first();

            if ($baselineRoomType !== null) {
                [$baselineAmount] = $this->calculateRoomRangeTotal(
                    roomType: $baselineRoomType,
                    checkInDate: $checkInDate,
                    nightsCount: $stay->nights_count,
                    roomsCount: $roomsCount,
                    currency: $currency,
                );

                $baseIncludedCredit = $baselineAmount;
                $diff = bcsub($selectedFullAmount, $baselineAmount, 2);
                $chargeableAmount = bccomp($diff, '0.00', 2) > 0 ? $diff : '0.00';
            }
        }

        return [
            'tour_stay_id' => $stay->id,
            'hotel_id' => $hotel->id,
            'hotel_name' => $hotel->name,
            'room_type_id' => $roomType->id,
            'room_type_name' => $roomType->name,
            'check_in_date' => $checkInDate->toDateString(),
            'nights_count' => $stay->nights_count,
            'rooms_count' => $roomsCount,
            'guests_count' => $guestsCount,
            'currency' => $currency,
            'includes_base_accommodation' => (bool) $tour->includes_base_accommodation,
            'selected_full_amount' => $selectedFullAmount,
            'base_included_credit' => $baseIncludedCredit,
            'chargeable_amount' => $chargeableAmount,
            'nightly_breakdown' => $nightlyBreakdown,
        ];
    }

    public function resolveRateForNight(HotelRoomType $roomType, CarbonInterface $nightDate, string $currency): ?HotelRoomRate
    {
        $dateStr = $nightDate->toDateString();
        $column = strtoupper($currency) === 'USD' ? 'nightly_rate_usd' : 'nightly_rate_pkr';

        $candidates = HotelRoomRate::query()
            ->where('hotel_room_type_id', $roomType->id)
            ->where('is_active', true)
            ->whereNotNull($column)
            ->where($column, '>', 0)
            ->whereDate('valid_from', '<=', $dateStr)
            ->whereDate('valid_to', '>=', $dateStr)
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        return $candidates
            ->sortByDesc(fn (HotelRoomRate $rate): int => ($rate->tierPrecedence() * 10000) + $rate->priority)
            ->first();
    }

    /**
     * @return array{0: string, 1: list<array{date: string, rate_id: int, rate_name: string, rate_tier: string, unit_nightly_rate: string, rooms_count: int, line_total: string}>}
     */
    private function calculateRoomRangeTotal(
        HotelRoomType $roomType,
        CarbonInterface $checkInDate,
        int $nightsCount,
        int $roomsCount,
        string $currency,
    ): array {
        $start = CarbonImmutable::instance($checkInDate)->startOfDay();
        $total = '0.00';
        $breakdown = [];

        for ($i = 0; $i < $nightsCount; $i++) {
            $night = $start->addDays($i);
            $rate = $this->resolveRateForNight($roomType, $night, $currency);

            if ($rate === null) {
                throw new InvalidArgumentException(
                    "Missing {$currency} nightly room rate for [{$roomType->name}] on {$night->toDateString()}."
                );
            }

            $unit = strtoupper($currency) === 'USD'
                ? number_format((float) $rate->nightly_rate_usd, 2, '.', '')
                : number_format((float) $rate->nightly_rate_pkr, 2, '.', '');

            $lineTotal = bcmul($unit, (string) $roomsCount, 2);
            $total = bcadd($total, $lineTotal, 2);

            $breakdown[] = [
                'date' => $night->toDateString(),
                'rate_id' => $rate->id,
                'rate_name' => $rate->rate_name,
                'rate_tier' => $rate->rate_tier,
                'unit_nightly_rate' => $unit,
                'rooms_count' => $roomsCount,
                'line_total' => $lineTotal,
            ];
        }

        return [$total, $breakdown];
    }
}

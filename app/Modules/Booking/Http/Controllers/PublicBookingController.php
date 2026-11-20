<?php

namespace App\Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Services\BookingWorkflowService;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Nomads\Models\NomadFacility;
use App\Modules\Tours\Models\Tour;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

final class PublicBookingController extends Controller
{
    public function __construct(
        private readonly BookingWorkflowService $workflow,
    ) {}

    public function create(string $slug): View
    {
        $tour = Tour::query()
            ->where('slug', $slug)
            ->where('status', Tour::STATUS_PUBLISHED)
            ->with([
                'destinations',
                'stays.stayHotels.hotel.roomTypes',
                'openDepartures',
            ])
            ->firstOrFail();

        $vehicles = Vehicle::query()->where('status', Vehicle::STATUS_ACTIVE)->orderBy('passenger_capacity')->get();
        $destIds = $tour->destinations->pluck('id')->all();
        $nomadFacilities = $tour->supports_nomad_addon
            ? NomadFacility::query()
                ->where('is_active', true)
                ->whereHas('location', fn ($q) => $q->where('status', 'active')->when($destIds !== [], fn ($sq) => $sq->whereIn('destination_id', $destIds)))
                ->with('location')
                ->get()
            : collect();

        return view('bookings.create', [
            'tour' => $tour,
            'vehicles' => $vehicles,
            'nomadFacilities' => $nomadFacilities,
        ]);
    }

    public function store(Request $request, string $slug): RedirectResponse
    {
        $tour = Tour::query()->where('slug', $slug)->where('status', Tour::STATUS_PUBLISHED)->firstOrFail();

        $validated = $request->validate([
            'idempotency_key' => ['nullable', 'string', 'max:120'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'guests_count' => ['required', 'integer', 'min:1', 'max:50'],
            'currency' => ['required', 'in:PKR,USD'],
            'departure_id' => ['nullable', 'integer'],
            'vehicle_id' => ['nullable', 'integer'],
            'fuel_mode' => ['nullable', 'in:with_fuel,without_fuel'],
            'customer_name' => ['required', 'string', 'max:160'],
            'customer_email' => ['required', 'email', 'max:160'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'customer_notes' => ['nullable', 'string', 'max:1500'],
        ]);

        $pricingInput = [
            'start_date' => $validated['start_date'],
            'guests_count' => (int) $validated['guests_count'],
            'currency' => $validated['currency'],
            'departure_id' => $validated['departure_id'] ?? null,
        ];

        if (! empty($validated['vehicle_id'])) {
            $pricingInput['vehicle'] = [
                'vehicle_id' => (int) $validated['vehicle_id'],
                'fuel_mode' => $validated['fuel_mode'] ?? 'with_fuel',
            ];
        }

        try {
            $booking = $this->workflow->submitBookingRequest(
                tour: $tour,
                pricingInput: $pricingInput,
                customerInput: [
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'customer_phone' => $validated['customer_phone'],
                    'customer_notes' => $validated['customer_notes'] ?? null,
                    'idempotency_key' => $validated['idempotency_key'] ?? null,
                ],
                customer: $request->user(),
            );
        } catch (InvalidArgumentException|RuntimeException $e) {
            return back()->withInput()->withErrors(['booking' => $e->getMessage()]);
        }

        return redirect()
            ->route('bookings.confirmation', ['reference' => $booking->booking_reference])
            ->with('status', "Booking request {$booking->booking_reference} submitted! Our Booking Officer will verify availability and follow up.");
    }

    public function confirmation(string $reference): View
    {
        $booking = Booking::query()
            ->where('booking_reference', $reference)
            ->with(['tour', 'items', 'hotelVerifications.requestedHotel'])
            ->firstOrFail();

        return view('bookings.confirmation', ['booking' => $booking]);
    }
}

<?php

namespace App\Modules\Tours\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\Region;
use App\Modules\Tours\Models\Tour;
use App\Modules\Tours\Services\TourSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class PublicTourController extends Controller
{
    public function index(Request $request, TourSearchService $search): View
    {
        $filters = [
            'q' => $request->string('q')->trim()->toString() ?: null,
            'category' => $request->string('category')->lower()->trim()->toString() ?: null,
            'region' => $request->string('region')->lower()->trim()->toString() ?: null,
            'destination' => $request->string('destination')->lower()->trim()->toString() ?: null,
            'difficulty' => $request->string('difficulty')->lower()->trim()->toString() ?: null,
            'guests' => $request->filled('guests') ? max(1, $request->integer('guests')) : null,
            'nomad' => $request->boolean('nomad'),
            'sort' => $request->string('sort')->trim()->toString() ?: 'featured',
        ];

        $tours = $search->searchPublished($filters);
        $regions = Region::query()->where('is_active', true)->orderBy('sort_order')->get();
        $destinations = Destination::query()->where('is_active', true)->orderBy('name')->get();

        return view('tours.index', [
            'tours' => $tours,
            'regions' => $regions,
            'destinations' => $destinations,
            'filters' => $filters,
        ]);
    }

    public function show(string $slug): View
    {
        $tour = Tour::query()
            ->where('slug', strtolower(trim($slug)))
            ->where('status', Tour::STATUS_PUBLISHED)
            ->with([
                'destinations.region',
                'itineraryDays.destination',
                'stays.destination',
                'openDepartures',
            ])
            ->firstOrFail();

        return view('tours.show', [
            'tour' => $tour,
        ]);
    }

    public function submitBookingEntry(string $slug, Request $request, TourSearchService $search): RedirectResponse
    {
        $tour = Tour::query()
            ->where('slug', strtolower(trim($slug)))
            ->where('status', Tour::STATUS_PUBLISHED)
            ->firstOrFail();

        $validated = $request->validate([
            'start_date' => ['required_without:departure_id', 'nullable', 'date'],
            'departure_id' => ['nullable', 'integer'],
            'guests' => ['required', 'integer', 'min:1', 'max:100'],
            'currency' => ['nullable', 'in:PKR,USD'],
        ]);

        try {
            $selection = $search->validateBookingEntry(
                tour: $tour,
                startDateInput: (string) ($validated['start_date'] ?? ''),
                guests: (int) $validated['guests'],
                departureId: filled($validated['departure_id'] ?? null) ? (int) $validated['departure_id'] : null,
                currency: (string) ($validated['currency'] ?? 'PKR'),
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['booking_entry' => $exception->getMessage()]);
        }

        $request->session()->put('precious.booking_entry', $selection);

        return back()->with(
            'booking_entry_status',
            sprintf(
                'Selection validated for %d traveler(s) (%s to %s, %s). Full hotel, fleet, Digital Nomad & checkout customization connects in Phases 6–9 — or submit an inquiry now.',
                $selection['guests'],
                $selection['start_date'],
                $selection['end_date'],
                $selection['currency']
            )
        );
    }
}

<?php

namespace App\Modules\Destinations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Destinations\Models\Destination;
use App\Modules\Destinations\Models\District;
use App\Modules\Destinations\Models\Region;
use App\Modules\Destinations\Services\GeoSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicDestinationController extends Controller
{
    public function index(Request $request, GeoSearchService $geo): View
    {
        $selectedRegionSlug = $request->string('region')->lower()->trim()->toString() ?: null;
        $selectedDistrictSlug = $request->string('district')->lower()->trim()->toString() ?: null;
        $searchQuery = $request->string('q')->trim()->toString() ?: null;
        $featuredOnly = $request->boolean('featured');

        $regions = Region::query()
            ->where('is_active', true)
            ->withCount(['destinations' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $districts = District::query()
            ->where('is_active', true)
            ->when($selectedRegionSlug !== null, fn ($q) => $q->whereHas('region', fn ($rq) => $rq->where('slug', $selectedRegionSlug)))
            ->orderBy('name')
            ->get();

        $destinations = $geo->searchDestinations([
            'region' => $selectedRegionSlug,
            'district' => $selectedDistrictSlug,
            'q' => $searchQuery,
            'featured' => $featuredOnly,
        ]);

        return view('destinations.index', [
            'regions' => $regions,
            'districts' => $districts,
            'destinations' => $destinations,
            'selectedRegionSlug' => $selectedRegionSlug,
            'selectedDistrictSlug' => $selectedDistrictSlug,
            'searchQuery' => $searchQuery,
            'featuredOnly' => $featuredOnly,
            'geoJson' => $geo->buildGeoJson($destinations),
        ]);
    }

    public function showRegion(string $slug, GeoSearchService $geo): View
    {
        $region = Region::query()
            ->where('slug', strtolower(trim($slug)))
            ->where('is_active', true)
            ->with([
                'country',
                'districts' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
                'destinations' => fn ($q) => $q->where('is_active', true)->with('district')->withCount('attractions')->orderByDesc('is_featured')->orderBy('name'),
            ])
            ->firstOrFail();

        return view('destinations.region', [
            'region' => $region,
            'geoJson' => $geo->buildGeoJson($region->destinations),
        ]);
    }

    public function showDestination(string $slug, GeoSearchService $geo): View
    {
        $destination = Destination::query()
            ->where('slug', strtolower(trim($slug)))
            ->where('is_active', true)
            ->with([
                'region.country',
                'district',
                'attractions' => fn ($q) => $q->where('is_active', true)->orderBy('name'),
            ])
            ->firstOrFail();

        $nearbyAttractions = $geo->nearbyAttractions($destination, 120.0, 12);
        $attractionModels = $nearbyAttractions->pluck('attraction');
        $relatedTours = $geo->relatedToursForDestination($destination);

        return view('destinations.show', [
            'destination' => $destination,
            'nearbyAttractions' => $nearbyAttractions,
            'relatedTours' => $relatedTours,
            'geoJson' => $geo->buildGeoJson(collect([$destination]), $attractionModels),
        ]);
    }

    public function geoJson(Request $request, GeoSearchService $geo): JsonResponse
    {
        $destinations = $geo->searchDestinations([
            'region' => $request->query('region'),
            'district' => $request->query('district'),
            'q' => $request->query('q'),
            'featured' => $request->boolean('featured'),
        ]);

        return response()->json($geo->buildGeoJson($destinations));
    }
}

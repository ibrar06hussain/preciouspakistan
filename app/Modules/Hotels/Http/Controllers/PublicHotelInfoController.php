<?php

namespace App\Modules\Hotels\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hotels\Models\Hotel;
use App\Modules\Tours\Models\Tour;
use Illuminate\View\View;

class PublicHotelInfoController extends Controller
{
    public function show(string $slug): View
    {
        $hotel = Hotel::query()
            ->where('slug', strtolower(trim($slug)))
            ->where('status', Hotel::STATUS_ACTIVE)
            ->with([
                'region',
                'district',
                'destination',
                'roomTypes' => fn ($q) => $q->where('is_active', true)->with(['rates' => fn ($rq) => $rq->where('is_active', true)->orderBy('valid_from')]),
            ])
            ->firstOrFail();

        $eligibleTours = Tour::query()
            ->where('status', Tour::STATUS_PUBLISHED)
            ->whereHas('stays.stayHotels', fn ($q) => $q->where('hotel_id', $hotel->id)->where('is_active', true))
            ->orderByDesc('is_featured')
            ->orderBy('title')
            ->get();

        return view('hotels.show', [
            'hotel' => $hotel,
            'eligibleTours' => $eligibleTours,
        ]);
    }
}

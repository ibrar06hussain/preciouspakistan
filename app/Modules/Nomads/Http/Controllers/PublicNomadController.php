<?php

namespace App\Modules\Nomads\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Nomads\Models\NomadLocation;
use App\Modules\Tours\Models\Tour;
use Illuminate\View\View;

class PublicNomadController extends Controller
{
    public function index(): View
    {
        $locations = NomadLocation::query()
            ->where('status', NomadLocation::STATUS_ACTIVE)
            ->with([
                'destination.region',
                'facilities' => fn ($q) => $q->where('is_active', true)->with(['rates' => fn ($rq) => $rq->where('is_active', true)->orderBy('valid_from')]),
            ])
            ->orderBy('name')
            ->get();

        $nomadTours = Tour::query()
            ->where('status', Tour::STATUS_PUBLISHED)
            ->where('supports_nomad_addon', true)
            ->orderByDesc('is_featured')
            ->get();

        return view('nomads.index', [
            'locations' => $locations,
            'nomadTours' => $nomadTours,
        ]);
    }
}

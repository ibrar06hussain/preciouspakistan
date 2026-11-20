<?php

namespace App\Modules\Fleet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fleet\Models\Vehicle;
use Illuminate\View\View;

class PublicFleetController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::query()
            ->where('status', Vehicle::STATUS_ACTIVE)
            ->with(['seasonalRates' => fn ($q) => $q->where('is_active', true)->orderBy('valid_from')])
            ->orderBy('passenger_capacity')
            ->orderBy('name')
            ->get();

        return view('fleet.index', [
            'vehicles' => $vehicles,
        ]);
    }
}

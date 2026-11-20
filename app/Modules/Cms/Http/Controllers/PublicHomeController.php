<?php

namespace App\Modules\Cms\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Cms\Services\SiteSettingsService;
use Illuminate\View\View;

class PublicHomeController extends Controller
{
    public function __invoke(SiteSettingsService $settings): View
    {
        return view('public.home', [
            'site' => $settings->allPublic(),
        ]);
    }
}

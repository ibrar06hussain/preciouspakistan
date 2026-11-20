<?php

namespace App\Modules\Destinations\Http\Middleware;

use App\Modules\Destinations\Models\UrlRedirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HandleUrlRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $trimmed = trim(strtolower($request->getPathInfo()), '/');
        $path = $trimmed === '' ? '/' : '/'.$trimmed;

        if ($path === '/' || str_starts_with($path, '/admin') || str_starts_with($path, '/livewire')) {
            return $next($request);
        }

        if (Schema::hasTable('url_redirects')) {
            $redirect = UrlRedirect::query()
                ->where('source_path', $path)
                ->where('is_active', true)
                ->first();

            if ($redirect !== null && $redirect->target_path !== $path) {
                $redirect->increment('hits');

                return redirect($redirect->target_path, $redirect->status_code === 302 ? 302 : 301);
            }
        }

        return $next($request);
    }
}

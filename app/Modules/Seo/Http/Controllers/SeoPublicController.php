<?php

namespace App\Modules\Seo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Seo\Services\SeoService;
use Illuminate\Http\Response;

final class SeoPublicController extends Controller
{
    public function __construct(
        private readonly SeoService $seo,
    ) {}

    public function sitemap(): Response
    {
        $entries = $this->seo->sitemapEntries();
        $xml = ['<?xml version="1.0" encoding="UTF-8"?>', '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($entries as $entry) {
            $loc = htmlspecialchars($entry['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $lastmod = htmlspecialchars($entry['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $priority = htmlspecialchars($entry['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $xml[] = "  <url><loc>{$loc}</loc><lastmod>{$lastmod}</lastmod><priority>{$priority}</priority></url>";
        }

        $xml[] = '</urlset>';

        return response(implode("\n", $xml), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $sitemapUrl = route('seo.sitemap');
        $body = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /cms/preview/',
            'Allow: /',
            '',
            "Sitemap: {$sitemapUrl}",
        ]);

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}

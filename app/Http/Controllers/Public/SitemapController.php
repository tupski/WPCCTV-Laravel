<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SitemapBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class SitemapController extends Controller
{
    public function __construct(protected SitemapBuilder $sitemap) {}

    public function index(): Response
    {
        // Absolute URLs must reflect the canonical host, not whatever Host
        // header the crawler happened to send.
        URL::forceRootUrl((string) config('app.url'));

        return response($this->sitemap->xml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * robots.txt is served dynamically so the Sitemap directive always points
     * at the canonical host instead of a hardcoded domain.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /login',
            'Disallow: /track/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
            '',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}

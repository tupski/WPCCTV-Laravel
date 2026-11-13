<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\SeoMetadataResolver;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(protected SeoMetadataResolver $seo)
    {
    }

    public function index(): View
    {
        $services = Service::published()->with('seo')->get();

        return view('public.services.index', compact('services'));
    }

    public function show(string $slug): View
    {
        $service = Service::published()
            ->where('slug', $slug)
            ->with('seo')
            ->firstOrFail();

        $seoData = $this->seo->resolve($service);

        return view('public.services.show', compact('service', 'seoData'));
    }
}

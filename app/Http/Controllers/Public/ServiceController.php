<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Package;
use App\Models\Service;
use App\Services\SeoMetadataResolver;
use App\Services\StructuredData;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        protected SeoMetadataResolver $seo,
        protected StructuredData $schema,
    ) {}

    public function index(): View
    {
        $services = Service::published()->with('seo')->orderBy('id')->get();

        $seoData = $this->seo->resolve(new Service(), [
            'seo_title' => 'Layanan CCTV | WP CCTV Indonesia',
            'meta_description' => 'Layanan survei, instalasi, perbaikan, pemeliharaan, upgrade, dan pelatihan sistem CCTV untuk rumah, toko, kantor, gudang, sekolah, dan pabrik.',
        ]);

        return view('public.services.index', [
            'services' => $services,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'Layanan', 'url' => route('services.index')],
                ]),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $service = Service::published()
            ->where('slug', $slug)
            ->with('seo')
            ->firstOrFail();

        $relatedServices = Service::published()
            ->whereKeyNot($service->getKey())
            ->orderBy('id')
            ->limit(3)
            ->get();

        // Installation pages are the natural place to surface packages.
        $relatedPackages = $service->type === 'installation'
            ? Package::published()->with('brand')->orderByRaw('price is null')->orderBy('price')->limit(3)->get()
            : collect();

        $faqs = Faq::published()->forContext($service)->get();

        $seoData = $this->seo->resolve($service);

        return view('public.services.show', [
            'service' => $service,
            'relatedServices' => $relatedServices,
            'relatedPackages' => $relatedPackages,
            'faqs' => $faqs,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->service($service),
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'Layanan', 'url' => route('services.index')],
                    ['name' => $service->title, 'url' => route('services.show', $service->slug)],
                ]),
                $this->schema->faqPage($faqs),
            ],
        ]);
    }
}

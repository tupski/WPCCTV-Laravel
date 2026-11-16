<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BlogArticle;
use App\Models\Faq;
use App\Models\Package;
use App\Models\PackageUsageCategory;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\Service;
use App\Services\SeoMetadataResolver;
use App\Services\StructuredData;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected SeoMetadataResolver $seo,
        protected StructuredData $schema,
    ) {}

    public function index(): View
    {
        $packages = Package::published()
            ->with(['brand', 'categories', 'usageCategories', 'seo'])
            ->orderByRaw('price is null')
            ->orderBy('price')
            ->limit(3)
            ->get();

        $services = Service::published()->with('seo')->orderBy('id')->limit(3)->get();

        $brands = ProductBrand::query()
            ->whereHas('products', fn ($query) => $query->where('published', true))
            ->orderBy('name')
            ->get();

        $products = Product::published()->with('brand')->latest()->limit(6)->get();

        $articles = BlogArticle::published()
            ->with(['category', 'author', 'seo'])
            ->latest('published_at')
            ->limit(3)
            ->get();

        $faqs = Faq::published()->whereNull('context_type')->orderBy('sort_order')->limit(5)->get();

        $usageCategories = PackageUsageCategory::query()
            ->whereHas('packages', fn ($query) => $query->where('published', true))
            ->orderBy('name')
            ->get();

        $seoData = $this->seo->resolve(new Package(), [
            'seo_title' => 'WP CCTV Indonesia | Instalasi CCTV Rumah, Toko, dan Kantor',
            'meta_description' => 'Jasa survei, instalasi, dan perbaikan CCTV untuk rumah, toko, kantor, gudang, sekolah, dan pabrik. Konsultasi kebutuhan CCTV Anda langsung via WhatsApp.',
        ]);

        return view('public.home', [
            'packages' => $packages,
            'services' => $services,
            'brands' => $brands,
            'products' => $products,
            'articles' => $articles,
            'faqs' => $faqs,
            'usageCategories' => $usageCategories,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->organization(),
                $this->schema->faqPage($faqs),
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Package;
use App\Models\PackageCategory;
use App\Models\PackageUsageCategory;
use App\Services\SeoMetadataResolver;
use App\Services\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    /**
     * Camera quantities the catalog is organised around (PRD §5.4).
     */
    public const CAMERA_QUANTITIES = [1, 2, 4, 8, 16];

    public function __construct(
        protected SeoMetadataResolver $seo,
        protected StructuredData $schema,
    ) {}

    public function index(Request $request): View
    {
        $query = Package::published()->with(['brand', 'categories', 'usageCategories', 'seo']);

        $categorySlug = $this->stringOrNull($request->query('category'));
        $usageSlug = $this->stringOrNull($request->query('usage'));
        $cameras = $request->query('cameras');

        if ($categorySlug !== null) {
            $query->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($usageSlug !== null) {
            $query->whereHas('usageCategories', fn ($q) => $q->where('slug', $usageSlug));
        }

        if (is_numeric($cameras) && in_array((int) $cameras, self::CAMERA_QUANTITIES, true)) {
            $query->where('camera_quantity', (int) $cameras);
        }

        $packages = $query
            ->orderByRaw('price is null')
            ->orderBy('price')
            ->paginate(12)
            ->appends($request->query());

        $categories = PackageCategory::query()
            ->whereHas('packages', fn ($q) => $q->where('published', true))
            ->orderBy('name')
            ->get();

        $usageCategories = PackageUsageCategory::query()
            ->whereHas('packages', fn ($q) => $q->where('published', true))
            ->orderBy('name')
            ->get();

        $activeUsage = $usageCategories->firstWhere('slug', $usageSlug);

        $seoData = $this->seo->resolve(new Package(), [
            'seo_title' => $activeUsage !== null
                ? 'Paket CCTV untuk '.$activeUsage->name.' | WP CCTV Indonesia'
                : 'Paket Instalasi CCTV untuk Rumah dan Bisnis | WP CCTV Indonesia',
            'meta_description' => 'Temukan paket instalasi CCTV berdasarkan jumlah kamera dan kebutuhan lokasi Anda. Harga transparan, pengerjaan rapi, dan konsultasi langsung via WhatsApp.',
            'canonical' => $categorySlug !== null || $usageSlug !== null || $cameras !== null
                ? route('packages.index')
                : null,
        ]);

        return view('public.packages.index', [
            'packages' => $packages,
            'categories' => $categories,
            'usageCategories' => $usageCategories,
            'cameraQuantities' => self::CAMERA_QUANTITIES,
            'activeCategorySlug' => $categorySlug,
            'activeUsageSlug' => $usageSlug,
            'activeCameras' => is_numeric($cameras) ? (int) $cameras : null,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'Paket CCTV', 'url' => route('packages.index')],
                ]),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $package = Package::published()
            ->where('slug', $slug)
            ->with(['brand', 'seo', 'categories', 'usageCategories', 'faqs'])
            ->firstOrFail();

        // Alternatives: closest by camera quantity first, then any other
        // published package so the section is never empty.
        $relatedPackages = Package::published()
            ->whereKeyNot($package->getKey())
            ->with('brand')
            ->orderByRaw('abs(coalesce(camera_quantity, 0) - ?) asc', [$package->camera_quantity ?? 0])
            ->limit(3)
            ->get();

        $faqs = Faq::published()->forContext($package)->get();

        $seoData = $this->seo->resolve($package);

        return view('public.packages.show', [
            'package' => $package,
            'relatedPackages' => $relatedPackages,
            'faqs' => $faqs,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->package($package),
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'Paket CCTV', 'url' => route('packages.index')],
                    ['name' => $package->title, 'url' => route('packages.show', $package->slug)],
                ]),
                $this->schema->faqPage($faqs),
            ],
        ]);
    }

    protected function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Services\SeoMetadataResolver;
use App\Services\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    /**
     * Camera type values offered by the catalog. Kept here rather than derived
     * from the table so the filter list does not change whenever an editor
     * saves a new value; the filter itself accepts any stored string.
     */
    public const CAMERA_TYPES = [
        'indoor' => 'Indoor',
        'outdoor' => 'Outdoor',
        'dome' => 'Dome',
        'bullet' => 'Bullet',
        'ptz' => 'PTZ',
        'nvr' => 'NVR',
        'dvr' => 'DVR',
        'aksesoris' => 'Aksesori',
    ];

    public function __construct(
        protected SeoMetadataResolver $seo,
        protected StructuredData $schema,
    ) {}

    public function index(Request $request): View
    {
        $query = Product::published()->with(['brand', 'seo']);

        $brandSlug = $this->stringOrNull($request->query('brand'));
        $cameraType = $this->stringOrNull($request->query('camera_type'));

        if ($brandSlug !== null) {
            $query->whereHas('brand', fn ($q) => $q->where('slug', $brandSlug));
        }

        if ($cameraType !== null) {
            $query->where('camera_type', $cameraType);
        }

        $products = $query->latest()->paginate(12)->appends($request->query());

        // Only brands with published products are offered as filters: linking
        // to an empty result set would be a dead end.
        $brands = ProductBrand::query()
            ->whereHas('products', fn ($q) => $q->where('published', true))
            ->orderBy('name')
            ->get();

        $activeBrand = $brands->firstWhere('slug', $brandSlug);

        $seoData = $this->seo->resolve(new Product(), [
            'seo_title' => $activeBrand !== null
                ? 'Produk CCTV '.$activeBrand->name.' | WP CCTV Indonesia'
                : 'Katalog Produk CCTV | WP CCTV Indonesia',
            'meta_description' => 'Referensi perangkat CCTV untuk kebutuhan instalasi rumah, toko, kantor, gudang, sekolah, dan pabrik. Konsultasikan pemilihan kamera melalui WhatsApp.',
            'canonical' => $brandSlug !== null || $cameraType !== null
                ? route('products.index')
                : null,
        ]);

        return view('public.products.index', [
            'products' => $products,
            'brands' => $brands,
            'cameraTypes' => self::CAMERA_TYPES,
            'activeBrand' => $activeBrand,
            'activeCameraType' => $cameraType,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'Produk', 'url' => route('products.index')],
                ]),
            ],
        ]);
    }

    public function show(string $slug): View
    {
        $product = Product::published()
            ->where('slug', $slug)
            ->with(['brand', 'seo', 'faqs'])
            ->firstOrFail();

        // Related products share the brand, but the same camera type is a
        // better signal when the brand catalog is thin.
        $relatedProducts = Product::published()
            ->whereKeyNot($product->getKey())
            ->where(function ($query) use ($product): void {
                $query->where('brand_id', $product->brand_id);

                if ($product->camera_type !== null) {
                    $query->orWhere('camera_type', $product->camera_type);
                }
            })
            ->with('brand')
            ->limit(3)
            ->get();

        $relatedPackages = \App\Models\Package::published()
            ->with('brand')
            ->orderByRaw('price is null')
            ->orderBy('price')
            ->limit(3)
            ->get();

        $faqs = Faq::published()
            ->forContext($product)
            ->get();

        $seoData = $this->seo->resolve($product);

        return view('public.products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'relatedPackages' => $relatedPackages,
            'faqs' => $faqs,
            'seoData' => $seoData,
            'jsonLd' => [
                $this->schema->product($product),
                $this->schema->breadcrumbs([
                    ['name' => 'Beranda', 'url' => route('home')],
                    ['name' => 'Produk', 'url' => route('products.index')],
                    ['name' => $product->name, 'url' => route('products.show', $product->slug)],
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

<?php

use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\BlogTagController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\MediaAssetController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PackageCategoryController;
use App\Http\Controllers\Admin\PackageUsageCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\SeoMetadataController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Public\BlogController as PublicBlogController;
use App\Http\Controllers\Public\FaqController as PublicFaqController;
use App\Http\Controllers\Public\PageController as PublicPageController;
use App\Http\Controllers\Public\PackageController as PublicPackageController;
use App\Http\Controllers\Public\ProductController as PublicProductController;
use App\Http\Controllers\Public\ServiceController as PublicServiceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'public.home')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

// ── Public content routes ──────────────────────────────────────────
Route::prefix('products')->name('products.')->group(function (): void {
    Route::get('/', [PublicProductController::class, 'index'])->name('index');
    Route::get('/{slug}', [PublicProductController::class, 'show'])->where('slug', '[0-9a-z-]+');
});

Route::prefix('packages')->name('packages.')->group(function (): void {
    Route::get('/', [PublicPackageController::class, 'index'])->name('index');
    Route::get('/{slug}', [PublicPackageController::class, 'show'])->where('slug', '[0-9a-z-]+');
});

Route::prefix('services')->name('services.')->group(function (): void {
    Route::get('/', [PublicServiceController::class, 'index'])->name('index');
    Route::get('/{slug}', [PublicServiceController::class, 'show'])->where('slug', '[0-9a-z-]+');
});

Route::prefix('blog')->name('blog.')->group(function (): void {
    Route::get('/', [PublicBlogController::class, 'index'])->name('index');
    Route::get('/{slug}', [PublicBlogController::class, 'show'])->where('slug', '[0-9a-z-]+');
});

Route::prefix('faq')->name('faqs.')->group(function (): void {
    Route::get('/', [PublicFaqController::class, 'index'])->name('index');
});

Route::prefix('pages')->name('pages.')->group(function (): void {
    Route::get('/{slug}', [PublicPageController::class, 'show'])->where('slug', '[0-9a-z-]+');
});

// ── Authenticated + admin routes ──────────────────────────────────
Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function (): void {
        Route::view('/', 'admin.home')->name('home');

        // Products
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::post('products', [ProductController::class, 'store'])->name('products.store');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        // Packages
        Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
        Route::post('packages', [PackageController::class, 'store'])->name('packages.store');
        Route::get('packages/{package}', [PackageController::class, 'show'])->name('packages.show');
        Route::put('packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::delete('packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');

        // Package categories
        Route::get('package-categories', [PackageCategoryController::class, 'index'])->name('package-categories.index');
        Route::post('package-categories', [PackageCategoryController::class, 'store'])->name('package-categories.store');
        Route::put('package-categories/{category}', [PackageCategoryController::class, 'update'])->name('package-categories.update');
        Route::delete('package-categories/{category}', [PackageCategoryController::class, 'destroy'])->name('package-categories.destroy');

        // Package usage categories
        Route::get('package-usage-categories', [PackageUsageCategoryController::class, 'index'])->name('package-usage-categories.index');
        Route::post('package-usage-categories', [PackageUsageCategoryController::class, 'store'])->name('package-usage-categories.store');
        Route::put('package-usage-categories/{usage_category}', [PackageUsageCategoryController::class, 'update'])->name('package-usage-categories.update');
        Route::delete('package-usage-categories/{usage_category}', [PackageUsageCategoryController::class, 'destroy'])->name('package-usage-categories.destroy');

        // Services
        Route::get('services', [ServiceController::class, 'index'])->name('services.index');
        Route::post('services', [ServiceController::class, 'store'])->name('services.store');
        Route::get('services/{service}', [ServiceController::class, 'show'])->name('services.show');
        Route::put('services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        // Blog
        Route::get('blog', [BlogController::class, 'index'])->name('blog.index');
        Route::post('blog', [BlogController::class, 'store'])->name('blog.store');
        Route::get('blog/{article}', [BlogController::class, 'show'])->name('blog.show');
        Route::put('blog/{article}', [BlogController::class, 'update'])->name('blog.update');
        Route::delete('blog/{article}', [BlogController::class, 'destroy'])->name('blog.destroy');

        // Blog categories
        Route::get('blog-categories', [BlogCategoryController::class, 'index'])->name('blog-categories.index');
        Route::post('blog-categories', [BlogCategoryController::class, 'store'])->name('blog-categories.store');
        Route::put('blog-categories/{category}', [BlogCategoryController::class, 'update'])->name('blog-categories.update');
        Route::delete('blog-categories/{category}', [BlogCategoryController::class, 'destroy'])->name('blog-categories.destroy');

        // Blog tags
        Route::get('blog-tags', [BlogTagController::class, 'index'])->name('blog-tags.index');
        Route::post('blog-tags', [BlogTagController::class, 'store'])->name('blog-tags.store');
        Route::put('blog-tags/{tag}', [BlogTagController::class, 'update'])->name('blog-tags.update');
        Route::delete('blog-tags/{tag}', [BlogTagController::class, 'destroy'])->name('blog-tags.destroy');

        // FAQs
        Route::get('faqs', [FaqController::class, 'index'])->name('faqs.index');
        Route::post('faqs', [FaqController::class, 'store'])->name('faqs.store');
        Route::put('faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update');
        Route::delete('faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy');

        // SEO metadata
        Route::get('seo', [SeoMetadataController::class, 'index'])->name('seo.index');
        Route::post('seo', [SeoMetadataController::class, 'store'])->name('seo.store');
        Route::put('seo/{seoMetadata}', [SeoMetadataController::class, 'update'])->name('seo.update');
        Route::delete('seo/{seoMetadata}', [SeoMetadataController::class, 'destroy'])->name('seo.destroy');

        // Media
        Route::get('media', [MediaAssetController::class, 'index'])->name('media.index');
        Route::post('media', [MediaAssetController::class, 'store'])->name('media.store');
        Route::delete('media/{asset}', [MediaAssetController::class, 'destroy'])->name('media.destroy');

        // Pages
        Route::get('pages', [PageController::class, 'index'])->name('pages.index');
        Route::post('pages', [PageController::class, 'store'])->name('pages.store');
        Route::get('pages/{page}', [PageController::class, 'show'])->name('pages.show');
        Route::put('pages/{page}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('pages/{page}', [PageController::class, 'destroy'])->name('pages.destroy');

        // Product brands
        Route::get('product-brands', [BrandController::class, 'productBrands'])->name('product-brands.index');
        Route::post('product-brands', [BrandController::class, 'storeProductBrand'])->name('product-brands.store');
        Route::put('product-brands/{product_brand}', [BrandController::class, 'updateProductBrand'])->name('product-brands.update');
        Route::delete('product-brands/{product_brand}', [BrandController::class, 'destroyProductBrand'])->name('product-brands.destroy');

        // Package brands
        Route::get('package-brands', [BrandController::class, 'packageBrands'])->name('package-brands.index');
        Route::post('package-brands', [BrandController::class, 'storePackageBrand'])->name('package-brands.store');
        Route::put('package-brands/{package_brand}', [BrandController::class, 'updatePackageBrand'])->name('package-brands.update');
        Route::delete('package-brands/{package_brand}', [BrandController::class, 'destroyPackageBrand'])->name('package-brands.destroy');
    });
});

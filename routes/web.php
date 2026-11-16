<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ActivityLogViewController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\AnalyticsViewController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\BlogTagController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\LeadActivityController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\MediaAssetController;
use App\Http\Controllers\Admin\NotificationPreferencesController;
use App\Http\Controllers\Admin\PackageCategoryController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PackageUsageCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeadViewController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\PushSettingsViewController;
use App\Http\Controllers\Admin\PushSubscriptionController;
use App\Http\Controllers\Admin\SeoMetadataController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SettingsViewController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Public\BlogController as PublicBlogController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\FaqController as PublicFaqController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PackageController as PublicPackageController;
use App\Http\Controllers\Public\PageController as PublicPageController;
use App\Http\Controllers\Public\ProductController as PublicProductController;
use App\Http\Controllers\Public\ServiceController as PublicServiceController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\WhatsAppTrackingController;
use App\Http\Middleware\RecordPageView;
use Illuminate\Support\Facades\Route;

// ── Public website ─────────────────────────────────────────────────
// Server-rendered Blade, Turbo navigation, RecordPageView on every public
// content GET so analytics see the whole public surface and nothing else.

// RecordPageView is applied to the home route individually as well as the
// content group below: analytics must see the homepage render.
Route::get('/', [HomeController::class, 'index'])
    ->middleware(RecordPageView::class)
    ->name('home');

Route::get('/kontak', [ContactController::class, 'show'])->name('contact.show');
Route::post('/kontak', [ContactController::class, 'store'])
    ->middleware('throttle:public-lead')
    ->name('contact.store');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

// ── Public content routes ──────────────────────────────────────────
// `RecordPageView` is applied here only: analytics records public renders,
// never admin/auth/tracking endpoints (also re-checked inside the middleware).
Route::middleware(RecordPageView::class)->group(function (): void {
    Route::prefix('produk')->name('products.')->group(function (): void {
        Route::get('/', [PublicProductController::class, 'index'])->name('index');
        Route::get('/{slug}', [PublicProductController::class, 'show'])->name('show')->where('slug', '[0-9a-z-]+');
    });

    Route::prefix('paket-cctv')->name('packages.')->group(function (): void {
        Route::get('/', [PublicPackageController::class, 'index'])->name('index');
        Route::get('/{slug}', [PublicPackageController::class, 'show'])->name('show')->where('slug', '[0-9a-z-]+');
    });

    Route::prefix('layanan')->name('services.')->group(function (): void {
        Route::get('/', [PublicServiceController::class, 'index'])->name('index');
        Route::get('/{slug}', [PublicServiceController::class, 'show'])->name('show')->where('slug', '[0-9a-z-]+');
    });

    Route::prefix('blog')->name('blog.')->group(function (): void {
        Route::get('/', [PublicBlogController::class, 'index'])->name('index');
        Route::get('/{slug}', [PublicBlogController::class, 'show'])->name('show')->where('slug', '[0-9a-z-]+');
    });

    Route::prefix('faq')->name('faqs.')->group(function (): void {
        Route::get('/', [PublicFaqController::class, 'index'])->name('index');
    });

    Route::prefix('halaman')->name('pages.')->group(function (): void {
        Route::get('/{slug}', [PublicPageController::class, 'show'])->name('show')->where('slug', '[0-9a-z-]+');
    });
});

// ── WhatsApp first-party tracking endpoint ───────────────────────
// GET request, CSRF-exempt by default. Rate limited per visitor+context.
Route::get('track/whatsapp', [WhatsAppTrackingController::class, 'track'])
    ->middleware('throttle:whatsapp-tracking')
    ->name('whatsapp.track');


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

        // Push subscriptions (own subscription scoping is enforced in the controller)
        Route::get('push-subscriptions', [PushSubscriptionController::class, 'index'])->name('push-subscriptions.index');
        Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
        Route::delete('push-subscriptions/{subscription}', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
        Route::post('push-subscriptions/{subscription}/toggle', [PushSubscriptionController::class, 'toggleActive'])->name('push-subscriptions.toggle');

        // Notification preferences (own preferences only, user_id server-forced)
        Route::get('notification-preferences', [NotificationPreferencesController::class, 'index'])->name('notification-preferences.index');
        Route::put('notification-preferences', [NotificationPreferencesController::class, 'update'])->name('notification-preferences.update');

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

    // ── Lead management ───────────────────────────────────────────────
    // `manage-leads` (not `admin`): lead_manager must reach these endpoints.
    // Per-lead scoping is enforced by LeadPolicy.
    Route::prefix('admin')->name('admin.')->middleware('can:manage-leads')->group(function (): void {
        Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
        Route::post('leads', [LeadController::class, 'store'])->name('leads.store');
        Route::post('leads/convert', [LeadController::class, 'convert'])->name('leads.convert');
        Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');
        Route::put('leads/{lead}', [LeadController::class, 'update'])->name('leads.update');
        Route::delete('leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy');

        Route::get('leads/{lead}/timeline', [LeadActivityController::class, 'index'])->name('leads.timeline.index');
        Route::post('leads/{lead}/activities', [LeadActivityController::class, 'store'])->name('leads.activities.store');
    });

    // ── Analytics (read-only JSON, `view-analytics`) ──────────────────
    // analyst reaches these; content_editor and lead_manager do not.
    Route::prefix('admin')->name('admin.')->middleware('can:view-analytics')->group(function (): void {
        Route::get('analytics/overview', [AnalyticsController::class, 'overview'])->name('analytics.overview');
        Route::get('analytics/whatsapp-clicks', [AnalyticsController::class, 'whatsappClicks'])->name('analytics.whatsapp-clicks');
        Route::get('analytics/leads', [AnalyticsController::class, 'leads'])->name('analytics.leads');
    });

    // ── Activity logs (`view-activity-logs`) ──────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('can:view-activity-logs')->group(function (): void {
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });

    // ── Settings (`manage-settings`) ──────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('can:manage-settings')->group(function (): void {
        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });

    // ── Server-rendered admin UI (WP9) ────────────────────────────────
    // Thin HTML controllers over the same services as the JSON APIs above.
    // Existing JSON routes are untouched; the `-ui` suffix keeps both worlds
    // addressable without a collision.

    Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function (): void {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

    Route::prefix('admin/leads-ui')->name('admin.leads-ui.')->middleware('can:manage-leads')->group(function (): void {
        Route::get('/', [LeadViewController::class, 'index'])->name('index');
        Route::get('convert/{whatsappClick}', [LeadViewController::class, 'convertForm'])->name('convert.form');
        Route::post('convert/{whatsappClick}', [LeadViewController::class, 'convert'])->name('convert.store');
        Route::get('{lead}', [LeadViewController::class, 'show'])->name('show');
        Route::patch('{lead}/status', [LeadViewController::class, 'updateStatus'])->name('status.update');
        Route::post('{lead}/activities', [LeadViewController::class, 'storeNote'])->name('activities.store');
        Route::patch('{lead}/assignee', [LeadViewController::class, 'assign'])->name('assignee.update');
        Route::patch('{lead}/follow-up', [LeadViewController::class, 'scheduleFollowUp'])->name('follow-up.update');
    });

    Route::prefix('admin/analytics-ui')->name('admin.analytics-ui.')->middleware('can:view-analytics')->group(function (): void {
        Route::get('/', [AnalyticsViewController::class, 'index'])->name('index');
        Route::get('whatsapp-clicks', [AnalyticsViewController::class, 'whatsappClicks'])->name('whatsapp-clicks');
    });

    Route::prefix('admin')->name('admin.')->middleware('can:view-activity-logs')->group(function (): void {
        Route::get('activity-logs-ui', [ActivityLogViewController::class, 'index'])->name('activity-logs-ui.index');
    });

    Route::prefix('admin/settings-ui')->name('admin.settings-ui.')->middleware('can:manage-settings')->group(function (): void {
        Route::get('/', [SettingsViewController::class, 'edit'])->name('edit');
        Route::put('/', [SettingsViewController::class, 'update'])->name('update');
    });

    Route::prefix('admin/push-settings')->name('admin.push-settings.')->middleware('can:admin')->group(function (): void {
        Route::get('/', [PushSettingsViewController::class, 'index'])->name('index');
        Route::put('/', [PushSettingsViewController::class, 'update'])->name('update');
    });
});

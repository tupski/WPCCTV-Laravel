<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AnalyticsDaily;
use App\Models\AnalyticsPageView;
use App\Models\BlogArticle;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Faq;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\NotificationPreference;
use App\Models\Page;
use App\Models\Package;
use App\Models\PackageBrand;
use App\Models\PackageCategory;
use App\Models\PackageUsageCategory;
use App\Models\PushSubscription;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\Service;
use App\Models\Setting;
use App\Models\SeoMetadata;
use App\Models\User;
use App\Models\WhatsAppClick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_belongs_to_brand(): void
    {
        $brand = ProductBrand::factory()->create();
        $product = Product::factory()->create(['brand_id' => $brand->id]);

        $this->assertSame($brand->id, $product->brand->id);
        $this->assertTrue($product->brand->is($brand));
    }

    public function test_product_has_seo_metadata(): void
    {
        $product = Product::factory()->create();
        $seo = SeoMetadata::factory()->create([
            'seoable_type' => (new Product())->getMorphClass(),
            'seoable_id' => $product->id,
            'seo_title' => 'Custom SEO Title',
        ]);

        $this->assertTrue($product->seo->is($seo));
        $this->assertSame('Custom SEO Title', $product->seo->seo_title);
    }

    public function test_package_belongs_to_brand_and_categories(): void
    {
        $brand = PackageBrand::factory()->create();
        $category = PackageCategory::factory()->create();
        $usage = PackageUsageCategory::factory()->create();
        $package = Package::factory()->create(['brand_id' => $brand->id]);

        $package->categories()->attach($category->id);
        $package->usageCategories()->attach($usage->id);

        $this->assertCount(1, $package->categories()->get());
        $this->assertCount(1, $package->usageCategories()->get());
        $this->assertTrue($package->brand->is($brand));
    }

    public function test_service_belongs_to_faqs(): void
    {
        $service = Service::factory()->create();
        $faq = Faq::factory()->create([
            'context_type' => (new Service())->getMorphClass(),
            'context_id' => $service->id,
        ]);

        $this->assertCount(1, $service->faqs()->get());
        $this->assertTrue($faq->context->is($service));
    }

    public function test_blog_article_has_author_category_and_tags(): void
    {
        $user = User::factory()->create();
        $category = BlogCategory::factory()->create();
        $tag = BlogTag::factory()->create();
        $article = BlogArticle::factory()->published()->create([
            'user_id' => $user->id,
            'blog_category_id' => $category->id,
        ]);

        $article->tags()->attach($tag->id);

        $this->assertTrue($article->author->is($user));
        $this->assertTrue($article->category->is($category));
        $this->assertCount(1, $article->tags);
        $this->assertTrue($article->tags->first()->is($tag));
    }

    public function test_page_has_seo_and_faqs(): void
    {
        $page = Page::factory()->create();
        $faq = Faq::factory()->create([
            'context_type' => (new Page())->getMorphClass(),
            'context_id' => $page->id,
        ]);

        $this->assertCount(1, $page->faqs()->get());
    }

    public function test_faq_context_morph_to_resolves(): void
    {
        $product = Product::factory()->create();
        $faq = Faq::factory()->create([
            'context_type' => $product->getMorphClass(),
            'context_id' => $product->id,
            'question' => 'What is this product?',
        ]);

        $this->assertTrue($faq->context->is($product));
    }

    // ── Business dashboard model relationships ─────────────────────────

    public function test_lead_belongs_to_whatsapp_click_and_user(): void
    {
        $click = WhatsAppClick::factory()->create();
        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'whatsapp_click_id' => $click->id,
            'assigned_to' => $user->id,
        ]);

        $this->assertTrue($lead->whatsappClick->is($click));
        $this->assertTrue($lead->assignedTo->is($user));
    }

    public function test_whatsapp_click_has_one_lead(): void
    {
        $click = WhatsAppClick::factory()->create();
        $lead = Lead::factory()->create(['whatsapp_click_id' => $click->id]);

        $this->assertTrue($click->lead->is($lead));
    }

    public function test_lead_has_many_activities(): void
    {
        $lead = Lead::factory()->create();
        $activity = LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'action' => LeadActivity::ACTION_STATUS_CHANGE,
            'field' => 'status',
            'old_value' => 'new',
            'new_value' => 'contacted',
        ]);

        $this->assertCount(1, $lead->activities);
        $this->assertTrue($lead->activities->first()->is($activity));
        $this->assertTrue($activity->lead->is($lead));
    }

    public function test_lead_activity_belongs_to_user_and_whatsapp_click(): void
    {
        $lead = Lead::factory()->create();
        $user = User::factory()->create();
        $click = WhatsAppClick::factory()->create();

        $activity = LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'user_id' => $user->id,
            'whatsapp_click_id' => $click->id,
        ]);

        $this->assertTrue($activity->user->is($user));
        $this->assertTrue($activity->whatsappClick->is($click));
    }

    public function test_push_subscription_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $sub = PushSubscription::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($sub->user->is($user));
    }

    public function test_notification_preference_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $pref = NotificationPreference::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($pref->user->is($user));
        $this->assertTrue(NotificationPreference::forUser($user->id)->is($pref));
    }

    public function test_activity_log_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $log = ActivityLog::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($log->user->is($user));
    }

    public function test_analytics_daily_belongs_to_top_product_package_article(): void
    {
        $product = Product::factory()->create();
        $package = Package::factory()->create();
        $article = BlogArticle::factory()->create();

        $daily = AnalyticsDaily::factory()->create([
            'top_product_id' => $product->id,
            'top_package_id' => $package->id,
            'top_article_id' => $article->id,
        ]);

        $this->assertTrue($daily->topProduct->is($product));
        $this->assertTrue($daily->topPackage->is($package));
        $this->assertTrue($daily->topArticle->is($article));
    }

    public function test_analytics_page_view_scope(): void
    {
        AnalyticsPageView::factory()->fromProduct()->create();
        AnalyticsPageView::factory()->fromProduct()->create();
        AnalyticsPageView::factory()->create(['page_type' => 'home']);

        $this->assertSame(2, AnalyticsPageView::fromType('product')->count());
        $this->assertSame(1, AnalyticsPageView::fromType('home')->count());
    }

    public function test_setting_get_and_set_helpers(): void
    {
        $user = User::factory()->create();

        Setting::set('whatsapp_number', '6281234567890', $user->id);
        $this->assertSame('6281234567890', Setting::get('whatsapp_number'));
        $this->assertSame('fallback', Setting::get('nonexistent_key', 'fallback'));

        $setting = Setting::where('key', 'whatsapp_number')->first();
        $this->assertTrue($setting->updatedBy->is($user));
    }
}

<?php

namespace Tests\Feature;

use App\Models\BlogArticle;
use App\Models\Page;
use App\Models\Package;
use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Models\WhatsAppClick;
use App\Services\WhatsAppTrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for PHASE_4_CLOSURE_AUDIT.md C3.
 *
 * Root cause confirmed at app/Services/WhatsAppTrackingService.php:101 —
 * `config('app.url').ltrim($path, '/')` stripped the leading slash and
 * concatenated, producing `http://localhostproducts/slug` for every non-home
 * context. The corrupted value was stored as the `page_url` analytics
 * dimension and embedded in the prefilled WhatsApp message.
 */
class EntityUrlTest extends TestCase
{
    use RefreshDatabase;

    private function service(): WhatsAppTrackingService
    {
        return app(WhatsAppTrackingService::class);
    }

    /**
     * Every non-home page type must produce host + exactly one slash + path.
     */
    public function test_every_page_type_joins_host_and_path_with_exactly_one_slash(): void
    {
        config(['app.url' => 'https://wpcctv.id']);

        $cases = [
            'product' => [new Product(['slug' => 'kamera-ip']), '/products/kamera-ip'],
            'package' => [new Package(['slug' => 'paket-4kamera']), '/packages/paket-4kamera'],
            'service' => [new Service(['slug' => 'instalasi']), '/services/instalasi'],
            'article' => [new BlogArticle(['slug' => 'tips-cctv']), '/blog/tips-cctv'],
            'page' => [new Page(['slug' => 'tentang-kami']), '/pages/tentang-kami'],
        ];

        foreach ($cases as $context => [$entity, $expectedPath]) {
            $url = $this->service()->entityUrl($entity, $context);

            $this->assertSame('https://wpcctv.id'.$expectedPath, $url, "Wrong URL for context [{$context}].");

            // The audit's specific defect: host and path fused together.
            $this->assertStringStartsWith('https://wpcctv.id/', $url, "Host/path boundary broken for [{$context}].");
            $this->assertStringContainsString('wpcctv.id/', $url);
            $this->assertStringContainsString('id'.$expectedPath, $url);
            $this->assertStringNotContainsString('idproducts', $url);
            $this->assertStringNotContainsString('//products', substr($url, strlen('https://')));
        }
    }

    /**
     * Home returns the bare base URL — no stray double slash, no trailing slash.
     */
    public function test_home_context_returns_the_base_url_without_trailing_slash(): void
    {
        config(['app.url' => 'https://wpcctv.id']);

        $this->assertSame('https://wpcctv.id', $this->service()->entityUrl(null, 'home'));
    }

    /**
     * A trailing slash on app.url must not double, and must not vanish.
     */
    public function test_trailing_slash_on_app_url_does_not_double_the_separator(): void
    {
        config(['app.url' => 'https://wpcctv.id/']);

        $url = $this->service()->entityUrl(new Product(['slug' => 'kamera-ip']), 'product');

        $this->assertSame('https://wpcctv.id/products/kamera-ip', $url);
        $this->assertStringNotContainsString('id//products', $url);

        $this->assertSame('https://wpcctv.id', $this->service()->entityUrl(null, 'home'));
    }

    /**
     * A subdirectory base URL must survive intact.
     */
    public function test_subdirectory_app_url_is_preserved(): void
    {
        config(['app.url' => 'https://wpcctv.id/shop/']);

        $this->assertSame(
            'https://wpcctv.id/shop/products/kamera-ip',
            $this->service()->entityUrl(new Product(['slug' => 'kamera-ip']), 'product')
        );

        $this->assertSame(
            'https://wpcctv.id/shop',
            $this->service()->entityUrl(null, 'home')
        );
    }

    /**
     * The malformed URL was persisted as an analytics dimension AND embedded
     * in the WhatsApp message. Both must be verified end to end.
     */
    public function test_stored_page_url_and_prefilled_message_both_carry_the_valid_url(): void
    {
        config(['app.url' => 'https://wpcctv.id']);
        Setting::set('whatsapp_number', '6281234567890');

        Product::factory()->published()->create(['slug' => 'kamera-ip', 'name' => 'Kamera IP 4MP']);

        $response = $this->get('/track/whatsapp?context=product&slug=kamera-ip');

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        // 1. Stored analytics dimension.
        $click = WhatsAppClick::firstOrFail();
        $this->assertSame('https://wpcctv.id/products/kamera-ip', $click->page_url);

        // 2. Prefilled message, urlencoded exactly once.
        $this->assertStringContainsString(
            rawurlencode('https://wpcctv.id/products/kamera-ip'),
            $location,
            'The prefilled WhatsApp message must carry the well-formed URL.'
        );
        $this->assertStringNotContainsString(rawurlencode('wpcctv.idproducts'), $location);
    }

    /**
     * joinUrl() is the contract that prevents the whole class of defect.
     */
    public function test_join_url_is_slash_agnostic_in_both_directions(): void
    {
        $join = fn (string $base, string $path): string => $this->service()->joinUrl($base, $path);

        $this->assertSame('https://h/p', $join('https://h', 'p'));
        $this->assertSame('https://h/p', $join('https://h', '/p'));
        $this->assertSame('https://h/p', $join('https://h/', 'p'));
        $this->assertSame('https://h/p', $join('https://h/', '/p'));
        $this->assertSame('https://h/sub/p', $join('https://h/sub/', '/p'));
        $this->assertSame('https://h', $join('https://h/', ''));
        $this->assertSame('https://h', $join('https://h', ''));
    }
}

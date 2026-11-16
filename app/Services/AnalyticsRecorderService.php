<?php

namespace App\Services;

use App\Models\AnalyticsPageView;
use App\Support\DeviceDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes a single analytics_page_views row per public page render.
 *
 * Contract:
 * - Never throws. Analytics must never break page rendering.
 * - Insert only. No synchronous aggregation.
 * - Anonymised: visitor token from the `wpc_visit` cookie (fallback: session
 *   id), referrer reduced to its host, no IP, no user agent stored.
 */
class AnalyticsRecorderService
{
    /**
     * Route-name prefix → page_type. Longest prefix wins.
     */
    public const PAGE_TYPE_MAP = [
        'home' => 'home',
        'products' => 'product',
        'packages' => 'package',
        'services' => 'service',
        'blog' => 'article',
        'faqs' => 'faq',
        'pages' => 'page',
    ];

    public function __construct(protected DeviceDetector $devices) {}

    public function recordPageView(Request $request, ?string $pageType = null): void
    {
        try {
            $type = $pageType ?? $this->resolvePageType($request);

            if ($type === null) {
                return;
            }

            AnalyticsPageView::create([
                'session_id' => $this->resolveSessionId($request),
                'page_url' => $this->resolvePageUrl($request),
                'page_type' => $type,
                'ref_domain' => $this->resolveReferrerDomain($request),
                'utm_source' => $this->queryString($request, 'utm_source'),
                'utm_medium' => $this->queryString($request, 'utm_medium'),
                'utm_campaign' => $this->queryString($request, 'utm_campaign'),
                'device_category' => $this->devices->detect($request),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('AnalyticsRecorderService: gagal mencatat page view.', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Derive page_type from the route name prefix, or null when unroutable.
     */
    public function resolvePageType(Request $request): ?string
    {
        $name = $request->route()?->getName();

        if ($name === null || $name === '') {
            return null;
        }

        foreach (self::PAGE_TYPE_MAP as $prefix => $type) {
            if ($name === $prefix || str_starts_with($name, $prefix.'.')) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Prefer the anonymous visitor token, fall back to the session id.
     */
    protected function resolveSessionId(Request $request): string
    {
        $token = $request->cookie(WhatsAppTrackingService::VISITOR_COOKIE);

        if (is_string($token) && $token !== '') {
            return mb_substr($token, 0, 100);
        }

        if ($request->hasSession()) {
            return mb_substr($request->session()->getId(), 0, 100);
        }

        return 'anonymous';
    }

    protected function resolvePageUrl(Request $request): string
    {
        // path() returns '/' for the root; normalize so we never emit '//'.
        $path = '/'.ltrim($request->path(), '/');

        return mb_substr($path, 0, 255);
    }

    /**
     * Host only — never the full referrer URL (privacy).
     */
    protected function resolveReferrerDomain(Request $request): ?string
    {
        $referer = $request->headers->get('referer');

        if (! is_string($referer) || $referer === '') {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        return mb_substr($host, 0, 255);
    }

    protected function queryString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return mb_substr($value, 0, 255);
    }
}

<?php

namespace App\Services;

use App\Models\BlogArticle;
use App\Models\Page;
use App\Models\Package;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Service;
use App\Models\WhatsAppClick;
use App\Support\DeviceDetector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response as ResponseFactory;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class WhatsAppTrackingService
{
    public const VISITOR_COOKIE = 'wpc_visit';

    /**
     * Resolve the entity referenced by a tracking context + slug.
     *
     * Returns null when the context is unknown, the slug is missing, or the
     * entity does not exist / is unpublished. `home` always returns null.
     */
    public function resolveDestination(string $context, ?string $slug): ?object
    {
        if ($context === 'home') {
            return null;
        }

        if ($slug === null || $slug === '') {
            return null;
        }

        return match ($context) {
            'product' => Product::published()->where('slug', $slug)->first(),
            'package' => Package::published()->where('slug', $slug)->first(),
            'service' => Service::published()->where('slug', $slug)->first(),
            'article' => BlogArticle::published()->where('slug', $slug)->first(),
            'page' => Page::published()->where('slug', $slug)->first(),
            default => null,
        };
    }

    /**
     * Build the prefilled WhatsApp message for an entity + context.
     *
     * Templates follow docs/PRD.md §7.2. URL-encoding happens only at the
     * redirect step, never here.
     */
    public function buildMessage(?object $entity, string $context): string
    {
        $title = match ($context) {
            'product' => $entity?->name ?? '',
            'package' => $entity?->title ?? '',
            'service' => $entity?->title ?? '',
            'article' => $entity?->title ?? '',
            'page' => $entity?->title ?? '',
            'home' => '',
            default => '',
        };

        $url = $this->entityUrl($entity, $context);

        return match ($context) {
            'product' => "Halo WP CCTV Indonesia, saya ingin konsultasi pemasangan menggunakan {$title}. Halaman: {$url}.",
            'package' => "Halo WP CCTV Indonesia, saya tertarik dengan {$title} dan ingin konsultasi instalasi. Halaman: {$url}.",
            'service' => match (strtolower($entity?->type ?? '')) {
                'repair' => 'Halo WP CCTV Indonesia, saya ingin konsultasi servis/perbaikan CCTV.',
                default => 'Halo WP CCTV Indonesia, saya ingin konsultasi jasa instalasi CCTV.',
            },
            'article' => "Halo WP CCTV Indonesia, saya ingin bertanya mengenai informasi pada artikel {$title}.",
            'page', 'home' => 'Halo WP CCTV Indonesia, saya ingin bertanya mengenai informasi pada situs wpcctv.id.',
            default => 'Halo WP CCTV Indonesia.',
        };
    }

    /**
     * Build the absolute URL of an entity's public page (used inside messages).
     *
     * The concatenation must not depend on the caller remembering a leading
     * slash: `config('app.url').ltrim($path, '/')` produced `http://hostproducts`
     * for every non-home context (audit C3), corrupting both the stored
     * `whatsapp_clicks.page_url` analytics dimension and the prefilled
     * WhatsApp message. Joining below is slash-agnostic in both directions and
     * preserves a subdirectory base URL (`https://host/sub`).
     */
    public function entityUrl(?object $entity, string $context): string
    {
        $path = match ($context) {
            'home' => '',
            'product' => 'products/'.$this->slugOf($entity),
            'package' => 'packages/'.$this->slugOf($entity),
            'service' => 'services/'.$this->slugOf($entity),
            'article' => 'blog/'.$this->slugOf($entity),
            'page' => 'pages/'.$this->slugOf($entity),
            default => '',
        };

        return $this->joinUrl((string) config('app.url'), $path);
    }

    /**
     * Join a base URL and a relative path with exactly one slash between them.
     *
     * - trailing slashes on the base are removed
     * - leading slashes on the path are removed
     * - an empty path returns the base unchanged (no trailing slash added)
     *
     * Subdirectory bases survive: `https://host/sub` + `products/x`
     * → `https://host/sub/products/x`.
     */
    public function joinUrl(string $base, string $path): string
    {
        $base = rtrim($base, '/');
        $path = ltrim($path, '/');

        if ($path === '') {
            return $base;
        }

        return $base.'/'.$path;
    }

    /**
     * Non-null slug for URL building; an entity without one yields a bare path
     * rather than the literal string "null".
     */
    protected function slugOf(?object $entity): string
    {
        $slug = $entity->slug ?? null;

        return is_string($slug) && $slug !== '' ? $slug : '';
    }

    /**
     * Full tracking flow. Records the click, dispatches the notification job,
     * and returns the redirect to wa.me.
     *
     * Errors:
     * - 404 invalid context / unresolved entity
     * - 503 whatsapp number not configured
     */
    public function handle(Request $request): Response|RedirectResponse
    {
        $context = (string) $request->input('context');
        $slug = $request->input('slug');

        if (! in_array($context, WhatsAppClick::PAGE_TYPES, true)) {
            abort(404);
        }

        $entity = $this->resolveDestination($context, $slug === '' ? null : $slug);

        if ($context !== 'home' && $entity === null) {
            abort(404);
        }

        $phone = Setting::get('whatsapp_number');

        if ($phone === null || $phone === '') {
            return ResponseFactory::make('WhatsApp belum dikonfigurasikan', 503);
        }

        $message = $this->buildMessage($entity, $context);
        $url = 'https://wa.me/'.$phone.'?text='.rawurlencode($message);

        $click = $this->recordClick($request, $context, $entity);

        \App\Jobs\SendWhatsAppNotificationJob::dispatch($click);

        $response = redirect()->away($url);
        $response = $this->applyVisitorCookie($request, $response);

        return $response;
    }

    /**
     * Column width for `whatsapp_clicks` string dimensions created by
     * `$table->string(...)` in the business-tables migration.
     */
    public const DIMENSION_MAX_LENGTH = 255;

    protected function recordClick(Request $request, string $context, ?object $entity): WhatsAppClick
    {
        $pageUrl = $entity !== null
            ? $this->entityUrl($entity, $context)
            : url('/');

        return WhatsAppClick::create([
            'event_id' => (string) Str::uuid(),
            'page_url' => $this->truncateDimension($pageUrl),
            'page_title' => $this->truncateDimension($this->pageTitle($entity, $context)),
            'page_type' => $context,
            'context_id' => $entity?->id,
            'referrer' => $this->truncateDimension($request->header('referer')),
            'utm_source' => $this->truncateDimension($this->queryString($request, 'utm_source')),
            'utm_medium' => $this->truncateDimension($this->queryString($request, 'utm_medium')),
            'utm_campaign' => $this->truncateDimension($this->queryString($request, 'utm_campaign')),
            'device_category' => $this->detectDevice($request),
            'visitor_token' => $this->truncateDimension($request->cookie(self::VISITOR_COOKIE)),
        ]);
    }

    /**
     * Truncate a free-text analytics dimension to the column width.
     *
     * Referrer URLs and UTM values routinely exceed 255 characters; MySQL and
     * MariaDB in strict mode reject the insert with SQLSTATE 22001, which
     * aborted the click before the redirect and before the job dispatch
     * (audit H2). Truncating here — at the recording boundary — keeps the
     * click, the redirect, and the notification working. The column is NOT
     * widened: a URL shortened to 255 chars is still a usable dimension, and
     * widening would tie a schema change to unbounded user-controlled input.
     */
    protected function truncateDimension(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return mb_substr($value, 0, self::DIMENSION_MAX_LENGTH);
    }

    protected function queryString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    protected function pageTitle(?object $entity, string $context): ?string
    {
        return match ($context) {
            'product' => $entity?->name,
            'package' => $entity?->title,
            'service' => $entity?->title,
            'article' => $entity?->title,
            'page' => $entity?->title,
            'home' => 'Beranda',
            default => null,
        };
    }

    /**
     * Attach/reuse the anonymous visitor token cookie on the response.
     */
    protected function applyVisitorCookie(Request $request, RedirectResponse $response): RedirectResponse
    {
        $existing = $request->cookie(self::VISITOR_COOKIE);

        // Reuse: the token already exists on the request. Do not re-emit the
        // cookie — the browser already holds it.
        if ($existing !== null && $existing !== '') {
            return $response;
        }

        // Fresh: mint a 128-bit hex token, persist it on the request so the
        // click row (written earlier via ensureVisitorToken) and the
        // Set-Cookie value are the identical 32-char string.
        $token = bin2hex(random_bytes(16));
        $request->cookies->set(self::VISITOR_COOKIE, $token);

        $response->cookie($this->buildVisitorCookie($token));

        return $response;
    }

    public function buildVisitorCookie(string $token): \Symfony\Component\HttpFoundation\Cookie
    {
        return (new \Symfony\Component\HttpFoundation\Cookie(
            name: self::VISITOR_COOKIE,
            value: $token,
            path: '/',
            domain: null,
            secure: app()->isProduction(),
            httpOnly: true,
            sameSite: \Symfony\Component\HttpFoundation\Cookie::SAMESITE_LAX,
        ))->withExpires(now()->addDays(30)->toDateTimeImmutable());
    }

    /**
     * Delegates to the shared helper so click and page-view analytics agree.
     */
    protected function detectDevice(Request $request): string
    {
        return app(DeviceDetector::class)->detect($request);
    }
}

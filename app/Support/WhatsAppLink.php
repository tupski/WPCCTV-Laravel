<?php

namespace App\Support;

/**
 * Builds the first-party WhatsApp tracking URL used by every public CTA.
 *
 * All public CTAs funnel through `whatsapp.track` so a click is recorded and
 * the visitor is redirected to wa.me with a contextual, prefilled message.
 * Building links here (instead of inline in Blade) keeps the context vocabulary
 * in one place and makes a typo a test failure rather than a silent 404.
 */
final class WhatsAppLink
{
    /**
     * Contexts accepted by `WhatsAppTrackingService::resolveDestination()`.
     */
    public const CONTEXTS = ['home', 'product', 'package', 'service', 'article', 'page'];

    /**
     * @param  string  $context  One of self::CONTEXTS
     * @param  string|null  $slug  Entity slug; must be null for `home`
     */
    public static function url(string $context = 'home', ?string $slug = null): string
    {
        $params = ['context' => $context];

        if ($slug !== null && $slug !== '' && $context !== 'home') {
            $params['slug'] = $slug;
        }

        return route('whatsapp.track', $params);
    }

    public static function forProduct(?string $slug): string
    {
        return self::url('product', $slug);
    }

    public static function forPackage(?string $slug): string
    {
        return self::url('package', $slug);
    }

    public static function forService(?string $slug): string
    {
        return self::url('service', $slug);
    }

    public static function forArticle(?string $slug): string
    {
        return self::url('article', $slug);
    }

    public static function forPage(?string $slug): string
    {
        return self::url('page', $slug);
    }

    public static function forHome(): string
    {
        return self::url('home');
    }
}

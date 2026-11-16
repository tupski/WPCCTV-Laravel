<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Shared user-agent device classification.
 *
 * Single source of truth for `device_category` (desktop / tablet / mobile)
 * used by both the WhatsApp click tracker and the analytics page-view
 * recorder. Kept dependency-free and side-effect-free.
 */
class DeviceDetector
{
    public const DESKTOP = 'desktop';
    public const TABLET = 'tablet';
    public const MOBILE = 'mobile';

    public const CATEGORIES = [self::DESKTOP, self::TABLET, self::MOBILE];

    public function detect(Request $request): string
    {
        return self::categorize((string) $request->userAgent());
    }

    public static function categorize(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        if (preg_match('/(Android|iPhone|iPad|iPod|Mobile|Windows Phone)/i', $ua)) {
            $tablet = (bool) preg_match('/(iPad|Tablet|SILK|Kindle|Playbook)/i', $ua);

            return $tablet ? self::TABLET : self::MOBILE;
        }

        return self::DESKTOP;
    }
}

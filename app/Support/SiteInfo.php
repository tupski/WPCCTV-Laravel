<?php

namespace App\Support;

use App\Models\Setting;
use Throwable;

/**
 * Single source of public brand/contact information.
 *
 * Precedence: `settings` table (admin-editable) → config('site') → hard
 * fallback. Values are cached for the lifetime of the request so a page that
 * renders the header, footer and a JSON-LD block does not issue the same
 * query five times.
 *
 * The settings lookup is wrapped in a try/catch: `artisan config:cache`,
 * `package:discover` and early-boot code paths run before the `settings`
 * table is guaranteed to exist, and a public page must never 500 because a
 * contact detail could not be read.
 */
final class SiteInfo
{
    /**
     * @var array<string, mixed>|null
     */
    private static ?array $resolved = null;

    /**
     * Drop the per-request cache. Called by tests that change settings.
     */
    public static function flush(): void
    {
        self::$resolved = null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        $defaults = (array) config('site', []);
        $stored = self::stored();

        $whatsapp = self::normalizeWhatsapp((string) ($stored['whatsapp_number']
            ?? $defaults['whatsapp_number']
            ?? ''));

        return self::$resolved = [
            'name' => self::string($stored['site_name'] ?? null, (string) ($defaults['name'] ?? 'WP CCTV Indonesia')),
            'tagline' => self::string($stored['site_tagline'] ?? null, (string) ($defaults['tagline'] ?? '')),
            'whatsapp_number' => $whatsapp,
            'whatsapp_display' => self::formatWhatsapp($whatsapp),
            'email' => self::string($stored['contact_email'] ?? null, (string) ($defaults['email'] ?? '')),
            'address_locality' => self::string($stored['contact_locality'] ?? null, (string) ($defaults['address_locality'] ?? '')),
            'address_region' => self::string($stored['contact_region'] ?? null, (string) ($defaults['address_region'] ?? '')),
            'address_country' => (string) ($defaults['address_country'] ?? 'ID'),
            'operating_hours' => self::string($stored['operating_hours'] ?? null, (string) ($defaults['operating_hours'] ?? '')),
            'opening_hours_spec' => (string) ($defaults['opening_hours_spec'] ?? ''),
            'service_areas' => self::areas($stored['service_areas'] ?? null, (array) ($defaults['service_areas'] ?? [])),
        ];
    }

    public static function name(): string
    {
        return (string) self::all()['name'];
    }

    public static function whatsappNumber(): string
    {
        return (string) self::all()['whatsapp_number'];
    }

    public static function whatsappDisplay(): string
    {
        return (string) self::all()['whatsapp_display'];
    }

    /**
     * Convert any human-typed Indonesian number into wa.me form (62…).
     *
     * An operator can type "0878-1155-6995", "+62 878 1155 6995" or
     * "6287811556995"; all three must produce a working wa.me link, because a
     * leading zero makes `https://wa.me/08…` unreachable. Punctuation is
     * stripped first; only digits reach the link.
     */
    public static function normalizeWhatsapp(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    /**
     * Render a wa.me number for humans: "+62 878-1155-6995", or the national
     * form "0878-1155-6995" when the number starts with 0.
     */
    public static function formatWhatsapp(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if ($digits === '') {
            return '';
        }

        $prefix = '';
        $rest = $digits;

        if (str_starts_with($digits, '62')) {
            $prefix = '+62 ';
            $rest = substr($digits, 2);
        }

        $length = strlen($rest);
        $groups = match ($length) {
            12 => [4, 4, 4],
            11 => [3, 4, 4],
            10 => [4, 3, 3],
            default => [],
        };

        if ($groups === []) {
            return $prefix.$rest;
        }

        $parts = [];
        $offset = 0;

        foreach ($groups as $size) {
            $chunk = substr($rest, $offset, $size);

            if ($chunk === '') {
                break;
            }

            $parts[] = $chunk;
            $offset += $size;
        }

        return $prefix.implode('-', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    private static function stored(): array
    {
        $keys = [
            'site_name', 'site_tagline', 'whatsapp_number', 'contact_email',
            'contact_locality', 'contact_region', 'operating_hours', 'service_areas',
        ];

        try {
            $values = [];

            foreach ($keys as $key) {
                $values[$key] = Setting::get($key);
            }

            return $values;
        } catch (Throwable) {
            // Settings table missing or database unreachable during boot.
            return [];
        }
    }

    private static function string(mixed $value, string $fallback): string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        if (is_scalar($value) && (string) $value !== '') {
            return (string) $value;
        }

        return $fallback;
    }

    /**
     * @param  array<int, mixed>  $fallback
     * @return array<int, string>
     */
    private static function areas(mixed $value, array $fallback): array
    {
        $source = is_array($value) ? $value : $fallback;

        return array_values(array_filter(array_map(
            static fn (mixed $area): string => is_scalar($area) ? trim((string) $area) : '',
            $source,
        ), static fn (string $area): bool => $area !== ''));
    }
}

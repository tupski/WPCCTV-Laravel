<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Single source of number, date and relative-time formatting for the admin UI.
 *
 * Indonesian conventions: thousands separator ".", decimal separator ",",
 * dates written "16 Nov 2026", clock times written "14.05".
 *
 * A missing value renders as an em dash so an absent measurement is never
 * confused with a measured zero.
 */
final class Format
{
    public const MISSING = '—';

    /**
     * Indonesian month abbreviations, indexed 1..12.
     */
    private const MONTHS = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
        7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    /**
     * Format a number, or the missing marker when there is no number.
     *
     * A non-finite float (`INF`, `-INF`, `NAN`) is not a measurement, so it is
     * treated exactly like `null` and renders the em dash. `number_format()`
     * is not a guard: it happily prints the literal strings "inf" and "nan",
     * which is a plausible-looking number that is silently wrong (audit M4).
     *
     * `is_finite()` is false for NAN as well as for infinities, so one test
     * covers all three non-finite values.
     */
    public static function number(int|float|null $value, int $decimals = 0): string
    {
        if ($value === null) {
            return self::MISSING;
        }

        $float = (float) $value;

        if (! is_finite($float)) {
            return self::MISSING;
        }

        return number_format($float, $decimals, ',', '.');
    }

    /**
     * Render a 0..1 ratio as a percentage.
     *
     * `null` means "no denominator" (a zero denominator renders a dash rather
     * than a misleading 0%). A non-finite ratio — reachable only from a caller
     * that divided by zero, or by a float that is not exactly 0.0 but is
     * smaller than the smallest representable ratio — is also rendered as the
     * missing marker. The multiplication is done first and then validated, so
     * a ratio that overflows PHP's float range to `INF` at `* 100` is caught
     * here too rather than escaping to `number()` (audit M4).
     */
    public static function percent(?float $ratio, int $decimals = 1): string
    {
        if ($ratio === null) {
            return self::MISSING;
        }

        $percent = $ratio * 100;

        if (! is_finite($percent)) {
            return self::MISSING;
        }

        return self::number($percent, $decimals).'%';
    }

    public static function date(?DateTimeInterface $value): string
    {
        if ($value === null) {
            return self::MISSING;
        }

        $date = CarbonImmutable::instance($value);

        return $date->format('j').' '.self::MONTHS[(int) $date->format('n')].' '.$date->format('Y');
    }

    public static function dateTime(?DateTimeInterface $value): string
    {
        if ($value === null) {
            return self::MISSING;
        }

        return self::date($value).' '.CarbonImmutable::instance($value)->format('H.i');
    }

    /**
     * Distance in the past or future, e.g. "3 hari lalu", "Dalam 2 jam".
     */
    public static function relative(?DateTimeInterface $value): string
    {
        if ($value === null) {
            return self::MISSING;
        }

        // Rounded, not truncated: a 2-hour gap measured against a clock that
        // ticks microseconds can arrive as 7199.99s and would otherwise read
        // as "1 jam".
        $seconds = (int) round(CarbonImmutable::now()->diffInSeconds(CarbonImmutable::instance($value), false));

        if (abs($seconds) < 60) {
            return 'Baru saja';
        }

        $past = $seconds < 0;
        $abs = abs($seconds);

        foreach ([[86400 * 30, 'bulan'], [86400 * 7, 'minggu'], [86400, 'hari'], [3600, 'jam'], [60, 'menit']] as [$size, $unit]) {
            if ($abs >= $size) {
                $count = intdiv($abs, $size);

                return $past ? $count.' '.$unit.' lalu' : 'Dalam '.$count.' '.$unit;
            }
        }

        return 'Baru saja';
    }

    /**
     * Follow-up deadline phrasing measured in whole days.
     */
    public static function deadline(?DateTimeInterface $value): string
    {
        if ($value === null) {
            return 'Belum dijadwalkan';
        }

        $days = (int) CarbonImmutable::today()
            ->diffInDays(CarbonImmutable::instance($value)->startOfDay(), false);

        if ($days === 0) {
            return 'Jatuh tempo hari ini';
        }

        return $days > 0 ? 'Jatuh tempo dalam '.$days.' hari' : 'Lewat '.abs($days).' hari';
    }
}

<?php

namespace Tests\Unit;

use App\Support\Format;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * The admin UI has exactly one formatter. These assertions pin the Indonesian
 * conventions so a stray number_format() call elsewhere is caught as a defect.
 */
class FormatTest extends TestCase
{
    public function test_numbers_use_dot_thousands_and_comma_decimals(): void
    {
        $this->assertSame('1.234.567', Format::number(1234567));
        $this->assertSame('1.234,5', Format::number(1234.5, 1));
        $this->assertSame('0', Format::number(0));
    }

    public function test_a_missing_number_is_an_em_dash_not_a_zero(): void
    {
        $this->assertSame('—', Format::number(null));
        $this->assertSame('—', Format::percent(null));
    }

    public function test_percentages_use_a_comma_decimal(): void
    {
        $this->assertSame('73,4%', Format::percent(0.734));
        $this->assertSame('100,0%', Format::percent(1.0));
        $this->assertSame('0,0%', Format::percent(0.0));
    }

    public function test_dates_are_written_in_indonesian_form(): void
    {
        $date = CarbonImmutable::create(2026, 11, 16, 14, 5);

        $this->assertSame('16 Nov 2026', Format::date($date));
        $this->assertSame('16 Nov 2026 14.05', Format::dateTime($date));
        $this->assertSame('—', Format::date(null));
    }

    public function test_august_uses_the_indonesian_abbreviation(): void
    {
        $this->assertSame('17 Agu 2026', Format::date(CarbonImmutable::create(2026, 8, 17)));
    }

    public function test_relative_time_reads_naturally_in_both_directions(): void
    {
        $this->assertSame('Baru saja', Format::relative(now()));
        $this->assertSame('3 hari lalu', Format::relative(now()->subDays(3)));
        $this->assertSame('Dalam 2 jam', Format::relative(now()->addHours(2)));
    }

    public function test_deadline_phrasing_states_the_distance_in_days(): void
    {
        $this->assertSame('Jatuh tempo hari ini', Format::deadline(CarbonImmutable::today()));
        $this->assertSame('Lewat 1 hari', Format::deadline(CarbonImmutable::today()->subDay()));
        $this->assertSame('Jatuh tempo dalam 2 hari', Format::deadline(CarbonImmutable::today()->addDays(2)));
        $this->assertSame('Belum dijadwalkan', Format::deadline(null));
    }
}

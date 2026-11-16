<?php

namespace Tests\Unit;

use App\Support\Format;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Audit M4: a reachable `INF` on the ratio path.
 *
 * `number_format(INF)` does not raise; it prints the literal string "inf", so
 * an infinite ratio used to render as a plausible-looking value. The same is
 * true of `-INF` ("-inf") and `NAN` ("nan"). Every one of those inputs is
 * asserted to render the missing marker instead.
 */
class NonFiniteRatioTest extends TestCase
{
    /**
     * @return array<string, array{0: float}>
     */
    public static function nonFinite(): array
    {
        return [
            'positive infinity' => [INF],
            'negative infinity' => [-INF],
            'not a number' => [NAN],
            // The "divisor that is not exactly zero": the smallest positive
            // subnormal double. 1 / 5e-324 overflows to INF, and the old
            // `denominator === 0` guard never fired because the divisor was
            // not zero — it was merely tiny.
            'ratio from a denormal divisor' => [1 / 5e-324],
        ];
    }

    #[DataProvider('nonFinite')]
    public function test_percent_renders_the_missing_marker_for_non_finite_ratios(float $ratio): void
    {
        $this->assertSame(Format::MISSING, Format::percent($ratio));
    }

    #[DataProvider('nonFinite')]
    public function test_number_renders_the_missing_marker_for_non_finite_values(float $value): void
    {
        $this->assertSame(Format::MISSING, Format::number($value));
    }

    public function test_the_denormal_divisor_really_does_overflow_to_infinity(): void
    {
        // Guards the premise of the case above: if PHP ever stopped producing
        // INF here, the test would be vacuous and should fail loudly.
        //
        // 5e-324 is the smallest positive subnormal double, so it is NOT zero
        // — which is exactly why a `denominator === 0` guard misses it — and
        // 1 / 5e-324 overflows the double range to INF.
        $this->assertNotSame(0.0, 5e-324);
        $this->assertTrue(is_infinite(1 / 5e-324));

        // PHP_FLOAT_MIN is the smallest *normal* float, two orders of magnitude
        // larger, so dividing by it stays finite. Asserted so the distinction
        // is documented rather than implied.
        $this->assertTrue(is_finite(1 / PHP_FLOAT_MIN));
    }

    public function test_finite_ratios_still_format_normally(): void
    {
        $this->assertSame('0,0%', Format::percent(0.0));
        $this->assertSame('50,0%', Format::percent(0.5));
        $this->assertSame('100,0%', Format::percent(1.0));
        $this->assertSame('—', Format::percent(null));
    }

    public function test_finite_numbers_still_format_normally(): void
    {
        $this->assertSame('1.234', Format::number(1234));
        $this->assertSame('1,5', Format::number(1.5, 1));
        $this->assertSame('—', Format::number(null));
        $this->assertSame('0', Format::number(0));
    }

    /**
     * A ratio that overflows only after the `* 100` is still caught: `percent`
     * validates the scaled value, not just the input.
     */
    public function test_percent_validates_after_scaling(): void
    {
        $huge = PHP_FLOAT_MAX / 10;

        $this->assertTrue(is_finite($huge), 'Premise: the input is finite.');
        $this->assertTrue(is_infinite($huge * 100), 'Premise: scaling overflows.');

        $this->assertSame(Format::MISSING, Format::percent($huge));
    }
}

<?php

namespace App\Support;

use RuntimeException;
use Throwable;

/**
 * Raised when no VAPID keypair generation strategy succeeded.
 *
 * Carries a per-strategy diagnostic map so the console command can print the
 * real OpenSSL error chain instead of a generic "gagal" message. The audit
 * (C1) found that the original command swallowed the reason entirely, which
 * made "OpenSSL cannot generate EC keys here" indistinguishable from
 * "keys are not configured yet".
 */
class VapidGenerationException extends RuntimeException
{
    /**
     * @param  array<string, string>  $diagnostics  strategy label => failure reason
     */
    public function __construct(
        public readonly array $diagnostics,
        ?Throwable $previous = null,
    ) {
        parent::__construct(
            'Tidak ada strategi pembuatan kunci VAPID yang berhasil.',
            0,
            $previous,
        );
    }

    /**
     * Human-readable, newline-separated report of every attempted strategy.
     */
    public function diagnosticReport(): string
    {
        $lines = [];

        foreach ($this->diagnostics as $strategy => $reason) {
            $lines[] = "- {$strategy}: {$reason}";
        }

        return implode(PHP_EOL, $lines);
    }
}

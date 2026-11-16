<?php

namespace App\Console\Commands;

use App\Services\AnalyticsAggregationService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

/**
 * php artisan analytics:aggregate [--date=YYYY-MM-DD] [--days=N]
 *
 * Default: aggregate yesterday (the scheduled daily run at 01:00).
 * --days=N backfills the last N days, ending yesterday unless --date given.
 */
class AggregateAnalyticsCommand extends Command
{
    protected $signature = 'analytics:aggregate
        {--date= : Tanggal tunggal (Y-m-d) yang akan diagregasi}
        {--days= : Backfill sejumlah N hari ke belakang}';

    protected $description = 'Agregasi data analitik harian ke tabel analytics_daily';

    public function handle(AnalyticsAggregationService $aggregation): int
    {
        $dateOption = $this->option('date');
        $daysOption = $this->option('days');

        if ($daysOption !== null && $daysOption !== '') {
            if (! ctype_digit((string) $daysOption) || (int) $daysOption < 1) {
                $this->error('Opsi --days harus berupa bilangan bulat positif.');

                return self::FAILURE;
            }

            $end = $dateOption !== null && $dateOption !== ''
                ? $this->parseDate((string) $dateOption)
                : CarbonImmutable::yesterday();

            if ($end === null) {
                $this->error('Format --date tidak valid. Gunakan Y-m-d.');

                return self::FAILURE;
            }

            $days = (int) $daysOption;
            $from = $end->subDays($days - 1);

            try {
                $count = $aggregation->aggregateRange($from, $end);
            } catch (Throwable $e) {
                $this->error('Agregasi gagal: '.$e->getMessage());

                return self::FAILURE;
            }

            $this->info("Agregasi selesai: {$count} hari ({$from->toDateString()} s/d {$end->toDateString()}).");

            return self::SUCCESS;
        }

        $target = $dateOption !== null && $dateOption !== ''
            ? $this->parseDate((string) $dateOption)
            : CarbonImmutable::yesterday();

        if ($target === null) {
            $this->error('Format --date tidak valid. Gunakan Y-m-d.');

            return self::FAILURE;
        }

        try {
            $row = $aggregation->aggregateDay($target);
        } catch (Throwable $e) {
            $this->error('Agregasi gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Agregasi selesai untuk {$row->date->toDateString()}: {$row->page_views} page views, {$row->whatsapp_clicks} klik WhatsApp.");

        return self::SUCCESS;
    }

    protected function parseDate(string $value): ?CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $value);
        } catch (Throwable) {
            return null;
        }

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return null;
        }

        return $date->startOfDay();
    }
}

<?php

use App\Jobs\ExpirePushSubscriptionsJob;
use App\Jobs\RetentionCleanupJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(ExpirePushSubscriptionsJob::class)->dailyAt('02:00');
Schedule::job(RetentionCleanupJob::class)->dailyAt('04:00');

// Aggregate yesterday's raw analytics rows into analytics_daily.
Schedule::command('analytics:aggregate')->dailyAt('01:00');

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Reachability monitoring loop (PRD H1): queue a check for every device
// twice a minute so online/offline transitions surface within one ~30s cycle.
Schedule::command('devices:monitor')
    ->everyThirtySeconds()
    ->withoutOverlapping();

// Capture Horizon queue metrics so the dashboard's wait-time and throughput
// graphs populate. Without a scheduled snapshot the metrics view stays blank.
Schedule::command('horizon:snapshot')->everyFiveMinutes();

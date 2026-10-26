<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Laravel Scheduler: executed by SIGIT cron once per minute.
\Illuminate\Support\Facades\Schedule::command('pm:run-due')
    ->everyMinute()->withoutOverlapping(10);

// Scan event baru setiap 15 menit; deduplikasi unik per event dan penerima.
\Illuminate\Support\Facades\Schedule::command('pm:notifications:scan')
    ->everyFifteenMinutes()->withoutOverlapping(10);

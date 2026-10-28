<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('onestack:sync-users')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/onestack-sync.log'));

Schedule::command('attendance:default-pejabat')
    ->weekdays()
    ->between('06:00', '18:00')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/attendance-defaults.log'));


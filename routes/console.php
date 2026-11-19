<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Application Schedule (Laravel 11 Task Scheduler)
|--------------------------------------------------------------------------
| Runs via standard cron: * * * * * php artisan schedule:run >> /dev/null 2>&1
*/

// Nightly export of activated SIM numbers to the configured operations inbox (12:00 AM Midnight WAT)
Schedule::command('sims:export-activated')
    ->dailyAt('00:00')
    ->timezone('Africa/Lagos')
    ->withoutOverlapping();

// Daily check for activated SIMs reaching 28 days for activation bonus review (1:00 AM WAT)
Schedule::command('sim:grant-bulk-activation-bonus')
    ->dailyAt('01:00')
    ->timezone('Africa/Lagos')
    ->withoutOverlapping();

// Dispatch due push notifications
Schedule::command('notifications:dispatch-due')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Periodic queue health monitor
Schedule::command('queue:health-check')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground();

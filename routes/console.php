<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly activated-SIMs export email (see ExportActivatedSims) — the
// command itself checks the Admin > SIMs Operations on/off toggle, so
// this only actually does anything while that's turned on. Only takes
// effect if the server's crontab runs `php artisan schedule:run` every
// minute; a direct crontab entry for `sims:export-activated` works too
// and doesn't need this at all — see the command's own docblock.
Schedule::command('sims:export-activated')->dailyAt('00:30');

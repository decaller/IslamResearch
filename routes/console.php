<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Populate Horizon metrics dashboard snapshots every 5 minutes.
// Without this schedule, the Horizon metrics graph stays permanently blank.
Schedule::command('horizon:snapshot')->everyFiveMinutes();

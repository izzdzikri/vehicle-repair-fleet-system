<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requires the Laravel scheduler cron entry to be running (`php artisan
// schedule:run` every minute) to actually fire on Herd — until that's
// set up, AdminController::index() also triggers a throttled run on
// every admin dashboard load, and the "Run Predictions Now" button on
// the Maintenance Alerts page triggers one on demand.
Schedule::command('maintenance:predict')->daily();
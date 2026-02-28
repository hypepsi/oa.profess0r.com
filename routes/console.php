<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule automatic backup daily at 3:00 AM
Schedule::command('backup:data')->dailyAt('03:00');

// Schedule automatic GeoFeed sync to remote daily at 3:05 AM
// Always syncs to geofeed.test.csv (change config URL when ready for production)
Schedule::command('geofeed:sync-remote --mode=test')
    ->dailyAt('03:05')
    ->name('geofeed-sync-remote')
    ->withoutOverlapping();

// Sync all active email accounts every 5 minutes
Schedule::command('email:sync-all')
    ->everyFiveMinutes()
    ->name('email-sync-all')
    ->withoutOverlapping()
    ->runInBackground();

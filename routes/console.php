<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule the expire rentals command to run daily at midnight
Schedule::command('rentals:expire')->daily();

// Schedule the reveal expired ratings command to run daily
// Reveals ratings that have passed the 14-day window after stay completion
Schedule::command('ratings:reveal-expired')->daily();

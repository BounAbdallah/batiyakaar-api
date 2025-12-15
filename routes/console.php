<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule lease expiration check to run daily at 8:00 AM
Schedule::command('leases:check-expiration')->dailyAt('08:00');

// Schedule payment reminders check to run daily at 9:00 AM
Schedule::command('payments:check-reminders')->dailyAt('09:00');

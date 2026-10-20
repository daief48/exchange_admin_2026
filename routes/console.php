<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Scheduled Daily at 20:00 (8:00 PM) to check missing daily depot expense entries
Schedule::command('app:check-missing-depot-entries')->dailyAt('20:00');

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('appointments:send-reminders')->everyMinute();

Schedule::command('export:messages', [
    2,
    1,
    '1pQxgpUojIEVvaekjQTsM_y0qRs1y64U7w29TGZaqnFo',
    'Hoja 1',
])
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

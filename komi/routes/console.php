<?php

use App\Jobs\UnbanExpiredUsersJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new UnbanExpiredUsersJob)->everyMinute();

// Backup diario de BD a las 03:00 (mysqldump/sqlite3; retenidos últimos 14).
Schedule::command('backup:database')->dailyAt('03:00');

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Automated Multi-Tenant Database Backup Scheduler
|--------------------------------------------------------------------------
|
| Staggered automated database backups sent directly to administrator email.
| Runs daily at 02:00 AM and executes for the specific database scheduled
| for today's date (Day 1: Landlord, Day 5: Living Spring, Day 10: Cathedral, etc.).
|
*/
\Illuminate\Support\Facades\Schedule::command('db:backup-email')
    ->dailyAt('02:00')
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/db-backup.log'));


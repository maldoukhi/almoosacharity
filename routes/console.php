<?php

use App\Jobs\Confirmations\SendConfirmationReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Phase 6b: exactly one automatic reminder for each outstanding
// delivery-confirmation link past its reminder_after_days threshold.
Schedule::job(new SendConfirmationReminders)->daily();

// Phase 10: clone every due recurring-aid plan into a fresh draft aid and
// advance its next run date.
Schedule::command('aids:generate-recurring')->daily();

// Nightly backups (spatie/laravel-backup): prune old archives per the
// retention policy in config/backup.php, then take a fresh backup of the
// private documents + database. Failures email BACKUP_NOTIFICATION_EMAIL.
Schedule::command('backup:clean')->dailyAt('01:30');
Schedule::command('backup:run')->dailyAt('02:00');

// Approval SLA: once a day, notify stage approvers about aids that have sat
// in a stage past its configured max_days (idempotent per stage entry).
Schedule::command('aids:escalate-overdue')->daily();

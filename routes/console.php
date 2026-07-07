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

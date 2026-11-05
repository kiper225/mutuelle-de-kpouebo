<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:cotisation-reminders')->dailyAt('08:00');
Schedule::command('notifications:publish-pending')->everyTenMinutes();
// Les notifications de plus de 90 jours sont supprimées.
Schedule::call(fn () => DB::table('notifications')->where('created_at', '<', now()->subDays(90))->delete())->daily();
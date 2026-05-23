<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Project;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::call(function () {
    $projects = Project::where('is_cloud_backup_enabled', true)->get();
    foreach ($projects as $project) {
        \App\Http\Controllers\DataSyncController::checkAndRunWeeklyBackup($project);
    }
})->weeklyOn(1, '03:00')->name('auto_cloud_backup')->withoutOverlapping();

Schedule::command('backup:clean')->weekly()->sundays()->at('01:00');
Schedule::command('backup:run')->weekly()->sundays()->at('02:00');

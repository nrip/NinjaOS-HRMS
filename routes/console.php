<?php

use App\Jobs\AccrueLeavesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─────────────────────────────────────────────────────────────────────────────
// NexusOS Scheduled Tasks
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Monthly Leave Accrual
 * Runs on the 1st of every month at 00:05 IST.
 * Processes all active locations and expires overdue Comp Off balances.
 */
Schedule::job(new AccrueLeavesJob())
    ->monthlyOn(1, '00:05')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error(
            'AccrueLeavesJob: Monthly accrual failed — check the queue worker logs.'
        );
    })
    ->name('monthly-leave-accrual')
    ->description('Monthly leave accrual for all active locations');

/**
 * Daily Database Backup (Spatie Laravel Backup)
 * Runs every day at 01:00 IST.
 * Backs up the database to the local disk (simulating S3 in dev).
 * In production, add an 's3' disk to config/backup.php destination.disks.
 */
Schedule::command('backup:run --only-db')
    ->dailyAt('01:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping()
    ->name('daily-db-backup')
    ->description('Daily database backup via Spatie Laravel Backup');

/**
 * Weekly Files Backup (Spatie Laravel Backup)
 * Runs every Sunday at 02:00 IST.
 * Backs up storage/app (bank files, payslips, uploaded documents).
 */
Schedule::command('backup:run --only-files')
    ->weeklyOn(0, '02:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping()
    ->name('weekly-files-backup')
    ->description('Weekly storage/app files backup via Spatie Laravel Backup');

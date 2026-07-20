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

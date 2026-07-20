<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('leave:accrue {--location= : Specific location ID to process} {--year= : Year (default: current)} {--month= : Month 1-12 (default: current)}')]
#[Description('Run monthly leave accruals for all active locations (or a specific one). Also expires overdue Comp Off balances.')]
class RunLeaveAccruals extends Command
{
    public function handle(\App\Services\Leave\LeaveAccrualEngine $engine): int
    {
        $locationId = $this->option('location') ? (int) $this->option('location') : null;
        $year       = $this->option('year')     ? (int) $this->option('year')     : (int) now()->format('Y');
        $month      = $this->option('month')    ? (int) $this->option('month')    : (int) now()->format('n');

        $this->info("Running leave accruals for {$year}-{$month}" . ($locationId ? " (Location #{$locationId})" : ' (all locations)'));

        \App\Jobs\AccrueLeavesJob::dispatch($locationId, $year, $month)->onQueue('default');

        $this->info('AccrueLeavesJob dispatched successfully.');

        return self::SUCCESS;
    }
}

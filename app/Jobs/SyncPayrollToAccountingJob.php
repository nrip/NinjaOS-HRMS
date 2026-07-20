<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PayrollRecord;
use App\Services\Integrations\Accounting\AccountingIntegrationInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SyncPayrollToAccountingJob
 *
 * Dispatched after payroll finalization to sync salary journal entries
 * to the accounting system (Tally/Zoho Books).
 */
class SyncPayrollToAccountingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 300; // 5 minutes between retries

    public function __construct(
        public readonly int $month,
        public readonly int $year,
    ) {}

    public function handle(AccountingIntegrationInterface $accounting): void
    {
        $records = PayrollRecord::with('employee')
            ->where('payroll_month', $this->month)
            ->where('payroll_year', $this->year)
            ->where('status', 'finalized')
            ->get();

        if ($records->isEmpty()) {
            Log::channel('accounting')->warning('SyncPayrollToAccountingJob: no finalized records found', [
                'month' => $this->month,
                'year'  => $this->year,
            ]);
            return;
        }

        $result = $accounting->syncPayroll($records, $this->month, $this->year);

        if (! $result['success']) {
            $this->fail(new \RuntimeException($result['error'] ?? 'Accounting sync failed'));
        }
    }
}

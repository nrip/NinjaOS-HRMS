<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\PayrollRecord;
use Illuminate\Support\Collection;

/**
 * PayrollVarianceService
 *
 * Generates the variance report for a given payroll month/year.
 * Flags any employee whose net pay has changed by more than the configured
 * threshold (default 5%) compared to the previous month.
 *
 * Per the mandate: flagged employees MUST receive explicit HR acknowledgment
 * before the payroll can be finalized.
 */
class PayrollVarianceService
{
    /**
     * Generate the variance report for a location and period.
     *
     * @return array{
     *   total_employees: int,
     *   flagged_count: int,
     *   unacknowledged_count: int,
     *   can_finalize: bool,
     *   threshold_percent: float,
     *   records: Collection,
     * }
     */
    public function generateReport(int $locationId, int $month, int $year): array
    {
        $threshold = (float) config('statutory.payroll.variance_threshold_percent', 5.0);

        $records = PayrollRecord::where('location_id', $locationId)
            ->where('payroll_month', $month)
            ->where('payroll_year', $year)
            ->with('employee')
            ->get();

        $flagged       = $records->where('variance_flag', true);
        $unacknowledged = $flagged->where('variance_acknowledged', false);

        return [
            'total_employees'     => $records->count(),
            'flagged_count'       => $flagged->count(),
            'unacknowledged_count'=> $unacknowledged->count(),
            'can_finalize'        => $unacknowledged->isEmpty(),
            'threshold_percent'   => $threshold,
            'records'             => $records->sortByDesc('variance_percent'),
        ];
    }

    /**
     * Acknowledge a variance flag for a specific payroll record.
     */
    public function acknowledgeVariance(int $payrollRecordId, int $userId): PayrollRecord
    {
        $record = PayrollRecord::findOrFail($payrollRecordId);
        $record->acknowledgeVariance($userId);
        return $record;
    }

    /**
     * Check if all variance flags are acknowledged for a location/period.
     * This is the gate check before finalization is allowed.
     */
    public function allVariancesAcknowledged(int $locationId, int $month, int $year): bool
    {
        return ! PayrollRecord::where('location_id', $locationId)
            ->where('payroll_month', $month)
            ->where('payroll_year', $year)
            ->where('variance_flag', true)
            ->where('variance_acknowledged', false)
            ->exists();
    }
}

<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * LeaveProjectionService
 *
 * Calculates a 12-month forward projection of leave balances for an employee.
 *
 * Algorithm:
 *   1. Load the employee's current balances for each leave type.
 *   2. For each future month (up to 12), simulate:
 *      a. Monthly accrual additions (from config)
 *      b. Deductions for already-approved future leave applications
 *   3. Return a month-by-month projection array per leave type.
 *
 * This is a read-only simulation — it does NOT mutate any database records.
 * The projection is computed in-memory for performance.
 */
class LeaveProjectionService
{
    public function __construct(
        private readonly LeaveAccrualEngine $accrualEngine,
    ) {}

    /**
     * Generate a 12-month forward projection for an employee.
     *
     * @param Employee $employee
     * @param Carbon   $asOf     The date from which to project (defaults to today)
     * @return array  Keyed by leave_type, each containing an array of monthly snapshots
     *
     * Example return structure:
     * [
     *   'EL' => [
     *     ['month' => '2026-08', 'projected_balance' => 12.5, 'accrual' => 1.5, 'planned_availed' => 0],
     *     ...
     *   ],
     *   'CL' => [...],
     * ]
     */
    public function project(Employee $employee, ?Carbon $asOf = null): array
    {
        $asOf     = $asOf ?? Carbon::now();
        $location = $employee->location()->withoutGlobalScopes()->first();
        $stateCode= $location?->state_code ?? 'default';

        // Load current balances — use withoutGlobalScopes to bypass LocationScope in CLI context
        $currentBalances = LeaveBalance::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('year', $asOf->year)
            ->get()
            ->keyBy('leave_type');

        // Load all approved future leave applications (from today onwards)
        // Uses the composite index: (employee_id, status, from_date, to_date)
        $futureApplications = LeaveApplication::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('from_date', '>=', $asOf->toDateString())
            ->get();

        // Group future applications by leave_type and month
        $futureAvailed = [];
        foreach ($futureApplications as $app) {
            $monthKey = Carbon::parse($app->from_date)->format('Y-m');
            $futureAvailed[$app->leave_type][$monthKey] =
                ($futureAvailed[$app->leave_type][$monthKey] ?? 0)
                + (float) $app->number_of_days;
        }

        $leaveConfig = $this->accrualEngine->getLeaveConfig($stateCode);
        $leaveTypes  = array_keys($leaveConfig['leave_types'] ?? []);

        $projection = [];

        foreach ($leaveTypes as $leaveType) {
            $typeConfig    = $leaveConfig['leave_types'][$leaveType];
            $frequency     = $typeConfig['accrual_frequency'] ?? 'none';
            $ratePerMonth  = (float) ($typeConfig['accrual_rate_per_month'] ?? 0.0);

            // Start from the current closing balance
            $currentBalance = (float) ($currentBalances[$leaveType]->closing_balance ?? 0.0);

            $monthlyProjection = [];

            for ($i = 1; $i <= 12; $i++) {
                $projectedMonth = $asOf->copy()->addMonths($i);
                $monthKey       = $projectedMonth->format('Y-m');

                // Accrue for this month if applicable
                $accrual = 0.0;
                if ($frequency === 'monthly' && $ratePerMonth > 0) {
                    $accrual = $ratePerMonth;
                }

                // Deduct planned availed leaves for this month
                $plannedAvailed = $futureAvailed[$leaveType][$monthKey] ?? 0.0;

                $currentBalance = round($currentBalance + $accrual - $plannedAvailed, 2);

                $monthlyProjection[] = [
                    'month'              => $monthKey,
                    'accrual'            => $accrual,
                    'planned_availed'    => $plannedAvailed,
                    'projected_balance'  => max(0.0, $currentBalance),
                ];
            }

            $projection[$leaveType] = $monthlyProjection;
        }

        return $projection;
    }

    /**
     * Get a simplified summary of the projection — just the 12-month-end balance per type.
     *
     * @param Employee $employee
     * @return Collection  Keyed by leave_type, value = projected balance at 12 months
     */
    public function summary(Employee $employee): Collection
    {
        $projection = $this->project($employee);

        return collect($projection)->map(function (array $months) {
            return $months[count($months) - 1]['projected_balance'] ?? 0.0;
        });
    }
}

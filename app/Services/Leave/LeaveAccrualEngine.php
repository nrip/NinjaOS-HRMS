<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveBalance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * LeaveAccrualEngine
 *
 * All accrual rules are read from config/statutory.php — nothing is hardcoded here.
 * This engine handles:
 *   - Pro-rata accrual for mid-year joiners
 *   - Monthly accrual for monthly-frequency leave types
 *   - Year-end carry-forward capping with lapse/encash handling
 *   - Comp Off expiry deductions
 */
class LeaveAccrualEngine
{
    /**
     * Retrieve the effective leave config for a given state code.
     * Falls back to 'default' if no state-specific config exists.
     */
    public function getLeaveConfig(string $stateCode): array
    {
        return config("statutory.leave.{$stateCode}")
            ?? config('statutory.leave.default')
            ?? [];
    }

    /**
     * Retrieve the config for a specific leave type within a state.
     */
    public function getLeaveTypeConfig(string $stateCode, string $leaveType): ?array
    {
        $config = $this->getLeaveConfig($stateCode);
        return $config['leave_types'][$leaveType] ?? null;
    }

    /**
     * Calculate the pro-rata accrual for a given employee, leave type, and year.
     *
     * For monthly-frequency leave types, the accrual is prorated based on the
     * employee's date_of_joining. If the employee joined in a prior year, they
     * receive the full annual accrual (12 months × rate). If they joined in the
     * current year, they receive (months remaining from joining month to Dec) × rate.
     *
     * @return float Days to accrue for the full year (rounded to 2 decimal places)
     */
    public function calculateProRataAccrual(
        Employee $employee,
        string $leaveType,
        int $year,
        string $stateCode,
    ): float {
        $typeConfig = $this->getLeaveTypeConfig($stateCode, $leaveType);

        if (! $typeConfig) {
            return 0.0;
        }

        $frequency = $typeConfig['accrual_frequency'] ?? 'none';

        if ($frequency !== 'monthly') {
            // Non-monthly types (annual, on_grant, none) are not handled here
            return 0.0;
        }

        $ratePerMonth = (float) ($typeConfig['accrual_rate_per_month'] ?? 0.0);

        if ($ratePerMonth <= 0) {
            return 0.0;
        }

        $joiningDate = Carbon::parse($employee->date_of_joining);
        $joiningYear = $joiningDate->year;

        if ($joiningYear < $year) {
            // Employee joined before this year — full 12 months
            $months = 12;
        } elseif ($joiningYear === $year) {
            // Employee joined this year — count months from joining month to December
            // Month 1 = January, Month 12 = December
            // If joined in July (month 7), remaining months = 12 - 7 + 1 = 6
            $months = 12 - $joiningDate->month + 1;
        } else {
            // Employee joins in a future year — no accrual for this year
            return 0.0;
        }

        return round($months * $ratePerMonth, 2);
    }

    /**
     * Apply year-end carry-forward capping logic.
     *
     * Reads carry_forward_limit and excess_handling from config.
     * Returns an array with three keys:
     *   - carry_forward: days to carry into the new year's opening balance
     *   - lapsed:        days that lapse (excess_handling = 'lapse')
     *   - encashed:      days to encash (excess_handling = 'encash')
     *
     * @param float  $currentBalance The employee's closing balance at year-end
     * @param string $leaveType      Leave type code (e.g. 'EL', 'CL')
     * @param string $stateCode      2-letter state code
     * @return array{carry_forward: float, lapsed: float, encashed: float}
     */
    public function applyYearEndCarryForward(
        float $currentBalance,
        string $leaveType,
        string $stateCode,
    ): array {
        $typeConfig = $this->getLeaveTypeConfig($stateCode, $leaveType);

        if (! $typeConfig) {
            return ['carry_forward' => 0.0, 'lapsed' => 0.0, 'encashed' => 0.0];
        }

        $limit           = (float) ($typeConfig['carry_forward_limit'] ?? 0.0);
        $excessHandling  = $typeConfig['excess_handling'] ?? 'lapse';
        $encashAllowed   = (bool) ($typeConfig['encashment_allowed'] ?? false);

        $carryForward = min($currentBalance, $limit);
        $excess       = max(0.0, $currentBalance - $limit);

        $lapsed   = 0.0;
        $encashed = 0.0;

        if ($excess > 0) {
            if ($excessHandling === 'encash' && $encashAllowed) {
                $encashed = $excess;
            } else {
                $lapsed = $excess;
            }
        }

        return [
            'carry_forward' => round($carryForward, 2),
            'lapsed'        => round($lapsed, 2),
            'encashed'      => round($encashed, 2),
        ];
    }

    /**
     * Expire all Comp Off (and other expiring) leave balances whose expiry_date
     * is on or before the given date. Sets their closing_balance to 0 and logs
     * the expiry.
     *
     * @return int Total number of days expired across all affected balances
     */
    public function expireCompOffBalances(Carbon $asOf): int
    {
        $totalExpired = 0;

        $expiredBalances = LeaveBalance::withoutGlobalScopes()
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $asOf->toDateString())
            ->where('closing_balance', '>', 0)
            ->get();

        foreach ($expiredBalances as $balance) {
            $expiredDays = (float) $balance->closing_balance;

            DB::transaction(function () use ($balance, $expiredDays) {
                // Zero out the balance — the days are forfeited
                $balance->closing_balance = 0.00;
                $balance->accrued         = max(0.0, (float) $balance->accrued - $expiredDays);
                $balance->save();

                Log::info('LeaveAccrualEngine: Comp Off balance expired', [
                    'employee_id' => $balance->employee_id,
                    'leave_type'  => $balance->leave_type,
                    'expired_days'=> $expiredDays,
                    'expiry_date' => $balance->expiry_date,
                ]);
            });

            $totalExpired += (int) ceil($expiredDays);
        }

        return $totalExpired;
    }

    /**
     * Run the monthly accrual for all eligible employees at a given location.
     * Called by AccrueLeavesJob on the first day of each month.
     *
     * @param int    $locationId
     * @param string $stateCode
     * @param int    $year
     * @param int    $month  1–12
     */
    public function runMonthlyAccrual(int $locationId, string $stateCode, int $year, int $month): void
    {
        $config = $this->getLeaveConfig($stateCode);

        if (empty($config['leave_types'])) {
            return;
        }

        $employees = Employee::withoutGlobalScopes()
            ->where('location_id', $locationId)
            ->whereIn('status', ['confirmed', 'probation'])
            ->get();

        foreach ($employees as $employee) {
            $joiningDate = Carbon::parse($employee->date_of_joining);

            // Skip employees who haven't joined yet this month
            if ($joiningDate->year > $year || ($joiningDate->year === $year && $joiningDate->month > $month)) {
                continue;
            }

            foreach ($config['leave_types'] as $leaveType => $typeConfig) {
                if (($typeConfig['accrual_frequency'] ?? 'none') !== 'monthly') {
                    continue;
                }

                $ratePerMonth = (float) ($typeConfig['accrual_rate_per_month'] ?? 0.0);

                if ($ratePerMonth <= 0) {
                    continue;
                }

                // If the employee joined this year in this month, prorate for partial month
                // (simplified: grant full month's accrual from joining month onwards)
                $accrualDays = $ratePerMonth;

                DB::transaction(function () use ($employee, $locationId, $leaveType, $year, $accrualDays) {
                    $balance = LeaveBalance::withoutGlobalScopes()->firstOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'leave_type'  => $leaveType,
                            'year'        => $year,
                        ],
                        [
                            'location_id'     => $locationId,
                            'opening_balance' => 0.0,
                            'accrued'         => 0.0,
                            'availed'         => 0.0,
                            'pending'         => 0.0,
                            'closing_balance' => 0.0,
                            'expiry_date'     => null,
                        ]
                    );

                    $balance->addAccrual($accrualDays);
                });
            }
        }
    }
}

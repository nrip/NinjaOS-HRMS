<?php

declare(strict_types=1);

namespace App\Services\StatutoryEngine\DTOs;

/**
 * PayrollInputDTO
 *
 * Immutable value object that carries all data required by the StatutoryEngine
 * calculators. No Eloquent models are allowed inside this DTO — all data must
 * be resolved by the PayrollService before constructing this object.
 *
 * LWP Proration Formula (documented here as the single source of truth):
 *   Deduction = (Component / totalWorkingDays) * lwpDays
 *
 * Example: Basic = ₹30,000, totalWorkingDays = 26, lwpDays = 2
 *   LWP deduction on Basic = (30000 / 26) * 2 = ₹2,307.69
 */
readonly class PayrollInputDTO
{
    /**
     * @param string $employeeId          Internal DB ID of the employee
     * @param string $employeeCode        Human-readable code (EMP-MH-00001)
     * @param string $stateCode           ISO 3166-2 state code (MH, KA, DL, HR, …)
     * @param float  $grossSalary         Agreed monthly CTC gross (pre-LWP)
     * @param float  $basicSalary         Basic component (typically 40–50% of gross)
     * @param float  $hra                 House Rent Allowance component
     * @param float  $specialAllowance    Balancing/special allowance component
     * @param float  $otherAllowances     Any other taxable allowances (transport, etc.)
     * @param int    $totalWorkingDays    Calendar working days in the payroll month
     * @param float  $lwpDays            Leave Without Pay days (from Phase 3 LeaveApplication)
     * @param float  $encashmentDays     EL encashment days to be paid out (from Phase 3 LeaveBalance)
     * @param float  $noticePayRecovery  Notice period recovery amount (from Phase 1 lifecycle)
     * @param float  $otEarnings         Overtime earnings for the month (from Phase 2 Attendance)
     * @param int    $yearsOfService     Completed years of service (for Gratuity eligibility)
     * @param bool   $isEsiEligible      True if employee's gross is within ESI ceiling at time of joining
     * @param bool   $isDisabled         True for persons with disabilities (ESI ceiling = 25k)
     * @param bool   $optedForPf         True if employee has opted for PF (mandatory if basic < 15k)
     * @param string $taxRegime          'old' or 'new' — employee's chosen Income Tax regime
     * @param array  $investmentDeclarations Declared investments for TDS projection
     *                                   Keys: '80c', '80d', 'hra_rent_paid', 'lta', 'other_deductions'
     *                                   Values: float amounts in INR
     * @param int    $payrollMonth       Month number (1–12) for PT February rule
     * @param int    $payrollYear        Year for context
     */
    public function __construct(
        public readonly string $employeeId,
        public readonly string $employeeCode,
        public readonly string $stateCode,
        public readonly float  $grossSalary,
        public readonly float  $basicSalary,
        public readonly float  $hra,
        public readonly float  $specialAllowance,
        public readonly float  $otherAllowances,
        public readonly int    $totalWorkingDays,
        public readonly float  $lwpDays,
        public readonly float  $encashmentDays,
        public readonly float  $noticePayRecovery,
        public readonly float  $otEarnings,
        public readonly int    $yearsOfService,
        public readonly bool   $isEsiEligible,
        public readonly bool   $isDisabled,
        public readonly bool   $optedForPf,
        public readonly string $taxRegime,
        public readonly array  $investmentDeclarations,
        public readonly int    $payrollMonth,
        public readonly int    $payrollYear,
    ) {
        // Validate tax regime
        if (! in_array($taxRegime, ['old', 'new'], true)) {
            throw new \InvalidArgumentException(
                "taxRegime must be 'old' or 'new', got: {$taxRegime}"
            );
        }

        // Validate month
        if ($payrollMonth < 1 || $payrollMonth > 12) {
            throw new \InvalidArgumentException(
                "payrollMonth must be 1–12, got: {$payrollMonth}"
            );
        }

        // Validate working days
        if ($totalWorkingDays < 1) {
            throw new \InvalidArgumentException(
                "totalWorkingDays must be >= 1, got: {$totalWorkingDays}"
            );
        }

        // Validate LWP days do not exceed total working days
        if ($lwpDays > $totalWorkingDays) {
            throw new \InvalidArgumentException(
                "lwpDays ({$lwpDays}) cannot exceed totalWorkingDays ({$totalWorkingDays})"
            );
        }
    }

    /**
     * Calculate the LWP-prorated value of a given salary component.
     *
     * Formula: (component / totalWorkingDays) * lwpDays
     *
     * @param float $component The salary component amount (e.g., basicSalary)
     * @return float The deduction amount, rounded to 2 decimal places
     */
    public function lwpDeductionFor(float $component): float
    {
        if ($this->lwpDays === 0.0) {
            return 0.0;
        }

        return round(($component / $this->totalWorkingDays) * $this->lwpDays, 2);
    }

    /**
     * Return the effective gross after LWP deduction.
     * All components are prorated proportionally.
     */
    public function effectiveGross(): float
    {
        return round(
            $this->grossSalary
            - $this->lwpDeductionFor($this->grossSalary)
            + $this->otEarnings,
            2
        );
    }
}

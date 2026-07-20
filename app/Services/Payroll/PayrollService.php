<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\PayrollRecord;
use App\Services\StatutoryEngine\BonusCalculator;
use App\Services\StatutoryEngine\DTOs\PayrollInputDTO;
use App\Services\StatutoryEngine\EsiCalculator;
use App\Services\StatutoryEngine\GratuityCalculator;
use App\Services\StatutoryEngine\NetPayCalculator;
use App\Services\StatutoryEngine\PfCalculator;
use App\Services\StatutoryEngine\PtCalculator;
use App\Services\StatutoryEngine\TdsCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * PayrollService
 *
 * Orchestrates the full payroll processing pipeline for a given month/year.
 * Integrates with:
 *   - Phase 1: Employee lifecycle (notice pay recovery, joining date for proration)
 *   - Phase 3: LeaveApplication (LWP deductions) and LeaveBalance (encashment)
 *   - StatutoryEngine: Pure PHP calculators for PF, ESI, PT, TDS, Gratuity, Bonus
 */
class PayrollService
{
    public function __construct(
        private readonly NetPayCalculator   $netPayCalculator,
        private readonly PfCalculator       $pfCalculator,
        private readonly EsiCalculator      $esiCalculator,
        private readonly PtCalculator       $ptCalculator,
        private readonly TdsCalculator      $tdsCalculator,
        private readonly GratuityCalculator $gratuityCalculator,
        private readonly BonusCalculator    $bonusCalculator,
    ) {}

    /**
     * Process payroll for all active employees at a given location for a given month.
     *
     * @param int $locationId
     * @param int $month  1–12
     * @param int $year
     * @return Collection<PayrollRecord>
     */
    public function processPayrollForLocation(int $locationId, int $month, int $year): Collection
    {
        $employees = Employee::withoutGlobalScopes()
            ->where('location_id', $locationId)
            ->where('status', 'active')
            ->get();

        $records = collect();
        foreach ($employees as $employee) {
            $records->push($this->processEmployeePayroll($employee, $month, $year));
        }

        return $records;
    }

    /**
     * Process payroll for a single employee for a given month.
     */
    public function processEmployeePayroll(Employee $employee, int $month, int $year): PayrollRecord
    {
        return DB::transaction(function () use ($employee, $month, $year) {
            $config = config('statutory.payroll');

            // ── Step 1: Gather Phase 3 data ───────────────────────────────────
            $lwpDays         = $this->getLwpDays($employee, $month, $year);
            $encashmentDays  = $this->getEncashmentDays($employee, $month, $year);

            // ── Step 2: Gather Phase 1 data ───────────────────────────────────
            $noticePayRecovery = $this->getNoticePayRecovery($employee, $month, $year);

            // ── Step 3: Build the DTO ─────────────────────────────────────────
            $dto = new PayrollInputDTO([
                'employeeId'          => $employee->id,
                'employeeCode'        => $employee->employee_code,
                'stateCode'           => $employee->location->state_code ?? 'MH',
                'grossSalary'         => (float) ($employee->gross_salary ?? 0),
                'basicSalary'         => (float) ($employee->basic_salary ?? 0),
                'hra'                 => (float) ($employee->hra ?? 0),
                'specialAllowance'    => (float) ($employee->special_allowance ?? 0),
                'totalWorkingDays'    => $this->getTotalWorkingDays($month, $year),
                'lwpDays'             => $lwpDays,
                'otEarnings'          => 0.0,
                'encashmentDays'      => $encashmentDays,
                'noticePayRecovery'   => $noticePayRecovery,
                'optedForPf'          => (bool) ($employee->opted_for_pf ?? true),
                'isEsiEligible'       => $this->isEsiEligible($employee),
                'yearsOfService'      => $this->getYearsOfService($employee),
                'taxRegime'           => $employee->tax_regime ?? 'new',
                'investmentDeclarations' => $employee->investment_declarations ?? [],
                'payrollMonth'        => $month,
                'payrollYear'         => $year,
            ]);

            // ── Step 4: Run the StatutoryEngine ───────────────────────────────
            $result = $this->netPayCalculator->calculate($dto, [
                'pf'  => $config['pf'],
                'esi' => $config['esi'],
                'pt'  => $config['pt_slabs'],
                'tds' => $config['tds'],
                'lwp' => $config['lwp'],
            ]);

            // ── Step 5: Compute variance vs previous month ────────────────────
            $prevRecord = PayrollRecord::where('employee_id', $employee->id)
                ->where('payroll_month', $month === 1 ? 12 : $month - 1)
                ->where('payroll_year', $month === 1 ? $year - 1 : $year)
                ->first();

            $prevNetPay       = $prevRecord ? (float) $prevRecord->net_pay : null;
            $variancePercent  = null;
            $varianceFlag     = false;

            if ($prevNetPay !== null && $prevNetPay > 0) {
                $variancePercent = round(abs($result['net_pay'] - $prevNetPay) / $prevNetPay * 100, 2);
                $varianceFlag    = $variancePercent > (float) $config['variance_threshold_percent'];
            }

            // ── Step 6: Persist the PayrollRecord ─────────────────────────────
            $record = PayrollRecord::updateOrCreate(
                [
                    'employee_id'   => $employee->id,
                    'payroll_month' => $month,
                    'payroll_year'  => $year,
                ],
                [
                    'employee_code'     => $employee->employee_code,
                    'location_id'       => $employee->location_id,
                    'state_code'        => $employee->location->state_code ?? 'MH',
                    'tax_regime'        => $dto->taxRegime,
                    'gross_salary'      => $dto->grossSalary,
                    'basic_salary'      => $dto->basicSalary,
                    'hra'               => $dto->hra,
                    'special_allowance' => $dto->specialAllowance,
                    'ot_earnings'       => $result['ot_earnings'],
                    'encashment_payout' => $result['encashment_payout'],
                    'lwp_days'          => $dto->lwpDays,
                    'lwp_deduction'     => $result['lwp_deduction'],
                    'effective_gross'   => $result['effective_gross'],
                    'employee_pf'       => $result['employee_pf'],
                    'employer_pf'       => $result['employer_pf'],
                    'employee_esi'      => $result['employee_esi'],
                    'employer_esi'      => $result['employer_esi'],
                    'professional_tax'  => $result['professional_tax'],
                    'monthly_tds'       => $result['monthly_tds'],
                    'notice_pay_recovery' => $result['notice_pay_recovery'],
                    'total_deductions'  => $result['total_deductions'],
                    'net_pay'           => $result['net_pay'],
                    'prev_net_pay'      => $prevNetPay,
                    'variance_percent'  => $variancePercent,
                    'variance_flag'     => $varianceFlag,
                    'payslip_snapshot'  => $result,
                    'status'            => 'draft',
                ]
            );

            return $record;
        });
    }

    // ── Phase 3 Integration ───────────────────────────────────────────────────

    /**
     * Get the number of LWP (Leave Without Pay) days for an employee in a given month.
     * Reads approved unpaid leave applications from Phase 3.
     */
    private function getLwpDays(Employee $employee, int $month, int $year): float
    {
        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();

        return (float) LeaveApplication::where('employee_id', $employee->id)
            ->where('leave_type', 'UL')
            ->where('status', 'approved')
            ->where('from_date', '<=', $endOfMonth)
            ->where('to_date', '>=', $startOfMonth)
            ->sum('working_days_count');
    }

    /**
     * Get the number of EL/PL days to be encashed for an employee in a given month.
     * Reads from Phase 3 LeaveBalance encashment_days field.
     */
    private function getEncashmentDays(Employee $employee, int $month, int $year): float
    {
        $balance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type', 'EL')
            ->where('year', $year)
            ->first();

        return $balance ? (float) ($balance->encashment_days_this_month ?? 0) : 0.0;
    }

    // ── Phase 1 Integration ───────────────────────────────────────────────────

    /**
     * Get notice pay recovery amount for an employee.
     * Reads from Phase 1 Employee lifecycle data.
     */
    private function getNoticePayRecovery(Employee $employee, int $month, int $year): float
    {
        // If employee is in notice period and has a notice_pay_recovery_amount set
        if ($employee->status === 'notice_period' && isset($employee->notice_pay_recovery_amount)) {
            return (float) $employee->notice_pay_recovery_amount;
        }
        return 0.0;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getTotalWorkingDays(int $month, int $year): int
    {
        $days = Carbon::create($year, $month, 1)->daysInMonth;
        $workingDays = 0;
        for ($d = 1; $d <= $days; $d++) {
            $day = Carbon::create($year, $month, $d);
            if (! $day->isWeekend()) {
                $workingDays++;
            }
        }
        return $workingDays;
    }

    private function isEsiEligible(Employee $employee): bool
    {
        $esiCeiling = (float) config('statutory.payroll.esi.wage_ceiling');
        return (float) ($employee->gross_salary ?? 0) <= $esiCeiling;
    }

    private function getYearsOfService(Employee $employee): int
    {
        if (! $employee->date_of_joining) {
            return 0;
        }
        return (int) Carbon::parse($employee->date_of_joining)->diffInYears(now());
    }
}

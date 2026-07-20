<?php

declare(strict_types=1);

namespace App\Services\StatutoryEngine;

use App\Services\StatutoryEngine\DTOs\PayrollInputDTO;

/**
 * NetPayCalculator
 *
 * Orchestrates all statutory calculators to produce the final payslip breakdown.
 * This class does NOT contain any calculation logic itself — it delegates to
 * the individual pure PHP calculators and assembles the final result.
 *
 * LWP Proration Formula (applied here):
 *   Deduction per component = (Component / totalWorkingDays) * lwpDays
 *
 * Net Pay = Effective Gross + Encashment Payout
 *         - Employee PF
 *         - Employee ESI
 *         - Professional Tax
 *         - Monthly TDS
 *         - Notice Pay Recovery
 */
final class NetPayCalculator
{
    public function __construct(
        private readonly PfCalculator  $pfCalculator,
        private readonly EsiCalculator $esiCalculator,
        private readonly PtCalculator  $ptCalculator,
        private readonly TdsCalculator $tdsCalculator,
    ) {}

    /**
     * @param PayrollInputDTO $input   The payroll input DTO
     * @param array           $config  Array with keys: 'pf', 'esi', 'pt', 'tds', 'lwp'
     *
     * @return array Full payslip breakdown
     */
    public function calculate(PayrollInputDTO $input, array $config): array
    {
        // ── Step 1: LWP deduction ─────────────────────────────────────────────
        // Formula: (Gross / totalWorkingDays) * lwpDays
        $lwpDeduction = $input->lwpDeductionFor($input->grossSalary);

        // ── Step 2: Effective gross after LWP and OT ─────────────────────────
        $effectiveGross = $input->effectiveGross();

        // ── Step 3: Leave encashment payout ──────────────────────────────────
        // Encashment = (Basic / 26) * encashmentDays
        $encashmentPayout = 0.0;
        if ($input->encashmentDays > 0) {
            $encashmentPayout = round(
                ($input->basicSalary / $config['lwp']['standard_working_days']) * $input->encashmentDays,
                2
            );
        }

        // ── Step 4: Statutory deductions ─────────────────────────────────────
        $pf  = $this->pfCalculator->calculate($input, $config['pf']);
        $esi = $this->esiCalculator->calculate($input, $config['esi']);
        $pt  = $this->ptCalculator->calculate($input, $config['pt']);
        $tds = $this->tdsCalculator->calculate($input, $config['tds']);

        // ── Step 5: Total deductions ──────────────────────────────────────────
        $totalDeductions = round(
            $pf['employee_pf']
            + $esi['employee_esi']
            + $pt
            + $tds['monthly_tds']
            + $input->noticePayRecovery,
            2
        );

        // ── Step 6: Net pay ───────────────────────────────────────────────────
        $netPay = round($effectiveGross + $encashmentPayout - $totalDeductions, 2);

        return [
            // Earnings
            'gross_salary'       => $input->grossSalary,
            'effective_gross'    => $effectiveGross,
            'lwp_deduction'      => $lwpDeduction,
            'lwp_days'           => $input->lwpDays,
            'ot_earnings'        => $input->otEarnings,
            'encashment_payout'  => $encashmentPayout,

            // Statutory deductions
            'employee_pf'        => $pf['employee_pf'],
            'employer_pf'        => $pf['employer_total'],
            'pf_details'         => $pf,
            'employee_esi'       => $esi['employee_esi'],
            'employer_esi'       => $esi['employer_esi'],
            'professional_tax'   => $pt,
            'monthly_tds'        => $tds['monthly_tds'],
            'tds_details'        => $tds,
            'notice_pay_recovery'=> $input->noticePayRecovery,

            // Totals
            'total_deductions'   => $totalDeductions,
            'net_pay'            => $netPay,

            // Employer cost
            'employer_pf_total'  => $pf['employer_total'],
            'employer_esi_total' => $esi['employer_esi'],
            'total_employer_cost'=> round($effectiveGross + $pf['employer_total'] + $esi['employer_esi'], 2),
        ];
    }
}

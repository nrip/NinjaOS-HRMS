<?php

declare(strict_types=1);

namespace App\Services\StatutoryEngine;

use App\Services\StatutoryEngine\DTOs\PayrollInputDTO;

/**
 * TdsCalculator
 *
 * Calculates monthly TDS (Tax Deducted at Source) on salary income.
 * All tax slabs, rates, and deduction limits are read exclusively from $config.
 *
 * Statutory basis: Income Tax Act, 1961 — Section 192
 *
 * Algorithm:
 *   1. Project annual gross = monthly gross * 12
 *   2. Apply regime-specific deductions (standard deduction, 80C, 80D, HRA, etc.)
 *   3. Compute tax on taxable income using the chosen regime's slabs
 *   4. Apply surcharge if applicable
 *   5. Add 4% health & education cess
 *   6. Apply Section 87A rebate if eligible
 *   7. Divide by 12 to get monthly TDS
 *
 * The $config parameter must be config('statutory.payroll.tds').
 */
final class TdsCalculator
{
    /**
     * @param PayrollInputDTO $input  The payroll input DTO
     * @param array           $config config('statutory.payroll.tds')
     *
     * @return array{
     *   regime: string,
     *   annual_gross: float,
     *   taxable_income: float,
     *   tax_before_cess: float,
     *   surcharge: float,
     *   cess: float,
     *   annual_tax: float,
     *   monthly_tds: float,
     *   deductions_applied: array,
     * }
     */
    public function calculate(PayrollInputDTO $input, array $config): array
    {
        $regime = $input->taxRegime; // 'old' or 'new'

        // Step 1: Project annual gross
        $annualGross = $input->grossSalary * 12;

        // Step 2: Compute deductions based on regime
        $deductions = $this->computeDeductions($input, $config, $regime, $annualGross);
        $taxableIncome = max(0.0, $annualGross - $deductions['total']);

        // Step 3: Compute tax on taxable income using regime slabs
        $regimeConfig  = $config[$regime . '_regime'];
        $taxBeforeCess = $this->applySlabs($taxableIncome, $regimeConfig['slabs']);

        // Step 4: Apply surcharge
        $surcharge = $this->applySurcharge($taxableIncome, $taxBeforeCess, $regimeConfig['surcharge_slabs']);
        $taxAfterSurcharge = $taxBeforeCess + $surcharge;

        // Step 5: Apply Section 87A rebate (if taxable income is within rebate limit)
        $rebate = 0.0;
        if ($taxableIncome <= $regimeConfig['rebate_87a_limit']) {
            $rebate = min($taxAfterSurcharge, (float) $regimeConfig['rebate_87a_amount']);
        }
        $taxAfterRebate = max(0.0, $taxAfterSurcharge - $rebate);

        // Step 6: Add 4% health & education cess
        $cess       = round($taxAfterRebate * $config['cess_rate'], 2);
        $annualTax  = round($taxAfterRebate + $cess, 2);

        // Step 7: Monthly TDS = annual tax / 12
        $monthlyTds = round($annualTax / 12, 2);

        return [
            'regime'             => $regime,
            'annual_gross'       => $annualGross,
            'taxable_income'     => $taxableIncome,
            'tax_before_cess'    => $taxBeforeCess,
            'surcharge'          => $surcharge,
            'cess'               => $cess,
            'annual_tax'         => $annualTax,
            'monthly_tds'        => $monthlyTds,
            'deductions_applied' => $deductions,
        ];
    }

    /**
     * Compute allowable deductions based on the tax regime.
     * Old regime allows more deductions (80C, 80D, HRA, etc.).
     * New regime allows only the standard deduction.
     */
    private function computeDeductions(
        PayrollInputDTO $input,
        array $config,
        string $regime,
        float $annualGross
    ): array {
        $declarations = $input->investmentDeclarations;
        $deductions   = [];

        // Standard deduction is available in both regimes
        $deductions['standard_deduction'] = (float) $config['standard_deduction'];

        if ($regime === 'old') {
            // 80C: PF, LIC, ELSS, etc. — capped at ₹1,50,000
            $deductions['80c'] = min(
                (float) ($declarations['80c'] ?? 0.0),
                (float) $config['section_80c_limit']
            );

            // 80D: Medical insurance — capped at ₹25,000
            $deductions['80d'] = min(
                (float) ($declarations['80d'] ?? 0.0),
                (float) $config['section_80d_limit']
            );

            // HRA exemption (simplified: 40% of basic for non-metro)
            $annualBasic   = $input->basicSalary * 12;
            $annualHra     = $input->hra * 12;
            $rentPaid      = (float) ($declarations['hra_rent_paid'] ?? 0.0) * 12;
            $hraExemption  = 0.0;
            if ($rentPaid > 0) {
                $hraExemption = min(
                    $annualHra,
                    $rentPaid - ($annualBasic * 0.10),
                    $annualBasic * $config['hra_exemption_rate_non_metro']
                );
                $hraExemption = max(0.0, $hraExemption);
            }
            $deductions['hra_exemption'] = round($hraExemption, 2);

            // LTA exemption
            $deductions['lta'] = min(
                (float) ($declarations['lta'] ?? 0.0),
                (float) $config['lta_exemption_limit']
            );

            // Other deductions (NPS, etc.)
            $deductions['other'] = (float) ($declarations['other_deductions'] ?? 0.0);
        }

        $deductions['total'] = (float) array_sum($deductions);

        return $deductions;
    }

    /**
     * Apply progressive tax slabs to the taxable income.
     */
    private function applySlabs(float $taxableIncome, array $slabs): float
    {
        $tax = 0.0;
        foreach ($slabs as $slab) {
            if ($taxableIncome <= 0) {
                break;
            }
            if ($taxableIncome >= $slab['min']) {
                $slabMax    = $slab['max'] === PHP_INT_MAX ? $taxableIncome : (float) $slab['max'];
                $taxableInSlab = min($taxableIncome, $slabMax) - $slab['min'] + 1;
                if ($taxableInSlab > 0) {
                    $tax += $taxableInSlab * $slab['rate'];
                }
            }
        }
        return round($tax, 2);
    }

    /**
     * Apply surcharge based on taxable income.
     */
    private function applySurcharge(float $taxableIncome, float $tax, array $surchargeSlabs): float
    {
        foreach (array_reverse($surchargeSlabs) as $slab) {
            if ($taxableIncome > $slab['min']) {
                return round($tax * $slab['rate'], 2);
            }
        }
        return 0.0;
    }
}

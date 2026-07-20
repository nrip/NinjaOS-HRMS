<?php

declare(strict_types=1);

namespace App\Services\StatutoryEngine;

use App\Services\StatutoryEngine\DTOs\PayrollInputDTO;

/**
 * PtCalculator
 *
 * Calculates Professional Tax for a given state.
 * All slabs and applicability flags are read exclusively from the $config array.
 *
 * Statutory basis: State-specific Professional Tax Acts
 *
 * Key rules:
 *   - Delhi (DL) and Haryana (HR): PT is NOT levied → always returns 0.0
 *   - Maharashtra (MH): February has a special ₹300 slab (feb_amount)
 *   - All other states: standard slab lookup on monthly gross
 *
 * The $config parameter must be config('statutory.payroll.pt_slabs').
 * The state is identified by $input->stateCode (ISO 3166-2 code, e.g., 'MH').
 */
final class PtCalculator
{
    /**
     * @param PayrollInputDTO $input  The payroll input DTO
     * @param array           $config config('statutory.payroll.pt_slabs')
     *
     * @return float Monthly Professional Tax amount in INR
     */
    public function calculate(PayrollInputDTO $input, array $config): float
    {
        $stateCode = strtoupper($input->stateCode);

        // If state is not in config, no PT is applicable
        if (! isset($config[$stateCode])) {
            return 0.0;
        }

        $stateConfig = $config[$stateCode];

        // If PT is not applicable for this state, return 0
        if (! $stateConfig['is_applicable']) {
            return 0.0;
        }

        $grossSalary = $input->effectiveGross();
        $isFeb       = ($input->payrollMonth === 2);

        // Walk through slabs to find the applicable PT amount
        foreach ($stateConfig['slabs'] as $slab) {
            if ($grossSalary >= $slab['min'] && $grossSalary <= $slab['max']) {
                // Maharashtra has a special February amount
                if ($isFeb && isset($slab['feb_amount'])) {
                    return (float) $slab['feb_amount'];
                }
                return (float) $slab['amount'];
            }
        }

        return 0.0;
    }
}

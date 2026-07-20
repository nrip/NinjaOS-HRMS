<?php

declare(strict_types=1);

namespace App\Services\StatutoryEngine;

use App\Services\StatutoryEngine\DTOs\PayrollInputDTO;

/**
 * BonusCalculator
 *
 * Calculates annual bonus entitlement under the Payment of Bonus Act, 1965.
 * All ceilings and rates are read exclusively from the $config array.
 *
 * Statutory basis: Payment of Bonus Act, 1965
 *
 * Eligibility: Gross salary <= ₹21,000/month
 *
 * Calculation:
 *   - Bonus base = min(basicSalary, calculation_ceiling)
 *     Where calculation_ceiling = ₹7,000 (statutory cap on bonus calculation)
 *   - Annual bonus = bonus_base * 12 * min_rate (minimum 8.33%)
 *   - Maximum bonus = bonus_base * 12 * max_rate (maximum 20%)
 *
 * This calculator returns the MINIMUM bonus. The PayrollService may apply
 * a higher rate (up to max_rate) based on company policy.
 */
final class BonusCalculator
{
    /**
     * @param PayrollInputDTO $input  The payroll input DTO
     * @param array           $config config('statutory.payroll.bonus')
     *
     * @return array{
     *   eligible: bool,
     *   bonus_base: float,
     *   annual_bonus: float,
     *   monthly_provision: float,
     *   rate_applied: float,
     * }
     */
    public function calculate(PayrollInputDTO $input, array $config, float $rateOverride = 0.0): array
    {
        $wageCeiling = (float) $config['wage_ceiling'];

        // Eligibility: gross salary must be <= ₹21,000
        if ($input->grossSalary > $wageCeiling) {
            return [
                'eligible'          => false,
                'bonus_base'        => 0.0,
                'annual_bonus'      => 0.0,
                'monthly_provision' => 0.0,
                'rate_applied'      => 0.0,
            ];
        }

        // Bonus base = min(basic, calculation_ceiling)
        $calculationCeiling = (float) $config['calculation_ceiling'];
        $bonusBase          = min($input->basicSalary, $calculationCeiling);

        // Apply rate: use override if provided and within bounds, else use minimum
        $minRate     = (float) $config['min_rate'];
        $maxRate     = (float) $config['max_rate'];
        $rateApplied = ($rateOverride > 0.0)
            ? min($maxRate, max($minRate, $rateOverride))
            : $minRate;

        // Annual bonus = bonus_base * 12 * rate
        $annualBonus      = round($bonusBase * 12 * $rateApplied, 2);
        $monthlyProvision = round($annualBonus / 12, 2);

        return [
            'eligible'          => true,
            'bonus_base'        => $bonusBase,
            'annual_bonus'      => $annualBonus,
            'monthly_provision' => $monthlyProvision,
            'rate_applied'      => $rateApplied,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\StatutoryEngine;

use App\Services\StatutoryEngine\DTOs\PayrollInputDTO;

/**
 * GratuityCalculator
 *
 * Calculates gratuity entitlement for an employee.
 * All rates, ceilings, and eligibility thresholds are read from $config.
 *
 * Statutory basis: Payment of Gratuity Act, 1972
 *
 * Formula:
 *   Gratuity = (Basic / days_divisor) * years_multiplier * years_of_service
 *   Where:
 *     - days_divisor = 26 (standard working days per month)
 *     - years_multiplier = 15 (15 days per completed year of service)
 *
 * Ceiling: ₹20,00,000 (₹20 Lakh) — any amount above is capped
 *
 * Eligibility: Minimum 5 completed years of continuous service
 *
 * Note: This calculator computes the TOTAL gratuity entitlement at the time
 * of separation. It is NOT a monthly accrual figure.
 */
final class GratuityCalculator
{
    /**
     * @param PayrollInputDTO $input  The payroll input DTO
     * @param array           $config config('statutory.payroll.gratuity')
     *
     * @return array{
     *   eligible: bool,
     *   gratuity_amount: float,
     *   capped: bool,
     *   uncapped_amount: float,
     *   years_of_service: int,
     * }
     */
    public function calculate(PayrollInputDTO $input, array $config): array
    {
        $minimumYears = (int) $config['minimum_service_years'];

        // Check eligibility: minimum 5 completed years of service
        if ($input->yearsOfService < $minimumYears) {
            return [
                'eligible'        => false,
                'gratuity_amount' => 0.0,
                'capped'          => false,
                'uncapped_amount' => 0.0,
                'years_of_service'=> $input->yearsOfService,
            ];
        }

        // Formula: (Basic / 26) * 15 * years_of_service
        $dailyRate      = $input->basicSalary / $config['days_divisor'];
        $uncappedAmount = round($dailyRate * $config['years_multiplier'] * $input->yearsOfService, 2);

        // Apply the statutory ceiling
        $ceiling        = (float) $config['ceiling'];
        $capped         = $uncappedAmount > $ceiling;
        $gratuityAmount = $capped ? $ceiling : $uncappedAmount;

        return [
            'eligible'         => true,
            'gratuity_amount'  => $gratuityAmount,
            'capped'           => $capped,
            'uncapped_amount'  => $uncappedAmount,
            'years_of_service' => $input->yearsOfService,
        ];
    }
}

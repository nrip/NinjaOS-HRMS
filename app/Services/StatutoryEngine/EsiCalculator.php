<?php

declare(strict_types=1);

namespace App\Services\StatutoryEngine;

use App\Services\StatutoryEngine\DTOs\PayrollInputDTO;

/**
 * EsiCalculator
 *
 * Calculates Employee State Insurance contributions.
 * All rates and ceilings are read exclusively from the $config array.
 *
 * Statutory basis: ESI Act, 1948
 *
 * Eligibility is determined at the time of joining:
 *   - Standard ceiling: ₹21,000/month gross
 *   - Disabled employees ceiling: ₹25,000/month gross
 *
 * Once an employee's gross exceeds the ceiling, they are no longer eligible
 * for ESI. This is tracked by the isEsiEligible flag on the DTO.
 *
 * Rates:
 *   - Employee: 0.75% of gross
 *   - Employer: 3.25% of gross
 */
final class EsiCalculator
{
    /**
     * @param PayrollInputDTO $input  The payroll input DTO
     * @param array           $config config('statutory.payroll.esi')
     *
     * @return array{
     *   employee_esi: float,
     *   employer_esi: float,
     *   total_esi: float,
     *   esi_wage: float,
     * }
     */
    public function calculate(PayrollInputDTO $input, array $config): array
    {
        // ESI eligibility is determined at the time of joining and tracked on the DTO
        if (! $input->isEsiEligible) {
            return [
                'employee_esi' => 0.0,
                'employer_esi' => 0.0,
                'total_esi'    => 0.0,
                'esi_wage'     => 0.0,
            ];
        }

        // Use effective gross (after LWP deduction) as the ESI wage base
        $esiWage = $input->effectiveGross();

        // Employee contribution: 0.75% of ESI wage
        $employeeEsi = round($esiWage * $config['employee_rate'], 2);

        // Employer contribution: 3.25% of ESI wage
        $employerEsi = round($esiWage * $config['employer_rate'], 2);

        return [
            'employee_esi' => $employeeEsi,
            'employer_esi' => $employerEsi,
            'total_esi'    => round($employeeEsi + $employerEsi, 2),
            'esi_wage'     => $esiWage,
        ];
    }
}

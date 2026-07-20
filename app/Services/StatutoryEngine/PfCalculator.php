<?php

declare(strict_types=1);

namespace App\Services\StatutoryEngine;

use App\Services\StatutoryEngine\DTOs\PayrollInputDTO;

/**
 * PfCalculator
 *
 * Calculates Provident Fund contributions for both employee and employer.
 * All rates and ceilings are read exclusively from the $config array —
 * NO hardcoded numbers exist in this class.
 *
 * Statutory basis: Payment of Provident Funds Act, 1952
 *
 * Employer contribution split:
 *   - EPF (Employee Provident Fund): 3.67% of PF wage
 *   - EPS (Employee Pension Scheme): 8.33% of PF wage
 *   - Total employer = 12% of PF wage (same as employee)
 *
 * PF wage = min(basicSalary, wage_ceiling)
 * If employee has not opted for PF, all contributions are zero.
 */
final class PfCalculator
{
    /**
     * @param PayrollInputDTO $input  The payroll input DTO
     * @param array           $config config('statutory.payroll.pf')
     *
     * @return array{
     *   pf_wage: float,
     *   employee_pf: float,
     *   employer_epf: float,
     *   employer_eps: float,
     *   employer_total: float,
     *   admin_charges: float,
     *   edli: float,
     * }
     */
    public function calculate(PayrollInputDTO $input, array $config): array
    {
        // If employee has not opted for PF, return all zeros
        if (! $input->optedForPf) {
            return [
                'pf_wage'       => 0.0,
                'employee_pf'   => 0.0,
                'employer_epf'  => 0.0,
                'employer_eps'  => 0.0,
                'employer_total'=> 0.0,
                'admin_charges' => 0.0,
                'edli'          => 0.0,
            ];
        }

        // PF wage is capped at the statutory wage ceiling
        $pfWage = (float) min($input->basicSalary, $config['wage_ceiling']);

        // Employee contribution: 12% of PF wage
        $employeePf = round($pfWage * $config['employee_rate'], 2);

        // Employer EPS: 8.33% of PF wage (pension scheme)
        $employerEps = round($pfWage * $config['employer_eps_rate'], 2);

        // Employer EPF: 3.67% of PF wage (provident fund proper)
        $employerEpf = round($pfWage * $config['employer_pf_rate'], 2);

        // Admin charges: 0.5% of PF wage
        $adminCharges = round($pfWage * $config['admin_charges_rate'], 2);

        // EDLI: 0.5% of PF wage
        $edli = round($pfWage * $config['edli_rate'], 2);

        // Total employer contribution = EPF + EPS = 12% of PF wage
        $employerTotal = round($employerEpf + $employerEps, 2);

        return [
            'pf_wage'        => $pfWage,
            'employee_pf'    => $employeePf,
            'employer_epf'   => $employerEpf,
            'employer_eps'   => $employerEps,
            'employer_total' => $employerTotal,
            'admin_charges'  => $adminCharges,
            'edli'           => $edli,
        ];
    }
}

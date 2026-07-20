<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\PayrollRecord;
use Illuminate\Support\Collection;

/**
 * StatutoryChallanService
 *
 * Generates statutory challan data for:
 *   1. PF ECR (Electronic Challan cum Return) — EPFO format
 *   2. ESI Challan — ESIC portal format
 *   3. PT Challan — State-specific format
 *
 * These are CSV/text files that are uploaded to the respective government portals.
 */
class StatutoryChallanService
{
    /**
     * Generate PF ECR (Electronic Challan cum Return) in EPFO format.
     *
     * ECR Format: Member ID | Name | Gross Wages | EPF Wages | EPS Wages |
     *             EDLI Wages | EPF Contribution | EPS Contribution | EDLI Contribution |
     *             NCP Days | Refund of Advances
     *
     * @param Collection<PayrollRecord> $records
     * @return string  ECR text content
     */
    public function generatePfEcr(Collection $records, int $month, int $year): string
    {
        $lines = [];

        // ECR header
        $lines[] = '#~#';
        $lines[] = 'MEMBER_ID~NAME~GROSS_WAGES~EPF_WAGES~EPS_WAGES~EDLI_WAGES~EPF_CONTRIBUTION~EPS_CONTRIBUTION~EDLI_CONTRIBUTION~NCP_DAYS~REFUND_OF_ADVANCES';

        foreach ($records->where('status', 'finalized') as $record) {
            $employee  = $record->employee;
            $snapshot  = $record->payslip_snapshot ?? [];
            $pfDetails = $snapshot['pf_details'] ?? [];

            if (! $employee || empty($pfDetails) || ($pfDetails['employee_pf'] ?? 0) == 0) {
                continue;
            }

            $lines[] = implode('~', [
                $employee->uan_number ?? $record->employee_code,
                strtoupper($employee->full_name ?? $employee->name ?? 'N/A'),
                number_format((float) $record->gross_salary, 0, '.', ''),
                number_format((float) ($pfDetails['pf_wage'] ?? 0), 0, '.', ''),
                number_format((float) ($pfDetails['pf_wage'] ?? 0), 0, '.', ''),
                number_format((float) ($pfDetails['pf_wage'] ?? 0), 0, '.', ''),
                number_format((float) ($pfDetails['employee_pf'] ?? 0), 0, '.', ''),
                number_format((float) ($pfDetails['employer_eps'] ?? 0), 0, '.', ''),
                number_format((float) ($pfDetails['edli'] ?? 0), 0, '.', ''),
                (int) ($record->lwp_days ?? 0),
                '0',
            ]);
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Generate ESI challan data in ESIC portal format.
     *
     * @param Collection<PayrollRecord> $records
     * @return string  CSV content
     */
    public function generateEsiChallan(Collection $records, int $month, int $year): string
    {
        $lines = [];
        $lines[] = implode(',', [
            'Employee IP Number', 'Employee Name', 'Gross Wages',
            'Employee ESI', 'Employer ESI', 'Total ESI',
        ]);

        foreach ($records->where('status', 'finalized') as $record) {
            $employee = $record->employee;
            if (! $employee || $record->employee_esi == 0) {
                continue;
            }

            $lines[] = implode(',', [
                $employee->esi_ip_number ?? $record->employee_code,
                '"' . ($employee->full_name ?? $employee->name ?? 'N/A') . '"',
                number_format((float) $record->effective_gross, 2, '.', ''),
                number_format((float) $record->employee_esi, 2, '.', ''),
                number_format((float) $record->employer_esi, 2, '.', ''),
                number_format((float) ($record->employee_esi + $record->employer_esi), 2, '.', ''),
            ]);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Generate PT challan summary grouped by state.
     *
     * @param Collection<PayrollRecord> $records
     * @return array<string, array{state_code: string, total_pt: float, employee_count: int, records: Collection}>
     */
    public function generatePtChallanSummary(Collection $records): array
    {
        $byState = $records
            ->where('status', 'finalized')
            ->where('professional_tax', '>', 0)
            ->groupBy('state_code');

        $summary = [];
        foreach ($byState as $stateCode => $stateRecords) {
            $summary[$stateCode] = [
                'state_code'     => $stateCode,
                'total_pt'       => $stateRecords->sum('professional_tax'),
                'employee_count' => $stateRecords->count(),
                'records'        => $stateRecords,
            ];
        }

        return $summary;
    }

    /**
     * Generate filenames for statutory challans.
     */
    public function pfEcrFilename(int $month, int $year): string
    {
        return sprintf('pf_ecr_%04d_%02d.txt', $year, $month);
    }

    public function esiChallanFilename(int $month, int $year): string
    {
        return sprintf('esi_challan_%04d_%02d.csv', $year, $month);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\PayrollRecord;
use Illuminate\Support\Collection;

/**
 * BankTransferFileService
 *
 * Generates the HDFC NEFT/RTGS bulk payment file for salary disbursement.
 *
 * HDFC Salary Upload Format (CSV):
 *   Sr No | Beneficiary Name | Account No | IFSC | Amount | Remarks
 *
 * This file is uploaded to HDFC NetBanking → Bulk Payment → Salary Upload.
 */
class BankTransferFileService
{
    /**
     * Generate the HDFC bank transfer CSV for a given set of payroll records.
     *
     * @param Collection<PayrollRecord> $records
     * @return string  CSV content
     */
    public function generateHdfcCsv(Collection $records, int $month, int $year): string
    {
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $lines     = [];

        // HDFC Salary Upload header
        $lines[] = implode(',', [
            'Sr No',
            'Beneficiary Name',
            'Account Number',
            'IFSC Code',
            'Amount',
            'Remarks',
        ]);

        $srNo = 1;
        foreach ($records->where('status', 'finalized') as $record) {
            $employee = $record->employee;
            if (! $employee) {
                continue;
            }

            $lines[] = implode(',', [
                $srNo++,
                $this->csvEscape($employee->full_name ?? $employee->name ?? 'N/A'),
                $this->csvEscape($employee->bank_account_number ?? ''),
                $this->csvEscape($employee->bank_ifsc_code ?? ''),
                number_format((float) $record->net_pay, 2, '.', ''),
                $this->csvEscape("Salary {$monthName} {$year} - {$record->employee_code}"),
            ]);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Generate the filename for the bank transfer file.
     */
    public function filename(int $month, int $year, string $bankCode = 'HDFC'): string
    {
        return sprintf('salary_transfer_%s_%04d_%02d.csv', $bankCode, $year, $month);
    }

    private function csvEscape(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}

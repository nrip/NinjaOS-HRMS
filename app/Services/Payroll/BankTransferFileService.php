<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\PayrollRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * BankTransferFileService
 *
 * Generates the HDFC NEFT/RTGS bulk salary payment file.
 *
 * HDFC Salary Upload Format — 8 Mandatory Columns:
 * ─────────────────────────────────────────────────
 * 1. Transaction Type      (NEFT / RTGS / FT)
 * 2. Debit Account No      (Company's HDFC account number)
 * 3. Beneficiary Account No
 * 4. Beneficiary Name
 * 5. Amount                (2 decimal places, no thousand separator)
 * 6. Beneficiary IFSC
 * 7. Value Date            (DD/MM/YYYY — the salary credit date)
 * 8. Customer Reference No (e.g., SAL-JUL-2026-EMP-MH-00001)
 *
 * Files are stored in storage/app/bank-files/ (protected from public access).
 *
 * PRODUCTION NOTE: The company debit account number should be stored in
 * config/nexusos.php or environment variables, not hardcoded here.
 */
class BankTransferFileService
{
    /** Company's HDFC salary disbursement account (read from config). */
    private string $companyAccountNo;

    public function __construct()
    {
        $this->companyAccountNo = config('nexusos.bank.hdfc_debit_account', '50100000000000');
    }

    /**
     * Generate the HDFC bank transfer CSV for a given set of payroll records.
     *
     * @param  Collection<PayrollRecord>  $records
     * @param  int                        $month
     * @param  int                        $year
     * @return string  CSV content (CRLF line endings per HDFC spec)
     */
    public function generateHdfcCsv(Collection $records, int $month, int $year): string
    {
        $monthName = strtoupper(date('M', mktime(0, 0, 0, $month, 1)));
        $valueDate = now()->format('d/m/Y');
        $lines     = [];

        // ── Header row (8 columns) ────────────────────────────────────────────
        $lines[] = implode(',', [
            'Transaction Type',
            'Debit Account No',
            'Beneficiary Account No',
            'Beneficiary Name',
            'Amount',
            'Beneficiary IFSC',
            'Value Date',
            'Customer Reference No',
        ]);

        // ── Data rows ─────────────────────────────────────────────────────────
        foreach ($records->where('status', 'finalized') as $record) {
            $employee = $record->employee;
            if (! $employee) {
                continue;
            }

            // RTGS for amounts >= Rs 2,00,000; NEFT otherwise
            $transactionType = ((float) $record->net_pay >= 200000.00) ? 'RTGS' : 'NEFT';

            $fullName = $employee->full_name
                ?? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''));

            $lines[] = implode(',', [
                $transactionType,
                $this->csvEscape($this->companyAccountNo),
                $this->csvEscape($employee->bank_account_number ?? ''),
                $this->csvEscape($fullName),
                number_format((float) $record->net_pay, 2, '.', ''),
                $this->csvEscape($employee->bank_ifsc_code ?? ''),
                $valueDate,
                $this->csvEscape("SAL-{$monthName}-{$year}-{$record->employee_code}"),
            ]);
        }

        return implode("\r\n", $lines) . "\r\n";
    }

    /**
     * Generate and store the bank file in storage/app/bank-files/.
     *
     * @return string  The stored file path relative to storage/app/
     */
    public function generateAndStore(Collection $records, int $month, int $year): string
    {
        $csv      = $this->generateHdfcCsv($records, $month, $year);
        $filename = $this->filename($month, $year);
        $path     = 'bank-files/' . $filename;

        Storage::disk('local')->put($path, $csv);

        return $path;
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

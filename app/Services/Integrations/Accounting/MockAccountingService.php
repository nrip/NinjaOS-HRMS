<?php

declare(strict_types=1);

namespace App\Services\Integrations\Accounting;

use App\Models\PayrollRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MockAccountingService
 *
 * Simulates a Tally/Zoho Books accounting integration.
 *
 * PRODUCTION SWAP: Replace this binding in AppServiceProvider with:
 *   - TallyAccountingService  → generates XML and POSTs to Tally ODBC bridge
 *   - ZohoBooksService        → uses Zoho Books REST API (OAuth2)
 *
 * This mock generates a JSON journal entry payload and logs it to
 * storage/logs/accounting-mock.log for development and testing.
 */
class MockAccountingService implements AccountingIntegrationInterface
{
    public function syncPayroll(Collection $records, int $month, int $year): array
    {
        $referenceId = 'ACC-MOCK-' . strtoupper(Str::random(8));
        $monthName   = date('F', mktime(0, 0, 0, $month, 1));

        $finalized = $records->where('status', 'finalized');

        $totals = [
            'gross_salary'    => $finalized->sum('gross_salary'),
            'employee_pf'     => $finalized->sum('employee_pf'),
            'employer_pf'     => $finalized->sum('employer_pf'),
            'employee_esi'    => $finalized->sum('employee_esi'),
            'employer_esi'    => $finalized->sum('employer_esi'),
            'professional_tax' => $finalized->sum('professional_tax'),
            'tds'             => $finalized->sum('tds'),
            'net_pay'         => $finalized->sum('net_pay'),
        ];

        // Double-entry journal entry payload (Zoho Books / Tally compatible JSON)
        $journalEntry = [
            'reference_id'   => $referenceId,
            'date'           => now()->format('Y-m-d'),
            'narration'      => "Salary for {$monthName} {$year}",
            'currency_code'  => 'INR',
            'journal_type'   => 'both',
            'line_items'     => [
                // Debit: Salary Expense
                [
                    'account_name' => 'Salary Expense',
                    'debit_amount' => $totals['gross_salary'] + $totals['employer_pf'] + $totals['employer_esi'],
                    'credit_amount' => 0,
                    'description'  => "Gross salary + employer contributions — {$monthName} {$year}",
                ],
                // Credit: Employee PF Payable
                [
                    'account_name' => 'PF Payable (Employee)',
                    'debit_amount' => 0,
                    'credit_amount' => $totals['employee_pf'],
                    'description'  => "Employee PF deduction — {$monthName} {$year}",
                ],
                // Credit: Employer PF Payable
                [
                    'account_name' => 'PF Payable (Employer)',
                    'debit_amount' => 0,
                    'credit_amount' => $totals['employer_pf'],
                    'description'  => "Employer PF contribution — {$monthName} {$year}",
                ],
                // Credit: ESI Payable (combined)
                [
                    'account_name' => 'ESI Payable',
                    'debit_amount' => 0,
                    'credit_amount' => $totals['employee_esi'] + $totals['employer_esi'],
                    'description'  => "ESI (employee + employer) — {$monthName} {$year}",
                ],
                // Credit: PT Payable
                [
                    'account_name' => 'Professional Tax Payable',
                    'debit_amount' => 0,
                    'credit_amount' => $totals['professional_tax'],
                    'description'  => "Professional Tax — {$monthName} {$year}",
                ],
                // Credit: TDS Payable
                [
                    'account_name' => 'TDS Payable',
                    'debit_amount' => 0,
                    'credit_amount' => $totals['tds'],
                    'description'  => "TDS on Salary — {$monthName} {$year}",
                ],
                // Credit: Salary Payable (net pay)
                [
                    'account_name' => 'Salary Payable',
                    'debit_amount' => 0,
                    'credit_amount' => $totals['net_pay'],
                    'description'  => "Net salary payable — {$monthName} {$year}",
                ],
            ],
        ];

        Log::channel('accounting')->info('Accounting mock journal entry generated', [
            'reference_id'   => $referenceId,
            'month'          => $monthName,
            'year'           => $year,
            'employee_count' => $finalized->count(),
            'total_gross'    => $totals['gross_salary'],
            'total_net'      => $totals['net_pay'],
            'journal_entry'  => $journalEntry,
        ]);

        return [
            'success'      => true,
            'reference_id' => $referenceId,
            'error'        => null,
        ];
    }
}

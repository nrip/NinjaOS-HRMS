<?php

declare(strict_types=1);

namespace App\Services\Integrations\Accounting;

use App\Models\PayrollRecord;
use Illuminate\Support\Collection;

interface AccountingIntegrationInterface
{
    /**
     * Sync a finalized payroll run to the accounting system.
     *
     * Generates a double-entry journal entry:
     *   DR  Salary Expense          (gross payroll)
     *   CR  PF Payable              (employer + employee PF)
     *   CR  ESI Payable             (employer + employee ESI)
     *   CR  PT Payable              (professional tax)
     *   CR  TDS Payable             (income tax deducted at source)
     *   CR  Salary Payable / Bank   (net pay)
     *
     * @param  Collection<PayrollRecord>  $records
     * @param  int                        $month
     * @param  int                        $year
     * @return array{success: bool, reference_id: string|null, error: string|null}
     */
    public function syncPayroll(Collection $records, int $month, int $year): array;
}

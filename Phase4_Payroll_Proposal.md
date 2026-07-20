# Phase 4: Payroll & Statutory Compliance — Implementation Proposal

This document outlines the architecture, data flow, and testing strategy for Phase 4, strictly adhering to the orchestrator mandates, the 100% config-driven rule, and the isolated pure-PHP Statutory Engine requirement.

## 1. Isolated Statutory Engine Architecture

To ensure 100% testability and zero Eloquent coupling, the calculation logic will reside in `app/Services/StatutoryEngine/`. These classes will be pure PHP, accepting Data Transfer Objects (DTOs) and returning calculated arrays.

### 1.1 Data Transfer Objects (DTOs)

The engine will consume a `PayrollInputDTO` that aggregates all required data from Phase 1 (Core HR) and Phase 3 (Leave Management).

```php
namespace App\Services\StatutoryEngine\DTOs;

readonly class PayrollInputDTO
{
    public function __construct(
        public string $employeeId,
        public string $stateCode,          // e.g., 'MH', 'DL', 'HR'
        public float $grossSalary,         // Monthly gross
        public float $basicSalary,         // Usually 50% of gross
        public float $hra,                 // Usually 40% or 50% of basic
        public float $specialAllowance,    // Balancing figure
        public int $totalWorkingDays,      // From LeaveService/Calendar
        public float $lwpDays,             // Unpaid leave days from Phase 3
        public float $encashmentDays,      // Leave encashment from Phase 3
        public float $noticePayRecovery,   // From Phase 1 exit lifecycle
        public bool $isEsiEligible,        // Based on joining gross < 21k
        public bool $isDisabled,           // For ESI 25k ceiling check
        public bool $optedForPf,           // If basic < 15k, mandatory; else optional
    ) {}
}
```

### 1.2 Pure PHP Calculators

The engine will consist of dedicated calculators for each component:

- `PfCalculator::calculate(PayrollInputDTO $input, array $config): array`
  - Enforces ₹15,000 ceiling
  - Calculates Employee PF (12%), Employer PF (3.67%), and Employer EPS (8.33%)
- `EsiCalculator::calculate(PayrollInputDTO $input, array $config): array`
  - Enforces ₹21,000 ceiling (₹25,000 if `$input->isDisabled`)
  - Calculates Employee ESI (0.75%) and Employer ESI (3.25%)
- `PtCalculator::calculate(PayrollInputDTO $input, array $config): float`
  - Evaluates state-specific slabs from config
- `NetPayCalculator::calculate(PayrollInputDTO $input, array $config): array`
  - Orchestrates the above and computes final Net Pay, Gross Deductions, and Encashment payouts.

## 2. 100% Config-Driven Statutory Rules

The `config/statutory.php` file will be expanded to include all payroll ceilings, rates, and the 9-state PT slabs. **No hardcoded numbers will exist in the PHP classes.**

### 2.1 Expanded `config/statutory.php` Structure

```php
return [
    // ... existing leave and OT config ...

    'payroll' => [
        'pf' => [
            'wage_ceiling' => 15000,
            'employee_rate' => 12.0, // %
            'employer_pf_rate' => 3.67, // %
            'employer_eps_rate' => 8.33, // %
            'admin_charges' => 0.5, // %
        ],
        'esi' => [
            'wage_ceiling_standard' => 21000,
            'wage_ceiling_disabled' => 25000,
            'employee_rate' => 0.75, // %
            'employer_rate' => 3.25, // %
        ],
        'gratuity' => [
            'ceiling' => 2000000, // 20 Lakhs
            'days_divisor' => 26,
            'years_multiplier' => 15,
        ],
        'bonus' => [
            'wage_ceiling' => 21000,
            'calculation_ceiling' => 7000,
            'min_rate' => 8.33, // %
            'max_rate' => 20.0, // %
        ],
        
        // Professional Tax (PT) Slabs for all 9 states
        'pt_slabs' => [
            'MH' => [
                'is_applicable' => true,
                'slabs' => [
                    // Min, Max, Amount (Feb usually has 300, handled by month check)
                    ['min' => 0, 'max' => 7500, 'amount' => 0, 'feb_amount' => 0],
                    ['min' => 7501, 'max' => 10000, 'amount' => 175, 'feb_amount' => 175],
                    ['min' => 10001, 'max' => 9999999, 'amount' => 200, 'feb_amount' => 300],
                ]
            ],
            'KA' => [
                'is_applicable' => true,
                'slabs' => [
                    ['min' => 0, 'max' => 14999, 'amount' => 0],
                    ['min' => 15000, 'max' => 9999999, 'amount' => 200],
                ]
            ],
            'DL' => [
                'is_applicable' => false, // Delhi: Nil
                'slabs' => []
            ],
            'HR' => [
                'is_applicable' => false, // Haryana: Nil
                'slabs' => []
            ],
            // ... UP, GJ, WB, JH, GA defined similarly based on state laws
        ]
    ]
];
```

## 3. Payroll Processing & Variance Report

### 3.1 Processing Workflow
1. **Data Aggregation:** `PayrollService` gathers active employees, LWP from `LeaveApplication`, encashment from `LeaveBalance`, and OT from `Attendance`.
2. **DTO Mapping:** Maps data to `PayrollInputDTO`.
3. **Engine Execution:** Passes DTO to `StatutoryEngine` for pure calculation.
4. **Variance Check:** Compares resulting Net Pay against the previous month's `payroll_records`.
5. **Persistence:** Saves to `payroll_records` with status `draft`.

### 3.2 Variance Report Protocol
The system will generate a variance report before finalizing the payroll. 
- If `abs((CurrentNet - PreviousNet) / PreviousNet) > 0.05` (5%), the record is flagged `requires_acknowledgment`.
- HR must explicitly click "Acknowledge Variance" on the UI before the payroll batch can transition from `draft` to `approved`.

## 4. 1-Month Parallel Run Reconciliation Protocol

To satisfy the SRS mandate (Section 9.2) of a 1-month parallel run with a ₹1 variance threshold, the following protocol will be built into the system:

1. **Reconciliation Import:** A dedicated UI (`/payroll/reconciliation`) will allow uploading the Legacy System's final payroll CSV.
2. **Automated Diffing:** The system will join the Legacy CSV with the NexusOS `draft` payroll batch on `employee_code`.
3. **Variance Calculation:** It will calculate `Legacy_Net - NexusOS_Net`.
4. **Zero-Variance Gate:** If any variance > ₹1 exists, the UI will block the `approve` action and force a CSV export of the mismatches for root-cause analysis.
5. **Sign-off:** Only when all 16 locations show ₹0 variance can the batch be finalized.

## 5. Outputs

Once approved, the system will generate:
- **Payslips:** Snappy PDF generation, stored in `storage/app/payslips` and linked to the employee portal.
- **Bank File:** HDFC standard format CSV (Account Number, Amount, IFSC, Beneficiary Name).
- **Statutory Challans:** PF ECR text file, ESI Excel format, and PT state-wise reports.

## 6. Testing Strategy (100% Coverage Target)

The pure PHP nature of the `StatutoryEngine` makes 100% coverage trivial. I will write comprehensive Pest unit tests using data providers to test edge cases:

- `test_pf_calculation_respects_15k_ceiling_and_eps_split`
- `test_pt_calculation_applies_correct_state_slab_and_haryana_nil`
- `test_esi_calculation_respects_21k_standard_and_25k_disabled_ceilings`
- `test_lwp_deduction_prorates_basic_and_allowances_correctly`
- `test_payroll_variance_report_flags_greater_than_5_percent_change`

## Next Steps

If this proposal is approved, I will begin by writing the Unit Tests for the `StatutoryEngine`, followed by the pure PHP calculators, the config expansion, and finally the Laravel services and UI.

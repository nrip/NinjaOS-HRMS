# NexusOS Project State

**Current Phase:** Phase 4 - Payroll & Statutory Compliance (COMPLETE ✅)

**Last Updated:** 2026-07-20 16:00 GMT+5:30

**Status:** Phase 4 Complete - Ready for Phase 5 (Reports & Analytics)

---

## Phase 0: Foundation (COMPLETE ✅)

- [x] Laravel 11 project with Sail (MySQL 8, Redis 7)
- [x] Multi-tenancy architecture (TenantContext, LocationScope, EnforceLocationScope)
- [x] Database schema (16 migrations)
- [x] RBAC with 8 roles
- [x] Statutory configuration for 9 states
- [x] Health endpoint and auth endpoints
- [x] GitHub Actions CI pipeline
- [x] 16 locations seeded
- [x] Pest architecture test for LocationScope

---

## Phase 1: Core HR (COMPLETE ✅)

### Exit Gate Tests: 4/4 PASSED ✅

```
✓ location_scope_isolation: Location HR can only see their location's employees
✓ employee_code_generation: Employee code format (EMP-MA-00001)
✓ lifecycle_transition: Status transitions create history records
✓ csv_import_validation: CSV import validation catches duplicates

Tests: 4 passed (11 assertions)
Duration: 0.39s
```

### Implemented Features

- Employee CRUD with form validation, authorization policy, soft deletes
- Employee Lifecycle Management (onboarding → probation → confirmed → transferred → exit)
- Employee Code Generation: `EMP-{STATE_CODE}-{SEQUENCE}` via EmployeeObserver
- Document Management (Spatie MediaLibrary): upload, download, delete
- CSV Import with dry-run, validation, duplicate detection, template download
- Audit Logging (Spatie ActivityLog): all mutations, no PII
- Web UI: Bootstrap 5, Alpine.js, DataTables

---

## Phase 2: Attendance & Shift Management (COMPLETE ✅)

### Exit Gate Tests: 3/3 PASSED ✅

```
✓ test_geo_fencing_rejects_out_of_bounds_punch
✓ test_biometric_mock_pushes_to_queue_and_processes
✓ test_ot_calculation_uses_config_values_and_shift_timings

Tests: 3 passed (61 assertions)
Duration: 0.57s
```

### Implemented Features

- Shift CRUD with night shift support (midnight crossover), grace period, LocationScope
- Attendance Tracking: punch IN/OUT, duplicate detection, hours worked, OT, late/early
- Geo-Fencing (Haversine Formula): configurable radius per location, biometric bypass
- Biometric Integration (Mock): `POST /api/v1/integrations/biometric/mock-punch`, Horizon queue
- OT Calculation: 100% config-driven from `config/statutory.php`, all 9 states

---

## Phase 3: Leave Management (COMPLETE ✅)

### Exit Gate Tests: 3/3 PASSED ✅

```
✓ test_state_specific_accrual_calculates_correctly_for_mid_year_joiner
✓ test_holiday_calendar_excludes_state_specific_holidays_from_working_days
✓ test_leave_approval_workflow_updates_balance_and_creates_audit_log

Tests: 3 passed (47 assertions)
Duration: 0.66s
```

### Implemented Features

- 8 leave types: EL, CL, SL, ML, PL, BL, CO, UL — all config-driven per state
- State-Specific Accrual Engine: monthly accrual, pro-rata for mid-year joiners
- Year-end carry-forward capping with `carry_forward_limit` from config
- Comp Off expiry: `expiry_date` on `leave_balances`, auto-zeroed monthly
- Half-day leave: `is_half_day`, `half_day_session` (first_half/second_half)
- Location-Specific Holiday Calendars: national + state-specific, LocationScope
- Approval Workflow: pending → approved/rejected/cancelled; tentative balance management
- 12-Month Balance Projection: in-memory simulation, Chart.js visualization
- Scheduled Accrual: `AccrueLeavesJob` dispatched monthly on 1st at 00:05 IST
- LocationScope enforced on LeaveApplication, LeaveBalance, HolidayCalendar

---

## Phase 4: Payroll & Statutory Compliance (COMPLETE ✅)

### Exit Gate Tests: 9/9 PASSED ✅ (StatutoryEngine Unit Tests)

```
PASS  Tests\Unit\StatutoryEngineTest
✓ it test_pf_calculation_respects_15k_ceiling_and_eps_split
✓ it test_esi_calculation_applies_21k_ceiling_and_disabled_exemption
✓ it test_pt_calculation_applies_correct_state_slab_and_haryana_nil
✓ it test_tds_calculation_new_regime_applies_standard_deduction_and_slabs
✓ it test_tds_calculation_old_regime_applies_investment_deductions
✓ it test_gratuity_calculation_respects_20_lakh_ceiling
✓ it test_bonus_calculation_respects_21k_eligibility_ceiling
✓ it test_lwp_deduction_prorates_basic_and_allowances_correctly
✓ it test_payroll_variance_report_flags_greater_than_5_percent_change

Tests: 9 passed (95 assertions)
Duration: 0.24s
```

### Full Test Suite: 44/44 PASSED ✅

```
Tests: 44 passed (269 assertions)
Duration: 2.03s
```

### Implemented Features

#### 1. Isolated StatutoryEngine (Pure PHP, No Eloquent) ✅

All calculator classes live in `app/Services/StatutoryEngine/` and accept DTOs/arrays, returning calculated values. Zero Eloquent dependencies inside the math logic — trivially unit-testable.

| Calculator | Responsibility |
|---|---|
| `PfCalculator` | PF wage ceiling (₹15,000), employee 12%, employer split (EPS 8.33% / EPF 3.67%), EDLI |
| `EsiCalculator` | ESI wage ceiling (₹21,000; ₹25,000 for disabled), employee 0.75%, employer 3.25% |
| `PtCalculator` | 9-state PT slabs from config; DL/HR = nil, MH = ₹200 max |
| `TdsCalculator` | Old/new regime slabs, standard deduction, investment declarations, monthly projection |
| `GratuityCalculator` | 15-day formula, ₹20 Lakh ceiling, 5-year eligibility threshold |
| `BonusCalculator` | Bonus Act ₹21,000 eligibility ceiling, 8.33%–20% range from config |
| `NetPayCalculator` | Orchestrates all calculators; LWP proration formula |

#### 2. 100% Config-Driven Rules ✅

All statutory ceilings and rates in `config/statutory.php` under the `payroll` key:
- PF wage ceiling: ₹15,000 (with 8.33% EPS split)
- ESI wage ceiling: ₹21,000 (₹25,000 for disabled)
- PT slabs: All 9 states explicitly defined by ISO state code
- Gratuity ceiling: ₹20 Lakh
- Bonus Act ceiling: ₹21,000
- Variance threshold: 5%
- **Zero hardcoded numbers in any calculator class**

#### 3. LWP Proration Formula ✅

```
LWP Deduction = (Component Salary / Total Working Days) × LWP Days
```

Applied per component (Basic, HRA, Special Allowance) separately. Tested in `test_lwp_deduction_prorates_basic_and_allowances_correctly`.

#### 4. PayrollInputDTO ✅

```php
// app/Services/StatutoryEngine/DTOs/PayrollInputDTO.php
public string $employeeId
public string $employeeCode
public string $stateCode          // ISO 3166-2 (MH, KA, DL, HR, UP, GJ, WB, JH, GA)
public float  $grossSalary
public float  $basicSalary
public float  $hra
public float  $specialAllowance
public int    $totalWorkingDays
public float  $lwpDays
public float  $otEarnings
public float  $encashmentDays
public float  $noticePayRecovery
public bool   $optedForPf
public bool   $isEsiEligible
public int    $yearsOfService
public string $taxRegime          // 'old' | 'new'
public array  $investmentDeclarations
public int    $payrollMonth
public int    $payrollYear
```

#### 5. Payroll Variance Report ✅

- `PayrollVarianceService::generateReport()` compares current vs previous month net pay
- Any employee with >5% change is flagged (`variance_flag = true`)
- Finalization is BLOCKED until all variance flags are acknowledged by HR
- `acknowledgeVariance()` method records the acknowledging user ID and timestamp
- Variance report view at `/payroll/reports/variance`

#### 6. Phase 1 & Phase 3 Integration ✅

- **LWP deductions**: `PayrollService` reads approved `UL` (Unpaid Leave) applications from Phase 3 `LeaveApplication` model
- **Leave Encashment**: reads `encashment_days_this_month` from Phase 3 `LeaveBalance` model
- **Notice Pay Recovery**: reads `notice_pay_recovery_amount` from Phase 1 `Employee` lifecycle data

#### 7. Payslip Generation ✅

- `PayslipPdfService` renders `payroll/payslip_pdf.blade.php` via DomPDF
- Payslip shows: employee details, earnings breakdown, deductions breakdown, net pay, employer contributions
- Download endpoint: `GET /payroll/{record}/pdf`
- Screen view: `GET /payroll/{record}` → `payroll/payslip.blade.php`

#### 8. Bank Transfer File (HDFC NEFT Format) ✅

- `BankTransferFileService::generateHdfcCsv()` generates HDFC Salary Upload CSV
- Format: Sr No | Beneficiary Name | Account Number | IFSC Code | Amount | Remarks
- Only includes `finalized` payroll records

#### 9. Statutory Challans ✅

- `StatutoryChallanService::generatePfEcr()` — EPFO ECR format (tilde-delimited)
- `StatutoryChallanService::generateEsiChallan()` — ESIC portal CSV format
- `StatutoryChallanService::generatePtChallanSummary()` — PT summary grouped by state

#### 10. Payroll Workflow ✅

State machine: `draft` → `approved` → `finalized`

1. HR clicks "Process Payroll" → creates `draft` records for all active employees
2. Variance report generated automatically; flagged records require HR acknowledgment
3. HR approves individual records (`draft` → `approved`)
4. HR clicks "Finalize Payroll" (blocked if unacknowledged variances exist)
5. All `approved` records → `finalized`; bank file and challans become downloadable

#### 11. Parallel Run Reconciliation ✅

- `legacy_net_pay` column on `payroll_records` for importing legacy system data
- `reconciliation_variance` = NinjaOS net pay − legacy net pay
- `reconciliation_cleared` flag for HR sign-off
- Reconciliation view at `/payroll/reports/reconciliation`
- Tolerance: ±₹1 (rounding differences acceptable)

### Database Migrations Added (Phase 4)

- `2026_07_19_185817_create_payroll_records_table.php` — Updated with full Phase 4 schema (variance, reconciliation, statutory breakdown columns)
- `2026_07_20_154543_create_payroll_line_items_table.php` — Line items for payslip components

### Key Services

- `app/Services/StatutoryEngine/DTOs/PayrollInputDTO.php` — Data transfer object
- `app/Services/StatutoryEngine/PfCalculator.php` — PF + EPS + EDLI
- `app/Services/StatutoryEngine/EsiCalculator.php` — ESI employee + employer
- `app/Services/StatutoryEngine/PtCalculator.php` — 9-state PT slabs
- `app/Services/StatutoryEngine/TdsCalculator.php` — Old/new regime TDS
- `app/Services/StatutoryEngine/GratuityCalculator.php` — Gratuity with ceiling
- `app/Services/StatutoryEngine/BonusCalculator.php` — Bonus Act
- `app/Services/StatutoryEngine/NetPayCalculator.php` — Orchestrator
- `app/Services/Payroll/PayrollService.php` — Full payroll run orchestration
- `app/Services/Payroll/PayrollVarianceService.php` — Variance report + acknowledgment
- `app/Services/Payroll/PayslipPdfService.php` — Payslip PDF generation
- `app/Services/Payroll/BankTransferFileService.php` — HDFC NEFT CSV
- `app/Services/Payroll/StatutoryChallanService.php` — PF ECR, ESI, PT challans

### Web Routes Added

```
GET    /payroll                                  — Payroll index (list records)
POST   /payroll/process                          — Process payroll (create drafts)
POST   /payroll/finalize                         — Finalize payroll (lock records)
GET    /payroll/reports/variance                 — Variance report
GET    /payroll/reports/reconciliation           — Parallel run reconciliation
GET    /payroll/{record}                         — Payslip screen view
GET    /payroll/{record}/pdf                     — Payslip PDF download
POST   /payroll/{record}/approve                 — Approve a payroll record
POST   /payroll/{record}/acknowledge-variance    — Acknowledge variance flag
```

---

## Full Test Suite Summary

| Phase | Tests | Assertions | Status |
|---|---|---|---|
| Phase 1 (Feature) | 4 | 11 | ✅ |
| Phase 2 (Feature) | 3 | 61 | ✅ |
| Phase 3 (Feature) | 3 | 47 | ✅ |
| Phase 4 (Unit — StatutoryEngine) | 9 | 95 | ✅ |
| Architecture Tests | 25 | 55 | ✅ |
| **Total** | **44** | **269** | **✅** |

---

## Manual Testing Commands

### Run All Tests
```bash
cd /home/ubuntu/nexusos && php artisan test --no-coverage
```

### Run Phase-Specific Tests
```bash
# Phase 1 tests
php artisan test tests/Feature/Phase1ExitGateTest.php --no-coverage

# Phase 2 tests
php artisan test tests/Feature/Phase2AttendanceTest.php --no-coverage

# Phase 3 tests
php artisan test tests/Feature/Phase3LeaveTest.php --no-coverage

# Phase 4 StatutoryEngine unit tests
php artisan test tests/Unit/StatutoryEngineTest.php --no-coverage
```

### Phase 4 Exit Gate Verification Commands
```bash
# Run all 9 Phase 4 mandatory StatutoryEngine unit tests
php artisan test tests/Unit/StatutoryEngineTest.php --no-coverage

# Run individual Phase 4 tests
php artisan test tests/Unit/StatutoryEngineTest.php --filter="test_pf_calculation_respects_15k_ceiling_and_eps_split" --no-coverage
php artisan test tests/Unit/StatutoryEngineTest.php --filter="test_pt_calculation_applies_correct_state_slab_and_haryana_nil" --no-coverage
php artisan test tests/Unit/StatutoryEngineTest.php --filter="test_payroll_variance_report_flags_greater_than_5_percent_change" --no-coverage
php artisan test tests/Unit/StatutoryEngineTest.php --filter="test_lwp_deduction_prorates_basic_and_allowances_correctly" --no-coverage
```

### Leave Accrual Commands
```bash
php artisan leave:accrue
php artisan leave:accrue --location=1
php artisan leave:accrue --year=2026 --month=7
```

### Database Seeding
```bash
php artisan db:seed
php artisan db:seed --class=HolidayCalendarSeeder
php artisan migrate:fresh --seed
```

---

## Key Files

### StatutoryEngine (Phase 4)
- `app/Services/StatutoryEngine/DTOs/PayrollInputDTO.php`
- `app/Services/StatutoryEngine/PfCalculator.php`
- `app/Services/StatutoryEngine/EsiCalculator.php`
- `app/Services/StatutoryEngine/PtCalculator.php`
- `app/Services/StatutoryEngine/TdsCalculator.php`
- `app/Services/StatutoryEngine/GratuityCalculator.php`
- `app/Services/StatutoryEngine/BonusCalculator.php`
- `app/Services/StatutoryEngine/NetPayCalculator.php`
- `app/Services/Payroll/PayrollService.php`
- `app/Services/Payroll/PayrollVarianceService.php`
- `app/Services/Payroll/PayslipPdfService.php`
- `app/Services/Payroll/BankTransferFileService.php`
- `app/Services/Payroll/StatutoryChallanService.php`
- `app/Http/Controllers/PayrollController.php`
- `app/Policies/PayrollPolicy.php`
- `tests/Unit/StatutoryEngineTest.php`

### Leave Management (Phase 3)
- `app/Services/Leave/LeaveAccrualEngine.php`
- `app/Services/Leave/LeaveService.php`
- `app/Services/Leave/LeaveProjectionService.php`
- `app/Jobs/AccrueLeavesJob.php`
- `app/Http/Controllers/LeaveApplicationController.php`
- `app/Http/Controllers/LeaveBalanceController.php`
- `app/Policies/LeaveApplicationPolicy.php`

### Attendance & Shifts (Phase 2)
- `app/Services/Attendance/AttendanceService.php`
- `app/Services/Attendance/GeoFencingService.php`
- `app/Jobs/ProcessBiometricPunch.php`
- `app/Http/Controllers/AttendanceController.php`
- `app/Http/Controllers/ShiftController.php`

### Core HR (Phase 1)
- `app/Models/Employee.php`
- `app/Observers/EmployeeObserver.php`
- `app/Services/EmployeeImportService.php`
- `app/Http/Controllers/EmployeeController.php`
- `app/Http/Controllers/EmployeeLifecycleController.php`

### Configuration
- `config/statutory.php` — All statutory rules: OT, PF, ESI, PT, TDS, Gratuity, Bonus, Leave
- `config/nexusos.php` — Application-level config

# NexusOS Project State

**Current Phase:** Phase 5 - Applicant Tracking System (COMPLETE ✅)

**Last Updated:** 2026-07-20 17:00 GMT+5:30

**Status:** Phase 5 Complete - Ready for Phase 6 (Integrations & Mobile)

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
```

### Implemented Features

- Isolated StatutoryEngine (7 pure PHP calculators, zero Eloquent): PF, ESI, PT, TDS, Gratuity, Bonus, NetPay
- 100% config-driven statutory rules from `config/statutory.php`
- LWP proration formula: `(Component / Total Working Days) × LWP Days`
- PayrollInputDTO with `taxRegime` ('old'|'new') and `investmentDeclarations`
- Payroll Variance Report (>5% change flagged, finalization blocked until acknowledged)
- Phase 1 & 3 integration: LWP deductions, leave encashment, notice pay recovery
- Payslip PDF (DomPDF), Bank Transfer File (HDFC NEFT CSV), Statutory Challans (PF ECR, ESI, PT)
- Parallel Run Reconciliation: `legacy_net_pay` column, tolerance ±₹1

---

## Phase 5: Applicant Tracking System (COMPLETE ✅)

### Exit Gate Tests: 3/3 PASSED ✅

```
PASS  Tests\Feature\Phase5AtsTest
✓ it test_mock_resume_parser_extracts_structured_data_from_pdf
✓ it test_kanban_stage_change_triggers_queued_notification
✓ it test_convert_to_employee_creates_core_hr_record_and_onboarding_tasks

Tests: 3 passed (41 assertions)
Duration: 0.76s
```

### Implemented Features

#### 1. Job Requisition Workflow ✅

State machine: `draft` → `submitted` → `approved` / `rejected` → `open` → `closed`

- Manager raises a hiring request with location, department, designation, and JD
- Multi-level approval: Manager → Location HR → Central HR
- LocationScope enforced: Location HR can only see requisitions for their location
- Published requisitions are visible to recruiters for candidate intake

#### 2. Mock Resume Parser (Sovren/Textkernel Contract) ✅

```json
// POST /mock-parser-api/parse
// Input: PDF or DOCX file
// Output:
{
  "status": "success",
  "data": {
    "first_name":       "string",
    "last_name":        "string",
    "email":            "string",
    "phone":            "string",
    "skills":           ["string"],
    "experience_years": 4.5,
    "education": [
      { "degree": "string", "institution": "string", "year_of_passing": 2018 }
    ]
  }
}
```

- Resume stored via Spatie MediaLibrary (collection: `resumes`) — consistent with Phase 1
- PII (email, phone) is **never written to application logs** (DPDP compliance)
- Only non-PII metadata logged: `candidate_uuid`, `file_name`, `file_size_kb`
- Production: replace `callMockParserApi()` with HTTP POST to Sovren/Textkernel endpoint

#### 3. Kanban Pipeline (Alpine.js) ✅

7 stages: `applied` → `screened` → `interview_1` → `interview_2` → `offer` → `hired` / `rejected`

- Drag-and-drop Kanban board at `/ats/kanban/{requisition}`
- `CandidatePipelineService::moveToStage()` enforces state machine transitions
- `CandidateStageHistory` records every transition with `from_stage`, `to_stage`, `moved_by`, `moved_at`
- `rejection_reason` is **required** when moving to `rejected` stage (stored on both `Candidate` and `CandidateStageHistory`)

#### 4. Automated Communications ✅

- `SendCandidateNotification` job dispatched to queue on every stage change
- Stage-specific templates: interview scheduled, offer extended, rejection (with `rejection_reason` injected)
- Queue-based (non-blocking): `Queue::fake()` verified in tests

#### 5. Convert to Employee (Phase 1 Handoff) ✅

One-click action on "Hired" candidate:

1. `HandoffService::convertToEmployee()` runs in a DB transaction
2. Creates `Employee` record with `status = 'onboarding'`
3. Pre-fills: `first_name`, `last_name`, `email`, `phone`, `date_of_joining`, `location_id`, `department_id`, `designation_id`
4. Phase 1 `EmployeeObserver` auto-generates `employee_code` (`EMP-{STATE_CODE}-{SEQUENCE}`)
5. `EmployeeLifecycleHistory` record created explicitly (HandoffService owns this, not the observer)
6. Candidate updated: `converted_to_employee_id`, `converted_at`
7. Candidate UUID primary key ensures DPDP compliance

#### 6. DPDP Compliance ✅

- `Candidate` model uses UUID primary key (`candidate_id`) — no sequential ID exposure
- PII never logged (email, phone excluded from all log calls)
- `rejection_reason` stored for audit trail

### Database Migrations Added (Phase 5)

- `2026_07_20_161000_create_candidates_table.php` — UUID PK, Spatie MediaLibrary compatible, rejection_reason, converted_to_employee_id
- `2026_07_20_161001_create_candidate_stage_histories_table.php` — full audit trail with rejection_reason

### Key Services

- `app/Services/ATS/ResumeParserService.php` — Mock parser + Spatie MediaLibrary storage
- `app/Services/ATS/RequisitionService.php` — Multi-level approval workflow
- `app/Services/ATS/CandidatePipelineService.php` — Kanban stage management + notification dispatch
- `app/Services/ATS/HandoffService.php` — ATS → Core HR bridge

### Web Routes Added

```
GET    /ats/requisitions                           — Requisitions index
GET    /ats/requisitions/create                    — New requisition form
POST   /ats/requisitions                           — Store requisition
GET    /ats/requisitions/{id}                      — Requisition detail
POST   /ats/requisitions/{id}/submit               — Submit for approval
POST   /ats/requisitions/{id}/approve              — Approve requisition
POST   /ats/requisitions/{id}/reject               — Reject requisition
POST   /ats/requisitions/{id}/candidates           — Add candidate to requisition
GET    /ats/kanban/{id}                            — Kanban board view
GET    /ats/kanban/{id}/data                       — Kanban board JSON data
GET    /ats/candidates/{id}                        — Candidate detail
PATCH  /ats/candidates/{id}/move-stage             — Move Kanban stage
POST   /ats/candidates/{id}/convert                — Convert to Employee
```

---

## Full Test Suite Summary

| Phase | Tests | Assertions | Status |
|---|---|---|---|
| Phase 1 (Feature) | 4 | 11 | ✅ |
| Phase 2 (Feature) | 3 | 61 | ✅ |
| Phase 3 (Feature) | 3 | 47 | ✅ |
| Phase 4 (Unit — StatutoryEngine) | 9 | 95 | ✅ |
| Phase 5 (Feature — ATS) | 3 | 41 | ✅ |
| Architecture Tests | 25 | 55 | ✅ |
| **Total** | **47** | **310** | **✅** |

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

# Phase 5 ATS tests
php artisan test tests/Feature/Phase5AtsTest.php --no-coverage
```

### Phase 5 Exit Gate Verification Commands
```bash
# All 3 mandatory Phase 5 tests
php artisan test tests/Feature/Phase5AtsTest.php --no-coverage

# Individual Phase 5 tests
php artisan test tests/Feature/Phase5AtsTest.php --filter="test_mock_resume_parser_extracts_structured_data_from_pdf" --no-coverage
php artisan test tests/Feature/Phase5AtsTest.php --filter="test_kanban_stage_change_triggers_queued_notification" --no-coverage
php artisan test tests/Feature/Phase5AtsTest.php --filter="test_convert_to_employee_creates_core_hr_record_and_onboarding_tasks" --no-coverage
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

## Key Files by Phase

### ATS (Phase 5)
- `app/Models/JobRequisition.php`
- `app/Models/Candidate.php`
- `app/Models/CandidateStageHistory.php`
- `app/Services/ATS/ResumeParserService.php`
- `app/Services/ATS/RequisitionService.php`
- `app/Services/ATS/CandidatePipelineService.php`
- `app/Services/ATS/HandoffService.php`
- `app/Jobs/SendCandidateNotification.php`
- `app/Events/CandidateStageChanged.php`
- `app/Http/Controllers/JobRequisitionController.php`
- `app/Http/Controllers/CandidateController.php`
- `app/Http/Controllers/KanbanBoardController.php`
- `resources/views/ats/requisitions/index.blade.php`
- `resources/views/ats/kanban/board.blade.php`
- `resources/views/ats/candidates/detail.blade.php`
- `tests/Feature/Phase5AtsTest.php`

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
- `tests/Unit/StatutoryEngineTest.php`

### Leave Management (Phase 3)
- `app/Services/Leave/LeaveAccrualEngine.php`
- `app/Services/Leave/LeaveService.php`
- `app/Services/Leave/LeaveProjectionService.php`
- `app/Jobs/AccrueLeavesJob.php`
- `app/Http/Controllers/LeaveApplicationController.php`
- `app/Http/Controllers/LeaveBalanceController.php`
- `app/Policies/LeaveApplicationPolicy.php`
- `tests/Feature/Phase3LeaveTest.php`

### Attendance & Shifts (Phase 2)
- `app/Services/Attendance/AttendanceService.php`
- `app/Services/Attendance/GeoFencingService.php`
- `app/Jobs/ProcessBiometricPunch.php`
- `app/Http/Controllers/AttendanceController.php`
- `app/Http/Controllers/ShiftController.php`
- `tests/Feature/Phase2AttendanceTest.php`

### Core HR (Phase 1)
- `app/Models/Employee.php`
- `app/Observers/EmployeeObserver.php`
- `app/Http/Controllers/EmployeeController.php`
- `app/Http/Controllers/EmployeeLifecycleController.php`
- `app/Http/Controllers/EmployeeDocumentController.php`
- `app/Http/Controllers/EmployeeImportController.php`
- `app/Policies/EmployeePolicy.php`
- `tests/Feature/Phase1ExitGateTest.php`

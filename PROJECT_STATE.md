# NexusOS Project State

**Current Phase:** Phase 6 - Integrations & Mobile (COMPLETE ✅)

**Last Updated:** 2026-07-20 17:30 GMT+5:30

**Status:** ALL 6 PHASES COMPLETE — Production-Ready

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
```

### Implemented Features

- Job Requisition Workflow: draft → submitted → approved → open → closed
- Multi-level approval: Manager → Location HR → Central HR
- Mock Resume Parser (Sovren/Textkernel contract): PDF/DOCX → structured JSON, PII-safe logging
- Kanban Pipeline (Alpine.js drag-and-drop): 7 stages, rejection_reason required
- Automated Communications: queued `SendCandidateNotification` job per stage change
- Convert to Employee: one-click handoff to Core HR with EmployeeLifecycleHistory
- Candidate UUID primary key (DPDP compliance)

---

## Phase 6: Integrations & Mobile (COMPLETE ✅)

### Exit Gate Tests: 3/3 PASSED ✅

```
PASS  Tests\Feature\Phase6IntegrationsTest
✓ it test_hdfc_bank_file_generation_formats_correctly
✓ it test_whatsapp_mock_job_logs_payload_and_queues_successfully
✓ it test_mobile_attendance_api_rejects_missing_geo_coordinates

Tests: 3 passed (20 assertions)
```

### Implemented Features

#### 1. Mock External Integrations (Interface-Driven) ✅

All mock services implement a production-ready interface. Swapping to real APIs requires only a binding change in `AppServiceProvider`.

**WhatsApp Business API Mock**
- Interface: `app/Services/Integrations/WhatsApp/WhatsAppServiceInterface.php`
- Mock: `app/Services/Integrations/WhatsApp/MockWhatsAppService.php`
- Job: `app/Jobs/SendWhatsAppMessageJob.php` (queued, logs to `storage/logs/whatsapp-mock.log`)
- Log channel: `whatsapp` (daily rotation, 30-day retention)

**Tally/Zoho Accounting Mock**
- Interface: `app/Services/Integrations/Accounting/AccountingIntegrationInterface.php`
- Mock: `app/Services/Integrations/Accounting/MockAccountingService.php`
- Job: `app/Jobs/SyncPayrollToAccountingJob.php` (queued, logs JSON journal entry to `storage/logs/accounting-mock.log`)
- Log channel: `accounting` (daily rotation, 30-day retention)

**HDFC Bank Transfer File (8-column NEFT format)**
- Service: `app/Services/Payroll/BankTransferFileService.php`
- CSV columns: `Transaction Type | Debit Account No | Beneficiary Account No | Beneficiary Name | Amount | Beneficiary IFSC | Value Date | Customer Reference No`
- Output: `storage/app/bank-files/HDFC-SAL-{MONTH}-{YEAR}-{timestamp}.csv`

#### 2. API Resources (PII-Safe, Consistent Shaping) ✅

- `EmployeeResource` — excludes Aadhaar, PAN, bank details from list responses
- `LeaveBalanceResource` — real-time balance (opening + accrued - availed = closing)
- `PayslipResource` — returns signed URL (15-min TTL) instead of base64 PDF data
- `AttendanceResource` — daily punch record with working hours

#### 3. Security & Performance ✅

- Rate Limiting: `throttle:60,1` on all public/auth API routes
- API Resources: consistent JSON shaping, no accidental PII leakage
- Spatie Backup: daily database backup + `storage/app` to local disk (simulating S3)
- Backup schedule: daily at 01:00 IST, weekly cleanup (keep 4 weeks)

#### 4. Mobile API Routes ✅

All routes under `/api/v1/` with `auth:sanctum` middleware:

```
POST   /api/v1/auth/login                    — Sanctum token issue
POST   /api/v1/auth/logout                   — Token revocation
GET    /api/v1/auth/me                       — Authenticated user profile
POST   /api/v1/attendance/punch              — Mobile punch with geo-fencing
GET    /api/v1/attendance                    — Attendance records
GET    /api/v1/leave/balances                — Real-time leave balances
POST   /api/v1/leave/apply                   — Submit leave application
GET    /api/v1/leave                         — Leave history
GET    /api/v1/payroll/payslips              — Payslip list with signed URLs
```

#### 5. React Native Mobile App (Expo) ✅

Location: `/home/ubuntu/mobile/` (sibling to `nexusos/`)

```
mobile/
├── App.tsx                        # Entry point
├── src/
│   ├── navigation/AppNavigator.tsx  # Auth guard + Bottom Tab Navigator
│   ├── screens/
│   │   ├── LoginScreen.tsx          # Sanctum login, expo-secure-store
│   │   ├── AttendanceScreen.tsx     # Punch IN/OUT, geo-fencing UI
│   │   ├── LeaveScreen.tsx          # Balance display + application form
│   │   └── PayslipScreen.tsx        # Payslip list + signed URL PDF viewer
│   ├── services/
│   │   ├── api.ts                   # Axios + Sanctum interceptors
│   │   ├── authService.ts           # Login/logout/profile
│   │   ├── attendanceService.ts     # Punch + GPS
│   │   ├── leaveService.ts          # Balances + applications
│   │   └── payslipService.ts        # Signed URL PDF open
│   └── store/authStore.ts           # Zustand global auth state
└── README.md                        # Full API contract documentation
```

**Security:**
- Sanctum tokens stored in `expo-secure-store` (iOS Keychain / Android Keystore, AES-256)
- 401 interceptor clears token and triggers nav guard to Login
- Payslip PDF: signed URL (15-min TTL) — no PDF data in app state
- Email/password never logged

### Key Bugs Fixed in Phase 6

| Bug | Root Cause | Fix |
|---|---|---|
| `locations.state` NOT NULL | Test created Location without `state` (full name) field | Added `'state' => 'Maharashtra'` etc. to all test Location creates |
| Geo-fencing check always passes | Controller checked `$location->radius_meters` but column is `attendance_radius_meters` | Fixed column reference in `AttendanceController::punch()` |
| `PayrollRecord` LocationScope import | Wrong namespace `App\Traits\LocationScope` | Fixed to `App\Models\Scopes\LocationScope` |

---

## Full Test Suite Summary

| Phase | Tests | Assertions | Status |
|---|---|---|---|
| Phase 1 (Feature) | 4 | 11 | ✅ |
| Phase 2 (Feature) | 3 | 61 | ✅ |
| Phase 3 (Feature) | 3 | 47 | ✅ |
| Phase 4 (Unit — StatutoryEngine) | 9 | 95 | ✅ |
| Phase 5 (Feature — ATS) | 3 | 41 | ✅ |
| Phase 6 (Feature — Integrations) | 3 | 20 | ✅ |
| Architecture Tests | 25 | 55 | ✅ |
| **Total** | **50** | **330** | **✅** |

---

## Exit Gate Verification Commands

### Run All Tests
```bash
cd /home/ubuntu/nexusos && php artisan test --no-coverage
```

### Run Phase-Specific Tests
```bash
# Phase 1
php artisan test tests/Feature/Phase1ExitGateTest.php --no-coverage

# Phase 2
php artisan test tests/Feature/Phase2AttendanceTest.php --no-coverage

# Phase 3
php artisan test tests/Feature/Phase3LeaveTest.php --no-coverage

# Phase 4 (StatutoryEngine unit tests)
php artisan test tests/Unit/StatutoryEngineTest.php --no-coverage

# Phase 5 (ATS)
php artisan test tests/Feature/Phase5AtsTest.php --no-coverage

# Phase 6 (Integrations)
php artisan test tests/Feature/Phase6IntegrationsTest.php --no-coverage
```

### Phase 6 Individual Exit Gate Commands
```bash
php artisan test tests/Feature/Phase6IntegrationsTest.php --filter="test_hdfc_bank_file_generation_formats_correctly" --no-coverage
php artisan test tests/Feature/Phase6IntegrationsTest.php --filter="test_whatsapp_mock_job_logs_payload_and_queues_successfully" --no-coverage
php artisan test tests/Feature/Phase6IntegrationsTest.php --filter="test_mobile_attendance_api_rejects_missing_geo_coordinates" --no-coverage
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

### Integrations & Mobile (Phase 6)
- `app/Services/Integrations/WhatsApp/WhatsAppServiceInterface.php`
- `app/Services/Integrations/WhatsApp/MockWhatsAppService.php`
- `app/Services/Integrations/Accounting/AccountingIntegrationInterface.php`
- `app/Services/Integrations/Accounting/MockAccountingService.php`
- `app/Jobs/SendWhatsAppMessageJob.php`
- `app/Jobs/SyncPayrollToAccountingJob.php`
- `app/Http/Resources/EmployeeResource.php`
- `app/Http/Resources/LeaveBalanceResource.php`
- `app/Http/Resources/PayslipResource.php`
- `app/Http/Resources/AttendanceResource.php`
- `config/backup.php` (Spatie Backup)
- `tests/Feature/Phase6IntegrationsTest.php`
- `mobile/` (React Native Expo app — sibling directory)

### ATS (Phase 5)
- `app/Models/JobRequisition.php`
- `app/Models/Candidate.php`
- `app/Models/CandidateStageHistory.php`
- `app/Services/ATS/ResumeParserService.php`
- `app/Services/ATS/RequisitionService.php`
- `app/Services/ATS/CandidatePipelineService.php`
- `app/Services/ATS/HandoffService.php`
- `app/Jobs/SendCandidateNotification.php`
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
- `app/Services/Payroll/BankTransferFileService.php`
- `tests/Unit/StatutoryEngineTest.php`

### Leave Management (Phase 3)
- `app/Services/Leave/LeaveAccrualEngine.php`
- `app/Services/Leave/LeaveService.php`
- `app/Services/Leave/LeaveProjectionService.php`
- `app/Jobs/AccrueLeavesJob.php`
- `tests/Feature/Phase3LeaveTest.php`

### Attendance & Shifts (Phase 2)
- `app/Services/Attendance/AttendanceService.php`
- `app/Services/Attendance/GeoFencingService.php`
- `app/Jobs/ProcessBiometricPunch.php`
- `app/Http/Controllers/AttendanceController.php`
- `tests/Feature/Phase2AttendanceTest.php`

### Core HR (Phase 1)
- `app/Models/Employee.php`
- `app/Observers/EmployeeObserver.php`
- `app/Http/Controllers/EmployeeController.php`
- `app/Policies/EmployeePolicy.php`
- `tests/Feature/Phase1ExitGateTest.php`

# NexusOS Project State

**Current Phase:** Phase 3 - Leave Management (COMPLETE ✅)

**Last Updated:** 2026-07-20 14:30 GMT+5:30

**Status:** Phase 3 Complete - Ready for Phase 4 (Payroll & Statutory Compliance)

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

#### 1. Employee CRUD ✅
- Create, read, update, delete employees
- Form validation (email unique per location, phone format, Aadhaar/PAN)
- Authorization policy (role-based access control)
- Soft deletes for audit trail

#### 2. Employee Lifecycle Management ✅
- Status transitions: onboarding → probation → confirmed → transferred → exit
- Lifecycle history tracking (previous_status, new_status, reason, changed_by)
- Probation period management
- Confirmation date tracking

#### 3. Employee Code Generation ✅
- Auto-generated: `EMP-{STATE_CODE}-{SEQUENCE}`
- Format: EMP-MA-00001 (Maharashtra, sequence 1)
- Implemented via EmployeeObserver

#### 4. Document Management (Spatie MediaLibrary) ✅
- Upload documents, certificates, identification
- Collections: documents, certificates, identification
- Download and delete documents
- File type validation (PDF, JPG, PNG)

#### 5. CSV Import ✅
- Bulk employee import via CSV
- Validation with detailed error reporting
- Dry-run mode for preview
- Department/designation resolution by code
- Duplicate email detection per location
- CSV template download

#### 6. Audit Logging (Spatie ActivityLog) ✅
- Automatic logging of all employee mutations
- No PII in logs (only IDs, status changes, dates)
- Audit trail visible in employee detail view

#### 7. Web UI ✅
- Bootstrap 5 responsive layout
- Employee list with DataTables (filterable, searchable, paginated)
- Employee form (create/edit)
- Employee detail view with lifecycle history and documents
- CSV import form with template download
- Status transition modal

#### 8. Frontend Stack ✅
- Bootstrap 5.3
- Alpine.js 3.x
- DataTables
- jQuery 3.7
- Vite asset bundling

---

## Phase 2: Attendance & Shift Management (COMPLETE ✅)

### Exit Gate Tests: 3/3 PASSED ✅

```
✓ test_geo_fencing_rejects_out_of_bounds_punch (61 assertions total across 3 tests)
✓ test_biometric_mock_pushes_to_queue_and_processes
✓ test_ot_calculation_uses_config_values_and_shift_timings

Tests: 3 passed (61 assertions)
Duration: 0.57s
```

### Implemented Features

#### 1. Shift Management ✅
- Shift CRUD (create, read, update, delete)
- Night shift support (`is_night_shift` flag — handles midnight crossover)
- Grace period per shift (`grace_period_minutes`)
- Shift code for biometric device mapping
- LocationScope enforced on Shift model
- Blade views: shifts/index, shifts/form

#### 2. Attendance Tracking ✅
- Punch IN / OUT recording
- Duplicate punch detection (same type within 5 minutes)
- Hours worked calculation (handles night-shift crossover)
- OT calculation from `config/statutory.php` (state-specific thresholds)
- Late arrival / early departure detection
- Status auto-set: present, absent, half_day, on_leave
- LocationScope enforced on Attendance model
- Blade view: attendance/index

#### 3. Geo-Fencing (Haversine Formula) ✅
- `GeoFencingService` with Haversine distance calculation
- Configurable radius per location (`attendance_radius_meters`)
- GPS-sourced punches validated against location boundary
- Biometric punches bypass geo-fencing
- Detailed rejection messages with distance info

#### 4. Biometric Integration (Mock) ✅
- `POST /api/v1/integrations/biometric/mock-punch` endpoint
- Sanctum-authenticated (location_hr role required)
- Dispatches `ProcessBiometricPunch` job to `biometric` Horizon queue
- Job handles employee lookup by code, attendance creation
- Returns 202 Accepted with job metadata

#### 5. Laravel Horizon (Queue Management) ✅
- Horizon installed and configured
- `biometric` queue with dedicated worker
- `ProcessBiometricPunch` job on biometric queue
- Dashboard at `/horizon`

#### 6. OT Calculation (Config-Driven) ✅
- All OT thresholds from `config/statutory.php`
- State-specific `ot_applicable_after_hours` per state
- `AttendanceService::calculateOtHours()` public method
- No hardcoded OT values anywhere in business logic
- Supports all 9 states: MH, KA, DL, HR, UP, GJ, WB, JH, GA

### Database Migrations Added (Phase 2)

- `2026_07_20_071755_add_attendance_radius_to_locations_table.php` — `attendance_radius_meters` on locations
- `2026_07_20_073324_add_state_code_to_locations_table.php` — `state_code` (ISO 3166-2) on locations
- `2026_07_20_073553_add_code_to_locations_table.php` — `code` (short location code) on locations
- `2026_07_20_XXXXXX_add_phase2_columns_to_shifts_table.php` — `is_night_shift`, `grace_period_minutes` on shifts
- `2026_07_20_XXXXXX_add_phase2_columns_to_attendance_table.php` — 8 new columns on attendance

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

### Full Test Suite: 35/35 PASSED ✅

```
Tests: 35 passed (174 assertions)
Duration: 2.08s
```

### Implemented Features

#### 1. Leave Types ✅
- 8 leave types: EL (Earned Leave), CL (Casual Leave), SL (Sick Leave), ML (Maternity Leave),
  PL (Paternity Leave), BL (Bereavement Leave), CO (Compensatory Off), UL (Unpaid Leave)
- All types configured in `config/statutory.php` per state
- LocationScope enforced on LeaveApplication and LeaveBalance models

#### 2. State-Specific Accrual Engine ✅
- `App\Services\Leave\LeaveAccrualEngine` — all rules from `config/statutory.php`
- Monthly accrual frequency with pro-rata calculation for mid-year joiners
- Year-end carry-forward capping with configurable `carry_forward_limit` per leave type per state
- Excess handling: `lapse` or `encash` (per config)
- Comp Off expiry: `expiry_date` on `leave_balances`, auto-zeroed by `expireCompOffBalances()`
- No hardcoded accrual values anywhere in business logic

#### 3. Location-Specific Holiday Calendars ✅
- `HolidayCalendar` model with `type` (national/state) and LocationScope
- `HolidayCalendarSeeder` seeds national + state-specific holidays for all 9 states (2026)
- `LeaveService::countWorkingDays()` excludes weekends AND location holidays
- Half-day leave returns `0.5` working days

#### 4. Approval Workflow ✅
- State machine: `pending_approval` → `approved` / `rejected` / `cancelled`
- Tentative balance deduction on application (pending bucket)
- On approval: pending → availed
- On rejection: pending → restored to available (closing_balance)
- On cancellation: pending/availed → restored
- Spatie ActivityLog records every approval/rejection with causer and comments
- `LeaveApplicationPolicy` enforces LocationScope: Location HR can only act on their location

#### 5. Balance Visibility & 12-Month Projection ✅
- Real-time balance: Opening + Accrued − Availed = Closing (live from DB)
- Pending bucket shows tentative deductions
- `App\Services\Leave\LeaveProjectionService` — in-memory 12-month forward simulation
- Projection accounts for future approved leaves and monthly accrual
- Chart.js visualization on `/leave/balances`
- JSON API endpoint: `GET /leave/balances/projection`

#### 6. Half-Day Leave Support ✅
- `is_half_day` boolean and `half_day_session` enum (`first_half`/`second_half`) on `leave_applications`
- `countWorkingDays()` returns `0.5` for half-day
- Form validation: half-day requires `from_date === to_date`

#### 7. Scheduled Accrual Job ✅
- `App\Jobs\AccrueLeavesJob` — dispatched monthly on 1st at 00:05 IST
- Registered in `routes/console.php` via Laravel Scheduler
- `leave:accrue` Artisan command for manual/backfill runs
- Comp Off expiry runs as part of the same monthly job

### Database Migrations Added (Phase 3)

- `2026_07_20_141504_create_leave_balances_table.php` — `leave_balances` with composite index `(employee_id, leave_type, year)`
- `2026_07_20_141505_add_phase3_columns_to_leave_applications_table.php` — half-day columns, rejection/cancellation tracking, composite indexes

### Key Services

- `App\Services\Leave\LeaveAccrualEngine` — config-driven accrual, carry-forward, Comp Off expiry
- `App\Services\Leave\LeaveService` — apply, approve, reject, cancel; working days calculation
- `App\Services\Leave\LeaveProjectionService` — 12-month forward balance projection

### Key Jobs

- `App\Jobs\AccrueLeavesJob` — Monthly leave accrual for all active locations (Scheduler: 1st of month)

### Key Controllers

- `App\Http\Controllers\LeaveApplicationController` — Apply, approve, reject, cancel
- `App\Http\Controllers\LeaveBalanceController` — Balance view + projection API

### Key Policies

- `App\Policies\LeaveApplicationPolicy` — LocationScope enforcement for approve/reject/cancel

### Web Routes Added

```
GET    /leave                           — Employee: list own applications
GET    /leave/apply                     — Employee: application form
POST   /leave                           — Employee: submit application
PATCH  /leave/{id}/cancel               — Employee/HR: cancel
GET    /leave/approvals                 — Manager/HR: pending approvals
PATCH  /leave/{id}/approve              — Manager/HR: approve
PATCH  /leave/{id}/reject               — Manager/HR: reject
GET    /leave/balances                  — Employee: balance + projection view
GET    /leave/balances/projection       — API: JSON projection for Chart.js
```

### Multi-Tenancy Verification

- [x] LocationScope applied to LeaveApplication model
- [x] LocationScope applied to LeaveBalance model
- [x] LocationScope applied to HolidayCalendar model
- [x] LeaveApplicationPolicy enforces location isolation for approve/reject/cancel
- [x] Location HR can ONLY approve regularization for their own location

---

## Database Schema

- [x] employees (with soft deletes, employee_code, lifecycle fields)
- [x] employee_lifecycle_history (tracking status transitions)
- [x] departments (with parent_id for hierarchy)
- [x] designations
- [x] locations (+ attendance_radius_meters, state_code, code)
- [x] shifts (+ is_night_shift, grace_period_minutes)
- [x] attendance (+ punch_source, device_id, ot_hours, is_late, is_early_departure, geo_lat, geo_lng, geo_distance_metres, shift_id)
- [x] leave_applications (+ is_half_day, half_day_session, rejected_by, rejected_at, cancelled_at; composite indexes)
- [x] leave_balances (opening_balance, accrued, availed, pending, closing_balance, expiry_date; composite index)
- [x] holiday_calendars (national + state-specific, LocationScope)
- [x] users
- [x] activity_log (Spatie ActivityLog)
- [x] roles, permissions (Spatie Permission)
- [x] personal_access_tokens (Sanctum)

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
```

### Phase 3 Exit Gate Verification Commands
```bash
# Run all 3 Phase 3 mandatory tests
php artisan test tests/Feature/Phase3LeaveTest.php --no-coverage

# Run specific Phase 3 tests
php artisan test tests/Feature/Phase3LeaveTest.php --filter="test_state_specific_accrual_calculates_correctly_for_mid_year_joiner" --no-coverage
php artisan test tests/Feature/Phase3LeaveTest.php --filter="test_holiday_calendar_excludes_state_specific_holidays_from_working_days" --no-coverage
php artisan test tests/Feature/Phase3LeaveTest.php --filter="test_leave_approval_workflow_updates_balance_and_creates_audit_log" --no-coverage
```

### Leave Accrual Commands
```bash
# Run monthly accrual for all locations (dispatches job)
php artisan leave:accrue

# Run for specific location
php artisan leave:accrue --location=1

# Run for specific month/year (backfill)
php artisan leave:accrue --year=2026 --month=7
```

### Database Seeding
```bash
# Seed roles, permissions, and locations
php artisan db:seed

# Seed holiday calendars
php artisan db:seed --class=HolidayCalendarSeeder

# Seed with fresh database
php artisan migrate:fresh --seed
```

---

## Key Files

### Core Implementation
- `/app/Models/Employee.php` — Employee model with MediaLibrary, ActivityLog, SoftDeletes, LocationScope
- `/app/Models/Attendance.php` — Attendance model with LocationScope, SoftDeletes
- `/app/Models/Shift.php` — Shift model with LocationScope, SoftDeletes
- `/app/Models/LeaveApplication.php` — Leave application with state machine, half-day, LocationScope
- `/app/Models/LeaveBalance.php` — Leave balance with balance helpers, expiry_date, LocationScope
- `/app/Models/HolidayCalendar.php` — Holiday calendar with LocationScope
- `/app/Models/Location.php` — Location model (code, state_code, attendance_radius_meters)
- `/app/Services/Attendance/AttendanceService.php` — Core attendance logic
- `/app/Services/Attendance/GeoFencingService.php` — Haversine geo-fencing
- `/app/Services/Leave/LeaveAccrualEngine.php` — Config-driven accrual, carry-forward, Comp Off expiry
- `/app/Services/Leave/LeaveService.php` — Apply, approve, reject, cancel; working days calculation
- `/app/Services/Leave/LeaveProjectionService.php` — 12-month forward balance projection
- `/app/Jobs/ProcessBiometricPunch.php` — Biometric queue job
- `/app/Jobs/AccrueLeavesJob.php` — Monthly leave accrual job

### Controllers
- `/app/Http/Controllers/AttendanceController.php` — Attendance CRUD
- `/app/Http/Controllers/ShiftController.php` — Shift CRUD
- `/app/Http/Controllers/Api/BiometricMockController.php` — Mock biometric endpoint
- `/app/Http/Controllers/LeaveApplicationController.php` — Leave apply/approve/reject/cancel
- `/app/Http/Controllers/LeaveBalanceController.php` — Balance view + projection API

### Policies
- `/app/Policies/LeaveApplicationPolicy.php` — LocationScope enforcement

### Views
- `/resources/views/attendance/index.blade.php` — Attendance list
- `/resources/views/shifts/index.blade.php` — Shift list
- `/resources/views/shifts/form.blade.php` — Create/edit shift
- `/resources/views/leave/index.blade.php` — Employee leave applications
- `/resources/views/leave/form.blade.php` — Apply for leave (with half-day support)
- `/resources/views/leave/approvals.blade.php` — Manager/HR approval dashboard
- `/resources/views/leave/balances.blade.php` — Balance view + 12-month Chart.js projection

### Tests
- `/tests/Feature/Phase1ExitGateTest.php` — 4 Phase 1 exit gate tests
- `/tests/Feature/Phase2AttendanceTest.php` — 3 Phase 2 exit gate tests
- `/tests/Feature/Phase3LeaveTest.php` — 3 Phase 3 exit gate tests

### Configuration
- `/config/statutory.php` — All statutory values for 9 states (OT config + leave accrual rules)
- `/config/horizon.php` — Horizon queue configuration
- `/bootstrap/app.php` — Middleware registration
- `/routes/console.php` — Laravel Scheduler (monthly leave accrual)

### Seeders
- `/database/seeders/HolidayCalendarSeeder.php` — National + state-specific holidays for all 9 states (2026)

---

## Known Limitations & Future Improvements

1. **Shift Assignment**: Employees are not yet assigned to specific shifts; shift lookup is by location
2. **Attendance Reports**: No attendance summary/analytics views yet
3. **Leave-Attendance Integration**: Attendance status does not yet auto-update from approved leave
4. **Biometric Device Auth**: Mock endpoint uses Sanctum; real integration would use device-specific API keys
5. **Horizon Supervisor**: Production Horizon supervisor config needs tuning for load
6. **Leave Encashment Processing**: Encashment flagging at year-end is implemented; actual payroll debit handled in Phase 4

---

## Next Phase: Phase 4 - Payroll & Statutory Compliance

### Planned Features
1. Payroll computation (gross, deductions, net pay)
2. PF / ESI / PT calculations from config/statutory.php
3. Leave balance integration (unpaid leave deductions, encashment payouts)
4. Salary slip generation (PDF)
5. Payroll approval workflow
6. Statutory filing reports (Form 16, PF challan, ESI challan)

---

## Deployment Notes

- All migrations are reversible
- Soft deletes preserve data for audit trail
- ActivityLog is immutable (append-only)
- Statutory config is environment-specific
- No hardcoded values in business logic
- UUIDs for public-facing identifiers
- LocationScope enforced at application layer

---

**Status:** Phase 3 Complete ✅ - Ready for Phase 4 (Payroll & Statutory Compliance)

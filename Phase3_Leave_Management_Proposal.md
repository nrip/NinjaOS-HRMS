# Phase 3: Leave Management — Implementation Proposal

Based on the master orchestrator, SRS v2.0, and your specific mandates, here is the technical approach and file list for Phase 3 (Leave Management). 

This phase is critical because its output directly feeds into the Phase 4 Payroll Engine (unpaid leave deductions, encashment, etc.).

## 1. Technical Approach

### 1.1 State-Specific Accrual Engine (Config-Driven)
The accrual logic will be entirely driven by `config/statutory.php`. We will expand the existing `leave` array to define state-specific accrual rules, limits, and encashment policies for all 9 states (including Haryana).
- **Job Structure:** A scheduled Laravel Job (`AccrueLeavesJob`) will run daily via Laravel Scheduler. It will identify employees eligible for accrual (e.g., end of month, or 1 day per 20 working days) and calculate their accrual.
- **Mid-Year Joiners:** The engine will prorate accruals based on the `date_of_joining` and the location's specific accrual frequency.
- **Data Model:** A new `LeaveBalance` model will track opening balance, accrued, availed, and closing balance per employee per leave type per year.

### 1.2 Location-Specific Holiday Calendars
- The `HolidayCalendar` model already exists and uses `LocationScope`.
- We will seed national holidays (applicable to all locations) and state-specific holidays (e.g., Haryana Day, Kannada Rajyotsava) based on Appendix A of the SRS.
- **Working Days Calculation:** A `LeaveService` will calculate actual working days between `from_date` and `to_date`, automatically excluding weekends (based on shift) and any holidays defined in the employee's `HolidayCalendar`.

### 1.3 Approval Workflows & Balance Management
- **State Machine:** Draft → Pending Approval → Approved / Rejected / Cancelled.
- **Balance Deductions:** 
  - On `Pending Approval`: Deduct from "available" balance and add to "tentative/pending" balance to prevent double-booking.
  - On `Approved`: Move from tentative to availed.
  - On `Rejected/Cancelled`: Restore from tentative back to available.
- **Notifications:** Laravel Events (`LeaveApplied`, `LeaveApproved`, `LeaveRejected`) will trigger queued notification jobs.

### 1.4 Balance Visibility & Projection
- **Projection Logic:** A `LeaveProjectionService` will calculate a 12-month forward projection. It will simulate future accruals (based on the config rules) minus any already approved future leaves.
- **UI:** The employee dashboard will show real-time balances and a visual projection chart (using Chart.js via Alpine).

## 2. File List (To Be Created / Modified)

### Database Migrations
- `2026_07_20_XXXXXX_create_leave_balances_table.php` (employee_id, leave_type, year, opening, accrued, availed, pending, closing)
- `2026_07_20_XXXXXX_add_state_specific_leave_config.php` (Seeder update for config/statutory.php to include all 9 states)

### Models & Scopes
- `app/Models/LeaveBalance.php` (with `LocationScope`)
- `app/Models/LeaveApplication.php` (already has `LocationScope`, will add state machine methods)
- `app/Models/HolidayCalendar.php` (already exists, will ensure comprehensive seeding)

### Services & Jobs
- `app/Services/Leave/LeaveService.php` (working days calculation, holiday exclusion)
- `app/Services/Leave/LeaveAccrualEngine.php` (config-driven accrual logic)
- `app/Services/Leave/LeaveProjectionService.php` (12-month forward projection)
- `app/Jobs/AccrueLeavesJob.php` (Scheduled daily job)
- `app/Console/Commands/RunLeaveAccruals.php` (Artisan command to trigger job)

### Controllers & Requests
- `app/Http/Controllers/LeaveApplicationController.php`
- `app/Http/Controllers/LeaveBalanceController.php` (for projection API)
- `app/Http/Requests/StoreLeaveApplicationRequest.php`
- `app/Http/Requests/ApproveLeaveRequest.php`

### Policies
- `app/Policies/LeaveApplicationPolicy.php` (Manager / Location HR approval logic)

### Tests (Pest)
- `tests/Feature/Phase3LeaveTest.php` (Will include the 3 mandated tests: mid-year joiner accrual, holiday exclusion, and approval workflow balance audit).

### Views
- `resources/views/leave/index.blade.php` (Employee view: balances, projection, history)
- `resources/views/leave/approvals.blade.php` (Manager/HR view: pending requests)
- `resources/views/leave/form.blade.php` (Application form)

## 3. Next Steps
Once you approve this approach and file list, I will begin implementation by writing the configuration and the 3 mandated Pest tests FIRST, followed by the models, services, and UI.

Please confirm if this aligns with your expectations or if any adjustments are needed.

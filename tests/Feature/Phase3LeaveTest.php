<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\HolidayCalendar;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\Location;
use App\Models\User;
use App\Services\Leave\LeaveAccrualEngine;
use App\Services\Leave\LeaveService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Test 1: State-specific accrual calculates correctly for a mid-year joiner
//
// Scenario: Employee joins Karnataka on 2026-07-01 (month 7 of the year).
// KA EL accrual rate = 1.5 days/month.
// Months remaining in year from July = 6 (Jul, Aug, Sep, Oct, Nov, Dec).
// Expected EL accrual for the year = 6 * 1.5 = 9.0 days.
// ─────────────────────────────────────────────────────────────────────────────
it('test_state_specific_accrual_calculates_correctly_for_mid_year_joiner', function () {
    $engine = app(LeaveAccrualEngine::class);

    // ── Part A: Verify KA config is loaded correctly ──────────────────────────
    $kaConfig = config('statutory.leave.KA');
    expect($kaConfig)->not->toBeNull('KA leave config must exist in statutory.php')
        ->and($kaConfig['leave_types'])->toHaveKey('EL')
        ->and($kaConfig['leave_types'])->toHaveKey('CL')
        ->and($kaConfig['leave_types'])->toHaveKey('CO');

    $kaElConfig = $kaConfig['leave_types']['EL'];
    expect($kaElConfig['accrual_rate_per_month'])->toBe(1.5)
        ->and($kaElConfig['carry_forward_limit'])->toBe(30)
        ->and($kaElConfig['encashment_allowed'])->toBeTrue();

    // Comp Off must have expiry_days set (45 days)
    $kaCoConfig = $kaConfig['leave_types']['CO'];
    expect($kaCoConfig['expiry_days'])->toBe(45);

    // ── Part B: Mid-year joiner accrual calculation ───────────────────────────
    $location = Location::withoutGlobalScopes()->create([
        'name'       => 'Bengaluru Tech Park',
        'code'       => 'BLR-02',
        'address'    => 'Outer Ring Road, Bengaluru',
        'city'       => 'Bengaluru',
        'state'      => 'Karnataka',
        'state_code' => 'KA',
        'pin_code'   => '560103',
        'gis_lat'    => 12.9352,
        'gis_lng'    => 77.6245,
        'is_active'  => true,
    ]);

    // Employee joins on 2026-07-01 — mid-year joiner
    $employee = Employee::withoutGlobalScopes()->create([
        'employee_code'   => 'EMP-KA-10001',
        'first_name'      => 'Priya',
        'last_name'       => 'Sharma',
        'email'           => 'priya.sharma@nexusos.test',
        'phone'           => '9100000001',
        'date_of_birth'   => '1995-03-15',
        'gender'          => 'female',
        'location_id'     => $location->id,
        'status'          => 'confirmed',
        'date_of_joining' => '2026-07-01',
    ]);

    // Calculate EL accrual for 2026 — joining in July means 6 months remaining
    $accrualDays = $engine->calculateProRataAccrual(
        employee:    $employee,
        leaveType:   'EL',
        year:        2026,
        stateCode:   'KA',
    );

    // KA EL = 1.5 days/month × 6 months (Jul–Dec) = 9.0 days
    expect($accrualDays)->toBe(9.0);

    // ── Part C: Full-year employee gets full quota ────────────────────────────
    $fullYearEmployee = Employee::withoutGlobalScopes()->create([
        'employee_code'   => 'EMP-KA-10002',
        'first_name'      => 'Ravi',
        'last_name'       => 'Kumar',
        'email'           => 'ravi.kumar@nexusos.test',
        'phone'           => '9100000002',
        'date_of_birth'   => '1992-06-10',
        'gender'          => 'male',
        'location_id'     => $location->id,
        'status'          => 'confirmed',
        'date_of_joining' => '2025-01-01',  // Joined previous year — full year
    ]);

    $fullYearAccrual = $engine->calculateProRataAccrual(
        employee:    $fullYearEmployee,
        leaveType:   'EL',
        year:        2026,
        stateCode:   'KA',
    );

    // Full year: 1.5 × 12 = 18.0 days
    expect($fullYearAccrual)->toBe(18.0);

    // ── Part D: Year-end carry-forward capping ────────────────────────────────
    // Employee has 35 EL days at year-end; KA carry_forward_limit = 30
    // Excess = 5 days; excess_handling = 'encash' → encash 5, carry forward 30
    $carryForwardResult = $engine->applyYearEndCarryForward(
        currentBalance: 35.0,
        leaveType:      'EL',
        stateCode:      'KA',
    );

    expect($carryForwardResult['carry_forward'])->toBe(30.0)
        ->and($carryForwardResult['lapsed'])->toBe(0.0)
        ->and($carryForwardResult['encashed'])->toBe(5.0);

    // ── Part E: CL does not carry forward (limit = 0, lapse) ─────────────────
    $clCarryForward = $engine->applyYearEndCarryForward(
        currentBalance: 4.0,
        leaveType:      'CL',
        stateCode:      'KA',
    );

    expect($clCarryForward['carry_forward'])->toBe(0.0)
        ->and($clCarryForward['lapsed'])->toBe(4.0)
        ->and($clCarryForward['encashed'])->toBe(0.0);

    // ── Part F: Comp Off expiry — expired balance is deducted ────────────────
    $balance = LeaveBalance::withoutGlobalScopes()->create([
        'employee_id'    => $employee->id,
        'location_id'    => $location->id,
        'leave_type'     => 'CO',
        'year'           => 2026,
        'opening_balance'=> 0.0,
        'accrued'        => 2.0,
        'availed'        => 0.0,
        'pending'        => 0.0,
        'closing_balance'=> 2.0,
        'expiry_date'    => Carbon::now()->subDays(1)->toDateString(), // expired yesterday
    ]);

    $expiredDays = $engine->expireCompOffBalances(Carbon::now());

    // The expired balance should have been zeroed out
    $balance->refresh();
    expect((float) $balance->closing_balance)->toBe(0.0)
        ->and($expiredDays)->toBeGreaterThanOrEqual(2);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2: Holiday calendar excludes state-specific holidays from working days
//
// Scenario: Karnataka employee applies for leave from 2026-11-01 to 2026-11-07.
// 2026-11-01 is a Sunday (weekend — excluded automatically).
// 2026-11-01 is also Kannada Rajyotsava (state holiday for KA).
// Working days = 7 calendar days - 1 Sunday - 1 state holiday = 5 working days.
// ─────────────────────────────────────────────────────────────────────────────
it('test_holiday_calendar_excludes_state_specific_holidays_from_working_days', function () {
    $service = app(LeaveService::class);

    $location = Location::withoutGlobalScopes()->create([
        'name'       => 'Mysuru Office',
        'code'       => 'MYS-01',
        'address'    => 'Vijayanagar, Mysuru',
        'city'       => 'Mysuru',
        'state'      => 'Karnataka',
        'state_code' => 'KA',
        'pin_code'   => '570017',
        'gis_lat'    => 12.2958,
        'gis_lng'    => 76.6394,
        'is_active'  => true,
    ]);

    // ── Seed holidays for this location ───────────────────────────────────────
    // National holiday: Diwali on 2026-10-30 (outside our range — should not affect count)
    HolidayCalendar::withoutGlobalScopes()->create([
        'location_id'  => $location->id,
        'holiday_name' => 'Diwali',
        'holiday_date' => '2026-10-30',
        'type'         => 'national',
        'is_active'    => true,
    ]);

    // State holiday: Kannada Rajyotsava on 2026-11-01 (Sunday AND state holiday)
    HolidayCalendar::withoutGlobalScopes()->create([
        'location_id'  => $location->id,
        'holiday_name' => 'Kannada Rajyotsava',
        'holiday_date' => '2026-11-01',
        'type'         => 'state',
        'is_active'    => true,
    ]);

    // Another state holiday within range: Kanaka Jayanti on 2026-11-04 (Wednesday)
    HolidayCalendar::withoutGlobalScopes()->create([
        'location_id'  => $location->id,
        'holiday_name' => 'Kanaka Jayanti',
        'holiday_date' => '2026-11-04',
        'type'         => 'state',
        'is_active'    => true,
    ]);

    // ── Part A: Count working days 2026-11-01 to 2026-11-07 ──────────────────
    // Calendar: Sun(holiday) Mon Tue Wed(holiday) Thu Fri Sat
    // Weekends: Sun(01), Sat(07) = 2 weekend days
    // Holidays in range: 01-Nov (Sun/holiday — already counted as weekend), 04-Nov (Wed)
    // Working days = 7 - 2 weekends - 1 unique holiday (04-Nov, since 01-Nov is already weekend)
    // = 7 - 2 - 1 = 4 working days
    $workingDays = $service->countWorkingDays(
        location:  $location,
        fromDate:  Carbon::parse('2026-11-01'),
        toDate:    Carbon::parse('2026-11-07'),
    );

    expect($workingDays)->toBe(4);

    // ── Part B: No holidays in range — only weekends excluded ─────────────────
    // 2026-11-09 (Mon) to 2026-11-13 (Fri) = 5 working days, no holidays
    $workingDaysNoHoliday = $service->countWorkingDays(
        location:  $location,
        fromDate:  Carbon::parse('2026-11-09'),
        toDate:    Carbon::parse('2026-11-13'),
    );

    expect($workingDaysNoHoliday)->toBe(5);

    // ── Part C: National holiday is also excluded for all locations ───────────
    // Republic Day 2026-01-26 (Monday) — seed as national for this location
    HolidayCalendar::withoutGlobalScopes()->create([
        'location_id'  => $location->id,
        'holiday_name' => 'Republic Day',
        'holiday_date' => '2026-01-26',
        'type'         => 'national',
        'is_active'    => true,
    ]);

    // 2026-01-26 (Mon) to 2026-01-30 (Fri) = 5 calendar days - 1 national holiday = 4 working days
    $workingDaysWithNational = $service->countWorkingDays(
        location:  $location,
        fromDate:  Carbon::parse('2026-01-26'),
        toDate:    Carbon::parse('2026-01-30'),
    );

    expect($workingDaysWithNational)->toBe(4);

    // ── Part D: Half-day leave counts as 0.5 working days ────────────────────
    $halfDayCount = $service->countWorkingDays(
        location:   $location,
        fromDate:   Carbon::parse('2026-11-02'),  // Monday — working day
        toDate:     Carbon::parse('2026-11-02'),
        isHalfDay:  true,
    );

    expect($halfDayCount)->toBe(0.5);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3: Leave approval workflow updates balance and creates audit log
//
// Scenario: Employee applies for 3 days CL.
// - On application: balance moves from available to pending (tentative deduction).
// - On approval: balance moves from pending to availed; activity log entry created.
// - On rejection: balance is fully restored to available.
// ─────────────────────────────────────────────────────────────────────────────
it('test_leave_approval_workflow_updates_balance_and_creates_audit_log', function () {
    Event::fake();

    $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

    $location = Location::withoutGlobalScopes()->create([
        'name'       => 'Pune Tech Hub',
        'code'       => 'PNQ-02',
        'address'    => 'Baner Road, Pune',
        'city'       => 'Pune',
        'state'      => 'Maharashtra',
        'state_code' => 'MH',
        'pin_code'   => '411045',
        'gis_lat'    => 18.5590,
        'gis_lng'    => 73.7868,
        'is_active'  => true,
    ]);

    $manager = User::factory()->create(['location_id' => $location->id]);
    $manager->assignRole('manager');

    $employee = Employee::withoutGlobalScopes()->create([
        'employee_id'     => (string) \Illuminate\Support\Str::uuid(),
        'employee_code'   => 'EMP-MH-20001',
        'first_name'      => 'Anita',
        'last_name'       => 'Desai',
        'email'           => 'anita.desai@nexusos.test',
        'phone'           => '9200000001',
        'date_of_birth'   => '1993-08-20',
        'gender'          => 'female',
        'location_id'     => $location->id,
        'status'          => 'confirmed',
        'date_of_joining' => '2024-01-01',
    ]);

    // Seed a CL balance of 8 days (MH annual quota)
    $balance = LeaveBalance::withoutGlobalScopes()->create([
        'employee_id'     => $employee->id,
        'location_id'     => $location->id,
        'leave_type'      => 'CL',
        'year'            => 2026,
        'opening_balance' => 8.0,
        'accrued'         => 0.0,
        'availed'         => 0.0,
        'pending'         => 0.0,
        'closing_balance' => 8.0,
        'expiry_date'     => null,
    ]);

    $service = app(LeaveService::class);

    // ── Step 1: Employee applies for 3 days CL ────────────────────────────────
    $application = $service->applyLeave(
        employee:         $employee,
        leaveType:        'CL',
        fromDate:         Carbon::parse('2026-08-10'),
        toDate:           Carbon::parse('2026-08-12'),
        reason:           'Family function',
        isHalfDay:        false,
        halfDaySession:   null,
    );

    expect($application)->not->toBeNull()
        ->and($application->status)->toBe('pending_approval')
        ->and((float) $application->number_of_days)->toBe(3.0);

    // Balance: available = 8 - 3 = 5, pending = 3, availed = 0
    $balance->refresh();
    expect((float) $balance->closing_balance)->toBe(5.0)
        ->and((float) $balance->pending)->toBe(3.0)
        ->and((float) $balance->availed)->toBe(0.0);

    // ── Step 2: Manager approves the leave ────────────────────────────────────
    $service->approveLeave(
        application: $application,
        approver:    $manager,
        comments:    'Approved. Enjoy the function.',
    );

    $application->refresh();
    expect($application->status)->toBe('approved')
        ->and($application->approved_by)->toBe($manager->id);

    // Balance: available = 5, pending = 0, availed = 3
    $balance->refresh();
    expect((float) $balance->closing_balance)->toBe(5.0)
        ->and((float) $balance->pending)->toBe(0.0)
        ->and((float) $balance->availed)->toBe(3.0);

    // Audit log must exist for the approval action
    $auditLog = \Spatie\Activitylog\Models\Activity::where('subject_type', LeaveApplication::class)
        ->where('subject_id', $application->id)
        ->where('event', 'approved')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->causer_id)->toBe($manager->id);

    // ── Step 3: Test rejection restores balance ───────────────────────────────
    // Apply a second leave application
    $application2 = $service->applyLeave(
        employee:       $employee,
        leaveType:      'CL',
        fromDate:       Carbon::parse('2026-09-01'),
        toDate:         Carbon::parse('2026-09-02'),
        reason:         'Personal work',
        isHalfDay:      false,
        halfDaySession: null,
    );

    // Balance after second application: available = 5 - 2 = 3, pending = 2
    $balance->refresh();
    expect((float) $balance->closing_balance)->toBe(3.0)
        ->and((float) $balance->pending)->toBe(2.0);

    // Manager rejects the second application
    $service->rejectLeave(
        application: $application2,
        approver:    $manager,
        comments:    'Insufficient notice period.',
    );

    $application2->refresh();
    expect($application2->status)->toBe('rejected');

    // Balance must be fully restored: available = 5, pending = 0
    $balance->refresh();
    expect((float) $balance->closing_balance)->toBe(5.0)
        ->and((float) $balance->pending)->toBe(0.0);

    // ── Step 4: Half-day leave application ───────────────────────────────────
    $halfDayApp = $service->applyLeave(
        employee:       $employee,
        leaveType:      'CL',
        fromDate:       Carbon::parse('2026-09-10'),
        toDate:         Carbon::parse('2026-09-10'),
        reason:         'Doctor appointment',
        isHalfDay:      true,
        halfDaySession: 'first_half',
    );

    expect($halfDayApp)->not->toBeNull()
        ->and((float) $halfDayApp->number_of_days)->toBe(0.5)
        ->and($halfDayApp->is_half_day)->toBeTrue()
        ->and($halfDayApp->half_day_session)->toBe('first_half');

    // Balance: available = 5 - 0.5 = 4.5, pending = 0.5
    $balance->refresh();
    expect((float) $balance->closing_balance)->toBe(4.5)
        ->and((float) $balance->pending)->toBe(0.5);
});

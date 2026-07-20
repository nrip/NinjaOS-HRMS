<?php

declare(strict_types=1);

use App\Jobs\ProcessBiometricPunch;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Shift;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\GeoFencingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Test 1: Geo-fencing rejects out-of-bounds punch
// ─────────────────────────────────────────────────────────────────────────────
it('test_geo_fencing_rejects_out_of_bounds_punch', function () {
    // Mumbai office coordinates (Nariman Point)
    $location = Location::withoutGlobalScopes()->create([
        'name'                     => 'Mumbai HQ',
        'address'                  => '1 Nariman Point, Mumbai',
        'city'                     => 'Mumbai',
        'state'                    => 'Maharashtra',
        'pin_code'                 => '400021',
        'gis_lat'                  => 18.9220,   // Nariman Point
        'gis_lng'                  => 72.8347,
        'attendance_radius_meters' => 100,        // 100m radius
        'is_active'                => true,
    ]);

    $geoService = new GeoFencingService();

    // ── Case 1: Punch from 50m away — WITHIN radius ──────────────────────────
    // Approximately 50m north of Nariman Point
    $nearLat = 18.9225;
    $nearLng = 72.8347;
    $nearResult = $geoService->validate($location, $nearLat, $nearLng);

    expect($nearResult['allowed'])->toBeTrue()
        ->and($nearResult['distance_metres'])->toBeLessThanOrEqual(100);

    // ── Case 2: Punch from 5km away (Bandra) — OUTSIDE radius ───────────────
    $farLat = 19.0596;  // Bandra, Mumbai (~15km from Nariman Point)
    $farLng = 72.8295;
    $farResult = $geoService->validate($location, $farLat, $farLng);

    expect($farResult['allowed'])->toBeFalse()
        ->and($farResult['distance_metres'])->toBeGreaterThan(100)
        ->and($farResult['message'])->toContain('Punch rejected')
        ->and($farResult['message'])->toContain('Maximum allowed distance is 100m');

    // ── Case 3: AttendanceService rejects the punch and returns success=false ─
    $employee = Employee::withoutGlobalScopes()->create([
        'employee_code'   => 'EMP-MH-99001',
        'first_name'      => 'Test',
        'last_name'       => 'Employee',
        'email'           => 'geo.test@nexusos.test',
        'phone'           => '9000000001',
        'date_of_birth'   => '1990-01-01',
        'gender'          => 'male',
        'location_id'     => $location->id,
        'status'          => 'confirmed',
        'date_of_joining' => now()->subYear()->toDateString(),
    ]);

    $service = app(AttendanceService::class);
    $result  = $service->processPunch(
        employee:    $employee,
        punchType:   'IN',
        timestamp:   Carbon::now(),
        punchSource: 'mobile_gps',   // GPS source — geo-fencing IS applied
        latitude:    $farLat,
        longitude:   $farLng,
    );

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Punch rejected')
        ->and($result['attendance'])->toBeNull();

    // Verify no attendance record was created
    expect(
        Attendance::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->count()
    )->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2: Biometric mock endpoint pushes job to queue and job processes correctly
// ─────────────────────────────────────────────────────────────────────────────
it('test_biometric_mock_pushes_to_queue_and_processes', function () {
    Queue::fake();

    $location = Location::withoutGlobalScopes()->create([
        'name'       => 'Pune Office',
        'address'    => 'Hinjewadi Phase 1, Pune',
        'city'       => 'Pune',
        'state'      => 'Maharashtra',
        'pin_code'   => '411057',
        'gis_lat'    => 18.5913,
        'gis_lng'    => 73.7389,
        'is_active'  => true,
    ]);

    // Seed roles and create an authenticated user
    $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);
    $user = \App\Models\User::factory()->create([
        'location_id' => $location->id,
    ]);
    $user->assignRole('location_hr');

    // ── Part A: POST to mock endpoint returns 202 and dispatches job ─────────
    $payload = [
        'employee_code' => 'EMP-MH-00001',
        'punch_type'    => 'IN',
        'timestamp'     => '2026-07-20T09:15:00+05:30',
        'latitude'      => 18.5913,
        'longitude'     => 73.7389,
        'device_id'     => 'ZK-MOCK-01',
    ];

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/integrations/biometric/mock-punch', $payload);

    $response->assertStatus(202)
        ->assertJson([
            'status'        => 'queued',
            'employee_code' => 'EMP-MH-00001',
            'punch_type'    => 'IN',
            'device_id'     => 'ZK-MOCK-01',
        ]);

    // Verify the job was dispatched to the biometric queue
    Queue::assertPushedOn('biometric', ProcessBiometricPunch::class, function ($job) {
        return $job->employeeCode === 'EMP-MH-00001'
            && $job->punchType    === 'IN'
            && $job->deviceId     === 'ZK-MOCK-01';
    });

    // ── Part B: Job processes correctly when handled synchronously ───────────
    Queue::fake(); // reset

    $employee = Employee::withoutGlobalScopes()->create([
        'employee_code'   => 'EMP-MH-00001',
        'first_name'      => 'Biometric',
        'last_name'       => 'TestUser',
        'email'           => 'biometric.test@nexusos.test',
        'phone'           => '9000000002',
        'date_of_birth'   => '1990-01-01',
        'gender'          => 'male',
        'location_id'     => $location->id,
        'status'          => 'confirmed',
        'date_of_joining' => now()->subYear()->toDateString(),
    ]);

    // Dispatch and process the job synchronously
    $job = new ProcessBiometricPunch(
        employeeCode: 'EMP-MH-00001',
        punchType:    'IN',
        timestamp:    '2026-07-20T09:15:00+05:30',
        latitude:     18.5913,
        longitude:    73.7389,
        deviceId:     'ZK-MOCK-01',
    );
    $job->handle(app(AttendanceService::class));

    // Verify attendance record was created
    $attendance = Attendance::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->punch_source)->toBe('biometric')
        ->and($attendance->device_id)->toBe('ZK-MOCK-01')
        ->and($attendance->status)->toBe('present');
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3: OT calculation reads from config/statutory.php, not hardcoded values
// ─────────────────────────────────────────────────────────────────────────────
it('test_ot_calculation_uses_config_values_and_shift_timings', function () {
    $service = app(AttendanceService::class);

    // ── Part A: Verify config values are present for all 9 states ────────────
    $requiredStates = ['MH', 'KA', 'DL', 'HR', 'UP', 'GJ', 'WB', 'JH', 'GA'];
    foreach ($requiredStates as $state) {
        $otConfig = config("statutory.overtime.{$state}");
        expect($otConfig)->not->toBeNull("OT config missing for state: {$state}")
            ->and($otConfig)->toHaveKey('daily_working_hours')
            ->and($otConfig)->toHaveKey('ot_applicable_after_hours')
            ->and($otConfig)->toHaveKey('ot_rate_multiplier');
    }

    // ── Part B: OT calculation uses config threshold, not hardcoded values ───
    // Maharashtra: ot_applicable_after_hours = 9
    $mhConfig = config('statutory.overtime.MH');
    $mhThreshold = (float) $mhConfig['ot_applicable_after_hours'];

    // Exactly at threshold — no OT
    $otAtThreshold = $service->calculateOtHours($mhThreshold, 'MH');
    expect($otAtThreshold)->toBe(0.0);

    // 1 hour over threshold — 1h OT
    $otOneHourOver = $service->calculateOtHours($mhThreshold + 1.0, 'MH');
    expect($otOneHourOver)->toBe(1.0);

    // 2.5 hours over threshold — 2.5h OT
    $otTwoAndHalfOver = $service->calculateOtHours($mhThreshold + 2.5, 'MH');
    expect($otTwoAndHalfOver)->toBe(2.5);

    // ── Part C: Different states have different thresholds ───────────────────
    $dlConfig    = config('statutory.overtime.DL');
    $dlThreshold = (float) $dlConfig['ot_applicable_after_hours'];

    // Delhi threshold may differ from Maharashtra — verify they're read from config
    $otDl = $service->calculateOtHours($dlThreshold + 1.5, 'DL');
    expect($otDl)->toBe(1.5);

    // ── Part D: Full punch cycle with OT via shift timings ───────────────────
    $location = Location::withoutGlobalScopes()->create([
        'name'       => 'Bengaluru Office',
        'code'       => 'BLR-01',
        'address'    => 'Whitefield, Bengaluru',
        'city'       => 'Bengaluru',
        'state'      => 'Karnataka',
        'state_code' => 'KA',
        'pin_code'   => '560066',
        'gis_lat'    => 12.9716,
        'gis_lng'    => 77.5946,
        'is_active'  => true,
    ]);

    $shift = Shift::withoutGlobalScopes()->create([
        'location_id'          => $location->id,
        'name'                 => 'General Shift',
        'code'                 => 'GEN-SHIFT-01',
        'start_time'           => '09:00:00',
        'end_time'             => '18:00:00',
        'duration_hours'       => 9,
        'is_night_shift'       => false,
        'grace_period_minutes' => 15,
        'is_active'            => true,
    ]);

    $employee = Employee::withoutGlobalScopes()->create([
        'employee_code'   => 'EMP-KA-99001',
        'first_name'      => 'OT',
        'last_name'       => 'TestEmployee',
        'email'           => 'ot.test@nexusos.test',
        'phone'           => '9000000003',
        'date_of_birth'   => '1990-01-01',
        'gender'          => 'female',
        'location_id'     => $location->id,
        'status'          => 'confirmed',
        'date_of_joining' => now()->subYear()->toDateString(),
    ]);

    // Punch IN at 09:00
    $service->processPunch(
        employee:    $employee,
        punchType:   'IN',
        timestamp:   Carbon::parse('2026-07-20 09:00:00'),
        punchSource: 'biometric',
    );

    // Punch OUT at 20:00 — 11 hours worked
    // KA threshold is 9 hours, so OT = 11 - 9 = 2 hours
    $kaConfig    = config('statutory.overtime.KA');
    $kaThreshold = (float) $kaConfig['ot_applicable_after_hours'];

    $service->processPunch(
        employee:    $employee,
        punchType:   'OUT',
        timestamp:   Carbon::parse('2026-07-20 20:00:00'),
        punchSource: 'biometric',
    );

    $attendance = Attendance::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->where('attendance_date', '2026-07-20')
        ->first();

    $expectedOt = round(11.0 - $kaThreshold, 2);

    expect($attendance)->not->toBeNull()
        ->and((float) $attendance->ot_hours)->toBe($expectedOt)
        ->and((float) $attendance->hours_worked)->toBeGreaterThan($kaThreshold);
});

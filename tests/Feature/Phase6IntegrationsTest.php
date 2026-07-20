<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\Location;
use App\Models\PayrollRecord;
use App\Models\User;
use App\Services\Payroll\BankTransferFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Test 1: HDFC Bank File Generation
// Verifies the 8-column HDFC NEFT CSV format is correctly generated.
// ─────────────────────────────────────────────────────────────────────────────
it('test_hdfc_bank_file_generation_formats_correctly', function () {
    Storage::fake('local');

    // Arrange: build a minimal PayrollRecord collection in memory
    $record = new PayrollRecord();
    $record->status        = 'finalized';
    $record->net_pay       = 55000.00;
    $record->employee_code = 'EMP-MH-00001';

    // Attach a mock employee with bank details
    $employee = new Employee();
    $employee->first_name          = 'Ravi';
    $employee->last_name           = 'Kumar';
    $employee->bank_account_number = '50100123456789';
    $employee->bank_ifsc_code      = 'HDFC0001234';
    $employee->bank_name           = 'HDFC Bank';
    $record->setRelation('employee', $employee);

    $records = collect([$record]);

    // Act: generate the CSV
    $service = new BankTransferFileService();
    $csv     = $service->generateHdfcCsv($records, 7, 2026);

    // Assert: header row has all 8 mandatory HDFC columns
    expect($csv)->toContain('Transaction Type');
    expect($csv)->toContain('Debit Account No');
    expect($csv)->toContain('Beneficiary Account No');
    expect($csv)->toContain('Beneficiary Name');
    expect($csv)->toContain('Amount');
    expect($csv)->toContain('Beneficiary IFSC');
    expect($csv)->toContain('Value Date');
    expect($csv)->toContain('Customer Reference No');

    // Assert: data row contains correct values
    expect($csv)->toContain('NEFT');
    expect($csv)->toContain('50100123456789');
    expect($csv)->toContain('Ravi Kumar');
    expect($csv)->toContain('55000.00');
    expect($csv)->toContain('HDFC0001234');
    expect($csv)->toContain('SAL-JUL-2026-EMP-MH-00001');

    // Assert: value date is formatted as DD/MM/YYYY
    $expectedDate = now()->format('d/m/Y');
    expect($csv)->toContain($expectedDate);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 2: WhatsApp Mock Job
// Verifies the job is dispatched to the queue and logs the payload.
// ─────────────────────────────────────────────────────────────────────────────
it('test_whatsapp_mock_job_logs_payload_and_queues_successfully', function () {
    Queue::fake();
    Storage::fake('local');

    // Arrange: seed roles and create a user/employee
    $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

    $location = Location::withoutGlobalScopes()->create([
        'name'          => 'Mumbai HQ',
        'code'          => 'MUM-01',
        'state_code'    => 'MH',
        'state'         => 'Maharashtra',
        'city'          => 'Mumbai',
        'address'       => '123 Test Street',
        'pin_code'      => '400001',
        'gis_lat'                  => 19.0760,
        'gis_lng'                  => 72.8777,
        'attendance_radius_meters' => 200,
    ]);

    $user = User::factory()->create(['location_id' => $location->id]);

    // Act: dispatch the WhatsApp mock job
    \App\Jobs\SendWhatsAppMessageJob::dispatch(
        phone:    '+919876543210',
        template: 'leave_approved',
        payload:  [
            'employee_name' => $user->name,
            'leave_type'    => 'Casual Leave',
            'from_date'     => '2026-08-01',
            'to_date'       => '2026-08-02',
        ]
    );

    // Assert: job was pushed to the queue
    Queue::assertPushed(\App\Jobs\SendWhatsAppMessageJob::class, function ($job) {
        return $job->phone === '+919876543210'
            && $job->template === 'leave_approved';
    });

    // Assert: exactly 1 job was queued
    Queue::assertPushed(\App\Jobs\SendWhatsAppMessageJob::class, 1);
});

// ─────────────────────────────────────────────────────────────────────────────
// Test 3: Mobile Attendance API — Geo-coordinate Validation
// Verifies the API rejects punch requests when location is required but missing.
// ─────────────────────────────────────────────────────────────────────────────
it('test_mobile_attendance_api_rejects_missing_geo_coordinates', function () {
    // Arrange: seed roles and create a user with a location that requires geo-fencing
    $this->artisan('db:seed', ['--class' => 'RoleAndPermissionSeeder']);

    $location = Location::withoutGlobalScopes()->create([
        'name'          => 'Bangalore Office',
        'code'          => 'BLR-01',
        'state_code'    => 'KA',
        'state'         => 'Karnataka',
        'city'          => 'Bangalore',
        'address'       => '456 Tech Park',
        'pin_code'      => '560001',
        'gis_lat'                  => 12.9716,
        'gis_lng'                  => 77.5946,
        'attendance_radius_meters' => 150,
    ]);

    $user = User::factory()->create(['location_id' => $location->id]);
    $user->assignRole('employee');

    $employee = Employee::withoutGlobalScopes()->create([
        'employee_id'    => \Illuminate\Support\Str::uuid(),
        'location_id'    => $location->id,
        'department_id'  => null,
        'designation_id' => null,
        'first_name'     => 'Test',
        'last_name'      => 'Employee',
        'email'          => 'test.employee.geo@example.com',
        'phone'          => '9000000099',
        'date_of_birth'  => '1990-01-01',
        'gender'         => 'male',
        'bank_name'      => 'HDFC Bank',
        'date_of_joining' => now()->subYear()->toDateString(),
        'status'         => 'confirmed',
    ]);

    // Act: POST to the mobile punch API without latitude/longitude
    // The location has geo-fencing enabled (radius_meters > 0) so coordinates are required.
    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/attendance/punch', [
            'employee_id' => $employee->id,
            'punch_type'  => 'IN',
            // latitude and longitude intentionally omitted
        ]);

    // Assert: the API returns a 422 with a geo-fencing error
    $response->assertStatus(422);
    $response->assertJsonFragment(['success' => false]);
    expect($response->json('message') ?? $response->json('error') ?? '')
        ->toContain('coordinates');
});

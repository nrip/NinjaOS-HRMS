<?php

use App\Models\Employee;
use App\Models\Location;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Services\EmployeeImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * Write CSV content to a real temp file and return its absolute path.
 * We use sys_get_temp_dir() to avoid Storage::fake() conflicts with the service.
 */
function csvFile(string $content): string
{
    $path = sys_get_temp_dir() . '/nexusos_test_' . uniqid() . '.csv';
    file_put_contents($path, $content);
    return $path;
}

beforeEach(function () {
    $this->location    = Location::factory()->create(['state' => 'Maharashtra']);
    $this->department  = Department::factory()->create(['code' => 'HR']);
    $this->designation = Designation::factory()->create(['code' => 'MGR']);

    Role::firstOrCreate(['name' => 'central_hr', 'guard_name' => 'web']);

    $this->user = User::factory()->create(['location_id' => $this->location->id]);
    $this->user->assignRole('central_hr');

    $tenantContext = app(\App\Services\TenantContext::class);
    $tenantContext->setLocationId($this->location->id);
    $tenantContext->setUserId($this->user->id);

    $this->actingAs($this->user);
});

// ─────────────────────────────────────────────────────────────────────────────
describe('Employee Import', function () {

    test('import service accepts valid CSV and returns success', function () {
        $csv = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,john@example.com,9876543210,1990-01-01,male,HR,MGR,2026-08-01
Jane,Smith,jane@example.com,9876543211,1992-02-02,female,HR,MGR,2026-08-02
CSV;

        $path    = csvFile($csv);
        $service = new EmployeeImportService();
        $result  = $service->import($path, $this->location->id, false);

        expect($result['success'])->toBeTrue();
        expect($result['imported_count'])->toBe(2);
        expect($result['error_count'])->toBe(0);

        $count = Employee::query()->withoutGlobalScopes()
            ->where('location_id', $this->location->id)
            ->count();
        expect($count)->toBe(2);

        @unlink($path);
    });

    test('import fails with invalid email and reports row error', function () {
        $csv = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,invalid-email,9876543210,1990-01-01,male,HR,MGR,2026-08-01
CSV;

        $path    = csvFile($csv);
        $service = new EmployeeImportService();
        $result  = $service->import($path, $this->location->id, false);

        expect($result['success'])->toBeFalse();
        expect($result['error_count'])->toBeGreaterThan(0);
        expect($result['errors'][0])->toHaveKey('row');
        expect($result['errors'][0])->toHaveKey('errors');

        $count = Employee::query()->withoutGlobalScopes()
            ->where('location_id', $this->location->id)
            ->count();
        expect($count)->toBe(0);

        @unlink($path);
    });

    test('import fails with invalid phone', function () {
        $csv = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,john@example.com,123,1990-01-01,male,HR,MGR,2026-08-01
CSV;

        $path    = csvFile($csv);
        $service = new EmployeeImportService();
        $result  = $service->import($path, $this->location->id, false);

        expect($result['success'])->toBeFalse();
        expect($result['error_count'])->toBeGreaterThan(0);

        @unlink($path);
    });

    test('import fails with non-existent department code', function () {
        $csv = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,john@example.com,9876543210,1990-01-01,male,INVALID_DEPT,MGR,2026-08-01
CSV;

        $path    = csvFile($csv);
        $service = new EmployeeImportService();
        $result  = $service->import($path, $this->location->id, false);

        expect($result['success'])->toBeFalse();
        expect($result['error_count'])->toBeGreaterThan(0);

        @unlink($path);
    });

    test('import fails with duplicate email and reports specific error', function () {
        // Pre-create an employee with the same email (bypass scope)
        Employee::query()->withoutGlobalScopes()->create([
            'location_id'    => $this->location->id,
            'department_id'  => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name'     => 'Existing',
            'last_name'      => 'Employee',
            'email'          => 'john@example.com',
            'phone'          => '9876543210',
            'date_of_birth'  => '1990-01-01',
            'gender'         => 'male',
            'date_of_joining'=> now()->format('Y-m-d'),
            'status'         => 'confirmed',
        ]);

        $csv = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,john@example.com,9876543210,1990-01-01,male,HR,MGR,2026-08-01
CSV;

        $path    = csvFile($csv);
        $service = new EmployeeImportService();
        $result  = $service->import($path, $this->location->id, false);

        expect($result['success'])->toBeFalse();
        expect($result['error_count'])->toBeGreaterThan(0);

        // Still only 1 employee (the pre-existing one)
        $count = Employee::query()->withoutGlobalScopes()
            ->where('location_id', $this->location->id)
            ->count();
        expect($count)->toBe(1);

        @unlink($path);
    });

    test('dry run does not persist data', function () {
        $csv = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,john@example.com,9876543210,1990-01-01,male,HR,MGR,2026-08-01
CSV;

        $path    = csvFile($csv);
        $service = new EmployeeImportService();
        $result  = $service->import($path, $this->location->id, true); // dry_run = true

        expect($result['dry_run'])->toBeTrue();

        // No records should be persisted
        $count = Employee::query()->withoutGlobalScopes()
            ->where('location_id', $this->location->id)
            ->count();
        expect($count)->toBe(0);

        @unlink($path);
    });

    test('import service returns structured error report with row numbers', function () {
        $csv = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,invalid-email,123,1990-01-01,male,INVALID_DEPT,MGR,2026-08-01
CSV;

        $path    = csvFile($csv);
        $service = new EmployeeImportService();
        $result  = $service->import($path, $this->location->id, false);

        expect($result['success'])->toBeFalse();
        expect($result['error_count'])->toBeGreaterThan(0);
        expect($result['errors'])->toBeArray();
        expect($result['errors'][0])->toHaveKey('row');
        expect($result['errors'][0])->toHaveKey('errors');
        expect($result['errors'][0]['row'])->toBe(2); // Row 2 (header is row 1)

        @unlink($path);
    });

    test('can download CSV template', function () {
        $response = $this->get(route('employees.import.template'));

        $response->assertStatus(200);
    });
});

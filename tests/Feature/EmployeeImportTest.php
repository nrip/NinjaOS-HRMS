<?php

use App\Models\Employee;
use App\Models\Location;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Services\EmployeeImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->location = Location::factory()->create();
    $this->department = Department::factory()->create(['code' => 'HR', 'location_id' => $this->location->id]);
    $this->designation = Designation::factory()->create(['code' => 'MGR']);
    
    $this->user = User::factory()->create();
    $this->user->assignRole('central_hr');
    
    $this->actingAs($this->user);
});

describe('Employee Import', function () {
    test('can import valid employees from CSV', function () {
        $csvContent = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,john@example.com,9876543210,1990-01-01,male,HR,MGR,2026-08-01
Jane,Smith,jane@example.com,9876543211,1992-02-02,female,HR,MGR,2026-08-02
CSV;

        $file = UploadedFile::fromString($csvContent, 'employees.csv', 'text/csv');
        
        $response = $this->post(route('employees.import.process'), [
            'csv_file' => $file,
            'location_id' => $this->location->id,
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseCount('employees', 2);
    });

    test('import fails with invalid email', function () {
        $csvContent = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,invalid-email,9876543210,1990-01-01,male,HR,MGR,2026-08-01
CSV;

        $file = UploadedFile::fromString($csvContent, 'employees.csv', 'text/csv');
        
        $response = $this->post(route('employees.import.process'), [
            'csv_file' => $file,
            'location_id' => $this->location->id,
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('employees', 0);
    });

    test('import fails with invalid phone', function () {
        $csvContent = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,john@example.com,123,1990-01-01,male,HR,MGR,2026-08-01
CSV;

        $file = UploadedFile::fromString($csvContent, 'employees.csv', 'text/csv');
        
        $response = $this->post(route('employees.import.process'), [
            'csv_file' => $file,
            'location_id' => $this->location->id,
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('employees', 0);
    });

    test('import fails with non-existent department', function () {
        $csvContent = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,john@example.com,9876543210,1990-01-01,male,INVALID,MGR,2026-08-01
CSV;

        $file = UploadedFile::fromString($csvContent, 'employees.csv', 'text/csv');
        
        $response = $this->post(route('employees.import.process'), [
            'csv_file' => $file,
            'location_id' => $this->location->id,
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('employees', 0);
    });

    test('import fails with duplicate email', function () {
        Employee::factory()->create([
            'location_id' => $this->location->id,
            'email' => 'john@example.com',
        ]);

        $csvContent = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,john@example.com,9876543210,1990-01-01,male,HR,MGR,2026-08-01
CSV;

        $file = UploadedFile::fromString($csvContent, 'employees.csv', 'text/csv');
        
        $response = $this->post(route('employees.import.process'), [
            'csv_file' => $file,
            'location_id' => $this->location->id,
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('employees', 1);
    });

    test('can download CSV template', function () {
        $response = $this->get(route('employees.import.template'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv');
    });

    test('dry run does not persist data', function () {
        $csvContent = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,john@example.com,9876543210,1990-01-01,male,HR,MGR,2026-08-01
CSV;

        $file = UploadedFile::fromString($csvContent, 'employees.csv', 'text/csv');
        
        $response = $this->post(route('employees.import.process'), [
            'csv_file' => $file,
            'location_id' => $this->location->id,
            'dry_run' => true,
        ]);

        $response->assertRedirect(route('employees.index'));
        $this->assertDatabaseCount('employees', 0);
    });

    test('import service returns detailed error report', function () {
        $service = new EmployeeImportService();

        $csvContent = <<<CSV
first_name,last_name,email,phone,date_of_birth,gender,department_code,designation_code,date_of_joining
John,Doe,invalid-email,123,1990-01-01,male,INVALID,MGR,2026-08-01
CSV;

        $file = UploadedFile::fromString($csvContent, 'employees.csv', 'text/csv');
        $filePath = $file->store('imports');

        $result = $service->import(storage_path("app/{$filePath}"), $this->location->id);

        expect($result['success'])->toBeFalse();
        expect($result['error_count'])->toBeGreaterThan(0);
        expect($result['errors'])->toBeArray();
        expect($result['errors'][0])->toHaveKey('row');
        expect($result['errors'][0])->toHaveKey('errors');
    });
});

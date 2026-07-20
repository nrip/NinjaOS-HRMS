<?php

use App\Models\Employee;
use App\Models\Location;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->location = Location::factory()->create();
    $this->department = Department::factory()->create(['location_id' => $this->location->id]);
    $this->designation = Designation::factory()->create();
    
    $this->user = User::factory()->create();
    $this->user->assignRole('central_hr');
    
    $this->actingAs($this->user);
});

describe('Employee CRUD', function () {
    test('can list employees', function () {
        Employee::factory(5)->create(['location_id' => $this->location->id]);
        
        $response = $this->get(route('employees.index'));
        
        $response->assertStatus(200);
        $response->assertViewHas('employees');
    });

    test('can create employee with valid data', function () {
        $data = [
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '9876543210',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'date_of_joining' => now()->addDay()->format('Y-m-d'),
        ];

        $response = $this->post(route('employees.store'), $data);

        $response->assertRedirect(route('employees.show', Employee::first()));
        $this->assertDatabaseHas('employees', ['email' => 'john@example.com']);
    });

    test('cannot create employee with invalid email', function () {
        $data = [
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'phone' => '9876543210',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'date_of_joining' => now()->addDay()->format('Y-m-d'),
        ];

        $response = $this->post(route('employees.store'), $data);

        $response->assertSessionHasErrors('email');
    });

    test('cannot create employee with invalid phone', function () {
        $data = [
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '12345',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'date_of_joining' => now()->addDay()->format('Y-m-d'),
        ];

        $response = $this->post(route('employees.store'), $data);

        $response->assertSessionHasErrors('phone');
    });

    test('can view employee details', function () {
        $employee = Employee::factory()->create(['location_id' => $this->location->id]);

        $response = $this->get(route('employees.show', $employee));

        $response->assertStatus(200);
        $response->assertViewHas('employee');
    });

    test('can update employee', function () {
        $employee = Employee::factory()->create(['location_id' => $this->location->id]);

        $response = $this->put(route('employees.update', $employee), [
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'phone' => '9876543210',
            'date_of_birth' => '1990-01-01',
            'gender' => 'female',
            'date_of_joining' => $employee->date_of_joining->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('employees.show', $employee));
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'first_name' => 'Jane']);
    });

    test('can delete employee', function () {
        $employee = Employee::factory()->create(['location_id' => $this->location->id]);

        $response = $this->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    });

    test('employee code is auto-generated', function () {
        $data = [
            'location_id' => $this->location->id,
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '9876543210',
            'date_of_birth' => '1990-01-01',
            'gender' => 'male',
            'date_of_joining' => now()->addDay()->format('Y-m-d'),
        ];

        $this->post(route('employees.store'), $data);

        $employee = Employee::first();
        expect($employee->employee_code)->toMatch('/^EMP-[A-Z]{2}-\d{5}$/');
    });

    test('location scope filters employees by location', function () {
        $location2 = Location::factory()->create();
        
        Employee::factory(3)->create(['location_id' => $this->location->id]);
        Employee::factory(2)->create(['location_id' => $location2->id]);

        // Switch to location HR for location1
        $locHr = User::factory()->create();
        $locHr->assignRole('location_hr');
        $locHr->employee()->associate(Employee::factory()->create(['location_id' => $this->location->id]))->save();

        $this->actingAs($locHr);
        
        $response = $this->get(route('employees.index'));
        
        // Should only see employees from their location
        $response->assertStatus(200);
    });
});

describe('Employee Lifecycle', function () {
    test('can transition employee from onboarding to probation', function () {
        $employee = Employee::factory()->create([
            'location_id' => $this->location->id,
            'status' => 'onboarding',
        ]);

        $response = $this->post(route('employees.transition', $employee), [
            'new_status' => 'probation',
            'probation_end_date' => now()->addMonths(3)->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('employees.show', $employee));
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'status' => 'probation']);
        $this->assertDatabaseHas('employee_lifecycle_history', [
            'employee_id' => $employee->id,
            'previous_status' => 'onboarding',
            'new_status' => 'probation',
        ]);
    });

    test('can transition employee from probation to confirmed', function () {
        $employee = Employee::factory()->create([
            'location_id' => $this->location->id,
            'status' => 'probation',
        ]);

        $response = $this->post(route('employees.transition', $employee), [
            'new_status' => 'confirmed',
            'confirmation_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('employees.show', $employee));
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'status' => 'confirmed']);
    });

    test('cannot transition employee to invalid status', function () {
        $employee = Employee::factory()->create([
            'location_id' => $this->location->id,
            'status' => 'exit',
        ]);

        $response = $this->post(route('employees.transition', $employee), [
            'new_status' => 'onboarding',
        ]);

        $response->assertStatus(422);
    });

    test('lifecycle history is tracked', function () {
        $employee = Employee::factory()->create([
            'location_id' => $this->location->id,
            'status' => 'onboarding',
        ]);

        $this->post(route('employees.transition', $employee), [
            'new_status' => 'probation',
            'probation_end_date' => now()->addMonths(3)->format('Y-m-d'),
            'reason' => 'Initial probation',
        ]);

        $this->assertDatabaseHas('employee_lifecycle_history', [
            'employee_id' => $employee->id,
            'reason' => 'Initial probation',
        ]);
    });
});

describe('Authorization', function () {
    test('location hr cannot view employees from other location', function () {
        $location2 = Location::factory()->create();
        $employee = Employee::factory()->create(['location_id' => $location2->id]);

        $locHr = User::factory()->create();
        $locHr->assignRole('location_hr');
        $locHr->employee()->associate(Employee::factory()->create(['location_id' => $this->location->id]))->save();

        $this->actingAs($locHr);
        
        $response = $this->get(route('employees.show', $employee));
        
        $response->assertStatus(403);
    });

    test('employee can view themselves', function () {
        $employee = Employee::factory()->create(['location_id' => $this->location->id]);
        $user = User::factory()->create();
        $user->employee()->associate($employee)->save();

        $this->actingAs($user);
        
        $response = $this->get(route('employees.show', $employee));
        
        $response->assertStatus(200);
    });
});

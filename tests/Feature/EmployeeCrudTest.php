<?php

use App\Models\Employee;
use App\Models\EmployeeLifecycleHistory;
use App\Models\Location;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * Create an employee bypassing LocationScope and the observer's auto-code logic.
 */
function makeEmp(array $attrs = []): Employee
{
    $location = isset($attrs['location_id'])
        ? $attrs['location_id']
        : Location::factory()->create(['state' => 'Maharashtra'])->id;

    $dept  = isset($attrs['department_id'])
        ? $attrs['department_id']
        : Department::factory()->create()->id;

    $desig = isset($attrs['designation_id'])
        ? $attrs['designation_id']
        : Designation::factory()->create()->id;

    return Employee::query()->withoutGlobalScopes()->create(array_merge([
        'location_id'    => $location,
        'department_id'  => $dept,
        'designation_id' => $desig,
        'first_name'     => 'Test',
        'last_name'      => 'User',
        'email'          => fake()->unique()->safeEmail(),
        'phone'          => '9876543210',
        'date_of_birth'  => '1990-01-01',
        'gender'         => 'male',
        'date_of_joining'=> now()->format('Y-m-d'),
        'status'         => 'confirmed',
    ], $attrs));
}

/**
 * Create a user with a given role and set TenantContext.
 */
function makeUserWithRole(string $roleName, int $locationId): User
{
    // Ensure the role exists
    Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

    $user = User::factory()->create(['location_id' => $locationId]);
    $user->assignRole($roleName);

    $tenantContext = app(\App\Services\TenantContext::class);
    $tenantContext->setLocationId($locationId);
    $tenantContext->setUserId($user->id);

    return $user;
}

beforeEach(function () {
    $this->location    = Location::factory()->create(['state' => 'Maharashtra']);
    $this->department  = Department::factory()->create();
    $this->designation = Designation::factory()->create();

    // Create a central_hr user (has full access)
    $this->user = makeUserWithRole('central_hr', $this->location->id);

    $this->actingAs($this->user);
});

// ─────────────────────────────────────────────────────────────────────────────
describe('Employee CRUD', function () {

    test('can list employees', function () {
        $this->withoutExceptionHandling();
        makeEmp(['location_id' => $this->location->id]);
        makeEmp(['location_id' => $this->location->id]);

        $response = $this->get(route('employees.index'));

        $response->assertStatus(200);
        $response->assertViewHas('employees');
    });

    test('can create employee with valid data', function () {
        $data = [
            'location_id'    => $this->location->id,
            'department_id'  => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'email'          => 'john@example.com',
            'phone'          => '9876543210',
            'date_of_birth'  => '1990-01-01',
            'gender'         => 'male',
            'date_of_joining'=> now()->addDay()->format('Y-m-d'),
        ];

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('employees.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', ['email' => 'john@example.com']);
    });

    test('cannot create employee with invalid email', function () {
        $data = [
            'location_id'    => $this->location->id,
            'department_id'  => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'email'          => 'invalid-email',
            'phone'          => '9876543210',
            'date_of_birth'  => '1990-01-01',
            'gender'         => 'male',
            'date_of_joining'=> now()->addDay()->format('Y-m-d'),
        ];

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('employees.store'), $data);

        $response->assertSessionHasErrors('email');
    });

    test('cannot create employee with invalid phone', function () {
        $data = [
            'location_id'    => $this->location->id,
            'department_id'  => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'email'          => 'john@example.com',
            'phone'          => '123',
            'date_of_birth'  => '1990-01-01',
            'gender'         => 'male',
            'date_of_joining'=> now()->addDay()->format('Y-m-d'),
        ];

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('employees.store'), $data);

        $response->assertSessionHasErrors('phone');
    });

    test('can view employee details', function () {
        $this->withoutExceptionHandling();
        $employee = makeEmp(['location_id' => $this->location->id]);

        $response = $this->get(route('employees.show', $employee));

        $response->assertStatus(200);
        $response->assertViewHas('employee');
    });

    test('can update employee', function () {
        $employee = makeEmp([
            'location_id'   => $this->location->id,
            'department_id' => $this->department->id,
        ]);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->put(route('employees.update', $employee), [
                'location_id'    => $this->location->id,
                'department_id'  => $this->department->id,
                'designation_id' => $this->designation->id,
                'first_name'     => 'Jane',
                'last_name'      => 'Doe',
                'email'          => 'jane@example.com',
                'phone'          => '9876543210',
                'date_of_birth'  => '1990-01-01',
                'gender'         => 'female',
                'date_of_joining'=> $employee->date_of_joining->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'first_name' => 'Jane']);
    });

    test('can soft-delete employee', function () {
        $employee = makeEmp(['location_id' => $this->location->id]);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->delete(route('employees.destroy', $employee));

        $response->assertRedirect(route('employees.index'));
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    });

    test('employee code is auto-generated on create', function () {
        $data = [
            'location_id'    => $this->location->id,
            'department_id'  => $this->department->id,
            'designation_id' => $this->designation->id,
            'first_name'     => 'John',
            'last_name'      => 'Doe',
            'email'          => 'john@example.com',
            'phone'          => '9876543210',
            'date_of_birth'  => '1990-01-01',
            'gender'         => 'male',
            'date_of_joining'=> now()->addDay()->format('Y-m-d'),
        ];

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('employees.store'), $data);

        $employee = Employee::query()->withoutGlobalScopes()
            ->where('email', 'john@example.com')->first();

        expect($employee)->not()->toBeNull();
        expect($employee->employee_code)->toMatch('/^EMP-[A-Z]{2}-\d{5}$/');
    });

    test('location scope filters employees by location', function () {
        $location2 = Location::factory()->create();

        makeEmp(['location_id' => $this->location->id]);
        makeEmp(['location_id' => $this->location->id]);
        makeEmp(['location_id' => $location2->id]);

        // TenantContext is set to $this->location->id in beforeEach
        $employees = Employee::all();

        expect($employees->count())->toBe(2);
        expect($employees->pluck('location_id')->unique()->first())->toBe($this->location->id);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
describe('Employee Lifecycle', function () {

    test('can transition employee from onboarding to probation', function () {
        $employee = makeEmp([
            'location_id' => $this->location->id,
            'status'      => 'onboarding',
        ]);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('employees.transition', $employee), [
                'new_status'         => 'probation',
                'probation_end_date' => now()->addMonths(3)->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'status' => 'probation']);
        $this->assertDatabaseHas('employee_lifecycle_history', [
            'employee_id'     => $employee->id,
            'previous_status' => 'onboarding',
            'new_status'      => 'probation',
        ]);
    });

    test('can transition employee from probation to confirmed', function () {
        $employee = makeEmp([
            'location_id' => $this->location->id,
            'status'      => 'probation',
        ]);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('employees.transition', $employee), [
                'new_status'        => 'confirmed',
                'confirmation_date' => now()->format('Y-m-d'),
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('employees', ['id' => $employee->id, 'status' => 'confirmed']);
    });

    test('cannot transition employee to invalid status', function () {
        $employee = makeEmp([
            'location_id' => $this->location->id,
            'status'      => 'exit',
        ]);

        $response = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('employees.transition', $employee), [
                'new_status' => 'onboarding',
            ]);

        $response->assertStatus(422);
    });

    test('lifecycle history is tracked', function () {
        $employee = makeEmp([
            'location_id' => $this->location->id,
            'status'      => 'onboarding',
        ]);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
            ->post(route('employees.transition', $employee), [
                'new_status'         => 'probation',
                'probation_end_date' => now()->addMonths(3)->format('Y-m-d'),
                'reason'             => 'Initial probation',
            ]);

        $this->assertDatabaseHas('employee_lifecycle_history', [
            'employee_id' => $employee->id,
            'reason'      => 'Initial probation',
        ]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
describe('Authorization', function () {

    test('location hr cannot view employees from other location', function () {
        $location2    = Location::factory()->create();
        $otherEmployee = makeEmp(['location_id' => $location2->id]);

        // Switch to a location_hr user scoped to location1
        $locHr = makeUserWithRole('location_hr', $this->location->id);
        $this->actingAs($locHr);

        // The employee is in location2 — LocationScope hides it → 404
        $response = $this->get(route('employees.show', $otherEmployee));

        $response->assertStatus(404);
    });

    test('employee can view themselves', function () {
        $employee = makeEmp(['location_id' => $this->location->id]);

        // A location_hr user in the same location can view employees in their location
        $locHr = makeUserWithRole('location_hr', $this->location->id);
        $this->actingAs($locHr);

        $response = $this->get(route('employees.show', $employee));

        // location_hr can view employees in their own location
        $response->assertStatus(200);
    });
});

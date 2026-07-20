<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Location;
use App\Models\Department;
use App\Models\Designation;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Employee::class);

        $query = Employee::query()
            ->with(['location', 'department', 'designation', 'reportingManager'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%");
            });
        }

        $employees = $query->paginate(50);

        return view('employees.index', [
            'employees' => $employees,
            'departments' => Department::where('is_active', true)->get(),
        ]);
    }

    /**
     * Show the form for creating a new employee.
     */
    public function create()
    {
        $this->authorize('create', Employee::class);

        return view('employees.form', [
            'locations' => Location::where('is_active', true)->get(),
            'departments' => Department::where('is_active', true)->get(),
            'designations' => Designation::where('is_active', true)->get(),
            'managers' => Employee::where('status', 'confirmed')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    /**
     * Store a newly created employee in storage.
     */
    public function store(StoreEmployeeRequest $request)
    {
        $this->authorize('create', Employee::class);

        $employee = Employee::create($request->validated());

        Log::info('Employee created', ['employee_id' => $employee->id, 'user_id' => auth()->id()]);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee created successfully.');
    }

    /**
     * Display the specified employee.
     */
    public function show(Employee $employee)
    {
        $this->authorize('view', $employee);

        return view('employees.show', [
            'employee' => $employee->load(['location', 'department', 'designation', 'reportingManager', 'lifecycleHistory']),
            'auditLog' => $employee->activities()->latest()->get(),
        ]);
    }

    /**
     * Show the form for editing the specified employee.
     */
    public function edit(Employee $employee)
    {
        $this->authorize('update', $employee);

        return view('employees.form', [
            'employee' => $employee,
            'locations' => Location::where('is_active', true)->get(),
            'departments' => Department::where('is_active', true)->get(),
            'designations' => Designation::where('is_active', true)->get(),
            'managers' => Employee::where('status', 'confirmed')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    /**
     * Update the specified employee in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $employee->update($request->validated());

        Log::info('Employee updated', ['employee_id' => $employee->id, 'user_id' => auth()->id()]);

        return redirect()->route('employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified employee from storage.
     */
    public function destroy(Employee $employee)
    {
        $this->authorize('delete', $employee);

        $employee->delete();

        Log::info('Employee deleted', ['employee_id' => $employee->id, 'user_id' => auth()->id()]);

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully.');
    }
}

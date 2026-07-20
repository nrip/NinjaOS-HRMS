@extends('layouts.app')

@section('title', 'Employees')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Employees</h1>
    <div>
        <a href="{{ route('employees.import.show') }}" class="btn btn-secondary">
            <i class="bi bi-upload"></i> Import
        </a>
        <a href="{{ route('employees.create') }}" class="btn btn-primary">
            <i class="bi bi-plus"></i> Add Employee
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" placeholder="Search by name, email, or code" value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="onboarding" {{ request('status') === 'onboarding' ? 'selected' : '' }}>Onboarding</option>
                    <option value="probation" {{ request('status') === 'probation' ? 'selected' : '' }}>Probation</option>
                    <option value="confirmed" {{ request('status') === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="transferred" {{ request('status') === 'transferred' ? 'selected' : '' }}>Transferred</option>
                    <option value="exit" {{ request('status') === 'exit' ? 'selected' : '' }}>Exit</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Employee Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Joining Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>
                            <code>{{ $employee->employee_code ?? 'N/A' }}</code>
                        </td>
                        <td>{{ $employee->full_name }}</td>
                        <td>{{ $employee->email }}</td>
                        <td>{{ $employee->department->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge badge-{{ $employee->status }}">
                                {{ ucfirst($employee->status) }}
                            </span>
                        </td>
                        <td>{{ $employee->date_of_joining->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('employees.show', $employee) }}" class="btn btn-sm btn-info">View</a>
                            <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-warning">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No employees found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $employees->links() }}
        </div>
    </div>
</div>
@endsection

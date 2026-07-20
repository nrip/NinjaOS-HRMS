@extends('layouts.app')

@section('title', isset($employee) ? 'Edit Employee' : 'Create Employee')

@section('content')
<div class="mb-4">
    <h1>{{ isset($employee) ? 'Edit Employee' : 'Create Employee' }}</h1>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ isset($employee) ? route('employees.update', $employee) : route('employees.store') }}">
            @csrf
            @if(isset($employee))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">First Name *</label>
                    <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $employee->first_name ?? '') }}" required>
                    @error('first_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Last Name *</label>
                    <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $employee->last_name ?? '') }}" required>
                    @error('last_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $employee->email ?? '') }}" required>
                    @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone *</label>
                    <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $employee->phone ?? '') }}" placeholder="10 digit number" required>
                    @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="location_id" class="form-label">Location *</label>
                    <select class="form-select @error('location_id') is-invalid @enderror" id="location_id" name="location_id" required>
                        <option value="">Select Location</option>
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ old('location_id', $employee->location_id ?? '') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                        @endforeach
                    </select>
                    @error('location_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="department_id" class="form-label">Department *</label>
                    <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id ?? '') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="designation_id" class="form-label">Designation *</label>
                    <select class="form-select @error('designation_id') is-invalid @enderror" id="designation_id" name="designation_id" required>
                        <option value="">Select Designation</option>
                        @foreach($designations as $des)
                        <option value="{{ $des->id }}" {{ old('designation_id', $employee->designation_id ?? '') == $des->id ? 'selected' : '' }}>{{ $des->name }}</option>
                        @endforeach
                    </select>
                    @error('designation_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="reporting_manager_id" class="form-label">Reporting Manager</label>
                    <select class="form-select @error('reporting_manager_id') is-invalid @enderror" id="reporting_manager_id" name="reporting_manager_id">
                        <option value="">Select Manager</option>
                        @foreach($managers as $mgr)
                        <option value="{{ $mgr->id }}" {{ old('reporting_manager_id', $employee->reporting_manager_id ?? '') == $mgr->id ? 'selected' : '' }}>{{ $mgr->first_name }} {{ $mgr->last_name }}</option>
                        @endforeach
                    </select>
                    @error('reporting_manager_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_of_birth" class="form-label">Date of Birth *</label>
                    <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $employee->date_of_birth?->format('Y-m-d') ?? '') }}" required>
                    @error('date_of_birth')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="gender" class="form-label">Gender *</label>
                    <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="male" {{ old('gender', $employee->gender ?? '') === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ old('gender', $employee->gender ?? '') === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ old('gender', $employee->gender ?? '') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('gender')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_of_joining" class="form-label">Date of Joining *</label>
                    <input type="date" class="form-control @error('date_of_joining') is-invalid @enderror" id="date_of_joining" name="date_of_joining" value="{{ old('date_of_joining', $employee->date_of_joining?->format('Y-m-d') ?? '') }}" required>
                    @error('date_of_joining')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="probation_end_date" class="form-label">Probation End Date</label>
                    <input type="date" class="form-control @error('probation_end_date') is-invalid @enderror" id="probation_end_date" name="probation_end_date" value="{{ old('probation_end_date', $employee->probation_end_date?->format('Y-m-d') ?? '') }}">
                    @error('probation_end_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="aadhaar" class="form-label">Aadhaar</label>
                    <input type="text" class="form-control @error('aadhaar') is-invalid @enderror" id="aadhaar" name="aadhaar" value="{{ old('aadhaar', $employee->aadhaar ?? '') }}" placeholder="12 digit Aadhaar">
                    @error('aadhaar')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="pan" class="form-label">PAN</label>
                    <input type="text" class="form-control @error('pan') is-invalid @enderror" id="pan" name="pan" value="{{ old('pan', $employee->pan ?? '') }}" placeholder="PAN format">
                    @error('pan')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="bank_account" class="form-label">Bank Account</label>
                    <input type="text" class="form-control @error('bank_account') is-invalid @enderror" id="bank_account" name="bank_account" value="{{ old('bank_account', $employee->bank_account ?? '') }}">
                    @error('bank_account')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label for="bank_name" class="form-label">Bank Name</label>
                    <input type="text" class="form-control @error('bank_name') is-invalid @enderror" id="bank_name" name="bank_name" value="{{ old('bank_name', $employee->bank_name ?? '') }}">
                    @error('bank_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ifsc_code" class="form-label">IFSC Code</label>
                    <input type="text" class="form-control @error('ifsc_code') is-invalid @enderror" id="ifsc_code" name="ifsc_code" value="{{ old('ifsc_code', $employee->ifsc_code ?? '') }}">
                    @error('ifsc_code')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">{{ isset($employee) ? 'Update' : 'Create' }} Employee</button>
                <a href="{{ route('employees.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

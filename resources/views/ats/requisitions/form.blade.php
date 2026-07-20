@extends('layouts.app')

@section('title', 'New Job Requisition')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">New Job Requisition</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('ats.requisitions.store') }}">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Location <span class="text-danger">*</span></label>
                                <select name="location_id" class="form-select @error('location_id') is-invalid @enderror" required>
                                    <option value="">Select Location</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>
                                            {{ $loc->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Department <span class="text-danger">*</span></label>
                                <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Designation <span class="text-danger">*</span></label>
                                <select name="designation_id" class="form-select @error('designation_id') is-invalid @enderror" required>
                                    <option value="">Select Designation</option>
                                    @foreach($designations as $desig)
                                        <option value="{{ $desig->id }}" {{ old('designation_id') == $desig->id ? 'selected' : '' }}>
                                            {{ $desig->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('designation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">No. of Positions <span class="text-danger">*</span></label>
                                <input type="number" name="number_of_positions" value="{{ old('number_of_positions', 1) }}"
                                    class="form-control @error('number_of_positions') is-invalid @enderror" min="1" required>
                                @error('number_of_positions')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Closing Date</label>
                                <input type="date" name="closing_date" value="{{ old('closing_date') }}"
                                    class="form-control @error('closing_date') is-invalid @enderror">
                                @error('closing_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Job Description <span class="text-danger">*</span></label>
                            <textarea name="job_description" rows="5"
                                class="form-control @error('job_description') is-invalid @enderror" required>{{ old('job_description') }}</textarea>
                            @error('job_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Required Skills <span class="text-danger">*</span></label>
                            <input type="text" name="required_skills" value="{{ old('required_skills') }}"
                                class="form-control @error('required_skills') is-invalid @enderror"
                                placeholder="e.g. PHP, Laravel, MySQL" required>
                            @error('required_skills')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save as Draft</button>
                            <a href="{{ route('ats.requisitions.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

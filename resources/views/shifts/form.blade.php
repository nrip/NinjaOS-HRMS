@extends('layouts.app')

@section('title', isset($shift->id) ? 'Edit Shift' : 'Create Shift')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ isset($shift->id) ? 'Edit Shift: ' . $shift->name : 'Create Shift' }}</h1>
        <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Shifts
        </a>
    </div>

    <div class="card" style="max-width: 600px;">
        <div class="card-body">
            <form method="POST" action="{{ isset($shift->id) ? route('shifts.update', $shift) : route('shifts.store') }}">
                @csrf
                @if(isset($shift->id))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">Shift Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $shift->name) }}" placeholder="e.g. Morning Shift, General Shift">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Start Time <span class="text-danger">*</span></label>
                        <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror"
                               value="{{ old('start_time', $shift->start_time) }}">
                        @error('start_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End Time <span class="text-danger">*</span></label>
                        <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror"
                               value="{{ old('end_time', $shift->end_time) }}">
                        @error('end_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Grace Period (minutes)</label>
                    <input type="number" name="grace_period_minutes" class="form-control" min="0" max="60"
                           value="{{ old('grace_period_minutes', $shift->grace_period_minutes ?? 15) }}">
                    <div class="form-text">How many minutes after shift start before marking as Late.</div>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_night_shift" id="isNightShift" value="1"
                               {{ old('is_night_shift', $shift->is_night_shift ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isNightShift">
                            <i class="bi bi-moon-stars"></i> Night Shift (crosses midnight)
                        </label>
                    </div>
                    <div class="form-text">Enable if the shift end time is on the next calendar day.</div>
                </div>

                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="isActive" value="1"
                               {{ old('is_active', $shift->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive">Active</label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        {{ isset($shift->id) ? 'Update Shift' : 'Create Shift' }}
                    </button>
                    <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

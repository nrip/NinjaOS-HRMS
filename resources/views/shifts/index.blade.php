@extends('layouts.app')

@section('title', 'Shift Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Shift Management</h1>
        @can('create', \App\Models\Shift::class)
        <a href="{{ route('shifts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add Shift
        </a>
        @endcan
    </div>

    <div class="card">
        <div class="card-body">
            <table id="shiftsTable" class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Night Shift</th>
                        <th>Grace Period</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $shift)
                    <tr>
                        <td><strong>{{ $shift->name }}</strong></td>
                        <td>{{ $shift->start_time }}</td>
                        <td>{{ $shift->end_time }}</td>
                        <td>
                            @if($shift->is_night_shift)
                                <span class="badge bg-dark"><i class="bi bi-moon-stars"></i> Night</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-sun"></i> Day</span>
                            @endif
                        </td>
                        <td>{{ $shift->grace_period_minutes ?? 0 }} min</td>
                        <td>
                            @if($shift->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('shifts.edit', $shift) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('shifts.destroy', $shift) }}" class="d-inline" onsubmit="return confirm('Delete this shift?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No shifts configured yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    $('#shiftsTable').DataTable({ ordering: true, searching: true });
});
</script>
@endpush

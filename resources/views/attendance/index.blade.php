@extends('layouts.app')

@section('title', 'Attendance')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Attendance Records</h1>
        <div>
            <a href="{{ route('attendance.dashboard') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-bar-chart-line"></i> Dashboard
            </a>
            @can('create', \App\Models\Attendance::class)
            <a href="{{ route('attendance.punch') }}" class="btn btn-primary">
                <i class="bi bi-fingerprint"></i> Manual Punch
            </a>
            @endcan
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ request('date', today()->format('Y-m-d')) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="present" @selected(request('status') === 'present')>Present</option>
                        <option value="absent" @selected(request('status') === 'absent')>Absent</option>
                        <option value="late" @selected(request('status') === 'late')>Late</option>
                        <option value="half_day" @selected(request('status') === 'half_day')>Half Day</option>
                        <option value="on_leave" @selected(request('status') === 'on_leave')>On Leave</option>
                        <option value="holiday" @selected(request('status') === 'holiday')>Holiday</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Regularization</label>
                    <select name="regularization_status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" @selected(request('regularization_status') === 'pending')>Pending Approval</option>
                        <option value="approved" @selected(request('regularization_status') === 'approved')>Approved</option>
                        <option value="rejected" @selected(request('regularization_status') === 'rejected')>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-success">
                <div class="card-body text-center py-2">
                    <div class="h4 text-success mb-0">{{ $summary['present'] ?? 0 }}</div>
                    <small class="text-muted">Present</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-danger">
                <div class="card-body text-center py-2">
                    <div class="h4 text-danger mb-0">{{ $summary['absent'] ?? 0 }}</div>
                    <small class="text-muted">Absent</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-warning">
                <div class="card-body text-center py-2">
                    <div class="h4 text-warning mb-0">{{ $summary['late'] ?? 0 }}</div>
                    <small class="text-muted">Late</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-info">
                <div class="card-body text-center py-2">
                    <div class="h4 text-info mb-0">{{ $summary['on_leave'] ?? 0 }}</div>
                    <small class="text-muted">On Leave</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-secondary">
                <div class="card-body text-center py-2">
                    <div class="h4 text-secondary mb-0">{{ $summary['ot_hours'] ?? 0 }}</div>
                    <small class="text-muted">OT Hours</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-primary">
                <div class="card-body text-center py-2">
                    <div class="h4 text-primary mb-0">{{ $summary['pending_regularization'] ?? 0 }}</div>
                    <small class="text-muted">Pending Reg.</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Attendance Table --}}
    <div class="card">
        <div class="card-body">
            <table id="attendanceTable" class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Shift</th>
                        <th>In Time</th>
                        <th>Out Time</th>
                        <th>Hours</th>
                        <th>OT Hours</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Regularization</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                    <tr>
                        <td>
                            <a href="{{ route('employees.show', $attendance->employee_id) }}">
                                {{ $attendance->employee?->employee_code }}
                            </a>
                        </td>
                        <td>{{ $attendance->attendance_date->format('d M Y') }}</td>
                        <td>{{ $attendance->shift?->name ?? '—' }}</td>
                        <td>{{ $attendance->in_time ? $attendance->in_time->format('H:i') : '—' }}</td>
                        <td>{{ $attendance->out_time ? $attendance->out_time->format('H:i') : '—' }}</td>
                        <td>{{ $attendance->total_hours ? number_format($attendance->total_hours, 1) . 'h' : '—' }}</td>
                        <td>
                            @if($attendance->ot_hours > 0)
                                <span class="badge bg-warning text-dark">{{ number_format($attendance->ot_hours, 1) }}h</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @php
                                $statusMap = [
                                    'present'  => 'success',
                                    'absent'   => 'danger',
                                    'late'     => 'warning',
                                    'half_day' => 'info',
                                    'on_leave' => 'secondary',
                                    'holiday'  => 'primary',
                                ];
                                $color = $statusMap[$attendance->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $attendance->status)) }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">{{ ucfirst($attendance->punch_source ?? 'manual') }}</span>
                        </td>
                        <td>
                            @if($attendance->regularization_status === 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($attendance->regularization_status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($attendance->regularization_status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('attendance.show', $attendance) }}" class="btn btn-xs btn-outline-primary">View</a>
                            @if(!$attendance->regularization_status || $attendance->regularization_status === 'rejected')
                            <button class="btn btn-xs btn-outline-warning" data-bs-toggle="modal" data-bs-target="#regularizeModal" data-id="{{ $attendance->id }}">
                                Regularize
                            </button>
                            @endif
                            @can('approveRegularization', $attendance)
                            @if($attendance->regularization_status === 'pending')
                            <form method="POST" action="{{ route('attendance.approve-regularization', $attendance) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-xs btn-success">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('attendance.reject-regularization', $attendance) }}" class="d-inline">
                                @csrf
                                <button class="btn btn-xs btn-danger">Reject</button>
                            </form>
                            @endif
                            @endcan
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center text-muted py-4">No attendance records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $attendances->links() }}
        </div>
    </div>
</div>

{{-- Regularization Modal --}}
<div class="modal fade" id="regularizeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Regularization</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="regularizeForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for Regularization <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Explain why you need this attendance record regularized..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTables
    if (document.getElementById('attendanceTable')) {
        $('#attendanceTable').DataTable({
            paging: false,
            searching: true,
            ordering: true,
            info: false,
        });
    }

    // Set regularization form action dynamically
    const regularizeModal = document.getElementById('regularizeModal');
    if (regularizeModal) {
        regularizeModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const attendanceId = button.getAttribute('data-id');
            document.getElementById('regularizeForm').action = `/attendance/${attendanceId}/regularize`;
        });
    }
});
</script>
@endpush

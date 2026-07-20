@extends('layouts.app')

@section('title', 'Job Requisitions')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Job Requisitions</h1>
        <a href="{{ route('ats.requisitions.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Requisition
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="requisitionsTable">
                <thead class="table-light">
                    <tr>
                        <th>Requisition ID</th>
                        <th>Position</th>
                        <th>Location</th>
                        <th>Department</th>
                        <th>Positions</th>
                        <th>Status</th>
                        <th>Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requisitions as $req)
                    <tr>
                        <td><code>{{ substr($req->requisition_id, 0, 8) }}...</code></td>
                        <td>{{ $req->designation->name ?? '—' }}</td>
                        <td>{{ $req->location->name ?? '—' }}</td>
                        <td>{{ $req->department->name ?? '—' }}</td>
                        <td>{{ $req->number_of_positions }}</td>
                        <td>
                            @php
                                $statusColors = [
                                    'draft'              => 'secondary',
                                    'pending_location_hr' => 'warning',
                                    'pending_central_hr' => 'info',
                                    'open'               => 'success',
                                    'closed'             => 'dark',
                                    'rejected'           => 'danger',
                                    'cancelled'          => 'secondary',
                                ];
                                $color = $statusColors[$req->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $color }}">{{ str_replace('_', ' ', $req->status) }}</span>
                        </td>
                        <td>{{ $req->posting_date?->format('d M Y') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('ats.requisitions.show', $req) }}" class="btn btn-sm btn-outline-primary">View</a>
                            @if($req->status === 'open')
                                <a href="{{ route('ats.kanban.board', $req) }}" class="btn btn-sm btn-outline-success">Kanban</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No requisitions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requisitions->hasPages())
        <div class="card-footer">
            {{ $requisitions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

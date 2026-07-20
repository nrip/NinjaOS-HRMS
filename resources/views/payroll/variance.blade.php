@extends('layouts.app')

@section('title', 'Payroll Variance Report')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold">Payroll Variance Report</h4>
            <small class="text-muted">{{ date('F', mktime(0,0,0,$month,1)) }} {{ $year }} — Threshold: {{ $report['threshold_percent'] }}%</small>
        </div>
        <a href="{{ route('payroll.index', ['location_id'=>$locationId,'month'=>$month,'year'=>$year]) }}"
           class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Payroll
        </a>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-primary">{{ $report['total_employees'] }}</div>
                    <div class="text-muted small">Total Employees</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-warning">{{ $report['flagged_count'] }}</div>
                    <div class="text-muted small">Flagged (>{{ $report['threshold_percent'] }}% change)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-danger">{{ $report['unacknowledged_count'] }}</div>
                    <div class="text-muted small">Pending Acknowledgment</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold {{ $report['can_finalize'] ? 'text-success' : 'text-danger' }}">
                        {{ $report['can_finalize'] ? 'Yes' : 'No' }}
                    </div>
                    <div class="text-muted small">Can Finalize</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Variance Records Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">All Records (sorted by variance %)</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th class="text-end">Prev Net Pay</th>
                            <th class="text-end">Current Net Pay</th>
                            <th class="text-end">Change</th>
                            <th class="text-center">Variance %</th>
                            <th class="text-center">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['records'] as $record)
                        <tr class="{{ $record->variance_flag && !$record->variance_acknowledged ? 'table-warning' : '' }}">
                            <td>
                                <div class="fw-semibold">{{ $record->employee?->full_name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $record->employee_code }}</small>
                            </td>
                            <td class="text-end">
                                {{ $record->prev_net_pay ? '₹' . number_format($record->prev_net_pay, 0) : '—' }}
                            </td>
                            <td class="text-end fw-bold">₹{{ number_format($record->net_pay, 0) }}</td>
                            <td class="text-end {{ $record->net_pay > ($record->prev_net_pay ?? $record->net_pay) ? 'text-success' : 'text-danger' }}">
                                @if($record->prev_net_pay)
                                    {{ $record->net_pay > $record->prev_net_pay ? '+' : '' }}₹{{ number_format($record->net_pay - $record->prev_net_pay, 0) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-center">
                                @if($record->variance_flag)
                                    <span class="badge bg-danger fs-6">{{ $record->variance_percent }}%</span>
                                @elseif($record->variance_percent !== null)
                                    <span class="badge bg-success">{{ $record->variance_percent }}%</span>
                                @else
                                    <span class="text-muted">New</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($record->variance_flag)
                                    @if($record->variance_acknowledged)
                                        <span class="badge bg-success">Acknowledged</span>
                                    @else
                                        <span class="badge bg-danger">Needs Acknowledgment</span>
                                    @endif
                                @else
                                    <span class="badge bg-light text-dark">OK</span>
                                @endif
                            </td>
                            <td>
                                @if($record->variance_flag && !$record->variance_acknowledged)
                                    @can('acknowledgeVariance', $record)
                                    <form method="POST"
                                          action="{{ route('payroll.acknowledge-variance', $record) }}"
                                          class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning"
                                            onclick="return confirm('Acknowledge this variance?')">
                                            Acknowledge
                                        </button>
                                    </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

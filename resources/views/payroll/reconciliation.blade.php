@extends('layouts.app')

@section('title', 'Parallel Run Reconciliation')

@section('content')
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold">Parallel Run Reconciliation</h4>
            <small class="text-muted">{{ date('F', mktime(0,0,0,$month,1)) }} {{ $year }} — NinjaOS vs Legacy System</small>
        </div>
        <a href="{{ route('payroll.index', ['location_id'=>$locationId,'month'=>$month,'year'=>$year]) }}"
           class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Payroll
        </a>
    </div>

    {{-- Summary --}}
    @php
        $totalVariance   = $records->sum('reconciliation_variance');
        $uncleared       = $records->where('reconciliation_cleared', false)->count();
        $cleared         = $records->where('reconciliation_cleared', true)->count();
        $withinTolerance = $records->filter(fn($r) => abs($r->reconciliation_variance ?? 0) <= 1)->count();
    @endphp

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-primary">{{ $records->count() }}</div>
                    <div class="text-muted small">Employees in Parallel Run</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-success">{{ $withinTolerance }}</div>
                    <div class="text-muted small">Within ₹1 Tolerance</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold text-danger">{{ $uncleared }}</div>
                    <div class="text-muted small">Uncleared Variances</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <div class="fs-2 fw-bold {{ abs($totalVariance) < 100 ? 'text-success' : 'text-danger' }}">
                        ₹{{ number_format(abs($totalVariance), 0) }}
                    </div>
                    <div class="text-muted small">Total Absolute Variance</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reconciliation Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">
            Reconciliation Detail
            <small class="text-muted ms-2">Tolerance: ±₹1 (rounding differences)</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th class="text-end">Legacy Net Pay</th>
                            <th class="text-end">NinjaOS Net Pay</th>
                            <th class="text-end">Variance</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records->sortByDesc(fn($r) => abs($r->reconciliation_variance ?? 0)) as $record)
                        @php
                            $variance = $record->reconciliation_variance ?? 0;
                            $isWithinTolerance = abs($variance) <= 1;
                        @endphp
                        <tr class="{{ !$isWithinTolerance && !$record->reconciliation_cleared ? 'table-danger' : '' }}">
                            <td>
                                <div class="fw-semibold">{{ $record->employee?->full_name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $record->employee_code }}</small>
                            </td>
                            <td class="text-end">₹{{ number_format($record->legacy_net_pay, 2) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($record->net_pay, 2) }}</td>
                            <td class="text-end {{ abs($variance) > 1 ? 'text-danger fw-bold' : 'text-success' }}">
                                {{ $variance >= 0 ? '+' : '' }}₹{{ number_format($variance, 2) }}
                            </td>
                            <td class="text-center">
                                @if($record->reconciliation_cleared)
                                    <span class="badge bg-success">Cleared</span>
                                @elseif($isWithinTolerance)
                                    <span class="badge bg-success">Within Tolerance</span>
                                @else
                                    <span class="badge bg-danger">Investigate</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No parallel run data found. Upload legacy payroll data to begin reconciliation.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

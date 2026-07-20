@extends('layouts.app')

@section('title', 'Payroll — ' . date('F', mktime(0,0,0,$month,1)) . ' ' . $year)

@section('content')
<div class="container-fluid py-4">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold">Payroll Processing</h4>
            <small class="text-muted">{{ date('F', mktime(0,0,0,$month,1)) }} {{ $year }}</small>
        </div>
        <div class="d-flex gap-2">
            @can('process', \App\Models\PayrollRecord::class)
            <form method="POST" action="{{ route('payroll.process') }}" class="d-inline">
                @csrf
                <input type="hidden" name="location_id" value="{{ $locationId }}">
                <input type="hidden" name="month" value="{{ $month }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <button type="submit" class="btn btn-primary btn-sm"
                    onclick="return confirm('Process payroll for all active employees?')">
                    <i class="fas fa-play me-1"></i> Process Payroll
                </button>
            </form>
            @endcan
            <a href="{{ route('payroll.variance', ['location_id'=>$locationId,'month'=>$month,'year'=>$year]) }}"
               class="btn btn-outline-warning btn-sm">
                <i class="fas fa-chart-bar me-1"></i> Variance Report
                @if($varianceReport['unacknowledged_count'] > 0)
                    <span class="badge bg-danger ms-1">{{ $varianceReport['unacknowledged_count'] }}</span>
                @endif
            </a>
        </div>
    </div>

    {{-- Period Selector --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('payroll.index') }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">Month</label>
                    <select name="month" class="form-select form-select-sm">
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" @selected($m == $month)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">Year</label>
                    <select name="year" class="form-select form-select-sm">
                        @for($y = now()->year - 2; $y <= now()->year + 1; $y++)
                            <option value="{{ $y }}" @selected($y == $year)>{{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <input type="hidden" name="location_id" value="{{ $locationId }}">
                <div class="col-auto">
                    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Variance Alert --}}
    @if($varianceReport['unacknowledged_count'] > 0)
    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <div>
            <strong>{{ $varianceReport['unacknowledged_count'] }} variance(s) require acknowledgment</strong>
            before payroll can be finalized.
            <a href="{{ route('payroll.variance', ['location_id'=>$locationId,'month'=>$month,'year'=>$year]) }}"
               class="alert-link ms-1">Review now →</a>
        </div>
    </div>
    @endif

    {{-- Payroll Records Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Employee</th>
                            <th class="text-end">Gross</th>
                            <th class="text-end">LWP Days</th>
                            <th class="text-end">PF</th>
                            <th class="text-end">ESI</th>
                            <th class="text-end">PT</th>
                            <th class="text-end">TDS</th>
                            <th class="text-end">Net Pay</th>
                            <th class="text-center">Variance</th>
                            <th class="text-center">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                        <tr class="{{ $record->variance_flag && !$record->variance_acknowledged ? 'table-warning' : '' }}">
                            <td>
                                <div class="fw-semibold">{{ $record->employee?->full_name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $record->employee_code }}</small>
                            </td>
                            <td class="text-end">₹{{ number_format($record->gross_salary, 0) }}</td>
                            <td class="text-end">{{ $record->lwp_days > 0 ? $record->lwp_days : '—' }}</td>
                            <td class="text-end">₹{{ number_format($record->employee_pf, 0) }}</td>
                            <td class="text-end">₹{{ number_format($record->employee_esi, 0) }}</td>
                            <td class="text-end">₹{{ number_format($record->professional_tax, 0) }}</td>
                            <td class="text-end">₹{{ number_format($record->monthly_tds, 0) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($record->net_pay, 0) }}</td>
                            <td class="text-center">
                                @if($record->variance_flag)
                                    @if($record->variance_acknowledged)
                                        <span class="badge bg-success">Acknowledged</span>
                                    @else
                                        <span class="badge bg-danger">{{ $record->variance_percent }}%</span>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ match($record->status) {
                                    'draft'     => 'secondary',
                                    'approved'  => 'primary',
                                    'finalized' => 'success',
                                    'rejected'  => 'danger',
                                    default     => 'light text-dark',
                                } }}">{{ ucfirst($record->status) }}</span>
                            </td>
                            <td>
                                <a href="{{ route('payroll.show', $record) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                No payroll records found. Click "Process Payroll" to generate.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($records->hasPages())
        <div class="card-footer">{{ $records->links() }}</div>
        @endif
    </div>

    {{-- Finalize Button --}}
    @if($records->count() > 0 && $varianceReport['can_finalize'])
    <div class="mt-3 text-end">
        @can('finalize', \App\Models\PayrollRecord::class)
        <form method="POST" action="{{ route('payroll.finalize') }}">
            @csrf
            <input type="hidden" name="location_id" value="{{ $locationId }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <button type="submit" class="btn btn-success"
                onclick="return confirm('Finalize payroll? This action cannot be undone.')">
                <i class="fas fa-lock me-1"></i> Finalize Payroll
            </button>
        </form>
        @endcan
    </div>
    @endif

</div>
@endsection

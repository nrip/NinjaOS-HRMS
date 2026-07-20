@extends('layouts.app')

@section('title', 'Payslip — ' . $record->employee_code . ' — ' . date('F', mktime(0,0,0,$record->payroll_month,1)) . ' ' . $record->payroll_year)

@section('content')
<div class="container py-4" style="max-width: 800px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('payroll.index', ['month'=>$record->payroll_month,'year'=>$record->payroll_year,'location_id'=>$record->location_id]) }}"
           class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back
        </a>
        <a href="{{ route('payroll.payslip.pdf', $record) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-download me-1"></i> Download PDF
        </a>
    </div>

    {{-- Payslip Card --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            {{-- Company Header --}}
            <div class="row mb-4 pb-3 border-bottom">
                <div class="col">
                    <h5 class="fw-bold mb-0">{{ config('app.name') }}</h5>
                    <small class="text-muted">{{ $record->location?->name ?? '' }}</small>
                </div>
                <div class="col text-end">
                    <h6 class="fw-bold text-primary mb-0">PAYSLIP</h6>
                    <small class="text-muted">{{ date('F Y', mktime(0,0,0,$record->payroll_month,1,$record->payroll_year)) }}</small>
                </div>
            </div>

            {{-- Employee Details --}}
            <div class="row mb-4">
                <div class="col-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted small">Employee Name</td><td class="fw-semibold">{{ $record->employee?->full_name ?? 'N/A' }}</td></tr>
                        <tr><td class="text-muted small">Employee Code</td><td>{{ $record->employee_code }}</td></tr>
                        <tr><td class="text-muted small">Designation</td><td>{{ $record->employee?->designation ?? '—' }}</td></tr>
                        <tr><td class="text-muted small">Department</td><td>{{ $record->employee?->department?->name ?? '—' }}</td></tr>
                    </table>
                </div>
                <div class="col-6">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted small">Location</td><td>{{ $record->location?->name ?? '—' }}</td></tr>
                        <tr><td class="text-muted small">Date of Joining</td><td>{{ $record->employee?->date_of_joining ? \Carbon\Carbon::parse($record->employee->date_of_joining)->format('d M Y') : '—' }}</td></tr>
                        <tr><td class="text-muted small">Pay Period</td><td>{{ date('F Y', mktime(0,0,0,$record->payroll_month,1,$record->payroll_year)) }}</td></tr>
                        <tr><td class="text-muted small">Tax Regime</td><td>{{ strtoupper($record->tax_regime) }} Regime</td></tr>
                    </table>
                </div>
            </div>

            {{-- Earnings & Deductions --}}
            <div class="row">
                {{-- Earnings --}}
                <div class="col-6">
                    <h6 class="fw-bold text-success mb-2">Earnings</h6>
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Component</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            <tr><td>Basic Salary</td><td class="text-end">₹{{ number_format($record->basic_salary, 2) }}</td></tr>
                            <tr><td>HRA</td><td class="text-end">₹{{ number_format($record->hra, 2) }}</td></tr>
                            <tr><td>Special Allowance</td><td class="text-end">₹{{ number_format($record->special_allowance, 2) }}</td></tr>
                            @if($record->ot_earnings > 0)
                            <tr><td>OT Earnings</td><td class="text-end">₹{{ number_format($record->ot_earnings, 2) }}</td></tr>
                            @endif
                            @if($record->encashment_payout > 0)
                            <tr><td>Leave Encashment</td><td class="text-end">₹{{ number_format($record->encashment_payout, 2) }}</td></tr>
                            @endif
                            @if($record->lwp_days > 0)
                            <tr class="text-danger"><td>LWP Deduction ({{ $record->lwp_days }} days)</td><td class="text-end">-₹{{ number_format($record->lwp_deduction, 2) }}</td></tr>
                            @endif
                        </tbody>
                        <tfoot class="table-light">
                            <tr><th>Effective Gross</th><th class="text-end">₹{{ number_format($record->effective_gross, 2) }}</th></tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Deductions --}}
                <div class="col-6">
                    <h6 class="fw-bold text-danger mb-2">Deductions</h6>
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Component</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            <tr><td>Provident Fund (12%)</td><td class="text-end">₹{{ number_format($record->employee_pf, 2) }}</td></tr>
                            @if($record->employee_esi > 0)
                            <tr><td>ESI (0.75%)</td><td class="text-end">₹{{ number_format($record->employee_esi, 2) }}</td></tr>
                            @endif
                            @if($record->professional_tax > 0)
                            <tr><td>Professional Tax</td><td class="text-end">₹{{ number_format($record->professional_tax, 2) }}</td></tr>
                            @endif
                            @if($record->monthly_tds > 0)
                            <tr><td>TDS ({{ strtoupper($record->tax_regime) }} Regime)</td><td class="text-end">₹{{ number_format($record->monthly_tds, 2) }}</td></tr>
                            @endif
                            @if($record->notice_pay_recovery > 0)
                            <tr><td>Notice Pay Recovery</td><td class="text-end">₹{{ number_format($record->notice_pay_recovery, 2) }}</td></tr>
                            @endif
                        </tbody>
                        <tfoot class="table-light">
                            <tr><th>Total Deductions</th><th class="text-end">₹{{ number_format($record->total_deductions, 2) }}</th></tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Net Pay --}}
            <div class="row mt-3">
                <div class="col-12">
                    <div class="bg-primary text-white rounded p-3 d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-5">Net Pay</span>
                        <span class="fw-bold fs-4">₹{{ number_format($record->net_pay, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Employer Contributions (informational) --}}
            <div class="row mt-3">
                <div class="col-12">
                    <small class="text-muted">
                        <strong>Employer Contributions (not deducted from salary):</strong>
                        PF: ₹{{ number_format($record->employer_pf, 2) }} |
                        ESI: ₹{{ number_format($record->employer_esi, 2) }}
                    </small>
                </div>
            </div>

            {{-- Footer --}}
            <div class="row mt-4 pt-3 border-top">
                <div class="col text-center">
                    <small class="text-muted">This is a computer-generated payslip and does not require a signature.</small>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection

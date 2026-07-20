<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip — {{ $record->employee_code }} — {{ date('F Y', mktime(0,0,0,$record->payroll_month,1,$record->payroll_year)) }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #0d6efd; padding-bottom: 10px; margin-bottom: 15px; }
        .company-name { font-size: 16px; font-weight: bold; color: #0d6efd; }
        .payslip-title { font-size: 14px; font-weight: bold; text-align: right; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th, td { padding: 5px 8px; text-align: left; border: 1px solid #dee2e6; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .net-pay-row { background-color: #0d6efd; color: white; font-size: 13px; font-weight: bold; }
        .section-title { font-size: 12px; font-weight: bold; margin: 10px 0 5px 0; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #dee2e6; text-align: center; font-size: 9px; color: #6c757d; }
        .row { display: flex; gap: 10px; }
        .col { flex: 1; }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div style="display:flex; justify-content:space-between;">
            <div>
                <div class="company-name">{{ config('app.name') }}</div>
                <div>{{ $record->location?->name ?? '' }}</div>
            </div>
            <div class="text-right">
                <div class="payslip-title">PAYSLIP</div>
                <div>{{ date('F Y', mktime(0,0,0,$record->payroll_month,1,$record->payroll_year)) }}</div>
            </div>
        </div>
    </div>

    {{-- Employee Details --}}
    <table>
        <tr>
            <td><strong>Employee Name</strong></td>
            <td>{{ $record->employee?->full_name ?? 'N/A' }}</td>
            <td><strong>Employee Code</strong></td>
            <td>{{ $record->employee_code }}</td>
        </tr>
        <tr>
            <td><strong>Designation</strong></td>
            <td>{{ $record->employee?->designation ?? '—' }}</td>
            <td><strong>Department</strong></td>
            <td>{{ $record->employee?->department?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Date of Joining</strong></td>
            <td>{{ $record->employee?->date_of_joining ? \Carbon\Carbon::parse($record->employee->date_of_joining)->format('d M Y') : '—' }}</td>
            <td><strong>Tax Regime</strong></td>
            <td>{{ strtoupper($record->tax_regime) }} Regime</td>
        </tr>
    </table>

    {{-- Earnings & Deductions --}}
    <div style="display:flex; gap:10px;">
        <div style="flex:1;">
            <div class="section-title">Earnings</div>
            <table>
                <thead><tr><th>Component</th><th class="text-right">Amount (₹)</th></tr></thead>
                <tbody>
                    <tr><td>Basic Salary</td><td class="text-right">{{ number_format($record->basic_salary, 2) }}</td></tr>
                    <tr><td>HRA</td><td class="text-right">{{ number_format($record->hra, 2) }}</td></tr>
                    <tr><td>Special Allowance</td><td class="text-right">{{ number_format($record->special_allowance, 2) }}</td></tr>
                    @if($record->ot_earnings > 0)
                    <tr><td>OT Earnings</td><td class="text-right">{{ number_format($record->ot_earnings, 2) }}</td></tr>
                    @endif
                    @if($record->encashment_payout > 0)
                    <tr><td>Leave Encashment</td><td class="text-right">{{ number_format($record->encashment_payout, 2) }}</td></tr>
                    @endif
                    @if($record->lwp_days > 0)
                    <tr><td>LWP ({{ $record->lwp_days }} days)</td><td class="text-right">-{{ number_format($record->lwp_deduction, 2) }}</td></tr>
                    @endif
                </tbody>
                <tfoot><tr><th>Effective Gross</th><th class="text-right">{{ number_format($record->effective_gross, 2) }}</th></tr></tfoot>
            </table>
        </div>
        <div style="flex:1;">
            <div class="section-title">Deductions</div>
            <table>
                <thead><tr><th>Component</th><th class="text-right">Amount (₹)</th></tr></thead>
                <tbody>
                    <tr><td>Provident Fund (12%)</td><td class="text-right">{{ number_format($record->employee_pf, 2) }}</td></tr>
                    @if($record->employee_esi > 0)
                    <tr><td>ESI (0.75%)</td><td class="text-right">{{ number_format($record->employee_esi, 2) }}</td></tr>
                    @endif
                    @if($record->professional_tax > 0)
                    <tr><td>Professional Tax</td><td class="text-right">{{ number_format($record->professional_tax, 2) }}</td></tr>
                    @endif
                    @if($record->monthly_tds > 0)
                    <tr><td>TDS</td><td class="text-right">{{ number_format($record->monthly_tds, 2) }}</td></tr>
                    @endif
                    @if($record->notice_pay_recovery > 0)
                    <tr><td>Notice Pay Recovery</td><td class="text-right">{{ number_format($record->notice_pay_recovery, 2) }}</td></tr>
                    @endif
                </tbody>
                <tfoot><tr><th>Total Deductions</th><th class="text-right">{{ number_format($record->total_deductions, 2) }}</th></tr></tfoot>
            </table>
        </div>
    </div>

    {{-- Net Pay --}}
    <table style="margin-top:10px;">
        <tr class="net-pay-row">
            <td colspan="3"><strong>NET PAY</strong></td>
            <td class="text-right"><strong>₹ {{ number_format($record->net_pay, 2) }}</strong></td>
        </tr>
    </table>

    {{-- Employer Contributions --}}
    <div style="font-size:9px; color:#6c757d; margin-top:8px;">
        Employer Contributions (not deducted from salary):
        PF: ₹{{ number_format($record->employer_pf, 2) }} |
        ESI: ₹{{ number_format($record->employer_esi, 2) }}
    </div>

    <div class="footer">
        This is a computer-generated payslip and does not require a signature. | {{ config('app.name') }}
    </div>

</body>
</html>

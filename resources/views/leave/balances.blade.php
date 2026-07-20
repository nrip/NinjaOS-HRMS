@extends('layouts.app')

@section('title', 'Leave Balances')

@section('content')
<div class="container mx-auto px-4 py-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Leave Balances — {{ $year }}</h1>
        <a href="{{ route('leave.create') }}"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            + Apply for Leave
        </a>
    </div>

    {{-- Real-Time Balance Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        @foreach(['EL' => 'Earned Leave', 'CL' => 'Casual Leave', 'SL' => 'Sick Leave', 'CO' => 'Comp Off'] as $code => $label)
            @php $bal = $balances[$code] ?? null; @endphp
            <div class="bg-white rounded-xl shadow p-4">
                <div class="text-xs font-medium text-gray-500 uppercase mb-1">{{ $label }}</div>
                <div class="text-3xl font-bold text-indigo-700">{{ $bal ? number_format((float)$bal->closing_balance, 1) : '0.0' }}</div>
                <div class="text-xs text-gray-400 mt-1">
                    Opening: {{ $bal ? number_format((float)$bal->opening_balance, 1) : '0.0' }} |
                    Accrued: {{ $bal ? number_format((float)$bal->accrued, 1) : '0.0' }} |
                    Availed: {{ $bal ? number_format((float)$bal->availed, 1) : '0.0' }}
                </div>
                @if($bal && $bal->pending > 0)
                    <div class="text-xs text-yellow-600 mt-1">Pending: {{ number_format((float)$bal->pending, 1) }} days</div>
                @endif
                @if($bal && $bal->expiry_date)
                    <div class="text-xs text-red-500 mt-1">Expires: {{ $bal->expiry_date->format('d M Y') }}</div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Full Balance Table --}}
    <div class="bg-white rounded-xl shadow overflow-hidden mb-8">
        <div class="px-4 py-3 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-700">All Leave Types — {{ $year }}</h2>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Opening</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Accrued</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Availed</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Pending</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Available</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($balances as $code => $bal)
                <tr>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ $code }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-600">{{ number_format((float)$bal->opening_balance, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-green-600">+{{ number_format((float)$bal->accrued, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-red-600">-{{ number_format((float)$bal->availed, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-yellow-600">{{ number_format((float)$bal->pending, 2) }}</td>
                    <td class="px-4 py-3 text-sm text-right font-bold text-indigo-700">{{ number_format((float)$bal->closing_balance, 2) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-6 text-center text-gray-400 text-sm">No balance records for {{ $year }}.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 12-Month Projection Chart --}}
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-base font-semibold text-gray-700 mb-4">12-Month Earned Leave Projection</h2>
        <canvas id="projectionChart" height="80"></canvas>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const projection = @json($projection['EL'] ?? []);
    if (!projection.length) return;

    const labels  = projection.map(m => m.month);
    const data    = projection.map(m => m.projected_balance);
    const accrual = projection.map(m => m.accrual);

    new Chart(document.getElementById('projectionChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Projected EL Balance',
                    data,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79,70,229,0.08)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 4,
                },
                {
                    label: 'Monthly Accrual',
                    data: accrual,
                    borderColor: '#10b981',
                    backgroundColor: 'transparent',
                    borderDash: [4, 4],
                    tension: 0.3,
                    pointRadius: 3,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: ctx => `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)} days`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Days' }
                }
            }
        }
    });
});
</script>
@endpush

@extends('layouts.app')

@section('title', 'My Leave Applications')

@section('content')
<div class="container mx-auto px-4 py-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">My Leave Applications</h1>
        <a href="{{ route('leave.create') }}"
           class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
            + Apply for Leave
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Applications Table --}}
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($applications as $app)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                        {{ config('nexusos.leave_types.' . strtolower($app->leave_type), $app->leave_type) }}
                        @if($app->is_half_day)
                            <span class="ml-1 text-xs text-indigo-600">({{ $app->half_day_session === 'first_half' ? '1st Half' : '2nd Half' }})</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $app->from_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $app->to_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $app->number_of_days }}</td>
                    <td class="px-4 py-3">
                        @php
                            $statusClasses = [
                                'pending_approval' => 'bg-yellow-100 text-yellow-800',
                                'approved'         => 'bg-green-100 text-green-800',
                                'rejected'         => 'bg-red-100 text-red-800',
                                'cancelled'        => 'bg-gray-100 text-gray-600',
                                'draft'            => 'bg-blue-100 text-blue-800',
                            ];
                            $cls = $statusClasses[$app->status] ?? 'bg-gray-100 text-gray-600';
                        @endphp
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $cls }}">
                            {{ ucwords(str_replace('_', ' ', $app->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $app->reason }}">
                        {{ Str::limit($app->reason, 50) }}
                    </td>
                    <td class="px-4 py-3 text-sm">
                        @if($app->canBeCancelled())
                            <form method="POST" action="{{ route('leave.cancel', $app) }}" class="inline"
                                  onsubmit="return confirm('Cancel this leave application?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-red-600 hover:underline text-xs">Cancel</button>
                            </form>
                        @else
                            <span class="text-gray-400 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">
                        No leave applications found. <a href="{{ route('leave.create') }}" class="text-indigo-600 hover:underline">Apply now</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $applications->links() }}
    </div>

</div>
@endsection

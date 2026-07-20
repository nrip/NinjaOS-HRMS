@extends('layouts.app')

@section('title', 'Leave Approvals')

@section('content')
<div class="container mx-auto px-4 py-6">

    <h1 class="text-2xl font-bold text-gray-800 mb-6">Pending Leave Approvals</h1>

    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">From</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">To</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reason</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($applications as $app)
                <tr class="hover:bg-gray-50" x-data="{ showComments: false }">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                        {{ $app->employee?->first_name }} {{ $app->employee?->last_name }}
                        <div class="text-xs text-gray-400">{{ $app->employee?->employee_code }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $app->employee?->location?->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">
                        {{ $app->leave_type }}
                        @if($app->is_half_day)
                            <span class="text-xs text-indigo-600">(½)</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $app->from_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $app->to_date->format('d M Y') }}</td>
                    <td class="px-4 py-3 text-sm font-semibold text-gray-800">{{ $app->number_of_days }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate" title="{{ $app->reason }}">
                        {{ Str::limit($app->reason, 40) }}
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <div class="flex flex-col gap-2">
                            {{-- Approve --}}
                            <form method="POST" action="{{ route('leave.approve', $app) }}"
                                  onsubmit="return confirm('Approve this leave?')">
                                @csrf @method('PATCH')
                                <input type="hidden" name="comments" value="Approved">
                                <button type="submit"
                                        class="w-full px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                                    Approve
                                </button>
                            </form>

                            {{-- Reject with comments --}}
                            <div x-show="!showComments">
                                <button @click="showComments = true"
                                        class="w-full px-3 py-1 bg-red-100 text-red-700 text-xs rounded hover:bg-red-200">
                                    Reject
                                </button>
                            </div>
                            <div x-show="showComments" x-cloak>
                                <form method="POST" action="{{ route('leave.reject', $app) }}">
                                    @csrf @method('PATCH')
                                    <textarea name="comments" rows="2" placeholder="Reason for rejection..."
                                              class="w-full border border-gray-300 rounded text-xs px-2 py-1 mb-1" required></textarea>
                                    <div class="flex gap-1">
                                        <button type="submit"
                                                class="flex-1 px-2 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                                            Confirm Reject
                                        </button>
                                        <button type="button" @click="showComments = false"
                                                class="flex-1 px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300">
                                            Cancel
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-4 py-8 text-center text-gray-400 text-sm">
                        No pending leave applications.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $applications->links() }}
    </div>

</div>
@endsection

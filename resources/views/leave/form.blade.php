@extends('layouts.app')

@section('title', 'Apply for Leave')

@section('content')
<div class="container mx-auto px-4 py-6 max-w-2xl">

    <div class="flex items-center mb-6">
        <a href="{{ route('leave.index') }}" class="text-indigo-600 hover:underline text-sm mr-3">&larr; Back</a>
        <h1 class="text-2xl font-bold text-gray-800">Apply for Leave</h1>
    </div>

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-lg">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow p-6">
        <form method="POST" action="{{ route('leave.store') }}" x-data="leaveForm()" @submit.prevent="submitForm">
            @csrf

            {{-- Leave Type --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Leave Type <span class="text-red-500">*</span></label>
                <select name="leave_type" x-model="leaveType" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Select Leave Type —</option>
                    @foreach($leaveTypes as $code => $label)
                        <option value="{{ strtoupper(substr($code, 0, 2)) }}" {{ old('leave_type') === strtoupper(substr($code, 0, 2)) ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Half-Day Toggle --}}
            <div class="mb-5 flex items-center gap-3">
                <input type="checkbox" id="is_half_day" name="is_half_day" value="1"
                       x-model="isHalfDay" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                <label for="is_half_day" class="text-sm font-medium text-gray-700">Half-Day Leave</label>
            </div>

            {{-- Half-Day Session (shown only when half-day is checked) --}}
            <div class="mb-5" x-show="isHalfDay" x-cloak>
                <label class="block text-sm font-medium text-gray-700 mb-1">Session <span class="text-red-500">*</span></label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="half_day_session" value="first_half" class="text-indigo-600"> First Half (AM)
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="half_day_session" value="second_half" class="text-indigo-600"> Second Half (PM)
                    </label>
                </div>
            </div>

            {{-- Date Range --}}
            <div class="grid grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date <span class="text-red-500">*</span></label>
                    <input type="date" name="from_date" value="{{ old('from_date') }}"
                           :max="isHalfDay ? toDate : null"
                           x-model="fromDate" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date <span class="text-red-500">*</span></label>
                    <input type="date" name="to_date" value="{{ old('to_date') }}"
                           :min="fromDate"
                           :max="isHalfDay ? fromDate : null"
                           x-model="toDate" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>

            {{-- Reason --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="3" minlength="10" maxlength="500" required
                          placeholder="Please provide a reason for your leave request (min. 10 characters)..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('reason') }}</textarea>
            </div>

            {{-- Submit --}}
            <div class="flex justify-end gap-3">
                <a href="{{ route('leave.index') }}"
                   class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Cancel</a>
                <button type="submit"
                        class="px-6 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Submit Application
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function leaveForm() {
    return {
        leaveType: '{{ old("leave_type", "") }}',
        isHalfDay: {{ old("is_half_day") ? "true" : "false" }},
        fromDate: '{{ old("from_date", "") }}',
        toDate: '{{ old("to_date", "") }}',
        submitForm() {
            // Sync toDate with fromDate for half-day
            if (this.isHalfDay) {
                this.toDate = this.fromDate;
            }
            this.$el.submit();
        }
    }
}
</script>
@endpush
@endsection

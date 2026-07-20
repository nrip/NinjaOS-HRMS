<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\Attendance\AttendanceService;
use App\Services\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::query()
            ->with(['employee:id,employee_code,first_name,last_name', 'shift:id,name'])
            ->when($request->date, fn ($q) => $q->whereDate('attendance_date', $request->date))
            ->when($request->employee_id, fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('attendance_date');

        return response()->json($query->paginate(50));
    }

    public function show(Attendance $attendance): JsonResponse
    {
        $this->authorize('view', $attendance);
        return response()->json($attendance->load(['employee', 'shift']));
    }

    public function punch(Request $request): JsonResponse
    {
        $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'punch_type'  => ['required', 'in:IN,OUT'],
            'timestamp'   => ['nullable', 'date'],
            'latitude'    => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'   => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $employee = Employee::withoutGlobalScopes()->findOrFail($request->employee_id);

        $result = $this->attendanceService->processPunch(
            employee:    $employee,
            punchType:   $request->punch_type,
            timestamp:   Carbon::parse($request->timestamp ?? now()),
            punchSource: 'manual',
            latitude:    $request->latitude ? (float) $request->latitude : null,
            longitude:   $request->longitude ? (float) $request->longitude : null,
        );

        $statusCode = $result['success'] ? 200 : 422;
        return response()->json($result, $statusCode);
    }

    public function requestRegularization(Request $request, Attendance $attendance): JsonResponse
    {
        $this->authorize('requestRegularization', $attendance);

        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        if ($attendance->regularization_status === 'pending') {
            return response()->json(['message' => 'A regularization request is already pending for this record.'], 422);
        }

        $attendance->update([
            'regularization_status' => 'pending',
            'regularization_reason' => $request->reason,
        ]);

        return response()->json(['message' => 'Regularization request submitted successfully.', 'attendance' => $attendance]);
    }

    public function approveRegularization(Request $request, Attendance $attendance): JsonResponse
    {
        $this->authorize('approveRegularization', $attendance);

        if ($attendance->regularization_status !== 'pending') {
            return response()->json(['message' => 'No pending regularization request found.'], 422);
        }

        $attendance->update([
            'regularization_status' => 'approved',
            'regularized_by'        => $request->user()->id,
            'regularized_at'        => now(),
        ]);

        return response()->json(['message' => 'Regularization approved.', 'attendance' => $attendance]);
    }

    public function rejectRegularization(Request $request, Attendance $attendance): JsonResponse
    {
        $this->authorize('rejectRegularization', $attendance);

        if ($attendance->regularization_status !== 'pending') {
            return response()->json(['message' => 'No pending regularization request found.'], 422);
        }

        $attendance->update([
            'regularization_status' => 'rejected',
            'regularized_by'        => $request->user()->id,
            'regularized_at'        => now(),
        ]);

        return response()->json(['message' => 'Regularization rejected.', 'attendance' => $attendance]);
    }
}

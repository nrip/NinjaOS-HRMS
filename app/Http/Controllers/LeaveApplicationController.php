<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveLeaveRequest;
use App\Http\Requests\StoreLeaveApplicationRequest;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Services\Leave\LeaveService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveApplicationController extends Controller
{
    public function __construct(private readonly LeaveService $leaveService) {}

    /** Employee: list own leave applications */
    public function index(Request $request): View
    {
        $applications = LeaveApplication::withoutGlobalScopes()
            ->where('employee_id', auth()->user()->employee?->id)
            ->orderByDesc('from_date')
            ->paginate(15);

        return view('leave.index', compact('applications'));
    }

    /** Employee: show the application form */
    public function create(): View
    {
        $leaveTypes = config('nexusos.leave_types');
        return view('leave.form', compact('leaveTypes'));
    }

    /** Employee: submit a new leave application */
    public function store(StoreLeaveApplicationRequest $request): RedirectResponse
    {
        $employee = auth()->user()->employee;

        $this->leaveService->applyLeave(
            employee:       $employee,
            leaveType:      $request->leave_type,
            fromDate:       Carbon::parse($request->from_date),
            toDate:         Carbon::parse($request->to_date),
            reason:         $request->reason,
            isHalfDay:      (bool) $request->is_half_day,
            halfDaySession: $request->half_day_session,
        );

        return redirect()->route('leave.index')->with('success', 'Leave application submitted successfully.');
    }

    /** Manager/HR: list pending applications for approval */
    public function approvals(Request $request): View
    {
        $applications = LeaveApplication::withoutGlobalScopes()
            ->where('status', 'pending_approval')
            ->with(['employee', 'employee.location'])
            ->orderBy('from_date')
            ->paginate(20);

        return view('leave.approvals', compact('applications'));
    }

    /** Manager/HR: approve a leave application */
    public function approve(ApproveLeaveRequest $request, LeaveApplication $leaveApplication): RedirectResponse
    {
        $this->authorize('approve', $leaveApplication);

        $this->leaveService->approveLeave(
            application: $leaveApplication,
            approver:    auth()->user(),
            comments:    $request->comments ?? '',
        );

        return back()->with('success', 'Leave approved successfully.');
    }

    /** Manager/HR: reject a leave application */
    public function reject(ApproveLeaveRequest $request, LeaveApplication $leaveApplication): RedirectResponse
    {
        $this->authorize('reject', $leaveApplication);

        $this->leaveService->rejectLeave(
            application: $leaveApplication,
            approver:    auth()->user(),
            comments:    $request->comments ?? '',
        );

        return back()->with('success', 'Leave rejected.');
    }

    /** Employee/HR: cancel a leave application */
    public function cancel(LeaveApplication $leaveApplication): RedirectResponse
    {
        $this->authorize('cancel', $leaveApplication);

        $this->leaveService->cancelLeave(
            application: $leaveApplication,
            cancelledBy: auth()->user(),
        );

        return back()->with('success', 'Leave cancelled.');
    }
}

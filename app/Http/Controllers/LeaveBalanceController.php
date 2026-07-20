<?php

namespace App\Http\Controllers;

use App\Models\LeaveBalance;
use App\Services\Leave\LeaveProjectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveBalanceController extends Controller
{
    public function __construct(private readonly LeaveProjectionService $projectionService) {}

    /** Employee: view real-time balances and 12-month projection */
    public function index(Request $request): View
    {
        $employee = auth()->user()->employee;
        $year     = (int) ($request->year ?? now()->year);

        $balances = LeaveBalance::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get()
            ->keyBy('leave_type');

        $projection = $this->projectionService->project($employee);

        return view('leave.balances', compact('balances', 'projection', 'year'));
    }

    /** API: return projection as JSON for Chart.js */
    public function projection(Request $request): JsonResponse
    {
        $employee   = auth()->user()->employee;
        $projection = $this->projectionService->project($employee);

        return response()->json($projection);
    }
}

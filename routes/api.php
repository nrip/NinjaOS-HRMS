<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Api\BiometricMockController;
use App\Http\Controllers\AttendanceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint (no auth required)
Route::get('/health', [HealthController::class, 'check']);

// Authentication routes (no auth required)
Route::post('/auth/login', [AuthController::class, 'login']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/profile', [AuthController::class, 'profile']);

    // User endpoint
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Biometric mock endpoint (POST /api/v1/integrations/biometric/mock-punch)
    Route::prefix('v1/integrations/biometric')->group(function () {
        Route::post('/mock-punch', [BiometricMockController::class, 'punch'])
            ->name('biometric.mock-punch');
    });

    // Attendance API routes
    Route::prefix('v1/attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/punch', [AttendanceController::class, 'punch'])->name('attendance.punch');
        Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::post('/{attendance}/regularize', [AttendanceController::class, 'requestRegularization'])
            ->name('attendance.regularize');
        Route::post('/{attendance}/approve-regularization', [AttendanceController::class, 'approveRegularization'])
            ->name('attendance.approve-regularization');
        Route::post('/{attendance}/reject-regularization', [AttendanceController::class, 'rejectRegularization'])
            ->name('attendance.reject-regularization');
    });
});

// ── Phase 6: Mobile API routes with rate limiting ────────────────────────────
// throttle:60,1 = 60 requests per minute per IP/user
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('v1/mobile')->group(function () {

    // Employee profile (self)
    Route::get('/profile', function (Request $request) {
        $employee = \App\Models\Employee::withoutGlobalScopes()
            ->with(['location', 'department', 'designation'])
            ->where('user_id', $request->user()->id)
            ->first();
        if (! $employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }
        return new \App\Http\Resources\EmployeeResource($employee);
    })->name('mobile.profile');

    // Leave balances (self)
    Route::get('/leave-balances', function (Request $request) {
        $employee = \App\Models\Employee::withoutGlobalScopes()
            ->where('user_id', $request->user()->id)->first();
        if (! $employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }
        $balances = \App\Models\LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->get();
        return \App\Http\Resources\LeaveBalanceResource::collection($balances);
    })->name('mobile.leave-balances');

    // Payslips (self)
    Route::get('/payslips', function (Request $request) {
        $employee = \App\Models\Employee::withoutGlobalScopes()
            ->where('user_id', $request->user()->id)->first();
        if (! $employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }
        $payslips = \App\Models\PayrollRecord::where('employee_id', $employee->id)
            ->where('status', 'finalized')
            ->orderByDesc('payroll_year')
            ->orderByDesc('payroll_month')
            ->limit(12)
            ->get();
        return \App\Http\Resources\PayslipResource::collection($payslips);
    })->name('mobile.payslips');
});

// ── Signed payslip PDF download route ────────────────────────────────────────
// This route validates the signed URL before serving the PDF.
Route::middleware(['auth:sanctum', 'signed'])->get('/v1/payroll/{record}/pdf', function (\App\Models\PayrollRecord $record) {
    $employee = \App\Models\Employee::withoutGlobalScopes()->find($record->employee_id);
    $pdf = app(\App\Services\Payroll\PayslipPdfService::class)->generate($record, $employee);
    return response($pdf, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"payslip_{$record->employee_code}_{$record->payroll_year}_{$record->payroll_month}.pdf\"",
    ]);
})->name('api.payroll.pdf');

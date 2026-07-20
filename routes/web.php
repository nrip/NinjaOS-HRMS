<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeLifecycleController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeImportController;

Route::get('/', function () {
    return view('welcome');
});

// Employee Management Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::resource('employees', EmployeeController::class);
    Route::post('employees/{employee}/transition', [EmployeeLifecycleController::class, 'transition'])->name('employees.transition');
    Route::post('employees/{employee}/documents/upload', [EmployeeDocumentController::class, 'upload'])->name('employees.documents.upload');
    Route::get('employees/{employee}/documents', [EmployeeDocumentController::class, 'list'])->name('employees.documents.list');
    Route::get('employees/{employee}/documents/{mediaId}/download', [EmployeeDocumentController::class, 'download'])->name('employees.documents.download');
    Route::delete('employees/{employee}/documents/{mediaId}', [EmployeeDocumentController::class, 'delete'])->name('employees.documents.delete');
    
    // Import routes
    Route::get('employees-import', [EmployeeImportController::class, 'show'])->name('employees.import.show');
    Route::post('employees-import', [EmployeeImportController::class, 'process'])->name('employees.import.process');
    Route::get('employees-import-template', [EmployeeImportController::class, 'template'])->name('employees.import.template');
});

// Auth routes (web-based)
use App\Http\Controllers\AuthController;
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth:sanctum');
Route::get('/auth/login', function () {
    return response()->json(['message' => 'Please login via API.'], 401);
})->name('auth.login');

// Attendance routes
Route::middleware('auth')->group(function () {
    Route::resource('attendance', \App\Http\Controllers\AttendanceController::class)->except(['create', 'store', 'edit', 'update', 'destroy']);
    Route::get('/attendance', [\App\Http\Controllers\AttendanceController::class, 'webIndex'])->name('attendance.index');
    Route::post('/attendance/{attendance}/regularize', [\App\Http\Controllers\AttendanceController::class, 'requestRegularizationWeb'])->name('attendance.regularize');
    Route::post('/attendance/{attendance}/approve-regularization', [\App\Http\Controllers\AttendanceController::class, 'approveRegularizationWeb'])->name('attendance.approve-regularization');
    Route::post('/attendance/{attendance}/reject-regularization', [\App\Http\Controllers\AttendanceController::class, 'rejectRegularizationWeb'])->name('attendance.reject-regularization');

    // Shift routes
    Route::resource('shifts', \App\Http\Controllers\ShiftController::class);

    // Phase 3: Leave Management
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/',         [\App\Http\Controllers\LeaveApplicationController::class, 'index'])->name('index');
        Route::get('/apply',    [\App\Http\Controllers\LeaveApplicationController::class, 'create'])->name('create');
        Route::post('/',        [\App\Http\Controllers\LeaveApplicationController::class, 'store'])->name('store');
        Route::patch('/{leaveApplication}/cancel',  [\App\Http\Controllers\LeaveApplicationController::class, 'cancel'])->name('cancel');
        Route::get('/approvals',                    [\App\Http\Controllers\LeaveApplicationController::class, 'approvals'])->name('approvals');
        Route::patch('/{leaveApplication}/approve', [\App\Http\Controllers\LeaveApplicationController::class, 'approve'])->name('approve');
        Route::patch('/{leaveApplication}/reject',  [\App\Http\Controllers\LeaveApplicationController::class, 'reject'])->name('reject');
        Route::get('/balances',            [\App\Http\Controllers\LeaveBalanceController::class, 'index'])->name('balances');
        Route::get('/balances/projection', [\App\Http\Controllers\LeaveBalanceController::class, 'projection'])->name('balances.projection');
    });

    // Phase 4: Payroll & Statutory Compliance
    Route::prefix('payroll')->name('payroll.')->group(function () {
        Route::get('/',                                       [\App\Http\Controllers\PayrollController::class, 'index'])->name('index');
        Route::get('/reports/variance',                       [\App\Http\Controllers\PayrollController::class, 'varianceReport'])->name('variance');
        Route::get('/reports/reconciliation',                 [\App\Http\Controllers\PayrollController::class, 'reconciliation'])->name('reconciliation');
        Route::post('/process',                               [\App\Http\Controllers\PayrollController::class, 'process'])->name('process');
        Route::post('/finalize',                              [\App\Http\Controllers\PayrollController::class, 'finalize'])->name('finalize');
        Route::get('/{payrollRecord}',                        [\App\Http\Controllers\PayrollController::class, 'show'])->name('show');
        Route::get('/{payrollRecord}/pdf',                    [\App\Http\Controllers\PayrollController::class, 'downloadPdf'])->name('payslip.pdf');
        Route::post('/{payrollRecord}/approve',               [\App\Http\Controllers\PayrollController::class, 'approve'])->name('approve');
        Route::post('/{payrollRecord}/acknowledge-variance',  [\App\Http\Controllers\PayrollController::class, 'acknowledgeVariance'])->name('acknowledge-variance');
    });

    // Phase 5: Applicant Tracking System (ATS)
    Route::prefix('ats')->name('ats.')->group(function () {
        // Job Requisitions
        Route::prefix('requisitions')->name('requisitions.')->group(function () {
            Route::get('/',                                    [\App\Http\Controllers\JobRequisitionController::class, 'index'])->name('index');
            Route::get('/create',                             [\App\Http\Controllers\JobRequisitionController::class, 'create'])->name('create');
            Route::post('/',                                  [\App\Http\Controllers\JobRequisitionController::class, 'store'])->name('store');
            Route::get('/{requisition}',                      [\App\Http\Controllers\JobRequisitionController::class, 'show'])->name('show');
            Route::post('/{requisition}/submit',              [\App\Http\Controllers\JobRequisitionController::class, 'submit'])->name('submit');
            Route::post('/{requisition}/approve',             [\App\Http\Controllers\JobRequisitionController::class, 'approve'])->name('approve');
            Route::post('/{requisition}/reject',              [\App\Http\Controllers\JobRequisitionController::class, 'reject'])->name('reject');
            Route::post('/{requisition}/candidates',          [\App\Http\Controllers\CandidateController::class, 'store'])->name('candidates.store');
        });
        // Kanban Board
        Route::prefix('kanban')->name('kanban.')->group(function () {
            Route::get('/{requisition}',       [\App\Http\Controllers\KanbanBoardController::class, 'board'])->name('board');
            Route::get('/{requisition}/data',  [\App\Http\Controllers\KanbanBoardController::class, 'boardData'])->name('data');
        });
        // Candidates
        Route::prefix('candidates')->name('candidates.')->group(function () {
            Route::get('/{candidate}',              [\App\Http\Controllers\CandidateController::class, 'show'])->name('show');
            Route::patch('/{candidate}/move-stage', [\App\Http\Controllers\CandidateController::class, 'moveStage'])->name('move-stage');
            Route::post('/{candidate}/convert',     [\App\Http\Controllers\CandidateController::class, 'convertToEmployee'])->name('convert');
        });
    });
});

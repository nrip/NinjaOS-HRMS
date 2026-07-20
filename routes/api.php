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

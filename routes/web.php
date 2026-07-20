<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Employee Management Routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
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

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportEmployeeRequest;
use App\Services\EmployeeImportService;
use App\Models\Location;
use Illuminate\Http\Request;

class EmployeeImportController extends Controller
{
    /**
     * Show the import form.
     */
    public function show()
    {
        $this->authorize('create', \App\Models\Employee::class);

        return view('employees.import', [
            'locations' => Location::where('is_active', true)->get(),
        ]);
    }

    /**
     * Process the CSV import.
     */
    public function process(ImportEmployeeRequest $request)
    {
        $this->authorize('create', \App\Models\Employee::class);

        $validated = $request->validated();
        $filePath = $request->file('csv_file')->store('imports');
        $dryRun = $validated['dry_run'] ?? false;

        $service = new EmployeeImportService();
        $result = $service->import(
            storage_path("app/{$filePath}"),
            $validated['location_id'],
            $dryRun
        );

        // Clean up temporary file
        @unlink(storage_path("app/{$filePath}"));

        if ($result['success']) {
            return redirect()->route('employees.index')
                ->with('success', "Successfully imported {$result['imported_count']} employees.");
        } else {
            return back()
                ->with('error', "Import failed with {$result['error_count']} errors.")
                ->with('import_errors', $result['errors']);
        }
    }

    /**
     * Download CSV template.
     */
    public function template()
    {
        $headers = [
            'first_name',
            'last_name',
            'email',
            'phone',
            'date_of_birth',
            'gender',
            'department_code',
            'designation_code',
            'aadhaar',
            'pan',
            'bank_account',
            'bank_name',
            'ifsc_code',
            'date_of_joining',
        ];

        $filename = 'employee_import_template_' . date('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

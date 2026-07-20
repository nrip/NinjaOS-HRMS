<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Location;
use App\Models\Department;
use App\Models\Designation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;

class EmployeeImportService
{
    private array $errors = [];
    private array $imported = [];
    private int $rowNumber = 0;

    /**
     * Import employees from CSV file.
     */
    public function import(string $filePath, int $locationId, bool $dryRun = false): array
    {
        // Reset state for each import call
        $this->errors = [];
        $this->imported = [];
        $this->rowNumber = 0;
        HeadingRowFormatter::default('none');

        try {
            $rows = Excel::toArray([], $filePath);
            $data = $rows[0] ?? [];

            if (empty($data)) {
                return [
                    'success' => false,
                    'message' => 'CSV file is empty.',
                    'imported_count' => 0,
                    'error_count' => 0,
                    'errors' => [],
                ];
            }

            // Extract header row and normalize keys (trim whitespace, lowercase)
            $headerRow = array_shift($data);
            $headerRow = array_map(fn($h) => strtolower(trim((string) $h)), $headerRow);

            foreach ($data as $index => $row) {
                $this->rowNumber = $index + 2; // +2 because of header and 0-indexing

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Map numeric indices to named keys using header row
                $namedRow = array_combine($headerRow, array_pad(array_values($row), count($headerRow), ''));
                $this->processRow($namedRow, $locationId, $dryRun);
            }

            return [
                'success' => count($this->errors) === 0,
                'message' => count($this->errors) === 0 ? 'Import completed successfully.' : 'Import completed with errors.',
                'imported_count' => count($this->imported),
                'error_count' => count($this->errors),
                'errors' => $this->errors,
                'dry_run' => $dryRun,
            ];
        } catch (\Exception $e) {
            Log::error('Employee import failed', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'imported_count' => 0,
                'error_count' => 0,
                'errors' => [],
            ];
        }
    }

    /**
     * Process a single row.
     */
    private function processRow(array $row, int $locationId, bool $dryRun = false): void
    {
        // Extract and normalize data
        $data = [
            'location_id' => $locationId,
            'first_name' => trim($row['first_name'] ?? ''),
            'last_name' => trim($row['last_name'] ?? ''),
            'email' => trim($row['email'] ?? ''),
            'phone' => trim($row['phone'] ?? ''),
            'date_of_birth' => $row['date_of_birth'] ?? null,
            'gender' => trim($row['gender'] ?? ''),
            'department_code' => trim($row['department_code'] ?? ''),
            'designation_code' => trim($row['designation_code'] ?? ''),
            'aadhaar' => trim($row['aadhaar'] ?? ''),
            'pan' => trim($row['pan'] ?? ''),
            'bank_account' => trim($row['bank_account'] ?? ''),
            'bank_name' => trim($row['bank_name'] ?? ''),
            'ifsc_code' => trim($row['ifsc_code'] ?? ''),
            'date_of_joining' => $row['date_of_joining'] ?? null,
        ];

        // Validate required fields
        $validator = Validator::make($data, [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'required|regex:/^[0-9]{10}$/',
            'date_of_birth' => 'required|date_format:Y-m-d|before:today',
            'gender' => 'required|in:male,female,other',
            'department_code' => 'required|string',
            'designation_code' => 'required|string',
            'date_of_joining' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            $this->errors[] = [
                'row' => $this->rowNumber,
                'errors' => $validator->errors()->toArray(),
            ];
            return;
        }

        // Resolve department and designation
        $department = Department::where('code', $data['department_code'])->first();
        if (!$department) {
            $this->errors[] = [
                'row' => $this->rowNumber,
                'errors' => ['department_code' => ["Department with code '{$data['department_code']}' not found."]],
            ];
            return;
        }

        $designation = Designation::where('code', $data['designation_code'])->first();
        if (!$designation) {
            $this->errors[] = [
                'row' => $this->rowNumber,
                'errors' => ['designation_code' => ["Designation with code '{$data['designation_code']}' not found."]],
            ];
            return;
        }

        // Check for duplicate email in location
        $existingEmployee = Employee::where('location_id', $locationId)
            ->where('email', $data['email'])
            ->first();

        if ($existingEmployee) {
            $this->errors[] = [
                'row' => $this->rowNumber,
                'errors' => ['email' => ["Email '{$data['email']}' already exists in this location."]],
            ];
            return;
        }

        // Prepare employee data
        $employeeData = [
            'location_id' => $data['location_id'],
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'aadhaar' => $data['aadhaar'] ?: null,
            'pan' => $data['pan'] ?: null,
            'bank_account' => $data['bank_account'] ?: null,
            'bank_name' => $data['bank_name'] ?: null,
            'ifsc_code' => $data['ifsc_code'] ?: null,
            'date_of_joining' => $data['date_of_joining'],
            'status' => 'onboarding',
        ];

        if (!$dryRun) {
            try {
                $employee = Employee::create($employeeData);
                $this->imported[] = $employee->id;

                Log::info('Employee imported', [
                    'employee_id' => $employee->id,
                    'email' => $employee->email,
                    'row' => $this->rowNumber,
                ]);
            } catch (\Exception $e) {
                $this->errors[] = [
                    'row' => $this->rowNumber,
                    'errors' => ['general' => [$e->getMessage()]],
                ];
            }
        } else {
            $this->imported[] = 'row_' . $this->rowNumber;
        }
    }
}

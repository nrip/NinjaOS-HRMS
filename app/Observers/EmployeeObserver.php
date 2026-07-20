<?php

namespace App\Observers;

use App\Models\Employee;
use Illuminate\Support\Facades\Log;

class EmployeeObserver
{
    /**
     * Handle the Employee "creating" event.
     */
    public function creating(Employee $employee): void
    {
        // Auto-generate employee_code if not provided
        if (!$employee->employee_code) {
            $employee->employee_code = $this->generateEmployeeCode($employee);
        }

        // Log creation
        Log::channel('audit')->info('Employee creating', [
            'location_id' => $employee->location_id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        Log::channel('audit')->info('Employee created', [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'location_id' => $employee->location_id,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Employee "updating" event.
     */
    public function updating(Employee $employee): void
    {
        // Log changes
        $changes = $employee->getChanges();
        $original = $employee->getOriginal();

        $logData = [
            'employee_id' => $employee->id,
            'user_id' => auth()->id(),
            'changes' => [],
        ];

        // Only log non-sensitive fields
        $loggableFields = ['status', 'department_id', 'designation_id', 'reporting_manager_id', 'probation_end_date', 'confirmation_date'];

        foreach ($loggableFields as $field) {
            if (isset($changes[$field])) {
                $logData['changes'][$field] = [
                    'from' => $original[$field] ?? null,
                    'to' => $changes[$field],
                ];
            }
        }

        if (!empty($logData['changes'])) {
            Log::channel('audit')->info('Employee updating', $logData);
        }
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        Log::channel('audit')->info('Employee updated', [
            'employee_id' => $employee->id,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Employee "deleting" event.
     */
    public function deleting(Employee $employee): void
    {
        Log::channel('audit')->info('Employee deleting', [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Employee "deleted" event.
     */
    public function deleted(Employee $employee): void
    {
        Log::channel('audit')->info('Employee deleted', [
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Employee "restoring" event.
     */
    public function restoring(Employee $employee): void
    {
        Log::channel('audit')->info('Employee restoring', [
            'employee_id' => $employee->id,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Handle the Employee "restored" event.
     */
    public function restored(Employee $employee): void
    {
        Log::channel('audit')->info('Employee restored', [
            'employee_id' => $employee->id,
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Generate employee code based on location state code and sequence.
     */
    private function generateEmployeeCode(Employee $employee): string
    {
        $location = $employee->location;
        $stateCode = strtoupper(substr($location->state, 0, 2));

        // Get the next sequence number for this location
        $sequence = Employee::where('location_id', $employee->location_id)
            ->count() + 1;

        return sprintf('EMP-%s-%05d', $stateCode, $sequence);
    }
}

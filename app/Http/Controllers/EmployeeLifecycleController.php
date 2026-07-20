<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeLifecycleHistory;
use App\Http\Requests\TransitionEmployeeRequest;
use Illuminate\Support\Facades\Log;

class EmployeeLifecycleController extends Controller
{
    /**
     * Transition employee to a new status.
     */
    public function transition(TransitionEmployeeRequest $request, Employee $employee)
    {
        $this->authorize('update', $employee);

        $validated = $request->validated();
        $newStatus = $validated['new_status'];
        $previousStatus = $employee->status;

        // Validate state transitions
        $this->validateTransition($previousStatus, $newStatus);

        // Update employee status and related dates
        $updateData = ['status' => $newStatus];

        if ($newStatus === 'probation' && isset($validated['probation_end_date'])) {
            $updateData['probation_end_date'] = $validated['probation_end_date'];
        }

        if ($newStatus === 'confirmed' && isset($validated['confirmation_date'])) {
            $updateData['confirmation_date'] = $validated['confirmation_date'];
        }

        if ($newStatus === 'exit' && isset($validated['date_of_exit'])) {
            $updateData['date_of_exit'] = $validated['date_of_exit'];
        }

        $employee->update($updateData);

        // Log lifecycle history
        EmployeeLifecycleHistory::create([
            'employee_id' => $employee->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'reason' => $validated['reason'] ?? null,
            'changed_by' => auth()->id(),
        ]);

        Log::info('Employee status transitioned', [
            'employee_id' => $employee->id,
            'from_status' => $previousStatus,
            'to_status' => $newStatus,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('employees.show', $employee)
            ->with('success', "Employee transitioned from {$previousStatus} to {$newStatus}.");
    }

    /**
     * Validate state transitions.
     */
    private function validateTransition(string $from, string $to): void
    {
        $validTransitions = [
            'onboarding' => ['probation', 'confirmed', 'exit'],
            'probation' => ['confirmed', 'exit', 'suspended'],
            'confirmed' => ['transferred', 'suspended', 'on_leave', 'exit'],
            'transferred' => ['confirmed', 'suspended', 'exit'],
            'on_leave' => ['confirmed', 'exit'],
            'suspended' => ['confirmed', 'exit'],
            'exit' => [],
        ];

        if (!isset($validTransitions[$from]) || !in_array($to, $validTransitions[$from])) {
            abort(422, "Cannot transition from {$from} to {$to}.");
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services\ATS;

use App\Models\Candidate;
use App\Models\Employee;
use App\Models\EmployeeLifecycleHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * HandoffService
 *
 * Bridges the ATS module (Phase 5) with Core HR (Phase 1).
 *
 * "Convert to Employee" Workflow:
 * ────────────────────────────────
 * 1. Validates the candidate is in the 'hired' stage.
 * 2. Maps candidate + requisition data to Employee fields.
 * 3. Creates the Employee record with status 'onboarding'.
 * 4. The Phase 1 EmployeeObserver auto-generates the employee_code
 *    and creates the initial EmployeeLifecycleHistory record.
 * 5. Updates the candidate with converted_to_employee_id and converted_at.
 *
 * Data Mapping:
 * ─────────────
 * Candidate.first_name          → Employee.first_name
 * Candidate.last_name           → Employee.last_name
 * Candidate.email               → Employee.email
 * Candidate.phone               → Employee.phone
 * Candidate.date_of_joining     → Employee.date_of_joining
 * Requisition.location_id       → Employee.location_id
 * Requisition.department_id     → Employee.department_id
 * Requisition.designation_id    → Employee.designation_id
 */
class HandoffService
{
    /**
     * Convert a hired candidate into a Core HR Employee record.
     *
     * @param  Candidate  $candidate  Must be in 'hired' stage.
     * @param  User       $actor      The HR user triggering the conversion.
     * @return Employee   The newly created Employee record.
     *
     * @throws \LogicException  If candidate is not in 'hired' stage.
     * @throws \LogicException  If candidate has already been converted.
     */
    public function convertToEmployee(Candidate $candidate, User $actor): Employee
    {
        // ── Guard: must be hired ──────────────────────────────────────────────
        if (! $candidate->isHired()) {
            throw new \LogicException(
                "Only candidates in the 'hired' stage can be converted to employees. " .
                "Current stage: {$candidate->current_stage}"
            );
        }

        // ── Guard: prevent double conversion ─────────────────────────────────
        if ($candidate->isConverted()) {
            throw new \LogicException(
                "Candidate has already been converted to employee ID: {$candidate->converted_to_employee_id}"
            );
        }

        // Load the requisition to get location/department/designation
        $requisition = $candidate->requisition()->withoutGlobalScopes()->first();

        if (! $requisition) {
            throw new \LogicException("Cannot convert candidate: associated job requisition not found.");
        }

        return DB::transaction(function () use ($candidate, $requisition, $actor): Employee {
            // ── Create Employee (Phase 1 Core HR) ─────────────────────────────
            // The EmployeeObserver (Phase 1) will:
            //   a) Auto-generate employee_code (EMP-{STATE_CODE}-{SEQUENCE})
            //   b) Create EmployeeLifecycleHistory record for 'onboarding' status
            $employee = Employee::withoutGlobalScopes()->create([
                'employee_id'     => (string) Str::uuid(),
                'location_id'     => $requisition->location_id,
                'department_id'   => $requisition->department_id,
                'designation_id'  => $requisition->designation_id,
                'first_name'      => $candidate->first_name,
                'last_name'       => $candidate->last_name,
                'email'           => $candidate->email,
                'phone'           => $candidate->phone ?? '',
                'status'          => 'onboarding',
                'date_of_joining' => $candidate->date_of_joining ?? now()->toDateString(),
                // Placeholder values — HR will complete the profile post-onboarding
                'date_of_birth'   => now()->subYears(25)->toDateString(),
                'gender'          => 'other',
                'aadhaar'         => '000000000000',
                'pan'             => 'AAAAA0000A',
                'bank_account'    => '0000000000',
                'bank_name'       => 'TBD',
                'ifsc_code'       => 'AAAA0000000',
            ]);

            // ── Create EmployeeLifecycleHistory (Phase 1 integration) ──────────
            // The EmployeeObserver only logs to the audit channel; it does not
            // create lifecycle history records. HandoffService explicitly creates
            // the initial 'onboarding' lifecycle record here.
            EmployeeLifecycleHistory::create([
                'employee_id'     => $employee->id,
                'previous_status' => null,
                'new_status'      => 'onboarding',
                'reason'          => 'Converted from ATS candidate via HandoffService',
                'changed_by'      => $actor->id,
            ]);

            // ── Update candidate: mark as converted ───────────────────────────
            $candidate->update([
                'converted_to_employee_id' => $employee->id,
                'converted_at'             => now(),
            ]);

            // ── Log (PII-safe) ────────────────────────────────────────────────
            Log::info('ATS: Candidate converted to employee', [
                'candidate_uuid'  => $candidate->candidate_id,
                'employee_id'     => $employee->id,
                'employee_code'   => $employee->employee_code,
                'converted_by'    => $actor->id,
            ]);

            return $employee;
        });
    }
}

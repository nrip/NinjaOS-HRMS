<?php

namespace App\Policies;

use App\Models\LeaveApplication;
use App\Models\User;

/**
 * LeaveApplicationPolicy
 *
 * Enforces LocationScope: Location HR can ONLY approve/reject leave applications
 * for employees mapped to their own location (SRS FR3.1.7 / Phase 3 Mandate 3).
 *
 * Super Admin and Central HR can act on any application.
 */
class LeaveApplicationPolicy
{
    /** Super Admin and Central HR bypass all checks */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasAnyRole(['super_admin', 'central_hr'])) {
            return true;
        }
        return null;
    }

    /** Only Manager or Location HR at the same location can approve */
    public function approve(User $user, LeaveApplication $application): bool
    {
        if ($user->hasRole('manager')) {
            // Manager must be at the same location as the employee
            return $user->location_id === $application->location_id;
        }

        if ($user->hasRole('location_hr')) {
            return $user->location_id === $application->location_id;
        }

        return false;
    }

    /** Same as approve */
    public function reject(User $user, LeaveApplication $application): bool
    {
        return $this->approve($user, $application);
    }

    /** Employee can cancel their own pending/approved future leaves; HR can cancel any */
    public function cancel(User $user, LeaveApplication $application): bool
    {
        // Employee cancels their own leave
        if ($user->employee?->id === $application->employee_id) {
            return true;
        }

        // Location HR can cancel leaves for employees at their location
        if ($user->hasRole('location_hr') && $user->location_id === $application->location_id) {
            return true;
        }

        return false;
    }

    /** Employee can view their own; Manager/HR can view their location's */
    public function view(User $user, LeaveApplication $application): bool
    {
        if ($user->employee?->id === $application->employee_id) {
            return true;
        }

        if ($user->hasAnyRole(['manager', 'location_hr'])) {
            return $user->location_id === $application->location_id;
        }

        return false;
    }
}

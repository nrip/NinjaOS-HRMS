<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

/**
 * AttendancePolicy
 *
 * Enforces LocationScope on all attendance actions.
 * Location HR can ONLY approve/reject regularizations for employees
 * mapped to their own location (Mandatory Addition #3).
 */
class AttendancePolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'central_hr', 'location_hr', 'manager', 'employee', 'payroll_admin', 'auditor']);
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->hasAnyRole(['central_hr', 'payroll_admin', 'auditor'])) {
            return true;
        }
        if ($user->hasAnyRole(['location_hr', 'manager'])) {
            return $user->location_id === $attendance->location_id;
        }
        if ($user->hasRole('employee')) {
            return $user->id === $attendance->employee_id;
        }
        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'central_hr', 'location_hr']);
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole('central_hr')) {
            return true;
        }
        if ($user->hasRole('location_hr')) {
            return $user->location_id === $attendance->location_id;
        }
        return false;
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasAnyRole(['super_admin', 'central_hr']);
    }

    public function requestRegularization(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->employee_id
            || $user->hasAnyRole(['central_hr', 'location_hr']);
    }

    /**
     * Location HR can ONLY approve regularizations for their own location.
     * Central HR can approve for any location.
     * Mandatory Addition #3: strict LocationScope enforcement.
     */
    public function approveRegularization(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole('central_hr')) {
            return true;
        }
        if ($user->hasRole('location_hr')) {
            return $user->location_id === $attendance->location_id;
        }
        return false;
    }

    public function rejectRegularization(User $user, Attendance $attendance): bool
    {
        return $this->approveRegularization($user, $attendance);
    }
}

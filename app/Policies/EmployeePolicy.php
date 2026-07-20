<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Determine whether the user can view any employees.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'central_hr', 'location_hr', 'manager']);
    }

    /**
     * Determine whether the user can view the employee.
     */
    public function view(User $user, Employee $employee): bool
    {
        // Super Admin can view all
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Central HR can view all
        if ($user->hasRole('central_hr')) {
            return true;
        }

        // Location HR can view employees in their location
        if ($user->hasRole('location_hr') && $user->location_id === $employee->location_id) {
            return true;
        }

        // Manager can view their direct reports
        if ($user->hasRole('manager') && $user->employee_id === $employee->reporting_manager_id) {
            return true;
        }

        // Employee can view themselves
        if ($user->employee_id === $employee->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create employees.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'central_hr', 'location_hr']);
    }

    /**
     * Determine whether the user can update the employee.
     */
    public function update(User $user, Employee $employee): bool
    {
        // Super Admin can update all
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Central HR can update all
        if ($user->hasRole('central_hr')) {
            return true;
        }

        // Location HR can update employees in their location
        if ($user->hasRole('location_hr') && $user->location_id === $employee->location_id) {
            return true;
        }

        // Manager can update their direct reports
        if ($user->hasRole('manager') && $user->employee_id === $employee->reporting_manager_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the employee.
     */
    public function delete(User $user, Employee $employee): bool
    {
        // Only Super Admin and Central HR can delete
        return $user->hasAnyRole(['super_admin', 'central_hr']);
    }

    /**
     * Determine whether the user can restore the employee.
     */
    public function restore(User $user, Employee $employee): bool
    {
        return $user->hasAnyRole(['super_admin', 'central_hr']);
    }

    /**
     * Determine whether the user can permanently delete the employee.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        return $user->hasRole('super_admin');
    }
}

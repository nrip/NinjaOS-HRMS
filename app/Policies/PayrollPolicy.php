<?php

namespace App\Policies;

use App\Models\PayrollRecord;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PayrollPolicy
{
    use HandlesAuthorization;

    public function view(User $user, PayrollRecord $record): bool
    {
        // Employee can view their own; HR/Admin can view all in their location
        if ($user->hasRole('employee')) {
            return $user->employee?->id === $record->employee_id;
        }
        return $user->location_id === $record->location_id
            || $user->hasRole(['super_admin', 'hr_admin']);
    }

    public function process(User $user): bool
    {
        return $user->hasRole(['hr_admin', 'location_hr', 'super_admin']);
    }

    public function approve(User $user, PayrollRecord $record): bool
    {
        return $user->hasRole(['hr_admin', 'super_admin'])
            && $user->location_id === $record->location_id;
    }

    public function finalize(User $user): bool
    {
        return $user->hasRole(['hr_admin', 'super_admin']);
    }

    public function acknowledgeVariance(User $user, PayrollRecord $record): bool
    {
        return $user->hasRole(['hr_admin', 'super_admin'])
            && $user->location_id === $record->location_id;
    }

    public function viewVarianceReport(User $user): bool
    {
        return $user->hasRole(['hr_admin', 'location_hr', 'super_admin']);
    }

    public function viewReconciliation(User $user): bool
    {
        return $user->hasRole(['hr_admin', 'super_admin']);
    }
}

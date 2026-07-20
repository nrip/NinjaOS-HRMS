<?php

use App\Models\Scopes\LocationScope;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\PayrollRecord;
use App\Models\StatutoryRecord;
use App\Models\HolidayCalendar;
use App\Models\Shift;
use App\Models\JobRequisition;

/**
 * LocationScope Architecture Test
 * 
 * This test ensures that all tenant-scoped models have the LocationScope
 * global scope applied. This is critical for multi-tenancy security.
 * 
 * CRITICAL: This test MUST pass on every commit. Failure means data leakage risk.
 */

it('verifies all tenant-scoped models have LocationScope applied', function () {
    $tenantScopedModels = [
        Employee::class,
        Attendance::class,
        LeaveApplication::class,
        PayrollRecord::class,
        StatutoryRecord::class,
        HolidayCalendar::class,
        Shift::class,
        JobRequisition::class,
    ];

    foreach ($tenantScopedModels as $modelClass) {
        $model = new $modelClass();
        $scopes = $model->getGlobalScopes();

        // Check if LocationScope is applied
        $hasLocationScope = collect($scopes)->contains(function ($scope) {
            return $scope instanceof LocationScope;
        });

        expect($hasLocationScope)
            ->toBe(true, "Model {$modelClass} must have LocationScope applied");
    }
});

it('verifies non-tenant-scoped models do NOT have LocationScope', function () {
    $nonTenantScopedModels = [
        \App\Models\Location::class,
        \App\Models\Department::class,
        \App\Models\Designation::class,
        \App\Models\User::class,
    ];

    foreach ($nonTenantScopedModels as $modelClass) {
        $model = new $modelClass();
        $scopes = $model->getGlobalScopes();

        // Check that LocationScope is NOT applied
        $hasLocationScope = collect($scopes)->contains(function ($scope) {
            return $scope instanceof LocationScope;
        });

        expect($hasLocationScope)
            ->toBe(false, "Model {$modelClass} should NOT have LocationScope applied");
    }
});

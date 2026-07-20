<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use App\Services\TenantContext;

/**
 * LocationScope Global Scope
 * 
 * Automatically filters all queries on tenant-scoped models to include only
 * records belonging to the current location (tenant).
 * 
 * This is the core of our multi-tenancy enforcement in MySQL.
 * Every query on Employee, Attendance, LeaveApplication, etc. will have
 * WHERE location_id = ? automatically appended.
 * 
 * CRITICAL: This prevents data leakage between locations.
 */
class LocationScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantContext = app(TenantContext::class);

        if ($tenantContext->hasLocationId()) {
            $builder->where($model->getTable() . '.location_id', '=', $tenantContext->getLocationId());
        }
    }
}

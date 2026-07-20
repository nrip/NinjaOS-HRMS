<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Services\TenantContext;
use App\Models\Employee;
use App\Policies\EmployeePolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register TenantContext as a singleton
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register observers
        Employee::observe(\App\Observers\EmployeeObserver::class);

        // Register policies explicitly (Laravel 11 does not auto-discover by default)
        Gate::policy(Employee::class, EmployeePolicy::class);
    }
}

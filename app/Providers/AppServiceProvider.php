<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\TenantContext;
use App\Models\Employee;

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
    }
}

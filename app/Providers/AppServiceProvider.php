<?php
namespace App\Providers;
use App\Models\Employee;
use App\Policies\EmployeePolicy;
use App\Services\Integrations\Accounting\AccountingIntegrationInterface;
use App\Services\Integrations\Accounting\MockAccountingService;
use App\Services\Integrations\WhatsApp\MockWhatsAppService;
use App\Services\Integrations\WhatsApp\WhatsAppServiceInterface;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // TenantContext singleton
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext();
        });

        // ── Phase 6: Integration service bindings ─────────────────────────────
        // Bind interfaces to mock implementations for development.
        // PRODUCTION SWAP: Replace MockWhatsAppService with WhatsAppCloudApiService,
        // and MockAccountingService with TallyAccountingService or ZohoBooksService.
        $this->app->bind(WhatsAppServiceInterface::class, MockWhatsAppService::class);
        $this->app->bind(AccountingIntegrationInterface::class, MockAccountingService::class);
    }

    public function boot(): void
    {
        // Register observers
        Employee::observe(\App\Observers\EmployeeObserver::class);

        // Register policies explicitly (Laravel 11 does not auto-discover by default)
        Gate::policy(Employee::class, EmployeePolicy::class);
    }
}

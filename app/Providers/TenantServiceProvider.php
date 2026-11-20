<?php

namespace App\Providers;

use App\Domain\DomainTenantFinder;
use App\Domain\TenantFinder;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind the tenant finder contract to the domain-based implementation
        $this->app->bind(TenantFinder::class, DomainTenantFinder::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        $this->determineCurrentTenant(request());
    }

    /**
     * Determine and set the current tenant for the request.
     */
    protected function determineCurrentTenant(Request $request): void
    {
        $tenantFinder = $this->app->make(TenantFinder::class);

        $tenant = $tenantFinder->findForRequest($request);

        if ($tenant) {
            $tenant->makeCurrent();
        }
    }
}

<?php

namespace Modules\CustomerStatements\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\CustomerStatements\Services\CustomerStatementNumberingService;
use Modules\CustomerStatements\Services\CustomerStatementPersistenceService;
use Modules\CustomerStatements\Services\CustomerStatementWorkspaceAdapter;

class CustomerStatementsServiceProvider extends ServiceProvider
{
    /**
     * Register module services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->scoped(CustomerStatementWorkspaceAdapter::class, function () {
            return new CustomerStatementWorkspaceAdapter();
        });

        $this->app->scoped(CustomerStatementNumberingService::class, function () {
            return new CustomerStatementNumberingService();
        });

        $this->app->scoped(CustomerStatementPersistenceService::class, function ($app) {
            return new CustomerStatementPersistenceService(
                $app->make(CustomerStatementNumberingService::class)
            );
        });
    }

    /**
     * Bootstrap module services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(
            module_path('CustomerStatements', 'Resources/views'),
            'customerstatements'
        );

        // The complete statement workspace is intentionally reused through a
        // small adapter.  Register its view namespace as a fallback in case the
        // module provider boot order changes after deployment or route caching.
        $customerViews = module_path('Customers', 'Resources/views');
        if (is_dir($customerViews)) {
            $this->loadViewsFrom($customerViews, 'customers');
        }

        $translations = module_path('CustomerStatements', 'Resources/lang');
        if (is_dir($translations)) {
            $this->loadTranslationsFrom($translations, 'customerstatements');
        }

        $migrations = module_path('CustomerStatements', 'Database/Migrations');
        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }
}

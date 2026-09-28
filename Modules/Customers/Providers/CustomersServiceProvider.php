<?php

namespace Modules\Customers\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factory;
use Modules\Customers\Http\Middleware\EnsureCustomersAccess;
use Modules\Customers\Http\Middleware\CustomerAccessMiddleware;
use Modules\Customers\Http\Middleware\CustomerPortalMiddleware;
use Modules\Customers\Http\Middleware\CustomerApprovalMiddleware;
use Modules\Customers\Http\Middleware\CustomerCreditMiddleware;
use Modules\Customers\Http\Middleware\CustomerReportMiddleware;
use Modules\Customers\Http\Middleware\CustomersFeatureMiddleware;
use Modules\Customers\Services\CustomerPermissionService;
use Modules\Customers\Services\CustomerPaymentActionService;
use Modules\Customers\Services\CustomerPaymentReferenceService;
use Modules\Customers\Services\CustomerReceivableService;
use Modules\Customers\Services\CustomerService;

class CustomersServiceProvider extends ServiceProvider
{
    /**
     * Module name.
     *
     * @var string
     */
    protected $moduleName = 'Customers';

    /**
     * Module name lowercase.
     *
     * @var string
     */
    protected $moduleNameLower = 'customers';

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        // Route middleware alias used to block direct URL access when the
        // Customers module is disabled or the user role has no permission.
        $router = $this->app['router'];
        $router->aliasMiddleware('customers.access', EnsureCustomersAccess::class);

        // CUS_SEP_009: Dedicated Customers module middleware aliases.
        // Keep these inside the Customers module so future Customer routes do
        // not depend on Contact module middleware or global ERP sidebars.
        $router->aliasMiddleware('customers.module.access', CustomerAccessMiddleware::class);
        $router->aliasMiddleware('customers.portal.access', CustomerPortalMiddleware::class);
        $router->aliasMiddleware('customers.approval.access', CustomerApprovalMiddleware::class);
        $router->aliasMiddleware('customers.credit.access', CustomerCreditMiddleware::class);
        $router->aliasMiddleware('customers.report.access', CustomerReportMiddleware::class);

        // CUSTOMERS_SOURCE_HARDENING_V1: optional feature-table gate.
        $router->aliasMiddleware('customers.feature', CustomersFeatureMiddleware::class);

        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        // Keep the fast All Customers Total Due cache accurate even when the
        // underlying customer/sale/payment write originates from another module.
        // DB::listen sees both Eloquent and query-builder writes without changing
        // any core file.  Only the current business cache is marked dirty.
        $this->registerOverallTotalDueDirtyListener();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Scoped bindings share request-local caches between middleware and
        // controllers without leaking tenant/user data across Octane requests.
        $this->app->scoped(CustomerPermissionService::class, function () {
            return new CustomerPermissionService();
        });
        $this->app->scoped(CustomerPaymentActionService::class, function () {
            return new CustomerPaymentActionService();
        });
        $this->app->scoped(CustomerPaymentReferenceService::class, function () {
            return new CustomerPaymentReferenceService();
        });
        $this->app->scoped(CustomerReceivableService::class, function () {
            return new CustomerReceivableService();
        });

        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path($this->moduleNameLower . '.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/permissions.php'),
            $this->moduleNameLower . '.permissions'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);

        $sourcePath = module_path($this->moduleName, 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', $this->moduleNameLower . '-module-views']);

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/' . $this->moduleNameLower;
        }, config('view.paths')), [$sourcePath]), $this->moduleNameLower);
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(
                module_path($this->moduleName, 'Resources/lang'),
                $this->moduleNameLower
            );
        }
    }

    /**
     * Mark the cached overall receivable as dirty whenever Laravel changes a
     * source table that can affect Customer Total Due.  We deliberately keep the
     * last verified value in cache so the register card can render immediately;
     * CustomerController refreshes the exact value after the response.
     */
    protected function registerOverallTotalDueDirtyListener(): void
    {
        DB::listen(function ($query) {
            $sql = strtolower(ltrim((string) $query->sql));

            if (!preg_match('/^(insert|update|delete|replace|truncate)\b/', $sql)) {
                return;
            }

            $relevant = false;
            foreach (['transactions', 'transaction_payments', 'contact_ledgers', 'contacts'] as $table) {
                if (strpos($sql, '`' . $table . '`') !== false
                    || preg_match('/\b' . preg_quote($table, '/') . '\b/', $sql)) {
                    $relevant = true;
                    break;
                }
            }

            if (!$relevant) {
                return;
            }

            try {
                $businessId = request()->hasSession()
                    ? (int) request()->session()->get('user.business_id')
                    : 0;

                if ($businessId > 0) {
                    app(CustomerService::class)->markOverallTotalDueDirty($businessId);
                }
            } catch (\Throwable $e) {
                // A CLI/bootstrap query can occur before an HTTP session exists.
                // Never let cache maintenance interfere with the business write.
            }
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
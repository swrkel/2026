<?php

namespace Modules\SimpleAudit\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\SimpleAudit\Console\DiagnoseSimpleAuditCommand;
use Modules\SimpleAudit\Console\InstallSimpleAuditCommand;
use Modules\SimpleAudit\Console\RoutesStatusCommand;
use Modules\SimpleAudit\Console\SnapshotStockCommand;
use Modules\SimpleAudit\Console\TestPurchaseAuditCommand;
use Modules\SimpleAudit\Console\TenantsStatusCommand;
use Modules\SimpleAudit\Services\AccessService;
use Modules\SimpleAudit\Services\ContextService;
use Modules\SimpleAudit\Services\PurchaseAuditService;
use Modules\SimpleAudit\Services\ReportExportService;
use Modules\SimpleAudit\Services\SchemaInstaller;
use Modules\SimpleAudit\Services\TenantConnectionManager;

class SimpleAuditServiceProvider extends ServiceProvider
{
    /**
     * Request/process-local route guard.
     *
     * The previous provider inspected the complete application route collection
     * during boot and also scheduled a second route-registration pass in the
     * application's booted callback. On this very large ERP that extra work is
     * unnecessary. The host bootstrap bridge already guarantees this provider
     * is registered when SimpleAudit is enabled, so the module now loads its
     * small route file exactly once per PHP process.
     */
    protected static $moduleRoutesRegistered = false;

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'simpleaudit');

        $this->app->singleton(TenantConnectionManager::class);
        $this->app->singleton(AccessService::class);
        $this->app->singleton(ContextService::class);
        $this->app->singleton(PurchaseAuditService::class);
        $this->app->singleton(ReportExportService::class);
        $this->app->singleton(SchemaInstaller::class);
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'simpleaudit');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'simpleaudit');

        $this->registerModuleRoutes();

        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('simpleaudit.php'),
        ], 'simpleaudit-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallSimpleAuditCommand::class,
                SnapshotStockCommand::class,
                DiagnoseSimpleAuditCommand::class,
                RoutesStatusCommand::class,
                TestPurchaseAuditCommand::class,
                TenantsStatusCommand::class,
            ]);
        }
    }

    /**
     * Load only the module's own small route file once.
     *
     * No scan of the application's full route collection is performed here.
     * This keeps Simple Audit boot lightweight under the server's existing
     * 128 MB PHP CLI memory limit.
     */
    protected function registerModuleRoutes()
    {
        if (self::$moduleRoutesRegistered) {
            return;
        }

        $routeFile = __DIR__ . '/../Routes/web.php';
        if (!is_file($routeFile)) {
            return;
        }

        require $routeFile;
        self::$moduleRoutesRegistered = true;
    }
}

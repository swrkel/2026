<?php
namespace Modules\EggManagement\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\View;
use Modules\EggManagement\Http\Middleware\EggPermission;
use Modules\EggManagement\Services\EggContext;
use Modules\EggManagement\Services\AuthorizationService;
use Modules\EggManagement\Integrations\CustomerGateway;
use Modules\EggManagement\Integrations\SupplierGateway;
use Modules\EggManagement\Integrations\ProductGateway;
use Modules\EggManagement\Integrations\FinanceGateway;
use Modules\EggManagement\Integrations\MessagingGateway;
use Modules\EggManagement\Integrations\LocationStoreGateway;
use Modules\EggManagement\Console\Commands\EggHealthCheck;
use Modules\EggManagement\Console\Commands\EggGrantUser;

class EggManagementServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'egg');
        $this->app->singleton(EggContext::class);
        $this->app->singleton(AuthorizationService::class);
        $this->app->singleton(CustomerGateway::class);
        $this->app->singleton(SupplierGateway::class);
        $this->app->singleton(ProductGateway::class);
        $this->app->singleton(FinanceGateway::class);
        $this->app->singleton(MessagingGateway::class);
        $this->app->singleton(LocationStoreGateway::class);
    }

    public function boot(Router $router)
    {
        $router->aliasMiddleware('egg.permission', EggPermission::class);

        $this->app->register(RouteServiceProvider::class);
        $viewPath = __DIR__.'/../Resources/views';
        $this->loadViewsFrom($viewPath, 'egg');
        // Compatibility aliases for host automatic sidebar loaders which derive
        // the Blade namespace from the module name/alias instead of the short
        // standalone namespace used by Egg Management.
        View::addNamespace('eggmanagement', $viewPath);
        View::addNamespace('egg-management', $viewPath);
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'egg');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->publishes([
            __DIR__.'/../Resources/assets' => public_path('modules/egg-management'),
        ], 'egg-assets');
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('egg.php'),
        ], 'egg-config');
        if ($this->app->runningInConsole()) { $this->commands([EggHealthCheck::class, EggGrantUser::class]); }
    }
}

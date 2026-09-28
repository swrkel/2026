<?php

namespace Modules\Product\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Product\Http\Middleware\EnsureProductPermission;

class ProductServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Product';
    protected string $moduleNameLower = 'product';

    public function boot(): void
    {
        $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);

        // PROD-012: keep legacy Product views standalone by resolving un-namespaced
        // view names like product.index, brand.index, unit.index and import_products.index
        // from this module before Laravel checks the main resources/views folder.
        if ($this->app->bound('view')) {
            $this->app['view']->getFinder()->prependLocation(module_path($this->moduleName, 'Resources/views'));
        }
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        $this->publishes([
            module_path($this->moduleName, 'Public') => public_path('modules/product'),
        ], 'product-assets');

        Route::aliasMiddleware('product.permission', EnsureProductPermission::class);
        Blade::componentNamespace('Modules\\Product\\View\\Components', 'product');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower);
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/permissions.php'), 'product_permissions');
    }
}

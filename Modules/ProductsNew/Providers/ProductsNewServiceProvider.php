<?php

namespace Modules\ProductsNew\Providers;

use Illuminate\Support\ServiceProvider;

class ProductsNewServiceProvider extends ServiceProvider
{
    protected $moduleNameLower = 'productsnew';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', $this->moduleNameLower);

        if (! $this->app->getProvider(RouteServiceProvider::class)) {
            $this->app->register(RouteServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $this->registerViews();
        $this->registerTranslations();
        $this->registerMigrations();
    }

    protected function registerViews(): void
    {
        $sourcePath = __DIR__ . '/../Resources/views';
        $viewPath = resource_path('views/modules/' . $this->moduleNameLower);
        $paths = [];

        foreach ((array) config('view.paths', []) as $path) {
            if (is_dir($path . '/modules/' . $this->moduleNameLower)) {
                $paths[] = $path . '/modules/' . $this->moduleNameLower;
            }
        }

        /*
         * IS2215: Products New is maintained as a standalone module. Keep the
         * module's current views first so an old copied override under the main
         * application's resources/views/modules/productsnew cannot silently
         * resurrect an older Edit Pricing form that drops profit_basis.
         */
        $viewPaths = array_values(array_unique(array_filter(
            array_merge([$sourcePath], $paths, [$viewPath]),
            'is_dir'
        )));
        $this->loadViewsFrom($viewPaths, $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        foreach ([resource_path('lang/modules/' . $this->moduleNameLower), __DIR__ . '/../Resources/lang'] as $path) {
            if (is_dir($path)) {
                $this->loadTranslationsFrom($path, $this->moduleNameLower);
            }
        }
    }

    protected function registerMigrations(): void
    {
        $path = __DIR__ . '/../Database/Migrations';
        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }
}

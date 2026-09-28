<?php

namespace Modules\Ran\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Modules\Ran\Http\Middleware\EnsureRanEnabled;
use Modules\Ran\Http\Middleware\EnsureRanPageEnabled;

class RanServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Ran';
    protected string $moduleNameLower = 'ran';

    public function boot(): void
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
        $this->publishes([
            module_path($this->moduleName, 'Config/config.php') => config_path('ran.php'),
        ], 'ran-config');
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app['router']->aliasMiddleware('ran.enabled', EnsureRanEnabled::class);
        $this->app['router']->aliasMiddleware('ran.page', EnsureRanPageEnabled::class);
    }

    protected function registerConfig(): void
    {
        foreach (['config', 'menu', 'permissions'] as $file) {
            $path = module_path($this->moduleName, 'Config/'.$file.'.php');
            if (is_file($path)) {
                $key = $file === 'config' ? $this->moduleNameLower : $this->moduleNameLower.'.'.$file;
                $this->mergeConfigFrom($path, $key);
            }
        }
    }

    protected function registerViews(): void
    {
        $source = module_path($this->moduleName, 'Resources/views');
        $published = resource_path('views/modules/'.$this->moduleNameLower);
        $paths = [];
        foreach ((array) Config::get('view.paths', []) as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }
        $this->loadViewsFrom(array_merge($paths, [$source, $published]), $this->moduleNameLower);
    }

    protected function registerTranslations(): void
    {
        $published = resource_path('lang/modules/'.$this->moduleNameLower);
        $source = module_path($this->moduleName, 'Resources/lang');
        if (is_dir($published)) {
            $this->loadTranslationsFrom($published, $this->moduleNameLower);
        }
        $this->loadTranslationsFrom($source, $this->moduleNameLower);
    }
}

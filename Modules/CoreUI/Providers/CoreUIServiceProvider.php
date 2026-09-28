<?php

namespace Modules\CoreUI\Providers;

use Illuminate\Support\ServiceProvider;

class CoreUIServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'CoreUI';
    protected string $moduleNameLower = 'coreui';

    public function boot(): void
    {
        $this->loadViewsFrom(module_path($this->moduleName, 'Resources/views'), $this->moduleNameLower);
        $this->loadTranslationsFrom(module_path($this->moduleName, 'Resources/lang'), $this->moduleNameLower);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(module_path($this->moduleName, 'Config/config.php'), $this->moduleNameLower);
    }
}

<?php

namespace Modules\EnterpriseFramework\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\EnterpriseFramework\Services\Registry\ReportRegistryService;

class EnterpriseFrameworkServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'enterpriseframework');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'enterpriseframework');
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'enterpriseframework');
    }

    public function register(): void
    {
        $this->app->singleton(ReportRegistryService::class, function () {
            return new ReportRegistryService();
        });
    }
}

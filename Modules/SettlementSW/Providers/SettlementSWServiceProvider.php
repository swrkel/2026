<?php
namespace Modules\SettlementSW\Providers;

use Illuminate\Support\ServiceProvider;

class SettlementSWServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load the module’s routes:
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');

        // Load its views under the "settlementsw::" namespace:
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'settlementsw');

        //load translation
        $this->registerTranslations();

        // SW_SEP_005: publish Settlement SW module JS/CSS under public/modules/settlementsw.
        $this->publishes([
            __DIR__ . '/../Resources/assets' => public_path('modules/settlementsw'),
        ], 'settlementsw-assets');
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/settlementsw.php', 'settlementsw');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/SettlementSW');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'SettlementSW');
            $this->loadTranslationsFrom($langPath, 'settlementsw');
        } else {
            $moduleLangPath = __DIR__ . '/../Resources/lang';
            $this->loadTranslationsFrom($moduleLangPath, 'SettlementSW');
            $this->loadTranslationsFrom($moduleLangPath, 'settlementsw');
        }
    }
}

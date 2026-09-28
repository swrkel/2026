<?php
namespace Modules\EzyLaw\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\EzyLaw\Contracts\FinanceGateway;
use Modules\EzyLaw\Services\Integrations\OptionalFinanceGateway;
use Modules\EzyLaw\Http\Middleware\EnsureEzyLawEnabled;
use Modules\EzyLaw\Console\Commands\ProcessNotifications;

class EzyLawServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/config.php', 'ezylaw');
        $this->app->singleton(FinanceGateway::class, OptionalFinanceGateway::class);
        if (! $this->app->getProvider(RouteServiceProvider::class)) {
            $this->app->register(RouteServiceProvider::class);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'ezylaw');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'ezylaw');
        if ($this->app->runningInConsole()) {
            $this->commands([ProcessNotifications::class]);
        }
        if ((bool) config('ezylaw.auto_load_migrations', false)) {
            $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        }
        if ($this->app->bound('router')) {
            $this->app['router']->aliasMiddleware('ezylaw.enabled', EnsureEzyLawEnabled::class);
        }
        $this->publishes([
            __DIR__.'/../Resources/assets' => public_path('modules/ezylaw'),
        ], 'ezylaw-assets');
    }
}

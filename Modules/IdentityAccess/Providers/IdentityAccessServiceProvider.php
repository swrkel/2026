<?php

namespace Modules\IdentityAccess\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\IdentityAccess\Services\Auth\IdentityAuthenticationService;
use Modules\IdentityAccess\Services\Session\IdentitySessionService;
use Modules\IdentityAccess\Services\Policy\IdentityPolicyService;

class IdentityAccessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'identityaccess');
        $this->app->singleton(IdentityAuthenticationService::class);
        $this->app->singleton(IdentitySessionService::class);
        $this->app->singleton(IdentityPolicyService::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'identityaccess');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'identityaccess');
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->publishes([__DIR__ . '/../Config/config.php' => config_path('identityaccess.php')], 'identityaccess-config');
    }
}

<?php

namespace Modules\Audit\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Audit\Console\Commands\AuditDiagnoseCommand;
use Modules\Audit\Console\Commands\AuditRunCommand;
use Modules\Audit\Console\Commands\AuditScheduledCommand;
use Modules\Audit\Http\Middleware\AuditPermission;
use Modules\Audit\Http\Middleware\CentralAuditOnly;

class AuditServiceProvider extends ServiceProvider
{
    protected $moduleName = 'Audit';
    protected $moduleNameLower = 'audit';

    public function register()
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'Config/config.php'),
            $this->moduleNameLower
        );
    }

    public function boot()
    {
        $this->app['router']->aliasMiddleware('audit.permission', AuditPermission::class);
        $this->app['router']->aliasMiddleware('audit.central', CentralAuditOnly::class);

        $this->registerViews();
        $this->registerTranslations();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));

        $assetPath = module_path($this->moduleName, 'Resources/assets');
        if (is_dir($assetPath)) {
            $this->publishes([$assetPath => public_path('modules/audit')], 'audit-assets');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                AuditRunCommand::class,
                AuditScheduledCommand::class,
                AuditDiagnoseCommand::class,
            ]);
        }

        $this->app->booted(function (): void {
            if (!config('audit.scheduled.enabled', false)) {
                return;
            }

            try {
                app(Schedule::class)
                    ->command('audit:scheduled --all-tenants')
                    ->hourly()
                    ->withoutOverlapping();
            } catch (\Throwable $e) {
                // Optional scheduling must never affect host application boot.
            }
        });
    }

    protected function registerViews(): void
    {
        $sourcePath = module_path($this->moduleName, 'Resources/views');
        if (is_dir($sourcePath)) {
            $this->loadViewsFrom($sourcePath, $this->moduleNameLower);
        }
    }

    protected function registerTranslations(): void
    {
        $sourcePath = module_path($this->moduleName, 'Resources/lang');
        if (is_dir($sourcePath)) {
            $this->loadTranslationsFrom($sourcePath, $this->moduleNameLower);
        }
    }
}

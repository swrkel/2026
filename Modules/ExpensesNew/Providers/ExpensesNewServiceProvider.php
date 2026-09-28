<?php

namespace Modules\ExpensesNew\Providers;

use Illuminate\Support\ServiceProvider;

class ExpensesNewServiceProvider extends ServiceProvider
{
    /**
     * Register module services before the module boots.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $configFiles = [
            'permissions' => 'permissions.php',
            'reports' => 'expensesnew_reports.php',
            'command_center' => 'command_center.php',
            'integration' => 'integration.php',
            'costing' => 'costing.php',
            'financial_intelligence' => 'financial_intelligence.php',
            'expnew005_permissions' => 'expnew005_permissions.php',
            'expnew006_permissions' => 'expnew006_permissions.php',
        ];

        foreach ($configFiles as $key => $file) {
            $path = __DIR__ . '/../Config/' . $file;
            if (is_file($path)) {
                $this->mergeConfigFrom($path, 'expensesnew.' . $key);
            }
        }
    }

    /**
     * Register module resources. Routes are owned by RouteServiceProvider so
     * they are loaded once and are compatible with the current Laravel route
     * lifecycle.
     */
    public function boot(): void
    {
        $views = __DIR__ . '/../Resources/views';
        $translations = __DIR__ . '/../Resources/lang';
        $migrations = __DIR__ . '/../Database/Migrations';

        if (is_dir($views)) {
            $this->loadViewsFrom($views, 'expensesnew');
        }

        if (is_dir($translations)) {
            $this->loadTranslationsFrom($translations, 'expensesnew');
        }

        if (is_dir($migrations)) {
            $this->loadMigrationsFrom($migrations);
        }
    }
}

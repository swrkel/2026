<?php

namespace Modules\MyHealthMembers\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = 'Modules\MyHealthMembers\Http\Controllers';

    /** Prevent canonical MyHealth routes from being mapped more than once per process. */
    protected static bool $routesMapped = false;

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        if (static::$routesMapped) {
            return;
        }

        static::$routesMapped = true;

        $webRouteFiles = [
            'web.php',
            'members.php',
            'doctors.php',
            'documents.php',
            'labs.php',
            'pharmacy.php',
            'insurance.php',
            'telemedicine.php',
            'billing.php',
            'reports.php',
            'settings.php',
            'hospital.php',
            'nursing.php',
            'laboratory.php',
            'radiology.php',
            'operation_theatre.php',
            'vaccination.php',
            'analytics.php',
            'ai_clinical.php',
            'clinical.php',
            'notifications.php',
            'access.php',
            'admin.php',
            'audit.php',
            'enterprise.php',
            'disaster_recovery.php',
            'production_certification.php',
        ];

        $this->mapWebRoutes($webRouteFiles);
        $this->mapPublicRoutes();
        $this->mapPortalCompatibilityRoutes();
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes(array $routeFiles): void
    {
        foreach (array_unique($routeFiles) as $routeFile) {
            $path = module_path('MyHealthMembers', '/Routes/' . $routeFile);

            if (file_exists($path)) {
                Route::middleware(['web', 'auth', 'SetSessionData', 'language', 'timezone'])
                    ->namespace($this->moduleNamespace)
                    ->group($path);
            }
        }
    }

    protected function mapPublicRoutes(): void
    {
        $path = module_path('MyHealthMembers', '/Routes/public.php');

        if (! file_exists($path)) {
            return;
        }

        // Avoid duplicate route registration when the service provider fallback has already
        // loaded the public login/registration routes for the unauthenticated login page.
        if (! Route::has('myhealth.public.login.create')) {
            Route::middleware(['web', 'language', 'timezone'])
                ->namespace($this->moduleNamespace)
                ->group($path);
        }
    }

    protected function mapPortalCompatibilityRoutes(): void
    {
        $path = module_path('MyHealthMembers', '/Routes/portal.php');

        if (! file_exists($path)) {
            return;
        }

        if (! Route::has('myhealth.portal.dashboard')) {
            Route::middleware(['web', 'language', 'timezone'])
                ->namespace($this->moduleNamespace)
                ->group($path);
        }
    }

    protected function mapApiRoutes(): void
    {
        $path = module_path('MyHealthMembers', '/Routes/api.php');

        if (file_exists($path)) {
            Route::middleware(['api'])
                ->namespace($this->moduleNamespace)
                ->group($path);
        }
    }
}

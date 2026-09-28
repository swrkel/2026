<?php

namespace Modules\PumperDashboardNew\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\PumperDashboardNew\Http\Middleware\EnsurePoneOperatorSession;
use Modules\PumperDashboardNew\Http\Middleware\EnsurePoneSchema;
use Modules\PumperDashboardNew\Http\Middleware\InitializePoneTenantContext;

class RouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->routes(function (): void {
            $root = dirname(__DIR__);
            $prefix = trim((string) config('pumperdashboardnew.route_prefix', 'pumper-dashboard-new'), '/ ');
            $name = trim((string) config('pumperdashboardnew.route_name', 'pumper-dashboard-new.'), '. ') . '.';

            Route::middleware([
                'web',
                InitializePoneTenantContext::class,
            ])
                ->prefix($prefix)
                ->name($name)
                ->group($root . '/Routes/public.php');

            Route::middleware([
                'web',
                InitializePoneTenantContext::class,
                EnsurePoneSchema::class,
                'auth',
                EnsurePoneOperatorSession::class,
            ])
                ->prefix($prefix)
                ->name($name)
                ->group($root . '/Routes/operator.php');

            Route::middleware([
                'web',
                InitializePoneTenantContext::class,
                EnsurePoneSchema::class,
                'auth',
            ])
                ->prefix($prefix)
                ->name($name)
                ->group($root . '/Routes/web.php');
        });
    }
}

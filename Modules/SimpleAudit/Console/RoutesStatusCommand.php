<?php

namespace Modules\SimpleAudit\Console;

use Illuminate\Console\Command;

class RoutesStatusCommand extends Command
{
    protected $signature = 'simple-audit:routes';
    protected $description = 'Show only Simple Audit routes without running Laravel route:list.';

    public function handle()
    {
        $router = $this->laravel->make('router');
        $routes = $router->getRoutes();

        $wanted = [
            'simpleaudit.home',
            'simpleaudit.purchase-audit',
            'simpleaudit.purchase-audit.data',
            'simpleaudit.purchase-audit.details',
            'simpleaudit.purchase-audit.export',
            'simpleaudit.purchase-audit.print',
            'simpleaudit.purchase-audit.share',
            'simpleaudit.purchase-audit.email',
            'simpleaudit.context.tenants',
            'simpleaudit.context.businesses',
            'simpleaudit.context.locations',
            'simpleaudit.context.stores',
            'simpleaudit.asset',
            'simpleaudit.share.public',
        ];

        $missing = 0;
        $this->line('Simple Audit routes (low-memory check):');

        foreach ($wanted as $name) {
            $route = method_exists($routes, 'getByName') ? $routes->getByName($name) : null;
            if (!$route) {
                $this->error('[MISSING] ' . $name);
                $missing++;
                continue;
            }

            $methods = method_exists($route, 'methods') ? implode('|', $route->methods()) : '';
            $uri = method_exists($route, 'uri') ? '/' . ltrim($route->uri(), '/') : '';
            $this->line('[OK] ' . str_pad($name, 42) . ' ' . str_pad($methods, 12) . ' ' . $uri);
        }

        if ($missing > 0) {
            $this->error('Result: FAIL - ' . $missing . ' Simple Audit route(s) are missing.');
            return self::FAILURE;
        }

        $this->info('Result: PASS - Simple Audit routes are registered.');
        $this->line('Note: this command intentionally avoids the global `php artisan route:list`, which is expensive on this ERP.');

        return self::SUCCESS;
    }
}

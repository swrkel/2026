<?php

namespace Modules\BankingUI\Services;

use Illuminate\Support\Facades\Route;

class BankingRouteHealthService
{
    public function routes(): array
    {
        $expected = [
            'banking.tester-dashboard', 'banking.testing-checklist', 'banking.route-health',
            'banking.navigation-audit', 'banking.placeholder',
        ];

        return collect($expected)->map(function ($name) {
            return [
                'name' => $name,
                'registered' => Route::has($name),
                'url' => Route::has($name) ? (str_contains($name, 'placeholder') ? route($name, ['module' => 'sample']) : route($name)) : null,
            ];
        })->all();
    }

    public function summary(): array
    {
        $routes = $this->routes();
        return [
            'total' => count($routes),
            'registered' => collect($routes)->where('registered', true)->count(),
        ];
    }
}

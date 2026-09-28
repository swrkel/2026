<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class LiveSupportController extends Controller
{
    public function index()
    {
        $requiredRoutes = [
            '/pos-module',
            '/pos-module/sales/workspace',
            '/pos-module/products',
            '/pos-module/purchases',
            '/pos-module/registers',
            '/pos-module/shifts',
            '/pos-module/cash-drawer',
            '/pos-module/returns',
            '/pos-module/exchanges',
            '/pos-module/reports',
            '/pos-module/settings',
            '/pos-module/standalone-audit',
        ];

        $registeredUris = collect(Route::getRoutes())->map(function ($route) {
            return '/' . ltrim($route->uri(), '/');
        })->values()->all();

        $routeChecks = collect($requiredRoutes)->map(function ($uri) use ($registeredUris) {
            return [
                'uri' => $uri,
                'ok' => in_array(ltrim($uri, '/'), array_map(function ($r) { return ltrim($r, '/'); }, $registeredUris), true),
            ];
        })->all();

        $tableChecks = collect([
            'pos_products',
            'pos_sales',
            'pos_sale_lines',
            'pos_payments',
            'pos_registers',
            'pos_shift_sessions',
            'pos_cash_drawer_movements',
            'pos_returns',
            'pos_return_lines',
            'pos_settings',
        ])->map(function ($table) {
            try {
                $exists = Schema::hasTable($table);
            } catch (\Throwable $e) {
                $exists = false;
            }
            return ['table' => $table, 'ok' => $exists];
        })->all();

        return view('pos::support.live_testing', compact('routeChecks', 'tableChecks'));
    }
}

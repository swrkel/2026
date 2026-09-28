<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ProductionReadinessController extends Controller
{
    public function index()
    {
        $requiredRoutes = [
            'pos.dashboard' => '/pos-module',
            'pos.sales.workspace' => '/pos-module/sales/workspace',
            'pos.products.index' => '/pos-module/products',
            'pos.purchases.index' => '/pos-module/purchases',
            'pos.registers.index' => '/pos-module/registers',
            'pos.shifts.index' => '/pos-module/shifts',
            'pos.cash_drawer.index' => '/pos-module/cash-drawer',
            'pos.returns.index' => '/pos-module/returns',
            'pos.reports.index' => '/pos-module/reports',
            'pos.settings.index' => '/pos-module/settings',
        ];

        $routeChecks = collect($requiredRoutes)->map(function ($url, $name) {
            return [
                'name' => $name,
                'url' => $url,
                'ok' => Route::has($name),
            ];
        })->values()->all();

        $requiredTables = [
            'pos_products', 'pos_sales', 'pos_sale_lines', 'pos_payments', 'pos_purchases',
            'pos_registers', 'pos_shift_sessions', 'pos_cash_drawer_movements',
            'pos_returns', 'pos_return_lines', 'pos_settings'
        ];

        $tableChecks = collect($requiredTables)->map(function ($table) {
            try {
                $ok = Schema::hasTable($table);
            } catch (\Throwable $e) {
                $ok = false;
            }
            return ['table' => $table, 'ok' => $ok];
        })->all();

        $uiChecks = [
            'All POS pages should extend the ERP layout and Communication Hub style.',
            'KPI cards should use the same 4-column card style.',
            'Buttons should use the ERP standard colours and sizes.',
            'Tables should use the ERP standard toolbar and spacing.',
            'No POS page should show the old dark/custom standalone layout.',
        ];

        $businessChecks = [
            'Complete one cash sale, one card sale and one credit sale.',
            'Confirm Customers module ledger posting for credit sale/payment.',
            'Confirm purchase increases stock and sale reduces stock.',
            'Confirm return/exchange restores/reduces stock correctly.',
            'Close a shift and compare cash/card/credit/refund totals with reports.',
            'Print 58mm/80mm receipt and A4 invoice for alignment.',
        ];

        return view('pos::support.production_readiness', compact('routeChecks', 'tableChecks', 'uiChecks', 'businessChecks'));
    }
}

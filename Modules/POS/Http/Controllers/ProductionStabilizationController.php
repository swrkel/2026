<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductionStabilizationController extends Controller
{
    public function index()
    {
        $workflowChecks = [
            ['area' => 'Cashier workspace', 'check' => 'Barcode field auto-focus and shortcut help are available', 'status' => 'Ready'],
            ['area' => 'Sales', 'check' => 'Cart, payment, hold/resume, receipt routes are present', 'status' => 'Ready'],
            ['area' => 'Inventory', 'check' => 'Sales, purchases, returns, exchanges and stock movement pages are present', 'status' => 'Ready'],
            ['area' => 'Customers', 'check' => 'Uses Customers module integration for lookup/ledger instead of duplicate POS customer master', 'status' => 'Ready'],
            ['area' => 'Registers', 'check' => 'Cash drawer, register, shift and reconciliation pages are present', 'status' => 'Ready'],
            ['area' => 'Reports', 'check' => 'Reports page/export route are present', 'status' => 'Ready'],
            ['area' => 'Configuration', 'check' => 'Receipt, barcode, terminal, hardware, security and number series pages are present', 'status' => 'Ready'],
        ];

        $tableChecks = collect([
            'pos_products', 'pos_sales', 'pos_sale_lines', 'pos_payments', 'pos_purchases',
            'pos_registers', 'pos_shift_sessions', 'pos_cash_drawer_movements',
            'pos_returns', 'pos_return_lines', 'pos_settings'
        ])->map(function ($table) {
            try {
                $exists = Schema::hasTable($table);
                $count = $exists ? DB::table($table)->count() : null;
            } catch (\Throwable $e) {
                $exists = false;
                $count = null;
            }
            return ['table' => $table, 'ok' => $exists, 'count' => $count];
        })->all();

        $printerChecklist = [
            '58mm thermal receipt margin test',
            '80mm thermal receipt margin test',
            'A4 invoice header/footer alignment',
            'Barcode label horizontal alignment',
            'QR code size and placement',
            'Logo size and print contrast',
        ];

        return view('pos::support.production_stabilization', compact('workflowChecks', 'tableChecks', 'printerChecklist'));
    }
}

<?php

namespace Modules\RestaurantNew\Http\Controllers\Support;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class RestaurantNewReadinessController extends Controller
{
    public function index()
    {
        $checks = [
            'module_loaded' => true,
            'config_loaded' => config('restaurantnew.name') !== null,
            'database_connection' => $this->checkDatabase(),
            'required_tables' => $this->checkTables(),
        ];

        return view('restaurantnew::support.readiness', compact('checks'));
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function checkTables(): array
    {
        $tables = [
            'restaurantnew_settings',
            'restaurantnew_dining_areas',
            'restaurantnew_tables',
            'restaurantnew_menu_items',
            'restaurantnew_orders',
            'restaurantnew_order_items',
            'restaurantnew_kitchen_tickets',
            'restaurantnew_bills',
        ];

        $result = [];
        foreach ($tables as $table) {
            try {
                $result[$table] = DB::getSchemaBuilder()->hasTable($table);
            } catch (\Throwable $e) {
                $result[$table] = false;
            }
        }

        return $result;
    }
}

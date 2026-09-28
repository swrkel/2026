<?php

namespace Modules\RestaurantNew\Services\Finalization;

use Illuminate\Support\Facades\Schema;

class RestaurantNewReadinessService
{
    public function checklist(): array
    {
        $requiredTables = [
            'restaurant_new_orders',
            'restaurant_new_order_lines',
            'restaurant_new_kitchen_tickets',
            'restaurant_new_bills',
            'restaurant_new_order_payments',
            'rn_tables',
            'rn_menu_items',
            'rn_settings',
        ];

        $tables = [];
        foreach ($requiredTables as $table) {
            $tables[$table] = Schema::hasTable($table);
        }

        return [
            'module' => 'RestaurantNew',
            'release' => 'RESTNEW_030_ENTERPRISE_FINAL',
            'tables' => $tables,
            'ready' => ! in_array(false, $tables, true),
        ];
    }
}

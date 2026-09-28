<?php

namespace Modules\RestaurantNew\Services\Hardening;

use Illuminate\Support\Facades\Schema;
use Modules\RestaurantNew\Entities\RestaurantNewIntegrityCheck;

class RestaurantNewIntegrityService
{
    public function run(?int $businessId = null, ?int $locationId = null): array
    {
        $checks = [];
        $checks[] = $this->checkRequiredTables($businessId, $locationId);
        $checks[] = $this->checkTenantColumns($businessId, $locationId);
        $checks[] = $this->checkStandaloneAssets($businessId, $locationId);
        return $checks;
    }

    protected function save(?int $businessId, ?int $locationId, string $code, string $group, string $status, string $message, array $details = []): RestaurantNewIntegrityCheck
    {
        return RestaurantNewIntegrityCheck::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'check_code' => $code,
            'check_group' => $group,
            'status' => $status,
            'message' => $message,
            'details' => $details,
            'checked_at' => now(),
        ]);
    }

    protected function checkRequiredTables(?int $businessId, ?int $locationId): RestaurantNewIntegrityCheck
    {
        $required = [
            'restaurant_new_orders', 'restaurant_new_order_lines', 'restaurant_new_kitchen_tickets',
            'restaurant_new_bills', 'restaurant_new_payments', 'restaurant_new_tables',
            'restaurant_new_menu_items', 'restaurant_new_ingredients', 'restaurant_new_stock_movements',
        ];
        $missing = array_values(array_filter($required, fn($table) => !Schema::hasTable($table)));
        return $this->save($businessId, $locationId, 'required_tables', 'database', empty($missing) ? 'passed' : 'failed', empty($missing) ? 'All required RestaurantNew tables exist.' : 'Some RestaurantNew tables are missing.', ['missing' => $missing]);
    }

    protected function checkTenantColumns(?int $businessId, ?int $locationId): RestaurantNewIntegrityCheck
    {
        $tables = ['restaurant_new_orders', 'restaurant_new_bills', 'restaurant_new_payments', 'restaurant_new_kitchen_tickets'];
        $missing = [];
        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) { continue; }
            foreach (['business_id', 'location_id'] as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $missing[] = $table . '.' . $column;
                }
            }
        }
        return $this->save($businessId, $locationId, 'tenant_scope_columns', 'database', empty($missing) ? 'passed' : 'failed', empty($missing) ? 'Tenant/business scope columns are available.' : 'Tenant/business scope columns are missing.', ['missing' => $missing]);
    }

    protected function checkStandaloneAssets(?int $businessId, ?int $locationId): RestaurantNewIntegrityCheck
    {
        $paths = [
            module_path('RestaurantNew', 'Resources/assets/css'),
            module_path('RestaurantNew', 'Resources/assets/js'),
            module_path('RestaurantNew', 'Resources/views'),
            module_path('RestaurantNew', 'Routes'),
        ];
        $missing = array_values(array_filter($paths, fn($path) => !is_dir($path)));
        return $this->save($businessId, $locationId, 'standalone_assets', 'filesystem', empty($missing) ? 'passed' : 'warning', empty($missing) ? 'Standalone assets and route folders exist.' : 'Some standalone folders are missing.', ['missing' => $missing]);
    }
}

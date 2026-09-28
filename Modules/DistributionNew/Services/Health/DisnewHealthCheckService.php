<?php

namespace Modules\DistributionNew\Services\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\DistributionNew\Models\DisnewHealthCheck;

class DisnewHealthCheckService
{
    public function run(?int $businessId = null, ?int $locationId = null, ?int $userId = null): array
    {
        $checks = [];
        foreach ($this->requiredTables() as $table) {
            $checks[] = $this->record($businessId, $locationId, 'table_'.$table, 'database', Schema::hasTable($table) ? 'pass' : 'fail', Schema::hasTable($table) ? "$table exists" : "$table missing", ['table' => $table], $userId);
        }

        foreach ($this->requiredPermissions() as $permission) {
            $checks[] = $this->record($businessId, $locationId, 'permission_'.$permission, 'permissions', 'warning', 'Verify permission is registered in the ERP permission cache.', ['permission' => $permission], $userId);
        }

        $checks[] = $this->record($businessId, $locationId, 'customer_bridge', 'bridges', class_exists('Modules\\DistributionNew\\Services\\DisnewCustomerLookupService') ? 'pass' : 'fail', 'Customer lookup bridge check completed.', [], $userId);
        $checks[] = $this->record($businessId, $locationId, 'sms_bridge', 'bridges', class_exists('Modules\\DistributionNew\\Services\\Sms\\DisnewExistingSmsModuleBridge') ? 'pass' : 'fail', 'Existing SMS module bridge check completed.', [], $userId);

        return $checks;
    }

    protected function record(?int $businessId, ?int $locationId, string $key, string $group, string $status, string $message, array $payload = [], ?int $userId = null): DisnewHealthCheck
    {
        return DisnewHealthCheck::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'check_key' => $key,
            'check_group' => $group,
            'status' => $status,
            'message' => $message,
            'payload' => $payload,
            'checked_by' => $userId,
            'checked_at' => now(),
        ]);
    }

    public function requiredTables(): array
    {
        return [
            'disnew_sales_orders','disnew_sales_order_lines','disnew_sales_invoices','disnew_sales_invoice_lines',
            'disnew_vehicles','disnew_vehicle_stocks','disnew_warehouses','disnew_warehouse_stocks',
            'disnew_loading_plans','disnew_loadings','disnew_unloading_lines','disnew_deliveries',
            'disnew_collections','disnew_settlements','disnew_returns','disnew_credit_notes',
            'disnew_sms_logs','disnew_health_checks','disnew_installation_steps','disnew_export_logs'
        ];
    }

    public function requiredPermissions(): array
    {
        return [
            'distributionnew.view','distributionnew.create','distributionnew.update','distributionnew.delete',
            'distributionnew.sales_order.view','distributionnew.sales_invoice.view','distributionnew.loading.view',
            'distributionnew.unloading.view','distributionnew.vehicle.view','distributionnew.reports.view','distributionnew.audit.view'
        ];
    }
}

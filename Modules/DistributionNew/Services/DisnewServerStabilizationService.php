<?php

namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\DistributionNew\Models\DisnewServerCheck;

class DisnewServerStabilizationService
{
    public function run(?int $businessId = null, ?int $locationId = null, ?int $userId = null): array
    {
        $checks = [
            $this->checkTable('disnew_sales_orders', 'Sales order table'),
            $this->checkTable('disnew_sales_invoices', 'Sales invoice table'),
            $this->checkTable('disnew_vehicles', 'Vehicle table'),
            $this->checkTable('disnew_vehicle_stock_balances', 'Vehicle stock table'),
            $this->checkTable('disnew_sms_event_logs', 'SMS bridge event table'),
            $this->checkRoute('distribution-new.stabilization.index', 'Stabilization route'),
            $this->checkRoute('distribution-new.dashboard.index', 'Dashboard route'),
            $this->checkRoute('distribution-new.sales-orders.index', 'Sales order route'),
            $this->checkRoute('distribution-new.sales-invoices.index', 'Sales invoice route'),
        ];

        foreach ($checks as $check) {
            DisnewServerCheck::create([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'check_code' => $check['code'],
                'check_name' => $check['name'],
                'status' => $check['status'],
                'message' => $check['message'],
                'repair_hint' => $check['repair_hint'],
                'checked_by' => $userId,
                'checked_at' => now(),
            ]);
        }

        return $checks;
    }

    protected function checkTable(string $table, string $name): array
    {
        $ok = Schema::hasTable($table);
        return [
            'code' => 'table_' . $table,
            'name' => $name,
            'status' => $ok ? 'passed' : 'failed',
            'message' => $ok ? "Table {$table} exists." : "Table {$table} is missing.",
            'repair_hint' => $ok ? null : 'Run the stage SQL/master SQL or Laravel migrations for Distribution New on this tenant database.',
        ];
    }

    protected function checkRoute(string $routeName, string $name): array
    {
        $ok = Route::has($routeName);
        return [
            'code' => 'route_' . str_replace('.', '_', $routeName),
            'name' => $name,
            'status' => $ok ? 'passed' : 'failed',
            'message' => $ok ? "Route {$routeName} is registered." : "Route {$routeName} is missing.",
            'repair_hint' => $ok ? null : 'Clear route cache and verify DistributionNew service provider loads module route files.',
        ];
    }

    public function latest(?int $businessId = null): array
    {
        return DisnewServerCheck::query()
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->latest('checked_at')
            ->limit(50)
            ->get()
            ->toArray();
    }
}

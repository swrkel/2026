<?php

namespace Modules\DistributionNew\Services\ServerTesting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DisnewServerTestingService
{
    protected array $requiredTables = [
        'disnew_sales_orders','disnew_sales_order_lines','disnew_sales_invoices','disnew_sales_invoice_lines',
        'disnew_vehicles','disnew_vehicle_stock_balances','disnew_loading_plans','disnew_loadings',
        'disnew_unloadings','disnew_deliveries','disnew_collections','disnew_settlements','disnew_returns',
        'disnew_credit_notes','disnew_warehouses','disnew_warehouse_stock_balances','disnew_sms_logs'
    ];

    protected array $requiredRoutes = [
        'distributionnew.dashboard','distributionnew.sales-orders.index','distributionnew.sales-invoices.index',
        'distributionnew.vehicles.index','distributionnew.loading.index','distributionnew.unloading.index',
        'distributionnew.deliveries.index','distributionnew.collections.index','distributionnew.reports.index',
        'distributionnew.server-testing.index'
    ];

    public function summary(int $businessId): array
    {
        return [
            'business_id' => $businessId,
            'missing_tables' => $this->missingTables(),
            'missing_routes' => $this->missingRoutes(),
            'record_counts' => $this->recordCounts(),
            'last_run' => $this->lastRun($businessId),
        ];
    }

    public function runFullCheck(int $businessId, ?int $userId = null): array
    {
        $checks = [
            'tables' => ['status' => count($this->missingTables()) === 0 ? 'passed' : 'failed', 'missing' => $this->missingTables()],
            'routes' => ['status' => count($this->missingRoutes()) === 0 ? 'passed' : 'failed', 'missing' => $this->missingRoutes()],
            'business_scope' => $this->checkBusinessScope($businessId),
            'stock_integrity' => $this->checkStockIntegrity($businessId),
            'order_invoice_integrity' => $this->checkOrderInvoiceIntegrity($businessId),
            'sms_bridge' => $this->checkSmsBridge($businessId),
        ];

        $overall = collect($checks)->contains(fn($check) => ($check['status'] ?? 'failed') !== 'passed') ? 'attention_required' : 'passed';
        $this->storeRun($businessId, $userId, $overall, $checks);
        return ['overall' => $overall, 'checks' => $checks];
    }

    protected function missingTables(): array
    {
        return array_values(array_filter($this->requiredTables, fn($table) => !Schema::hasTable($table)));
    }

    protected function missingRoutes(): array
    {
        return array_values(array_filter($this->requiredRoutes, fn($name) => !Route::has($name)));
    }

    protected function recordCounts(): array
    {
        $counts = [];
        foreach ($this->requiredTables as $table) {
            $counts[$table] = Schema::hasTable($table) ? DB::table($table)->count() : null;
        }
        return $counts;
    }

    protected function checkBusinessScope(int $businessId): array
    {
        $issues = [];
        foreach ($this->requiredTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'business_id')) {
                $count = DB::table($table)->whereNull('business_id')->count();
                if ($count > 0) { $issues[$table] = $count; }
            }
        }
        return ['status' => empty($issues) ? 'passed' : 'failed', 'issues' => $issues];
    }

    protected function checkStockIntegrity(int $businessId): array
    {
        $issues = [];
        foreach (['disnew_vehicle_stock_balances','disnew_warehouse_stock_balances'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'qty_available')) {
                $negative = DB::table($table)->where('business_id', $businessId)->where('qty_available', '<', 0)->count();
                if ($negative > 0) { $issues[$table] = $negative; }
            }
        }
        return ['status' => empty($issues) ? 'passed' : 'failed', 'negative_stock_rows' => $issues];
    }

    protected function checkOrderInvoiceIntegrity(int $businessId): array
    {
        if (!Schema::hasTable('disnew_sales_orders') || !Schema::hasTable('disnew_sales_invoices')) {
            return ['status' => 'failed', 'message' => 'Sales order or invoice tables missing'];
        }
        return ['status' => 'passed', 'message' => 'Core order/invoice tables are available'];
    }

    protected function checkSmsBridge(int $businessId): array
    {
        $hasLogs = Schema::hasTable('disnew_sms_logs');
        $hasTemplates = Schema::hasTable('disnew_sms_templates');
        return ['status' => ($hasLogs && $hasTemplates) ? 'passed' : 'failed', 'logs_table' => $hasLogs, 'templates_table' => $hasTemplates];
    }

    protected function lastRun(int $businessId): ?object
    {
        if (!Schema::hasTable('disnew_server_testing_runs')) { return null; }
        return DB::table('disnew_server_testing_runs')->where('business_id', $businessId)->latest('id')->first();
    }

    protected function storeRun(int $businessId, ?int $userId, string $overall, array $checks): void
    {
        if (!Schema::hasTable('disnew_server_testing_runs')) { return; }
        DB::table('disnew_server_testing_runs')->insert([
            'business_id' => $businessId,
            'user_id' => $userId,
            'overall_status' => $overall,
            'payload' => json_encode($checks),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function testingChecklist(int $businessId): string
    {
        return "Distribution New Server Testing Checklist
".
            "Business ID: {$businessId}

".
            "1. Open /distribution-new and confirm dashboard loads.
".
            "2. Open Sales Orders, create draft order, approve it, then create invoice.
".
            "3. Create loading plan, convert to loading, complete loading, verify vehicle stock.
".
            "4. Complete delivery and unloading, verify warehouse/vehicle reconciliation.
".
            "5. Create collection and settlement, verify status updates.
".
            "6. Create return and credit note, verify stock and customer balance impact.
".
            "7. Confirm SMS bridge logs are generated without duplicating SMS module code.
".
            "8. Run Server Testing Centre and resolve any failed checks.
";
    }
}

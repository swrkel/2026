<?php

namespace Modules\StockTransferNew\Services\Support;

use Illuminate\Support\Facades\Schema;

class DiagnosticsService
{
    public function summary(): array
    {
        return [
            'Module' => 'Stock Transfer-New',
            'Version' => '1.0.15',
            'Mode' => 'Production Support',
            'Product Source' => 'Existing standalone Products module bridge',
            'Tenant Safe' => 'Business, location and store scope checks required',
        ];
    }

    public function checks(): array
    {
        return [
            $this->checkTable('stn_transfers', 'Transfer header table'),
            $this->checkTable('stn_transfer_lines', 'Transfer line table'),
            $this->checkTable('stn_stock_movements', 'Stock movement ledger'),
            $this->checkTable('stn_transfer_approvals', 'Approval workflow table'),
            $this->checkTable('stn_transfer_audit_logs', 'Audit log table'),
            $this->checkTable('stn_transfer_settings', 'Module settings table'),
        ];
    }

    public function tenantScopeChecks(): array
    {
        return [
            ['check' => 'business_id exists on transfer header', 'status' => $this->columnStatus('stn_transfers', 'business_id')],
            ['check' => 'from_location_id exists', 'status' => $this->columnStatus('stn_transfers', 'from_location_id')],
            ['check' => 'to_location_id exists', 'status' => $this->columnStatus('stn_transfers', 'to_location_id')],
            ['check' => 'from_store_id exists', 'status' => $this->columnStatus('stn_transfers', 'from_store_id')],
            ['check' => 'to_store_id exists', 'status' => $this->columnStatus('stn_transfers', 'to_store_id')],
            ['check' => 'product_id exists on lines', 'status' => $this->columnStatus('stn_transfer_lines', 'product_id')],
        ];
    }

    public function permissionChecks(): array
    {
        return [
            'stocktransfernew.view',
            'stocktransfernew.create',
            'stocktransfernew.approve',
            'stocktransfernew.dispatch',
            'stocktransfernew.receive',
            'stocktransfernew.reconcile',
            'stocktransfernew.reports',
            'stocktransfernew.audit',
            'stocktransfernew.performance',
            'stocktransfernew.support',
            'stocktransfernew.support.repair',
        ];
    }

    public function routesAndAssets(): array
    {
        return [
            ['type' => 'Route', 'name' => 'stock-transfer-new.support.diagnostics', 'status' => 'Expected'],
            ['type' => 'Route', 'name' => 'stock-transfer-new.support.tenant-scope', 'status' => 'Expected'],
            ['type' => 'Route', 'name' => 'stock-transfer-new.support.permissions', 'status' => 'Expected'],
            ['type' => 'Asset', 'name' => 'public/modules/stocktransfernew/css/stocktransfernew-support.css', 'status' => 'Included'],
            ['type' => 'Asset', 'name' => 'public/modules/stocktransfernew/js/stocktransfernew-support.js', 'status' => 'Included'],
        ];
    }

    protected function checkTable(string $table, string $label): array
    {
        return ['item' => $label, 'table' => $table, 'status' => Schema::hasTable($table) ? 'OK' : 'Missing'];
    }

    protected function columnStatus(string $table, string $column): string
    {
        return Schema::hasTable($table) && Schema::hasColumn($table, $column) ? 'OK' : 'Missing';
    }
}

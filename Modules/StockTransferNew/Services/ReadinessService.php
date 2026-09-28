<?php

namespace Modules\StockTransferNew\Services;

class ReadinessService
{
    public function summary(): array
    {
        return [
            'module' => 'Stock Transfer-New',
            'version' => '1.0.14',
            'status' => 'Production readiness parcel prepared',
            'standalone' => 'Yes',
            'product_master' => 'Uses existing standalone Products module bridge; no duplicate product master.',
        ];
    }

    public function checks(): array
    {
        return [
            ['area' => 'Tenant Scope', 'status' => 'Required', 'note' => 'Every query must be filtered by business, location/store where applicable.'],
            ['area' => 'Products', 'status' => 'Bridge Only', 'note' => 'Do not create duplicate product master inside StockTransferNew.'],
            ['area' => 'Approval', 'status' => 'Enabled', 'note' => 'Approval matrix and timeline are separated from transfer records.'],
            ['area' => 'Dispatch/Receive', 'status' => 'Enabled', 'note' => 'Stock movement ledger records dispatch and receive.'],
            ['area' => 'Audit', 'status' => 'Enabled', 'note' => 'Critical status changes are logged.'],
            ['area' => 'Performance', 'status' => 'Enabled', 'note' => 'Indexes and large export queue prepared.'],
        ];
    }

    public function sqlFiles(): array
    {
        return [
            '00_MASTER_STOCK_TRANSFER_NEW.sql',
            '01_MASTER_CREATE_TABLES_STOCK_TRANSFER_NEW.sql',
            '02_MASTER_ALTER_TABLES_STOCK_TRANSFER_NEW.sql',
            '03_MASTER_INSERT_PERMISSIONS_STOCK_TRANSFER_NEW.sql',
            '04_MASTER_INDEXES_STOCK_TRANSFER_NEW.sql',
        ];
    }

    public function exportChecklist(): array
    {
        return [
            'Transfer List export',
            'Movement Ledger export',
            'Variance Report export',
            'Aging Report export',
            'Advanced Analysis export',
            'Audit Log export',
            'Large Export Queue verification',
        ];
    }
}

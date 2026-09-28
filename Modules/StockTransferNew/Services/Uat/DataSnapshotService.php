<?php

namespace Modules\StockTransferNew\Services\Uat;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataSnapshotService
{
    public function snapshot(): array
    {
        $tables = [
            'stn_transfers', 'stn_transfer_lines', 'stn_stock_movements',
            'stn_approval_steps', 'stn_activity_logs', 'stn_transfer_locks',
            'stn_scan_sessions', 'stn_transfer_returns', 'stn_variance_reconciliations',
        ];

        $snapshot = [];
        foreach ($tables as $table) {
            $snapshot[$table] = Schema::hasTable($table) ? DB::table($table)->count() : 'MISSING';
        }

        return $snapshot;
    }
}

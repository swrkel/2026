<?php
namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\DB;

class QueryHealthService
{
    public function tableStats(): array
    {
        $tables = [
            'stn_transfers','stn_transfer_lines','stn_transfer_movements','stn_approval_steps',
            'stn_scan_sessions','stn_scan_lines','stn_audit_logs','stn_export_queue'
        ];
        $rows = [];
        foreach ($tables as $table) {
            try {
                $rows[] = [
                    'table' => $table,
                    'rows' => DB::table($table)->count(),
                    'status' => 'ok',
                ];
            } catch (\Throwable $e) {
                $rows[] = ['table' => $table, 'rows' => 0, 'status' => 'missing / not migrated'];
            }
        }
        return $rows;
    }

    public function indexRecommendations(): array
    {
        return [
            'stn_transfers' => 'business_id, status, from_location_id, to_location_id, transfer_date',
            'stn_transfer_lines' => 'transfer_id, product_id, from_store_id, to_store_id',
            'stn_transfer_movements' => 'business_id, transfer_id, product_id, movement_type, movement_date',
            'stn_approval_steps' => 'transfer_id, status, approval_level, approver_id',
            'stn_audit_logs' => 'business_id, transfer_id, event, created_at',
            'stn_scan_sessions' => 'business_id, transfer_id, scan_type, status',
        ];
    }
}

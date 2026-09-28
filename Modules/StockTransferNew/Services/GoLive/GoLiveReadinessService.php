<?php

namespace Modules\StockTransferNew\Services\GoLive;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GoLiveReadinessService
{
    public function summary(): array
    {
        return [
            'database' => $this->databaseChecks(),
            'permissions' => $this->permissionChecks(),
            'workflow' => $this->workflowChecks(),
            'assets' => $this->assetChecks(),
            'recommendation' => 'Run this page after uploading all STN packages and SQL to every tenant database.',
        ];
    }

    protected function databaseChecks(): array
    {
        $tables = [
            'stn_transfers', 'stn_transfer_lines', 'stn_stock_movements',
            'stn_approval_matrices', 'stn_approval_steps', 'stn_transfer_locks',
        ];
        $result = [];
        foreach ($tables as $table) {
            $result[$table] = Schema::hasTable($table) ? 'OK' : 'MISSING';
        }
        return $result;
    }

    protected function permissionChecks(): array
    {
        if (!Schema::hasTable('permissions')) {
            return ['permissions_table' => 'MISSING'];
        }
        $needed = [
            'stock_transfer_new.view', 'stock_transfer_new.create', 'stock_transfer_new.approve',
            'stock_transfer_new.dispatch', 'stock_transfer_new.receive', 'stock_transfer_new.reports',
        ];
        $found = DB::table('permissions')->whereIn('name', $needed)->pluck('name')->toArray();
        $out = [];
        foreach ($needed as $permission) {
            $out[$permission] = in_array($permission, $found, true) ? 'OK' : 'MISSING';
        }
        return $out;
    }

    protected function workflowChecks(): array
    {
        return [
            'draft_create' => 'Manual test required',
            'approval_flow' => 'Manual test required',
            'dispatch_receive' => 'Manual test required',
            'variance_close' => 'Manual test required',
            'returns' => 'Manual test required',
        ];
    }

    protected function assetChecks(): array
    {
        return [
            'css_path' => public_path('modules/stocktransfernew/css/stocktransfernew.css'),
            'js_path' => public_path('modules/stocktransfernew/js/stocktransfernew.js'),
        ];
    }
}

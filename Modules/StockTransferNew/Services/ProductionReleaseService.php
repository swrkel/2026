<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\ProductionReleaseCheck;
use Modules\StockTransferNew\Entities\ReleaseSignOff;

class ProductionReleaseService
{
    public function runChecks(int $businessId, ?int $locationId = null, ?int $storeId = null): array
    {
        $checks = [
            $this->checkTableExists($businessId, $locationId, $storeId, 'stn_transfers', 'Core transfer table'),
            $this->checkTableExists($businessId, $locationId, $storeId, 'stn_transfer_lines', 'Transfer line table'),
            $this->checkTableExists($businessId, $locationId, $storeId, 'stn_stock_movements', 'Stock movement ledger'),
            $this->checkTableExists($businessId, $locationId, $storeId, 'stn_transfer_approvals', 'Approval workflow table'),
            $this->checkPendingTransfers($businessId, $locationId, $storeId),
            $this->checkUnresolvedVariances($businessId, $locationId, $storeId),
            $this->checkOpenLocks($businessId, $locationId, $storeId),
            $this->checkDuplicateReferences($businessId, $locationId, $storeId),
        ];

        foreach ($checks as $check) {
            ProductionReleaseCheck::create($check);
        }

        return $checks;
    }

    public function signOff(int $businessId, string $stage, string $status, ?string $remarks = null): ReleaseSignOff
    {
        return ReleaseSignOff::create([
            'business_id' => $businessId,
            'release_code' => 'STN-040',
            'release_stage' => $stage,
            'signed_by' => Auth::id(),
            'signed_at' => now(),
            'status' => $status,
            'remarks' => $remarks,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
        ]);
    }

    private function checkTableExists(int $businessId, ?int $locationId, ?int $storeId, string $table, string $name): array
    {
        $exists = DB::getSchemaBuilder()->hasTable($table);

        return $this->row($businessId, $locationId, $storeId, 'table_' . $table, $name, 'schema', $exists ? 'passed' : 'failed', $exists ? 'info' : 'critical', $exists ? 'Ready' : 'Missing table: ' . $table);
    }

    private function checkPendingTransfers(int $businessId, ?int $locationId, ?int $storeId): array
    {
        $count = $this->safeCount('stn_transfers', $businessId, $locationId, $storeId, ['draft', 'pending_approval', 'approved', 'dispatched', 'in_transit']);
        return $this->row($businessId, $locationId, $storeId, 'open_transfers', 'Open transfer review', 'workflow', $count > 0 ? 'warning' : 'passed', $count > 0 ? 'medium' : 'info', $count . ' open transfer(s) found before sign-off.');
    }

    private function checkUnresolvedVariances(int $businessId, ?int $locationId, ?int $storeId): array
    {
        $count = $this->safeCount('stn_transfer_lines', $businessId, $locationId, $storeId, null, 'variance_qty');
        return $this->row($businessId, $locationId, $storeId, 'unresolved_variances', 'Variance review', 'stock', $count > 0 ? 'warning' : 'passed', $count > 0 ? 'high' : 'info', $count . ' variance line(s) require review.');
    }

    private function checkOpenLocks(int $businessId, ?int $locationId, ?int $storeId): array
    {
        $count = $this->safeCount('stn_transfer_locks', $businessId, $locationId, $storeId, ['locked']);
        return $this->row($businessId, $locationId, $storeId, 'open_locks', 'Lock review', 'security', $count > 0 ? 'warning' : 'passed', $count > 0 ? 'medium' : 'info', $count . ' active lock(s) found.');
    }

    private function checkDuplicateReferences(int $businessId, ?int $locationId, ?int $storeId): array
    {
        if (!DB::getSchemaBuilder()->hasTable('stn_transfers')) {
            return $this->row($businessId, $locationId, $storeId, 'duplicate_refs', 'Duplicate reference review', 'data', 'skipped', 'low', 'Core table missing.');
        }

        $duplicates = DB::table('stn_transfers')
            ->select('reference_no')
            ->where('business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where('from_location_id', $locationId))
            ->whereNotNull('reference_no')
            ->groupBy('reference_no')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        return $this->row($businessId, $locationId, $storeId, 'duplicate_refs', 'Duplicate reference review', 'data', $duplicates > 0 ? 'failed' : 'passed', $duplicates > 0 ? 'critical' : 'info', $duplicates . ' duplicate reference group(s) found.');
    }

    private function safeCount(string $table, int $businessId, ?int $locationId, ?int $storeId, ?array $statuses = null, ?string $positiveColumn = null): int
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)
            ->where('business_id', $businessId)
            ->when($locationId && DB::getSchemaBuilder()->hasColumn($table, 'location_id'), fn ($q) => $q->where('location_id', $locationId))
            ->when($storeId && DB::getSchemaBuilder()->hasColumn($table, 'store_id'), fn ($q) => $q->where('store_id', $storeId))
            ->when($statuses && DB::getSchemaBuilder()->hasColumn($table, 'status'), fn ($q) => $q->whereIn('status', $statuses))
            ->when($positiveColumn && DB::getSchemaBuilder()->hasColumn($table, $positiveColumn), fn ($q) => $q->where($positiveColumn, '!=', 0))
            ->count();
    }

    private function row(int $businessId, ?int $locationId, ?int $storeId, string $code, string $name, string $group, string $status, string $severity, string $message): array
    {
        return [
            'business_id' => $businessId,
            'location_id' => $locationId,
            'store_id' => $storeId,
            'check_code' => $code,
            'check_name' => $name,
            'check_group' => $group,
            'status' => $status,
            'severity' => $severity,
            'message' => $message,
            'checked_by' => Auth::id(),
            'checked_at' => now(),
        ];
    }
}

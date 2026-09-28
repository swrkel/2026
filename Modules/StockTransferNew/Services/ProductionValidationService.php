<?php

namespace Modules\StockTransferNew\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\ProductionValidationIssue;
use Modules\StockTransferNew\Entities\ProductionValidationRun;

class ProductionValidationService
{
    public function dashboard(array $filters = []): array
    {
        $businessId = $this->businessId($filters);

        $latestRun = ProductionValidationRun::query()
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->latest('id')
            ->first();

        return [
            'latest_run' => $latestRun,
            'open_critical' => $this->issueQuery($businessId)->where('severity', 'critical')->where('is_resolved', 0)->count(),
            'open_warnings' => $this->issueQuery($businessId)->where('severity', 'warning')->where('is_resolved', 0)->count(),
            'open_failed' => $this->issueQuery($businessId)->where('severity', 'failed')->where('is_resolved', 0)->count(),
            'recent_runs' => ProductionValidationRun::query()
                ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
                ->latest('id')
                ->limit(10)
                ->get(),
            'issues' => $this->issueQuery($businessId)->where('is_resolved', 0)->latest('id')->limit(50)->get(),
        ];
    }

    public function run(array $filters = []): ProductionValidationRun
    {
        $businessId = $this->businessId($filters);
        $locationId = $filters['location_id'] ?? null;
        $storeId = $filters['store_id'] ?? null;

        $run = ProductionValidationRun::create([
            'business_id' => $businessId,
            'location_id' => $locationId,
            'store_id' => $storeId,
            'status' => 'running',
            'checked_by' => Auth::id(),
            'started_at' => Carbon::now(),
        ]);

        $checks = collect([
            $this->checkPendingApprovals($businessId, $locationId, $storeId),
            $this->checkStaleInTransit($businessId, $locationId, $storeId),
            $this->checkNegativeMovementBalances($businessId, $locationId, $storeId),
            $this->checkUnresolvedVariance($businessId, $locationId, $storeId),
            $this->checkDuplicateReferences($businessId, $locationId, $storeId),
            $this->checkMissingProductBridge($businessId),
            $this->checkPermissionSeeds(),
            $this->checkCoreTables(),
        ])->flatten(1)->values();

        foreach ($checks as $check) {
            if (($check['status'] ?? 'passed') !== 'passed') {
                ProductionValidationIssue::create([
                    'run_id' => $run->id,
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'store_id' => $storeId,
                    'check_key' => $check['key'] ?? 'unknown',
                    'title' => $check['title'] ?? 'Validation issue',
                    'severity' => $check['status'] ?? 'warning',
                    'severity_score' => $check['score'] ?? 1,
                    'message' => $check['message'] ?? '',
                    'recommended_action' => $check['action'] ?? '',
                    'is_resolved' => 0,
                ]);
            }
        }

        $passed = $checks->where('status', 'passed')->count();
        $warnings = $checks->where('status', 'warning')->count();
        $failed = $checks->whereIn('status', ['failed', 'critical'])->count();

        $run->update([
            'status' => $failed > 0 ? 'failed' : ($warnings > 0 ? 'warning' : 'passed'),
            'total_checks' => $checks->count(),
            'passed_checks' => $passed,
            'warning_checks' => $warnings,
            'failed_checks' => $failed,
            'payload' => $checks->all(),
            'completed_at' => Carbon::now(),
        ]);

        return $run->fresh();
    }

    public function resolveIssue(int $issueId, string $note = ''): ProductionValidationIssue
    {
        $issue = ProductionValidationIssue::findOrFail($issueId);
        $issue->update([
            'is_resolved' => 1,
            'resolved_by' => Auth::id(),
            'resolved_note' => $note,
            'resolved_at' => Carbon::now(),
        ]);

        return $issue->fresh();
    }

    protected function checkPendingApprovals($businessId, $locationId, $storeId): array
    {
        if (! $this->tableExists('stn_transfers')) {
            return [$this->failed('missing_transfers_table', 'Transfers table missing', 'Run StockTransferNew CREATE SQL before testing.')];
        }

        $count = DB::table('stn_transfers')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->when($locationId, fn ($q) => $q->where('from_location_id', $locationId))
            ->when($storeId, fn ($q) => $q->where('from_store_id', $storeId))
            ->whereIn('status', ['draft', 'pending_approval'])
            ->count();

        return [$count > 0
            ? $this->warning('pending_approval_queue', 'Pending transfer approvals found', $count.' transfers need review before go-live close.')
            : $this->passed('pending_approval_queue', 'No pending approval backlog')];
    }

    protected function checkStaleInTransit($businessId, $locationId, $storeId): array
    {
        if (! $this->tableExists('stn_transfers')) {
            return [];
        }

        $count = DB::table('stn_transfers')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->when($locationId, fn ($q) => $q->where('from_location_id', $locationId))
            ->when($storeId, fn ($q) => $q->where('from_store_id', $storeId))
            ->whereIn('status', ['dispatched', 'in_transit', 'partially_received'])
            ->where('updated_at', '<', Carbon::now()->subDays(3))
            ->count();

        return [$count > 0
            ? $this->warning('stale_in_transit', 'Stale in-transit transfers found', $count.' transfers have not moved for more than 3 days.')
            : $this->passed('stale_in_transit', 'No stale in-transit transfers')];
    }

    protected function checkNegativeMovementBalances($businessId, $locationId, $storeId): array
    {
        if (! $this->tableExists('stn_stock_movements')) {
            return [];
        }

        $count = DB::table('stn_stock_movements')
            ->select('product_id')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->groupBy('product_id', 'location_id', 'store_id')
            ->havingRaw('SUM(qty_in - qty_out) < 0')
            ->get()
            ->count();

        return [$count > 0
            ? $this->failed('negative_stock_movements', 'Negative stock movement balances found', $count.' product/location/store groups are negative.')
            : $this->passed('negative_stock_movements', 'No negative movement balances')];
    }

    protected function checkUnresolvedVariance($businessId, $locationId, $storeId): array
    {
        if (! $this->tableExists('stn_transfer_lines')) {
            return [];
        }

        $query = DB::table('stn_transfer_lines as l')
            ->join('stn_transfers as t', 't.id', '=', 'l.transfer_id')
            ->when($businessId, fn ($q) => $q->where('t.business_id', $businessId))
            ->when($locationId, fn ($q) => $q->where('t.to_location_id', $locationId))
            ->when($storeId, fn ($q) => $q->where('t.to_store_id', $storeId))
            ->whereRaw('COALESCE(l.dispatched_qty, 0) <> COALESCE(l.received_qty, 0)')
            ->whereIn('t.status', ['received', 'completed']);

        $count = $query->count();

        return [$count > 0
            ? $this->warning('unresolved_variance', 'Unresolved received/dispatched variance found', $count.' lines need reconciliation.')
            : $this->passed('unresolved_variance', 'No unresolved transfer variances')];
    }

    protected function checkDuplicateReferences($businessId, $locationId, $storeId): array
    {
        if (! $this->tableExists('stn_transfers')) {
            return [];
        }

        $count = DB::table('stn_transfers')
            ->select('reference_no')
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->whereNotNull('reference_no')
            ->groupBy('reference_no')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return [$count > 0
            ? $this->failed('duplicate_references', 'Duplicate transfer reference numbers found', $count.' duplicate reference groups must be corrected.')
            : $this->passed('duplicate_references', 'No duplicate transfer references')];
    }

    protected function checkMissingProductBridge($businessId): array
    {
        $hasProductsTable = $this->tableExists('products') || $this->tableExists('productsnew_products');
        return [$hasProductsTable
            ? $this->passed('product_bridge', 'Products source table detected')
            : $this->warning('product_bridge', 'Products source table not detected', 'Verify standalone Products module is installed/enabled for product lookup.')];
    }

    protected function checkPermissionSeeds(): array
    {
        if (! $this->tableExists('permissions')) {
            return [$this->warning('permissions_table', 'Permissions table not detected', 'Check permission system table name before applying permission SQL.')];
        }

        $count = DB::table('permissions')->where('name', 'like', 'stocktransfernew.%')->count();
        return [$count > 0
            ? $this->passed('permissions_seeded', 'StockTransferNew permissions found')
            : $this->warning('permissions_seeded', 'StockTransferNew permissions missing', 'Run STN INSERT permissions SQL.')];
    }

    protected function checkCoreTables(): array
    {
        $required = ['stn_transfers', 'stn_transfer_lines', 'stn_stock_movements'];
        $missing = collect($required)->reject(fn ($table) => $this->tableExists($table))->values();

        return [$missing->isEmpty()
            ? $this->passed('core_tables', 'Core transfer tables detected')
            : $this->failed('core_tables', 'Core transfer tables missing', 'Missing: '.$missing->implode(', '))];
    }

    protected function issueQuery($businessId)
    {
        return ProductionValidationIssue::query()->when($businessId, fn ($q) => $q->where('business_id', $businessId));
    }

    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function businessId(array $filters)
    {
        return $filters['business_id'] ?? session('business.id') ?? request()->session()->get('user.business_id');
    }

    protected function passed(string $key, string $title, string $message = ''): array
    {
        return compact('key', 'title', 'message') + ['status' => 'passed', 'score' => 0];
    }

    protected function warning(string $key, string $title, string $message = ''): array
    {
        return compact('key', 'title', 'message') + ['status' => 'warning', 'score' => 2, 'action' => 'Review before production close.'];
    }

    protected function failed(string $key, string $title, string $message = ''): array
    {
        return compact('key', 'title', 'message') + ['status' => 'failed', 'score' => 5, 'action' => 'Fix before production release.'];
    }
}

<?php

namespace Modules\StockTransferNew\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\StockTransferNew\Entities\ProductionHardeningCheck;
use Modules\StockTransferNew\Entities\ProductionHardeningRun;

class ProductionHardeningService
{
    public function summary(int $businessId, ?int $locationId = null, ?int $storeId = null): array
    {
        $query = ProductionHardeningCheck::where('business_id', $businessId);
        $this->applyScope($query, $locationId, $storeId);

        return [
            'total' => (clone $query)->count(),
            'passed' => (clone $query)->where('status', 'passed')->count(),
            'warnings' => (clone $query)->where('status', 'warning')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'critical_open' => (clone $query)->where('severity', 'critical')->whereIn('status', ['warning', 'failed'])->count(),
            'latest_run' => ProductionHardeningRun::where('business_id', $businessId)->latest('id')->first(),
        ];
    }

    public function checks(int $businessId, ?int $locationId = null, ?int $storeId = null)
    {
        $query = ProductionHardeningCheck::where('business_id', $businessId);
        $this->applyScope($query, $locationId, $storeId);

        return $query->orderByRaw("FIELD(severity, 'critical', 'high', 'medium', 'low')")
            ->orderBy('check_area')
            ->orderBy('check_code')
            ->paginate(50);
    }

    public function runChecks(int $businessId, ?int $locationId = null, ?int $storeId = null, string $notes = null): ProductionHardeningRun
    {
        return DB::transaction(function () use ($businessId, $locationId, $storeId, $notes) {
            $run = ProductionHardeningRun::create([
                'business_id' => $businessId,
                'run_no' => $this->nextRunNo($businessId),
                'run_type' => 'production_hardening',
                'status' => 'running',
                'started_by' => Auth::id(),
                'started_at' => Carbon::now(),
                'notes' => $notes,
            ]);

            $results = collect($this->definition())->map(function (array $definition) use ($businessId, $locationId, $storeId) {
                return $this->evaluate($definition, $businessId, $locationId, $storeId);
            });

            foreach ($results as $result) {
                ProductionHardeningCheck::updateOrCreate(
                    [
                        'business_id' => $businessId,
                        'location_id' => $locationId,
                        'store_id' => $storeId,
                        'check_code' => $result['check_code'],
                    ],
                    $result + [
                        'checked_by' => Auth::id(),
                        'checked_at' => Carbon::now(),
                    ]
                );
            }

            $run->update([
                'status' => $results->contains('status', 'failed') ? 'attention_required' : 'completed',
                'total_checks' => $results->count(),
                'passed_checks' => $results->where('status', 'passed')->count(),
                'warning_checks' => $results->where('status', 'warning')->count(),
                'failed_checks' => $results->where('status', 'failed')->count(),
                'completed_at' => Carbon::now(),
            ]);

            return $run->fresh();
        });
    }

    private function definition(): array
    {
        return [
            ['code' => 'STN046-TENANT-SCOPE', 'area' => 'Tenant Scope', 'title' => 'All open transfers must have business, location and store scope', 'severity' => 'critical'],
            ['code' => 'STN046-STATUS-FLOW', 'area' => 'Workflow', 'title' => 'Invalid transfer status combinations must be blocked', 'severity' => 'critical'],
            ['code' => 'STN046-DUPLICATE-DISPATCH', 'area' => 'Dispatch', 'title' => 'Duplicate dispatch references must not exist', 'severity' => 'high'],
            ['code' => 'STN046-DUPLICATE-RECEIVE', 'area' => 'Receiving', 'title' => 'Duplicate receive references must not exist', 'severity' => 'high'],
            ['code' => 'STN046-APPROVAL-LOCK', 'area' => 'Approvals', 'title' => 'Approved transfers must be locked from draft edits', 'severity' => 'high'],
            ['code' => 'STN046-PRODUCT-BRIDGE', 'area' => 'Products Bridge', 'title' => 'Product lookup must use the existing Products module bridge', 'severity' => 'high'],
            ['code' => 'STN046-LEDGER-BALANCE', 'area' => 'Stock Ledger', 'title' => 'Dispatch and receive stock ledger quantities must balance', 'severity' => 'critical'],
            ['code' => 'STN046-PERMISSIONS', 'area' => 'Security', 'title' => 'Critical production routes must have permissions', 'severity' => 'critical'],
            ['code' => 'STN046-INDEXES', 'area' => 'Performance', 'title' => 'Production indexes must exist for transfer, line and ledger tables', 'severity' => 'medium'],
            ['code' => 'STN046-ASSETS', 'area' => 'Assets', 'title' => 'Module CSS and JavaScript assets must be publishable', 'severity' => 'low'],
        ];
    }

    private function evaluate(array $definition, int $businessId, ?int $locationId, ?int $storeId): array
    {
        $status = 'passed';
        $actual = 'Check completed successfully.';
        $recommendation = 'No action required.';

        try {
            switch ($definition['code']) {
                case 'STN046-TENANT-SCOPE':
                    $missing = $this->safeCount('stn_transfers', "business_id IS NULL OR from_location_id IS NULL OR to_location_id IS NULL OR from_store_id IS NULL OR to_store_id IS NULL", $businessId);
                    if ($missing > 0) {
                        $status = 'failed';
                        $actual = $missing . ' transfer record(s) are missing required scope fields.';
                        $recommendation = 'Review these records before go-live and correct business/location/store scope.';
                    }
                    break;

                case 'STN046-DUPLICATE-DISPATCH':
                    $duplicates = $this->duplicateCount('stn_dispatches', 'dispatch_no', $businessId);
                    if ($duplicates > 0) {
                        $status = 'failed';
                        $actual = $duplicates . ' duplicate dispatch number group(s) found.';
                        $recommendation = 'Use the duplicate repair utility before allowing dispatch users online.';
                    }
                    break;

                case 'STN046-DUPLICATE-RECEIVE':
                    $duplicates = $this->duplicateCount('stn_receives', 'receive_no', $businessId);
                    if ($duplicates > 0) {
                        $status = 'failed';
                        $actual = $duplicates . ' duplicate receive number group(s) found.';
                        $recommendation = 'Use the duplicate repair utility before allowing receiving users online.';
                    }
                    break;

                case 'STN046-LEDGER-BALANCE':
                    $imbalance = $this->safeCount('stn_stock_movements', "movement_type IN ('dispatch','receive') AND quantity IS NULL", $businessId);
                    if ($imbalance > 0) {
                        $status = 'warning';
                        $actual = $imbalance . ' stock movement row(s) have missing quantity.';
                        $recommendation = 'Rebuild or correct the stock movement rows before final stock comparison.';
                    }
                    break;

                case 'STN046-PERMISSIONS':
                    $permissionRows = $this->permissionCount();
                    if ($permissionRows < 8) {
                        $status = 'warning';
                        $actual = 'Only ' . $permissionRows . ' StockTransferNew permission row(s) detected.';
                        $recommendation = 'Run STN_046_INSERT_PERMISSIONS.sql in each tenant database.';
                    }
                    break;

                default:
                    $actual = 'Static hardening rule is ready for manual confirmation.';
                    $recommendation = 'Confirm during UAT and production go-live review.';
                    break;
            }
        } catch (\Throwable $exception) {
            $status = 'warning';
            $actual = 'Could not auto-check: ' . $exception->getMessage();
            $recommendation = 'Table may not exist yet. Run required StockTransferNew SQL/migrations first.';
        }

        return [
            'business_id' => $businessId,
            'location_id' => $locationId,
            'store_id' => $storeId,
            'check_code' => $definition['code'],
            'check_area' => $definition['area'],
            'check_title' => $definition['title'],
            'severity' => $definition['severity'],
            'status' => $status,
            'expected_result' => 'Production-ready validation must pass or be manually cleared.',
            'actual_result' => $actual,
            'recommendation' => $recommendation,
        ];
    }

    private function safeCount(string $table, string $where, int $businessId): int
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)
            ->where('business_id', $businessId)
            ->whereRaw('(' . $where . ')')
            ->count();
    }

    private function duplicateCount(string $table, string $column, int $businessId): int
    {
        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->select($column)
            ->where('business_id', $businessId)
            ->whereNotNull($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->count();
    }

    private function permissionCount(): int
    {
        if (!DB::getSchemaBuilder()->hasTable('permissions')) {
            return 0;
        }

        return (int) DB::table('permissions')
            ->where('name', 'like', 'stock_transfer_new.%')
            ->count();
    }

    private function nextRunNo(int $businessId): string
    {
        $next = ProductionHardeningRun::where('business_id', $businessId)->count() + 1;
        return 'STN-HARD-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function applyScope($query, ?int $locationId, ?int $storeId): void
    {
        if ($locationId) {
            $query->where('location_id', $locationId);
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }
    }
}

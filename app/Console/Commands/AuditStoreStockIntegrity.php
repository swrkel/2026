<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditStoreStockIntegrity extends Command
{
    protected $signature = 'stock:audit-store-integrity
        {--business= : Restrict to one business ID}
        {--repair-single-store : Repair Store balances only where a Location has exactly one active Store}
        {--repair-duplicates : Consolidate duplicate Store summary rows without changing their combined quantity}
        {--repair-missing-transaction-store : Fill transaction.store_id only when the transaction Location has exactly one active Store}';

    protected $description = 'Audit Location vs Store stock and safely repair unambiguous Store stock inconsistencies.';

    public function handle(): int
    {
        foreach (['stores', 'variation_location_details', 'variation_store_details'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->error("Required table {$table} is not available on the current database connection. Run this command in the tenant database context.");
                return self::FAILURE;
            }
        }

        $businessId = (int) ($this->option('business') ?: 0);
        $storesQuery = DB::table('stores')->select(['id', 'location_id']);
        if ($businessId > 0 && Schema::hasColumn('stores', 'business_id')) {
            $storesQuery->where('business_id', $businessId);
        }
        if (Schema::hasColumn('stores', 'status')) {
            $storesQuery->where('status', 1);
        }
        if (Schema::hasColumn('stores', 'deleted_at')) {
            $storesQuery->whereNull('deleted_at');
        }

        $storesByLocation = $storesQuery->orderBy('id')->get()->groupBy('location_id');
        $storeIds = $storesByLocation->flatten()->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($storeIds === []) {
            $this->warn('No active Stores found in scope.');
            return self::SUCCESS;
        }

        $this->auditDuplicates($storeIds, (bool) $this->option('repair-duplicates'));

        $issues = 0;
        $repairs = 0;
        foreach ($storesByLocation as $locationId => $stores) {
            $locationId = (int) $locationId;
            $locationStoreIds = $stores->pluck('id')->map(fn ($id) => (int) $id)->all();

            $locationRows = DB::table('variation_location_details')
                ->where('location_id', $locationId)
                ->select('product_id', 'product_variation_id', 'variation_id', DB::raw('SUM(COALESCE(qty_available,0)) as qty'))
                ->groupBy('product_id', 'product_variation_id', 'variation_id')
                ->get();

            $storeRows = DB::table('variation_store_details')
                ->whereIn('store_id', $locationStoreIds)
                ->select('product_id', 'product_variation_id', 'variation_id', DB::raw('SUM(COALESCE(qty_available,0)) as qty'))
                ->groupBy('product_id', 'product_variation_id', 'variation_id')
                ->get();

            $keys = [];
            foreach ($locationRows as $row) {
                $keys[$this->key($row)] = ['location' => (float) $row->qty, 'row' => $row];
            }
            foreach ($storeRows as $row) {
                $key = $this->key($row);
                $keys[$key] = $keys[$key] ?? ['location' => 0.0, 'row' => $row];
                $keys[$key]['stores'] = (float) $row->qty;
            }

            foreach ($keys as $key => $data) {
                $locationQty = (float) ($data['location'] ?? 0);
                $storeQty = (float) ($data['stores'] ?? 0);
                if (abs($locationQty - $storeQty) < 0.00005) {
                    continue;
                }

                $issues++;
                $row = $data['row'];
                $this->line(sprintf(
                    'Mismatch Location %d | Product %d | Variation %d | Location %.4f | Stores %.4f | Difference %.4f',
                    $locationId,
                    (int) $row->product_id,
                    (int) $row->variation_id,
                    $locationQty,
                    $storeQty,
                    $storeQty - $locationQty
                ));

                if ($this->option('repair-single-store') && count($locationStoreIds) === 1) {
                    $this->setSingleStoreQty(
                        $locationStoreIds[0],
                        (int) $row->product_id,
                        (int) ($row->product_variation_id ?? 0),
                        (int) $row->variation_id,
                        $locationQty
                    );
                    $repairs++;
                }
            }
        }

        if ($this->option('repair-missing-transaction-store')) {
            $repairs += $this->repairMissingTransactionStores($storesByLocation, $businessId);
        }

        $this->newLine();
        $this->info("Audit complete. Mismatches: {$issues}. Safe repairs applied: {$repairs}.");
        if (! $this->option('repair-single-store')) {
            $this->comment('No balance repair was performed. Re-run with --repair-single-store only after reviewing the audit output.');
        }

        return self::SUCCESS;
    }

    private function auditDuplicates(array $storeIds, bool $repair): void
    {
        $duplicates = DB::table('variation_store_details')
            ->whereIn('store_id', $storeIds)
            ->select('store_id', 'product_id', 'variation_id', DB::raw('COUNT(*) as rows_count'), DB::raw('SUM(COALESCE(qty_available,0)) as qty'))
            ->groupBy('store_id', 'product_id', 'variation_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $this->warn(sprintf(
                'Duplicate Store stock rows: Store %d Product %d Variation %d Rows %d Combined Qty %.4f',
                $duplicate->store_id,
                $duplicate->product_id,
                $duplicate->variation_id,
                $duplicate->rows_count,
                $duplicate->qty
            ));

            if (! $repair) {
                continue;
            }

            $rows = DB::table('variation_store_details')
                ->where('store_id', $duplicate->store_id)
                ->where('product_id', $duplicate->product_id)
                ->where('variation_id', $duplicate->variation_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($rows->count() <= 1) {
                continue;
            }

            DB::table('variation_store_details')->where('id', $rows->first()->id)->update([
                'qty_available' => (float) $rows->sum(fn ($r) => (float) ($r->qty_available ?? 0)),
                'updated_at' => now(),
            ]);
            DB::table('variation_store_details')->whereIn('id', $rows->slice(1)->pluck('id')->all())->update([
                'qty_available' => 0,
                'updated_at' => now(),
            ]);
        }
    }

    private function setSingleStoreQty(int $storeId, int $productId, int $productVariationId, int $variationId, float $qty): void
    {
        $rows = DB::table('variation_store_details')
            ->where('store_id', $storeId)
            ->where('product_id', $productId)
            ->where('variation_id', $variationId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($rows->isEmpty()) {
            DB::table('variation_store_details')->insert([
                'product_id' => $productId,
                'product_variation_id' => $productVariationId,
                'variation_id' => $variationId,
                'store_id' => $storeId,
                'qty_available' => $qty,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return;
        }

        DB::table('variation_store_details')->where('id', $rows->first()->id)->update([
            'qty_available' => $qty,
            'updated_at' => now(),
        ]);
        if ($rows->count() > 1) {
            DB::table('variation_store_details')->whereIn('id', $rows->slice(1)->pluck('id')->all())->update([
                'qty_available' => 0,
                'updated_at' => now(),
            ]);
        }
    }

    private function repairMissingTransactionStores($storesByLocation, int $businessId): int
    {
        if (! Schema::hasTable('transactions') || ! Schema::hasColumn('transactions', 'store_id')) {
            return 0;
        }

        $stockTypes = ['purchase', 'purchase_return', 'sell', 'sell_return', 'opening_stock', 'stock_adjustment', 'stock_transfer'];
        $count = 0;
        foreach ($storesByLocation as $locationId => $stores) {
            if ($stores->count() !== 1) {
                continue;
            }
            $storeId = (int) $stores->first()->id;
            $query = DB::table('transactions')
                ->where('location_id', (int) $locationId)
                ->whereNull('store_id');
            if (Schema::hasColumn('transactions', 'type')) {
                $query->whereIn('type', $stockTypes);
            }
            if ($businessId > 0 && Schema::hasColumn('transactions', 'business_id')) {
                $query->where('business_id', $businessId);
            }
            $count += $query->update(['store_id' => $storeId, 'updated_at' => now()]);
        }
        return $count;
    }

    private function key(object $row): string
    {
        return (int) $row->product_id . ':' . (int) $row->variation_id;
    }
}

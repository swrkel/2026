<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseEntryTankService
{
    /** @var array<string, bool> */
    protected array $fuelProductCache = [];

    public function __construct(
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseSchemaUtil $schema
    ) {
    }

    public function isFuelProduct(int $productId, ?int $locationId = null): bool
    {
        if ($productId <= 0 || ! Schema::hasTable('products')) {
            return false;
        }

        $cacheKey = $productId . ':' . ($locationId ?: 0);
        if (array_key_exists($cacheKey, $this->fuelProductCache)) {
            return $this->fuelProductCache[$cacheKey];
        }

        $businessId = $this->numbers->businessId();
        $query = DB::table('products as p')
            ->where('p.id', $productId)
            ->where('p.business_id', $businessId);

        $hasCategory = Schema::hasTable('categories') && Schema::hasColumn('products', 'category_id');
        $hasSubCategory = Schema::hasTable('categories') && Schema::hasColumn('products', 'sub_category_id');

        if ($hasCategory) {
            $query->leftJoin('categories as pc', 'pc.id', '=', 'p.category_id');
        }
        if ($hasSubCategory) {
            $query->leftJoin('categories as psc', 'psc.id', '=', 'p.sub_category_id');
        }

        $columns = ['p.id', 'p.name as product_name'];
        $columns[] = $hasCategory ? 'pc.name as category_name' : DB::raw("'' as category_name");
        $columns[] = $hasSubCategory ? 'psc.name as sub_category_name' : DB::raw("'' as sub_category_name");
        $product = $query->first($columns);

        if (! $product) {
            return $this->fuelProductCache[$cacheKey] = false;
        }

        $categoryNames = [
            $this->normaliseCategoryName((string) ($product->category_name ?? '')),
            $this->normaliseCategoryName((string) ($product->sub_category_name ?? '')),
        ];

        /*
         * IS2310: Unload Tanks belongs only to real Fuel-category purchases.
         *
         * Lubricants and gas can legitimately sit below a Fuel parent category in
         * older business data, and some installations also retain historical tank
         * mappings for those products.  Treating either condition as authoritative
         * made Add Purchase wait for a tank allocation that must not apply, which in
         * turn kept Cancel / Save & Add Another / Save & View / Save Purchase Entry
         * hidden forever.
         *
         * An explicit Lubricant/Gas category therefore wins over the parent/fallback
         * rule. Petrol/Diesel/etc. under a Fuel parent still remain fuel products.
         */
        if ($this->isNonFuelPurchaseCategory($categoryNames, (string) ($product->product_name ?? ''))) {
            return $this->fuelProductCache[$cacheKey] = false;
        }

        $isFuel = collect($categoryNames)->contains(
            fn (string $name): bool => $this->isFuelCategoryName($name)
        );

        /*
         * Keep legacy compatibility only for products which genuinely have no
         * category information. If a named category says the product is something
         * other than Fuel, an old fuel_tanks row must not silently convert it into
         * a fuel purchase and block the action buttons.
         */
        $hasNamedCategory = collect($categoryNames)->contains(fn (string $name): bool => $name !== '');
        if (! $isFuel && ! $hasNamedCategory && $this->fuelTankLookupAvailable((bool) ($locationId && $locationId > 0))) {
            $tankQuery = DB::table('fuel_tanks')
                ->where('business_id', $businessId)
                ->where('product_id', $productId);
            if ($locationId && $locationId > 0) {
                $tankQuery->where('location_id', $locationId);
            }
            $isFuel = $tankQuery->exists();
        }

        return $this->fuelProductCache[$cacheKey] = $isFuel;
    }

    protected function normaliseCategoryName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9]+/', ' ', $name) ?? '';

        return trim(preg_replace('/\s+/', ' ', $name) ?? '');
    }

    /** @param array<int, string> $categoryNames */
    protected function isNonFuelPurchaseCategory(array $categoryNames, string $productName = ''): bool
    {
        $productName = $this->normaliseCategoryName($productName);
        $signals = array_values(array_filter(array_merge($categoryNames, [$productName])));

        foreach ($signals as $name) {
            if (preg_match('/\b(lubricants?|lubrication|lubes?|grease|lpg|lp gas|gas|coolants?|brake fluid|transmission fluid|power steering fluid|atf)\b/', $name) === 1) {
                return true;
            }

            if ($name === 'liquefied petroleum gas') {
                return true;
            }

            // Common lubricant product/category names used by older businesses.
            // Do not blanket-classify every "oil" as non-fuel because Furnace
            // Oil / Fuel Oil are genuine fuel products.
            if (preg_match('/\b(?:2\s*t|4\s*t|two stroke|four stroke|engine|motor|gear|hydraulic|compressor|transmission|lubricating)\b.*\boils?\b/', $name) === 1
                || preg_match('/\boils?\b.*\b(?:2\s*t|4\s*t|engine|motor|gear|hydraulic|compressor|transmission|lubricating)\b/', $name) === 1) {
                return true;
            }

            if (preg_match('/\bloose oils?\b/', $name) === 1
                && preg_match('/\b(fuel oil|furnace oil|heavy fuel|diesel|kerosene)\b/', $name) !== 1) {
                return true;
            }
        }

        // A generic Oil/Oils child category under Fuel is frequently used for
        // lubricants in legacy businesses. Use the product name as a safety
        // check so known fuel-oil products stay in the fuel workflow.
        if (collect($categoryNames)->contains(fn (string $name): bool => in_array($name, ['oil', 'oils'], true))
            && preg_match('/\b(fuel oil|furnace oil|heavy fuel|diesel|kerosene)\b/', $productName) !== 1) {
            return true;
        }

        return false;
    }

    protected function fuelTankLookupAvailable(bool $needsLocation = true): bool
    {
        if (! Schema::hasTable('fuel_tanks')) {
            return false;
        }

        foreach (['id', 'business_id', 'product_id'] as $column) {
            if (! Schema::hasColumn('fuel_tanks', $column)) {
                return false;
            }
        }

        return ! $needsLocation || Schema::hasColumn('fuel_tanks', 'location_id');
    }

    protected function isFuelCategoryName(string $name): bool
    {
        return $name !== '' && preg_match('/\bfuels?\b/', $name) === 1;
    }

    /** @return array<string, mixed> */
    public function rowData(int $productId, int $locationId): array
    {
        $businessId = $this->numbers->businessId();
        $isFuel = $this->isFuelProduct($productId, $locationId);
        $productName = '';

        if (Schema::hasTable('products')) {
            $productName = (string) (DB::table('products')
                ->where('business_id', $businessId)
                ->where('id', $productId)
                ->value('name') ?? '');
        }

        $tanks = collect();
        if ($isFuel && $this->fuelTankLookupAvailable(true)) {
            $columns = ['id', 'product_id'];
            $columns[] = Schema::hasColumn('fuel_tanks', 'fuel_tank_number')
                ? 'fuel_tank_number'
                : DB::raw('CAST(id AS CHAR) as fuel_tank_number');
            $columns[] = Schema::hasColumn('fuel_tanks', 'current_balance')
                ? 'current_balance'
                : DB::raw('0 as current_balance');
            if (Schema::hasColumn('fuel_tanks', 'tank_capacity')) {
                $columns[] = 'tank_capacity';
            }
            if (Schema::hasColumn('fuel_tanks', 'unit_name')) {
                $columns[] = 'unit_name';
            }

            $tankQuery = DB::table('fuel_tanks')
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->where('product_id', $productId);

            $tanks = $tankQuery
                ->orderBy(Schema::hasColumn('fuel_tanks', 'fuel_tank_number') ? 'fuel_tank_number' : 'id')
                ->get($columns);

            /*
             * Tank Transaction Details is the stock authority for the Unload Tanks
             * form.  fuel_tanks.current_balance is a cached/legacy value and can be
             * stale after back-dated settlements, dip resets, deleted purchases or
             * tank transfers.  Recalculate each displayed tank from the exact four
             * movement branches used by Petro General -> Tank Transaction Details,
             * but keep this implementation inside Purchase so the modules remain
             * independent.
             */
            $ledgerBalances = $this->tankTransactionDetailBalances(
                $businessId,
                $tanks->pluck('id')->map(fn ($id): int => (int) $id)->all()
            );

            if ($ledgerBalances !== null) {
                $tanks->transform(function ($tank) use ($ledgerBalances) {
                    $tankId = (int) $tank->id;
                    $tank->current_balance = (float) ($ledgerBalances[$tankId] ?? 0.0);

                    return $tank;
                });
            }
        }

        return [
            'is_fuel' => $isFuel,
            'product_id' => $productId,
            'product_name' => $productName,
            'tanks' => $tanks,
        ];
    }

    /**
     * Calculate the latest balance exactly from the sources used by Petro General's
     * Tank Transaction Details ledger.
     *
     * Each transaction/tank movement is kept as a separate row before accumulating
     * so sold quantities are ABS()'d at the same stage as the ledger running balance.
     * Testing litres are intentionally display-only and therefore do not reduce stock.
     *
     * @param array<int, int> $tankIds
     * @return array<int, float>|null Null means the legacy fallback should be kept.
     */
    protected function tankTransactionDetailBalances(int $businessId, array $tankIds): ?array
    {
        $tankIds = array_values(array_unique(array_filter(array_map('intval', $tankIds), fn (int $id): bool => $id > 0)));
        if ($tankIds === []) {
            return [];
        }

        if (! Schema::hasTable('transactions')
            || ! Schema::hasTable('tank_purchase_lines')
            || ! Schema::hasTable('tank_sell_lines')
            || ! Schema::hasTable('fuel_tanks')) {
            return null;
        }

        try {
            $balances = array_fill_keys($tankIds, 0.0);

            // Mirrors TanksTransactionDetailController::_getPurchaseQuery().
            $purchaseRows = DB::table('transactions as t')
                ->leftJoin('tank_purchase_lines as tpl', function ($join) {
                    $join->on('t.id', '=', 'tpl.transaction_id')
                        ->where('tpl.quantity', '!=', 0);
                })
                ->join('fuel_tanks as ft', 'tpl.tank_id', '=', 'ft.id')
                ->where('t.business_id', $businessId)
                ->where('ft.business_id', $businessId)
                ->whereIn('ft.id', $tankIds)
                ->select([
                    'ft.id as fuel_tank_id',
                    't.id as source_id',
                    DB::raw("SUM(CASE
                        WHEN t.type IN ('purchase', 'opening_stock') THEN tpl.quantity
                        WHEN t.type = 'stock_adjustment'
                             AND COALESCE(t.sub_type, '') = 'dip_resetting'
                             AND COALESCE(t.stock_adjustment_type, '') = 'increase'
                            THEN tpl.quantity
                        ELSE 0
                    END) as purchase_qty"),
                    DB::raw("SUM(CASE
                        WHEN t.type = '_deleted_purchase' THEN tpl.quantity
                        ELSE 0
                    END) as sold_qty"),
                ])
                ->groupBy('ft.id', 't.id')
                ->get();

            foreach ($purchaseRows as $row) {
                $tankId = (int) $row->fuel_tank_id;
                $balances[$tankId] = ($balances[$tankId] ?? 0.0)
                    + (float) ($row->purchase_qty ?? 0)
                    - abs((float) ($row->sold_qty ?? 0));
            }

            // Mirrors TanksTransactionDetailController::_getSellQuery().
            $sellRows = DB::table('transactions as t')
                ->leftJoin('tank_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('fuel_tanks as ft', 'tsl.tank_id', '=', 'ft.id')
                ->where('t.business_id', $businessId)
                ->where('ft.business_id', $businessId)
                ->whereIn('ft.id', $tankIds)
                ->whereNotIn('t.type', ['purchase', '_deleted_purchase'])
                ->select([
                    'ft.id as fuel_tank_id',
                    't.id as source_id',
                    DB::raw("SUM(CASE
                        WHEN t.type = 'stock_adjustment'
                             AND COALESCE(t.sub_type, '') = 'dip_resetting'
                             AND COALESCE(t.stock_adjustment_type, '') = 'decrease'
                            THEN tsl.quantity
                        WHEN t.type != 'stock_adjustment' THEN tsl.quantity
                        ELSE 0
                    END) as sold_qty"),
                ])
                ->groupBy('ft.id', 't.id')
                ->get();

            foreach ($sellRows as $row) {
                $tankId = (int) $row->fuel_tank_id;
                $balances[$tankId] = ($balances[$tankId] ?? 0.0)
                    - abs((float) ($row->sold_qty ?? 0));
            }

            if (Schema::hasTable('tank_transfers')) {
                $transferInRows = DB::table('tank_transfers as tt')
                    ->join('fuel_tanks as ft', 'tt.to_tank', '=', 'ft.id')
                    ->where('tt.business_id', $businessId)
                    ->where('ft.business_id', $businessId)
                    ->whereIn('ft.id', $tankIds)
                    ->select('ft.id as fuel_tank_id', 'tt.quantity')
                    ->get();

                foreach ($transferInRows as $row) {
                    $tankId = (int) $row->fuel_tank_id;
                    $balances[$tankId] = ($balances[$tankId] ?? 0.0) + (float) ($row->quantity ?? 0);
                }

                $transferOutRows = DB::table('tank_transfers as tt')
                    ->join('fuel_tanks as ft', 'tt.from_tank', '=', 'ft.id')
                    ->where('tt.business_id', $businessId)
                    ->where('ft.business_id', $businessId)
                    ->whereIn('ft.id', $tankIds)
                    ->select('ft.id as fuel_tank_id', 'tt.quantity')
                    ->get();

                foreach ($transferOutRows as $row) {
                    $tankId = (int) $row->fuel_tank_id;
                    $balances[$tankId] = ($balances[$tankId] ?? 0.0) - abs((float) ($row->quantity ?? 0));
                }
            }

            return array_map(static fn ($value): float => round((float) $value, 6), $balances);
        } catch (\Throwable $e) {
            \Log::error('Purchase unload tanks: unable to calculate Tank Transaction Details balance', [
                'business_id' => $businessId,
                'tank_ids' => $tankIds,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $preparedLines
     * @param array<string|int, mixed> $submittedTanks
     * @return array<int, array{tank_id: int, product_id: int, quantity: float}>
     */
    public function prepareAllocations(array $preparedLines, array $submittedTanks, int $locationId, string $status): array
    {
        if ($status !== 'received') {
            return [];
        }

        $requiredByProduct = [];
        foreach ($preparedLines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            if ($productId > 0 && $this->isFuelProduct($productId, $locationId)) {
                $requiredByProduct[$productId] = ($requiredByProduct[$productId] ?? 0.0)
                    + max(0, (float) ($line['quantity'] ?? 0));
            }
        }

        if ($requiredByProduct === []) {
            return [];
        }
        if (! Schema::hasTable('fuel_tanks') || ! Schema::hasTable('tank_purchase_lines')) {
            throw new \RuntimeException('Fuel tank tables are not available in this tenant database.');
        }

        $businessId = $this->numbers->businessId();
        $allocations = [];
        foreach ($requiredByProduct as $productId => $requiredQty) {
            $validTanks = DB::table('fuel_tanks')
                ->where('business_id', $businessId)
                ->where('location_id', $locationId)
                ->where('product_id', $productId)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            if ($validTanks === []) {
                throw new \InvalidArgumentException('No unload tank is configured for one of the selected fuel products at this business location.');
            }

            $productTanks = (array) ($submittedTanks[$productId] ?? $submittedTanks[(string) $productId] ?? []);
            $allocatedQty = 0.0;
            foreach ($productTanks as $tankId => $tankData) {
                $tankId = (int) $tankId;
                $quantity = max(0, $this->numbers->number(is_array($tankData) ? ($tankData['qty'] ?? 0) : $tankData));
                if ($quantity <= 0) {
                    continue;
                }
                if (! in_array($tankId, $validTanks, true)) {
                    throw new \InvalidArgumentException('An unload tank selection is not valid for the selected product and location.');
                }
                $allocatedQty += $quantity;
                $allocations[] = [
                    'tank_id' => $tankId,
                    'product_id' => (int) $productId,
                    'quantity' => round($quantity, 6),
                ];
            }

            if (abs($allocatedQty - $requiredQty) > 0.00001) {
                throw new \InvalidArgumentException(sprintf(
                    'Allocate the complete received fuel quantity to unload tanks. Required: %.3f; allocated: %.3f.',
                    $requiredQty,
                    $allocatedQty
                ));
            }
        }

        return $allocations;
    }

    /** @param array<int, array{tank_id: int, product_id: int, quantity: float}> $allocations */
    public function saveAllocations(int $transactionId, array $allocations): void
    {
        if ($allocations === []) {
            return;
        }

        $businessId = $this->numbers->businessId();
        foreach ($allocations as $allocation) {
            $tank = DB::table('fuel_tanks')
                ->where('business_id', $businessId)
                ->where('id', $allocation['tank_id'])
                ->where('product_id', $allocation['product_id'])
                ->lockForUpdate()
                ->first();
            if (! $tank) {
                throw new \InvalidArgumentException('The selected unload tank is no longer available.');
            }

            // Use the same ledger balance shown to the user, not the stale cached
            // fuel_tanks.current_balance, when recording the pre-unload quantity.
            $ledgerBalances = $this->tankTransactionDetailBalances($businessId, [(int) $tank->id]);
            $before = $ledgerBalances !== null
                ? (float) ($ledgerBalances[(int) $tank->id] ?? 0.0)
                : (float) ($tank->current_balance ?? 0);
            $quantity = (float) $allocation['quantity'];
            DB::table('fuel_tanks')->where('id', $tank->id)->update($this->schema->filter('fuel_tanks', [
                'current_balance' => round($before + $quantity, 6),
                'updated_at' => now(),
            ]));

            DB::table('tank_purchase_lines')->insert($this->schema->filter('tank_purchase_lines', [
                'business_id' => $businessId,
                'transaction_id' => $transactionId,
                'tank_id' => (int) $tank->id,
                'product_id' => (int) $allocation['product_id'],
                'quantity' => round($quantity, 6),
                'instock_qty' => round($before, 6),
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /** @return array<int, array<int, float>> */
    public function existingAllocations(int $transactionId): array
    {
        if (! Schema::hasTable('tank_purchase_lines')) {
            return [];
        }

        $query = DB::table('tank_purchase_lines')
            ->where('business_id', $this->numbers->businessId())
            ->where('transaction_id', $transactionId);
        if (Schema::hasColumn('tank_purchase_lines', 'new_deleted_at')) {
            $query->whereNull('new_deleted_at');
        }

        $result = [];
        foreach ($query->get(['tank_id', 'product_id', 'quantity']) as $line) {
            $productId = (int) $line->product_id;
            $tankId = (int) $line->tank_id;
            $result[$productId][$tankId] = (float) $line->quantity;
        }

        return $result;
    }

    public function reverseAndDelete(int $transactionId): void
    {
        if (! Schema::hasTable('tank_purchase_lines') || ! Schema::hasTable('fuel_tanks')) {
            return;
        }

        $query = DB::table('tank_purchase_lines')
            ->where('business_id', $this->numbers->businessId())
            ->where('transaction_id', $transactionId);
        if (Schema::hasColumn('tank_purchase_lines', 'new_deleted_at')) {
            $query->whereNull('new_deleted_at');
        }

        $lines = $query->lockForUpdate()->get(['id', 'tank_id', 'quantity']);
        foreach ($lines as $line) {
            $tank = DB::table('fuel_tanks')->where('id', $line->tank_id)->lockForUpdate()->first();
            if ($tank) {
                $balance = max(0, (float) ($tank->current_balance ?? 0) - (float) $line->quantity);
                DB::table('fuel_tanks')->where('id', $tank->id)->update($this->schema->filter('fuel_tanks', [
                    'current_balance' => round($balance, 6),
                    'updated_at' => now(),
                ]));
            }
        }

        DB::table('tank_purchase_lines')->whereIn('id', $lines->pluck('id')->all())->delete();
    }
}

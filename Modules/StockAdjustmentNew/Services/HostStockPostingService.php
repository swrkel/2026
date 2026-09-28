<?php

namespace Modules\StockAdjustmentNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\StockAdjustmentNew\Entities\StockAdjustment;
use Modules\StockAdjustmentNew\Entities\StockAdjustmentAccountMapping;
use Modules\StockAdjustmentNew\Entities\StockAdjustmentLine;

/**
 * Posts an approved Stock Adjustment New document into the shared ERP stock
 * and finance ledgers. All writes are executed on the active tenant connection
 * and are therefore covered by the caller's database transaction.
 */
class HostStockPostingService
{
    /** @var array<string, array<int, string>> */
    private array $columns = [];

    /**
     * @return array{
     *   host_transaction_id:?int,
     *   host_transaction_ids:array<string,int>,
     *   document_direction:string,
     *   stock_rows:int,
     *   account_entries:int,
     *   warnings:array<int,string>
     * }
     */
    public function post(StockAdjustment $adjustment, ?int $userId, array $settings = []): array
    {
        $adjustment->loadMissing('lines');

        if (! $adjustment->location_id) {
            throw new \RuntimeException('A valid Location is required before the stock adjustment can be posted.');
        }

        $nonZeroLines = $adjustment->lines->filter(
            static fn (StockAdjustmentLine $line): bool => abs((float) $line->adjustment_qty) > 0.0000001
        )->values();

        if ($nonZeroLines->isEmpty()) {
            throw new \RuntimeException('This adjustment has no stock difference to post.');
        }

        $lineGroups = [
            'increase' => collect(),
            'decrease' => collect(),
        ];

        foreach ($nonZeroLines as $line) {
            $declaredDirection = strtolower((string) $line->stock_adjustment_type);
            $quantityDirection = (float) $line->adjustment_qty > 0 ? 'increase' : 'decrease';

            if (! in_array($declaredDirection, ['increase', 'decrease'], true)) {
                throw new \RuntimeException(
                    'Product ' . ($line->product_name ?: ('#' . $line->product_id)) .
                    ' does not have a valid Increase/Decrease type.'
                );
            }

            if ($declaredDirection !== $quantityDirection) {
                throw new \RuntimeException(
                    sprintf(
                        'Product %s is marked %s but its counted quantity creates a %s difference.',
                        $line->product_name ?: ('#' . $line->product_id),
                        ucfirst($declaredDirection),
                        ucfirst($quantityDirection)
                    )
                );
            }

            $lineGroups[$declaredDirection]->push($line);
        }

        $activeDirections = array_values(array_filter(
            ['increase', 'decrease'],
            static fn (string $direction): bool => $lineGroups[$direction]->isNotEmpty()
        ));
        $documentDirection = count($activeDirections) > 1 ? 'mixed' : $activeDirections[0];

        if ((string) $adjustment->stock_adjustment_type !== $documentDirection) {
            $adjustment->forceFill(['stock_adjustment_type' => $documentDirection])->save();
        }

        $this->assertHostPostingTables();
        $hostTransactionIds = [];
        $stockRows = 0;
        $accountEntries = 0;
        $warnings = [];

        foreach ($activeDirections as $direction) {
            $directionLines = $lineGroups[$direction];
            $reference = count($activeDirections) > 1
                ? $adjustment->adjustment_no . ($direction === 'increase' ? '-INC' : '-DEC')
                : $adjustment->adjustment_no;
            $groupAmount = (float) $directionLines->sum(
                static fn (StockAdjustmentLine $line): float => abs((float) $line->cost_amount)
            );

            $hostTransactionId = $this->createHostTransaction(
                $adjustment,
                $userId,
                $direction,
                $reference,
                $groupAmount
            );
            $hostTransactionIds[$direction] = $hostTransactionId;

            foreach ($directionLines as $line) {
                $absoluteQty = abs((float) $line->adjustment_qty);
                $signedQty = $direction === 'increase' ? $absoluteQty : -$absoluteQty;

                if (! (bool) ($settings['allow_negative_stock'] ?? false) && $direction === 'decrease') {
                    $this->assertSufficientLocationStock($adjustment, $line, $absoluteQty);
                }

                $this->createHostAdjustmentLine($hostTransactionId, $line, $direction);
                $this->updateLocationStock($adjustment, $line, $signedQty, $settings);
                $this->updateStoreStock($adjustment, $line, $signedQty, $settings);
                $this->updateBatchStock($adjustment, $line, $signedQty, $settings);
                $stockRows++;

                $accountEntries += $this->postAccountingEntries(
                    $adjustment,
                    $line,
                    $hostTransactionId,
                    $direction,
                    $userId
                );
            }
        }

        return [
            'host_transaction_id' => $hostTransactionIds[$activeDirections[0]] ?? null,
            'host_transaction_ids' => $hostTransactionIds,
            'document_direction' => $documentDirection,
            'stock_rows' => $stockRows,
            'account_entries' => $accountEntries,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function assertHostPostingTables(): void
    {
        if (! Schema::hasTable('stock_adjustment_lines')) {
            throw new \RuntimeException('The shared stock adjustment lines table is not available. Stock and accounts were not changed.');
        }

        if (! Schema::hasTable('variation_location_details')) {
            throw new \RuntimeException('The shared location stock table is not available. Stock and accounts were not changed.');
        }

        if (! class_exists('App\\AccountTransaction') && ! Schema::hasTable('account_transactions')) {
            throw new \RuntimeException('The Finance account transaction ledger is not available. Stock and accounts were not changed.');
        }
    }

    private function createHostTransaction(
        StockAdjustment $adjustment,
        ?int $userId,
        string $direction,
        string $reference,
        float $amount
    ): int {
        if (! Schema::hasTable('transactions')) {
            throw new \RuntimeException('The shared transactions table is not available in this tenant database. Stock and accounts were not changed.');
        }

        // The module status/row lock is the idempotency guard. An existing
        // shared transaction with the same reference means the reference has
        // already been consumed (or collided with another process). Stop rather
        // than applying stock/account movements a second time.
        $existing = null;
        if ($this->hasColumn('transactions', 'id') && $this->hasColumn('transactions', 'ref_no')) {
            $existing = DB::table('transactions')
                ->where('ref_no', $reference)
                ->when($this->hasColumn('transactions', 'business_id'), fn ($query) => $query->where('business_id', $adjustment->business_id))
                ->when($this->hasColumn('transactions', 'type'), fn ($query) => $query->where('type', 'stock_adjustment'))
                ->first();
        }

        if ($existing && isset($existing->id)) {
            throw new \RuntimeException(
                'Shared stock adjustment transaction ' . $reference .
                ' already exists (ID ' . (int) $existing->id . '). Posting was stopped to prevent duplicate stock or account entries.'
            );
        }

        $payload = [
            'business_id' => $adjustment->business_id,
            'location_id' => $adjustment->location_id,
            'store_id' => $adjustment->store_id,
            'type' => 'stock_adjustment',
            'sub_type' => 'stock_adjustment_new',
            'status' => 'received',
            'ref_no' => $reference,
            'invoice_no' => $reference,
            'transaction_date' => optional($adjustment->adjustment_date)->format('Y-m-d') . ' ' . now()->format('H:i:s'),
            'adjustment_type' => 'normal',
            'stock_adjustment_type' => $direction,
            'final_total' => abs($amount),
            'total_before_tax' => abs($amount),
            'additional_notes' => trim('Stock Adjustment New ' . $adjustment->adjustment_no . ' (' . ucfirst($direction) . '). ' . (string) $adjustment->notes),
            'created_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $this->filterPayload('transactions', $payload);
        if ($payload === [] || ! array_key_exists('type', $payload)) {
            throw new \RuntimeException('The shared transaction columns could not be identified. Stock and accounts were not changed.');
        }

        return (int) DB::table('transactions')->insertGetId($payload);
    }

    private function createHostAdjustmentLine(int $transactionId, StockAdjustmentLine $line, string $direction): void
    {
        if (! Schema::hasTable('stock_adjustment_lines')
            || ! $this->hasColumn('stock_adjustment_lines', 'transaction_id')) {
            throw new \RuntimeException('The shared stock adjustment lines table is not available. Stock and accounts were not changed.');
        }

        $existing = DB::table('stock_adjustment_lines')
            ->where('transaction_id', $transactionId)
            ->when($this->hasColumn('stock_adjustment_lines', 'product_id'), fn ($query) => $query->where('product_id', $line->product_id))
            ->when(
                $line->variation_id && $this->hasColumn('stock_adjustment_lines', 'variation_id'),
                fn ($query) => $query->where('variation_id', $line->variation_id)
            )
            ->when(
                $line->batch_no && $this->hasColumn('stock_adjustment_lines', 'lot_no'),
                fn ($query) => $query->where('lot_no', $line->batch_no)
            )
            ->exists();

        if ($existing) {
            return;
        }

        $payload = [
            'transaction_id' => $transactionId,
            'product_id' => $line->product_id,
            'variation_id' => $line->variation_id,
            'quantity' => abs((float) $line->adjustment_qty),
            'unit_price' => abs((float) $line->unit_cost),
            'type' => 'normal',
            'stock_adjustment_type' => $direction,
            'lot_no' => $line->batch_no,
            'batch_no' => $line->batch_no,
            'expiry_date' => optional($line->expiry_date)->format('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = $this->filterPayload('stock_adjustment_lines', $payload);
        if ($payload !== []) {
            DB::table('stock_adjustment_lines')->insert($payload);
        }
    }

    private function assertSufficientLocationStock(StockAdjustment $adjustment, StockAdjustmentLine $line, float $quantity): void
    {
        if (! Schema::hasTable('variation_location_details')) {
            return;
        }

        $table = 'variation_location_details';
        $qtyColumn = $this->firstColumn($table, ['qty_available', 'quantity', 'stock_qty', 'current_stock']);
        if ($qtyColumn === null) {
            return;
        }

        $query = $this->stockRowQuery($table, $adjustment, $line, false);
        if ($query === null) {
            return;
        }

        $available = (float) ($query->lockForUpdate()->value($qtyColumn) ?? 0);
        if ($available + 0.0000001 < $quantity) {
            throw new \RuntimeException(
                sprintf(
                    'Insufficient stock for %s. Available: %s, decrease requested: %s.',
                    $line->product_name ?: ('#' . $line->product_id),
                    rtrim(rtrim(number_format($available, 4, '.', ''), '0'), '.'),
                    rtrim(rtrim(number_format($quantity, 4, '.', ''), '0'), '.')
                )
            );
        }
    }

    private function updateLocationStock(
        StockAdjustment $adjustment,
        StockAdjustmentLine $line,
        float $signedQty,
        array $settings
    ): void {
        $this->updateStockTable(
            'variation_location_details',
            $adjustment,
            $line,
            $signedQty,
            false,
            (bool) ($settings['allow_negative_stock'] ?? false)
        );
    }

    private function updateStoreStock(
        StockAdjustment $adjustment,
        StockAdjustmentLine $line,
        float $signedQty,
        array $settings
    ): void {
        if (! $adjustment->store_id) {
            return;
        }

        // A Store is a child of a Location. Never post a Location transaction
        // into a Store belonging to another Location, even if an old/default
        // Store id reaches the module from session state.
        if (Schema::hasTable('stores') && Schema::hasColumn('stores', 'location_id')) {
            $storeQuery = DB::table('stores')
                ->where('id', $adjustment->store_id)
                ->where('location_id', $adjustment->location_id);
            if (Schema::hasColumn('stores', 'business_id')) {
                $storeQuery->where('business_id', $adjustment->business_id);
            }
            if (! $storeQuery->exists()) {
                throw new \RuntimeException('The selected Store does not belong to the selected Location. Stock and accounts were not changed.');
            }
        }

        foreach (['variation_store_details', 'variation_location_store_details'] as $table) {
            if (Schema::hasTable($table)) {
                $this->updateStockTable(
                    $table,
                    $adjustment,
                    $line,
                    $signedQty,
                    true,
                    (bool) ($settings['allow_negative_stock'] ?? false)
                );
                return;
            }
        }

        if (! $this->hasColumn('variation_location_details', 'store_id')) {
            throw new \RuntimeException(
                'A Store was selected, but no shared store stock table is available in this tenant database. Stock and accounts were not changed.'
            );
        }
    }
    private function updateStockTable(
        string $table,
        StockAdjustment $adjustment,
        StockAdjustmentLine $line,
        float $signedQty,
        bool $requiresStore,
        bool $allowNegative
    ): void {
        if (! Schema::hasTable($table)) {
            if ($table === 'variation_location_details') {
                throw new \RuntimeException('The shared location stock table is not available in this tenant database.');
            }
            return;
        }

        $quantityColumn = $this->firstColumn($table, ['qty_available', 'quantity', 'stock_qty', 'current_stock']);
        if ($quantityColumn === null) {
            throw new \RuntimeException('The shared stock quantity column could not be identified in ' . $table . '.');
        }

        $query = $this->stockRowQuery($table, $adjustment, $line, $requiresStore);
        if ($query === null) {
            throw new \RuntimeException('The shared stock keys could not be identified in ' . $table . '.');
        }

        $rows = $query->orderBy('id')->lockForUpdate()->get();
        if ($rows->isNotEmpty()) {
            $currentQty = (float) $rows->sum(
                static fn ($row): float => (float) ($row->{$quantityColumn} ?? 0)
            );
            $newQty = $currentQty + $signedQty;
            if (! $allowNegative && $newQty < -0.0000001) {
                throw new \RuntimeException('Posting this adjustment would create negative stock for ' . ($line->product_name ?: ('#' . $line->product_id)) . '.');
            }

            $updates = [$quantityColumn => $newQty];
            if ($this->hasColumn($table, 'updated_at')) {
                $updates['updated_at'] = now();
            }
            DB::table($table)->where('id', $rows->first()->id)->update($updates);

            // Duplicate summary rows used to be updated together, multiplying
            // the total quantity. Preserve their combined pre-posting total and
            // neutralise the extras instead.
            $duplicateIds = $rows->slice(1)->pluck('id')->all();
            if ($duplicateIds !== []) {
                $duplicateUpdate = [$quantityColumn => 0];
                if ($this->hasColumn($table, 'updated_at')) {
                    $duplicateUpdate['updated_at'] = now();
                }
                DB::table($table)->whereIn('id', $duplicateIds)->update($duplicateUpdate);
            }
            return;
        }

        if ($signedQty < 0 && ! $allowNegative) {
            throw new \RuntimeException('No stock row exists for ' . ($line->product_name ?: ('#' . $line->product_id)) . ' in the selected location/store.');
        }

        $payload = $this->stockKeyPayload($table, $adjustment, $line, $requiresStore);
        $payload[$quantityColumn] = $signedQty;
        if ($this->hasColumn($table, 'created_at')) {
            $payload['created_at'] = now();
        }
        if ($this->hasColumn($table, 'updated_at')) {
            $payload['updated_at'] = now();
        }

        DB::table($table)->insert($payload);
    }

    private function stockRowQuery(
        string $table,
        StockAdjustment $adjustment,
        StockAdjustmentLine $line,
        bool $requiresStore
    ) {
        $keys = $this->stockKeyPayload($table, $adjustment, $line, $requiresStore);
        if ($keys === []) {
            return null;
        }

        $query = DB::table($table);
        foreach ($keys as $column => $value) {
            $value === null ? $query->whereNull($column) : $query->where($column, $value);
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function stockKeyPayload(
        string $table,
        StockAdjustment $adjustment,
        StockAdjustmentLine $line,
        bool $requiresStore
    ): array {
        $payload = [];
        $productColumn = $this->firstColumn($table, ['product_id']);
        $variationColumn = $this->firstColumn($table, ['variation_id', 'product_variation_id']);
        $locationColumn = $this->firstColumn($table, ['location_id', 'business_location_id']);
        $storeColumn = $this->firstColumn($table, ['store_id']);

        if ($productColumn !== null) {
            $payload[$productColumn] = $line->product_id;
        }
        if ($variationColumn !== null) {
            $payload[$variationColumn] = $line->variation_id;
        }
        if ($locationColumn !== null) {
            $payload[$locationColumn] = $adjustment->location_id;
        }
        if ($requiresStore) {
            if ($storeColumn === null) {
                return [];
            }
            $payload[$storeColumn] = $adjustment->store_id;
        } elseif ($storeColumn !== null && $adjustment->store_id !== null) {
            // Some tenant versions keep location/store stock in one table.
            $payload[$storeColumn] = $adjustment->store_id;
        }

        if ($variationColumn === null && $productColumn === null) {
            return [];
        }

        return $payload;
    }

    private function updateBatchStock(
        StockAdjustment $adjustment,
        StockAdjustmentLine $line,
        float $signedQty,
        array $settings
    ): void {
        $batchNo = trim((string) $line->batch_no);
        if ($batchNo === '') {
            return;
        }

        if ($this->updateProductsNewBatch($adjustment, $line, $signedQty, $settings)) {
            return;
        }

        $this->updateLegacyPurchaseBatch($adjustment, $line, $signedQty, $settings);
    }

    private function updateProductsNewBatch(
        StockAdjustment $adjustment,
        StockAdjustmentLine $line,
        float $signedQty,
        array $settings
    ): bool {
        $table = 'products_new_batches';
        if (! Schema::hasTable($table)) {
            return false;
        }

        $batchColumn = $this->firstColumn($table, ['batch_no', 'batch_number', 'lot_number', 'lot_no']);
        $quantityColumn = $this->firstColumn($table, ['available_qty', 'qty_available', 'current_qty', 'current_stock', 'stock_qty', 'quantity']);
        if ($batchColumn === null || $quantityColumn === null) {
            return false;
        }

        $query = DB::table($table)->where($batchColumn, $line->batch_no);
        foreach ([
            $this->firstColumn($table, ['product_id']) => $line->product_id,
            $this->firstColumn($table, ['variation_id', 'product_variation_id']) => $line->variation_id,
            $this->firstColumn($table, ['business_id']) => $adjustment->business_id,
            $this->firstColumn($table, ['location_id', 'business_location_id']) => $adjustment->location_id,
            $this->firstColumn($table, ['store_id']) => $adjustment->store_id,
        ] as $column => $value) {
            if (is_string($column) && $column !== '' && $value !== null) {
                $query->where($column, $value);
            }
        }

        $row = $query->lockForUpdate()->first();
        if (! $row) {
            return false;
        }

        $newQty = (float) $row->{$quantityColumn} + $signedQty;
        if (! (bool) ($settings['allow_negative_stock'] ?? false) && $newQty < -0.0000001) {
            throw new \RuntimeException('Posting would create negative batch stock for batch ' . $line->batch_no . '.');
        }

        $updates = [$quantityColumn => $newQty];
        if ($this->hasColumn($table, 'updated_at')) {
            $updates['updated_at'] = now();
        }
        $query->update($updates);

        return true;
    }

    private function updateLegacyPurchaseBatch(
        StockAdjustment $adjustment,
        StockAdjustmentLine $line,
        float $signedQty,
        array $settings
    ): void {
        if (! Schema::hasTable('purchase_lines') || ! Schema::hasTable('transactions')) {
            return;
        }

        $batchColumn = $this->firstColumn('purchase_lines', ['lot_number', 'batch_no', 'batch_number', 'lot_no']);
        $quantityColumn = $this->firstColumn('purchase_lines', ['quantity', 'qty']);
        $adjustedColumn = $this->firstColumn('purchase_lines', ['quantity_adjusted', 'qty_adjusted']);
        $soldColumn = $this->firstColumn('purchase_lines', ['quantity_sold', 'qty_sold']);
        $returnedColumn = $this->firstColumn('purchase_lines', ['quantity_returned', 'qty_returned']);
        if ($batchColumn === null || $quantityColumn === null || $adjustedColumn === null) {
            return;
        }

        $query = DB::table('purchase_lines as pl')
            ->join('transactions as t', 'pl.transaction_id', '=', 't.id')
            ->where('pl.' . $batchColumn, $line->batch_no)
            ->where('pl.product_id', $line->product_id)
            ->when($line->variation_id && $this->hasColumn('purchase_lines', 'variation_id'), fn ($q) => $q->where('pl.variation_id', $line->variation_id))
            ->when($this->hasColumn('transactions', 'business_id'), fn ($q) => $q->where('t.business_id', $adjustment->business_id))
            ->when($this->hasColumn('transactions', 'location_id'), fn ($q) => $q->where('t.location_id', $adjustment->location_id))
            ->when($adjustment->store_id && $this->hasColumn('transactions', 'store_id'), fn ($q) => $q->where('t.store_id', $adjustment->store_id))
            ->select('pl.*')
            ->orderBy('pl.id')
            ->lockForUpdate();

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return;
        }

        if ($signedQty < 0) {
            $remaining = abs($signedQty);
            foreach ($rows as $row) {
                $available = (float) $row->{$quantityColumn}
                    - (float) ($soldColumn ? $row->{$soldColumn} : 0)
                    - (float) $row->{$adjustedColumn}
                    - (float) ($returnedColumn ? $row->{$returnedColumn} : 0);
                $take = min(max(0, $available), $remaining);
                if ($take <= 0) {
                    continue;
                }
                DB::table('purchase_lines')->where('id', $row->id)->update([
                    $adjustedColumn => (float) $row->{$adjustedColumn} + $take,
                    ...($this->hasColumn('purchase_lines', 'updated_at') ? ['updated_at' => now()] : []),
                ]);
                $remaining -= $take;
                if ($remaining <= 0.0000001) {
                    break;
                }
            }

            if ($remaining > 0.0000001 && ! (bool) ($settings['allow_negative_stock'] ?? false)) {
                throw new \RuntimeException('Insufficient available quantity in batch ' . $line->batch_no . '.');
            }
            return;
        }

        // For an increase, first reverse earlier adjustments from the same batch.
        $remaining = $signedQty;
        foreach ($rows as $row) {
            $adjusted = max(0, (float) $row->{$adjustedColumn});
            $restore = min($adjusted, $remaining);
            if ($restore <= 0) {
                continue;
            }
            DB::table('purchase_lines')->where('id', $row->id)->update([
                $adjustedColumn => $adjusted - $restore,
                ...($this->hasColumn('purchase_lines', 'updated_at') ? ['updated_at' => now()] : []),
            ]);
            $remaining -= $restore;
            if ($remaining <= 0.0000001) {
                return;
            }
        }

        // Any balance represents genuinely additional stock for the selected lot.
        $first = $rows->first();
        DB::table('purchase_lines')->where('id', $first->id)->update([
            $quantityColumn => (float) $first->{$quantityColumn} + $remaining,
            ...($this->hasColumn('purchase_lines', 'updated_at') ? ['updated_at' => now()] : []),
        ]);
    }

    private function postAccountingEntries(
        StockAdjustment $adjustment,
        StockAdjustmentLine $line,
        int $transactionId,
        string $direction,
        ?int $userId
    ): int {
        $amount = abs((float) $line->cost_amount);
        if ($amount <= 0.0000001) {
            return 0;
        }

        $productScope = $this->productCategoryScope((int) $line->product_id);
        $mapping = $this->resolveMapping(
            (int) $adjustment->business_id,
            $direction,
            (string) $adjustment->adjustment_type,
            $productScope['category_id'],
            $productScope['sub_category_id'],
            (string) optional($adjustment->adjustment_date)->format('Y-m-d')
        );

        $counterpartAccountId = $mapping
            ? $this->mappingCounterpartAccountId($mapping, $direction)
            : null;

        if (! $mapping || ! $mapping->stock_account_id || ! $counterpartAccountId) {
            throw new \RuntimeException(
                'No complete active accounting mapping was found for ' .
                ($line->product_name ?: ('product #' . $line->product_id)) .
                ' and adjustment type ' . ucfirst($direction) . '. Select the Stock Account and both Increase/Decrease Accounts in Stock Adjustment Settings.'
            );
        }

        $this->assertAccountOwnership((int) $mapping->stock_account_id, (int) $adjustment->business_id);
        $this->assertAccountOwnership((int) $counterpartAccountId, (int) $adjustment->business_id);

        if ($direction === 'increase') {
            $entries = [
                [(int) $mapping->stock_account_id, 'debit', 'Stock increase - ' . $adjustment->adjustment_no],
                [(int) $counterpartAccountId, 'credit', 'Stock increase counterpart - ' . $adjustment->adjustment_no],
            ];
        } else {
            $entries = [
                [(int) $counterpartAccountId, 'debit', 'Stock decrease/loss - ' . $adjustment->adjustment_no],
                [(int) $mapping->stock_account_id, 'credit', 'Stock decrease - ' . $adjustment->adjustment_no],
            ];
        }

        foreach ($entries as [$accountId, $type, $note]) {
            $this->createAccountTransaction(
                $accountId,
                $type,
                $amount,
                $transactionId,
                $adjustment,
                $userId,
                $note
            );
        }

        return count($entries);
    }

    private function createAccountTransaction(
        int $accountId,
        string $type,
        float $amount,
        int $transactionId,
        StockAdjustment $adjustment,
        ?int $userId,
        string $note
    ): void {
        $payload = [
            'amount' => $amount,
            'account_id' => $accountId,
            'type' => $type,
            'sub_type' => 'ledger',
            'operation_date' => optional($adjustment->adjustment_date)->format('Y-m-d') . ' ' . now()->format('H:i:s'),
            'created_by' => $userId,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => null,
            'business_id' => $adjustment->business_id,
            'location_id' => $adjustment->location_id,
            'note' => $note,
        ];

        if (class_exists('App\\AccountTransaction') && method_exists('App\\AccountTransaction', 'createAccountTransaction')) {
            $modelPayload = $payload;
            // The shared AccountTransaction helper accepts business_id and uses
            // it to keep entries inside the active business. location_id is not
            // part of that helper's payload contract.
            unset($modelPayload['location_id']);
            if (Schema::hasTable('account_transactions')) {
                $modelPayload = $this->filterPayload('account_transactions', $modelPayload);
            }
            \App\AccountTransaction::createAccountTransaction($modelPayload);
            return;
        }

        if (! Schema::hasTable('account_transactions')) {
            throw new \RuntimeException('The Finance account transaction ledger is not available in this tenant database.');
        }

        $payload['created_at'] = now();
        $payload['updated_at'] = now();
        $filtered = $this->filterPayload('account_transactions', $payload);
        if ($filtered === []) {
            throw new \RuntimeException('The Finance account transaction columns could not be identified.');
        }
        DB::table('account_transactions')->insert($filtered);
    }

    /** @return array{category_id:?int,sub_category_id:?int} */
    private function productCategoryScope(int $productId): array
    {
        foreach (['products', 'products_new_products'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $idColumn = $this->firstColumn($table, ['id', 'product_id']);
            if ($idColumn === null) {
                continue;
            }

            $row = DB::table($table)->where($idColumn, $productId)->first();
            if (! $row) {
                continue;
            }

            $categoryColumn = $this->firstColumn($table, ['category_id']);
            $subCategoryColumn = $this->firstColumn($table, ['sub_category_id', 'subcategory_id']);

            return [
                'category_id' => $categoryColumn && $row->{$categoryColumn} !== null ? (int) $row->{$categoryColumn} : null,
                'sub_category_id' => $subCategoryColumn && $row->{$subCategoryColumn} !== null ? (int) $row->{$subCategoryColumn} : null,
            ];
        }

        return ['category_id' => null, 'sub_category_id' => null];
    }

    private function resolveMapping(
        int $businessId,
        string $direction,
        string $classification,
        ?int $categoryId,
        ?int $subCategoryId,
        string $date
    ): ?StockAdjustmentAccountMapping {
        $candidateTypes = array_values(array_unique(array_filter([
            $direction,
            $classification,
            'quantity',
        ])));

        // First honour a mapping that was genuinely effective on the document
        // date. This preserves historical accounting behaviour where such a
        // mapping exists.
        $mapping = $this->findCompleteMapping(
            $businessId,
            $candidateTypes,
            $direction,
            $classification,
            $categoryId,
            $subCategoryId,
            $date
        );

        if ($mapping) {
            return $mapping;
        }

        /*
         * Back-dated adjustments are allowed by this module. A common and valid
         * workflow is therefore:
         *   1. save/approve an adjustment dated a few days earlier;
         *   2. configure the finance mapping today;
         *   3. post the approved adjustment today.
         *
         * The old resolver looked only at the adjustment date. Since a newly
         * saved mapping defaults Effective From to today, that valid mapping was
         * invisible to a back-dated document and posting failed with "No complete
         * active accounting mapping" even though both accounts were linked.
         *
         * If there was no historical mapping for the document date, use the
         * currently effective mapping at the time of posting. Future-dated
         * mappings are still excluded because the lookup remains bounded by
         * today's date.
         */
        $postingDate = now()->toDateString();
        if ($postingDate !== $date) {
            return $this->findCompleteMapping(
                $businessId,
                $candidateTypes,
                $direction,
                $classification,
                $categoryId,
                $subCategoryId,
                $postingDate
            );
        }

        return null;
    }

    /**
     * Resolve one usable mapping for the requested date.
     *
     * Older tenant data can contain active mapping rows with one account blank.
     * Such an incomplete row must not shadow a later/general mapping that is
     * actually complete, so completeness is part of the candidate query rather
     * than being checked only after the first row has already been selected.
     *
     * @param array<int, string> $candidateTypes
     */
    private function findCompleteMapping(
        int $businessId,
        array $candidateTypes,
        string $direction,
        string $classification,
        ?int $categoryId,
        ?int $subCategoryId,
        string $date
    ): ?StockAdjustmentAccountMapping {
        $directionColumn = $direction === 'decrease' ? 'decrease_account_id' : 'increase_account_id';
        $hasDirectionColumns = Schema::hasColumn('san_stock_adjustment_account_mappings', $directionColumn);

        $mappings = StockAdjustmentAccountMapping::query()
            ->where('business_id', $businessId)
            ->where('is_active', 1)
            ->whereNotNull('stock_account_id')
            ->where('stock_account_id', '>', 0)
            ->where(function ($query) use ($hasDirectionColumns, $directionColumn, $candidateTypes): void {
                if ($hasDirectionColumns) {
                    // 8052 rows carry both direction accounts on one mapping and
                    // therefore do not need an Adjustment Type match. Legacy rows
                    // still use account_to_link_id + adjustment_type.
                    $query->where(function ($newStyle) use ($directionColumn): void {
                        $newStyle->whereNotNull($directionColumn)->where($directionColumn, '>', 0);
                    })->orWhere(function ($legacy) use ($candidateTypes): void {
                        $legacy->whereNotNull('account_to_link_id')
                            ->where('account_to_link_id', '>', 0)
                            ->whereIn('adjustment_type', $candidateTypes);
                    });
                    return;
                }

                $query->whereNotNull('account_to_link_id')
                    ->where('account_to_link_id', '>', 0)
                    ->whereIn('adjustment_type', $candidateTypes);
            })
            ->where(function ($query) use ($date): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date);
            });

        if ($hasDirectionColumns) {
            // Prefer the new two-account contract when an equally specific
            // legacy mapping is also present for the same category/date.
            $mappings->orderByRaw(
                'CASE WHEN ' . $directionColumn . ' IS NOT NULL AND ' . $directionColumn . ' > 0 THEN 0 ELSE 1 END'
            );
        }

        $mappings = $mappings
            ->orderByRaw(
                'CASE WHEN adjustment_type = ? THEN 0 WHEN adjustment_type = ? THEN 1 WHEN adjustment_type = ? THEN 2 ELSE 3 END',
                [$direction, $classification, 'quantity']
            )
            ->latest('effective_from')
            ->latest('id')
            ->get();

        return $mappings->sortByDesc(static function (StockAdjustmentAccountMapping $mapping) use ($categoryId, $subCategoryId): int {
            $score = 0;
            if ($mapping->sub_category_id !== null) {
                if ($subCategoryId === null || (int) $mapping->sub_category_id !== $subCategoryId) {
                    return -1000;
                }
                $score += 100;
            }
            if ($mapping->category_id !== null) {
                if ($categoryId === null || (int) $mapping->category_id !== $categoryId) {
                    return -1000;
                }
                $score += 10;
            }
            return $score;
        })->first(static function (StockAdjustmentAccountMapping $mapping) use ($categoryId, $subCategoryId): bool {
            return ($mapping->sub_category_id === null || (int) $mapping->sub_category_id === (int) $subCategoryId)
                && ($mapping->category_id === null || (int) $mapping->category_id === (int) $categoryId);
        });
    }

    private function mappingCounterpartAccountId(StockAdjustmentAccountMapping $mapping, string $direction): ?int
    {
        $directionColumn = $direction === 'decrease' ? 'decrease_account_id' : 'increase_account_id';
        $specific = (int) ($mapping->getAttribute($directionColumn) ?? 0);
        if ($specific > 0) {
            return $specific;
        }

        // Backward compatibility for mappings saved before 8052. Direction-only
        // legacy rows apply only to their own direction; Quantity/Value/Damage/
        // Expiry rows historically supplied the same counterpart to both sides.
        $legacy = (int) ($mapping->account_to_link_id ?? 0);
        if ($legacy <= 0) {
            return null;
        }

        $legacyType = strtolower((string) ($mapping->adjustment_type ?? 'quantity'));
        if (in_array($legacyType, ['increase', 'decrease'], true)) {
            return $legacyType === $direction ? $legacy : null;
        }

        return $legacy;
    }

    private function assertAccountOwnership(int $accountId, int $businessId): void
    {
        $table = Schema::hasTable('accounts') ? 'accounts' : (Schema::hasTable('finance_accounts') ? 'finance_accounts' : null);
        if ($table === null) {
            return;
        }

        $idColumn = $this->firstColumn($table, ['id', 'account_id']);
        $businessColumn = $this->firstColumn($table, ['business_id']);
        if ($idColumn === null || $businessColumn === null) {
            return;
        }

        if (! DB::table($table)->where($idColumn, $accountId)->where($businessColumn, $businessId)->exists()) {
            throw new \RuntimeException('An accounting mapping points to an account outside the selected business.');
        }
    }

    /** @return array<string, mixed> */
    private function filterPayload(string $table, array $payload): array
    {
        $columns = $this->columns($table);
        return array_filter(
            $payload,
            static fn ($value, string $key): bool => in_array($key, $columns, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /** @return array<int, string> */
    private function columns(string $table): array
    {
        if (! isset($this->columns[$table])) {
            $this->columns[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        return $this->columns[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    private function firstColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if ($this->hasColumn($table, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}

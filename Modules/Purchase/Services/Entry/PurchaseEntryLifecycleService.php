<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\StoreStockIntegrityService;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseEntryLifecycleService
{
    public function __construct(protected PurchaseSchemaUtil $schema, protected StoreStockIntegrityService $storeStock)
    {
    }

    public function lockPurchase(int $id, int $businessId): object
    {
        $query = DB::table('transactions')
            ->where('business_id', $businessId)
            ->where('type', 'purchase')
            ->where('id', $id)
            ->lockForUpdate();

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        $purchase = $query->first();
        if (! $purchase) {
            throw new \InvalidArgumentException('The purchase entry was not found or has already been deleted.');
        }

        return $purchase;
    }

    public function linesForUpdate(int $transactionId): Collection
    {
        if (! Schema::hasTable('purchase_lines')) {
            return collect();
        }

        $query = DB::table('purchase_lines as pl')
            ->where('pl.transaction_id', $transactionId)
            ->lockForUpdate();

        if (Schema::hasColumn('purchase_lines', 'deleted_at')) {
            $query->whereNull('pl.deleted_at');
        }

        $select = ['pl.*'];
        if (Schema::hasTable('products')) {
            $query->leftJoin('products as p', 'p.id', '=', 'pl.product_id');
            $select[] = Schema::hasColumn('products', 'enable_stock')
                ? 'p.enable_stock as enable_stock'
                : DB::raw('1 as enable_stock');
        } else {
            $select[] = DB::raw('1 as enable_stock');
        }

        return $query->orderBy('pl.id')->get($select);
    }

    public function assertCanModify(object $purchase, Collection $lines): void
    {
        $lineIds = $lines->pluck('id')->filter()->map(fn ($id): int => (int) $id)->values()->all();

        if (Schema::hasColumn('purchase_lines', 'quantity_returned')) {
            $returned = (float) $lines->sum(fn ($line): float => (float) ($line->quantity_returned ?? 0));
            if ($returned > 0.000001) {
                throw new \InvalidArgumentException('This purchase has purchase returns and cannot be edited or deleted. Delete the related returns first.');
            }
        }

        if (Schema::hasColumn('transactions', 'return_parent_id')) {
            $returnQuery = DB::table('transactions')
                ->where('business_id', (int) $purchase->business_id)
                ->where('type', 'purchase_return')
                ->where('return_parent_id', (int) $purchase->id);
            if (Schema::hasColumn('transactions', 'deleted_at')) {
                $returnQuery->whereNull('deleted_at');
            }
            if ($returnQuery->exists()) {
                throw new \InvalidArgumentException('This purchase has a related purchase return and cannot be edited or deleted. Delete the return first.');
            }
        }

        if ($lineIds !== [] && Schema::hasTable('transaction_sell_lines_purchase_lines')
            && Schema::hasColumn('transaction_sell_lines_purchase_lines', 'purchase_line_id')) {
            $allocationQuery = DB::table('transaction_sell_lines_purchase_lines')
                ->whereIn('purchase_line_id', $lineIds);
            if (Schema::hasColumn('transaction_sell_lines_purchase_lines', 'deleted_at')) {
                $allocationQuery->whereNull('deleted_at');
            }
            if ($allocationQuery->exists()) {
                throw new \InvalidArgumentException('Stock from this purchase has already been allocated or sold. The purchase cannot be edited or deleted.');
            }
        }
    }

    public function reverseReceivedStock(object $purchase, Collection $lines): void
    {
        if ((string) ($purchase->status ?? '') !== 'received') {
            return;
        }

        $locationId = (int) ($purchase->location_id ?? 0);
        $storeId = (int) ($purchase->store_id ?? 0);

        foreach ($lines as $line) {
            if (! (bool) ($line->enable_stock ?? true)) {
                continue;
            }

            $quantity = max(0, (float) ($line->quantity ?? 0))
                + max(0, (float) ($line->bonus_qty ?? 0));
            if ($quantity <= 0.000001) {
                continue;
            }

            if ($locationId > 0 && Schema::hasTable('variation_location_details')) {
                $this->decrementStock(
                    'variation_location_details',
                    ['variation_id' => (int) $line->variation_id, 'location_id' => $locationId],
                    $quantity,
                    'location'
                );
            }

            if ($storeId > 0 && Schema::hasTable('variation_store_details')) {
                $this->storeStock->adjustStoreStock(
                    $locationId,
                    (int) $line->product_id,
                    (int) $line->variation_id,
                    -$quantity,
                    $storeId,
                    null,
                    false,
                    (int) ($purchase->business_id ?? 0)
                );
            }
        }
    }

    /** @param array<string, int> $where */
    protected function decrementStock(string $table, array $where, float $quantity, string $scope): void
    {
        $query = DB::table($table);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
        $rows = $query->orderBy('id')->lockForUpdate()->get();
        if ($rows->isEmpty()) {
            throw new \InvalidArgumentException("The {$scope} stock record required to reverse this purchase is missing.");
        }

        $available = (float) $rows->sum(fn ($row): float => (float) ($row->qty_available ?? 0));
        if ($available + 0.000001 < $quantity) {
            throw new \InvalidArgumentException(sprintf(
                'This purchase cannot be changed because some purchased stock has already been used. Available %s stock: %.3f; required to reverse: %.3f.',
                $scope,
                $available,
                $quantity
            ));
        }

        $updates = $this->schema->filter($table, [
            'qty_available' => max(0, $available - $quantity),
            'updated_at' => now(),
        ]);
        DB::table($table)->where('id', $rows->first()->id)->update($updates);
        if ($rows->count() > 1) {
            DB::table($table)->whereIn('id', $rows->slice(1)->pluck('id')->all())->update(
                $this->schema->filter($table, ['qty_available' => 0, 'updated_at' => now()])
            );
        }
    }

    public function clearRelatedRecords(int $transactionId): void
    {
        $paymentIds = [];
        if (Schema::hasTable('transaction_payments')) {
            $paymentIds = DB::table('transaction_payments')
                ->where('transaction_id', $transactionId)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        if (Schema::hasTable('account_transactions')) {
            $hasTransactionId = Schema::hasColumn('account_transactions', 'transaction_id');
            $hasPaymentId = Schema::hasColumn('account_transactions', 'transaction_payment_id');
            if ($hasTransactionId || ($hasPaymentId && $paymentIds !== [])) {
                DB::table('account_transactions')->where(function ($query) use ($transactionId, $paymentIds, $hasTransactionId, $hasPaymentId): void {
                    if ($hasTransactionId) {
                        $query->where('transaction_id', $transactionId);
                    }
                    if ($hasPaymentId && $paymentIds !== []) {
                        $hasTransactionId
                            ? $query->orWhereIn('transaction_payment_id', $paymentIds)
                            : $query->whereIn('transaction_payment_id', $paymentIds);
                    }
                })->delete();
            }
        }

        if (Schema::hasTable('transaction_payments')) {
            DB::table('transaction_payments')->where('transaction_id', $transactionId)->delete();
        }
        if (Schema::hasTable('purchase_lines')) {
            DB::table('purchase_lines')->where('transaction_id', $transactionId)->delete();
        }
    }

    public function deleteTransaction(int $transactionId): void
    {
        if (Schema::hasColumn('transactions', 'deleted_at')) {
            DB::table('transactions')->where('id', $transactionId)->update($this->schema->filter('transactions', [
                'deleted_at' => now(),
                'updated_at' => now(),
            ]));

            return;
        }

        DB::table('transactions')->where('id', $transactionId)->delete();
    }

    public function removeDocument(?string $document): void
    {
        if (! $document) {
            return;
        }

        $path = public_path('uploads/documents/' . basename($document));
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

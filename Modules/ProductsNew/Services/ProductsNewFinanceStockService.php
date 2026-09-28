<?php

namespace Modules\ProductsNew\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\ProductsNew\Entities\ProductsNewInventoryMovement;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductsNewFinanceStockService
{
    public const MIRROR_REFERENCE_PREFIX = 'PNFG-';

    public function __construct(protected ProductsNewTenantGuard $guard)
    {
    }

    /**
     * Products New is a finished-goods product master.  All stock values created
     * by this module must therefore use the business Finished Goods Account,
     * irrespective of product category (including Fuel).
     */
    public function finishedGoodsAccountId(?int $businessId = null): int
    {
        $businessId = $businessId ?: $this->guard->businessId();

        if (! Schema::hasTable('accounts') || ! Schema::hasColumn('accounts', 'id')) {
            throw ValidationException::withMessages([
                'stock_type' => 'The Finance accounts table is missing. Finished Goods Account cannot be resolved.',
            ]);
        }

        $query = DB::table('accounts')
            ->where('business_id', $businessId)
            ->where('name', 'Finished Goods Account');

        if (Schema::hasColumn('accounts', 'is_closed')) {
            $query->where(function ($where): void {
                $where->whereNull('is_closed')->orWhere('is_closed', 0);
            });
        }

        if (Schema::hasColumn('accounts', 'location_id')) {
            $query->orderByRaw("CASE WHEN COALESCE(location_id, 'all') = 'all' THEN 0 ELSE 1 END");
        }
        if (Schema::hasColumn('accounts', 'is_main_account')) {
            $query->orderByDesc('is_main_account');
        }

        $accountId = $query->orderBy('id')->value('id');

        if (empty($accountId)) {
            throw ValidationException::withMessages([
                'stock_type' => 'Finished Goods Account is not configured for this business in Finance / List Accounts.',
            ]);
        }

        return (int) $accountId;
    }

    protected function openingBalanceEquityAccountId(int $businessId): int
    {
        $accountId = DB::table('accounts')
            ->where('business_id', $businessId)
            ->where('name', 'Opening Balance Equity Account')
            ->when(Schema::hasColumn('accounts', 'is_closed'), function ($query): void {
                $query->where(function ($where): void {
                    $where->whereNull('is_closed')->orWhere('is_closed', 0);
                });
            })
            ->when(Schema::hasColumn('accounts', 'location_id'), fn ($query) => $query->orderByRaw("CASE WHEN COALESCE(location_id, 'all') = 'all' THEN 0 ELSE 1 END"))
            ->when(Schema::hasColumn('accounts', 'is_main_account'), fn ($query) => $query->orderByDesc('is_main_account'))
            ->orderBy('id')
            ->value('id');

        if (empty($accountId)) {
            throw ValidationException::withMessages([
                'opening_stock' => 'Opening Balance Equity Account is not configured for this business in Finance / List Accounts.',
            ]);
        }

        return (int) $accountId;
    }

    /**
     * Mirror Products New opening-stock movements into the standard Finance
     * ledger.  Quantity remains owned by Products New / variation stock tables;
     * this method creates only the accounting representation used by Finance.
     *
     * The value is ALWAYS quantity x inclusive purchase cost.
     */
    public function mirrorOpeningStockMovement(ProductsNewInventoryMovement $movement, array $sourceData = []): void
    {
        $movementType = (string) $movement->movement_type;
        if (! in_array($movementType, [
            'opening_stock',
            'opening_stock_adjustment_in',
            'opening_stock_adjustment_out',
        ], true)) {
            return;
        }

        $qty = abs((float) $movement->qty);
        $inclusiveCost = max(0, (float) $movement->unit_cost);
        $amount = round($qty * $inclusiveCost, 6);

        // A zero-cost opening quantity changes stock but has no financial value.
        if ($qty <= 0 || $amount <= 0) {
            return;
        }

        foreach (['transactions', 'purchase_lines', 'account_transactions'] as $table) {
            if (! Schema::hasTable($table)) {
                throw ValidationException::withMessages([
                    'opening_stock' => 'The standard Finance stock tables are incomplete (' . $table . ' is missing). Opening stock was not saved.',
                ]);
            }
        }

        $businessId = $this->guard->businessId();
        $accountId = $this->finishedGoodsAccountId($businessId);
        $userId = (int) ($this->guard->userId() ?: 0);
        $locationId = ! empty($movement->location_id) ? (int) $movement->location_id : null;
        $productId = (int) $movement->product_id;
        $variationId = ! empty($movement->variation_id) ? (int) $movement->variation_id : null;
        $operationDate = $movement->movement_date ?: now();
        $reference = self::MIRROR_REFERENCE_PREFIX . 'M' . (int) $movement->id;
        $isOut = $movementType === 'opening_stock_adjustment_out';

        $product = Schema::hasTable('products')
            ? DB::table('products')->where('id', $productId)->first()
            : null;
        $variation = ($variationId && Schema::hasTable('variations'))
            ? DB::table('variations')->where('id', $variationId)->first()
            : null;

        $exclusiveCost = isset($variation->default_purchase_price)
            ? (float) $variation->default_purchase_price
            : $inclusiveCost;
        if ($exclusiveCost <= 0) {
            $exclusiveCost = $inclusiveCost;
        }
        $itemTax = max(0, $inclusiveCost - $exclusiveCost);
        $taxId = isset($product->tax) && $product->tax !== null ? (int) $product->tax : null;

        $transactionId = DB::table('transactions')
            ->where('business_id', $businessId)
            ->when(Schema::hasColumn('transactions', 'ref_no'), fn ($q) => $q->where('ref_no', $reference))
            ->when(! Schema::hasColumn('transactions', 'ref_no'), fn ($q) => $q->where('opening_stock_product_id', $productId)->where('transaction_date', $operationDate))
            ->value('id');

        $transactionPayload = $this->schemaPayload('transactions', [
            'business_id' => $businessId,
            'location_id' => $locationId,
            'store_id' => $sourceData['store_id'] ?? null,
            'type' => 'opening_stock',
            'status' => 'received',
            'payment_status' => 'paid',
            'ref_no' => $reference,
            'transaction_date' => $operationDate,
            'total_before_tax' => $amount,
            'tax_amount' => 0,
            'final_total' => $amount,
            'opening_stock_product_id' => $productId,
            'created_by' => $userId,
            'imported' => 1,
            'additional_notes' => 'Products New Finished Goods finance mirror. Source movement #' . $movement->id,
            'transaction_note' => 'Products New Finished Goods finance mirror. Source movement #' . $movement->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($transactionId) {
            $update = $transactionPayload;
            unset($update['created_at']);
            DB::table('transactions')->where('id', $transactionId)->update($update);
            $transactionId = (int) $transactionId;
        } else {
            $transactionId = (int) DB::table('transactions')->insertGetId($transactionPayload);
        }

        $lineQuery = DB::table('purchase_lines')
            ->where('transaction_id', $transactionId)
            ->where('product_id', $productId);
        if ($variationId && Schema::hasColumn('purchase_lines', 'variation_id')) {
            $lineQuery->where('variation_id', $variationId);
        }
        $purchaseLineId = $lineQuery->value('id');

        $signedQty = $isOut ? -$qty : $qty;
        $purchaseLinePayload = $this->schemaPayload('purchase_lines', [
            'transaction_id' => $transactionId,
            'product_id' => $productId,
            'variation_id' => $variationId,
            'quantity' => $signedQty,
            'bonus_qty' => 0,
            'pp_without_discount' => $exclusiveCost,
            'discount_amount' => 0,
            'discount_percent' => 0,
            'purchase_price' => $exclusiveCost,
            'purchase_price_inc_tax' => $inclusiveCost,
            'item_tax' => $itemTax,
            'tax_id' => $taxId,
            'quantity_sold' => 0,
            'quantity_adjusted' => 0,
            'quantity_returned' => 0,
            'mfg_quantity_used' => 0,
            'secondary_unit_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($purchaseLineId) {
            $update = $purchaseLinePayload;
            unset($update['created_at']);
            DB::table('purchase_lines')->where('id', $purchaseLineId)->update($update);
            $purchaseLineId = (int) $purchaseLineId;
        } else {
            $purchaseLineId = (int) DB::table('purchase_lines')->insertGetId($purchaseLinePayload);
        }

        $type = $isOut ? 'credit' : 'debit';
        $accountTransactionQuery = DB::table('account_transactions')
            ->where('transaction_id', $transactionId)
            ->where('account_id', $accountId)
            ->where('type', $type);

        if (Schema::hasColumn('account_transactions', 'purchase_line_id')) {
            $accountTransactionQuery->where('purchase_line_id', $purchaseLineId);
        }

        $accountTransactionId = $accountTransactionQuery->value('id');
        $note = 'Products New opening stock | Product: '
            . (string) ($product->name ?? ('#' . $productId))
            . ' | Inclusive cost: ' . number_format($inclusiveCost, 4, '.', '')
            . ' | Ref: ' . $reference;

        $accountTransactionPayload = $this->schemaPayload('account_transactions', [
            'account_id' => $accountId,
            'business_id' => $businessId,
            'type' => $type,
            'amount' => $amount,
            'operation_date' => $operationDate,
            'created_by' => $userId,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => null,
            'purchase_line_id' => $purchaseLineId,
            'note' => $note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($accountTransactionId) {
            $update = $accountTransactionPayload;
            unset($update['created_at']);
            DB::table('account_transactions')->where('id', $accountTransactionId)->update($update);
        } else {
            DB::table('account_transactions')->insert($accountTransactionPayload);
        }

        // Keep the Finance ledger balanced exactly like the core Opening Stock
        // workflow: Finished Goods is offset by Opening Balance Equity.
        $equityAccountId = $this->openingBalanceEquityAccountId($businessId);
        $equityType = $isOut ? 'debit' : 'credit';
        $equityQuery = DB::table('account_transactions')
            ->where('transaction_id', $transactionId)
            ->where('account_id', $equityAccountId)
            ->where('type', $equityType);
        $equityId = $equityQuery->value('id');
        $equityPayload = $this->schemaPayload('account_transactions', [
            'account_id' => $equityAccountId,
            'business_id' => $businessId,
            'type' => $equityType,
            'amount' => $amount,
            'operation_date' => $operationDate,
            'created_by' => $userId,
            'transaction_id' => $transactionId,
            'transaction_payment_id' => null,
            'note' => 'Products New opening stock counter-entry | Ref: ' . $reference,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($equityId) {
            $update = $equityPayload;
            unset($update['created_at']);
            DB::table('account_transactions')->where('id', $equityId)->update($update);
        } else {
            DB::table('account_transactions')->insert($equityPayload);
        }
    }

    private function schemaPayload(string $table, array $payload): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_filter(
            $payload,
            fn (string $key): bool => isset($columns[$key]),
            ARRAY_FILTER_USE_KEY
        );
    }
}

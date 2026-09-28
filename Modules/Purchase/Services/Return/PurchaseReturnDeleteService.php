<?php

namespace Modules\Purchase\Services\Return;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\StoreStockIntegrityService;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseSchemaUtil;

class PurchaseReturnDeleteService
{
    public function __construct(
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseSchemaUtil $schema,
        protected StoreStockIntegrityService $storeStock
    ) {
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $return = DB::table('transactions')
                ->where('business_id', $this->numbers->businessId())
                ->where('type', 'purchase_return')
                ->where('id', $id)
                ->lockForUpdate()
                ->first();
            if (! $return) {
                throw new \InvalidArgumentException('Purchase return not found.');
            }

            $lines = Schema::hasTable('purchase_lines')
                ? DB::table('purchase_lines')->where('transaction_id', $id)->lockForUpdate()->get()
                : collect();

            foreach ($lines as $line) {
                $quantity = Schema::hasColumn('purchase_lines', 'quantity_returned')
                    ? (float) ($line->quantity_returned ?? 0)
                    : (float) ($line->quantity ?? 0);
                if ($quantity <= 0) {
                    continue;
                }

                $this->restoreStock(
                    (int) $line->product_id,
                    (int) $line->variation_id,
                    (int) $return->location_id,
                    (int) ($return->store_id ?? 0),
                    $quantity
                );

                if (Schema::hasColumn('purchase_lines', 'quantity_returned') && ! empty($return->return_parent_id)) {
                    $originalLineId = null;
                    foreach (['parent_purchase_line_id', 'purchase_line_id'] as $column) {
                        if (Schema::hasColumn('purchase_lines', $column) && ! empty($line->{$column})) {
                            $originalLineId = (int) $line->{$column};
                            break;
                        }
                    }
                    if (! $originalLineId) {
                        $originalLineId = (int) DB::table('purchase_lines')
                            ->where('transaction_id', $return->return_parent_id)
                            ->where('product_id', $line->product_id)
                            ->where('variation_id', $line->variation_id)
                            ->orderBy('id')
                            ->value('id');
                    }
                    if ($originalLineId) {
                        $original = DB::table('purchase_lines')->where('id', $originalLineId)->lockForUpdate()->first();
                        if ($original) {
                            DB::table('purchase_lines')->where('id', $originalLineId)->update([
                                'quantity_returned' => max(0, (float) ($original->quantity_returned ?? 0) - $quantity),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            if (Schema::hasTable('account_transactions')) {
                DB::table('account_transactions')->where('transaction_id', $id)->delete();
            }
            if (Schema::hasTable('transaction_payments')) {
                DB::table('transaction_payments')->where('transaction_id', $id)->delete();
            }
            if (Schema::hasTable('purchase_lines')) {
                DB::table('purchase_lines')->where('transaction_id', $id)->delete();
            }
            DB::table('transactions')->where('id', $id)->delete();
        }, 3);
    }

    protected function restoreStock(int $productId, int $variationId, int $locationId, int $storeId, float $quantity): void
    {
        $enableStock = Schema::hasTable('products')
            ? (bool) DB::table('products')->where('id', $productId)->value('enable_stock')
            : true;
        if (! $enableStock || $quantity <= 0) {
            return;
        }

        if (Schema::hasTable('variation_location_details')) {
            $rows = DB::table('variation_location_details')
                ->where('variation_id', $variationId)
                ->where('location_id', $locationId)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            if ($rows->isNotEmpty()) {
                $current = (float) $rows->sum(fn ($row): float => (float) ($row->qty_available ?? 0));
                DB::table('variation_location_details')->where('id', $rows->first()->id)->update($this->schema->filter('variation_location_details', [
                    'qty_available' => $current + $quantity,
                    'updated_at' => now(),
                ]));
                if ($rows->count() > 1) {
                    DB::table('variation_location_details')->whereIn('id', $rows->slice(1)->pluck('id')->all())->update(
                        $this->schema->filter('variation_location_details', ['qty_available' => 0, 'updated_at' => now()])
                    );
                }
            } else {
                DB::table('variation_location_details')->insert($this->schema->filter('variation_location_details', [
                    'product_id' => $productId,
                    'variation_id' => $variationId,
                    'location_id' => $locationId,
                    'qty_available' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        if ($storeId > 0 && Schema::hasTable('variation_store_details')) {
            $this->storeStock->adjustStoreStock(
                $locationId,
                $productId,
                $variationId,
                $quantity,
                $storeId,
                null,
                true,
                $this->numbers->businessId()
            );
        }
    }
}

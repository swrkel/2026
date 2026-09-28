<?php

namespace Modules\Purchase\Services\Entry;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\StoreStockIntegrityService;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;
use Modules\Purchase\Utils\PurchaseSchemaUtil;
use Modules\Purchase\Utils\PurchaseUnitUtil;

class PurchaseEntryStockService
{
    public function __construct(
        protected PurchaseDateNumberUtil $numbers,
        protected PurchaseSchemaUtil $schema,
        protected PurchaseUnitUtil $units,
        protected StoreStockIntegrityService $storeStock
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $submittedLines
     * @return array{lines: array<int, array<string, mixed>>, subtotal: float, line_tax: float, line_total: float, free_product_total: float}
     */
    public function prepareLines(array $submittedLines, float $exchangeRate = 1.0): array
    {
        $businessId = $this->numbers->businessId();
        $exchangeRate = max(0.000001, $exchangeRate);
        $taxRates = Schema::hasTable('tax_rates')
            ? DB::table('tax_rates')->where('business_id', $businessId)->pluck('amount', 'id')
            : collect();

        $lines = [];
        $subtotal = 0.0;
        $lineTaxTotal = 0.0;
        $lineTotal = 0.0;
        $freeProductTotal = 0.0;

        foreach ($submittedLines as $index => $submitted) {
            $variationId = (int) ($submitted['variation_id'] ?? 0);
            $productId = (int) ($submitted['product_id'] ?? 0);

            $variationQuery = DB::table('variations as v')
                ->join('products as p', 'p.id', '=', 'v.product_id')
                ->where('p.business_id', $businessId)
                ->where('p.id', $productId)
                ->where('v.id', $variationId);
            if (Schema::hasColumn('variations', 'deleted_at')) {
                $variationQuery->whereNull('v.deleted_at');
            }
            $variationColumns = ['v.id', 'v.product_id', 'v.product_variation_id', 'p.unit_id'];
            $variationColumns[] = Schema::hasColumn('products', 'sub_unit_ids')
                ? 'p.sub_unit_ids'
                : DB::raw("'' as sub_unit_ids");
            $variationColumns[] = Schema::hasColumn('products', 'enable_stock')
                ? 'p.enable_stock'
                : DB::raw('1 as enable_stock');
            $variation = $variationQuery->first($variationColumns);

            if (! $variation) {
                throw new \InvalidArgumentException('Product row ' . ($index + 1) . ' is not valid for this business.');
            }

            $unit = $this->units->resolve(
                $businessId,
                (int) ($variation->unit_id ?? 0),
                ! empty($submitted['sub_unit_id']) ? (int) $submitted['sub_unit_id'] : null,
                $variation->sub_unit_ids ?? null
            );
            $multiplier = $unit['multiplier'];

            $enteredQuantity = $this->numbers->number($submitted['quantity'] ?? 0);
            $enteredFreeQty = max(0, $this->numbers->number($submitted['free_qty'] ?? 0));
            // CH1 IS2115: the UI displays purchase prices at four decimals,
            // while calculations may legitimately contain more precision (for
            // example 342.0932 + 18% = 403.669976). Prefer the hidden precise
            // value when present so saving the entry cannot turn a browser total
            // such as 2,466,222.07 into 2,466,222.00.
            $enteredBaseCost = max(0, $this->numbers->number(
                $submitted['pp_without_discount_precise'] ?? $submitted['pp_without_discount'] ?? 0
            ));
            $discountType = (string) ($submitted['discount_type'] ?? 'percentage');
            $enteredDiscountValue = max(0, $this->numbers->number($submitted['discount_value'] ?? $submitted['discount_percent'] ?? 0));

            if ($enteredQuantity <= 0) {
                throw new \InvalidArgumentException('Product row ' . ($index + 1) . ' must have a quantity greater than zero.');
            }
            if (! $unit['allow_decimal'] && abs($enteredQuantity - round($enteredQuantity)) > 0.000001) {
                throw new \InvalidArgumentException('Product row ' . ($index + 1) . ' does not allow decimal quantities for the selected unit.');
            }

            $enteredDiscountPerUnit = $discountType === 'fixed'
                ? min($enteredBaseCost, $enteredDiscountValue)
                : min($enteredBaseCost, $enteredBaseCost * min(100, $enteredDiscountValue) / 100);
            $enteredPurchasePrice = max(0, $enteredBaseCost - $enteredDiscountPerUnit);

            $taxId = ! empty($submitted['purchase_line_tax_id']) ? (int) $submitted['purchase_line_tax_id'] : null;
            $taxRate = $taxId ? (float) ($taxRates[$taxId] ?? 0) : 0.0;
            $calculatedEnteredIncTax = $enteredPurchasePrice + ($enteredPurchasePrice * $taxRate / 100);
            $submittedEnteredIncTax = $this->numbers->number(
                $submitted['purchase_price_inc_tax_precise'] ?? $submitted['purchase_price_inc_tax'] ?? 0
            );
            $enteredIncTax = $submittedEnteredIncTax > 0 ? max($enteredPurchasePrice, $submittedEnteredIncTax) : $calculatedEnteredIncTax;

            // Persist all quantities and purchase costs in the product's base unit and business currency.
            $quantity = $enteredQuantity * $multiplier;
            $freeQty = $enteredFreeQty * $multiplier;
            $baseCost = ($enteredBaseCost * $exchangeRate) / $multiplier;
            $discountPerUnit = ($enteredDiscountPerUnit * $exchangeRate) / $multiplier;
            $purchasePrice = ($enteredPurchasePrice * $exchangeRate) / $multiplier;
            $purchasePriceIncTax = ($enteredIncTax * $exchangeRate) / $multiplier;
            $itemTax = max(0, $purchasePriceIncTax - $purchasePrice);

            $lineBeforeTax = round($quantity * $purchasePrice, 6);
            $lineTax = round($quantity * $itemTax, 6);
            $total = round($quantity * $purchasePriceIncTax, 6);
            $freeValue = round($freeQty * $purchasePriceIncTax, 6);

            $subtotal += $lineBeforeTax;
            $lineTaxTotal += $lineTax;
            $lineTotal += $total;
            $freeProductTotal += $freeValue;

            $sellingEntered = max(0, $this->numbers->number($submitted['default_sell_price'] ?? 0));
            $sellingBase = $sellingEntered / $multiplier;

            $lines[] = [
                'product_id' => $productId,
                'variation_id' => $variationId,
                'product_variation_id' => (int) ($variation->product_variation_id ?? 0),
                'product_unit_id' => (int) ($variation->unit_id ?? 0),
                'sub_unit_id' => $unit['unit_id'] !== (int) ($variation->unit_id ?? 0) ? $unit['unit_id'] : null,
                'unit_multiplier' => $multiplier,
                'enable_stock' => (bool) $variation->enable_stock,
                'quantity' => $quantity,
                'free_qty' => $freeQty,
                'stock_quantity' => $quantity + $freeQty,
                'pp_without_discount' => $baseCost,
                'discount_type' => $discountType,
                'discount_value' => $enteredDiscountValue,
                'discount_percent' => $enteredBaseCost > 0 ? ($enteredDiscountPerUnit / $enteredBaseCost) * 100 : 0,
                'discount_amount' => $discountPerUnit,
                'purchase_price' => $purchasePrice,
                'purchase_price_inc_tax' => $purchasePriceIncTax,
                'item_tax' => $itemTax,
                'tax_id' => $taxId,
                'line_before_tax' => $lineBeforeTax,
                'line_tax' => $lineTax,
                'line_total' => $total,
                'free_product_total' => $freeValue,
                'profit_percent' => $this->numbers->number($submitted['profit_percent'] ?? 0),
                'default_sell_price' => $sellingBase,
                'lot_number' => trim((string) ($submitted['lot_number'] ?? '')) ?: null,
                'mfg_date' => $this->numbers->date($submitted['mfg_date'] ?? null),
                'exp_date' => $this->numbers->date($submitted['exp_date'] ?? null),
            ];
        }

        return [
            'lines' => $lines,
            'subtotal' => round($subtotal, 6),
            'line_tax' => round($lineTaxTotal, 6),
            'line_total' => round($lineTotal, 6),
            'free_product_total' => round($freeProductTotal, 6),
        ];
    }

    /** @param array<int, array<string, mixed>> $lines */
    public function saveLines(int $transactionId, array $lines, int $locationId, int $storeId, string $status): void
    {
        foreach ($lines as $line) {
            $payload = $this->schema->filter('purchase_lines', [
                'transaction_id' => $transactionId,
                'product_id' => $line['product_id'],
                'variation_id' => $line['variation_id'],
                'quantity' => $line['quantity'],
                'bonus_qty' => $line['free_qty'],
                'pp_without_discount' => $line['pp_without_discount'],
                'discount_percent' => $line['discount_percent'],
                'purchase_price' => $line['purchase_price'],
                'purchase_price_inc_tax' => $line['purchase_price_inc_tax'],
                'item_tax' => $line['item_tax'],
                'tax_id' => $line['tax_id'],
                'mfg_date' => $line['mfg_date'],
                'exp_date' => $line['exp_date'],
                'lot_number' => $line['lot_number'],
                'sub_unit_id' => $line['sub_unit_id'],
                'secondary_unit_quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('purchase_lines')->insert($payload);
            $this->updateVariationPrices($line);

            if ($status === 'received' && $line['enable_stock']) {
                $this->incrementLocationStock($line, $locationId);
                $this->incrementStoreStock($line, $locationId, $storeId);
            }
        }
    }

    /** @param array<string, mixed> $line */
    protected function updateVariationPrices(array $line): void
    {
        $updates = $this->schema->filter('variations', [
            'default_purchase_price' => $line['purchase_price'],
            'dpp_inc_tax' => $line['purchase_price_inc_tax'],
            'profit_percent' => $line['profit_percent'],
            // IS2112: a purchase updates purchase cost only. Selling price is a
            // product-master value and must not be changed by Add/Edit Purchase.
            'updated_at' => now(),
        ]);

        if ($updates !== []) {
            DB::table('variations')->where('id', $line['variation_id'])->update($updates);
        }
    }

    /** @param array<string, mixed> $line */
    protected function incrementLocationStock(array $line, int $locationId): void
    {
        if (! Schema::hasTable('variation_location_details')) {
            return;
        }

        $rows = DB::table('variation_location_details')
            ->where('variation_id', $line['variation_id'])
            ->where('location_id', $locationId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($rows->isNotEmpty()) {
            $current = (float) $rows->sum(fn ($row): float => (float) ($row->qty_available ?? 0));
            DB::table('variation_location_details')->where('id', $rows->first()->id)->update($this->schema->filter('variation_location_details', [
                'qty_available' => $current + $line['stock_quantity'],
                'updated_at' => now(),
            ]));
            if ($rows->count() > 1) {
                DB::table('variation_location_details')->whereIn('id', $rows->slice(1)->pluck('id')->all())->update(
                    $this->schema->filter('variation_location_details', ['qty_available' => 0, 'updated_at' => now()])
                );
            }
            return;
        }

        DB::table('variation_location_details')->insert($this->schema->filter('variation_location_details', [
            'product_id' => $line['product_id'],
            'product_variation_id' => $line['product_variation_id'],
            'variation_id' => $line['variation_id'],
            'location_id' => $locationId,
            'qty_available' => $line['stock_quantity'],
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    /** @param array<string, mixed> $line */
    protected function incrementStoreStock(array $line, int $locationId, int $storeId): void
    {
        if ($storeId <= 0 || ! Schema::hasTable('variation_store_details')) {
            return;
        }

        $this->storeStock->adjustStoreStock(
            $locationId,
            (int) $line['product_id'],
            (int) $line['variation_id'],
            (float) $line['stock_quantity'],
            $storeId,
            (int) ($line['product_variation_id'] ?? 0),
            true,
            $this->numbers->businessId()
        );
    }
}

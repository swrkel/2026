<?php

namespace Modules\ProductsNew\Services;

use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductActionService
{
    public function __construct(
        protected ProductsNewTenantGuard $guard,
        protected ProductStatusService $status
    ) {
    }

    public function setActive(ProductsNewProduct $product, bool $active, ?string $note = null): void
    {
        $this->status->setActive($product, $active, $note);
    }

    /**
     * Return operational references that make permanent deletion unsafe.
     * Metadata and audit-only rows are deliberately excluded because those can
     * be cleaned together with an unused product.
     */
    public function deletionBlockers(ProductsNewProduct $product): array
    {
        $this->assertBusiness($product);
        $productId = (int) $product->id;
        $businessId = $this->guard->businessId();
        $blockers = [];

        $purchaseCount = $this->transactionLineCount('purchase_lines', $productId, $businessId);
        if ($purchaseCount > 0) {
            $blockers[] = $purchaseCount . ' purchase, opening-stock or transfer line(s)';
        }

        $saleCount = $this->transactionLineCount('transaction_sell_lines', $productId, $businessId);
        if ($saleCount > 0) {
            $blockers[] = $saleCount . ' sales line(s)';
        }

        $adjustmentCount = $this->productRowCount('stock_adjustment_lines', $productId, $businessId);
        if ($adjustmentCount > 0) {
            $blockers[] = $adjustmentCount . ' stock-adjustment line(s)';
        }

        $moduleTransactions = [
            'products_new_inventory_movements' => 'Products New inventory movement(s)',
            'products_new_batches' => 'batch / lot record(s)',
            'products_new_serial_numbers' => 'serial-number record(s)',
            'products_new_serial_movements' => 'serial movement(s)',
            'products_new_warranty_registrations' => 'warranty registration(s)',
            'products_new_warranty_claims' => 'warranty claim(s)',
            'products_new_recalls' => 'recall record(s)',
        ];

        foreach ($moduleTransactions as $table => $label) {
            $count = $this->productRowCount($table, $productId, $businessId);
            if ($count > 0) {
                $blockers[] = $count . ' ' . $label;
            }
        }

        $variationIds = $this->variationIds($productId);
        if ($variationIds !== []) {
            $ingredientCount = $this->variationRowCount(
                'mfg_recipe_ingredients',
                'variation_id',
                $variationIds
            );
            if ($ingredientCount > 0) {
                $blockers[] = $ingredientCount . ' manufacturing recipe ingredient reference(s)';
            }
        }

        $stock = $this->currentStock($productId);
        if (abs($stock) > 0.0005) {
            $blockers[] = 'current stock balance of ' . number_format($stock, 3);
        }

        return array_values(array_unique($blockers));
    }

    public function delete(ProductsNewProduct $product): void
    {
        $blockers = $this->deletionBlockers($product);

        if ($blockers !== []) {
            throw new DomainException(
                'This product cannot be deleted because it is already used: ' . implode('; ', $blockers)
                . '. Deactivate the product instead to preserve transaction history.'
            );
        }

        $productId = (int) $product->id;
        $variationIds = $this->variationIds($productId);

        DB::transaction(function () use ($productId, $variationIds): void {
            // Module-owned non-transactional details.
            foreach ([
                'products_new_product_meta',
                'products_new_timeline',
                'products_new_status_transitions',
                'products_new_product_notes',
                'products_new_media',
                'products_new_availability_snapshots',
                'products_new_command_center_snapshots',
                'products_new_duplicate_reviews',
                'products_new_price_history',
                'products_new_price_tiers',
                'products_new_cost_snapshots',
                'products_new_product_classifications',
                'products_new_inventory_planning_profiles',
                'products_new_stock_intelligence_snapshots',
                'products_new_barcode_queue',
                'products_new_expiry_alerts',
            ] as $table) {
                $this->deleteByProduct($table, $productId);
            }

            $this->deleteProductRelationships($productId);

            if ($variationIds !== []) {
                foreach ([
                    ['variation_group_prices', 'variation_id'],
                    ['product_racks', 'variation_id'],
                    ['combo_product_details', 'variation_id'],
                ] as [$table, $column]) {
                    $this->deleteByValues($table, $column, $variationIds);
                }
            }

            $this->deleteModifierSetLinks($productId);

            $this->deleteByProduct('variation_store_details', $productId, $variationIds);
            $this->deleteByProduct('variation_location_details', $productId, $variationIds);
            $this->deleteByProduct('product_locations', $productId);

            if ($variationIds !== []) {
                $this->deleteByValues('variations', 'id', $variationIds);
            }
            $this->deleteByProduct('product_variations', $productId);

            $productDelete = DB::table('products')->where('id', $productId);
            if (Schema::hasColumn('products', 'business_id')) {
                $productDelete->where('business_id', $this->guard->businessId());
            }
            $productDelete->delete();
        });
    }

    /**
     * MA-002 (IS-1919 #4): has this product been used in any transaction?
     *
     * Certain fields must stop being editable once a product has moved -
     * changing a unit or a stock account after stock has been bought and sold
     * silently rewrites what those existing lines mean, and the ledger no
     * longer reconciles.
     *
     * Both line tables are checked, and transactionLineCount() already guards
     * every table and column it touches, so this returns false rather than
     * throwing on an installation where one of them is absent.
     */
    public function isUsedInTransactions(int $productId, int $businessId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        if ($this->transactionLineCount('purchase_lines', $productId, $businessId) > 0) {
            return true;
        }

        return $this->transactionLineCount('transaction_sell_lines', $productId, $businessId) > 0;
    }

    private function transactionLineCount(string $lineTable, int $productId, int $businessId): int
    {
        if (! Schema::hasTable($lineTable)
            || ! Schema::hasColumn($lineTable, 'product_id')) {
            return 0;
        }

        $query = DB::table($lineTable . ' as pn_line')->where('pn_line.product_id', $productId);

        if (Schema::hasColumn($lineTable, 'transaction_id')
            && Schema::hasTable('transactions')
            && Schema::hasColumn('transactions', 'id')) {
            $query->join('transactions as pn_tx', 'pn_tx.id', '=', 'pn_line.transaction_id');
            if (Schema::hasColumn('transactions', 'business_id')) {
                $query->where('pn_tx.business_id', $businessId);
            }
        }

        return (int) $query->count();
    }

    private function productRowCount(string $table, int $productId, int $businessId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'product_id')) {
            return 0;
        }

        $query = DB::table($table)->where('product_id', $productId);
        if (Schema::hasColumn($table, 'business_id')) {
            $query->where('business_id', $businessId);
        }

        return (int) $query->count();
    }

    private function variationRowCount(string $table, string $column, array $variationIds): int
    {
        if ($variationIds === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return (int) DB::table($table)->whereIn($column, $variationIds)->count();
    }

    private function currentStock(int $productId): float
    {
        $stock = 0.0;

        if (Schema::hasTable('variation_location_details')
            && Schema::hasColumn('variation_location_details', 'qty_available')) {
            $query = DB::table('variation_location_details');
            if (Schema::hasColumn('variation_location_details', 'product_id')) {
                $query->where('product_id', $productId);
            } elseif (Schema::hasColumn('variation_location_details', 'variation_id')) {
                $variationIds = $this->variationIds($productId);
                if ($variationIds === []) {
                    return 0.0;
                }
                $query->whereIn('variation_id', $variationIds);
            } else {
                return 0.0;
            }
            $stock += (float) $query->sum('qty_available');
        }

        if (Schema::hasTable('variation_store_details')
            && Schema::hasColumn('variation_store_details', 'qty_available')) {
            $query = DB::table('variation_store_details');
            if (Schema::hasColumn('variation_store_details', 'product_id')) {
                $query->where('product_id', $productId);
            } elseif (Schema::hasColumn('variation_store_details', 'variation_id')) {
                $variationIds = $this->variationIds($productId);
                if ($variationIds === []) {
                    return round($stock, 3);
                }
                $query->whereIn('variation_id', $variationIds);
            } else {
                return round($stock, 3);
            }
            // Store balances are a subdivision of location stock in this ERP,
            // so they are used only when location balances are unavailable.
            if (! Schema::hasTable('variation_location_details')) {
                $stock += (float) $query->sum('qty_available');
            }
        }

        return round($stock, 3);
    }

    private function variationIds(int $productId): array
    {
        if (! Schema::hasTable('variations') || ! Schema::hasColumn('variations', 'product_id')) {
            return [];
        }

        return DB::table('variations')
            ->where('product_id', $productId)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    private function deleteByProduct(string $table, int $productId, array $variationIds = []): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        if (Schema::hasColumn($table, 'product_id')) {
            DB::table($table)->where('product_id', $productId)->delete();
            return;
        }

        if ($variationIds !== [] && Schema::hasColumn($table, 'variation_id')) {
            DB::table($table)->whereIn('variation_id', $variationIds)->delete();
        }
    }

    private function deleteProductRelationships(int $productId): void
    {
        $table = 'products_new_product_relationships';

        if (! Schema::hasTable($table)) {
            return;
        }

        $hasProduct = Schema::hasColumn($table, 'product_id');
        $hasRelatedProduct = Schema::hasColumn($table, 'related_product_id');

        // Never execute an unqualified DELETE when a tenant has a partial or
        // older table structure.
        if (! $hasProduct && ! $hasRelatedProduct) {
            return;
        }

        DB::table($table)
            ->where(function ($query) use ($productId, $hasProduct, $hasRelatedProduct): void {
                if ($hasProduct) {
                    $query->where('product_id', $productId);
                }
                if ($hasRelatedProduct) {
                    $hasProduct
                        ? $query->orWhere('related_product_id', $productId)
                        : $query->where('related_product_id', $productId);
                }
            })
            ->delete();
    }

    private function deleteModifierSetLinks(int $productId): void
    {
        $table = 'res_product_modifier_sets';

        if (! Schema::hasTable($table)) {
            return;
        }

        $hasProduct = Schema::hasColumn($table, 'product_id');
        $hasModifierProduct = Schema::hasColumn($table, 'modifier_set_id');

        if (! $hasProduct && ! $hasModifierProduct) {
            return;
        }

        DB::table($table)
            ->where(function ($query) use ($productId, $hasProduct, $hasModifierProduct): void {
                if ($hasProduct) {
                    $query->where('product_id', $productId);
                }
                if ($hasModifierProduct) {
                    $hasProduct
                        ? $query->orWhere('modifier_set_id', $productId)
                        : $query->where('modifier_set_id', $productId);
                }
            })
            ->delete();
    }

    private function deleteByValues(string $table, string $column, array $values): void
    {
        if ($values === [] || ! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)->whereIn($column, $values)->delete();
    }

    private function assertBusiness(ProductsNewProduct $product): void
    {
        if (Schema::hasColumn('products', 'business_id')
            && (int) $product->business_id !== $this->guard->businessId()) {
            abort(404);
        }
    }
}

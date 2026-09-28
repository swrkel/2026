<?php

namespace Modules\ProductsNew\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductQueryService
{
    /** @var array<string,array<int,string>> */
    private array $columnCache = [];

    public function __construct(protected ProductsNewTenantGuard $guard)
    {
    }

    public function baseQuery(array $filters = [], bool $includePurchasePrice = false): Builder
    {
        $productColumns = $this->columns('products');
        $query = DB::table('products as p');

        $hasCategory = $this->canJoin('categories', ['id', 'name'])
            && in_array('category_id', $productColumns, true);
        $hasSubCategory = $this->canJoin('categories', ['id', 'name'])
            && in_array('sub_category_id', $productColumns, true);
        $hasBrand = $this->canJoin('brands', ['id', 'name'])
            && in_array('brand_id', $productColumns, true);
        $hasUnit = $this->canJoin('units', ['id', 'short_name'])
            && in_array('unit_id', $productColumns, true);
        $hasTax = $this->canJoin('tax_rates', ['id', 'name'])
            && in_array('tax', $productColumns, true);
        $metaColumns = $this->columns('products_new_product_meta');
        $hasMeta = ! empty($metaColumns) && in_array('product_id', $metaColumns, true);
        $priceSummary = $this->priceSummaryQuery();
        $stockSummary = $this->stockSummaryQuery();

        if ($hasCategory) {
            $query->leftJoin('categories as c', 'p.category_id', '=', 'c.id');
        }
        if ($hasSubCategory) {
            $query->leftJoin('categories as sc', 'p.sub_category_id', '=', 'sc.id');
        }
        if ($hasBrand) {
            $query->leftJoin('brands as b', 'p.brand_id', '=', 'b.id');
        }
        if ($hasUnit) {
            $query->leftJoin('units as u', 'p.unit_id', '=', 'u.id');
        }
        if ($hasTax) {
            $query->leftJoin('tax_rates as tr', 'p.tax', '=', 'tr.id');
        }
        if ($hasMeta) {
            $query->leftJoin('products_new_product_meta as meta', 'meta.product_id', '=', 'p.id');
        }
        if ($priceSummary !== null) {
            $query->leftJoinSub($priceSummary, 'pn_price', function ($join): void {
                $join->on('pn_price.product_id', '=', 'p.id');
            });
        }
        if ($stockSummary !== null) {
            $query->leftJoinSub($stockSummary, 'pn_stock', function ($join): void {
                $join->on('pn_stock.product_id', '=', 'p.id');
            });
        }

        $query->select($this->selectColumns(
            $productColumns,
            $metaColumns,
            $hasCategory,
            $hasSubCategory,
            $hasBrand,
            $hasUnit,
            $hasTax,
            $hasMeta,
            $priceSummary !== null,
            $stockSummary !== null,
            $includePurchasePrice
        ));

        $this->guard->applyBusiness($query, 'p.business_id');

        if (! empty($filters['search'])) {
            $search = '%' . trim((string) $filters['search']) . '%';
            $query->where(function (Builder $where) use ($search, $productColumns): void {
                $hasCondition = false;

                foreach (['name', 'sku', 'barcode', 'product_description'] as $column) {
                    if (! in_array($column, $productColumns, true)) {
                        continue;
                    }

                    if ($hasCondition) {
                        $where->orWhere('p.' . $column, 'like', $search);
                    } else {
                        $where->where('p.' . $column, 'like', $search);
                        $hasCondition = true;
                    }
                }

                if (! $hasCondition) {
                    $where->whereRaw('1 = 0');
                }
            });
        }

        foreach (['category_id', 'sub_category_id', 'brand_id', 'unit_id', 'tax'] as $field) {
            if (! empty($filters[$field]) && in_array($field, $productColumns, true)) {
                $query->where('p.' . $field, $filters[$field]);
            }
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $inactive = $filters['status'] === 'inactive';
            $hasNotForSelling = in_array('not_for_selling', $productColumns, true);
            $hasIsInactive = in_array('is_inactive', $productColumns, true);
            $hasProductsNewStatus = in_array('products_new_status', $productColumns, true);

            if ($hasNotForSelling || $hasIsInactive || $hasProductsNewStatus) {
                if ($inactive) {
                    $query->where(function (Builder $status) use (
                        $hasNotForSelling,
                        $hasIsInactive,
                        $hasProductsNewStatus
                    ): void {
                        $hasCondition = false;

                        if ($hasNotForSelling) {
                            $status->where('p.not_for_selling', 1);
                            $hasCondition = true;
                        }
                        if ($hasIsInactive) {
                            $hasCondition
                                ? $status->orWhere('p.is_inactive', 1)
                                : $status->where('p.is_inactive', 1);
                            $hasCondition = true;
                        }
                        if ($hasProductsNewStatus) {
                            $inactiveStatuses = ['inactive', 'suspended', 'discontinued', 'archived'];
                            $hasCondition
                                ? $status->orWhereIn('p.products_new_status', $inactiveStatuses)
                                : $status->whereIn('p.products_new_status', $inactiveStatuses);
                        }
                    });
                } else {
                    if ($hasNotForSelling) {
                        $query->where(function (Builder $status): void {
                            $status->whereNull('p.not_for_selling')->orWhere('p.not_for_selling', 0);
                        });
                    }
                    if ($hasIsInactive) {
                        $query->where(function (Builder $status): void {
                            $status->whereNull('p.is_inactive')->orWhere('p.is_inactive', 0);
                        });
                    }
                    if ($hasProductsNewStatus) {
                        $query->where(function (Builder $status): void {
                            $status->whereNull('p.products_new_status')
                                ->orWhereNotIn('p.products_new_status', [
                                    'inactive', 'suspended', 'discontinued', 'archived',
                                ]);
                        });
                    }
                }
            }
        }

        if (isset($filters['stock_type'])
            && $filters['stock_type'] !== ''
            && in_array('enable_stock', $productColumns, true)) {
            if ($filters['stock_type'] === 'stock') {
                $query->where('p.enable_stock', 1);
            } else {
                $query->where(function (Builder $stock): void {
                    $stock->whereNull('p.enable_stock')->orWhere('p.enable_stock', 0);
                });
            }
        }

        return $query;
    }

    public function paginated(
        array $filters = [],
        int $perPage = 25,
        bool $includePurchasePrice = false
    )
    {
        return $this->baseQuery($filters, $includePurchasePrice)
            ->orderByDesc('p.id')
            ->paginate($perPage)
            ->appends($filters);
    }

    public function findForView(int $id, bool $includePurchasePrice = false)
    {
        return $this->baseQuery([], $includePurchasePrice)->where('p.id', $id)->first();
    }

    public function dashboardStats(): array
    {
        $base = DB::table('products');
        $this->guard->applyBusiness($base);

        $productColumns = $this->columns('products');
        $total = (clone $base)->count();

        $active = in_array('not_for_selling', $productColumns, true)
            ? (clone $base)->where(function (Builder $query): void {
                $query->whereNull('not_for_selling')->orWhere('not_for_selling', 0);
            })->count()
            : $total;

        $inactive = max(0, $total - $active);
        $noImage = in_array('image', $productColumns, true)
            ? (clone $base)->where(function (Builder $query): void {
                $query->whereNull('image')->orWhere('image', '');
            })->count()
            : $total;
        $noBarcode = in_array('sku', $productColumns, true)
            ? (clone $base)->where(function (Builder $query): void {
                $query->whereNull('sku')->orWhere('sku', '');
            })->count()
            : $total;

        $lowStock = 0;
        if ($this->canJoin('variation_location_details', ['variation_id', 'qty_available'])
            && $this->canJoin('variations', ['id', 'product_id'])
            && in_array('alert_quantity', $productColumns, true)) {
            $low = DB::table('variation_location_details as vld')
                ->join('variations as v', 'vld.variation_id', '=', 'v.id')
                ->join('products as p', 'v.product_id', '=', 'p.id')
                ->whereRaw('COALESCE(vld.qty_available, 0) <= COALESCE(p.alert_quantity, 0)')
                ->whereRaw('COALESCE(p.alert_quantity, 0) > 0');

            $this->guard->applyBusiness($low, 'p.business_id');
            $lowStock = $low->count();
        }

        return compact('total', 'active', 'inactive', 'noImage', 'noBarcode', 'lowStock');
    }

    private function columns(string $table): array
    {
        if (array_key_exists($table, $this->columnCache)) {
            return $this->columnCache[$table];
        }

        return $this->columnCache[$table] = Schema::hasTable($table)
            ? Schema::getColumnListing($table)
            : [];
    }

    private function canJoin(string $table, array $requiredColumns): bool
    {
        $columns = $this->columns($table);
        if (empty($columns)) {
            return false;
        }

        foreach ($requiredColumns as $column) {
            if (! in_array($column, $columns, true)) {
                return false;
            }
        }

        return true;
    }

    private function selectColumns(
        array $productColumns,
        array $metaColumns,
        bool $hasCategory,
        bool $hasSubCategory,
        bool $hasBrand,
        bool $hasUnit,
        bool $hasTax,
        bool $hasMeta,
        bool $hasPriceSummary,
        bool $hasStockSummary,
        bool $includePurchasePrice
    ): array {
        return [
            'p.id',
            $this->productColumn($productColumns, 'name', "CONCAT('Product #', p.id)", 'name'),
            $this->productColumn($productColumns, 'sku', "''", 'sku'),
            in_array('barcode', $productColumns, true)
                ? 'p.barcode'
                : (in_array('sku', $productColumns, true)
                    ? DB::raw('p.sku as barcode')
                    : DB::raw("'' as barcode")),
            $this->productColumn($productColumns, 'type', "'single'", 'type'),
            $this->productColumn($productColumns, 'image', 'NULL', 'image'),
            $this->productColumn($productColumns, 'enable_stock', '0', 'enable_stock'),
            $this->productColumn($productColumns, 'alert_quantity', '0', 'alert_quantity'),
            $this->productColumn($productColumns, 'not_for_selling', '0', 'not_for_selling'),
            $this->productColumn($productColumns, 'is_inactive', '0', 'is_inactive'),
            $this->productColumn($productColumns, 'products_new_status', "'active'", 'products_new_status'),
            $this->productColumn($productColumns, 'category_id', 'NULL', 'category_id'),
            $this->productColumn($productColumns, 'sub_category_id', 'NULL', 'sub_category_id'),
            $this->productColumn($productColumns, 'brand_id', 'NULL', 'brand_id'),
            $this->productColumn($productColumns, 'unit_id', 'NULL', 'unit_id'),
            $this->productColumn($productColumns, 'tax', 'NULL', 'tax'),
            $this->productColumn($productColumns, 'tax_type', "'exclusive'", 'tax_type'),
            $this->productColumn($productColumns, 'created_at', 'NULL', 'created_at'),
            $this->productColumn($productColumns, 'updated_at', 'NULL', 'updated_at'),
            $hasCategory ? 'c.name as category_name' : DB::raw("'' as category_name"),
            $hasSubCategory ? 'sc.name as sub_category_name' : DB::raw("'' as sub_category_name"),
            $hasBrand ? 'b.name as brand_name' : DB::raw("'' as brand_name"),
            $hasUnit ? 'u.short_name as unit_short_name' : DB::raw("'' as unit_short_name"),
            $hasTax ? 'tr.name as tax_name' : DB::raw("'' as tax_name"),
            $hasTax && in_array('amount', $this->columns('tax_rates'), true)
                ? 'tr.amount as tax_amount'
                : DB::raw('0 as tax_amount'),
            $hasMeta && in_array('health_score', $metaColumns, true)
                ? 'meta.health_score'
                : DB::raw('0 as health_score'),
            $hasMeta && in_array('primary_image', $metaColumns, true)
                ? 'meta.primary_image'
                : DB::raw('NULL as primary_image'),
            $hasStockSummary
                ? DB::raw('COALESCE(pn_stock.current_stock, 0) as current_stock')
                : DB::raw('0 as current_stock'),
            $includePurchasePrice && $hasPriceSummary
                ? DB::raw('COALESCE(pn_price.purchase_price_min, 0) as purchase_price_min')
                : DB::raw('NULL as purchase_price_min'),
            $includePurchasePrice && $hasPriceSummary
                ? DB::raw('COALESCE(pn_price.purchase_price_max, 0) as purchase_price_max')
                : DB::raw('NULL as purchase_price_max'),
            $hasPriceSummary
                ? DB::raw('COALESCE(pn_price.selling_price_min, 0) as selling_price_min')
                : DB::raw('0 as selling_price_min'),
            $hasPriceSummary
                ? DB::raw('COALESCE(pn_price.selling_price_max, 0) as selling_price_max')
                : DB::raw('0 as selling_price_max'),
        ];
    }

    /**
     * Build one price row per product. The list must not join the variations
     * table directly because variable products would otherwise be duplicated.
     * Inclusive prices are preferred and the exclusive price is used as a
     * compatibility fallback for older tenant schemas.
     */
    private function priceSummaryQuery(): ?Builder
    {
        $columns = $this->columns('variations');

        if (empty($columns) || ! in_array('product_id', $columns, true)) {
            return null;
        }

        $purchaseExpression = $this->priceExpression(
            $columns,
            'dpp_inc_tax',
            'default_purchase_price'
        );
        $sellingExpression = $this->priceExpression(
            $columns,
            'sell_price_inc_tax',
            'default_sell_price'
        );

        $query = DB::table('variations as v')
            ->select('v.product_id')
            ->selectRaw('MIN(' . $purchaseExpression . ') as purchase_price_min')
            ->selectRaw('MAX(' . $purchaseExpression . ') as purchase_price_max')
            ->selectRaw('MIN(' . $sellingExpression . ') as selling_price_min')
            ->selectRaw('MAX(' . $sellingExpression . ') as selling_price_max')
            ->whereNotNull('v.product_id')
            ->groupBy('v.product_id');

        if (in_array('deleted_at', $columns, true)) {
            $query->whereNull('v.deleted_at');
        }

        return $query;
    }

    /**
     * Current stock is summed across every location for the product. The
     * implementation supports both common variation_location_details schemas:
     * one with product_id and one where product ownership is resolved through
     * the variations table.
     */
    private function stockSummaryQuery(): ?Builder
    {
        $stockColumns = $this->columns('variation_location_details');

        if (empty($stockColumns) || ! in_array('qty_available', $stockColumns, true)) {
            return null;
        }

        if (in_array('product_id', $stockColumns, true)) {
            return DB::table('variation_location_details as vld')
                ->select('vld.product_id')
                ->selectRaw('SUM(COALESCE(vld.qty_available, 0)) as current_stock')
                ->whereNotNull('vld.product_id')
                ->groupBy('vld.product_id');
        }

        $variationColumns = $this->columns('variations');
        if (! in_array('variation_id', $stockColumns, true)
            || empty($variationColumns)
            || ! in_array('id', $variationColumns, true)
            || ! in_array('product_id', $variationColumns, true)) {
            return null;
        }

        $query = DB::table('variation_location_details as vld')
            ->join('variations as v', 'v.id', '=', 'vld.variation_id')
            ->select('v.product_id')
            ->selectRaw('SUM(COALESCE(vld.qty_available, 0)) as current_stock')
            ->whereNotNull('v.product_id')
            ->groupBy('v.product_id');

        if (in_array('deleted_at', $variationColumns, true)) {
            $query->whereNull('v.deleted_at');
        }

        return $query;
    }

    private function priceExpression(
        array $columns,
        string $preferredColumn,
        string $fallbackColumn
    ): string {
        $parts = [];

        if (in_array($preferredColumn, $columns, true)) {
            $parts[] = 'v.' . $preferredColumn;
        }
        if (in_array($fallbackColumn, $columns, true)) {
            $parts[] = 'v.' . $fallbackColumn;
        }

        if ($parts === []) {
            return '0';
        }

        $parts[] = '0';

        return 'COALESCE(' . implode(', ', $parts) . ')';
    }

    private function productColumn(array $columns, string $column, string $fallback, string $alias)
    {
        return in_array($column, $columns, true)
            ? 'p.' . $column
            : DB::raw($fallback . ' as ' . $alias);
    }
}

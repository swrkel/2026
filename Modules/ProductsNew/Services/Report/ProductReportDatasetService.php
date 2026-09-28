<?php

namespace Modules\ProductsNew\Services\Report;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductReportDatasetService
{
    public function __construct(
        protected ProductReportFilterService $filters,
        protected ProductsNewTenantGuard $guard
    ) {
    }

    /**
     * Build the common report dataset without assuming that every tenant has
     * every optional Products New table or every later schema column.
     */
    public function rows(array $request, int $perPage = 50)
    {
        $filters = $this->filters->filters($request);

        if (! Schema::hasTable('products')) {
            throw new \RuntimeException('The products table is not available in the active tenant database.');
        }

        $productColumns = Schema::getColumnListing('products');
        $query = DB::table('products as p');
        $select = [
            'p.id',
            $this->columnOrAlias($productColumns, 'name', "CONCAT('Product #', p.id)", 'name'),
            $this->columnOrAlias($productColumns, 'sku', "''", 'sku'),
            $this->columnOrAlias($productColumns, 'type', "'single'", 'type'),
            $this->columnOrAlias($productColumns, 'enable_stock', '0', 'enable_stock'),
            $this->columnOrAlias($productColumns, 'alert_quantity', '0', 'alert_quantity'),
        ];

        if ($this->canJoin('categories', ['id', 'name']) && in_array('category_id', $productColumns, true)) {
            $query->leftJoin('categories as c', 'c.id', '=', 'p.category_id');
            $select[] = 'c.name as category';
        } else {
            $select[] = DB::raw("'' as category");
        }

        if ($this->canJoin('brands', ['id', 'name']) && in_array('brand_id', $productColumns, true)) {
            $query->leftJoin('brands as b', 'b.id', '=', 'p.brand_id');
            $select[] = 'b.name as brand';
        } else {
            $select[] = DB::raw("'' as brand");
        }

        if ($this->canJoin('products_new_product_meta', ['product_id'])) {
            $metaColumns = Schema::getColumnListing('products_new_product_meta');
            $query->leftJoin('products_new_product_meta as pm', 'pm.product_id', '=', 'p.id');
            $select[] = in_array('health_score', $metaColumns, true)
                ? 'pm.health_score'
                : DB::raw('0 as health_score');

            // Older Products New schemas never had lifecycle_status in this
            // table. Use it only when it actually exists.
            if (in_array('lifecycle_status', $metaColumns, true)) {
                $select[] = 'pm.lifecycle_status';
            } else {
                $select[] = $this->statusExpression($productColumns);
            }
        } else {
            $select[] = DB::raw('0 as health_score');
            $select[] = $this->statusExpression($productColumns);
        }

        $query->select($select);
        $this->guard->applyBusiness($query, 'p.business_id');

        if (! empty($filters['category_id']) && in_array('category_id', $productColumns, true)) {
            $query->where('p.category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['brand_id']) && in_array('brand_id', $productColumns, true)) {
            $query->where('p.brand_id', (int) $filters['brand_id']);
        }

        if (! empty($filters['keyword'])) {
            $keyword = '%' . trim((string) $filters['keyword']) . '%';
            $query->where(function (Builder $where) use ($keyword, $productColumns): void {
                if (in_array('name', $productColumns, true)) {
                    $where->where('p.name', 'like', $keyword);
                }

                if (in_array('sku', $productColumns, true)) {
                    $method = in_array('name', $productColumns, true) ? 'orWhere' : 'where';
                    $where->{$method}('p.sku', 'like', $keyword);
                }
            });
        }

        return $query
            ->orderBy(in_array('name', $productColumns, true) ? 'p.name' : 'p.id')
            ->paginate($perPage)
            ->appends($request);
    }

    protected function canJoin(string $table, array $requiredColumns): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        foreach ($requiredColumns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    protected function columnOrAlias(array $columns, string $column, string $fallback, string $alias)
    {
        return in_array($column, $columns, true)
            ? 'p.' . $column
            : DB::raw($fallback . ' as ' . $alias);
    }

    protected function statusExpression(array $productColumns)
    {
        if (in_array('not_for_selling', $productColumns, true)) {
            return DB::raw("CASE WHEN COALESCE(p.not_for_selling, 0) = 1 THEN 'inactive' ELSE 'active' END as lifecycle_status");
        }

        return DB::raw("'active' as lifecycle_status");
    }
}

<?php

namespace Modules\ProductsNew\Services\Framework;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class InventoryExceptionService
{
    public function __construct(protected ProductsNewTenantGuard $guard) {}

    public function summary(): array
    {
        $columns = $this->productColumns();
        $base = DB::table('products');

        if (! in_array('business_id', $columns, true)) {
            return ['missing_image' => 0, 'missing_barcode' => 0, 'inactive' => 0];
        }

        $base->where('business_id', $this->guard->businessId());
        $total = (clone $base)->count();

        $missingImage = in_array('image', $columns, true)
            ? (clone $base)->where(function (Builder $query): void {
                $query->whereNull('image')->orWhere('image', '');
            })->count()
            : $total;

        $identifierColumn = $this->identifierColumn($columns);
        $missingBarcode = $identifierColumn !== null
            ? (clone $base)->where(function (Builder $query) use ($identifierColumn): void {
                $query->whereNull($identifierColumn)->orWhere($identifierColumn, '');
            })->count()
            : $total;

        if (in_array('not_for_selling', $columns, true)) {
            $inactive = (clone $base)->where('not_for_selling', 1)->count();
        } elseif (in_array('is_inactive', $columns, true)) {
            $inactive = (clone $base)->where('is_inactive', 1)->count();
        } else {
            $inactive = 0;
        }

        return [
            'missing_image' => $missingImage,
            'missing_barcode' => $missingBarcode,
            'inactive' => $inactive,
        ];
    }

    public function items(array $filters)
    {
        $columns = $this->productColumns();
        $query = DB::table('products');

        if (! in_array('business_id', $columns, true)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where('business_id', $this->guard->businessId());
        }

        $query->select($this->selectColumns($columns));

        if (($filters['type'] ?? '') === 'missing_image') {
            if (in_array('image', $columns, true)) {
                $query->where(function (Builder $where): void {
                    $where->whereNull('image')->orWhere('image', '');
                });
            }
        }

        if (($filters['type'] ?? '') === 'missing_barcode') {
            $identifierColumn = $this->identifierColumn($columns);

            if ($identifierColumn !== null) {
                $query->where(function (Builder $where) use ($identifierColumn): void {
                    $where->whereNull($identifierColumn)->orWhere($identifierColumn, '');
                });
            }
        }

        return $query
            ->orderByDesc(in_array('id', $columns, true) ? 'id' : 'created_at')
            ->paginate(50);
    }

    private function productColumns(): array
    {
        return Schema::hasTable('products')
            ? Schema::getColumnListing('products')
            : [];
    }

    private function identifierColumn(array $columns): ?string
    {
        if (in_array('barcode', $columns, true)) {
            return 'barcode';
        }

        return in_array('sku', $columns, true) ? 'sku' : null;
    }

    private function selectColumns(array $columns): array
    {
        return [
            $this->columnOrFallback($columns, 'id', '0', 'id'),
            $this->columnOrFallback($columns, 'name', "''", 'name'),
            $this->columnOrFallback($columns, 'sku', "''", 'sku'),
            in_array('barcode', $columns, true)
                ? 'barcode'
                : (in_array('sku', $columns, true)
                    ? DB::raw('sku as barcode')
                    : DB::raw("'' as barcode")),
            $this->columnOrFallback($columns, 'image', 'NULL', 'image'),
            in_array('not_for_selling', $columns, true)
                ? 'not_for_selling'
                : (in_array('is_inactive', $columns, true)
                    ? DB::raw('is_inactive as not_for_selling')
                    : DB::raw('0 as not_for_selling')),
            $this->columnOrFallback($columns, 'created_at', 'NULL', 'created_at'),
        ];
    }

    private function columnOrFallback(array $columns, string $column, string $fallback, string $alias)
    {
        return in_array($column, $columns, true)
            ? $column
            : DB::raw($fallback . ' as ' . $alias);
    }
}

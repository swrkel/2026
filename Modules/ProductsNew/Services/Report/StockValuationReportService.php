<?php

namespace Modules\ProductsNew\Services\Report;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class StockValuationReportService
{
    public function __construct(protected ProductsNewTenantGuard $guard)
    {
    }

    public function data(array $filters = [])
    {
        if (! $this->hasRequiredTables()) {
            return DB::table('products')->whereRaw('1 = 0')->paginate(50);
        }

        $hasLocation = Schema::hasTable('business_locations')
            && Schema::hasColumn('business_locations', 'id')
            && Schema::hasColumn('business_locations', 'name');
        $variationColumns = Schema::getColumnListing('variations');
        $productColumns = Schema::getColumnListing('products');

        $query = DB::table('variation_location_details as vld')
            ->join('variations as v', 'v.id', '=', 'vld.variation_id')
            ->join('products as p', 'p.id', '=', 'v.product_id');

        if ($hasLocation) {
            $query->leftJoin('business_locations as l', 'l.id', '=', 'vld.location_id');
        }

        $query->select([
            'p.id',
            in_array('name', $productColumns, true) ? 'p.name' : DB::raw("CONCAT('Product #', p.id) as name"),
            in_array('sku', $productColumns, true) ? 'p.sku' : DB::raw("'' as sku"),
        ]);

        $query->selectRaw(
            $hasLocation
                ? "COALESCE(l.name, CONCAT('Location #', vld.location_id)) as location_name"
                : "CONCAT('Location #', vld.location_id) as location_name"
        );
        $query->selectRaw('SUM(COALESCE(vld.qty_available, 0)) as qty_available');
        $query->selectRaw(
            in_array('default_purchase_price', $variationColumns, true)
                ? 'SUM(COALESCE(vld.qty_available, 0) * COALESCE(v.default_purchase_price, 0)) as stock_value'
                : '0 as stock_value'
        );

        $groupBy = ['p.id'];
        if (in_array('name', $productColumns, true)) {
            $groupBy[] = 'p.name';
        }
        if (in_array('sku', $productColumns, true)) {
            $groupBy[] = 'p.sku';
        }
        $groupBy[] = 'vld.location_id';
        if ($hasLocation) {
            $groupBy[] = 'l.name';
        }

        $query->groupBy($groupBy);
        $this->guard->applyBusiness($query, 'p.business_id');

        if (! empty($filters['location_id'])) {
            $query->where('vld.location_id', (int) $filters['location_id']);
        }

        return $query
            ->orderBy(in_array('name', $productColumns, true) ? 'p.name' : 'p.id')
            ->paginate(50)
            ->appends($filters);
    }

    protected function hasRequiredTables(): bool
    {
        foreach (['products', 'variations', 'variation_location_details'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return Schema::hasColumn('products', 'business_id')
            && Schema::hasColumn('variations', 'id')
            && Schema::hasColumn('variations', 'product_id')
            && Schema::hasColumn('variation_location_details', 'variation_id')
            && Schema::hasColumn('variation_location_details', 'location_id')
            && Schema::hasColumn('variation_location_details', 'qty_available');
    }
}

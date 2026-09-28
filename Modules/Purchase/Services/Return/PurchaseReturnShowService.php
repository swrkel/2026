<?php

namespace Modules\Purchase\Services\Return;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;

class PurchaseReturnShowService
{
    public function __construct(protected PurchaseDateNumberUtil $numbers)
    {
    }

    /** @return array<string, mixed> */
    public function details(int $id): array
    {
        $query = DB::table('transactions as t')
            ->where('t.business_id', $this->numbers->businessId())
            ->where('t.type', 'purchase_return')
            ->where('t.id', $id)
            ->leftJoin('transactions as parent', 'parent.id', '=', 't.return_parent_id');
        if (Schema::hasTable('contacts')) {
            $query->leftJoin('contacts as c', 'c.id', '=', 't.contact_id');
        }
        if (Schema::hasTable('business_locations')) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 't.location_id');
        }
        if (Schema::hasTable('stores') && Schema::hasColumn('transactions', 'store_id')) {
            $query->leftJoin('stores as st', 'st.id', '=', 't.store_id');
        }

        $select = ['t.*', DB::raw("COALESCE(parent.invoice_no, parent.ref_no, CONCAT('PUR-', parent.id), '') as parent_purchase_no")];
        $select[] = Schema::hasTable('contacts')
            ? DB::raw("COALESCE(NULLIF(c.supplier_business_name, ''), c.name, '') as supplier_name")
            : DB::raw("'' as supplier_name");
        $select[] = Schema::hasTable('business_locations') ? 'bl.name as location_name' : DB::raw("'' as location_name");
        $select[] = Schema::hasTable('stores') && Schema::hasColumn('transactions', 'store_id')
            ? 'st.name as store_name'
            : DB::raw("'' as store_name");
        $return = $query->first($select);
        abort_unless($return, 404, 'Purchase return not found.');

        $lines = collect();
        if (Schema::hasTable('purchase_lines')) {
            $lineQuery = DB::table('purchase_lines as pl')
                ->leftJoin('products as p', 'p.id', '=', 'pl.product_id')
                ->leftJoin('variations as v', 'v.id', '=', 'pl.variation_id')
                ->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
                ->leftJoin('units as u', 'u.id', '=', 'p.unit_id')
                ->where('pl.transaction_id', $id);
            $quantityExpr = Schema::hasColumn('purchase_lines', 'quantity_returned')
                ? 'COALESCE(pl.quantity_returned, pl.quantity, 0)'
                : 'COALESCE(pl.quantity, 0)';
            $lines = $lineQuery->get([
                'pl.id', 'pl.product_id', 'pl.variation_id', 'pl.purchase_price_inc_tax',
                DB::raw($quantityExpr . ' as return_quantity'),
                'p.name as product_name', 'v.sub_sku', 'v.name as variation_name',
                'pv.name as variation_group', 'u.short_name as unit_name',
            ]);
        }

        return ['return' => $return, 'lines' => $lines];
    }
}

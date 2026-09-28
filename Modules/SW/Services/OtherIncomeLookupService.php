<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Services available as Other Income — 8045.
 *
 * "All the products which are not enabled as Manage Stock": a service has no
 * stock to draw down, which is precisely what separates it from Other Sales.
 */
class OtherIncomeLookupService
{
    public function services(int $businessId): array
    {
        return DB::table('products')
            ->join('variations', 'variations.product_id', '=', 'products.id')
            ->where('products.business_id', $businessId)
            ->where('products.enable_stock', 0)
            ->when(Schema::hasColumn('products', 'deleted_at'),
                fn ($q) => $q->whereNull('products.deleted_at'))
            ->orderBy('products.name')
            ->limit(500)
            ->get([
                'products.id as product_id',
                'products.name',
                'products.sku',
                'variations.id as variation_id',
                'variations.sell_price_inc_tax as amount',
            ])
            ->all();
    }

    /** The amount for one service. */
    public function forService(int $businessId, int $productId): array
    {
        $row = DB::table('products')
            ->join('variations', 'variations.product_id', '=', 'products.id')
            ->where('products.id', $productId)
            ->where('products.business_id', $businessId)
            ->first([
                'products.id as product_id',
                'products.name',
                'products.sku',
                'variations.sell_price_inc_tax as amount',
            ]);

        if (! $row) {
            return ['found' => false];
        }

        return [
            'found' => true,
            'product_id' => $row->product_id,
            'name' => $row->name,
            'sku' => $row->sku,
            'amount' => (float) $row->amount,

            /*
             | Whether this user may change the price.
             |
             | Decided on the server, not in the browser. A disabled input is a
             | courtesy; the permission is the control, and it is checked again
             | when the settlement is saved.
            */
            'can_edit_price' => auth()->user()->can('superadmin')
                || auth()->user()->can('sw.other_income.edit_price'),
        ];
    }
}

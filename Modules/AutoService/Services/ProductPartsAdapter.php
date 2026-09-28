<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductPartsAdapter
{
    public function list($businessId = null, $term = null)
    {
        if (!Schema::hasTable('products')) return collect();

        $q = DB::table('products');
        if ($businessId && Schema::hasColumn('products','business_id')) $q->where('business_id',$businessId);
        if (Schema::hasColumn('products','not_for_selling')) {
            $q->where(fn($x) => $x->whereNull('not_for_selling')->orWhere('not_for_selling',0));
        }
        if ($term) {
            $q->where(fn($x) => $x->where('name','like',"%{$term}%")->orWhere('sku','like',"%{$term}%"));
        }

        return $q->orderBy('name')->limit(250)->get(['id','name','sku']);
    }

    public function listForSelect($businessId = null, $term = null): array
    {
        return $this->list($businessId,$term)->mapWithKeys(function($row){
            return [$row->id => trim(($row->sku ? $row->sku.' - ' : '').$row->name)];
        })->all();
    }

    public function packageStockItems($businessId = null, $locationId = null, $term = null): Collection
    {
        if (!Schema::hasTable('products')) return collect();

        if (Schema::hasTable('variations') && Schema::hasTable('product_variations')) {
            $q = DB::table('variations as v')
                ->join('product_variations as pv','pv.id','=','v.product_variation_id')
                ->join('products as p','p.id','=','pv.product_id')
                ->leftJoin('variation_location_details as vld', function($join) use ($locationId) {
                    $join->on('vld.variation_id','=','v.id');
                    if ($locationId) $join->where('vld.location_id','=',$locationId);
                })
                ->select([
                    'p.id as product_id','v.id as variation_id','p.name',
                    'p.sku','v.sub_sku',
                    DB::raw('COALESCE(v.sell_price_inc_tax, v.default_sell_price, 0) as unit_price'),
                    DB::raw('COALESCE(vld.qty_available,0) as qty_available')
                ]);

            if ($businessId && Schema::hasColumn('products','business_id')) $q->where('p.business_id',$businessId);
            if ($term) $q->where(fn($x) => $x->where('p.name','like',"%{$term}%")->orWhere('p.sku','like',"%{$term}%")->orWhere('v.sub_sku','like',"%{$term}%"));

            return $q->orderBy('p.name')->limit(500)->get();
        }

        return $this->list($businessId,$term)->map(function($p){
            return (object)[
                'product_id'=>$p->id,'variation_id'=>null,'name'=>$p->name,
                'sku'=>$p->sku,'sub_sku'=>null,'unit_price'=>0,'qty_available'=>0
            ];
        });
    }
}

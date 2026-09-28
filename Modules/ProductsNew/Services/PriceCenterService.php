<?php
namespace Modules\ProductsNew\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Entities\ProductsNewPriceTier;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class PriceCenterService
{
    public function __construct(
        protected ProductsNewTenantGuard $guard,
        protected ProductTimelineService $timeline,
        protected ProductStatusService $status
    ) {}

    public function query(array $filters=[])
    {
        /*
         * MA-002: products_new_price_tiers has NO migration anywhere in this
         * module, so on any tenant it simply does not exist and this query
         * throws "Base table or view not found".
         *
         * That surfaced in the log alongside the products_new_timeline error.
         * I have NOT invented a schema for it - there is no migration to copy
         * from, and guessing at the columns of a pricing table is a good way
         * to create a second, wrong one. Returning an empty result keeps the
         * screen usable until someone decides what that table should be.
         */
        if (! \Illuminate\Support\Facades\Schema::hasTable('products_new_price_tiers')) {
            return DB::table('products as p')->whereRaw('1 = 0');
        }

        $q = DB::table('products_new_price_tiers as pt')
            ->leftJoin('products as p','p.id','=','pt.product_id')
            ->leftJoin('variations as v','v.id','=','pt.variation_id')
            ->leftJoin('business_locations as l','l.id','=','pt.location_id')
            ->select('pt.*','p.name as product_name','p.sku','v.name as variation_name','l.name as location_name')
            ->orderBy('p.name')->orderBy('pt.price_type');
        $this->guard->applyBusiness($q,'pt.business_id');
        if(!empty($filters['search'])){ $s='%'.$filters['search'].'%'; $q->where(fn($x)=>$x->where('p.name','like',$s)->orWhere('p.sku','like',$s)->orWhere('pt.price_type','like',$s)); }
        if(!empty($filters['price_type'])){ $q->where('pt.price_type',$filters['price_type']); }
        if(!empty($filters['location_id'])){ $q->where('pt.location_id',$filters['location_id']); }
        return $q;
    }

    public function store(array $data): ProductsNewPriceTier
    {
        $this->status->assertActiveProductId((int) $data['product_id']);

        $tier = ProductsNewPriceTier::create([
            'business_id' => $this->guard->businessId(),
            'product_id' => $data['product_id'],
            'variation_id' => $data['variation_id'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'price_type' => $data['price_type'],
            'price' => $data['price'],
            'currency' => $data['currency'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_by' => Auth::id(),
        ]);
        $this->timeline->record($tier->product_id, 'price_center', 'Price tier added', ['price_type'=>$tier->price_type,'price'=>$tier->price]);
        return $tier;
    }

    public function priceTypes(): array
    {
        return ['retail','wholesale','dealer','online','promotion','branch','replacement_cost','standard_cost'];
    }
}

<?php
namespace Modules\ProductsNew\Services\Intelligence;

use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Entities\ProductsNewProductRelationship;

class ProductRelationshipService
{
    public function list(array $filters = [])
    {
        return DB::table('products_new_product_relationships as r')
            ->leftJoin('products as p', 'p.id', '=', 'r.product_id')
            ->leftJoin('products as rp', 'rp.id', '=', 'r.related_product_id')
            ->select('r.*', 'p.name as product_name', 'p.sku as product_sku', 'rp.name as related_product_name', 'rp.sku as related_product_sku')
            ->when($filters['business_id'] ?? session('business.id'), fn($q,$businessId)=>$q->where('r.business_id',$businessId))
            ->when($filters['relationship_type'] ?? null, fn($q,$type)=>$q->where('r.relationship_type',$type))
            ->orderByDesc('r.id')->paginate($filters['per_page'] ?? 25);
    }

    public function create(array $data): ProductsNewProductRelationship
    {
        $data['business_id'] = $data['business_id'] ?? session('business.id');
        $data['created_by'] = auth()->id();
        $data['is_active'] = array_key_exists('is_active', $data) ? (bool)$data['is_active'] : true;
        return ProductsNewProductRelationship::create($data);
    }
}

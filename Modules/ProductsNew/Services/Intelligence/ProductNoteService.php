<?php
namespace Modules\ProductsNew\Services\Intelligence;

use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Entities\ProductsNewProductNote;

class ProductNoteService
{
    public function list(array $filters = [])
    {
        return DB::table('products_new_product_notes as n')
            ->leftJoin('products as p','p.id','=','n.product_id')
            ->select('n.*','p.name as product_name','p.sku')
            ->when($filters['business_id'] ?? session('business.id'), fn($q,$businessId)=>$q->where('n.business_id',$businessId))
            ->when($filters['product_id'] ?? null, fn($q,$productId)=>$q->where('n.product_id',$productId))
            ->orderByDesc('n.is_pinned')->orderByDesc('n.id')->paginate($filters['per_page'] ?? 25);
    }

    public function create(array $data): ProductsNewProductNote
    {
        $data['business_id'] = $data['business_id'] ?? session('business.id');
        $data['created_by'] = auth()->id();
        $data['is_pinned'] = !empty($data['is_pinned']);
        return ProductsNewProductNote::create($data);
    }
}

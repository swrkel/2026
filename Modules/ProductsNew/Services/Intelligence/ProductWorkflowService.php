<?php
namespace Modules\ProductsNew\Services\Intelligence;

use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Entities\ProductsNewProduct;
use Modules\ProductsNew\Entities\ProductsNewProductStatus;
use Modules\ProductsNew\Entities\ProductsNewStatusTransition;
use Modules\ProductsNew\Services\ProductTimelineService;

class ProductWorkflowService
{
    public function statuses(?int $businessId = null)
    {
        $businessId = $businessId ?: session('business.id');
        return ProductsNewProductStatus::query()
            ->where(function($q) use ($businessId){ $q->whereNull('business_id')->orWhere('business_id',$businessId); })
            ->where('is_active',1)->orderBy('sort_order')->get();
    }

    public function transition(ProductsNewProduct $product, string $toStatus, ?string $note = null): ProductsNewStatusTransition
    {
        $fromStatus = $product->products_new_status ?: ($product->status ?: 'active');
        $product->forceFill(['products_new_status' => $toStatus])->save();
        $transition = ProductsNewStatusTransition::create([
            'business_id' => $product->business_id ?: session('business.id'),
            'product_id' => $product->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'note' => $note,
            'created_by' => auth()->id(),
        ]);
        app(ProductTimelineService::class)->log($product->id, 'status_changed', ['from'=>$fromStatus,'to'=>$toStatus,'note'=>$note]);
        return $transition;
    }

    public function transitions(array $filters = [])
    {
        return DB::table('products_new_status_transitions as t')
            ->leftJoin('products as p','p.id','=','t.product_id')
            ->select('t.*','p.name as product_name','p.sku')
            ->when($filters['business_id'] ?? session('business.id'), fn($q,$businessId)=>$q->where('t.business_id',$businessId))
            ->orderByDesc('t.id')->paginate($filters['per_page'] ?? 25);
    }
}

<?php
namespace Modules\ProductsNew\Services\Media;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Entities\ProductsNewMedia;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class ProductMediaService
{
    public function list(Request $request)
    {
        ProductsNewTenantGuard::ensureBusinessId($request);
        return ProductsNewMedia::query()
            ->where('business_id', $request->session()->get('user.business_id'))
            ->when($request->product_id, fn($q)=>$q->where('product_id', $request->product_id))
            ->whereNull('deleted_at')
            ->orderByDesc('is_primary')
            ->orderByDesc('id')
            ->paginate(30);
    }

    public function store(Request $request): ProductsNewMedia
    {
        ProductsNewTenantGuard::ensureBusinessId($request);
        $businessId = $request->session()->get('user.business_id');
        return DB::transaction(function () use ($request, $businessId) {
            if ($request->boolean('is_primary')) {
                ProductsNewMedia::where('business_id', $businessId)->where('product_id', $request->product_id)->update(['is_primary'=>0]);
            }
            return ProductsNewMedia::create([
                'business_id' => $businessId,
                'product_id' => $request->product_id,
                'media_type' => $request->media_type ?: 'image',
                'title' => $request->title,
                'file_name' => $request->file_name,
                'file_path' => $request->file_path,
                'external_url' => $request->external_url,
                'is_primary' => $request->boolean('is_primary'),
                'metadata' => ['note'=>$request->note],
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function remove(Request $request, int $id): void
    {
        ProductsNewTenantGuard::ensureBusinessId($request);
        ProductsNewMedia::where('business_id', $request->session()->get('user.business_id'))->where('id', $id)->update(['deleted_at'=>now()]);
    }
}

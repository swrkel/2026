<?php
namespace Modules\ProductsNew\Services\Barcode;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ProductsNew\Entities\ProductsNewBarcodeTemplate;
use Modules\ProductsNew\Entities\ProductsNewBarcodeQueue;
use Modules\ProductsNew\Utilities\ProductsNewTenantGuard;

class BarcodeTemplateService
{
    public function templates(Request $request)
    {
        ProductsNewTenantGuard::ensureBusinessId($request);
        return ProductsNewBarcodeTemplate::where('business_id', $request->session()->get('user.business_id'))
            ->where('is_active', 1)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
    }

    public function saveTemplate(Request $request): ProductsNewBarcodeTemplate
    {
        ProductsNewTenantGuard::ensureBusinessId($request);
        $businessId = $request->session()->get('user.business_id');
        return DB::transaction(function () use ($request, $businessId) {
            if ($request->boolean('is_default')) {
                ProductsNewBarcodeTemplate::where('business_id', $businessId)->update(['is_default'=>0]);
            }
            return ProductsNewBarcodeTemplate::updateOrCreate(
                ['id'=>$request->id, 'business_id'=>$businessId],
                [
                    'name'=>$request->name,
                    'paper_size'=>$request->paper_size ?: 'A4',
                    'label_width'=>$request->label_width ?: 38,
                    'label_height'=>$request->label_height ?: 25,
                    'labels_per_row'=>$request->labels_per_row ?: 3,
                    'barcode_type'=>$request->barcode_type ?: 'CODE128',
                    'settings'=>[
                        'show_name'=>$request->boolean('show_name'),
                        'show_price'=>$request->boolean('show_price'),
                        'show_sku'=>$request->boolean('show_sku'),
                        'show_business_name'=>$request->boolean('show_business_name'),
                    ],
                    'is_default'=>$request->boolean('is_default'),
                    'is_active'=>1,
                    'created_by'=>auth()->id(),
                ]
            );
        });
    }

    public function queue(Request $request): ProductsNewBarcodeQueue
    {
        ProductsNewTenantGuard::ensureBusinessId($request);
        return ProductsNewBarcodeQueue::create([
            'business_id'=>$request->session()->get('user.business_id'),
            'product_id'=>$request->product_id,
            'variation_id'=>$request->variation_id,
            'template_id'=>$request->template_id,
            'barcode_value'=>$request->barcode_value,
            'qty'=>$request->quantity ?: 1,
            'status'=>'pending',
            'created_by'=>auth()->id(),
        ]);
    }
}

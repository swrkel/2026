<?php
namespace Modules\ProductsNew\Http\Controllers\Batch;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Entities\ProductsNewBatch;
use Modules\ProductsNew\Services\Batch\BatchService;
use Modules\ProductsNew\Services\ProductLookupService;

class BatchController extends Controller
{
    public function __construct(protected BatchService $service, protected ProductLookupService $lookup) {}

    public function index(Request $request)
    {
        $batches = $this->service->query($request->all())->paginate(50);
        $lookups = $this->lookup->formLookups();
        return view('productsnew::batch.index', compact('batches','lookups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'=>'required|integer','variation_id'=>'nullable|integer','location_id'=>'required|integer',
            'batch_no'=>'required|string|max:191','lot_no'=>'nullable|string|max:191','supplier_batch_no'=>'nullable|string|max:191',
            'manufactured_at'=>'nullable|date','expiry_at'=>'nullable|date','opening_qty'=>'nullable|numeric|min:0',
            'cost_price'=>'nullable|numeric|min:0','selling_price'=>'nullable|numeric|min:0','note'=>'nullable|string|max:1000'
        ]);
        $this->service->create($data);
        return back()->with('status', __('productsnew::product.batch_saved'));
    }

    public function show(ProductsNewBatch $batch)
    {
        $movements = $this->service->movements(['batch_id'=>$batch->id])->paginate(50);
        return view('productsnew::batch.show', compact('batch','movements'));
    }

    public function movement(Request $request, ProductsNewBatch $batch)
    {
        $data = $request->validate(['movement_type'=>'required|string|max:50','qty_in'=>'nullable|numeric|min:0','qty_out'=>'nullable|numeric|min:0','note'=>'nullable|string|max:500']);
        $this->service->movement($batch, $data['movement_type'], (float)($data['qty_in'] ?? 0), (float)($data['qty_out'] ?? 0), $data['note'] ?? null);
        return back()->with('status', __('productsnew::product.batch_movement_saved'));
    }
}

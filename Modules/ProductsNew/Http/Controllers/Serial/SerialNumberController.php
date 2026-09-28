<?php
namespace Modules\ProductsNew\Http\Controllers\Serial;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Entities\ProductsNewSerialNumber;
use Modules\ProductsNew\Services\ProductLookupService;
use Modules\ProductsNew\Services\Serial\SerialNumberService;

class SerialNumberController extends Controller
{
    public function __construct(protected SerialNumberService $service, protected ProductLookupService $lookup) {}

    public function index(Request $request)
    {
        $serials = $this->service->query($request->all())->paginate(50);
        $lookups = $this->lookup->formLookups();
        return view('productsnew::serial.index', compact('serials','lookups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'=>'required|integer','variation_id'=>'nullable|integer','location_id'=>'nullable|integer',
            'serial_no'=>'required|string|max:191','imei_no'=>'nullable|string|max:191','asset_tag'=>'nullable|string|max:191',
            'batch_id'=>'nullable|integer','purchase_reference'=>'nullable|string|max:191','purchase_date'=>'nullable|date',
            'cost_price'=>'nullable|numeric|min:0','selling_price'=>'nullable|numeric|min:0','status'=>'nullable|string|max:50','note'=>'nullable|string|max:1000'
        ]);
        $this->service->create($data);
        return back()->with('status', __('productsnew::product.serial_saved'));
    }

    public function show(ProductsNewSerialNumber $serial)
    {
        $movements = $this->service->movements($serial->id)->paginate(50);
        return view('productsnew::serial.show', compact('serial','movements'));
    }

    public function status(Request $request, ProductsNewSerialNumber $serial)
    {
        $data = $request->validate(['status'=>'required|string|max:50','location_id'=>'nullable|integer','note'=>'nullable|string|max:500']);
        $this->service->updateStatus($serial, $data['status'], $data['location_id'] ?? null, $data['note'] ?? null);
        return back()->with('status', __('productsnew::product.serial_status_updated'));
    }
}

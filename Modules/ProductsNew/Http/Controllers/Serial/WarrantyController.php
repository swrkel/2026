<?php
namespace Modules\ProductsNew\Http\Controllers\Serial;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Entities\ProductsNewWarrantyClaim;
use Modules\ProductsNew\Services\ProductLookupService;
use Modules\ProductsNew\Services\Serial\WarrantyService;

class WarrantyController extends Controller
{
    public function __construct(protected WarrantyService $service, protected ProductLookupService $lookup) {}

    public function index(Request $request)
    {
        $registrations = $this->service->registrations($request->all())->paginate(50);
        $claims = $this->service->claims($request->all())->paginate(25);
        $lookups = $this->lookup->formLookups();
        return view('productsnew::serial.warranty', compact('registrations','claims','lookups'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'product_id'=>'required|integer','serial_id'=>'nullable|integer','contact_id'=>'nullable|integer',
            'warranty_code'=>'nullable|string|max:191','warranty_start_date'=>'required|date','warranty_end_date'=>'required|date',
            'invoice_no'=>'nullable|string|max:191','sale_reference'=>'nullable|string|max:191','status'=>'nullable|string|max:50','terms'=>'nullable|string'
        ]);
        $this->service->register($data);
        return back()->with('status', __('productsnew::product.warranty_registered'));
    }

    public function claim(Request $request)
    {
        $data = $request->validate([
            'registration_id'=>'required|integer','claim_no'=>'nullable|string|max:191','claim_date'=>'required|date',
            'fault_description'=>'required|string','resolution_note'=>'nullable|string','claim_status'=>'nullable|string|max:50','claim_amount'=>'nullable|numeric|min:0'
        ]);
        $this->service->claim($data);
        return back()->with('status', __('productsnew::product.warranty_claim_saved'));
    }

    public function updateClaim(Request $request, ProductsNewWarrantyClaim $claim)
    {
        $data = $request->validate(['claim_status'=>'required|string|max:50','resolution_note'=>'nullable|string','claim_amount'=>'nullable|numeric|min:0']);
        $this->service->updateClaim($claim, $data);
        return back()->with('status', __('productsnew::product.warranty_claim_updated'));
    }
}

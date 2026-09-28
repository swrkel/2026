<?php
namespace Modules\ProductsNew\Http\Controllers\Serial;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Serial\OwnershipService;

class OwnershipController extends Controller
{
    public function __construct(protected OwnershipService $service) {}

    public function index(Request $request)
    {
        $histories = $this->service->query($request->all())->paginate(50);
        return view('productsnew::serial.ownership', compact('histories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'serial_id'=>'required|integer','contact_id'=>'nullable|integer','transaction_id'=>'nullable|integer',
            'owner_name'=>'nullable|string|max:191','owner_mobile'=>'nullable|string|max:50','ownership_type'=>'required|string|max:50',
            'started_at'=>'nullable|date','ended_at'=>'nullable|date','note'=>'nullable|string|max:1000'
        ]);
        $this->service->record($data);
        return back()->with('status', __('productsnew::product.ownership_saved'));
    }
}

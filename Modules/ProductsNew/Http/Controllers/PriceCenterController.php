<?php
namespace Modules\ProductsNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\InventoryMovementService;
use Modules\ProductsNew\Services\PriceCenterService;

class PriceCenterController extends Controller
{
    public function __construct(protected PriceCenterService $prices, protected InventoryMovementService $inventory) {}
    public function index(Request $request)
    {
        $rows = $this->prices->query($request->all())->paginate(50);
        return view('productsnew::price_center.index', [
            'rows'=>$rows, 'priceTypes'=>$this->prices->priceTypes(), 'locations'=>$this->inventory->locations(), 'products'=>$this->inventory->products()
        ]);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'=>'required|integer','variation_id'=>'nullable|integer','location_id'=>'nullable|integer',
            'price_type'=>'required|string|max:50','price'=>'required|numeric|min:0','currency'=>'nullable|string|max:10',
            'starts_at'=>'nullable|date','ends_at'=>'nullable|date','is_active'=>'nullable|boolean'
        ]);
        $this->prices->store($data);
        return back()->with('status','Price tier saved successfully.');
    }
}

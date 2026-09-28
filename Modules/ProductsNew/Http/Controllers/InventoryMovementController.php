<?php
namespace Modules\ProductsNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\InventoryMovementService;

class InventoryMovementController extends Controller
{
    public function __construct(protected InventoryMovementService $service) {}
    public function index(Request $request)
    {
        $rows = $this->service->query($request->all())->paginate(50);
        return view('productsnew::inventory_movements.index', [
            'rows' => $rows,
            'locations' => $this->service->locations(),
            'products' => $this->service->products(),
        ]);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|integer', 'variation_id' => 'nullable|integer', 'location_id' => 'nullable|integer',
            'movement_type' => 'required|string|max:50', 'qty' => 'required|numeric|min:0.001', 'unit_cost' => 'nullable|numeric|min:0',
            'movement_date' => 'nullable|date', 'reference_no' => 'nullable|string|max:100', 'notes' => 'nullable|string|max:1000',
        ]);
        $this->service->record($data);
        return redirect()->route('products-new.inventory-movements.index')->with('status','Inventory movement recorded successfully.');
    }
}

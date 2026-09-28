<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\POS\Services\POSInventoryService;

class ProductController extends Controller
{
    protected POSInventoryService $inventory;
    public function __construct(POSInventoryService $inventory) { $this->inventory = $inventory; }

    public function index(Request $request)
    {
        return view('pos::products.index', ['title' => 'POS Products', 'stats' => $this->inventory->dashboard(), 'products' => $this->inventory->listProducts($request->all())] + $this->inventory->lookups());
    }
    public function create() { return view('pos::products.create', ['title' => 'Add POS Product'] + $this->inventory->lookups()); }
    public function store(Request $request)
    {
        $data = $request->validate(['name'=>'required|string|max:191','sku'=>'nullable|string|max:100','barcode'=>'nullable|string|max:100','category_id'=>'nullable|integer','brand_id'=>'nullable|integer','unit_id'=>'nullable|integer','cost_price'=>'nullable|numeric','selling_price'=>'required|numeric','tax_rate'=>'nullable|numeric','current_stock'=>'nullable|numeric','alert_quantity'=>'nullable|numeric','description'=>'nullable|string','is_active'=>'nullable']);
        $this->inventory->storeProduct($data);
        return redirect()->route('pos.products.index')->with('status', 'Product saved successfully.');
    }
    public function edit($id)
    {
        $product = DB::table('pos_products')->where('id', $id)->whereNull('deleted_at')->first(); abort_if(!$product, 404);
        return view('pos::products.edit', ['title' => 'Edit POS Product', 'product' => $product] + $this->inventory->lookups());
    }
    public function update(Request $request, $id)
    {
        $data = $request->validate(['name'=>'required|string|max:191','sku'=>'nullable|string|max:100','barcode'=>'nullable|string|max:100','category_id'=>'nullable|integer','brand_id'=>'nullable|integer','unit_id'=>'nullable|integer','cost_price'=>'nullable|numeric','selling_price'=>'required|numeric','tax_rate'=>'nullable|numeric','current_stock'=>'nullable|numeric','alert_quantity'=>'nullable|numeric','description'=>'nullable|string','is_active'=>'nullable']);
        $this->inventory->updateProduct((int)$id, $data);
        return redirect()->route('pos.products.index')->with('status', 'Product updated successfully.');
    }
    public function destroy($id)
    {
        $this->inventory->deleteProduct((int)$id);
        return redirect()->route('pos.products.index')->with('status', 'Product removed successfully.');
    }
    public function stockAdjustForm()
    {
        $products = DB::table('pos_products')->whereNull('deleted_at')->where('is_active', 1)->orderBy('name')->get();
        return view('pos::products.stock_adjust', ['title' => 'POS Stock Adjustment', 'products' => $products]);
    }
    public function stockAdjust(Request $request)
    {
        $data = $request->validate(['product_id'=>'required|integer','movement_type'=>'required|string','quantity'=>'required|numeric|min:0.001','unit_cost'=>'nullable|numeric','note'=>'nullable|string|max:500']);
        $this->inventory->adjustStock($data);
        return redirect()->route('pos.products.index')->with('status', 'Stock adjustment saved successfully.');
    }
    public function movements(Request $request)
    {
        $movements = DB::table('pos_stock_movements as m')->join('pos_products as p','p.id','=','m.product_id')->select('m.*','p.name as product_name','p.sku')->orderByDesc('m.id')->paginate(30);
        return view('pos::products.movements', ['title' => 'POS Stock Movements', 'movements' => $movements]);
    }
}

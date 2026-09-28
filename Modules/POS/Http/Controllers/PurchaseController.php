<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\POS\Services\POSInventoryService;

class PurchaseController extends Controller
{
    protected POSInventoryService $inventory;
    public function __construct(POSInventoryService $inventory) { $this->inventory = $inventory; }

    public function index(Request $request)
    {
        return view('pos::purchases.index', [
            'title' => 'POS Purchase Stock',
            'purchases' => $this->inventory->listPurchases($request->all()),
            'stats' => $this->inventory->purchaseDashboard(),
        ]);
    }

    public function create()
    {
        return view('pos::purchases.create', [
            'title' => 'Add POS Purchase Stock',
            'products' => $this->inventory->activeProducts(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_name' => 'nullable|string|max:191',
            'reference_no' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'note' => 'nullable|string|max:500',
            'product_id' => 'required|array|min:1',
            'product_id.*' => 'required|integer',
            'quantity' => 'required|array|min:1',
            'quantity.*' => 'required|numeric|min:0.001',
            'unit_cost' => 'required|array|min:1',
            'unit_cost.*' => 'required|numeric|min:0',
        ]);

        $id = $this->inventory->storePurchase($data);
        return redirect()->route('pos.purchases.show', $id)->with('status', 'Purchase stock saved successfully.');
    }

    public function show($id)
    {
        return view('pos::purchases.show', $this->inventory->purchaseDetails((int)$id) + ['title' => 'POS Purchase Details']);
    }

    public function destroy($id)
    {
        $this->inventory->voidPurchase((int)$id);
        return redirect()->route('pos.purchases.index')->with('status', 'Purchase was voided and stock was reversed.');
    }
}

<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\POS\Services\POSInventoryService;

class InventorySetupController extends Controller
{
    protected POSInventoryService $inventory;
    protected array $map = [
        'categories' => 'pos_categories',
        'brands' => 'pos_brands',
        'units' => 'pos_units',
    ];

    public function __construct(POSInventoryService $inventory) { $this->inventory = $inventory; }

    public function index()
    {
        return view('pos::inventory_setup.index', ['title' => 'POS Inventory Setup'] + $this->inventory->lookups());
    }

    public function store(Request $request, string $type)
    {
        abort_unless(isset($this->map[$type]), 404);
        $data = $request->validate(['name' => 'required|string|max:191', 'short_name' => 'nullable|string|max:50']);
        $insert = ['name' => trim($data['name']), 'created_at' => now(), 'updated_at' => now()];
        if ($type === 'units') $insert['short_name'] = $data['short_name'] ?: trim($data['name']);
        DB::table($this->map[$type])->insert($insert);
        return redirect()->route('pos.inventory_setup.index')->with('status', ucfirst($type).' saved successfully.');
    }

    public function destroy(string $type, int $id)
    {
        abort_unless(isset($this->map[$type]), 404);
        DB::table($this->map[$type])->where('id', $id)->update(['deleted_at' => now(), 'updated_at' => now()]);
        return redirect()->route('pos.inventory_setup.index')->with('status', ucfirst($type).' removed successfully.');
    }

    public function barcodes(Request $request)
    {
        return view('pos::inventory_setup.barcodes', [
            'title' => 'POS Barcode Labels',
            'products' => $this->inventory->listProducts($request->all()),
        ]);
    }
}

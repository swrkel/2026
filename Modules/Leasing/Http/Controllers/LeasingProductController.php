<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Leasing\Models\LeaseAssetType;
use Modules\Leasing\Models\LeasingProduct;

class LeasingProductController extends Controller
{
    public function index()
    {
        $products = LeasingProduct::with('collateralType')->orderBy('name')->paginate(20);
        return view('leasing::products.index', compact('products'));
    }

    public function create()
    {
        $product = new LeasingProduct();
        $types = LeaseAssetType::orderBy('name')->pluck('name', 'id');
        return view('leasing::products.form', ['product' => $product, 'types' => $types, 'action' => route('leasing.products.store')]);
    }

    public function store(Request $request)
    {
        LeasingProduct::create($request->all());
        return redirect()->route('leasing.products.index')->with('status', ['success' => 1, 'msg' => 'Leasing product saved successfully']);
    }

    public function edit($id)
    {
        $product = LeasingProduct::findOrFail($id);
        $types = LeaseAssetType::orderBy('name')->pluck('name', 'id');
        return view('leasing::products.form', ['product' => $product, 'types' => $types, 'action' => route('leasing.products.update', $id)]);
    }

    public function update(Request $request, $id)
    {
        LeasingProduct::findOrFail($id)->update($request->all());
        return redirect()->route('leasing.products.index')->with('status', ['success' => 1, 'msg' => 'Leasing product updated successfully']);
    }
}

<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pawning\Models\CollateralType;
use Modules\Pawning\Models\PawningProduct;

class PawningProductController extends Controller
{
    public function index()
    {
        $products = PawningProduct::with('collateralType')->orderBy('name')->paginate(20);
        return view('pawning::products.index', compact('products'));
    }

    public function create()
    {
        $product = new PawningProduct();
        $types = CollateralType::orderBy('name')->pluck('name', 'id');
        return view('pawning::products.form', ['product' => $product, 'types' => $types, 'action' => route('pawning.products.store')]);
    }

    public function store(Request $request)
    {
        PawningProduct::create($request->all());
        return redirect()->route('pawning.products.index')->with('status', ['success' => 1, 'msg' => 'Pawning product saved successfully']);
    }

    public function edit($id)
    {
        $product = PawningProduct::findOrFail($id);
        $types = CollateralType::orderBy('name')->pluck('name', 'id');
        return view('pawning::products.form', ['product' => $product, 'types' => $types, 'action' => route('pawning.products.update', $id)]);
    }

    public function update(Request $request, $id)
    {
        PawningProduct::findOrFail($id)->update($request->all());
        return redirect()->route('pawning.products.index')->with('status', ['success' => 1, 'msg' => 'Pawning product updated successfully']);
    }
}

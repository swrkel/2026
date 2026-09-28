<?php
namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Entities\BeautyProduct;

class BeautyProductController extends Controller
{
    public function index() { $products = BeautyProduct::latest()->paginate(25); return view('beautysaloons::inventory.products.index', compact('products')); }
    public function create() { return view('beautysaloons::inventory.products.create'); }
    public function store(Request $request) { BeautyProduct::create($request->all()); return redirect()->route('beauty_saloons.inventory.products.index')->with('status', 'Product Added Successfully'); }
    public function edit($id) { $product = BeautyProduct::findOrFail($id); return view('beautysaloons::inventory.products.edit', compact('product')); }
    public function update(Request $request, $id) { BeautyProduct::findOrFail($id)->update($request->all()); return redirect()->route('beauty_saloons.inventory.products.index')->with('status', 'Product Updated Successfully'); }
    public function destroy($id) { BeautyProduct::findOrFail($id)->delete(); return back()->with('status', 'Product Deleted Successfully'); }
}

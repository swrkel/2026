<?php
namespace Modules\Product\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\ProductVariation;
use Modules\Product\Utils\ProductPermissionUtil;
use Yajra\DataTables\Facades\DataTables;
class ProductVariationController extends Controller
{
    public function __construct(private ProductPermissionUtil $permission) {}
    public function index(){ $this->permission->abortUnless('product.variation.view'); return view('product::settings.index', ['active_tab'=>'variations']); }
    public function create(){ $this->permission->abortUnless('product.variation.create'); return view('product::settings.index', ['active_tab'=>'variations']); }
    public function store(Request $request){ $this->permission->abortUnless('product.variation.create'); ProductVariation::create($request->except('_token')); return back()->with('status',['success'=>1,'msg'=>__('product::lang.saved_successfully')]); }
    public function edit(ProductVariation $variation){ $this->permission->abortUnless('product.variation.edit'); return view('product::settings.index', compact('variation') + ['active_tab'=>'variations']); }
    public function update(Request $request, ProductVariation $variation){ $this->permission->abortUnless('product.variation.edit'); $variation->update($request->except('_token','_method')); return back()->with('status',['success'=>1,'msg'=>__('product::lang.updated_successfully')]); }
    public function destroy(ProductVariation $variation){ $this->permission->abortUnless('product.variation.delete'); $variation->delete(); return response()->json(['success'=>true,'msg'=>__('product::lang.deleted_successfully')]); }
    public function datatable(){ $this->permission->abortUnless('product.variation.view'); return DataTables::of(ProductVariation::query())->make(true); }
}

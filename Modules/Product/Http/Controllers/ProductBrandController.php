<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\ProductBrand;
use Modules\Product\Services\ProductBrandService;
use Modules\Product\Utils\ProductPermissionUtil;
use Yajra\DataTables\Facades\DataTables;

class ProductBrandController extends Controller
{
    public function __construct(private ProductBrandService $service, private ProductPermissionUtil $permission) {}
    public function index() { $this->permission->abortUnless('product.brand.view'); return view('product::brands.index'); }
    public function create() { $this->permission->abortUnless('product.brand.create'); return view('product::brands.create'); }
    public function edit(ProductBrand $brand) { $this->permission->abortUnless('product.brand.edit'); return view('product::brands.edit', compact('brand')); }
    public function store(Request $request) { $this->permission->abortUnless('product.brand.create'); $this->service->create($request->except('_token')); return redirect()->route('product.brands.index')->with('status', ['success'=>1,'msg'=>__('product::lang.saved_successfully')]); }
    public function update(Request $request, ProductBrand $brand) { $this->permission->abortUnless('product.brand.edit'); $this->service->update($brand, $request->except('_token','_method')); return redirect()->route('product.brands.index')->with('status', ['success'=>1,'msg'=>__('product::lang.updated_successfully')]); }
    public function destroy(ProductBrand $brand) { $this->permission->abortUnless('product.brand.delete'); $this->service->delete($brand); return response()->json(['success'=>true,'msg'=>__('product::lang.deleted_successfully')]); }
    public function datatable() { $this->permission->abortUnless('product.brand.view'); return DataTables::of($this->service->query())->addColumn('action', fn($row)=>view('product::partials.simple_actions',['row'=>$row,'route'=>'brands'])->render())->rawColumns(['action'])->make(true); }
}

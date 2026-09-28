<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\ProductCategory;
use Modules\Product\Services\ProductCategoryService;
use Modules\Product\Utils\ProductPermissionUtil;
use Yajra\DataTables\Facades\DataTables;

class ProductCategoryController extends Controller
{
    public function __construct(private ProductCategoryService $service, private ProductPermissionUtil $permission) {}
    public function index() { $this->permission->abortUnless('product.category.view'); return view('product::categories.index'); }
    public function create() { $this->permission->abortUnless('product.category.create'); return view('product::categories.create'); }
    public function edit(ProductCategory $category) { $this->permission->abortUnless('product.category.edit'); return view('product::categories.edit', compact('category')); }
    public function store(Request $request) { $this->permission->abortUnless('product.category.create'); $this->service->create($request->except('_token')); return redirect()->route('product.categories.index')->with('status', ['success'=>1,'msg'=>__('product::lang.saved_successfully')]); }
    public function update(Request $request, ProductCategory $category) { $this->permission->abortUnless('product.category.edit'); $this->service->update($category, $request->except('_token','_method')); return redirect()->route('product.categories.index')->with('status', ['success'=>1,'msg'=>__('product::lang.updated_successfully')]); }
    public function destroy(ProductCategory $category) { $this->permission->abortUnless('product.category.delete'); $this->service->delete($category); return response()->json(['success'=>true,'msg'=>__('product::lang.deleted_successfully')]); }
    public function datatable() { $this->permission->abortUnless('product.category.view'); return DataTables::of($this->service->query())->addColumn('action', fn($row)=>view('product::partials.simple_actions',['row'=>$row,'route'=>'categories'])->render())->rawColumns(['action'])->make(true); }
}

<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\Product;
use Modules\Product\Services\ProductDataService;
use Modules\Product\Utils\ProductPermissionUtil;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function __construct(private ProductDataService $service, private ProductPermissionUtil $permission) {}

    public function index() { $this->permission->abortUnless('product.view'); return view('product::products.index'); }
    public function create() { $this->permission->abortUnless('product.create'); return view('product::products.create'); }
    public function show(Product $product) { $this->permission->abortUnless('product.view'); return view('product::products.show', compact('product')); }
    public function edit(Product $product) { $this->permission->abortUnless('product.edit'); return view('product::products.edit', compact('product')); }

    public function store(Request $request)
    {
        $this->permission->abortUnless('product.create');
        $this->service->create($request->except(['_token']));
        return redirect()->route('product.index')->with('status', ['success' => 1, 'msg' => __('product::lang.saved_successfully')]);
    }

    public function update(Request $request, Product $product)
    {
        $this->permission->abortUnless('product.edit');
        $this->service->update($product, $request->except(['_token', '_method']));
        return redirect()->route('product.index')->with('status', ['success' => 1, 'msg' => __('product::lang.updated_successfully')]);
    }

    public function destroy(Product $product)
    {
        $this->permission->abortUnless('product.delete');
        $this->service->delete($product);
        return response()->json(['success' => true, 'msg' => __('product::lang.deleted_successfully')]);
    }

    public function datatable()
    {
        $this->permission->abortUnless('product.view');
        return DataTables::of($this->service->query()->with(['category', 'brand', 'unit']))
            ->addColumn('action', fn($row) => view('product::partials.actions', ['row' => $row])->render())
            ->editColumn('enable_stock', fn($row) => $row->enable_stock ? __('product::lang.yes') : __('product::lang.no'))
            ->rawColumns(['action'])
            ->make(true);
    }
}

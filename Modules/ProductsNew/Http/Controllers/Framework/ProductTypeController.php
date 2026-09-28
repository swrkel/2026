<?php
namespace Modules\ProductsNew\Http\Controllers\Framework;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Framework\ProductTypeService;
use Modules\ProductsNew\Http\Requests\Framework\StoreProductTypeRequest;

class ProductTypeController extends Controller
{
    public function __construct(protected ProductTypeService $service) {}
    public function index(){ $types=$this->service->list(); return view('productsnew::framework.product_types.index', compact('types')); }
    public function store(StoreProductTypeRequest $request){ $this->service->store($request->validated()); return back()->with('status', __('productsnew::lang.product_type_saved')); }
    public function show(int $id){ $type=$this->service->find($id); $fields=$this->service->fields($id); return view('productsnew::framework.product_types.show', compact('type','fields')); }
}

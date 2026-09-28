<?php
namespace Modules\ProductsNew\Http\Controllers\Framework;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Framework\CustomFieldService;
use Modules\ProductsNew\Http\Requests\Framework\StoreCustomFieldRequest;

class CustomFieldController extends Controller
{
    public function __construct(protected CustomFieldService $service) {}
    public function index(){ $fields=$this->service->list(); return view('productsnew::framework.custom_fields.index', compact('fields')); }
    public function store(StoreCustomFieldRequest $request){ $this->service->store($request->validated()); return back()->with('status', __('productsnew::lang.custom_field_saved')); }
    public function values(int $productId){ return response()->json($this->service->values($productId)); }
    public function saveValues(int $productId){ $this->service->saveValues($productId, request()->input('fields', [])); return response()->json(['saved'=>true]); }
}

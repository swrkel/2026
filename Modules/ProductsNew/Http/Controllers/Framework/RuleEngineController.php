<?php
namespace Modules\ProductsNew\Http\Controllers\Framework;

use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Rules\ProductRuleEngineService;

class RuleEngineController extends Controller
{
    public function __construct(protected ProductRuleEngineService $service) {}
    public function index(){ $rules=$this->service->rules(); return view('productsnew::framework.rules.index', compact('rules')); }
    public function store(){ $this->service->store(request()->all()); return back()->with('status', __('productsnew::lang.rule_saved')); }
    public function validateProduct(int $productId){ return response()->json($this->service->validateProduct($productId)); }
}

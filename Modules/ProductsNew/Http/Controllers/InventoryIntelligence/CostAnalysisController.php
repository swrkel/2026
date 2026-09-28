<?php
namespace Modules\ProductsNew\Http\Controllers\InventoryIntelligence;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\ProductsNew\Services\InventoryIntelligence\ProductCostEngineService;
class CostAnalysisController extends Controller
{
    protected ProductCostEngineService $service;
    public function __construct(ProductCostEngineService $service) { $this->service=$service; }
    public function index(Request $request) { $filters=$request->all(); $summary=method_exists($this->service,'dashboard')?$this->service->dashboard($filters):$this->service->summary($filters); $rows=$this->service->snapshots($filters); return view('productsnew::inventory_intelligence.cost_analysis', compact('summary','rows','filters')); }
    public function store(Request $request) { $this->service->captureSnapshot($request->all()); return redirect()->back()->with('status','Saved successfully.'); }
}

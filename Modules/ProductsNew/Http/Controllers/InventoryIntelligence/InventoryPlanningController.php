<?php
namespace Modules\ProductsNew\Http\Controllers\InventoryIntelligence;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\ProductsNew\Services\InventoryIntelligence\InventoryPlanningService;
class InventoryPlanningController extends Controller
{
    protected InventoryPlanningService $service;
    public function __construct(InventoryPlanningService $service) { $this->service=$service; }
    public function index(Request $request) { $filters=$request->all(); $summary=method_exists($this->service,'dashboard')?$this->service->dashboard($filters):$this->service->summary($filters); $rows=$this->service->listProfiles($filters); return view('productsnew::inventory_intelligence.planning', compact('summary','rows','filters')); }
    public function store(Request $request) { $this->service->saveProfile($request->all()); return redirect()->back()->with('status','Saved successfully.'); }

    public function buildProposals(Request $request) { $created=$this->service->buildReorderProposals($request->all()); return redirect()->back()->with('status',$created.' reorder proposal(s) generated.'); }
}

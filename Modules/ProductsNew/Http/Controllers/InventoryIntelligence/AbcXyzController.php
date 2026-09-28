<?php
namespace Modules\ProductsNew\Http\Controllers\InventoryIntelligence;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\ProductsNew\Services\InventoryIntelligence\AbcXyzClassificationService;
class AbcXyzController extends Controller
{
    protected AbcXyzClassificationService $service;
    public function __construct(AbcXyzClassificationService $service) { $this->service=$service; }
    public function index(Request $request) { $filters=$request->all(); $summary=method_exists($this->service,'dashboard')?$this->service->dashboard($filters):$this->service->summary($filters); $rows=$this->service->classifications($filters); return view('productsnew::inventory_intelligence.abc_xyz', compact('summary','rows','filters')); }
    public function store(Request $request) { $this->service->classify($request->all()); return redirect()->back()->with('status','Saved successfully.'); }
}

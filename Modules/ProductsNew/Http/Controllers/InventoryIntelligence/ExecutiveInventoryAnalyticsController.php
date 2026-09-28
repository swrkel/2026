<?php
namespace Modules\ProductsNew\Http\Controllers\InventoryIntelligence;
use App\Http\Controllers\Controller; use Illuminate\Http\Request; use Modules\ProductsNew\Services\InventoryIntelligence\ExecutiveInventoryAnalyticsService;
class ExecutiveInventoryAnalyticsController extends Controller
{
    protected ExecutiveInventoryAnalyticsService $service;
    public function __construct(ExecutiveInventoryAnalyticsService $service) { $this->service=$service; }
    public function index(Request $request) { $filters=$request->all(); $summary=$this->service->dashboard($filters); $rows=collect(); return view('productsnew::inventory_intelligence.executive', compact('summary','rows','filters')); }
}

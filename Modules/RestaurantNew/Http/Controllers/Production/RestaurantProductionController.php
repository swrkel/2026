<?php

namespace Modules\RestaurantNew\Http\Controllers\Production;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewBatchProduction;
use Modules\RestaurantNew\Entities\RestaurantNewProductionPlan;
use Modules\RestaurantNew\Services\Production\RestaurantProductionService;

class RestaurantProductionController extends Controller
{
    public function __construct(private RestaurantProductionService $productionService) {}

    public function dashboard(Request $request)
    {
        $businessId = (int) session('business.id');
        $locationId = $request->get('location_id');
        $summary = $this->productionService->dashboard($businessId, $locationId ? (int)$locationId : null);
        return view('restaurantnew::production.dashboard', compact('summary'));
    }

    public function plans(Request $request)
    {
        $businessId = (int) session('business.id');
        $plans = RestaurantNewProductionPlan::where('business_id', $businessId)->latest('plan_date')->paginate(25);
        return view('restaurantnew::production.plans.index', compact('plans'));
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'plan_no' => 'required|string|max:60',
            'plan_date' => 'required|date',
            'location_id' => 'nullable|integer',
            'production_type' => 'nullable|string|max:40',
            'note' => 'nullable|string',
        ]);
        $data['business_id'] = (int) session('business.id');
        $data['created_by'] = optional(auth()->user())->id;
        $this->productionService->createPlan($data, $request->get('items', []));
        return redirect()->back()->with('status', __('restaurantnew::production.plan_created'));
    }

    public function approvePlan(RestaurantNewProductionPlan $plan)
    {
        $this->productionService->approvePlan($plan, (int) optional(auth()->user())->id);
        return redirect()->back()->with('status', __('restaurantnew::production.plan_approved'));
    }

    public function batches(Request $request)
    {
        $businessId = (int) session('business.id');
        $batches = RestaurantNewBatchProduction::where('business_id', $businessId)->latest()->paginate(25);
        return view('restaurantnew::production.batches.index', compact('batches'));
    }

    public function storeBatch(Request $request)
    {
        $data = $request->validate([
            'batch_no' => 'required|string|max:80',
            'location_id' => 'nullable|integer',
            'production_plan_id' => 'nullable|integer',
            'input_cost' => 'nullable|numeric',
        ]);
        $data['business_id'] = (int) session('business.id');
        $data['created_by'] = optional(auth()->user())->id;
        $this->productionService->startBatch($data);
        return redirect()->back()->with('status', __('restaurantnew::production.batch_started'));
    }

    public function completeBatch(Request $request, RestaurantNewBatchProduction $batch)
    {
        $this->productionService->completeBatch($batch, $request->all());
        return redirect()->back()->with('status', __('restaurantnew::production.batch_completed'));
    }
}

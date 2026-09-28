<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Services\Loading\DisnewLoadingPlanService;
use Modules\DistributionNew\Utils\DisnewTenantUtil;

class LoadingPlanController extends Controller
{
    public function index()
    {
        $plans = DB::table('disnew_loading_plans')
            ->where('business_id', DisnewTenantUtil::businessId())
            ->orderByDesc('id')
            ->paginate(25);
        return view('distributionnew::loading_plans.index', compact('plans'));
    }

    public function create()
    {
        return view('distributionnew::loading_plans.create');
    }

    public function store(Request $request, DisnewLoadingPlanService $service)
    {
        $planId = $service->create($request->all(), (array) $request->input('lines', []));
        return redirect()->route('distributionnew.loading-plans.show', $planId)->with('status', 'Loading plan created successfully.');
    }

    public function show($id)
    {
        $plan = DB::table('disnew_loading_plans')->where('id', $id)->first();
        $lines = DB::table('disnew_loading_plan_lines')->where('loading_plan_id', $id)->get();
        return view('distributionnew::loading_plans.show', compact('plan', 'lines'));
    }

    public function approve($id, DisnewLoadingPlanService $service)
    {
        $service->approve((int) $id);
        return redirect()->back()->with('status', 'Loading plan approved.');
    }

    public function convert($id, DisnewLoadingPlanService $service)
    {
        $loadingId = $service->convertToLoading((int) $id);
        return redirect()->route('distributionnew.loading.show', $loadingId)->with('status', 'Loading plan converted to loading.');
    }
}

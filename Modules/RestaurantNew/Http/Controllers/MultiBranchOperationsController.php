<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewBranchTransfer;
use Modules\RestaurantNew\Services\MultiBranchOperationsService;

class MultiBranchOperationsController extends Controller
{
    protected MultiBranchOperationsService $service;

    public function __construct(MultiBranchOperationsService $service)
    {
        $this->service = $service;
    }

    public function dashboard()
    {
        return view('restaurantnew::multi_branch.dashboard');
    }

    public function transfers()
    {
        return view('restaurantnew::multi_branch.transfers');
    }

    public function comparison(Request $request)
    {
        $summary = $this->service->comparisonSummary($request->all());
        return view('restaurantnew::multi_branch.comparison', compact('summary'));
    }

    public function approve(RestaurantNewBranchTransfer $transfer)
    {
        $this->service->approveTransfer($transfer, auth()->id());
        return response()->json(['success' => true, 'message' => __('restaurantnew::multi_branch.transfer_approved')]);
    }

    public function dispatch(RestaurantNewBranchTransfer $transfer)
    {
        $this->service->markDispatched($transfer, auth()->id());
        return response()->json(['success' => true, 'message' => __('restaurantnew::multi_branch.transfer_dispatched')]);
    }

    public function receive(RestaurantNewBranchTransfer $transfer)
    {
        $this->service->markReceived($transfer, auth()->id());
        return response()->json(['success' => true, 'message' => __('restaurantnew::multi_branch.transfer_received')]);
    }
}

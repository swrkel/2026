<?php

namespace Modules\DistributionNew\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DistributionNew\Services\CustomerPortal\CustomerComplaintPortalService;
use Modules\DistributionNew\Services\CustomerPortal\CustomerOrderPortalService;
use Modules\DistributionNew\Services\CustomerPortal\CustomerReturnPortalService;
use Modules\DistributionNew\Services\CustomerPortal\CustomerSelfServiceDashboardService;

class CustomerSelfServiceApiController extends Controller
{
    public function dashboard(Request $request, CustomerSelfServiceDashboardService $service)
    {
        return response()->json($service->summary($request->session()->get('business.id'), (int)$request->user()->id));
    }

    public function orders() { return response()->json(['data' => []]); }
    public function invoices() { return response()->json(['data' => []]); }
    public function deliveries() { return response()->json(['data' => []]); }

    public function storeOrder(Request $request, CustomerOrderPortalService $service)
    {
        $id = $service->createOrder($request->all(), $request->session()->get('business.id'), (int)$request->user()->id, (int)$request->user()->id);
        return response()->json(['id' => $id, 'status' => 'created']);
    }

    public function storeReturnRequest(Request $request, CustomerReturnPortalService $service)
    {
        $id = $service->createReturnRequest($request->all(), $request->session()->get('business.id'), (int)$request->user()->id, (int)$request->user()->id);
        return response()->json(['id' => $id, 'status' => 'submitted']);
    }

    public function storeComplaint(Request $request, CustomerComplaintPortalService $service)
    {
        $id = $service->createComplaint($request->all(), $request->session()->get('business.id'), (int)$request->user()->id);
        return response()->json(['id' => $id, 'status' => 'open']);
    }
}

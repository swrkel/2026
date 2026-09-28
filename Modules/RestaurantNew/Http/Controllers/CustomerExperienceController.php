<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewTableServiceRequest;
use Modules\RestaurantNew\Entities\RestaurantNewCustomerOrderTracking;
use Modules\RestaurantNew\Services\CustomerExperienceService;

class CustomerExperienceController extends Controller
{
    protected CustomerExperienceService $service;

    public function __construct(CustomerExperienceService $service)
    {
        $this->service = $service;
    }

    public function requests(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $requests = RestaurantNewTableServiceRequest::where('business_id', $businessId)
            ->when($request->location_id, fn($q) => $q->where('location_id', $request->location_id))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest('id')
            ->paginate(25);

        return view('restaurantnew::customer_experience.requests', compact('requests'));
    }

    public function storeRequest(Request $request)
    {
        $businessId = $request->session()->get('user.business_id');
        $data = $request->validate([
            'location_id' => 'nullable|integer',
            'table_id' => 'nullable|integer',
            'order_id' => 'nullable|integer',
            'request_type' => 'required|string|max:50',
            'customer_note' => 'nullable|string',
        ]);
        $data['business_id'] = $businessId;

        $serviceRequest = $this->service->openRequest($data);

        return response()->json(['success' => true, 'data' => $serviceRequest]);
    }

    public function updateRequestStatus(Request $request, $id)
    {
        $businessId = $request->session()->get('user.business_id');
        $serviceRequest = RestaurantNewTableServiceRequest::where('business_id', $businessId)->findOrFail($id);
        $request->validate(['status' => 'required|string|max:40', 'remarks' => 'nullable|string']);

        return response()->json([
            'success' => true,
            'data' => $this->service->changeRequestStatus($serviceRequest, $request->status, $request->remarks),
        ]);
    }

    public function publicTracking($token)
    {
        $tracking = RestaurantNewCustomerOrderTracking::where('tracking_token', $token)->where('is_active', 1)->firstOrFail();
        return view('restaurantnew::customer_experience.public_tracking', compact('tracking'));
    }
}

<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Services\RestaurantCrmService;
use Modules\RestaurantNew\Entities\RestaurantNewCustomerProfile;
use Modules\RestaurantNew\Entities\RestaurantNewCrmFeedbackCase;

class RestaurantCrmController extends Controller
{
    protected RestaurantCrmService $crm;

    public function __construct(RestaurantCrmService $crm)
    {
        $this->crm = $crm;
    }

    public function dashboard(Request $request)
    {
        $businessId = (int) session('business.id');
        $locationId = $request->get('location_id');
        $summary = $this->crm->dashboard($businessId, $locationId ? (int)$locationId : null);
        $segments = $this->crm->segments($businessId, $locationId ? (int)$locationId : null);
        return view('restaurantnew::crm.dashboard', compact('summary', 'segments'));
    }

    public function customers(Request $request)
    {
        $businessId = (int) session('business.id');
        $customers = RestaurantNewCustomerProfile::where('business_id', $businessId)
            ->when($request->get('q'), fn($q) => $q->where(function ($x) use ($request) {
                $term = '%'.$request->get('q').'%';
                $x->where('customer_name', 'like', $term)->orWhere('mobile', 'like', $term)->orWhere('customer_code', 'like', $term);
            }))
            ->latest('last_visit_at')
            ->paginate(25);
        return view('restaurantnew::crm.customers', compact('customers'));
    }

    public function showCustomer(int $id)
    {
        $data = $this->crm->customer360($id);
        return view('restaurantnew::crm.customer_360', $data);
    }

    public function feedback(Request $request)
    {
        $businessId = (int) session('business.id');
        $cases = RestaurantNewCrmFeedbackCase::where('business_id', $businessId)->latest()->paginate(25);
        return view('restaurantnew::crm.feedback', compact('cases'));
    }

    public function campaigns()
    {
        return view('restaurantnew::crm.campaigns');
    }
}

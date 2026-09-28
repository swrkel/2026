<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Services\RestaurantAdministrationService;
use Modules\RestaurantNew\Entities\RestaurantNewCateringOrder;

class CateringController extends Controller
{
    protected RestaurantAdministrationService $service;

    public function __construct(RestaurantAdministrationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $records = RestaurantNewCateringOrder::query()
            ->where('business_id', $request->session()->get('user.business_id'))
            ->latest('id')
            ->paginate(25);

        return view('restaurantnew::admin.catering-orders', compact('records'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = $request->session()->get('user.business_id');
        $data['created_by'] = optional($request->user())->id;
        RestaurantNewCateringOrder::create($data);

        return redirect()->back()->with('status', __('restaurantnew::lang.saved_successfully'));
    }
}

<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Services\RestaurantAdministrationService;
use Modules\RestaurantNew\Entities\RestaurantNewComboMeal;

class ComboMealController extends Controller
{
    protected RestaurantAdministrationService $service;

    public function __construct(RestaurantAdministrationService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $records = RestaurantNewComboMeal::query()
            ->where('business_id', $request->session()->get('user.business_id'))
            ->latest('id')
            ->paginate(25);

        return view('restaurantnew::admin.combo-meals', compact('records'));
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = $request->session()->get('user.business_id');
        $data['created_by'] = optional($request->user())->id;
        RestaurantNewComboMeal::create($data);

        return redirect()->back()->with('status', __('restaurantnew::lang.saved_successfully'));
    }
}

<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenRouteRule;

class KitchenRoutingController extends Controller
{
    public function index()
    {
        $rules = RestaurantNewKitchenRouteRule::where('business_id', session('business.id'))->latest()->paginate(25);
        return view('restaurantnew::kitchen.routing', compact('rules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'location_id' => 'required|integer',
            'menu_category_id' => 'nullable|integer',
            'menu_item_id' => 'nullable|integer',
            'kitchen_section_id' => 'required|integer',
            'order_type' => 'nullable|string|max:30',
            'priority' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);
        $data['business_id'] = session('business.id');
        $data['created_by'] = auth()->id();
        RestaurantNewKitchenRouteRule::create($data);
        return back()->with('status', __('restaurantnew::lang.saved_successfully'));
    }
}

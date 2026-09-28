<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Entities\RestaurantNewTableOperation;

class TableOperationController extends Controller
{
    public function index(Request $request)
    {
        $businessId = session('business.id');
        $operations = RestaurantNewTableOperation::with('order')
            ->where('business_id', $businessId)
            ->latest()
            ->paginate(50);

        return view('restaurantnew::tables.operations', compact('operations'));
    }
}

<?php

namespace Modules\RestaurantNew\Http\Controllers\Api;

use Illuminate\Routing\Controller;

class RestaurantNewLookupController extends Controller
{
    public function tables()
    {
        return response()->json(['data' => []]);
    }

    public function menuItems()
    {
        return response()->json(['data' => []]);
    }
}

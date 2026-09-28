<?php

namespace Modules\DistributionNew\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Utils\DisnewTenantUtil;

class StoreVehicleStockController extends Controller
{
    public function index()
    {
        $rows = DB::table('disnew_vehicle_store_stocks')
            ->where('business_id', DisnewTenantUtil::businessId())
            ->orderBy('store_id')
            ->orderBy('vehicle_id')
            ->orderBy('product_id')
            ->paginate(50);
        return view('distributionnew::store_vehicle_stock.index', compact('rows'));
    }
}

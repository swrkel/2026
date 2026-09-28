<?php
namespace Modules\EggManagement\Http\Controllers;

use Illuminate\Http\Request;
use Modules\EggManagement\Services\DashboardService;
use Modules\EggManagement\Utilities\DateRange;
use Modules\EggManagement\Integrations\LocationStoreGateway;

class DashboardController extends BaseController
{
    public function index(Request $request, DashboardService $dashboard, LocationStoreGateway $directory)
    {
        [$from, $to] = DateRange::resolve($request);
        $location = $request->input('location_id', $this->context->locationId() ?: 'all');
        $store = $request->input('store_id', $this->context->storeId() ?: 'all');

        return view('egg::dashboard.index', [
            'metrics' => $dashboard->metrics($from, $to, $location, $store),
            'recentActivity' => $dashboard->recentActivity($from, $to, $location, $store),
            'from' => $from,
            'to' => $to,
            'locations' => $directory->locations(),
            'stores' => $directory->stores(),
            'selectedLocation' => $location,
            'selectedStore' => $store,
        ]);
    }
}

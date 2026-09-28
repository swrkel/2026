<?php

namespace Modules\Distribution\Http\Controllers;

use \Modules\Distribution\Entities\Core\Unit;
use Modules\Distribution\Utils\ModuleUtil;
use Modules\Distribution\Utils\ProductUtil;
use Modules\Distribution\Utils\TransactionUtil;
use Modules\Distribution\Utils\Util;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Distribution\Entities\Distribution_areas;
use Modules\Distribution\Entities\Distribution_districts;
use Modules\Distribution\Entities\Distribution_provinces;

class SettingController extends Controller
{
    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->moduleUtil =  $moduleUtil;
        $this->productUtil =  $productUtil;
        $this->transactionUtil =  $transactionUtil;
    }


    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        // DIST-328: Distribution Settings page must render all tab contents with
        // their own Add buttons and table headers. Do not depend only on lazy JS
        // loading, because a JS/ajax failure leaves every tab blank.
        $provinces = Distribution_provinces::where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');

        $districts = Distribution_districts::leftJoin('distribution_provinces', 'distribution_districts.province_id', 'distribution_provinces.id')
            ->where('distribution_provinces.business_id', $business_id)
            ->orderBy('distribution_districts.name')
            ->pluck('distribution_districts.name', 'distribution_districts.id');

        $units = Unit::where('business_id', $business_id)
            ->pluck('actual_name', 'id');

        $business = \App\Business::select('id', 'currency_precision', 'quantity_precision')->find($business_id);
        $currency_precision = !empty($business->currency_precision) ? $business->currency_precision : 2;
        $quantity_precision = !empty($business->quantity_precision) ? $business->quantity_precision : 2;

        $products = \Modules\Distribution\Entities\Core\Product::where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');

        $categories = \Modules\Distribution\Entities\Core\Category::where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');

        $vehicle_types = \Modules\Distribution\Entities\DistributionVehicles::where('business_id', $business_id)
            ->whereNotNull('vehicle_type')
            ->where('vehicle_type', '!=', '')
            ->distinct()
            ->orderBy('vehicle_type')
            ->pluck('vehicle_type', 'vehicle_type');

        $maps = \Modules\Distribution\Entities\DistributionRouteUserMap::query()
            ->leftJoin('distribution_routes', 'distribution_route_user_maps.route_id', '=', 'distribution_routes.id')
            ->leftJoin('distribution_sales_agents', 'distribution_route_user_maps.sales_rep_id', '=', 'distribution_sales_agents.id')
            ->leftJoin('users as added_users', 'distribution_route_user_maps.added_by', '=', 'added_users.id')
            ->leftJoin('users as updated_users', 'distribution_route_user_maps.updated_by', '=', 'updated_users.id')
            ->leftJoin('users as status_users', 'distribution_route_user_maps.status_changed_by', '=', 'status_users.id')
            ->where('distribution_route_user_maps.business_id', $business_id)
            ->select(
                'distribution_route_user_maps.*',
                'distribution_routes.name as route_name',
                'distribution_sales_agents.name as sales_rep_name',
                'added_users.username as added_by_user',
                'updated_users.username as changed_user_name',
                'status_users.username as status_changed_user_name'
            )
            ->orderByDesc('distribution_route_user_maps.id')
            ->get();

        $grouped_maps = $maps->groupBy('sales_rep_id')->map(function ($group) {
            $latest = $group->sortByDesc('updated_at')->first();
            return (object) [
                'sales_rep_id' => $latest->sales_rep_id,
                'sales_rep_name' => $latest->sales_rep_name,
                'route_ids' => $group->pluck('route_id')->unique()->values()->all(),
                'routes_mapped' => $group->pluck('route_name')->filter()->unique()->values()->all(),
                'status' => $group->contains(function ($item) {
                    return $item->status === 'active';
                }) ? 'active' : 'inactive',
                'created_at' => $group->min('created_at'),
                'updated_at' => $group->max('updated_at'),
                'added_by_user' => $latest->added_by_user,
                'changed_user_name' => $latest->changed_user_name,
                'status_changed_user_name' => $latest->status_changed_user_name,
                'last_status_from' => $latest->last_status_from,
                'last_status_to' => $latest->last_status_to,
                'status_changed_at' => $latest->status_changed_at,
            ];
        })->values();

        $sales_reps = \App\SalesAgent::forBusiness($business_id)->orderBy('name')->pluck('name', 'id');
        $routes = \Illuminate\Support\Facades\DB::table('distribution_routes')
            ->where('business_id', $business_id)
            ->orderBy('name')
            ->pluck('name', 'id');
        $business_locations = \App\BusinessLocation::forDropdown($business_id, false);
        $default_location_id = array_key_first($business_locations->toArray());

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all' && !empty($permitted_locations)) {
            $default_location_id = $permitted_locations[0] ?? $default_location_id;
        }

        return view('distribution::settings.index')->with(compact(
            'provinces',
            'districts',
            'units',
            'currency_precision',
            'quantity_precision',
            'products',
            'categories',
            'vehicle_types',
            'maps',
            'grouped_maps',
            'sales_reps',
            'routes',
            'business_locations',
            'default_location_id'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('distribution::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('distribution::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('distribution::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function loadTab($tab)
    {
        $business_id = request()->session()->get('user.business_id');
    switch($tab) {
        case 'provinces':
            $provinces = Distribution_provinces::where('business_id', $business_id)->pluck('name', 'id');
            return view('distribution::settings.provinces.index', compact('provinces'));
        case 'districts':
            $provinces = Distribution_provinces::where('business_id', $business_id)
                ->orderBy('name')
                ->pluck('name', 'id');
            return view('distribution::settings.districts.index', compact('provinces'));
        case 'areas':
            $provinces = Distribution_provinces::where('business_id', $business_id)
                ->orderBy('name')
                ->pluck('name', 'id');
            $districts = Distribution_districts::leftJoin('distribution_provinces', 'distribution_districts.province_id', 'distribution_provinces.id')
                ->where('distribution_provinces.business_id', $business_id)
                ->orderBy('distribution_districts.name')
                ->pluck('distribution_districts.name', 'distribution_districts.id');
            return view('distribution::settings.areas.index', compact('provinces', 'districts'));
        case 'routes':
            $provinces = Distribution_provinces::where('business_id', $business_id)
                ->orderBy('name')
                ->pluck('name', 'id');
            return view('distribution::settings.routes.index', compact('provinces'));
        case 'user_routes':
            $maps = \Modules\Distribution\Entities\DistributionRouteUserMap::query()
                ->leftJoin('distribution_routes', 'distribution_route_user_maps.route_id', '=', 'distribution_routes.id')
                ->leftJoin('distribution_sales_agents', 'distribution_route_user_maps.sales_rep_id', '=', 'distribution_sales_agents.id')
                ->leftJoin('users as added_users', 'distribution_route_user_maps.added_by', '=', 'added_users.id')
                ->leftJoin('users as updated_users', 'distribution_route_user_maps.updated_by', '=', 'updated_users.id')
                ->leftJoin('users as status_users', 'distribution_route_user_maps.status_changed_by', '=', 'status_users.id')
                ->where('distribution_route_user_maps.business_id', $business_id)
                ->select(
                    'distribution_route_user_maps.*',
                    'distribution_routes.name as route_name',
                    'distribution_sales_agents.name as sales_rep_name',
                    'added_users.username as added_by_user',
                    'updated_users.username as changed_user_name',
                    'status_users.username as status_changed_user_name'
                )
                ->orderByDesc('distribution_route_user_maps.id')
                ->get();

            $grouped_maps = $maps->groupBy('sales_rep_id')->map(function ($group) {
                $latest = $group->sortByDesc('updated_at')->first();
                return (object) [
                    'sales_rep_id' => $latest->sales_rep_id,
                    'sales_rep_name' => $latest->sales_rep_name,
                    'route_ids' => $group->pluck('route_id')->unique()->values()->all(),
                    'routes_mapped' => $group->pluck('route_name')->filter()->unique()->values()->all(),
                    'status' => $group->contains(function ($item) {
                        return $item->status === 'active';
                    }) ? 'active' : 'inactive',
                    'created_at' => $group->min('created_at'),
                    'updated_at' => $group->max('updated_at'),
                    'added_by_user' => $latest->added_by_user,
                    'changed_user_name' => $latest->changed_user_name,
                    'status_changed_user_name' => $latest->status_changed_user_name,
                    'last_status_from' => $latest->last_status_from,
                    'last_status_to' => $latest->last_status_to,
                    'status_changed_at' => $latest->status_changed_at,
                ];
            })->values();

            $sales_reps = \App\SalesAgent::forBusiness($business_id)->orderBy('name')->pluck('name', 'id');
            $routes = \Illuminate\Support\Facades\DB::table('distribution_routes')->where('business_id', $business_id)->orderBy('name')->pluck('name', 'id');
            $business_locations = \App\BusinessLocation::forDropdown($business_id, false);
            $default_location_id = array_key_first($business_locations->toArray());

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all' && !empty($permitted_locations)) {
                $default_location_id = $permitted_locations[0] ?? $default_location_id;
            }

            return view('distribution::settings.routes.user_maps.index', compact(
                'maps',
                'grouped_maps',
                'sales_reps',
                'routes',
                'business_locations',
                'default_location_id'
            ));
        case 'prefix':
            return view('distribution::settings.prefix.index');
        case 'vehicles':
            return view('distribution::settings.vehicles.index');
        case 'discounts':
            $units = Unit::where('business_id', $business_id)->pluck('actual_name', 'id');
            $business = \App\Business::find($business_id);
            $currency_precision = !empty($business->currency_precision) ? $business->currency_precision : 2;
            $quantity_precision = !empty($business->quantity_precision) ? $business->quantity_precision : 2;
            return view('distribution::settings.discounts.index', compact('units', 'currency_precision', 'quantity_precision'));
        case 'free_issue':
            $products = \Modules\Distribution\Entities\Core\Product::where('business_id', $business_id)->pluck('name', 'id');
            $categories = \Modules\Distribution\Entities\Core\Category::where('business_id', $business_id)->pluck('name', 'id');
            return view('distribution::settings.free_issue.index', compact('products', 'categories'));
        default:
            return response()->json(['error' => 'Tab not found'], 404);
    }
}
}

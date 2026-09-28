<?php

namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Modules\Distribution\Entities\Core\SalesAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Distribution\Entities\DistributionRouteUserMap;

class DistributionRouteUserMapController extends Controller
{
    public function index()
    {
        $business_id = session()->get('user.business_id');
        $maps = DistributionRouteUserMap::query()
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

        $sales_reps = SalesAgent::forBusiness($business_id)->orderBy('name')->pluck('name', 'id');
        $routes = DB::table('distribution_routes')->where('business_id', $business_id)->orderBy('name')->pluck('name', 'id');

        $business_locations = \Modules\Distribution\Entities\Core\BusinessLocation::forDropdown($business_id, false);
        $default_location_id = array_key_first($business_locations->toArray());
        
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all' && !empty($permitted_locations)) {
            $default_location_id = $permitted_locations[0] ?? $default_location_id;
        }

        return view('distribution::settings.routes.user_maps.index', compact('maps', 'grouped_maps', 'sales_reps', 'routes', 'business_locations', 'default_location_id'));
    }

    public function store(Request $request)
    {
        $business_id = session()->get('user.business_id');
        $request->validate([
            'sales_rep_id' => 'required|integer',
            'route_ids' => 'required|array|min:1',
        ]);

        foreach ($request->route_ids as $route_id) {
            DistributionRouteUserMap::updateOrCreate(
                [
                    'business_id' => $business_id,
                    'route_id' => $route_id,
                    'sales_rep_id' => $request->sales_rep_id,
                ],
                [
                    'status' => 'active',
                    'last_status_from' => null,
                    'last_status_to' => 'active',
                    'status_changed_at' => now(),
                    'added_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]
            );
        }

        return back()->with(['status' => 'User to routes mapping saved successfully.', 'page' => 'user_routes']);
    }

    public function toggleStatus($id)
    {
        $business_id = session()->get('user.business_id');
        $map = DistributionRouteUserMap::where('business_id', $business_id)->findOrFail($id);
        $previousStatus = $map->status;
        $newStatus = $map->status === 'active' ? 'inactive' : 'active';
        $map->status = $newStatus;
        $map->last_status_from = $previousStatus;
        $map->last_status_to = $newStatus;
        $map->status_changed_at = now();
        $map->updated_by = auth()->id();
        $map->status_changed_by = auth()->id();
        $map->save();

        return back()->with(['status' => 'Mapping status updated successfully.', 'page' => 'user_routes']);
    }

    public function toggleStatusBySalesRep($sales_rep_id)
    {
        $business_id = session()->get('user.business_id');
        $maps = DistributionRouteUserMap::where('business_id', $business_id)
            ->where('sales_rep_id', $sales_rep_id)
            ->get();

        if ($maps->isEmpty()) {
            return back()->withErrors('No mapping found for selected sales rep.');
        }

        $hasActive = $maps->contains(function ($map) {
            return $map->status === 'active';
        });
        $newStatus = $hasActive ? 'inactive' : 'active';

        foreach ($maps as $map) {
            $previousStatus = $map->status;
            $map->status = $newStatus;
            $map->last_status_from = $previousStatus;
            $map->last_status_to = $newStatus;
            $map->status_changed_at = now();
            $map->updated_by = auth()->id();
            $map->status_changed_by = auth()->id();
            $map->save();
        }

        return back()->with(['status' => 'Sales rep route mapping status updated successfully.', 'page' => 'user_routes']);
    }

    public function updateBySalesRep(Request $request, $sales_rep_id)
    {
        $business_id = session()->get('user.business_id');
        $request->validate([
            'route_ids' => 'required|array|min:1',
        ]);

        $route_ids = collect($request->route_ids)->map(function ($id) {
            return (int) $id;
        })->unique()->values()->all();

        DB::beginTransaction();
        try {
            $existing = DistributionRouteUserMap::where('business_id', $business_id)
                ->where('sales_rep_id', $sales_rep_id)
                ->get()
                ->keyBy('route_id');

            foreach ($route_ids as $route_id) {
                if (!isset($existing[$route_id])) {
                    DistributionRouteUserMap::create([
                        'business_id' => $business_id,
                        'route_id' => $route_id,
                        'sales_rep_id' => $sales_rep_id,
                        'status' => 'active',
                        'last_status_from' => null,
                        'last_status_to' => 'active',
                        'status_changed_at' => now(),
                        'status_changed_by' => auth()->id(),
                        'added_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]);
                } else {
                    $map = $existing[$route_id];
                    $map->updated_by = auth()->id();
                    $map->save();
                }
            }

            DistributionRouteUserMap::where('business_id', $business_id)
                ->where('sales_rep_id', $sales_rep_id)
                ->whereNotIn('route_id', $route_ids)
                ->delete();

            DB::commit();
            return back()->with(['status' => 'User to routes mapping updated successfully.', 'page' => 'user_routes']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors($e->getMessage());
        }
    }

    public function destroy($id)
    {
        $business_id = session()->get('user.business_id');
        $map = DistributionRouteUserMap::where('business_id', $business_id)->findOrFail($id);
        $map->delete();

        return back()->with(['status' => 'Mapping removed successfully.', 'page' => 'user_routes']);
    }

    public function destroyBySalesRep($sales_rep_id)
    {
        $business_id = session()->get('user.business_id');
        DistributionRouteUserMap::where('business_id', $business_id)
            ->where('sales_rep_id', $sales_rep_id)
            ->delete();

        return back()->with(['status' => 'Sales rep mappings removed successfully.', 'page' => 'user_routes']);
    }

    public function routesBySalesRep(Request $request)
    {
        $business_id = session()->get('user.business_id');
        $sales_rep_id = $request->input('sales_rep_id');
        if (empty($sales_rep_id)) {
            return response()->json([]);
        }

        $routes = DistributionRouteUserMap::query()
            ->join('distribution_routes', 'distribution_route_user_maps.route_id', '=', 'distribution_routes.id')
            ->join('distribution_sales_agents', 'distribution_route_user_maps.sales_rep_id', '=', 'distribution_sales_agents.id')
            ->where('distribution_route_user_maps.business_id', $business_id)
            ->where('distribution_route_user_maps.sales_rep_id', $sales_rep_id)
            ->where('distribution_route_user_maps.status', 'active')
            ->select('distribution_routes.id', 'distribution_routes.name')
            ->orderBy('distribution_routes.name')
            ->get();

        return response()->json($routes);
    }
}

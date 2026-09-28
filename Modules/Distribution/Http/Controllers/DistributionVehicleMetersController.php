<?php
namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Distribution\Entities\DistributionDailySummary;
use Modules\Distribution\Entities\DistributionVehicleMeters;
use Modules\Distribution\Entities\DistributionVehicles;
use Yajra\DataTables\Facades\DataTables;

class DistributionVehicleMetersController extends Controller
{
    public function index(Request $request)
    {
        // Fetch filter data
        $vehicles   = DistributionVehicles::pluck('vehicle_no', 'id');
        $sales_reps = DB::table('users')->pluck('first_name', 'id');
        $routes     = DB::table('distribution_routes')->pluck('name', 'id');
        $users      = DB::table('users')->pluck('first_name', 'id');

        if ($request->ajax()) {

            $vehicle_meters = DistributionVehicleMeters::query()
                ->leftJoin('distribution_vehicles as dv', 'distribution_vehicle_meters.vehicle_id', '=', 'dv.id')
                ->leftJoin('users as sales', 'distribution_vehicle_meters.sales_rep_id', '=', 'sales.id')
                ->leftJoin('users as added', 'distribution_vehicle_meters.added_by', '=', 'added.id')
                ->leftJoin('distribution_routes as dr', 'distribution_vehicle_meters.route_id', '=', 'dr.id')
                ->select(
                    'distribution_vehicle_meters.*',
                    'dv.vehicle_no',
                    'sales.first_name as sales_rep',
                    'added.first_name as added_by_name',
                    'dr.name as route'
                );

            if ($request->vehicle_id) {
                $vehicle_meters->where('distribution_vehicle_meters.vehicle_id', $request->vehicle_id);
            }

            if ($request->daily_summary_sheet_no) {
                $vehicle_meters->where('distribution_vehicle_meters.daily_summary_sheet_no', 'like', '%' . $request->daily_summary_sheet_no . '%');
            }

            if ($request->sales_rep_id) {
                $vehicle_meters->where('distribution_vehicle_meters.sales_rep_id', $request->sales_rep_id);
            }

            if ($request->route_id) {
                $vehicle_meters->where('distribution_vehicle_meters.route_id', $request->route_id);
            }

            if ($request->added_by) {
                $vehicle_meters->where('distribution_vehicle_meters.added_by', $request->added_by);
            }

            if ($request->date_range) {
                // $dates = explode(' - ', $request->date_range);
                $dates = preg_split('/\s*~\s*/', $request->date_range);
                if (count($dates) === 2) {
                    $vehicle_meters->whereBetween('distribution_vehicle_meters.date', [$dates[0], $dates[1]]);
                }
            }

            return DataTables::of($vehicle_meters)
                ->addColumn('action', function ($row) {
                    return '<button class="btn btn-xs btn-primary view_summary" data-id="' . $row->daily_summary_id . '">View</button>';
                })
                ->editColumn('starting_meter', fn($row) => $row->starting_meter)
                ->editColumn('closing_meter', fn($row) => $row->closing_meter)
                ->editColumn('sales_rep', fn($row) => $row->sales_rep ?? '—')
                ->editColumn('route', fn($row) => $row->route ?? '—')
                ->editColumn('added_by_name', fn($row) => $row->added_by_name ?? '—')
                ->rawColumns(['action'])
                ->make(true);
        } else {
            Log::info('not an ajax request');
        }

        return view('distribution::settings.vehicle_meters.index', compact('vehicles', 'sales_reps', 'routes', 'users'));
    }

    public function showDailySummary($id)
    {
        $sheet = DistributionDailySummary::with(['vehicle', 'salesRep', 'route'])->findOrFail($id);
        return view('distribution::settings.vehicle_meters.view_summary', compact('sheet'));
    }

    public static function getStartingMeter($vehicle_id)
    {
        $lastMeter = DistributionVehicleMeters::where('vehicle_id', $vehicle_id)
            ->orderBy('id', 'desc')
            ->first();

        return $lastMeter ? $lastMeter->closing_meter : null;
    }

}

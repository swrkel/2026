<?php
namespace Modules\Distribution\Http\Controllers;

use Modules\Distribution\Http\Controllers\Base\DistributionBaseController as Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Distribution\Entities\DistributionVehicles;
use Yajra\DataTables\Facades\DataTables;

class DistributionVehiclesController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $vehicles = DistributionVehicles::leftJoin('users', 'distribution_vehicles.added_by', '=', 'users.id')
                ->where('distribution_vehicles.business_id', $business_id)
                ->select(
                    'distribution_vehicles.*',
                    'users.username as added_by_user'
                );

            return DataTables::of($vehicles)
                ->addColumn('action', function ($row) {
                    return '
                    <button class="btn btn-xs btn-primary btn-modal edit_vehicle" data-container="#vehicleModal" data-href="' . action('\Modules\Distribution\Http\Controllers\DistributionVehiclesController@edit', [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> Edit</button>
                    <button class="btn btn-xs btn-danger delete_vehicle" data-id="' . $row->id . '"><i class="glyphicon glyphicon-trash"></i> Delete</button>
                ';
                })
                ->editColumn('revenue_license_renewal_date', function ($row) {
                    // return optional($row->revenue_license_renewal_date)->format('Y-m-d');
                    return $row->revenue_license_renewal_date ?? '-';
                })
                ->editColumn('added_by_user', function ($row) {
                    return $row->added_by_user ?? '—';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('distribution::settings.vehicles.index');
    }

    /**
     * Create method
     */
    public function create()
    {
        Log::info('Reached create method in DistributionVehiclesController');
        return view('distribution::settings.vehicles.create');
    }

    /**
     * Store a newly created vehicle in storage.
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $request->validate([
            'vehicle_no'                   => 'required|string|max:255',
            'vehicle_type'                 => 'nullable|string|max:255',
            'vehicle_brand'                => 'nullable|string|max:255',
            'vehicle_model'                => 'nullable|string|max:255',
            'revenue_license_renewal_date' => 'nullable|date',
            'starting_meter'               => 'required|numeric|min:0',
        ]);
        $vehicle_no = trim((string) $request->input('vehicle_no'));
        if (DistributionVehicles::where('business_id', $business_id)->whereRaw('LOWER(TRIM(vehicle_no)) = ?', [mb_strtolower($vehicle_no)])->exists()) {
            $message = 'This vehicle number already exists. Duplicate entries are not allowed.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->with('error', $message)->withInput();
        }

        // Wrap in transaction for safety
        DB::beginTransaction();
        try {
            $vehicle                               = new DistributionVehicles();
            $vehicle->business_id                  = $business_id;
            $vehicle->vehicle_no                   = $vehicle_no;
            $vehicle->vehicle_type                 = $request->input('vehicle_type');
            $vehicle->vehicle_brand                = $request->input('vehicle_brand');
            $vehicle->vehicle_model                = $request->input('vehicle_model');
            $vehicle->revenue_license_renewal_date = $request->input('revenue_license_renewal_date');
            $vehicle->starting_meter               = $request->input('starting_meter');
            $vehicle->added_by                     = Auth::id();
            $vehicle->save();

            DB::commit();

            // Return JSON response for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle added successfully!'
                ]);
            }

            // Redirect back with success message for regular form submissions
            return redirect()->back()->with('success', 'Vehicle added successfully!');

        } catch (\Exception $e) {
            DB::rollBack();

            // Return JSON response for AJAX requests
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error adding vehicle: ' . $e->getMessage()
                ], 500);
            }

            // Redirect back with error message for regular form submissions
            return redirect()->back()->with('error', 'Error adding vehicle: ' . $e->getMessage())
                ->withInput(); // Preserve form input
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $vehicle = DistributionVehicles::where('business_id', $business_id)->findOrFail($id);
        
        return view('distribution::settings.vehicles.edit', compact('vehicle'));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        $request->validate([
            'vehicle_no'                   => 'required|string|max:255',
            'vehicle_type'                 => 'nullable|string|max:255',
            'vehicle_brand'                => 'nullable|string|max:255',
            'vehicle_model'                => 'nullable|string|max:255',
            'revenue_license_renewal_date' => 'nullable|date',
            'starting_meter'               => 'required|numeric|min:0',
        ]);
        $vehicle_no = trim((string) $request->input('vehicle_no'));
        if (DistributionVehicles::where('business_id', $business_id)->where('id', '!=', $id)->whereRaw('LOWER(TRIM(vehicle_no)) = ?', [mb_strtolower($vehicle_no)])->exists()) {
            $message = 'This vehicle number already exists. Duplicate entries are not allowed.';
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return redirect()->back()->with('error', $message)->withInput();
        }

        DB::beginTransaction();
        try {
            $vehicle = DistributionVehicles::where('business_id', $business_id)->findOrFail($id);
            $vehicle->vehicle_no                   = $vehicle_no;
            $vehicle->vehicle_type                 = $request->input('vehicle_type');
            $vehicle->vehicle_brand                = $request->input('vehicle_brand');
            $vehicle->vehicle_model                = $request->input('vehicle_model');
            $vehicle->revenue_license_renewal_date = $request->input('revenue_license_renewal_date');
            $vehicle->starting_meter               = $request->input('starting_meter');
            $vehicle->save();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vehicle updated successfully!'
                ]);
            }

            return redirect()->back()->with('success', 'Vehicle updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating vehicle: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error updating vehicle: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                $vehicle = DistributionVehicles::where('business_id', $business_id)->findOrFail($id);
                $vehicle->delete();

                return response()->json([
                    'success' => true,
                    'msg' => 'Vehicle deleted successfully!'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Error deleting vehicle: ' . $e->getMessage()
                ]);
            }
        }
    }
}

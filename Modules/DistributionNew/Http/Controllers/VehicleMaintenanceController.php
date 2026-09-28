<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewVehicleMaintenance;

class VehicleMaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $records = DisnewVehicleMaintenance::forBusiness($businessId)
            ->forLocation($request->get('business_location_id'))
            ->latest('id')
            ->paginate(25);
        return view('distributionnew::maintenance.index', compact('records'));
    }

    public function create()
    {
        return view('distributionnew::maintenance.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = (int) $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        DisnewVehicleMaintenance::create($data);
        return redirect()->route('distributionnew.maintenance.index')->with('status', __('distributionnew::lang.maintenance_created'));
    }

    public function edit($id)
    {
        $record = DisnewVehicleMaintenance::findOrFail($id);
        return view('distributionnew::maintenance.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = DisnewVehicleMaintenance::findOrFail($id);
        $data = $request->all();
        $data['updated_by'] = auth()->id();
        $record->update($data);
        return redirect()->route('distributionnew.maintenance.index')->with('status', __('distributionnew::lang.maintenance_updated'));
    }
}

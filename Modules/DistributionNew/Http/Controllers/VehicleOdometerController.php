<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewVehicleOdometerHistory;

class VehicleOdometerController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $records = DisnewVehicleOdometerHistory::forBusiness($businessId)
            ->forLocation($request->get('business_location_id'))
            ->latest('id')
            ->paginate(25);
        return view('distributionnew::odometer_history.index', compact('records'));
    }

    public function create()
    {
        return view('distributionnew::odometer_history.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = (int) $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        DisnewVehicleOdometerHistory::create($data);
        return redirect()->route('distributionnew.odometer_history.index')->with('status', __('distributionnew::lang.odometer_created'));
    }

    public function edit($id)
    {
        $record = DisnewVehicleOdometerHistory::findOrFail($id);
        return view('distributionnew::odometer_history.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = DisnewVehicleOdometerHistory::findOrFail($id);
        $data = $request->all();
        $data['updated_by'] = auth()->id();
        $record->update($data);
        return redirect()->route('distributionnew.odometer_history.index')->with('status', __('distributionnew::lang.odometer_updated'));
    }
}

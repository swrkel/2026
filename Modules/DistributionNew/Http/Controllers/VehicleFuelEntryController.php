<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewVehicleFuelEntry;

class VehicleFuelEntryController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $records = DisnewVehicleFuelEntry::forBusiness($businessId)
            ->forLocation($request->get('business_location_id'))
            ->latest('id')
            ->paginate(25);
        return view('distributionnew::fuel_entries.index', compact('records'));
    }

    public function create()
    {
        return view('distributionnew::fuel_entries.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = (int) $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        DisnewVehicleFuelEntry::create($data);
        return redirect()->route('distributionnew.fuel_entries.index')->with('status', __('distributionnew::lang.fuel_created'));
    }

    public function edit($id)
    {
        $record = DisnewVehicleFuelEntry::findOrFail($id);
        return view('distributionnew::fuel_entries.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = DisnewVehicleFuelEntry::findOrFail($id);
        $data = $request->all();
        $data['updated_by'] = auth()->id();
        $record->update($data);
        return redirect()->route('distributionnew.fuel_entries.index')->with('status', __('distributionnew::lang.fuel_updated'));
    }
}

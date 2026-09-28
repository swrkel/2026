<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewDriver;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $records = DisnewDriver::forBusiness($businessId)
            ->forLocation($request->get('business_location_id'))
            ->latest('id')
            ->paginate(25);
        return view('distributionnew::drivers.index', compact('records'));
    }

    public function create()
    {
        return view('distributionnew::drivers.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = (int) $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        DisnewDriver::create($data);
        return redirect()->route('distributionnew.drivers.index')->with('status', __('distributionnew::lang.driver_created'));
    }

    public function edit($id)
    {
        $record = DisnewDriver::findOrFail($id);
        return view('distributionnew::drivers.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = DisnewDriver::findOrFail($id);
        $data = $request->all();
        $data['updated_by'] = auth()->id();
        $record->update($data);
        return redirect()->route('distributionnew.drivers.index')->with('status', __('distributionnew::lang.driver_updated'));
    }
}

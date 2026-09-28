<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewTripCommission;

class TripCommissionController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $records = DisnewTripCommission::forBusiness($businessId)
            ->forLocation($request->get('business_location_id'))
            ->latest('id')
            ->paginate(25);
        return view('distributionnew::trip_commissions.index', compact('records'));
    }

    public function create()
    {
        return view('distributionnew::trip_commissions.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = (int) $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        DisnewTripCommission::create($data);
        return redirect()->route('distributionnew.trip_commissions.index')->with('status', __('distributionnew::lang.trip_commission_created'));
    }

    public function edit($id)
    {
        $record = DisnewTripCommission::findOrFail($id);
        return view('distributionnew::trip_commissions.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = DisnewTripCommission::findOrFail($id);
        $data = $request->all();
        $data['updated_by'] = auth()->id();
        $record->update($data);
        return redirect()->route('distributionnew.trip_commissions.index')->with('status', __('distributionnew::lang.trip_commission_updated'));
    }
}

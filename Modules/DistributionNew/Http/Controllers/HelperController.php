<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewHelper;

class HelperController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $records = DisnewHelper::forBusiness($businessId)
            ->forLocation($request->get('business_location_id'))
            ->latest('id')
            ->paginate(25);
        return view('distributionnew::helpers.index', compact('records'));
    }

    public function create()
    {
        return view('distributionnew::helpers.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = (int) $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        DisnewHelper::create($data);
        return redirect()->route('distributionnew.helpers.index')->with('status', __('distributionnew::lang.helper_created'));
    }

    public function edit($id)
    {
        $record = DisnewHelper::findOrFail($id);
        return view('distributionnew::helpers.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = DisnewHelper::findOrFail($id);
        $data = $request->all();
        $data['updated_by'] = auth()->id();
        $record->update($data);
        return redirect()->route('distributionnew.helpers.index')->with('status', __('distributionnew::lang.helper_updated'));
    }
}

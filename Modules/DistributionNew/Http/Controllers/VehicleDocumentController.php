<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewVehicleDocument;

class VehicleDocumentController extends Controller
{
    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $records = DisnewVehicleDocument::forBusiness($businessId)
            ->forLocation($request->get('business_location_id'))
            ->latest('id')
            ->paginate(25);
        return view('distributionnew::vehicle_documents.index', compact('records'));
    }

    public function create()
    {
        return view('distributionnew::vehicle_documents.create');
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['business_id'] = (int) $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();
        DisnewVehicleDocument::create($data);
        return redirect()->route('distributionnew.vehicle_documents.index')->with('status', __('distributionnew::lang.vehicle_document_created'));
    }

    public function edit($id)
    {
        $record = DisnewVehicleDocument::findOrFail($id);
        return view('distributionnew::vehicle_documents.edit', compact('record'));
    }

    public function update(Request $request, $id)
    {
        $record = DisnewVehicleDocument::findOrFail($id);
        $data = $request->all();
        $data['updated_by'] = auth()->id();
        $record->update($data);
        return redirect()->route('distributionnew.vehicle_documents.index')->with('status', __('distributionnew::lang.vehicle_document_updated'));
    }
}

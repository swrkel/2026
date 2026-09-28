<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tailoring\Entities\TailoringMeasurementProfile;

class TailoringMeasurementProfileController extends Controller
{
    public function index()
    {
        $profiles = TailoringMeasurementProfile::latest()->paginate(25);
        return view('tailoring::measurement_profiles.index', compact('profiles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|integer',
            'profile_name' => 'required|string|max:191',
            'garment_type' => 'nullable|string|max:191',
            'measurements' => 'nullable|array',
            'style_preferences' => 'nullable|array',
            'fitting_notes' => 'nullable|string',
        ]);
        $data['business_id'] = session('business.id');
        $data['location_id'] = $request->input('location_id');
        $data['created_by'] = auth()->id();
        TailoringMeasurementProfile::create($data);
        return redirect()->back()->with('status', 'Measurement profile saved successfully.');
    }
}

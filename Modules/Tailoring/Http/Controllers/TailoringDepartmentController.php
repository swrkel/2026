<?php

namespace Modules\Tailoring\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Tailoring\Entities\TailoringDepartment;

class TailoringDepartmentController extends Controller
{
    public function index()
    {
        $departments = TailoringDepartment::orderBy('sort_order')->paginate(25);
        return view('tailoring::settings.departments', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:191', 'code' => 'nullable|string|max:50', 'sort_order' => 'nullable|integer']);
        $data['business_id'] = session('business.id');
        $data['location_id'] = $request->input('location_id');
        TailoringDepartment::create($data);
        return redirect()->back()->with('status', 'Department saved successfully.');
    }
}

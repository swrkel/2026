<?php

namespace Modules\MyHealthMembers\Http\Controllers\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthVaccine;
use Modules\MyHealthMembers\Services\Vaccination\MyHealthVaccinationService;

class MyHealthVaccineController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::vaccination.vaccines.index', ['vaccines' => MyHealthVaccine::orderBy('vaccine_name')->paginate(25)]);
    }

    public function create(MyHealthVaccinationService $service)
    {
        return view('myhealthmembers::vaccination.vaccines.create', ['types' => $service->vaccineTypes()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'vaccine_code' => 'required|string|max:50', 'vaccine_name' => 'required|string|max:191', 'manufacturer' => 'nullable|string|max:191',
            'vaccine_type' => 'nullable|string|max:50', 'dose_schedule' => 'nullable|string|max:191', 'storage_temperature' => 'nullable|string|max:100',
            'default_interval_days' => 'nullable|integer|min:0', 'booster_required' => 'nullable|boolean', 'booster_interval_days' => 'nullable|integer|min:0', 'notes' => 'nullable|string'
        ]);
        $data['business_id'] = session('business.id') ?? session('user.business_id');
        $data['location_id'] = session('business_location_id') ?? null;
        $data['status'] = 'active';
        $data['created_by'] = auth()->id();
        $data['booster_required'] = $request->boolean('booster_required');
        MyHealthVaccine::create($data);
        return redirect()->route('myhealth.vaccination.vaccines.index')->with('status', 'Vaccine saved successfully.');
    }
}

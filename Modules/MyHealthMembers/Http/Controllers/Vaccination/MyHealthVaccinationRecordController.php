<?php

namespace Modules\MyHealthMembers\Http\Controllers\Vaccination;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthVaccinationRecord;
use Modules\MyHealthMembers\Entities\MyHealthVaccine;
use Modules\MyHealthMembers\Services\Vaccination\MyHealthVaccinationService;

class MyHealthVaccinationRecordController extends Controller
{
    public function index()
    {
        return view('myhealthmembers::vaccination.records.index', ['records' => MyHealthVaccinationRecord::orderByDesc('date_given')->paginate(25)]);
    }

    public function create()
    {
        return view('myhealthmembers::vaccination.records.create', ['vaccines' => MyHealthVaccine::where('status', 'active')->orderBy('vaccine_name')->get()]);
    }

    public function store(Request $request, MyHealthVaccinationService $service)
    {
        $data = $request->validate([
            'member_id' => 'required|integer', 'vaccine_id' => 'required|integer', 'batch_id' => 'nullable|integer', 'dose_no' => 'nullable|integer|min:1',
            'date_given' => 'required|date', 'next_due_date' => 'nullable|date', 'administered_by' => 'nullable|string|max:191', 'administered_location' => 'nullable|string|max:191',
            'adverse_reaction' => 'nullable|string|max:191', 'reaction_notes' => 'nullable|string', 'remarks' => 'nullable|string'
        ]);
        $data['business_id'] = session('business.id') ?? session('user.business_id');
        $data['location_id'] = session('business_location_id') ?? null;
        $data['vaccination_no'] = $service->nextVaccinationNo();
        $data['status'] = 'given';
        $data['created_by'] = auth()->id();
        MyHealthVaccinationRecord::create($data);
        return redirect()->route('myhealth.vaccination.records.index')->with('status', 'Vaccination record saved successfully.');
    }

    public function certificate(MyHealthVaccinationRecord $record, MyHealthVaccinationService $service)
    {
        if (!$record->certificate_no) {
            $record->update(['certificate_no' => $service->nextCertificateNo(), 'certificate_issued_at' => now()]);
        }
        return view('myhealthmembers::vaccination.certificates.show', compact('record'));
    }
}

<?php

namespace Modules\MyHealthMembers\Http\Controllers\Nursing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthNursingNote;
use Modules\MyHealthMembers\Services\Nursing\MyHealthNursingService;

class MyHealthNursingNoteController extends Controller
{
    public function index()
    {
        $notes = MyHealthNursingNote::with('member')->latest()->paginate(25);
        return view('myhealthmembers::nursing.notes.index', compact('notes'));
    }

    public function create()
    {
        $members = MyHealthMember::orderBy('name')->limit(200)->get();
        return view('myhealthmembers::nursing.notes.create', compact('members'));
    }

    public function store(Request $request, MyHealthNursingService $service)
    {
        $data = $request->validate([
            'member_id' => 'required|integer',
            'shift' => 'required|string|max:20',
            'note_type' => 'nullable|string|max:50',
            'observations' => 'nullable|string',
            'doctor_instructions' => 'nullable|string',
            'nursing_actions' => 'nullable|string',
            'medication_notes' => 'nullable|string',
            'escalation_notes' => 'nullable|string',
        ]);

        $data['business_id'] = $request->session()->get('user.business_id');
        $data['location_id'] = $request->session()->get('business_location_id');
        $service->createNote($data);

        return redirect()->route('myhealth.nursing.notes.index')->with('status', 'Nursing note saved successfully.');
    }
}

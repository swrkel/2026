<?php

namespace Modules\MyHealthMembers\Http\Controllers\Doctor;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthConsultation;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Services\MyHealthAuditService;
use Modules\MyHealthMembers\Services\MyHealthConsultationNumberService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthConsultationController extends Controller
{
    public function index(MyHealthMember $member, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_view_medical_history'), 403);

        $consultations = MyHealthConsultation::where('member_id', $member->id)->latest()->paginate(25);

        return view('myhealthmembers::doctor.consultations.index', compact('member', 'consultations'));
    }

    public function create(MyHealthMember $member, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        $doctors = MyHealthDoctor::where('business_id', $permissionService->businessId())
            ->where('is_active', 1)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('myhealthmembers::doctor.consultations.create', compact('member', 'doctors'));
    }

    public function store(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService, MyHealthConsultationNumberService $numberService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        $data = $request->validate([
            'doctor_id' => ['nullable', 'integer'],
            'consultation_date' => ['required', 'date'],
            'consultation_time' => ['nullable'],
            'visit_type' => ['nullable', 'string', 'max:100'],
            'chief_complaint' => ['nullable', 'string'],
            'summary' => ['nullable', 'string'],
        ]);

        $consultation = MyHealthConsultation::create($data + [
            'consultation_no' => $numberService->nextNumber(),
            'member_id' => $member->id,
            'business_id' => $permissionService->businessId(),
            'doctor_user_id' => auth()->id(),
            'status' => 'open',
        ]);

        $auditService->log($member->id, 'consultation', 'create', 'Consultation created: ' . $consultation->consultation_no);

        return redirect()->route('myhealth.consultations.index', $member->id)->with('status', 'Consultation created successfully.');
    }
}

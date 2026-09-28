<?php

namespace Modules\MyHealthMembers\Http\Controllers\Doctor;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthConsultation;
use Modules\MyHealthMembers\Entities\MyHealthDiagnosis;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthPrescription;
use Modules\MyHealthMembers\Services\MyHealthAuditService;
use Modules\MyHealthMembers\Services\MyHealthConsultationNumberService;
use Modules\MyHealthMembers\Services\MyHealthDoctorPortalService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthDoctorPortalController extends Controller
{
    public function dashboard(Request $request, MyHealthPermissionService $permissionService, MyHealthDoctorPortalService $portalService)
    {
        abort_unless($permissionService->can('can_view_medical_history'), 403);

        $businessId = $permissionService->businessId();
        $summary = $portalService->dashboardSummary($businessId);
        $queue = $portalService->todayQueue($businessId);
        $members = $portalService->memberSearch($businessId, $request->input('q'));

        return view('myhealthmembers::doctor.portal.dashboard', compact('summary', 'queue', 'members'));
    }

    public function createConsultation(MyHealthMember $member, MyHealthPermissionService $permissionService, MyHealthDoctorPortalService $portalService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        $doctors = MyHealthDoctor::where('business_id', $permissionService->businessId())->where('is_active', 1)->orderBy('name')->get();
        $clinical = $portalService->recentClinicalItems($member);

        return view('myhealthmembers::doctor.portal.consultation', compact('member', 'doctors', 'clinical'));
    }

    public function storeConsultation(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService, MyHealthConsultationNumberService $numberService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        $data = $request->validate([
            'doctor_id' => ['nullable', 'integer'],
            'consultation_date' => ['required', 'date'],
            'consultation_time' => ['nullable'],
            'visit_type' => ['nullable', 'string', 'max:100'],
            'chief_complaint' => ['nullable', 'string'],
            'history_present_illness' => ['nullable', 'string'],
            'examination_notes' => ['nullable', 'string'],
            'vital_signs' => ['nullable', 'string'],
            'investigation_plan' => ['nullable', 'string'],
            'treatment_plan' => ['nullable', 'string'],
            'follow_up_date' => ['nullable', 'date'],
            'summary' => ['nullable', 'string'],
            'diagnosis_title' => ['nullable', 'string', 'max:255'],
            'diagnosis_notes' => ['nullable', 'string'],
            'prescription_details' => ['nullable', 'string'],
            'prescription_instructions' => ['nullable', 'string'],
        ]);

        $consultation = MyHealthConsultation::create([
            'consultation_no' => $numberService->nextNumber(),
            'member_id' => $member->id,
            'business_id' => $permissionService->businessId(),
            'doctor_id' => $data['doctor_id'] ?? null,
            'doctor_user_id' => auth()->id(),
            'consultation_date' => $data['consultation_date'],
            'consultation_time' => $data['consultation_time'] ?? null,
            'visit_type' => $data['visit_type'] ?? null,
            'status' => 'open',
            'chief_complaint' => $data['chief_complaint'] ?? null,
            'history_present_illness' => $data['history_present_illness'] ?? null,
            'examination_notes' => $data['examination_notes'] ?? null,
            'vital_signs' => $data['vital_signs'] ?? null,
            'investigation_plan' => $data['investigation_plan'] ?? null,
            'treatment_plan' => $data['treatment_plan'] ?? null,
            'follow_up_date' => $data['follow_up_date'] ?? null,
            'summary' => $data['summary'] ?? null,
        ]);

        if (!empty($data['diagnosis_title']) || !empty($data['diagnosis_notes'])) {
            MyHealthDiagnosis::create([
                'member_id' => $member->id,
                'business_id' => $permissionService->businessId(),
                'doctor_user_id' => auth()->id(),
                'consultation_id' => $consultation->id,
                'diagnosis_date' => $data['consultation_date'],
                'title' => $data['diagnosis_title'] ?: 'Consultation Diagnosis',
                'diagnosis' => $data['diagnosis_notes'] ?? null,
                'notes' => $data['diagnosis_notes'] ?? null,
            ]);
        }

        if (!empty($data['prescription_details'])) {
            MyHealthPrescription::create([
                'member_id' => $member->id,
                'business_id' => $permissionService->businessId(),
                'doctor_user_id' => auth()->id(),
                'consultation_id' => $consultation->id,
                'prescription_date' => $data['consultation_date'],
                'prescription_details' => $data['prescription_details'],
                'instructions' => $data['prescription_instructions'] ?? null,
            ]);
        }

        $auditService->log($member->id, 'doctor_portal', 'create', 'Doctor portal consultation created: ' . $consultation->consultation_no);

        return redirect()->route('myhealth.doctor.portal.dashboard')->with('status', 'Consultation saved successfully.');
    }

    public function complete(MyHealthConsultation $consultation, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        $consultation->update(['status' => 'completed']);
        $auditService->log($consultation->member_id, 'doctor_portal', 'complete', 'Consultation completed: ' . $consultation->consultation_no);

        return back()->with('status', 'Consultation completed successfully.');
    }
}

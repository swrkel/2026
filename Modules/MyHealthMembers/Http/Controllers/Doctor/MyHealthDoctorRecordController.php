<?php

namespace Modules\MyHealthMembers\Http\Controllers\Doctor;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthDiagnosis;
use Modules\MyHealthMembers\Entities\MyHealthMedicalHistory;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthPrescription;
use Modules\MyHealthMembers\Services\MyHealthAuditService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthDoctorRecordController extends Controller
{
    public function index(MyHealthMember $member, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_view_medical_history'), 403);

        $history = MyHealthMedicalHistory::firstOrNew(['member_id' => $member->id]);
        $diagnoses = MyHealthDiagnosis::where('member_id', $member->id)->latest()->get();
        $prescriptions = MyHealthPrescription::where('member_id', $member->id)->latest()->get();

        $auditService->log($member->id, 'medical_records', 'view', 'Doctor records viewed.');

        return view('myhealthmembers::doctor.records', compact('member', 'history', 'diagnoses', 'prescriptions'));
    }

    public function saveHistory(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        MyHealthMedicalHistory::updateOrCreate(
            ['member_id' => $member->id],
            [
                'allergies' => $request->input('allergies'),
                'chronic_conditions' => $request->input('chronic_conditions'),
                'current_medications' => $request->input('current_medications'),
                'past_surgeries' => $request->input('past_surgeries'),
                'family_history' => $request->input('family_history'),
                'updated_by_business_id' => $permissionService->businessId(),
                'updated_by_user_id' => auth()->id(),
            ]
        );

        $auditService->log($member->id, 'medical_history', 'update', 'Medical history updated.');

        return back()->with('status', 'Medical history saved successfully.');
    }

    public function storeDiagnosis(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        MyHealthDiagnosis::create([
            'member_id' => $member->id,
            'business_id' => $permissionService->businessId(),
            'doctor_user_id' => auth()->id(),
            'diagnosis_date' => $request->input('diagnosis_date', date('Y-m-d')),
            'title' => $request->input('title'),
            'symptoms' => $request->input('symptoms'),
            'diagnosis' => $request->input('diagnosis'),
            'notes' => $request->input('notes'),
        ]);

        $auditService->log($member->id, 'diagnosis', 'create', 'Diagnosis created.');

        return back()->with('status', __('myhealthmembers::lang.diagnosis_saved'));
    }

    public function storePrescription(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_create_prescription'), 403);

        $request->validate([
            'prescription_details' => ['required', 'string'],
        ]);

        MyHealthPrescription::create([
            'member_id' => $member->id,
            'business_id' => $permissionService->businessId(),
            'doctor_user_id' => auth()->id(),
            'prescription_date' => $request->input('prescription_date', date('Y-m-d')),
            'prescription_details' => $request->input('prescription_details'),
            'instructions' => $request->input('instructions'),
        ]);

        $auditService->log($member->id, 'prescription', 'create', 'Prescription created.');

        return back()->with('status', __('myhealthmembers::lang.prescription_saved'));
    }
}

<?php

namespace Modules\MyHealthMembers\Http\Controllers\Doctor;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Services\MyHealthAuditService;
use Modules\MyHealthMembers\Services\MyHealthConsultationNumberService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthDoctorController extends Controller
{
    public function index(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_view_medical_history'), 403);

        $businessId = $permissionService->businessId();
        $doctors = MyHealthDoctor::where('business_id', $businessId)->orderBy('name')->paginate(25);

        return view('myhealthmembers::doctor.doctors.index', compact('doctors'));
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        return view('myhealthmembers::doctor.doctors.create');
    }

    public function store(Request $request, MyHealthPermissionService $permissionService, MyHealthConsultationNumberService $numberService, MyHealthAuditService $auditService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'registration_no' => ['nullable', 'string', 'max:100'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $doctor = MyHealthDoctor::create($data + [
            'doctor_code' => $numberService->nextDoctorCode(),
            'business_id' => $permissionService->businessId(),
            'user_id' => auth()->id(),
        ]);

        $auditService->log(null, 'doctor', 'create', 'Doctor created: ' . $doctor->doctor_code);

        return redirect()->route('myhealth.doctors.index')->with('status', 'Doctor created successfully.');
    }
}

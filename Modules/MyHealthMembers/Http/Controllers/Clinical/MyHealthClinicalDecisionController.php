<?php

namespace Modules\MyHealthMembers\Http\Controllers\Clinical;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthAllergy;
use Modules\MyHealthMembers\Entities\MyHealthChronicCondition;
use Modules\MyHealthMembers\Entities\MyHealthClinicalAlert;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Services\MyHealthClinicalDecisionService;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthClinicalDecisionController extends Controller
{
    public function index(MyHealthMember $member, MyHealthPermissionService $permissionService, MyHealthClinicalDecisionService $service)
    {
        abort_unless($permissionService->can('can_view_medical_history'), 403);

        $businessId = $permissionService->businessId();
        $alerts = $service->evaluateMember($member, $businessId);
        $allergies = MyHealthAllergy::where('member_id', $member->id)->latest('id')->get();
        $conditions = MyHealthChronicCondition::where('member_id', $member->id)->latest('id')->get();

        return view('myhealthmembers::clinical.index', compact('member', 'alerts', 'allergies', 'conditions'));
    }

    public function storeAllergy(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        $data = $request->validate([
            'allergy_name' => ['required', 'string', 'max:255'],
            'allergy_type' => ['nullable', 'string', 'max:100'],
            'severity' => ['nullable', 'string', 'max:50'],
            'reaction' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        MyHealthAllergy::create(array_merge($data, [
            'member_id' => $member->id,
            'business_id' => $permissionService->businessId(),
            'is_active' => 1,
        ]));

        return back()->with('status', 'Allergy added successfully.');
    }

    public function storeCondition(MyHealthMember $member, Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        $data = $request->validate([
            'condition_name' => ['required', 'string', 'max:255'],
            'diagnosed_date' => ['nullable', 'date'],
            'severity' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        MyHealthChronicCondition::create(array_merge($data, [
            'member_id' => $member->id,
            'business_id' => $permissionService->businessId(),
            'is_active' => 1,
        ]));

        return back()->with('status', 'Chronic condition added successfully.');
    }

    public function acknowledge(MyHealthClinicalAlert $alert, MyHealthClinicalDecisionService $service, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_create_diagnosis'), 403);

        $service->acknowledge($alert->id);

        return back()->with('status', 'Clinical alert acknowledged.');
    }
}

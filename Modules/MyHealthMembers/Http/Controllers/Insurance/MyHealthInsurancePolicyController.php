<?php

namespace Modules\MyHealthMembers\Http\Controllers\Insurance;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthInsuranceCompany;
use Modules\MyHealthMembers\Entities\MyHealthInsurancePolicy;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;
use Modules\MyHealthMembers\Services\MyHealthPolicyNumberService;

class MyHealthInsurancePolicyController extends Controller
{
    public function index(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);

        $query = MyHealthInsurancePolicy::query()->with(['member', 'company']);
        if ($search = trim((string) $request->input('search'))) {
            $query->where('policy_no', 'like', "%{$search}%")
                ->orWhereHas('member', function ($q) use ($search) {
                    $q->where('myhealth_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
        }

        $policies = $query->latest('id')->paginate(25);
        return view('myhealthmembers::insurance.policies.index', compact('policies'));
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);
        return view('myhealthmembers::insurance.policies.create', [
            'members' => MyHealthMember::query()->orderBy('name')->get(),
            'companies' => MyHealthInsuranceCompany::query()->where('is_active', true)->orderBy('company_name')->get(),
        ]);
    }

    public function store(Request $request, MyHealthPolicyNumberService $numberService, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);

        $data = $request->validate([
            'member_id' => ['required', 'integer'],
            'insurance_company_id' => ['required', 'integer'],
            'policy_type' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'coverage_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['policy_no'] = $numberService->nextNumber();
        $data['coverage_amount'] = $data['coverage_amount'] ?? 0;
        $data['available_balance'] = $data['coverage_amount'];
        $data['status'] = 'active';

        MyHealthInsurancePolicy::create($data);

        return redirect()->route('myhealth.insurance.policies.index')->with('status', __('myhealthmembers::lang.insurance_policy_saved'));
    }
}

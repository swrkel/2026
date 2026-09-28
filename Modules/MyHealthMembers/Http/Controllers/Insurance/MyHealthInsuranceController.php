<?php

namespace Modules\MyHealthMembers\Http\Controllers\Insurance;

use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthInsuranceClaim;
use Modules\MyHealthMembers\Entities\MyHealthInsuranceCompany;
use Modules\MyHealthMembers\Entities\MyHealthInsurancePolicy;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthInsuranceController extends Controller
{
    public function dashboard(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);

        return view('myhealthmembers::insurance.dashboard', [
            'totalCompanies' => MyHealthInsuranceCompany::query()->count(),
            'activePolicies' => MyHealthInsurancePolicy::query()->where('status', 'active')->count(),
            'submittedClaims' => MyHealthInsuranceClaim::query()->where('status', 'submitted')->count(),
            'approvedClaims' => MyHealthInsuranceClaim::query()->where('status', 'approved')->count(),
        ]);
    }
}

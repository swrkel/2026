<?php

namespace Modules\MyHealthMembers\Http\Controllers\Billing;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthBillingService as BillingServiceEntity;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthBillingServiceController extends Controller
{
    public function index(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_billing'), 403);
        $query = BillingServiceEntity::query();
        if ($search = trim((string) $request->input('search'))) {
            $query->where('service_code', 'like', "%{$search}%")->orWhere('service_name', 'like', "%{$search}%");
        }
        return view('myhealthmembers::billing.services.index', ['services' => $query->latest('id')->paginate(25)]);
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_manage_billing'), 403);
        return view('myhealthmembers::billing.services.create');
    }

    public function store(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_manage_billing'), 403);
        $data = $request->validate([
            'service_code' => ['required', 'string', 'max:50'],
            'service_name' => ['required', 'string', 'max:191'],
            'service_type' => ['required', 'string', 'max:50'],
            'default_amount' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
        ]);
        $data['default_amount'] = (float) ($data['default_amount'] ?? 0);
        $data['is_active'] = $request->boolean('is_active', true);
        BillingServiceEntity::create($data);
        return redirect()->route('myhealth.billing.services.index')->with('status', __('myhealthmembers::lang.billing_service_saved'));
    }
}

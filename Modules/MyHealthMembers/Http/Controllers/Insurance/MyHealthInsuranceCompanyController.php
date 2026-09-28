<?php

namespace Modules\MyHealthMembers\Http\Controllers\Insurance;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthInsuranceCompany;
use Modules\MyHealthMembers\Services\MyHealthPermissionService;

class MyHealthInsuranceCompanyController extends Controller
{
    public function index(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);

        $query = MyHealthInsuranceCompany::query();
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('company_code', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('contact_no', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $companies = $query->orderBy('company_name')->paginate(25);
        return view('myhealthmembers::insurance.companies.index', compact('companies'));
    }

    public function create(MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);
        return view('myhealthmembers::insurance.companies.create');
    }

    public function store(Request $request, MyHealthPermissionService $permissionService)
    {
        abort_unless($permissionService->can('can_access_insurance'), 403);

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'contact_no' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $lastId = (int) MyHealthInsuranceCompany::query()->max('id');
        $data['company_code'] = 'MHI-' . str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
        $data['business_id'] = $permissionService->businessId();
        $data['is_active'] = $request->boolean('is_active', true);

        MyHealthInsuranceCompany::create($data);

        return redirect()->route('myhealth.insurance.companies.index')->with('status', __('myhealthmembers::lang.insurance_company_saved'));
    }
}

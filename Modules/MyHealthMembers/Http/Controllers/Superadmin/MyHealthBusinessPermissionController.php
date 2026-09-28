<?php

namespace Modules\MyHealthMembers\Http\Controllers\Superadmin;

use App\Business;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthBusinessPermission;
use Modules\MyHealthMembers\Services\Support\MyHealthPortalBranding;

class MyHealthBusinessPermissionController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('superadmin'), 403);

        $businesses = Business::orderBy('name')->get();
        $permissions = MyHealthBusinessPermission::get()->keyBy('business_id');

        return view('myhealthmembers::superadmin.permissions.index', compact('businesses', 'permissions'));
    }

    public function edit($business_id)
    {
        abort_unless(auth()->user()->can('superadmin'), 403);

        $business = Business::findOrFail($business_id);
        $permission = MyHealthBusinessPermission::firstOrNew(['business_id' => $business_id]);

        $branding = array_merge(MyHealthPortalBranding::defaults(), (array) ($permission->portal_branding ?? []));

        return view('myhealthmembers::superadmin.permissions.edit', compact('business', 'permission', 'branding'));
    }

    public function update($business_id, Request $request)
    {
        abort_unless(auth()->user()->can('superadmin'), 403);

        $data = $request->validate([
            'can_register_member' => ['nullable', 'boolean'],
            'can_view_profile' => ['nullable', 'boolean'],
            'can_edit_profile' => ['nullable', 'boolean'],
            'can_view_medical_history' => ['nullable', 'boolean'],
            'can_create_diagnosis' => ['nullable', 'boolean'],
            'can_create_prescription' => ['nullable', 'boolean'],
            'can_access_pharmacy' => ['nullable', 'boolean'],
            'can_dispense_medicine' => ['nullable', 'boolean'],
            'can_manage_pharmacy_stock' => ['nullable', 'boolean'],
            'can_access_insurance' => ['nullable', 'boolean'],
            'can_manage_claims' => ['nullable', 'boolean'],
            'can_access_telemedicine' => ['nullable', 'boolean'],
            'can_manage_telemedicine' => ['nullable', 'boolean'],
            'can_access_billing' => ['nullable', 'boolean'],
            'can_manage_billing' => ['nullable', 'boolean'],
            'can_upload_documents' => ['nullable', 'boolean'],
            'can_view_documents' => ['nullable', 'boolean'],
            'can_export_print' => ['nullable', 'boolean'],
            'access_expiry_date' => ['nullable', 'date'],
            'portal_branding.portal_name' => ['nullable', 'string', 'max:190'],
            'portal_branding.browser_title' => ['nullable', 'string', 'max:190'],
            'portal_branding.welcome_message' => ['nullable', 'string', 'max:1000'],
            'portal_branding.footer_text' => ['nullable', 'string', 'max:1000'],
            'portal_branding.copyright_text' => ['nullable', 'string', 'max:255'],
            'portal_branding.primary_theme_color' => ['nullable', 'string', 'max:20'],
            'portal_branding.secondary_theme_color' => ['nullable', 'string', 'max:20'],
            'portal_branding.support_email' => ['nullable', 'email', 'max:190'],
            'portal_branding.support_telephone' => ['nullable', 'string', 'max:80'],
            'portal_logo' => ['nullable', 'image', 'max:2048'],
            'login_banner' => ['nullable', 'image', 'max:4096'],
        ]);

        foreach ([
            'can_register_member',
            'can_view_profile',
            'can_edit_profile',
            'can_view_medical_history',
            'can_create_diagnosis',
            'can_create_prescription',
            'can_access_pharmacy',
            'can_dispense_medicine',
            'can_manage_pharmacy_stock',
            'can_access_insurance',
            'can_manage_claims',
            'can_access_telemedicine',
            'can_manage_telemedicine',
            'can_access_billing',
            'can_manage_billing',
            'can_upload_documents',
            'can_view_documents',
            'can_export_print',
        ] as $field) {
            $data[$field] = (int) $request->boolean($field);
        }

        $existing = MyHealthBusinessPermission::firstOrNew(['business_id' => $business_id]);
        $branding = array_merge(MyHealthPortalBranding::defaults(), (array) ($existing->portal_branding ?? []), (array) $request->input('portal_branding', []));

        $uploadDir = public_path('uploads/myhealthmembers/branding/' . $business_id);
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        if ($request->hasFile('portal_logo')) {
            $file = $request->file('portal_logo');
            $name = 'portal-logo-' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $name);
            $branding['logo'] = 'uploads/myhealthmembers/branding/' . $business_id . '/' . $name;
        }

        if ($request->hasFile('login_banner')) {
            $file = $request->file('login_banner');
            $name = 'login-banner-' . time() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadDir, $name);
            $branding['login_banner'] = 'uploads/myhealthmembers/branding/' . $business_id . '/' . $name;
        }

        $data['portal_branding'] = $branding;

        MyHealthBusinessPermission::updateOrCreate(
            ['business_id' => $business_id],
            $data + ['business_id' => $business_id]
        );

        return redirect()->route('myhealth.superadmin.permissions.index')
            ->with('status', __('myhealthmembers::lang.permissions_saved'));
    }
}

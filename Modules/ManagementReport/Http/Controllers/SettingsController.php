<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ManagementReport\Entities\ReportSetting;
use Modules\ManagementReport\Support\TenantConnection;

class SettingsController extends Controller
{
    protected $allowed = [
        'link_expiry_hours',
        'currency_decimals',
        'default_sections',
        'email_subject',
        'sms_message',
        'whatsapp_message',
        'show_zero_rows',
        'print_logo',
    ];

    public function index()
    {
        TenantConnection::activate();
        $businessId = (int) session('user.business_id');
        $settings = ReportSetting::where('business_id', $businessId)->get()->mapWithKeys(function ($setting) {
            return [$setting->setting_key => $setting->setting_value];
        })->all();
        $sections = config('managementreport_sections', []);

        // 8053 compatibility: an existing default_sections setting may still
        // contain the retired add_less key. Show the restored/new sections as the
        // selected defaults immediately; no SQL/data migration is required.
        $defaults = (array) ($settings['default_sections'] ?? []);
        if (in_array('add_less', $defaults, true)) {
            $defaults = array_values(array_unique(array_merge(
                array_diff($defaults, ['add_less']),
                ['received_in', 'out', 'total_add']
            )));
        }
        if ($defaults && !in_array('received_in', $defaults, true)) {
            $defaults[] = 'received_in';
        }
        if ($defaults) {
            $settings['default_sections'] = $defaults;
        }

        return view('managementreport::settings.index', compact('settings', 'sections'));
    }

    public function update(Request $request)
    {
        TenantConnection::activate();
        $businessId = (int) session('user.business_id');
        foreach ($this->allowed as $key) {
            $isBoolean = in_array($key, ['show_zero_rows', 'print_logo'], true);
            if (!$isBoolean && !$request->exists($key)) {
                continue;
            }
            $value = $isBoolean ? $request->boolean($key) : $request->input($key);
            if ($key === 'default_sections') {
                $value = array_values((array) $value);
            }
            ReportSetting::updateOrCreate(
                ['business_id' => $businessId, 'location_id' => null, 'store_id' => null, 'setting_key' => $key],
                ['setting_value' => $value, 'updated_by' => auth()->id()]
            );
        }

        return back()->with('success', 'Management Report settings saved.');
    }
}

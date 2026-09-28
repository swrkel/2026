<?php

namespace Modules\RestaurantNew\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\RestaurantNew\Services\CoreSetupService;

class SettingsController extends Controller
{
    public function index(CoreSetupService $service)
    {
        $service->ensureDefaultOrderTypes();
        $service->ensureDefaultNumbering();

        return view('restaurantnew::setup.settings.index', [
            'settings' => $service->settings(),
            'counts' => $service->dashboardCounts(),
        ]);
    }

    public function update(Request $request, CoreSetupService $service)
    {
        $data = $request->validate([
            'restaurant_name' => ['nullable', 'string', 'max:191'],
            'enable_kot' => ['nullable'],
            'enable_service_charge' => ['nullable'],
            'service_charge_percent' => ['nullable', 'numeric', 'min:0'],
            'enable_table_qr' => ['nullable'],
            'default_order_type' => ['nullable', 'string', 'max:50'],
            'bill_footer_note' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach (['enable_kot', 'enable_service_charge', 'enable_table_qr'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        $service->saveSettings($data);
        return back()->with('status', __('restaurantnew::lang.updated_successfully'));
    }
}

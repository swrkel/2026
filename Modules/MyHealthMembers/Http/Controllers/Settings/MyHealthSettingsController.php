<?php

namespace Modules\MyHealthMembers\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\MyHealthSettingsService;

class MyHealthSettingsController extends Controller
{
    public function index(MyHealthSettingsService $settingsService)
    {
        $businessId = session('business.id');
        $settings = $settingsService->getGroupedSettings($businessId);

        return view('myhealthmembers::settings.index', compact('settings'));
    }

    public function update(Request $request, MyHealthSettingsService $settingsService)
    {
        $settingsService->saveMany(
            $request->input('settings', []),
            session('business.id'),
            session('business_location_id')
        );

        return redirect()->route('myhealth.settings.index')
            ->with('status', __('myhealthmembers::lang.settings_saved'));
    }
}

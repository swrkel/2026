<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Admin;

use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\SettingsUpdateRequest;
use Modules\PumperDashboardNew\Services\PoneSettingsService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;

class SettingsController extends Controller
{
    public function __construct(private PoneSettingsService $settings, private PoneSharedMasterDataService $masterData) {}
    public function edit()
    {
        $businessId = $this->businessId();
        $locationId = request()->integer('location_id') ?: null;
        $settings = $this->settings->get($businessId, $locationId);
        $locations = $this->masterData->locations($businessId);
        $stores = $this->masterData->stores($businessId, $locationId);
        return view('pumperdashboardnew::admin.settings.edit', compact('settings', 'locationId', 'locations', 'stores'));
    }
    public function update(SettingsUpdateRequest $request)
    {
        $data = $request->validated();
        $locationId = ! empty($data['location_id']) ? (int) $data['location_id'] : null;
        unset($data['location_id']);
        $this->settings->update($this->businessId(), $locationId, $data, (int) auth()->id());
        return $this->ok(__('pumperdashboardnew::lang.settings_saved'));
    }
}

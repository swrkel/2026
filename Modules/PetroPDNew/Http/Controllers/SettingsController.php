<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Modules\PetroPDNew\Http\Requests\SettingsUpdateRequest;
use Modules\PetroPDNew\Services\PdnewMasterDataService;
use Modules\PetroPDNew\Services\PdnewSettingsService;

class SettingsController extends PdnewController
{
    public function edit(PdnewSettingsService $service, PdnewMasterDataService $masterData)
    {
        $settings = $service->forScope($this->context->businessId(), $this->context->locationId());
        $locations = $masterData->locations($this->context->businessId());
        $currentLocationId = $this->context->locationId();

        return view('petropdnew::settings.edit', compact('settings', 'locations', 'currentLocationId'));
    }

    public function update(SettingsUpdateRequest $request, PdnewSettingsService $service)
    {
        try {
            $locationId = (int) ($request->validated('location_id') ?: $this->context->locationId()) ?: null;
            $this->context->authorizeLocation($locationId);
            $service->update($this->context->businessId(), $locationId, $request->validated());
            return back()->with('success', 'Petro PD-New settings updated.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}

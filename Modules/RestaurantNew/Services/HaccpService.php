<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Http\Request;
use Modules\RestaurantNew\Models\RestnewHaccpTemperatureLog;
use Modules\RestaurantNew\Models\RestnewHaccpCorrectiveAction;

class HaccpService
{
    public function summary(Request $request): array
    {
        $businessId = $request->session()->get('user.business_id');

        return [
            'open_checks' => 0,
            'failed_checks' => 0,
            'temperature_alerts' => RestnewHaccpTemperatureLog::forBusiness($businessId)->where('status', 'out_of_range')->count(),
            'open_corrective_actions' => RestnewHaccpCorrectiveAction::forBusiness($businessId)->where('status', 'open')->count(),
        ];
    }

    public function temperatureLogs(Request $request)
    {
        return RestnewHaccpTemperatureLog::forBusiness($request->session()->get('user.business_id'))
            ->latest('checked_at')
            ->paginate(25);
    }

    public function storeTemperature(Request $request): RestnewHaccpTemperatureLog
    {
        $data = $request->validate([
            'business_location_id' => 'nullable|integer',
            'asset_name' => 'required|string|max:191',
            'check_type' => 'required|string|max:100',
            'temperature' => 'required|numeric',
            'min_temperature' => 'nullable|numeric',
            'max_temperature' => 'nullable|numeric',
            'checked_at' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        $data['business_id'] = $request->session()->get('user.business_id');
        $data['created_by'] = auth()->id();
        $data['checked_at'] = $data['checked_at'] ?? now();
        $data['status'] = $this->temperatureStatus($data);

        return RestnewHaccpTemperatureLog::create($data);
    }

    public function correctiveActions(Request $request)
    {
        return RestnewHaccpCorrectiveAction::forBusiness($request->session()->get('user.business_id'))
            ->latest('due_at')
            ->paginate(25);
    }

    protected function temperatureStatus(array $data): string
    {
        if (isset($data['min_temperature']) && $data['temperature'] < $data['min_temperature']) {
            return 'out_of_range';
        }
        if (isset($data['max_temperature']) && $data['temperature'] > $data['max_temperature']) {
            return 'out_of_range';
        }
        return 'ok';
    }
}

<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\DistributionNew\Models\DisnewVehicleLimit;
use Modules\DistributionNew\Services\SuperAdmin\DisnewVehicleLimitAdminService;

class SuperAdminVehicleLimitController extends Controller
{
    public function edit(Request $request, int $businessId)
    {
        $limit = DisnewVehicleLimit::firstOrNew(['business_id' => $businessId]);
        return view('distributionnew::superadmin.vehicle_limit', compact('businessId', 'limit'));
    }

    public function update(Request $request, int $businessId, DisnewVehicleLimitAdminService $service)
    {
        $data = $request->validate([
            'vehicle_limit' => ['nullable', 'integer', 'min:0'],
            'allow_unlimited' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string'],
        ]);
        $service->saveLimit($businessId, $data);
        return back()->with('status', __('distributionnew::lang.vehicle_limit_updated'));
    }
}

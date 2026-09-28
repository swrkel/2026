<?php

namespace Modules\Membership\Http\Controllers\Settings;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Membership\Services\PrefixStartingNumberService;

class PrefixStartingNumberController extends Controller
{
    protected $service;

    public function __construct(PrefixStartingNumberService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $settings = $this->service->listForBusiness($businessId);

        return view('membership::partials.membership_settings', compact('settings'));
    }

    public function store(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        $data = $request->validate([
            'region'          => 'required|string|max:255',
            'prefix'          => 'nullable|string|max:10',
            'starting_number' => 'required|integer|min:1',
        ]);

        if ($this->service->existsRegion($businessId, $data['region'])) {
            return response()->json([
                'success' => false,
                'msg' => __('membership::lang.region_already_exists'),
            ]);
        }

        $setting = $this->service->create($businessId, $data);

        return response()->json([
            'success' => true,
            'id' => $setting->id,
            'region' => $setting->region,
            'msg' => __('membership::lang.setting_created_successfully'),
        ]);
    }

    public function show(Request $request, $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $setting = $this->service->getForBusiness((int) $id, $businessId);

        return view('membership::settings.prefix_starting_numbers.view', compact('setting'));
    }

    public function edit(Request $request, $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $setting = $this->service->getForBusiness((int) $id, $businessId);

        return response()->json($setting);
    }

    public function update(Request $request, $id)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        $data = $request->validate([
            'region'          => 'required|string|max:255',
            'prefix'          => 'nullable|string|max:10',
            'starting_number' => 'required|integer|min:1',
        ]);

        $setting = $this->service->getForBusiness((int) $id, $businessId);

        if ($this->service->existsRegion($businessId, $data['region'], (int) $id)) {
            return response()->json([
                'success' => false,
                'msg' => __('membership::lang.region_already_exists'),
            ]);
        }

        $this->service->update($setting, $data);

        return response()->json([
            'success' => true,
            'msg' => __('membership::lang.setting_updated_successfully'),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        try {
            $businessId = (int) $request->session()->get('user.business_id');
            $setting = $this->service->getForBusiness((int) $id, $businessId);
            $this->service->delete($setting);

            return response()->json([
                'success' => true,
                'msg' => __('membership::lang.setting_deleted_successfully'),
            ]);
        } catch (\Exception $e) {
            Log::emergency('MEM-006 PrefixStartingNumber destroy failed', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }
}

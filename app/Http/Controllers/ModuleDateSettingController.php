<?php

namespace App\Http\Controllers;

use App\BusinessModuleDateSetting;
use App\Services\ModuleDateDefaultService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ModuleDateSettingController extends Controller
{
    public function index(Request $request, ModuleDateDefaultService $service)
    {
        $this->authorizeBusinessSettings();

        $businessId = $this->businessId($request);
        $modules = $service->availableModules();
        $setting = null;

        if ($service->settingsTableExists()) {
            $setting = BusinessModuleDateSetting::where('business_id', $businessId)->first();
        }

        $moduleSettings = $setting && is_array($setting->module_settings)
            ? $setting->module_settings
            : [];
        $globalDate = $setting && !empty($setting->global_date)
            ? $setting->global_date->format('Y-m-d')
            : now()->toDateString();

        return view('business.module_date_defaults', compact(
            'modules',
            'moduleSettings',
            'globalDate',
            'setting'
        ));
    }

    public function update(Request $request, ModuleDateDefaultService $service)
    {
        $this->authorizeBusinessSettings();

        if (!$service->settingsTableExists()) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => 'Module Date Defaults table is not installed in this tenant database. Run the supplied migration/SQL first.',
            ]);
        }

        $businessId = $this->businessId($request);
        $modules = $service->availableModules();
        $allowedKeys = array_keys($modules);

        $validated = $request->validate([
            'global_date' => 'nullable|date_format:Y-m-d',
            'module_settings' => 'nullable|array',
            'module_settings.*' => 'nullable|in:computer,global',
        ]);

        $submitted = isset($validated['module_settings']) && is_array($validated['module_settings'])
            ? $validated['module_settings']
            : [];

        $moduleSettings = [];
        foreach ($allowedKeys as $moduleKey) {
            $source = isset($submitted[$moduleKey]) ? strtolower((string) $submitted[$moduleKey]) : ModuleDateDefaultService::SOURCE_COMPUTER;
            $moduleSettings[$moduleKey] = $source === ModuleDateDefaultService::SOURCE_GLOBAL
                ? ModuleDateDefaultService::SOURCE_GLOBAL
                : ModuleDateDefaultService::SOURCE_COMPUTER;
        }

        if (in_array(ModuleDateDefaultService::SOURCE_GLOBAL, $moduleSettings, true) && empty($validated['global_date'])) {
            throw ValidationException::withMessages([
                'global_date' => 'Global Date is required because one or more modules use Global Date.',
            ]);
        }

        $userId = optional($request->user())->id ?: $request->session()->get('user.id');
        $record = BusinessModuleDateSetting::firstOrNew(['business_id' => $businessId]);
        if (!$record->exists) {
            $record->created_by = $userId;
        }
        $record->global_date = !empty($validated['global_date']) ? $validated['global_date'] : null;
        $record->module_settings = $moduleSettings;
        $record->updated_by = $userId;
        $record->save();

        return redirect()->route('business.module-date-defaults.index')->with('status', [
            'success' => 1,
            'msg' => 'Module date defaults updated successfully.',
        ]);
    }

    protected function authorizeBusinessSettings()
    {
        if (!auth()->check() || !auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function businessId(Request $request)
    {
        $businessId = (int) (
            optional($request->user())->business_id
            ?: $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
        );

        if ($businessId < 1) {
            abort(403, 'Business context is required.');
        }

        return $businessId;
    }
}

<?php

namespace Modules\MyHealthMembers\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Services\Admin\MyHealthAdministrationService;

class MyHealthSystemSettingsController extends Controller
{
    public function index(MyHealthAdministrationService $service)
    {
        return view('myhealthmembers::admin.settings.index', [
            'groups' => $service->settingsGroups(),
        ]);
    }

    public function store(Request $request)
    {
        if (DB::getSchemaBuilder()->hasTable('myhealth_settings')) {
            foreach ((array) $request->input('settings', []) as $key => $value) {
                DB::table('myhealth_settings')->updateOrInsert(
                    ['key' => $key],
                    ['value' => is_array($value) ? json_encode($value) : $value, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        return redirect()->route('myhealth.admin.settings.index')->with('status', 'My Health settings updated successfully.');
    }
}

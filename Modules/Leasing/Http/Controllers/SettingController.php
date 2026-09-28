<?php

namespace Modules\Leasing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Leasing\Models\LeasingSetting;

class SettingController extends Controller
{
    public function index()
    {
        $settings = LeasingSetting::pluck('value', 'key')->toArray();
        return view('leasing::settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            LeasingSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return redirect()->route('leasing.settings.index')->with('status', ['success' => 1, 'msg' => 'Settings saved successfully']);
    }
}

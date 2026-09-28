<?php

namespace Modules\Pawning\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Pawning\Models\PawningSetting;

class SettingController extends Controller
{
    public function index()
    {
        $settings = PawningSetting::pluck('value', 'key')->toArray();
        return view('pawning::settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            PawningSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        return redirect()->route('pawning.settings.index')->with('status', ['success' => 1, 'msg' => 'Settings saved successfully']);
    }
}

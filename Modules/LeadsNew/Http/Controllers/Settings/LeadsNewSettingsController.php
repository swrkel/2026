<?php

namespace Modules\LeadsNew\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\LeadsNew\Models\LeadsNewSetting;

class LeadsNewSettingsController extends Controller
{
    public function index()
    {
        $businessId = session('business.id') ?? session('user.business_id');
        $numbering = LeadsNewSetting::getValue('numbering', ['prefix' => 'LN', 'next_no' => 1], $businessId);
        $defaults = LeadsNewSetting::getValue('defaults', ['status' => 'New', 'source' => 'Direct'], $businessId);

        return view('leadsnew::settings.index', compact('numbering', 'defaults'));
    }

    public function saveNumbering(Request $request)
    {
        $data = $request->validate([
            'prefix' => ['required','string','max:10'],
            'next_no' => ['required','integer','min:1'],
        ]);
        LeadsNewSetting::setValue('numbering', $data, session('business.id') ?? session('user.business_id'));
        return back()->with('status', __('leadsnew::lang.settings_saved'));
    }

    public function saveDefaults(Request $request)
    {
        $data = $request->validate([
            'status' => ['required','string','max:50'],
            'source' => ['required','string','max:50'],
        ]);
        LeadsNewSetting::setValue('defaults', $data, session('business.id') ?? session('user.business_id'));
        return back()->with('status', __('leadsnew::lang.settings_saved'));
    }
}

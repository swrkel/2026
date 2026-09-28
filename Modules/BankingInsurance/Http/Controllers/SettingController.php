<?php

namespace Modules\BankingInsurance\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BankingInsurance\Entities\Setting;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::where('business_id', $this->businessId())->pluck('value', 'key');
        return view('bankinginsurance::settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            Setting::updateOrCreate(['business_id' => $this->businessId(), 'key' => $key], ['value' => $value]);
        }
        return back()->with('status', ['success' => 1, 'msg' => 'Settings saved']);
    }
}

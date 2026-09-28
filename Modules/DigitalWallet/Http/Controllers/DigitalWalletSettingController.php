<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Entities\DigitalWalletSetting;

class DigitalWalletSettingController extends Controller
{
    public function index()
    {
        $settings = DigitalWalletSetting::pluck('setting_value', 'setting_key')->toArray();
        return view('digitalwallet::settings.index', compact('settings'));
    }

    public function store(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            DigitalWalletSetting::updateOrCreate(['setting_key' => $key], ['setting_value' => $value, 'setting_type' => 'string']);
        }

        return back()->with('status', 'Settings saved successfully.');
    }
}

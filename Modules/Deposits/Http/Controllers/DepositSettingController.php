<?php

namespace Modules\Deposits\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Deposits\Services\DepositSettingsService;

class DepositSettingController extends Controller
{
    public function index(DepositSettingsService $service)
    {
        $settings = $service->all();
        return view('deposits::settings.index', compact('settings'));
    }

    public function update(Request $request, DepositSettingsService $service)
    {
        $data = $request->validate([
            'account_prefix' => 'nullable|string|max:20',
            'certificate_prefix' => 'nullable|string|max:20',
            'transaction_prefix' => 'nullable|string|max:20',
            'default_interest_frequency' => 'nullable|string|max:50',
            'default_interest_method' => 'nullable|string|max:50',
            'allow_negative_balance' => 'nullable|in:0,1',
            'require_nominee' => 'nullable|in:0,1',
            'require_beneficiary' => 'nullable|in:0,1',
        ]);

        $service->save($data);
        return redirect()->route('deposits.settings.index')->with('status', ['success' => 1, 'msg' => 'Deposit settings saved successfully.']);
    }
}

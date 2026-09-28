<?php

namespace Modules\Purchase\Http\Controllers\Setting;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Purchase\Services\Settings\PurchaseSettingService;

class PurchaseSettingController extends Controller
{
    public function index()
    {
        return view('purchase::settings.index');
    }

    public function saveNumbering(Request $request, PurchaseSettingService $service)
    {
        $service->saveNumbering($request->all());

        return back()->with('status', [
            'success' => true,
            'msg' => __('purchase::lang.settings_saved')
        ]);
    }
}

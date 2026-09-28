<?php

namespace Modules\SettlementSW\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SettlementSwTempController extends Controller
{
    /**
     * Store unfinished Settlement SW form data inside the user session.
     *
     * This avoids depending on the main TempController while preserving the
     * auto-save request used by the Settlement SW create page.
     */
    public function save(Request $request)
    {
        $businessId = (int) $request->session()->get('business.id');
        $userId = optional($request->user())->id ?: 'guest';
        $key = "settlement_sw_temp_{$businessId}_{$userId}";

        $request->session()->put($key, [
            'saved_at' => now()->format('Y-m-d H:i:s'),
            'payload' => $request->except(['_token']),
        ]);

        return response()->json([
            'success' => 1,
            'msg' => __('settlementsw::lang.success'),
        ]);
    }
}

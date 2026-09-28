<?php

namespace Modules\PumperDashboardNew\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    protected function businessId(): int
    {
        $businessId = (int) session('business.id', session('user.business_id', 0));
        abort_if($businessId <= 0, 403, __('pumperdashboardnew::lang.business_context_missing'));
        return $businessId;
    }

    protected function ok(string $message, array $extra = [])
    {
        if (request()->expectsJson()) return response()->json(['success' => true, 'message' => $message] + $extra);
        return back()->with('status', ['success' => 1, 'msg' => $message]);
    }
}

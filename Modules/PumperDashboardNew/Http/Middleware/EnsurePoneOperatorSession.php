<?php

namespace Modules\PumperDashboardNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsurePoneOperatorSession
{
    public function handle(Request $request, Closure $next)
    {
        $profileId = (int) $request->session()->get('pone.operator_profile_id');
        $businessId = (int) $request->session()->get('pone.business_id');
        $userId = (int) $request->session()->get('pone.user_id');

        if (! auth()->check() || $profileId <= 0 || $businessId <= 0 || $userId !== (int) auth()->id()) {
            return redirect()->route('pumper-dashboard-new.login', [
                'cc' => $request->session()->get('pone.company_number'),
            ])->with('status', ['success' => 0, 'msg' => __('pumperdashboardnew::lang.session_expired')]);
        }

        $valid = DB::table('pone_pd_operators')
            ->where('id', $profileId)
            ->where('business_id', $businessId)
            ->where('user_id', $userId)
            ->where('login_enabled', 1)
            ->where('status', 'active')
            ->exists();

        if (! $valid) {
            $companyNumber = $request->session()->get('pone.company_number');
            $request->session()->forget('pone');
            return redirect()->route('pumper-dashboard-new.login', [
                'cc' => $companyNumber,
            ])->with('status', ['success' => 0, 'msg' => __('pumperdashboardnew::lang.operator_access_disabled')]);
        }

        DB::table('pone_operator_sessions')
            ->where('session_key', $request->session()->get('pone.session_key'))
            ->where('status', 'active')
            ->update(['last_seen_at' => now(), 'updated_at' => now()]);

        return $next($request);
    }
}

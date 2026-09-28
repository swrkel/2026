<?php

namespace App\Http\Middleware;

use Closure;
use App\UserSetting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class isVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        if ($user) {
            $sessionKey = 'erp_perf.verification_flags.' . (int) $user->id;
            $flags = $request->session()->get($sessionKey);

            // Verification settings change rarely, but this middleware runs on
            // nearly every authenticated page. Keep the flags in the existing
            // tenant session and refresh them once per minute instead of issuing
            // a user_settings query on every browser refresh and Ajax request.
            if (!is_array($flags)
                || (int) ($flags['cached_at'] ?? 0) < (time() - 60)) {
                $setting = UserSetting::query()
                    ->where('user_id', $user->id)
                    ->first(['opt_verification_enabled', 'verification_done']);

                $flags = [
                    'enabled' => (bool) optional($setting)->opt_verification_enabled,
                    'done' => (bool) optional($setting)->verification_done,
                    'cached_at' => time(),
                ];
                $request->session()->put($sessionKey, $flags);
            }

            $isSuperAdminLogin = class_exists(\App\Services\Authorization\SuperAdminImpersonation::class)
                && \App\Services\Authorization\SuperAdminImpersonation::isActive($user, $request);
            if (!$isSuperAdminLogin
                && !empty($flags['enabled'])
                && empty($flags['done'])) {
                return redirect('/login');
            }
        }

        return $next($request);
                
        
    }
}

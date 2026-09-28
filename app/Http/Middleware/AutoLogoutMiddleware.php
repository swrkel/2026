<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AutoLogoutMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $autoLogoutTime = max(7200, ((int) config('session.lifetime', 120)) * 60); // Follow the configured session lifetime; minimum 120 minutes
        if (Auth::check()) {
            $lastActivity = Session::get('lastActivityTime');
            if ($lastActivity !== null && time() - $lastActivity > $autoLogoutTime) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->to($request->getSchemeAndHttpHost() . '/login')->with('warning', 'You have been automatically logged out due to inactivity.');
            }
            Session::put('lastActivityTime', time());
        }
        return $next($request);
    }
}

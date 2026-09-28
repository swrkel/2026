<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class RecoveryOfficerMiddleware
{
    /**
     * Handle incoming request.
     */

    public function handle(
        Request $request,
        Closure $next
    ) {

        /*
        |--------------------------------------------------------------------------
        | Must Be Logged In
        |--------------------------------------------------------------------------
        */

        if (!Auth::check()) {

            return redirect('/login');
        }

        $user = Auth::user();

        /*
        |--------------------------------------------------------------------------
        | Admin Bypass
        |--------------------------------------------------------------------------
        */

        if ($user->hasRole('admin')) {

            return $next($request);
        }

        /*
        |--------------------------------------------------------------------------
        | Recovery Officer Access
        |--------------------------------------------------------------------------
        */

        if (
            !$user->hasRole('recovery_officer')
            &&
            !$user->hasRole('collection_manager')
        ) {

            abort(
                403,
                'Recovery access only.'
            );
        }

        return $next($request);
    }
}
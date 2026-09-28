<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class BorrowerMiddleware
{
    /**
     * Handle incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
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
        | Borrower Role Required
        |--------------------------------------------------------------------------
        */

        if (!$user->hasRole('borrower')) {

            abort(
                403,
                'Borrower access only.'
            );
        }

        return $next($request);
    }
}
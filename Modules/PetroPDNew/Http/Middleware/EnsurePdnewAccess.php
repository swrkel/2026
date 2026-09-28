<?php

namespace Modules\PetroPDNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsurePdnewAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) abort(401);

        if (method_exists($user, 'can') && ! $user->can('petro_pd_new.access')) {
            abort(403, 'Petro PD-New is not enabled for this user.');
        }

        return $next($request);
    }
}

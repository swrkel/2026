<?php
namespace Modules\DealerManagement\Http\Middleware;

use Closure;
use Modules\DealerManagement\Services\DealerContext;

class EnsureDealerAuthenticated
{
    public function handle($request, Closure $next)
    {
        $user = app(DealerContext::class)->user();
        if (!$user) return redirect()->route('dealermanagement.login')->with('error','Please log in to Dealer Management.');
        $request->attributes->set('dealer_user',$user);
        return $next($request);
    }
}

<?php
namespace Modules\DealerManagement\Http\Middleware;
use Closure;
class EnsureHubAuthenticated
{
    public function handle($request, Closure $next)
    {
        if(!app(\Modules\DealerManagement\Services\HubContext::class)->user()) return redirect()->route('dealermanagement.hub.login');
        return $next($request);
    }
}

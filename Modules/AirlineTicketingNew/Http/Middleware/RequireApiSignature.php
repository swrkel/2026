<?php
namespace Modules\AirlineTicketingNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireApiSignature
{
    public function handle(Request $request,Closure $next)
    {
        $signature=(string)$request->header('X-ATN-Signature');
        $timestamp=(string)$request->header('X-ATN-Timestamp');

        abort_if(!$signature || !$timestamp,401);
        abort_if(abs(now()->timestamp-(int)$timestamp)>300,401);

        return $next($request);
    }
}

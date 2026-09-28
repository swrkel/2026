<?php
namespace Modules\AirlineTicketingNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ThrottleAirlineTicketingApi
{
    public function handle(Request $request,Closure $next,int $maxAttempts=60)
    {
        $businessId=(int)$request->attributes->get('atn_api_business_id');
        $key='atn-api:'.$businessId.':'.sha1($request->ip().'|'.$request->path());
        $attempts=(int)Cache::get($key,0);

        abort_if($attempts >= $maxAttempts,429,'Too many requests.');

        Cache::put($key,$attempts+1,now()->addMinute());

        return $next($request);
    }
}

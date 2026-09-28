<?php

namespace Modules\MyHealthMembers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\MyHealthMembers\Services\MyHealthMobileTokenService;

class MyHealthMobileAuth
{
    public function handle(Request $request, Closure $next)
    {
        $bearer = $request->bearerToken();

        if (empty($bearer)) {
            return response()->json(['success' => false, 'message' => 'Mobile access token is required.'], 401);
        }

        $token = app(MyHealthMobileTokenService::class)->findValid($bearer);

        if (! $token || ! $token->member) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired mobile access token.'], 401);
        }

        $request->attributes->set('myhealth_member', $token->member);
        $request->attributes->set('myhealth_mobile_token', $token);

        return $next($request);
    }
}

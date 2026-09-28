<?php
namespace Modules\AirlineTicketingNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\AirlineTicketingNew\Entities\ApiCredential;

class ValidateAirlineTicketingApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $token = (string) $request->bearerToken();

        $credential = ApiCredential::query()
            ->where('is_active', true)
            ->where('credential_code', hash('sha256', $token))
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        abort_unless($credential, 401);

        $request->attributes->set('atn_api_business_id', $credential->business_id);
        $credential->update(['last_used_at' => now()]);

        return $next($request);
    }
}

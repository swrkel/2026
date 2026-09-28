<?php

namespace Modules\Customers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\Customer;

class EnsureDealerApiToken
{
    public function handle(Request $request, Closure $next)
    {
        if (!Schema::hasTable('customer_portal_api_tokens')) {
            return response()->json([
                'success' => false,
                'message' => 'Customer dealer API token table is missing. Please run CUS_031 SQL first.',
            ], 503);
        }

        $token = $this->bearerToken($request);
        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Dealer API token is required.',
            ], 401);
        }

        $tokenHash = hash('sha256', $token);
        $row = DB::table('customer_portal_api_tokens')
            ->where('token_hash', $tokenHash)
            ->whereNull('revoked_at')
            ->first();

        if (empty($row)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired dealer API token.',
            ], 401);
        }

        if (!empty($row->expires_at) && strtotime($row->expires_at) < time()) {
            return response()->json([
                'success' => false,
                'message' => 'Dealer API token has expired. Please login again.',
            ], 401);
        }

        $customer = Customer::where('business_id', (int) $row->business_id)
            ->whereIn('type', ['customer', 'both'])
            ->whereNull('deleted_at')
            ->find((int) $row->contact_id);

        if (empty($customer)) {
            return response()->json([
                'success' => false,
                'message' => 'Dealer account is not available.',
            ], 401);
        }

        DB::table('customer_portal_api_tokens')
            ->where('id', $row->id)
            ->update([
                'last_used_at' => now(),
                'updated_at' => now(),
            ]);

        $request->attributes->set('dealer_api_token_id', (int) $row->id);
        $request->attributes->set('dealer_business_id', (int) $row->business_id);
        $request->attributes->set('dealer_customer_id', (int) $row->contact_id);
        $request->attributes->set('dealer_customer', $customer);

        return $next($request);
    }

    protected function bearerToken(Request $request): ?string
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            $token = $request->header('X-Dealer-Token');
        }

        if (empty($token)) {
            $token = $request->input('api_token');
        }

        return $token ? trim($token) : null;
    }
}

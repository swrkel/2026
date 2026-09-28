<?php

namespace Modules\Customers\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Customers\Entities\Customer;

class AuthController extends BaseDealerApiController
{
    public function login(Request $request)
    {
        if (!Schema::hasTable('customer_portal_api_tokens')) {
            return $this->fail('Customer dealer API token table is missing. Please run CUS_031 SQL first.', 503);
        }

        $data = $request->validate([
            'passcode' => 'required|digits:4',
            'company_number' => 'nullable|string|max:50',
            'device_name' => 'nullable|string|max:191',
        ]);

        $businessId = $this->resolveBusinessId($data['company_number'] ?? null);
        if ($businessId <= 0) {
            return $this->fail('Business could not be resolved for dealer login.', 422);
        }

        $customer = Customer::where('business_id', $businessId)
            ->whereIn('type', ['customer', 'both'])
            ->where('active', 1)
            ->whereNull('deleted_at')
            ->where('customer_passcode', $data['passcode'])
            ->first();

        if (empty($customer)) {
            return $this->fail('Invalid Distribution Dealer passcode.', 401);
        }

        $plainToken = bin2hex(random_bytes(40));
        $tokenHash = hash('sha256', $plainToken);

        DB::table('customer_portal_api_tokens')->insert([
            'business_id' => $businessId,
            'contact_id' => (int) $customer->id,
            'token_hash' => $tokenHash,
            'device_name' => $data['device_name'] ?? $request->userAgent(),
            'ip_address' => $request->ip(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays(30),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success([
            'token_type' => 'Bearer',
            'access_token' => $plainToken,
            'expires_at' => now()->addDays(30)->toDateTimeString(),
            'customer' => $this->customerPayload($customer),
        ], 'Dealer API login successful.');
    }

    public function logout(Request $request)
    {
        $tokenId = (int) $request->attributes->get('dealer_api_token_id', 0);
        if ($tokenId > 0 && Schema::hasTable('customer_portal_api_tokens')) {
            DB::table('customer_portal_api_tokens')
                ->where('id', $tokenId)
                ->update([
                    'revoked_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        return $this->success([], 'Logged out successfully.');
    }

    public function me(Request $request)
    {
        return $this->success([
            'customer' => $this->customerPayload($this->customer($request)),
            'summary' => $this->portalSummary($this->businessId($request), $this->customerId($request)),
        ]);
    }

    protected function resolveBusinessId(?string $companyNumber): int
    {
        if (!Schema::hasTable('business')) {
            return 0;
        }

        if (!empty($companyNumber)) {
            return (int) DB::table('business')
                ->where('company_number', $companyNumber)
                ->value('id');
        }

        return (int) DB::table('business')->orderBy('id')->value('id');
    }

    protected function customerPayload(?Customer $customer): array
    {
        if (empty($customer)) {
            return [];
        }

        return [
            'id' => (int) $customer->id,
            'contact_id' => $customer->contact_id,
            'name' => $customer->name,
            'mobile' => $customer->mobile,
            'email' => $customer->email,
            'credit_limit' => (float) ($customer->credit_limit ?? 0),
        ];
    }
}

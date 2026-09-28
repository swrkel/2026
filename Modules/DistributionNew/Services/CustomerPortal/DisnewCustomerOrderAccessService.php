<?php

namespace Modules\DistributionNew\Services\CustomerPortal;

use Illuminate\Support\Str;
use Modules\DistributionNew\Models\DisnewCustomerAccessToken;

class DisnewCustomerOrderAccessService
{
    public function createAccessToken($businessId, $customerId, $mobile = null): DisnewCustomerAccessToken
    {
        return DisnewCustomerAccessToken::create([
            'business_id' => $businessId,
            'customer_id' => $customerId,
            'mobile' => $mobile,
            'token' => hash('sha256', Str::random(80)),
            'expires_at' => now()->addDays(7),
            'is_used' => 0,
        ]);
    }

    public function validateToken(string $token): ?DisnewCustomerAccessToken
    {
        return DisnewCustomerAccessToken::where('token', hash('sha256', $token))
            ->where('is_used', 0)
            ->where('expires_at', '>=', now())
            ->first();
    }
}

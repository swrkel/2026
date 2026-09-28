<?php

namespace Modules\IdentityAccess\Services\Session;

use Illuminate\Support\Str;
use Modules\IdentityAccess\Entities\IdentityAccessLoginIdentity;
use Modules\IdentityAccess\Entities\IdentityAccessSession;

class IdentitySessionService
{
    public function create(IdentityAccessLoginIdentity $identity, array $context = []): IdentityAccessSession
    {
        return IdentityAccessSession::create([
            'business_id' => $identity->business_id,
            'location_id' => $identity->location_id,
            'login_identity_id' => $identity->id,
            'portal_type' => $identity->portal_type,
            'session_token' => hash('sha256', Str::uuid() . Str::random(40)),
            'device_name' => $context['device_name'] ?? null,
            'browser' => $context['browser'] ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'trusted_device' => false,
            'logged_in_at' => now(),
            'expires_at' => now()->addMinutes(config('identityaccess.session_lifetime_minutes', 120)),
            'status' => 'active',
        ]);
    }

    public function revoke(int $sessionId): bool
    {
        $session = IdentityAccessSession::find($sessionId);
        if (!$session) {
            return false;
        }
        $session->update(['status' => 'revoked', 'logged_out_at' => now()]);
        return true;
    }
}

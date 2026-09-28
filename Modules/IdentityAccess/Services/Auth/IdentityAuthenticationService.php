<?php

namespace Modules\IdentityAccess\Services\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\IdentityAccess\Entities\IdentityAccessLoginIdentity;
use Modules\IdentityAccess\Entities\IdentityAccessSecurityEvent;
use Modules\IdentityAccess\Services\Session\IdentitySessionService;

class IdentityAuthenticationService
{
    public function __construct(private IdentitySessionService $sessions) {}

    public function authenticatePasscode(string $portalType, string $passcode, array $context = []): array
    {
        $identity = IdentityAccessLoginIdentity::where('portal_type', $portalType)
            ->where('status', 'active')
            ->get()
            ->first(fn ($row) => $row->passcode_hash && Hash::check($passcode, $row->passcode_hash));

        if (!$identity) {
            $this->event(null, $portalType, 'login_failed', 'warning', 'Invalid passcode login attempt.', $context);
            return ['success' => false, 'message' => 'Invalid passcode.'];
        }

        if ($identity->locked_until && now()->lessThan($identity->locked_until)) {
            $this->event($identity->id, $portalType, 'login_locked', 'warning', 'Login blocked because account is locked.', $context);
            return ['success' => false, 'message' => 'Account is temporarily locked.'];
        }

        $session = $this->sessions->create($identity, $context);
        $identity->update(['last_login_at' => now(), 'failed_attempts' => 0]);
        $this->event($identity->id, $portalType, 'login_success', 'info', 'Login successful.', $context);

        return ['success' => true, 'identity' => $identity, 'session' => $session];
    }

    public function registerPasscodeIdentity(string $portalType, string $identifier, string $passcode, array $data = []): IdentityAccessLoginIdentity
    {
        return IdentityAccessLoginIdentity::updateOrCreate(
            ['portal_type' => $portalType, 'login_identifier' => $identifier],
            array_merge($data, [
                'portal_type' => $portalType,
                'login_identifier' => $identifier,
                'passcode_hash' => Hash::make($passcode),
                'status' => $data['status'] ?? 'active',
            ])
        );
    }

    public function generatePasscode(int $digits = 6): string
    {
        $min = (int) str_pad('1', $digits, '0');
        $max = (int) str_repeat('9', $digits);
        return (string) random_int($min, $max);
    }

    private function event(?int $identityId, ?string $portalType, string $type, string $severity, string $description, array $context): void
    {
        IdentityAccessSecurityEvent::create([
            'business_id' => $context['business_id'] ?? null,
            'location_id' => $context['location_id'] ?? null,
            'login_identity_id' => $identityId,
            'portal_type' => $portalType,
            'event_type' => $type,
            'severity' => $severity,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $context,
        ]);
    }
}

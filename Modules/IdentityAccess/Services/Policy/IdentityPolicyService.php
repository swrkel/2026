<?php

namespace Modules\IdentityAccess\Services\Policy;

class IdentityPolicyService
{
    public function otpRequired(string $portalType, array $context = []): bool
    {
        return (bool) ($context['otp_required'] ?? false);
    }

    public function canAccessOwnRecord(int|string $authenticatedOwnerId, int|string $requestedOwnerId): bool
    {
        return (string) $authenticatedOwnerId === (string) $requestedOwnerId;
    }
}

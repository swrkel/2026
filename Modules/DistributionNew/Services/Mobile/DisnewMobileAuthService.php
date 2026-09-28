<?php

namespace Modules\DistributionNew\Services\Mobile;

use Illuminate\Support\Str;
use Modules\DistributionNew\Entities\DisnewMobileToken;
use Modules\DistributionNew\Entities\DisnewOfflineDevice;

class DisnewMobileAuthService
{
    public function login(array $payload, $user): array
    {
        $deviceUuid = $payload['device_uuid'] ?? (string) Str::uuid();
        DisnewOfflineDevice::updateOrCreate(['device_uuid' => $deviceUuid], [
            'business_id' => $payload['business_id'] ?? session('business.id'),
            'location_id' => $payload['location_id'] ?? null,
            'user_id' => $user->id ?? null,
            'device_name' => $payload['device_name'] ?? 'Mobile Device',
            'platform' => $payload['platform'] ?? 'unknown',
            'last_seen_at' => now(),
            'status' => 'active',
        ]);

        $token = hash('sha256', Str::random(80));
        DisnewMobileToken::create([
            'business_id' => $payload['business_id'] ?? session('business.id'),
            'user_id' => $user->id ?? null,
            'device_uuid' => $deviceUuid,
            'token' => $token,
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);

        return ['success' => true, 'device_uuid' => $deviceUuid, 'token' => $token];
    }

    public function logout(?string $deviceUuid, ?int $userId): array
    {
        DisnewMobileToken::where('device_uuid', $deviceUuid)->where('user_id', $userId)->update(['status' => 'revoked']);
        return ['success' => true];
    }
}

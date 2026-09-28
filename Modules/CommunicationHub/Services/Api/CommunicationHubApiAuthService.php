<?php

namespace Modules\CommunicationHub\Services\Api;

use Illuminate\Http\Request;
use Modules\CommunicationHub\Entities\CommunicationHubApiClient;

class CommunicationHubApiAuthService
{
    public function authenticate(Request $request): ?CommunicationHubApiClient
    {
        $token = (string) ($request->bearerToken() ?: $request->header('X-CommunicationHub-Token'));
        if ($token === '') {
            return null;
        }

        $client = CommunicationHubApiClient::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('is_active', 1)
            ->first();

        if (! $client) {
            return null;
        }

        $allowedIps = $client->allowed_ips ?: [];
        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps, true)) {
            return null;
        }

        $client->forceFill(['last_used_at' => now()])->save();
        return $client;
    }

    public function canUseChannel(CommunicationHubApiClient $client, string $channel): bool
    {
        $channels = $client->allowed_channels ?: [];
        return empty($channels) || in_array($channel, $channels, true);
    }
}

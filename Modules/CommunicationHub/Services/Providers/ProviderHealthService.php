<?php

namespace Modules\CommunicationHub\Services\Providers;

use Modules\CommunicationHub\Entities\CommunicationHubProvider;

class ProviderHealthService
{
    public function __construct(protected ProviderDriverRegistry $registry) {}

    public function check(CommunicationHubProvider $provider): array
    {
        $startedAt = microtime(true);
        $driver = $this->registry->get($provider->driver);

        if (! $driver) {
            $result = [
                'healthy' => false,
                'response_code' => 'DRIVER_NOT_FOUND',
                'response_message' => 'Provider driver is not registered.',
            ];
        } else {
            $result = $driver->healthCheck($provider->provider_config ?? []);
        }

        $provider->forceFill([
            'health_status' => ! empty($result['healthy']) ? 'healthy' : 'failed',
            'last_health_check_at' => now(),
            'last_response_code' => $result['response_code'] ?? null,
            'last_response_message' => $result['response_message'] ?? null,
            'average_response_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ])->save();

        return $result;
    }
}

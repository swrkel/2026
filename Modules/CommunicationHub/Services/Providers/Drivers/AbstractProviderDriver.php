<?php

namespace Modules\CommunicationHub\Services\Providers\Drivers;

use Modules\CommunicationHub\Contracts\CommunicationHubProviderDriverInterface;

abstract class AbstractProviderDriver implements CommunicationHubProviderDriverInterface
{
    public function healthCheck(array $providerConfig = []): array
    {
        return [
            'healthy' => true,
            'response_code' => 'OK',
            'response_message' => $this->label() . ' driver is available.',
            'response_time_ms' => 0,
        ];
    }

    protected function simulatedSend(array $message, array $providerConfig = []): array
    {
        if (! empty($providerConfig['force_fail'])) {
            return [
                'success' => false,
                'response_code' => 'FORCED_FAIL',
                'response_message' => 'Provider failed by configuration.',
                'cost' => (float) ($providerConfig['cost_per_message'] ?? 0),
            ];
        }

        return [
            'success' => true,
            'response_code' => 'QUEUED',
            'response_message' => 'Message accepted by ' . $this->label() . '.',
            'provider_reference' => 'CH-' . strtoupper(substr(md5(json_encode($message) . microtime(true)), 0, 12)),
            'cost' => (float) ($providerConfig['cost_per_message'] ?? 0),
        ];
    }
}

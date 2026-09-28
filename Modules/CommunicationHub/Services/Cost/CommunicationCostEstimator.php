<?php

namespace Modules\CommunicationHub\Services\Cost;

use Modules\CommunicationHub\Entities\CommunicationHubProvider;

class CommunicationCostEstimator
{
    public function estimate(string $channel, ?CommunicationHubProvider $provider = null, array $message = []): float
    {
        if ($provider) {
            return (float) ($provider->cost_per_message ?? ($provider->provider_config['cost_per_message'] ?? 0));
        }

        return match ($channel) {
            'sms' => 1.00,
            'whatsapp' => 0.50,
            'email' => 0.00,
            'push' => 0.00,
            default => 0.00,
        };
    }
}

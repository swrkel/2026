<?php

namespace Modules\CommunicationHub\Services\Providers;

use Modules\CommunicationHub\Contracts\CommunicationHubChannelInterface;

class NullChannelProvider implements CommunicationHubChannelInterface
{
    public function __construct(protected string $channel = 'null') {}

    public function channel(): string
    {
        return $this->channel;
    }

    public function send(array $message, array $providerConfig = []): array
    {
        return [
            'success' => true,
            'provider_reference' => 'NULL-' . now()->format('YmdHis') . '-' . random_int(1000, 9999),
            'response_code' => 'NULL_PROVIDER',
            'response_message' => 'Message accepted by standalone null provider. Configure a real provider before production sending.',
            'cost' => 0,
        ];
    }
}

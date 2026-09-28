<?php

namespace Modules\CommunicationHub\Services\Delivery;

use Modules\CommunicationHub\Entities\CommunicationHubDeliveryEvent;
use Modules\CommunicationHub\Entities\CommunicationHubMessage;

class DeliveryTracker
{
    public function event(CommunicationHubMessage $message, string $status, array $payload = []): void
    {
        CommunicationHubDeliveryEvent::create([
            'message_id' => $message->id,
            'status' => $status,
            'provider_id' => $payload['provider_id'] ?? null,
            'provider_reference' => $payload['provider_reference'] ?? null,
            'response_code' => $payload['response_code'] ?? null,
            'response_message' => $payload['response_message'] ?? null,
            'cost' => $payload['cost'] ?? 0,
            'meta' => $payload,
        ]);
    }
}

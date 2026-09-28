<?php

namespace Modules\DistributionNew\Services;

use Modules\DistributionNew\Services\Sms\DisnewSmsEventService;

class DisnewSmsService
{
    public function __construct(protected DisnewSmsEventService $events) {}

    public function queue(int $businessId, ?int $locationId, string $event, ?int $customerId, ?string $customerMobile, string $message, array $payload = []): void
    {
        $payload['number'] = $payload['number'] ?? ($payload['reference'] ?? '');
        $payload['amount'] = $payload['amount'] ?? '';
        $this->events->fire($businessId, $locationId, $event, $customerId, $customerMobile, $payload);
    }
}

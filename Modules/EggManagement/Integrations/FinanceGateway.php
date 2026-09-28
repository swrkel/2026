<?php
namespace Modules\EggManagement\Integrations;

use Modules\EggManagement\Models\IntegrationOutbox;
use Modules\EggManagement\Services\EggContext;

class FinanceGateway
{
    protected $context;
    public function __construct(EggContext $context) { $this->context = $context; }

    public function queue($event, array $payload)
    {
        return IntegrationOutbox::create([
            'business_id' => $this->context->businessId(),
            'event_type' => $event,
            'aggregate_type' => $payload['aggregate_type'] ?? null,
            'aggregate_id' => $payload['aggregate_id'] ?? null,
            'payload' => $payload,
            'attempts' => 0,
            'status' => 'pending',
        ]);
    }
}

<?php

namespace Modules\CommunicationHub\DTO\Wallet;

class WalletChargeRequest
{
    public function __construct(
        public float $amount,
        public string $currency = 'LKR',
        public string $channel = '',
        public ?string $sourceModule = null,
        public ?string $sourceReference = null,
        public ?int $businessId = null,
        public ?int $locationId = null,
        public ?int $messageId = null,
        public array $context = []
    ) {}

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'channel' => $this->channel,
            'source_module' => $this->sourceModule,
            'source_reference' => $this->sourceReference,
            'business_id' => $this->businessId,
            'location_id' => $this->locationId,
            'message_id' => $this->messageId,
            'context' => $this->context,
        ];
    }
}

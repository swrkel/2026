<?php

namespace Modules\CommunicationHub\DTO\Wallet;

class WalletChargeResponse
{
    public function __construct(
        public bool $approved,
        public ?string $transactionReference = null,
        public ?float $remainingBalance = null,
        public ?string $message = null,
        public array $raw = []
    ) {}

    public static function approved(?string $transactionReference = null, ?float $remainingBalance = null, array $raw = []): self
    {
        return new self(true, $transactionReference, $remainingBalance, null, $raw);
    }

    public static function declined(?string $message = null, array $raw = []): self
    {
        return new self(false, null, null, $message, $raw);
    }

    public function toArray(): array
    {
        return [
            'approved' => $this->approved,
            'transaction_reference' => $this->transactionReference,
            'remaining_balance' => $this->remainingBalance,
            'message' => $this->message,
            'raw' => $this->raw,
        ];
    }
}

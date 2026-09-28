<?php

namespace Modules\CommunicationHub\Contracts;

interface CommunicationHubWalletInterface
{
    public function canCharge(float $amount, array $context = []): bool;

    public function charge(float $amount, array $context = []): array;
}

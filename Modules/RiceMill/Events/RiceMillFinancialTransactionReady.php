<?php
namespace Modules\RiceMill\Events;
class RiceMillFinancialTransactionReady
{
    public function __construct(public int $outboxId, public array $payload) {}
}

<?php

namespace Modules\MembershipNew\app\Contracts;

interface MembershipNewCardBridgeContract
{
    public function lookupCard(string $cardNo, int $businessId): array;

    public function issueCard(int $businessId, int $memberId, array $options = []): array;

    public function blockCard(int $businessId, int $cardId, ?string $reason = null): array;
}

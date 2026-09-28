<?php

namespace Modules\MembershipNew\app\Integration;

use Modules\MembershipNew\app\Services\MembershipNewCentralMemberService;
use Modules\MembershipNew\app\Services\MembershipNewBusinessHistoryService;

class MembershipNewCentralCustomerBridge
{
    public function __construct(
        private MembershipNewCentralMemberService $centralService,
        private MembershipNewBusinessHistoryService $historyService
    ) {
    }

    public function registerOrLinkCustomer(array $payload): array
    {
        $central = $this->centralService->findOrCreateCentral([
            'first_name' => $payload['first_name'] ?? null,
            'last_name' => $payload['last_name'] ?? null,
            'mobile' => $payload['mobile'] ?? null,
            'email' => $payload['email'] ?? null,
            'nic' => $payload['nic'] ?? null,
            'address' => $payload['address'] ?? null,
            'date_of_birth' => $payload['date_of_birth'] ?? null,
        ]);

        $map = $this->centralService->linkToBusiness(
            $central->id,
            (int) $payload['business_id'],
            $payload['local_customer_id'] ?? null,
            $payload['local_member_id'] ?? null
        );

        return [
            'central_member_id' => $central->id,
            'central_member_code' => $central->central_member_code,
            'member_business_map_id' => $map->id,
        ];
    }

    public function recordBusinessSale(array $payload): array
    {
        $entry = $this->historyService->recordSale(
            (int) $payload['business_id'],
            (int) $payload['member_business_map_id'],
            (float) $payload['amount'],
            $payload['reference_type'] ?? null,
            $payload['reference_id'] ?? null
        );

        return ['history_id' => $entry->id, 'balance' => $entry->balance];
    }

    public function recordBusinessPayment(array $payload): array
    {
        $entry = $this->historyService->recordPayment(
            (int) $payload['business_id'],
            (int) $payload['member_business_map_id'],
            (float) $payload['amount'],
            $payload['reference_type'] ?? null,
            $payload['reference_id'] ?? null
        );

        return ['history_id' => $entry->id, 'balance' => $entry->balance];
    }
}

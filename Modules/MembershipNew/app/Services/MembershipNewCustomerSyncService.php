<?php

namespace Modules\MembershipNew\app\Services;

use Modules\MembershipNew\app\Models\MembershipNewCustomerMap;
use Modules\MembershipNew\app\Models\MembershipNewLinkedBusiness;
use Modules\MembershipNew\app\Models\MembershipNewMember;

class MembershipNewCustomerSyncService
{
    public function prepareAll(int $businessId): int
    {
        $count = 0;
        MembershipNewMember::forBusiness($businessId)->where('is_active', 1)->chunkById(100, function ($members) use ($businessId, &$count) {
            foreach ($members as $member) {
                $count += $this->prepareMember($businessId, $member->id);
            }
        });

        return $count;
    }

    public function prepareMember(int $businessId, int $memberId): int
    {
        $member = MembershipNewMember::forBusiness($businessId)->findOrFail($memberId);
        $linkedBusinesses = MembershipNewLinkedBusiness::forBusiness($businessId)->where('is_active', 1)->get();

        $count = 0;
        foreach ($linkedBusinesses as $linked) {
            MembershipNewCustomerMap::updateOrCreate(
                [
                    'business_id' => $businessId,
                    'member_id' => $member->id,
                    'linked_business_id' => $linked->linked_business_id,
                ],
                [
                    'member_snapshot' => [
                        'member_code' => $member->member_code,
                        'name' => trim($member->first_name . ' ' . $member->last_name),
                        'mobile' => $member->mobile,
                        'email' => $member->email,
                        'nic' => $member->nic,
                        'address' => $member->address,
                    ],
                    'is_synced' => 0,
                    'last_synced_at' => now(),
                    'note' => 'Prepared by Membership-New sync queue. Final customer insert/update must be handled by tenant/business bridge.',
                ]
            );
            $count++;
        }

        return $count;
    }

    public function markSynced(int $businessId, int $mapId, ?int $linkedCustomerId = null): MembershipNewCustomerMap
    {
        $map = MembershipNewCustomerMap::forBusiness($businessId)->findOrFail($mapId);
        $map->update([
            'linked_customer_id' => $linkedCustomerId,
            'is_synced' => 1,
            'last_synced_at' => now(),
        ]);

        return $map->fresh();
    }
}

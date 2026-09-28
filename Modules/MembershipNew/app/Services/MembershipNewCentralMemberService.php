<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewCentralMember;
use Modules\MembershipNew\app\Models\MembershipNewMemberBusinessMap;

class MembershipNewCentralMemberService
{
    public function findOrCreateCentral(array $data): MembershipNewCentralMember
    {
        return DB::transaction(function () use ($data) {
            $query = MembershipNewCentralMember::query();

            if (!empty($data['nic'])) {
                $existing = (clone $query)->where('nic', $data['nic'])->first();
                if ($existing) {
                    return $existing;
                }
            }

            if (!empty($data['mobile'])) {
                $existing = (clone $query)->where('mobile', $data['mobile'])->first();
                if ($existing) {
                    return $existing;
                }
            }

            if (!empty($data['email'])) {
                $existing = (clone $query)->where('email', $data['email'])->first();
                if ($existing) {
                    return $existing;
                }
            }

            $next = ((int) MembershipNewCentralMember::max('id')) + 1;
            $data['central_member_code'] = $data['central_member_code'] ?? 'CMN-' . str_pad((string) $next, 8, '0', STR_PAD_LEFT);
            $data['is_active'] = $data['is_active'] ?? 1;

            return MembershipNewCentralMember::create($data);
        });
    }

    public function linkToBusiness(int $centralMemberId, int $businessId, ?int $localCustomerId = null, ?int $localMemberId = null): MembershipNewMemberBusinessMap
    {
        return MembershipNewMemberBusinessMap::updateOrCreate(
            [
                'central_member_id' => $centralMemberId,
                'business_id' => $businessId,
            ],
            [
                'local_customer_id' => $localCustomerId,
                'local_member_id' => $localMemberId,
                'points_enabled' => 1,
                'dividend_enabled' => 1,
                'is_active' => 1,
                'last_activity_at' => now(),
            ]
        );
    }
}

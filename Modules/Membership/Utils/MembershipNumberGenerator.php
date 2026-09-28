<?php

namespace Modules\Membership\Utils;

use Illuminate\Support\Facades\DB;
use Modules\Membership\Entities\MembershipMember;
use Modules\Membership\Entities\PrefixStartingNumber;

class MembershipNumberGenerator
{
    public function nextMemberNumber(int $businessId, ?int $locationId = null): string
    {
        return DB::transaction(function () use ($businessId, $locationId) {
            $setup = PrefixStartingNumber::where('business_id', $businessId)
                ->when($locationId, function ($query) use ($locationId) {
                    $query->where(function ($q) use ($locationId) {
                        $q->whereNull('location_id')->orWhere('location_id', $locationId);
                    });
                })
                ->orderByRaw('location_id IS NULL ASC')
                ->lockForUpdate()
                ->first();

            $prefix = $setup->member_prefix ?? $setup->membership_prefix ?? 'MEM-';
            $startNo = (int) ($setup->member_starting_number ?? $setup->starting_number ?? 1);

            $latest = MembershipMember::where('business_id', $businessId)
                ->when($locationId, function ($query) use ($locationId) {
                    $query->where('location_id', $locationId);
                })
                ->whereNotNull('member_no')
                ->where('member_no', 'LIKE', $prefix . '%')
                ->orderByDesc('id')
                ->value('member_no');

            $latestNo = $this->extractTrailingNumber($latest);
            $next = max($startNo, $latestNo + 1);

            return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
        });
    }

    public function extractTrailingNumber(?string $value): int
    {
        if (empty($value)) {
            return 0;
        }

        preg_match_all('/\d+/', $value, $matches);
        return empty($matches[0]) ? 0 : (int) end($matches[0]);
    }
}

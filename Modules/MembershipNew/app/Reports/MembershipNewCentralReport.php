<?php

namespace Modules\MembershipNew\app\Reports;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewBusinessCustomerHistory;
use Modules\MembershipNew\app\Models\MembershipNewMemberBusinessMap;

class MembershipNewCentralReport
{
    public function centralMembers()
    {
        return DB::table('mn_central_members')
            ->whereNull('deleted_at')
            ->orderByDesc('id');
    }

    public function businessMembers(int $businessId)
    {
        return MembershipNewMemberBusinessMap::with('centralMember')
            ->forBusiness($businessId)
            ->latest();
    }

    public function businessHistory(int $businessId, ?int $mapId = null, ?string $search = null)
    {
        return MembershipNewBusinessCustomerHistory::with('businessMap.centralMember')
            ->forBusiness($businessId)
            ->when($mapId, fn ($q) => $q->where('member_business_map_id', $mapId))
            ->when($search, function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('transaction_type', 'like', $like)
                        ->orWhere('reference_type', 'like', $like)
                        ->orWhere('reference_id', 'like', $like)
                        ->orWhere('note', 'like', $like)
                        ->orWhereHas('businessMap.centralMember', function ($member) use ($like) {
                            $member->where('central_member_code', 'like', $like)->orWhere('first_name', 'like', $like)->orWhere('last_name', 'like', $like);
                        });
                });
            })
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
    }
}

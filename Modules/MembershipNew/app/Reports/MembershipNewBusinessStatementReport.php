<?php

namespace Modules\MembershipNew\app\Reports;

use Modules\MembershipNew\app\Models\MembershipNewBusinessCustomerHistory;

class MembershipNewBusinessStatementReport
{
    public function query(int $businessId, ?int $mapId = null, ?string $fromDate = null, ?string $toDate = null, ?string $search = null)
    {
        return MembershipNewBusinessCustomerHistory::with('businessMap.centralMember')
            ->forBusiness($businessId)
            ->when($mapId, fn ($q) => $q->where('member_business_map_id', $mapId))
            ->when($fromDate, fn ($q) => $q->whereDate('transaction_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('transaction_date', '<=', $toDate))
            ->when($search, function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where(function ($sub) use ($like) {
                    $sub->where('transaction_type', 'like', $like)
                        ->orWhere('reference_type', 'like', $like)
                        ->orWhere('reference_id', 'like', $like)
                        ->orWhere('note', 'like', $like)
                        ->orWhereHas('businessMap.centralMember', function ($member) use ($like) {
                            $member->where('central_member_code', 'like', $like)
                                ->orWhere('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('mobile', 'like', $like);
                        });
                });
            })
            ->orderBy('transaction_date')
            ->orderBy('id');
    }
}

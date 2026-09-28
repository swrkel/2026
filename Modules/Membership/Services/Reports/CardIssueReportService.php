<?php

namespace Modules\Membership\Services\Reports;

use Illuminate\Http\Request;
use Modules\Membership\Entities\MembershipMember;

class CardIssueReportService
{
    public function query(int $businessId, Request $request)
    {
        $query = MembershipMember::query()
            ->where('business_id', $businessId)
            ->whereNotNull('card_number')
            ->orderByDesc('id');

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        return $query;
    }
}

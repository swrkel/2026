<?php

namespace Modules\Membership\Services\Reports;

use Illuminate\Http\Request;
use Modules\Membership\Entities\MembershipPoint;

class PointsSummaryReportService
{
    public function query(int $businessId, Request $request)
    {
        $query = MembershipPoint::query()
            ->where('business_id', $businessId)
            ->with(['member'])
            ->orderByDesc('id');

        if ($request->filled('member_id')) {
            $query->where('member_id', $request->input('member_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->input('date_to'));
        }

        return $query;
    }
}

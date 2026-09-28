<?php

namespace Modules\MembershipNew\app\Reports;

use Illuminate\Http\Request;
use Modules\MembershipNew\app\Models\MembershipNewPayment;

class MembershipNewPaymentReport
{
    public function query(Request $request)
    {
        $businessId = \Modules\MembershipNew\app\Support\MembershipNewContext::businessId();

        return MembershipNewPayment::with(['member', 'plan'])
            ->forBusiness($businessId)
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('payment_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('payment_date', '<=', $request->to_date))
            ->latest();
    }
}

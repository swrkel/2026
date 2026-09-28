<?php

namespace Modules\BankingMicrofinance\Http\Controllers;

use Modules\BankingMicrofinance\Entities\Group;
use Modules\BankingMicrofinance\Entities\Member;
use Modules\BankingMicrofinance\Entities\Loan;
use Modules\BankingMicrofinance\Entities\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $businessId = $this->businessId();
        $summary = [
            'groups' => Group::when($businessId, fn($q) => $q->where('business_id', $businessId))->count(),
            'members' => Member::when($businessId, fn($q) => $q->where('business_id', $businessId))->count(),
            'active_loans' => Loan::when($businessId, fn($q) => $q->where('business_id', $businessId))->whereIn('status', ['disbursed','active'])->count(),
            'outstanding' => Loan::when($businessId, fn($q) => $q->where('business_id', $businessId))->whereIn('status', ['disbursed','active'])->sum('total_payable'),
            'today_collection' => Collection::when($businessId, fn($q) => $q->where('business_id', $businessId))->whereDate('collection_date', today())->sum('total_paid'),
        ];
        return view('bankingmicrofinance::dashboard.index', compact('summary'));
    }
}

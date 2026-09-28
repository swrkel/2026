<?php

namespace Modules\BankingInsurance\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BankingInsurance\Entities\Policy;
use Modules\BankingInsurance\Entities\Premium;
use Modules\BankingInsurance\Entities\Claim;

class ReportController extends Controller
{
    public function policies(Request $request)
    {
        $records = Policy::with('product')->forBusiness($this->businessId())
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->from_date, fn($q) => $q->whereDate('start_date', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('start_date', '<=', $request->to_date))
            ->latest()->paginate(50);
        return view('bankinginsurance::reports.policies', compact('records'));
    }

    public function premiums(Request $request)
    {
        $records = Premium::with('policy')->where('business_id', $this->businessId())
            ->when($request->from_date, fn($q) => $q->whereDate('payment_date', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('payment_date', '<=', $request->to_date))
            ->latest()->paginate(50);
        return view('bankinginsurance::reports.premiums', compact('records'));
    }

    public function claims(Request $request)
    {
        $records = Claim::with('policy')->where('business_id', $this->businessId())
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->from_date, fn($q) => $q->whereDate('claim_date', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('claim_date', '<=', $request->to_date))
            ->latest()->paginate(50);
        return view('bankinginsurance::reports.claims', compact('records'));
    }
}

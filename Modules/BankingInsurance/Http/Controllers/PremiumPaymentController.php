<?php

namespace Modules\BankingInsurance\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BankingInsurance\Entities\Policy;
use Modules\BankingInsurance\Entities\Premium;
use Modules\BankingInsurance\Services\NumberGenerator;

class PremiumPaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Premium::with('policy')->where('business_id', $this->businessId());
        if ($request->filled('from_date')) $query->whereDate('payment_date', '>=', $request->from_date);
        if ($request->filled('to_date')) $query->whereDate('payment_date', '<=', $request->to_date);
        $premiums = $query->latest()->paginate(25);
        return view('bankinginsurance::premiums.index', compact('premiums'));
    }

    public function create()
    {
        $policies = Policy::forBusiness($this->businessId())->where('status', 'active')->pluck('policy_no', 'id');
        return view('bankinginsurance::premiums.form', compact('policies'));
    }

    public function store(Request $request, NumberGenerator $numbers)
    {
        $data = $request->validate([
            'policy_id' => 'required|integer',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.0001',
            'payment_method' => 'nullable|string|max:60',
            'reference_no' => 'nullable|string|max:100',
            'note' => 'nullable|string',
        ]);
        $policy = Policy::forBusiness($this->businessId())->findOrFail($data['policy_id']);
        $data['business_id'] = $this->businessId();
        $data['location_id'] = $policy->location_id;
        $data['created_by'] = $this->userId();
        $data['receipt_no'] = $numbers->next($this->businessId(), 'banking_insurance_premiums', 'receipt_no', config('bankinginsurance.receipt_prefix'));
        $premium = Premium::create($data);
        return redirect()->route('banking-insurance.premiums.receipt', $premium)->with('status', ['success' => 1, 'msg' => 'Premium payment saved']);
    }

    public function show(Premium $premium)
    {
        abort_unless($premium->business_id == $this->businessId(), 403);
        return view('bankinginsurance::premiums.show', compact('premium'));
    }

    public function receipt(Premium $premium)
    {
        abort_unless($premium->business_id == $this->businessId(), 403);
        $premium->load('policy');
        return view('bankinginsurance::premiums.receipt', compact('premium'));
    }

    public function destroy(Premium $premium)
    {
        abort_unless($premium->business_id == $this->businessId(), 403);
        $premium->delete();
        return back()->with('status', ['success' => 1, 'msg' => 'Premium payment deleted']);
    }
}

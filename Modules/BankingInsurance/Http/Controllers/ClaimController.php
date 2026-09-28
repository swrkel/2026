<?php

namespace Modules\BankingInsurance\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BankingInsurance\Entities\Claim;
use Modules\BankingInsurance\Entities\Policy;
use Modules\BankingInsurance\Services\NumberGenerator;

class ClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = Claim::with('policy')->where('business_id', $this->businessId());
        if ($request->filled('status')) $query->where('status', $request->status);
        $claims = $query->latest()->paginate(25);
        return view('bankinginsurance::claims.index', compact('claims'));
    }

    public function create()
    {
        $policies = Policy::forBusiness($this->businessId())->where('status', 'active')->pluck('policy_no', 'id');
        return view('bankinginsurance::claims.form', compact('policies'));
    }

    public function store(Request $request, NumberGenerator $numbers)
    {
        $data = $request->validate([
            'policy_id' => 'required|integer',
            'claim_date' => 'required|date',
            'claim_amount' => 'required|numeric|min:0.0001',
            'reason' => 'nullable|string',
        ]);
        $policy = Policy::forBusiness($this->businessId())->findOrFail($data['policy_id']);
        $data['business_id'] = $this->businessId();
        $data['location_id'] = $policy->location_id;
        $data['created_by'] = $this->userId();
        $data['claim_no'] = $numbers->next($this->businessId(), 'banking_insurance_claims', 'claim_no', config('bankinginsurance.claim_prefix'));
        Claim::create($data);
        return redirect()->route('banking-insurance.claims.index')->with('status', ['success' => 1, 'msg' => 'Claim saved']);
    }

    public function edit(Claim $claim)
    {
        abort_unless($claim->business_id == $this->businessId(), 403);
        abort_unless(in_array($claim->status, ['submitted','under_review']), 403, 'Claim is locked.');
        $policies = Policy::forBusiness($this->businessId())->where('status', 'active')->pluck('policy_no', 'id');
        return view('bankinginsurance::claims.form', compact('claim', 'policies'));
    }

    public function update(Request $request, Claim $claim)
    {
        abort_unless($claim->business_id == $this->businessId(), 403);
        abort_unless(in_array($claim->status, ['submitted','under_review']), 403, 'Claim is locked.');
        $claim->update($request->validate([
            'claim_date' => 'required|date',
            'claim_amount' => 'required|numeric|min:0.0001',
            'reason' => 'nullable|string',
            'status' => 'nullable|string|max:30',
        ]));
        return redirect()->route('banking-insurance.claims.index')->with('status', ['success' => 1, 'msg' => 'Claim updated']);
    }

    public function approve(Request $request, Claim $claim)
    {
        abort_unless($claim->business_id == $this->businessId(), 403);
        $data = $request->validate(['approved_amount' => 'required|numeric|min:0.0001']);
        $claim->update($data + ['status' => 'approved', 'approved_by' => $this->userId(), 'approved_at' => now()]);
        return back()->with('status', ['success' => 1, 'msg' => 'Claim approved']);
    }

    public function reject(Request $request, Claim $claim)
    {
        abort_unless($claim->business_id == $this->businessId(), 403);
        $claim->update(['status' => 'rejected', 'settlement_note' => $request->settlement_note]);
        return back()->with('status', ['success' => 1, 'msg' => 'Claim rejected']);
    }

    public function settle(Request $request, Claim $claim)
    {
        abort_unless($claim->business_id == $this->businessId(), 403);
        abort_unless($claim->status == 'approved', 403, 'Only approved claims can be settled.');
        $claim->update(['status' => 'settled', 'settlement_note' => $request->settlement_note]);
        return back()->with('status', ['success' => 1, 'msg' => 'Claim settled']);
    }

    public function show(Claim $claim)
    {
        abort_unless($claim->business_id == $this->businessId(), 403);
        return view('bankinginsurance::claims.show', compact('claim'));
    }

    public function destroy(Claim $claim)
    {
        abort_unless($claim->business_id == $this->businessId(), 403);
        abort_unless($claim->status == 'submitted', 403, 'Only submitted claims can be deleted.');
        $claim->delete();
        return back()->with('status', ['success' => 1, 'msg' => 'Claim deleted']);
    }
}

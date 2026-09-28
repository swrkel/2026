<?php

namespace Modules\BankingInsurance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\BankingInsurance\Entities\Policy;
use Modules\BankingInsurance\Entities\Product;
use Modules\BankingInsurance\Services\NumberGenerator;

class PolicyController extends Controller
{
    public function index(Request $request)
    {
        $query = Policy::with('product')->forBusiness($this->businessId());
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('customer_search')) {
            $search = $request->customer_search;
            $query->where(function ($q) use ($search) {
                $q->where('policy_no', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('nic_no', 'like', "%{$search}%");
            });
        }
        $policies = $query->latest()->paginate(25);
        return view('bankinginsurance::policies.index', compact('policies'));
    }

    public function create()
    {
        $policy = new Policy(['start_date' => now()->toDateString(), 'premium_frequency' => 'monthly']);
        $products = Product::forBusiness($this->businessId())->where('is_active', 1)->pluck('name', 'id');
        return view('bankinginsurance::policies.form', compact('policy', 'products'));
    }

    public function store(Request $request, NumberGenerator $numbers)
    {
        $data = $this->validateData($request);
        DB::transaction(function () use (&$data, $numbers) {
            $data['business_id'] = $this->businessId();
            $data['created_by'] = $this->userId();
            $data['policy_no'] = $data['policy_no'] ?? $numbers->next($this->businessId(), 'banking_insurance_policies', 'policy_no', config('bankinginsurance.policy_prefix'));
            Policy::create($data);
        });
        return redirect()->route('banking-insurance.policies.index')->with('status', ['success' => 1, 'msg' => 'Policy saved successfully']);
    }

    public function show(Policy $policy)
    {
        abort_unless($policy->business_id == $this->businessId(), 403);
        $policy->load(['product', 'premiums', 'claims']);
        return view('bankinginsurance::policies.show', compact('policy'));
    }

    public function edit(Policy $policy)
    {
        abort_unless($policy->business_id == $this->businessId(), 403);
        abort_if(in_array($policy->status, ['cancelled', 'matured']), 403, 'Locked policy cannot be edited.');
        $products = Product::forBusiness($this->businessId())->where('is_active', 1)->pluck('name', 'id');
        return view('bankinginsurance::policies.form', compact('policy', 'products'));
    }

    public function update(Request $request, Policy $policy)
    {
        abort_unless($policy->business_id == $this->businessId(), 403);
        abort_if(in_array($policy->status, ['cancelled', 'matured']), 403, 'Locked policy cannot be edited.');
        $policy->update($this->validateData($request, $policy->id));
        return redirect()->route('banking-insurance.policies.index')->with('status', ['success' => 1, 'msg' => 'Policy updated successfully']);
    }

    public function activate(Policy $policy)
    {
        abort_unless($policy->business_id == $this->businessId(), 403);
        $policy->update(['status' => 'active']);
        return back()->with('status', ['success' => 1, 'msg' => 'Policy activated']);
    }

    public function cancel(Policy $policy)
    {
        abort_unless($policy->business_id == $this->businessId(), 403);
        $policy->update(['status' => 'cancelled']);
        return back()->with('status', ['success' => 1, 'msg' => 'Policy cancelled']);
    }

    public function print(Policy $policy)
    {
        abort_unless($policy->business_id == $this->businessId(), 403);
        return view('bankinginsurance::policies.print', compact('policy'));
    }

    public function destroy(Policy $policy)
    {
        abort_unless($policy->business_id == $this->businessId(), 403);
        abort_unless($policy->status == 'draft', 403, 'Only draft policies can be deleted.');
        $policy->delete();
        return back()->with('status', ['success' => 1, 'msg' => 'Policy deleted']);
    }

    private function validateData(Request $request, $id = null)
    {
        return $request->validate([
            'location_id' => 'nullable|integer',
            'product_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer',
            'policy_no' => 'nullable|string|max:60',
            'customer_name' => 'required|string|max:191',
            'mobile' => 'nullable|string|max:30',
            'nic_no' => 'nullable|string|max:60',
            'nominee_name' => 'nullable|string|max:191',
            'nominee_mobile' => 'nullable|string|max:30',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'sum_assured' => 'required|numeric|min:0',
            'premium_amount' => 'required|numeric|min:0',
            'premium_frequency' => 'required|string|max:30',
            'status' => 'nullable|string|max:30',
            'remarks' => 'nullable|string',
        ]);
    }
}

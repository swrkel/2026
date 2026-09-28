<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Entities\DigitalWalletRule;

class DigitalWalletRuleController extends Controller
{
    public function index()
    {
        $rules = DigitalWalletRule::latest()->paginate(50);
        return view('digitalwallet::rules.index', compact('rules'));
    }

    public function create()
    {
        return view('digitalwallet::rules.form', ['rule' => new DigitalWalletRule()]);
    }

    public function store(Request $request)
    {
        DigitalWalletRule::create($this->validated($request));
        return redirect()->route('digitalwallet.rules.index')->with('status', 'Rule created successfully.');
    }

    public function edit(DigitalWalletRule $rule)
    {
        return view('digitalwallet::rules.form', compact('rule'));
    }

    public function update(Request $request, DigitalWalletRule $rule)
    {
        $rule->update($this->validated($request));
        return redirect()->route('digitalwallet.rules.index')->with('status', 'Rule updated successfully.');
    }

    public function destroy(DigitalWalletRule $rule)
    {
        $rule->delete();
        return redirect()->route('digitalwallet.rules.index')->with('status', 'Rule deleted successfully.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'rule_name' => 'required|string|max:191',
            'rule_type' => 'required|string|max:100',
            'wallet_type' => 'nullable|string|max:100',
            'minimum_balance' => 'nullable|numeric|min:0',
            'maximum_balance' => 'nullable|numeric|min:0',
            'daily_limit' => 'nullable|numeric|min:0',
            'monthly_limit' => 'nullable|numeric|min:0',
            'approval_threshold' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}

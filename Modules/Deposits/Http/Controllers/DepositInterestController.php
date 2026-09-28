<?php

namespace Modules\Deposits\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Deposits\Models\DepositAccount;
use Modules\Deposits\Services\DepositInterestService;

class DepositInterestController extends Controller
{
    public function index()
    {
        $accounts = Schema::hasTable('deposit_accounts')
            ? DepositAccount::where('status', 'active')->latest()->paginate(30)
            : collect();
        return view('deposits::interest.index', compact('accounts'));
    }

    public function post(Request $request, DepositInterestService $service)
    {
        $data = $request->validate([
            'deposit_account_id' => 'required|integer',
            'posting_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $account = DepositAccount::findOrFail($data['deposit_account_id']);
        $tx = $service->postMonthlyInterest($account, $data['posting_date'] ?? null, $data['notes'] ?? null);

        if (! $tx) {
            return back()->with('status', ['success' => 0, 'msg' => 'No interest amount calculated for this account.']);
        }
        return back()->with('status', ['success' => 1, 'msg' => 'Interest posted successfully.']);
    }
}

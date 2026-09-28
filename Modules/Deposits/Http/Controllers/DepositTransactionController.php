<?php

namespace Modules\Deposits\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Deposits\Models\DepositAccount;
use Modules\Deposits\Models\DepositTransaction;
use Modules\Deposits\Services\DepositNumberService;
use Modules\Deposits\Services\DepositSettingsService;

class DepositTransactionController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('deposit_transactions')) {
            $transactions = collect();
            return view('deposits::transactions.index', compact('transactions'));
        }

        $query = DepositTransaction::with('account')->latest('transaction_date')->latest('id');
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('deposit_account_id')) {
            $query->where('deposit_account_id', $request->deposit_account_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->date_to);
        }

        $transactions = $query->paginate(30);
        $accounts = Schema::hasTable('deposit_accounts') ? DepositAccount::orderBy('account_no')->pluck('account_no', 'id') : collect();
        return view('deposits::transactions.index', compact('transactions', 'accounts'));
    }

    public function create(DepositNumberService $numberService)
    {
        $accounts = Schema::hasTable('deposit_accounts') ? DepositAccount::where('status', 'active')->orderBy('account_no')->pluck('account_no', 'id') : collect();
        return view('deposits::transactions.form', [
            'accounts' => $accounts,
            'transaction_no' => $numberService->nextTransactionNumber(),
        ]);
    }

    public function store(Request $request, DepositSettingsService $settings)
    {
        $data = $request->validate([
            'deposit_account_id' => 'required|integer',
            'transaction_no' => 'nullable|string|max:191',
            'type' => 'required|string|max:50',
            'transaction_date' => 'nullable|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:100',
            'reference_no' => 'nullable|string|max:191',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($data, $settings) {
            $account = DepositAccount::lockForUpdate()->findOrFail($data['deposit_account_id']);
            if ($account->status !== 'active' && ! in_array($data['type'], ['closure'])) {
                throw ValidationException::withMessages(['deposit_account_id' => 'Only active deposit accounts can receive transactions.']);
            }

            $amount = (float) $data['amount'];
            $balance = (float) $account->current_balance;
            $type = $data['type'];

            if (in_array($type, ['withdrawal', 'closure', 'charge', 'penalty'])) {
                $balance -= $amount;
            } else {
                $balance += $amount;
            }

            if ($balance < 0 && (string) $settings->get('allow_negative_balance', '0') !== '1') {
                throw ValidationException::withMessages(['amount' => 'This transaction would make the deposit balance negative.']);
            }

            $data['business_id'] = session('business.id');
            $data['location_id'] = $account->location_id;
            $data['transaction_date'] = $data['transaction_date'] ?: date('Y-m-d');
            $data['balance_after'] = $balance;
            $data['created_by'] = auth()->id();
            DepositTransaction::create($data);

            $updates = ['current_balance' => $balance];
            if ($type === 'interest') {
                $updates['interest_accrued'] = (float) $account->interest_accrued + $amount;
                $updates['last_interest_posted_on'] = $data['transaction_date'];
            }
            $account->update($updates);
        });

        return redirect()->route('deposits.transactions.index')->with('status', ['success' => 1, 'msg' => __('deposits::lang.transaction_saved')]);
    }
}

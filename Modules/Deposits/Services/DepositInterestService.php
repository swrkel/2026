<?php

namespace Modules\Deposits\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Deposits\Models\DepositAccount;
use Modules\Deposits\Models\DepositTransaction;

class DepositInterestService
{
    public function calculateMonthlyInterest(DepositAccount $account): float
    {
        $balance = (float) $account->current_balance;
        $rate = (float) $account->interest_rate;
        if ($balance <= 0 || $rate <= 0) {
            return 0.0;
        }
        return round(($balance * $rate / 100) / 12, 4);
    }

    public function postMonthlyInterest(DepositAccount $account, ?string $date = null, ?string $notes = null): ?DepositTransaction
    {
        $date = $date ?: date('Y-m-d');
        $amount = $this->calculateMonthlyInterest($account);
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($account, $date, $notes, $amount) {
            $account = DepositAccount::lockForUpdate()->findOrFail($account->id);
            $balance = (float) $account->current_balance + $amount;

            $tx = DepositTransaction::create([
                'business_id' => $account->business_id ?: session('business.id'),
                'location_id' => $account->location_id,
                'deposit_account_id' => $account->id,
                'transaction_no' => 'INT-' . date('YmdHis') . '-' . $account->id,
                'type' => 'interest',
                'transaction_date' => $date,
                'amount' => $amount,
                'balance_after' => $balance,
                'payment_method' => 'system',
                'reference_no' => null,
                'notes' => $notes ?: 'Monthly interest posting',
                'created_by' => auth()->id(),
            ]);

            $account->update([
                'current_balance' => $balance,
                'interest_accrued' => (float) $account->interest_accrued + $amount,
                'last_interest_posted_on' => $date,
                'maturity_amount' => max((float) $account->maturity_amount, $balance),
            ]);

            return $tx;
        });
    }
}

<?php

namespace Modules\Deposits\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Deposits\Models\DepositAccount;
use Modules\Deposits\Models\DepositTransaction;

class DepositLifecycleService
{
    protected $numbers;

    public function __construct(DepositNumberService $numbers)
    {
        $this->numbers = $numbers;
    }

    public function close(DepositAccount $account, ?string $date = null, ?string $notes = null): DepositAccount
    {
        $date = $date ?: date('Y-m-d');
        return DB::transaction(function () use ($account, $date, $notes) {
            $account = DepositAccount::with('product')->lockForUpdate()->findOrFail($account->id);
            if ($account->status === 'closed') {
                return $account;
            }

            if ($account->maturity_on && $account->maturity_on > $date && optional($account->product)->premature_closure_allowed === false) {
                throw ValidationException::withMessages(['closed_on' => 'Premature closure is not allowed for this product.']);
            }

            $amount = (float) $account->current_balance;
            DepositTransaction::create([
                'business_id' => $account->business_id ?: session('business.id'),
                'location_id' => $account->location_id,
                'deposit_account_id' => $account->id,
                'transaction_no' => $this->numbers->nextTransactionNumber('CLS'),
                'type' => 'closure',
                'transaction_date' => $date,
                'amount' => $amount,
                'balance_after' => 0,
                'payment_method' => 'closure',
                'reference_no' => null,
                'notes' => $notes ?: 'Deposit account closed',
                'created_by' => auth()->id(),
            ]);
            $account->update(['current_balance' => 0, 'status' => 'closed', 'closed_on' => $date, 'updated_by' => auth()->id()]);
            return $account;
        });
    }

    public function renew(DepositAccount $account, ?string $maturityOn = null): DepositAccount
    {
        return DB::transaction(function () use ($account, $maturityOn) {
            $account = DepositAccount::with('product')->lockForUpdate()->findOrFail($account->id);
            $new = $account->replicate();
            $new->renewed_from_account_id = $account->id;
            $new->account_no = $this->numbers->nextAccountNumber(optional($account->product)->account_prefix);
            $new->certificate_no = $this->numbers->nextCertificateNumber();
            $new->principal_amount = $account->current_balance;
            $new->current_balance = $account->current_balance;
            $new->interest_accrued = 0;
            $new->opened_on = date('Y-m-d');
            $new->maturity_on = $maturityOn ?: null;
            $new->status = 'active';
            $new->closed_on = null;
            $new->created_by = auth()->id();
            $new->updated_by = null;
            $new->save();

            $account->update(['status' => 'renewed', 'closed_on' => date('Y-m-d'), 'updated_by' => auth()->id()]);
            return $new;
        });
    }
}

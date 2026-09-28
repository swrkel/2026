<?php

namespace Modules\Chequer\Entities;

use Modules\Chequer\Utils\ChequerTransactionUtil;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Chequer\Entities\Account;

class AccountTransaction extends Model
{
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = ['*'];

    protected static $logFillable = true;

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['fillable', 'some_other_attribute']);
    }

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'operation_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    public function transaction()
    {
        return $this->belongsTo(\App\Transaction::class, 'transaction_id')->withTrashed();
        ;
    }

    /**
     * Gives account transaction type from payment transaction type
     * @param  string $payment_transaction_type
     * @return string
     */
    public static function getAccountTransactionType($transaction_type)
    {
        $account_transaction_types = [
            'sell' => 'credit',
            'purchase' => 'credit', // change debit to credit as required by syzygy
            'expense' => 'debit',
            // 'purchase_return' => 'credit',
            'purchase_return' => 'debit',
            'sell_return' => 'debit',
            'autoservice' => 'credit',
            'property_purchase' => 'credit',
            'property_sell' => 'credit',
            'route_operation' => 'credit',
            'shipment' => 'credit',
            'airline_ticket' => 'credit',
            'hms_booking' => 'credit',
            'fpos_sale' => 'credit',
        ];

        return $account_transaction_types[$transaction_type] ?? 'debit';
    }

    /**
     * Creates new account transaction
     * @return obj
     */
    public static function createAccountTransaction($data)
    {
        // If account_id is not provided, try to get it from transaction_payment table
        if (empty($data['account_id']) && !empty($data['transaction_payment_id'])) {
            $transaction_payment = DB::table('transaction_payments')
                ->where('id', $data['transaction_payment_id'])
                ->first();

            if ($transaction_payment && !empty($transaction_payment->account_id)) {
                $data['account_id'] = $transaction_payment->account_id;
            }
        }

        // If still no account_id, try to get default Cash account
        // Skip this fallback if caller explicitly opts out (e.g. stock_adjustment entries should not fall back to Cash)
        if (empty($data['account_id']) && empty($data['skip_account_fallback'])) {
            $business_id = request()->session()->get('user.business_id');
            $default_cash_account = DB::table('accounts')
                ->where('business_id', $business_id)
                ->where('name', 'Cash')
                ->where('is_closed', 0)
                ->first();

            if ($default_cash_account) {
                $data['account_id'] = $default_cash_account->id;
            }
        }

        if (!empty($data['interest'])) {
            $transaction_data['interest'] = $data['interest'];
        }

        // Use business_id from data if provided, otherwise get from session
        $business_id = !empty($data['business_id']) ? $data['business_id'] : request()->session()->get('user.business_id');

        // $account = Account::where('id', $data['account_id'])->select('business_id')->first();
        $transaction_data = [
            'amount' => $data['amount'],
            'account_id' => $data['account_id'] ?? null,
            'business_id' => $business_id,
            'type' => $data['type'] ?? $data['sub_type'] ?? 'debit',
            'sub_type' => !empty($data['sub_type']) ? $data['sub_type'] : null,
            'operation_date' => !empty($data['operation_date']) ? $data['operation_date'] : \Carbon::now(),
            'created_by' => !empty($data['created_by']) ? $data['created_by'] : Auth::user()->id,
            'transaction_id' => !empty($data['transaction_id']) ? $data['transaction_id'] : null,
            'transaction_payment_id' => !empty($data['transaction_payment_id']) ? $data['transaction_payment_id'] : null,
            'note' => !empty($data['note']) ? $data['note'] : null,
            'slip_no' => !empty($data['slip_no']) ? $data['slip_no'] : null,
            'cheque_number' => !empty($data['cheque_number']) ? $data['cheque_number'] : null,
            'attachment' => !empty($data['attachment']) ? $data['attachment'] : null,
            'cheque_ref_no' => !empty($data['cheque_ref_no']) ? $data['cheque_ref_no'] : null,
            'transfer_transaction_id' => !empty($data['transfer_transaction_id']) ? $data['transfer_transaction_id'] : null,
            'transaction_sell_line_id' => !empty($data['transaction_sell_line_id']) ? $data['transaction_sell_line_id'] : null,
            'sell_line_id' => !empty($data['sell_line_id']) ? $data['sell_line_id'] : null,
            'purchase_line_id' => !empty($data['purchase_line_id']) ? $data['purchase_line_id'] : null,
            'income_type' => !empty($data['income_type']) ? $data['income_type'] : null,
            'installment_id' => !empty($data['installment_id']) ? $data['installment_id'] : null,
            'interest' => !empty($data['interest']) ? $data['interest'] : null,
            'fixed_asset_id' => !empty($data['fixed_asset_id']) ? $data['fixed_asset_id'] : null,

            'pair_at_id' => !empty($data['pair_at_id']) ? $data['pair_at_id'] : null,

            'post_dated_cheque' => !empty($data['post_dated_cheque']) ? $data['post_dated_cheque'] : 0, // post dated cheque input

            'update_post_dated_cheque' => !empty($data['update_post_dated_cheque']) ? $data['update_post_dated_cheque'] : 0,

            'related_account_id' => !empty($data['related_account_id']) ? $data['related_account_id'] : 0,
            'credit_related_account' => !empty($data['credit_related_account']) ? $data['credit_related_account'] : 0,

            'bank_name' => !empty($data['bank_name']) ? $data['bank_name'] : 0,
            'shift_number' => !empty($data['shift_number']) ? $data['shift_number'] : null,

            'cheque_date' => !empty($data['cheque_date']) ? $data['cheque_date'] : null,
            'auto_transfer' => !empty($data['auto_transfer']) ? $data['auto_transfer'] : null,
        ];


        // Check if account_id is null and abort if so
        if (empty($transaction_data['account_id'])) {
            return null;
        }

        // Prevent duplicate account transactions
        $criteria = [
            'account_id' => $transaction_data['account_id'],
            'type' => $transaction_data['type'],
            'transaction_id' => $transaction_data['transaction_id'],
            'transaction_payment_id' => $transaction_data['transaction_payment_id'],
            'amount' => $transaction_data['amount']
        ];

        if (empty($transaction_data['transaction_id']) && empty($transaction_data['transaction_payment_id'])) {
            $criteria['sub_type'] = $transaction_data['sub_type'];
            $criteria['operation_date'] = $transaction_data['operation_date'];
            $criteria['cheque_number'] = $transaction_data['cheque_number'];
        }

        // Sale/stock postings are line-level entries. Two meter sales or other sales can
        // legitimately have the same account, type, transaction, and amount, so include
        // the sell line in the duplicate key when it is available.
        if (!empty($transaction_data['sell_line_id'])) {
            $criteria['sell_line_id'] = $transaction_data['sell_line_id'];
        }

        // Skip duplicate check for stock_adjustment transactions — each product line
        // must create its own entry even if amounts and accounts match.
        $skip_duplicate_check = !empty($data['skip_duplicate_check']) ? true : false;
        if (!$skip_duplicate_check && !empty($transaction_data['transaction_id'])) {
            $txn_type = DB::table('transactions')
                ->where('id', $transaction_data['transaction_id'])
                ->value('type');
            if ($txn_type === 'stock_adjustment') {
                $skip_duplicate_check = true;
            }
        }

        // Only create if no duplicate exists
        if ($skip_duplicate_check) {
            $account_transaction = AccountTransaction::create($transaction_data);
        } else {
            $existing = AccountTransaction::where($criteria)->first();
            if (!$existing) {
                $account_transaction = AccountTransaction::create($transaction_data);
            } else {
                $account_transaction = $existing;
            }
        }

        return $account_transaction;
    }

    /**
     * Updates transaction payment from transaction payment
     * @param  obj $transaction_payment
     * @param  array $inputs
     * @param  string $transaction_type
     * @return string
     */
    public static function updateAccountTransaction($transaction_payment, $transaction_type)
    {
        $account_id = $transaction_payment->account_id;
        $transaction_id = $transaction_payment->transaction_id;
        $transaction = Transaction::find($transaction_id);
        $effective_payment = !empty($transaction_payment->parent_id)
            ? TransactionPayment::find($transaction_payment->parent_id)
            : $transaction_payment;
        $payment_id = !empty($effective_payment) ? $effective_payment->id : $transaction_payment->id;
        $payment_amount = !empty($effective_payment) ? $effective_payment->amount : $transaction_payment->amount;
        $payment_paid_on = !empty($effective_payment) ? $effective_payment->paid_on : $transaction_payment->paid_on;

        if ($transaction_type == 'purchase') {
            //update contact ledger transaction
            ContactLedger::where(
                'transaction_id',
                $transaction_payment->transaction_id
            )->where('transaction_payment_id', $payment_id)
                ->update(['amount' => $payment_amount]);
        }
        if ($transaction_type == 'sell' || $transaction_type == 'property_sell') {
            //update contact ledger transaction
            ContactLedger::where('transaction_payment_id', $payment_id)
                ->update(['amount' => $payment_amount]);
        }

        $account_transactions = AccountTransaction::where('transaction_payment_id', $payment_id)->get();
        if ($account_transactions->isNotEmpty()) {
            $accounts_receivable_id = !empty($transaction) && !empty($transaction->business_id)
                ? Account::where('business_id', $transaction->business_id)->where('name', 'Accounts Receivable')->value('id')
                : null;

            foreach ($account_transactions as $account_transaction) {
                $update_data = [
                    'amount' => $payment_amount,
                    'operation_date' => $payment_paid_on,
                ];

                if (empty($accounts_receivable_id) || intval($account_transaction->account_id) !== intval($accounts_receivable_id)) {
                    $update_data['account_id'] = $account_id;
                }

                $account_transaction->update($update_data);
            }

            return $account_transactions->first();
        } else {
            $accnt_trans_data = [
                'amount' => $payment_amount,
                'note' => !empty($effective_payment) ? $effective_payment->note : $transaction_payment->note,
                'slip_no' => !empty($effective_payment) ? $effective_payment->slip_no : $transaction_payment->slip_no,
                'account_id' => $account_id,
                'type' => self::getAccountTransactionType($transaction_type),
                'operation_date' => $payment_paid_on,
                'created_by' => !empty($effective_payment) ? $effective_payment->created_by : $transaction_payment->created_by,
                'transaction_id' => $transaction_payment->transaction_id,
                'transaction_payment_id' => $payment_id,
                'post_dated_cheque' => !empty($data['post_dated_cheque']) ? $data['post_dated_cheque'] : 0, // post dated cheque input

                'update_post_dated_cheque' => !empty($data['update_post_dated_cheque']) ? $data['update_post_dated_cheque'] : 0,

                'related_account_id' => !empty($data['related_account_id']) ? $data['related_account_id'] : 0,

                'bank_name' => !empty($data['bank_name']) ? $data['bank_name'] : 0,

            ];

            if ($transaction_type === 'purchase_return') {
                $supplier = '';
                if (!empty($transaction) && !empty($transaction->contact)) {
                    $supplier = $transaction->contact->supplier_business_name ?: $transaction->contact->name;
                }
                $accnt_trans_data['note'] = 'Purchase Return No ' . ($transaction->ref_no ?? '') . (!empty($supplier) ? (' - ' . $supplier) : '');
                if (!empty($transaction_payment->cheque_number)) {
                    $accnt_trans_data['cheque_number'] = $transaction_payment->cheque_number;
                }
            }


            //If change return then set type as debit
            if ($transaction_payment->transaction->type == 'sell' && $transaction_payment->is_return == 1) {
                $accnt_trans_data['type'] = 'debit';
            }

            self::createAccountTransaction($accnt_trans_data);
            $accnt_trans_data['contact_id'] = $transaction->contact_id;
            ContactLedger::createContactLedger($accnt_trans_data);
        }
    }

    public function transfer_transaction()
    {
        return $this->belongsTo(\Modules\Finance\Entities\AccountTransaction::class, 'transfer_transaction_id');
    }

    public function account()
    {
        return $this->belongsTo(\Modules\Finance\Entities\Account::class, 'account_id');
    }
}

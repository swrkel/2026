<?php

namespace Modules\SW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\DailyCash;
use Modules\SW\Entities\Shift;

/**
 * Daily Cash Status - Balance In Hand.
 *
 *   Cash Collection
 * + Customer Payment - Cash
 * - Cash Expenses
 * - Cash Deposit
 * = Balance In Hand
 *
 * PER SHIFT, not per operator. Daily entries carry an operator because the
 * money is that person's responsibility, but the other three figures come from
 * Finance and core screens which ask for a shift and not an operator. Splitting
 * the balance per operator would mean adding an operator field to expense,
 * payment and deposit forms.
 *
 * COMPUTED, NEVER STORED. A stored balance drifts the moment an expense or
 * deposit is attributed to the shift afterwards, and then two figures disagree
 * with no way to tell which is right. Closing the shift is what fixes it: once
 * closed, nothing further can be attributed.
 *
 * Every lookup is guarded with Schema::hasColumn. sw_shift_no is added by this
 * module's migration, and a report should degrade to zero rather than throw if
 * it has not run yet.
 */
class BalanceService
{
    public function forShift(Shift $shift): array
    {
        $no = (string) $shift->sw_shift_no;

        $cashCollection  = $this->cashCollection($shift);
        $customerPayment = $this->customerPaymentsCash($no);
        $cashExpenses    = $this->cashExpenses($no);
        $cashDeposit     = $this->cashDeposits($no);

        $balance = $cashCollection + $customerPayment - $cashExpenses - $cashDeposit;

        return [
            'cash_collection'  => round($cashCollection, 4),
            'customer_payment' => round($customerPayment, 4),
            'cash_expenses'    => round($cashExpenses, 4),
            'cash_deposit'     => round($cashDeposit, 4),
            'balance_in_hand'  => round($balance, 4),
        ];
    }

    /** Everything entered on the Daily Cash tab for this shift, all operators. */
    protected function cashCollection(Shift $shift): float
    {
        return (float) DailyCash::where('sw_shift_id', $shift->id)->sum('amount');
    }

    /**
     * Customer payments in cash attributed to this shift - settlement of old
     * credit sales, or advances.
     */
    protected function customerPaymentsCash(string $shiftNo): float
    {
        if ($shiftNo === '' || ! Schema::hasTable('transaction_payments')
            || ! Schema::hasColumn('transaction_payments', 'sw_shift_no')) {
            return 0.0;
        }

        $q = DB::table('transaction_payments')->where('sw_shift_no', $shiftNo);

        if (Schema::hasColumn('transaction_payments', 'method')) {
            $q->where('method', 'cash');
        }
        if (Schema::hasColumn('transaction_payments', 'is_return')) {
            $q->where('is_return', 0);
        }

        return (float) $q->sum('amount');
    }

    /** Cash expenses booked against this shift. */
    protected function cashExpenses(string $shiftNo): float
    {
        if ($shiftNo === '' || ! Schema::hasTable('transactions')
            || ! Schema::hasColumn('transactions', 'sw_shift_no')) {
            return 0.0;
        }

        return (float) DB::table('transactions')
            ->where('sw_shift_no', $shiftNo)
            ->where('type', 'expense')
            ->sum('final_total');
    }

    /**
     * Cash deposited through Finance - List Accounts - Cash Deposit.
     *
     * Read from account_transactions, which is where Finance records them. SW
     * does not record deposits of its own: one place for a fact, not two.
     */
    protected function cashDeposits(string $shiftNo): float
    {
        if ($shiftNo === '' || ! Schema::hasTable('account_transactions')
            || ! Schema::hasColumn('account_transactions', 'sw_shift_no')) {
            return 0.0;
        }

        $q = DB::table('account_transactions')->where('sw_shift_no', $shiftNo);

        if (Schema::hasColumn('account_transactions', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }

        return (float) $q->sum('amount');
    }
}

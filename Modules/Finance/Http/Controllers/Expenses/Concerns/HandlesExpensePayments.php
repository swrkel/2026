<?php

namespace Modules\Finance\Http\Controllers\Expenses\Concerns;

use Modules\Finance\Entities\User;
use App\Account;
use Modules\Finance\Entities\Contact;
use App\TaxRate;
use App\Business;
use App\AccountType;
use Modules\Finance\Entities\System;
use Modules\Finance\Entities\Transaction;
use App\ContactLedger;
use App\ExpenseCategory;
use Modules\Finance\Entities\BusinessLocation;
use App\Utils\ModuleUtil;
use App\AccountTransaction;
use Modules\Finance\Entities\TransactionPayment;
use App\Utils\BusinessUtil;
use Illuminate\Http\Request;
use App\NotificationTemplate;
use App\Utils\TransactionUtil;
use App\Utils\NotificationUtil;
use Modules\Fleet\Entities\Fleet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Services\FinanceBudgetControlService;
use Illuminate\Support\Facades\Gate;
use App\Providers\AppServiceProvider;
use Modules\Petro\Entities\PetroDailyShift;
use Modules\Superadmin\Entities\Package;
use Yajra\DataTables\Facades\DataTables;
use Modules\Fleet\Entities\RouteOperation;
use Modules\Property\Entities\PaymentOption;
use Modules\Superadmin\Entities\Subscription;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Essentials\Entities\EssentialsEmployee;
use Illuminate\Routing\Controller;

/**
 * Payment methods, post-dated cheque handling, print and show.
 *
 * MA-002: split out of ExpenseController, which reached 3,605 lines after the
 * controller was extracted from core. Large controllers are hard to maintain
 * and hard to review, so the methods are grouped by what they do.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at ExpenseController,
 *   action('...ExpenseController@store') still resolves, and $this-> calls
 *   between these methods still work exactly as before. Splitting into
 *   separate controller classes would have meant changing routes and every
 *   action() reference - a behavioural change dressed up as tidying.
 *
 *   So this is a purely physical split: same class at runtime, smaller files
 *   to read.
 *
 * The method bodies are byte-identical to what was in ExpenseController.
 * Nothing was rewritten while moving.
 *
 * Methods here: expensePaymentTypes, getPaymentMethodByLocationDropDown, normalizePdChequeFlagValue, normalizeExpensePaymentPdChequeInputs, prepareExpensePdChequePaymentInputs, enforceIssuedPdChequePayment, print, show
 */
trait HandlesExpensePayments
{
    private function expensePaymentTypes($payment_types)
    {
        if ($payment_types instanceof \Illuminate\Support\Collection) {
            $payment_types = $payment_types->toArray();
        }

        if (!is_array($payment_types)) {
            $payment_types = [];
        }

        unset($payment_types['credit_sale'], $payment_types['location_id']);
        $payment_types['credit_expense'] = 'Credit Expenses';

        return $payment_types;
    }

    public function getPaymentMethodByLocationDropDown($location_id)

    {

        $payment_methods = $this->transactionUtil->payment_types($location_id, true, false, false, false, true, "is_expense_enabled");
        $payment_methods = $this->expensePaymentTypes($payment_methods);

        return $this->transactionUtil->createDropdownHtml($payment_methods, 'Please Select');
    }

    private function normalizePdChequeFlagValue($value): int
    {
        if (is_array($value)) {
            return collect($value)->contains(function ($item) {
                return !empty($item);
            }) ? 1 : 0;
        }

        return !empty($value) ? 1 : 0;
    }

    private function normalizeExpensePaymentPdChequeInputs(array $inputs): array
    {
        $inputs['post_dated_cheque'] = $this->normalizePdChequeFlagValue($inputs['post_dated_cheque'] ?? 0);
        $inputs['update_post_dated_cheque'] = $this->normalizePdChequeFlagValue($inputs['update_post_dated_cheque'] ?? 0);

        return $inputs;
    }

    private function prepareExpensePdChequePaymentInputs(array $inputs, ?int $expenseCategoryId = null): array
    {
        $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

        if (!empty($inputs['post_dated_cheque']) || !empty($inputs['update_post_dated_cheque'])) {
            $originalAccountId = $inputs['related_account_id'] ?? ($inputs['account_id'] ?? null);
            $inputs['related_account_id'] = $originalAccountId;
            $inputs['account_id'] = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');

            $expense_category_name = !empty($expenseCategoryId)
                ? optional(ExpenseCategory::find($expenseCategoryId))->name
                : null;
            $bank_account = !empty($originalAccountId) ? Account::find($originalAccountId) : null;
            $bank_name = !empty($bank_account) ? $bank_account->name : '';

            $inputs['note'] = trim(($expense_category_name ?? '') . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
        }

        return $inputs;
    }

    private function enforceIssuedPdChequePayment(Transaction $transaction, ?TransactionPayment $tp): ?TransactionPayment
    {
        if (
            empty($tp)
            || $transaction->type !== 'expense'
            || (empty($tp->post_dated_cheque) && empty($tp->update_post_dated_cheque))
        ) {
            return $tp;
        }

        $issuedAccountId = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');

        if ((int) $tp->account_id !== (int) $issuedAccountId) {
            if (empty($tp->related_account_id)) {
                $tp->related_account_id = $tp->account_id;
            }

            $tp->account_id = $issuedAccountId;

            if (empty($tp->note)) {
                $expense_category_name = optional(ExpenseCategory::find($transaction->expense_category_id))->name;
                $bank_account = !empty($tp->related_account_id) ? Account::find($tp->related_account_id) : null;
                $bank_name = !empty($bank_account) ? $bank_account->name : '';
                $tp->note = trim(($expense_category_name ?? '') . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
            }

            $tp->save();

            Log::info('Expense PD cheque payment account corrected', [
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => $tp->id,
                'account_id' => $tp->account_id,
                'account_name' => optional(Account::find($tp->account_id))->name,
                'related_account_id' => $tp->related_account_id,
                'related_account_name' => optional(Account::find($tp->related_account_id))->name,
            ]);
        }

        return $tp->fresh();
    }

    public function print($transaction_id)
    {
        $id = $transaction_id;
        $transaction = Transaction::leftJoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
            ->where('transactions.id', $transaction_id)
            ->withTrashed()
            ->with(['contact', 'business', 'transaction_for'])
            ->first();

        $transaction_type = $transaction->type;

        $payments_query = TransactionPayment::where('transaction_id', $transaction_id);

        $accounts_enabled = false;

        if ($this->moduleUtil->isModuleEnabled('account')) {

            $accounts_enabled = true;

            $payments_query->with(['payment_account']);
        }

        $payments = $payments_query->get();

        $ref_nos = TransactionPayment::where('transaction_id', $transaction_id)->whereNotNull('payment_ref_no')->distinct('payment_ref_no')->pluck('payment_ref_no', 'payment_ref_no');

        $payment_types = $this->transactionUtil->payment_types();

        $on_account_ofs = PaymentOption::where('business_id', $transaction->business_id)->pluck('payment_option', 'id');

        $users = User::where('business_id', $transaction->business_id)->pluck('username', 'id');

        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();

        $business_locations = BusinessLocation::where('business_id', $business_id)->first();
        $show_payment_note_in_print = (int) System::getProperty('expense_show_payment_note_in_print_' . $business_id) === 1;
        $show_expense_note_in_print = (int) System::getProperty('expense_show_expense_note_in_print_' . $business_id) === 1;


        return view('expense.invoice', compact(

            'transaction',

            'payments',

            'payment_types',

            'ref_nos',

            'id',

            'accounts_enabled',

            'users',

            'business',

            'business_locations',

            'on_account_ofs',
            'show_payment_note_in_print',
            'show_expense_note_in_print'

        ));
    }


    /**

     * Add Account Transactions

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function show($id)

    {

        //

    }



    /**

     * Show the form for editing the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */
}

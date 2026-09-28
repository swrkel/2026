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
 * Posting and reversing the ledger entries behind an expense.
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
 * Methods here: addAccountTransaction, reverseAccountTransaction
 */
trait PostsExpenseLedger
{
    public function addAccountTransaction($transaction, $request, $business_id,  $tp)

    {
        // dd('addAccountTransaction');
        $final_total = $this->transactionUtil->num_uf($request->final_total);

        if (!empty($request->expense_account)) {
            $ob_transaction_data = [

                'amount' => $final_total,

                'account_id' => $request->expense_account,

                'type' => 'debit',

                'sub_type' => 'expense',

                'operation_date' => $transaction->transaction_date,

                'created_by' => Auth::user()->id,

                'transaction_id' => $transaction->id,

                'transaction_payment_id' => !empty($tp) ? $tp->id : null,

                'post_dated_cheque' =>  0
            ];
            AccountTransaction::createAccountTransaction($ob_transaction_data);
        }

        $payment = $request->payment[0];
        // dump($payment,'payment');
        $payment['amount'] = $this->transactionUtil->num_uf($payment['amount']);


        $account_payable = Account::where('business_id', $business_id)->where('name', 'Accounts Payable')->first();
        $account_payable_id = !empty($payment['controller_account'])
            ? $payment['controller_account']
            : (!empty($account_payable) ? $account_payable->id : null);

        // S421: Do not rollback a saved expense only because the tenant has not
        // configured an Accounts Payable account. Earlier code used first()->id
        // which threw an exception and the newly-added expense was not visible in
        // List Expenses. Keep the expense/payment saved and skip only the AP leg
        // when no payable account is available.
        if (empty($account_payable_id)) {
            Log::warning('Expense account transaction skipped Accounts Payable leg because no Accounts Payable account is configured. Business ID: ' . $business_id . ' Transaction ID: ' . $transaction->id);
        }
        // dd($account_payable_id,'00');
        $ap_transaction_data = [

            'operation_date' => $transaction->transaction_date,

            'created_by' => Auth::user()->id,

            'transaction_id' => $transaction->id,

            'transaction_payment_id' => !empty($tp) ? $tp->id : null,

            'post_dated_cheque' => !empty($tp) ? $tp->post_dated_cheque : 0,

            'update_post_dated_cheque' => !empty($tp) ? $tp->update_post_dated_cheque : 0,

            'operation_date' =>  $transaction->transaction_date

        ];

        if ($payment['method'] == 'credit_expense') {
            $payment['amount'] = 0;
        }

        // dd($request->select_cheques);
        // dd($request);
        if (!empty($request->select_cheques)) {
            // If partial amount paid with cheques, put the balance in accounts payable
            if ($payment['amount'] < $final_total) {
                $ap_transaction_data['amount'] = $final_total - $payment['amount'];
                if (!empty($account_payable_id)) {
                    $ap_transaction_data['account_id'] = $account_payable_id;
                    $ap_transaction_data['type'] = 'credit';
                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }
            }
        } else {
            //if no amount paid; insert to Account Payable
            if ($payment['amount'] == 0) {
                $ap_transaction_data['amount'] = $final_total;
                if (!empty($account_payable_id)) {
                    $ap_transaction_data['account_id'] = $account_payable_id;
                    $ap_transaction_data['type'] =  'credit';
                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }
            }
            //if partial amount paid; insert paid amount to the payment account and the balance in account payable
            else if ($payment['amount'] < $final_total) {
                $ap_transaction_data['amount'] = $payment['amount'];  //paid amount
                $ap_transaction_data['account_id'] = !empty($tp) ? $tp->account_id : null;
                $ap_transaction_data['type'] =  'credit';
                if (!empty($ap_transaction_data['account_id'])) {
                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }

                $ap_transaction_data['amount'] = $final_total - $payment['amount']; //unpaid amount
                if (!empty($account_payable_id)) {
                    $ap_transaction_data['account_id'] = $account_payable_id;
                    $ap_transaction_data['type'] = 'credit';
                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }
            }
            // if full amount paid; insert amount in payment account
            else if ($payment['amount'] == $final_total) {
                $ap_transaction_data['amount'] = $payment['amount'];
                $ap_transaction_data['account_id'] = !empty($tp) ? $tp->account_id : null;
                $ap_transaction_data['type'] = 'credit';
                if (!empty($ap_transaction_data['account_id'])) {
                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }
            }
        }
    }



    /**

     * Add Account Transactions

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function reverseAccountTransaction($transaction, $request, $business_id)

    {
        // dd($transaction->id);
        // dd(AccountTransaction::where('transaction_id', $transaction->id)->get());
        // dd($transaction, $request, $business_id);
        $records = AccountTransaction::where('transaction_id', $transaction->id);

        // dd($records->count()); // Shows how many rows match before deletion
        // dd($records->forceDelete());
        $records->forceDelete(); // Actually deletes them

        // AccountTransaction::where('transaction_id', $transaction->id)->forcedelete();

    }





    /**

     * Display the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */
}

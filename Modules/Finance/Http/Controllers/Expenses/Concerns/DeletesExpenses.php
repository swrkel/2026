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
 * Deleting an expense and reversing what it posted.
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
 * Methods here: destroy
 */
trait DeletesExpenses
{
    public function destroy($id)

    {
        if (!Gate::forUser(auth()->user())->check('expense.delete')) {

            abort(403, 'Unauthorized action.');
        }
        if (request()->ajax()) {
            try {

                $business_id = request()->session()->get('user.business_id');
                $expense = Transaction::where('business_id', $business_id)->where('id', $id)
                    ->first();

                $has_reviewed = $this->transactionUtil->hasReviewed($expense->transaction_date);

                if (!empty($has_reviewed)) {
                    $output              = [
                        'success' => 0,
                        'msg'     => __('lang_v1.review_first'),
                    ];

                    return Redirect::back()->with(['status' => $output]);
                }


                $reviewed = $this->transactionUtil->get_review($expense->transaction_date, $expense->transaction_date);


                if (!empty($reviewed)) {
                    $output = [
                        'success' => 0,
                        'msg'     => "You can't delete an expense for an already reviewed date",
                    ];

                    return $output;
                }


                $changes = DB::table('reviewed_changes')
                    ->where('business_id', $business_id)
                    ->whereDate('date', date('Y-m-d', strtotime($expense->transaction_date)))
                    ->select('id')
                    ->first();
                if (!empty($changes)) {
                    // dd($changes);
                    $reviewID = $changes->id;
                    $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Deleted an expense: " . $expense->ref_no, "module" => "expense"];

                    DB::table('reviewed_changes_description')->insert($newReview);
                } else {

                    $reviewID = DB::table('reviewed_changes')->insertGetId([
                        'business_id' => $business_id,
                        'date' => $expense->transaction_date
                    ]);


                    $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Deleted an expense: " . $expense->ref_no, "module" => "expense"];

                    DB::table('reviewed_changes_description')->insert($newReview);
                }
                $transaction = $expense;
                $contact = Contact::find($transaction->contact_id);

                if (!empty($contact)) {
                    $this->notificationUtil->autoSendNotification($transaction->business_id, 'supplier_expense_deleted', $transaction, $contact, true);
                }

                $business_id = request()->session()->get('user.business_id');
                $business = Business::where('id', $business_id)->first();
                $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
                $accountName = null;
                $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'expense_deleted')->first();

                if (!empty($msg_template)) {

                    $msg = $msg_template->sms_body;

                    $msg = str_replace('{account}', !empty($accountName) ? $accountName->name : "", $msg);
                    $msg = str_replace('{amount}', $this->transactionUtil->num_f($expense->final_total), $msg);
                    $msg = str_replace('{ref}', $expense->ref_no, $msg);
                    $msg = str_replace('{staff}', auth()->user()->username, $msg);

                    $phones = [];
                    if (!empty($business->sms_settings)) {
                        $phones = explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']));
                    }

                    if (!empty($phones)) {
                        $data = [
                            'sms_settings' => $sms_settings,
                            'mobile_number' => implode(',', $phones),
                            'sms_body' => $msg
                        ];

                        $response = $this->businessUtil->sendSms($data, 'expense_delete');
                    }
                }

                $expense->deleted_by = auth()->user()->id;
                $expense->save();

                //Get original account transactions BEFORE updating them
                $accountTransactions = AccountTransaction::where('transaction_id', $expense->id)->get();
                
                //Mark original account transactions as deleted
                //This allows them to be displayed in red with strikethrough
                AccountTransaction::where('transaction_id', $expense->id)
                    ->update([
                        'new_deleted_at' => now(),
                        'new_deleted_by' => auth()->id()
                    ]);
                
                //Still create reverse entries for accounting balance purposes, but mark them so they can be filtered out from display
                $reverseRecords = [];
                foreach ($accountTransactions as $t) {
                    $reverseRecords[] = [
                        'transaction_id' => $expense->id,
                        'account_id' => $t->account_id,
                        'amount' => $t->amount,
                        'type' => $t->type == 'debit' ? 'credit' : 'debit',
                        'operation_date' => now(),
                        'transaction_payment_id' => $t->transaction_payment_id,
                        'related_account_id' => $t->related_account_id,
                        'created_by' => auth()->id(),
                        'sub_type' => 'expense_reverse' // Mark reverse entries so we can filter them out
                    ];
                }
                AccountTransaction::insert($reverseRecords);

                $output = [

                    'success' => true,

                    'msg' => __("expense.expense_delete_success")

                ];
            } catch (\Exception $e) {
                Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [

                    'success' => false,

                    'msg' => __("messages.something_went_wrong")

                ];
            }



            return $output;
        }
    }



    /**
     * EXP315: Expenses have a system-only payment method that is not configured
     * in Super Admin > Payment Methods. Ensure it is always available on
     * Expense add/edit screens and dynamic location dropdowns.
     */
}

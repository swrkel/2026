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
 * Editing an existing expense.
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
 * Methods here: edit, update, update1, update_old
 */
trait UpdatesExpenses
{
    public function edit($id)

    {
        // dd('12223');

        if (!Gate::forUser(auth()->user())->check('expense.update')) {

            abort(403, 'Unauthorized action.');
        }



        $business_id = request()->session()->get('user.business_id');



        //Check if subscribed or not

        if (!$this->moduleUtil->isSubscribed($business_id)) {

            return $this->moduleUtil->expiredResponse(action('\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseController@index'));
        }



        $business_locations = BusinessLocation::forDropdown($business_id);



        $expense_categories = ExpenseCategory::where('business_id', $business_id)

            ->pluck('name', 'id');

        $expense = Transaction::where('business_id', $business_id)

            ->where('id', $id)->with(['purchase_lines'])

            ->first();



        $users = User::forDropdown($business_id, true, true);
        $employees = EssentialsEmployee::pluck('name', 'id');

        $fleets = Fleet::where('business_id', $business_id)->pluck('vehicle_number', 'id');

        $first_location = BusinessLocation::where('business_id', $business_id)->first();

        $payment_types = !empty($first_location) ? $this->transactionUtil->payment_types($first_location->id, true, false, false, false, true, "is_expense_enabled") : [];
        $payment_types = $this->expensePaymentTypes($payment_types);

        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $account_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');

        $payment_line = $this->dummyPaymentLine;

        $accounts = [];

        $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();

        $current_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Current Assets')->first();

        $current_liability_account_type = AccountType::where('business_id', $business_id)->where('name', 'Current Liabilities')->first();

        $current_liability_account_type_id = !empty($current_liability_account_type) ? $current_liability_account_type->id : 0;



        $contacts = Contact::contactDropdown($business_id, false, false);

        $expense_accounts = [];



        if ($account_module) {

            if (!empty($expense_account_type_id)) {

                $expense_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
                    ->where('accounts.business_id', $business_id)
                    ->where(function ($query) use ($expense_account_type_id) {
                        $query->where('account_groups.name', 'CPC')
                            ->orWhere('accounts.account_type_id', $expense_account_type_id->id)
                            ->orWhere('accounts.name', 'like', '%Expense%');
                    })
                    ->select('accounts.id', 'accounts.name')
                    ->orderBy('accounts.name')
                    ->get()->pluck('name', 'id');
            }
        } else {

            $expense_accounts = Account::where('business_id', $business_id)->where('name', 'Expenses')->pluck('name', 'id');
        }

        if ($expense_accounts->isEmpty()) {
            $expense_accounts = Account::where('business_id', $business_id)
                ->where(function ($query) use ($expense_account_type_id) {
                    if (!empty($expense_account_type_id)) {
                        $query->where('account_type_id', $expense_account_type_id->id);
                    }
                    $query->orWhere('name', 'like', '%Expense%');
                })
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        $current_liabilities_accounts =  Account::where('business_id', $business_id)->where('account_type_id', $current_liability_account_type_id)->pluck('name', 'id');

        $cash_account = Account::where('business_id', $business_id)->where('name', 'Cash')->first();
        $cash_account_id = !empty($cash_account) ? $cash_account->id : null;

        return view('finance::expense.edit')

            ->with(compact(

                'cash_account_id',

                'expense',

                'expense_categories',

                'business_locations',

                'users',

                'employees',

                'fleets',

                'taxes',

                'payment_types',

                'account_module',

                'payment_line',

                'accounts',

                'current_liabilities_accounts',

                'expense_accounts',

                'contacts'

            ));
    }



    /**

     * Update the specified resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @param  int  $id

     * @return \Illuminate\Http\Response
 
     */

    public function update(Request $request, $id)
    {

        if (!Gate::forUser(auth()->user())->check('expense.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate([
                'document' => 'file|max:' . (config('constants.document_size_limit') / 1000)
            ]);

            $transaction_data = $request->only(['is_vat', 'ref_no', 'transaction_date', 'location_id', 'final_total', 'expense_for', 'additional_notes', 'expense_category_id', 'tax_id', 'contact_id', 'expense_account']);
            $transaction_data['transaction_date'] = $transaction_data['transaction_date'] ?? $request->expense_transaction_date;

            $has_reviewed = $this->transactionUtil->hasReviewed($transaction_data['transaction_date']);
            if (!empty($has_reviewed)) {
                return Redirect::back()->with([
                    'status' => ['success' => 0, 'msg' => __('lang_v1.review_first')]
                ]);
            }

            $reviewed = $this->transactionUtil->get_review($transaction_data['transaction_date'], $transaction_data['transaction_date']);
            if (!empty($reviewed)) {
                return Redirect::to('expenses')->with('status', [
                    'success' => 0,
                    'msg' => "You can't modify an expense for an already reviewed date"
                ]);
            }

            $business_id = $request->session()->get('user.business_id');
            if (!$this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse(action('\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseController@index'));
            }

            $transaction_data['transaction_date'] = $this->transactionUtil->uf_date($transaction_data['transaction_date'], true);
            $transaction_data['final_total'] = $this->transactionUtil->num_uf($transaction_data['final_total']);

            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            if (!empty($document_name)) {
                $transaction_data['document'] = $document_name;
            }

            $transaction_data['total_before_tax'] = $transaction_data['final_total'];
            if (!empty($transaction_data['tax_id'])) {
                $tax_details = TaxRate::find($transaction_data['tax_id']);
                $transaction_data['total_before_tax'] = $this->transactionUtil->calc_percentage_base($transaction_data['final_total'], $tax_details->amount);
                $transaction_data['tax_amount'] = $transaction_data['final_total'] - $transaction_data['total_before_tax'];
            }

            // 17 Sep 2026: Pay may exceed the current balance for normal
            // accounts. Only Cash/Card/Cheques in Hand linked accounts are
            // protected. When editing the same expense, add back this expense's
            // existing credit from that protected account because it will be
            // reversed before the replacement entry is posted.
            $balancePayment = $request->input('payment.0', []);
            $balanceMethod = strtolower((string) ($balancePayment['method'] ?? ''));
            $balanceAccountId = (int) ($balancePayment['account_id'] ?? 0);
            $balanceAmount = (float) $this->transactionUtil->num_uf($balancePayment['amount'] ?? 0);

            if ($balanceMethod !== 'credit_expense'
                && $balanceAccountId > 0
                && $balanceAmount > 0
                && Account::checkInsufficientBalance($balanceAccountId)) {
                $available = (float) Account::getAccountBalance($balanceAccountId);
                $existingExpenseCredit = (float) AccountTransaction::where('transaction_id', (int) $id)
                    ->where('account_id', $balanceAccountId)
                    ->where('type', 'credit')
                    ->whereNull('deleted_at')
                    ->sum('amount');
                $effectiveAvailable = $available + $existingExpenseCredit;

                if ($balanceAmount > $effectiveAvailable + 0.000001) {
                    return Redirect::back()->with('status', [
                        'success' => 0,
                        'msg' => 'Insufficient Balance: the selected Cash, Card or Cheques in Hand account has only ' . number_format($effectiveAvailable, 2, '.', ',') . ' available for this updated payment.',
                    ]);
                }
            }

            DB::beginTransaction();


            $transaction = Transaction::findOrFail($id);
            $expense = $transaction;
            $prevTot = $expense->final_total;
            $prevRef = $expense->ref_no;

            $transaction_data['is_recurring'] = $request->has('is_recurring') ? 1 : $transaction->is_recurring;
            $transaction_data['recur_interval'] = $request->has('is_recurring') && !empty($request->input('recur_interval')) ? $request->input('recur_interval') : $transaction->recur_interval;
            $transaction_data['recur_interval_type'] = !empty($request->input('recur_interval_type')) ? $request->input('recur_interval_type') : $transaction->recur_interval_type;
            $transaction_data['recur_repetitions'] = !empty($request->input('recur_repetitions')) ? $request->input('recur_repetitions') : $transaction->recur_repetitions;
            $transaction_data['subscription_repeat_on'] = !empty($request->input('subscription_repeat_on')) ? $request->input('subscription_repeat_on') : $transaction->subscription_repeat_on;

            $transaction->update($transaction_data);

            // Detect changes
            $old_payment_method = $expense->payment_lines->first()->method ?? null;
            $new_payment_method = $request->payment[0]['method'];

            $old_expense_account = $expense->expense_account;
            $new_expense_account = $transaction_data['expense_account'];

            if ($old_payment_method !== $new_payment_method && $new_payment_method === 'credit_expense') {
                $transaction->payment_status = 'due';
                $transaction->update();
            }
            $business = Business::find($business_id);
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

            $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'expense_changed')->first();
            if (!empty($msg_template)) {
                $msg = $msg_template->sms_body;
                $msg = str_replace('{account}', '', $msg);
                $msg = str_replace('{amount}', $this->transactionUtil->num_f($transaction->final_total), $msg);
                $msg = str_replace('{ref}', $transaction->ref_no, $msg);
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
                    $this->businessUtil->sendSms($data, 'expense_changed');
                }
            }

            $transaction_id = $transaction->id;
            $inputs = $this->prepareExpensePdChequePaymentInputs($request->payment[0], $transaction->expense_category_id);
            // dd($inputs);

            $inputs['paid_on'] = $transaction->transaction_date;
            $inputs['transaction_id'] = $transaction->id;
            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
            $inputs['created_by'] = auth()->user()->id;
            $inputs['payment_for'] = $transaction->contact_id;
            $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

            $transaction->controller_account = $inputs['controller_account'] ?? null;
            $transaction->save();
            // dd($transaction);

            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense_payment');

            $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber('expense_payment', $ref_count);
            //  dd($ref_count);
            $inputs['business_id'] = $business_id;
            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            $inputs['is_return'] = 0;
            $inputs['cheque_date'] = $inputs['cheque_date'] ?? $transaction->transaction_date;

            unset($inputs['transaction_no_1'], $inputs['transaction_no_2'], $inputs['transaction_no_3'], $inputs['controller_account']);

            $this->reverseAccountTransaction($transaction, $request, $business_id);
            // dd($test);
            // Handle cheque linking
            $cheque_nos = "";
            if (!empty($request->select_cheques)) {
                foreach ($request->select_cheques as $select_cheque) {
                    $account_transaction = AccountTransaction::find($select_cheque);
                    $transaction_payment = TransactionPayment::find($account_transaction->transaction_payment_id);

                    if (!empty($transaction_payment)) {
                        $amount = $this->transactionUtil->num_uf($account_transaction->amount);
                        if (!empty($amount)) {
                            $credit_data = [
                                'amount' => $amount,
                                'account_id' => $account_transaction->account_id,
                                'transaction_id' => $transaction->id,
                                'type' => 'credit',
                                'operation_date' => $transaction_data['transaction_date'],
                                'created_by' => session()->get('user.id'),
                                'transaction_payment_id' => $transaction_payment->id
                            ];
                            AccountTransaction::createAccountTransaction($credit_data);
                            $cheque_nos .= $transaction_payment->cheque_number . ",";
                            $transaction_payment->is_deposited = 1;
                            $transaction_payment->save();
                        }
                    }
                }
                $inputs['cheque_number'] = rtrim($cheque_nos, ',');
            }

            $tp = null;
            if ($inputs['method'] != 'credit_expense') {
                $tp = TransactionPayment::updateOrCreate(['transaction_id' => $transaction_id], $inputs);
                $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);
            }

            // update payment status
            $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total, $inputs['method']);

            // update note/description if expense account changed
            if ($old_expense_account != $new_expense_account) {
                $new_account_name = Account::find($new_expense_account)->name ?? '';
                $inputs['note'] = 'Expense for: ' . $new_account_name;
            }

            $this->addAccountTransaction($transaction, $request, $business_id, $tp);

            // change review notes
            if ($transaction_data['final_total'] != $prevTot) {
                $this->transactionUtil->reviewChange($transaction_data['transaction_date'], [
                    "created_by" => auth()->id(),
                    "description" => "Changed expense amount from: " . $this->transactionUtil->num_f($prevTot) . " to: " . $this->transactionUtil->num_f($transaction_data['final_total']) . " for expense " . $expense->ref_no,
                    "module" => "expense"
                ]);
            }

            if ($transaction_data['ref_no'] != $prevRef) {
                $this->transactionUtil->reviewChange($transaction_data['transaction_date'], [
                    "created_by" => auth()->id(),
                    "description" => "Changed expense reference no from : $prevRef to: " . $transaction_data['ref_no'] . " for expense " . $expense->ref_no,
                    "module" => "expense"
                ]);
            }

            $this->transactionUtil->calculateAndUpdateVAT($transaction);

            DB::commit();

            $output = ['success' => 1, 'msg' => __('expense.expense_update_success')];

            if ($request->is_print == 1) {
                return Redirect::route('expense-print', [$transaction_id]);
            }
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return Redirect::to('expenses')->with('status', $output);
    }


    /**

     * Remove the specified resource from storage.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function update1(Request $request, $id)

    {
        // dump($request->payment[0]['method']); 
        // dd($request->all(),$id, 'testing');
        $paymentMethod = $request->payment[0]['method'];
        $transaction_id = '1';
        // $transaction->final_total ='2';
        $this->transactionUtil->updatePaymentStatus($transaction_id, '2', $paymentMethod);
    }

    public function update_old(Request $request, $id)

    {
        dd($request->all());

        if (!Gate::forUser(auth()->user())->check('expense.update')) {

            abort(403, 'Unauthorized action.');
        }



        try {

            //Validate document size

            $request->validate([

                'document' => 'file|max:' . (config('constants.document_size_limit') / 1000)

            ]);



            $transaction_data = $request->only(['is_vat', 'ref_no', 'transaction_date', 'location_id', 'final_total', 'expense_for', 'additional_notes', 'expense_category_id', 'tax_id', 'contact_id', 'expense_account']);
            $transaction_data['transaction_date'] = $transaction_data['transaction_date'] ?? $request->expense_transaction_date;
            $has_reviewed = $this->transactionUtil->hasReviewed($transaction_data['transaction_date']);

            if (!empty($has_reviewed)) {
                $output              = [
                    'success' => 0,
                    'msg'     => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($transaction_data['transaction_date'], $transaction_data['transaction_date']);

            // dd($reviewed,'$reviewed');
            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => "You can't modify an expense for an already reviewed date",
                ];

                return Redirect::to('expenses')->with('status', $output);
            }




            $business_id = $request->session()->get('user.business_id');



            //Check if subscribed or not

            if (!$this->moduleUtil->isSubscribed($business_id)) {

                return $this->moduleUtil->expiredResponse(action('\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseController@index'));
            }



            $transaction_data['transaction_date'] = $this->transactionUtil->uf_date($transaction_data['transaction_date'], true);

            $transaction_data['final_total'] = $this->transactionUtil->num_uf(

                $transaction_data['final_total']

            );



            //upload document

            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            if (!empty($document_name)) {

                $transaction_data['document'] = $document_name;
            }



            $transaction_data['total_before_tax'] = $transaction_data['final_total'];

            if (!empty($transaction_data['tax_id'])) {

                $tax_details = TaxRate::find($transaction_data['tax_id']);

                $transaction_data['total_before_tax'] = $this->transactionUtil->calc_percentage_base($transaction_data['final_total'], $tax_details->amount);

                $transaction_data['tax_amount'] = $transaction_data['final_total'] - $transaction_data['total_before_tax'];
            }

            DB::beginTransaction();



            $transaction = Transaction::findOrFail($id);

            $expense = $transaction;

            $prevTot = $expense->final_total;
            $prevRef = $expense->ref_no;
            // dump($prevTot,$prevRef);
            // dd($transaction,'$business_id',$expense);

            $transaction_data['is_recurring'] = $request->has('is_recurring') ? 1 : $transaction->is_recurring;

            $transaction_data['recur_interval'] = $request->has('is_recurring') && !empty($request->input('recur_interval')) ? $request->input('recur_interval') : $transaction->recur_interval;

            $transaction_data['recur_interval_type'] = !empty($request->input('recur_interval_type')) ? $request->input('recur_interval_type') : $transaction->recur_interval_type;

            $transaction_data['recur_repetitions'] = !empty($request->input('recur_repetitions')) ? $request->input('recur_repetitions') : $transaction->recur_repetitions;

            $transaction_data['subscription_repeat_on'] = !empty($request->input('subscription_repeat_on')) ? $request->input('subscription_repeat_on') : $transaction->subscription_repeat_on;



            $transaction->update($transaction_data);


            $business_id = request()->session()->get('user.business_id');
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            $accountName = null;
            $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'expense_changed')->first();
            if (!empty($msg_template)) {
                $msg = $msg_template->sms_body;

                $msg = str_replace('{account}', !empty($accountName) ? $accountName->name : "", $msg);
                $msg = str_replace('{amount}', $this->transactionUtil->num_f($transaction->final_total), $msg);
                $msg = str_replace('{ref}', $transaction->ref_no, $msg);
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

                    $response = $this->businessUtil->sendSms($data, 'expense_changed');
                }
            }




            $transaction_id =  $transaction->id;



            $inputs = $this->prepareExpensePdChequePaymentInputs($request->payment[0], $transaction->expense_category_id);


            $inputs['paid_on'] = $transaction->transaction_date;

            $inputs['transaction_id'] = $transaction->id;



            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            $inputs['created_by'] = auth()->user()->id;

            $inputs['payment_for'] = $transaction->contact_id;

            // post dated cheque input
            $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

            if (!empty($inputs['post_dated_cheque']) || !empty($inputs['update_post_dated_cheque'])) {
                $expense_category_name = optional(ExpenseCategory::find($transaction->expense_category_id))->name;
                $bank_account = !empty($inputs['related_account_id']) ? Account::find($inputs['related_account_id']) : null;
                $bank_name = !empty($bank_account) ? $bank_account->name : '';
                $inputs['note'] = trim(($expense_category_name ?? '') . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
                Log::info('Expense PD cheque update prepared', [
                    'transaction_id' => $transaction->id,
                    'ref_no' => $transaction->ref_no,
                    'payment_method' => $inputs['method'] ?? null,
                    'account_id' => $inputs['account_id'] ?? null,
                    'account_name' => optional(Account::find($inputs['account_id'] ?? null))->name,
                    'related_account_id' => $inputs['related_account_id'] ?? null,
                    'related_account_name' => optional($bank_account)->name,
                    'post_dated_cheque' => $inputs['post_dated_cheque'] ?? 0,
                    'update_post_dated_cheque' => $inputs['update_post_dated_cheque'] ?? 0,
                    'cheque_number' => $inputs['cheque_number'] ?? null,
                    'cheque_date' => $inputs['cheque_date'] ?? null,
                    'note' => $inputs['note'] ?? null,
                ]);
            }



            $prefix_type = 'expense_payment';

            if ($transaction->type == 'expense') {
                $prefix_type = 'expense_payment';
            }



            $transaction->controller_account = !empty($inputs['controller_account']) ? $inputs['controller_account'] : null;

            $transaction->save();



            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);

            //Generate reference number

            $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);



            $inputs['business_id'] = $business_id;

            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');



            $inputs['is_return'] =  0; //added by ahmed

            $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? $inputs['cheque_date'] : $transaction->transaction_date;


            unset($inputs['transaction_no_1']);

            unset($inputs['transaction_no_2']);

            unset($inputs['transaction_no_3']);

            unset($inputs['controller_account']);

            $tp = null;

            $this->reverseAccountTransaction($transaction, $request, $business_id); // reverse previous account transaction


            $cheque_nos = "";
            if (!empty($request->select_cheques)) {
                foreach ($request->select_cheques as $select_cheque) {
                    if (!empty($select_cheque)) {
                        $account_transaction = AccountTransaction::find($select_cheque);

                        $transaction_payment = TransactionPayment::find($account_transaction->transaction_payment_id);

                        if (!empty($transaction_payment)) {
                            $amount = $this->transactionUtil->num_uf($account_transaction->amount);
                            if (!empty($amount)) {
                                $credit_data = [
                                    'amount' => $amount,
                                    'account_id' => $account_transaction->account_id,
                                    'transaction_id' => $transaction->id,
                                    'type' => 'credit',
                                    'sub_type' => null,
                                    'operation_date' => $transaction_data['transaction_date'],
                                    'created_by' => session()->get('user.id'),
                                    'transaction_payment_id' => $transaction_payment->id,
                                    'note' => null,
                                    'attachment' => null
                                ];
                                $credit = AccountTransaction::createAccountTransaction($credit_data);

                                $cheque_nos .= !empty($transaction_payment->cheque_number) ? $transaction_payment->cheque_number . "," : "";

                                $transaction_payment->is_deposited = 1;
                                $transaction_payment->save();
                            }
                        }
                    }
                }

                $inputs['cheque_number'] = $cheque_nos;
            }


            if ($inputs['method'] != 'credit_expense') {
                $tp = TransactionPayment::updateOrCreate(['transaction_id' => $transaction_id], $inputs);
                $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);
            }


            //update payment status
            $paymentMethod = $request->payment[0]['method'];

            $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total, $paymentMethod);
            // dump('001');


            dd('1001');
            if ($transaction_data['final_total'] != $prevTot) {
                $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Changed expense amount from: " . $this->transactionUtil->num_f($prevTot) . " to: " . $this->transactionUtil->num_f($transaction_data['final_total']) . " for expense " . $expense->ref_no, "module" => "expense"];
                $reviewed = $this->transactionUtil->reviewChange($transaction_data['transaction_date'], $newReview);
            }

            dd('00111');
            if ($transaction_data['ref_no'] != $prevRef) {
                $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Changed expense reference no from : " . $prevRef . " to: " . $transaction_data['ref_no'] . " for expense " . $expense->ref_no, "module" => "expense"];
                $reviewed = $this->transactionUtil->reviewChange($transaction_data['transaction_date'], $newReview);
            }

            $this->transactionUtil->calculateAndUpdateVAT($transaction);

            DB::commit();

            $output = [

                'success' => 1,

                'msg' => __('expense.expense_update_success')

            ];
            if ($request->is_print == 1) {
                return Redirect::route('expense-print', [$transaction_id]);
            }
            $this->addAccountTransaction($transaction, $request, $business_id, $tp); // add new transactions

        } catch (\Exception $e) {

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());



            $output = [

                'success' => 0,

                'msg' => __('messages.something_went_wrong')

            ];
        }



        return Redirect::to('expenses')->with('status', $output);
    }
}

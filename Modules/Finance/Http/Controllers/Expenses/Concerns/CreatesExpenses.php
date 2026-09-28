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
 * Creating an expense.
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
 * Methods here: create, store, storeold
 */
trait CreatesExpenses
{
    public function create()

    {

        if (!Gate::forUser(auth()->user())->check('expense.create')) {

            abort(403, 'Unauthorized action.');
        }



        $business_id = request()->session()->get('user.business_id');



        //Check if subscribed or not

        if (!$this->moduleUtil->isSubscribed($business_id)) {

            return $this->moduleUtil->expiredResponse(action('\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseController@index'));
        }



        $account_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');

        $payment_line = $this->dummyPaymentLine;
        $first_location = BusinessLocation::where('business_id', $business_id)->first();

        $payment_types = !empty($first_location) ? $this->transactionUtil->payment_types($first_location->id, true, false, false, false, true, "is_expense_enabled") : [];
        $payment_types = $this->expensePaymentTypes($payment_types);

        $accounts = [];

        $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();

        $current_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Current Assets')->first();

        $current_liability_account_type = AccountType::where('business_id', $business_id)->where('name', 'Current Liabilities')->first();

        $current_liability_account_type_id = !empty($current_liability_account_type) ? $current_liability_account_type->id : 0;

        $expense_accounts = [];

        $expense_account_id = null;

        $payee_name = Contact::select('name')->where('business_id', $business_id)->first();



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

            $expense_account = Account::where('name', 'Expenses')->where('business_id', $business_id)->first();
            $expense_account_id = !empty($expense_account) ? $expense_account->id : null;

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



        $business_locations = BusinessLocation::forDropdown($business_id);

        $contacts = Contact::contactDropdown($business_id, false, false);

        $expense_categories = ExpenseCategory::where('business_id', $business_id)

            ->pluck('name', 'id');

        $users = User::forDropdown($business_id, true, true);
        $employees = EssentialsEmployee::pluck('name', 'id');

        $fleets = Fleet::where('business_id', $business_id)->pluck('vehicle_number', 'id');



        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);



        $ref_count = $this->transactionUtil->onlyGetReferenceCount('expense', null, false);

        //Generate reference number

        $ref_no = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);





        $temp_data = DB::table('temp_data')->where('business_id', $business_id)->select('add_expense_data')->first();

        if (!empty($temp_data)) {

            $temp_data = json_decode($temp_data->add_expense_data);
        }

        if (!request()->session()->get('business.popup_load_save_data')) {

            $temp_data = [];
        }

        $cash_account = Account::where('business_id', $business_id)->where('name', 'Cash')->first();
        $cash_account_id = !empty($cash_account) ? $cash_account->id : null;



        $fleet_module = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'fleet_module');

        $bank_group_accounts = Account::leftJoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->where('accounts.business_id', $business_id)
            ->where('account_groups.name', 'Bank Account')
            ->pluck('accounts.name', 'accounts.id');

        // Remove "Issued Post Dated Cheques"
        $bank_group_accounts = $bank_group_accounts->reject(function ($name) {
            return trim($name) === "Issued Post Dated Cheques";
        });

        // Debugging to check the filtered collection


        $cpc_accounts = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->where('accounts.business_id', $business_id)
            ->where('account_groups.name', 'CPC')
            ->pluck('accounts.name', 'accounts.id');

        $dailyCashShiftNumbers = PetroDailyShift::where('business_id', $business_id)
            // ->where(function($query) {
            //     $query ->WhereRaw('CHAR_LENGTH(pump_operator_pending) >= 1');
            // })
            ->where('status', 0)
            ->pluck('shift_no');
        $dailyCashShiftNumbers = $dailyCashShiftNumbers->unique()->toArray();

        return view('finance::expense.create')

            ->with(compact(
                'cpc_accounts',
                'dailyCashShiftNumbers',
                'bank_group_accounts',
                'cash_account_id',

                'cash_account_id',

                'ref_no',

                'account_module',

                'accounts',

                'expense_accounts',

                'payment_types',

                'payment_line',

                'expense_categories',

                'business_locations',

                'users',

                'employees',

                'fleets',

                'fleet_module',

                'taxes',

                'temp_data',

                'contacts',

                'current_liabilities_accounts',

                'expense_account_id',
                'payee_name'

            ));
    }



    /**

     * Store a newly created resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */

    public function store(Request $request)
{
    if (!Gate::forUser(auth()->user())->check('expense.create')) {
        abort(403, 'Unauthorized action.');
    }
    try {
        $business_id = $request->session()->get('user.business_id');
        DB::table('temp_data')->where('business_id', $business_id)->update(['add_expense_data' => '']);

        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action('\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseController@index'));
        }

        //Validate document size
        $request->validate([
            'document' => 'file|max:' . (config('constants.document_size_limit') / 1000)
        ]);

        $transaction_data = $request->only([
            'is_vat',
            'ref_no',
            'shift_number',
            'transaction_date',
            'location_id',
            'final_total',
            'expense_for',
            'fleet_id',
            'additional_notes',
            'expense_category_id',
            'tax_id',
            'contact_id'
        ]);
        $expense_items = collect($request->input('expense_items', []))
            ->map(function ($item) {
                return [
                    'expense_category_id' => !empty($item['expense_category_id']) ? (int) $item['expense_category_id'] : null,
                    'amount' => isset($item['amount']) ? (float) str_replace(',', '', $item['amount']) : 0,
                    'expense_account' => !empty($item['expense_account']) ? (int) $item['expense_account'] : null,
                    'is_vat' => isset($item['is_vat']) ? (int) $item['is_vat'] : 0,
                    'tax_id' => !empty($item['tax_id']) ? (int) $item['tax_id'] : null,
                    'ref_no' => $item['ref_no'] ?? null,
                    'additional_notes' => $item['additional_notes'] ?? null,
                ];
            })
            ->filter(function ($item) {
                return !empty($item['expense_category_id']) && $item['amount'] > 0;
            })
            ->values();

        // IS1539 root save fix:
        // The Add Expense screen now submits detail rows. If the summary fields were not
        // synchronized by JavaScript before clicking Save / Save & Print, the old code tried
        // to save blank category/account/total values and the transaction was rolled back.
        // Build the required summary values on the server as well so both buttons save reliably.
        if ($expense_items->count() > 0) {
            $first_expense_item = $expense_items->first();
            $calculated_total = $expense_items->sum('amount');

            if (empty($transaction_data['expense_category_id']) && !empty($first_expense_item['expense_category_id'])) {
                $transaction_data['expense_category_id'] = $first_expense_item['expense_category_id'];
                $request->merge(['expense_category_id' => $first_expense_item['expense_category_id']]);
            }

            if ((empty($transaction_data['final_total']) || (float) str_replace(',', '', $transaction_data['final_total']) <= 0) && $calculated_total > 0) {
                $transaction_data['final_total'] = $calculated_total;
                $request->merge(['final_total' => $calculated_total]);
            }

            if (empty($request->expense_account) && !empty($first_expense_item['expense_account'])) {
                $request->merge(['expense_account' => $first_expense_item['expense_account']]);
            }

            if (!isset($transaction_data['is_vat']) && isset($first_expense_item['is_vat'])) {
                $transaction_data['is_vat'] = $first_expense_item['is_vat'];
                $request->merge(['is_vat' => $first_expense_item['is_vat']]);
            }

            if (empty($transaction_data['tax_id']) && !empty($first_expense_item['tax_id'])) {
                $transaction_data['tax_id'] = $first_expense_item['tax_id'];
                $request->merge(['tax_id' => $first_expense_item['tax_id']]);
            }

            if (empty($transaction_data['additional_notes']) && !empty($first_expense_item['additional_notes'])) {
                $transaction_data['additional_notes'] = $first_expense_item['additional_notes'];
                $request->merge(['additional_notes' => $first_expense_item['additional_notes']]);
            }
        }

        $is_multi_expense_submit = $expense_items->count() > 1;
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

        if (!empty($reviewed)) {
            $output = [
                'success' => 0,
                'msg'     => "You can't add an expense for an already reviewed date",
            ];

            return Redirect::to('expenses')->with('status', $output);
        }

        // ============= PROTECTED SOURCE ACCOUNT BALANCE CHECK =============
        // Only Cash, Card and Cheques in Hand linked accounts are restricted.
        // Every other account may be overpaid / go beyond its current balance.
        $accsForWhichToCheckInsufficientBalances = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
            ->whereIn('account_groups.name', ['Cash Account', 'Card', "Cheques in Hand (Customer's)"])
            ->where('accounts.business_id', $business_id)
            ->pluck('accounts.id')
            ->map(static function ($id) {
                return (int) $id;
            })
            ->toArray();
        
        // Check payments only when the selected source account is protected.
        $payments = $request->input('payment') ?? [];
        foreach ($payments as $payment_arr) {
            $method = strtolower((string) ($payment_arr['method'] ?? ''));
            if ($method === 'credit_expense') {
                continue;
            }
            $accountId   = $payment_arr['account_id'] ?? null;
            $amountToPay = (float) $this->transactionUtil->num_uf($payment_arr['amount'] ?? 0);

            if (empty($accountId) || (float) $amountToPay <= 0) {
                continue;
            }

            if (in_array((int) $accountId, $accsForWhichToCheckInsufficientBalances, true)) {
                $balance = Account::getAccountBalance($accountId);

                if ($balance < $amountToPay) {
                    $output = [
                        'success' => 0,
                        'msg'     => "Insufficient balance in the selected Cash, Card or Cheques in Hand account",
                    ];
                    return Redirect::to('expenses')->with('status', $output);
                }
            }
        }
        // ============= END PROTECTED SOURCE ACCOUNT BALANCE CHECK =============

        if ($is_multi_expense_submit) {
            $user_id = $request->session()->get('user.id');
            $base_transaction_data = $transaction_data;
            $base_transaction_data['business_id'] = $business_id;
            $base_transaction_data['created_by'] = $user_id;
            $base_transaction_data['type'] = 'expense';
            $base_transaction_data['status'] = 'final';
            $base_transaction_data['payment_status'] = 'due';
            $base_transaction_data['controller_account'] = $request->controller_account;
            $base_transaction_data['transaction_date'] = $this->transactionUtil->uf_date($base_transaction_data['transaction_date'], true);
            $base_transaction_data['tax_id'] = null;
            $base_transaction_data['tax_amount'] = 0;

            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            if (!empty($document_name)) {
                $base_transaction_data['document'] = $document_name;
            }

            $original_payment = $request->input('payment.0', []);
            $original_payment_method = $original_payment['method'] ?? 'credit_expense';
            $created_transaction_ids = [];
            $budget_usage_rows = [];

            DB::beginTransaction();
            foreach ($expense_items as $index => $expense_item) {
                $item_transaction_data = $base_transaction_data;
                $item_amount = $this->transactionUtil->num_uf($expense_item['amount']);
                $item_transaction_data['expense_category_id'] = $expense_item['expense_category_id'];
                $item_transaction_data['expense_account'] = $expense_item['expense_account'] ?: $request->expense_account;
                $item_transaction_data['is_vat'] = $expense_item['is_vat'];
                $item_transaction_data['tax_id'] = !empty($expense_item['tax_id']) ? $expense_item['tax_id'] : null;
                $item_transaction_data['additional_notes'] = !empty($expense_item['additional_notes']) ? $expense_item['additional_notes'] : ($request->additional_notes ?? null);
                $item_transaction_data['final_total'] = $item_amount;
                $item_transaction_data['total_before_tax'] = $item_amount;

                if (!empty($expense_item['ref_no'])) {
                    $item_transaction_data['ref_no'] = $expense_item['ref_no'];
                } elseif (!empty($request->ref_no)) {
                    $item_transaction_data['ref_no'] = $request->ref_no . '-' . ($index + 1);
                } else {
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');
                    $item_transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);
                }

                if (!empty($item_transaction_data['tax_id'])) {
                    $tax_details = TaxRate::find($item_transaction_data['tax_id']);
                    if (!empty($tax_details)) {
                        $item_transaction_data['total_before_tax'] = $this->transactionUtil->calc_percentage_base($item_amount, $tax_details->amount);
                        $item_transaction_data['tax_amount'] = $item_amount - $item_transaction_data['total_before_tax'];
                    }
                }

                $transaction = Transaction::create($item_transaction_data);
                $this->transactionUtil->calculateAndUpdateVAT($transaction);

                $row_payment = $original_payment;
                $row_payment['method'] = $original_payment_method;
                $row_payment['amount'] = $item_amount;
                $row_payment['controller_account'] = $request->controller_account;
                $row_payment['cheque_date'] = !empty($row_payment['cheque_date']) ? $row_payment['cheque_date'] : $transaction->transaction_date;
                $row_payment['created_by'] = $user_id;
                $row_payment['payment_for'] = $transaction->contact_id;
                $row_payment['business_id'] = $business_id;
                $row_payment['paid_on'] = $transaction->transaction_date;
                $row_payment['transaction_id'] = $transaction->id;
                $row_payment['is_return'] = 0;

                $tp = null;
                if ($item_amount > 0) {
                    $row_payment = $this->prepareExpensePdChequePaymentInputs($row_payment, $transaction->expense_category_id);
                    $row_payment['amount'] = $item_amount;
                    $row_payment['created_by'] = $user_id;
                    $row_payment['payment_for'] = $transaction->contact_id;
                    $row_payment['business_id'] = $business_id;
                    $row_payment['paid_on'] = $transaction->transaction_date;
                    $row_payment['transaction_id'] = $transaction->id;
                    $row_payment['is_return'] = 0;
                    $row_payment = $this->normalizeExpensePaymentPdChequeInputs($row_payment);
                    $tp = TransactionPayment::create($row_payment);
                    $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);

                    if ($row_payment['method'] != 'credit_expense') {
                        $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total, $row_payment['method']);
                    }
                }

                $row_request = new Request();
                $row_request->replace([
                    'expense_account' => $item_transaction_data['expense_account'],
                    'final_total' => $item_amount,
                    'payment' => [$row_payment]
                ]);
                $this->addAccountTransaction($transaction, $row_request, $business_id, $tp);

                $budget_usage_rows[] = [
                    'location_id' => $transaction->location_id,
                    'expense_account' => $transaction->expense_account,
                    'amount' => $transaction->final_total,
                    'transaction_id' => $transaction->id,
                ];

                $created_transaction_ids[] = $transaction->id;
            }

            DB::commit();

            // Budget and audit integrations are secondary to the core expense save.
            // A missing Finance table/configuration must not roll back valid expenses.
            foreach ($budget_usage_rows as $budget_usage_row) {
                try {
                    FinanceBudgetControlService::checkBudgetUsage(
                        $business_id,
                        $budget_usage_row['location_id'],
                        $budget_usage_row['expense_account'],
                        $budget_usage_row['amount'],
                        'transactions',
                        $budget_usage_row['transaction_id']
                    );
                } catch (\Throwable $budget_exception) {
                    Log::warning('Expense saved but Finance budget update failed. Transaction ID: '
                        . $budget_usage_row['transaction_id'] . ' Message: ' . $budget_exception->getMessage());
                }
            }

            try {
                $newReview = [
                    "created_by" => $user_id,
                    "description" => "Created multiple expenses in one submit",
                    "module" => "expense"
                ];
                $this->transactionUtil->reviewChange($base_transaction_data['transaction_date'], $newReview);
            } catch (\Throwable $review_exception) {
                Log::warning('Multiple expenses saved but audit review logging failed. Message: ' . $review_exception->getMessage());
            }

            $output = [
                'success' => 1,
                'msg' => __('expense.expense_add_success')
            ];

            if ($request->is_print == 1 && !empty($created_transaction_ids[0])) {
                return Redirect::route('expense-print', [$created_transaction_ids[0]]);
            }

            return Redirect::to('expenses')->with('status', $output);
        }


        $user_id = $request->session()->get('user.id');

        $transaction_data['business_id'] = $business_id;

        $transaction_data['created_by'] = $user_id;

        $transaction_data['type'] = 'expense';

        $transaction_data['status'] = 'final';

        $transaction_data['payment_status'] = 'due';

        $transaction_data['expense_account'] = $request->expense_account;

        $transaction_data['controller_account'] = $request->controller_account;
        // dd($transaction_data['controller_account']);
        $transaction_data['transaction_date'] = $this->transactionUtil->uf_date($transaction_data['transaction_date'], true);

        $transaction_data['final_total'] = $this->transactionUtil->num_uf(

            $transaction_data['final_total']

        );

        $transaction_data['total_before_tax'] = $transaction_data['final_total'];

        if (!empty($transaction_data['tax_id'])) {

            $tax_details = TaxRate::find($transaction_data['tax_id']);

            $transaction_data['total_before_tax'] = $this->transactionUtil->calc_percentage_base($transaction_data['final_total'], $tax_details->amount);

            $transaction_data['tax_amount'] = $transaction_data['final_total'] - $transaction_data['total_before_tax'];
        }

        //Update reference count

        $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');

        //Generate reference number

        if (empty($transaction_data['ref_no'])) {

            $transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);
        }

        //upload document

        $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');

        if (!empty($document_name)) {

            $transaction_data['document'] = $document_name;
        }

        if ($request->has('is_recurring')) {

            $transaction_data['is_recurring'] = 1;

            $transaction_data['recur_interval'] = !empty($request->input('recur_interval')) ? $request->input('recur_interval') : 1;

            $transaction_data['recur_interval_type'] = $request->input('recur_interval_type');

            $transaction_data['recur_repetitions'] = $request->input('recur_repetitions');

            $transaction_data['subscription_repeat_on'] = $request->input('recur_interval_type') == 'months' && !empty($request->input('subscription_repeat_on')) ? $request->input('subscription_repeat_on') : null;
        }

        DB::beginTransaction();
        $transaction = Transaction::create($transaction_data);
        // add VAT components
        $this->transactionUtil->calculateAndUpdateVAT($transaction);
        $transaction_id =  $transaction->id;
        $tp = null;

        if (!empty($request->payment[0])) {
            $inputs = $this->prepareExpensePdChequePaymentInputs($request->payment[0], $transaction->expense_category_id);

            $inputs['paid_on'] = $transaction->transaction_date;

            $inputs['transaction_id'] = $transaction->id;

            $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? $inputs['cheque_date'] : $transaction->transaction_date;


            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            $amount = $inputs['amount'];

            if ($amount > 0) {
                if ($inputs['method'] != 'credit_expense') {
                    // Check payment method (Cash, Credit, etc.)
                    if ($inputs['method'] == 'cash') {
                        // If the full amount is paid
                        if ($amount >= $transaction->final_total) {
                            $transaction->payment_status = 'paid'; // Set status to paid
                        } else {
                            $transaction->payment_status = 'partial'; // Set status to partial if it's less than the total
                        }
                    }

                    $transaction->save();
                    $inputs['created_by'] = auth()->user()->id;

                    $inputs['payment_for'] = $transaction->contact_id;
                    $prefix_type = 'expense_payment';
                    if ($transaction->type == 'expense') {
                        $prefix_type = 'expense_payment';
                    }

                    $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                    //Generate reference number

                    $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);



                    $inputs['business_id'] = $business_id;

                    $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');


                    // post dated cheque input
                    $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

                    if (!empty($inputs['post_dated_cheque']) || !empty($inputs['update_post_dated_cheque'])) {
                        $expense_category_name = optional(ExpenseCategory::find($transaction->expense_category_id))->name;
                        $bank_account = !empty($inputs['related_account_id']) ? Account::find($inputs['related_account_id']) : null;
                        $bank_name = !empty($bank_account) ? $bank_account->name : '';
                        $inputs['note'] = trim(($expense_category_name ?? '') . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
                    }

                    $inputs['is_return'] =  0; //added by 

                    unset($inputs['transaction_no_1']);

                    unset($inputs['transaction_no_2']);

                    unset($inputs['transaction_no_3']);

                    unset($inputs['controller_account']);


                    $cheque_nos = "";
                    // dd($request->select_cheques);
                    if (!empty($request->select_cheques)) {
                        foreach ($request->select_cheques as $select_cheque) {
                            if (!empty($select_cheque)) {
                                $account_transaction = AccountTransaction::find($select_cheque);

                                $transaction_payment = TransactionPayment::find($account_transaction->transaction_payment_id);
                                // dd($transaction_payment);
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
                    $inputs['shift_number'] = $transaction_data['shift_number'] ?? null;
                    $tp = TransactionPayment::create($inputs);
                    $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);


                    //update payment status

                    $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total, $inputs['method']);
                } else {
                    // Special credit expense payment logic
                    $inputs['created_by'] = auth()->user()->id;
                    $inputs['payment_for'] = $transaction->contact_id;
                    $inputs['business_id'] = $business_id;

                    // Generate proper reference number (don't use time())
                    $prefix_type = 'expense_payment';
                    // $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                    // $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                    // Essential fields for any payment
                    $inputs['paid_on'] = $transaction->transaction_date;
                    $inputs['transaction_id'] = $transaction->id;
                    // $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                    $inputs['method'] = 'credit_expense';
                    $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? $inputs['cheque_date'] : $transaction->transaction_date;

                    // Document handling if exists
                    // $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

                    // Default values
                    $inputs['is_return'] = 0;
                    $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

                    $tp = TransactionPayment::create($inputs);
                    $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);

                    // Update payment status
                    // $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                }
            }
        }

        $this->addAccountTransaction($transaction, $request, $business_id, $tp);

        // Commit the complete expense/payment/account posting before optional integrations.
        // Finance budget, audit-log or notification configuration errors must not make a
        // successfully posted expense disappear from the List Expenses page.
        DB::commit();

        try {
            FinanceBudgetControlService::checkBudgetUsage(
                $business_id,
                $transaction->location_id,
                $transaction->expense_account,
                $transaction->final_total,
                'transactions',
                $transaction->id
            );
        } catch (\Throwable $budget_exception) {
            Log::warning('Expense saved but Finance budget update failed. Transaction ID: '
                . $transaction_id . ' Message: ' . $budget_exception->getMessage());
        }

        try {
            $newReview = [
                "created_by" => request()->session()->get('user.id'),
                "description" => "Created a new expense: " . $transaction_data['ref_no'],
                "module" => "expense"
            ];
            $this->transactionUtil->reviewChange($transaction_data['transaction_date'], $newReview);
        } catch (\Throwable $review_exception) {
            Log::warning('Expense saved but audit review logging failed. Transaction ID: '
                . $transaction_id . ' Message: ' . $review_exception->getMessage());
        }

        try {
            $accountName = Account::find($transaction->expense_account);
            $expenseCategory = ExpenseCategory::find($transaction->expense_category_id);
            $sms_data = [
                'transaction_date' => $this->transactionUtil->format_date($transaction_data['transaction_date']),
                'ref' => $transaction_data['ref_no'],
                'amount' => $this->transactionUtil->num_f($transaction->final_total),
                'account' => !empty($accountName) ? $accountName->name : '',
                'staff' => auth()->user()->username,
                'expense_category' => !empty($expenseCategory) ? $expenseCategory->name : '',
            ];
            $this->notificationUtil->sendGeneralNotification('expense_created', $sms_data);
        } catch (\Throwable $notify_exception) {
            Log::warning('Expense saved but notification failed. Transaction ID: '
                . $transaction_id . ' Message: ' . $notify_exception->getMessage());
        }

        $output = [

            'success' => 1,

            'msg' => __('expense.expense_add_success')

        ];

        if ($request->is_print == 1) {
            return Redirect::route('expense-print', [$transaction_id]);
        }
    } catch (\Exception $e) {

        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());



        $output = [

            'success' => 0,

            'msg' => __('messages.something_went_wrong')

        ];
    }



    return Redirect::to('expenses')->with('status', $output);
}
    /**
     * Make Print of Save Expense
     *
     *  
     *
     * @param Type $var Description
     * @return type
     * @throws conditon
     **/

    public function storeold(Request $request)

    {
        // dd('123');

        if (!Gate::forUser(auth()->user())->check('expense.create')) {

            abort(403, 'Unauthorized action.');
        }



        try {

            $business_id = $request->session()->get('user.business_id');



            DB::table('temp_data')->where('business_id', $business_id)->update(['add_expense_data' => '']);

            //Check if subscribed or not

            if (!$this->moduleUtil->isSubscribed($business_id)) {

                return $this->moduleUtil->expiredResponse(action('\\Modules\\Finance\\Http\\Controllers\\Expenses\\ExpenseController@index'));
            }



            //Validate document size

            $request->validate([

                'document' => 'file|max:' . (config('constants.document_size_limit') / 1000)

            ]);



            $transaction_data = $request->only(['is_vat', 'ref_no', 'transaction_date', 'location_id', 'final_total', 'expense_for', 'fleet_id', 'additional_notes', 'expense_category_id', 'tax_id', 'contact_id']);
            // S374 visibility guard: every expense saved from Add Expense must be returned by
        // List Expenses immediately after redirect.
        $transaction_data['type'] = 'expense';
        $transaction_data['sub_type'] = $transaction_data['sub_type'] ?? 'expense';
        $transaction_data['status'] = $transaction_data['status'] ?? 'final';
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


            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg'     => "You can't add an expense for an already reviewed date",
                ];

                return Redirect::to('expenses')->with('status', $output);
            }





            $user_id = $request->session()->get('user.id');

            $transaction_data['business_id'] = $business_id;

            $transaction_data['created_by'] = $user_id;

            $transaction_data['type'] = 'expense';

            $transaction_data['status'] = 'final';

            $transaction_data['payment_status'] = 'due';

            $transaction_data['expense_account'] = $request->expense_account;

            $transaction_data['controller_account'] = $request->controller_account;
            // dd($transaction_data['controller_account']);
            $transaction_data['transaction_date'] = $this->transactionUtil->uf_date($transaction_data['transaction_date'], true);

            $transaction_data['final_total'] = $this->transactionUtil->num_uf(

                $transaction_data['final_total']

            );



            $transaction_data['total_before_tax'] = $transaction_data['final_total'];

            if (!empty($transaction_data['tax_id'])) {

                $tax_details = TaxRate::find($transaction_data['tax_id']);

                $transaction_data['total_before_tax'] = $this->transactionUtil->calc_percentage_base($transaction_data['final_total'], $tax_details->amount);

                $transaction_data['tax_amount'] = $transaction_data['final_total'] - $transaction_data['total_before_tax'];
            }



            //Update reference count

            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');

            //Generate reference number

            if (empty($transaction_data['ref_no'])) {

                $transaction_data['ref_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);
            }



            //upload document

            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            if (!empty($document_name)) {

                $transaction_data['document'] = $document_name;
            }



            if ($request->has('is_recurring')) {

                $transaction_data['is_recurring'] = 1;

                $transaction_data['recur_interval'] = !empty($request->input('recur_interval')) ? $request->input('recur_interval') : 1;

                $transaction_data['recur_interval_type'] = $request->input('recur_interval_type');

                $transaction_data['recur_repetitions'] = $request->input('recur_repetitions');

                $transaction_data['subscription_repeat_on'] = $request->input('recur_interval_type') == 'months' && !empty($request->input('subscription_repeat_on')) ? $request->input('subscription_repeat_on') : null;
            }

            // dump($transaction_data,'$transaction_data');



            DB::beginTransaction();

            $transaction = Transaction::create($transaction_data);

            // add VAT components
            $this->transactionUtil->calculateAndUpdateVAT($transaction);


            $transaction_id =  $transaction->id;
            // dd($request->payment[0],'payment 00');

            $tp = null;

            if (!empty($request->payment[0])) {

                $inputs = $this->prepareExpensePdChequePaymentInputs($request->payment[0], $transaction->expense_category_id);


                $inputs['paid_on'] = $transaction->transaction_date;

                $inputs['transaction_id'] = $transaction->id;

                $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? $inputs['cheque_date'] : $transaction->transaction_date;


                $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

                $amount = $inputs['amount'];


                // dd( $amount ,$inputs['method'] , $amount > 0 && $inputs['method'] != 'credit_expense');
                if ($amount > 0 && $inputs['method'] != 'credit_expense') {

                    // Check payment method (Cash, Credit, etc.)
                    if ($inputs['method'] == 'cash') {
                        // If the full amount is paid
                        if ($amount >= $transaction->final_total) {
                            $transaction->payment_status = 'paid'; // Set status to paid
                        } else {
                            $transaction->payment_status = 'partial'; // Set status to partial if it's less than the total
                        }
                    }

                    $transaction->save();
                    // dd($transaction);
                    $inputs['created_by'] = auth()->user()->id;

                    $inputs['payment_for'] = $transaction->contact_id;
                    // $transaction->save();
                    // dd($inputs['method'] , $transaction->payment_status , $transaction ,$transaction->save());
                    $prefix_type = 'expense_payment';
                    if ($transaction->type == 'expense') {
                        $prefix_type = 'expense_payment';
                    }

                    // $transaction->controller_account = !empty($inputs['controller_account']) ? $inputs['controller_account'] : null;




                    $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                    // dd($ref_count);
                    //Generate reference number

                    $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);



                    $inputs['business_id'] = $business_id;

                    $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');


                    // post dated cheque input
                    $inputs = $this->normalizeExpensePaymentPdChequeInputs($inputs);

                    if (!empty($inputs['post_dated_cheque']) || !empty($inputs['update_post_dated_cheque'])) {
                        $expense_category_name = optional(ExpenseCategory::find($transaction->expense_category_id))->name;
                        $bank_account = !empty($inputs['related_account_id']) ? Account::find($inputs['related_account_id']) : null;
                        $bank_name = !empty($bank_account) ? $bank_account->name : '';
                        $inputs['note'] = trim(($expense_category_name ?? '') . "\n" . 'Post dated Cheque Issued from Bank ' . $bank_name);
                        Log::info('Expense PD cheque payment prepared', [
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

                    $inputs['is_return'] =  0; //added by 

                    unset($inputs['transaction_no_1']);

                    unset($inputs['transaction_no_2']);

                    unset($inputs['transaction_no_3']);

                    unset($inputs['controller_account']);


                    $cheque_nos = "";
                    // dd($request->select_cheques);
                    if (!empty($request->select_cheques)) {
                        foreach ($request->select_cheques as $select_cheque) {
                            if (!empty($select_cheque)) {
                                $account_transaction = AccountTransaction::find($select_cheque);

                                $transaction_payment = TransactionPayment::find($account_transaction->transaction_payment_id);
                                // dd($transaction_payment);
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

                    $tp = TransactionPayment::create($inputs);
                    $tp = $this->enforceIssuedPdChequePayment($transaction, $tp);
                    // dd($tp,'tp tp tp');


                    //update payment status

                    $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total, $inputs['method']);
                    // dd('store');
                }
            }

            $this->addAccountTransaction($transaction, $request, $business_id, $tp);

            $newReview = ["created_by" => request()->session()->get('user.id'),  "description" => "Created a new expense: " . $transaction_data['ref_no'], "module" => "expense"];
            $reviewed = $this->transactionUtil->reviewChange($transaction_data['transaction_date'], $newReview);


            $accountName = Account::find($request->expense_account);
            $expense_category = ExpenseCategory::find($request->expense_category_id)->name ?? '';
            $sms_data = array(
                'transaction_date' => $this->transactionUtil->format_date($transaction_data['transaction_date']),
                'ref' => $transaction_data['ref_no'],
                'amount' => $this->transactionUtil->num_f($transaction->final_total),
                'account' => !empty($accountName) ? $accountName->name : "",
                'staff' => auth()->user()->username,
                'expense_category' => $expense_category,
            );
            $this->notificationUtil->sendGeneralNotification('expense_created', $sms_data);



            DB::commit();

            $output = [

                'success' => 1,

                'msg' => __('expense.expense_add_success')

            ];

            if ($request->is_print == 1) {
                return Redirect::route('expense-print', [$transaction_id]);
            }
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

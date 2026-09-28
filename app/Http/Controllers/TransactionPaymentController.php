<?php



namespace App\Http\Controllers;
use Modules\Finance\Services\FinanceRiskService;
use Modules\Finance\Services\FinanceAuditService;
use Modules\Finance\Services\FinanceNotificationService;



use App\Account;

use App\AccountGroup;

use App\Contact;

use App\Events\TransactionPaymentAdded;

use App\Events\TransactionPaymentDeleted;

use App\Events\TransactionPaymentUpdated;

use App\Transaction;

use App\TransactionPayment;

use App\Utils\BusinessUtil;

use App\Utils\ModuleUtil;

use App\Utils\TransactionUtil;

use App\PaymentMethod;

use App\AccountTransaction;

use App\Business;

use App\NotificationTemplate;

use App\AccountType;

use App\BusinessLocation;

use App\ContactLedger;

use App\System;

use App\User;

//use Yajra\DataTables\DataTables;

use Modules\Petro\Entities\PetroDailyShift;
use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\DB;

use Spatie\Activitylog\Models\Activity;


use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

use Modules\Property\Entities\PropertySellLine;

use Modules\Property\Entities\PaymentOption;

use App\Utils\NotificationUtil;

use App\Http\Controllers\ContactController;

use App\ContactLinkedAccount;

use Modules\Vat\Entities\VatCustomerStatement;
use Modules\Vat\Entities\VatCustomerStatementDetail;
use App\Utils\ContactUtil;

use App\Http\Controllers\AccountController;
use App\Utils\Util;
use Intervention\Image\Facades\Image;
use App\Utils\ProductUtil;
use Carbon\Carbon;
use Modules\Finance\Services\FinanceBudgetControlService;

class TransactionPaymentController extends Controller
{
    /** @var \App\Utils\TransactionUtil */

    protected $transactionUtil;

    protected $moduleUtil;

    protected $businessUtil;

    protected $notificationUtil;

    protected $contactUtil;

    protected $commonUtil;
    protected $productUtil;

    /**

     * Constructor

     *

     * @param TransactionUtil $transactionUtil

     * @return void

     */



    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil, BusinessUtil $businessUtil, NotificationUtil $notificationUtil, ContactUtil $contactUtil, Util $commonUtil = null, ProductUtil $productUtil = null)
    {

        $this->transactionUtil = $transactionUtil;

        $this->moduleUtil = $moduleUtil;

        $this->businessUtil = $businessUtil;

        $this->notificationUtil = $notificationUtil;

        $this->contactUtil = $contactUtil;

        $this->commonUtil = $commonUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Resolve each PD cheque control from the active business subscription.
     *
     * Older subscriptions used pd_cheque_module without the current parent and
     * child keys, so keep that key as a backwards-compatible fallback only.
     */
    private function getPdChequePermissions($business_id)
    {
        $subscription = \Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
        $package_details = !empty($subscription) ? (array) $subscription->package_details : [];

        $legacyModuleEnabled = !empty($package_details['pd_cheque_module']);
        $hasExplicitParent = array_key_exists('post_dated_cheque', $package_details);
        $moduleEnabled = $hasExplicitParent
            ? !empty($package_details['post_dated_cheque'])
            : (
                $legacyModuleEnabled
                || !empty($package_details['add_pd_cheque'])
                || !empty($package_details['show_post_dated_cheque'])
                || !empty($package_details['update_post_dated_cheque'])
            );

        return [
            'post_dated_cheque' => $moduleEnabled && (
                !empty($package_details['add_pd_cheque'])
                || !empty($package_details['show_post_dated_cheque'])
                || $legacyModuleEnabled
            ),
            'update_post_dated_cheque' => $moduleEnabled && (
                !empty($package_details['update_post_dated_cheque'])
                || $legacyModuleEnabled
            ),
        ];
    }

    /**
     * Keep contact due/advance payment dropdowns restricted to valid actionable methods.
     */
    private function sanitizeContactPaymentTypes(array $payment_types)
    {
        unset($payment_types['location_id']);
        unset($payment_types['credit_sale']);
        unset($payment_types['credit_purchase']);
        unset($payment_types['credit_expense']);
        unset($payment_types['pd_cheque']);
        unset($payment_types['post_dated_cheque']);

        return $payment_types;
    }


    /**
     * Resolve the active business consistently on central and tenant domains.
     */
    private function resolveContactPaymentBusinessId(Request $request): int
    {
        $businessId = (int) (
            $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional(auth()->user())->business_id
        );

        if ($businessId <= 0) {
            throw new \RuntimeException('Unable to determine the active business for this payment.');
        }

        return $businessId;
    }

    /**
     * Accept either the contacts.id primary key or the displayed contact code,
     * while always enforcing the active business boundary.
     */
    private function resolveContactForPayment($reference, int $businessId): Contact
    {
        return Contact::where('business_id', $businessId)
            ->where(function ($query) use ($reference) {
                if (is_numeric($reference)) {
                    $query->where('id', (int) $reference)
                        ->orWhere('contact_id', (string) $reference);
                } else {
                    $query->where('contact_id', (string) $reference);
                }
            })
            ->firstOrFail();
    }

    /**
     * Some older tenant databases still contain the historical enum value
     * " payment" (with a leading space), while newer databases use "payment".
     * Try both values and finally NULL so supplier due payments remain saveable
     * across tenants that have not yet received the enum-normalisation migration.
     */
    private function createSupplierPaymentLedger(array $data, string $page = 'Pay due Amount')
    {
        $lastException = null;

        foreach (['payment', ' payment', null] as $subType) {
            try {
                $data['sub_type'] = $subType;

                return ContactLedger::createContactLedger($data, $page);
            } catch (\Illuminate\Database\QueryException $exception) {
                $lastException = $exception;
                $message = strtolower($exception->getMessage());
                $driverCode = (int) ($exception->errorInfo[1] ?? 0);

                if (!in_array($driverCode, [1265, 1366, 3819], true)
                    && strpos($message, 'sub_type') === false) {
                    throw $exception;
                }
            }
        }

        throw $lastException ?: new \RuntimeException('Unable to create the supplier payment ledger entry.');
    }

    /**

     * Display a listing of the resource.

     *

     * @return \Illuminate\Http\Response

     */



    public function index()
    {

        //

    }

    /**

     * Show the form for creating a new resource.

     *

     * @return \Illuminate\Http\Response

     */



    public function create()
    {

        //

    }

    /**

     * Store a newly created resource in storage.

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */



    public function store(Request $request)
    {

        try {

            $business_id = $request->session()->get('user.business_id');

            $transaction_id = $request->input('transaction_id');

            $transaction = Transaction::where('business_id', $business_id)->findOrFail($transaction_id);

            $has_reviewed = $this->transactionUtil->hasReviewed($transaction->transaction_date);

            if (!empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($transaction->transaction_date, $transaction->transaction_date);

            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => "You can't make a payment for an already reviewed date",
                ];

                return Redirect::back()->with(['status' => $output]);
            }





            if (
                !Gate::forUser(auth()->user())->check('purchase.delete.payments') && !Gate::forUser(auth()->user())->check('purchase.payments') &&
                !Gate::forUser(auth()->user())->check('purchase.edit.payments') &&
                !Gate::forUser(auth()->user())->check('add.payments') && !Gate::forUser(auth()->user())->check('sell.payments')
            ) {
                abort(403, 'Unauthorized action.');
            }

            if ($transaction->payment_status != 'paid') {

                $inputs = $request->only([

                    'amount',
                    'method',
                    'note',
                    'card_number',
                    'card_holder_name',

                    'card_transaction_number',
                    'card_type',
                    'card_month',
                    'card_year',
                    'card_security',

                    'cheque_number',
                    'bank_account_number',
                    'bank_name',
                    'post_dated_cheque',
                    'update_post_dated_cheque'

                ]);


                $selectedChequeDetails = $this->lockSelectedChequesForPayment($request->input('select_cheques', []), false);
                $hasSelectedCheques = ! empty($request->input('select_cheques')) && $selectedChequeDetails['amount'] > 0;

                if ($hasSelectedCheques) {
                    $inputs['amount'] = $selectedChequeDetails['amount'];
                    $inputs['cheque_number'] = $selectedChequeDetails['cheque_number'];
                    $inputs['bank_name'] = $selectedChequeDetails['bank_name'];
                    $request->merge(['cheque_date' => $selectedChequeDetails['cheque_date']]);
                }

                if ($inputs['method'] == 'cheque' && ! $hasSelectedCheques) {
                    if (empty($inputs['cheque_number']) || empty($inputs['bank_name'])) {
                        $output = [
                            'success' => false,
                            'msg' => 'Bank name and Cheque number are required for Cheque payments'
                        ];
                        return Redirect::back()->with('status', $output);
                    } else {
                        // check duplicates
                        $chequesAdded = $this->transactionUtil->checkCheques($inputs['cheque_number'], $inputs['bank_name']);

                        if ($chequesAdded > 0) {
                            $output = [
                                'success' => false,
                                'msg' => 'Cheque with the same number and bank name already exists!'
                            ];
                            return Redirect::back()->with('status', $output);
                        }
                    }
                }


                $inputs['paid_on'] = $this->resolveSelectedPaymentDate($request, true);

                $inputs['transaction_id'] = $transaction->id;

                $inputs['amount'] = $hasSelectedCheques ? $selectedChequeDetails['amount'] : $this->transactionUtil->num_uf($inputs['amount']);

                // Validate that payment amount is greater than 0
                if ($inputs['amount'] <= 0) {
                    $output = [
                        'success' => false,
                        'msg' => 'Payment amount must be greater than 0'
                    ];
                    return Redirect::back()->with('status', $output);
                }

                $inputs['created_by'] = auth()->user()->id;

                $inputs['payment_for'] = $transaction->contact_id;

                $inputs['cheque_date'] = !empty($request->cheque_date) ? $this->transactionUtil->uf_date($request->cheque_date) : null;

                if ($inputs['method'] == 'custom_pay_1') {

                    $inputs['transaction_no'] = $request->input('transaction_no_1');
                } elseif ($inputs['method'] == 'custom_pay_2') {

                    $inputs['transaction_no'] = $request->input('transaction_no_2');
                } elseif ($inputs['method'] == 'custom_pay_3') {

                    $inputs['transaction_no'] = $request->input('transaction_no_3');
                }

                if (is_numeric($inputs['method'])) {

                    //$inputs['account_id'] = $inputs['method'];

                    $inputs['method'] = 'cash';
                }
                // else {

                //     $inputs['account_id'] = $this->transactionUtil->getDefaultAccountId($inputs['method'], $transaction->location_id);

                // }

                // if ($inputs['method'] == 'bank_transfer' && !empty($request->input('account_id'))) {

                //     $inputs['account_id'] = $request->input('account_id');

                // }

                // if ($inputs['method'] == 'card' && !empty($request->input('account_id'))) {

                //     $inputs['account_id'] = $request->input('account_id');

                // }

                $inputs['account_id'] = $request->input('account_id');

                $prefix_type = 'purchase_payment';

                if (in_array($transaction->type, ['sell', 'sell_return', 'fpos_sale', 'tpos_sale'], true)) {

                    $prefix_type = 'sell_payment';
                } elseif ($transaction->type == 'expense') {

                    $prefix_type = 'expense_payment';
                }

                DB::beginTransaction();

                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);

                //Generate reference number

                $inputs['payment_ref_no'] = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                $payment_ref_no = $inputs['payment_ref_no'];

                $inputs['reference_no'] = $request->refNo;

                $inputs['business_id'] = $request->session()->get('business.id');

                $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

                $inputs['is_return'] = !empty($request->is_return) ? $request->is_return : 0; //added by ahmed

                if (in_array($transaction->type, ['sell', 'fpos_sale', 'tpos_sale'], true)) {

                    $inputs['paid_in_type'] = 'all_sale_page';
                    
                    // Add POS identification to payment note
                    $pos_note = 'POS Payment';
                    if (!empty($inputs['note'])) {
                        $inputs['note'] = $pos_note . ' - ' . $inputs['note'];
                    } else {
                        $inputs['note'] = $pos_note;
                    }
                }

                $account_id = $inputs['account_id'];
                $post_dated = $this->transactionUtil->account_exist_return_id('Post Dated Cheques');
                $issued_post_dated = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');
                $usePdChequeAccount = !empty($inputs['update_post_dated_cheque']) || !empty($inputs['post_dated_cheque']);

                if ($usePdChequeAccount) {
                    $inputs['related_account_id'] = $account_id;

                    if ($transaction->type == 'purchase' || $transaction->type == 'expense') {
                        $inputs['account_id'] = $issued_post_dated;
                    } else {
                        $inputs['account_id'] = $post_dated;
                    }
                }

                if ($hasSelectedCheques) {
                    $this->lockSelectedChequesForPayment($request->input('select_cheques', []));
                }

                $tp = TransactionPayment::create($inputs);
                
                /*
|--------------------------------------------------------------------------
| Finance Duplicate Payment Detection
|--------------------------------------------------------------------------
*/

FinanceRiskService::detectDuplicatePayment(
    $business_id,
    $transaction->id,
    $inputs['amount'] ?? 0,
    $inputs['payment_ref_no'] ?? null,
    'Transaction Payments',
    'transaction_payments',
    $tp->id ?? null,
    $transaction->location_id ?? null
);


                //update payment status

                $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total);


                $account_receivable_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');

                $account_payable_id = $this->transactionUtil->account_exist_return_id('Accounts Payable');



                if (in_array($transaction->type, ['sell', 'fpos_sale', 'tpos_sale'], true)) {

                    $account_transaction_data = [

                        'amount' => $inputs['amount'],

                        'account_id' => $account_id,

                        'type' => 'debit',

                        'operation_date' => $inputs['paid_on'],

                        'created_by' => Auth::user()->id,

                        'transaction_id' => !empty($transaction) ? $transaction->id : null,

                        'transaction_payment_id' => $tp->id,

                        'post_dated_cheque' => $inputs['post_dated_cheque'] ?? 0,
                        'update_post_dated_cheque' => $inputs['update_post_dated_cheque'] ?? 0,

                    ];

                    if ($usePdChequeAccount) {
                        $account_transaction_data['related_account_id'] = $account_id;
                        $account_transaction_data['account_id'] = $post_dated;
                    }


                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_transaction_data['account_id'] = $account_receivable_id;

                    $account_transaction_data['type'] = 'credit';

                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_transaction_data['contact_id'] = $transaction->contact_id;

                    $account_transaction_data['sub_type'] = 'payment';

                    ContactLedger::createContactLedger($account_transaction_data, 'Pay due Amount');

                    $transaction->contact = Contact::where('id', $transaction->contact_id)->first();
                    $transaction->payment_ref_number = $payment_ref_no;
                    $this->notificationUtil->autoSendNotification($business_id, 'payment_received', $transaction, $transaction->contact);
                } else if ($transaction->type == 'purchase') {

                    $account_transaction_data = [

                        'amount' => $inputs['amount'],

                        'account_id' => $account_id,

                        'type' => 'credit',

                        'operation_date' => $inputs['paid_on'],

                        'created_by' => Auth::user()->id,

                        'transaction_id' => !empty($transaction) ? $transaction->id : null,

                        'transaction_payment_id' => $tp->id,

                        'post_dated_cheque' => $inputs['post_dated_cheque'] ?? 0,
                        'update_post_dated_cheque' => $inputs['update_post_dated_cheque'] ?? 0,

                    ];

                    if ($usePdChequeAccount) {
                        $account_transaction_data['related_account_id'] = $account_id;
                        $account_transaction_data['account_id'] = $issued_post_dated;
                    }


                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_transaction_data['account_id'] = $account_payable_id;

                    $account_transaction_data['type'] = 'debit';

                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_transaction_data['contact_id'] = $transaction->contact_id;

                    $account_transaction_data['sub_type'] = 'payment';

                    ContactLedger::createContactLedger($account_transaction_data, 'Pay due Amount');
                } else {

                    $inputs['transaction_type'] = $transaction->type;
                    if ($usePdChequeAccount && ($transaction->type == 'purchase' || $transaction->type == 'expense')) {
                        $inputs['account_id'] = $issued_post_dated;
                    } elseif ($usePdChequeAccount && in_array($transaction->type, ['sell', 'fpos_sale', 'tpos_sale'], true)) {
                        $inputs['account_id'] = $post_dated;
                    }
                    event(new TransactionPaymentAdded($tp, $inputs));
                }

                // auto transfer update_post_dated_cheque
                $msg = "";
                if (!empty($inputs['update_post_dated_cheque'])) {
                    $autoFundTransfer = $this->autoFundTransfer($request);
                    if ($autoFundTransfer['success']) {
                        $msg = " And Auto Transferred";
                    } else {
                        $msg = " And Auto Transfer Failed";
                        $autoFundTransfer['msg'] = $autoFundTransfer['msg'] . $msg;
                        return Redirect::back()->with(['status' => $autoFundTransfer]);
                    }
                }



                /*
                |--------------------------------------------------------------------------
                | Finance Module Budget Control Integration
                |--------------------------------------------------------------------------
                |
                | Supplier purchase payments and expense payments are operational entries.
                | The Finance Module only monitors them for budget governance.
                |
                */
                if (in_array($transaction->type, ['purchase', 'expense'], true)) {
                    FinanceBudgetControlService::checkBudgetUsage(
                        $business_id,
                        $transaction->location_id ?? null,
                        $inputs['account_id'] ?? null,
                        $inputs['amount'] ?? 0,
                        'transaction_payments',
                        $tp->id ?? null
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Customer Receipt Finance Visibility
                |--------------------------------------------------------------------------
                |
                | Customer receipts are cash inflows. They should be visible in Finance
                | audit and notifications, but they should NOT reduce budgets.
                |
                */
                if (in_array($transaction->type, ['sell', 'fpos_sale', 'tpos_sale'], true)) {
                    FinanceAuditService::log(
                        'Customer Receipt',
                        'Created',
                        'Customer payment received',
                        'transaction_payments',
                        $tp->id,
                        null,
                        $tp->toArray(),
                        $transaction->location_id ?? null
                    );

                    FinanceNotificationService::create(
                        'Customer Receipt',
                        'Customer Payment Received',
                        'Customer payment recorded successfully.',
                        null,
                        'medium',
                        'transaction_payments',
                        $tp->id,
                        $transaction->location_id ?? null
                    );
                }

                DB::commit();
            }

            $output = [

                'success' => true,

                'msg' => __('purchase.payment_added_success') . $msg

            ];
        } catch (\Exception $e) {

            DB::rollBack();

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];
        }

        return Redirect::back()->with(['status' => $output]);
    }

    private function lockSelectedChequesForPayment(array $selectedChequeIds = [], bool $lockCheques = true): array
    {
        $chequeNumbers = [];
        $bankNames = [];
        $chequeDates = [];
        $totalAmount = 0;

        foreach ($selectedChequeIds as $select_cheque) {
            if (empty($select_cheque)) {
                continue;
            }

            $account_transaction = AccountTransaction::find($select_cheque);
            if (empty($account_transaction)) {
                continue;
            }

            $totalAmount += (float) $account_transaction->amount;

            $transaction_payment = ! empty($account_transaction->transaction_payment_id)
                ? TransactionPayment::find($account_transaction->transaction_payment_id)
                : null;

            if (! empty($transaction_payment)) {
                if ($lockCheques) {
                    $transaction_payment->is_deposited = 1;
                    $transaction_payment->save();
                }
                if (! empty($transaction_payment->cheque_number)) {
                    $chequeNumbers[] = trim($transaction_payment->cheque_number);
                }
                if (! empty($transaction_payment->bank_name)) {
                    $bankNames[] = trim($transaction_payment->bank_name);
                }
                if (! empty($transaction_payment->cheque_date)) {
                    $chequeDates[] = $transaction_payment->cheque_date;
                }
            }

            if (empty($transaction_payment) || empty($transaction_payment->cheque_number)) {
                if (! empty($account_transaction->cheque_number)) {
                    $chequeNumbers[] = trim($account_transaction->cheque_number);
                }
            }
            if (empty($transaction_payment) || empty($transaction_payment->bank_name)) {
                if (! empty($account_transaction->bank_name)) {
                    $bankNames[] = trim($account_transaction->bank_name);
                }
            }
            if (empty($transaction_payment) || empty($transaction_payment->cheque_date)) {
                if (! empty($account_transaction->cheque_date)) {
                    $chequeDates[] = $account_transaction->cheque_date;
                }
            }
        }

        return [
            'amount' => $totalAmount,
            'cheque_number' => implode(',', array_values(array_unique(array_filter($chequeNumbers)))),
            'bank_name' => implode(',', array_values(array_unique(array_filter($bankNames)))),
            'cheque_date' => collect($chequeDates)->filter()->sort()->first(),
        ];
    }

    /**

     * Display the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */

    public function print($id)
    {

        // Allow expense users to view/print the expense voucher from the Expenses list.
        // Previously this was restricted to purchase/sell permissions, which can render a blank/unauthorized view.
        if (
            !Gate::forUser(auth()->user())->check('purchase.create')
            && !Gate::forUser(auth()->user())->check('sell.create')
            && !Gate::forUser(auth()->user())->check('expense.access')
            && !Gate::forUser(auth()->user())->check('expense.create')
        ) {

            abort(403, 'Unauthorized action.');
        }

        $transaction = Transaction::leftJoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
            ->where('transactions.id', $id)
            ->withTrashed()
            ->with(['contact', 'business', 'transaction_for'])
            ->first();

        $payments_query = TransactionPayment::where('transaction_id', $id);
        $accounts_enabled = false;
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts_enabled = true;
            $payments_query->with(['payment_account']);
        }

        $payments = $payments_query->orderByDesc('id')->get();
        $ref_nos = TransactionPayment::where('transaction_id', $id)
            ->whereNotNull('payment_ref_no')
            ->distinct('payment_ref_no')
            ->pluck('payment_ref_no', 'payment_ref_no');
        $payment_types = $this->transactionUtil->payment_types();
        $on_account_ofs = PaymentOption::where('business_id', $transaction->business_id)->pluck('payment_option', 'id');
        $users = User::where('business_id', $transaction->business_id)->pluck('username', 'id');

        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();
        $business_locations = BusinessLocation::where('business_id', $business_id)->first();
        $arranged_by_user = User::where('id', $transaction->created_by)->first();
        $show_payment_note_in_print = (int) System::getProperty('expense_show_payment_note_in_print_' . $business_id) === 1;
        $show_expense_note_in_print = (int) System::getProperty('expense_show_expense_note_in_print_' . $business_id) === 1;

        return view('transaction_payment.print_payments')->with(compact(
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
            'arranged_by_user',
            'show_payment_note_in_print',
            'show_expense_note_in_print'
        ));
    }



    public function show($id)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create') && !Gate::forUser(auth()->user())->check('sell.create')) {

            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $transaction = Transaction::where('id', $id)
                ->withTrashed()
                ->with(['contact', 'business', 'transaction_for'])

                ->first();

            $transaction_type = $transaction->type;

            $payments_query = TransactionPayment::where('transaction_id', $id);

            $accounts_enabled = false;

            if ($this->moduleUtil->isModuleEnabled('account')) {

                $accounts_enabled = true;

                $payments_query->with(['payment_account']);
            }

            $payments = $payments_query->get();

            $ref_nos = TransactionPayment::where('transaction_id', $id)->whereNotNull('payment_ref_no')->distinct('payment_ref_no')->pluck('payment_ref_no', 'payment_ref_no');

            $payment_types = $this->transactionUtil->payment_types();

            $on_account_ofs = PaymentOption::where('business_id', $transaction->business_id)->pluck('payment_option', 'id');

            $users = User::where('business_id', $transaction->business_id)->pluck('username', 'id');

            $show_shortage = false;
            if (request()->show_shortage == 1) {
                $transaction_shortages_count = Transaction::leftJoin('pump_operators', 'transactions.pump_operator_id', 'pump_operators.id')
                    ->select('transactions.id', 'transactions.final_total', 'transactions.created_at', 'pump_operators.name')
                    ->where('transactions.invoice_no', $transaction->invoice_no)
                    ->where('transactions.sub_type', "shortage")
                    ->where('transactions.deleted_at', NULL)
                    ->count();
                $show_shortage = $transaction_shortages_count > 0;
            }

            return view('transaction_payment.show_payments')

                ->with(compact(

                    'transaction',

                    'payments',

                    'payment_types',

                    'ref_nos',

                    'id',

                    'accounts_enabled',

                    'users',

                    'on_account_ofs',
                    'show_shortage'

                ));
        }
    }

    public function show_credit_sales(Request $request)
    {
        if (!Gate::forUser(auth()->user())->check('purchase.create') && !Gate::forUser(auth()->user())->check('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $credit_transaction_ids = explode(',', $request->credit_transaction_ids);
            $transactions = Transaction::whereIn('transactions.id', $credit_transaction_ids)
                ->leftJoin('pump_operators', 'transactions.pump_operator_id', 'pump_operators.id')
                ->where('transactions.deleted_at', NULL)
                ->select('transactions.*', 'pump_operators.name')
                ->with(['contact', 'business', 'transaction_for'])
                ->get();

            $all_transactions = collect($transactions); // Use a collection to hold the transactions

            foreach ($transactions as $transaction) {
                // Fetch related shortages for the current transaction
                $transaction_shortages = Transaction::leftJoin('pump_operators', 'transactions.pump_operator_id', 'pump_operators.id')
                    ->where('transactions.invoice_no', $transaction->invoice_no)
                    ->where('transactions.sub_type', "shortage")
                    ->where('transactions.deleted_at', NULL)
                    ->select('transactions.*', 'pump_operators.name')
                    ->with(['contact', 'business', 'transaction_for'])
                    ->get();
                $all_transactions = $all_transactions->merge($transaction_shortages);
            }

            // Reset the keys to ensure consistent indexing
            $transactions = $all_transactions->unique('id')->values();

            return view('transaction_payment.show_credit_sales')
                ->with(compact(
                    'transactions',
                    'credit_transaction_ids',
                ));
        }
    }

    public function showdetails($id)
    {
        // Keep legacy route compatible, but render the working print/view voucher template.
        return $this->print($id);
    }

    public function getPaymentViewDatatable($id)
    {
        $trans = DB::table('transactions')->select('created_at', 'ref_no', 'final_total')->where('id', $id)->get();
        $single_payment = Datatables::of($trans)

            ->addColumn('paid_on', function ($trans) {
                return !empty($trans->created_at) ? $trans->created_at : '';
            })
            ->addColumn('payment_ref_no', function ($trans) {
                return !empty($trans->ref_no) ? $trans->ref_no : '';
            })

            ->addColumn('amount', function ($trans) {
                return !empty($trans->final_total) ? $trans->final_total : '';
            })

            ->addColumn('note', function ($trans) {
                return '';
            });
        return $single_payment->rawColumns(['paid_on', 'payment_ref_no', 'amount', 'note'])
            ->make(true);
    }

    public function getPaymentDatatable($id)
    {

        $transaction = Transaction::where('id', $id)
            ->withTrashed()
            ->with(['contact', 'business', 'transaction_for'])

            ->first();

        $payments_query = TransactionPayment::leftjoin('transactions', 'transaction_payments.transaction_id', 'transactions.id')
            ->leftjoin('accounts', 'accounts.id', 'transaction_payments.account_id')
            ->leftjoin('users', 'transaction_payments.created_by', 'users.id')
            ->leftjoin('expense_categories', 'transactions.expense_category_id', 'expense_categories.id')
            ->leftjoin('expense_categories_codes', 'transaction_payments.business_id', 'expense_categories_codes.business_id')
            ->leftjoin('payment_options', 'transaction_payments.payment_option_id', 'payment_options.id')
            ->select('transaction_payments.*', 'transactions.type', 'transactions.additional_notes as expense_note', 'payment_options.payment_option', 'users.username', 'expense_categories_codes.prefix', 'expense_categories_codes.starting_no', 'expense_categories.name', 'accounts.name as account_name')
            ->where('transaction_payments.transaction_id', $id)
            ->where('transaction_payments.deleted_at', NULL);


        if (!empty(request()->start_date) && !empty(request()->end_date)) {

            $payments_query->whereDate('paid_on', '>=', request()->start_date);

            $payments_query->whereDate('paid_on', '<=', request()->end_date);
        }

        if (!empty(request()->method)) {

            $payments_query->where('method', request()->method);
        }

        if (!empty(request()->ref_no)) {

            $payments_query->where('transaction_payments.payment_ref_no', request()->receipt_no);
        }

        if (!empty(request()->payment_option)) {

            $payments_query->where('payment_option_id', request()->payment_option);
        }

        if (!empty(request()->user_id)) {

            $payments_query->where('created_by', request()->user_id);
        }

        if (!empty(request()->user_id)) {

            $payments_query->where('created_by', request()->user_id);
        }

        $payments_query->get();


        $single_payment = Datatables::of($payments_query)

            ->addColumn(

                'action',

                function ($row) use ($transaction) {

                    $html = '';
                    if (empty($transaction->deleted_by)) {
                        if (Gate::forUser(auth()->user())->check("purchase.edit.payments")) {

                            $html .= '<button type="button" class="btn btn-info btn-xs edit_payment"

                            data-href="' . action('TransactionPaymentController@edit', [$row->id]) . '"><i

                                class="glyphicon glyphicon-edit"></i></button>';
                        }
                        if (Gate::forUser(auth()->user())->check("purchase.delete.payments")) {

                            $html .= '&nbsp; <button type="button" class="btn btn-danger btn-xs delete_payment"

                            data-href="' . action('TransactionPaymentController@destroy', [$row->id]) . '"><i

                                class="fa fa-trash" aria-hidden="true"></i></button>';
                        }

                        $html .= '&nbsp; <button type="button" class="btn btn-primary btn-xs view_payment" 
                        data-href="' . action([\App\Http\Controllers\TransactionPaymentController::class, 'viewPayment'], [$row->id]) . '">
                        <i class="fa fa-eye" aria-hidden="true"></i>
                    </button>';





                        if (!empty($row->document_path)) {

                            $html .= '&nbsp';

                            $html .= '<a href="' . $row->document_path . '" class="btn btn-success btn-xs"

                    download="' . $row->document_name . '"><i class="fa fa-download"

                        data-toggle="tooltip" title="' . __("purchase.download_document") . '"></i></a>';

                            if (isFileImage($row->document_name)) {

                                $html .= '&nbsp';

                                $html .= '<button data-href="' . $row->document_path . '"

                    class="btn btn-info btn-xs view_uploaded_document" data-toggle="tooltip"

                    title="' . __("lang_v1.view_document") . '"><i class="fa fa-picture-o"></i></button> ';
                            }
                        }
                    }


                    return $html;
                }

            )


            ->addColumn('payment_ref_no', function ($payments_query) {
                return !empty($payments_query->payment_ref_no) ? $payments_query->payment_ref_no : '';
            })

            ->addColumn('amount', function ($payments_query) {
                return !empty($payments_query->amount) ? $this->transactionUtil->num_f($payments_query->amount) : '';
            })

            ->addColumn('paid_on', function ($payments_query) {
                return !empty($payments_query->paid_on) ? date('Y-m-d', strtotime($payments_query->paid_on)) : '';
            })

            ->addColumn('on_account_of', function ($payments_query) {
                return !empty($payments_query->payment_option) ? $payments_query->payment_option : '';
            })

            ->addColumn('method', function ($row) {
                $html = "";
                if (strtolower($row->method) == 'bank_transfer' || strtolower($row->method) == 'direct_bank_deposit' || strtolower($row->method) == 'bank') {
                    $html .= ucfirst(str_replace("_", " ", $row->method));

                    $bank_acccount = Account::find($row->account_id);
                    if (!empty($bank_acccount)) {
                        $html .= '<br><b>Bank Name:</b> ' . $bank_acccount->name;
                    }
                    if (!empty($row->cheque_number)) {
                        $html .= '<br><b>Cheque Number:</b> ' . $row->cheque_number;
                    }
                    if (!empty($row->cheque_date)) {
                        $html .= '<br><b>Cheque Date:</b> ' . $this->transactionUtil->format_date($row->cheque_date);
                    }
                } else {
                    $html .= ucfirst(str_replace("_", " ", $row->method));
                }

                return $html;
            })

            ->addColumn('account_name', function ($payments_query) {
                return !empty($payments_query->account_name) ? $payments_query->account_name : '';
            })

            ->addColumn('note', function ($payments_query) {
                return '';
            })
            ->addColumn('username', function ($payments_query) {
                return !empty($payments_query->username) ? $payments_query->username : '';
            })
            ->addColumn('designation', function ($payments_query) {
                return !empty($payments_query->designation) ? $payments_query->designation : '';
            });


        return $single_payment->rawColumns(['action', 'paid_on', 'payment_ref_no', 'amount', 'method', 'on_account_of', 'note'])
            ->make(true);
    }

    public function getTransactionShortagesDataTable($id)
    {
        $transaction = Transaction::where('id', $id)
            ->select('invoice_no')
            ->first();

        $transaction_shortages = Transaction::leftJoin('pump_operators', 'transactions.pump_operator_id', 'pump_operators.id')
            ->select('transactions.id', 'transactions.final_total', 'transactions.created_at', 'pump_operators.name')
            ->where('transactions.invoice_no', $transaction->invoice_no)
            ->where('transactions.sub_type', "shortage")
            ->where('transactions.deleted_at', NULL);

        $transaction_shortages->get();
        // \Log::debug("getTransactionShortagesDataTable",["transaction_shortages" => $transaction_shortages->get()]);

        $single_payment = Datatables::of($transaction_shortages)
            ->addColumn('final_total', function ($row) {
                return !empty($row->final_total) ? $this->transactionUtil->num_f($row->final_total) : '';
            })
            ->addColumn('created_at', function ($row) {
                return !empty($row->created_at) ? date('Y-m-d', strtotime($row->created_at)) : '';
            });


        return $single_payment->rawColumns(['final_total', 'created_at'])->make(true);
    }

    /**

     * Display the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */



    public function pendingPayment($id)
    {

        if (request()->ajax()) {

            $transaction = Transaction::where('id', $id)

                ->with(['contact', 'business', 'transaction_for'])

                ->first();

            $payments_query = TransactionPayment::where('transaction_id', $id);

            $accounts_enabled = false;

            if ($this->moduleUtil->isModuleEnabled('account')) {

                $accounts_enabled = true;

                $payments_query->with(['payment_account']);
            }

            $payments = $payments_query->get();

            $payment_types = $this->transactionUtil->payment_types();

            return view('transaction_payment.pending_payments')

                ->with(compact('transaction', 'payments', 'payment_types', 'accounts_enabled', 'id'));
        }
    }

    /**

     * Display the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */



    public function pendingPaymentConfirm($transaction_id)
    {

        $business_id = request()->session()->get('user.business_id');

        $transaction = Transaction::where('business_id', $business_id)->findOrFail($transaction_id);

        $location = BusinessLocation::find($transaction->location_id);

        $default_payment_accounts = !empty($location->default_payment_accounts) ? json_decode($location->default_payment_accounts, true) : [];

        try {

            DB::beginTransaction();

            //update payment status

            $this->transactionUtil->updatePaymentStatus($transaction_id, $transaction->final_total);

            $tp = TransactionPayment::where('transaction_id', $transaction_id)->first();

            $inputs['transaction_type'] = $transaction->type;

            $inputs['amount'] = $transaction->final_total;

            $inputs['method'] = 'bank_transfer';

            $inputs['account_id'] = $default_payment_accounts['bank_transfer']['account'];

            event(new TransactionPaymentAdded($tp, $inputs));

            DB::commit();

            $output = [

                'success' => true,

                'msg' => __('lang_v1.success')

            ];
        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];
        }

        return Redirect::back()->with('status', $output);
    }

    /**

     * Show the form for editing the specified resource.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */



    public function edit($id, Request $request)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create') && !Gate::forUser(auth()->user())->check('sell.create')) {

            abort(403, 'Unauthorized action.');
        }

        $is_transaction = filter_var($request->query('is_transaction', false), FILTER_VALIDATE_BOOLEAN);

        if (! request()->ajax()) {
            return Redirect::back();
        }

            $business_id = request()->session()->get('user.business_id');

            //if has the parent transaction
            $payment_line = null;

            if (!$is_transaction) {
                $payment_line = TransactionPayment::findOrFail($id);
                
                // Check if this is a cheque payment that has been returned
                if (($payment_line->method == 'cheque' || $payment_line->method == 'bank_transfer') && !empty($payment_line->cheque_number)) {
                    $cheque_return = Transaction::where('type', 'cheque_return')
                        ->where('contact_id', $payment_line->payment_for)
                        ->whereExists(function($query) use ($payment_line) {
                            $query->select(DB::raw(1))
                                ->from('account_transactions')
                                ->whereColumn('account_transactions.transaction_id', 'transactions.id')
                                ->where('account_transactions.cheque_number', $payment_line->cheque_number)
                                ->whereNull('account_transactions.deleted_at');
                        })
                        ->first();
                    
                    if (!empty($cheque_return)) {
                        return response()->json([
                            'success' => false,
                            'msg' => __('lang_v1.cannot_edit_delete_returned_cheque'),
                        ], 422);
                    }
                }
                
                if (!empty($payment_line->parent_id)) {

                    $parent_payment = TransactionPayment::where('id', $payment_line->parent_id)->first();

                    if (!empty($parent_payment)) {

                        $payment_line->amount = $parent_payment->amount;
                    }
                }

                $transaction = Transaction::where('id', $payment_line->transaction_id)

                    ->where('business_id', $business_id)

                    ->with(['contact', 'location'])

                    ->first();
            } else {
                $transaction = Transaction::where('id', $id)

                    ->where('business_id', $business_id)

                    ->with(['contact', 'location'])

                    ->first();
            }



            if ($transaction->type == 'expense') {

                $payment_types = $this->transactionUtil->payment_types($transaction->location, true, false, true, false, true);
            } else if ($transaction->type == 'purchase') {

                $payment_types = $this->transactionUtil->payment_types($transaction->location, true, true, false, false, true);
            } else if ($transaction->type == 'property_purchase') {

                $payment_types = $this->transactionUtil->payment_types($transaction->location, true, true);
            } else if (in_array($transaction->type, ['sell', 'fpos_sale', 'tpos_sale', 'property_sell'], true)) {

                $payment_types = $this->transactionUtil->payment_types($transaction->location, true, false, false, true);
            } else {

                $payment_types = $this->transactionUtil->payment_types($transaction->location);
            }

            //Accounts

            $accounts = $this->moduleUtil->accountsDropdown($business_id, true);
            $expense_accounts = [];

            $expense_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();

            // $this->moduleUtil->isModuleEnabled('account')
            if ($transaction->type == 'expense') {

                if (!empty($expense_account_type_id)) {

                    $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->pluck('name', 'id');
                }
            }

            $selectedAccount = null;
            if ($payment_line) {
                $selectedAccount = Account::find($payment_line->account_id);
            }

            // Get customers dropdown for customer payments
            $customers = [];
            if (in_array($transaction->type, ['sell', 'fpos_sale', 'tpos_sale', 'opening_balance'], true)) {
                $customers = Contact::customersDropdown($business_id, false);
            }

            return view('transaction_payment.edit_payment_row')

                ->with(compact('transaction', 'payment_types', 'payment_line', 'accounts', 'expense_accounts', 'selectedAccount', 'is_transaction', 'customers'));
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
        if (
            !Gate::forUser(auth()->user())->check('purchase.delete.payments') && !Gate::forUser(auth()->user())->check('purchase.payments') &&
            !Gate::forUser(auth()->user())->check('purchase.edit.payments') &&
            !Gate::forUser(auth()->user())->check('add.payments') && !Gate::forUser(auth()->user())->check('sell.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $payment = TransactionPayment::findOrFail($id);
            $transaction = Transaction::findOrFail($payment->transaction_id);

            // Check if this is a cheque payment that has been returned
            if (($payment->method == 'cheque' || $payment->method == 'bank_transfer') && !empty($payment->cheque_number)) {
                $cheque_return = Transaction::where('type', 'cheque_return')
                    ->where('contact_id', $payment->payment_for)
                    ->whereExists(function($query) use ($payment) {
                        $query->select(DB::raw(1))
                            ->from('account_transactions')
                            ->whereColumn('account_transactions.transaction_id', 'transactions.id')
                            ->where('account_transactions.cheque_number', $payment->cheque_number)
                            ->whereNull('account_transactions.deleted_at');
                    })
                    ->first();
                
                if (!empty($cheque_return)) {
                    $output = [
                        'success' => false,
                        'msg' => __('lang_v1.cannot_edit_delete_returned_cheque'),
                    ];
                    return Redirect::back()->with(['status' => $output]);
                }
            }

            $old_contact_id = $transaction->contact_id;
            $new_contact_id = $request->input('contact_id', $old_contact_id);
            $customer_changed = ($old_contact_id != $new_contact_id);

            $inputs = $request->only([
                'amount',
                'method',
                'note',
                'card_number',
                'card_holder_name',
                'card_transaction_number',
                'card_type',
                'card_month',
                'card_year',
                'card_security',
                'cheque_number',
                'bank_account_number'
            ]);
            $inputs['paid_on'] = $this->resolveSelectedPaymentDate($request, false);
            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
            $inputs['account_id'] = $request->input('account_id');

            if ($inputs['method'] == 'custom_pay_1') {
                $inputs['transaction_no'] = $request->input('transaction_no_1');
            } elseif ($inputs['method'] == 'custom_pay_2') {
                $inputs['transaction_no'] = $request->input('transaction_no_2');
            } elseif ($inputs['method'] == 'custom_pay_3') {
                $inputs['transaction_no'] = $request->input('transaction_no_3');
            }

            if ($transaction->type == 'hms_booking') {
                $accountTransaction = AccountTransaction::where('transaction_id', $transaction->id)->where('type', 'debit')->first();
                $accountTransaction->account_id = $request->input('account_id');
                $accountTransaction->save();
            }

            if (is_numeric($inputs['method'])) {
                $inputs['method'] = 'cash';
            }

            $oldAcc = Account::find($payment->account_id);
            $newAcc = Account::find($inputs['account_id']);
            $old = [
                'date' => $this->transactionUtil->format_date($payment->paid_on),
                'amount' => $this->transactionUtil->num_f($payment->amount),
                'payment_method' => ucfirst(str_replace('_', ' ', $payment->method)),
                'account' => !empty($oldAcc) ? $oldAcc->name : '',
                'customer' => !empty($old_contact_id) ? Contact::find($old_contact_id)->name : '',
            ];
            $new = [
                'date' => $this->transactionUtil->format_date($inputs['paid_on']),
                'amount' => $this->transactionUtil->num_f($inputs['amount']),
                'payment_method' => ucfirst(str_replace('_', ' ', $inputs['method'])),
                'account' => !empty($newAcc) ? $newAcc->name : "",
                'customer' => !empty($new_contact_id) ? Contact::find($new_contact_id)->name : '',
            ];
            $attributes = ['attributes' => $new, 'old' => $old];

            DB::beginTransaction();

            // Handle customer change if applicable
            if ($customer_changed && in_array($transaction->type, ['sell', 'fpos_sale', 'opening_balance', 'property_sell'])) {
                $old_contact = Contact::find($old_contact_id);
                $new_contact = Contact::find($new_contact_id);

                // Update transaction contact
                $transaction->contact_id = $new_contact_id;
                $transaction->save();

                // Build list of related payments (parent/children) to keep ledgers/accounts in sync
                $related_payment_ids = collect([$payment->id]);

                if (!empty($payment->parent_id)) {
                    $related_payment_ids->push($payment->parent_id);
                    $related_payment_ids = $related_payment_ids->merge(
                        TransactionPayment::where('parent_id', $payment->parent_id)->pluck('id')
                    );
                } else {
                    $related_payment_ids = $related_payment_ids->merge(
                        TransactionPayment::where('parent_id', $payment->id)->pluck('id')
                    );
                }

                $related_payment_ids = $related_payment_ids->filter()->unique()->values();

                if ($related_payment_ids->isNotEmpty()) {
                    // Move existing ledger entries to the new contact
                    ContactLedger::whereIn('transaction_payment_id', $related_payment_ids)
                        ->update(['contact_id' => $new_contact_id]);

                    // Ensure all related payments reference the new contact
                    TransactionPayment::whereIn('id', $related_payment_ids)
                        ->update(['payment_for' => $new_contact_id]);
                }

                // Update transaction-level ledger records to reflect the new customer
                ContactLedger::where('transaction_id', $transaction->id)
                    ->update(['contact_id' => $new_contact_id]);

                // Ensure the current payment persists the new contact id when updating later in the method
                $inputs['payment_for'] = $new_contact_id;

                $old_name = $old_contact->name ?? 'Unknown';
                $new_name = $new_contact->name ?? 'Unknown';

                // Log customer change in activity log using the requested phrasing
                $change_log = "Changed customer {$old_name} (old customer name) and to Customer {$new_name} (new customer name), by the User " . auth()->user()->username;

                Activity::create([
                    'log_name' => "Customer Payment - Customer Change",
                    'description' => "customer_changed",
                    'subject_id' => $payment->id,
                    'subject_type' => "App\\TransactionPayment",
                    'causer_id' => auth()->user()->id,
                    'causer_type' => 'App\\User',
                    'properties' => [
                        'message' => $change_log,
                        'old_customer_name' => $old_name,
                        'new_customer_name' => $new_name,
                        'old_customer_id' => $old_contact_id,
                        'new_customer_id' => $new_contact_id,
                        'changed_by' => auth()->user()->username,
                    ],
                    'created_at' => date('Y-m-d H:i'),
                    'updated_at' => date('Y-m-d H:i'),
                ]);
            }

            $accounts_receivable_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');

            // Step 1: Update the payment itself (parent or child)
            $payment->update($inputs);

            // Step 2: Update related account transactions for this payment
            $accountTransactions = AccountTransaction::where('transaction_payment_id', $payment->id)->get();
            foreach ($accountTransactions as $accountTransaction) {
                if ($accounts_receivable_id == $accountTransaction->account_id) {
                    $accountTransaction->update([
                        'amount' => $inputs['amount'],
                        'operation_date' => $inputs['paid_on']
                    ]);
                } else {
                    $accountTransaction->update([
                        'amount' => $inputs['amount'],
                        'operation_date' => $inputs['paid_on'],
                        'account_id' => $inputs['account_id']
                    ]);
                }
            }
            ContactLedger::where('transaction_payment_id', $payment->id)
                ->update(['amount' => $inputs['amount']]);

            // Step 3: If it's a child payment, update the parent's amount
            if (!empty($payment->parent_id)) {
                $parent_payment = TransactionPayment::find($payment->parent_id);
                $total_child_amount = TransactionPayment::where('parent_id', $parent_payment->id)->sum('amount');
                $parent_payment->amount = $total_child_amount;
                $parent_payment->save();

                // Update parent's account transactions
                $parentAccountTransactions = AccountTransaction::where('transaction_payment_id', $parent_payment->id)->get();
                foreach ($parentAccountTransactions as $accountTransaction) {
                    if ($accounts_receivable_id == $accountTransaction->account_id) {
                        $accountTransaction->update([
                            'amount' => $total_child_amount,
                            'operation_date' => $inputs['paid_on']
                        ]);
                    } else {
                        $accountTransaction->update([
                            'amount' => $total_child_amount,
                            'operation_date' => $inputs['paid_on'],
                            'account_id' => $inputs['account_id']
                        ]);
                    }
                }
                ContactLedger::where('transaction_payment_id', $parent_payment->id)
                    ->update(['amount' => $total_child_amount]);

                $this->transactionUtil->updatePaymentAtOnce($parent_payment, $transaction->type);
            } else {
                // If it's a parent or standalone, update payment status
                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);
            }

            $business_id = $request->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                ->find($payment->transaction_id);

            $document_name = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            if (!empty($document_name)) {
                $inputs['document'] = $document_name;
                $payment->update(['document' => $document_name]);
            }

            // Handle specific transaction types (e.g., expense, sell, property_sell)
            if ($transaction->type == 'expense') {
                $transaction->expense_account = $request->expense_account;
                $transaction->save();
                AccountTransaction::where('transaction_id', $transaction->id)->delete();
                $this->addExpenseAccountTransaction($transaction, $inputs, $business_id);
            } else {
                if (in_array($transaction->type, ['sell', 'fpos_sale', 'tpos_sale'], true)) {
                    if (!empty($payment->parent_id)) {
                        $parent_payment = TransactionPayment::find($payment->parent_id);
                        $parent_payment->update($inputs);
                        $parentAccountTransactions = AccountTransaction::where('transaction_payment_id', $parent_payment->id)->get();
                        foreach ($parentAccountTransactions as $accountTransaction) {
                            $accountTransaction->update(['amount' => $inputs['amount']]);
                        }
                        $this->transactionUtil->updatePaymentAtOnce($parent_payment, $transaction->type);
                    }

                    $this->transactionUtil->reconcileSellPaymentAccountTransactions($transaction, true);
                    AccountTransaction::where('transaction_id', $transaction->id)
                        ->where('account_id', $accounts_receivable_id)
                        ->whereNotNull('transaction_payment_id')
                        ->update(['operation_date' => $inputs['paid_on']]);
                }
                if ($transaction->type == 'property_sell') {
                    $account_settings = $this->transactionUtil->getPropertyAccountSettingsByTransaction($transaction->id);
                    if (!empty($account_settings)) {
                        $account_receivable_account_id = $account_settings->account_receivable_account_id;
                        AccountTransaction::where('account_id', $account_receivable_account_id)
                            ->where('transaction_payment_id', $payment->id)
                            ->update(['amount' => $inputs['amount'], 'operation_date' => $inputs['paid_on']]);
                    }
                }
                event(new TransactionPaymentUpdated($payment, $transaction->type));
            }

            Activity::create([
                'log_name' => "Transaction Payment",
                'description' => "updated",
                'subject_id' => $payment->id,
                'subject_type' => "App\TransactionPayment",
                'causer_id' => auth()->user()->id,
                'causer_type' => 'App\User',
                'properties' => $attributes,
                'created_at' => date('Y-m-d H:i'),
                'updated_at' => date('Y-m-d H:i'),
            ]);

            $payment_ref = $payment->payment_ref_no;
            $payment_amt = $payment->amount;
            $contact = Contact::findOrFail($transaction->contact_id);
            $transaction->contact = $contact;
            $not = $contact->type == 'supplier' ? 'supplier_payment_editted' : 'customer_payment_editted';
            $transaction->single_payment_amount = $this->transactionUtil->num_uf($payment_amt);
            $transaction->payment_ref_number = $payment_ref;
            $this->notificationUtil->autoSendNotification($transaction->business_id, $not, $transaction, $transaction->contact, true);

            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
            $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'general_payment_editted')->first();
            if (!empty($msg_template)) {
                $logo_name = $business->logo;
                $business_logo = !empty($logo_name) ? '<img src="' . url('public/uploads/business_logos/' . $logo_name) . '" alt="Business Logo" >' : '';
                $msg = $msg_template->sms_body;
                $msg = str_replace('{transaction_date}', $this->transactionUtil->format_date($transaction->transaction_date), $msg);
                $msg = str_replace('{received_amount}', $this->transactionUtil->num_f($transaction->final_total), $msg);
                $msg = str_replace('{business_name}', $business->name, $msg);
                $msg = str_replace('{business_logo}', $logo_name, $msg);
                $msg = str_replace('{payment_ref_number}', $transaction->ref_no, $msg);
                $msg = str_replace('{contact_name}', $contact->name, $msg);
                $phones = !empty($business->sms_settings) ? explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos'])) : [];
                if (!empty($phones)) {
                    $data = [
                        'sms_settings' => $sms_settings,
                        'mobile_number' => implode(',', $phones),
                        'sms_body' => $msg
                    ];
                    $this->businessUtil->sendSms($data, 'general_payment_editted');
                }
            }

            DB::commit();

            $output = [
                'success' => true,
                'msg' => __('purchase.payment_updated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return Redirect::back()->with(['status' => $output]);
    }

    public function updateChequeTransaction(Request $request, $id)
    {
        if (
            !Gate::forUser(auth()->user())->check('purchase.delete.payments') && !Gate::forUser(auth()->user())->check('purchase.payments') &&
            !Gate::forUser(auth()->user())->check('purchase.edit.payments') &&
            !Gate::forUser(auth()->user())->check('add.payments') && !Gate::forUser(auth()->user())->check('sell.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        try {
            DB::beginTransaction();

            $account_transaction = AccountTransaction::findOrFail($id);
            $trans = Transaction::find($account_transaction->transaction_id);
            
            // Check if this cheque has been returned
            if (!empty($account_transaction->cheque_number)) {
                $cheque_return = Transaction::where('type', 'cheque_return')
                    ->where('contact_id', $trans->contact_id)
                    ->whereExists(function($query) use ($account_transaction) {
                        $query->select(DB::raw(1))
                            ->from('account_transactions')
                            ->whereColumn('account_transactions.transaction_id', 'transactions.id')
                            ->where('account_transactions.cheque_number', $account_transaction->cheque_number)
                            ->whereNull('account_transactions.deleted_at');
                    })
                    ->first();
                
                if (!empty($cheque_return)) {
                    DB::rollBack();
                    $output = [
                        'success' => false,
                        'msg' => __('lang_v1.cannot_edit_delete_returned_cheque'),
                    ];
                    return Redirect::back()->with(['status' => $output]);
                }
            }
            
            $contact = Contact::find($trans->contact_id);

            $old_data = [
                'date' => $this->transactionUtil->format_date($trans->transaction_date),
                'contact_type' => $contact->type,
                'name' => $contact->name,
                'bank' => $account_transaction->bank_name,
                'cheque_number' => $account_transaction->cheque_number,
                'amount' => $this->transactionUtil->num_f($account_transaction->amount),
                'cheque_date' => $this->transactionUtil->format_date($account_transaction->cheque_date),
                'cheque_return_charges' => $trans->cheque_return_charges,
            ];
            // Mise à jour des valeurs
            $account = Account::find($request->input('cheque_bank') ?? $request->input('cheque_bank_s'));

            $account_transaction->bank_name = $account->name;
            $account_transaction->operation_date = Carbon::parse($request->input('paid_on'));
            $account_transaction->account_id = $account->id;
            $account_transaction->cheque_number = TransactionPayment::find($request->input('cheque_number_return'))->cheque_number ?? $request->input('cheque_number_s');
            $account_transaction->amount = floatval(str_replace(',', '', $request->input('amount')));
            $trans->final_total = floatval(str_replace(',', '', $request->input('amount')));
            $account_transaction->cheque_date = Carbon::parse($request->input('cheque_date'));

            // on fait un update de masse pour liberer l'autre transaction au cas ou
            AccountTransaction::where('cheque_number', $request->cheque_number_s)
                ->update(['cheque_number' => $account_transaction->cheque_number, 'bank_name' => $account->name]);

            if ($request->has('cheque_return_charges')) {
                $chq_return_charges_acc = AccountTransaction::where(['sub_type' => 'cheque_return_charges', 'cheque_number' => $request->cheque_number_s])->first();

                AccountTransaction::where('cheque_number', $request->cheque_number_s)->where('amount', $chq_return_charges_acc->amount)
                    ->update(['amount' => $request->input('cheque_return_charges')]);

                $trans->cheque_return_charges = $request->input('cheque_return_charges');
            }

            $account_transaction->save();
            $trans->transaction_date = Carbon::parse($request->input('paid_on'));
            $trans->update();

            // Nouvelle version
            $new_data = [
                'date' => $this->transactionUtil->format_date($trans->transaction_date),
                'contact_type' => $contact->type,
                'name' => $contact->name,
                'bank' => $account_transaction->bank_name,
                'cheque_number' => $account_transaction->cheque_number,
                'amount' => $this->transactionUtil->num_f($account_transaction->amount),
                'cheque_date' => $this->transactionUtil->format_date($account_transaction->cheque_date),
                'cheque_return_charges' => $trans->cheque_return_charges,
            ];

            // Enregistrement de l'activité
            Activity::create([
                'log_name' => "Cheque Transaction",
                'description' => "updated",
                'subject_id' => $account_transaction->id,
                'subject_type' => AccountTransaction::class,
                'causer_id' => auth()->user()->id,
                'causer_type' => \App\User::class,
                'properties' => [
                    'old' => $old_data,
                    'attributes' => $new_data,
                ],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            $output = [
                'success' => true,
                'msg' => __('purchase.payment_updated_success')
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return Redirect::back()->with(['status' => $output]);
    }

    /**

     * Add Account Transactions

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */



    public function addExpenseAccountTransaction($transaction, $payment, $business_id)
    {

        if (!empty($transaction->expense_account)) {

            $ob_transaction_data = [

                'amount' => $transaction->final_total,

                'account_id' => $transaction->expense_account,

                'type' => 'debit',

                'sub_type' => 'expense',

                'operation_date' => Carbon::now(),

                'created_by' => Auth::user()->id,

                'transaction_id' => $transaction->id

            ];

            AccountTransaction::createAccountTransaction($ob_transaction_data);

            $account_payable_id = Account::where('business_id', $business_id)->where('name', 'Accounts Payable')->first()->id;

            $ap_transaction_data = [

                'operation_date' => Carbon::now(),

                'created_by' => Auth::user()->id,

                'transaction_id' => $transaction->id

            ];

            //if no amount paid

            if ($payment['amount'] == 0) {

                $ap_transaction_data['amount'] = $transaction->final_total;

                $ap_transaction_data['account_id'] = $account_payable_id;

                $ap_transaction_data['type'] = 'credit';

                AccountTransaction::createAccountTransaction($ap_transaction_data);
            }

            //if partial amount paid
            else if ($payment['amount'] < $transaction->final_total) {

                $ap_transaction_data['amount'] = $payment['amount'];  //paid amount

                $ap_transaction_data['account_id'] = $this->transactionUtil->getDefaultAccountId($payment['method'], $transaction->location_id);

                $ap_transaction_data['type'] = 'credit';

                AccountTransaction::createAccountTransaction($ap_transaction_data);

                $ap_transaction_data['amount'] = $transaction->final_total - $payment['amount']; //unpaid amount

                $ap_transaction_data['account_id'] = $account_payable_id;

                $ap_transaction_data['type'] = 'credit';

                AccountTransaction::createAccountTransaction($ap_transaction_data);
            }

            // if full amount paid

            if ($payment['amount'] == $transaction->final_total) {

                $ap_transaction_data['amount'] = $payment['amount'];  // full paid amount

                $ap_transaction_data['account_id'] = $this->transactionUtil->getDefaultAccountId($payment['method'], $transaction->location_id);

                $ap_transaction_data['type'] = 'credit';

                AccountTransaction::createAccountTransaction($ap_transaction_data);
            }
        }
    }

    /**

     * Remove the specified resource from storage.

     *

     * @param  int  $id

     * @return \Illuminate\Http\Response

     */



    public function destroy($id)
    {

        if (
            !Gate::forUser(auth()->user())->check('purchase.delete.payments') && !Gate::forUser(auth()->user())->check('purchase.payments') &&
            !Gate::forUser(auth()->user())->check('purchase.edit.payments') &&
            !Gate::forUser(auth()->user())->check('add.payments') && !Gate::forUser(auth()->user())->check('sell.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            try {

                $payment = TransactionPayment::findOrFail($id);

                // Check if cheque is deposited or transferred - prevent deletion
                if (($payment->method == 'cheque' || $payment->method == 'bank_transfer')) {
                    // Check if this cheque has been returned
                    $cheque_return = Transaction::where('type', 'cheque_return')
                        ->where('contact_id', $payment->payment_for)
                        ->whereExists(function($query) use ($payment) {
                            $query->select(DB::raw(1))
                                ->from('account_transactions')
                                ->whereColumn('account_transactions.transaction_id', 'transactions.id')
                                ->where('account_transactions.cheque_number', $payment->cheque_number)
                                ->whereNull('account_transactions.deleted_at');
                        })
                        ->first();
                    
                    if (!empty($cheque_return)) {
                        $output = [
                            'success' => false,
                            'msg' => __('lang_v1.cannot_edit_delete_returned_cheque'),
                        ];
                        return $output;
                    }
                    
                    // Check if is_deposited flag is explicitly set to 1
                    // This flag is set when a cheque is deposited to a bank account
                    if ($payment->is_deposited == 1) {
                        $output = [
                            'success' => false,
                            'msg' => __('lang_v1.cheque_already_deposited_cannot_delete'),
                        ];
                        return $output;
                    }

                    // Check if there's a cheque_deposit_bank record linked through account_transactions
                    // This indicates the cheque has been deposited to a bank account
                    $cheque_deposit = DB::table('cheque_deposit_bank')
                        ->where('cheque_number', $payment->cheque_number)
                        ->exists();

                    if ($cheque_deposit) {
                        $output = [
                            'success' => false,
                            'msg' => __('lang_v1.cheque_already_deposited_cannot_delete'),
                        ];
                        return $output;
                    }
                }

                DB::beginTransaction();

                $transaction_id = $payment->transaction_id;
                $transaction = Transaction::findOrFail($transaction_id);

                //Update parent payment if exists

                if (!empty($payment->parent_id)) {

                    $parent_payment = TransactionPayment::find($payment->parent_id);

                    $parent_payment->amount -= $payment->amount;

                    if ($parent_payment->amount <= 0) {
                        $parent_payment_id = $parent_payment->id;
                        $parent_payment_account_id = $parent_payment->account_id;

                        $parent_payment->deleted_by = auth()->user()->id;
                        $parent_payment->save();
                        $parent_payment->delete();

                        if ($transaction->type != 'purchase' && $transaction->sub_type != 'excess' && $transaction->sub_type != 'shortage') {
                            event(new TransactionPaymentDeleted($parent_payment_id, $parent_payment_account_id));
                        }
                    } else {

                        $parent_payment->save();
                        $this->transactionUtil->syncPaymentAccountAndLedgerEntries($parent_payment);
                    }
                }

                $payment_ref = $payment->payment_ref_no;
                $payment_amt = $payment->amount;

                $this->transactionUtil->deleteAccountAndLedgerTransactionReverse($transaction, $id);

                $payment->deleted_by = auth()->user()->id;
                $payment->save();

                $payment->delete();

                //update payment status

                $this->transactionUtil->updatePaymentStatus($payment->transaction_id);

                $changed_msg = "Contact #" . (Contact::find($transaction->contact_id))->name . " - #Payment Ref: {$payment_ref} Transaction has been deleted by " . auth()->user()->username;

                $activity = new Activity();
                $activity->log_name = "Contact Payment";
                $activity->description = "delete";
                $activity->subject_id = $id;
                $activity->subject_type = "";
                $activity->causer_id = auth()->user()->id;
                $activity->causer_type = 'App\AccountTransaction';
                $activity->properties = $changed_msg;
                $activity->created_at = date('Y-m-d H:i');
                $activity->updated_at = date('Y-m-d H:i');

                // Save the activity
                $activity->save();

                if ($transaction->sub_type == 'excess' || $transaction->sub_type == 'shortage') {

                    $transaction->payment_status = 'due';

                    $transaction->save();
                }

                if ($transaction->type != 'purchase' && $transaction->sub_type != 'excess' && $transaction->sub_type != 'shortage') {

                    event(new TransactionPaymentDeleted($payment->id, $payment->account_id));
                }

                DB::commit();
                $contact = Contact::find($transaction->contact_id);

                if (!empty($contact)) {

                    $transaction->contact = $contact;
                    if ($contact->type == 'supplier') {
                        $not = 'supplier_payment_deleted';
                    } else {
                        $not = 'customer_payment_deleted';
                    }

                    $transaction->single_payment_amount = $this->transactionUtil->num_uf($payment_amt);
                    $transaction->payment_ref_number = $payment_ref;
                    $this->notificationUtil->autoSendNotification($transaction->business_id, $not, $transaction, $transaction->contact, true);

                    $business_id = request()->session()->get('user.business_id');
                    $business = Business::where('id', $business_id)->first();
                    $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;
                    $accountName = null;
                    $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'general_payment_deleted')->first();
                    if (!empty($msg_template)) {
                        $logo_name = $business->logo;
                        $business_logo = !empty($logo_name) ? '<img src="' . url('public/uploads/business_logos/' . $logo_name) . '" alt="Business Logo" >' : '';

                        $msg = $msg_template->sms_body;
                        $msg = str_replace('{transaction_date}', $this->transactionUtil->format_date($transaction->transaction_date), $msg);
                        $msg = str_replace('{received_amount}', $this->transactionUtil->num_f($transaction->final_total), $msg);
                        $msg = str_replace('{business_name}', $business->name, $msg);
                        $msg = str_replace('{business_logo}', $logo_name, $msg);
                        $msg = str_replace('{payment_ref_number}', $transaction->ref_no, $msg);
                        $msg = str_replace('{contact_name}', $contact->name, $msg);

                        $phones = [];

                        if (!empty($business->sms_settings) && !empty($business->sms_settings['msg_phone_nos'])) {
                            $phones = explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']));
                        }
                        foreach ($phones as $phone) {
                            $data = [
                                'sms_settings' => $sms_settings,
                                'mobile_number' => $phone,
                                'sms_body' => $msg
                            ];
                            $response = $this->transactionUtil->sendSms($data);
                        }
                    }
                }

                $output = [

                    'success' => true,

                    'msg' => __('purchase.payment_deleted_success')

                ];
            } catch (\Exception $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }

                logger($e);

                Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [

                    'success' => false,

                    'msg' => __('messages.something_went_wrong')

                ];
            }

            return $output;
        }
    }

    /**

     * Adds new payment to the given transaction.

     *

     * @param  int  $transaction_id

     * @return \Illuminate\Http\Response

     */



    public function addPayment($transaction_id)
    {

        if (
            !Gate::forUser(auth()->user())->check('purchase.delete.payments') && !Gate::forUser(auth()->user())->check('purchase.payments') &&
            !Gate::forUser(auth()->user())->check('purchase.edit.payments') &&
            !Gate::forUser(auth()->user())->check('add.payments') && !Gate::forUser(auth()->user())->check('sell.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (! request()->ajax()) {
            return Redirect::back();
        }

            $business_id = request()->session()->get('user.business_id');

            $transaction = Transaction::where('id', $transaction_id)

                ->where('business_id', $business_id)

                ->with(['contact', 'location'])

                ->first();

            if ($transaction->payment_status != 'paid') {

                if ($transaction->type == 'expense') {
                    // For expense add-payment, only show methods enabled for expense payments.
                    $payment_types = $this->transactionUtil->payment_types($transaction->location_id, false, false, false, false, true, "is_expense_enabled");
                } else if ($transaction->type == 'purchase') {

                    $payment_types = $this->transactionUtil->payment_types(null, false, false, false, false, true, "is_purchase_enabled");
                } else if ($transaction->type == 'property_purchase') {

                    $payment_types = $this->transactionUtil->payment_types($transaction->location, true, true);
                } else if (in_array($transaction->type, ['sell', 'fpos_sale', 'tpos_sale', 'property_sell'], true)) {

                    $payment_types = $this->transactionUtil->payment_types($transaction->location, true, false, false, true);
                } else {

                    $payment_types = $this->transactionUtil->payment_types($transaction->location);
                }

                $credit_sales_supported = ['production_sale', 'property_sale', 'route_operation', 'sell', 'fpos_sale', 'tpos_sale', 'sell_return', 'sell_transfer', 'settlement'];
                $credit_purchases_supported = ['expense', 'purchase', 'production_purchase', 'property_purchase', 'purchase', 'purchase_return', 'purchase_transfer'];

                if (in_array($transaction->type, $credit_sales_supported)) {
                    $payment_types['credit_sale'] = "Credit Sale";
                }

                if (in_array($transaction->type, $credit_purchases_supported)) {
                    $payment_types['credit_purchase'] = "Credit Purchase";
                }

                $payment_types = collect($payment_types)
                    ->filter(function ($label, $key) {
                        return !is_numeric($key) && !is_numeric($label);
                    })
                    ->reject(function ($label, $key) {
                        $key_l = strtolower((string) $key);
                        $label_l = strtolower((string) $label);
                        // Credit Expense is not a real user-selectable payment method for Add Payment.
                        return in_array($key_l, ['credit_expense', 'credit_sale', 'credit_purchase', 'location_id'])
                            || str_contains($label_l, 'credit expense');
                    })
                    ->toArray();

                $paid_amount = $this->transactionUtil->getTotalPaid($transaction_id);

                $amount = $transaction->final_total - $paid_amount;

                if ($amount < 0) {

                    $amount = 0;
                }

                $amount_formated = $this->transactionUtil->num_f($amount);

                $payment_line = new TransactionPayment();

                $payment_line->amount = $amount;

                $payment_line->method = 'cash';

                $payment_line->paid_on = Carbon::now()->toDateTimeString();

                $current_account_type_id = AccountType::where('business_id', $business_id)->where('name', 'Current Assets')->first();

                $account_module = $this->moduleUtil->isModuleEnabled('account');

                if ($transaction->type == 'expense') {

                    $accounts = $this->moduleUtil->accountsDropdown($business_id, true);

                    if ($this->moduleUtil->isModuleEnabled('account')) {

                        $accounts = Account::where('business_id', $business_id)->where('account_type_id', $current_account_type_id->id)->notClosed()->pluck('name', 'id');
                    }
                } else {

                    //Accounts

                    $accounts = $this->moduleUtil->accountsDropdown($business_id, true);
                }

                $trans_count = Transaction::count();

                // $refNo = $transaction->ref_no ?$transaction->ref_no : "SP-".$trans_count;

                $refNo = "SP-" . $trans_count;

                $view = view('transaction_payment.payment_row')

                    ->with(compact('transaction', 'payment_types', 'account_module', 'payment_line', 'amount_formated', 'accounts', 'refNo'))->render();

                $output = [

                    'status' => 'due',

                    'view' => $view

                ];
            } else {

                $output = [

                    'status' => 'paid',

                    'view' => '',

                    'msg' => __('purchase.amount_already_paid')

                ];
            }

        // return json_encode($output);
        return response()->json($output);
    }

    /**

     * Shows contact's advance payment modal

     *

     * @param  int  $contact_id

     * @return \Illuminate\Http\Response

     */



    public function getAdvancePayment($contact_id)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create')) {

            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');
            $pdChequePermissions = $this->getPdChequePermissions($business_id);

            $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

            $business_location_id = BusinessLocation::where('business_id', $business_id)->first()->id;

            $contact_details = Contact::where('id', $contact_id)->first();

            $payment_action = $contact_details->type == 'customer' ? 'is_sale_enabled' : 'is_purchase_enabled';
            $payment_types = $this->transactionUtil->payment_types($business_location_id, false, false, false, false, true, $payment_action);
            $payment_types = $this->sanitizeContactPaymentTypes($payment_types);

            $clati = 0;

            $current_liability_account_type_id = AccountType::where('name', 'Current Liabilities')->where('business_id', $business_id)->first();

            if (!empty($current_liability_account_type_id)) {

                $clati = $current_liability_account_type_id->id;
            }

            //Accounts

            $advance_to_supplier_account_id = $this->transactionUtil->account_exist_return_id('Advances to Suppliers');

            $customer_deposit_account_id = $this->transactionUtil->account_exist_return_id('Customer Deposits');

            if ($contact_details->type == 'customer') {

                $accounts = Account::where('business_id', $business_id)->where('id', $customer_deposit_account_id)->where('is_closed', 0)->pluck('name', 'id');
            } else {

                $accounts = Account::where('business_id', $business_id)->where('id', $advance_to_supplier_account_id)->where('is_closed', 0)->pluck('name', 'id');
            }

            $prefix_type = 'advance_payment';

            $ref_count = $this->transactionUtil->onlyGetReferenceCount($prefix_type, $business_id, false);

            //Generate reference number

            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            return view('transaction_payment.customer_advance_payment')
                ->with(compact('business_locations', 'business_location_id', 'contact_details', 'payment_types', 'accounts', 'payment_ref_no', 'contact_id', 'customer_deposit_account_id', 'pdChequePermissions'));
        }
    }

    public function getDirectLoan($contact_id)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create')) {

            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            $contact_details = Contact::where('id', $contact_id)->first();


            //Accounts

            $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');

            $accounts = Account::where('business_id', $business_id)->where('id', $cash_account_id)->where('is_closed', 0)->pluck('name', 'id');


            return view('transaction_payment.direct_loan')

                ->with(compact('contact_details', 'accounts', 'contact_id'));
        }
    }

    public function getRefundDeposit($contact_id)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create')) {

            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

            $business_location_id = BusinessLocation::where('business_id', $business_id)->first()->id;

            $contact_details = Contact::where('id', $contact_id)->first();

            $payment_types = $this->transactionUtil->payment_types($business_location_id);

            unset($payment_types['credit_sale']);  // removing credit sale method from array

            if ($contact_details->type == 'customer') {
                unset($payment_types['cheque']);
            } else {
                unset($payment_types['bank']);
                unset($payment_types['bank_transfer']);
            }

            $bank_account_group_id = AccountGroup::getGroupByName('Bank Account');

            $bank_accounts = Account::where('business_id', $business_id)->where('asset_type', $bank_account_group_id->id)->get();


            $clati = 0;

            $current_liability_account_type_id = AccountType::where('name', 'Current Liabilities')->where('business_id', $business_id)->first();

            if (!empty($current_liability_account_type_id)) {

                $clati = $current_liability_account_type_id->id;
            }

            $current_accounts = Account::where('business_id', $business_id)->where('is_closed', 0)->pluck('name', 'id');

            $security_deposit = Transaction::leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'security_deposit')
                ->where('transactions.contact_id', $contact_id)->sum('transactions.final_total');

            $security_deposit_paid = Transaction::leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'security_deposit_refund')
                ->where('transactions.contact_id', $contact_id)->sum('transactions.final_total');
            $balance = $security_deposit - $security_deposit_paid;

            $settings = ContactLinkedAccount::where('business_id', $business_id)->first();


            $prefix_type = 'advance_payment';

            $ref_count = $this->transactionUtil->onlyGetReferenceCount($prefix_type, $business_id, false);

            //Generate reference number

            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            return view('transaction_payment.customer_refund_deposit')

                ->with(compact('bank_accounts', 'settings', 'current_accounts', 'balance', 'business_locations', 'business_location_id', 'contact_details', 'payment_types', 'payment_ref_no', 'contact_id'));
        }
    }

    /**

     * Adds Advance Payments for Contact

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */



    public function postAdvancePayment(Request $request)
    {

        /*
         * S631 TRACE. Deliberately unconditional and at WARNING level so it
         * appears whatever the log level is set to.
         *
         * Its job is to answer, in one payment, a question we could not answer
         * before: did this code run at all? If you make a payment on this page
         * and this line is NOT in laravel.log, then the deployed files are not
         * these files - opcache, a stale copy, or the request going somewhere
         * else entirely - and no amount of fixing in here will change anything.
         *
         * Remove these three TRACE lines once S631 is confirmed working.
         */
        Log::warning('S631 TRACE postAdvancePayment reached.', ['contact_id' => $request->input('contact_id') ?? ($contact_id ?? null)]);

        if (!Gate::forUser(auth()->user())->check('purchase.create') && !Gate::forUser(auth()->user())->check('sell.create')) {

            abort(403, 'Unauthorized action.');
        }

        try {

            $has_reviewed = $this->transactionUtil->hasReviewed($request->input('paid_on'));

            if (!empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($request->input('paid_on'), $request->input('paid_on'));

            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => "You can't add a payment for an already reviewed date",
                ];

                return Redirect::back()->with(['status' => $output]);
            }


            $business_id = $this->resolveContactPaymentBusinessId($request);

            $contact = $this->resolveContactForPayment($request->input('contact_id'), $business_id);
            $contact_id = (int) $contact->id;

            $inputs = $request->only([

                'amount',
                'method',
                'note',
                'card_number',
                'card_holder_name',

                'card_transaction_number',
                'card_type',
                'card_month',
                'card_year',
                'card_security',

                'cheque_number',
                'bank_account_number',
                'bank_name',
                'cheque_date',
                'post_dated_cheque',
                'update_post_dated_cheque'

            ]);

            if ($inputs['method'] == 'cheque') {
                if (empty($inputs['cheque_number']) || empty($inputs['bank_name']) || empty($inputs['cheque_date'])) {
                    $output = [
                        'success' => false,
                        'msg' => 'Bank name, Cheque Date and Cheque number are required for Cheque payments'
                    ];
                    return Redirect::back()->with('status', $output);
                } else {
                    // check duplicates
                    $chequesAdded = $this->transactionUtil->checkCheques($inputs['cheque_number'], $inputs['bank_name']);

                    if ($chequesAdded > 0) {
                        $output = [
                            'success' => false,
                            'msg' => 'Cheque with the same number and bank name already exists!'
                        ];
                        return Redirect::back()->with('status', $output);
                    }
                }
            }


            $inputs['paid_on'] = $this->resolveSelectedPaymentDate($request, true);
            if (empty($inputs['paid_on']) && !empty($request->input('paid_on'))) {
                try {
                    $inputs['paid_on'] = Carbon::parse($request->input('paid_on'))->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    $inputs['paid_on'] = null;
                }
            }
            if (empty($inputs['paid_on'])) {
                $inputs['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            }

            $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? Carbon::parse($inputs['cheque_date'])->format('Y-m-d') : date('Y-m-d');

            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            if ($inputs['amount'] <= 0) {
                return Redirect::back()->with('status', [
                    'success' => false,
                    'msg' => 'Payment amount must be greater than zero.',
                ]);
            }

            if (empty($request->input('account_id'))) {
                return Redirect::back()->with('status', [
                    'success' => false,
                    'msg' => 'Please select a payment account.',
                ]);
            }

            $inputs['created_by'] = auth()->user()->id;

            $inputs['payment_for'] = $contact_id;

            $inputs['business_id'] = $business_id;

            if ($inputs['method'] == 'custom_pay_1') {

                $inputs['transaction_no'] = $request->input('transaction_no_1');
            } elseif ($inputs['method'] == 'custom_pay_2') {

                $inputs['transaction_no'] = $request->input('transaction_no_2');
            } elseif ($inputs['method'] == 'custom_pay_3') {

                $inputs['transaction_no'] = $request->input('transaction_no_3');
            }

            $payment_type = $request->type;

            $prefix_type = $request->type;

            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);

            //Generate reference number

            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            $inputs['payment_ref_no'] = $payment_ref_no;

            $business_location = BusinessLocation::where('business_id', $business_id)

                ->first();

            $inputs['account_id'] = $request->account_id;

            //Upload documents if added

            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            DB::beginTransaction();

            //Add opening balance


            $transaction = $this->transactionUtil->createAdvancePaymentTransaction($business_id, $contact_id, $inputs['amount'], $inputs['account_id'], $payment_type, $inputs['paid_on'], null, $inputs);

            $date = Carbon::parse($inputs['paid_on'])->format('Y-m-d');

            if ($inputs['method'] == 'Bank') {
                $ipdcAccount = Account::where('business_id', $business_id)
                    ->where('name', 'Account Payable')
                    ->first();

                if ($ipdcAccount) {
                    $acc_tran = [
                        'account_id'     => $ipdcAccount->id,
                        'type'           => 'debit',
                        'business_id'    => $business_id,
                        'amount'         => $inputs['amount'],
                        'operation_date' => $date,
                        'created_by'     => Auth::user()->id,
                        'traction_id'    => $transaction->id,
                    ];
                    AccountTransaction::create($acc_tran);
                }

                if ($inputs['post_dated_cheque'] == 1) {
                    $ipdcAccount = Account::where('business_id', $business_id)
                        ->where('name', 'Issued Post Dated Cheques')
                        ->first();

                    if ($ipdcAccount) {
                        $acc_tran = [
                            'account_id'     => $ipdcAccount->id,
                            'type'           => 'credit',
                            'business_id'    => $business_id,
                            'amount'         => $inputs['amount'],
                            'operation_date' => $date,
                            'created_by'     => Auth::user()->id,
                            'traction_id'    => $transaction->id,
                        ];
                        AccountTransaction::create($acc_tran);
                    }
                }

            }
            $inputs['transaction_id'] = $transaction->id;


            $account_id = $inputs['account_id'];
            $post_dated = $this->transactionUtil->account_exist_return_id('Post Dated Cheques');
            $issued_post_dated = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');

            if ($payment_type == 'advance_payment') {
                if ($contact->type == 'customer') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $post_dated;
                    }
                }
                if ($contact->type == 'supplier') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $issued_post_dated;
                    }
                }
            }
            if ($payment_type == 'security_deposit') {
                if ($contact->type == 'customer') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $post_dated;
                    }
                }
                if ($contact->type == 'supplier') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $issued_post_dated;
                    }
                }
            }

            if ($payment_type == 'security_deposit_refund') {
                if ($contact->type == 'customer') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $issued_post_dated;
                    }
                }
                if ($contact->type == 'supplier') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $post_dated;
                    }
                }
            }


            $parent_payment = TransactionPayment::create($inputs);

            ContactLedger::where('transaction_id', $transaction->id)->update(['transaction_payment_id' => $parent_payment->id]);

            AccountTransaction::where('transaction_id', $transaction->id)->update(['transaction_payment_id' => $parent_payment->id]);


            $transaction->contact = Contact::where('id', $contact_id)->first();
            $transaction->transaction_date = $inputs['paid_on'];
            $transaction->single_payment_amount = $this->transactionUtil->num_uf($inputs['amount']);
            $transaction->payment_ref_number = $payment_ref_no;
            $transaction->total_payment_amount = $this->transactionUtil->num_uf($inputs['amount']);
            if (!empty($transaction->contact) && $transaction->contact->type === 'supplier') {
                $transaction->cumulative_due_amount = $this->contactUtil->getSupplierBalance($contact_id, $business_id, true);
            } else {
                $transaction->cumulative_due_amount = $this->contactUtil->getCustomerBalance($contact_id, $business_id, true);
            }

            /*
             * S631: the notification used to be SENT HERE, on this line, which is
             * still inside the transaction - DB::commit() is a few lines below.
             *
             * Two consequences:
             *   1. A slow or failing SMS gateway threw into the catch and rolled
             *      back an otherwise good advance payment.
             *   2. If anything between here and the commit returned early - the
             *      auto-transfer branch below does exactly that on failure - the
             *      SMS had already gone out for a payment that was then discarded.
             *
             * It is now queued and sent after the commit, like the other pages.
             */
            $pending_advance_payment_notification = $transaction;

            // auto transfer update_post_dated_cheque
            $msg = "";
            if (!empty($inputs['update_post_dated_cheque'])) {
                $autoFundTransfer = $this->autoFundTransfer($request);
                if ($autoFundTransfer['success']) {
                    $msg = " And Auto Transferred";
                } else {
                    $msg = " And Auto Transfer Failed";
                    $autoFundTransfer['msg'] = $autoFundTransfer['msg'] . $msg;
                    return Redirect::back()->with(['status' => $autoFundTransfer]);
                }
            }

            DB::commit();

            // S631: sent only once the advance payment is durably saved, and never
            // allowed to fail the request.
            try {
                $this->notificationUtil->autoSendNotification(
                    $business_id,
                    'payment_received',
                    $pending_advance_payment_notification,
                    $pending_advance_payment_notification->contact,
                    true
                );
            } catch (\Exception $notification_exception) {
                Log::error('S631 payment_received notification failed after a saved advance payment', [
                    'business_id' => $business_id,
                    'contact_id' => $contact_id,
                    'message' => $notification_exception->getMessage(),
                    'file' => $notification_exception->getFile(),
                    'line' => $notification_exception->getLine(),
                ]);
            }


            $output = [

                'success' => true,

                'msg' => __('purchase.payment_added_success') . $msg

            ];
        } catch (\Exception $e) {

            DB::rollBack();

            Log::emergency('Supplier advance payment failed', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'business_id' => $business_id ?? null,
                'contact_reference' => $request->input('contact_id'),
                'method' => $request->input('method'),
                'account_id' => $request->input('account_id'),
            ]);

            $output = [
                'success' => false,
                'msg' => config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong'),
            ];
        }

        return Redirect::back()->with(['status' => $output]);
    }

    public function postDirectLoan(Request $request, $contact_id)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create') && !Gate::forUser(auth()->user())->check('sell.create')) {

            abort(403, 'Unauthorized action.');
        }

        try {

            $has_reviewed = $this->transactionUtil->hasReviewed($request->input('paid_on'));

            if (!empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($request->input('paid_on'), $request->input('paid_on'));

            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => "You can't add a payment for an already reviewed date",
                ];

                return Redirect::back()->with(['status' => $output]);
            }


            $business_id = request()->session()->get('user.business_id');


            $business_location_id = BusinessLocation::where('business_id', $business_id)->first()->id;

            $inputs = $request->only([
                'amount',
                'paid_on'
            ]);


            $inputs['paid_on'] = $this->resolveSelectedPaymentDate($request, true);


            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);



            $prefix_type = 'sell_payment';
            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);


            $ob_data = [
                'transaction_note' => request()->note,

                'business_id' => $business_id,

                'location_id' => $business_location_id,

                'type' => 'direct_customer_loan',

                'status' => 'final',

                'payment_status' => 'due',

                'contact_id' => $contact_id,

                'transaction_date' => $inputs['paid_on'],

                'total_before_tax' => $inputs['amount'],

                'final_total' => $inputs['amount'],

                'invoice_no' => $payment_ref_no,

                'created_by' => request()->session()->get('user.id')

            ];



            DB::beginTransaction();

            $transaction = Transaction::create($ob_data);

            $cash_account_id = $this->transactionUtil->account_exist_return_id('Cash');
            $receivable_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');

            $account_transaction_data = [

                'amount' => abs($transaction->final_total),

                'account_id' => $cash_account_id,

                'contact_id' => $contact_id,

                'operation_date' => $inputs['paid_on'],

                'created_by' => $transaction->created_by,

                'transaction_id' => $transaction->id,
                'note' => request()->note,

            ];

            $account_transaction_data['type'] = 'credit';

            AccountTransaction::createAccountTransaction($account_transaction_data);

            $account_transaction_data['account_id'] = $receivable_id;

            $account_transaction_data['type'] = 'debit';

            AccountTransaction::createAccountTransaction($account_transaction_data);

            $transaction->contact = Contact::findOrFail($contact_id);

            DB::commit();

            $transaction->payment_ref_number = $payment_ref_no;
            $this->notificationUtil->autoSendNotification($transaction->business_id, 'customer_loan_given', $transaction, $transaction->contact);

            $output = [

                'success' => true,

                'msg' => __('lang_v1.success')

            ];
        } catch (\Exception $e) {

            DB::rollBack();

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];
        }

        return Redirect::back()->with(['status' => $output]);
    }

    public function postRefundDeposit(Request $request)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create') && !Gate::forUser(auth()->user())->check('sell.create')) {

            abort(403, 'Unauthorized action.');
        }

        try {

            $has_reviewed = $this->transactionUtil->hasReviewed($request->input('paid_on'));

            if (!empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($request->input('paid_on'), $request->input('paid_on'));

            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => "You can't add a payment for an already reviewed date",
                ];

                return Redirect::back()->with(['status' => $output]);
            }


            $business_id = request()->session()->get('user.business_id');

            $contact_id = $request->input('contact_id');

            $contact_id = Contact::where('contact_id', $contact_id)->where('business_id', $business_id)->first()->id;

            $inputs = $request->only([

                'amount',
                'method',
                'note',
                'card_number',
                'card_holder_name',

                'card_transaction_number',
                'card_type',
                'card_month',
                'card_year',
                'card_security',

                'cheque_number',
                'bank_account_number',
                'bank_name',
                'cheque_date',
                'post_dated_cheque',
                'update_post_dated_cheque'

            ]);


            $inputs['paid_on'] = $this->resolveSelectedPaymentDate($request, true);

            $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? Carbon::parse($inputs['cheque_date'])->format('Y-m-d') : date('Y-m-d');

            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            $inputs['created_by'] = auth()->user()->id;

            $inputs['payment_for'] = $contact_id;

            $inputs['business_id'] = $request->session()->get('business.id');


            $payment_type = 'security_deposit_refund';

            $prefix_type = $request->type;

            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);

            //Generate reference number

            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            $inputs['payment_ref_no'] = $payment_ref_no;

            $business_location = BusinessLocation::where('business_id', $business_id)

                ->first();

            $inputs['account_id'] = $request->account_id;

            //Upload documents if added

            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            DB::beginTransaction();

            $settings = ContactLinkedAccount::where('business_id', $business_id)->first();

            //Add opening balance

            $transaction = $this->transactionUtil->createAdvancePaymentTransaction($business_id, $contact_id, $inputs['amount'], $inputs['account_id'], $payment_type, $inputs['paid_on'], $settings->customer_deposit_refund_liability_account, $inputs);

            $inputs['transaction_id'] = $transaction->id;

            $contact = Contact::findOrFail($contact_id);
            $account_id = $inputs['account_id'];
            $post_dated = $this->transactionUtil->account_exist_return_id('Post Dated Cheques');
            $issued_post_dated = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');

            if ($payment_type == 'advance_payment') {
                if ($contact->type == 'customer') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $post_dated;
                    }
                }
                if ($contact->type == 'supplier') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $issued_post_dated;
                    }
                }
            }
            if ($payment_type == 'security_deposit') {
                if ($contact->type == 'customer') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $post_dated;
                    }
                }
                if ($contact->type == 'supplier') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $issued_post_dated;
                    }
                }
            }

            if ($payment_type == 'security_deposit_refund') {
                if ($contact->type == 'customer') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $issued_post_dated;
                    }
                }
                if ($contact->type == 'supplier') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $post_dated;
                    }
                }
            }

            $parent_payment = TransactionPayment::create($inputs);


            AccountTransaction::where('transaction_id', $transaction->id)->update(['transaction_payment_id' => $parent_payment->id]);



            DB::commit();

            $output = [

                'success' => true,

                'msg' => __('purchase.payment_added_success')

            ];
        } catch (\Exception $e) {

            DB::rollBack();

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];
        }

        return Redirect::back()->with(['status' => $output]);
    }

    /**

     * Shows contact's refund/cheque return payment modal

     *

     * @param  int  $contact_id

     * @return \Illuminate\Http\Response

     */



    public function getRefundPayment($contact_id, Request $request)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create')) {

            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            $contact_details = Contact::where('id', $contact_id)->first();

            $payment_types = ['cash' => __('lang_v1.cash'), 'cheque' => __('lang_v1.cheque'), 'bank_transfer' => __('lang_v1.bank')];;

            $clati = 0;

            $current_liability_account_type_id = AccountType::where('name', 'Current Liabilities')->where('business_id', $business_id)->first();

            if (!empty($current_liability_account_type_id)) {

                $clati = $current_liability_account_type_id->id;
            }

            // Get Bank Account Group from Accounting Module
            $bank_account_group = AccountGroup::where('business_id', $business_id)
                ->where('name', 'Bank Account')
                ->first();

            // Get all bank accounts from the Bank Account group
            $bank_accounts = collect();
            if ($bank_account_group) {
                $bank_accounts = Account::where('business_id', $business_id)
                    ->where('asset_type', $bank_account_group->id)
                    ->where('is_closed', 0)
                    ->where('visible', 1)
                    ->orderBy('name')
                    ->get();
            }

            $invoices = Transaction::where('contact_id', $contact_id)->where('type', 'sell')->pluck('invoice_no', 'invoice_no');

            //Accounts

            $advance_to_supplier_account_id = $this->transactionUtil->account_exist_return_id('Advances to Suppliers');

            $customer_deposit_account_id = $this->transactionUtil->account_exist_return_id('Customer Deposits');

            if ($contact_details->type == 'customer') {

                $accounts = Account::where('business_id', $business_id)->where('id', $customer_deposit_account_id)->where('is_closed', 0)->pluck('name', 'id');
            } else {

                $accounts = Account::where('business_id', $business_id)->where('id', $advance_to_supplier_account_id)->where('is_closed', 0)->pluck('name', 'id');
            }

            $selected_cheque = null;
            $account_transaction_id = $request->account_transaction_id;

            if ($request->is_edit) {
                $selected_cheque = new \stdClass();

                $account_transaction = AccountTransaction::findOrFail($request->account_transaction_id);
                $transaction = Transaction::findOrFail($account_transaction->transaction_id);
                // $tp = TransactionPayment::where('cheque_number', $account_transaction->cheque_number)->first();
                $bank = Account::where('name', $account_transaction->bank_name)->first();

                $selected_cheque->type = 'cheque_return';
                $selected_cheque->amount = $account_transaction->amount;
                $selected_cheque->paid_on = $transaction->transaction_date;
                $selected_cheque->method = 'bank_transfer';
                $selected_cheque->cheque_bank = $bank->id;
                $selected_cheque->cheque_number = $account_transaction->cheque_number;
                $selected_cheque->cheque_return_charges = $transaction->cheque_return_charges;
                $selected_cheque->transfer_date = $account_transaction->cheque_date;
            }

            $cheque_array = [];

            $cheque_banks = AccountTransaction::leftjoin('transactions', 'account_transactions.transaction_id', 'transactions.id')



                ->leftjoin('transaction_payments', 'account_transactions.transaction_payment_id', 'transaction_payments.id')

                ->leftjoin('accounts', 'account_transactions.account_id', 'accounts.id')

                ////                ->where('account_transactions.sub_type', 'deposit')
                // ->where('account_transactions.sub_type', 'deposit')

                ->where('transaction_payments.method', 'cheque')

                ->where('account_transactions.type', 'debit')

                ->where('transactions.contact_id', $contact_id)

                ->pluck('accounts.name', 'accounts.id');


            // Prepare bank accounts for dropdown
            $bank_accounts_dropdown = [];
            if (!empty($bank_accounts)) {
                foreach ($bank_accounts as $bank_account) {
                    $bank_accounts_dropdown[$bank_account->id] = $bank_account->name;
                }
            }

            return view('transaction_payment.customer_refund_payment')

                ->with(compact(

                    'invoices',

                    'bank_accounts',

                    'bank_accounts_dropdown',

                    'contact_details',

                    'payment_types',

                    'accounts',

                    'contact_id',

                    'customer_deposit_account_id',

                    'cheque_array',

                    'cheque_banks',

                    'selected_cheque',

                    'account_transaction_id'
                ));
        }
    }

    /**

     * Adds Advance Payments for Contact

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */



    public function postRefundPayment(Request $request)
    {
        if (!Gate::forUser(auth()->user())->check('purchase.create') && !Gate::forUser(auth()->user())->check('sell.create')) {

            abort(403, 'Unauthorized action.');
        }

        try {

            $has_reviewed = $this->transactionUtil->hasReviewed($request->input('paid_on'));

            if (!empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($request->input('paid_on'), $request->input('paid_on'));

            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => "You can't add a payment for an already reviewed date",
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $business_id = request()->session()->get('user.business_id');

            $contact_id = $request->input('contact_id');

            $contact_id = Contact::where('contact_id', $contact_id)->where('business_id', $business_id)->first()->id;

            $inputs = $request->only([

                'amount',
                'method',
                'note',
                'card_number',
                'card_holder_name',

                'card_transaction_number',
                'card_type',
                'card_month',
                'card_year',
                'card_security',
                'cheque_date',

                'cheque_number',
                'bank_account_number',
                'transfer_date',
                'bank_name',
                'sale_invoice_bill_number',
                'cheque_return_charges',
                'post_dated_cheque',
                'bank_name'

            ]);

            $inputs['paid_on'] = $this->resolveSelectedPaymentDate($request, true);

            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            $inputs['created_by'] = auth()->user()->id;

            $inputs['payment_for'] = $contact_id;

            $inputs['business_id'] = $request->session()->get('user.business_id');

            $method = $request->input('method');

            if ($request->type == 'cheque_return') {

                $method = 'bank_transfer';

                $inputs['method'] = $method;

                $inputs['cheque_number'] = $this->getPaymentDetailsById($request->cheque_number_return)->cheque_number;

                $inputs['bank_name'] = $this->getBankNameByBankId($request->cheque_bank);
            }

            if ($inputs['method'] == 'custom_pay_1') {

                $inputs['transaction_no'] = $request->input('transaction_no_1');
            } elseif ($inputs['method'] == 'custom_pay_2') {

                $inputs['transaction_no'] = $request->input('transaction_no_2');
            } elseif ($inputs['method'] == 'custom_pay_3') {

                $inputs['transaction_no'] = $request->input('transaction_no_3');
            }

            $payment_type = $request->type;

            $prefix_type = $request->type;

            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);

            //Generate reference number

            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            $inputs['payment_ref_no'] = $payment_ref_no;

            $business_location = BusinessLocation::where('business_id', $business_id)

                ->first();

            $cheque_bank = $request->cheque_bank;
            $bank_name = $request->bank_name; // Get the bank name from input
            $bank = Account::where('name', $bank_name)->first(); // Find the record where 'name' matches $bank_name
            if (!empty($bank)) {
                $inputs['account_id'] = $bank->id; // $cheque_bank ?? $request->account_id;
            } else {
                $inputs['account_id'] = $cheque_bank ?? $request->account_id;
            }
            //Upload documents if added

            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            DB::beginTransaction();

            //Add opening balance

            $transaction = $this->transactionUtil->createRefundPaymentTransaction($business_id, $contact_id, $inputs['amount'], $inputs['account_id'], $payment_type, $inputs['paid_on'], $inputs['cheque_number'], $inputs['bank_name'], $inputs['cheque_date']);

            $inputs['transaction_id'] = $transaction->id;

            unset($inputs['sale_invoice_bill_number']);

            unset($inputs['cheque_return_charges']);

            $inputs['transfer_date'] = !empty($inputs['transfer_date']) ? Carbon::parse($inputs['transfer_date'])->format('Y-m-d') : null;

            $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? Carbon::parse($inputs['cheque_date'])->format('Y-m-d') : null;

            $inputs['is_return'] = 1;

            // $parent_payment = TransactionPayment::create($inputs);

            // $contactLedger = ContactLedger::where('transaction_id', $transaction->id)->update(['transaction_payment_id' => $parent_payment->id]);

            // AccountTransaction::where('transaction_id', $transaction->id)->update(['transaction_payment_id' => $parent_payment->id]);

            DB::commit();

            if ($request->type == 'cheque_return' && !empty($transaction)) {
                $transaction->bank_name = Account::find($cheque_bank)->name ?? '';
                $transaction->contact = Contact::where('id', $transaction->contact_id)->first();
                $transaction->payment_ref_number = $payment_ref_no;
                $this->notificationUtil->autoSendNotification($business_id, 'cheque_return', $transaction, $transaction->contact);
            }


            $output = [

                'success' => true,

                'msg' => __('purchase.payment_added_success')

            ];
        } catch (\Exception $e) {

            DB::rollBack();

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];
        }

        return Redirect::back()->with(['status' => $output]);
    }

    /**

     * Shows contact's advance payment modal

     *

     * @param  int  $contact_id

     * @return \Illuminate\Http\Response

     */



    public function getSecurityDeposit($contact_id)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create')) {

            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

            $business_location_id = BusinessLocation::where('business_id', $business_id)->first()->id;

            $contact_details = Contact::where('contacts.id', $contact_id)->first();

            $payment_types = $this->transactionUtil->payment_types($business_location_id);

            unset($payment_types['credit_sale']);  // removing credit sale method from array

            $clati = 0;

            $current_liability_account_type_id = AccountType::where('name', 'Current Liabilities')->where('business_id', $business_id)->first();

            if (!empty($current_liability_account_type_id)) {

                $clati = $current_liability_account_type_id->id;
            }

            //Accounts

            $current_libility_account_id = $this->transactionUtil->account_exist_return_id('Customer Deposits');

            $current_libility_accounts = Account::where('business_id', $business_id)->where('parent_account_id', $current_libility_account_id)->where('is_closed', 0)->pluck('name', 'id');

            if (count($current_libility_accounts) == 0) {

                $current_libility_accounts = Account::where('id', $current_libility_account_id)->where('is_closed', 0)->pluck('name', 'id');
            }

            $account_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account');

            $disabled = '';

            $message = '';

            if (!$account_access) {

                $font_size = System::getProperty('customer_supplier_security_deposit_current_liability_font_size');

                $color = System::getProperty('customer_supplier_security_deposit_current_liability_color');

                $msg = System::getProperty('customer_supplier_security_deposit_current_liability_message');

                if ($contact_details->type == 'supplier' && System::getProperty('supplier_secrity_deposit_current_liability_checkbox') == 1) {

                    $message = '<p style="font-size: ' . $font_size . ';color: ' . $color . ' ">' . $msg . '</p>';
                }

                if ($contact_details->type == 'customer' && System::getProperty('customer_secrity_deposit_current_liability_checkbox') == 1) {

                    $message = '<p style="font-size: ' . $font_size . ';color: ' . $color . ' ">' . $msg . '</p>';
                }

                $disabled = 'disabled';

                $current_libility_account_id = 0;
            }

            $prefix_type = 'security_deposit';

            $ref_count = $this->transactionUtil->onlyGetReferenceCount($prefix_type, $business_id, false);

            //Generate reference number

            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            $customer_deposit_account_id = $this->transactionUtil->account_exist_return_id('Cash');

            $settings = ContactLinkedAccount::where('business_id', $business_id)->first();

            if ($contact_details->type == 'supplier') {
                if (!empty($settings)) {
                    $customer_deposit_account_id = $settings->supplier_advance;
                }
            } else {
                if (!empty($settings)) {
                    $customer_deposit_account_id = $settings->customer_advance;
                }
            }


            $accounts = Account::where('business_id', $business_id)->where('id', $customer_deposit_account_id)->where('is_closed', 0)->pluck('name', 'id');


            $security_deposit_already = Transaction::where('contact_id', $contact_id)->where('type', 'security_deposit')->first();

            return view('transaction_payment.customer_security_payment')

                ->with(compact(

                    'business_locations',

                    'business_location_id',

                    'security_deposit_already',

                    'contact_details',

                    'payment_types',

                    'accounts',

                    'payment_ref_no',

                    'contact_id',

                    'customer_deposit_account_id',

                    'current_libility_accounts',

                    'current_libility_account_id',

                    'disabled',

                    'message'

                ));
        }
    }

    /**

     * Adds Advance Payments for Contact

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */



    public function postSecurityDeposit(Request $request)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create') && !Gate::forUser(auth()->user())->check('sell.create')) {

            abort(403, 'Unauthorized action.');
        }

        try {

            $has_reviewed = $this->transactionUtil->hasReviewed($request->input('paid_on'));

            if (!empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($request->input('paid_on'), $request->input('paid_on'));

            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => "You can't add a payment for an already reviewed date",
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $business_id = request()->session()->get('user.business_id');

            $contact_id = $request->input('contact_id');

            //$contact_id = Contact::where('contact_id', $contact_id)->where('business_id', $business_id)->first()->id;
            $inputs = $request->only([
                'amount',
                'method',
                'note',
                'card_number',
                'card_holder_name',
                'card_transaction_number',
                'card_type',
                'card_month',
                'card_year',
                'card_security',
                'cheque_number',
                'bank_account_number',
                'bank_name',
                'cheque_date',
                'post_dated_cheque',
                'update_post_dated_cheque'
            ]);

            // dd($inputs);


            $selectedChequeDetails = $this->lockSelectedChequesForPayment($request->input('select_cheques', []), false);
            $hasSelectedCheques = ! empty($request->input('select_cheques')) && $selectedChequeDetails['amount'] > 0;

            if ($hasSelectedCheques) {
                $inputs['amount'] = $selectedChequeDetails['amount'];
                $inputs['cheque_number'] = $selectedChequeDetails['cheque_number'];
                $inputs['bank_name'] = $selectedChequeDetails['bank_name'];
                $request->merge(['cheque_date' => $selectedChequeDetails['cheque_date']]);
            }

            if ($inputs['method'] == 'cheque' && ! $hasSelectedCheques) {
                if (empty($inputs['cheque_number']) || empty($inputs['bank_name'])) {
                    $output = [
                        'success' => false,
                        'msg' => 'Bank name and Cheque number are required for Cheque payments'
                    ];
                    return Redirect::back()->with('status', $output);
                } else {
                    // check duplicates
                    $chequesAdded = $this->transactionUtil->checkCheques($inputs['cheque_number'], $inputs['bank_name']);

                    if ($chequesAdded > 0) {
                        $output = [
                            'success' => false,
                            'msg' => 'Cheque with the same number and bank name already exists!'
                        ];
                        return Redirect::back()->with('status', $output);
                    }
                }
            }


            $inputs['paid_on'] = $this->resolveSelectedPaymentDate($request, true);

            $inputs['amount'] = $hasSelectedCheques ? $selectedChequeDetails['amount'] : $this->transactionUtil->num_uf($inputs['amount']);

            $inputs['created_by'] = auth()->user()->id;

            $inputs['payment_for'] = $contact_id;

            $inputs['business_id'] = $request->session()->get('business.id');

            if ($inputs['method'] == 'custom_pay_1') {

                $inputs['transaction_no'] = $request->input('transaction_no_1');
            } elseif ($inputs['method'] == 'custom_pay_2') {

                $inputs['transaction_no'] = $request->input('transaction_no_2');
            } elseif ($inputs['method'] == 'custom_pay_3') {

                $inputs['transaction_no'] = $request->input('transaction_no_3');
            }

            $payment_type = $request->type;

            $inputs['payment_ref_no'] = $request->payment_ref_no;

            $payment_ref_no = $inputs['payment_ref_no'];

            $business_location = BusinessLocation::where('business_id', $business_id)

                ->first();

            $inputs['account_id'] = $request->account_id;

            //Upload documents if added

            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            DB::beginTransaction();

            $current_liability_account = $request->current_liability_account;

            //Add opening balance

            $transaction = $this->transactionUtil->createAdvancePaymentTransaction($business_id, $contact_id, $inputs['amount'], $inputs['account_id'], $payment_type, $inputs['paid_on'], $current_liability_account, $inputs);

            $date = Carbon::parse($request->input('paid_on'))->format('Y-m-d');

            if ($inputs['method'] == 'Bank') {

                $ipdcAccount = Account::where('business_id', $business_id)
                    ->where('name', 'Account Payable')
                    ->first();

                if ($ipdcAccount) {
                    $acc_tran = [
                        'account_id'     => $ipdcAccount->id,
                        'type'           => 'debit',
                        'business_id'    => $business_id,
                        'amount'         => $inputs['amount'],
                        'operation_date' => $date,
                        'created_by'     => Auth::user()->id,
                        'traction_id'    => $transaction->id,
                    ];
                    AccountTransaction::create($acc_tran);
                }
            }
            $inputs['transaction_id'] = $transaction->id;

            $contact = Contact::findOrFail($contact_id);
            $account_id = $inputs['account_id'];
            $post_dated = $this->transactionUtil->account_exist_return_id('Post Dated Cheques');
            $issued_post_dated = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');

            if ($payment_type == 'advance_payment') {
                if ($contact->type == 'customer') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $post_dated;
                    }
                }
                if ($contact->type == 'supplier') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $issued_post_dated;
                    }
                }
            }
            if ($payment_type == 'security_deposit') {
                if ($contact->type == 'customer') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $post_dated;
                    }
                }
                if ($contact->type == 'supplier') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $issued_post_dated;
                    }
                }
            }

            if ($payment_type == 'security_deposit_refund') {
                if ($contact->type == 'customer') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $issued_post_dated;
                    }
                }
                if ($contact->type == 'supplier') {

                    if (!empty($inputs) && !empty($inputs['update_post_dated_cheque'])) {
                        $inputs['related_account_id'] = $account_id;
                        $inputs['account_id'] = $post_dated;
                    }
                }
            }

            $parent_payment = TransactionPayment::create($inputs);

            AccountTransaction::where('transaction_id', '=', $transaction->id)->update(['transaction_payment_id' => $parent_payment['id']]);

            $transaction->contact = Contact::where('id', $contact_id)->first();
            $transaction->payment_ref_number = $payment_ref_no;
            $this->notificationUtil->autoSendNotification($business_id, 'payment_received', $transaction, $transaction->contact);

            // auto transfer update_post_dated_cheque
            $msg = "";
            if (!empty($inputs['update_post_dated_cheque'])) {
                $autoFundTransfer = $this->autoFundTransfer($request);
                if ($autoFundTransfer['success']) {
                    $msg = " And Auto Transferred";
                } else {
                    $msg = " And Auto Transfer Failed";
                    $autoFundTransfer['msg'] = $autoFundTransfer['msg'] . $msg;
                    return Redirect::back()->with(['status' => $autoFundTransfer]);
                }
            }

            DB::commit();

            $output = [

                'success' => true,

                'msg' => __('lang_v1.security_deposit_added_success') . $msg

            ];
        } catch (\Exception $e) {

            DB::rollBack();

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];
        }

        return Redirect::back()->with(['status' => $output]);
    }

    /**

     * Refund security deposit

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */



    public function getRefundSecurityDeposit($contact_id, Request $request)
    {

        try {

            $business_id = request()->session()->get('user.business_id');

            $refund_transaction_id = $request->refund_transaction_id;

            $refund_transaction = Transaction::find($refund_transaction_id);

            if (empty($refund_transaction)) {

                $output = [

                    'success' => 0,

                    'msg' => __('messages.something_went_wrong')

                ];

                return $output;
            }

            $amount = $request->amount;

            $account_id = $request->account_id;

            $current_liability_account = $request->current_liability_account;

            $business_location = BusinessLocation::where('business_id', $business_id)

                ->first();

            $inputs['paid_on'] = $this->resolveSelectedPaymentDate($request, true);

            $inputs['amount'] = $this->transactionUtil->num_uf($amount);

            $payment_type = 'refund_security_deposit';

            $ob_data = [

                'business_id' => $business_id,

                'location_id' => $business_location->id,

                'type' => $payment_type,

                'status' => 'final',

                'payment_status' => 'paid',

                'contact_id' => $contact_id,

                'transaction_date' => $inputs['paid_on'],

                'total_before_tax' => $inputs['amount'],

                'final_total' => $inputs['amount'],

                'return_parent_id' => $refund_transaction->id,

                'created_by' => request()->session()->get('user.id')

            ];

            //Generate reference number

            $ref_count = $this->transactionUtil->onlyGetReferenceCount($payment_type, $business_id, false);

            //Generate reference number

            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($payment_type, $ref_count);

            $ob_data['ref_no'] = $payment_ref_no;

            DB::beginTransaction();

            //Create opening balance transaction

            $transaction = Transaction::create($ob_data);

            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            $inputs['created_by'] = auth()->user()->id;

            $inputs['payment_for'] = $contact_id;

            $inputs['business_id'] = $request->session()->get('business.id');

            $inputs['account_id'] = $account_id;

            $inputs['transaction_id'] = $transaction->id;

            $inputs['method'] = $transaction->id;

            $payment = TransactionPayment::create($inputs);

            //create account transaction

            $account_transaction_data = [

                'amount' => abs($transaction->final_total),

                'account_id' => $account_id,

                'contact_id' => $contact_id,

                'operation_date' => $inputs['paid_on'],

                'created_by' => $transaction->created_by,

                'transaction_id' => $transaction->id,

                'transaction_payment_id' => $payment->id,

                'note' => null

            ];

            $account_transaction_data['type'] = 'credit';

            AccountTransaction::createAccountTransaction($account_transaction_data);

            $account_transaction_data['account_id'] = $current_liability_account;

            $account_transaction_data['type'] = 'debit';

            AccountTransaction::createAccountTransaction($account_transaction_data);

            DB::commit();

            $output = [

                'success' => 1,

                'msg' => __('lang_v1.success')

            ];
        } catch (\Exception $e) {

            DB::rollBack();

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];
        }

        return $output;
    }

    /**

     * Shows contact's payment due modal

     *

     * @param  int  $contact_id

     * @return \Illuminate\Http\Response

     */



    public function getPayContactDue($contact_id, ContactController $contactController)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create')) {

            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $pdChequePermissions = $this->getPdChequePermissions($business_id);
            $dailyCashShiftNumbers = PetroDailyShift::where('business_id', $business_id)
                // ->where(function($query) {
                //     $query ->WhereRaw('CHAR_LENGTH(pump_operator_pending) >= 1');
                // })
                ->where('status', 0)
                ->pluck('shift_no');

            $dailyCashShiftNumbers = $dailyCashShiftNumbers->unique()->toArray();

            $due_payment_type = request()->input('type');

            $query = Contact::where('contacts.id', $contact_id)
                ->join('transactions AS t', 'contacts.id', '=', 't.contact_id');

            if ($due_payment_type == 'purchase') {

                $query->select(
                    DB::raw("SUM(IF(t.type = 'purchase', final_total, 0)) as total_purchase"),
                    DB::raw("SUM(IF(t.type = 'purchase', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND transaction_payments.deleted_at IS NULL), 0)) as total_paid"),
                    'contacts.name',
                    'contacts.supplier_business_name',
                    'contacts.id as contact_id',
                    't.transaction_date'
                );
            } elseif ($due_payment_type == 'purchase_return') {

                $query->select(
                    DB::raw("SUM(IF(t.type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                    DB::raw("SUM(IF(t.type = 'purchase_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND transaction_payments.deleted_at  IS NULL), 0)) as total_return_paid"),
                    'contacts.name',
                    'contacts.supplier_business_name',
                    'contacts.id as contact_id'
                );
            } elseif ($due_payment_type == 'sell') {

                $query->select(
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                    DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND transaction_payments.deleted_at  IS NULL), 0)) as total_paid"),
                    DB::raw("SUM(IF(t.type = 'cheque_return' AND t.status = 'final', final_total, 0)) as total_cheque_return"),
                    DB::raw("SUM(IF(t.type = 'cheque_return' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND transaction_payments.deleted_at  IS NULL  AND is_return=0), 0)) as total_paid_cheque_return"),
                    'contacts.name',
                    'contacts.supplier_business_name',
                    'contacts.id as contact_id'
                );
            } elseif ($due_payment_type == 'sell_return') {

                $query->select(
                    DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                    DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND transaction_payments.deleted_at  IS NULL), 0)) as total_return_paid"),
                    'contacts.name',
                    'contacts.supplier_business_name',
                    'contacts.id as contact_id'
                );
            }

            //Query for opening balance details

            $query->addSelect(
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND transaction_payments.deleted_at  IS NULL), 0)) as opening_balance_paid")
            );

            $contact_details = $query->first();

            $payment_line = new TransactionPayment();

            $amount_formated = $this->transactionUtil->num_f(strval($contactController->get_cus_due_bal($contact_id, false)));

            if ($due_payment_type == 'purchase') {

                $contact_details->total_purchase = empty($contact_details->total_purchase) ? 0 : $contact_details->total_purchase;

                $payment_line->amount = $contactController->get_cus_due_bal($contact_id, false);

                $prefix_type = 'purchase_payment';
            } elseif ($due_payment_type == 'purchase_return') {

                $payment_line->amount = $contact_details->total_purchase_return -

                    $contact_details->total_return_paid;

                $amount_formated = $this->transactionUtil->num_f($payment_line->amount);

                $prefix_type = 'purchase_payment';
            } elseif ($due_payment_type == 'sell') {
                $contact_details->total_invoice = empty($contact_details->total_invoice) ? 0 : $contact_details->total_invoice;
                $contact_details->total_cheque_return = empty($contact_details->total_cheque_return) ? 0 : $contact_details->total_cheque_return;
                $cheque_return_amount = $contact_details->total_cheque_return - $contact_details->total_paid_cheque_return;
                $payment_line->amount = $contactController->get_cus_due_bal($contact_id, false);
                $prefix_type = 'sell_payment';
            } elseif ($due_payment_type == 'sell_return') {
                $payment_line->amount = $contact_details->total_sell_return - $contact_details->total_return_paid;
                $amount_formated = $this->transactionUtil->num_f($payment_line->amount);
                $prefix_type = 'sell_payment';
            }

            //If opening balance due exists add to payment amount
            $contact_details->opening_balance = !empty($contact_details->opening_balance) ? $contact_details->opening_balance : 0;
            $contact_details->opening_balance_paid = !empty($contact_details->opening_balance_paid) ? $contact_details->opening_balance_paid : 0;
            $ob_due = $contact_details->opening_balance - $contact_details->opening_balance_paid;

            $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');
            $business_location_id = BusinessLocation::where('business_id', $business_id)->first()->id;
            $contact_details->total_paid = empty($contact_details->total_paid) ? 0 : $contact_details->total_paid;
            $payment_line->method = 'cash';
            $payment_line->paid_on = Carbon::now()->toDateTimeString();


            if ($due_payment_type == 'purchase') {
                $payment_types = $this->transactionUtil->payment_types($business_location_id, false, false, false, false, true, "is_purchase_enabled");
            } else {
                $payment_types = $this->transactionUtil->payment_types($business_location_id, false, false, false, false, true, "is_sale_enabled");
            }

            $payment_types = $this->sanitizeContactPaymentTypes($payment_types);

            $location_id = $business_location_id ?? null;

            //Accounts

            $accounts = $this->moduleUtil->accountsDropdown($business_id, true);
            $ref_count = $this->transactionUtil->onlyGetReferenceCount($prefix_type, $business_id, false);

            //Generate reference number

            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            // SYZ-SUPPLIER-PAYMENT-REF-20260910-V3
            // Preview only. The final SLP number is generated again inside POST's DB transaction.
            if ($due_payment_type === 'purchase'
                && Contact::where('id', $contact_id)->value('type') === 'supplier') {
                $payment_ref_no = app(\App\Services\SupplierPaymentReferenceService::class)
                    ->preview('SLP', (int) $business_id, $payment_line->paid_on ?? now());
            }

            $payment_line->amount = $this->get_due_bal($contact_id);


            return view('transaction_payment.pay_supplier_due_modal')
                ->with(compact(
                    'location_id',
                    'business_locations',
                    'business_location_id',
                    'contact_details',
                    'payment_types',
                    'payment_line',
                    'due_payment_type',
                    'ob_due',
                    'amount_formated',
                    'accounts',
                    'payment_ref_no',
                    'dailyCashShiftNumbers',
                    'pdChequePermissions'
                ));
        }
    }

    // Get Cheque Numbers for pay amount due modal

    public function getChequeNumbers($contact_id)
    {
        // Check if the user has the 'purchase.create' permission
        if (!Gate::forUser(auth()->user())->check('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        // Fetch the cheque numbers for the given contact_id
        $chequeNumbers = TransactionPayment::join('transactions', 'transaction_payments.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'pending')
            ->where('transaction_payments.payment_type', 'pre_payment')
            ->where('transaction_payments.method', 'cheque')
            ->where('transactions.contact_id', $contact_id)
            ->pluck('transaction_payments.cheque_number', 'transaction_payments.id');  // Get cheque_number and id

        // Return the result as a JSON response
        return response()->json($chequeNumbers);
    }


    public function getPayVatDue($statement_id, ContactController $contactController)
    {

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            $due_payment_type = request()->input('type');

            $payment_line = new TransactionPayment();

            $payment_line->method = 'cash';

            $payment_line->paid_on = Carbon::now()->toDateTimeString();

            $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

            $business_location_id = BusinessLocation::where('business_id', $business_id)->first()->id;


            $payment_types = $this->transactionUtil->payment_types($business_location_id);

            unset($payment_types['credit_sale']);  // removing credit sale method from array

            //Accounts

            $prefix_type = 'sell_payment';

            $accounts = $this->moduleUtil->accountsDropdown($business_id, true);
            $ref_count = $this->transactionUtil->onlyGetReferenceCount($prefix_type, $business_id, false);

            //Generate reference number

            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            // Get VAT Statement details and calculate amount
            try {
                $vat_statement = VatCustomerStatement::findOrFail($statement_id);
                $vat_statement_amount = 0;

                // Calculate the total amount from VAT statement details
                if ($vat_statement) {
                    $vat_statement_details = VatCustomerStatementDetail::where('statement_id', $statement_id)->get();
                    foreach ($vat_statement_details as $detail) {
                        $transaction = Transaction::find($detail->transaction_id);
                        if ($transaction && $transaction->payment_status != 'paid') {
                            $vat_statement_amount += $transaction->final_total - $transaction->amount_paid;
                        }
                    }

                    // If no unpaid transactions found, check if there's a direct amount on the VAT statement
                    if ($vat_statement_amount == 0 && isset($vat_statement->total_amount)) {
                        $vat_statement_amount = $vat_statement->total_amount;
                    }
                }

                // Ensure amount is not negative and set in payment_line
                $vat_statement_amount = max(0, $vat_statement_amount);
                $payment_line->amount = $vat_statement_amount;

                // Log the calculated amount for debugging
                Log::info('VAT Statement Amount Calculated', [
                    'statement_id' => $statement_id,
                    'calculated_amount' => $vat_statement_amount,
                    'vat_statement_details_count' => $vat_statement_details ? $vat_statement_details->count() : 0
                ]);
            } catch (\Exception $e) {
                // If VAT statement not found, set amount to 0
                $vat_statement_amount = 0;
                $payment_line->amount = 0;

                // Log the error for debugging
                Log::error('Error calculating VAT Statement Amount', [
                    'statement_id' => $statement_id,
                    'error' => $e->getMessage()
                ]);
            }

            return view('transaction_payment.pay_supplier_vat_modal')

                ->with(compact('payment_types', 'payment_line', 'due_payment_type', 'accounts', 'payment_ref_no', 'business_locations', 'business_location_id', 'statement_id', 'vat_statement_amount'));
        }
    }



    public function get_due_bal($contact_id)
    {

        $start_date = date('Y-m-d');

        $end_date = date('Y-m-d');

        $business_id = request()->session()->get('user.business_id');

        $asset_account_id = Account::leftjoin('account_types', 'accounts.account_type_id', 'accounts.id')

            ->where('account_types.name', 'like', '%Assets%')

            ->where('accounts.business_id', $business_id)

            ->pluck('accounts.id')->toArray();

        $contact = Contact::find($contact_id);

        $business_details = $this->businessUtil->getDetails($contact->business_id);

        $location_details = BusinessLocation::where('business_id', $contact->business_id)->first();

        $opening_balance = Transaction::where('contact_id', $contact_id)->where('type', 'opening_balance')->where('payment_status', 'due')->sum('final_total');

        if ($contact->type == 'customer') {

            $opening_amount = ''; // ONLY SHOW OPENING BALANCE WHEN NO SALES AND PAYMENT

            $opening_balance_new = DB::select("select `cl`.`amount` as opening_balance

                    from `contact_ledgers` cl left join `transactions` t on `cl`.`transaction_id` = `t`.`id`

                    left join `business_locations` bl on `t`.`location_id` = `bl`.`id`

                    where `cl`.`contact_id` = " . $contact_id . "

                    and `cl`.`type` = 'debit'

                    and `t`.`business_id` = " . $business_id . "

                    and `t`.`type` = 'opening_balance'

                    and date(`cl`.`operation_date`) >= '" . $start_date . "'

                    and date(`cl`.`operation_date`) <= '" . $end_date . "'

                    order by `cl`.`operation_date` limit 2");

            if (count($opening_balance_new) <= 1) {

                $opening_amount = DB::select(" select (select(0 - IFNULL(amount,0))) as opening_balance 

                    from `contact_ledgers` where contact_id = '$contact_id' order by created_at ASC limit 1");

                if (count($opening_balance_new) == 0) {

                    $opening_balance_new = DB::select(" select ( select

                        sum(`bc_cl`.`amount`) as total_paid

                        from `contact_ledgers` bc_cl left join `transactions` bc_t on `bc_cl`.`transaction_id` = `bc_t`.`id`

                        left join `business_locations` bc_bl on `bc_t`.`location_id` = `bc_bl`.`id`

                        where `bc_cl`.`contact_id` =  " . $contact_id . "

                        and `bc_cl`.`type` = 'credit'

                        and `bc_t`.`business_id` = " . $business_id . "

                        and date(`bc_cl`.`operation_date`)  <= '" . $start_date . "'

                        group by `bc_cl`.`id` and `bc_cl`.`contact_id` order by bc_cl.operation_date) as before_purchase,

                        (select sum(`cl`.`amount`)

                        from `contact_ledgers` cl left join `transactions` t on `cl`.`transaction_id` = `t`.`id`

                        left join `business_locations` bl on `t`.`location_id` = `bl`.`id`

                            where `cl`.`contact_id` = " . $contact_id . "

                            and `cl`.`type` = 'debit'

                            and `t`.`business_id` = " . $business_id . "

                            and date(`cl`.`operation_date`) < '" . $start_date . "'

                            group by `cl`.`id` and `cl`.`contact_id` order by cl.operation_date)  as before_sell,

                        (select(IFNULL(before_sell,0) - IFNULL(before_purchase,0))) as opening_balance");
                }
            } else {

                $opening_balance_new = DB::select("select `cl`.`amount` as opening_balance

                    from `contact_ledgers` cl left join `transactions` t on `cl`.`transaction_id` = `t`.`id`

                    left join `business_locations` bl on `t`.`location_id` = `bl`.`id`

                    where `cl`.`contact_id` = " . $contact_id . "

                    and `cl`.`type` = 'debit'

                    and `t`.`business_id` = " . $business_id . "

                    and `t`.`type` = 'opening_balance'

                    and date(`cl`.`operation_date`) >= '" . $start_date . "'

                    and date(`cl`.`operation_date`) <= '" . $end_date . "'

                    order by `cl`.`operation_date`");
            }

            $total_sell = DB::select("select

                sum(`bc_cl`.`amount`) as total_sell

                from `contact_ledgers` bc_cl left join `transactions` bc_t on `bc_cl`.`transaction_id` = `bc_t`.`id`

               left join `business_locations` bc_bl on `bc_t`.`location_id` = `bc_bl`.`id`

               where `bc_cl`.`contact_id` =  " . $contact_id . "

               and `bc_cl`.`type` = 'debit'

               and `bc_t`.`type` != 'opening_balance'

               and `bc_t`.`business_id` = " . $business_id . "

               and date(`bc_cl`.`operation_date`)  >= '" . $start_date . "'

               and date(`bc_cl`.`operation_date`)  <= '" . $end_date . "'

               group by `bc_cl`.`id` and `bc_cl`.`contact_id` ");

            $ledger_details['total_invoice'] = count($total_sell) > 0 ? $total_sell[0]->total_sell : 0;

            $ledger_details['opening'] = $opening_amount;

            //$GLOBALS['n'] = $array($ledger_details['balance_due'], $contact_id);



        }
        $query = ContactLedger::leftjoin('transactions', 'contact_ledgers.transaction_id', 'transactions.id')

            ->leftjoin('business_locations', 'transactions.location_id', 'business_locations.id')

            ->leftjoin('transaction_payments', 'contact_ledgers.transaction_payment_id', 'transaction_payments.id')

            ->where('contact_ledgers.contact_id', $contact_id)

            ->where('transactions.business_id', $business_id)

            ->select(

                'contact_ledgers.*',

                'contact_ledgers.type as acc_transaction_type',

                'business_locations.name as location_name',

                'transactions.sub_type as t_sub_type',

                'transactions.final_total',

                'transactions.ref_no',

                'transactions.invoice_no',

                'transactions.is_direct_sale',

                'transactions.is_credit_sale',

                'transactions.is_settlement',

                'transactions.transaction_date',

                'transactions.payment_status',

                'transactions.pay_term_number',

                'transactions.pay_term_type',

                'transactions.type as transaction_type',

                'transaction_payments.method as payment_method',

                'transaction_payments.transaction_id as tp_transaction_id',

                'transaction_payments.paid_on',

                'transaction_payments.bank_name',

                'transaction_payments.cheque_date',

                'transaction_payments.cheque_number',

            )->groupBy('contact_ledgers.id')->orderBy('contact_ledgers.id', 'asc');

        if (!empty($start_date) && !empty($end_date)) {

            $query->whereDate('contact_ledgers.operation_date', '>=', $start_date);

            $query->whereDate('contact_ledgers.operation_date', '<=', $end_date);
        }

        $query->orderby('contact_ledgers.operation_date');

        // $query->skip(0)->take(5);

        // $ledger_transactions = $query->get();

        $ledger_transactions = $query->get();

        // if ($contact->type == 'customer') {

        //     $total_paid = 0;

        //     foreach($ledger_transactions->toArray() as $val) {

        //        if($val['acc_transaction_type'] == 'credit') {

        //             if(!empty($val['transaction_payment_id'])){

        //                 $transaction_payment = TransactionPayment::where('id', $val['transaction_payment_id'])->withTrashed()->first();

        //             }

        //             $amount = 0;

        //             if(!empty($transaction_payment)){

        //                 if(empty($transaction_payment->transaction_id)){ // if empty then it will be parent payment

        //                     $amount = $transaction_payment->amount;  // show parent transaction payment amount

        //                 }else{

        //                     $amount = $val['amount']; // get the amount from contact ledger if not a payment

        //                 }

        //             }else{

        //                 $amount = $val['amount'];

        //             }

        //             $total_paid = $total_paid + $amount;

        //        }

        //     }

        //     $dateTimestamp1 = date('Y-m-d',strtotime($contact->created_at));

        //     // $dateTimestamp2 = strtotime($date2);

        //     $ledger_details['total_paid'] = $total_paid;

        //     $ledger_details['bf_balance'] = $ledger_details['beginning_balance'] = count($opening_balance_new) > 0 ? $opening_balance_new[0]->opening_balance : 0;

        //     if(!empty($start_date) && $dateTimestamp1 >= $start_date){

        //         $ledger_details['bf_balance'] = 0;

        //     }

        //     // $ledger_details['beginning_balance'] = count($bg_bl) > 0 ? $bg_bl[0]->opening_balance : 0;

        //     $ledger_details['balance_due'] = $ledger_details['beginning_balance'] + $ledger_details['total_invoice'] - $ledger_details['total_paid'];

        //     // dd($ledger_details);

        //     return  $ledger_details['balance_due'];

        // }

        if ($contact->type == 'customer') {

            $total_paid = $skipped_cr = 0;

            $dateTimestamp1 = date('Y-m-d', strtotime($contact->created_at));

            foreach ($ledger_transactions->toArray() as $val) {

                if ($val['acc_transaction_type'] == 'credit') {

                    if (!empty($val['transaction_payment_id'])) {

                        $transaction_payment = TransactionPayment::where('id', $val['transaction_payment_id'])->withTrashed()->first();
                    }

                    $amount = 0;

                    if (!empty($transaction_payment)) {

                        if (empty($transaction_payment->transaction_id)) { // if empty then it will be parent payment

                            $amount = $transaction_payment->amount;  // show parent transaction payment amount

                        } else {

                            $amount = $val['amount']; // get the amount from contact ledger if not a payment

                        }
                    } else {

                        $amount = $val['amount'];
                    }

                    if ($val['transaction_type'] == 'opening_balance') {

                        $dateTimestamp1 = date('Y-m-d', strtotime($val['transaction_date']));

                        $skipped_cr += $amount;

                        continue;
                    }

                    $total_paid = $total_paid + $amount;
                }
            }

            // dd($dateTimestamp1);

            // $dateTimestamp2 = strtotime($date2);

            $ledger_details['total_paid'] = $total_paid;

            $ledger_details['bf_balance'] = $ledger_details['beginning_balance'] = count($opening_balance_new) > 0 ? $opening_balance_new[0]->opening_balance : 0;

            // $ledger_details['beginning_balance'] = count($bg_bl) > 0 ? $bg_bl[0]->opening_balance : 0;

            $ledger_details['balance'] = $ledger_details['balance_due'] = $ledger_details['beginning_balance'] + $ledger_details['total_invoice'] - ($ledger_details['total_paid']);

            // dd($ledger_details);

            //    dd($ledger_details);

            if (!empty($start_date) && $dateTimestamp1 >= $start_date) {

                $ledger_details['bf_balance'] = 0;

                $ledger_details['balance'] = 0;
            }

            if (!empty($start_date) && $dateTimestamp1 > $start_date) {

                echo "Inside Con<br>";

                $ledger_details['beginning_balance'] = 0;

                $ledger_details['balance_due'] -= $skipped_cr;
            } else {

                // $ledger_details['balance'] -=  $skipped_cr ;

            }

            return $ledger_details['balance_due'];
        }

        return 0;
    }

    /**

     * Adds Payments for Contact due

     *

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */



    public function postPayContactDue(Request $request)
    {

        /*
         * S631 TRACE. Deliberately unconditional and at WARNING level so it
         * appears whatever the log level is set to.
         *
         * Its job is to answer, in one payment, a question we could not answer
         * before: did this code run at all? If you make a payment on this page
         * and this line is NOT in laravel.log, then the deployed files are not
         * these files - opcache, a stale copy, or the request going somewhere
         * else entirely - and no amount of fixing in here will change anything.
         *
         * Remove these three TRACE lines once S631 is confirmed working.
         */
        Log::warning('S631 TRACE postPayContactDue reached.', ['contact_id' => $request->input('contact_id') ?? ($contact_id ?? null)]);

        if (!Gate::forUser(auth()->user())->check('purchase.create') && !Gate::forUser(auth()->user())->check('sell.create')) {

            abort(403, 'Unauthorized action.');
        }

        $business_id = $this->resolveContactPaymentBusinessId($request);

        try {
            $contact = $this->resolveContactForPayment($request->input('contact_id'), $business_id);
            $contact_id = (int) $contact->id;
            $has_reviewed = $this->transactionUtil->hasReviewed($request->input('paid_on'));
            if (!empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($request->input('paid_on'), $request->input('paid_on'));

            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => "You can't add a payment for an already reviewed date",
                ];

                return Redirect::back()->with(['status' => $output]);
            }


            $inputs = $request->only([
                'amount',
                'method',
                'note',
                'card_number',
                'card_holder_name',
                'shift_number',
                'card_transaction_number',
                'card_type',
                'card_month',
                'card_year',
                'card_security',
                'cheque_number',
                'bank_account_number',
                'bank_name',
                'post_dated_cheque',
                'update_post_dated_cheque'

            ]);

            // Normalize PD cheque flags (may be array when both hidden and checkbox submit)
            $pd = $inputs['post_dated_cheque'] ?? 0;
            $inputs['post_dated_cheque'] = (is_array($pd) ? max(array_map('intval', $pd)) : (int) $pd) ? 1 : 0;
            $upd = $inputs['update_post_dated_cheque'] ?? 0;
            $inputs['update_post_dated_cheque'] = (is_array($upd) ? max(array_map('intval', $upd)) : (int) $upd) ? 1 : 0;

            $postDatedCheque = (bool) $inputs['post_dated_cheque'];
            $updatePostDatedCheque = (bool) $inputs['update_post_dated_cheque'];

            $selectedChequeDetails = $this->lockSelectedChequesForPayment($request->input('select_cheques', []), false);
            $hasSelectedCheques = ! empty($request->input('select_cheques')) && $selectedChequeDetails['amount'] > 0;

            if ($hasSelectedCheques) {
                $inputs['amount'] = $selectedChequeDetails['amount'];
                $inputs['cheque_number'] = $selectedChequeDetails['cheque_number'];
                $inputs['bank_name'] = $selectedChequeDetails['bank_name'];
                $request->merge(['cheque_date' => $selectedChequeDetails['cheque_date']]);
            }

            if ($inputs['method'] == 'cheque' && ! $hasSelectedCheques) {
                if (empty($inputs['cheque_number']) || empty($inputs['bank_name'])) {
                    $output = [
                        'success' => false,
                        'msg' => 'Bank name and Cheque number are required for Cheque payments'
                    ];
                    return Redirect::back()->with('status', $output);
                } else {
                    // check duplicates
                    $chequesAdded = $this->transactionUtil->checkCheques($inputs['cheque_number'], $inputs['bank_name']);

                    if ($chequesAdded > 0) {
                        $output = [
                            'success' => false,
                            'msg' => 'Cheque with the same number and bank name already exists!'
                        ];
                        return Redirect::back()->with('status', $output);
                    }
                }
            }

            $inputs['paid_on'] = $this->resolveSelectedPaymentDate($request, true);
            if (empty($inputs['paid_on'])) {
                try {
                    $inputs['paid_on'] = Carbon::parse($request->input('paid_on'))->format('Y-m-d H:i:s');
                } catch (\Throwable $exception) {
                    $inputs['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
                }
            }

            $inputs['amount'] = $hasSelectedCheques ? $selectedChequeDetails['amount'] : $this->transactionUtil->num_uf($inputs['amount']);

            if ($inputs['amount'] <= 0) {
                return Redirect::back()->with('status', [
                    'success' => false,
                    'msg' => 'Payment amount must be greater than zero.',
                ]);
            }

            if (empty($request->input('account_id'))) {
                return Redirect::back()->with('status', [
                    'success' => false,
                    'msg' => 'Please select a payment account.',
                ]);
            }

            $inputs['created_by'] = auth()->user()->id;
            $inputs['payment_for'] = $contact_id;
            $inputs['business_id'] = $business_id;
            $inputs['cheque_date'] = !empty($request->cheque_date) ? $this->transactionUtil->uf_date($request->cheque_date) : null;

            if ($inputs['method'] == 'custom_pay_1') {
                $inputs['transaction_no'] = $request->input('transaction_no_1');
            } elseif ($inputs['method'] == 'custom_pay_2') {
                $inputs['transaction_no'] = $request->input('transaction_no_2');
            } elseif ($inputs['method'] == 'custom_pay_3') {
                $inputs['transaction_no'] = $request->input('transaction_no_3');
            }

            $due_payment_type = $request->input('due_payment_type');

            // SYZ-SUPPLIER-PAYMENT-REF-20260910-V3
            $isSupplierPayDue = $contact->type === 'supplier' && $due_payment_type === 'purchase';
            $prefix_type = 'purchase_payment';

            if (in_array($due_payment_type, ['sell', 'sell_return'])) {
                $prefix_type = 'sell_payment';
            }

            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);

            //Generate reference number

            // $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            $payment_ref_no = $request->input('payment_ref_no');
            $inputs['payment_ref_no'] = $payment_ref_no;

            if (!empty($request->input('account_id'))) {
                $inputs['account_id'] = $request->input('account_id');
            }

            $cashAccountIds = Account::leftjoin('account_groups', 'accounts.asset_type', 'account_groups.id')
                ->where('account_groups.name', 'Cash Account')
                ->where('accounts.business_id', $business_id)
                ->pluck('accounts.id')
                ->toArray();

            // Cash-balance validation only applies when this due-payment flow is an outgoing payment (cash leaving).
            $isOutgoingDuePayment = false;
            if ($contact->type === 'supplier') {
                $isOutgoingDuePayment = in_array($due_payment_type, ['purchase', 'purchase_return'], true);
            } elseif ($contact->type === 'customer') {
                $isOutgoingDuePayment = ($due_payment_type === 'sell_return');
            } else {
                $isOutgoingDuePayment = in_array($due_payment_type, ['purchase', 'purchase_return', 'sell_return'], true);
            }

            if ($isOutgoingDuePayment && !empty($inputs['account_id']) && in_array((int) $inputs['account_id'], $cashAccountIds)) {
                $balance = Account::getAccountBalance($inputs['account_id']);

                if ($balance < $inputs['amount']) {
                    $output = [
                        'success' => 0,
                        'msg'     => 'Insufficient balance in the selected cash account',
                    ];

                    return Redirect::back()->with('status', $output);
                }
            }

            //Upload documents if added
            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');
            $inputs['paid_in_type'] = 'customer_page';

            DB::beginTransaction();

            // SYZ-SUPPLIER-PAYMENT-REF-20260910-V3
            // Ignore any browser-posted ref for Supplier Pay Due and allocate the next SLP under DB lock.
            if ($isSupplierPayDue) {
                $payment_ref_no = app(\App\Services\SupplierPaymentReferenceService::class)
                    ->next('SLP', (int) $business_id, $inputs['paid_on']);
                $inputs['payment_ref_no'] = $payment_ref_no;
            }

            if ($hasSelectedCheques) {
                $this->lockSelectedChequesForPayment($request->input('select_cheques', []));
            }

            $post_dated = $this->transactionUtil->account_exist_return_id('Post Dated Cheques');
            $issued_post_dated = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');
            $usePdChequeAccount = $updatePostDatedCheque || $postDatedCheque;
            if ($usePdChequeAccount) {
                $inputs['related_account_id'] = $request->input('account_id');

                if ($contact->type == 'supplier') {
                    // For supplier transactions, use Issued Post Dated Cheques so it shows in that account book
                    $inputs['account_id'] = $issued_post_dated;
                }

                if ($contact->type == 'customer') {
                    if ($due_payment_type == 'sell_return') {
                        $inputs['account_id'] = $issued_post_dated;
                    } else {
                        $inputs['account_id'] = $post_dated;
                    }
                }
            }

            $parent_payment = TransactionPayment::create($inputs);
            $account_payable = Account::where('business_id', $business_id)
                ->whereIn('name', ['Accounts Payable', 'Account Payable'])
                ->where('is_closed', 0)
                ->first();

            if (empty($account_payable)) {
                throw new \RuntimeException('Accounts Payable account is not configured for this business.');
            }

            $account_payable_id = (int) $account_payable->id;

            $note_for_account_book = !empty($inputs['note']) ? $inputs['note'] : null;
            if ($contact->type === 'customer') {
                $customer_note_prefix = 'Customer Name: ' . ($contact->name ?? '');
                $note_for_account_book = !empty($note_for_account_book)
                    ? ($customer_note_prefix . ' | ' . $note_for_account_book)
                    : $customer_note_prefix;
            }

            $account_transaction_data = [
                'business_id' => $business_id,
                'contact_id' => $contact_id,
                'amount' => $parent_payment->amount,
                'account_id' => $parent_payment->account_id,
                'type' => 'credit',
                'operation_date' => $inputs['paid_on'],
                'created_by' => Auth::user()->id,
                'transaction_payment_id' => $parent_payment->id,
                'note' => $note_for_account_book,
                'post_dated_cheque' => $postDatedCheque,
                'update_post_dated_cheque' => $updatePostDatedCheque
            ];

            // SYZ-SUPPLIER-PAYMENT-REF-20260910-V3
            // createAccountTransaction() and createSupplierPaymentLedger() both receive this same data array.
            if ($isSupplierPayDue) {
                $account_transaction_data['reff_no'] = $payment_ref_no;
            }
            // 'operation_date' =>!empty($parent_payment->cheque_date) ? $parent_payment->cheque_date : $parent_payment->paid_on,//this code replace it does not show the specific date report
            $location_id = BusinessLocation::where('business_id', $business_id)->first();

            if (!($contact->type == 'supplier' && $usePdChequeAccount)) {
                $account_transaction_data['account_id'] = $request->account_id;
            }

            if ($contact->type == 'supplier') {
                if ($due_payment_type == 'purchase_return') {
                    $purchase_return_due = Transaction::where('contact_id', $contact_id)->whereIn('type', ['purchase_return'])->whereIn('payment_status', ['due', 'partial'])->first();

                    $transaction = $purchase_return_due;

                    $account_transaction_data['account_id'] = $request->account_id;

                    // $account_transaction_data['transaction_id'] = !empty($purchase_return_due) ? $purchase_return_due->id : null;

                    if ($usePdChequeAccount) {
                        $account_transaction_data['related_account_id'] = $request->input('account_id');
                        $account_transaction_data['account_id'] = $post_dated;
                    }

                    $account_transaction_data['type'] = 'debit';

                    $account_transaction_data['sub_type'] = 'payment';

                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_transaction_data['account_id'] = $account_payable_id;

                    $account_transaction_data['type'] = 'credit';

                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_transaction_data['type'] = 'credit';

                    $this->createSupplierPaymentLedger($account_transaction_data, 'Pay due Amount');
                } else {
                    $due_transaction_id = Transaction::where('contact_id', $contact_id)->whereIn('type', $this->contactUtil->payable_supplier_txns)->whereIn('payment_status', ['due', 'partial'])->first();
                    $transaction = $due_transaction_id;

                    if ($inputs['method'] == 'bank_transfer' || $inputs['method'] == 'direct_bank_deposit') {

                        $account_transaction_data['account_id'] = $inputs['account_id'];
                    }

                    // $account_transaction_data['transaction_id'] = !empty($due_transaction_id) ? $due_transaction_id->id : null;

                    $account_transaction_data['type'] = 'credit';

                    if ($usePdChequeAccount) {
                        $account_transaction_data['related_account_id'] = $request->input('account_id');
                        $account_transaction_data['account_id'] = $issued_post_dated;
                    }

                    if ($inputs['method'] == 'Bank') {

                        $ipdcAccount = Account::where('business_id', $business_id)
                            ->where('name', 'Account Payable')
                            ->first();

                        if ($ipdcAccount) {
                            $acc_tran = [
                                'account_id'     => $ipdcAccount->id,
                                'type'           => 'debit',
                                'business_id'    => $business_id,
                                'amount'         => $inputs['amount'],
                                'created_by'     => Auth::user()->id,
                                'traction_id'    => !empty($transaction) ? $transaction->id : null,
                            ];
                            AccountTransaction::create($acc_tran);
                        }

                        if ($usePdChequeAccount) {
                            $bankAccount = Account::where('business_id', $business_id)
                                ->where('name', 'Bank Account')
                                ->first();

                            if ($bankAccount) {
                                $acc_tran = [
                                    'account_id'     => $bankAccount->id,
                                    'type'           => 'credit',
                                    'business_id'    => $business_id,
                                    'amount'         => $inputs['amount'],
                                    'created_by'     => Auth::user()->id,
                                    'traction_id'    => !empty($transaction) ? $transaction->id : null,
                                ];
                                AccountTransaction::create($acc_tran);
                            }
                        } else {
                            $account_transaction_data['account_id'] = $request->input('account_id');
                        }
                    }


                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_transaction_data['account_id'] = $account_payable_id;

                    $account_transaction_data['type'] = 'debit';

                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_transaction_data['sub_type'] = 'payment';

                    $this->createSupplierPaymentLedger($account_transaction_data, 'Pay due Amount');
                }
            }

            if ($contact->type == 'customer') {

                if ($due_payment_type == 'sell_return') {

                    $sell_return_due = Transaction::where('contact_id', $contact_id)->whereIn('type', ['sell_return'])->whereIn('payment_status', ['due', 'partial'])->first();

                    $transaction = $sell_return_due;

                    $account_transaction_data['account_id'] = $request->account_id;

                    // $account_transaction_data['transaction_id'] = !empty($sell_return_due) ? $sell_return_due->id : null;

                    $account_transaction_data['type'] = 'debit';

                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_transaction_data['sub_type'] = 'payment';

                    ContactLedger::createContactLedger($account_transaction_data, 'Pay due Amount');
                } else {

                    $due_transaction_id = Transaction::where('contact_id', $contact_id)->whereIn('type', $this->contactUtil->payable_customer_txns)->whereIn('payment_status', ['due', 'partial'])->first();

                    $transaction = $due_transaction_id;

                    // $account_transaction_data['transaction_id'] = !empty($due_transaction_id) ? $due_transaction_id->id : null;

                    $account_transaction_data['type'] = 'debit';

                    if ($updatePostDatedCheque) {
                        $account_transaction_data['related_account_id'] = $request->input('account_id');
                        $account_transaction_data['account_id'] = $post_dated;
                    }


                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_receivable = Account::where('business_id', $business_id)->where('name', 'Accounts Receivable')->where('is_closed', 0)->first();

                    $account_receivable_id = !empty($account_receivable) ? $account_receivable->id : 0;

                    $account_transaction_data['account_id'] = $account_receivable_id;

                    $account_transaction_data['type'] = 'credit';

                    $account_transaction_data['sub_type'] = 'ledger_show';

                    AccountTransaction::createAccountTransaction($account_transaction_data);

                    $account_transaction_data['contact_id'] = $contact_id;

                    $account_transaction_data['sub_type'] = 'payment';

                    ContactLedger::createContactLedger($account_transaction_data, 'Pay due Amount');
                }
            }

            //Distribute above payment among unpaid transactions

            $this->transactionUtil->payAtOnce($parent_payment, $due_payment_type);

            // SYZ-SUPPLIER-PAYMENT-REF-20260910-V3
            if ($isSupplierPayDue) {
                TransactionPayment::where('parent_id', $parent_payment->id)
                    ->update(['payment_ref_no' => $payment_ref_no]);
            }

            // auto transfer update_post_dated_cheque
            $msg = "";
            if ($updatePostDatedCheque) {
                $autoFundTransfer = $this->autoFundTransfer($request);
                if ($autoFundTransfer['success']) {
                    $msg = " And Auto Transferred";
                } else {
                    $msg = " And Auto Transfer Failed";
                    $autoFundTransfer['msg'] = $autoFundTransfer['msg'] . $msg;
                    return Redirect::back()->with(['status' => $autoFundTransfer]);
                }
            }

            DB::commit();

            /*
             |------------------------------------------------------------------
             | S631: this is the Customer Register -> Action -> Pay due Amount /
             | Advance Payment path, and it was the reason no SMS arrived and no
             | line appeared in laravel.log.
             |
             | WHAT WAS HAPPENING
             |   $transaction is filled a few hundred lines above from:
             |
             |       Transaction::where('contact_id', $contact_id)
             |           ->whereIn('payment_status', ['due', 'partial'])->first();
             |
             |   i.e. the customer's oldest OUTSTANDING invoice. On an Advance
             |   Payment there is no outstanding invoice, so that returns null,
             |   and the old guard - `if (!empty($transaction))` - skipped the
             |   notification entirely. Silently: no SMS, no error, and nothing
             |   written to the log, which is exactly what was reported.
             |
             |   It also skipped whenever a customer paid while their dues were
             |   already settled by other means.
             |
             | THE FIX
             |   CustomerPaymentSimpleController already solved this by building a
             |   throwaway Transaction to carry the tag values when no real one
             |   exists. That fix was never applied here. It is now, so both pages
             |   behave the same way.
             |
             |   The template tags are populated in BOTH branches now. The real
             |   branch used to set only three of them, so {total_payment_amount}
             |   and {cumulative_due_amount} came out blank in the SMS text.
             |
             | Sending sits after DB::commit() and inside its own try/catch: an
             | SMS gateway that is down must never roll back or fail a payment
             | that has already been saved.
             |------------------------------------------------------------------
             */
            try {
                $notification_transaction = ! empty($transaction) ? $transaction : new Transaction();

                $notification_transaction->contact = $contact;
                $notification_transaction->transaction_date = $this->transactionUtil->uf_date($request->input('paid_on'), true);
                $notification_transaction->single_payment_amount = $this->transactionUtil->num_uf($inputs['amount']);
                $notification_transaction->payment_ref_number = $payment_ref_no;
                $notification_transaction->total_payment_amount = $this->transactionUtil->num_uf($inputs['amount']);
                $notification_transaction->cumulative_due_amount = ! empty($contact)
                    ? $this->contactUtil->getCustomerBalance($contact->id, $business_id, true)
                    : 0;

                if (empty($transaction)) {
                    Log::info('S631: no outstanding transaction for this contact; sending Payment Received on a placeholder (advance payment).', [
                        'business_id' => $business_id,
                        'contact_id' => optional($contact)->id,
                        'payment_ref_no' => $payment_ref_no,
                    ]);
                }

                $this->notificationUtil->autoSendNotification(
                    $business_id,
                    'payment_received',
                    $notification_transaction,
                    $contact,
                    true
                );
            } catch (\Exception $notification_exception) {
                Log::error('S631 payment_received notification failed after a saved contact-due payment', [
                    'business_id' => $business_id,
                    'contact_id' => optional($contact)->id,
                    'message' => $notification_exception->getMessage(),
                    'file' => $notification_exception->getFile(),
                    'line' => $notification_exception->getLine(),
                ]);
            }



            $output = [

                'success' => true,

                'msg' => __('purchase.payment_added_success') . $msg

            ];
        } catch (\Exception $e) {

            DB::rollBack();

            Log::emergency('Supplier contact payment failed', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'business_id' => $business_id ?? null,
                'contact_reference' => $request->input('contact_id'),
                'due_payment_type' => $request->input('due_payment_type'),
                'method' => $request->input('method'),
                'account_id' => $request->input('account_id'),
            ]);

            $output = [
                'success' => false,
                'msg' => config('app.debug') ? $e->getMessage() : __('messages.something_went_wrong'),
            ];
        }

        return Redirect::back()->with(['status' => $output]);
    }

    public function autoFundTransfer(Request $request)
    {
        try {
            $business_id = session()->get('user.business_id');
            $operation_date = $request->input('paid_on');
            $has_reviewed = $this->transactionUtil->hasReviewed($operation_date);

            if (!empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];
                Log::error("autoFundTransfer", ['output' => $output]);
                return false;
            }

            $reviewed = $this->transactionUtil->get_review($operation_date, $operation_date);

            if (!empty($reviewed)) {
                $output = [
                    'success' => false,
                    'msg' => "You can't add a transfer for an already reviewed date",
                ];
                Log::error("autoFundTransfer", ['output' => $output]);
                return $output;
            }

            $amount = $this->commonUtil->num_uf($request->amount);
            $post_dated_account_id = $this->transactionUtil->account_exist_return_id('Post Dated Cheques');
            $issued_post_dated_account_id = $this->transactionUtil->account_exist_return_id('Issued Post Dated Cheques');
            
            $is_purchase = in_array($request->due_payment_type, ['purchase', 'expense']) || in_array($request->transaction_type, ['purchase', 'expense']);
            
            if ($is_purchase) {
                // For a purchase, money goes from "Bank" to reduce "Issued Post Dated Cheques"
                $from = $request->account_id;
                $to = $issued_post_dated_account_id;
            } else {
                // For a sale, money goes from "Post Dated Cheques" to "Bank"
                $from = $post_dated_account_id;
                $to = $request->account_id;
            }
            $cheque_number = $request->cheque_number;
            $note = $request->note;
            $uploadFile = null;

            $fromAcc = Account::find($from);
            $toAcc = Account::find($to);

            //upload file
            if (!file_exists('./public/img/account_transaction/' . $business_id)) {
                mkdir('./public/img/account_transaction/' . $business_id, 0777, true);
            }
            if ($request->hasfile('document')) {
                $image_width = (int) System::getProperty('upload_image_width');
                $image_hieght = (int) System::getProperty('upload_image_height');
                $file = $request->file('document');
                $extension = $file->getClientOriginalExtension();
                $filename = time() . '.' . $extension;
                if (in_array($extension, ['jpg', 'jpeg', 'png'])) {
                    Image::make($file->getRealPath())->resize($image_width, $image_hieght)->save('public/img/account_transaction/' . $business_id . '/' . $filename);
                } else {
                    $file->move('public/img/account_transaction/' . $business_id . '/', $filename);
                }
                $uploadFile = 'public/img/account_transaction/' . $business_id . '/' . $filename;
            }




            $tp_id = null;

            if (!empty($amount)) {
                $prefix_type = 'security_deposit';
                $ref_count = $this->transactionUtil->onlyGetReferenceCount($prefix_type, $business_id, false);
                //Generate reference number
                $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

                $post_dated_cheque = 0;
                $update_post_dated_cheque = 0;
                $parent_array = [
                    'business_id' => $business_id,
                    'method' => 'cheque',
                    'bank_name' => !empty($fromAcc) ? $fromAcc->name : null,
                    'cheque_number' => $cheque_number,
                    'paid_on' => $this->commonUtil->uf_date($operation_date, true),
                    'created_by' => Auth::user()->id,
                    'amount' => $amount,
                    'cheque_date' => $this->commonUtil->uf_date($request->cheque_date, true),
                    'is_deposited' => 1,
                    'note' => $note,
                    'payment_ref_no' => $payment_ref_no,
                    'post_dated_cheque' => $post_dated_cheque,
                    'update_post_dated_cheque' => $update_post_dated_cheque,
                    'payment_for' => $request->contact_id
                ];

                if (!empty($update_post_dated_cheque)) {
                    $pd_cheque_acc_id = $is_purchase ? $issued_post_dated_account_id : $post_dated_account_id;
                    $parent_array['account_id'] = $pd_cheque_acc_id;
                    $parent_array['related_account_id'] = $is_purchase ? $from : $to;
                    $use_to = $pd_cheque_acc_id;
                } else {
                    $parent_array['account_id'] = $to;
                    $use_to = $to;
                }

                $parent_payment = TransactionPayment::create($parent_array);

                $tp_id = $parent_payment->id;

                // Previously, credit creation was skipped when update_post_dated_cheque was true.
                // We now always create the credit side to ensure dual-entry accounting works.
                $credit_data = [
                    'amount' => $amount,
                    'account_id' => $from,
                    'type' => 'credit',
                    'sub_type' => 'fund_transfer',
                    'created_by' => session()->get('user.id'),
                    'note' => $note,
                    'cheque_number' => $cheque_number,
                    'transfer_account_id' => $to,
                    'operation_date' => $this->commonUtil->uf_date($operation_date, true),
                    'cheque_date' => $this->commonUtil->uf_date($request->cheque_date, true),
                    'attachment' => $uploadFile,
                    'transaction_payment_id' => $tp_id,
                    'post_dated_cheque' => $post_dated_cheque,
                    'update_post_dated_cheque' => $update_post_dated_cheque,
                    'auto_transfer' => "update_post_dated_cheque"
                ];

                $credit = AccountTransaction::createAccountTransaction($credit_data);

                $debit_data = [
                    'amount' => $amount,
                    'account_id' => $use_to,
                    'type' => 'debit',
                    'sub_type' => 'fund_transfer',
                    'created_by' => session()->get('user.id'),
                    'note' => $note,
                    'cheque_number' => $cheque_number,
                    'transfer_account_id' => $from,
                    'operation_date' => $this->commonUtil->uf_date($operation_date, true),
                    'cheque_date' => $this->commonUtil->uf_date($request->cheque_date, true),
                    'attachment' => $uploadFile,
                    'post_dated_cheque' => $post_dated_cheque,
                    'update_post_dated_cheque' => $update_post_dated_cheque,
                    'transaction_payment_id' => $tp_id,
                    'auto_transfer' => "update_post_dated_cheque"
                ];

                if (!empty($update_post_dated_cheque)) {
                    $pd_cheque_acc_id = $is_purchase ? $issued_post_dated_account_id : $post_dated_account_id;
                    $debit_data['account_id'] = $pd_cheque_acc_id;
                    $debit_data['related_account_id'] = $is_purchase ? $from : $to;
                    $debit_data['credit_related_account'] = $is_purchase ? $to : $from;
                }

                $debit = AccountTransaction::createAccountTransaction($debit_data);
                if (!empty($credit)) {
                    $credit->transfer_transaction_id = $debit->id;
                    $credit->save();
                    $debit->transfer_transaction_id = $credit->id;
                    $debit->save();
                }
                $from_name = Account::find($from);
            }

            $business_id = request()->session()->get('user.business_id');
            $business = Business::where('id', $business_id)->first();
            $sms_settings = empty($business->sms_settings) ? $this->businessUtil->defaultSmsSettings() : $business->sms_settings;

            $accountName = "from : " . $fromAcc->name . " to : " . $toAcc->name;

            $msg_template = NotificationTemplate::where('business_id', $business_id)->where('template_for', 'transfer')->first();
            if (!empty($msg_template)) {
                $msg = $msg_template->sms_body;
                $msg = str_replace('{account}', $accountName, $msg);
                $msg = str_replace('{amount}', $this->productUtil->num_f($amount), $msg);
                $msg = str_replace('{date}', $operation_date, $msg);
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
                    $response = $this->businessUtil->sendSms($data, 'transfer');
                }
            }

            $output = [
                'success' => true,
                'msg' => __("account.fund_transfered_success")
            ];
            Log::info("autoFundTransfer", ['output' => $output]);
            return $output;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }
        Log::error("autoFundTransfer", ['output' => $output]);
        return $output;
    }

    public function postPayVatDue(Request $request)
    {

        if (!Gate::forUser(auth()->user())->check('purchase.create') && !Gate::forUser(auth()->user())->check('sell.create')) {

            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('business.id');

        try {

            $statement_id = $request->input('statement_id');
            $customer_statement = VatCustomerStatement::findOrFail($statement_id);
            $contact_id = $customer_statement->customer_id;

            $has_reviewed = $this->transactionUtil->hasReviewed($request->input('paid_on'));

            if (!empty($has_reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.review_first'),
                ];

                return Redirect::back()->with(['status' => $output]);
            }

            $reviewed = $this->transactionUtil->get_review($request->input('paid_on'), $request->input('paid_on'));

            if (!empty($reviewed)) {
                $output = [
                    'success' => 0,
                    'msg' => "You can't add a payment for an already reviewed date",
                ];

                return Redirect::back()->with(['status' => $output]);
            }


            $inputs = $request->only([

                'amount',
                'method',
                'note',
                'card_number',
                'card_holder_name',

                'card_transaction_number',
                'card_type',
                'card_month',
                'card_year',
                'card_security',

                'cheque_number',
                'bank_account_number',
                'bank_name'

            ]);


            if ($inputs['method'] == 'cheque') {
                if (empty($inputs['cheque_number']) || empty($inputs['bank_name'])) {
                    $output = [
                        'success' => false,
                        'msg' => 'Bank name and Cheque number are required for Cheque payments'
                    ];
                    return Redirect::back()->with('status', $output);
                } else {
                    // check duplicates
                    $chequesAdded = $this->transactionUtil->checkCheques($inputs['cheque_number'], $inputs['bank_name']);

                    if ($chequesAdded > 0) {
                        $output = [
                            'success' => false,
                            'msg' => 'Cheque with the same number and bank name already exists!'
                        ];
                        return Redirect::back()->with('status', $output);
                    }
                }
            }


            $inputs['paid_on'] = $this->resolveSelectedPaymentDate($request, true);

            $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);

            $inputs['created_by'] = auth()->user()->id;

            $inputs['payment_for'] = $contact_id;

            $inputs['business_id'] = $request->session()->get('business.id');

            $inputs['cheque_date'] = !empty($request->cheque_date) ? Carbon::parse($request->cheque_date)->format('Y-m-d') : null;

            if ($inputs['method'] == 'custom_pay_1') {

                $inputs['transaction_no'] = $request->input('transaction_no_1');
            } elseif ($inputs['method'] == 'custom_pay_2') {

                $inputs['transaction_no'] = $request->input('transaction_no_2');
            } elseif ($inputs['method'] == 'custom_pay_3') {

                $inputs['transaction_no'] = $request->input('transaction_no_3');
            }

            $due_payment_type = $request->input('due_payment_type');

            $prefix_type = 'sell_payment';

            $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);

            //Generate reference number

            $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);

            $inputs['payment_ref_no'] = $payment_ref_no;

            if (!empty($request->input('account_id'))) {

                $inputs['account_id'] = $request->input('account_id');
            }

            //Upload documents if added

            $inputs['document'] = $this->transactionUtil->uploadFile($request, 'document', 'documents');

            $inputs['paid_in_type'] = 'customer_page';

            DB::beginTransaction();

            $parent_payment = TransactionPayment::create($inputs);

            $inputs['transaction_type'] = $due_payment_type;

            $account_payable = Account::where('business_id', $business_id)->where('name', 'Accounts Payable')->where('is_closed', 0)->first();

            $account_payable_id = !empty($account_payable) ? $account_payable->id : 0;

            // $amount_consumed = $this->transactionUtil->getTotalAmountConsumable($parent_payment, $due_payment_type);

            $account_transaction_data = [

                'contact_id' => $contact_id,

                'amount' => $parent_payment->amount,

                'account_id' => $parent_payment->account_id,

                'type' => 'credit',

                'operation_date' => $parent_payment->paid_on,

                'created_by' => Auth::user()->id,

                // 'transaction_id' => null,

                'transaction_payment_id' => $parent_payment->id,

                'note' => null

            ];

            $location_id = BusinessLocation::where('business_id', $business_id)->first();

            $account_transaction_data['account_id'] = $request->account_id;

            $contact = Contact::findOrFail($contact_id);

            $individual_ids = VatCustomerStatementDetail::where('statement_id', $statement_id)->pluck('transaction_id') ?? [];

            $due_transaction_id = Transaction::whereIn('id', $individual_ids)
                ->where('payment_status', '!=', 'paid')
                ->orderBy('transaction_date', 'asc')
                ->first();

            $transaction = $due_transaction_id;

            $account_transaction_data['type'] = 'debit';

            AccountTransaction::createAccountTransaction($account_transaction_data);

            $account_receivable = Account::where('business_id', $business_id)->where('name', 'Accounts Receivable')->where('is_closed', 0)->first();

            $account_receivable_id = !empty($account_receivable) ? $account_receivable->id : 0;

            $account_transaction_data['account_id'] = $account_receivable_id;

            $account_transaction_data['type'] = 'credit';

            $account_transaction_data['sub_type'] = 'ledger_show';

            AccountTransaction::createAccountTransaction($account_transaction_data);

            $account_transaction_data['contact_id'] = $contact_id;

            $account_transaction_data['sub_type'] = 'payment';

            ContactLedger::createContactLedger($account_transaction_data, 'Pay due Amount');

            //Distribute above payment among unpaid transactions

            $this->transactionUtil->payVATAtOnce($parent_payment, $statement_id);

            DB::commit();

            $transaction->contact = $contact;
            $transaction->transaction_date = $this->transactionUtil->uf_date($request->input('paid_on'), true);
            $transaction->single_payment_amount = $this->transactionUtil->num_uf($inputs['amount']);
            $transaction->payment_ref_number = $payment_ref_no;
            $this->notificationUtil->autoSendNotification($business_id, 'payment_received', $transaction, $transaction->contact, true);

            $output = [

                'success' => true,

                'msg' => __('purchase.payment_added_success')

            ];
        } catch (\Exception $e) {

            DB::rollBack();

            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong')

            ];
        }

        return Redirect::back()->with(['status' => $output]);
    }

    /**

     * view details of single..,

     * payment.

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */



    public function viewPayment($payment_id)
    {

        if (
            !Gate::forUser(auth()->user())->check('purchase.delete.payments') && !Gate::forUser(auth()->user())->check('purchase.payments') &&
            !Gate::forUser(auth()->user())->check('purchase.edit.payments') &&
            !Gate::forUser(auth()->user())->check('add.payments') && !Gate::forUser(auth()->user())->check('sell.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('business.id');

            $single_payment_line = TransactionPayment::findOrFail($payment_id);

            $transaction = null;

            if (!empty($single_payment_line->transaction_id)) {

                $transaction = Transaction::where('id', $single_payment_line->transaction_id)

                    ->with(['contact', 'location', 'transaction_for'])

                    ->first();
            } else {

                $child_payment = TransactionPayment::where('business_id', $business_id)

                    ->where('parent_id', $payment_id)

                    ->with(['transaction', 'transaction.contact', 'transaction.location', 'transaction.transaction_for'])

                    ->first();

                $transaction = $child_payment->transaction;
            }

            $payment_types = $this->transactionUtil->payment_types();

            return view('transaction_payment.single_payment_view')

                ->with(compact('single_payment_line', 'transaction', 'payment_types'));
        }
    }

    /**

     * Retrieves all the child payments of a parent payments

     * payment.

     * @param  \Illuminate\Http\Request  $request

     * @return \Illuminate\Http\Response

     */



    public function showChildPayments($payment_id)
    {

        if (
            !Gate::forUser(auth()->user())->check('purchase.delete.payments') && !Gate::forUser(auth()->user())->check('purchase.payments') &&
            !Gate::forUser(auth()->user())->check('purchase.edit.payments') &&
            !Gate::forUser(auth()->user())->check('add.payments') && !Gate::forUser(auth()->user())->check('sell.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('business.id');

            $child_payments = TransactionPayment::where('business_id', $business_id)

                ->where('parent_id', $payment_id)

                ->with(['transaction', 'transaction.contact'])

                ->get();

            $payment_types = $this->transactionUtil->payment_types();

            return view('transaction_payment.show_child_payments')

                ->with(compact('child_payments', 'payment_types'));
        }
    }

    /**

     * Retrieves list of all opening balance payments.

     *

     * @param  int  $contact_id

     * @return \Illuminate\Http\Response

     */



    public function getOpeningBalancePayments($contact_id)
    {

        if (
            !Gate::forUser(auth()->user())->check('purchase.delete.payments') && !Gate::forUser(auth()->user())->check('purchase.payments') &&
            !Gate::forUser(auth()->user())->check('purchase.edit.payments') &&
            !Gate::forUser(auth()->user())->check('add.payments') && !Gate::forUser(auth()->user())->check('sell.payments')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('business.id');

        if (request()->ajax()) {

            $query = TransactionPayment::leftjoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')

                ->where('t.business_id', $business_id)

                ->where('t.type', 'opening_balance')

                ->where('t.contact_id', $contact_id)

                ->where('transaction_payments.business_id', $business_id)

                ->select(

                    'transaction_payments.amount',

                    'method',

                    'paid_on',

                    'transaction_payments.payment_ref_no',

                    'transaction_payments.document',

                    'transaction_payments.id',

                    'cheque_number',

                    'card_transaction_number',

                    'bank_account_number'

                )

                ->groupBy('transaction_payments.id');

            $user = auth()->user();
            $permitted_locations = (is_object($user) && method_exists($user, 'permitted_locations'))
                ? $user->permitted_locations()
                : 'all';

            if ($permitted_locations != 'all') {

                $query->whereIn('t.location_id', $permitted_locations);
            }

            return Datatables::of($query)

                ->editColumn('paid_on', '{{@format_datetime($paid_on)}}')

                ->editColumn('method', function ($row) {

                    $method = __('lang_v1.' . $row->method);

                    if ($row->method == 'cheque') {

                        $method .= '<br>(' . __('lang_v1.cheque_no') . ': ' . $row->cheque_number . ')';
                    } elseif ($row->method == 'card') {

                        $method .= '<br>(' . __('lang_v1.card_transaction_no') . ': ' . $row->card_transaction_number . ')';
                    } elseif ($row->method == 'bank_transfer') {

                        $method .= '<br>(' . __('lang_v1.bank_account_no') . ': ' . $row->bank_account_number . ')';
                    } elseif ($row->method == 'custom_pay_1') {

                        $method = __('lang_v1.custom_payment_1') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_2') {

                        $method = __('lang_v1.custom_payment_2') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    } elseif ($row->method == 'custom_pay_3') {

                        $method = __('lang_v1.custom_payment_3') . '<br>(' . __('lang_v1.transaction_no') . ': ' . $row->transaction_no . ')';
                    }

                    return $method;
                })

                ->editColumn('amount', function ($row) {

                    return '<span class="display_currency paid-amount" data-orig-value="' . $row->amount . '" data-currency_symbol = true>' . $row->amount . '</span>';
                })

                ->addColumn('action', '<button type="button" class="btn btn-primary btn-xs view_payment" data-href="{{ action("TransactionPaymentController@viewPayment", [$id]) }}"><i class="fa fa-external-link"></i> @lang("messages.view")

                    </button> <button type="button" class="btn btn-info btn-xs edit_payment" 

                    data-href="{{action("TransactionPaymentController@edit", [$id]) }}"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>

                    &nbsp; <button type="button" class="btn btn-danger btn-xs delete_payment" 

                    data-href="{{ action("TransactionPaymentController@destroy", [$id]) }}"

                    ><i class="fa fa-trash" aria-hidden="true"></i> @lang("messages.delete")</button> @if(!empty($document))<a href="{{asset("/uploads/documents/" . $document)}}" class="btn btn-success btn-xs" download=""><i class="fa fa-download"></i> @lang("purchase.download_document")</a>@endif')

                ->rawColumns(['amount', 'method', 'action'])

                ->make(true);
        }
    }



    public function getAccountDropDown(Request $request)
    {

        $method_name = $request->method_name;

        $business_id = $request->session()->get('business.id');

        $html = '<option value="">None</option>';

        $accounts = Account::where('business_id', $business_id)->notClosed()->get();

        if ($method_name == 'direct_bank_deposit') {

            $accounts = Account::where('business_id', $business_id)->where('asset_type', '4')->notClosed()->get();
        }

        foreach ($accounts as $account) {

            $html .= '<option value="' . $account->id . '">' . $account->name . '</option>';
        }

        return $html;
    }

    public function getLiabilityAccounts()
    {
        $accounts = Account::where('sub_account_type', 'Current Liability')->get();
        return response()->json($accounts);
    }



    public function getPaymentMethodByLocationDropDown($location_id)
    {
        // Optional filter by account group, e.g. ?group=Bank Account
        $group_name = request()->query('group');
        $action = request()->query('action');

        if (!empty($group_name)) {
            $base = $this->transactionUtil->payment_types($location_id, false, false, false, false, true, $action);
            $payment_methods = $this->filterPaymentTypesByAccountGroup($base, $location_id, $group_name);
        } else {
            $payment_methods = $this->transactionUtil->payment_types($location_id, false, false, false, false, true, $action);
        }

        $html = '';
        foreach ($payment_methods as $key => $value) {
            if ($key === 'location_id') { // never render location id as an option
                continue;
            }
            $html .= '<option value="' . $key . '">' . $value . '</option>';
        }

        return $html;
    }



    public function getPaymentDetailsById($payment_id)
    {

        $payment = TransactionPayment::find($payment_id);

        $business_id = request()->session()->get('user.business_id');

        if ($payment->method == 'cheque') {

            $amount = TransactionPayment::where('business_id', $business_id)->whereNotNull('transaction_id')->where('cheque_number', $payment->cheque_number)->sum('amount');
        }

        $payment->amount = $amount;

        return $payment;
    }

    public function getBankNameByBankId($bank_id)
    {
        if ($bank_id) {
            $bank = Account::find($bank_id);
            if ($bank) {
                return $bank->name;
            } else {
                return null;
            }
        }
    }
	
	
	
	public function getChequeDropdownByBankId($bank_id, $contact_id)
	{
		$business_id = request()->session()->get('user.business_id');

		// Get the bank account name to match by bank_name
		$bank_account = Account::find($bank_id);
		$bank_name = $bank_account ? $bank_account->name : null;

		// Get all cheque payments for this contact
		// Match by either account_id OR bank_name (since cheques might be stored with bank_name instead of account_id)
		$cheques = TransactionPayment::where('transaction_payments.business_id', $business_id)
			->where('transaction_payments.payment_for', $contact_id)
			->where('transaction_payments.method', 'cheque')
			->whereNull('transaction_payments.deleted_at')
			->where(function ($query) use ($bank_id, $bank_name) {
				// Match by account_id
				$query->where('transaction_payments.account_id', $bank_id)
					// OR match by bank_name (if bank_name is provided)
					->orWhere('transaction_payments.bank_name', $bank_name)
					// OR match by account_transactions account_id
					->orWhereExists(function ($q) use ($bank_id) {
						$q->select(DB::raw(1))
							->from('account_transactions')
							->whereColumn('account_transactions.transaction_payment_id', 'transaction_payments.id')
							->where('account_transactions.account_id', $bank_id)
							->whereNull('account_transactions.deleted_at');
					});
			})
			->whereNotNull('transaction_payments.cheque_number')
			->select([
				'transaction_payments.cheque_number',
				'transaction_payments.cheque_date',
				'transaction_payments.bank_name',
				'transaction_payments.amount',
				'transaction_payments.id as tp_id',
			])
			->get();

		$array = [];

		foreach ($cheques as $cheque) {
			// Check if this cheque has already been returned by looking at account_transactions
			// where the cheque_return transaction stores the cheque_number
			$cheque_return = Transaction::where('type', 'cheque_return')
				->where('contact_id', $contact_id)
				->whereExists(function($query) use ($cheque) {
					$query->select(DB::raw(1))
						->from('account_transactions')
						->whereColumn('account_transactions.transaction_id', 'transactions.id')
						->where('account_transactions.cheque_number', $cheque->cheque_number)
						->whereNull('account_transactions.deleted_at');
				})
				->first();
			
			// Only include cheques that haven't been returned
			if (empty($cheque_return)) {
				$array[$cheque->tp_id] = $cheque->cheque_number . ' (Amount: ' . $this->transactionUtil->num_f($cheque->amount) . ')';
			}
		}

		return $this->transactionUtil->createDropdownHtml($array, 'Please Select');
	}

    /**
     * Filter payment types to show only those linked to a specific account group
     *
     * @param array $payment_types
     * @param int $location_id
     * @param string $group_name
     * @return array
     */
    /**
     * Resolve the selected payment transaction date from request fields.
     *
     * Some payment popups post the field as paid_on, while customer payment
     * screens may post transaction_date/customer_payment_bulk_transaction_date.
     * This method preserves the user-selected date for ledger Transaction Date
     * instead of falling back to the system date.
     */
    private function resolveSelectedPaymentDate(Request $request, bool $withTime = true): string
    {
        foreach ([
            'paid_on',
            'transaction_date',
            'customer_payment_bulk_transaction_date',
        ] as $field) {
            $value = $request->input($field);
            if (!empty($value)) {
                try {
                    $formatted = $this->transactionUtil->uf_date($value, $withTime);
                    if (!empty($formatted)) {
                        return $formatted;
                    }
                } catch (\Throwable $e) {
                    try {
                        return Carbon::parse($value)->format($withTime ? 'Y-m-d H:i:s' : 'Y-m-d');
                    } catch (\Throwable $ignored) {
                        // Continue to next field.
                    }
                }
            }
        }

        return Carbon::now()->format($withTime ? 'Y-m-d H:i:s' : 'Y-m-d');
    }

    private function filterPaymentTypesByAccountGroup($payment_types, $location_id, $group_name)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            // Get the account group ID
            $group = \App\AccountGroup::getGroupByName($group_name);
            $group_id = !empty($group) ? (is_object($group) ? $group->id : $group) : null;
            Log::info('Group ID for ' . $group_name, ['group' => $group, 'group_id' => $group_id]);

            if (empty($group_id)) {
                Log::warning('Group not found: ' . $group_name);
                return $payment_types; // Return all if group not found
            }

            // Get location payment settings
            $location = \App\BusinessLocation::find($location_id);
            if (empty($location)) {
                Log::warning('Location not found: ' . $location_id);
                return $payment_types;
            }

            $location_accounts = json_decode($location->default_payment_accounts, true);
            Log::info('Location accounts', ['location_accounts' => $location_accounts]);

            if (empty($location_accounts)) {
                Log::warning('No location accounts found');
                return $payment_types;
            }

            // Filter payment types
            $filtered_payment_types = [];

            foreach ($payment_types as $key => $value) {
                // Keep location_id
                if ($key === 'location_id') {
                    $filtered_payment_types[$key] = $value;
                    continue;
                }

                // Get the account ID linked to this payment type
                $account_id = $location_accounts[$key]['account'] ?? null;
                Log::info('Checking payment type: ' . $key, ['account_id' => $account_id]);

                if (!empty($account_id)) {
                    // Check if this account belongs to the specified account group
                    $account = \App\Account::where('id', $account_id)
                        ->where('business_id', $business_id)
                        ->first();

                    $matchesByAssetType = !empty($account) && (string) $account->asset_type === (string) $group_id;
                    $matchesByIdEqualsGroup = (string) $account_id === (string) $group_id; // fallback: some installs encode group as account id

                    Log::info('Account check for ' . $key, [
                        'account' => $account ? $account->id : 'null',
                        'asset_type' => $account->asset_type ?? null,
                        'group_id' => $group_id,
                        'by_asset_type' => $matchesByAssetType,
                        'by_id_equals_group' => $matchesByIdEqualsGroup,
                    ]);

                    if ($matchesByAssetType || $matchesByIdEqualsGroup) {
                        $filtered_payment_types[$key] = $value;
                    }
                }
            }

            Log::info('Filtered payment types', ['filtered' => $filtered_payment_types]);
            return $filtered_payment_types;
        } catch (\Exception $e) {
            Log::error('Error in filterPaymentTypesByAccountGroup: ' . $e->getMessage());
            return $payment_types; // Return original on error
        }
    }
}






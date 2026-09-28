<?php

namespace App\Http\Controllers;

use App\AccountTransaction;
use App\ContactLedger;
use App\Transaction;
use App\AccountGroup;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Interfaces\CommonConstants;
use App\Utils\NotificationUtil;
use App\Utils\ContactUtil;
use App\Contact;

class CustomerPaymentBulkController extends Controller implements CommonConstants
{
    protected $transactionUtil;
    protected $moduleUtil;
    protected $notificationUtil;
    protected $contactUtil;

    /**
     * Constructor
     *
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil,NotificationUtil $notificationUtil, ContactUtil $contactUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;
        $this->contactUtil = $contactUtil;
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
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        
        $method = 'cash';
        $acGroup = AccountGroup::where(['id' => $request->customer_payment_bulk_payment_method])->first();
        if ($acGroup) {
            if (strpos(strtolower($acGroup->name), 'card') !== false) {
                $method = 'card';
            } else if (strpos(strtolower($acGroup->name), 'cheques') !== false) {
                $method = 'cheque';
            } else if (strpos(strtolower($acGroup->name), 'bank') !== false) {
                $method = 'bank_transfer';
            }
        }

        try {
            $business_id = request()->session()->get('business.id');
            $input['payment_ref_no'] = 'CPB-' . $request->customer_payment_bulk_payment_ref_no;
            $input['payment_for'] = $request->customer_payment_bulk_customer_id;
            $input['method'] = $method;
            $input['shift_number'] = $request->daily_shift_no;
            $input['card_number'] = $request->customer_payment_bulk_card_number;
            $input['account_id'] = $request->customer_payment_bulk_accounting_module;
            $input['card_type'] = $request->customer_payment_bulk_card_name;
            $input['bank_name'] = $request->customer_payment_bulk_bank_name;
            $input['cheque_date'] = !empty($request->customer_payment_bulk_cheque_date) ? $this->transactionUtil->uf_date($request->customer_payment_bulk_cheque_date) : null;
            $input['cheque_number'] = $request->customer_payment_bulk_cheque_number;
            $input['bank_name'] = $request->customer_payment_bulk_bank_name;
            $input['paid_on'] = $this->resolveCustomerBulkTransactionDate($request);
            $input['business_id'] = $business_id;
            $input['created_by'] = Auth::user()->id;
            $input['paid_in_type'] = 'customer_bulk';
            $input['post_dated_cheque'] = $request->post_dated_cheque;
            $input['update_post_dated_cheque'] = $request->update_post_dated_cheque;
            $input['payable_amount'] = $request->payable_amount;

            $ledger_sub_type = 'cash_payment';
            if ($method === 'card') {
                $ledger_sub_type = 'card_payment';
            } elseif ($method === 'cheque') {
                $ledger_sub_type = 'cheque_payment';
            } elseif ($method === 'bank_transfer') {
                $ledger_sub_type = 'cash_deposit';
            }
            
            
            if($input['method'] == 'cheque'){
                    if(empty($input['cheque_number']) || empty($input['bank_name'])){
                        $output = [
                                        'success' => false,
                                        'msg' => 'Bank name and Cheque number are required for Cheque payments'
                                    ];
                        return redirect()->back()->with('status', $output);
                    }else{
                        // check duplicates
                        $chequesAdded = $this->transactionUtil->checkCheques($input['cheque_number'], $input['bank_name']);
                        
                        if($chequesAdded > 0){
                            $output = [
                                        'success' => false,
                                        'msg' => 'Cheque with the same number and bank name already exists!'
                                    ];
                            return redirect()->back()->with('status', $output);
                        }
                    }
                }
                
            

            // S631: notifications collected here and dispatched only after commit.
            $pending_payment_notifications = [];

            DB::beginTransaction();

            $total_amount = $total_interest = 0;
            if(isset($request->paying)){
                foreach ($request->paying as $key => $paying) {
                    if ($paying != null) {
                        $total_interest += $request->interest[$key];
                    }

                    $input['transaction_id'] = $key;
                    $input['amount'] = $request->amount[$key];
                    $total_amount += $input['amount'];
                    $tp = TransactionPayment::create($input);
                    
                    $transaction = Transaction::find($key);
                    
                    $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                    
                    
                    
                    
                    
                    $account_transaction_data = [
                        'business_id' => $business_id,
                        'contact_id' => $transaction->contact_id,
                        'amount' => $input['amount'],
                        'interest' => $request->interest[$key],
                        'account_id' => $input['account_id'],
                        'type' => 'credit',
                        'sub_type' => $ledger_sub_type,
                        'operation_date' => \Carbon::parse($input['paid_on'])->format('Y-m-d H:i:s'),
                        'created_by' => Auth::user()->id,
                        'transaction_id' => !empty($transaction) ? $transaction->id : null,
                        'transaction_payment_id' => $tp->id,
                        'cheque_number' =>  @$input['cheque_number'],
                        'cheque_date' =>  @$input['cheque_date'],
                        'bank_name' =>  @$input['bank_name'],
                        'payment_method'=> @$input['method']
                    ];
                }
            }

            $account_id = $input['account_id'];
            $account_receivable_id = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
            if(isset($request->paying)) {
                $account_transaction_data = [
                    'amount' => $total_amount,
                    'interest' => $total_interest,
                    'account_id' => $account_id,
                    'type' => 'debit',
                    'shift_number' => $request->daily_shift_no,
                    'operation_date' => \Carbon::parse($input['paid_on'])->format('Y-m-d H:i:s'),
                    'created_by' => Auth::user()->id,
                    'transaction_id' => !empty($transaction) ? $transaction->id : null,
                    'transaction_payment_id' => $tp->id,
                    'cheque_number' =>  @$input['cheque_number'],
                    'cheque_date' =>  @$input['cheque_date'],
                    'bank_name' =>  @$input['bank_name'],
                    'payment_method'=> @$input['method']
                ];

                $return =  AccountTransaction::createAccountTransaction($account_transaction_data);
                $account_transaction_data['account_id'] = $account_receivable_id;
                $account_transaction_data['type'] = 'credit';
                Log::info('here', [$account_transaction_data]);
                $receivable = AccountTransaction::createAccountTransaction($account_transaction_data);//Accounts Receivable
                
                $account_customer_interest_id = $this->transactionUtil->account_exist_return_id('Customer Interest Account');
                if($account_customer_interest_id == 0 || $account_customer_interest_id == null){
                    $account_customer_interest_id = DB::table('contacts')->where('id', $request->customer_payment_bulk_customer_id)->first()->customer_group_id ?? self::GENERAL_CUSTOMER_GROUP;
                }

                $account_transaction_data['account_id'] = $account_customer_interest_id;
                $account_transaction_data['amount'] = $total_interest;
                $account_transaction_data['interest']=0;
                $account_transaction_data['type'] = 'credit';

                $customerInterest = AccountTransaction::createAccountTransaction($account_transaction_data);//Customer Interest Income

                $account_transaction_data['amount'] = $total_amount;
                $account_transaction_data['contact_id'] = $transaction->contact_id;
                $account_transaction_data['sub_type'] = $ledger_sub_type;
                $account_transaction_data['interest']=$total_interest;
                ContactLedger::createContactLedger($account_transaction_data, 'Customer Payment Bulk page');
                
                $contact = Contact::findOrFail($transaction->contact_id);
                
                $transaction->contact = $contact;
                $transaction->transaction_date = $input['paid_on'];
                $transaction->single_payment_amount = $this->transactionUtil->num_uf($total_amount); // Changed from individual amount to total
                $transaction->payment_ref_number = $input['payment_ref_no'];
                $transaction->total_payment_amount = $input['payable_amount']; // Add total payment amount
                $transaction->cumulative_due_amount = $this->contactUtil->getCustomerBalance($contact->id, $business_id, true); // Add remaining due amount

                /*
                 * S631: queue the notification, do NOT send it here.
                 *
                 * This call used to sit between DB::beginTransaction() and
                 * DB::commit(). autoSendNotification() reaches an SMS gateway over
                 * the network, so a gateway timeout or any error inside the
                 * notification stack threw straight into the catch below and
                 * ROLLED BACK a payment that had otherwise saved correctly - after
                 * the SMS had very likely already gone out.
                 *
                 * That is the reported shape exactly: "SMS for the same is auto
                 * sent, but the issue is with the payment."
                 *
                 * The Customer Register / Pay Due page (CustomerPaymentSimpleController)
                 * already commits before notifying. This makes the two agree.
                 */
                $pending_payment_notifications[] = [
                    'transaction' => $transaction,
                    'contact'     => $contact,
                ];
                       


            }
            DB::commit();

            /*
             * S631: the payment is now durably saved. Sending happens outside the
             * transaction and inside its own try/catch, so a dead SMS gateway can
             * no longer undo a payment or report one as failed. A send that fails
             * is logged and nothing else.
             */
            foreach ($pending_payment_notifications as $pending_notification) {
                try {
                    $this->notificationUtil->autoSendNotification(
                        $business_id,
                        'payment_received',
                        $pending_notification['transaction'],
                        $pending_notification['contact'],
                        true
                    );
                } catch (\Exception $notification_exception) {
                    Log::error('S631 payment_received notification failed after a saved bulk payment', [
                        'business_id' => $business_id,
                        'contact_id'  => optional($pending_notification['contact'])->id,
                        'message'     => $notification_exception->getMessage(),
                        'file'        => $notification_exception->getFile(),
                        'line'        => $notification_exception->getLine(),
                    ]);
                }
            }

            $output = [
                'success' => true,
                'tab' => 'bulk',
                'msg' => __('lang_v1.payment_added_success')
            ];
        } catch (\Exception $e) {
            $error = 'File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage();
            Log::emergency($error);
            $output = [
                'success' => false,
                'tab' => 'bulk',
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Resolve the user selected transaction date for Customer Payment Bulk.
     *
     * The UI has used different field names across versions, so keep a
     * defensive fallback order and never silently overwrite the selected date
     * with today's date when a parsable value was submitted.
     */
    private function resolveCustomerBulkTransactionDate(Request $request): string
    {
        foreach ([
            'customer_payment_bulk_transaction_date',
            'transaction_date',
            'paid_on',
        ] as $field) {
            $value = $request->input($field);
            if (!empty($value)) {
                try {
                    $formatted = $this->transactionUtil->uf_date($value);
                    if (!empty($formatted)) {
                        return \Carbon::parse($formatted)->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                    try {
                        return \Carbon::parse($value)->format('Y-m-d');
                    } catch (\Throwable $ignored) {
                        // Continue to next possible field.
                    }
                }
            }
        }

        return date('Y-m-d');
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function bulkPaymentTable(Request $request)
    {
        $latest_ref_number_CPB = 0;
        try {
            $latest_ref_number_CPB = DB::table('transaction_payments')->where('paid_in_type', 'customer_bulk')->orderBy('created_at', 'DESC')->first()->payment_ref_no;
            $latest_ref_number_CPB = (int)explode('-', $latest_ref_number_CPB)[1];

        } catch (\Exception $exception) {}

        $latest_ref_number_CPB = $latest_ref_number_CPB+1;
            $sells = Transaction::leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                ->whereIn('transactions.payment_status', ['due', 'partial'])
                ->where('transactions.contact_id', $request->customer_id)
                ->whereIn('transactions.type', $this->contactUtil->payable_customer_txns)
                ->select(
                    'transactions.id as transaction_id',
                    'transactions.transaction_date',
                    'transactions.cheque_return_charges',
                    'transaction_payments.cheque_number',
                    'transaction_payments.bank_name',
                    'transaction_payments.is_return',
                    'transactions.invoice_no',
                    'transactions.ref_no',
                    'transactions.order_no',
                    'transactions.final_total',
                    'transactions.type',
                    // DB::raw('SUM(IF(transaction_payments.is_return = 1,-0*transaction_payments.amount,transaction_payments.amount)) as total_paid')
                   DB::raw('SUM(IF(transaction_payments.is_return = 1 AND transaction_payments.deleted_at IS NULL, -0 * transaction_payments.amount, IF(transaction_payments.deleted_at IS NULL, transaction_payments.amount, 0))) as total_paid')
                )->groupBy('transactions.id')->get();
            
        return view('customer_payments.partials.bulk_payment_table')->with(compact('sells', 'latest_ref_number_CPB'));
    }
}

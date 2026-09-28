<?php

namespace App\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\BusinessLocation;
use App\Contact;
use App\ContactLedger;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use App\Utils\NotificationUtil;
use App\Utils\ContactUtil;

class CustomerPaymentSimpleController extends Controller
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
        $business_id = request()->session()->get('business.id');
        $customers = Contact::customersDropdown($business_id, false);
        
        return view('customer_payment_simple.create')->with(compact(
            'customers'
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
        $payments = $request->payment;
        $business_id = request()->session()->get('business.id');

        try {
            foreach ($payments as $inputs) {
                 // check cheque
                if($inputs['method'] == 'cheque'){
                    if(empty($inputs['cheque_number']) || empty($inputs['bank_name'])){
                        $output = [
                                        'success' => false,
                                        'msg' => 'Bank name and Cheque number are required for Cheque payments'
                                    ];
                        DB::rollback();
                        return Redirect::back()->with('status', $output);
                    }else{
                        // check duplicates
                        $chequesAdded = $this->transactionUtil->checkCheques($inputs['cheque_number'], $inputs['bank_name']);
                        
                        if($chequesAdded > 0){
                            $output = [
                                        'success' => false,
                                        'msg' => 'Cheque with the same number and bank name already exists!'
                                    ];
                            DB::rollback();
                            return Redirect::back()->with('status', $output);
                        }
                    }
                }
                
                $contact_id = $inputs['contact_id'];
                unset($inputs['contact_id']);
                $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                $paid_on_raw = $inputs['paid_on'] ?? null;
                $paid_on = null;
                if (!empty($paid_on_raw)) {
                    $paid_on = $this->transactionUtil->uf_date($paid_on_raw, true);
                    if (empty($paid_on)) {
                        try {
                            $paid_on = Carbon::parse($paid_on_raw)->format('Y-m-d H:i:s');
                        } catch (\Exception $e) {
                            $paid_on = null;
                        }
                    }
                }
                $inputs['paid_on'] = $paid_on ?? Carbon::now()->format('Y-m-d H:i:s');
                $inputs['amount'] = $this->transactionUtil->num_uf($inputs['amount']);
                $inputs['created_by'] = auth()->user()->id;
                $inputs['payment_for'] = $contact_id;
                $inputs['business_id'] = $request->session()->get('business.id');
                
                $due_payment_type = 'sell';

                $prefix_type = 'purchase_payment';
                if (in_array($due_payment_type, ['sell', 'sell_return'])) {
                    $prefix_type = 'sell_payment';
                }
                $ref_count = $this->transactionUtil->setAndGetReferenceCount($prefix_type);
                //Generate reference number
                $payment_ref_no = $this->transactionUtil->generateReferenceNumber($prefix_type, $ref_count);
                $inputs['payment_ref_no'] = $payment_ref_no;


                //Upload documents if added
                $inputs['document'] = null;
                $inputs['cheque_date'] = !empty($inputs['cheque_date']) ? $this->transactionUtil->uf_date($inputs['cheque_date']) : "";

                $location_id = BusinessLocation::where('business_id', $business_id)->first();
                $inputs['account_id'] = $this->transactionUtil->getDefaultAccountId($inputs['method'], $location_id->id);
                DB::beginTransaction();
                
                $inputs['transaction_type'] = $due_payment_type;

                $account_payable = Account::where('business_id', $business_id)->where('name', 'Accounts Payable')->where('is_closed', 0)->first();
                $account_payable_id = !empty($account_payable) ? $account_payable->id : 0;

                // Initialize variables
                $transaction = null;
                $parent_payment = null;

                $contact = Contact::findOrFail($contact_id);

                if ($contact->type ==  'customer') {

                    if ($due_payment_type == 'sell_return') {
                        $sell_return_due = Transaction::where('contact_id', $contact_id)->whereIn('type', ['sell_return'])->whereIn('payment_status', ['due', 'partial'])->first();
                        $transaction = $sell_return_due;
                        // $inputs['transaction_id'] = !empty($sell_return_due) ? $sell_return_due->id : null;
                        $parent_payment = TransactionPayment::create($inputs);
                        
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
                        
                        
                        $account_transaction_data['account_id'] = $request->account_id;
                        // $account_transaction_data['transaction_id'] = !empty($sell_return_due) ? $sell_return_due->id : null;
                        $account_transaction_data['type'] = 'debit';
                        AccountTransaction::createAccountTransaction($account_transaction_data);
                        $account_transaction_data['sub_type'] = 'payment';
                        ContactLedger::createContactLedger($account_transaction_data, 'Customer Statement');
                    } else {
                        $due_transaction_id = Transaction::where('contact_id', $contact_id)->whereIn('type', $this->contactUtil->payable_customer_txns)->whereIn('payment_status', ['due', 'partial'])->first();
                        $transaction = $due_transaction_id; 
                        
                        $parent_payment = TransactionPayment::create($inputs);
                        
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
                            
                            
                            
                            
                        // $account_transaction_data['transaction_id'] = !empty($due_transaction_id) ? $due_transaction_id->id : null;
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
                        ContactLedger::createContactLedger($account_transaction_data, 'Customer Statement');
                    }
                }
                DB::commit();
                
                /*
                 * S631: the payment is committed above. Everything below is
                 * after-the-fact, so it is wrapped: a failing SMS gateway must not
                 * make a saved payment report itself as "something went wrong",
                 * which is what the shared catch below would otherwise do.
                 */
                // Only process payment distribution and notifications if parent_payment was created
                if (!empty($parent_payment)) {
                    //Distribute above payment among unpaid transactions
                    $this->transactionUtil->payAtOnce($parent_payment, $due_payment_type);
                    
                    // Only send notification if transaction exists
                    if (!empty($transaction)) {
                        $transaction->contact = $contact;
                        $transaction->transaction_date = $inputs['paid_on'];
                        $transaction->single_payment_amount = $this->transactionUtil->num_uf($inputs['amount']);
                        $transaction->payment_ref_number = $payment_ref_no;
                        $transaction->total_payment_amount = $this->transactionUtil->num_uf($inputs['amount']);
                        $transaction->cumulative_due_amount = $this->contactUtil->getCustomerBalance($contact->id, $business_id, true);
                        $this->sendPaymentReceivedNotification($business_id, $transaction, $transaction->contact);
                    } else {
                        // Create a dummy transaction object for notification if no transaction exists
                        $dummy_transaction = new Transaction();
                        $dummy_transaction->contact = $contact;
                        $dummy_transaction->transaction_date = $inputs['paid_on'];
                        $dummy_transaction->single_payment_amount = $this->transactionUtil->num_uf($inputs['amount']);
                        $dummy_transaction->payment_ref_number = $payment_ref_no;
                        $dummy_transaction->total_payment_amount = $this->transactionUtil->num_uf($inputs['amount']);
                        $dummy_transaction->cumulative_due_amount = $this->contactUtil->getCustomerBalance($contact->id, $business_id, true);
                        $this->sendPaymentReceivedNotification($business_id, $dummy_transaction, $contact);
                    }
                }
            }
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
     * Display the specified resource.
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
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * S631: send the Payment Received notification without letting a failure
     * reach the caller.
     *
     * The payment is already committed by the time this runs. An SMS gateway
     * that is slow or down must not turn a saved payment into a
     * "something went wrong" message on screen, and it must not hide the reason
     * from the log either.
     */
    private function sendPaymentReceivedNotification($business_id, $transaction, $contact)
    {
        try {
            $this->notificationUtil->autoSendNotification(
                $business_id,
                'payment_received',
                $transaction,
                $contact,
                true
            );
        } catch (\Exception $e) {
            Log::error('S631 payment_received notification failed after a saved payment', [
                'business_id' => $business_id,
                'contact_id'  => optional($contact)->id,
                'message'     => $e->getMessage(),
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
            ]);
        }
    }
}

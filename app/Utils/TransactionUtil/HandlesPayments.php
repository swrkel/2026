<?php

namespace App\Utils\TransactionUtil;

use App\AccountTransaction;
use App\Account;
use App\AccountType;
use Modules\Fleet\Entities\Fleet;
use App\Business;
use App\BusinessLocation;
use App\Utils\Util;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\Currency;
use App\Events\TransactionPaymentAdded;
use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use App\Exceptions\PurchaseSellMismatch;
use App\Http\Controllers\Ecom\ContactController;
use App\InvoiceScheme;
use App\Product;
use App\PurchaseLine;
use App\Restaurant\ResTable;
use App\TaxRate;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\StockAdjustmentLine;
use App\TransactionSellLinesPurchaseLines;
use App\Variation;
use App\VariationLocationDetails;
use App\VariationStoreDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\PaymentMethod;
use App\System;;
use Illuminate\Support\Facades\Auth;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\TankSellLine;
use Modules\Petro\Entities\TankPurchaseLine;
use Modules\Property\Entities\Property;
use Modules\Property\Entities\PropertyBlock;
use Modules\Property\Entities\PropertySellLine;
use Modules\Property\Entities\PropertyAccountSetting;
use Modules\Petro\Entities\DipReading;
use Modules\Petro\Entities\PumpOperatorCommission;
use App\Variation_store_detail;
use App\ExpenseCategory;
use App\Utils\ModuleUtil;
use App\Utils\ContactUtil;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\TankTransfer;
use Modules\Vat\Entities\VatCustomerStatement;
use Modules\Vat\Entities\VatCustomerStatementDetail;
use Modules\Shipping\Entities\ShippingAgentCommission;
use Modules\Shipping\Entities\ShippingPartnerCommission;
use Modules\SMS\Entities\SmsListInterest;
use Modules\Superadmin\Entities\RefillBusiness;
use App\SmsLog;
use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\VatSetting;
use Modules\Superadmin\Entities\SmsApiClient;
use Modules\Superadmin\Entities\SmsReminderSetting;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use App\ProductVariation;
use App\Unit;
use App\Brands;
use Modules\Petro\Entities\DailyVoucherItem;
use Modules\Petro\Entities\DailyVoucher;
use App\Http\Controllers\SellController;
use Illuminate\Http\Request;

/**
 * Payments, payment status and cheques.
 *
 * MA-002: split out of App\Utils\TransactionUtil, which was 11,666 lines in
 * a single file with 180 methods.
 *
 * THIS IS CORE, NOT A MODULE - it is used across the whole system, so the
 * split is deliberately the safest kind available: a trait. The class keeps
 * its name, its namespace and every one of its methods, so all 485 call sites
 * that reach into TransactionUtil resolve exactly as before. Nothing outside
 * this directory needed to change.
 *
 * Method bodies are byte-identical to the original.
 *
 * Methods here: getOutstandingPaymentRefs, getOutstandingCheques, __correctPurchasePayments, __correctCustomerPayments, __correctSellPayments, transferPostDatedCheques, createOrUpdatePaymentLines, reconcileSellPaymentAccountTransactions, updatePaymentAccountTransactions, creaetAccountPayableDiffChequeDate, editPaymentLine, getPaymentDetails, getSellTotalsByPaymentType, getTotalPaid, calculatePaymentStatus, updatePaymentStatus00, updatePaymentStatus, syncPaymentAccountAndLedgerEntries, payAtOnce, payCustomerStatementAtOnce, payVATAtOnce, updatePaymentAtOnce, adjustAdvancePayments, createAdvancePaymentTransaction, createRefundPaymentTransaction, getTotalAmountPaid, updatePostdatedCheque
 */
trait HandlesPayments
{
public function getOutstandingPaymentRefs($start_date, $end_date)
    {
        $outstanding_types = ($this->outstanding_payment_types);

        $business_id = request()->session()->get('user.business_id');
        [$paid_on_start, $paid_on_end] = $this->normaliseDateRange($start_date, $end_date);

        $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')

            ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')

            ->whereNull('tp.deleted_at')

            ->where('transactions.business_id', $business_id)

            ->where('contacts.type', 'customer')

            ->whereIn('transactions.payment_status', ['paid', 'partial'])

            ->whereIn('transactions.type', $outstanding_types)

            ->whereBetween('tp.paid_on', [$paid_on_start, $paid_on_end])

            ->whereNotNull('tp.payment_ref_no')

            ->select('tp.payment_ref_no')

            ->distinct('tp.payment_ref_no')
            ->pluck('tp.payment_ref_no', 'tp.payment_ref_no');

        return $sells;
    }

    public function getOutstandingCheques($start_date, $end_date)
    {
        $outstanding_types = ($this->outstanding_payment_types);

        $business_id = request()->session()->get('user.business_id');
        [$paid_on_start, $paid_on_end] = $this->normaliseDateRange($start_date, $end_date);

        $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')

            ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')

            ->whereNull('tp.deleted_at')

            ->where('transactions.business_id', $business_id)

            ->where('contacts.type', 'customer')

            ->whereIn('transactions.payment_status', ['paid', 'partial'])

            ->whereIn('transactions.type', $outstanding_types)

            ->whereBetween('tp.paid_on', [$paid_on_start, $paid_on_end])

            ->whereNotNull('tp.cheque_number')

            ->select('tp.cheque_number')

            ->distinct('tp.cheque_number')

            ->pluck('tp.cheque_number', 'tp.cheque_number');

        return $sells;
    }


    /**
     * Convert an inclusive date filter to an index-friendly timestamp range.
     *
     * @param string|\DateTimeInterface $start_date
     * @param string|\DateTimeInterface $end_date
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */

    public function __correctPurchasePayments()
    {
        $transactions = TransactionPayment::leftjoin('transactions', 'transactions.id', 'transaction_payments.transaction_id')
            ->leftjoin('account_transactions', 'account_transactions.transaction_payment_id', 'transaction_payments.id')
            ->where('transactions.type', 'purchase')->whereNull('account_transactions.id')->where('transaction_payments.account_id', '>', 0)->with(['contact'])->select('transaction_payments.*')->get();
        // dd($transactions->toArray());
        $start_time = time();

        foreach ($transactions as $one) {

            $transaction = $one;
            $contact = $transaction->contact;

            $account_transaction_data = [
                'amount' => abs($transaction->amount),
                'account_id' => $transaction->account_id,
                'contact_id' => !empty($contact) ? $contact->id : null,
                'operation_date' => $transaction->paid_on,
                'created_by' => $transaction->created_by,
                'business_id' => $transaction->business_id,
                'transaction_id' => $transaction->transaction_id,
                'transaction_payment_id' => $transaction->id
            ];
            $account_transaction_data['type'] = 'credit';
            $criteria = ['account_id' => $account_transaction_data['account_id'], 'type' => $account_transaction_data['type'], 'transaction_payment_id' => $account_transaction_data['transaction_payment_id']];


            // dd($account_transaction_data);

            // add transaction
            if (!empty($account_transaction_data['account_id'])) {
                AccountTransaction::updateOrCreate($criteria, $account_transaction_data);
            }

            $one->is_deposited = 0;
            $one->save();
        }
        $end_time = time();
        echo "Completed<br><b>Time taken: </b>" . $this->__formatTime($start_time, $end_time);
    }

    public function __correctCustomerPayments()
    {
        $transactions = TransactionPayment::where('paid_in_type', 'customer_page')->whereNull('transaction_id')->where('account_id', '>', 0)->with(['contact'])->get();
        $start_time = time();

        foreach ($transactions as $one) {

            $transaction = $one;
            $contact = $transaction->contact;

            $account_transaction_data = [
                'amount' => abs($transaction->amount),
                'account_id' => $transaction->account_id,
                'contact_id' => !empty($contact) ? $contact->id : null,
                'operation_date' => $transaction->paid_on,
                'business_id' => $transaction->business_id,
                'created_by' => $transaction->created_by,
                'transaction_payment_id' => $transaction->id
            ];
            if ($contact->type == 'customer') {
                $account_transaction_data['type'] = 'debit';
            }
            if ($contact->type == 'supplier') {
                $account_transaction_data['type'] = 'credit';
            }

            $criteria = ['account_id' => $account_transaction_data['account_id'], 'type' => $account_transaction_data['type'], 'transaction_payment_id' => $account_transaction_data['transaction_payment_id']];


            // dd($account_transaction_data);

            // add transaction
            if (!empty($account_transaction_data['account_id'])) {
                AccountTransaction::updateOrCreate($criteria, $account_transaction_data);
            }

            if ($contact->type == 'customer') {
                $account_transaction_data['account_id'] = Account::where('business_id', $transaction->business_id)->where('name', 'Accounts Receivable')->first()->id ?? 0;
                $account_transaction_data['type'] = 'credit';
            }
            if ($contact->type == 'supplier') {
                $account_transaction_data['account_id'] = Account::where('business_id', $transaction->business_id)->where('name', 'Accounts Payable')->first()->id ?? 0;
                $account_transaction_data['type'] = 'debit';
            }

            $account_transaction_data['sub_type'] = 'ledger_show';

            $criteria = ['account_id' => $account_transaction_data['account_id'], 'type' => $account_transaction_data['type'], 'transaction_payment_id' => $account_transaction_data['transaction_payment_id']];


            if (!empty($account_transaction_data['account_id'])) {
                AccountTransaction::updateOrCreate($criteria, $account_transaction_data);
            }

            $one->is_deposited = 0;
            $one->save();
        }
        $end_time = time();
        echo "Completed<br><b>Time taken: </b>" . $this->__formatTime($start_time, $end_time);
    }

    public function __correctSellPayments()
    {
        $transactions = TransactionPayment::leftjoin('transactions', 'transactions.id', 'transaction_payments.transaction_id')
            ->leftjoin('account_transactions', 'account_transactions.transaction_payment_id', 'transaction_payments.id')
            ->where('transactions.type', 'sell')->whereNull('account_transactions.id')->whereNull('transaction_payments.parent_id')->select('transaction_payments.*')->get();

        $start_time = time();

        foreach ($transactions as $one) {

            $transaction = $one;

            if ($one->method == 'cash') {
                $account_id = Account::where('business_id', $transaction->business_id)->where('name', 'Cash')->first()->id ?? 0;
            } else {
                $account_id = $one->card_type;
            }

            $account_transaction_data = [
                'amount' => abs($transaction->amount),
                'account_id' => $account_id,
                'operation_date' => $transaction->paid_on,
                'business_id' => $transaction->business_id,
                'created_by' => $transaction->created_by,
                'transaction_payment_id' => $transaction->id
            ];
            $account_transaction_data['type'] = 'debit';

            $criteria = ['account_id' => $account_transaction_data['account_id'], 'type' => $account_transaction_data['type'], 'transaction_payment_id' => $account_transaction_data['transaction_payment_id']];

            if (!empty($account_id)) {
                if (!empty($account_transaction_data['account_id'])) {
                    AccountTransaction::updateOrCreate($criteria, $account_transaction_data);
                }
            }


            $one->is_deposited = 0;
            $one->save();
        }
        $end_time = time();
        echo "Completed<br><b>Time taken: </b>" . $this->__formatTime($start_time, $end_time);
    }

    public function transferPostDatedCheques()
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);

        $postdated = AccountTransaction::join('accounts', 'accounts.id', 'account_transactions.account_id')
            ->join('transaction_payments', 'transaction_payments.id', 'account_transactions.transaction_payment_id')
            ->whereIn('accounts.name', ['Post Dated Cheques', 'Issued Post Dated Cheques'])
            ->whereDate('transaction_payments.cheque_date', date('Y-m-d'))
            ->where('account_transactions.postdated_transafer_status', 0)
            ->where('account_transactions.update_post_dated_cheque', 1)
            ->select('account_transactions.*', 'transaction_payments.cheque_date', 'transaction_payments.cheque_number', 'transaction_payments.bank_name', 'transaction_payments.payment_for', 'transaction_payments.payment_ref_no', 'transaction_payments.related_account_id as rel_acc_id', 'transaction_payments.id as tp_id')
            ->get();
        foreach ($postdated as $one) {
            DB::beginTransaction();

            $transaction = Transaction::create([
                'business_id' => $one->business_id,
                'type' => 'postdated_transfer',
                'status' => 'final',
                'contact_id' => $one->payment_for,
                'ref_no' => $one->payment_ref_no,
                'total_before_tax' => $one->amount,
                'transaction_date' => $one->cheque_date,
                'final_total' => $one->amount,
                'created_by' => $one->created_by
            ]);

            if ($one->type == 'credit') {
                $postdated_type = 'debit';
            } else {
                $postdated_type = 'credit';
            }

            $account_transaction_data = [
                'amount' => $one->amount,
                'account_id' => $one->account_id,
                'type' => $postdated_type,
                'sub_type' => null,
                'operation_date' => $one->cheque_date,
                'created_by' => $one->created_by,
                'transaction_id' => $transaction->id,
                'note' => null,
                'bank_name' => $one->bank_name,
                'cheque_date' => $one->cheque_date,
                'cheque_number' => $one->cheque_number,
                'transaction_payment_id' => $one->tp_id
            ];


            AccountTransaction::createAccountTransaction($account_transaction_data);

            if (!empty($one->credit_related_account)) {
                $account_transaction_data['account_id'] = $one->credit_related_account;
                $account_transaction_data['transfer_account_id'] = $one->rel_acc_id;
                $account_transaction_data['sub_type'] = 'fund_transfer';

                $credit = AccountTransaction::createAccountTransaction($account_transaction_data);

                $account_transaction_data['transfer_account_id'] = $one->credit_related_account;
                $account_transaction_data['transfer_transaction_id'] = $credit->id;
            }

            $account_transaction_data['account_id'] = $one->rel_acc_id;
            $account_transaction_data['type'] = $one->type;

            $debit = AccountTransaction::createAccountTransaction($account_transaction_data);

            if (!empty($one->credit_related_account)) {
                $credit->transfer_transaction_id = $debit->id;
                $credit->save();
            }

            $one->postdated_transafer_status = 1;
            $one->save();

            DB::commit();
        }
    }

    public function createOrUpdatePaymentLines($transaction, $payments, $business_id = null, $user_id = null, $uf_data = true, $status = null, $cheque_nos = null)
    {
        $payments_formatted = [];
        $account_transactions = [];
        $edit_ids = [];
        if (!is_object($transaction)) {
            $transaction = Transaction::findOrFail($transaction);
        }
        //If status is draft don't add payment
        if ($transaction->status == 'draft') {
            return true;
        }
        $c = 0;
        foreach ($payments as $payment) {

            //Check if transaction_sell_lines_id is set.
            $payment_mehod = $payment['method'];
            if (!empty($payment['payment_id'])) {

                $edit_ids[] = $payment['payment_id'];
                $this->editPaymentLine($payment, $transaction, $uf_data);
            } else {
                $payment_amount = $uf_data ? $this->num_uf($payment['amount']) : $payment['amount'];
                if ($payment_mehod == 'credit_sale') {
                    // $payment_amount = 0; // Fix customer ledger is not showing for credit sale [Chirag 16 Oct 2024]
                }

                //If amount is 0 then skip, except for credit sales
                if (($payment_amount > 0 || $payment_mehod == 'credit_sale') && $payment_mehod != 'credit_purchase') { //will be true if amount is zero to create account credit transaction  changed !=0 to >=0
                    $prefix_type = 'sell_payment';
                    if ($transaction->type == 'purchase') {
                        $prefix_type = 'purchase_payment';
                    }
                    $ref_count = $this->setAndGetReferenceCount($prefix_type, $business_id);
                    //Generate reference number
                    $payment_ref_no = $this->generateReferenceNumber($prefix_type, $ref_count, $business_id);
                    //If change return then set account id same as the first payment line account id
                    if (isset($payment['is_return']) && $payment['is_return'] == 1) {
                        $payment['account_id'] = !empty($payments[0]['account_id']) ? $payments[0]['account_id'] : null;
                    }


                    $payment_data = [
                        'amount' => $payment_amount,
                        'method' => $payment['method'],
                        'business_id' => $transaction->business_id,
                        'is_return' => isset($payment['is_return']) ? $payment['is_return'] : 0,
                        'card_transaction_number' => $payment['card_transaction_number'] ?? null,
                        'bank_name' => !empty($payment['bank_name']) ? $payment['bank_name'] : null,
                        'cheque_number' => ($payment['cheque_number'] ?? '') . $cheque_nos,
                        'cheque_date' => !empty($payment['cheque_date']) ? $payment['cheque_date'] : date('Y-m-d'),
                        'note' => !empty($payment['note']) ? $payment['note'] : null,
                        'paid_on' => !empty($payment['paid_on']) ? $this->uf_date($payment['paid_on']) : $transaction->transaction_date,
                        'created_by' => empty($user_id) ? auth()->user()->id : $user_id,
                        'payment_for' => $transaction->contact_id,
                        'payment_ref_no' => $payment_ref_no,
                        'account_id' => !empty($payment['account_id']) ? $payment['account_id'] : null,
                        'payment_option_id' => !empty($payment['payment_option_id']) ? $payment['payment_option_id'] : null,
                        'post_dated_cheque' => $payment['post_dated_cheque'] ?? 0,
                        'update_post_dated_cheque' => $payment['update_post_dated_cheque'] ?? 0
                    ];

                    if ($payment['method'] == 'custom_pay_1') {
                        $payment_data['transaction_no'] = $payment['transaction_no_1'];
                    } elseif ($payment['method'] == 'custom_pay_2') {
                        $payment_data['transaction_no'] = $payment['transaction_no_2'];
                    } elseif ($payment['method'] == 'custom_pay_3') {
                        $payment_data['transaction_no'] = $payment['transaction_no_3'];
                    }
                    // if method value is integer, then method holds cash group accounts id
                    if (!empty($payment['method'])) {

                        if (empty($payment['account_id'])) {

                            $payment_method_Cash = $this->account_exist_return_id('Cash'); //Account::where('business_id', $business_id)->where('name', 'Cash')->where('is_closed', 0)->select('id')->first();
                            $payment_method_Cards = $this->account_exist_return_id('Cards'); //Account::where('business_id', $business_id)->where('name', 'Cards')->where('is_closed', 0)->select('id')->first();
                            $payment_method_Credit_sales = $this->account_exist_return_id('Accounts Receivable'); //Account::where('business_id', $business_id)->where('name', 'Accounts Receivable')->where('is_closed', 0)->select('id')->first();

                            if ($payment['method'] == 'cash') {
                                $payment_data['account_id'] = !empty($payment_method_Cash) ? $payment_method_Cash : null;
                            } elseif ($payment['method'] == 'card') {
                                $payment_data['account_id'] = !empty($payment_method_Cards) ? $payment_method_Cards : null;
                            } elseif ($payment['method'] == 'credit_sale') {
                                $payment_data['account_id'] = !empty($payment_method_Credit_sales) ? $payment_method_Credit_sales : null;
                            }
                        } else {
                            $is_pd_cheque = !empty($payment_data['update_post_dated_cheque']) || !empty($payment_data['post_dated_cheque']);
                            if ($is_pd_cheque) {

                                $payment_data['related_account_id'] = $payment['account_id'];

                                if ($transaction->type == 'purchase' || $transaction->type == 'expense') {
                                    $payment_data['account_id'] = $this->account_exist_return_id('Issued Post Dated Cheques');
                                } else {
                                    $payment_data['account_id'] = $this->account_exist_return_id('Post Dated Cheques');
                                }
                            } else {
                                $payment_data['account_id'] = $payment['account_id'];
                            }
                        }



                        if ($payment['method'] == 'direct_bank_deposit' || $payment['method'] == 'bank_transfer') {
                            $payment_data['account_id'] = $payment['account_id']; // set account id to selected bank account id
                            if ($transaction->type == 'purchase') {
                                //if cheque date and order date does not matched then create account payable transactions
                                if (date("Y-m-d", strtotime($transaction->transaction_date)) != $payment_data['cheque_date']) {
                                    $this->creaetAccountPayableDiffChequeDate($transaction, $payment_data);
                                }
                            }
                        }
                    }


                    $payments_formatted[] = new TransactionPayment($payment_data);

                    //if method is cash, cheque , card get default account for method
                    //if method is bank transfer or direct bank deposit set account as selected
                    $payment_data['amount'] = $payment_amount;
                    $payment_data['transaction_type'] = $transaction->type;
                    $payment_data['location_id'] = $transaction->location_id;


                    $account_transactions[$c] = [];
                    $account_transactions[$c] = $payment_data;
                    $c++;
                } else {
                    $account_transaction_data = [
                        'contact_id' => !empty($transaction) ? $transaction->contact_id : null,
                        'amount' => $uf_data ? $this->num_uf($payment['amount']) : $payment['amount'],
                        'type' => 'credit',
                        'operation_date' => !empty($transaction->transaction_date) ? $transaction->transaction_date : date('Y-m-d H:i:s'),
                        'created_by' => Auth::user()->id,
                        'transaction_id' => !empty($transaction) ? $transaction->id : null,
                        'transaction_payment_id' => null
                    ];
                    if ($payment_mehod == 'credit_purchase' && $status == "received") {
                        $this->createCreditPurchaseTransactions($transaction, $account_transaction_data);
                    }

                    if ($payment_mehod == 'credit_sale') {
                        $account_transaction_data['account_id'] = $payment['account_id'];
                        $this->createCreditSaleTransactions($transaction, $account_transaction_data);
                    }
                }
            }
        }

        if (empty($edit_ids) && $status != "received" && $payment_mehod != 'credit_purchase') {

            $transit_transaction_data = [
                'contact_id' => !empty($transaction) ? $transaction->contact_id : null,
                'amount' => $transaction->final_total,
                'type' => 'debit',
                'operation_date' => !empty($transaction->transaction_date) ? $transaction->transaction_date : date('Y-m-d H:i:s'),
                'created_by' => Auth::user()->id,
                'transaction_id' => !empty($transaction) ? $transaction->id : null,
                'transaction_payment_id' => null,
                'post_dated_cheque' => $payments[0]['post_dated_cheque'] ?? 0,
                'update_post_dated_cheque' => $payments[0]['update_post_dated_cheque'] ?? 0
            ];

            $this->createTransitTransactions($transaction, $transit_transaction_data);
        }

        //Delete the payment lines removed.
        if (!empty($edit_ids)) {
            $deleted_transaction_payments = $transaction->payment_lines()->whereNotIn('id', $edit_ids)->get();
            $transaction->payment_lines()->whereNotIn('id', $edit_ids)->forcedelete();

            AccountTransaction::whereNotIn('transaction_payment_id', $edit_ids)->where('transaction_id', $transaction->id)->forcedelete();;

            //Fire delete transaction payment event
            foreach ($deleted_transaction_payments as $deleted_transaction_payment) {
                event(new TransactionPaymentDeleted($deleted_transaction_payment->id, $deleted_transaction_payment->account_id));
            }
        }
        if (!empty($payments_formatted)) {
            $transaction->payment_lines()->saveMany($payments_formatted);
            foreach ($transaction->payment_lines as $key => $value) {
                if (!empty($account_transactions[$key])) {
                    if ($account_transactions[$key]['transaction_type'] == 'route_operation') {
                        $this->addRouteOperationTransactions($value, $account_transactions[$key]);
                    } else {
                        event(new TransactionPaymentAdded($value, $account_transactions[$key]));
                    }
                }
            }
        }
        return true;
    }

    public function reconcileSellPaymentAccountTransactions($transaction, $force_update = false)
    {
        if (!is_object($transaction)) {
            $transaction = Transaction::find($transaction);
        }
        if (empty($transaction) || $transaction->type !== 'sell') {
            return false;
        }

        $payments = TransactionPayment::where('transaction_id', $transaction->id)->get();
        foreach ($payments as $tp) {
            if (empty($tp->amount) && $tp->method !== 'credit_sale') {
                continue;
            }

            $existing_rows = AccountTransaction::where('transaction_payment_id', $tp->id)
                ->where('type', 'debit')
                ->orderBy('id')
                ->get();
            $existing = $existing_rows->firstWhere('account_id', $tp->account_id);
            if (empty($existing) && $existing_rows->isNotEmpty()) {
                $existing = $existing_rows->first();
            }

            $account_transaction_data = [
                'contact_id' => $transaction->contact_id,
                'amount' => $tp->amount,
                'account_id' => $tp->account_id,
                'type' => 'debit',
                'operation_date' => !empty($transaction->transaction_date) ? $transaction->transaction_date : date('Y-m-d H:i:s'),
                'created_by' => $tp->created_by ?: (auth()->user()->id ?? null),
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => $tp->id,
                'cheque_date' => $tp->cheque_date,
                'post_dated_cheque' => $tp->post_dated_cheque ?? 0,
                'update_post_dated_cheque' => $tp->update_post_dated_cheque ?? 0,
            ];

            if (!empty($existing)) {
                $existing->update($account_transaction_data);
                // Keep a single debit line per payment row to prevent duplicated account-book entries.
                AccountTransaction::where('transaction_payment_id', $tp->id)
                    ->where('type', 'debit')
                    ->where('id', '!=', $existing->id)
                    ->delete();
            } else {
                AccountTransaction::createAccountTransaction($account_transaction_data, $force_update);
            }
        }

        return true;
    }

    public function updatePaymentAccountTransactions($transaction, $old_payments = [])
    {
        if (!is_object($transaction)) {
            $transaction = Transaction::find($transaction);
        }
        if (empty($transaction) || $transaction->type !== 'sell') {
            return false;
        }

        $current_payments = TransactionPayment::where('transaction_id', $transaction->id)->get();
        $old_total = collect($old_payments)->sum('amount');
        
        if ($old_total > 0 && abs($old_total - $transaction->final_total) > 0.01) {
            $ratio = $transaction->final_total / $old_total;
            
            foreach ($current_payments as $payment) {
                if ($payment->method === 'credit_sale') {
                    continue;
                }
                
                $old_payment = collect($old_payments)->firstWhere('id', $payment->id);
                if ($old_payment) {
                    $new_amount = round($old_payment['amount'] * $ratio, 2);
                    $payment->update(['amount' => $new_amount]);
                }
            }
            
            $current_payments = TransactionPayment::where('transaction_id', $transaction->id)->get();
        }
        
        foreach ($current_payments as $payment) {
            if (empty($payment->amount) && $payment->method !== 'credit_sale') {
                continue;
            }

            $existing_rows = AccountTransaction::where('transaction_payment_id', $payment->id)
                ->where('type', 'debit')
                ->orderBy('id')
                ->get();
            $existing_account_transaction = $existing_rows->firstWhere('account_id', $payment->account_id);
            if (empty($existing_account_transaction) && $existing_rows->isNotEmpty()) {
                $existing_account_transaction = $existing_rows->first();
            }

            $account_transaction_data = [
                'contact_id' => $transaction->contact_id,
                'amount' => $payment->amount,
                'account_id' => $payment->account_id,
                'type' => 'debit',
                'operation_date' => !empty($transaction->transaction_date) ? $transaction->transaction_date : date('Y-m-d H:i:s'),
                'created_by' => $payment->created_by ?: (auth()->user()->id ?? null),
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => $payment->id,
                'cheque_date' => $payment->cheque_date,
                'post_dated_cheque' => $payment->post_dated_cheque ?? 0,
                'update_post_dated_cheque' => $payment->update_post_dated_cheque ?? 0,
            ];

            if ($existing_account_transaction) {
                $existing_account_transaction->update($account_transaction_data);
                AccountTransaction::where('transaction_payment_id', $payment->id)
                    ->where('type', 'debit')
                    ->where('id', '!=', $existing_account_transaction->id)
                    ->delete();
            } else {
                AccountTransaction::createAccountTransaction($account_transaction_data, true);
            }
        }

        $total_payments = $current_payments->sum('amount');
        if (abs($total_payments - $transaction->final_total) > 0.01) {
            \Log::warning('Payment-transaction balance mismatch', [
                'transaction_id' => $transaction->id,
                'total_payments' => $total_payments,
                'final_total' => $transaction->final_total
            ]);
        }

        foreach ($current_payments as $payment) {
            if (!$payment->account_id || !Account::find($payment->account_id)) {
                \Log::error('Invalid account reference in payment', [
                    'payment_id' => $payment->id,
                    'account_id' => $payment->account_id
                ]);
                return false;
            }
        }

        return true;
    }

    public function creaetAccountPayableDiffChequeDate($transaction, $payment_data)
    {
        $account_payable_id = $this->account_exist_return_id('Accounts Payable');
        $account_transaction_credit = AccountTransaction::where('transaction_id', $transaction->id)->where('type', 'credit')->where('account_id', $account_payable_id)->first();
        $account_transaction_data = [
            'amount' => $this->num_uf($payment_data['amount']),
            'type' => 'credit',
            'account_id' => $account_payable_id,
            'operation_date' => $transaction->transaction_date,
            'created_by' => Auth::user()->id,
            'transaction_id' => !empty($transaction) ? $transaction->id : null,
            'transaction_payment_id' => null
        ];
        if (!empty($account_transaction_credit)) {
            $account_transaction_credit->update($account_transaction_data);
        } else {
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }
        $account_transaction_data['type'] = 'debit';
        $account_transaction_data['operation_date'] = $payment_data['cheque_date'];
        $account_transaction_debit = AccountTransaction::where('transaction_id', $transaction->id)->where('type', 'debit')->where('account_id', $account_payable_id)->first();
        if (!empty($account_transaction_debit)) {
            $account_transaction_debit->update($account_transaction_data);
        } else {
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }
    }
    /**
     * Edit transaction payment line
     *
     * @param array $product
     *
     * @return boolean
     */

    public function editPaymentLine($payment, $transaction = null, $uf_data = true)
    {
        $payment_id = $payment['payment_id'];
        unset($payment['payment_id']);
        unset($payment['paid_on']);
        //if payment method chagned to credit purchase then delete the account and ledger transactions and delete the older payment
        if ($payment['method'] == 'credit_purchase') {
            $this->updateCreditTransactions($transaction, $payment);
            return true;
        }
        if ($payment['method'] == 'direct_bank_deposit' || $payment['method'] == 'bank_transfer' || $payment['method'] == 'cheque') {
            if (!empty($payment['cheque_date'])) {
                // $payment['paid_on'] = $payment['cheque_date'];
            }
        }

        if ($payment['method'] == 'direct_bank_deposit' || $payment['method'] == 'bank_transfer' || $payment['method'] == 'Bank') {
            $payment['account_id'] = $payment['account_id'] ?? null;
            if ($transaction->type == 'purchase') {
                //if cheque date and order date does not matched then create account payable transactions
                if (date("Y-m-d", strtotime($transaction->transaction_date)) != date("Y-m-d", strtotime($payment['cheque_date']))) {
                    $this->creaetAccountPayableDiffChequeDate($transaction, $payment);
                }
            }
        }

        $is_pd_cheque = !empty($payment['update_post_dated_cheque']) || !empty($payment['post_dated_cheque']);
        if ($is_pd_cheque) {
            $payment_data['related_account_id'] = $payment['account_id'] ?? null;

            if ($transaction->type == 'purchase' || $transaction->type == 'expense') {
                $payment['account_id'] = $this->account_exist_return_id('Issued Post Dated Cheques');
            } else {
                $payment['account_id'] = $this->account_exist_return_id('Post Dated Cheques');
            }
        } else {
            $payment['account_id'] = $payment['account_id'] ?? null;
        }


        unset($payment['transaction_no_1'], $payment['transaction_no_2'], $payment['transaction_no_3']);
        $payment['cheque_date'] = !empty($payment['cheque_date']) ? $payment['cheque_date'] : null;
        $payment['amount'] = $uf_data ? $this->num_uf($payment['amount']) : $payment['amount'];
        $payment['post_dated_cheque'] = $payment['post_dated_cheque'] ?? 0;
        $payment['update_post_dated_cheque'] = $payment['update_post_dated_cheque'] ?? 0;
        $tp = TransactionPayment::where('id', $payment_id)
            ->first();
        $transaction_type = !empty($transaction->type) ? $transaction->type : null;
        $tp->update($payment);


        if ($tp) {
            AccountTransaction::where('transaction_payment_id', $tp->id)->update(['amount' => $payment['amount'], 'account_id' => $payment['account_id'], 'cheque_date' => $payment['cheque_date'], 'type' => 'credit', 'post_dated_cheque' => $payment['post_dated_cheque'], 'update_post_dated_cheque' => $payment['update_post_dated_cheque']]);

            $cts = ContactLedger::where('transaction_payment_id', $tp->id)->get();
            foreach ($cts as $ct) {
                $ct->amount = $payment['amount'];
                $ct->operation_date = $payment['cheque_date'];
                $ct->save();
            }
        }

        //event
        event(new TransactionPaymentUpdated($tp, $transaction->type));
        return true;
    }
    /**
     * Get payment line for a transaction
     *
     * @param int $transaction_id
     *
     * @return boolean
     */

    public function getPaymentDetails($transaction_id)
    {
        $payment_lines = TransactionPayment::where('transaction_id', $transaction_id)
            ->get();
        
        // Ensure account_id is populated for each payment line
        foreach ($payment_lines as $payment_line) {
            // If account_id is not set in transaction_payment, try to get it from account_transactions
            if (empty($payment_line->account_id)) {
                $account_transaction = AccountTransaction::where('transaction_payment_id', $payment_line->id)
                    ->first();
                if (!empty($account_transaction)) {
                    $payment_line->account_id = $account_transaction->account_id;
                }
            }
        }
        
        return $payment_lines->toArray();
    }
    /**
     * Gives the receipt details in proper format.
     *
     * @param int $transaction_id
     * @param int $location_id
     * @param object $invoice_layout
     * @param array $business_details
     * @param array $receipt_details
     * @param string $receipt_printer_type
     *
     * @return array
     */

    function floattostr($val)
    {
        preg_match("#^([\+\-]|)([0-9]*)(\.([0-9]*?)|)(0*)$#", trim($val), $o);
        return $o[1] . sprintf('%d', $o[2]) . ($o[3] != '.' ? $o[3] : '');
    }

    public function getSellTotalsByPaymentType($business_id, $start_date = null, $end_date = null, $location_id = null, $type)
    {
        $payment_method_Cash = PaymentMethod::where('business_id', $business_id)->where('name', 'Cash')->select('id')->first();
        $payment_method_Cheques = PaymentMethod::where('business_id', $business_id)->where('name', 'Cheques')->select('id')->first();
        $payment_method_Cards = PaymentMethod::where('business_id', $business_id)->where('name', 'Cards')->select('id')->first();
        $payment_method_Bank_transfer = PaymentMethod::where('business_id', $business_id)->where('name', 'Bank transfer')->select('id')->first();
        $payment_method_Other = PaymentMethod::where('business_id', $business_id)->where('name', 'Other')->select('id')->first();
        $payment_method_Credit_sales = PaymentMethod::where('business_id', $business_id)->where('name', 'Credit-Sales')->select('id')->first();
        $payment_method_Credit_purchases = PaymentMethod::where('business_id', $business_id)->where('name', 'Credit Purchases')->select('id')->first();
        if ($type == 1) {
            $query = Transaction::leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                // ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->select(
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cash->id . ' ,transaction_payments.amount, 0))  as total_cash'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cheques->id . ' , transaction_payments.amount, 0))  as total_cheques'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cards->id . ', transaction_payments.amount, 0))   as total_cards'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Bank_transfer->id . ', transaction_payments.amount, 0))   as total_bank_transfer'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Other->id . ', transaction_payments.amount, 0))   as total_other'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Credit_sales->id . ', transaction_payments.amount, 0))   as total_c_sale'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Credit_purchases->id . ', transaction_payments.amount, 0))   as total_c_purchase'),
                    DB::raw('SUM(IF(payment_status= "paid", transaction_payments.amount, 0))  as total_payment'),
                    DB::raw('(SELECT SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id) as total_refund'),
                    DB::raw('(SELECT SUM(tp.final_total) FROM transactions as tp WHERE tp.type = "sell") as total_sales')
                    // DB::raw('(SELECT SUM(IF(transaction_payments.method = '.$payment_method_Cash->id .', transaction_payments.amount, transaction_payments.amount))) as total_cash'),
                    // DB::raw('SUM(total_before_tax) as total_before_tax'),
                );
            // ->groupBy('transactions.id');
            // //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }
            if (!empty($start_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date);
            }
            if (!empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '<=', $end_date);
            }
            $data = $query->get();
        }
        if ($type == 3) {
            $query = Transaction::leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('is_return', '1')
                // ->whereBetween('transactions.transaction_date', [$start_date, $end_date])
                ->select(
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cash->id . ', transaction_payments.amount ,0 )) as total_cash'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cheques->id . ',transaction_payments.amount ,0)) as total_cheques'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cards->id . ', transaction_payments.amount ,0  )) as total_cards'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Bank_transfer->id . ', transaction_payments.amount, 0)) as total_bank_transfer'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Other->id . ' , transaction_payments.amount,0)) as total_other'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Credit_sales->id . ', transaction_payments.amount  ,0)) as total_c_sale'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Credit_purchases->id . ', transaction_payments.amount, 0 )) as total_c_purchase'),
                    DB::raw('SUM(IF(payment_status= "paid", transaction_payments.amount, 0)) as total_payment'),
                    DB::raw('(SELECT SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)) FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id) as total_refund'),
                    DB::raw('(SELECT SUM(tp.final_total) FROM transactions as tp WHERE tp.type = "sell") as total_sales')
                    // DB::raw('(SELECT SUM(IF(transaction_payments.method = '.$payment_method_Cash->id .', transaction_payments.amount, transaction_payments.amount))) as total_cash'),
                    // DB::raw('SUM(total_before_tax) as total_before_tax'),
                )
                ->groupBy('transactions.id');
            // //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }
            if (!empty($start_date)) {
                $query->whereDate('transactions.transaction_date', '>=', $start_date);
            }
            if (!empty($end_date)) {
                $query->whereDate('transactions.transaction_date', '<=', $end_date);
            }
            $data = $query->get();
        }
        if ($type == 4) {
            $query = Transaction::where('transactions.business_id', $business_id)
                ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
                ->where('type', 'purchase')
                ->select(
                    'final_total',
                    DB::raw("(final_total - tax_amount) as total_exc_tax"),
                    DB::raw("SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id)) as total_paid"),
                    DB::raw('SUM(total_before_tax) as total_before_tax'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cash->id . ', transaction_payments.amount ,0 )) as total_cash'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cheques->id . ',transaction_payments.amount ,0)) as total_cheques'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cards->id . ', transaction_payments.amount ,0  )) as total_cards'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Bank_transfer->id . ', transaction_payments.amount, 0)) as total_bank_transfer'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Other->id . ' , transaction_payments.amount,0)) as total_other'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Credit_sales->id . ', transaction_payments.amount  ,0)) as total_c_sale'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Credit_purchases->id . ', transaction_payments.amount, 0 )) as total_c_purchase'),
                    DB::raw('SUM(IF(payment_status= "paid", transaction_payments.amount, 0)) as total_payment'),
                    DB::raw('(SELECT SUM(IF(transaction_payments.is_return = "1", -1*transaction_payments.amount, 0)) FROM transaction_payments WHERE transaction_payments.transaction_id = transactions.id) as total_refund'),
                    DB::raw('(SELECT SUM(tp.final_total) FROM transactions as tp WHERE tp.type = "purchase") as total_purchase')
                );
            // ->groupBy('transactions.id');
            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween('transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }
            if (empty($start_date) && !empty($end_date)) {
                $query->where('transaction_date', '<=', $end_date . ' 23:59:59');
            }
            //Filter by the location
            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }
            $data = $query->get();
        }
        if ($type == 5) {
            $query = Transaction::where('transactions.business_id', $business_id)
                ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
                ->where('type', 'purchase')
                ->where('is_return', '1')
                ->select(
                    'final_total',
                    DB::raw("(final_total - tax_amount) as total_exc_tax"),
                    DB::raw("SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id)) as total_paid"),
                    DB::raw('SUM(total_before_tax) as total_before_tax'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cash->id . ', transaction_payments.amount ,0 )) as total_cash'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cheques->id . ',transaction_payments.amount ,0)) as total_cheques'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cards->id . ', transaction_payments.amount ,0  )) as total_cards'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Bank_transfer->id . ', transaction_payments.amount, 0)) as total_bank_transfer'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Other->id . ' , transaction_payments.amount,0)) as total_other'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Credit_sales->id . ', transaction_payments.amount  ,0)) as total_c_sale'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Credit_purchases->id . ', transaction_payments.amount, 0 )) as total_c_purchase'),
                    DB::raw('SUM(IF(payment_status= "paid", transaction_payments.amount, 0)) as total_payment'),
                    DB::raw('(SELECT SUM(IF(transaction_payments.is_return = "1", -1*transaction_payments.amount, 0)) FROM transaction_payments WHERE transaction_payments.transaction_id = transactions.id) as total_refund'),
                    DB::raw('(SELECT SUM(tp.final_total) FROM transactions as tp WHERE tp.type = "purchase") as total_purchase')
                );
            // ->groupBy('transactions.id');
            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween('transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
            }
            if (empty($start_date) && !empty($end_date)) {
                $query->where('transaction_date', '<=', $end_date . ' 23:59:59');
            }
            //Filter by the location
            if (!empty($location_id)) {
                $query->where('transactions.location_id', $location_id);
            }
            $data = $query->get();
        }
        if ($type == 6) {
            $query = Transaction::leftjoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
                ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->where('type', 'expense')
                ->select(
                    'final_total',
                    DB::raw("(final_total - tax_amount) as total_exc_tax"),
                    DB::raw("SUM((SELECT SUM(tp.amount) FROM transaction_payments as tp WHERE tp.transaction_id=transactions.id)) as total_paid"),
                    DB::raw('SUM(total_before_tax) as total_before_tax'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cash->id . ', transaction_payments.amount ,0 )) as total_cash'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cheques->id . ',transaction_payments.amount ,0)) as total_cheques'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Cards->id . ', transaction_payments.amount ,0  )) as total_cards'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Bank_transfer->id . ', transaction_payments.amount, 0)) as total_bank_transfer'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Other->id . ' , transaction_payments.amount,0)) as total_other'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Credit_sales->id . ', transaction_payments.amount  ,0)) as total_c_sale'),
                    DB::raw('SUM(IF(transaction_payments.method = ' . $payment_method_Credit_purchases->id . ', transaction_payments.amount, 0 )) as total_c_purchase'),
                    DB::raw('SUM(IF(payment_status= "paid", transaction_payments.amount, 0)) as total_payment'),
                    // DB::raw('(SELECT SUM(IF(transaction_payments.is_return = "1", -1*transaction_payments.amount, 0)) FROM transaction_payments WHERE transaction_payments.transaction_id = transactions.id) as total_refund'),
                    DB::raw('(SELECT SUM(tp.final_total) FROM transactions as tp WHERE tp.type = "expense") as total_expense')
                );
            // ->where('payment_status', 'paid');
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween('transaction_date', [
                    $start_date . ' 00:00:00',
                    $end_date . ' 23:59:59'
                ]);
            }
            $data = $query->get();
        }
        if ($type == 2) {
            $data = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.payment_status', 'final')
                ->whereBetween('transactions.transaction_date', [date($start_date), date($end_date)])
                // ->whereDate('transactions.transaction_date', '<=',$end_date)
                ->select(
                    DB::raw('SUM(tp.amount) as today_settled'),
                    DB::raw('SUM(tp.amount) as today_due'),
                    DB::raw('SUM(IF(transactions.transaction_date < ' . date($start_date) . ', tp.amount, 0)) as opening_due')
                )->first();
        }
        return $data;
    }

    public function getTotalPaid($transaction_id)
    {
        $total_paid = TransactionPayment::where('transaction_id', $transaction_id)
            ->where('method', '!=', 'credit_sale')
            ->where('method', '!=', 'credit_expense')
            ->whereNull('deleted_at')
            ->where('amount', '>', 0)
            ->select(DB::raw('SUM(IF(deleted_at IS NULL, IF(is_return = 0, amount, -amount), 0)) as total_paid'))
            ->first()
            ->total_paid;
        return $total_paid ?? 0;
    }
    /**
     * Calculates the payment status and returns back.
     *
     * @param int $transaction_id
     * @param float $final_amount = null
     *
     * @return string
     */

    public function calculatePaymentStatus($transaction_id, $final_amount = null, $amount = null, $paymentMethod = null)
    {
        // dd($paymentMethod);
        // allow zero value, so use strict null check
        if (! is_null($amount)) {
            $total_paid = $amount;
        } else {
            $total_paid = $this->getTotalPaid($transaction_id);
        }

        if (is_null($final_amount)) {
            $final_amount = Transaction::find($transaction_id)->final_total;
        }

        // Default status
        $status = 'due';

        // Check for credit purchase first
        if (in_array(strtolower($paymentMethod), ['credit', 'credit_purchase', 'credit_expense', 'credit_sale'])) {
            $status = 'due'; // Always due in credit purchase/expense/sale
        } else {
            // Fix: Only mark as paid if total_paid > 0 and covers the full amount.
            // Treat very small differences (due to rounding) as paid as well.
            if ($total_paid > 0) {
                // using a tolerance of 0.02 (2 cents) similar to other rounding logic
                if (abs($final_amount - $total_paid) < 0.02 || $final_amount <= $total_paid) {
                    $status = 'paid';
                } elseif ($final_amount > $total_paid) {
                    $status = 'partial';
                } else {
                    $status = 'paid';
                }
            } else {
                $status = 'due'; // If total_paid is 0 or negative, always due
            }
        }

        return $status;
    }
    /**
     * Update the payment status for purchase or sell transactions. Returns
     * the status
     *
     * @param int $transaction_id
     *
     * @return string
     */

    public function updatePaymentStatus00($transaction_id, $final_amount = null, $paymentMethod = null, $amount = null)
    {
        dd('ssss');
        $status = $this->calculatePaymentStatus($transaction_id, $final_amount, $amount);
        // dd($status);
        $transaction = Transaction::find($transaction_id);
        if ($transaction->type == 'sell' || ($transaction->type == 'settlement' && $transaction->sub_type == 'credit_sale')) {
            if ($status == 'due' || $status == 'partial') {
                Transaction::where('id', $transaction_id)
                    ->update(['is_credit_sale' => 1]);
            }
        }
        if ($transaction->is_pos_return == 1) {
            $status = 'paid';
        }
        if ($paymentMethod = "credit_expense") {
            $status = 'due';
        }

        Transaction::where('id', $transaction_id)
            ->update(['payment_status' => $status]);

        $updatedTransaction = Transaction::find($transaction_id);
        $databaseName = DB::connection()->getDatabaseName();

        return $status;
    }

    public function updatePaymentStatus($transaction_id, $final_amount = null, $paymentMethod = null, $amount = null)
    {
        // dd($paymentMethod);
        $status = $this->calculatePaymentStatus($transaction_id, $final_amount, $amount, $paymentMethod);
        $transaction = Transaction::find($transaction_id);
        if ($transaction->type == 'sell' || ($transaction->type == 'settlement' && $transaction->sub_type == 'credit_sale')) {
            if ($status == 'due' || $status == 'partial') {
                $transaction->is_credit_sale = 1; // Correctly set the model property
                $transaction->save(); // Persist the changes to the database
            }
        }
        if ($transaction->is_pos_return == 1) {
            $status = 'paid';
        }

        if (in_array(strtolower($paymentMethod), ['credit', 'credit_expense', 'credit_purchase', 'credit_sale'])) {

            $status = 'due';
        }

        // if($status == 'paid'){
        //     //send sms
        //     Util::print_sms($transaction->id, $transaction->mobile_no, 'payment_done');
        //     Util::print_sms($transaction->id, $transaction->whatsapp_no, 'payment_done');
        // }

        $transaction->payment_status = $status;
        $transaction->save();


        $updatedTransaction = Transaction::find($transaction_id);
        $databaseName = DB::connection()->getDatabaseName();

        return $status;
    }
    /**
     * Update the payment status for purchase or sell transactions. Returns
     * the status
     *
     * @param int $transaction_id
     *
     * @return string
     */

    public function syncPaymentAccountAndLedgerEntries($payment)
    {
        if (empty($payment) || empty($payment->id)) {
            return;
        }

        $accounts_receivable_id = $this->account_exist_return_id('Accounts Receivable');
        $operation_date = $payment->paid_on ?: \Carbon::now();

        $account_transactions = AccountTransaction::where('transaction_payment_id', $payment->id)->get();
        foreach ($account_transactions as $account_transaction) {
            $update_data = [
                'amount' => $payment->amount,
                'operation_date' => $operation_date,
            ];

            if (
                !empty($payment->account_id)
                && (empty($accounts_receivable_id) || intval($account_transaction->account_id) !== intval($accounts_receivable_id))
            ) {
                $update_data['account_id'] = $payment->account_id;
            }

            $account_transaction->update($update_data);
        }

        ContactLedger::where('transaction_payment_id', $payment->id)
            ->update([
                'amount' => $payment->amount,
                'operation_date' => $operation_date,
            ]);
    }

    public function payAtOnce($parent_payment, $type)
    {
        //Get all unpaid transaction for the contact
        // $types = ['opening_balance', $type];
        if ($type == 'purchase_return') {
            $types = [$type];
        } elseif ($type == 'sell_return') {
            $types = [$type];
        } else {
            $types = array_merge($this->contactUtil->payable_supplier_txns, $this->contactUtil->payable_customer_txns);
        }

        $due_transactions = Transaction::where('contact_id', $parent_payment->payment_for)
            ->whereIn('type', $types)
            ->where('payment_status', '!=', 'paid')
            ->orderBy('transaction_date', 'asc')
            ->get();
        $total_amount = $parent_payment->amount;
        $tranaction_payments = [];
        if ($due_transactions->count()) {
            foreach ($due_transactions as $key => $transaction) {
                if ($total_amount > 0) {
                    $total_paid = $this->getTotalPaid($transaction->id);
                    $due = $transaction->final_total - $total_paid;
                    $now = \Carbon::now()->toDateTimeString();
                    $array = [
                        'transaction_id' => $transaction->id,
                        'business_id' => $parent_payment->business_id,
                        'method' => $parent_payment->method,
                        'transaction_no' => $parent_payment->method,
                        'card_transaction_number' => $parent_payment->card_transaction_number,
                        'cheque_number' => $parent_payment->cheque_number,
                        'cheque_date' => $parent_payment->cheque_date,
                        'bank_account_number' => $parent_payment->bank_account_number,
                        'bank_name' => $parent_payment->bank_name,
                        'paid_on' => $parent_payment->paid_on,
                        'created_by' => $parent_payment->created_by,
                        'payment_for' => $parent_payment->payment_for,
                        'parent_id' => $parent_payment->id,
                        'account_id' => $parent_payment->account_id,
                        'paid_in_type' => $parent_payment->paid_in_type,
                        'created_at' => \Carbon::now(),
                        'updated_at' => \Carbon::now()
                    ];

                    //Generate reference number
                    $payment_ref_no = $parent_payment->payment_ref_no . "-" . ($key + 1);
                    $array['payment_ref_no'] = $payment_ref_no;

                    if ($due <= $total_amount) {
                        $array['amount'] = $due;
                        $tranaction_payments[] = $array;
                        //Update transaction status to paid
                        $transaction->payment_status = 'paid';
                        $transaction->save();
                        $total_amount = $total_amount - $due;
                    } else {
                        $array['amount'] = $total_amount;
                        $tranaction_payments[] = $array;
                        //Update transaction status to partial
                        $transaction->payment_status = 'partial';
                        $transaction->save();
                        break;
                    }
                }
            }
            //Insert new transaction payments
            if (!empty($tranaction_payments)) {
                TransactionPayment::insert($tranaction_payments);
            }

            $this->updatePaymentStatus($transaction->id);
        }
    }

    public function payCustomerStatementAtOnce($parent_payment, $transaction_ids)
    {


        $due_transactions = Transaction::where('contact_id', $parent_payment->payment_for)
            ->whereIn('id', $transaction_ids)
            ->where('payment_status', '!=', 'paid')
            ->orderBy('transaction_date', 'asc')
            ->get();

        $total_amount = $parent_payment->amount;
        $tranaction_payments = [];
        if ($due_transactions->count()) {
            foreach ($due_transactions as $key => $transaction) {
                if ($total_amount > 0) {
                    $total_paid = $this->getTotalPaid($transaction->id);
                    $due = $transaction->final_total - $total_paid;
                    $now = \Carbon::now()->toDateTimeString();
                    $array = [
                        'transaction_id' => $transaction->id,
                        'business_id' => $parent_payment->business_id,
                        'method' => $parent_payment->method,
                        'transaction_no' => $parent_payment->method,
                        'card_transaction_number' => $parent_payment->card_transaction_number,
                        'cheque_number' => $parent_payment->cheque_number,
                        'cheque_date' => $parent_payment->cheque_date,
                        'bank_account_number' => $parent_payment->bank_account_number,
                        'bank_name' => $parent_payment->bank_name,
                        'paid_on' => $parent_payment->paid_on,
                        'created_by' => $parent_payment->created_by,
                        'payment_for' => $parent_payment->payment_for,
                        'parent_id' => $parent_payment->id,
                        'account_id' => $parent_payment->account_id,
                        'paid_in_type' => $parent_payment->paid_in_type,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'linked_customer_statement' => $parent_payment->linked_customer_statement
                    ];

                    //Generate reference number
                    $payment_ref_no = $parent_payment->payment_ref_no . "-" . ($key + 1);
                    $array['payment_ref_no'] = $payment_ref_no;

                    if ($due <= $total_amount) {
                        $array['amount'] = $due;
                        $tranaction_payments[] = $array;
                        //Update transaction status to paid
                        $transaction->payment_status = 'paid';
                        $transaction->save();
                        $total_amount = $total_amount - $due;
                    } else {
                        $array['amount'] = $total_amount;
                        $tranaction_payments[] = $array;
                        //Update transaction status to partial
                        $transaction->payment_status = 'partial';
                        $transaction->save();
                        break;
                    }
                }
            }
            //Insert new transaction payments
            if (!empty($tranaction_payments)) {
                TransactionPayment::insert($tranaction_payments);
            }

            $this->updatePaymentStatus($transaction->id);
        }
    }

    public function payVATAtOnce($parent_payment, $statement_id)
    {
        $individual_ids = VatCustomerStatementDetail::where('statement_id', $statement_id)->pluck('transaction_id') ?? [];

        $due_transactions = Transaction::whereIn('id', $individual_ids)
            ->where('payment_status', '!=', 'paid')
            ->orderBy('transaction_date', 'asc')
            ->get();

        $total_amount = $parent_payment->amount;
        $tranaction_payments = [];
        if ($due_transactions->count()) {
            foreach ($due_transactions as $transaction) {
                if ($total_amount > 0) {
                    $total_paid = $this->getTotalPaid($transaction->id);
                    $due = $transaction->final_total - $total_paid;
                    $now = \Carbon::now()->toDateTimeString();
                    $array = [
                        'transaction_id' => $transaction->id,
                        'business_id' => $parent_payment->business_id,
                        'method' => $parent_payment->method,
                        'transaction_no' => $parent_payment->method,
                        'card_transaction_number' => $parent_payment->card_transaction_number,
                        'cheque_number' => $parent_payment->cheque_number,
                        'cheque_date' => $parent_payment->cheque_date,
                        'bank_account_number' => $parent_payment->bank_account_number,
                        'bank_name' => $parent_payment->bank_name,
                        'paid_on' => $parent_payment->paid_on,
                        'created_by' => $parent_payment->created_by,
                        'payment_for' => $parent_payment->payment_for,
                        'parent_id' => $parent_payment->id,
                        'account_id' => $parent_payment->account_id,
                        'paid_in_type' => $parent_payment->paid_in_type,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                    $prefix_type = 'purchase_payment';
                    if (in_array($transaction->type, ['sell', 'sell_return'])) {
                        $prefix_type = 'sell_payment';
                    }
                    $default_prefix = null;
                    if (in_array($transaction->type, ['cheque_return'])) {
                        $prefix_type = 'cheque_return_payment';
                        $default_prefix = 'CRP';
                    }
                    $ref_count = $this->setAndGetReferenceCount($prefix_type);
                    //Generate reference number
                    $payment_ref_no = $this->generateReferenceNumber($prefix_type, $ref_count, null, $default_prefix);
                    $array['payment_ref_no'] = $payment_ref_no;
                    if ($due <= $total_amount) {
                        $array['amount'] = $due;
                        $tranaction_payments[] = $array;
                        //Update transaction status to paid
                        $transaction->payment_status = 'paid';
                        $transaction->save();
                        $total_amount = $total_amount - $due;
                    } else {
                        $array['amount'] = $total_amount;
                        $tranaction_payments[] = $array;
                        //Update transaction status to partial
                        $transaction->payment_status = 'partial';
                        $transaction->save();
                        break;
                    }
                }
            }
            //Insert new transaction payments
            if (!empty($tranaction_payments)) {
                TransactionPayment::insert($tranaction_payments);
            }
        }
    }
    /**
     * Update payment contact due at once
     *
     * @param obj $parent_payment, string $type
     *
     * @return void
     */

    public function updatePaymentAtOnce($parent_payment, $type)
    {
        $payments = TransactionPayment::where('parent_id', $parent_payment->id)->get();
        $total_amount = $parent_payment->amount;
        if ($payments->count()) {
            foreach ($payments as $payment) {
                if ($total_amount > 0) {
                    $array = [
                        'method' => $parent_payment->method,
                        'transaction_no' => $parent_payment->method,
                        'card_transaction_number' => $parent_payment->card_transaction_number,
                        'cheque_number' => $parent_payment->cheque_number,
                        'cheque_date' => $parent_payment->cheque_date,
                        'bank_account_number' => $parent_payment->bank_account_number,
                        'bank_name' => $parent_payment->bank_name,
                        'paid_on' => $parent_payment->paid_on,
                        'created_by' => $parent_payment->created_by,
                        'payment_for' => $parent_payment->payment_for,
                        'parent_id' => $parent_payment->id,
                        'account_id' => $parent_payment->account_id
                    ];
                    $transaction = Transaction::find($payment->transaction_id);
                    $this_transaction_id = $payment->transaction_id;
                    if ($payment->amount <= $total_amount) {
                        $array['amount'] = $payment->amount;
                        //update payment
                        TransactionPayment::where('id', $payment->id)->update($array);
                        //Update transaction status to paid
                        $transaction->payment_status = 'paid';
                        $transaction->save();
                        $total_amount = $total_amount - $payment->amount;
                    } else {
                        if ($total_amount > 0) {
                            $array['amount'] = $total_amount;
                            $transaction->payment_status = 'partial';
                            $transaction->save();
                            $total_amount = $total_amount - $array['amount'];
                            TransactionPayment::where('id', $payment->id)->update($array);
                        } else {
                            TransactionPayment::where('id', $payment->id)->forcedelete();
                            $this->updatePaymentStatus($this_transaction_id);
                        }
                    }
                }
            }
            $this->updatePaymentStatus($this_transaction_id);
        }
    }
    /**
     * Pay contact due at once
     *
     * @param obj $parent_payment, string $type
     *
     * @return void
     */

    public function adjustAdvancePayments($transaction, $paying_amount, $business_id)
    {
        // get advance amounts to supplier
        $due_amount = $transaction->final_total - $paying_amount;  // amount to pay
        $advance_remainings = Transaction::where('business_id', $business_id)->where('contact_id', $transaction->contact_id)->where('type', 'advance_payment')->where('advance_remaining', '>', 0)->select('advance_remaining', 'id')->get();
        foreach ($advance_remainings as $advance_remaining) {
            if ($due_amount > 0) {
                $this_advance = $advance_remaining->advance_remaining;
                $this_advance_remaining = 0;
                if ($this_advance >= $due_amount) {
                    $this_advance_remaining = $this_advance - $due_amount;
                    $due_amount = 0;
                    Transaction::where('id', $advance_remaining->id)->update(['advance_remaining' => $this_advance_remaining]);
                    break;
                }
                if ($this_advance < $due_amount) {
                    $this_advance_remaining = 0;
                    $due_amount = $due_amount - $this_advance;
                    Transaction::where('id', $advance_remaining->id)->update(['advance_remaining' => $this_advance_remaining]);
                }
            }
        }
        return $due_amount;
    }
    /**
     * Creates a new advance payment transaction for a contact
     *
     * @param  int $business_id
     * @param  int $contact_id
     * @param  int $amount
     *
     * @return object
     */

    public function createAdvancePaymentTransaction($business_id, $contact_id, $amount, $account_id, $payment_type, $transaction_date, $current_liability_account = null, $inputs = null)
    {
        $contact = Contact::findOrFail($contact_id);
        $business_location = BusinessLocation::where('business_id', $business_id)
            ->first();
        $final_amount = $this->num_uf($amount);
        $ob_data = [
            'business_id' => $business_id,
            'location_id' => $business_location->id,
            'type' => $payment_type,
            'status' => 'final',
            'payment_status' => 'paid',
            'contact_id' => $contact_id,
            'transaction_date' => $transaction_date,
            'total_before_tax' => $final_amount,
            'final_total' => $final_amount,
            'created_by' => request()->session()->get('user.id')
        ];
        if ($payment_type == 'advance_payment') {
            $ob_data['advance_remaining'] = $final_amount;
        }
        //Update reference count
        $ob_ref_count = $this->setAndGetReferenceCount($payment_type);
        //Generate reference number
        $ob_data['ref_no'] = $this->generateReferenceNumber($payment_type, $ob_ref_count);
        //Create opening balance transaction
        $transaction = Transaction::create($ob_data);

        $account_transaction_data = [
            'amount' => abs($transaction->final_total),
            'account_id' => $account_id,
            'contact_id' => $contact_id,
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'note' => null,
            'post_dated_cheque' => $inputs['post_dated_cheque'] ?? 0,
            'update_post_dated_cheque' => $inputs['update_post_dated_cheque'] ?? 0,
        ];

        $issued_post_dated = $this->account_exist_return_id('Issued Post Dated Cheques');
        $post_dated = $this->account_exist_return_id('Post Dated Cheques');

        $use_issued_pd = !empty($inputs['update_post_dated_cheque']) || !empty($inputs['post_dated_cheque']);
        if ($payment_type == 'advance_payment') {
            if ($contact->type == 'customer') {
                $account_transaction_data['type'] = 'debit';

                if (!empty($inputs) && $use_issued_pd) {
                    $account_transaction_data['related_account_id'] = $account_id;
                    $account_transaction_data['account_id'] = $post_dated;
                }
            }
            if ($contact->type == 'supplier') {
                $account_transaction_data['type'] = 'credit';

                if (!empty($inputs) && $use_issued_pd) {
                    $account_transaction_data['related_account_id'] = $account_id;
                    $account_transaction_data['account_id'] = $issued_post_dated;
                }
            }
        }
        if ($payment_type == 'security_deposit') {
            if ($contact->type == 'customer') {
                $account_transaction_data['type'] = 'debit';

                if (!empty($inputs) && $use_issued_pd) {
                    $account_transaction_data['related_account_id'] = $account_id;
                    $account_transaction_data['account_id'] = $post_dated;
                }
            }
            if ($contact->type == 'supplier') {
                $account_transaction_data['type'] = 'credit';

                if (!empty($inputs) && $use_issued_pd) {
                    $account_transaction_data['related_account_id'] = $account_id;
                    $account_transaction_data['account_id'] = $issued_post_dated;
                }
            }
        }

        if ($payment_type == 'security_deposit_refund') {
            if ($contact->type == 'customer') {
                $account_transaction_data['type'] = 'credit';

                if (!empty($inputs) && $use_issued_pd) {
                    $account_transaction_data['related_account_id'] = $account_id;
                    $account_transaction_data['account_id'] = $issued_post_dated;
                }
            }
            if ($contact->type == 'supplier') {
                $account_transaction_data['type'] = 'debit';

                if (!empty($inputs) && $use_issued_pd) {
                    $account_transaction_data['related_account_id'] = $account_id;
                    $account_transaction_data['account_id'] = $post_dated;
                }
            }
        }

        if ($payment_type == 'deposit') {
            $account_transaction_data['type'] = 'debit';
        }

        // dd($account_transaction_data);

        AccountTransaction::createAccountTransaction($account_transaction_data);


        if ($payment_type == 'advance_payment') {
            if ($contact->type == 'customer') {
                $account_transaction_data['account_id'] = $this->account_exist_return_id('Accounts Receivable');
                $account_transaction_data['type'] = 'credit';
                ContactLedger::createContactLedger($account_transaction_data);
            }
            if ($contact->type == 'supplier') {
                $account_transaction_data['account_id'] = $this->account_exist_return_id('Advances to Suppliers');
                $account_transaction_data['type'] = 'debit';
                ContactLedger::createContactLedger($account_transaction_data);
            }
        }
        if ($payment_type == 'security_deposit') {
            if ($contact->type == 'customer') {
                $account_transaction_data['account_id'] = $current_liability_account;
                $account_transaction_data['type'] = 'credit';
            }
            if ($contact->type == 'supplier') {
                $account_transaction_data['account_id'] = $this->account_exist_return_id('Company Deposits');
                $account_transaction_data['type'] = 'debit';
            }
        }

        if ($payment_type == 'security_deposit_refund') {
            if ($contact->type == 'customer') {
                $account_transaction_data['account_id'] = $current_liability_account;
                $account_transaction_data['type'] = 'debit';
            }
            if ($contact->type == 'supplier') {
                $account_transaction_data['account_id'] = $current_liability_account;
                $account_transaction_data['type'] = 'credit';
            }
        }

        if ($payment_type == 'deposit') {
            $account_transaction_data['account_id'] = $this->account_exist_return_id('Customer Deposits');
            $account_transaction_data['type'] = 'credit';
        }
        AccountTransaction::createAccountTransaction($account_transaction_data);
        return $transaction;
    }
    /**
     * Creates a new refund payment transaction for a contact
     *
     * @param  int $business_id
     * @param  int $contact_id
     * @param  int $amount
     *
     * @return object
     */

    public function createRefundPaymentTransaction($business_id, $contact_id, $amount, $account_id, $payment_type, $transaction_date, $cheque_number = null, $bank_name, $cheque_date)
    {
        $business_location = BusinessLocation::where('business_id', $business_id)
            ->first();
        $final_amount = $this->num_uf($amount);
        if ($payment_type == 'cheque_return') {
            $payment_status = 'due';
        } else {
            $payment_status = 'paid';
        }
        $cheque_return_charges = !empty(request()->cheque_return_charges) ? $this->num_uf(request()->cheque_return_charges) : 0;
        $ob_data = [
            'business_id' => $business_id,
            'location_id' => $business_location->id,
            'type' => $payment_type,
            'status' => 'final',
            'payment_status' => $payment_status,
            'contact_id' => $contact_id,
            'transaction_date' => $transaction_date,
            'total_before_tax' => $final_amount,
            // 'final_total' => $final_amount + $cheque_return_charges, //adding $cheque_return_charges to final total
            'final_total' => $final_amount, //adding $cheque_return_charges to final total gave wrong amount value in account transactions and contact ledger tables
            'cheque_return_charges' => $cheque_return_charges,  // cheque return charges amount
            'invoice_no' => request()->sale_invoice_bill_number,  // invoice no for refund
            'created_by' => request()->session()->get('user.id')
        ];
        //Update reference count
        $ob_ref_count = $this->setAndGetReferenceCount($payment_type);
        //Generate reference number
        $ob_data['ref_no'] = $this->generateReferenceNumber($payment_type, $ob_ref_count);
        //Create opening balance transaction
        $transaction = Transaction::create($ob_data);
        $account_transaction_data = [
            'amount' => abs($transaction->final_total),
            'account_id' => $account_id,
            'contact_id' => $contact_id,
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'cheque_number' => $cheque_number,
            'note' => $payment_type == 'cheque_return' ? 'Cheque Return' : null,
            'sub_type' => $payment_type == 'cheque_return' ? 'cheque_return' : null,
            'bank_name' => $bank_name,
            'cheque_date' => $cheque_date,
        ];
        //Update reference count
        $cr_ref_count = $this->setAndGetReferenceCount($payment_type);
        //cheque_ref_no
        $account_transaction_data['cheque_ref_no'] = $this->generateReferenceNumber($payment_type, $cr_ref_count);

        if ($payment_type == 'refund' || $payment_type == 'cheque_return') {
            $account_transaction_data['type'] = 'credit';
            AccountTransaction::createAccountTransaction($account_transaction_data);

            $account_transaction_data['account_id'] = $this->account_exist_return_id('Accounts Receivable');
            $account_transaction_data['type'] = 'debit';

            AccountTransaction::createAccountTransaction($account_transaction_data);
            ContactLedger::createContactLedger($account_transaction_data);
        }
        //ledger and account transaction for cheque return charges amount
        if ($payment_type == 'cheque_return') {
            if (!empty(request()->cheque_return_charges)) {
                $account_transaction_data['amount'] = !empty(request()->cheque_return_charges) ? request()->cheque_return_charges : 0;
                $account_transaction_data['account_id'] = $this->account_exist_return_id('Accounts Receivable');
                $account_transaction_data['type'] = 'debit';
                AccountTransaction::createAccountTransaction($account_transaction_data);
                $account_transaction_data['sub_type'] = 'cheque_return_charges';
                ContactLedger::createContactLedger($account_transaction_data);
                $account_transaction_data['account_id'] = $this->account_exist_return_id('Cheque Return Income');
                $account_transaction_data['type'] = 'credit';
                AccountTransaction::createAccountTransaction($account_transaction_data);
            }
        }
        return $transaction;
    }
    /**
     * Updates quantity sold in purchase line for sell return
     *
     * @param  obj $sell_line
     * @param  decimal $new_quantity
     * @param  decimal $old_quantity
     *
     * @return void
     */

    public function getTotalAmountPaid($transaction_id)
    {
        $paid = TransactionPayment::where(
            'transaction_id',
            $transaction_id
        )->sum('amount');
        return $paid;
    }
    /**
     * Calculates transaction totals for the given transaction types
     *
     * @param  int $business_id
     * @param  array $transaction_types
     * available types = ['purchase_return', 'sell_return', 'expense',
     * 'stock_adjustment', 'sell_transfer', 'purchase', 'sell']
     * @param  string $start_date = null
     * @param  string $end_date = null
     * @param  int $location_id = null
     * @param  int $created_by = null
     *
     * @return array
     */

    public function updatePostdatedCheque($transaction)
    {
        $payments = TransactionPayment::where('transaction_id', $transaction->id)->get();
        if ($payments->count()) {
            foreach ($payments as $tp) {
                //update account transaction
                AccountTransaction::where('transaction_payment_id', $tp->id)->update(['type' => $tp->post_dated_cheque == 1 ? 'debit' : 'credit', 'post_dated_cheque' => $tp->post_dated_cheque, 'update_post_dated_cheque' => $tp->update_post_dated_cheque]);
            }
        }
    }
}

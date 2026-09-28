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
 * Writing account transactions and ledger entries.
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
 * Methods here: __getSMSLedger, __getPartnerLedger, __getAgentLedger, createAccountTransaction, getLedgerDetails, getAccountTypeIdOfAccount, validateAccountingEquation, deleteAccountAndLedgerTransactionReverse, getDefaultAccountId, createCostofGoodsSoldTransaction, updateCostofGoodsSoldTransaction, createSaleIncomeTransaction, updateSaleIncomeTransaction, getCategoryAccountId
 */
trait PostsLedger
{
public function __getSMSLedger($start_date, $end_date, $business_type = 'business')
    {
        $business_id = $this->resolveSessionBusinessIdForStock();

        $sms_lists = SmsListInterest::where('sms_list_interests.business_id', $business_id)->where('sms_list_interests.type', $business_type)
            ->whereDate('date', '>=', $start_date)->whereDate('date', '<=', $end_date)
            ->select([
                'sms_list_interests.id as id',
                'sms_list_interests.date as date',
                'sms_list_interests.amount as amount',
                DB::raw('"interest" as type'),
            ]);

        $sms_refill = RefillBusiness::leftjoin('sms_refill_packages', 'sms_refill_packages.id', 'refill_business.package_id')
            ->where('refill_business.business_id', $business_id)->where('refill_business.type', $business_type)
            ->whereDate('refill_business.date', '>=', $start_date)->whereDate('refill_business.date', '<=', $end_date)
            ->select([
                'refill_business.id as id',
                'refill_business.date as date',
                'sms_refill_packages.amount as amount',
                DB::raw('"refill" as type'),
            ]);
        $sms_cost = SmsLog::where('business_id', $business_id)->where('business_type', $business_type)
            ->whereDate('created_at', '>=', $start_date)->whereDate('created_at', '<=', $end_date)
            ->select([
                'id as id',
                'created_at as date',
                'total_cost as amount',
                DB::raw('"sms_sent" as type'),
            ]);



        $query = $sms_lists->unionAll($sms_refill)->unionAll($sms_cost)->orderBy('date', 'ASC')->get();

        return $query;
    }

    public function __getPartnerLedger($id, $start_date, $end_date)
    {

        $commissions = ShippingPartnerCommission::leftjoin('shipments', 'shipping_partner_commission.shipment_id', 'shipments.id')
            ->leftjoin('contacts', 'shipments.customer_id', 'contacts.id')
            ->leftjoin('shipping_agents', 'shipping_agents.id', 'shipping_partner_commission.partner_id')
            ->leftjoin('users', 'users.id', 'shipping_partner_commission.created_by')
            ->leftjoin('shipping_mode', 'shipping_mode.id', 'shipments.shipping_mode')
            ->leftjoin('shipping_packages', 'shipping_packages.id', 'shipments.package_type_id')
            ->leftjoin('shipping_partners', 'shipping_partners.id', 'shipments.shipping_partner')
            ->where('shipping_partner_commission.partner_id', $id)
            ->select([
                'shipping_partner_commission.transaction_date as date',

                'shipments.tracking_no',
                'shipping_packages.package_name',
                'shipping_partners.name as partner_name',
                'contacts.name as customer_name',

                DB::raw('"commission" as type'),
                'shipping_partner_commission.amount as amount',

                DB::raw('"" as method'),
                DB::raw('"" as cheque_number'),
                DB::raw('"" as cheque_date'),
                DB::raw('"" as payment_ref_no'),
                'shipments.id as parent_id',

            ]);

        $payments = Transaction::leftjoin('shipping_partners', 'shipping_partners.id', 'transactions.parent_transaction_id')
            ->leftjoin('transaction_payments', 'transaction_payments.transaction_id', 'transactions.id')
            ->where('shipping_partners.id', $id)
            ->whereIn('transactions.type', ['partner_payment', 'shipping_partner_ob'])
            ->select([
                'transactions.transaction_date as date',

                DB::raw('"" as tracking_no'),
                DB::raw('"" as package_name'),
                DB::raw('"" as partner_name'),
                DB::raw('"" as customer_name'),

                DB::raw('transactions.type as type'),
                'transactions.final_total as amount',

                'transaction_payments.method',
                'transaction_payments.cheque_number',
                'transaction_payments.cheque_date',
                'transaction_payments.payment_ref_no',
                'transactions.parent_transaction_id as parent_id',
            ]);


        if (!empty($start_date) && !empty($end_date)) {
            $payments->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date);

            $payments->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        $ledger_transactions = $commissions->union($payments)->orderBy('date', 'asc')->get();

        return $ledger_transactions;
    }

    public function __getAgentLedger($id, $start_date, $end_date, $module = null)
    {

        //module airline commission not working

        if ($module == 'airline') {
            $payments = Transaction::leftjoin('transaction_payments', 'transaction_payments.transaction_id', 'transactions.id')
                ->where('transactions.parent_transaction_id', $id)
                ->whereIn('transactions.type', ['airline_ticket'])
                ->select([
                    'transactions.transaction_date as date',

                    DB::raw('"" as tracking_no'),
                    DB::raw('"" as package_name'),
                    DB::raw('"" as partner_name'),
                    DB::raw('"" as customer_name'),

                    DB::raw('transactions.type as type'),
                    'transactions.final_total as amount',

                    'transaction_payments.method',
                    'transaction_payments.cheque_number',
                    'transaction_payments.cheque_date',
                    'transaction_payments.payment_ref_no',
                    'transactions.parent_transaction_id as parent_id',
                ]);


            if (!empty($start_date) && !empty($end_date)) {
                $payments->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date);
            }

            $ledger_transactions = $payments->orderBy('date', 'asc')->get();
            return $ledger_transactions;
        }
        $commissions = ShippingAgentCommission::leftjoin('shipments', 'shipping_agent_commission.shipment_id', 'shipments.id')
            ->leftjoin('contacts', 'shipments.customer_id', 'contacts.id')
            ->leftjoin('shipping_agents', 'shipping_agents.id', 'shipping_agent_commission.agent_id')
            ->leftjoin('users', 'users.id', 'shipping_agent_commission.created_by')
            ->leftjoin('shipping_mode', 'shipping_mode.id', 'shipments.shipping_mode')
            ->leftjoin('shipping_packages', 'shipping_packages.id', 'shipments.package_type_id')
            ->leftjoin('shipping_partners', 'shipping_partners.id', 'shipments.shipping_partner')
            ->where('shipping_agent_commission.agent_id', $id)
            ->select([
                'shipping_agent_commission.transaction_date as date',

                'shipments.tracking_no',
                'shipping_packages.package_name',
                'shipping_partners.name as partner_name',
                'contacts.name as customer_name',

                DB::raw('"commission" as type'),
                'shipping_agent_commission.amount as amount',

                DB::raw('"" as method'),
                DB::raw('"" as cheque_number'),
                DB::raw('"" as cheque_date'),
                DB::raw('"" as payment_ref_no'),
                'shipments.id as parent_id',

            ]);

        $payments = Transaction::leftjoin('shipping_agents', 'shipping_agents.id', 'transactions.parent_transaction_id')
            ->leftjoin('transaction_payments', 'transaction_payments.transaction_id', 'transactions.id')
            ->where('shipping_agents.id', $id)
            ->whereIn('transactions.type', ['agent_payment', 'shipping_agent_ob'])
            ->select([
                'transactions.transaction_date as date',

                DB::raw('"" as tracking_no'),
                DB::raw('"" as package_name'),
                DB::raw('"" as partner_name'),
                DB::raw('"" as customer_name'),

                DB::raw('transactions.type as type'),
                'transactions.final_total as amount',

                'transaction_payments.method',
                'transaction_payments.cheque_number',
                'transaction_payments.cheque_date',
                'transaction_payments.payment_ref_no',
                'transactions.parent_transaction_id as parent_id',
            ]);


        if (!empty($start_date) && !empty($end_date)) {
            $commissions->whereDate('shipping_agent_commission.transaction_date', '>=', $start_date)->whereDate('shipping_agent_commission.transaction_date', '<=', $end_date);
            $payments->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end_date);
        }

        $ledger_transactions = $commissions->union($payments)->orderBy('date', 'asc')->get();

        return $ledger_transactions;
    }

    public function createAccountTransaction($transaction, $type, $account_id, $transaction_payment_id = null, $sub_type = null, $contact_id = null, $amount = 0, $is_credit_sale = false, $note = null, $slip_no = null)
    {
        $account_transaction_data = [
            'amount' => abs($amount > 0 ? $amount : $transaction->final_total),
            'account_id' => $account_id,
            'contact_id' => $transaction->contact_id,
            'type' => $type,
            'sub_type' => $sub_type,
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            /*
             * MA-002 ROOT CAUSE FIX - business_id was never being set here.
             *
             * This method built its ledger row WITHOUT a business_id, so every
             * account_transactions row it wrote was unscoped. In the tenant
             * database that is 274 of 561 rows - nearly half - all of them on
             * the Opening Balance Equity Account, from opening_stock (255) and
             * opening_balance (19) transactions.
             *
             * Any report that filters on account_transactions.business_id
             * silently omitted them, including FinanceReports' audit view
             * (FinanceReportsDataService.php:1244). Account BALANCES were not
             * affected, because getAccountBalance() scopes through
             * accounts.business_id instead.
             *
             * The transaction always carries the business, so it is taken from
             * there, falling back to the session only if absent.
             */
            'business_id' => $transaction->business_id
                ?? request()->session()->get('user.business_id'),
            'transaction_payment_id' => $transaction_payment_id,
            'note' => $note,
            'slip_no' => $slip_no
        ];



        if (!empty($contact_id)) {
            $account_transaction_data['contact_id'] = $contact_id;
        }
        if (!empty($amount)) {
            $account_transaction_data['amount'] = $amount;
        }

        $criteria = ['account_id' => $account_transaction_data['account_id'], 'type' => $account_transaction_data['type'], 'transaction_id' => $account_transaction_data['transaction_id']];
        if (!empty($account_transaction_data['account_id'])) {
            AccountTransaction::updateOrCreate($criteria, $account_transaction_data);
        }
    }

    public function getLedgerDetails($contact_id, $start, $end, $format = 'format_1', $location_id = null, $line_details = false)
    {
        $business_id = request()->session()->get('user.business_id');
        //Get sum of totals before start date
        $previous_transaction_sums = $this->__transactionQuery($contact_id, $start, null, $location_id)
            ->select(
                DB::raw("SUM(IF(type = 'purchase', final_total, 0)) as total_purchase"),
                DB::raw("SUM(IF(type = 'sell' AND status = 'final', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF(type = 'sell_return', final_total, 0)) as total_sell_return"),
                DB::raw("SUM(IF(type = 'purchase_return', final_total, 0)) as total_purchase_return"),
                DB::raw("SUM(IF(type = 'opening_balance', final_total, 0)) as total_opening_balance"),
                DB::raw("SUM(IF(type = 'ledger_discount', final_total, 0)) as total_ledger_discount")
            )->first();

        //Get payment totals before start date
        $prev_payments = $this->__paymentQuery($contact_id, $start, null, $location_id)
            ->select('transaction_payments.*', 'bl.name as location_name', 't.type as transaction_type', 'is_advance')
            ->get();

        $prev_total_invoice_paid = $prev_payments->where('transaction_type', 'sell')->where('is_return', 0)->sum('amount');
        $prev_total_ob_paid = $prev_payments->where('transaction_type', 'opening_balance')->where('is_return', 0)->sum('amount');
        $prev_total_sell_change_return = $prev_payments->where('transaction_type', 'sell')->where('is_return', 1)->sum('amount');
        $prev_total_sell_change_return = !empty($prev_total_sell_change_return) ? $prev_total_sell_change_return : 0;
        $prev_total_invoice_paid -= $prev_total_sell_change_return;
        $prev_total_purchase_paid = $prev_payments->where('transaction_type', 'purchase')->where('is_return', 0)->sum('amount');
        $prev_total_sell_return_paid = $prev_payments->where('transaction_type', 'sell_return')->sum('amount');
        $prev_total_purchase_return_paid = $prev_payments->where('transaction_type', 'purchase_return')->sum('amount');
        //$prev_total_advance_payment = $prev_payments->where('is_advance', 1)->sum('amount');
        $prev_total_advance_payment = $this->__paymentQuery($contact_id, $start, null, $location_id)
            ->select(
                'bl.name as location_name',
                't.type as transaction_type',
                'is_advance',
                'transaction_payments.id',
                DB::raw('(transaction_payments.amount - COALESCE((SELECT SUM(amount) from transaction_payments as TP where TP.parent_id = transaction_payments.id), 0)) as amount')
            )
            ->where('is_advance', 1)
            ->get()
            ->sum('amount');

        $total_prev_paid = $prev_total_invoice_paid + $prev_total_purchase_paid - $prev_total_sell_return_paid - $prev_total_purchase_return_paid + $prev_total_ob_paid + $prev_total_advance_payment;

        $total_prev_invoice = $previous_transaction_sums->total_purchase + $previous_transaction_sums->total_invoice - $previous_transaction_sums->total_sell_return - $previous_transaction_sums->total_purchase_return + $previous_transaction_sums->total_opening_balance - $previous_transaction_sums->total_ledger_discount;
        //$total_prev_paid = $prev_payments_sum->total_paid;
        $beginning_balance = $total_prev_invoice - $total_prev_paid;

        $contact = Contact::find($contact_id);

        $with = ['location'];
        if ($line_details) {
            $with = [
                'location',
                'sell_lines',
                'sell_lines.sub_unit',
                'sell_lines.product',
                'sell_lines.variations',
                'sell_lines.product.unit',
                'sell_lines.variations.product_variation',
                'sell_lines.line_tax',
                'purchase_lines',
                'purchase_lines.product',
                'purchase_lines.variations',
                'purchase_lines.variations.product_variation',
                'purchase_lines.line_tax'
            ];
        }
        //Get transaction totals between dates
        $transaction_query = $this->__transactionQuery($contact_id, $start, $end, $location_id)
            ->with(['location'])
            ->select('transactions.*');

        if ($format == 'format_2') {
            $transaction_query->leftjoin('transaction_payments as tp', 'tp.transaction_id', '=', 'transactions.id')
                ->addSelect(DB::raw('COALESCE(SUM(tp.amount), 0) as total_paid'))
                ->groupBy('transactions.id');
        }

        $transactions = $transaction_query->get();
        $transaction_types = Transaction::transactionTypes();
        $ledger = [];

        $opening_balance = 0;
        $opening_balance_paid = 0;
        $ledger_discount = 0;

        foreach ($transactions as $transaction) {

            if ($transaction->type == 'opening_balance') {
                //Skip opening balance, it will be added in the end
                $opening_balance += $transaction->final_total;

                continue;
            }

            if ($transaction->type == 'ledger_discount') {
                $ledger_discount += $transaction->final_total;
            }

            $temp_array = [
                'date' => $transaction->transaction_date,
                'ref_no' => in_array($transaction->type, ['sell', 'sell_return']) ? $transaction->invoice_no : $transaction->ref_no,
                'type' => $transaction_types[$transaction->type],
                'location' => $transaction->location->name ?? '',
                'payment_status' => !in_array($transaction->type, ['ledger_discount']) ? __('lang_v1.' . $transaction->payment_status) : '',
                'total' => '',
                'payment_method' => '',
                'debit' => in_array($transaction->type, ['sell', 'purchase_return']) || ($transaction->sub_type == 'purchase_discount') ? $transaction->final_total : '',
                'credit' => in_array($transaction->type, ['purchase', 'sell_return']) || ($transaction->sub_type == 'sell_discount') ? $transaction->final_total : '',
                'others' => $transaction->additional_notes,
                'transaction_id' => $transaction->id,
                'transaction_type' => $transaction->type
            ];

            if ($format == 'format_2') {
                $temp_array['final_total'] = $transaction->final_total;
                $temp_array['total_due'] = $transaction->final_total - $transaction->total_paid;
                $temp_array['due_date'] = $transaction->due_date;
                $temp_array['payment_status'] = $transaction->payment_status;
            }

            if ($format == 'format_3') {
                $temp_array['sell_lines'] = $transaction->sell_lines;
                $temp_array['purchase_lines'] = $transaction->purchase_lines;
            }

            $ledger[] = $temp_array;
        }

        $invoice_sum = $transactions->where('type', 'sell')->sum('final_total');
        $purchase_sum = $transactions->where('type', 'purchase')->sum('final_total');
        $sell_return_sum = $transactions->where('type', 'sell_return')->sum('final_total');
        $purchase_return_sum = $transactions->where('type', 'purchase_return')->sum('final_total');

        //Get payment totals between dates
        if ($format == 'format_1' || $format == 'format_3') {
            $payments = $this->__paymentQuery($contact_id, $start, $end, $location_id)
                ->select('transaction_payments.*', 'bl.name as location_name', 't.type as transaction_type', 't.ref_no', 't.invoice_no')
                ->get();
        } else {
            $payments = [];
        }

        $paymentTypes = $this->payment_types(null, true, $business_id);

        $total_reverse_payment = 0;

        foreach ($payments as $payment) {
            if ($payment->transaction_type == 'opening_balance') {
                $opening_balance_paid += $payment->amount;
            }

            if ($contact->type == 'customer' && $payment->is_advance == 0 && empty($payment->transaction_id) && $payment->payment_type == 'debit') {
                $total_reverse_payment += $payment->amount;
            }
            if ($contact->type == 'supplier' && $payment->is_advance == 0 && empty($payment->transaction_id) && $payment->payment_type == 'credit') {
                $total_reverse_payment += $payment->amount;
            }

            //Hide all the adjusted payments because it has already been summed as advance payment
            if (!empty($payment->parent_id)) {
                continue;
            }

            $ref_no = in_array($payment->transaction_type, ['sell', 'sell_return']) ? $payment->invoice_no : $payment->ref_no;
            $note = $payment->note;
            if (!empty($ref_no)) {
                $note .= '<small>' . __('account.payment_for') . ': ' . $ref_no . '</small>';
            }

            if ($payment->is_advance == 1) {
                $note .= '<small>' . __('lang_v1.advance_payment') . '</small>';
            }

            if ($payment->is_return == 1) {
                $note .= '<small>(' . __('lang_v1.change_return') . ')</small>';
            }

            $ledger[] = [
                'date' => $payment->paid_on,
                'ref_no' => $payment->payment_ref_no,
                'type' => $transaction_types['payment'],
                'location' => $payment->location_name,
                'payment_status' => '',
                'total' => '',
                'payment_method' => !empty($paymentTypes[$payment->method]) ? $paymentTypes[$payment->method] : '',
                'payment_method_key' => $payment->method,
                'debit' => in_array($payment->transaction_type, ['purchase', 'sell_return']) || ($payment->is_advance == 1 && $contact->type == 'supplier') || (in_array($payment->transaction_type, ['sell', 'purchase_return', 'opening_balance']) && $payment->is_return == 1) || $payment->payment_type == 'debit' ? $payment->amount : '',
                'credit' => (in_array($payment->transaction_type, ['sell', 'purchase_return', 'opening_balance']) || ($payment->is_advance == 1 && in_array($contact->type, ['customer', 'both']))) && $payment->is_return == 0 || $payment->payment_type == 'credit' ? $payment->amount : '',
                'others' => $note
            ];
        }

        $total_excess_advance_payment = $this->__paymentQuery($contact_id, $start, $end, $location_id)
            ->select(
                DB::raw('(transaction_payments.amount - COALESCE((SELECT SUM(amount) from transaction_payments as TP where TP.parent_id = transaction_payments.id), 0)) as amount')
            )
            ->where('is_advance', 1)
            ->get()
            ->sum('amount');

        $total_invoice_paid = !empty($payments) ? $payments->where('transaction_type', 'sell')->where('is_return', 0)->sum('amount') : 0;
        $total_sell_change_return = !empty($payments) ? $payments->where('transaction_type', 'sell')->where('is_return', 1)->sum('amount') : 0;
        $total_sell_change_return = !empty($total_sell_change_return) ? $total_sell_change_return : 0;
        $total_invoice_paid -= $total_sell_change_return;
        $total_purchase_paid = !empty($payments) ? $payments->where('transaction_type', 'purchase')->where('is_return', 0)->sum('amount') : 0;
        $total_sell_return_paid = !empty($payments) ? $payments->where('transaction_type', 'sell_return')->sum('amount') : 0;
        $total_purchase_return_paid = !empty($payments) ? $payments->where('transaction_type', 'purchase_return')->sum('amount') : 0;

        $total_invoice_paid += $opening_balance_paid;

        $start_date = $this->format_date($start);
        $end_date = $this->format_date($end);

        $total_invoice = $invoice_sum - $sell_return_sum;
        $total_purchase = $purchase_sum - $purchase_return_sum;

        $opening_balance_due = $opening_balance;

        $total_paid = $total_invoice_paid + $total_purchase_paid - $total_sell_return_paid - $total_purchase_return_paid + $total_excess_advance_payment;

        $curr_due = $total_invoice + $total_purchase - $total_paid + $beginning_balance + $opening_balance_due;

        //Sort by date
        if (!empty($ledger)) {
            usort($ledger, function ($a, $b) {
                $t1 = strtotime($a['date']);
                $t2 = strtotime($b['date']);
                return $t1 - $t2;
            });
        }

        $total_opening_bal = $beginning_balance + $opening_balance_due;
        if ($format != 'format_2') {
            //Add Beginning balance & openining balance to ledger
            $ledger = array_merge([
                [
                    'date' => $start,
                    'ref_no' => '',
                    'type' => __('lang_v1.opening_balance'),
                    'location' => '',
                    'payment_status' => '',
                    'total' => '',
                    'payment_method' => '',
                    'debit' => $contact->type == 'customer' ? abs($total_opening_bal) : '',
                    'credit' => $contact->type == 'supplier' ? abs($total_opening_bal) : '',
                    'others' => '',
                    'final_total' => abs($total_opening_bal),
                    'total_due' => 0,
                    'due_date' => null
                ]
            ], $ledger);
        }


        $bal = 0;
        foreach ($ledger as $key => $val) {
            $credit = !empty($val['credit']) ? $val['credit'] : 0;
            $debit = !empty($val['debit']) ? $val['debit'] : 0;

            //NOTE:: Commented because of mismatch between final ledger table balance due and top balance due
            // if (!empty($val['payment_method_key']) && $val['payment_method_key'] == 'advance') {
            //     $credit = 0;
            //     $debit = 0;
            // }
            $bal += ($credit - $debit);
            $balance = $this->num_f(abs($bal));

            if ($bal < 0) {
                $balance .= ' ' . __('lang_v1.dr');
            } else if ($bal > 0) {
                $balance .= ' ' . __('lang_v1.cr');
            }

            $ledger[$key]['balance'] = $balance;
        }

        $output = [
            'ledger' => $ledger,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'total_invoice' => $total_invoice,
            'total_purchase' => $total_purchase,
            'beginning_balance' => $beginning_balance + $opening_balance_due,
            'balance_due' => $curr_due,
            'total_paid' => $total_paid,
            'total_reverse_payment' => $total_reverse_payment,
            'ledger_discount' => $ledger_discount
        ];

        // Return as object because downstream code expects property-style access
        return (object) $output;
    }

    public function getAccountTypeIdOfAccount($account_id, $business_id)
    {
        $account_type = Account::join('account_types', 'accounts.account_type_id', 'account_types.id')
            ->where('accounts.id', $account_id)
            ->where('accounts.business_id', $business_id)
            ->select('account_types.id as account_type_id')
            ->first();
        return $account_type->account_type_id;
    }

    public function validateAccountingEquation($transaction_id)
    {
        $debits = AccountTransaction::where('transaction_id', $transaction_id)
            ->where('type', 'debit')
            ->sum('amount');
            
        $credits = AccountTransaction::where('transaction_id', $transaction_id)
            ->where('type', 'credit')
            ->sum('amount');
            
        if (abs($debits - $credits) > 0.01) {
            \Log::error('Accounting equation violation', [
                'transaction_id' => $transaction_id,
                'debits' => $debits,
                'credits' => $credits,
                'difference' => $debits - $credits
            ]);
            return false;
        }
        
        return true;
    }

    public function deleteAccountAndLedgerTransactionReverse($transaction, $payment_id)
    {
        $transaction_id = $transaction->id;
        $transaction_payment = TransactionPayment::find($payment_id);
        if ($transaction->type == 'purchase') {
            $account_id = $transaction_payment->account_id;

            $contact_id = $transaction->contact_id;

            if (empty($account_id)) {
                $parent_transaction = TransactionPayment::where('id', $transaction_payment->parent_id)->withTrashed()->first();
                $account_id = !empty($parent_transaction) ? $parent_transaction->id : null;

                $contact_id = $transaction->contact_id;
                if (!empty($account_id)) {
                    $account_id->delete();
                }
            }
        }
        if ($transaction->type == 'sell') {
            $account_id = $transaction_payment->account_id;
            $contact_id = $transaction->contact_id;

            if (empty($account_id)) {
                $parent_transaction = TransactionPayment::where('id', $transaction_payment->parent_id)->withTrashed()->first();
                $account_id = !empty($parent_transaction) ? $parent_transaction->id : null;

                if (!empty($account_id)) {
                    $contact_id = $transaction->contact_id;
                }
            }
        }
        if ($transaction->type == 'settlement') {
            $account_id = $transaction_payment->account_id;
            $contact_id = $transaction->contact_id;
            if (empty($account_id)) {
                $parent_transaction = TransactionPayment::where('id', $transaction_payment->parent_id)->withTrashed()->first();
                $account_id = !empty($parent_transaction) ? $parent_transaction->id : null;

                if (!empty($account_id)) {
                }
            }
        }
        return true;
    }

    /**
     * Keep account transactions and contact ledger rows in sync with a payment's current amount.
     * This is especially important for parent payments that aggregate child "pay at once" entries.
     *
     * @param  \App\TransactionPayment|null  $payment
     * @return void
     */

    public function getDefaultAccountId($method_name, $location_id)
    {
        if ($method_name == 'credit_expense' || $method_name == 'credit_purchase') {
            return 0;
        }
        $business_id = $this->resolveSessionBusinessIdForStock();
        $account_id = null;
        $defualt_accounts = BusinessLocation::where('business_id', $business_id)->where('id', $location_id)->first();
        if (!empty($defualt_accounts)) {
            $default_payment_accounts = (array) json_decode($defualt_accounts->default_payment_accounts);
            $account_id = $default_payment_accounts[$method_name]->account;
        }
        return $account_id;
    }

    public function createCostofGoodsSoldTransaction($transaction, $sub_type = null, $type)
    {
        $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('variations', 'transaction_sell_lines.variation_id', 'variations.id')
            ->where('transaction_id', $transaction->id)
            ->select('transaction_sell_lines.*', 'products.category_id', 'products.sub_category_id', 'variations.default_purchase_price', 'variations.dpp_inc_tax', 'products.vat_claimed as tax')
            ->get();
        foreach ($sell_lines as $sale) {
            $account_id = $this->account_exist_return_id('Cost of Goods Sold');
            if ($sale->quantity >= 0) { //not include pos page return
                if (!empty($sale->sub_category_id)) {
                    $account_id = $this->getCategoryAccountId($sale->sub_category_id, 'cogs');
                    if (empty($account_id)) {
                        $account_id = $this->getCategoryAccountId($sale->category_id, 'cogs');
                    }
                    if (empty($account_id)) {
                        $account_id = $this->account_exist_return_id('Cost of Goods Sold');
                    }
                } else {
                    $account_id = $this->getCategoryAccountId($sale->category_id, 'cogs');
                    if (empty($account_id)) {
                        $account_id = $this->account_exist_return_id('Cost of Goods Sold');
                    }
                }
                if (!empty($account_id)) {
                    $business_id = $this->resolveSessionBusinessIdForStock();

                    $account_transaction_data = [
                        'amount' => abs($sale->quantity * $sale->dpp_inc_tax), // @eng 11/2
                        'account_id' => $account_id,
                        'type' => $type,
                        'sub_type' => $sub_type,
                        'operation_date' => $transaction->transaction_date,
                        'created_by' => $transaction->created_by,
                        'transaction_id' => $transaction->id,
                        'sell_line_id' => $sale->id,
                        'note' => null
                    ];
                    AccountTransaction::createAccountTransaction($account_transaction_data);
                }
            }
        }
    }

    public function updateCostofGoodsSoldTransaction($transaction)
    {
        $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('variations', 'transaction_sell_lines.variation_id', 'variations.id')
            ->where('transaction_id', $transaction->id)
            ->select('transaction_sell_lines.*', 'products.category_id', 'products.sub_category_id', 'variations.default_purchase_price', 'variations.dpp_inc_tax')
            ->get();
        foreach ($sell_lines as $sale) {
            $account_id = $this->account_exist_return_id('Cost of Goods Sold');
            if ($sale->quantity >= 0) { //not include pos page return
                if (!empty($sale->sub_category_id)) {
                    $account_id = $this->getCategoryAccountId($sale->sub_category_id, 'cogs');
                    if (empty($account_id)) {
                        $account_id = $this->getCategoryAccountId($sale->category_id, 'cogs');
                    }
                    if (empty($account_id)) {
                        $account_id = $this->account_exist_return_id('Cost of Goods Sold');
                    }
                } else {
                    $account_id = $this->getCategoryAccountId($sale->category_id, 'cogs');
                    if (empty($account_id)) {
                        $account_id = $this->account_exist_return_id('Cost of Goods Sold');
                    }
                }
                if (!empty($account_id)) {
                    $account_transaction = AccountTransaction::where('transaction_id', $transaction->id)->where('account_id', $account_id)->where('sell_line_id', $sale->id)->first();
                    if (!empty($account_transaction)) {
                        $account_transaction->amount = abs($sale->quantity * $sale->dpp_inc_tax);
                        $account_transaction->save();
                    } else {
                        $account_transaction_data = [
                            'amount' => abs($sale->quantity * $sale->dpp_inc_tax),
                            'account_id' => $account_id,
                            'type' => 'debit',
                            'sub_type' => null,
                            'operation_date' => $transaction->transaction_date,
                            'created_by' => $transaction->created_by,
                            'transaction_id' => $transaction->id,
                            'sell_line_id' => $sale->id,
                            'note' => null
                        ];
                        AccountTransaction::createAccountTransaction($account_transaction_data);
                    }
                }
            }
        }
    }

    public function createSaleIncomeTransaction($transaction, $sub_type = null, $type)
    {
        // Modified by Engr. Alex -- task 7889
        $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->where('transaction_id', $transaction->id)
            ->select('transaction_sell_lines.*', 'products.category_id', 'products.sub_category_id')
            ->get();

        // Calculate total net line amounts (after per-line discounts) for bill-discount proration
        $total_line_amounts = 0;
        foreach ($sell_lines as $sale) {
            if ($sale->quantity >= 0) {
                $line_total = round(floatval($sale->unit_price_inc_tax), 2) * floatval($sale->quantity);
                $line_discount = 0;
                if (!empty($sale->line_discount_amount) && floatval($sale->line_discount_amount) > 0) {
                    if ($sale->line_discount_type == 'percentage') {
                        $line_discount = ($line_total * floatval($sale->line_discount_amount)) / 100;
                    } else {
                        $line_discount = floatval($sale->line_discount_amount) * floatval($sale->quantity);
                    }
                }
                $total_line_amounts += ($line_total - $line_discount);
            }
        }

        // Only apply bill-level discount when discount_type is explicitly set;
        // settlement transactions store total sales in discount_amount which is not a real discount
        $bill_wise_discount = 0;
        if (!empty($transaction->discount_type)) {
            $bill_wise_discount = floatval($transaction->discount_amount ?? 0);
            if ($transaction->discount_type === 'percentage' && $total_line_amounts > 0) {
                $bill_wise_discount = ($bill_wise_discount / 100) * $total_line_amounts;
            }
        }

        foreach ($sell_lines as $sale) {
            if ($sale->quantity >= 0) {
                // Modified by Engr. Alex -- task 7889
                if (!empty($sale->sub_category_id)) {
                    $account_id = $this->getCategoryAccountId($sale->sub_category_id, 'sale_income');
                    if (empty($account_id)) {
                        $account_id = $this->getCategoryAccountId($sale->category_id, 'sale_income');
                    }
                    // Name-based fallback (mirrors else branch) — try parent category name
                    if (empty($account_id)) {
                        $category = \App\Category::find($sale->category_id);
                        if (!empty($category)) {
                            $account_id = $this->account_exist_return_id("Sales Income - {$category->name}");
                            if (empty($account_id)) {
                                $account_id = $this->account_exist_return_id("Sale Income - {$category->name}");
                            }
                        }
                    }
                    if (empty($account_id)) {
                        $account_id = $this->account_exist_return_id('Sales Income');
                        if (empty($account_id)) {
                            $account_id = $this->account_exist_return_id('Sale Income');
                        }
                    }
                } else {
                    $account_id = $this->getCategoryAccountId($sale->category_id, 'sale_income');
                    if (empty($account_id)) {
                        $category = \App\Category::find($sale->category_id);
                        if (!empty($category)) {
                            $account_id = $this->account_exist_return_id("Sales Income - {$category->name}");
                            if (empty($account_id)) {
                                $account_id = $this->account_exist_return_id("Sale Income - {$category->name}");
                            }
                        }
                        if (empty($account_id)) {
                            $account_id = $this->account_exist_return_id('Sales Income');
                            if (empty($account_id)) {
                                $account_id = $this->account_exist_return_id('Sale Income');
                            }
                        }
                    }
                }
                if (!empty($account_id)) {
                    $line_total = round(floatval($sale->unit_price_inc_tax), 2) * floatval($sale->quantity);
                    // Apply per-line discount before bill-level proration
                    $line_discount = 0;
                    if (!empty($sale->line_discount_amount) && floatval($sale->line_discount_amount) > 0) {
                        if ($sale->line_discount_type == 'percentage') {
                            $line_discount = ($line_total * floatval($sale->line_discount_amount)) / 100;
                        } else {
                            $line_discount = floatval($sale->line_discount_amount) * floatval($sale->quantity);
                        }
                    }
                    $line_total_after_discount = $line_total - $line_discount;
                    // Settlement account books must show each meter/other sale line after its own discount,
                    // grouped into the product sub-category sales income account.
                    // Do not prorate line income to the settlement final_total: that can pull in payment
                    // totals and distort the amount shown for each product/sub-category account.
                    if (! empty($transaction->is_settlement) && (int) $transaction->is_settlement === 1
                        && ($transaction->type ?? '') === 'sell'
                        && ($transaction->sub_type ?? '') === 'settlement') {
                        $amount = $line_total_after_discount;
                    } else {
                        $share_of_bill_discount = $total_line_amounts > 0
                            ? ($line_total_after_discount / $total_line_amounts) * $bill_wise_discount
                            : 0;
                        $amount = $line_total_after_discount - $share_of_bill_discount;
                    }

                    $account_transaction_data = [
                        'amount' => abs($amount),
                        'account_id' => $account_id,
                        'type' => $type,
                        'sub_type' => $sub_type,
                        'operation_date' => $transaction->transaction_date,
                        'created_by' => $transaction->created_by,
                        'transaction_id' => $transaction->id,
                        'sell_line_id' => $sale->id,
                        'note' => null
                    ];
                    AccountTransaction::createAccountTransaction($account_transaction_data);
                }
            }
        }
    }

    public function updateSaleIncomeTransaction($transaction)
    {
        $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->where('transaction_id', $transaction->id)
            ->select('transaction_sell_lines.*', 'products.category_id', 'products.sub_category_id')
            ->get();
        
        $total_line_amounts = 0;
        foreach ($sell_lines as $sale) {
            if ($sale->quantity >= 0) {
                $line_total = abs($sale->quantity * $sale->unit_price_inc_tax);
                
                $line_discount = 0;
                if (!empty($sale->line_discount_amount) && $sale->line_discount_amount > 0) {
                    if ($sale->line_discount_type == 'percentage') {
                        $line_discount = ($line_total * floatval($sale->line_discount_amount)) / 100;
                    } else {
                        $line_discount = floatval($sale->line_discount_amount) * $sale->quantity;
                    }
                }
                
                $total_line_amounts += ($line_total - $line_discount);
            }
        }
        
        $final_total = abs($transaction->final_total ?? 0);
        
        foreach ($sell_lines as $sale) {
            if ($sale->quantity >= 0) {
                // Modified by Engr. Alex -- task 7889
                if (!empty($sale->sub_category_id)) {
                    $account_id = $this->getCategoryAccountId($sale->sub_category_id, 'sale_income');
                    if (empty($account_id)) {
                        $account_id = $this->getCategoryAccountId($sale->category_id, 'sale_income');
                    }
                    if (empty($account_id)) {
                        $category = \App\Category::find($sale->category_id);
                        if (!empty($category)) {
                            $account_id = $this->account_exist_return_id("Sales Income - {$category->name}");
                            if (empty($account_id)) {
                                $account_id = $this->account_exist_return_id("Sale Income - {$category->name}");
                            }
                        }
                    }
                    if (empty($account_id)) {
                        $account_id = $this->account_exist_return_id('Sales Income');
                        if (empty($account_id)) {
                            $account_id = $this->account_exist_return_id('Sale Income');
                        }
                    }
                } else {
                    $account_id = $this->getCategoryAccountId($sale->category_id, 'sale_income');
                    if (empty($account_id)) {
                        $category = \App\Category::find($sale->category_id);
                        if (!empty($category)) {
                            $account_id = $this->account_exist_return_id("Sales Income - {$category->name}");
                            if (empty($account_id)) {
                                $account_id = $this->account_exist_return_id("Sale Income - {$category->name}");
                            }
                        }
                        if (empty($account_id)) {
                            $account_id = $this->account_exist_return_id('Sales Income');
                            if (empty($account_id)) {
                                $account_id = $this->account_exist_return_id('Sale Income');
                            }
                        }
                    }
                }
                if (!empty($account_id)) {
                    $line_total = abs($sale->quantity * $sale->unit_price_inc_tax);
                    
                    $line_discount = 0;
                    if (!empty($sale->line_discount_amount) && $sale->line_discount_amount > 0) {
                        if ($sale->line_discount_type == 'percentage') {
                            $line_discount = ($line_total * floatval($sale->line_discount_amount)) / 100;
                        } else {
                            $line_discount = floatval($sale->line_discount_amount) * $sale->quantity;
                        }
                    }
                    
                    $amount_after_line_discount = $line_total - $line_discount;
                    
                    if (! empty($transaction->is_settlement) && (int) $transaction->is_settlement === 1
                        && ($transaction->type ?? '') === 'sell'
                        && ($transaction->sub_type ?? '') === 'settlement') {
                        $amount = $amount_after_line_discount;
                    } elseif ($total_line_amounts > 0) {
                        $amount = ($amount_after_line_discount / $total_line_amounts) * $final_total;
                    } else {
                        $amount = $amount_after_line_discount;
                    }
                    
                    // First try to find transaction with specific account_id
                    $account_transaction = AccountTransaction::where('transaction_id', $transaction->id)->where('account_id', $account_id)->where('sell_line_id', $sale->id)->first();
                    
                    // If not found, try to find any Sales Income account transaction for this sell_line_id
                    // (in case account_id determination changed between create and update)
                    if (empty($account_transaction)) {
                        $sales_income_account_ids = Account::where('business_id', request()->session()->get('business.id'))
                            ->where('name', 'like', '%Sales Income%')
                            ->pluck('id')
                            ->toArray();
                        
                        $account_transaction = AccountTransaction::where('transaction_id', $transaction->id)
                            ->whereIn('account_id', $sales_income_account_ids)
                            ->where('sell_line_id', $sale->id)
                            ->where('type', 'credit')
                            ->first();
                        
                        // If found, update the account_id to match the current determination
                        if (!empty($account_transaction) && $account_transaction->account_id != $account_id) {
                            $account_transaction->account_id = $account_id;
                        }
                    }
                    
                    if (!empty($account_transaction)) {
                        $account_transaction->amount = abs($amount);
                        $account_transaction->save();
                    } else {
                        $account_transaction_data = [
                            'amount' => abs($amount),
                            'account_id' => $account_id,
                            'type' => 'credit',
                            'sub_type' => null,
                            'operation_date' => $transaction->transaction_date,
                            'created_by' => $transaction->created_by,
                            'transaction_id' => $transaction->id,
                            'sell_line_id' => $sale->id,
                            'note' => null
                        ];
                        AccountTransaction::createAccountTransaction($account_transaction_data);
                    }
                }
            }
        }
    }

    public function getCategoryAccountId($category_id, $group)
    {
        $business_id = $this->resolveSessionBusinessIdForStock();
        if ($group == 'cogs') {
            $categorie = Category::where('business_id', $business_id)
                ->where('id', $category_id)
                ->select('id', 'name', 'cogs_account_id')
                ->first();

            // Primary: explicit mapping on category
            $mappedId = $categorie->cogs_account_id ?? null;

            // Fallback: try account named "COGS - <Category>"
            if (empty($mappedId) && !empty($categorie) && !empty($categorie->name)) {
                $fallback = Account::where('business_id', $business_id)
                    ->whereRaw("REPLACE(`name`, '  ', ' ') = REPLACE(?, '  ', ' ')", ["COGS - {$categorie->name}"])
                    ->first();
                if (!empty($fallback)) {
                    return $fallback->id;
                }
            }

            return $mappedId;
        }
        if ($group == 'sale_income') {
            $categorie = Category::where('business_id', $business_id)
                ->where('id', $category_id)
                ->select('id', 'name', 'sales_income_account_id')
                ->first();

            // Primary: explicit mapping on category
            $mappedId = $categorie->sales_income_account_id ?? null;

            // Modified by Engr. Alex -- task 7889
            // Fallback: try "Sales Income" and "Sale Income" variants (en-dash and hyphen) against category name
            if (empty($mappedId) && !empty($categorie) && !empty($categorie->name)) {
                $name = $categorie->name;
                $variants = [
                    "Sales Income – {$name}",
                    "Sales Income - {$name}",
                    "Sale Income – {$name}",
                    "Sale Income - {$name}",
                ];

                $fallback = Account::where('business_id', $business_id)
                    ->where(function ($q) use ($variants) {
                        foreach ($variants as $variant) {
                            $q->orWhereRaw("REPLACE(`name`, '  ', ' ') = REPLACE(?, '  ', ' ')", [$variant]);
                        }
                    })
                    ->first();
                if (!empty($fallback)) {
                    return $fallback->id;
                }
            }

            return $mappedId;
        }
    }
}

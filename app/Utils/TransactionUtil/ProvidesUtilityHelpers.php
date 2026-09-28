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
 * Everything else - rewards, SMS and partner balances, small helpers.
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
 * Methods here: normaliseDateRange, validateNos, __getVatEffectiveDate, __getSMSBalance, __notifyLowSMSBalance, __getSMSBFBalance, __getPartnerBfBalance, __getPartnerBalance, __getAgentBalance, __getAgentBFBalance, __totalPurchaseAndTransferIn, __totalSellAndTransferOut, __totalTesting, __correctSecurityDeposits, __correctExpenses, __correctSettlement, __transactionQuery, __paymentQuery, __makeLinesForComboProduct, addRouteOperationTransactions, purchaseCurrencyDetails, getTotalAmountConsumable, getTotalSellCommission, isCustomerCreditLimitExeeded, isOverLimitCreditSale, getOverLimitAmount, calculateRewardPoints, getRewardRedeemDetails, isRewardExpired, getTotalProductionCost, getAmountofTransactionWithoutPosReturn, getProductDropDownArray, getPropertyAccountSettingsByTransaction, getProductsByStoreId, getLastQuotationRefNo
 */
trait ProvidesUtilityHelpers
{
private function normaliseDateRange($start_date, $end_date)
    {
        $start = $start_date instanceof \DateTimeInterface
            ? \Carbon::instance($start_date)->copy()
            : \Carbon::parse($start_date);

        $end = $end_date instanceof \DateTimeInterface
            ? \Carbon::instance($end_date)->copy()
            : \Carbon::parse($end_date);

        return [$start->startOfDay(), $end->endOfDay()];
    }

    /**
     * Add Sell transaction
     *
     * @param int $business_id
     * @param array $input
     * @param float $invoice_total
     * @param int $user_id
     *
     * @return object
     * 
     */

    public function validateNos($nos)
    {
        $business_id = request()->session()->get('user.business_id');
        $numbers = explode(',', str_replace(' ', '', $nos));

        $validNumbers = [];
        $invalidNumbers = [];

        $business = Business::findOrFail($business_id);
        $settings = json_decode($business->hms_settings, true);

        foreach ($numbers as $number) {
            $number = trim($number);

            $isAllCountries = isset($settings['all_countries']) && $settings['all_countries'];

            if ($isAllCountries) {
                // Allow all numbers
                $validNumbers[] = $number;
            } else {
                // Check against specific allowed country codes
                $allowedCodes = explode(',', $settings['applicable_country_codes'] ?? '');

                // Extract the country code from the beginning of the number (assume max 4-digit codes)
                foreach ($allowedCodes as $code) {
                    if (strpos($number, $code) === 0) {
                        $validNumbers[] = $number;
                        continue 2; // Break out of both inner and outer loop on match
                    }
                }

                // If we didnâ€™t match any allowed code
                $invalidNumbers[] = $number;
            }
        }

        return [
            'valid' => $validNumbers,
            'invalid' => $invalidNumbers,
        ];
    }

    public function __getVatEffectiveDate($business_id)
    {
        // vat effective date
        $subscription = Subscription::active_subscription($business_id);
        $pacakge_details = !empty($subscription) ? ($subscription->package_details ?? []) : [];

        $vat_settings = VatSetting::where('business_id', $business_id)->where('status', 1)->first();


        if (!empty($vat_settings)) {
            if (!empty($pacakge_details) && !empty($pacakge_details['vat_effective_date'])) {

                if (strtotime($vat_settings->effective_date) > strtotime($pacakge_details['vat_effective_date'])) {
                    $pacakge_details['vat_effective_date'] = $vat_settings->effective_date;
                }
            } else {
                $pacakge_details['vat_effective_date'] = $vat_settings->effective_date;
            }
        }

        $effective_date = (!empty($pacakge_details) && !empty($pacakge_details['vat_effective_date'])) ? $pacakge_details['vat_effective_date'] : date('Y-m-d');

        return $effective_date;
    }

    public function __getSMSBalance($date, $business_id = null, $business_type = 'business')
    {

        if (empty($business_id)) {
            $business_id = $this->resolveSessionBusinessIdForStock();
        }

        $interest = SmsListInterest::where('sms_list_interests.business_id', $business_id)->where('type', $business_type)
            ->where('date', '<=', $date)->sum('amount');

        $refill = RefillBusiness::leftjoin('sms_refill_packages', 'sms_refill_packages.id', 'refill_business.package_id')
            ->where('refill_business.business_id', $business_id)->where('refill_business.type', $business_type)
            ->where('refill_business.date', '<=', $date)->sum('amount');

        $sms_cost = SmsLog::where('business_id', $business_id)->where('business_type', $business_type)->whereDate('created_at', '<=', $date)->sum('total_cost');

        return ($refill - $interest - $sms_cost);
    }

    public function __notifyLowSMSBalance($business_id, $business_type = 'business', $sender_name = null)
    {
        $date = date('Y-m-d');
        $sms_bal = $this->__getSMSBalance($date, $business_id, $business_type);

        if ($business_type == 'business') {
            $business = Business::findOrFail($business_id);
            $bname = $business->name;
            $sms_settings = $business->sms_settings;
            $phones = [];
            if (!empty($business->sms_settings)) {
                $phones = str_replace(' ', '', $business->sms_settings['msg_phone_nos']);
            }
        } else {
            $business = SmsApiClient::findOrFail($business_id);
            $bname = $business->name;
            $phones = $business->contact_mobile;

            $sms_settings = array(
                'default_gateway' => $business->default_gateway,
                'ultimate_sender_id' => $sender_name,
                'ultimate_token' => $business->ultimate_token,
                'hutch_username' => $business->hutch_username,
                'hutch_password' => $business->hutch_password,
                'hutch_mask' => $sender_name,
            );
        }

        $sms_reminder = SmsReminderSetting::first();

        if (!empty($sms_reminder) && !empty($phones) && !empty($bname)) {
            $_amts = array();
            if (isset($sms_reminder->days_1) && !empty($sms_reminder->days_1_status)) {
                $_amts[] = $sms_reminder->days_1;
            }

            if (isset($sms_reminder->days_2) && !empty($sms_reminder->days_2_status)) {
                $_amts[] = $sms_reminder->days_2;
            }

            if (isset($sms_reminder->days_3) && !empty($sms_reminder->days_3_status)) {
                $_amts[] = $sms_reminder->days_3;
            }

            if (isset($sms_reminder->days_4) && !empty($sms_reminder->days_4_status)) {
                $_amts[] = $sms_reminder->days_4;
            }

            $should_notify = false;
            foreach ($_amts as $amt) {
                if ($sms_bal <= $amt && ($amt <= $business->last_sms_notification || empty($business->last_sms_notification))) {
                    $should_notify = true;
                    break;
                }
            }

            if ($should_notify) {
                $msg = $sms_reminder->sms_body;
                $msg = str_replace('{business_client_name}', $bname, $msg);
                $msg = str_replace('{sms_balance}', $sms_bal, $msg);

                $data = [
                    'sms_settings' => $sms_settings,
                    'mobile_number' => $phones,
                    'sms_body' => $msg
                ];


                $this->superadminTransactionalSms($data);
                $business->last_sms_notification = ceil($sms_bal);
                $business->save();
            }
        }
    }

    public function __getSMSBFBalance($date, $business_type = 'business')
    {
        $business_id = $this->resolveSessionBusinessIdForStock();

        $interest = SmsListInterest::where('sms_list_interests.business_id', $business_id)->where('type', $business_type)
            ->whereDate('date', '<', $date)->sum('amount');

        $refill = RefillBusiness::leftjoin('sms_refill_packages', 'sms_refill_packages.id', 'refill_business.package_id')
            ->where('refill_business.business_id', $business_id)->where('refill_business.type', $business_type)
            ->where('refill_business.date', '<', $date)->sum('amount');

        $sms_cost = SmsLog::where('business_id', $business_id)->where('business_type', $business_type)->whereDate('created_at', '<', $date)->sum('total_cost');

        return ($refill - $interest - $sms_cost);
    }

    public function __getPartnerBfBalance($id, $start_date)
    {
        $commissions = ShippingPartnerCommission::where('partner_id', $id)
            ->whereDate('transaction_date', '<', $start_date)
            ->sum('amount');

        $payments = Transaction::where('parent_transaction_id', $id)
            ->whereIn('transactions.type', ['partner_payment', 'shipping_partner_ob'])
            ->whereDate('transaction_date', '<', $start_date)
            ->sum('final_total');

        return $commissions - $payments;
    }

    public function __getPartnerBalance($id)
    {
        $commissions = ShippingPartnerCommission::where('partner_id', $id)
            ->sum('amount');

        $payments = Transaction::where('parent_transaction_id', $id)
            ->whereIn('transactions.type', ['partner_payment', 'shipping_partner_ob'])
            ->sum('final_total');

        return $commissions - $payments;
    }

    public function __getAgentBalance($id, $module = null)
    {

        $commissions = ShippingAgentCommission::where('agent_id', $id)
            ->sum('amount');
        if ($module == 'airline') {
            // $commissions = 0;
            return $payments = Transaction::where('parent_transaction_id', $id)
                ->whereIn('type', ['airline_ticket', 'airline_agent_ob'])
                ->sum('final_total');
        } else {
            $payments = Transaction::where('parent_transaction_id', $id)
                ->whereIn('type', ['agent_payment', 'shipping_agent_ob'])
                ->sum('final_total');
        }
        return $commissions - $payments;
    }

    public function __getAgentBFBalance($id, $start_date, $module = null)
    {
        if ($module == 'airline') {
            $payments = Transaction::where('parent_transaction_id', $id)
                ->whereIn('type', ['airline_ticket'])
                ->whereDate('transaction_date', '<', $start_date)
                ->sum('final_total');
            return $payments;
        }
        $commissions = ShippingAgentCommission::where('agent_id', $id)
            ->whereDate('transaction_date', '<', $start_date)
            ->sum('amount');

        $payments = Transaction::where('parent_transaction_id', $id)
            ->whereIn('type', ['agent_payment', 'shipping_agent_ob'])
            ->whereDate('transaction_date', '<', $start_date)
            ->sum('final_total');

        return $commissions - $payments;
    }

    public function __totalPurchaseAndTransferIn($business_id, $start, $end, $tank_id)
    {
        /*
         * IS1959 (performance): compare the raw column against a day RANGE
         * instead of wrapping it in DATE().
         *
         * whereDate() compiles to  DATE(transactions.transaction_date) >= ?
         * and a function applied to a column makes the predicate non-sargable -
         * MySQL cannot use the index and full-scans the transactions table. The
         * Tank Transaction Summary calls these helpers once per tank per day, so
         * a handful of tanks meant a dozen full scans per request and the page
         * timed out at the gateway (HTTP 524).
         *
         *     DATE(col) >= DATE(:start)   is equivalent to   col >= :start 00:00:00
         *     DATE(col) <= DATE(:end)     is equivalent to   col <= :end   23:59:59
         *
         * so the results are identical while the index can now be used.
         */
        $range_start = \Carbon\Carbon::parse($start)->startOfDay();
        $range_end   = \Carbon\Carbon::parse($end)->endOfDay();

        $transfer_in = TankTransfer::join('fuel_tanks', function ($join) {
            $join->on('tank_transfers.to_tank', 'fuel_tanks.id');
        })
            ->where('tank_transfers.business_id', $business_id)
            ->where('fuel_tanks.id', $tank_id)
            ->where('tank_transfers.date', '>=', $range_start)
            ->where('tank_transfers.date', '<=', $range_end)
            ->sum('tank_transfers.quantity');

        $purchase = $query = Transaction::leftjoin('tank_purchase_lines', function ($join) {
            $join->on('transactions.id', 'tank_purchase_lines.transaction_id')->where('tank_purchase_lines.quantity', '!=', 0);
        })
            ->join('fuel_tanks', function ($join) {
                $join->on('tank_purchase_lines.tank_id', 'fuel_tanks.id');
            })
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', '!=', 'opening_stock')
            ->where('transactions.transaction_date', '>=', $range_start)
            ->where('transactions.transaction_date', '<=', $range_end)
            ->where('fuel_tanks.id', $tank_id)->sum('tank_purchase_lines.quantity');



        $total = $transfer_in + $purchase;

        return $total;
    }

    public function __totalSellAndTransferOut($business_id, $start, $end, $tank_id)
    {
        /*
         * IS1959 (performance): compare the raw column against a day RANGE
         * instead of wrapping it in DATE().
         *
         * whereDate() compiles to  DATE(transactions.transaction_date) >= ?
         * and a function applied to a column makes the predicate non-sargable -
         * MySQL cannot use the index and full-scans the transactions table. The
         * Tank Transaction Summary calls these helpers once per tank per day, so
         * a handful of tanks meant a dozen full scans per request and the page
         * timed out at the gateway (HTTP 524).
         *
         *     DATE(col) >= DATE(:start)   is equivalent to   col >= :start 00:00:00
         *     DATE(col) <= DATE(:end)     is equivalent to   col <= :end   23:59:59
         *
         * so the results are identical while the index can now be used.
         */
        $range_start = \Carbon\Carbon::parse($start)->startOfDay();
        $range_end   = \Carbon\Carbon::parse($end)->endOfDay();

        $sell = Transaction::leftjoin('tank_sell_lines', 'transactions.id', 'tank_sell_lines.transaction_id')
            ->join('fuel_tanks', function ($join) {
                $join->on('tank_sell_lines.tank_id', 'fuel_tanks.id');
            })
            ->where('transactions.business_id', $business_id)
            ->where('transactions.transaction_date', '>=', $range_start)
            ->where('transactions.transaction_date', '<=', $range_end)
            ->where('fuel_tanks.id', $tank_id)->sum('tank_sell_lines.quantity');

        $transfer_out = TankTransfer::join('fuel_tanks', function ($join) {
            $join->on('tank_transfers.from_tank', 'fuel_tanks.id');
        })
            ->where('tank_transfers.business_id', $business_id)
            ->where('fuel_tanks.id', $tank_id)
            ->where('tank_transfers.date', '>=', $range_start)
            ->where('tank_transfers.date', '<=', $range_end)
            ->sum('tank_transfers.quantity');

        $total = $sell + $transfer_out;

        return $total;
    }

    public function __totalTesting($business_id, $start, $end, $tank_id)
    {
        // IS1959 (performance): same sargability fix as the helpers above - this
        // one joins four tables, so a full scan of transactions here is the most
        // expensive of the set.
        $range_start = \Carbon\Carbon::parse($start)->startOfDay();
        $range_end   = \Carbon\Carbon::parse($end)->endOfDay();

        $testing_qty = Transaction::leftjoin('tank_sell_lines', 'transactions.id', 'tank_sell_lines.transaction_id')
            ->join('settlements', 'transactions.invoice_no', 'settlements.settlement_no')
            ->join('meter_sales', 'settlements.id', 'meter_sales.settlement_no')
            ->join('fuel_tanks', function ($join) {
                $join->on('tank_sell_lines.tank_id', 'fuel_tanks.id');
            })
            ->where('transactions.business_id', $business_id)
            ->where('transactions.transaction_date', '>=', $range_start)
            ->where('transactions.transaction_date', '<=', $range_end)
            ->where('fuel_tanks.id', $tank_id)->sum('meter_sales.testing_qty');

        return $testing_qty;
    }

    public function __correctSecurityDeposits()
    {
        $transactions = Transaction::where('type', 'security_deposit')->with(['payment_lines', 'contact'])->get();
        $start_time = time();

        foreach ($transactions as $one) {

            $transaction = $one;
            $contact = $transaction->contact;
            $payment = $transaction->payment_lines[0];

            $account_transaction_data = [
                'amount' => abs($transaction->final_total),
                'business_id' => $transaction->business_id,
                'account_id' => $payment->account_id,
                'contact_id' => $contact->id,
                'operation_date' => $transaction->transaction_date,
                'created_by' => $transaction->created_by,
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => $payment->id
            ];
            if ($contact->type == 'customer') {
                $account_transaction_data['type'] = 'debit';
            }
            if ($contact->type == 'supplier') {
                $account_transaction_data['type'] = 'credit';
            }

            $criteria = ['account_id' => $account_transaction_data['account_id'], 'type' => $account_transaction_data['type'], 'transaction_id' => $account_transaction_data['transaction_id'], 'transaction_payment_id' => $account_transaction_data['transaction_payment_id']];


            // dd($account_transaction_data);

            // add transaction
            if (!empty($account_transaction_data['account_id'])) {
                if (!empty($account_transaction_data['account_id'])) {
                    AccountTransaction::updateOrCreate($criteria, $account_transaction_data);
                }
            }


            if ($contact->type == 'customer') {
                $account_transaction_data['account_id'] = Account::where('business_id', $transaction->business_id)->where('name', 'Accounts Payable')->first()->id ?? 0;
                $account_transaction_data['type'] = 'credit';
            }
            if ($contact->type == 'supplier') {
                $account_transaction_data['account_id'] = Account::where('business_id', $transaction->business_id)->where('name', 'Company Deposits')->first()->id ?? 0;
                $account_transaction_data['type'] = 'debit';
            }

            $criteria = ['account_id' => $account_transaction_data['account_id'], 'type' => $account_transaction_data['type'], 'transaction_id' => $account_transaction_data['transaction_id'], 'transaction_payment_id' => $account_transaction_data['transaction_payment_id']];


            if (!empty($account_transaction_data['account_id'])) {
                AccountTransaction::updateOrCreate($criteria, $account_transaction_data);
            }
        }
        $end_time = time();
        echo "Completed<br><b>Time taken: </b>" . $this->__formatTime($start_time, $end_time);
    }

    public function __correctExpenses()
    {
        $transactions = Transaction::leftjoin('account_transactions', 'account_transactions.transaction_id', 'transactions.id')
            ->where('transactions.type', 'expense')->whereNull('account_transactions.id')->with(['payment_lines'])->select('transactions.*')->get();
        $start_time = time();

        foreach ($transactions as $one) {

            $transaction = $one;
            $contact = $transaction->contact;
            $payment = empty($transaction->payment_lines->toArray()) ? [] : $one->payment_lines[0];

            if (!empty($transaction->expense_account)) {
                $account_transaction_data = [

                    'amount' => $transaction->final_total,

                    'account_id' => $transaction->expense_account,

                    'type' => 'debit',

                    'sub_type' => 'expense',

                    'operation_date' => $transaction->transaction_date,

                    'created_by' => $transaction->created_by,

                    'business_id' => $transaction->business_id,

                    'transaction_id' => $transaction->id,

                    'transaction_payment_id' => !empty($payment) ? $payment->id : null,
                ];


                $criteria = ['account_id' => $account_transaction_data['account_id'], 'type' => $account_transaction_data['type'], 'transaction_id' => $account_transaction_data['transaction_id']];
                if (!empty($account_transaction_data['account_id'])) {
                    AccountTransaction::updateOrCreate($criteria, $account_transaction_data);
                }
            }

            $account_payable_id = !empty($transaction->controller_account) ? $transaction->controller_account : Account::where('business_id', $transaction->business_id)->where('name', 'Accounts Payable')->first()->id;
            $ap_transaction_data = [

                'operation_date' => $transaction->transaction_date,

                'created_by' => $transaction->created_by,

                'transaction_id' => $transaction->id,

                'business_id' => $transaction->business_id,

                'transaction_payment_id' => !empty($payment) ? $payment->id : null,

                'operation_date' => $transaction->transaction_date

            ];


            if (empty($payment)) {

                if (!empty($account_payable_id)) {
                    $ap_transaction_data['amount'] = $transaction->final_total;

                    $ap_transaction_data['account_id'] = $account_payable_id;

                    $ap_transaction_data['type'] = 'credit';

                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }
            } else if ($payment->amount < $transaction->final_total) {

                $ap_transaction_data['amount'] = $payment->amount;  //paid amount

                $ap_transaction_data['account_id'] = $payment->account_id;

                $ap_transaction_data['type'] = 'credit';

                AccountTransaction::createAccountTransaction($ap_transaction_data);

                if (!empty($account_payable_id)) {
                    $ap_transaction_data['amount'] = $transaction->final_total - $payment->amount; //unpaid amount

                    $ap_transaction_data['account_id'] = $account_payable_id;

                    $ap_transaction_data['type'] = 'credit';

                    AccountTransaction::createAccountTransaction($ap_transaction_data);
                }
            }

            if ($payment->amount == $transaction->final_total) {

                $ap_transaction_data['amount'] = $payment->amount;

                $ap_transaction_data['account_id'] = $payment->account_id;

                $ap_transaction_data['type'] = 'credit';


                AccountTransaction::createAccountTransaction($ap_transaction_data);
            }
        }
        $end_time = time();
        echo "Completed<br><b>Time taken: </b>" . $this->__formatTime($start_time, $end_time);
    }

    public function __correctSettlement()
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);

        $start_time = time();
        $settlements = Settlement::with([
            'meter_sales',
            'other_sales',
            'other_incomes',
            'customer_payments',
            'cash_payments',
            'cash_deposits',
            'card_payments',
            'cheque_payments',
            'credit_sale_payments',
            'expense_payments',
            'excess_payments',
            'shortage_payments',
            'cash_deposits',
            'loan_payments',
            'drawings_payments',
            'customer_loans'
        ])
            ->select('settlements.*')
            ->get();

        foreach ($settlements as $settlement) {
            $settlement_no = $settlement->settlement_no;

            foreach ($settlement->customer_loans as $customer_loan) {

                $customer_loan_transaction = Transaction::where('type', 'settlement')
                    ->where('sub_type', 'customer_loan')
                    ->where('invoice_no', $settlement->settlement_no)
                    ->where('final_total', $customer_loan->amount)
                    ->where('contact_id', $customer_loan->customer_id)->first();

                if (!empty($customer_loan_transaction)) {
                    $type = 'debit';
                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Accounts Receivable')->first()->id ?? 0;

                    $this->createAccountTransaction($customer_loan_transaction, $type, $account_id, $customer_loan_transaction->id, 'null', null, $customer_loan->amount, false, $customer_loan->note);

                    $type = 'debit';
                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Cash')->first()->id ?? 0;
                    $this->createAccountTransaction($customer_loan_transaction, $type, $account_id, $customer_loan_transaction->id, 'null', null, $customer_loan->amount, false, $customer_loan->note);

                    $type = 'credit';
                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Cash')->first()->id ?? 0;
                    $this->createAccountTransaction($customer_loan_transaction, $type, $account_id, $customer_loan_transaction->id, 'null', null, $customer_loan->amount, false, $customer_loan->note);
                }
            }

            $loan_note = "";
            foreach ($settlement->loan_payments as $loan_payment) {
                $i = 0;
                //this transaction will use in report to show amounts
                $loan_transaction_payment = Transaction::where('type', 'settlement')
                    ->where('sub_type', 'loan_payment')
                    ->where('invoice_no', $settlement->settlement_no)
                    ->where('final_total', $loan_payment->amount)->first();

                if (!empty($loan_transaction_payment)) {
                    $loan_note .= !empty($loan_payment->note) ? "Note " . $i++ . ": " . $loan_payment->note . "\n" : "";

                    $type = 'debit';
                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Cash')->first()->id ?? 0;
                    $this->createAccountTransaction($loan_transaction_payment, $type, $account_id, $loan_transaction_payment->id, 'null', null, $loan_payment->amount, false, $loan_payment->note);


                    $type = 'credit';
                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Cash')->first()->id ?? 0;
                    $this->createAccountTransaction($loan_transaction_payment, $type, $account_id, $loan_transaction_payment->id, 'null', null, $loan_payment->amount, false, $loan_payment->note);

                    $type = 'debit';
                    $account_id = $loan_payment->loan_account;
                    $this->createAccountTransaction($loan_transaction_payment, $type, $account_id, $loan_transaction_payment->id, 'null', null, $loan_payment->amount, false, $loan_payment->note);
                }
            }

            $drawing_note = "";
            foreach ($settlement->drawings_payments as $drawing_payment) {
                $i = 0;
                //this transaction will use in report to show amounts
                $drawing_transaction_payment = Transaction::where('type', 'settlement')
                    ->where('sub_type', 'drawing_payment')
                    ->where('invoice_no', $settlement->settlement_no)
                    ->where('final_total', $drawing_payment->amount)->first();


                $drawing_note .= !empty($drawing_payment->note) ? "Note " . $i++ . ": " . $drawing_payment->note . "\n" : "";

                if (!empty($drawing_transaction_payment)) {
                    $type = 'debit';
                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Cash')->first()->id ?? 0;
                    $this->createAccountTransaction($drawing_transaction_payment, $type, $account_id, $drawing_transaction_payment->id, 'null', null, $drawing_payment->amount, false, $drawing_payment->note);

                    $type = 'credit';
                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Cash')->first()->id ?? 0;
                    $this->createAccountTransaction($drawing_transaction_payment, $type, $account_id, $drawing_transaction_payment->id, 'null', null, $drawing_payment->amount, false, $drawing_payment->note);


                    $type = 'debit';
                    $account_id = $drawing_payment->loan_account;
                    $this->createAccountTransaction($drawing_transaction_payment, $type, $account_id, $drawing_transaction_payment->id, 'null', null, $drawing_payment->amount, false, $drawing_payment->note);
                }
            }

            foreach ($settlement->cash_deposits as $cash_payment) {
                $i = 0;
                //this transaction will use in report to show amounts
                $cash_deposit = Transaction::where('type', 'settlement')
                    ->where('sub_type', 'cash_deposit')
                    ->where('invoice_no', $settlement->settlement_no)
                    ->where('ref_no', $cash_payment->id)
                    ->where('final_total', $cash_payment->amount)->first();

                if (!empty($cash_deposit)) {

                    $type = 'debit';
                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Cash')->first()->id ?? 0;
                    $this->createAccountTransaction($cash_deposit, $type, $account_id, $cash_deposit->id, 'null', null, $cash_payment->amount, false, null);

                    $this->createAccountTransaction($cash_deposit, 'credit', $account_id, $cash_deposit->id, 'null', null, $cash_payment->amount, false, null);


                    $type = 'debit';
                    $account_id = $cash_payment->bank_id;
                    $this->createAccountTransaction($cash_deposit, $type, $account_id, $cash_deposit->id, 'null', null, $cash_payment->amount, false, null);
                }
            }


            $cash_transaction_payment = null;

            foreach ($settlement->shortage_payments as $shortage_payment) {
                $transaction = Transaction::find($shortage_payment->transaction_id);

                if (!empty($transaction)) {
                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Accounts Receivable')->first()->id ?? 0;
                    $type = 'debit';
                    $this->createAccountTransaction($transaction, $type, $account_id, null, 'ledger_show', null, 0, false, $shortage_payment->note);
                }
            }

            foreach ($settlement->excess_payments as $excess_payment) {
                $transaction = Transaction::find($excess_payment->transaction_id);

                if (!empty($transaction)) {
                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Accounts Receivable')->first()->id ?? 0;
                    $type = 'credit';
                    $this->createAccountTransaction($transaction, $type, $account_id, null, 'ledger_show', null, 0, false, $excess_payment->note);
                }
            }


            foreach ($settlement->expense_payments as $expense_payment) {
                $transaction = Transaction::find($expense_payment->transaction_id);
                $transaction_payment = TransactionPayment::where('transaction_id', $transaction->id)->first();

                if (!empty($transaction) && !empty($transaction_payment)) {
                    $account_id = $expense_payment->account_id;
                    $type = 'debit';
                    $this->createAccountTransaction($transaction, $type, $account_id, $transaction_payment->id);

                    $account_id = Account::where('business_id', $settlement->business_id)->where('name', 'Cash')->first()->id ?? 0;
                    $type = 'credit';
                    $this->createAccountTransaction($transaction, $type, $account_id, $transaction_payment->id);
                }
            }
        }

        $end_time = time();
        echo "Completed<br><b>Time taken: </b>" . $this->__formatTime($start_time, $end_time);
    }

    private function __transactionQuery($contact_id, $start, $end = null, $location_id = null)
    {
        $business_id = request()->session()->get('user.business_id');
        $transaction_type_keys = array_keys(Transaction::transactionTypes());

        $query = Transaction::where('transactions.contact_id', $contact_id)
            ->where('transactions.business_id', $business_id)
            ->where('transactions.status', '!=', 'draft')
            ->whereIn('transactions.type', $transaction_type_keys);

        if (!empty($start) && !empty($end)) {
            $query->whereDate(
                'transactions.transaction_date',
                '>=',
                $start
            )
                ->whereDate('transactions.transaction_date', '<=', $end)->get();
        }

        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        if (!empty($start) && empty($end)) {
            $query->whereDate('transactions.transaction_date', '<', $start);
        }

        return $query;
    }

    private function __paymentQuery($contact_id, $start, $end = null, $location_id = null)
    {
        $business_id = request()->session()->get('user.business_id');

        $query = TransactionPayment::leftJoin(
            'transactions as t',
            'transaction_payments.transaction_id',
            '=',
            't.id'
        )
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->where('transaction_payments.payment_for', $contact_id);
        //->whereNotNull('transaction_payments.transaction_id');
        //->whereNull('transaction_payments.parent_id');

        if (!empty($start) && !empty($end)) {
            $query->whereDate('paid_on', '>=', $start)
                ->whereDate('paid_on', '<=', $end);
        }

        if (!empty($start) && empty($end)) {
            $query->whereDate('paid_on', '<', $start);
        }

        if (!empty($location_id)) {
            //if location id present get all transaction with the location id and opening balance
            $query->where(function ($q) use ($location_id) {
                $q->where('transaction_payments.is_advance', 1)
                    ->orWhere('t.location_id', $location_id);
            });
        }


        return $query;
    }

    private function __makeLinesForComboProduct($combo_items, $parent_sell_line)
    {
        $combo_lines = [];
        //Calculate the percentage change in price.
        $combo_total_price = 0;
        foreach ($combo_items as $key => $value) {
            $sell_price_inc_tax = Variation::findOrFail($value['variation_id'])->sell_price_inc_tax;
            $combo_items[$key]['unit_price_inc_tax'] = $sell_price_inc_tax;
            $combo_total_price += $value['quantity'] * $sell_price_inc_tax;
        }
        $change_percent = $this->get_percent($combo_total_price, $parent_sell_line->unit_price_inc_tax * $parent_sell_line->quantity);
        foreach ($combo_items as $value) {
            $price = $this->calc_percentage($value['unit_price_inc_tax'], $change_percent, $value['unit_price_inc_tax']);
            $combo_lines[] = new TransactionSellLine([
                'product_id' => $value['product_id'],
                'variation_id' => $value['variation_id'],
                'quantity' => $value['quantity'],
                'unit_price_before_discount' => $price,
                'unit_price' => $price,
                'line_discount_type' => null,
                'line_discount_amount' => 0,
                'item_tax' => 0,
                'tax_id' => null,
                'unit_price_inc_tax' => $price,
                'sub_unit_id' => null,
                'discount_id' => null,
                'parent_sell_line_id' => $parent_sell_line->id,
                'children_type' => 'combo'
            ]);
        }
        return $combo_lines;
    }
    /**
     * Edit transaction sell line
     *
     * @param array $product
     * @param int $location_id
     *
     * @return boolean
     */

    public function addRouteOperationTransactions($transactionPayment, $formInput)
    {
        $business_id = $this->resolveSessionBusinessIdForStock();
        $asset_type_ids = AccountType::getAccountTypeIdOfType('Assets', $business_id);
        $account_type_id = $this->getAccountTypeIdOfAccount($formInput['account_id'], $business_id);
        $transaction_payment_details = TransactionPayment::where('id', $transactionPayment->id)->first();
        $transaction = Transaction::where('id', $transaction_payment_details->transaction_id)->first();
        $account_payable = Account::where('business_id', $business_id)->where('name', 'Accounts Payable')->where('is_closed', 0)->first();
        $account_payable_id = !empty($account_payable) ? $account_payable->id : 0;
        $account_receivable = Account::where('business_id', $business_id)->where('name', 'Accounts Receivable')->where('is_closed', 0)->first();
        $account_receivable_id = !empty($account_receivable) ? $account_receivable->id : 0;


        $account_transaction_data = [
            'contact_id' => !empty($transaction) ? $transaction->contact_id : null,
            'amount' => $formInput['amount'],
            'account_id' => $formInput['account_id'],
            'type' => AccountTransaction::getAccountTransactionType($formInput['transaction_type']),
            'operation_date' => !empty($transaction->transaction_date) ? $transaction->transaction_date : date('Y-m-d H:i:s'),
            'created_by' => $transactionPayment->created_by,
            'transaction_id' => !empty($transaction) ? $transaction->id : null,
            'transaction_payment_id' => !empty($transactionPayment->id) ? $transactionPayment->id : null
        ];

        $fleet = Fleet::find($transaction->fleet_id);
        $account_transaction_data['type'] = 'debit';
        AccountTransaction::createAccountTransaction($account_transaction_data);

        $account_transaction_data['type'] = 'credit';
        $account_transaction_data['account_id'] = $fleet->income_account_id;
        AccountTransaction::createAccountTransaction($account_transaction_data);
    }

    public function purchaseCurrencyDetails($business_id)
    {
        $business = Business::find($business_id);
        $output = [
            'purchase_in_diff_currency' => false,
            'p_exchange_rate' => 1,
            'decimal_seperator' => '.',
            'thousand_seperator' => ',',
            'symbol' => '',
        ];
        //Check if diff currency is used or not.
        if ($business->purchase_in_diff_currency == 1) {
            $output['purchase_in_diff_currency'] = true;
            $output['p_exchange_rate'] = $business->p_exchange_rate;
            $currency_id = $business->purchase_currency_id;
        } else {
            $output['purchase_in_diff_currency'] = false;
            $output['p_exchange_rate'] = 1;
            $currency_id = $business->currency_id;
        }
        $currency = Currency::find($currency_id);
        $output['thousand_separator'] = $currency->thousand_separator;
        $output['decimal_separator'] = $currency->decimal_separator;
        $output['symbol'] = $currency->symbol;
        $output['code'] = $currency->code;
        $output['name'] = $currency->currency;
        return (object) $output;
    }
    /**
     * Pay contact due at once
     *
     * @param obj $parent_payment, string $type
     *
     * @return void
     */

    public function getTotalAmountConsumable($parent_payment, $type)
    {
        //Get all unpaid transaction for the contact
        $types = ['opening_balance', $type];
        if ($type == 'purchase_return') {
            $types = [$type];
        }
        $due_transactions = Transaction::where('contact_id', $parent_payment->payment_for)
            ->whereIn('type', $types)
            ->where('payment_status', '!=', 'paid')
            ->orderBy('transaction_date', 'asc')
            ->get();
        $total_amount = $parent_payment->amount;
        $amount_consumed = 0;
        if ($due_transactions->count()) {
            foreach ($due_transactions as $transaction) {
                if ($total_amount > 0) {
                    $total_paid = $this->getTotalPaid($transaction->id);
                    $due = $transaction->final_total - $total_paid;
                    if ($due <= $total_amount) {
                        $amount_consumed += $due;
                        $total_amount = $total_amount - $due;
                    } else {
                        $amount_consumed += $total_amount;
                    }
                }
            }
        }
        return $amount_consumed;
    }
    /**
     * Add a mapping between purchase & sell lines.
     * NOTE: Don't use request variable here, request variable don't exist while adding
     * dummybusiness via command line
     *
     * @param array $business
     * @param array $transaction_lines
     * @param string $mapping_type = purchase (purchase or stock_adjustment)
     * @param boolean $check_expiry = true
     * @param int $purchase_line_id (default: null)
     *
     * @return object
     */

    public function getTotalSellCommission($business_id, $start_date = null, $end_date = null, $location_id = null, $commission_agent = null)
    {
        $query = Transaction::leftjoin('transactions as SR', function ($join) {
            $join->on('SR.return_parent_id', '=', 'transactions.id')
                ->where('SR.type', 'sell_return');
        })
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')
            ->where('transactions.status', 'final')
            ->select(DB::raw("SUM( transactions.final_total - COALESCE(SR.final_total, 0) ) as final_total"));
        //Check for permitted locations of a user
        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween('transactions.transaction_date', [$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
        }
        //Filter by the location
        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }
        if (!empty($commission_agent)) {
            $query->where('transactions.commission_agent', $commission_agent);
        }
        $sell_details = $query->get();
        $output['total_sales_with_commission'] = $sell_details->sum('final_total');
        return $output;
    }
    /**
     * Add Sell transaction
     *
     * @param int $business_id
     * @param array $input
     * @param float $invoice_total
     * @param int $user_id
     *
     * @return boolean
     */

    public function isCustomerCreditLimitExeeded(
        $input,
        $exclude_transaction_id = null
    ) {
        $credit_limit = Contact::find($input['contact_id'])->credit_limit;
        $customer = Contact::find($input['contact_id']);
        if ($credit_limit == null) {
            return false;
        }
        $over_limit_percentage = 0;
        if ($customer->sell_over_limit == 1) {
            $over_limit_percentage = ($credit_limit * $customer->over_limit_percentage) / 100;
            $credit_limit = $credit_limit + $over_limit_percentage;
        }
        $query = Contact::where('contacts.id', $input['contact_id'])
            ->join('transactions AS t', 'contacts.id', '=', 't.contact_id');
        //Exclude transaction id if update transaction
        if (!empty($exclude_transaction_id)) {
            $query->where('t.id', '!=', $exclude_transaction_id);
        }
        $credit_details = $query->select(
            DB::raw("SUM(IF(t.type = 'sell', final_total, 0)) as total_invoice"),
            DB::raw("SUM(IF(t.type = 'sell', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_paid")
        )->first();
        $total_invoice = !empty($credit_details->total_invoice) ? $credit_details->total_invoice : 0;
        $invoice_paid = !empty($credit_details->invoice_paid) ? $credit_details->invoice_paid : 0;
        $final_total = $this->num_uf($input['final_total']);
        $curr_total_payment = 0;
        if (!empty($input['payment'])) {
            foreach ($input['payment'] as $payment) {
                $curr_total_payment += $this->num_uf($payment['amount']);
            }
        }
        $curr_due = $final_total - $curr_total_payment;
        $total_due = $total_invoice - $invoice_paid + $curr_due;
        if ($total_due <= $credit_limit) {
            return false;
        }
        return $credit_limit;
    }
    // if sale is overlimit sale return true

    public function isOverLimitCreditSale(
        $input,
        $exclude_transaction_id = null
    ) {
        $credit_limit = Contact::find($input['contact_id'])->credit_limit;
        $customer = Contact::find($input['contact_id']);
        $query = Contact::where('contacts.id', $input['contact_id'])
            ->join('transactions AS t', 'contacts.id', '=', 't.contact_id');
        //Exclude transaction id if update transaction
        if (!empty($exclude_transaction_id)) {
            $query->where('t.id', '!=', $exclude_transaction_id);
        }
        $credit_details = $query->select(
            DB::raw("SUM(IF(t.type = 'sell', final_total, 0)) as total_invoice"),
            DB::raw("SUM(IF(t.type = 'sell', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_paid")
        )->first();
        $total_invoice = !empty($credit_details->total_invoice) ? $credit_details->total_invoice : 0;
        $invoice_paid = !empty($credit_details->invoice_paid) ? $credit_details->invoice_paid : 0;
        $final_total = $this->num_uf($input['final_total']);
        $curr_total_payment = 0;
        if (!empty($input['payment'])) {
            foreach ($input['payment'] as $payment) {
                $curr_total_payment += $this->num_uf($payment['amount']);
            }
        }
        $curr_due = $final_total - $curr_total_payment;
        $total_due = $total_invoice - $invoice_paid + $curr_due;
        if ($total_due > $credit_limit) {
            return true;
        }
        return false;
    }
    // if sale is overlimit sale return true

    public function getOverLimitAmount(
        $input,
        $exclude_transaction_id = null
    ) {
        $credit_limit = Contact::find($input['contact_id'])->credit_limit;
        $customer = Contact::find($input['contact_id']);
        $query = Contact::where('contacts.id', $input['contact_id'])
            ->join('transactions AS t', 'contacts.id', '=', 't.contact_id');
        //Exclude transaction id if update transaction
        if (!empty($exclude_transaction_id)) {
            $query->where('t.id', '!=', $exclude_transaction_id);
        }
        $credit_details = $query->select(
            DB::raw("SUM(IF(t.type = 'sell', final_total, 0)) as total_invoice"),
            DB::raw("SUM(IF(t.type = 'sell', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_paid")
        )->first();
        $total_invoice = !empty($credit_details->total_invoice) ? $credit_details->total_invoice : 0;
        $invoice_paid = !empty($credit_details->invoice_paid) ? $credit_details->invoice_paid : 0;
        $final_total = $this->num_uf($input['final_total']);
        $curr_total_payment = 0;
        if (!empty($input['payment'])) {
            foreach ($input['payment'] as $payment) {
                $curr_total_payment += $this->num_uf($payment['amount']);
            }
        }
        $curr_due = $final_total - $curr_total_payment;
        $total_due = $total_invoice - $invoice_paid + $curr_due;
        return $total_due - $credit_limit;
    }
    /**
     * Creates a new opening balance transaction for a contact
     *
     * @param  int $business_id
     * @param  int $contact_id
     * @param  int $amount
     *
     * @return void
     */

    public function calculateRewardPoints($business_id, $total)
    {
        if (session()->has('business')) {
            $business = session()->get('business');
        } else {
            $business = Business::find($business_id);
        }
        $total_points = 0;
        if ($business->enable_rp == 1) {
            //check if order total elegible for reward
            if ($business->min_order_total_for_rp > $total) {
                return $total_points;
            }
            $amount_per_unit_point = $business->amount_for_unit_rp;
            $total_points = floor($total / $amount_per_unit_point);
            if (!empty($business->max_rp_per_order) && $business->max_rp_per_order < $total_points) {
                $total_points = $business->max_rp_per_order;
            }
        }
        return $total_points;
    }
    /**
     * Updates reward point of a customer
     *
     * @return void
     */

    public function getRewardRedeemDetails($business_id, $customer_id)
    {
        if (session()->has('business')) {
            $business = session()->get('business');
        } else {
            $business = Business::find($business_id);
        }
        $details = ['points' => 0, 'amount' => 0];
        $customer = Contact::where('business_id', $business_id)
            ->find($customer_id);
        $customer_reward_points = $customer->total_rp;
        //If zero reward point or walk in customer return blank values
        if (empty($customer_reward_points) || $customer->is_default == 1) {
            return $details;
        }
        $min_reward_point_required = $business->min_redeem_point;
        if (!empty($min_reward_point_required) && $customer_reward_points < $min_reward_point_required) {
            return $details;
        }
        $max_redeem_point = $business->max_redeem_point;
        if (!empty($max_redeem_point) && $max_redeem_point <= $customer_reward_points) {
            $customer_reward_points = $max_redeem_point;
        }
        $amount_per_unit_point = $business->redeem_amount_per_unit_rp;
        $equivalent_amount = $customer_reward_points * $amount_per_unit_point;
        $details = ['points' => $customer_reward_points, 'amount' => $equivalent_amount];
        return $details;
    }
    /**
     * Checks whether a reward point date is expired
     *
     * @return boolean
     */

    public function isRewardExpired($date, $business_id)
    {
        if (session()->has('business')) {
            $business = session()->get('business');
        } else {
            $business = Business::find($business_id);
        }
        $is_expired = false;
        if (!empty($business->rp_expiry_period)) {
            $expiry_date = \Carbon::parse($date);
            if ($business->rp_expiry_type == 'month') {
                $expiry_date = $expiry_date->addMonths($business->rp_expiry_period);
            } elseif ($business->rp_expiry_type == 'year') {
                $expiry_date = $expiry_date->addYears($business->rp_expiry_period);
            }
            if ($expiry_date->format('Y-m-d') >= \Carbon::now()->format('Y-m-d')) {
                $is_expired = true;
            }
        }
        return $is_expired;
    }
    /**
     * Calculates total production cost
     *
     * @param  int $business_id
     * @param  string $start_date = null
     * @param  string $end_date = null
     * @param  int $location_id = null
     *
     * @return array
     */

    public function getTotalProductionCost(
        $business_id,
        $start_date = null,
        $end_date = null,
        $location_id = null
    ) {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'production_purchase')
            ->where('mfg_is_final', 1);
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
        $total = $query->select(
            DB::raw('SUM(final_total - ((final_total * 100) / (mfg_production_cost + 100) ) ) as total_production_cost')
        )->first();
        $total_production_cost = !empty($total->total_production_cost) ? $total->total_production_cost : 0;
        return $total_production_cost;
    }

    public function getAmountofTransactionWithoutPosReturn($transaction)
    {
        $sell_lines = TransactionSellLine::leftjoin('products', 'transaction_sell_lines.product_id', 'products.id')
            ->leftjoin('product_variations', 'products.id', 'product_variations.product_id')
            ->leftjoin('variations', 'product_variations.id', 'variations.product_variation_id')
            ->where('transaction_id', $transaction->id)
            ->select('transaction_sell_lines.*')
            ->get();
        $amount = 0;
        foreach ($sell_lines as $sale) {
            if ($sale->quantity >= 0) { //not include pos page return
                $amount += ($sale->quantity * $sale->unit_price) - $sale->line_discount_amount;
            }
        }
        return $amount;
    }
    /**
     * Resolve business id reliably for stock/tank helpers. Some pages store it as user.business_id,
     * while older Petro helpers were reading business.id only.
     */

    public function getProductDropDownArray($business_id, ?int $fuel_category_id, $module = null)
    {
        $business_details = Business::find($business_id);
        $default_store = request()->session()->get('business.default_store');
        $business_locations = BusinessLocation::forDropdown($business_id);
        $location_id = current(array_keys($business_locations->toArray()));
        $products = Product::join('units', 'products.unit_id', 'units.id')
            ->where('products.business_id', $business_id)
            ->leftJoin('variation_location_details', 'products.id', 'variation_location_details.product_id');
        if ($location_id) {
            // $products = $products->join('stores', 'stores.location_id', 'variation_location_details.location_id');
            // $products = $products->join('variation_store_details', 'stores.id', 'variation_store_details.store_id');
        }
        $products = $products->join('variations', 'products.id', 'variations.product_id');
        // $products = $products->join('variations', function ($join) {
        //     $join->on('products.id', '=', 'variations.product_id');
        //     // $join->on('variation_location_details.variation_id', '=', 'variations.id');
        //     // $join->on('variations.id', '=', 'variation_store_details.variation_id');
        // })
        // ->active()
        // ->where('products.business_id', $business_id)
        // ->whereNull('variations.deleted_at');

        // if(!empty($module)){
        //     $products->forModule($module);
        // }

        // if($fuel_category_id){
        //     $products = $products->where('category_id', '!=', $fuel_category_id);
        // }
        // if($location_id){
        //     $products = $products->where('variation_location_details.location_id', $location_id);
        // }
        if ($default_store) {
            // $products = $products->where('stores.id', $default_store);
        }
        $products = $products->select('products.id', 'products.name', 'products.sku', 'units.short_name as unit', 'variation_location_details.qty_available as qty', 'variations.dpp_inc_tax as price')->get();
        $product_arr = [];
        foreach ($products as $product) {
            if ($default_store) {
                $variant = Variation_store_detail::where('store_id', $default_store)->where('product_id', $product->id)->get();
            } else {
                $variant = Variation_store_detail::where('product_id', $product->id)->get();
            }
            if ($variant) {
                $store_available_qty = 0;
                foreach ($variant as $var) {
                    $store_available_qty += $var->qty_available;
                }
                $product->store_available_qty = $store_available_qty;
            } else {
                $product->store_available_qty = 0;
            }
            $product_arr[$product->id] = $product->sku . ', ' . $product->name . ', ' . $this->num_f($product->qty, false, $business_details, true) . ' ' . $product->unit . ', ' . $this->num_f($product->price, true, $business_details, false) . ', (Available Qty : ' . $this->num_f($product->qty, false, $business_details, true) . ' ' . $product->unit . ')';
        }
        if ($default_store) {
            return $product_arr;
        } else {
            return array();
        }
    }

    public function getPropertyAccountSettingsByTransaction($transaction_id)
    {
        $transaction_sell_line = PropertySellLine::where('transaction_id', $transaction_id)->first();
        $account_settings = PropertyAccountSetting::where('property_id', $transaction_sell_line->property_id)->first();
        return $account_settings;
    }
    /**
     * Pay contact due at once
     *
     * @param obj $parent_payment, string $type
     *
     * @return void
     */

    public function getProductsByStoreId($business_id = false, $location_id = false, $store_id = false, $tab = false, $except = null, $module = null)
    {

        $business_details = Business::find($business_id);
        $business_locations = BusinessLocation::forDropdown($business_id);
        $products = Product::join('units', 'products.unit_id', 'units.id')
            ->join('variation_location_details', 'products.id', 'variation_location_details.product_id');

        // $products1 = Product::join('units', 'products.unit_id', 'units.id')
        // ->join('variation_location_details', 'products.id', 'variation_location_details.product_id')->get();


        if ($location_id) {
            $products = $products->join('stores', 'stores.location_id', 'variation_location_details.location_id');
            // $products = $products->join('variation_store_details', 'stores.id', 'variation_store_details.store_id');
        }
        $products = $products->join('variations', 'products.id', 'variations.product_id');
        // $products = $products->join('variations', function ($join) {
        //     $join->on('products.id', '=', 'variations.product_id');
        //     $join->on('variation_location_details.variation_id', '=', 'variations.id');
        //     $join->on('variations.id', '=', 'variation_store_details.variation_id');
        // })
        // ->active()
        // ->where('products.business_id', $business_id)
        // ->whereNull('variations.deleted_at');


        // if(!empty($module)){
        //     $products->forModule($module);
        // }

        if ($location_id) {
            $products->where('variation_location_details.location_id', $location_id);
        }

        // if(!empty($except)){
        //     $products->where('products.category_id','!=',$except);
        // }

        if ($store_id) {
            $products->where('stores.id', $store_id);
        }

        $cat_ids = [];
        // if($tab && $tab == 'other_sal'){
        //     $cat_ids = Category::where('business_id', $business_id)->where('name', 'Fuel')->pluck('id')->toArray();
        // }
        $products = $products->select('products.id', 'products.category_id', 'products.name', 'products.sku', 'units.short_name as unit', 'variation_location_details.qty_available as qty', 'variations.dpp_inc_tax as price')->get();
        $product_arr = [];
        foreach ($products as $product) {
            if ($cat_ids && (in_array($product->category_id, $cat_ids))) {
                continue;
            }
            $variant = Variation_store_detail::where('store_id', $store_id)->where('product_id', $product->id)->get();
            if ($variant) {
                $store_available_qty = 0;
                foreach ($variant as $var) {
                    $store_available_qty += $var->qty_available;
                }
                $product->store_available_qty = $store_available_qty;
            } else {
                $product->store_available_qty = 0;
            }
            $product_arr[$product->id] = $product->sku . ', ' . $product->name . ', ' . $this->num_f($product->qty, false, $business_details, true) . ' ' . $product->unit . ', ' . $this->num_f($product->price, true, $business_details, false) . ', (Available Qty : ' . $this->num_f($product->store_available_qty, false, $business_details, true) . ' ' . $product->unit . ')';
        }

        if (!empty($product_arr)) {

            return $this->createDropdownHtml($product_arr, 'Please Select');
        } else {
            return $this->createDropdownHtml($product_arr, 'No Item Found');
        }
    }

    // update post dated cheque field in account transaction after creating payment

    public function getLastQuotationRefNo()
    {
        $maxRefNo = Transaction::where("is_quotation", true)->where("type", "sell")->max("ref_no");
        $maxRefNo = $maxRefNo == "" ? 0 : $maxRefNo;
        return $maxRefNo + 1;
    }
}

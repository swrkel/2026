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
 * Pump operator balances, commission and excess/shortage.
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
 * Methods here: createOpeningBalanceTransactionForPumpOperator, updateOpeningBalanceTransactionForPumpOperator, getPumpOperatorBalance, getPumpOperatorLedgerSummary, formatPumpOperatorSummaryResponse, getPumpOperatorBFBalance, getPumpOperatorCommission, getPumpOperatorExcessOrShortage, getPumpOperatorExcessOrShortageByDate, payAtOnceExcessShortage, createExcessShortageAccountTransaction
 */
trait HandlesPumpOperators
{
public function createOpeningBalanceTransactionForPumpOperator($business_id, $pump_operator_id, $amount, $type, $location_id, $transaction_date = null)
    {
        $final_amount = $this->num_uf($amount);
        $ob_data = [
            'business_id' => $business_id,
            'location_id' => $location_id,
            'type' => 'opening_balance',
            'sub_type' => $type,
            'status' => 'final',
            'payment_status' => 'due',
            'pump_operator_id' => $pump_operator_id,
            'transaction_date' => \Carbon::parse($transaction_date) ?: \Carbon::now(),
            'total_before_tax' => $final_amount,
            'final_total' => $final_amount,
            'created_by' => request()->session()->get('user.id')
        ];
        //Update reference count
        $ob_ref_count = $this->setAndGetReferenceCount('opening_balance');
        //Generate reference number
        $ob_data['ref_no'] = $this->generateReferenceNumber('opening_balance', $ob_ref_count);
        //Create opening balance transaction
        $transaction = Transaction::create($ob_data);
        if ($type == 'shortage') {
            $transaction_type = 'debit';
            $account_id = $this->account_exist_return_id('Accounts Receivable');
        }
        if ($type == 'excess') {
            $transaction_type = 'credit';
            $account_id = $this->account_exist_return_id('Accounts Payable');
        }
        $account_transaction_data = [
            'amount' => abs($transaction->final_total),
            'account_id' => $account_id,
            'type' => $transaction_type,
            'sub_type' => 'ledger_show',
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'note' => null
        ];
        AccountTransaction::createAccountTransaction($account_transaction_data);

        if ($type == 'shortage') {
            $opening_balance_transaction_type = 'credit';
        }
        if ($type == 'excess') {
            $opening_balance_transaction_type = 'debit';
        }
        $opening_balance_equity_id = $this->account_exist_return_id('Opening Balance Equity Account');
        $this->createAccountTransaction($transaction, $opening_balance_transaction_type, $opening_balance_equity_id, abs($transaction->final_total));
    }

    public function updateOpeningBalanceTransactionForPumpOperator($business_id, $pump_operator_id, $amount, $type, $location_id, $transaction_date = null)
    {
        $final_amount = $this->num_uf($amount);

        $opening_bal = Transaction::where([
            'type' => 'opening_balance',
            'pump_operator_id' => $pump_operator_id,
            'business_id' => $business_id
        ])->first();

        if (!empty($opening_bal)) {
            AccountTransaction::where('transaction_id', $opening_bal->id)->forcedelete();
        }

        // print_r($amount);
        // if(!empty($amount)){

        $ob_data = [
            'business_id' => $business_id,
            'location_id' => $location_id,
            'type' => 'opening_balance',
            'sub_type' => $type,
            'status' => 'final',
            'payment_status' => 'due',
            'pump_operator_id' => $pump_operator_id,
            'transaction_date' => \Carbon::parse($transaction_date) ?: \Carbon::now(),
            'total_before_tax' => $final_amount,
            'final_total' => $final_amount,
            'created_by' => request()->session()->get('user.id')
        ];
        if (!empty($opening_bal)) {
            Transaction::where([
                'type' => 'opening_balance',
                'pump_operator_id' => $pump_operator_id,
                'business_id' => $business_id
            ])->update($ob_data);

            $transaction = $opening_bal;
        } else {

            //Update reference count
            $ob_ref_count = $this->setAndGetReferenceCount('opening_balance');
            //Generate reference number
            $ob_data['ref_no'] = $this->generateReferenceNumber('opening_balance', $ob_ref_count);
            //Create opening balance transaction
            $transaction = Transaction::create($ob_data);
        }


        if ($type == 'shortage') {
            $transaction_type = 'debit';
            $account_id = $this->account_exist_return_id('Accounts Receivable');
        }
        if ($type == 'excess') {
            $transaction_type = 'credit';
            $account_id = $this->account_exist_return_id('Accounts Payable');
        }
        $account_transaction_data = [
            'amount' => abs($transaction->final_total),
            'account_id' => $account_id,
            'type' => $transaction_type,
            'sub_type' => 'ledger_show',
            'operation_date' => $transaction->transaction_date,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'note' => null
        ];
        AccountTransaction::createAccountTransaction($account_transaction_data);

        if ($type == 'shortage') {
            $opening_balance_transaction_type = 'credit';
        }
        if ($type == 'excess') {
            $opening_balance_transaction_type = 'debit';
        }
        $opening_balance_equity_id = $this->account_exist_return_id('Opening Balance Equity Account');
        $this->createAccountTransaction($transaction, $opening_balance_transaction_type, $opening_balance_equity_id, abs($transaction->final_total));

        // }else{
        //     if(!empty($opening_bal)){
        //         $opening_bal->forcedelete();
        //     }
        // }



    }

    public function getPumpOperatorBalance($pump_operator_id)
    {
        $total_shortage = 0;
        $shortage = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
            ->where('pump_operators.id', $pump_operator_id)
            ->select([
                DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'shortage' AND t.status = 'final', ABS(final_total), 0)) as total_shortage"),
                DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'shortage' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as shortage_recover"),
            ])->groupBy('pump_operators.id')->first();
        if (!empty($shortage)) {
            $total_shortage = $shortage->total_shortage - $shortage->shortage_recover;
        }

        $total_excess = 0;
        $excess = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
            ->where('pump_operators.id', $pump_operator_id)
            ->select([
                DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'excess' AND t.status = 'final', ABS(final_total), 0)) as total_excess"),
                DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'excess' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as excess_paid"),
            ])->groupBy('pump_operators.id')->first();
        if (!empty($excess)) {
            $total_excess = $excess->total_excess - $excess->excess_paid;
        }

        $total_shortage_ob = 0;
        $shortage_ob = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
            ->where('pump_operators.id', $pump_operator_id)
            ->select([
                DB::raw("SUM(IF(t.type = 'opening_balance' AND sub_type = 'shortage' AND t.status = 'final', ABS(final_total), 0)) as total_shortage"),
                DB::raw("SUM(IF(t.type = 'opening_balance' AND sub_type = 'shortage' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as shortage_recover"),
            ])->groupBy('pump_operators.id')->first();
        if (!empty($shortage_ob)) {
            $total_shortage_ob = $shortage_ob->total_shortage - $shortage_ob->shortage_recover;
        }

        $total_excess_ob = 0;
        $excess_ob = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
            ->where('pump_operators.id', $pump_operator_id)
            ->select([
                DB::raw("SUM(IF(t.type = 'opening_balance' AND sub_type = 'excess' AND t.status = 'final', ABS(final_total), 0)) as total_excess"),
                DB::raw("SUM(IF(t.type = 'opening_balance' AND sub_type = 'excess' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as excess_paid"),
            ])->groupBy('pump_operators.id')->first();
        if (!empty($excess_ob)) {
            $total_excess_ob = $excess_ob->total_excess - $excess_ob->excess_paid;
        }



        $commission = PumpOperatorCommission::where('pump_operator_id', $pump_operator_id)
            ->sum('amount');

        $total_commission = !empty($commission) ? $commission : 0;

        return abs($total_shortage) - abs($total_excess) + abs($total_shortage_ob) - abs($total_excess_ob) + $total_commission;
    }

    /**
     * Returns a ledger summary for a pump operator or for all pump operators.
     *
     * @param  int         $business_id
     * @param  string      $start_date
     * @param  string      $end_date
     * @param  int|null    $pump_operator_id
     * @param  array       $filters
     * @return array
     */

    public function getPumpOperatorLedgerSummary($business_id, $start_date, $end_date, $pump_operator_id = null, $filters = [])
    {
        if (empty($start_date) || empty($end_date)) {
            return $this->formatPumpOperatorSummaryResponse([], $pump_operator_id);
        }

        $baseQuery = AccountTransaction::leftjoin('transactions', 'account_transactions.transaction_id', 'transactions.id')
            ->where('transactions.business_id', $business_id)
            ->where('account_transactions.sub_type', 'ledger_show')
            ->whereIn('transactions.sub_type', ['excess', 'shortage'])
            ->whereNull('account_transactions.deleted_at');

        if (! is_null($pump_operator_id)) {
            $baseQuery->where('transactions.pump_operator_id', $pump_operator_id);
        } else {
            $baseQuery->whereNotNull('transactions.pump_operator_id');
        }

        if (! empty($filters['location_id'])) {
            $baseQuery->where('transactions.location_id', $filters['location_id']);
        }

        $openingQuery = (clone $baseQuery)->whereDate('transactions.transaction_date', '<', $start_date);
        $periodQuery  = (clone $baseQuery)->whereDate('transactions.transaction_date', '>=', $start_date)
            ->whereDate('transactions.transaction_date', '<=', $end_date);

        $opening = $openingQuery->groupBy('transactions.pump_operator_id')
            ->select(
                'transactions.pump_operator_id',
                DB::raw("SUM(IF(account_transactions.type='debit', account_transactions.amount, 0)) as total_debit"),
                DB::raw("SUM(IF(account_transactions.type='credit', account_transactions.amount, 0)) as total_credit")
            )
            ->get()
            ->keyBy('pump_operator_id');

        $period = $periodQuery->groupBy('transactions.pump_operator_id')
            ->select(
                'transactions.pump_operator_id',
                DB::raw("SUM(IF(account_transactions.type='debit', account_transactions.amount, 0)) as balance_debit"),
                DB::raw("SUM(IF(account_transactions.type='credit', account_transactions.amount, 0)) as balance_credit"),
                DB::raw("SUM(IF(account_transactions.type='debit' AND transactions.sub_type='shortage' AND transactions.type IN ('settlement', 'opening_balance'), account_transactions.amount, 0)) as total_debit"),
                DB::raw("SUM(IF(account_transactions.type='credit' AND transactions.sub_type='excess' AND transactions.type IN ('settlement', 'opening_balance'), account_transactions.amount, 0)) as total_credit")
            )
            ->get()
            ->keyBy('pump_operator_id');

        $pumpOperatorIds = $opening->keys()->merge($period->keys())->unique();

        $summaries = [];
        foreach ($pumpOperatorIds as $operatorId) {
            $openingDebit  = $opening[$operatorId]->total_debit ?? 0;
            $openingCredit = $opening[$operatorId]->total_credit ?? 0;
            $openingBalance = $openingDebit - $openingCredit;

            $periodDebit  = $period[$operatorId]->total_debit ?? 0;
            $periodCredit = $period[$operatorId]->total_credit ?? 0;
            $periodBalanceDebit  = $period[$operatorId]->balance_debit ?? $periodDebit;
            $periodBalanceCredit = $period[$operatorId]->balance_credit ?? $periodCredit;
            $balanceForPeriod = $periodBalanceDebit - $periodBalanceCredit;
            $closingBalance   = $openingBalance + $balanceForPeriod;

            $summaries[$operatorId] = [
                'opening_balance'         => $openingBalance,
                'total_debit_for_period'  => $periodDebit,
                'total_credit_for_period' => $periodCredit,
                'balance_for_period'      => $balanceForPeriod,
                'closing_balance'         => $closingBalance,
            ];
        }

        return $this->formatPumpOperatorSummaryResponse($summaries, $pump_operator_id);
    }

    /**
     * Normalizes the ledger summary response.
     *
     * @param  array     $summaries
     * @param  int|null  $pump_operator_id
     * @return array
     */

    protected function formatPumpOperatorSummaryResponse($summaries, $pump_operator_id = null)
    {
        $empty = [
            'opening_balance'         => 0,
            'total_debit_for_period'  => 0,
            'total_credit_for_period' => 0,
            'balance_for_period'      => 0,
            'closing_balance'         => 0,
        ];

        if (is_null($pump_operator_id)) {
            return $summaries;
        }

        return $summaries[$pump_operator_id] ?? $empty;
    }

    public function getPumpOperatorBFBalance($pump_operator_id, $date)
    {
        $total_shortage = 0;
        $shortage = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
            ->where('pump_operators.id', $pump_operator_id)
            ->whereDate('t.transaction_date', '<', $date)
            ->select([
                DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'shortage' AND t.status = 'final' AND DATE(t.transaction_date) < '" . $date . "', ABS(final_total), 0)) as total_shortage"),
                DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'shortage' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND DATE(transaction_payments.paid_on) < '" . $date . "'), 0)) as shortage_recover"),
            ])->groupBy('pump_operators.id')->first();
        if (!empty($shortage)) {
            $total_shortage = $shortage->total_shortage - $shortage->shortage_recover;
        }

        $total_excess = 0;
        $excess = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
            ->where('pump_operators.id', $pump_operator_id)
            // ->whereDate('t.transaction_date','<',$date)
            ->select([
                DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'excess' AND t.status = 'final' AND DATE(t.transaction_date) < '" . $date . "', ABS(final_total), 0)) as total_excess"),
                DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'excess' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND DATE(transaction_payments.paid_on) < '" . $date . "'), 0)) as excess_paid"),
            ])->groupBy('pump_operators.id')->first();
        if (!empty($excess)) {
            $total_excess = $excess->total_excess - $excess->excess_paid;
            logger($excess->total_excess . "----" . $excess->excess_paid);
        }

        $total_shortage_ob = 0;
        $shortage_ob = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
            ->where('pump_operators.id', $pump_operator_id)
            ->whereDate('t.transaction_date', '<', $date)
            ->select([
                DB::raw("SUM(IF(t.type = 'opening_balance' AND sub_type = 'shortage' AND t.status = 'final' AND DATE(t.transaction_date) < '" . $date . "', ABS(final_total), 0)) as total_shortage"),
                DB::raw("SUM(IF(t.type = 'opening_balance' AND sub_type = 'shortage' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND DATE(transaction_payments.paid_on) < '" . $date . "'), 0)) as shortage_recover"),
            ])->groupBy('pump_operators.id')->first();
        if (!empty($shortage_ob)) {
            $total_shortage_ob = $shortage_ob->total_shortage - $shortage_ob->shortage_recover;
        }

        $total_excess_ob = 0;
        $excess_ob = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
            ->where('pump_operators.id', $pump_operator_id)
            ->whereDate('t.transaction_date', '<', $date)
            ->select([
                DB::raw("SUM(IF(t.type = 'opening_balance' AND sub_type = 'excess' AND t.status = 'final' AND DATE(t.transaction_date) < '" . $date . "', ABS(final_total), 0)) as total_excess"),
                DB::raw("SUM(IF(t.type = 'opening_balance' AND sub_type = 'excess' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND DATE(transaction_payments.paid_on) < '" . $date . "'), 0)) as excess_paid"),
            ])->groupBy('pump_operators.id')->first();
        if (!empty($excess_ob)) {
            $total_excess_ob = $excess_ob->total_excess - $excess_ob->excess_paid;
        }



        $commission = PumpOperatorCommission::whereDate('transaction_date', '<', $date)
            ->where('pump_operator_id', $pump_operator_id)
            ->sum('amount');

        $total_commission = !empty($commission) ? $commission : 0;

        return abs($total_shortage) - abs($total_excess) + abs($total_shortage_ob) - abs($total_excess_ob) + $total_commission;
    }

    public function getPumpOperatorCommission($pump_operator_id, $start_date, $end_date)
    {
        $commission = PumpOperatorCommission::whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->where('pump_operator_id', $pump_operator_id)
            ->sum('amount');

        $total_commission = !empty($commission) ? $commission : 0;

        return $total_commission;
    }

    public function getPumpOperatorExcessOrShortage($pump_operator_id, $type)
    {
        if ($type == 'shortage') {
            $total_shortage = 0;
            $shortage = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
                ->where('pump_operators.id', $pump_operator_id)
                ->select([
                    DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'shortage' AND t.status = 'final', ABS(final_total), 0)) as total_shortage"),
                    DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'shortage' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as shortage_recover"),
                ])->groupBy('pump_operators.id')->first();
            if (!empty($shortage)) {
                $total_shortage = $shortage->total_shortage - $shortage->shortage_recover;
            }
            return abs($total_shortage);
        }
        if ($type == 'excess') {
            $total_excess = 0;
            $excess = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
                ->where('pump_operators.id', $pump_operator_id)
                ->select([
                    DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'excess' AND t.status = 'final', ABS(final_total), 0)) as total_excess"),
                    DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'excess' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as excess_paid"),
                ])->groupBy('pump_operators.id')->first();
            if (!empty($excess)) {
                $total_excess = $excess->total_excess - $excess->excess_paid;
            }
            return abs($total_excess);
        }
    }

    public function getPumpOperatorExcessOrShortageByDate($pump_operator_id, $type, $start_date = null, $end_date = null)
    {
        $total_paid_query = Transaction::leftjoin('transaction_payments', 'transactions.id', 'transaction_payments.transaction_id')
            ->where('type', 'settlement')
            ->whereIn('sub_type', ['excess', 'shortage'])
            ->where('pump_operator_id', $pump_operator_id)
            ->whereDate('transaction_payments.paid_on', '>=', $start_date)
            ->whereDate('transaction_payments.paid_on', '<=', $end_date)
            ->select(
                DB::raw("SUM(IF(transactions.sub_type = 'excess', ABS(transaction_payments.amount), 0)) as excess_paid"),
                DB::raw("SUM(IF(transactions.sub_type = 'shortage', ABS(transaction_payments.amount), 0)) as shortage_recovered")
            )->first();
        $balance = 0;
        if ($type == 'shortage') {
            $total_shortage = 0;
            $query = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
                ->where('pump_operators.id', $pump_operator_id)
                ->select([
                    DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'shortage' AND t.status = 'final', ABS(final_total), 0)) as total_shortage"),
                    DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'shortage' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND DATE(transaction_payments.paid_on) >= $start_date AND DATE(transaction_payments.paid_on) <= $end_date), 0)) as shortage_recover"),
                ])->groupBy('pump_operators.id');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('t.transaction_date', '>=', $start_date);
                $query->whereDate('t.transaction_date', '<=', $end_date);
            }
            $shortage = $query->first();
            if (!empty($shortage)) {
                $total_shortage = !empty($shortage->total_shortage) ? $shortage->total_shortage : 0;
                $shortage_recover = !empty($total_paid_query->shortage_recovered) ? $total_paid_query->shortage_recovered : 0;
                $balance = $total_shortage - $shortage_recover;
            }
            return abs($balance);
        }
        if ($type == 'excess') {
            $total_excess = 0;
            $query = PumpOperator::leftjoin('transactions as t', 'pump_operators.id', 't.pump_operator_id')
                ->where('pump_operators.id', $pump_operator_id)
                ->select([
                    DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'excess' AND t.status = 'final', ABS(final_total), 0)) as total_excess"),
                    DB::raw("SUM(IF(t.type = 'settlement' AND sub_type = 'excess' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,ABS(amount))) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id AND DATE(transaction_payments.paid_on) >= $start_date AND DATE(transaction_payments.paid_on) <= $end_date), 0)) as excess_paid"),
                ])->groupBy('pump_operators.id');
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereDate('t.transaction_date', '>=', $start_date);
                $query->whereDate('t.transaction_date', '<=', $end_date);
            }
            $excess = $query->first();
            if (!empty($excess)) {
                $total_excess = !empty($excess->total_excess) ? $excess->total_excess : 0;
                $excess_paid = !empty($total_paid_query->excess_paid) ? $total_paid_query->excess_paid : 0;
                $balance = $total_excess - $excess_paid;
            }
            return abs($balance);
        }
    }

    public function payAtOnceExcessShortage($inputs, $sub_type, $pump_operator_id)
    {
        $business_id = request()->session()->get('business.id') ?: Auth::user()->business_id;
        $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();
        $due_transactions = Transaction::where('pump_operator_id', $pump_operator_id)
            ->whereIn('type', ['opening_balance', 'settlement'])
            ->where('sub_type', $sub_type)
            ->where('payment_status', '!=', 'paid')
            ->orderBy('transaction_date', 'asc')
            ->get();
        $total_amount = $inputs['amount'];

        // store transaction for parent pament amount
        $ob_data = [
            'business_id' => $business_id,
            'location_id' => $pump_operator->location_id,
            'type' => $sub_type . '_bulk_payment',
            'sub_type' => $sub_type,
            'status' => 'final',
            'payment_status' => 'paid',
            'pump_operator_id' => $pump_operator_id,
            'transaction_date' => \Carbon::parse($inputs['paid_on'])->format('Y-m-d'),
            'total_before_tax' => $total_amount,
            'final_total' => $total_amount,
            'tax_amount' => 0,
            'discount_type' => 'fixed',
            'discount_amount' => 0,
            'is_settlement' => 1,
            'created_by' => auth()->user()->id,
            'invoice_no' => $inputs['payment_ref_no']
        ];

        $parent_transaction = Transaction::create($ob_data);

        $parent_array = [
            'transaction_id' => $parent_transaction->id,
            'business_id' => $business_id,
            'method' => $inputs['method'],
            'transaction_no' => null,
            'card_transaction_number' => $inputs['card_transaction_number'],
            'bank_name' => !empty($inputs['bank_name']) ? $inputs['bank_name'] : null,
            'cheque_number' => $inputs['cheque_number'],
            'bank_account_number' => !empty($inputs['bank_account_number']) ? $inputs['bank_account_number'] : null,
            'bank_name' => !empty($inputs['bank_name']) ? $inputs['bank_name'] : null,
            'paid_on' => $inputs['paid_on'],
            'created_by' => Auth::user()->id,
            'payment_ref_no' => $inputs['payment_ref_no'],
            'amount' => $total_amount
        ];

        $parent_payment = TransactionPayment::create($parent_array);
        $this->createExcessShortageAccountTransaction($parent_payment, $inputs, $sub_type, $parent_transaction, $pump_operator);



        $tranaction_payments = [];
        if ($due_transactions->count()) {
            foreach ($due_transactions as $transaction) {
                $break = false;
                if ($total_amount > 0) {
                    $total_paid = $this->getTotalPaid($transaction->id);
                    $due = abs($transaction->final_total) - $total_paid;
                    $array = [
                        'transaction_id' => $transaction->id,
                        'business_id' => $business_id,
                        'method' => $inputs['method'],
                        'transaction_no' => null,
                        'card_transaction_number' => $inputs['card_transaction_number'],
                        'bank_name' => !empty($inputs['bank_name']) ? $inputs['bank_name'] : null,
                        'cheque_number' => $inputs['cheque_number'],
                        'bank_account_number' => !empty($inputs['bank_account_number']) ? $inputs['bank_account_number'] : null,
                        'bank_name' => !empty($inputs['bank_name']) ? $inputs['bank_name'] : null,
                        'paid_on' => $inputs['paid_on'],
                        'created_by' => Auth::user()->id,
                        // 'payment_for' => $inputs['payment_for'],
                        'parent_id' => $parent_payment->id
                    ];
                    $array['payment_ref_no'] = $inputs['payment_ref_no'];


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
                        $break = true;
                    }
                    $transaction_payment = TransactionPayment::create($array);
                    if ($break) {
                        break;
                    }
                }
            }
            // $this->createExcessShortageAccountTransaction($transaction_payment, $inputs, $sub_type, $transaction, $pump_operator);
            if ($sub_type == 'excess') {
                // $account_transaction_data['type'] = 'credit';
                $pump_operator->excess_amount = abs($pump_operator->excess_amount) - $inputs['amount'];
            }
            if ($sub_type == 'shortage') {
                // $account_transaction_data['type'] = 'debit';
                $pump_operator->short_amount = abs($pump_operator->short_amount) - $inputs['amount'];
            }
            $pump_operator->save();
        }
    }

    public function createExcessShortageAccountTransaction($transaction_payment, $inputs, $sub_type, $transaction, $pump_operator)
    {
        //create account transaction for expense account selected 
        $account_transaction_data = [
            'amount' => $inputs['amount'],
            'sub_type' => 'ledger_show',
            'operation_date' => $inputs['paid_on'],
            'created_by' => Auth::user()->id,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => $transaction_payment->id,
            'note' => null
        ];

        $location_id = $pump_operator->location_id;

        $account_transaction_data['account_id'] = $inputs['account_id'];

        if ($sub_type == 'excess') {
            $account_transaction_data['type'] = 'credit';
        }
        if ($sub_type == 'shortage') {
            $account_transaction_data['type'] = 'debit';
        }
        $account_transaction_data['sub_type'] = null;

        AccountTransaction::createAccountTransaction($account_transaction_data);
        $transaction_payment->account_id = $account_transaction_data['account_id'];
        $transaction_payment->save();

        // Accounts receivable entry
        if ($sub_type == 'excess') {
            $account_transaction_data['type'] = 'debit';
            $account_transaction_data['account_id'] = $this->account_exist_return_id('Accounts Receivable');
            $account_transaction_data['sub_type'] = 'ledger_show';
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }

        if ($sub_type == 'shortage') {
            $account_transaction_data['type'] = 'credit';
            $account_transaction_data['account_id'] = $this->account_exist_return_id('Accounts Receivable');
            $account_transaction_data['sub_type'] = 'ledger_show';
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }
    }
}

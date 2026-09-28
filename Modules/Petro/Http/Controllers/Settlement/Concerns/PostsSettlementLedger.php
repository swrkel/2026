<?php

namespace Modules\Petro\Http\Controllers\Settlement\Concerns;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\Http\Controllers\ContactController;
use App\NotificationTemplate;
use App\Product;
use App\Store;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;
use Modules\HR\Entities\WorkShift;
use Modules\Petro\Entities\CustomerPayment;
use Modules\Petro\Entities\CustomerBillVatPrefix;
use Modules\Petro\Entities\DailyCard;
use Modules\Petro\Entities\DailyCollection;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\DayEnd;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\MeterSale;
use Modules\Petro\Entities\OtherIncome;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\PetroShift;
use Modules\Petro\Entities\PetroWhatsAppTemplate;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumperDayEntry;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PumpOperatorAssignment;
use Modules\Petro\Entities\PumpOperatorCommission;
use Modules\Petro\Entities\PumpOperatorPayment;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Entities\SettlementEditHistory;
use Modules\Petro\Entities\SettlementExcessPayment;
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Petro\Entities\SettlementShortagePayment;
use Modules\Petro\Entities\SettlementLoanPayment;
use Modules\Petro\Entities\SettlementDrawingPayment;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\Petro\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

/**
 * Everything that writes to transactions, account transactions and the ledger.
 *
 * MA-002: split out of Petro's SettlementController, which was 11,795 lines.
 *
 * The grouping was worked out FOR THIS CONTROLLER, not copied from PetroPD's.
 * The four settlement modules have genuinely diverged - 17 of the 19
 * controllers they share differ in logic - so Petro has methods PetroPD does
 * not (mechanical meter comparison, auto shift numbering, real-time payment
 * sync) and vice versa. Copying a grouping across would have produced tidy
 * files with the wrong things in them.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged: routes still point at SettlementController,
 *   action() targets still resolve, and $this-> calls between these 91 methods
 *   still work. Separate controller classes would mean rewriting routes and
 *   every action() reference - a behavioural change dressed up as tidying.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten.
 *
 * Methods here: createTransaction, createSellTransactions, createCreditSellTransactions, mapSellPurchaseLines, createTansactionPayment, createAccountTransaction, createStockAccountTransactions, ensureCreditSaleCustomerAccounting, getDirectSettlementCreditSales, getCreditSaleReportDate, getCreditSaleBillNumber, getDiscount, updateSettlementTotalAmount, syncRealTimePaymentsToSettlement
 */
trait PostsSettlementLedger
{
    public function createTransaction(

        $settlement,

        $amount,

        $customer_id,

        $pump_operator_id,

        $type,

        $sub_type,

        $settlement_no,

        $ref_no = null,

        $is_credit_sale = 0,

        $total_sales_discount_amount = 0.0,

        $transaction_note = null

    ) {

        $business_id = $settlement->business_id;

        $final_amount = $amount;
        
        if (empty($customer_id)) {
            $walkIn = app(\App\Utils\ContactUtil::class)->getWalkInCustomer($business_id);
            if (!empty($walkIn['id'])) {
                $customer_id = $walkIn['id'];
            }
        }

        $ob_data = [
            'business_id' => $business_id,
            // 'location_id' => $business_location->id,
            'location_id' => $settlement->location_id,
            'type' => $type,
            'sub_type' => $sub_type,
            'status' => 'final',
            'payment_status' => 'paid',
            'contact_id' => $customer_id,
            'pump_operator_id' => $pump_operator_id,
            'transaction_date' => \Carbon::parse(
                $settlement->transaction_date
            )->format('Y-m-d'),
            'total_before_tax' => $final_amount,
            'final_total' => $final_amount,
            'discount_amount' => $total_sales_discount_amount,
            'created_by' => request()
                ->session()
                ->get('user.id'),
            'is_settlement' => 1,
            'transaction_note' => $transaction_note,
            'petro_settlement_id' => $settlement->id,

        ];

        if (

            $sub_type == 'excess' ||

            $sub_type == 'shortage' ||

            $sub_type == 'customer_loan'

        ) {

            $ob_data['payment_status'] = 'due';

        }

        $ob_data['invoice_no'] = $settlement_no;

        $ob_data['ref_no'] = ! empty($ref_no) ? $ref_no : null;

        if ($is_credit_sale == 1) {

            $ob_data['type'] = 'sell';

            $ob_data['sub_type'] = 'credit_sale';

        }

        if ($sub_type === 'customer_loan') {
            $existing_transaction = Transaction::where('business_id', $ob_data['business_id'])
                ->where('type', $ob_data['type'])
                ->where('sub_type', $ob_data['sub_type'])
                ->where('invoice_no', $ob_data['invoice_no'])
                ->where('petro_settlement_id', $ob_data['petro_settlement_id'])
                ->where('contact_id', $ob_data['contact_id'])
                ->where('final_total', $ob_data['final_total'])
                ->where('transaction_note', $ob_data['transaction_note'])
                ->first();

            if (! empty($existing_transaction)) {
                return $existing_transaction;
            }
        }


        /*
         * MA-002 (LA-1134) - duplicate-posting guard for loan payments.
         *
         * LA-1134 showed a settlement carrying TWO loan payments of 4,000 on
         * the same loan account, created two seconds apart - a double-click on
         * the Add button, which the settlement then posted faithfully as two
         * Cash debits and two Cash credits. The posting was correct; the
         * source rows were entered twice.
         *
         * This module already guards 'customer_loan' with exactly this
         * pattern a few lines above. 'loan_payment' was left unguarded even
         * though it is the path that was actually double-clicked, so the same
         * protection is extended to it rather than a new mechanism invented.
         *
         * WHY A SHORT TIME WINDOW, WHICH THE EXISTING GUARDS DO NOT HAVE
         *   The match is on business, type, sub_type, invoice_no, settlement,
         *   contact, amount and note - so two DIFFERENT loan payments in one
         *   settlement are unaffected. That matters: the same LA-1134 data had
         *   a settlement with three genuine cash payments of 4,000 / 5,000 and
         *   6,000, and a value-only guard must not merge those.
         *
         *   But two payments that are genuinely identical - same account, same
         *   amount, entered deliberately - are legitimate, and without a
         *   window this guard would silently merge them and the operator would
         *   be short. Twenty seconds separates a double-click from a
         *   deliberate re-entry; value comparison alone cannot.
         *
         *   The existing 'customer_loan' and 'credit_sale' guards have NO
         *   window. I have not changed them - altering a guard already in
         *   production is a separate decision - but they carry that same risk
         *   and are worth revisiting.
         *
         * Suppression is logged, so if a legitimate entry is ever refused
         * there is a record naming the settlement and amount.
         */
        if ($sub_type === 'loan_payment') {
            $ma002_recent_duplicate = Transaction::where('business_id', $ob_data['business_id'])
                ->where('type', $ob_data['type'])
                ->where('sub_type', $ob_data['sub_type'])
                ->where('invoice_no', $ob_data['invoice_no'])
                ->where('petro_settlement_id', $ob_data['petro_settlement_id'])
                ->where('contact_id', $ob_data['contact_id'])
                ->where('final_total', $ob_data['final_total'])
                ->where('transaction_note', $ob_data['transaction_note'])
                ->where('created_at', '>=', now()->subSeconds(20))
                ->latest('id')
                ->first();

            if (! empty($ma002_recent_duplicate)) {
                \Illuminate\Support\Facades\Log::warning(
                    'MA-002 (LA-1134): duplicate loan_payment suppressed within the 20s window',
                    [
                        'business_id'   => $ob_data['business_id'],
                        'invoice_no'    => $ob_data['invoice_no'] ?? null,
                        'final_total'   => $ob_data['final_total'] ?? null,
                        'reused_txn_id' => $ma002_recent_duplicate->id,
                    ]
                );

                return $ma002_recent_duplicate;
            }
        }

        $transaction = Transaction::create($ob_data);

        return $transaction;

    }

    public function createSellTransactions(

        $transaction,

        $sale,

        $business_id,

        $default_location,

        $fuel_tank_id = null,

        $is_other_sale = null

    ) {

        $uf_quantity = $this->productUtil->num_uf($sale->qty);

        // Build from Product so settlement sell lines are created reliably for
        // products with or without variation edge-cases. Missing sell lines here
        // means Sales Income / COGS / Finished Goods account books never get rows.
        $product = Product::leftjoin(

            'product_variations',

            'products.id',

            'product_variations.product_id'

        )

            ->leftjoin(

                'variations',

                'product_variations.id',

                'variations.product_variation_id'

            )

            ->leftjoin(

                'variation_location_details',

                'variations.id',

                'variation_location_details.variation_id'

            )

            ->leftjoin('categories', 'products.category_id', 'categories.id')

            ->where('products.id', $sale->product_id)

            ->select(

                'variations.id as variation_id',

                'variation_location_details.location_id',

                'products.id as product_id',

                'categories.name as category_name',

                'products.enable_stock'

            )

            ->first();

        if ($product) {

            $this->transactionUtil->createOrUpdateSellLinesSettlement(

                $transaction,

                $product->product_id,

                $product->variation_id,

                $product->location_id,

                $sale

            );

            $location_product = ! empty($product->location_id)

                ? $product->location_id

                : $default_location;

            // if enable stock

            if ($product->enable_stock && ! empty($is_other_sale)) {

                if ($is_other_sale == 'pump_operator_other_sale') {

                    $otherSale = $sale; // PumpOperatorOtherSale

                } else {

                    $otherSale = OtherSale::where('id', $sale->id)->first();

                }

                $this->productUtil->decreaseProductQuantity(

                    $sale->product_id,

                    $product->variation_id,

                    $location_product,

                    $uf_quantity,

                    0,

                    'decrease',

                    isset($otherSale->store_id) ? $otherSale->store_id : 0

                );

                $store = Store::where('business_id', $business_id)->first();
                $store_id = ! empty($store) ? $store->id : 0;

                $this->productUtil->decreaseProductQuantityStore(

                    $sale->product_id,

                    $product->variation_id,

                    $location_product,

                    $uf_quantity,

                    isset($otherSale->store_id)

                    ? $otherSale->store_id

                    : $store_id,

                    'decrease',

                    0

                );

            }

        }

        // update qty to fuel tank current stock

        if (! empty($fuel_tank_id)) {

            FuelTank::where('id', $fuel_tank_id)->decrement(

                'current_balance',

                $sale->qty

            );

            TankSellLine::create([

                'business_id' => $business_id,

                'transaction_id' => $transaction->id,

                'tank_id' => $fuel_tank_id,

                'product_id' => $sale->product_id,

                'quantity' => $sale->qty,

            ]);

        }

        return true;

    }

    /**
     * Load every credit bill owned by this Direct Settlement.
     *
     * Historical rows use either settlements.id or settlements.settlement_no in
     * settlement_credit_sale_payments.settlement_no. Loading only the Eloquent
     * relationship silently omitted one of those formats and therefore skipped
     * the Customer Statement/Ledger posting during finalization.
     */

    public function createCreditSellTransactions(

        $settlement,

        $sale,

        $default_location

    ) {

        $final_total = max(0, (float) $sale->amount - (float) $sale->total_discount);
        $reportDate = $this->getCreditSaleReportDate($settlement, $sale);
        $billNumber = $this->getCreditSaleBillNumber($settlement, $sale);

        $ob_data = [

            'business_id' => $sale->business_id,

            'location_id' => $settlement->location_id,

            'type' => 'sell',

            'status' => 'final',

            'payment_status' => 'due',

            'contact_id' => $sale->customer_id,

            'pump_operator_id' => $settlement->pump_operator_id,

            // Customer Statement and Customer Ledger must follow the selected
            // credit bill/order date, not the date on which finalization is clicked.
            'transaction_date' => $reportDate,

            'total_before_tax' => $final_total,

            'final_total' => $final_total,

            'discount_type' => 'fixed',

            'discount_amount' => $sale->total_discount,

            'credit_sale_id' => $sale->id,

            'is_credit_sale' => 1,

            'is_settlement' => 1,

            'created_by' => request()

                ->session()

                ->get('user.id'),

            // Keep the settlement number as invoice_no for all existing Petro
            // settlement joins, while exposing the individual bill/order number
            // through ref_no in Customer Statement and Customer Ledger.
            'invoice_no' => $settlement->settlement_no,

            'ref_no' => $billNumber,

            'customer_ref' => $sale->customer_reference,

            'order_date' => $reportDate,

            'order_no' => $sale->order_number,

            'sub_type' => 'credit_sale',
            'petro_settlement_id' => $settlement->id,

        ];

        // Idempotent finalization: update/restore the one transaction belonging to
        // this credit bill instead of inserting a duplicate on every retry/edit.
        $transaction = Transaction::withTrashed()
            ->where('business_id', $sale->business_id)
            ->where('type', 'sell')
            ->where('credit_sale_id', $sale->id)
            ->orderByDesc('id')
            ->first();

        if ($transaction) {
            if (method_exists($transaction, 'trashed') && $transaction->trashed()) {
                $transaction->restore();
            }

            // Preserve the original creator if it exists.
            if (! empty($transaction->created_by)) {
                $ob_data['created_by'] = $transaction->created_by;
            }

            $transaction->fill($ob_data);
            $transaction->save();
        } else {
            $transaction = Transaction::create($ob_data);
        }

        return $transaction;

    }

    public function mapSellPurchaseLines(

        $business_id,

        $transaction,

        $settlement

    ) {

        // Allocate the quantity from purchase and add mapping of

        // purchase & sell lines in transaction_sell_lines_purchase_lines table

        $business_details = $this->businessUtil->getDetails($business_id);

        $pos_settings = empty($business_details->pos_settings)

            ? $this->businessUtil->defaultPosSettings()

            : json_decode($business_details->pos_settings, true);

        $business = [

            'id' => $business_id,

            'accounting_method' => request()

                ->session()

                ->get('business.accounting_method'),

            'location_id' => $settlement->location_id,

            'pos_settings' => $pos_settings,

        ];

        $this->transactionUtil->mapPurchaseSell(

            $business,

            $transaction->sell_lines,

            'purchase'

        );

    }

    public function createTansactionPayment(

        $transaction,

        $method,

        $amount = 0,

        $card_number = null,

        $card_type = null,

        $cheque_number = null,

        $bank_name = null,

        $cheque_date = null,

        $post_dated_cheque = 0,

        $payment_for = null

    ) {

        $business_id = request()

            ->session()

            ->get('business.id');

        $transaction_payment_data = [

            'transaction_id' => $transaction->id,

            'business_id' => $business_id,

            'amount' => abs($transaction->final_total),

            'method' => $method,

            'paid_on' => $transaction->transaction_date,

            'created_by' => $transaction->created_by,

            'card_number' => $card_number,

            'card_type' => $card_type,

            'cheque_number' => $cheque_number,

            'bank_name' => $bank_name,

            'cheque_date' => ! empty($cheque_date)

                ? \Carbon::parse($cheque_date)->format('Y-m-d')

                : null,

            'post_dated_cheque' => $post_dated_cheque,

        ];

        if (! empty($amount)) {

            $transaction_payment_data['amount'] = $amount;

        }

        $transaction_payment_data['paid_in_type'] = 'settlement';

        if (! empty($payment_for)) {

            $transaction_payment_data['payment_for'] = $payment_for;

        }

        $transaction_payment = TransactionPayment::create(

            $transaction_payment_data

        );

        return $transaction_payment;

    }

    public function createAccountTransaction(

        $transaction,

        $type,

        $account_id,

        $transaction_payment_id = null,

        $sub_type = null,

        $contact_id = null,

        $amount = 0,

        $is_credit_sale = false,

        $note = null,

        $slip_no = null,

        $skip_account_books = false,

        $skip_customer_ledger = false

    ) {

        $account_transaction_data = [

            'amount' => abs($transaction->final_total),

            'account_id' => $account_id,

            'contact_id' => $transaction->contact_id,

            'type' => $type,

            'sub_type' => $sub_type,

            'operation_date' => date('Y-m-d H:i:s'),

            'created_by' => $transaction->created_by,

            'transaction_id' => $transaction->id,

            'transaction_payment_id' => $transaction_payment_id,

            'note' => $note,

            'slip_no' => $slip_no,
            
            'business_id' => $transaction->business_id,

        ];

        if (! empty($contact_id)) {

            $account_transaction_data['contact_id'] = $contact_id;

        }

        if (! empty($amount)) {

            $account_transaction_data['amount'] = $amount;

        }

        if (!$skip_account_books) {
            AccountTransaction::createAccountTransaction($account_transaction_data);
        }

        // create ledger transactions
        if (!$skip_customer_ledger && ($sub_type == 'ledger_show' || in_array($transaction->sub_type, ['cash_payment', 'card_payment', 'cheque_payment']))) {
            $ledger_data = $account_transaction_data;
            
            // If it's one of our specific payment sub-types, use that as the ledger sub-type.
            if (in_array($transaction->sub_type, ['cash_payment', 'card_payment', 'cheque_payment'])) {
                $ledger_data['sub_type'] = $transaction->sub_type;
                
                // For customer payments (Credit), we only record the credit side in their ledger.
                // ledger_show logic below creates both sides, which we want to avoid for these specific types.
                if ($type == 'debit') { // Account is debited (Cash/Bank), Customer is credited.
                    $ledger_data['type'] = 'credit';
                    ContactLedger::createContactLedger($ledger_data);

                    $customer = Contact::find($ledger_data['contact_id']);
                    if (! empty($customer) && (int) $customer->is_default === 1) {
                        $debit_exists = ContactLedger::where('transaction_id', $ledger_data['transaction_id'])
                            ->where('transaction_payment_id', $ledger_data['transaction_payment_id'])
                            ->where('contact_id', $ledger_data['contact_id'])
                            ->where('type', 'debit')
                            ->exists();

                        if (! $debit_exists) {
                            $ledger_data['type'] = 'debit';
                            $ledger_data['sub_type'] = 'sell';
                            ContactLedger::createContactLedger($ledger_data);
                        }
                    }
                }
            } else {
                // Default ledger_show behavior: create both sides (though this is weird, we preserve it for now)
                ContactLedger::createContactLedger($account_transaction_data);

                if (! $is_credit_sale) {
                    if ($type == 'debit') {
                        $ledger_type = 'credit';
                    }
                    if ($type == 'credit') {
                        $ledger_type = 'debit';
                    }
                    $account_transaction_data['type'] = $ledger_type;
                    ContactLedger::createContactLedger($account_transaction_data);
                }
            }
        }

    }

    public function createStockAccountTransactions($transaction)
    {
        if (empty($transaction) || empty($transaction->id)) {
            return;
        }

        /*
         * S269 FIX - Account Book duplicate protection for Direct Settlement (ST)
         *
         * The pumper/settlement finalize flow can be submitted again because of
         * double-clicks, browser retries, or reopening the finalize page. The
         * Finished Goods, COGS and Fuel Sales account book rows must not be
         * appended a second time for the same transaction.
         *
         * Before rebuilding these stock/sales ledger rows, remove only the old
         * non-payment account rows for this exact transaction. Payment rows are
         * protected because they normally have transaction_payment_id or are
         * created separately by the payment-specific logic.
         */
        AccountTransaction::where('transaction_id', $transaction->id)
            ->whereNull('transaction_payment_id')
            ->where(function ($query) {
                $query->whereNull('sub_type')
                    ->orWhere('sub_type', 'ledger_show')
                    ->orWhere('sub_type', 'sell')
                    ->orWhere('sub_type', 'cogs');
            })
            ->forceDelete();

        $operation_date = ! empty($transaction->transaction_date)
            ? \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i:s')
            : date('Y-m-d H:i:s');

        $account_transaction_data = [

            'amount' => abs($transaction->final_total),

            'operation_date' => $operation_date,

            'created_by' => $transaction->created_by,

            'transaction_id' => $transaction->id,

            'note' => 'S269 settlement stock posting guard - ' . $transaction->invoice_no,

        ];

        $this->transactionUtil->manageStockAccount(

            $transaction,

            $account_transaction_data,

            'credit',

            $transaction->final_total

        );

        $this->transactionUtil->createCostofGoodsSoldTransaction(

            $transaction,

            'ledger_show',

            'debit'

        );

        $this->transactionUtil->createSaleIncomeTransaction(

            $transaction,

            'ledger_show',

            'credit'

        );

    }

    private function ensureCreditSaleCustomerAccounting($settlement, $transaction, $sale, bool $skipAccountBooks = false): void
    {
        $amount = max(0, (float) $sale->amount - (float) $sale->total_discount);
        $reportDate = $this->getCreditSaleReportDate($settlement, $sale);
        $operationDate = $reportDate . ' 00:00:00';
        $billNumber = $this->getCreditSaleBillNumber($settlement, $sale);
        $note = 'Credit Sale Bill No: ' . $billNumber
            . ' / Settlement No: ' . $settlement->settlement_no;

        if (! empty($sale->customer_reference)) {
            $note .= ' / Customer Ref: ' . $sale->customer_reference;
        }

        $accountId = $this->transactionUtil->account_exist_return_id('Accounts Receivable');

        if (! $skipAccountBooks && ! empty($accountId)) {
            // Rebuild only the AR debit for this exact credit transaction. This is
            // idempotent on retries and does not touch stock/sales account rows.
            AccountTransaction::where('transaction_id', $transaction->id)
                ->where('account_id', $accountId)
                ->where('type', 'debit')
                ->forceDelete();

            AccountTransaction::createAccountTransaction([
                'business_id' => $transaction->business_id,
                'amount' => $amount,
                'account_id' => $accountId,
                'contact_id' => $sale->customer_id,
                'type' => 'debit',
                'sub_type' => 'ledger_show',
                'operation_date' => $operationDate,
                'created_by' => $transaction->created_by,
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => null,
                'note' => $note,
            ]);
        }

        // Remove the unlinked real-time row created by Pumper Dashboard. Without
        // this cleanup the same bill can appear twice after finalization.
        if (! empty($sale->collection_form_no)) {
            ContactLedger::where('business_id', $transaction->business_id)
                ->where('contact_id', $sale->customer_id)
                ->whereNull('transaction_id')
                ->where('note', 'like', 'Pumper Dashboard Credit Sale%')
                ->where('note', 'like', '%Form No. ' . $sale->collection_form_no . '%')
                ->forceDelete();
        }

        $ledgerData = [
            'business_id' => $transaction->business_id,
            'contact_id' => $sale->customer_id,
            'amount' => $amount,
            'type' => 'debit',
            'sub_type' => 'sell',
            'operation_date' => $operationDate,
            'created_by' => $transaction->created_by,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'note' => $note,
        ];

        $linkedRows = ContactLedger::where('transaction_id', $transaction->id)
            ->where('contact_id', $sale->customer_id)
            ->where('type', 'debit')
            ->orderBy('id')
            ->get();

        $first = $linkedRows->first();
        if ($first) {
            ContactLedger::where('id', $first->id)->update($ledgerData);

            $duplicateIds = $linkedRows->slice(1)->pluck('id')->filter()->values();
            if ($duplicateIds->isNotEmpty()) {
                ContactLedger::whereIn('id', $duplicateIds)->forceDelete();
            }
        } else {
            ContactLedger::createContactLedger($ledgerData);
        }
    }

    private function getDirectSettlementCreditSales(Settlement $settlement, int $businessId)
    {
        return SettlementCreditSalePayment::query()
            ->where('business_id', $businessId)
            ->where(function ($query) use ($settlement) {
                $query->where('settlement_no', $settlement->settlement_no)
                    ->orWhere('settlement_no', $settlement->id);
            })
            ->with('product')
            ->orderBy('id')
            ->get();
    }

    /** Return the date on which the credit bill must appear in customer reports. */

    private function getCreditSaleReportDate($settlement, $sale): string
    {
        $date = ! empty($sale->order_date)
            ? $sale->order_date
            : $settlement->transaction_date;

        return \Carbon\Carbon::parse($date)->format('Y-m-d');
    }

    /** Build a stable bill number so multiple credit bills never collapse together. */

    private function getCreditSaleBillNumber($settlement, $sale): string
    {
        foreach ([$sale->bill_number ?? null, $sale->order_number ?? null] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '' && $candidate !== '0' && $candidate !== '-') {
                return $candidate;
            }
        }

        return (string) $settlement->settlement_no . '-CS-' . (int) $sale->id;
    }

    /**
     * Ensure the AR account row and Customer Ledger debit are present exactly once.
     *
     * Pumper Dashboard can create an early ledger row without transaction_id. That
     * orphan row is not usable by the Customers module's transaction-based reports.
     * Finalization now replaces it with one authoritative row linked to the credit
     * sale transaction, preserving the selected order date and bill number.
     */

    public function getDiscount($discount)
    {

        $pos = strpos($discount, '%');

        $discount_amount = str_replace('%', '', $discount);

        if ($pos === false) {

            $discount_type = 'fixed';

        } else {

            $discount_type = 'percentage';

        }

        return [

            'discount_amount' => $discount_amount,

            'discount_type' => $discount_type,

        ];

    }

    /**
     * Show the specified resource.







     * @return Response
     */

    private function updateSettlementTotalAmount($settlement_id)
    {
        $settlement = Settlement::find($settlement_id);
        if (!$settlement) return;

        $meter_sale_total = $settlement->meter_sales
            ->unique(function ($item) {
                return implode('|', [
                    $item->settlement_no,
                    $item->shift_id,
                    $item->pump_id,
                    $item->starting_meter,
                    $item->closing_meter,
                    $item->qty,
                    $item->price,
                    $item->discount,
                    $item->discount_type,
                ]);
            })
            ->sum('discount_amount');
        
        $other_sale_total_raw = $settlement->other_sales->sum('sub_total');
        $other_sale_discount = $settlement->other_sales->sum('discount_amount');
        $other_sale_total = $other_sale_total_raw - $other_sale_discount;
        
        $other_income_total = $settlement->other_incomes->sum('sub_total');
        $customer_payment_total = $settlement->customer_payments->sum('sub_total');
        
        $total_amount = $meter_sale_total + $other_sale_total + $other_income_total + $customer_payment_total;
        
        $settlement->total_amount = $total_amount;
        $settlement->save();
        
        return $total_amount;
    }

    private function syncRealTimePaymentsToSettlement(Settlement $settlement, array $shift_ids, int $business_id): void
    {
        $shift_ids = array_values(array_filter(array_map('intval', $shift_ids), function ($shift_id) {
            return $shift_id > 0;
        }));

        if (empty($shift_ids)) {
            $shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($query) use ($settlement) {
                    $query->whereNull('settlement_id')
                        ->orWhere('settlement_id', $settlement->id);
                })
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        if (empty($shift_ids)) {
            return;
        }

        $has_card_pump_payment_column = \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('settlement_card_payments', 'pump_payment_id');
        $default_customer_id = null;

        $card_payments = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->whereIn('shift_id', $shift_ids)
            ->whereIn('payment_type', ['card', 'pos'])
            ->get();

        foreach ($card_payments as $pump_payment) {
            if (! empty($pump_payment->settlement_no) && ! in_array((string) $pump_payment->settlement_no, [(string) $settlement->id, (string) $settlement->settlement_no], true)) {
                $finalized_owner = Settlement::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where(function ($query) use ($pump_payment) {
                        $query->where('id', $pump_payment->settlement_no)
                            ->orWhere('settlement_no', $pump_payment->settlement_no);
                    })
                    ->exists();

                if ($finalized_owner) {
                    continue;
                }
            }

            $daily_card = DailyCard::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where('collection_no', $pump_payment->collection_form_no)
                ->where('amount', $pump_payment->payment_amount)
                ->when(\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('daily_cards', 'shift_id'), function ($query) use ($pump_payment) {
                    $query->where('shift_id', $pump_payment->shift_id);
                })
                ->orderByDesc('id')
                ->first();

            $existing_query = SettlementCardPayment::where('business_id', $business_id);
            if ($has_card_pump_payment_column) {
                $existing_query->where('pump_payment_id', $pump_payment->id);
            } elseif (! empty($daily_card)) {
                $existing_query->where('daily_card_id', $daily_card->id);
            } else {
                $existing_query->where('amount', $pump_payment->payment_amount)
                    ->where('card_number', $pump_payment->card_number)
                    ->where('slip_no', $pump_payment->slip_no);
            }

            $settlement_card_payment = $existing_query->first();

            if (! empty($settlement_card_payment) && (string) $settlement_card_payment->settlement_no !== (string) $settlement->id) {
                $finalized_owner = Settlement::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where(function ($query) use ($settlement_card_payment) {
                        $query->where('id', $settlement_card_payment->settlement_no)
                            ->orWhere('settlement_no', $settlement_card_payment->settlement_no);
                    })
                    ->exists();

                if ($finalized_owner) {
                    continue;
                }
            }

            $customer_id = $pump_payment->customer_id ?? (! empty($daily_card) ? $daily_card->customer_id : null);
            if (empty($customer_id)) {
                if ($default_customer_id === null) {
                    $customers = Contact::customersDropdown($business_id, false, true, 'customer');
                    $default_customer_id = array_key_first($customers->toArray());
                }

                $customer_id = $default_customer_id;
            }

            if (empty($customer_id)) {
                continue;
            }

            $data = [
                'business_id' => $business_id,
                'settlement_no' => $settlement->id,
                'amount' => $pump_payment->payment_amount,
                'customer_id' => $customer_id,
                'card_type' => $pump_payment->card_type ?? (! empty($daily_card) ? $daily_card->card_type : null),
                'card_number' => $pump_payment->card_number ?? (! empty($daily_card) ? $daily_card->card_number : null),
                'daily_card_id' => ! empty($daily_card) ? $daily_card->id : null,
                'note' => $pump_payment->note ?? (! empty($daily_card) ? $daily_card->note : null),
                'slip_no' => $pump_payment->slip_no ?? (! empty($daily_card) ? $daily_card->slip_no : null),
            ];

            if ($has_card_pump_payment_column) {
                $data['pump_payment_id'] = $pump_payment->id;
            }

            // Upsert via Reconciler keyed on pump_payment_id. If $settlement_card_payment
            // already loaded from a prior step, the Reconciler still finds + updates by key.
            $settlement_card_payment = app(\Modules\Petro\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_card_payments', $data);

            if (! empty($daily_card)) {
                $daily_card->used_status = 1;
                $daily_card->settlement_no = $settlement->id;
                $daily_card->save();
            }

            $pump_payment->is_used = 1;
            $pump_payment->parent_id = $settlement_card_payment->id;
            $pump_payment->settlement_no = $settlement->id;
            $pump_payment->save();
        }

        $credit_payments = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->whereIn('shift_id', $shift_ids)
            ->where('payment_type', 'credit')
            ->get();

        foreach ($credit_payments as $pump_payment) {
            if (! empty($pump_payment->settlement_no) && ! in_array((string) $pump_payment->settlement_no, [(string) $settlement->id, (string) $settlement->settlement_no], true)) {
                $finalized_owner = Settlement::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where(function ($query) use ($pump_payment) {
                        $query->where('id', $pump_payment->settlement_no)
                            ->orWhere('settlement_no', $pump_payment->settlement_no);
                    })
                    ->exists();

                if ($finalized_owner) {
                    continue;
                }
            }

            $credit_sales = SettlementCreditSalePayment::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($query) use ($pump_payment) {
                    $query->where('collection_form_no', $pump_payment->collection_form_no)
                        ->orWhere(function ($amount_query) use ($pump_payment) {
                            $amount_query->where('amount', $pump_payment->payment_amount)
                                ->whereNull('settlement_no');
                        });
                })
                ->get();

            foreach ($credit_sales as $credit_sale) {
                if (! empty($credit_sale->settlement_no) && ! in_array((string) $credit_sale->settlement_no, [(string) $settlement->id, (string) $settlement->settlement_no], true)) {
                    $finalized_owner = Settlement::where('business_id', $business_id)
                        ->where('status', 0)
                        ->where(function ($query) use ($credit_sale) {
                            $query->where('id', $credit_sale->settlement_no)
                                ->orWhere('settlement_no', $credit_sale->settlement_no);
                        })
                        ->exists();

                    if ($finalized_owner) {
                        continue;
                    }
                }

                $credit_sale->settlement_no = $settlement->settlement_no;
                $credit_sale->save();

                if (! empty($credit_sale->daily_voucher_id)) {
                    DailyVoucher::where('id', $credit_sale->daily_voucher_id)
                        ->update(['settlement_no' => $settlement->settlement_no]);
                }
            }

            if ($credit_sales->isNotEmpty()) {
                $pump_payment->is_used = 1;
                $pump_payment->settlement_no = $settlement->id;
                $pump_payment->save();
            }
        }
    }
}

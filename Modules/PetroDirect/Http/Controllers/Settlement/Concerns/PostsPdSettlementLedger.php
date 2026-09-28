<?php

namespace Modules\PetroDirect\Http\Controllers\Settlement\Concerns;

use Modules\PetroDirect\Support\PetroDirectDebug;
use Modules\PetroDirect\Support\SchemaCapabilityCache;
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
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;
use Modules\HR\Entities\WorkShift;
use Modules\PetroDirect\Entities\CustomerPayment;
use Modules\PetroDirect\Entities\CustomerBillVatPrefix;
use Modules\PetroDirect\Entities\DailyCard;
use Modules\PetroDirect\Entities\DailyCollection;
use Modules\PetroDirect\Entities\DailyVoucher;
use Modules\PetroDirect\Entities\DayEnd;
use Modules\PetroDirect\Entities\FuelTank;
use Modules\PetroDirect\Entities\MeterSale;
use Modules\PetroDirect\Entities\OtherIncome;
use Modules\PetroDirect\Entities\OtherSale;
use Modules\PetroDirect\Entities\PetroShift;
use Modules\PetroDirect\Entities\PetroWhatsAppTemplate;
use Modules\PetroDirect\Entities\Pump;
use Modules\PetroDirect\Entities\PumperDayEntry;
use Modules\PetroDirect\Entities\PumpOperator;
use Modules\PetroDirect\Entities\PumpOperatorAssignment;
use Modules\PetroDirect\Entities\PumpOperatorCommission;
use Modules\PetroDirect\Entities\PumpOperatorPayment;
use Modules\PetroDirect\Entities\PumpOperatorOtherSale;
use Modules\PetroDirect\Entities\Settlement;
use Modules\PetroDirect\Entities\SettlementCardPayment;
use Modules\PetroDirect\Entities\SettlementCashDeposit;
use Modules\PetroDirect\Entities\SettlementCashPayment;
use Modules\PetroDirect\Entities\SettlementChequePayment;
use Modules\PetroDirect\Entities\SettlementCreditSalePayment;
use Modules\PetroDirect\Entities\SettlementEditHistory;
use Modules\PetroDirect\Entities\SettlementExcessPayment;
use Modules\PetroDirect\Entities\PumpOperatorMeterSale;
use Modules\PetroDirect\Entities\SettlementExpensePayment;
use Modules\PetroDirect\Entities\SettlementShortagePayment;
use Modules\PetroDirect\Entities\SettlementLoanPayment;
use Modules\PetroDirect\Entities\SettlementDrawingPayment;
use Modules\PetroDirect\Entities\SettlementCustomerLoan;
use Modules\PetroDirect\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\PetroDirect\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

/**
 * Everything that writes to transactions, account transactions and the ledger.
 *
 * MA-002: split out of PetroDirect's SettlementController, which was 10,590
 * lines in a single file.
 *
 * The grouping follows the one used for the PD settlement controllers, since
 * these files share most of their method names - but it was rebuilt against
 * THIS file, because the modules have genuinely diverged in content.
 *
 * Traits, not separate controllers: routes, action() targets and the $this->
 * calls between these methods all resolve exactly as before. Method bodies
 * are byte-identical to the original.
 *
 * Methods here: createTransaction, createSellTransactions, createCreditSellTransactions, mapSellPurchaseLines, createTansactionPayment, createAccountTransaction, createStockAccountTransactions, getDiscount
 */
trait PostsPdSettlementLedger
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

                } elseif ($is_other_sale === 'meter_sale') {

                    /*
                     * IS1958 #1: a meter sale is not an OtherSale.
                     *
                     * Looking it up by id here would fetch an unrelated
                     * OtherSale row that happened to share the id and pull the
                     * wrong store, so the decrement is left to fall back to the
                     * business default store below.
                     */
                    $otherSale = null;

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
}

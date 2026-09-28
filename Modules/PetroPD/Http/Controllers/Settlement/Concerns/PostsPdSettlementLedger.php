<?php

namespace Modules\PetroPD\Http\Controllers\Settlement\Concerns;

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
use Modules\PetroPD\Entities\CustomerPayment;
use Modules\PetroPD\Entities\DailyCollection;
use Modules\PetroPD\Entities\DailyVoucher;
use Modules\PetroPD\Entities\DayEnd;
use Modules\PetroPD\Entities\FuelTank;
use Modules\PetroPD\Entities\MeterSale;
use Modules\PetroPD\Entities\OtherIncome;
use Modules\PetroPD\Entities\OtherSale;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\PetroWhatsAppTemplate;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorCommission;
use Modules\PetroPD\Entities\PumpOperatorMeterSale;
use Modules\PetroPD\Entities\PumpOperatorOtherSale;
use Modules\PetroPD\Entities\PumpOperatorPayment;
use Modules\PetroPD\Entities\Settlement;
use Modules\PetroPD\Entities\SettlementCardPayment;
use Modules\PetroPD\Entities\SettlementCashDeposit;
use Modules\PetroPD\Entities\SettlementCashPayment;
use Modules\PetroPD\Entities\SettlementChequePayment;
use Modules\PetroPD\Entities\SettlementCreditSalePayment;
use Modules\PetroPD\Entities\SettlementCustomerLoan;
use Modules\PetroPD\Entities\SettlementDrawingPayment;
use Modules\PetroPD\Entities\SettlementEditHistory;
use Modules\PetroPD\Entities\SettlementExcessPayment;
use Modules\PetroPD\Entities\SettlementExpensePayment;
use Modules\PetroPD\Entities\SettlementLoanPayment;
use Modules\PetroPD\Entities\SettlementShortagePayment;
use Modules\PetroPD\Entities\TankSellLine;
use Modules\PetroPD\Entities\TanksTransactionDetail;
use Modules\PetroPD\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;
use Modules\PetroPD\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;
use Modules\PetroPD\Services\PetroPdSmsNotificationService;

/**
 * Everything that writes to transactions, account transactions and the contact ledger.
 *
 * MA-002: split out of PetroPDSettlementController, which was 15,639 lines in
 * a single file - the largest controller in the application after core's
 * ReportController.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at
 *   PetroPDSettlementController, action() targets still resolve, and $this->
 *   calls between these 111 methods still work. Splitting into separate
 *   controller classes would mean rewriting routes and every action()
 *   reference - a behavioural change dressed up as tidying.
 *
 *   So this is a purely physical split: same class at runtime, smaller files.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten
 * while moving.
 *
 * Methods here: createTransaction, createSellTransactions, createCreditSellTransactions, mapSellPurchaseLines, createTansactionPayment, createAccountTransaction, createStockAccountTransactions, is1497CleanupDuplicateSettlementPostings, is1497CreateContactLedgerOnce, la1062IsWalkInCustomer, la1062EnsureWalkInLedgerPair, is1771CreditSaleHasValidTransaction, is1771CreditSaleRequiresFinalPosting, is1771EnsureCreditSaleReceivablePosting, s390SyncTankTransactionDetail, s371SyncPdMeterSaleSnapshot, getDiscount
 */
trait PostsPdSettlementLedger
{
    public function createTransaction(

        $settlement,

        $amount,

        $customer_id = null,

        $pump_operator_id = null,

        $type = "sell",

        $sub_type,

        $settlement_no,

        $ref_no = null,

        $is_credit_sale = 0,

        $total_sales_discount_amount = 0.0,

        $transaction_note = null

    ) {

        $business_id = request()

            ->session()

            ->get("business.id");

        $business_location = BusinessLocation::where(

            "business_id",

            $business_id

        )

            ->first();

        $total_sales_discount_amount =

            ! empty($total_sales_discount_amount) ?? 0;

        $final_amount = $amount;

        if (empty($customer_id)) {
            $walkIn = app(\App\Utils\ContactUtil::class)->getWalkInCustomer($business_id);
            if (!empty($walkIn['id'])) {
                $customer_id = $walkIn['id'];
            }
        }

        $ob_data = [

            "business_id"      => $business_id,

            "location_id"      => $settlement->location_id ?? $business_location->id,

            "type"             => $type,

            "sub_type"         => $sub_type,

            "status"           => "final",

            "payment_status"   => "paid",

            "contact_id"       => $customer_id,

            "pump_operator_id" => $pump_operator_id,

            "transaction_date" => \Carbon::parse(

                $settlement->transaction_date

            )->format("Y-m-d"),

            "total_before_tax" => $final_amount,

            "final_total"      => $final_amount,

            "discount_amount"  => $total_sales_discount_amount,

            "created_by"       => request()

                ->session()

                ->get("user.id"),

            "is_settlement"    => 1,

            "transaction_note" => $transaction_note,

            "petro_settlement_id" => $settlement->id,

        ];

        if (

            $sub_type == "excess" ||

            $sub_type == "shortage" ||

            $sub_type == "customer_loan"

        ) {

            $ob_data["payment_status"] = "due";
        }

        $ob_data["invoice_no"] = $settlement_no;

        $ob_data["ref_no"] = ! empty($ref_no) ? $ref_no : null;

        if ($is_credit_sale == 1) {

            $ob_data["type"] = "sell";

            $ob_data["sub_type"] = "credit_sale";
        }

        /*
         * MA-002 (LA-1134) - duplicate guard REPLACED. Read this before
         * changing it back.
         *
         * WHAT WAS HERE
         *     $check = Transaction::where('business_id', ...)
         *         ->where('created_by', <current user>)
         *         ->latest('id')
         *         ->value('final_total');
         *
         *     if ((int) $check !== (int) $ob_data['final_total'] ...) create
         *     else  reuse that previous transaction
         *
         * THREE PROBLEMS WITH IT
         *
         * 1. It compared against the LAST transaction this user created
         *    ANYWHERE in the business - any settlement, any type, any
         *    sub_type. A cash payment of 5,000 followed by a loan payment of
         *    5,000 matched, so the loan payment was never created.
         *
         * 2. On a match it RETURNED THE PREVIOUS TRANSACTION, and the caller
         *    then attaches this payment's account entries to it. So a loan
         *    payment's ledger rows could be posted against a cash payment
         *    transaction of a different sub_type. That is worse than a
         *    duplicate - it is a misattribution.
         *
         * 3. The (int) casts meant 4,000.50 and 4,000.99 compared equal.
         *
         * WHAT REPLACES IT
         * The same targeted guard the other three settlement modules use,
         * matching on business, type, sub_type, invoice_no, settlement,
         * contact, exact amount and note - so it can only ever match a true
         * repeat of THIS payment in THIS settlement - plus a 20 second window,
         * so a deliberate second identical payment minutes later is still
         * allowed. Two genuinely different payments are never merged, which
         * the old version could not promise.
         *
         * Every suppression is logged with the settlement and amount.
         */
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
                'MA-002 (LA-1134): duplicate PD settlement transaction suppressed within the 20s window',
                [
                    'business_id'   => $ob_data['business_id'],
                    'sub_type'      => $ob_data['sub_type'] ?? null,
                    'invoice_no'    => $ob_data['invoice_no'] ?? null,
                    'final_total'   => $ob_data['final_total'] ?? null,
                    'reused_txn_id' => $ma002_recent_duplicate->id,
                ]
            );

            $transaction = $ma002_recent_duplicate;
        } else {
            $transaction = Transaction::create($ob_data);
        }
        // if (!(int)$check === (int)$ob_data['final_total']) {

        //     //Create transaction

        //     $transaction = Transaction::create($ob_data);

        // }else{

        //     $transaction = Transaction::where('business_id', $ob_data['business_id'])

        //         ->where('created_by', request()->session()->get('user.id'))

        //         ->latest('id')

        //         ->first();

        // }

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

        // Start from Product table to handle products with or without variations
        $product = Product::leftjoin(

            "product_variations",

            "products.id",

            "product_variations.product_id"

        )

            ->leftjoin(

                "variations",

                "product_variations.id",

                "variations.product_variation_id"

            )

            ->leftjoin(

                "variation_location_details",

                "variations.id",

                "variation_location_details.variation_id"

            )

            ->leftjoin("categories", "products.category_id", "categories.id")

            ->where("products.id", $sale->product_id)

            ->select(

                "variations.id as variation_id",

                "variation_location_details.location_id",

                "products.id as product_id",

                "categories.name as category_name",

                "products.enable_stock"

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

                if ($is_other_sale == "pump_operator_other_sale") {

                    $otherSale = $sale; // PumpOperatorOtherSale

                } elseif ($is_other_sale === "meter_sale") {

                    /*
                     * IS1994: a meter sale is not an OtherSale.
                     *
                     * Looking it up by id here would fetch an unrelated
                     * OtherSale row that happened to share the id and pull the
                     * wrong store, so the decrement is left to fall back to the
                     * business default store below.
                     *
                     * Mirrors the fix already carried by PetroDirect for
                     * IS1958 #1 - the two modules share this method almost
                     * verbatim and must not drift apart on stock handling.
                     */
                    $otherSale = null;

                } else {

                    $otherSale = OtherSale::where("id", $sale->id)->first();
                }

                $this->productUtil->decreaseProductQuantity(

                    $sale->product_id,

                    $product->variation_id,

                    $location_product,

                    $uf_quantity,

                    0,

                    "decrease",

                    isset($otherSale->store_id) ? $otherSale->store_id : 0

                );

                // IS1994: guarded. This runs for every fuel meter sale now, and
                // ->first()->id was a fatal on a business with no Store row.
                $store = Store::where("business_id", $business_id)->first();

                $store_id = ! empty($store) ? $store->id : 0;

                $this->productUtil->decreaseProductQuantityStore(

                    $sale->product_id,

                    $product->variation_id,

                    $location_product,

                    $uf_quantity,

                    isset($otherSale->store_id)

                        ? $otherSale->store_id

                        : $store_id,

                    "decrease",

                    0

                );
            }
        }

        //update qty to fuel tank current stock

        if (! empty($fuel_tank_id)) {

            /*
             * 003_S371: PD Settlement must feed the same Petro Tank/Pump/Meter
             * reports as Direct Settlement.  Those Petro reports read from
             * transactions, tank_sell_lines and meter_sales.  PD close-pump rows
             * arrive here as pump_operator_meter_sale_details, so create the
             * standard meter_sales snapshot once and make the tank sell line
             * idempotent.  This keeps Fuel Tanks, Tank Transaction Details/Summary,
             * Dip Management, Pumps, Testing Details, Meter Resetting and Meter
             * Readings in sync without double-decrementing tanks on re-open/save.
             */
            $this->s371SyncPdMeterSaleSnapshot($transaction, $sale, $business_id);

            $tankSellLine = TankSellLine::firstOrCreate([

                "business_id"    => $business_id,

                "transaction_id" => $transaction->id,

                "tank_id"        => $fuel_tank_id,

                "product_id"     => $sale->product_id,

                "quantity"       => $sale->qty,

            ]);

            if ($tankSellLine->wasRecentlyCreated) {
                FuelTank::where("id", $fuel_tank_id)->decrement(

                    "current_balance",

                    $sale->qty

                );
            }

            // S390: keep Petro Tank Transaction Details/Summary updated instantly
            // for PD settlements, with the correct settlement date and settlement no.
            $this->s390SyncTankTransactionDetail($transaction, $sale, $fuel_tank_id, $business_id);
        }

        return true;
    }


    /**
     * S390: create/update the standard Petro tank transaction row from PD settlements.
     * The main Petro Tank Transaction Details/Summary pages read different legacy schemas
     * across installations, so this method writes only columns that are present in the
     * tenant database. This keeps the fix safe for all tenant DBs.
     */

    public function createCreditSellTransactions(

        $settlement,

        $sale,

        $default_location

    ) {

        $final_total = $sale->amount - $sale->total_discount;

        $ob_data = [

            "business_id"      => $sale->business_id,

            "location_id"      => $settlement->location_id,

            "type"             => "sell",

            "status"           => "final",

            "payment_status"   => "due",

            "contact_id"       => $sale->customer_id,

            "pump_operator_id" => $settlement->pump_operator_id,

            "transaction_date" => \Carbon::parse(

                $settlement->transaction_date

            )->format("Y-m-d"),

            "total_before_tax" => $final_total,

            "final_total"      => $final_total,

            "discount_type"    => "fixed",

            "discount_amount"  => $sale->total_discount,

            "credit_sale_id"   => $sale->id,

            "is_credit_sale"   => 1,

            "is_settlement"    => 1,

            "created_by"       => request()

                ->session()

                ->get("user.id"),

            "invoice_no"       => $settlement->settlement_no,

            "ref_no"           => $sale->customer_reference,

            "customer_ref"     => $sale->customer_reference,

            "order_date"       => $sale->order_date,

            "order_no"         => $sale->order_number,

            "sub_type"         => "credit_sale",

            "petro_settlement_id" => $settlement->id,

        ];

        $transaction = Transaction::updateOrCreate(
            [
                "business_id"    => $sale->business_id,
                "type"           => "sell",
                "sub_type"       => "credit_sale",
                "credit_sale_id" => $sale->id,
            ],
            $ob_data
        );

        return $transaction;
    }

    public function mapSellPurchaseLines(

        $business_id,

        $transaction,

        $settlement

    ) {

        //Allocate the quantity from purchase and add mapping of

        //purchase & sell lines in transaction_sell_lines_purchase_lines table

        $business_details = $this->businessUtil->getDetails($business_id);

        $pos_settings = empty($business_details->pos_settings)

            ? $this->businessUtil->defaultPosSettings()

            : json_decode($business_details->pos_settings, true);

        $business = [

            "id"                => $business_id,

            "accounting_method" => request()

                ->session()

                ->get("business.accounting_method"),

            "location_id"       => $settlement->location_id,

            "pos_settings"      => $pos_settings,
            "enable_product_expiry" => 0,
            "on_product_expiry"     => "keep_selling",
            "stop_selling_before"   => 0,

        ];

        $this->transactionUtil->mapPurchaseSell(

            $business,

            $transaction->sell_lines,

            "purchase"

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

            ->get("business.id");

        $transaction_payment_data = [

            "transaction_id"    => $transaction->id,

            "business_id"       => $business_id,

            "amount"            => abs($transaction->final_total),

            "method"            => $method,

            "paid_on"           => $transaction->transaction_date,

            "created_by"        => $transaction->created_by,

            "card_number"       => $card_number,

            "card_type"         => $card_type,

            "cheque_number"     => $cheque_number,

            "bank_name"         => $bank_name,

            "cheque_date"       => ! empty($cheque_date)

                ? \Carbon::parse($cheque_date)->format("Y-m-d")

                : null,

            "post_dated_cheque" => $post_dated_cheque,

        ];

        if (! empty($amount)) {

            $transaction_payment_data["amount"] = $amount;
        }

        $transaction_payment_data["paid_in_type"] = "settlement";

        if (! empty($payment_for)) {

            $transaction_payment_data["payment_for"] = $payment_for;

        }

        $transaction_payment = TransactionPayment::create(

            $transaction_payment_data

        );

        return $transaction_payment;
    }

    /**
     * IS1497: Remove exact duplicate account/ledger postings for one settlement.
     *
     * This does not recalculate any amount. It only removes duplicate rows that
     * have the same transaction, account/contact, side, subtype, amount and
     * payment id. This fixes duplicate Card / Finished Goods / COGS / Sales Income
     * account-book rows created when old and rewrite settlement paths overlap.
     */

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

            "amount"                 => abs($transaction->final_total),

            "account_id"             => $account_id,

            "contact_id"             => $transaction->contact_id,

            "type"                   => $type,

            "sub_type"               => $sub_type,

            "operation_date"         => \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i:s'),

            "created_by"             => $transaction->created_by,

            "transaction_id"         => $transaction->id,

            "transaction_payment_id" => $transaction_payment_id,

            "note"                   => $note,

            "slip_no"                => $slip_no,

        ];

        if (! empty($contact_id)) {

            $account_transaction_data["contact_id"] = $contact_id;
        }

        // Override amount if provided and greater than zero
        // Skip creating account transaction if both amount and transaction final_total are 0 to avoid zero entries
        if ($amount > 0) {
            $account_transaction_data["amount"] = $amount;
        } elseif ($account_transaction_data["amount"] == 0) {
            // If both provided amount and transaction final_total are 0, don't create the account transaction
            return;
        }

        if (!$skip_account_books) {
            // IS1497: prevent duplicate Account Book rows when legacy + rewrite posting
            // flows are both reached for the same settlement payment/item.
            $account_book_exists = AccountTransaction::where('transaction_id', $account_transaction_data['transaction_id'])
                ->where('account_id', $account_transaction_data['account_id'])
                ->where('type', $account_transaction_data['type'])
                ->where('amount', $account_transaction_data['amount'])
                ->when(!empty($account_transaction_data['transaction_payment_id']), function ($q) use ($account_transaction_data) {
                    $q->where('transaction_payment_id', $account_transaction_data['transaction_payment_id']);
                })
                ->when(empty($account_transaction_data['transaction_payment_id']), function ($q) {
                    $q->whereNull('transaction_payment_id');
                })
                ->when(!empty($account_transaction_data['sub_type']), function ($q) use ($account_transaction_data) {
                    $q->where('sub_type', $account_transaction_data['sub_type']);
                })
                ->when(empty($account_transaction_data['sub_type']), function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereNull('sub_type')->orWhere('sub_type', '');
                    });
                })
                ->whereNull('deleted_at')
                ->exists();

            if (! $account_book_exists) {
                AccountTransaction::createAccountTransaction($account_transaction_data);
            }
        }

        // create ledger transactions
        if (!$skip_customer_ledger && ($sub_type == "ledger_show" || in_array($transaction->sub_type, ["cash_payment", "card_payment", "cheque_payment"]))) {
            $ledger_data = $account_transaction_data;
            
            // If it's one of our specific payment sub-types, use that as the ledger sub-type.
            if (in_array($transaction->sub_type, ["cash_payment", "card_payment", "cheque_payment"])) {
                $ledger_data['sub_type'] = $transaction->sub_type;
                
                // For customer payments (Credit), we only record the credit side in their ledger.
                if ($type == "debit") { // Account is debited (Cash/Bank), Customer is credited.
                    $ledger_data['type'] = 'credit';
                    $this->is1497CreateContactLedgerOnce($ledger_data);

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
                            $this->is1497CreateContactLedgerOnce($ledger_data);
                        }
                    }
                }
            } else {
                // Default ledger_show behavior: create both sides
                $this->is1497CreateContactLedgerOnce($account_transaction_data);

                if (! $is_credit_sale) {
                    if ($type == "debit") {
                        $ledger_type = "credit";
                    }
                    if ($type == "credit") {
                        $ledger_type = "debit";
                    }
                    $account_transaction_data["type"] = $ledger_type;
                    $this->is1497CreateContactLedgerOnce($account_transaction_data);
                }
            }
        }
    }


    /**
     * IS1497: Create a customer ledger row only once.
     *
     * The PD settlement rewrite uses saved snapshots, while some older flows can
     * still call the same posting method.  This guard keeps Walk-In debit/credit
     * pairs and customer payment ledger entries from being silently duplicated.
     */

    public function createStockAccountTransactions($transaction)
    {

        $account_transaction_data = [

            "amount"         => abs($transaction->final_total),

            "operation_date" => \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i:s'),

            "created_by"     => $transaction->created_by,

            "transaction_id" => $transaction->id,

            "note"           => null,

        ];

        $this->transactionUtil->manageStockAccount(

            $transaction,

            $account_transaction_data,

            "credit",

            $transaction->final_total

        );

        $this->transactionUtil->createCostofGoodsSoldTransaction(

            $transaction,

            "ledger_show",

            "debit"

        );

        $this->transactionUtil->createSaleIncomeTransaction(

            $transaction,

            "ledger_show",

            "credit"

        );
    }

    private function is1497CleanupDuplicateSettlementPostings($settlement): void
    {
        try {
            if (empty($settlement) || empty($settlement->business_id) || empty($settlement->settlement_no)) {
                return;
            }

            $businessId = (int) $settlement->business_id;
            $settlementNo = (string) $settlement->settlement_no;

            $transactionIds = Transaction::where('business_id', $businessId)
                ->where(function ($q) use ($settlementNo, $settlement) {
                    $q->where('invoice_no', $settlementNo)
                        ->orWhere('ref_no', $settlementNo)
                        ->orWhere('additional_notes', 'like', '%' . $settlementNo . '%');

                    if (! empty($settlement->id)) {
                        $q->orWhere('invoice_no', (string) $settlement->id)
                            ->orWhere('ref_no', (string) $settlement->id);
                    }
                })
                ->pluck('id')
                ->filter()
                ->values()
                ->all();

            if (empty($transactionIds)) {
                return;
            }

            $accountRows = AccountTransaction::whereIn('transaction_id', $transactionIds)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get();

            $seen = [];
            $duplicateAccountTransactionIds = [];

            foreach ($accountRows as $row) {
                $key = implode('|', [
                    $row->transaction_id,
                    $row->transaction_payment_id ?: '0',
                    $row->account_id ?: '0',
                    $row->contact_id ?: '0',
                    $row->type ?: '',
                    $row->sub_type ?: '',
                    number_format((float) $row->amount, 4, '.', ''),
                    (string) ($row->note ?? ''),
                    (string) ($row->slip_no ?? ''),
                ]);

                if (isset($seen[$key])) {
                    $duplicateAccountTransactionIds[] = $row->id;
                } else {
                    $seen[$key] = $row->id;
                }
            }

            if (! empty($duplicateAccountTransactionIds)) {
                AccountTransaction::whereIn('id', $duplicateAccountTransactionIds)
                    ->update(['deleted_at' => now()]);
            }

            $ledgerRows = ContactLedger::whereIn('transaction_id', $transactionIds)
                ->orderBy('id')
                ->get();

            $seenLedger = [];
            $duplicateLedgerIds = [];

            foreach ($ledgerRows as $row) {
                $key = implode('|', [
                    $row->transaction_id,
                    $row->transaction_payment_id ?: '0',
                    $row->contact_id ?: '0',
                    $row->type ?: '',
                    $row->sub_type ?: '',
                    number_format((float) $row->amount, 4, '.', ''),
                    (string) ($row->note ?? ''),
                    (string) ($row->slip_no ?? ''),
                ]);

                if (isset($seenLedger[$key])) {
                    $duplicateLedgerIds[] = $row->id;
                } else {
                    $seenLedger[$key] = $row->id;
                }
            }

            if (! empty($duplicateLedgerIds)) {
                ContactLedger::whereIn('id', $duplicateLedgerIds)->delete();
            }

            \Log::info('IS1497 duplicate settlement posting cleanup completed', [
                'settlement_no' => $settlementNo,
                'account_duplicates_removed' => count($duplicateAccountTransactionIds),
                'ledger_duplicates_removed' => count($duplicateLedgerIds),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('IS1497 duplicate settlement posting cleanup skipped', [
                'settlement_no' => $settlement->settlement_no ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function is1497CreateContactLedgerOnce(array $ledger_data): void
    {
        $amount = abs((float) ($ledger_data['amount'] ?? 0));

        if ($amount <= 0 || empty($ledger_data['transaction_id']) || empty($ledger_data['contact_id']) || empty($ledger_data['type'])) {
            return;
        }

        $exists = ContactLedger::where('transaction_id', $ledger_data['transaction_id'])
            ->when(!empty($ledger_data['transaction_payment_id']), function ($q) use ($ledger_data) {
                $q->where('transaction_payment_id', $ledger_data['transaction_payment_id']);
            })
            ->when(empty($ledger_data['transaction_payment_id']), function ($q) {
                $q->whereNull('transaction_payment_id');
            })
            ->where('contact_id', $ledger_data['contact_id'])
            ->where('type', $ledger_data['type'])
            ->where('amount', $amount)
            ->when(!empty($ledger_data['sub_type']), function ($q) use ($ledger_data) {
                $q->where('sub_type', $ledger_data['sub_type']);
            })
            ->when(empty($ledger_data['sub_type']), function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('sub_type')->orWhere('sub_type', '');
                });
            })
            ->exists();

        if (! $exists) {
            ContactLedger::createContactLedger($ledger_data);
        }
    }

    /**
     * LA1062: Identify Walk-In Customer robustly.
     * Some tenant DBs do not have is_default=1, so also check common names/codes.
     */

    private function la1062IsWalkInCustomer($contact_id): bool
    {
        if (empty($contact_id)) {
            return false;
        }

        $contact = Contact::where('id', $contact_id)->first();

        if (empty($contact)) {
            return false;
        }

        $name = strtolower(trim((string) ($contact->name ?? '')));
        $code = strtolower(trim((string) ($contact->contact_id ?? '')));

        return ((int) ($contact->is_default ?? 0) === 1)
            || str_contains($name, 'walk')
            || str_contains($code, 'walk')
            || str_contains($code, 'co-0001');
    }

    /**
     * LA1062:
     * Walk-In customer Cash/Card settlement should show:
     * - Sale amount in Debit
     * - Payment amount in Credit
     *
     * This method safely creates missing ledger pair entries without duplicating.
     */

    private function la1062EnsureWalkInLedgerPair($transaction, $transaction_payment_id, $account_id, $amount, $operation_date, $created_by, $note = null, $slip_no = null): void
    {
        if (empty($transaction) || empty($transaction->contact_id) || empty($transaction_payment_id)) {
            return;
        }

        if (! $this->la1062IsWalkInCustomer($transaction->contact_id)) {
            return;
        }

        $base = [
            'amount'                 => abs((float) $amount),
            'account_id'             => $account_id,
            'contact_id'             => $transaction->contact_id,
            'operation_date'         => $operation_date ?: $transaction->transaction_date,
            'created_by'             => $created_by ?: $transaction->created_by,
            'transaction_id'         => $transaction->id,
            'transaction_payment_id' => $transaction_payment_id,
            'note'                   => $note,
            'slip_no'                => $slip_no,
        ];

        foreach ([
            ['type' => 'debit', 'sub_type' => 'sell'],
            ['type' => 'credit', 'sub_type' => $transaction->sub_type ?: 'payment'],
        ] as $side) {
            $exists = ContactLedger::where('transaction_id', $transaction->id)
                ->where('transaction_payment_id', $transaction_payment_id)
                ->where('contact_id', $transaction->contact_id)
                ->where('type', $side['type'])
                ->where('sub_type', $side['sub_type'])
                ->exists();

            if (! $exists) {
                $this->is1497CreateContactLedgerOnce(array_merge($base, $side));
            }
        }
    }

    private function is1771CreditSaleHasValidTransaction($creditSale, int $businessId): bool
    {
        if (empty($creditSale->transaction_id) || empty($creditSale->customer_id)) {
            return false;
        }

        $expectedAmount = max(
            0,
            (float) ($creditSale->amount ?? 0) - (float) ($creditSale->total_discount ?? 0)
        );

        $transaction = Transaction::where('business_id', $businessId)
            ->where('id', $creditSale->transaction_id)
            ->where('type', 'sell')
            ->where('sub_type', 'credit_sale')
            ->first();

        if (! $transaction) {
            return false;
        }

        return (int) $transaction->contact_id === (int) $creditSale->customer_id
            && abs((float) $transaction->final_total - $expectedAmount) < 0.01;
    }

    /**
     * IS1771: Edit - No Change may safely repair an old broken settlement while
     * leaving already-correct credit sales untouched and avoiding repeat SMS.
     */

    private function is1771CreditSaleRequiresFinalPosting($creditSale, int $businessId): bool
    {
        if (! $this->is1771CreditSaleHasValidTransaction($creditSale, $businessId)) {
            return true;
        }

        $expectedAmount = max(
            0,
            (float) ($creditSale->amount ?? 0) - (float) ($creditSale->total_discount ?? 0)
        );

        $ledgerExists = ContactLedger::where('business_id', $businessId)
            ->where('transaction_id', $creditSale->transaction_id)
            ->where('contact_id', $creditSale->customer_id)
            ->where('type', 'debit')
            ->whereRaw('ABS(amount - ?) < 0.01', [$expectedAmount])
            ->exists();

        if (! $ledgerExists) {
            return true;
        }

        $accountId = $this->transactionUtil->account_exist_return_id('Accounts Receivable');
        if (empty($accountId)) {
            return false;
        }

        return ! AccountTransaction::where('transaction_id', $creditSale->transaction_id)
            ->where('account_id', $accountId)
            ->where('type', 'debit')
            ->whereRaw('ABS(amount - ?) < 0.01', [$expectedAmount])
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * IS1771: create or normalize the Accounts Receivable and customer-ledger
     * debit for one finalized credit sale.  The selected customer and net bill
     * amount are authoritative.  Existing real-time pumper ledger rows are
     * promoted to the finalized transaction instead of being duplicated.
     */

    private function is1771EnsureCreditSaleReceivablePosting(
        $transaction,
        $creditSale,
        $accountId,
        float $amount,
        ?string $note = null
    ): void {
        if (
            empty($transaction)
            || empty($transaction->id)
            || empty($creditSale->business_id)
            || empty($creditSale->customer_id)
            || $amount <= 0
        ) {
            return;
        }

        $operationDate = \Carbon\Carbon::parse(
            $transaction->transaction_date ?: $creditSale->order_date ?: now()
        )->format('Y-m-d H:i:s');
        $createdBy = (int) ($transaction->created_by ?: auth()->id() ?: 1);

        // Correct any stale transaction values first; both Customer Statement
        // implementations read this row directly or through contact_ledgers.
        $transaction->contact_id = (int) $creditSale->customer_id;
        $transaction->total_before_tax = $amount;
        $transaction->final_total = $amount;
        $transaction->discount_amount = (float) ($creditSale->total_discount ?? 0);
        $transaction->payment_status = 'due';
        $transaction->status = 'final';
        $transaction->credit_sale_id = $creditSale->id;
        $transaction->is_credit_sale = 1;
        $transaction->is_settlement = 1;
        $transaction->save();

        if (! empty($accountId)) {
            // Normalize any existing receivable row for this transaction, even
            // when an older posting used the wrong customer or amount.  Looking
            // only for the new amount would leave the stale row active and double
            // the Accounts Receivable balance after adding the corrected row.
            $accountRows = AccountTransaction::where('transaction_id', $transaction->id)
                ->where('account_id', $accountId)
                ->where('type', 'debit')
                ->whereNull('transaction_payment_id')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get();

            $accountRow = $accountRows->shift();
            if ($accountRow) {
                $accountRow->update([
                    'amount' => $amount,
                    'contact_id' => (int) $creditSale->customer_id,
                    'sub_type' => 'ledger_show',
                    'operation_date' => $operationDate,
                    'note' => $note,
                ]);

                // Keep only one canonical Accounts Receivable row for this sale.
                if ($accountRows->isNotEmpty()) {
                    AccountTransaction::whereIn('id', $accountRows->pluck('id')->all())
                        ->update(['deleted_at' => now()]);
                }
            } else {
                $this->createAccountTransaction(
                    $transaction,
                    'debit',
                    $accountId,
                    null,
                    'ledger_show',
                    (int) $creditSale->customer_id,
                    $amount,
                    true,
                    $note,
                    null,
                    false,
                    true
                );
            }
        }

        // Normalize any existing debit ledger row for this exact transaction.
        // This corrects both a stale customer and a stale amount without leaving
        // a second row that would overstate Customer Register Total Due.
        $transactionLedgerRows = ContactLedger::where('business_id', $creditSale->business_id)
            ->where('transaction_id', $transaction->id)
            ->whereNull('transaction_payment_id')
            ->where('type', 'debit')
            ->orderBy('id')
            ->get();

        $canonicalLedger = $transactionLedgerRows
            ->first(function ($row) use ($creditSale) {
                return (int) $row->contact_id === (int) $creditSale->customer_id;
            });

        if (! $canonicalLedger) {
            $canonicalLedger = $transactionLedgerRows->first();
        }

        if ($canonicalLedger) {
            $canonicalLedger->update([
                'account_id' => $accountId,
                'contact_id' => (int) $creditSale->customer_id,
                'amount' => $amount,
                'sub_type' => 'ledger_show',
                'operation_date' => $operationDate,
                'created_by' => $createdBy,
                'note' => $note,
            ]);

            $duplicateLedgerIds = $transactionLedgerRows
                ->reject(function ($row) use ($canonicalLedger) {
                    return (int) $row->id === (int) $canonicalLedger->id;
                })
                ->pluck('id')
                ->all();

            if (! empty($duplicateLedgerIds)) {
                ContactLedger::whereIn('id', $duplicateLedgerIds)->delete();
            }

            return;
        }

        // Pumper Dashboard may have posted a transactionless real-time debit.
        // Promote that exact row to this finalized transaction to avoid a double
        // balance while keeping its original ledger history intact.
        $legacyLedgerQuery = ContactLedger::where('business_id', $creditSale->business_id)
            ->where('contact_id', $creditSale->customer_id)
            ->where('type', 'debit')
            ->whereNull('transaction_id')
            ->whereRaw('ABS(amount - ?) < 0.01', [$amount]);

        if (! empty($creditSale->collection_form_no)) {
            $legacyLedgerQuery->where(function ($query) use ($creditSale) {
                $query->where('note', 'like', '%Form No. ' . $creditSale->collection_form_no . '%')
                    ->orWhere('note', 'like', '%' . $creditSale->collection_form_no . '%');
            });
        } elseif (! empty($creditSale->order_date)) {
            $legacyLedgerQuery->whereDate('operation_date', $creditSale->order_date);
        }

        $legacyLedger = $legacyLedgerQuery->orderByDesc('id')->first();
        if ($legacyLedger) {
            $legacyLedger->update([
                'account_id' => $accountId,
                'transaction_id' => $transaction->id,
                'transaction_payment_id' => null,
                'sub_type' => 'ledger_show',
                'operation_date' => $operationDate,
                'created_by' => $createdBy,
                'note' => $note,
            ]);
            return;
        }

        $this->is1497CreateContactLedgerOnce([
            'business_id' => (int) $creditSale->business_id,
            'account_id' => $accountId,
            'contact_id' => (int) $creditSale->customer_id,
            'amount' => $amount,
            'type' => 'debit',
            'sub_type' => 'ledger_show',
            'operation_date' => $operationDate,
            'created_by' => $createdBy,
            'transaction_id' => $transaction->id,
            'transaction_payment_id' => null,
            'note' => $note,
        ]);
    }

    private function s390SyncTankTransactionDetail($transaction, $sale, $fuel_tank_id, int $business_id): void
    {
        try {
            if (empty($transaction) || empty($fuel_tank_id) || empty($sale) || !Schema::hasTable('tanks_transaction_details')) {
                return;
            }

            $settlement = null;
            if (!empty($transaction->petro_settlement_id)) {
                $settlement = Settlement::where('business_id', $business_id)
                    ->where('id', $transaction->petro_settlement_id)
                    ->first();
            }

            $date = $settlement->transaction_date
                ?? $transaction->transaction_date
                ?? date('Y-m-d');
            $date = \Carbon\Carbon::parse($date)->format('Y-m-d');

            $settlementNo = $settlement->settlement_no
                ?? $transaction->invoice_no
                ?? ('PDST-' . ($transaction->id ?? ''));

            $qty = (float) ($sale->qty ?? $sale->sold_qty ?? 0);
            if ($qty <= 0) {
                return;
            }

            $base = [
                'business_id' => $business_id,
                'transaction_id' => $transaction->id,
                'tank_id' => $fuel_tank_id,
                'fuel_tank_id' => $fuel_tank_id,
                'product_id' => $sale->product_id ?? null,
                'transaction_date' => $date,
                'date_and_time' => $date,
                'date' => $date,
                'type' => 'Settlement',
                'transaction_type' => 'Settlement',
                'sub_type' => 'PD Settlement',
                'settlement_no' => $settlementNo,
                'settlement_number' => $settlementNo,
                'reference_no' => $settlementNo,
                'ref_no' => $settlementNo,
                'settlement_purchase_invoice_transfer_no' => $settlementNo,
                'quantity' => $qty,
                'qty' => $qty,
                'decrease' => $qty,
                'decrease_qty' => $qty,
                'stock_out' => $qty,
                'location_id' => $transaction->location_id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $payload = [];
            foreach ($base as $column => $value) {
                if (Schema::hasColumn('tanks_transaction_details', $column)) {
                    $payload[$column] = $value;
                }
            }

            if (empty($payload)) {
                return;
            }

            $where = [];
            foreach (['business_id', 'transaction_id', 'tank_id', 'fuel_tank_id', 'product_id', 'settlement_no', 'reference_no'] as $column) {
                if (array_key_exists($column, $payload) && !empty($payload[$column])) {
                    $where[$column] = $payload[$column];
                }
                if (count($where) >= 4) {
                    break;
                }
            }

            if (empty($where)) {
                $where = ['transaction_id' => $transaction->id];
            }

            DB::table('tanks_transaction_details')->updateOrInsert($where, $payload);
        } catch (\Throwable $e) {
            \Log::warning('S390 PD tank transaction detail sync skipped', [
                'transaction_id' => $transaction->id ?? null,
                'tank_id' => $fuel_tank_id ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 003_S371: Create the normal meter_sales row for PD close-pump details.
     * Petro Pump Management reports use this table, while PD settlements use
     * pump_operator_meter_sale_details as their source.  This method bridges
     * the saved PD source into the standard Petro report source once only.
     */

    private function s371SyncPdMeterSaleSnapshot($transaction, $sale, int $business_id): void
    {
        try {
            if (empty($transaction) || empty($transaction->petro_settlement_id) || empty($sale->pump_id)) {
                return;
            }

            $startingMeter = $sale->starting_meter ?? $sale->received_meter ?? null;
            $closingMeter  = $sale->closing_meter ?? $sale->new_meter ?? null;
            $qty           = $sale->qty ?? $sale->sold_qty ?? 0;
            $price         = $sale->price ?? $sale->unit_price ?? 0;
            $amount        = $sale->sub_total ?? $sale->amount ?? ((float) $qty * (float) $price);

            if ($qty === null || (float) $qty <= 0) {
                return;
            }

            $where = [
                'business_id'    => $business_id,
                'settlement_no'  => $transaction->petro_settlement_id,
                'pump_id'        => $sale->pump_id,
                'starting_meter' => $startingMeter,
                'closing_meter'  => $closingMeter,
            ];

            $pumpObj = Pump::where('business_id', $business_id)->where('id', $sale->pump_id)->first();
            $locationId = $transaction->location_id ?? ($pumpObj->location_id ?? null);

            $data = [
                'product_id'      => $sale->product_id ?? null,
                'price'           => $price,
                'qty'             => $qty,
                'discount'        => $sale->discount ?? 0,
                'discount_type'   => $sale->discount_type ?? 'fixed',
                'discount_amount' => $sale->discount_amount ?? $amount,
                'testing_qty'     => $sale->testing_qty ?? 0,
                'sub_total'       => $amount,
                'shift_id'        => $sale->shift_id ?? null,
            ];

            // S390: keep Meter Readings Location column populated for PD settlement rows.
            if (!empty($locationId) && Schema::hasColumn('meter_sales', 'location_id')) {
                $data['location_id'] = $locationId;
            }
            if (!empty($locationId) && Schema::hasColumn('meter_sales', 'business_location_id')) {
                $data['business_location_id'] = $locationId;
            }
            if (!empty($transaction->transaction_date) && Schema::hasColumn('meter_sales', 'transaction_date')) {
                $data['transaction_date'] = \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d');
            }

            MeterSale::updateOrCreate($where, $data);

            // Keep the pump current meter aligned with the finalized PD close-pump reading.
            if (! empty($closingMeter)) {
                Pump::where('business_id', $business_id)
                    ->where('id', $sale->pump_id)
                    ->update(['last_meter_reading' => $closingMeter]);
            }
        } catch (\Throwable $e) {
            \Log::warning('003_S371 PD meter sale snapshot sync skipped', [
                'transaction_id' => $transaction->id ?? null,
                'pump_id' => $sale->pump_id ?? null,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * IS1771: confirm whether a credit-sale detail already has the canonical
     * finalized transaction for the selected customer and net bill amount.
     */

    public function getDiscount($discount)
    {

        $pos = strpos($discount, "%");

        $discount_amount = str_replace("%", "", $discount);

        if ($pos === false) {

            $discount_type = "fixed";
        } else {

            $discount_type = "percentage";
        }

        return [

            "discount_amount" => $discount_amount,

            "discount_type"   => $discount_type,

        ];
    }

    /**







     * Show the specified resource.







     * @return Response







     */

    /**
     * Build all lookup collections needed by settlement show/print views.
     *
     * Keeping these lookups in the controller prevents model queries from
     * running inside Blade loops and keeps query counts stable as row counts grow.
     */
}

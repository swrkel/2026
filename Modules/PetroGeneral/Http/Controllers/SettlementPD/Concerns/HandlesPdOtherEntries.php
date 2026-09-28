<?php

namespace Modules\PetroGeneral\Http\Controllers\SettlementPD\Concerns;

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
use Modules\PetroGeneral\Entities\CustomerPayment;
use Modules\PetroGeneral\Entities\DailyCollection;
use Modules\PetroGeneral\Entities\DailyVoucher;
use Modules\PetroGeneral\Entities\DayEnd;
use Modules\PetroGeneral\Entities\FuelTank;
use Modules\PetroGeneral\Entities\MeterSale;
use Modules\PetroGeneral\Entities\OtherIncome;
use Modules\PetroGeneral\Entities\OtherSale;
use Modules\PetroGeneral\Entities\PetroShift;
use Modules\PetroGeneral\Entities\PetroWhatsAppTemplate;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\PumperDayEntry;
use Modules\PetroGeneral\Entities\PumpOperator;
use Modules\PetroGeneral\Entities\PumpOperatorAssignment;
use Modules\PetroGeneral\Entities\PumpOperatorCommission;
use Modules\PetroGeneral\Entities\PumpOperatorMeterSale;
use Modules\PetroGeneral\Entities\PumpOperatorOtherSale;
use Modules\PetroGeneral\Entities\Settlement;
use Modules\PetroGeneral\Entities\SettlementCardPayment;
use Modules\PetroGeneral\Entities\SettlementCashDeposit;
use Modules\PetroGeneral\Entities\SettlementCashPayment;
use Modules\PetroGeneral\Entities\SettlementChequePayment;
use Modules\PetroGeneral\Entities\SettlementCreditSalePayment;
use Modules\PetroGeneral\Entities\SettlementCustomerLoan;
use Modules\PetroGeneral\Entities\SettlementDrawingPayment;
use Modules\PetroGeneral\Entities\SettlementEditHistory;
use Modules\PetroGeneral\Entities\SettlementExcessPayment;
use Modules\PetroGeneral\Entities\SettlementExpensePayment;
use Modules\PetroGeneral\Entities\SettlementLoanPayment;
use Modules\PetroGeneral\Entities\SettlementShortagePayment;
use Modules\PetroGeneral\Entities\TankSellLine;
use Modules\PetroGeneral\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;
use Modules\PetroGeneral\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroGeneralPD\Services\PetroPdClosedShiftQuery;

/**
 * Other sales, other income, customer payments and card reconciliation.
 *
 * MA-002: split out of PetroGeneral's SettlementPDController, which was
 * 13,430 lines in a single file.
 *
 * The grouping mirrors the one used for Petro's SettlementPDController, since
 * the two share 83 of the same method names - but it was rebuilt against THIS
 * file rather than copied, because the two have diverged in content. Two
 * methods Petro has (canonicalClosedPumpMeterSales,
 * resolvePetroPdSettlementLocationId) do not exist here and are simply absent.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at
 *   SettlementPDController, action() targets still resolve, and the $this->
 *   calls between these methods still work. Separate controller classes would
 *   mean rewriting routes and every action() reference.
 *
 * Method bodies are byte-identical to the original.
 *
 * Methods here: saveOtherSale, deleteOtherSale, saveOtherIncome, deleteOtherIncome, saveCustomerPayment, deleteCustomerPayment, getPumpOperatorOtherSalesForFinalization, getSettlementPDOtherSaleTotal, getFilteredCreditSalePayments, reloadSettlementPayments, settlementPaymentReloadIdentity, createSettlementCardPaymentsFromPumpPayments, ensureSettlementCardAccounting, uniqueSettlementCardPaymentsForAccounting, getPaymentTabTotals
 */
trait HandlesPdOtherEntries
{
    public function saveOtherSale(Request $request)
    {

        try {

            $business_id = $request->session()->get("business.id");

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return [

                    "success" => false,

                    "msg"     => __("petrogeneral::lang.date_greater_than_day_end"),

                ];
            }

            $data = [

                "business_id"     => $business_id,

                "settlement_no"   => $settlement_exist->id,

                "store_id"        => $request->store_id,

                "product_id"      => $request->product_id,

                "price"           => $request->price,

                "qty"             => $request->qty,

                "balance_stock"   => $request->balance_stock,

                "discount"        => $request->discount,

                "discount_type"   => $request->discount_type,

                "discount_amount" => $request->discount_amount,

                "sub_total"       => $request->sub_total,

            ];

            $other_sale = OtherSale::create($data);

            Settlement::where("id", $settlement_exist->id)->update([

                "is_edit" => request()->is_edit,

            ]);

            $product = Product::find($request->product_id);
            $business = Business::find($business_id);
            $currency_precision = ! empty($business->currency_precision) ? $business->currency_precision : 2;
            $with_discount = (float) $request->sub_total - (float) $request->discount_amount;

            $row_html = '<tr>' .
                '<td>' . e(optional($product)->sku) . '</td>' .
                '<td>' . e(optional($product)->name) . '</td>' .
                '<td>' . number_format((float) $request->balance_stock, 4, '.', ',') . '</td>' .
                '<td>' . number_format((float) $request->price, $currency_precision) . '</td>' .
                '<td>' . number_format((float) $request->qty, 4, '.', ',') . '</td>' .
                '<td>' . e($request->discount_type) . '</td>' .
                '<td>' . number_format((float) $request->discount, $currency_precision) . '</td>' .
                '<td>' . number_format((float) $request->sub_total, $currency_precision) . '</td>' .
                '<td>' . number_format($with_discount, $currency_precision) . '</td>' .
                '<td><button class="btn btn-xs btn-danger delete_other_sale" data-href="/petro-general/settlement/delete-other-sale/' . $other_sale->id . '"><i class="fa fa-times"></i></button></td>' .
                '</tr>';

            $output = [

                "success"       => true,

                "other_sale_id" => $other_sale->id,

                "row_html"      => $row_html,

                "amount"        => $with_discount,

                "msg"           => __("petrogeneral::lang.success"),

            ];
        } catch (\Exception $e) {

            \Log::emergency(

                "File: " .

                    $e->getFile() .

                    "Line: " .

                    $e->getLine() .

                    "Message: " .

                    $e->getMessage()

            );

            $output = [

                "success" => false,

                "msg"     => __("messages.something_went_wrong"),

            ];
        }

        return $output;
    }

    public function deleteOtherSale($id)
    {

        try {

            $other_sale = OtherSale::where("id", $id)->first();

            Settlement::where("id", $other_sale->settlement_no)->update([

                "is_edit" => request()->is_edit,

            ]);

            $amount = $other_sale->sub_total - $other_sale->discount_amount;

            $other_sale->delete();

            $output = [

                "success" => true,

                "amount"  => $amount,

                "msg"     => __("petrogeneral::lang.success"),

            ];
        } catch (\Exception $e) {

            \Log::emergency(

                "File: " .

                    $e->getFile() .

                    "Line: " .

                    $e->getLine() .

                    "Message: " .

                    $e->getMessage()

            );

            $output = [

                "success" => false,

                "msg"     => __("messages.something_went_wrong"),

            ];
        }

        return $output;
    }

    /**







     * save other income data in db







     * @param product_id







     * @return Response







     */

    public function saveOtherIncome(Request $request)
    {

        try {

            $business_id = $request->session()->get("business.id");

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return [

                    "success" => false,

                    "msg"     => __("petrogeneral::lang.date_greater_than_day_end"),

                ];
            }

            $data = [

                "business_id"   => $business_id,

                "settlement_no" => $settlement_exist->id,

                "product_id"    => $request->product_id,

                "qty"           => $request->qty,

                "price"         => $request->price,

                "reason"        => $request->other_income_reason,

                "sub_total"     => $request->sub_total,

            ];

            $other_income = OtherIncome::create($data);

            Settlement::where("id", $settlement_exist->id)->update([

                "is_edit" => request()->is_edit,

            ]);

            $output = [

                "success"         => true,

                "other_income_id" => $other_income->id,

                "msg"             => __("petrogeneral::lang.success"),

            ];
        } catch (\Exception $e) {

            \Log::emergency(

                "File: " .

                    $e->getFile() .

                    "Line: " .

                    $e->getLine() .

                    "Message: " .

                    $e->getMessage()

            );

            $output = [

                "success" => false,

                "msg"     => __("messages.something_went_wrong"),

            ];
        }

        return $output;
    }

    public function deleteOtherIncome($id)
    {

        try {

            $other_income = OtherIncome::where("id", $id)->first();

            Settlement::where("id", $other_income->settlement_no)->update([

                "is_edit" => request()->is_edit,

            ]);

            $sub_total = $other_income->sub_total;

            $other_income->delete();

            $output = [

                "success"   => true,

                "sub_total" => $sub_total,

                "msg"       => __("petrogeneral::lang.success"),

            ];
        } catch (\Exception $e) {

            Log::emergency(

                "File: " .

                    $e->getFile() .

                    "Line: " .

                    $e->getLine() .

                    "Message: " .

                    $e->getMessage()

            );

            $output = [

                "success" => false,

                "msg"     => __("messages.something_went_wrong"),

            ];
        }

        return $output;
    }

    /**







     * save customer payment data in db







     * @param product_id







     * @return Response







     */

    public function saveCustomerPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get("business.id");

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return [

                    "success" => false,

                    "msg"     => __("petrogeneral::lang.date_greater_than_day_end"),

                ];
            }

            $data = [

                "business_id"       => $business_id,

                "settlement_no"     => $settlement_exist->id,

                "customer_id"       => $request->customer_id,

                "payment_method"    => $request->payment_method,

                "cheque_date"       => ! empty($request->cheque_date)

                    ? \Carbon::parse($request->cheque_date)->format("Y-m-d")

                    : null,

                "cheque_number"     => $request->cheque_number,

                "bank_name"         => $request->bank_name,

                "amount"            => $request->amount,

                "sub_total"         => $request->sub_total,

                "post_dated_cheque" => $request->post_dated_cheque,

            ];

            DB::beginTransaction();

            $customer_payment = CustomerPayment::create($data);

            Settlement::where("id", $settlement_exist->id)->update([

                "is_edit" => request()->is_edit,

            ]);

            // Customer-payment flow — identity is customer_payment_id, not pump_payment_id.
            // Reconciler keyed on 'customer_payment_id' provides idempotency (replaces the
            // prior manual cash-only guard and extends the same protection to card/cheque).
            if ($request->payment_method == "cash") {
                $cash_data = [
                    "amount"              => $request->amount,
                    "customer_id"         => $request->customer_id,
                    "customer_payment_id" => $customer_payment->id,
                ];
                $settlement_cash_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, (string) $settlement_exist->settlement_no, 'settlement_cash_payments', $cash_data, 'customer_payment_id');
            }

            if ($request->payment_method == "card") {
                $card_data = [
                    "amount"              => $request->amount,
                    "card_type"           => $request->card_type,
                    "card_number"         => $request->card_number,
                    "customer_id"         => $request->customer_id,
                    "customer_payment_id" => $customer_payment->id,
                ];
                $settlement_card_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, (string) $settlement_exist->settlement_no, 'settlement_card_payments', $card_data, 'customer_payment_id');
            }

            if ($request->payment_method == "cheque") {
                $cheque_data = [
                    "amount"              => $request->amount,
                    "bank_name"           => $request->bank_name,
                    "cheque_number"       => $request->cheque_number,
                    "cheque_date"         => ! empty($request->cheque_date)
                        ? \Carbon::parse($request->cheque_date)->format("Y-m-d")
                        : null,
                    "customer_id"         => $request->customer_id,
                    "customer_payment_id" => $customer_payment->id,
                ];
                $settlement_cheque_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, (string) $settlement_exist->settlement_no, 'settlement_cheque_payments', $cheque_data, 'customer_payment_id');
            }

            DB::commit();

            $output = [

                "success"             => true,

                "customer_payment_id" => $customer_payment->id,

                "msg"                 => __("petrogeneral::lang.success"),

            ];
        } catch (\Exception $e) {

            \Log::emergency(

                "File: " .

                    $e->getFile() .

                    "Line: " .

                    $e->getLine() .

                    "Message: " .

                    $e->getMessage()

            );

            $output = [

                "success" => false,

                "msg"     => __("messages.something_went_wrong"),

            ];
        }

        return $output;
    }

    public function deleteCustomerPayment($id)
    {

        try {

            $customer_payment = CustomerPayment::where("id", $id)->first();

            Settlement::where("id", $customer_payment->settlement_no)->update([

                "is_edit" => request()->is_edit,

            ]);

            $amount = $customer_payment->amount;

            $customer_payment->delete();

            SettlementCashPayment::where("customer_payment_id", $id)->delete();

            SettlementCardPayment::where("customer_payment_id", $id)->delete();

            SettlementChequePayment::where(

                "customer_payment_id",

                $id

            )->delete();

            $output = [

                "success" => true,

                "amount"  => $amount,

                "msg"     => __("petrogeneral::lang.success"),

            ];
        } catch (\Exception $e) {

            \Log::emergency(

                "File: " .

                    $e->getFile() .

                    "Line: " .

                    $e->getLine() .

                    "Message: " .

                    $e->getMessage()

            );

            $output = [

                "success" => false,

                "msg"     => __("messages.something_went_wrong"),

            ];
        }

        return $output;
    }

    private function getPumpOperatorOtherSalesForFinalization(int $business_id, array $shift_ids)
    {
        $shift_ids = array_values(array_filter(array_map('intval', $shift_ids)));
        if (empty($shift_ids)) {
            return collect();
        }

        return PumpOperatorOtherSale::join(
            "products",
            "products.id",
            "=",
            "pump_operator_other_sales.product_id"
        )
            ->leftJoin("variations", "products.id", "variations.product_id")
            ->leftJoin(
                "variation_location_details",
                "variations.id",
                "variation_location_details.variation_id"
            )
            ->where("pump_operator_other_sales.business_id", $business_id)
            ->whereIn("pump_operator_other_sales.shift_id", $shift_ids)
            ->join("pump_operator_assignments", function ($join) {
                $join
                    ->on(
                        "pump_operator_assignments.shift_id",
                        "=",
                        "pump_operator_other_sales.shift_id"
                    )
                    ->where("pump_operator_assignments.status", "close")
                    ->whereRaw(
                        'pump_operator_assignments.id = (
                            SELECT MAX(poa.id)
                            FROM pump_operator_assignments poa
                            WHERE poa.shift_id = pump_operator_other_sales.shift_id
                            AND poa.status = "close"
                        )'
                    );
            })
            ->select("pump_operator_other_sales.*")
            ->get();
    }

    private function getSettlementPDOtherSaleTotal(int $business_id, ?Settlement $settlement): float
    {
        if (empty($settlement)) {
            return 0.0;
        }

        $settlement_total = (float) OtherSale::where('business_id', $business_id)
            ->where('settlement_no', (string) $settlement->id)
            ->get()
            ->sum(function ($sale) {
                return max(0, (float) ($sale->sub_total ?? 0) - (float) ($sale->discount_amount ?? 0));
            });

        $shift_ids = $this->getSettlementPDShiftIds($settlement);
        $pumper_total = 0.0;
        if (! empty($shift_ids)) {
            $pumper_total = (float) PumpOperatorOtherSale::where('business_id', $business_id)
                ->whereIn('shift_id', $shift_ids)
                ->get()
                ->sum(function ($sale) {
                    $sub = (float) ($sale->sub_total ?? 0);
                    if (empty($sale->discount_type)) {
                        return $sub;
                    }
                    if ($sale->discount_type === 'percentage') {
                        return max(0, $sub - ($sub * (float) ($sale->discount ?? 0) / 100));
                    }

                    $off = (float) ($sale->discount ?? 0);
                    if ($off <= 0) {
                        $off = (float) ($sale->discount_amount ?? 0);
                    }

                    return max(0, $sub - $off);
                });
        }

        return $settlement_total + $pumper_total;
    }

    private function getFilteredCreditSalePayments($settlement, array $shiftIds): \Illuminate\Support\Collection
    {
        $query = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->settlement_no)
                    ->orWhere('settlement_no', $settlement->id);
            });

        if (! empty($shiftIds)) {
            $query->whereExists(function ($sub) use ($shiftIds) {
                $sub->select(DB::raw(1))
                    ->from('pump_operator_payments')
                    ->whereColumn('pump_operator_payments.pump_operator_id', 'settlement_credit_sale_payments.pump_operator_id')
                    ->whereRaw('pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci = settlement_credit_sale_payments.collection_form_no COLLATE utf8mb4_unicode_ci')
                    ->where('pump_operator_payments.payment_type', 'credit')
                    ->whereIn('pump_operator_payments.shift_id', $shiftIds);
            });
        }

        $results = $query->with('product')->get();

        // S 262 FIX (2026-05-31): Do not collapse multiple credit-sale rows that share
        // the same collection_form_no/customer. One customer can add several products/amounts
        // in the same credit-sale form, and the print popup must show every saved row.
        return $results->unique(function ($item) {
            if (! empty($item->id)) {
                return 'id-' . $item->id;
            }
            if (! empty($item->daily_voucher_id)) {
                return 'dv-' . $item->daily_voucher_id . '-' . ($item->product_id ?? '0') . '-' . ($item->amount ?? '0');
            }
            $order_number = ! empty($item->order_number) ? $item->order_number : '0';
            return 'manual-' . $order_number . '-' . ($item->customer_id ?? '0') . '-' . ($item->product_id ?? '0') . '-' . ($item->amount ?? '0') . '-' . ($item->order_date ?? '');
        })->values();
    }

    private function reloadSettlementPayments($active_settlement)
    {
        $settlementNo = $active_settlement->settlement_no;

        $map = [
            'cash_payments' => \Modules\PetroGeneral\Entities\SettlementCashPayment::class,
            'card_payments' => \Modules\PetroGeneral\Entities\SettlementCardPayment::class,
            'cheque_payments' => \Modules\PetroGeneral\Entities\SettlementChequePayment::class,
            'credit_sale_payments' => \Modules\PetroGeneral\Entities\SettlementCreditSalePayment::class,
            'expense_payments' => \Modules\PetroGeneral\Entities\SettlementExpensePayment::class,
            'excess_payments' => \Modules\PetroGeneral\Entities\SettlementExcessPayment::class,
            'shortage_payments' => \Modules\PetroGeneral\Entities\SettlementShortagePayment::class,
            'loan_payments' => \Modules\PetroGeneral\Entities\SettlementLoanPayment::class,
            'drawings_payments' => \Modules\PetroGeneral\Entities\SettlementDrawingPayment::class,
            'customer_loans' => \Modules\PetroGeneral\Entities\SettlementCustomerLoan::class,
        ];

        foreach ($map as $relation => $model) {

            $byId = $active_settlement->$relation ?? collect();

            $byNo = $model::where('settlement_no', $settlementNo)->get();

            $active_settlement->setRelation(
                $relation,
                $byId->merge($byNo)
                    ->unique(fn ($row) => $this->settlementPaymentReloadIdentity($relation, $row))
                    ->values()
            );
        }
    }

    private function settlementPaymentReloadIdentity(string $relation, $row): string
    {
        if ($relation === 'card_payments') {
            if (! empty($row->pump_payment_id)) {
                return 'pump_payment_id:' . $row->pump_payment_id;
            }
            if (! empty($row->daily_card_id)) {
                return 'daily_card_id:' . $row->daily_card_id;
            }
            if (! empty($row->customer_payment_id)) {
                return 'customer_payment_id:' . $row->customer_payment_id;
            }
        }

        return 'id:' . $row->id;
    }

    /**
     * Load and return the settlement_credit_sale_payments collection for a settlement,
     * filtered to only include rows that belong to one of the given shift IDs
     * (matched via pump_operator_payments.shift_id ↔ collection_form_no).
     *
     * Used by both print() and the print-preview rendered by store() so that the
     * popup never shows credit sales from shifts not belonging to this settlement.
     *
     * @param  \Modules\PetroGeneral\Entities\Settlement  $settlement
     * @param  int[]  $shiftIds   IDs from settlement.work_shift (already cast to int)
     * @return \Illuminate\Support\Collection
     */

    private function createSettlementCardPaymentsFromPumpPayments($settlement, int $business_id, array $shift_ids = []): void
    {
        if (empty($settlement) || empty($settlement->pump_operator_id)) {
            return;
        }

        if (empty($shift_ids)) {
            $workShift = is_array($settlement->work_shift)
                ? $settlement->work_shift
                : json_decode($settlement->work_shift, true);

            if (! is_array($workShift)) {
                $workShift = array_filter(explode(',', (string) $settlement->work_shift));
            }

            $shift_ids = array_filter(array_map('intval', (array) $workShift));
        }

        if (empty($shift_ids)) {
            $shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where('settlement_id', $settlement->id)
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        if (empty($shift_ids)) {
            return;
        }

        $walkin_customer = Contact::where('name', 'Walk-In Customer')
            ->where('business_id', $business_id)
            ->first();

        $pumpPayments = \Modules\PetroGeneral\Entities\PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->where('payment_type', 'card')
            ->whereIn('shift_id', $shift_ids)
            ->get();

        foreach ($pumpPayments as $pumpPayment) {
            $existing = SettlementCardPayment::where('business_id', $business_id)
                ->where(function ($query) use ($settlement) {
                    $query->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no);
                })
                ->where('pump_payment_id', $pumpPayment->id)
                ->first();

            if ($existing) {
                continue;
            }

            $dailyCard = \Modules\PetroGeneral\Entities\DailyCard::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where('collection_no', $pumpPayment->collection_form_no)
                ->where('amount', $pumpPayment->payment_amount)
                ->orderByDesc('id')
                ->first();

            $data = [
                'settlement_no'       => $settlement->id,
                'business_id'         => $business_id,
                'customer_id'         => $pumpPayment->customer_id ?? optional($dailyCard)->customer_id ?? optional($walkin_customer)->id,
                'card_type'           => $pumpPayment->card_type ?? optional($dailyCard)->card_type,
                'card_number'         => $pumpPayment->card_number ?? optional($dailyCard)->card_number,
                'amount'              => $pumpPayment->payment_amount,
                'customer_payment_id' => $pumpPayment->id,
                'note'                => $pumpPayment->note,
                'slip_no'             => $pumpPayment->slip_no ?? optional($dailyCard)->slip_no,
            ];

            if (Schema::hasColumn('settlement_card_payments', 'daily_card_id')) {
                $data['daily_card_id'] = optional($dailyCard)->id;
            }
            if (Schema::hasColumn('settlement_card_payments', 'pump_payment_id')) {
                $data['pump_payment_id'] = $pumpPayment->id;
            }

            $settlementCardPayment = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_card_payments', $data);

            $pumpPayment->is_used = 1;
            $pumpPayment->parent_id = $settlementCardPayment->id;
            $pumpPayment->settlement_no = $settlement->id;
            $pumpPayment->save();

            if ($dailyCard) {
                $dailyCard->settlement_no = $settlement->settlement_no;
                $dailyCard->save();
            }
        }

        $cardPayments = SettlementCardPayment::where('business_id', $business_id)
            ->where(function ($query) use ($settlement) {
                $query->where('settlement_no', $settlement->id)
                    ->orWhere('settlement_no', $settlement->settlement_no);
            })
            ->get();

        $settlement->setRelation('card_payments', $cardPayments);
    }

    private function ensureSettlementCardAccounting($settlement, int $business_id): void
    {
        if (empty($settlement) || (int) $settlement->status !== 0) {
            return;
        }

        $settlement_no = $settlement->settlement_no;
        $cardPayments = $settlement->relationLoaded('card_payments')
            ? $settlement->card_payments
            : SettlementCardPayment::where('business_id', $business_id)
                ->where(function ($query) use ($settlement) {
                    $query->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no);
                })
                ->get();

        $cardPayments = $this->uniqueSettlementCardPaymentsForAccounting($cardPayments);

        if ($cardPayments->isEmpty()) {
            return;
        }

        $location_id = $settlement->location_id ?: optional(BusinessLocation::where('business_id', $business_id)->first())->id;
        $created_by = request()->session()->get('user.id') ?: $settlement->created_by ?: auth()->id();

        foreach ($cardPayments as $card_payment) {
            if ((float) $card_payment->amount <= 0) {
                continue;
            }

            $card_account_id = ! empty($card_payment->card_type)
                ? $card_payment->card_type
                : $this->transactionUtil->account_exist_return_id("Cards (Credit Debit) Account");

            if (empty($card_account_id)) {
                Log::warning('Settlement PD: unable to create card accounting without card account', [
                    'settlement_id' => $settlement->id,
                    'card_payment_id' => $card_payment->id,
                ]);
                continue;
            }

            $operation_date = \Carbon::parse($settlement->transaction_date)->format('Y-m-d');
            $card_ref_no = 'PD Card Payment #' . $card_payment->id;

            $existing_card_transaction_ids = Transaction::where('business_id', $business_id)
                ->where('invoice_no', $settlement_no)
                ->where('type', 'settlement')
                ->where('sub_type', 'card_payment')
                ->where(function ($query) use ($card_ref_no, $card_payment) {
                    $query->where('ref_no', $card_ref_no)
                        ->orWhere('final_total', $card_payment->amount);
                })
                ->pluck('id');

            $existing_account_transaction = AccountTransaction::where('business_id', $business_id)
                ->where('account_id', $card_account_id)
                ->where('type', 'debit')
                ->where('amount', $card_payment->amount)
                ->whereDate('operation_date', $operation_date)
                ->where(function ($query) use ($settlement_no, $card_payment, $existing_card_transaction_ids) {
                    $query->whereRaw('note LIKE ?', ['%Settlement No: ' . $settlement_no . '%']);

                    if (! empty($card_payment->slip_no)) {
                        $query->orWhereRaw('note LIKE ?', ['%Slip No: ' . $card_payment->slip_no . '%']);
                    }

                    if ($existing_card_transaction_ids->isNotEmpty()) {
                        $query->orWhereIn('transaction_id', $existing_card_transaction_ids);
                    }
                })
                ->first();

            if ($existing_account_transaction) {
                continue;
            }

            $card_transaction = Transaction::where('business_id', $business_id)
                ->where('invoice_no', $settlement_no)
                ->where('type', 'settlement')
                ->where('sub_type', 'card_payment')
                ->where('ref_no', $card_ref_no)
                ->first();

            if (! $card_transaction) {
                $card_transaction = Transaction::create([
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'type' => 'settlement',
                    'sub_type' => 'card_payment',
                    'status' => 'final',
                    'payment_status' => 'paid',
                    'contact_id' => $card_payment->customer_id,
                    'pump_operator_id' => $settlement->pump_operator_id,
                    'transaction_date' => $operation_date,
                    'total_before_tax' => $card_payment->amount,
                    'final_total' => $card_payment->amount,
                    'discount_amount' => 0,
                    'created_by' => $created_by,
                    'is_settlement' => 1,
                    'invoice_no' => $settlement_no,
                    'ref_no' => $card_ref_no,
                    'petro_settlement_id' => $settlement->id,
                ]);
            }

            $transaction_payment = null;
            if (! empty($card_payment->customer_payment_id)) {
                $transaction_payment = TransactionPayment::where('business_id', $business_id)
                    ->where('id', $card_payment->customer_payment_id)
                    ->where('transaction_id', $card_transaction->id)
                    ->first();
            }

            if (! $transaction_payment) {
                $transaction_payment = $this->createTansactionPayment(
                    $card_transaction,
                    'card',
                    $card_payment->amount,
                    $card_payment->card_number,
                    $card_payment->card_type,
                    null,
                    null,
                    null,
                    0,
                    $card_transaction->contact_id
                );

                SettlementCardPayment::where('id', $card_payment->id)->update([
                    'customer_payment_id' => $transaction_payment->id,
                ]);
            }

            $card_note = $card_payment->note;
            if (empty($card_note)) {
                $note_parts = ['Settlement No: ' . $settlement_no, 'Card Payment'];

                if (! empty($card_payment->customer_id)) {
                    $customer = Contact::find($card_payment->customer_id);
                    if ($customer) {
                        $note_parts[] = 'Customer: ' . $customer->name;
                    }
                }

                if (! empty($card_payment->slip_no)) {
                    $note_parts[] = 'Slip No: ' . $card_payment->slip_no;
                }

                $card_note = implode(' | ', $note_parts);
            }

            $this->createAccountTransaction(
                $card_transaction,
                'debit',
                $card_account_id,
                $transaction_payment->id,
                'card_payment',
                $card_payment->customer_id,
                $card_payment->amount,
                false,
                $card_note,
                $card_payment->slip_no
            );
        }
    }

    private function uniqueSettlementCardPaymentsForAccounting($cardPayments)
    {
        return collect($cardPayments)
            ->unique(function ($cardPayment) {
                if (! empty($cardPayment->pump_payment_id)) {
                    return 'pump_payment_id:' . $cardPayment->pump_payment_id;
                }
                if (! empty($cardPayment->daily_card_id)) {
                    return 'daily_card_id:' . $cardPayment->daily_card_id;
                }
                if (! empty($cardPayment->customer_payment_id)) {
                    return 'customer_payment_id:' . $cardPayment->customer_payment_id;
                }

                return 'id:' . $cardPayment->id;
            })
            ->values();
    }

    public function getPaymentTabTotals(Request $request)
    {
        $business_id = auth()->user()->business_id;
        $settlement_no = $request->input('settlement_no');

        if (empty($settlement_no)) {
            return response()->json([
                'success' => false,
                'message' => 'Settlement number is required'
            ]);
        }

        try {
            // Find the settlement by settlement_no
            $settlement = Settlement::where('business_id', $business_id)
                ->where('settlement_no', $settlement_no)
                ->first();

            if (!$settlement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Settlement not found'
                ]);
            }

            // Calculate meter sale total from the saved settlement rows.
            $payment_meter_sale_total = $this->getSettlementPDMeterSaleTotal((int) $business_id, $settlement);

            $payment_other_sale_total = $this->getSettlementPDOtherSaleTotal((int) $business_id, $settlement);

            // Calculate other income total
            $payment_other_income_total = !empty($settlement->other_incomes)
                ? $settlement->other_incomes->sum("sub_total")
                : 0.0;

            // Calculate customer payment total
            $payment_customer_payment_total = !empty($settlement->customer_payments)
                ? $settlement->customer_payments->sum("sub_total")
                : 0.0;

            return response()->json([
                'success' => true,
                'meter_sale_total' => (float) $payment_meter_sale_total,
                'other_sale_total' => (float) $payment_other_sale_total,
                'other_income_total' => (float) $payment_other_income_total,
                'customer_payment_total' => (float) $payment_customer_payment_total
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting payment tab totals:', [
                'settlement_no' => $settlement_no,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment totals'
            ]);
        }
    }
}

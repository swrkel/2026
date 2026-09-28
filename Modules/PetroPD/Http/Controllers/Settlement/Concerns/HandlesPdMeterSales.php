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
 * Meter sale entry, display and totals.
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
 * Methods here: meter_sales, editMeterSale, updateMeterSale, saveMeterSale, deleteMeterSale, repairMeterSalePdSettlementNo, onlyClosedPumpMeterSales, getSettlementPdDisplayMeterSales, hydrateSettlementPdMeterSalesForDisplay, getPdMeterSalesForFinalization, getRegularMeterSalesForFinalization, meterSaleLineKey, getMeterSaleForm, updateSettlementMeterSale, updatePumpOperatorMeterSale, getMeterSaleTotalByShift, getSettlementPDMeterSaleTotal
 */
trait HandlesPdMeterSales
{
    public function meter_sales()
    {

        $business_id = request()

            ->session()

            ->get("user.business_id");

        if (request()->ajax()) {

            try {

                $query = TankSellLine::leftjoin(

                    "transactions",

                    "transactions.id",

                    "tank_sell_lines.transaction_id"

                )

                    ->leftjoin(

                        "fuel_tanks",

                        "fuel_tanks.id",

                        "tank_sell_lines.tank_id"

                    )

                    ->leftjoin(

                        "products",

                        "products.id",

                        "tank_sell_lines.product_id"

                    )

                    ->leftjoin(

                        "settlements",

                        "settlements.id",

                        "transactions.invoice_no"

                    ) // Add this join

                    ->where("settlements.business_id", $business_id)

                    ->select([

                        "products.name as product_name",

                        "fuel_tanks.fuel_tank_number",

                        "tank_sell_lines.*",

                        "settlements.transaction_date",

                        "settlements.settlement_no",

                    ])

                    ->orderBy("settlements.transaction_date", "DESC");

                if (! empty(request()->settlement_no)) {

                    $query->where(

                        "settlements.settlement_no",

                        request()->settlement_no

                    );
                }

                if (

                    ! empty(request()->start_date) &&

                    ! empty(request()->end_date)

                ) {

                    $query->whereDate(

                        "settlements.transaction_date",

                        ">=",

                        request()->start_date

                    );

                    $query->whereDate(

                        "settlements.transaction_date",

                        "<=",

                        request()->end_date

                    );
                }

                $settlements = Datatables::of($query)

                    ->addColumn(

                        "action",

                        function ($row) {

                            $html =

                                '<button data-href="' .

                                action(

                                    "\Modules\PetroPD\Http\Controllers\PetroPDSettlementController@editMeterSale",

                                    [$row->id]

                                ) .

                                '" data-container=".fuel_tank_modal" class="btn btn-primary btn-xs btn-modal edit_reference_button"><i class="fa fa-pencil-square-o"></i>' .

                                trans("messages.edit") .

                                "</button>";

                            return $html;
                        }

                    )

                    ->editColumn(

                        "created_at",

                        function ($row) { return $this->transactionUtil->format_date($row->created_at, true); }

                    )

                    ->editColumn(

                        "transaction_date",

                        function ($row) { return $this->transactionUtil->format_date($row->transaction_date); }

                    )

                    ->removeColumn("id");

                return $settlements

                    ->rawColumns(["action"])

                    ->make(true);
            } catch (\Exception $e) {

                dd($e);
            }
        }
    }

    public function editMeterSale($id)
    {

        $meter_sale = TankSellLine::findOrFail($id);

        $transaction = Transaction::findOrFail($meter_sale->transaction_id);

        return view("petropd::edit_settlement_date.edit")->with(

            compact("meter_sale", "transaction")

        );
    }

    public function updateMeterSale($id, Request $request)
    {

        try {

            $business_id = $request->session()->get("business.id");

            $data = [

                "created_at" => $request->created_at,

            ];

            TankSellLine::where("id", $id)->update($data);

            $meter_sale = TankSellLine::findOrFail($id);

            $transaction = Transaction::findOrFail($meter_sale->transaction_id);

            $transaction->created_at = $request->created_at;

            $transaction->save();

            $output = [

                "success" => 1,

                "msg"     => __("lang_v1.success"),

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

                "success" => 0,

                "msg"     => __("messages.something_went_wrong"),

            ];
        }

        return redirect()

            ->back()

            ->with("status", $output);
    }

    /**







     * Display a listing of the resource.







     * @return Response







     */

    public function saveMeterSale(Request $request)
    {

        try {

            $business_id = $request->session()->get("business.id");

            $business_locations = BusinessLocation::forDropdown($business_id);

            $default_location = current(

                array_keys($business_locations->toArray())

            );

            DB::beginTransaction();

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return [

                    "success" => false,

                    "msg"     => __("petropd::lang.date_greater_than_day_end"),

                ];
            }

            if (empty($settlement_exist) || empty($settlement_exist->id)) {
                DB::rollBack();

                return [
                    "success" => false,
                    "msg"     => __("messages.something_went_wrong"),
                ];
            }

            $pump = Pump::where("id", $request->pump_id)->first();

            $tank_id = $pump->fuel_tank_id ?? '';

            $data = [

                "business_id"     => $business_id,

                "settlement_no"   => $settlement_exist->id,

                "product_id"      => $request->product_id,

                "pump_id"         => $request->pump_id,

                "starting_meter"  => $request->starting_meter,

                "closing_meter"   =>

                $pump->bulk_sale_meter == 0 ? $request->closing_meter : "",

                "price"           => $request->price,

                "qty"             => $request->qty,

                "discount"        => $request->discount,

                "discount_type"   => $request->discount_type,

                "discount_amount" => $request->discount_amount,

                "testing_qty"     => $request->testing_qty,

                "sub_total"       => $request->sub_total,

                "shift_id"        => $request->shift_id,

            ];

            $meter_sale_sync = MeterSale::create($data);

            $operator_meter_sale_data = [
                'business_id'        => $business_id,
                "settlement_no"   => $settlement_exist->settlement_no,
                'date_time'          => now(),
                'pump_operator_id'   => $request->pump_operator_id,
                'amount'             => $request->sub_total,
                'deposited'          => 0,
                'balance'            => $request->sub_total,
                'collection_form_no' => null,
                'shift_id'           => $request->shift_id,
                "discount"        => $request->discount,

                "discount_type"   => $request->discount_type,

                "discount_amount" => $request->discount_amount,

                "testing_qty"     => $request->testing_qty,

            ];

            if (Schema::hasColumn('pump_operator_meter_sales', 'source')) {
                $operator_meter_sale_data['source'] = 'closing';
            }

            /*
             * S269 permanent fix:
             * A meter sale entered from the Pumper Dashboard already creates records in
             * pump_operator_meter_sales and pump_operator_meter_sale_details.
             * When the same closed shift is pulled into Petro PD Settlement, this method
             * must NOT create another identical operator meter sale row.
             *
             * The physical meter sale row in meter_sales is still created above for the
             * settlement, but the operator-meter-sale master/detail rows are reused when
             * an identical row already exists.
             */
            $existingMeterSaleDetail = PumpOperatorMeterSaleDetail::where('business_id', $business_id)
                ->where('pump_operator_id', $request->pump_operator_id)
                ->where('pump_id', $request->pump_id)
                ->where('received_meter', $request->starting_meter)
                ->where('new_meter', $request->closing_meter)
                ->where('sold_qty', $request->qty)
                ->where('amount', $request->sub_total)
                ->orderByDesc('id')
                ->first();

            if (! empty($existingMeterSaleDetail) && ! empty($existingMeterSaleDetail->sale_id)) {
                $meterSale = PumpOperatorMeterSale::where('business_id', $business_id)
                    ->where('id', $existingMeterSaleDetail->sale_id)
                    ->first();

                if (! empty($meterSale)) {
                    $meterSale->settlement_no = $settlement_exist->settlement_no;
                    $meterSale->shift_id = $request->shift_id ?: $meterSale->shift_id;
                    $meterSale->amount = $request->sub_total;
                    $meterSale->balance = $request->sub_total - (float) ($meterSale->deposited ?? 0);

                    if (Schema::hasColumn('pump_operator_meter_sales', 'source') && empty($meterSale->source)) {
                        $meterSale->source = 'closing';
                    }

                    $meterSale->save();
                }
            }

            if (empty($meterSale)) {
                $meterSale = PumpOperatorMeterSale::create($operator_meter_sale_data);

                PumpOperatorMeterSaleDetail::create([
                    'sale_id'          => $meterSale->id,
                    'business_id'      => $business_id,
                    'pump_operator_id' => $request->pump_operator_id,
                    'pump_id'          => $request->pump_id,
                    'received_meter'   => $request->starting_meter,
                    'new_meter'        => $request->closing_meter,
                    'sold_qty'         => $request->qty,
                    'unit_price'       => $request->qty > 0
                        ? $request->sub_total / $request->qty
                        : 0,
                    'amount'           => $request->sub_total,
                ]);
            }


            if (! empty($request->is_from_pumper)) {

                logger($request->pumper_entry_id);

                logger($request->assignment_id);

                PumperDayEntry::where("id", $request->pumper_entry_id)

                    ->update([

                        "settlement_no"        => $settlement_exist->settlement_no,

                        "settlement_added_by"  => auth()->user()->id,

                        "closed_in_settlement" => 0,

                    ]);

                PumpOperatorAssignment::where(

                    "id",

                    $request->assignment_id

                )->update(["closed_in_settlement" => 0]);
            }

            Settlement::where("id", $settlement_exist->id)->update([

                "is_edit" => request()->is_edit,

            ]);

            // IS1759-11/12: When a meter sale is added, link the credit
            // bills for the same immutable PetroPD shift. Never copy the
            // PetroPD shift ID into daily_vouchers.shift_id because that
            // column references the legacy petro_daily_shifts table.
            if (! empty($request->shift_id)) {
                $shift_ids_for_filter = [(int) $request->shift_id];
                $has_credit_pump_payment_column = Schema::hasColumn(
                    'settlement_credit_sale_payments',
                    'pump_payment_id'
                );
                $has_credit_shift_column = Schema::hasColumn(
                    'settlement_credit_sale_payments',
                    'shift_id'
                );

                $credit_master_payments = PumpOperatorPayment::where(
                    'business_id',
                    $settlement_exist->business_id
                )
                    ->where('pump_operator_id', $settlement_exist->pump_operator_id)
                    ->whereIn('payment_type', ['credit', 'multiple_credit'])
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->get(['id', 'collection_form_no']);

                $credit_payment_ids = $credit_master_payments->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
                $credit_collection_form_nos = $credit_master_payments->pluck('collection_form_no')
                    ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
                    ->map(fn ($value) => (string) $value)
                    ->unique()
                    ->values()
                    ->toArray();

                $credit_scope = SettlementCreditSalePayment::where(
                    'business_id',
                    $settlement_exist->business_id
                )
                    ->where('pump_operator_id', $settlement_exist->pump_operator_id)
                    ->where(function ($ownerQuery) use ($settlement_exist) {
                        $ownerQuery->whereNull('settlement_no')
                            ->orWhere('settlement_no', '')
                            ->orWhere('settlement_no', (string) $settlement_exist->id)
                            ->orWhere('settlement_no', (string) $settlement_exist->settlement_no);
                    })
                    ->where(function ($identityQuery) use (
                        $credit_payment_ids,
                        $credit_collection_form_nos,
                        $shift_ids_for_filter,
                        $has_credit_pump_payment_column,
                        $has_credit_shift_column,
                        $settlement_exist
                    ) {
                        $identityQuery->where('settlement_no', (string) $settlement_exist->id)
                            ->orWhere('settlement_no', (string) $settlement_exist->settlement_no);

                        if ($has_credit_pump_payment_column && ! empty($credit_payment_ids)) {
                            $identityQuery->orWhereIn('pump_payment_id', $credit_payment_ids);
                        }

                        if ($has_credit_shift_column) {
                            $identityQuery->orWhereIn('shift_id', $shift_ids_for_filter);
                        }

                        if (! empty($credit_collection_form_nos)) {
                            $identityQuery->orWhere(function ($legacyQuery) use (
                                $credit_collection_form_nos,
                                $has_credit_pump_payment_column
                            ) {
                                if ($has_credit_pump_payment_column) {
                                    $legacyQuery->whereNull('pump_payment_id');
                                }
                                $legacyQuery->whereIn('collection_form_no', $credit_collection_form_nos);
                            });
                        }
                    });

                $linked_count = (clone $credit_scope)
                    ->update(['settlement_no' => $settlement_exist->settlement_no]);

                $credit_columns = ['daily_voucher_id', 'collection_form_no'];
                if ($has_credit_pump_payment_column) {
                    $credit_columns[] = 'pump_payment_id';
                }

                $linked_credit_sales = SettlementCreditSalePayment::where(
                    'business_id',
                    $settlement_exist->business_id
                )
                    ->where('pump_operator_id', $settlement_exist->pump_operator_id)
                    ->where('settlement_no', $settlement_exist->settlement_no)
                    ->get($credit_columns);

                $daily_voucher_ids = $linked_credit_sales->pluck('daily_voucher_id')
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
                $linked_collection_form_nos = $linked_credit_sales->pluck('collection_form_no')
                    ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
                    ->map(fn ($value) => (string) $value)
                    ->unique()
                    ->values()
                    ->toArray();
                $linked_pump_payment_ids = $has_credit_pump_payment_column
                    ? $linked_credit_sales->pluck('pump_payment_id')
                        ->map(fn ($id) => (int) $id)
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray()
                    : $credit_payment_ids;

                $voucher_scope = DailyVoucher::where('business_id', $settlement_exist->business_id)
                    ->where('operator_id', $settlement_exist->pump_operator_id)
                    ->where(function ($ownerQuery) use ($settlement_exist) {
                        $ownerQuery->whereNull('settlement_no')
                            ->orWhere('settlement_no', '')
                            ->orWhere('settlement_no', (string) $settlement_exist->id)
                            ->orWhere('settlement_no', (string) $settlement_exist->settlement_no);
                    })
                    ->where(function ($identityQuery) use (
                        $daily_voucher_ids,
                        $linked_pump_payment_ids,
                        $linked_collection_form_nos
                    ) {
                        $identityQuery->whereRaw('1 = 0');

                        if (! empty($daily_voucher_ids)) {
                            $identityQuery->orWhereIn('id', $daily_voucher_ids);
                        }

                        if (! empty($linked_pump_payment_ids)
                            && Schema::hasColumn('daily_vouchers', 'pump_payment_id')) {
                            $identityQuery->orWhereIn('pump_payment_id', $linked_pump_payment_ids);
                        }

                        // Legacy voucher rows can still be recovered by their
                        // collection form number, scoped to this operator.
                        if (! empty($linked_collection_form_nos)) {
                            $identityQuery->orWhereIn('daily_vouchers_no', $linked_collection_form_nos);
                        }
                    });

                $daily_vouchers_updated = $voucher_scope->update([
                    'settlement_no' => $settlement_exist->settlement_no,
                ]);

                \Log::info('Settlement PD saveMeterSale: authoritative credit linkage completed', [
                    'settlement_id' => $settlement_exist->id,
                    'settlement_no' => $settlement_exist->settlement_no,
                    'shift_ids' => $shift_ids_for_filter,
                    'pump_payment_ids' => $credit_payment_ids,
                    'credit_sales_updated' => $linked_count,
                    'daily_vouchers_updated' => $daily_vouchers_updated,
                ]);
            }

            // add pump operator commission

            $pump_operator = PumpOperator::find(

                $settlement_exist->pump_operator_id

            );

            if (! empty($pump_operator)) {

                if (

                    ! empty($pump_operator->commission_type) &&

                    ! empty($pump_operator->commission_ap)

                ) {

                    $commission_amount = 0;

                    $discounted_amount = $request->discount_amount;

                    if ($pump_operator->commission_type == "percentage") {

                        $commission_amount =

                            ($discounted_amount *

                                $pump_operator->commission_ap) /

                            100;
                    }

                    if ($pump_operator->commission_type == "fixed") {

                        $commission_amount =

                            $request->qty * $pump_operator->commission_ap;
                    }

                    $commission_data = [

                        "pump_operator_id" =>

                        $settlement_exist->pump_operator_id,

                        "meter_sale_id"    => $meter_sale_sync->id,

                        "transaction_date" =>

                        $settlement_exist->transaction_date,

                        "amount"           => $commission_amount,

                        "type"             => $pump_operator->commission_type,

                        "value"            => $pump_operator->commission_ap,

                    ];

                    PumpOperatorCommission::create($commission_data);
                }
            }

            Pump::where("id", $request->pump_id)->update([

                "starting_meter"     => $request->starting_meter,

                "last_meter_reading" => $request->closing_meter,

            ]);

            DB::commit();

            $output = [

                "success"       => true,

                "msg"           => "success",

                "meter_sale_id" => $meter_sale_sync->id,

                "settlement_id" => $settlement_exist->id,
                "settlement_no" => $settlement_exist->settlement_no,
                "shift_id"      => $request->shift_id,
                "pump_operator_id" => $request->pump_operator_id,

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

    public function deleteMeterSale($id)
    {

        $this->assertPetroPdSettlementEntryAction('delete', true);
        $businessId = $this->resolvePetroPdBusinessId(request());

        try {

            $meter_sale = MeterSale::where('business_id', $businessId)
                ->where("id", $id)
                ->first();

            if (empty($meter_sale)) {
                return [
                    "success" => false,
                    "msg"     => "Meter sale not found.",
                ];
            }

            Settlement::where('business_id', $businessId)
                ->where("id", $meter_sale->settlement_no)
                ->update([

                "is_edit" => request()->is_edit,

            ]);

            $amount = $meter_sale->discount_amount;

            $starting_meter = $meter_sale->starting_meter;

            $closing_meter = $meter_sale->closing_meter;

            $pump = Pump::where("id", $meter_sale->pump_id)->first();

            $tank_id = $pump->fuel_tank_id;

            FuelTank::where("id", $tank_id)->increment(

                "current_balance",

                $meter_sale->qty

            );

            $meter_sale->delete();

            $pump->last_meter_reading = $starting_meter; //reset back to previous starting meter

            $previous_meter_sale = MeterSale::where("pump_id", $pump->id)

                ->orderBy("id", "desc")

                ->first();

            if (! empty($previous_meter_sale)) {

                $pump->starting_meter = $previous_meter_sale->starting_meter;
            }

            $pump->save();

            $pump_name = $pump->pump_name;

            $pump_id = $pump->id;

            // delete pump operator commission

            PumpOperatorCommission::where("meter_sale_id", $id)->delete();

            $output = [

                "success"   => true,

                "amount"    => $amount,

                "pump_name" => $pump_name,

                "pump_id"   => $pump_id,

                "msg"       => __("petropd::lang.success"),

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







     * save other sale data in db







     * @param product_id







     * @return Response







     */

    private function repairMeterSalePdSettlementNo($settlement)
    {
        $updated = PumpOperatorMeterSale::where('settlement_no', (string) $settlement->id)
            ->update(['settlement_no' => $settlement->settlement_no]);

        if ($updated > 0) {
            $settlement->load('meter_sales_pd', 'meter_sales_pd.details');
        }


        return $updated;
    }

    private function onlyClosedPumpMeterSales($query)
    {
        if (Schema::hasColumn('pump_operator_meter_sales', 'source')) {
            $query->where('source', 'closing');
        }

        return $query;
    }

    private function getSettlementPdDisplayMeterSales(int $business_id, ?Settlement $settlement, array $shift_ids = [])
    {
        if (empty($settlement)) {
            return collect();
        }

        $rows = collect();

        $pd_meter_sales = $settlement->relationLoaded('meter_sales_pd')
            ? $settlement->meter_sales_pd
            : collect();

        foreach ($pd_meter_sales as $sale) {
            foreach (($sale->details ?? collect()) as $detail) {
                $pump = $detail->pump ?: Pump::find($detail->pump_id);
                $product = $pump ? Product::find($pump->product_id) : null;

                $rows->push((object) [
                    'id' => $sale->id,
                    'source' => 'pump_operator',
                    'detail_id' => $detail->id,
                    'sku' => $product?->sku ?? '-',
                    'product_name' => $product?->name ?? '-',
                    'pump_no' => $pump?->pump_no ?? '-',
                    /*
                     * MA-002 (IS-1938 #2): the opening meter is the pump's LAST
                     * CLOSING METER, not the figure entered on the meter sale.
                     *
                     * It used to read $detail->received_meter - whatever the
                     * operator typed when entering meters on the pumper
                     * dashboard. The agreed rule is that a shift opens where the
                     * previous one closed, for that same pump, wherever that
                     * close was recorded.
                     *
                     * pumpLastClosingMeter() below resolves it, and falls back
                     * to the entered figure if nothing earlier exists, so a
                     * pump's very first settlement is unchanged.
                     */
                    'starting_meter' => $this->pumpLastClosingMeter(
                        (int) ($detail->pump_id ?? 0),
                        (int) ($sale->business_id ?? 0),
                        $sale->created_at ?? null,
                        $detail->received_meter
                    ),
                    'closing_meter' => $detail->new_meter,
                    'price' => $detail->unit_price,
                    'quantity' => $detail->sold_qty,
                    'discount_type' => '-',
                    'discount' => 0,
                    'testing_qty' => $sale->testing_qty ?? 0,
                    'sub_total' => $detail->amount,
                    'discount_amount' => $detail->amount,
                    'form_url' => '/petropd/settlement-pd/get-meter-sale-form/' . $sale->id . '?meter_sale_source=pump_operator&detail_id=' . $detail->id,
                ]);
            }
        }

        if ($rows->isNotEmpty()) {
            return $rows->unique(function ($row) {
                return implode('|', [
                    $row->pump_no,
                    number_format((float) $row->starting_meter, 3, '.', ''),
                    number_format((float) $row->closing_meter, 3, '.', ''),
                    number_format((float) $row->price, 4, '.', ''),
                    number_format((float) $row->quantity, 3, '.', ''),
                    number_format((float) $row->discount_amount, 4, '.', ''),
                ]);
            })->values();
        }

        $meter_sales = MeterSale::where('business_id', $business_id)
            ->where(function ($query) use ($settlement, $shift_ids) {
                $query->where(function ($linked) use ($settlement) {
                    $linked->where('settlement_no', (string) $settlement->id);
                    if (! empty($settlement->settlement_no)) {
                        $linked->orWhere('settlement_no', (string) $settlement->settlement_no);
                    }
                });

                if (! empty($shift_ids)) {
                    $query->orWhereIn('shift_id', array_values(array_filter($shift_ids)));
                }
            })
            ->orderBy('id')
            ->get();

        foreach ($meter_sales as $sale) {
            $pump = Pump::find($sale->pump_id);
            $product = ! empty($sale->product_id)
                ? Product::find($sale->product_id)
                : ($pump ? Product::find($pump->product_id) : null);

            $rows->push((object) [
                'id' => $sale->id,
                'source' => 'meter_sale',
                'detail_id' => null,
                'sku' => $product?->sku ?? '-',
                'product_name' => $product?->name ?? '-',
                'pump_no' => $pump?->pump_no ?? '-',
                'starting_meter' => $sale->starting_meter,
                'closing_meter' => $sale->closing_meter,
                'price' => $sale->price,
                'quantity' => $sale->qty,
                'discount_type' => $sale->discount_type ?? '-',
                'discount' => $sale->discount ?? 0,
                'testing_qty' => $sale->testing_qty ?? 0,
                'sub_total' => $sale->sub_total,
                'discount_amount' => $sale->discount_amount,
                'form_url' => '/petropd/settlement-pd/get-meter-sale-form/' . $sale->id,
            ]);
        }

        return $rows->unique(function ($row) {
            return implode('|', [
                $row->pump_no,
                number_format((float) $row->starting_meter, 3, '.', ''),
                number_format((float) $row->closing_meter, 3, '.', ''),
                number_format((float) $row->price, 4, '.', ''),
                number_format((float) $row->quantity, 3, '.', ''),
                number_format((float) $row->discount_amount, 4, '.', ''),
            ]);
        })->values();
    }

    /**
     * S775-1: Load only the meter rows that belong to THIS settlement shift.
     *
     * The old display/finalize queries used:
     *
     *     settlement_no = current settlement OR shift_id IN selected shifts
     *
     * That OR is unsafe when historical/corrupt rows have been stamped with the
     * current settlement number: a row from another shift then enters the final
     * report merely because settlement_no matches.  The Meter Sales tab itself
     * is shift-scoped, so the finalized report must use the same authority.
     *
     * Rule when an authoritative shift is known:
     *   - business + operator + exact shift are mandatory;
     *   - rows already linked to this settlement are allowed;
     *   - unlinked PD close-pump rows are allowed only while their linked pumper
     *     payment is still unused (same protection used by the Meter Sales tab);
     *   - a row explicitly owned by another settlement is never borrowed.
     *
     * For old records where no shift can be resolved at all, we fall back to the
     * settlement link only.  That keeps legacy reprints available without ever
     * widening a known shift to other shifts.
     */
    private function hydrateSettlementPdMeterSalesForDisplay($settlement, array $shift_ids = []): void
    {
        if (empty($settlement)) {
            return;
        }

        $shift_ids = array_values(array_unique(array_filter(array_map('intval', $shift_ids))));
        if (empty($shift_ids)) {
            $shift_ids = array_values(array_unique(array_filter(array_map(
                'intval',
                $this->getSettlementPDShiftIds($settlement)
            ))));
        }

        $linked_settlement_numbers = $this->pdSettlementMeterSaleLinkKeys($settlement);

        $pd_query = PumpOperatorMeterSale::with(['details.pump'])
            ->where('business_id', $settlement->business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->when(true, function ($query) {
                $this->onlyClosedPumpMeterSales($query);
            });

        if (! empty($shift_ids)) {
            $pd_query->whereIn('shift_id', $shift_ids);
            $this->scopePdMeterSaleOwnershipForCurrentSettlement(
                $pd_query,
                $linked_settlement_numbers
            );
        } else {
            // Legacy fallback only: if the settlement has no resolvable shift,
            // never broaden the query beyond rows explicitly linked to it.
            $pd_query->whereIn('settlement_no', $linked_settlement_numbers);
        }

        $pd_meter_sales = $pd_query
            ->orderBy('id')
            ->get()
            ->unique('id')
            ->unique(function ($sale) {
                $keys = [];
                foreach (($sale->details ?? collect()) as $detail) {
                    $keys[] = $this->meterSaleLineKey(
                        $detail->pump_id,
                        $detail->received_meter,
                        $detail->new_meter,
                        $detail->sold_qty,
                        $detail->unit_price,
                        $detail->amount
                    );
                }
                sort($keys);
                return implode('||', $keys);
            })
            ->values();

        $settlement->setRelation('meter_sales_pd', $pd_meter_sales);

        // Manual/additional Meter Sales are stored in meter_sales.  They are
        // created with both settlement_no and shift_id, so when a shift is known
        // BOTH must match.  This is the key S775 isolation: never use
        // "settlement link OR shift" for the finalized Meter Sale section.
        $regular_query = MeterSale::where('business_id', $settlement->business_id)
            ->whereIn('settlement_no', $linked_settlement_numbers);

        if (! empty($shift_ids)) {
            $regular_query->whereIn('shift_id', $shift_ids);
        }

        $regular_meter_sales = $regular_query
            ->orderBy('id')
            ->get()
            ->unique(function ($sale) {
                return $this->meterSaleLineKey(
                    $sale->pump_id,
                    $sale->starting_meter,
                    $sale->closing_meter,
                    $sale->qty,
                    $sale->price,
                    $sale->discount_amount ?? $sale->sub_total
                );
            })
            ->values();

        $settlement->setRelation('meter_sales', $regular_meter_sales);
    }

    /**
     * Settlement link forms found in old and current PetroPD rows.
     */
    private function pdSettlementMeterSaleLinkKeys($settlement): array
    {
        return array_values(array_unique(array_filter([
            (string) ($settlement->settlement_no ?? ''),
            (string) ($settlement->id ?? ''),
        ], static fn ($value) => $value !== '')));
    }

    /**
     * Apply ownership rules to an already shift-scoped PD meter-sale query.
     *
     * Linked rows are retained even after finalization (their pumper payment may
     * legitimately be is_used=1 because THIS PD settlement consumed it).  Only
     * unlinked rows use the "unused payment" rule, matching the live Meter Sales
     * tab and preventing Direct/other-settlement readings from leaking in.
     */
    private function scopePdMeterSaleOwnershipForCurrentSettlement($query, array $linked_settlement_numbers): void
    {
        $query->where(function ($ownership) use ($linked_settlement_numbers) {
            $ownership->whereIn('settlement_no', $linked_settlement_numbers)
                ->orWhere(function ($unlinked) {
                    $unlinked->where(function ($emptySettlement) {
                        $emptySettlement->whereNull('settlement_no')
                            ->orWhere('settlement_no', '');
                    })->where(function ($readingQuery) {
                        $readingQuery->whereNull('p_o_payment_id')
                            ->orWhereNotExists(function ($settledQuery) {
                                $settledQuery
                                    ->select(DB::raw(1))
                                    ->from('pump_operator_payments')
                                    ->whereColumn(
                                        'pump_operator_payments.id',
                                        'pump_operator_meter_sales.p_o_payment_id'
                                    )
                                    ->where('pump_operator_payments.is_used', 1);
                            });
                    });
                });
        });
    }

    private function getPdMeterSalesForFinalization($settlement, array $shift_ids = [])
    {
        $shift_ids = array_values(array_unique(array_filter(array_map('intval', $shift_ids))));
        if (empty($shift_ids)) {
            $shift_ids = array_values(array_unique(array_filter(array_map(
                'intval',
                $this->getSettlementPDShiftIds($settlement)
            ))));
        }

        $linked_settlement_numbers = $this->pdSettlementMeterSaleLinkKeys($settlement);

        $query = PumpOperatorMeterSale::with('details')
            ->where('business_id', $settlement->business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->when(true, function ($query) {
                $this->onlyClosedPumpMeterSales($query);
            });

        if (! empty($shift_ids)) {
            $query->whereIn('shift_id', $shift_ids);
            $this->scopePdMeterSaleOwnershipForCurrentSettlement(
                $query,
                $linked_settlement_numbers
            );
        } else {
            $query->whereIn('settlement_no', $linked_settlement_numbers);
        }

        return $query->get()->unique('id')->values();
    }

    private function getRegularMeterSalesForFinalization($settlement, $pd_meter_sales, array $shift_ids = [])
    {
        $shift_ids = array_values(array_unique(array_filter(array_map('intval', $shift_ids))));
        if (empty($shift_ids)) {
            $shift_ids = array_values(array_unique(array_filter(array_map(
                'intval',
                $this->getSettlementPDShiftIds($settlement)
            ))));
        }

        $linked_settlement_numbers = $this->pdSettlementMeterSaleLinkKeys($settlement);

        $regular_query = MeterSale::where('business_id', $settlement->business_id)
            ->whereIn('settlement_no', $linked_settlement_numbers);

        if (! empty($shift_ids)) {
            $regular_query->whereIn('shift_id', $shift_ids);
        }

        $regular_meter_sales = $regular_query->orderBy('id')->get();

        $pd_line_keys = [];
        foreach ($pd_meter_sales as $pd_sale) {
            foreach ($pd_sale->details as $detail) {
                $pd_line_keys[$this->meterSaleLineKey(
                    $detail->pump_id,
                    $detail->received_meter,
                    $detail->new_meter,
                    $detail->sold_qty,
                    $detail->unit_price,
                    $detail->amount
                )] = true;
            }
        }

        return $regular_meter_sales->filter(function ($meter_sale) use ($pd_line_keys) {
            $key = $this->meterSaleLineKey(
                $meter_sale->pump_id,
                $meter_sale->starting_meter,
                $meter_sale->closing_meter,
                $meter_sale->qty,
                $meter_sale->price,
                $meter_sale->sub_total
            );

            return ! isset($pd_line_keys[$key]);
        })->values();
    }

    private function meterSaleLineKey($pump_id, $starting_meter, $closing_meter, $qty, $price, $amount): string
    {
        return implode('|', [
            (int) $pump_id,
            number_format((float) $starting_meter, 3, '.', ''),
            number_format((float) $closing_meter, 3, '.', ''),
            number_format((float) $qty, 3, '.', ''),
            number_format((float) $price, 4, '.', ''),
            number_format((float) $amount, 4, '.', ''),
        ]);
    }

    public function getMeterSaleForm($id)
    {

        $this->assertPetroPdSettlementEntryAction('edit', true);
        $request = request();
        $businessId = $this->resolvePetroPdBusinessId($request);

        $output = [

            "success" => false,

            "msg"     => __("messages.something_went_wrong"),

        ];

        try {
            $active_settlement = null;
            $business_id = null;
            $already_pumps = [];
            $discount_types = [
                "fixed"      => "Fixed",
                "percentage" => "Percentage",
            ];

            $is_petro_pd_source = in_array((string) request()->input('source', ''), ['petro_pd', 'petropd'], true);

            $forceOperatorMeterSale = request()->input('meter_sale_source') === 'pump_operator';

            // List passes meter_sales.id when a synced meter_sales row exists.
            $meterSaleRecord = $forceOperatorMeterSale
                ? null
                : MeterSale::where('business_id', $businessId)->where('id', $id)->first();

            if ($meterSaleRecord) {

                $active_settlement = Settlement::where("id", $meterSaleRecord->settlement_no)
                    ->orWhere("settlement_no", $meterSaleRecord->settlement_no)
                    ->first();

                $discount_types = [

                    "fixed"      => "Fixed",

                    "percentage" => "Percentage",

                ];

                $already_pumps = ! empty($active_settlement)
                    ? MeterSale::where(function ($query) use ($active_settlement) {
                        $query->where("settlement_no", $active_settlement->id)
                            ->orWhere("settlement_no", $active_settlement->settlement_no);
                    })
                        ->pluck("pump_id")
                        ->toArray()
                    : [];

                $business_id = $meterSaleRecord->business_id;

                if (request()->action_type == "cancel") {

                    $meter_sale = [];
                } else {

                    $already_pumps = array_diff($already_pumps, [

                        $meterSaleRecord->pump_id,

                    ]);

                    $already_pumps = array_values($already_pumps);

                    $meter_sale = $meterSaleRecord->toArray();
                    // Use computed sold qty (closing - starting - testing) so form shows correct value
                    $meter_sale['qty'] = $meterSaleRecord->closing_meter - $meterSaleRecord->starting_meter - $meterSaleRecord->testing_qty;
                }
            } else {

                // Fallback: pumper dashboard rows live in pump_operator_meter_sales/details.
                $operatorMeterSale = PumpOperatorMeterSale::with('details')
                    ->where('business_id', $businessId)
                    ->where("id", $id)
                    ->first();

                if ($operatorMeterSale) {

                    $active_settlement = Settlement::where("id", $operatorMeterSale->settlement_no)
                        ->orWhere("settlement_no", $operatorMeterSale->settlement_no)
                        ->first();

                    if (empty($active_settlement) && ! empty(request()->active_settlement_id)) {
                        $active_settlement = Settlement::where("id", request()->active_settlement_id)
                            ->where("business_id", $operatorMeterSale->business_id)
                            ->first();
                    }

                    if (empty($active_settlement) && ! empty(request()->settlement_no)) {
                        $active_settlement = Settlement::where("settlement_no", request()->settlement_no)
                            ->where("business_id", $operatorMeterSale->business_id)
                            ->first();
                    }

                    $discount_types = [

                        "fixed"      => "Fixed",

                        "percentage" => "Percentage",

                    ];

                    $already_pumps = ! empty($active_settlement)
                        ? MeterSale::where(function ($query) use ($active_settlement) {
                            $query->where("settlement_no", $active_settlement->id)
                                ->orWhere("settlement_no", $active_settlement->settlement_no);
                        })
                            ->pluck("pump_id")
                            ->toArray()
                        : [];

                    $business_id = $operatorMeterSale->business_id;

                    if (request()->action_type != "cancel") {

                        $detail = ! empty(request()->detail_id)
                            ? $operatorMeterSale->details->firstWhere('id', (int) request()->detail_id)
                            : $operatorMeterSale->details->first();

                        if (empty($detail)) {
                            $meter_sale = [];
                        } else {
                            $pump = Pump::find($detail->pump_id);

                        $already_pumps = array_diff($already_pumps, [

                            $detail->pump_id,

                        ]);

                        $already_pumps = array_values($already_pumps);

                            $meter_sale = [
                                "id"                => $operatorMeterSale->id,
                                "meter_sale_source" => "pump_operator",
                                "meter_sale_detail_id" => $detail->id,
                                "pump_id"           => $detail->pump_id,
                                "product_id"        => $pump->product_id ?? null,
                                "starting_meter"    => $detail->received_meter,
                                "closing_meter"     => $detail->new_meter,
                                "qty"               => $detail->sold_qty,
                                "price"             => $detail->unit_price,
                                "testing_qty"       => $operatorMeterSale->testing_qty ?? 0,
                                "discount_type"     => $operatorMeterSale->discount_type ?? "fixed",
                                "discount"          => $operatorMeterSale->discount ?? 0,
                            ];
                        }
                    } else {

                        $meter_sale = [];
                    }
                } else {

                    $meter_sale = [];
                }
            }

            if (isset($business_id)) {

                $pump_nos = Pump::where("business_id", $business_id)

                    ->whereNotIn("id", $already_pumps)

                    ->pluck("pump_name", "id");

                $manual_entry_permission = auth()->user()->can('petro_pd.manual_entry');
                $view = $is_petro_pd_source
                    ? "petropd::pd_settlement.partials.meter_sale_form"
                    : "petropd::pd_settlement.partials.meter_sale_form";

                $html = view(

                    $view,

                    compact("meter_sale", "pump_nos", "discount_types", "manual_entry_permission")

                )->render();

                $output = [

                    "success" => true,

                    "html"    => $html,

                    "msg"     => __("petropd::lang.success"),

                ];
            }
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

            ];
        }

        return $output;
    }

    public function updateSettlementMeterSale($id, Request $request)
    {

        $this->assertPetroPdSettlementEntryAction('edit', true);
        $businessId = $this->resolvePetroPdBusinessId($request);

        try {

            if ($request->input('meter_sale_source') === 'pump_operator') {
                return $this->updatePumpOperatorMeterSale($id, $request);
            }

            $meter_sale = MeterSale::where('business_id', $businessId)
                ->where("id", $id)
                ->first();

            if (empty($meter_sale)) {
                return [
                    "success" => false,
                    "msg"     => "Meter sale not found.",
                ];
            }

            //Settlement::where('id',$meter_sale->settlement_no)->update(['is_edit' => $request->is_edit]);

            $pumpOld = Pump::where("id", $meter_sale->pump_id)->first();

            if (empty($pumpOld)) {
                return [
                    "success" => false,
                    "msg"     => "Pump not found.",
                ];
            }

            $tank_id_old = $pumpOld->fuel_tank_id;

            FuelTank::where("id", $tank_id_old)->increment(

                "current_balance",

                $meter_sale->qty

            );

            $amount = $meter_sale->discount_amount;

            $starting_meter_old = $meter_sale->starting_meter;

            $closing_meter_old = $meter_sale->closing_meter;

            $pumpOld->last_meter_reading = $starting_meter_old; //reset back to previous starting meter

            $previous_meter_sale = MeterSale::where("pump_id", $pumpOld->id)

                ->orderBy("id", "desc")

                ->first();

            if (! empty($previous_meter_sale)) {

                $pumpOld->starting_meter = $previous_meter_sale->starting_meter;
            }

            $pumpOld->save();

            $pumpNew = Pump::where("id", $request->pump_id)->first();

            if (empty($pumpNew)) {
                return [
                    "success" => false,
                    "msg"     => "Pump not found.",
                ];
            }

            $tank_id_new = $pumpNew->fuel_tank_id;

            FuelTank::where("id", $tank_id_new)->decrement(

                "current_balance",

                $request->qty

            );

            $pumpNew->starting_meter = $request->starting_meter;

            $pumpNew->last_meter_reading = $request->closing_meter;

            $pumpNew->save();

            $data = [

                "pump_id"         => $pumpNew->id,

                "product_id"      => $pumpNew->product_id,

                "starting_meter"  => $request->starting_meter,

                "closing_meter"   =>

                $pumpNew->bulk_sale_meter == 0

                    ? $request->closing_meter

                    : "",

                "price"           => $request->price,

                "qty"             => $request->qty,

                "discount"        => $request->discount,

                "discount_type"   => $request->discount_type,

                "discount_amount" => $request->discount_amount,

                "testing_qty"     => $request->testing_qty,

                "sub_total"       => $request->sub_total,

            ];

            MeterSale::where('business_id', $businessId)
                ->where("id", $id)
                ->update($data);

            $settlement_exist = Settlement::where("id", $meter_sale->settlement_no)
                ->orWhere("settlement_no", $meter_sale->settlement_no)
                ->first();

            if (empty($settlement_exist)) {
                return [
                    "success" => false,
                    "msg"     => __("petropd::lang.settlement_not_found"),
                ];
            }

            $operator_meter_sale_detail = DB::table("pump_operator_meter_sale_details as pomsd")
                ->join("pump_operator_meter_sales as poms", "poms.id", "=", "pomsd.sale_id")
                ->where("pomsd.business_id", $meter_sale->business_id)
                ->where("pomsd.pump_id", $meter_sale->pump_id)
                ->where("pomsd.received_meter", $meter_sale->starting_meter)
                ->where("pomsd.new_meter", $meter_sale->closing_meter)
                ->where("pomsd.sold_qty", $meter_sale->qty)
                ->where(function ($query) use ($settlement_exist) {
                    $query->where("poms.settlement_no", $settlement_exist->settlement_no)
                        ->orWhere("poms.settlement_no", (string) $settlement_exist->id);
                })
                ->when(! empty($meter_sale->shift_id), function ($query) use ($meter_sale) {
                    $query->where("poms.shift_id", $meter_sale->shift_id);
                })
                ->select("pomsd.*", "poms.deposited")
                ->orderBy("pomsd.id", "desc")
                ->first();

            if (empty($operator_meter_sale_detail)) {
                $operator_meter_sale_detail = DB::table("pump_operator_meter_sale_details as pomsd")
                    ->join("pump_operator_meter_sales as poms", "poms.id", "=", "pomsd.sale_id")
                    ->where("pomsd.business_id", $meter_sale->business_id)
                    ->where("pomsd.pump_id", $meter_sale->pump_id)
                    ->where(function ($query) use ($settlement_exist) {
                        $query->where("poms.settlement_no", $settlement_exist->settlement_no)
                            ->orWhere("poms.settlement_no", (string) $settlement_exist->id);
                    })
                    ->when(! empty($meter_sale->shift_id), function ($query) use ($meter_sale) {
                        $query->where("poms.shift_id", $meter_sale->shift_id);
                    })
                    ->select("pomsd.*", "poms.deposited")
                    ->orderBy("pomsd.id", "desc")
                    ->first();
            }

            if (! empty($operator_meter_sale_detail)) {
                DB::table("pump_operator_meter_sale_details")
                    ->where("id", $operator_meter_sale_detail->id)
                    ->update([
                        "pump_id"        => $pumpNew->id,
                        "received_meter" => $request->starting_meter,
                        "new_meter"      => $request->closing_meter,
                        "sold_qty"       => $request->qty,
                        "unit_price"     => $request->qty > 0 ? $request->sub_total / $request->qty : 0,
                        "amount"         => $request->sub_total,
                    ]);

                DB::table("pump_operator_meter_sales")
                    ->where("id", $operator_meter_sale_detail->sale_id)
                    ->update([
                        "amount"          => $request->sub_total,
                        "balance"         => $request->sub_total - ($operator_meter_sale_detail->deposited ?? 0),
                        "discount"        => $request->discount,
                        "discount_type"   => $request->discount_type,
                        "discount_amount" => $request->discount_amount,
                        "testing_qty"     => $request->testing_qty,
                    ]);
            }

            $pump_operator = PumpOperator::find(

                $settlement_exist->pump_operator_id

            );

            if (! empty($pump_operator)) {

                if (

                    ! empty($pump_operator->commission_type) &&

                    ! empty($pump_operator->commission_ap)

                ) {

                    $commission_amount = 0;

                    $discounted_amount = $request->discount_amount;

                    if ($pump_operator->commission_type == "percentage") {

                        $commission_amount =

                            ($discounted_amount *

                                $pump_operator->commission_ap) /

                            100;
                    }

                    if ($pump_operator->commission_type == "fixed") {

                        $commission_amount =

                            $request->qty * $pump_operator->commission_ap;
                    }

                    $commission_data = [

                        "transaction_date" =>

                        $settlement_exist->transaction_date,

                        "amount"           => $commission_amount,

                        "type"             => $pump_operator->commission_type,

                        "value"            => $pump_operator->commission_ap,

                    ];

                    PumpOperatorCommission::where([

                        "pump_operator_id" =>

                        $settlement_exist->pump_operator_id,

                        "meter_sale_id"    => $meter_sale->id,

                    ])

                        ->update($commission_data);
                }
            }

            $output = [

                "success"       => true,

                "msg"           => "success",

                "meter_sale_id" => $meter_sale->id,

                "settlement_id" => $settlement_exist->id,

                "amount"        => $request->discount_amount,

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

    private function updatePumpOperatorMeterSale($id, Request $request)
    {
        $toNumber = function ($value) {
            return (float) str_replace(',', '', (string) $value);
        };

        DB::beginTransaction();

        try {
            $businessId = $this->resolvePetroPdBusinessId($request);
            $operatorMeterSale = PumpOperatorMeterSale::with('details')
                ->where('business_id', $businessId)
                ->where('id', $id)
                ->first();

            if (empty($operatorMeterSale)) {
                DB::rollBack();

                return [
                    "success" => false,
                    "msg"     => "Meter sale not found.",
                ];
            }

            $detail = ! empty($request->detail_id)
                ? $operatorMeterSale->details->firstWhere('id', (int) $request->detail_id)
                : $operatorMeterSale->details->first();

            if (empty($detail)) {
                DB::rollBack();

                return [
                    "success" => false,
                    "msg"     => "Meter sale details not found.",
                ];
            }

            $pump = Pump::find($request->pump_id);

            if (empty($pump)) {
                DB::rollBack();

                return [
                    "success" => false,
                    "msg"     => "Pump not found.",
                ];
            }

            $oldAttributes = [
                "settlement_no"  => $operatorMeterSale->settlement_no,
                "pump_id"        => $detail->pump_id,
                "starting_meter" => $detail->received_meter,
                "closing_meter"  => $detail->new_meter,
                "qty"            => $detail->sold_qty,
                "price"          => $detail->unit_price,
                "testing_qty"    => $operatorMeterSale->testing_qty ?? 0,
                "discount_type"  => $operatorMeterSale->discount_type ?? null,
                "discount"       => $operatorMeterSale->discount ?? 0,
                "sub_total"      => $detail->amount,
            ];

            $qty = $toNumber($request->qty);
            $subTotal = $toNumber($request->sub_total);
            $discountAmount = $toNumber($request->discount_amount);
            $testingQty = $toNumber($request->testing_qty);
            $unitPrice = $qty > 0 ? $subTotal / $qty : $toNumber($request->price);

            $detail->update([
                "pump_id"        => $pump->id,
                "received_meter" => $toNumber($request->starting_meter),
                "new_meter"      => $toNumber($request->closing_meter),
                "sold_qty"       => $qty,
                "unit_price"     => $unitPrice,
                "amount"         => $subTotal,
            ]);

            $operatorUpdate = [
                "amount"  => $subTotal,
                "balance" => $subTotal - (float) ($operatorMeterSale->deposited ?? 0),
            ];

            if (Schema::hasColumn('pump_operator_meter_sales', 'discount')) {
                $operatorUpdate["discount"] = $request->discount;
            }

            if (Schema::hasColumn('pump_operator_meter_sales', 'discount_type')) {
                $operatorUpdate["discount_type"] = $request->discount_type;
            }

            if (Schema::hasColumn('pump_operator_meter_sales', 'discount_amount')) {
                $operatorUpdate["discount_amount"] = $discountAmount;
            }

            if (Schema::hasColumn('pump_operator_meter_sales', 'testing_qty')) {
                $operatorUpdate["testing_qty"] = $testingQty;
            }

            $operatorMeterSale->update($operatorUpdate);

            $settlement = null;
            if (! empty($request->active_settlement_id)) {
                $settlement = Settlement::where('id', $request->active_settlement_id)
                    ->where('business_id', $operatorMeterSale->business_id)
                    ->first();
            }

            if (empty($settlement) && ! empty($request->settlement_no)) {
                $settlement = Settlement::where('settlement_no', $request->settlement_no)
                    ->where('business_id', $operatorMeterSale->business_id)
                    ->first();
            }

            if (empty($settlement) && ! empty($operatorMeterSale->settlement_no)) {
                $settlement = Settlement::where('id', $operatorMeterSale->settlement_no)
                    ->orWhere('settlement_no', $operatorMeterSale->settlement_no)
                    ->first();
            }

            if (! empty($settlement)) {
                if (empty($operatorMeterSale->settlement_no)) {
                    $operatorMeterSale->settlement_no = $settlement->settlement_no;
                    $operatorMeterSale->save();
                }

                $newAttributes = [
                    "settlement_no"  => $settlement->settlement_no,
                    "pump_id"        => $pump->id,
                    "starting_meter" => $toNumber($request->starting_meter),
                    "closing_meter"  => $toNumber($request->closing_meter),
                    "qty"            => $qty,
                    "price"          => $unitPrice,
                    "testing_qty"    => $testingQty,
                    "discount_type"  => $request->discount_type,
                    "discount"       => $request->discount,
                    "sub_total"      => $subTotal,
                ];

                activity()
                    ->performedOn($settlement)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        "attributes" => $newAttributes,
                        "old"        => $oldAttributes,
                    ])
                    ->log("updated");
            }

            DB::commit();

            return [
                "success"       => true,
                "msg"           => "success",
                "meter_sale_id" => $operatorMeterSale->id,
                "settlement_id" => $settlement->id ?? null,
                "amount"        => $discountAmount,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency(
                "File: " .
                    $e->getFile() .
                    "Line: " .
                    $e->getLine() .
                    "Message: " .
                    $e->getMessage()
            );

            return [
                "success" => false,
                "msg"     => __("messages.something_went_wrong"),
            ];
        }
    }

    /*
     * S 639: ONE SOURCE. This now reads pumper_day_entries through
     * ShiftSaleTotals, the same table and the same query the Pumper Dashboard
     * uses, instead of re-summing the copy in pump_operator_meter_sales.
     *
     * The old body is kept below as getMeterSaleTotalByShiftLegacy() and is
     * still called - but only to record, in the log, where it disagreed. It no
     * longer decides any figure on screen. It is deleted in the parcel that
     * moves the ledger over, once that log has been read.
     */
    private function getMeterSaleTotalByShift(int $business_id, ?int $shift_id, ?int $pump_operator_id = null): float
    {
        if (empty($shift_id)) {
            return 0.0;
        }

        $totals = \Modules\SettlementCore\Services\ShiftSaleTotals::for(
            (int) $business_id,
            (int) $shift_id,
            $pump_operator_id
        );

        $totals->logDivergence(
            'HandlesPdMeterSales::getMeterSaleTotalByShift',
            $this->getMeterSaleTotalByShiftLegacy($business_id, $shift_id, $pump_operator_id)
        );

        return $totals->meterSales();
    }

    private function getMeterSaleTotalByShiftLegacy(int $business_id, ?int $shift_id, ?int $pump_operator_id = null): float
    {
        if (empty($shift_id)) return 0.0;

        $query = PumpOperatorMeterSale::with('details')
            ->where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            /*
             * MA-002: this total now counts the SAME rows the settlement table
             * shows.
             *
             * It counted every meter sale for the shift - no filter on source,
             * none on p_o_payment_id - while the table beside it shows only
             * source 'closing' rows carrying no payment id. The two disagreed.
             *
             * WHY THAT MATTERED, AND IT IS MONEY: a meter sale entered on the
             * Payments page is stored as source 'payment'. The operator has
             * ALREADY HANDED OVER that cash, and it is settled in Petro Direct
             * / Direct Settlement - a different screen. Counting it here as
             * well charged the operator TWICE for the same sale.
             *
             * Both callers are PD Settlement paths - pdSettlement() and the
             * settlement create page - so neither relied on the unfiltered
             * figure. I checked both before changing this.
             */
            /*
             * MA-002: the SAME two conditions the settlement table uses.
             *
             * I first filtered this on source = 'closing'. That was wrong: a meter
             * sale added by hand in Manual Entry is saved with NO source, so it
             * would have appeared in the table but been LEFT OUT OF THE TOTAL -
             * the admin correcting a settlement would have seen their entry
             * silently ignored in the figure.
             *
             * The settlement_no test does the work instead: PDST belongs here, ST
             * belongs to Direct Settlement, and an unset value is a hand-added row
             * that has not been stamped yet.
             */
            // MA-002: one shared rule - see scopeForPdSettlement on the model.
            ->forPdSettlement($this->getPdSettlementPrefixes((int) $business_id));
            
        if (!empty($pump_operator_id)) {
            $query->where('pump_operator_id', $pump_operator_id);
        }

        $total = 0.0;
        $seenRows = [];

        foreach ($query->get() as $sale) {
            $details = collect($sale->details);
            if ($details->isEmpty()) {
                $saleKey = 'sale:' . (int) $sale->id;
                if (! isset($seenRows[$saleKey])) {
                    $seenRows[$saleKey] = true;
                    $total += (float) ($sale->balance ?? $sale->amount ?? 0);
                }
                continue;
            }

            foreach ($details as $detail) {
                $rowKey = implode('|', [
                    $detail->pump_id ?? '',
                    number_format((float) ($detail->received_meter ?? 0), 3, '.', ''),
                    number_format((float) ($detail->new_meter ?? 0), 3, '.', ''),
                    number_format((float) ($detail->unit_price ?? 0), 4, '.', ''),
                    number_format((float) ($detail->sold_qty ?? 0), 3, '.', ''),
                    number_format((float) ($detail->amount ?? 0), 4, '.', ''),
                ]);

                if (isset($seenRows[$rowKey])) {
                    continue;
                }

                $seenRows[$rowKey] = true;
                $total += (float) ($detail->amount ?? 0);
            }
        }

        return $total;
    }

    /*
     * S 639: ONE SOURCE. A settlement's meter sale total is the sum of its
     * shifts' totals, each read from pumper_day_entries. The three-way
     * linked/shift/regular fallback below was only ever needed because the copy
     * in pump_operator_meter_sales could be stamped by settlement id, by
     * settlement number, or not at all - a problem the day entries do not have.
     */
    private function getSettlementPDMeterSaleTotal(int $business_id, ?Settlement $settlement): float
    {
        if (empty($settlement)) {
            return 0.0;
        }

        $authoritative_shift_ids = $this->getSettlementPDShiftIds($settlement);

        if (! empty($authoritative_shift_ids)) {
            $authoritative_total = 0.0;

            foreach ($authoritative_shift_ids as $authoritative_shift_id) {
                $authoritative_total += \Modules\SettlementCore\Services\ShiftSaleTotals::for(
                    (int) $business_id,
                    (int) $authoritative_shift_id,
                    $settlement->pump_operator_id
                )->meterSales();
            }

            $this->logSettlementPDMeterSaleDivergence(
                $business_id,
                $settlement,
                $authoritative_shift_ids,
                $authoritative_total
            );

            return $authoritative_total;
        }

        /*
         * No shift on the settlement - a finalized or hand-built record. Fall
         * through to the legacy read so such rows still display, and log it, so
         * we can see whether any remain before the ledger parcel.
         */
        \Illuminate\Support\Facades\Log::warning('S639 settlement has no shift ids, using legacy meter sale read', [
            'business_id' => $business_id,
            'settlement_id' => $settlement->id ?? null,
            'settlement_no' => $settlement->settlement_no ?? null,
        ]);

        return $this->getSettlementPDMeterSaleTotalLegacy($business_id, $settlement);
    }

    private function logSettlementPDMeterSaleDivergence(
        int $business_id,
        Settlement $settlement,
        array $shift_ids,
        float $authoritative_total
    ): void {
        try {
            $legacy_total = $this->getSettlementPDMeterSaleTotalLegacy($business_id, $settlement);

            if (abs($authoritative_total - $legacy_total) < 0.005) {
                return;
            }

            \Illuminate\Support\Facades\Log::warning('S639 settlement meter sale divergence', [
                'business_id' => $business_id,
                'settlement_id' => $settlement->id ?? null,
                'settlement_no' => $settlement->settlement_no ?? null,
                'shift_ids' => $shift_ids,
                'authoritative_day_entries' => $authoritative_total,
                'legacy_meter_sales' => $legacy_total,
                'difference' => $authoritative_total - $legacy_total,
            ]);
        } catch (\Throwable $e) {
            // Evidence gathering must never affect the page.
        }
    }

    private function getSettlementPDMeterSaleTotalLegacy(int $business_id, ?Settlement $settlement): float
    {
        if (empty($settlement)) {
            return 0.0;
        }

        $linked_total = (float) PumpOperatorMeterSale::where('business_id', $business_id)
            ->whereNull('p_o_payment_id')
            ->when(true, function ($query) {
                $this->onlyClosedPumpMeterSales($query);
            })
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->where(function ($q) use ($settlement) {
                // `meter_sales.settlement_no` is stored as a string in some environments
                // (may contain the settlement id or settlement number like "ST41").
                $q->where('settlement_no', (string) $settlement->id);
                if (! empty($settlement->settlement_no)) {
                    $q->orWhere('settlement_no', (string) $settlement->settlement_no);
                }
            })
            ->sum('balance');

        $shift_ids   = $this->getSettlementPDShiftIds($settlement);
        $shift_total = 0.0;
        if (! empty($shift_ids)) {
            $shift_total = (float) PumpOperatorMeterSale::where('business_id', $business_id)
                ->whereNull('p_o_payment_id')
                ->when(true, function ($query) {
                    $this->onlyClosedPumpMeterSales($query);
                })
                ->whereIn('shift_id', $shift_ids)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->sum('balance');
        }

        $regular_total = (float) MeterSale::where('business_id', $business_id)
            ->where('settlement_no', (string) $settlement->id)
            ->sum(DB::raw('sub_total - discount_amount'));

        // Defensive fallback (production-safe):
        // If PD operator meter rows are missing, finalized settlements may still
        // have their displayable meter rows in meter_sales keyed by settlement id.
        $final_total = ($linked_total == 0.0 && $shift_total > 0.0) ? $shift_total : $linked_total;
        if ($final_total == 0.0 && $regular_total > 0.0) {
            $final_total = $regular_total;
        }

        if (env('PETRO_SETTLEMENT_PD_DEBUG', false)) {
            Log::debug('SettlementPD Payments: meter sales total', [
                'business_id'   => $business_id,
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'shift_ids'     => $shift_ids,
                'linked_total'  => $linked_total,
                'shift_total'   => $shift_total,
                'regular_total' => $regular_total,
                'final_total'   => $final_total,
            ]);
        }

        return $final_total;
    }

    /**
     * MA-002 (IS-1938 #2): the last closing meter recorded for a pump.
     *
     * A shift opens where the previous one closed. This looks for that closing
     * figure in the two places BOTH the Pumper Dashboard and Petro Direct write
     * to - so a pump last closed in either system is picked up, and switching
     * between them loses nothing:
     *
     *     1. pumper_day_entries.closing_meter
     *     2. pump_operator_assignments.closing_meter
     *
     * The most recent by date wins, whichever table it came from.
     *
     * Rows at or after $before are ignored, so the CURRENT shift's own closing
     * meter cannot become its own opening.
     *
     * ZERO IS A REAL READING and is returned as such - a pump genuinely closed
     * at zero opens at zero. The fallback is used only when NO earlier row
     * exists at all, which is a pump's first ever settlement.
     */
    protected function pumpLastClosingMeter(int $pumpId, int $businessId, $before = null, $fallback = 0)
    {
        if ($pumpId <= 0 || $businessId <= 0) {
            return $fallback;
        }

        $candidates = [];

        if (\Schema::hasTable('pumper_day_entries')) {
            $row = \DB::table('pumper_day_entries')
                ->where('business_id', $businessId)
                ->where('pump_id', $pumpId)
                ->whereNotNull('closing_meter')
                ->when(! empty($before), fn ($q) => $q->where('created_at', '<', $before))
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first(['closing_meter', 'created_at']);

            if ($row) {
                $candidates[] = $row;
            }
        }

        if (\Schema::hasTable('pump_operator_assignments')) {
            $row = \DB::table('pump_operator_assignments')
                ->where('business_id', $businessId)
                ->where('pump_id', $pumpId)
                ->whereNotNull('closing_meter')
                ->whereNotNull('close_date_and_time')
                ->when(! empty($before), fn ($q) => $q->where('close_date_and_time', '<', $before))
                ->orderByDesc('close_date_and_time')
                ->orderByDesc('id')
                ->first(['closing_meter', 'close_date_and_time as created_at']);

            if ($row) {
                $candidates[] = $row;
            }
        }

        if (empty($candidates)) {
            return $fallback;
        }

        // Most recent wins, whichever table recorded it.
        usort($candidates, function ($a, $b) {
            return strcmp((string) ($b->created_at ?? ''), (string) ($a->created_at ?? ''));
        });

        return $candidates[0]->closing_meter;
    }
}

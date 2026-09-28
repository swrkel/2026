<?php

namespace Modules\Petro\Http\Controllers\SettlementPD\Concerns;

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
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\SettlementDrawingPayment;
use Modules\Petro\Entities\SettlementEditHistory;
use Modules\Petro\Entities\SettlementExcessPayment;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Petro\Entities\SettlementLoanPayment;
use Modules\Petro\Entities\SettlementShortagePayment;
use Modules\Petro\Entities\TankSellLine;
use Modules\Petro\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;
use Modules\Petro\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;

/**
 * Meter sale entry, display and totals.
 *
 * MA-002: split out of SettlementPDController, which was 13,540 lines in a
 * single file.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at
 *   SettlementPDController, action() targets still resolve, and the $this->
 *   calls between these 85 methods still work. Separate controller classes
 *   would mean rewriting routes and every action() reference - a behavioural
 *   change dressed up as tidying, and with 85 methods the odds of missing one
 *   are high.
 *
 *   So this is a purely physical split: same class at runtime, smaller files.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten
 * while moving.
 *
 * Methods here: meter_sales, editMeterSale, updateMeterSale, saveMeterSale, deleteMeterSale, repairMeterSalePdSettlementNo, onlyClosedPumpMeterSales, canonicalClosedPumpMeterSales, getSettlementPdDisplayMeterSales, hydrateSettlementPdMeterSalesForDisplay, getPdMeterSalesForFinalization, getRegularMeterSalesForFinalization, meterSaleLineKey, getMeterSaleForm, updateSettlementMeterSale, updatePumpOperatorMeterSale, getMeterSaleTotalByShift, getSettlementPDMeterSaleTotal
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

                                    "\Modules\Petro\Http\Controllers\SettlementPDController@editMeterSale",

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

                \Log::emergency('Petro PD settlement list failed: ' . $e->getMessage());

                return response()->json([
                    'error' => __('messages.something_went_wrong'),
                ], 500);
            }
        }
    }

    public function editMeterSale($id)
    {

        $meter_sale = TankSellLine::findOrFail($id);

        $transaction = Transaction::findOrFail($meter_sale->transaction_id);

        return view("petro::edit_settlement_date.edit")->with(

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

                    "msg"     => __("petro::lang.date_greater_than_day_end"),

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

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operator_meter_sales', 'source')) {
                $operator_meter_sale_data['source'] = 'closing';
            }

/*
|--------------------------------------------------------------------------
| Prevent duplicate Pump Operator Meter Sales
|--------------------------------------------------------------------------
|
| If this meter sale already came from the Pump Operator Dashboard /
| Shift Closing process, reuse it instead of creating a second one.
|
*/

$existingMeterSaleDetail = PumpOperatorMeterSaleDetail::where(
        'business_id',
        $business_id
    )
    ->where('pump_operator_id', $request->pump_operator_id)
    ->where('pump_id', $request->pump_id)
    ->where('received_meter', $request->starting_meter)
    ->where('new_meter', $request->closing_meter)
    ->where('sold_qty', $request->qty)
    ->orderByDesc('id')
    ->first();

$meterSale = null;

if (!empty($existingMeterSaleDetail)) {

    $meterSale = PumpOperatorMeterSale::where(
            'id',
            $existingMeterSaleDetail->sale_id
        )
        ->first();

    if ($meterSale) {

        $meterSale->settlement_no =
            $settlement_exist->settlement_no;

        $meterSale->shift_id =
            $request->shift_id ?: $meterSale->shift_id;

        $meterSale->save();
    }
}

if (empty($meterSale)) {

    $meterSale = PumpOperatorMeterSale::create(
        $operator_meter_sale_data
    );

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

            // CRITICAL: Link credit sales from pumper dashboard when meter sale is added
            // This marks credit sales as "completed" in daily collection
            // Similar to how cash and card payments are marked as completed
            if (! empty($request->shift_id)) {
                $shift_ids_for_filter = [intval($request->shift_id)];

                \Modules\Petro\Support\PetroDebug::info('Settlement PD saveMeterSale: Linking Credit Sales from Pumper Dashboard', [
                    'settlement_id'        => $settlement_exist->id,
                    'settlement_no'        => $settlement_exist->settlement_no,
                    'pump_operator_id'     => $settlement_exist->pump_operator_id,
                    'shift_ids_for_filter' => $shift_ids_for_filter,
                ]);

                // Get DailyVoucher IDs for the shift being settled
                $daily_voucher_ids_for_shifts = DailyVoucher::where('operator_id', $settlement_exist->pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->whereNull('settlement_no')
                    ->pluck('id')
                    ->toArray();

                \Modules\Petro\Support\PetroDebug::info('Settlement PD saveMeterSale: DailyVoucher IDs for Shift', [
                    'daily_voucher_ids_for_shifts' => $daily_voucher_ids_for_shifts,
                    'count'                        => count($daily_voucher_ids_for_shifts),
                ]);

                // Link SettlementCreditSalePayment records that match these DailyVouchers
                if (! empty($daily_voucher_ids_for_shifts)) {
                    $linked_count = SettlementCreditSalePayment::where('business_id', $settlement_exist->business_id)
                        ->where('pump_operator_id', $settlement_exist->pump_operator_id)
                        ->whereIn('daily_voucher_id', $daily_voucher_ids_for_shifts)
                        ->where(function ($q) {
                            $q->whereNull('settlement_no')
                                ->orWhere('settlement_no', '');
                        })
                        ->update(['settlement_no' => $settlement_exist->settlement_no]);

                    \Modules\Petro\Support\PetroDebug::info('Settlement PD saveMeterSale: Credit Sales Linked (by daily_voucher_id)', [
                        'linked_count'  => $linked_count,
                        'settlement_no' => $settlement_exist->settlement_no,
                    ]);

                    // Update DailyVoucher settlement_no to mark as completed
                    DailyVoucher::whereIn('id', $daily_voucher_ids_for_shifts)
                        ->whereNull('settlement_no')
                        ->update(['settlement_no' => $settlement_exist->settlement_no]);

                    \Modules\Petro\Support\PetroDebug::info('Settlement PD saveMeterSale: DailyVouchers Updated with Settlement No', [
                        'updated_count' => count($daily_voucher_ids_for_shifts),
                    ]);
                }

                // Also link by matching order_number and customer_id with DailyVouchers
                $daily_vouchers_for_shifts = DailyVoucher::where('operator_id', $settlement_exist->pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->whereNull('settlement_no')
                    ->get(['id', 'voucher_order_number', 'customer_id']);

                foreach ($daily_vouchers_for_shifts as $dv) {
                    if (! empty($dv->voucher_order_number)) {
                        $linked_count = SettlementCreditSalePayment::where('business_id', $settlement_exist->business_id)
                            ->where('pump_operator_id', $settlement_exist->pump_operator_id)
                            ->where('order_number', $dv->voucher_order_number)
                            ->where('customer_id', $dv->customer_id)
                            ->where(function ($q) {
                                $q->whereNull('settlement_no')
                                    ->orWhere('settlement_no', '');
                            })
                            ->update(['settlement_no' => $settlement_exist->settlement_no]);

                        if ($linked_count > 0) {
                            // Update DailyVoucher settlement_no
                            DailyVoucher::where('id', $dv->id)
                                ->whereNull('settlement_no')
                                ->update(['settlement_no' => $settlement_exist->settlement_no]);
                        }
                    }
                }
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

        try {

            $meter_sale = MeterSale::where("id", $id)->first();

            Settlement::where("id", $meter_sale->settlement_no)->update([

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

                "msg"       => __("petro::lang.success"),

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
        if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operator_meter_sales', 'source')) {
            $query->where('source', 'closing');
        }

        return $query;
    }

    private function canonicalClosedPumpMeterSales($sales)
    {
        return collect($sales)
            ->sortByDesc('id')
            ->unique(function ($sale) {
                $pumpIds = collect($sale->details ?? [])
                    ->pluck('pump_id')
                    ->filter()
                    ->sort()
                    ->implode(',');

                return implode('|', [
                    (int) ($sale->shift_id ?? 0),
                    (int) ($sale->pump_operator_id ?? 0),
                    $pumpIds,
                ]);
            })
            ->sortBy('id')
            ->values();
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
                    'starting_meter' => $detail->received_meter,
                    'closing_meter' => $detail->new_meter,
                    'price' => $detail->unit_price,
                    'quantity' => $detail->sold_qty,
                    'discount_type' => '-',
                    'discount' => 0,
                    'testing_qty' => $sale->testing_qty ?? 0,
                    'sub_total' => $detail->amount,
                    'discount_amount' => $detail->amount,
                    'form_url' => '/petro/settlement-pd/get-meter-sale-form/' . $sale->id . '?meter_sale_source=pump_operator&detail_id=' . $detail->id,
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
                'form_url' => '/petro/settlement-pd/get-meter-sale-form/' . $sale->id,
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

    private function hydrateSettlementPdMeterSalesForDisplay($settlement, array $shift_ids = []): void
    {
        if (empty($settlement)) {
            return;
        }

        $shift_ids = array_values(array_filter(array_map('intval', $shift_ids)));
        if (empty($shift_ids)) {
            $shift_ids = $this->getSettlementPDShiftIds($settlement);
        }

        $pd_meter_sales = PumpOperatorMeterSale::with(['details.pump'])
            ->where('business_id', $settlement->business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->when(true, function ($query) {
                $this->onlyClosedPumpMeterSales($query);
            })
            ->where(function ($query) use ($settlement, $shift_ids) {
                $query->whereIn('settlement_no', [
                    $settlement->settlement_no,
                    (string) $settlement->id,
                    $settlement->id,
                ]);

                if (! empty($shift_ids)) {
                    $query->orWhereIn('shift_id', $shift_ids);
                }
            })
            ->get();

        $pd_meter_sales = $this->canonicalClosedPumpMeterSales($pd_meter_sales);

        if ($pd_meter_sales->isNotEmpty()) {
            $settlement->setRelation('meter_sales_pd', $pd_meter_sales);
        }
    }

    private function getPdMeterSalesForFinalization($settlement, array $shift_ids = [])
    {
        $shift_ids = array_values(array_filter(array_map('intval', $shift_ids)));

        $sales = PumpOperatorMeterSale::with('details')
            ->where('business_id', $settlement->business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->when(true, function ($query) {
                $this->onlyClosedPumpMeterSales($query);
            })
            ->where(function ($query) use ($settlement, $shift_ids) {
                $query->where('settlement_no', $settlement->settlement_no)
                    ->orWhere('settlement_no', (string) $settlement->id);

                if (! empty($shift_ids)) {
                    $query->orWhereIn('shift_id', $shift_ids);
                }
            })
            ->get();

        return $this->canonicalClosedPumpMeterSales($sales);
    }

    private function getRegularMeterSalesForFinalization($settlement, $pd_meter_sales)
    {
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

        return $settlement->meter_sales->filter(function ($meter_sale) use ($pd_line_keys) {
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
            $meterSaleRecord = $forceOperatorMeterSale ? null : MeterSale::find($id);

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
                    : "petro::settlement_pd.partials.meter_sale_form";

                $html = view(

                    $view,

                    compact("meter_sale", "pump_nos", "discount_types", "manual_entry_permission")

                )->render();

                $output = [

                    "success" => true,

                    "html"    => $html,

                    "msg"     => __("petro::lang.success"),

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

        try {

            if ($request->input('meter_sale_source') === 'pump_operator') {
                return $this->updatePumpOperatorMeterSale($id, $request);
            }

            $meter_sale = MeterSale::where("id", $id)->first();

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

            MeterSale::where("id", $id)->update($data);

            $settlement_exist = Settlement::where("id", $meter_sale->settlement_no)
                ->orWhere("settlement_no", $meter_sale->settlement_no)
                ->first();

            if (empty($settlement_exist)) {
                return [
                    "success" => false,
                    "msg"     => __("petro::lang.settlement_not_found"),
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
            $operatorMeterSale = PumpOperatorMeterSale::with('details')
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

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operator_meter_sales', 'discount')) {
                $operatorUpdate["discount"] = $request->discount;
            }

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operator_meter_sales', 'discount_type')) {
                $operatorUpdate["discount_type"] = $request->discount_type;
            }

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operator_meter_sales', 'discount_amount')) {
                $operatorUpdate["discount_amount"] = $discountAmount;
            }

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pump_operator_meter_sales', 'testing_qty')) {
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

    private function getMeterSaleTotalByShift(int $business_id, ?int $shift_id, ?int $pump_operator_id = null): float
    {
        if (empty($shift_id)) return 0.0;

        $query = PumpOperatorMeterSale::where('business_id', $business_id)
            ->whereNull('p_o_payment_id')
            ->when(true, function ($query) {
                $this->onlyClosedPumpMeterSales($query);
            })
            ->where('shift_id', $shift_id);
            
        if (!empty($pump_operator_id)) {
            $query->where('pump_operator_id', $pump_operator_id);
        }

        return (float) $query->sum('balance');
    }

    private function getSettlementPDMeterSaleTotal(int $business_id, ?Settlement $settlement): float
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
            \Modules\Petro\Support\PetroDebug::debug('SettlementPD Payments: meter sales total', [
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
}

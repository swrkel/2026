<?php

namespace Modules\PetroGeneral\Http\Controllers\Settlement\Concerns;

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
use Modules\PetroGeneral\Entities\CustomerBillVatPrefix;
use Modules\PetroGeneral\Entities\DailyCard;
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
use Modules\PetroGeneral\Entities\PumpOperatorPayment;
use Modules\PetroGeneral\Entities\PumpOperatorOtherSale;
use Modules\PetroGeneral\Entities\Settlement;
use Modules\PetroGeneral\Entities\SettlementCardPayment;
use Modules\PetroGeneral\Entities\SettlementCashDeposit;
use Modules\PetroGeneral\Entities\SettlementCashPayment;
use Modules\PetroGeneral\Entities\SettlementChequePayment;
use Modules\PetroGeneral\Entities\SettlementCreditSalePayment;
use Modules\PetroGeneral\Entities\SettlementEditHistory;
use Modules\PetroGeneral\Entities\SettlementExcessPayment;
use Modules\PetroGeneral\Entities\PumpOperatorMeterSale;
use Modules\PetroGeneral\Entities\SettlementExpensePayment;
use Modules\PetroGeneral\Entities\SettlementShortagePayment;
use Modules\PetroGeneral\Entities\SettlementLoanPayment;
use Modules\PetroGeneral\Entities\SettlementDrawingPayment;
use Modules\PetroGeneral\Entities\SettlementCustomerLoan;
use Modules\PetroGeneral\Entities\TankSellLine;
use Modules\Superadmin\Entities\Subscription;
use Modules\PetroGeneral\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;

/**
 * Meter sale entry, display and totals.
 *
 * MA-002: split out of PetroGeneral's SettlementController, which was 11,479
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
 * Methods here: meter_sales, editMeterSale, updateMeterSale, saveMeterSale, deleteMeterSale, getMeterSaleForm, updateSettlementMeterSale
 */
trait HandlesPdMeterSales
{
    public function meter_sales()
    {

        $business_id = request()

            ->session()

            ->get('user.business_id');

        if (request()->ajax()) {

            try {

                $query = TankSellLine::leftjoin(

                    'transactions',

                    'transactions.id',

                    'tank_sell_lines.transaction_id'

                )

                    ->leftjoin(

                        'fuel_tanks',

                        'fuel_tanks.id',

                        'tank_sell_lines.tank_id'

                    )

                    ->leftjoin(

                        'products',

                        'products.id',

                        'tank_sell_lines.product_id'

                    )

                    ->leftjoin(

                        'settlements',

                        'settlements.id',

                        'transactions.invoice_no'

                    ) // Add this join

                    ->where('settlements.business_id', $business_id)

                    ->select([

                        'products.name as product_name',

                        'fuel_tanks.fuel_tank_number',

                        'tank_sell_lines.*',

                        'settlements.transaction_date',

                        'settlements.settlement_no',

                    ])

                    ->orderBy('settlements.transaction_date', 'DESC');

                if (! empty(request()->settlement_no)) {

                    $query->where(

                        'settlements.settlement_no',

                        request()->settlement_no

                    );

                }

                if (

                    ! empty(request()->start_date) &&

                    ! empty(request()->end_date)

                ) {

                    $query->whereDate(

                        'settlements.transaction_date',

                        '>=',

                        request()->start_date

                    );

                    $query->whereDate(

                        'settlements.transaction_date',

                        '<=',

                        request()->end_date

                    );

                }

                $settlements = Datatables::of($query)

                    ->addColumn(

                        'action',

                        function ($row) {

                            $html =

                                '<button data-href="'.

                                action(

                                    "\Modules\PetroGeneral\Http\Controllers\SettlementController@editMeterSale",

                                    [$row->id]

                                ).

                                '" data-container=".fuel_tank_modal" class="btn btn-primary btn-xs btn-modal edit_reference_button"><i class="fa fa-pencil-square-o"></i>'.

                                trans('messages.edit').

                                '</button>';

                            return $html;

                        }

                    )

                    ->editColumn(

                        'created_at',

                        '{{@format_datetime($created_at)}}'

                    )

                    ->editColumn(

                        'transaction_date',

                        '{{@format_date($transaction_date)}}'

                    )

                    ->removeColumn('id');

                return $settlements

                    ->rawColumns(['action'])

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

        return view('petrogeneral::edit_settlement_date.edit')->with(

            compact('meter_sale', 'transaction')

        );

    }

    public function updateMeterSale($id, Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $data = [

                'created_at' => $request->created_at,

            ];

            TankSellLine::where('id', $id)->update($data);

            $meter_sale = TankSellLine::findOrFail($id);

            $transaction = Transaction::findOrFail($meter_sale->transaction_id);

            $transaction->created_at = $request->created_at;

            $transaction->save();

            $output = [

                'success' => 1,

                'msg' => __('lang_v1.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => 0,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return redirect()

            ->back()

            ->with('status', $output);

    }

    public function saveMeterSale(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $business_locations = BusinessLocation::forDropdown($business_id);

            $default_location = current(

                array_keys($business_locations->toArray())

            );

            DB::beginTransaction();

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                return [

                    'success' => false,

                    'msg' => __('petrogeneral::lang.date_greater_than_day_end'),

                ];

            }

            if (empty($settlement_exist) || empty($settlement_exist->id)) {
                DB::rollBack();

                return [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            $pump = Pump::where('id', $request->pump_id)->first();

            $tank_id = $pump->fuel_tank_id ?? '';

            // Modified by Engr. Alex -- task 7889: fallback — if frontend did not send product_id,
            // resolve it from the pump's fuel tank so sell lines are always created correctly
            $product_id = $request->product_id;
            if (empty($product_id) && !empty($tank_id)) {
                $fuel_tank = \Modules\PetroGeneral\Entities\FuelTank::find($tank_id);
                if ($fuel_tank && !empty($fuel_tank->product_id)) {
                    $product_id = $fuel_tank->product_id;
                }
            }

            $data = [

                'business_id' => $business_id,

                'settlement_no' => $settlement_exist->id,

                'product_id' => $product_id,

                'pump_id' => $request->pump_id,

                'starting_meter' => $request->starting_meter,

                'closing_meter' => $pump->bulk_sale_meter == 0 ? $request->closing_meter : '',

                'price' => $request->price,

                'qty' => $request->qty,

                'discount' => $request->discount,

                'discount_type' => $request->discount_type,

                'discount_amount' => $request->discount_amount,

                'testing_qty' => $request->testing_qty,

                'sub_total' => $request->sub_total,

                'shift_id' => $request->shift_id,

            ];

            if (Schema::hasColumn('meter_sales', 'mechanical_last_meter')) {
                $data['mechanical_last_meter'] = $request->mechanical_last_meter;
            }

            if (Schema::hasColumn('meter_sales', 'mechanical_digital_last_meter')) {
                $data['mechanical_digital_last_meter'] = $request->mechanical_digital_last_meter;
            }

            if (Schema::hasColumn('meter_sales', 'mechanical_meter_difference')) {
                $data['mechanical_meter_difference'] = $request->mechanical_meter_difference;
            }

            $existing_meter_sale = MeterSale::where('business_id', $business_id)
                ->where('settlement_no', $settlement_exist->id)
                ->where('pump_id', $request->pump_id)
                ->where('shift_id', $request->shift_id)
                ->where('starting_meter', $request->starting_meter)
                ->where('closing_meter', $pump->bulk_sale_meter == 0 ? $request->closing_meter : '')
                ->where('qty', $request->qty)
                ->where('price', $request->price)
                ->orderByDesc('id')
                ->first();

            if (! empty($existing_meter_sale)) {
                $this->updateSettlementTotalAmount($settlement_exist->id);
                $table_html = $this->getMeterSaleTableHtml(
                    $settlement_exist->id,
                    $this->extractRequestedSettlementShiftIds($request)
                );

                DB::commit();

                return [
                    'success' => true,
                    'msg' => __('petrogeneral::lang.success'),
                    'meter_sale_id' => $existing_meter_sale->id,
                    'settlement_id' => $settlement_exist->id,
                    'settlement_no' => $settlement_exist->settlement_no,
                    'amount' => $existing_meter_sale->discount_amount,
                    'table_html' => $table_html,
                ];
            }

            $meter_sale = MeterSale::create($data);

            if (! empty($request->is_from_pumper)) {

                logger($request->pumper_entry_id);

                logger($request->assignment_id);

                PumperDayEntry::where('id', $request->pumper_entry_id)

                    ->update([

                        'settlement_no' => $settlement_exist->settlement_no,

                        'settlement_added_by' => auth()->user()->id,

                        'closed_in_settlement' => 0,

                    ]);

                PumpOperatorAssignment::where(

                    'id',

                    $request->assignment_id

                )->update(['closed_in_settlement' => 0]);

            }

            Settlement::where('id', $settlement_exist->id)->update([

                'is_edit' => request()->is_edit,

            ]);

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

                    if ($pump_operator->commission_type == 'percentage') {

                        $commission_amount =

                            ($discounted_amount *

                                $pump_operator->commission_ap) /

                            100;

                    }

                    if ($pump_operator->commission_type == 'fixed') {

                        $commission_amount =

                            $request->qty * $pump_operator->commission_ap;

                    }

                    $commission_data = [

                        'pump_operator_id' => $settlement_exist->pump_operator_id,

                        'meter_sale_id' => $meter_sale->id,

                        'transaction_date' => $settlement_exist->transaction_date,

                        'amount' => $commission_amount,

                        'type' => $pump_operator->commission_type,

                        'value' => $pump_operator->commission_ap,

                    ];

                    PumpOperatorCommission::create($commission_data);

                }

            }

            Pump::where('id', $request->pump_id)->update([

                'starting_meter' => $request->starting_meter,

                'last_meter_reading' => $request->closing_meter,

            ]);

            Settlement::where('id', $settlement_exist->id)->update([
                'is_edit' => request()->is_edit,
            ]);

            $this->updateSettlementTotalAmount($settlement_exist->id);

            $table_html = $this->getMeterSaleTableHtml(
                $settlement_exist->id,
                $this->extractRequestedSettlementShiftIds($request)
            );

            DB::commit();

            $output = [

                'success' => true,

                'msg' => 'success',

                'meter_sale_id' => $meter_sale->id,

                'settlement_id' => $settlement_exist->id,
                'settlement_no' => $settlement_exist->settlement_no,
                'amount' => $request->discount_amount,
                'table_html' => $table_html

            ];

        } catch (\Exception $e) {

            dd($e);

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteMeterSale($id)
    {

        try {

            $meter_sale = MeterSale::where('id', $id)->first();
            if (!$meter_sale) {
                return ['success' => false, 'msg' => 'Meter sale not found'];
            }
            Settlement::where('id', $meter_sale->settlement_no)->update([
                'is_edit' => request()->is_edit,
            ]);

            $settlement_id = $meter_sale->settlement_no;
            $amount = $meter_sale->discount_amount;
            $starting_meter = $meter_sale->starting_meter;
            $closing_meter = $meter_sale->closing_meter;
            $pump = Pump::where('id', $meter_sale->pump_id)->first();
            $tank_id = $pump->fuel_tank_id;
            FuelTank::where('id', $tank_id)->increment(
                'current_balance',
                $meter_sale->qty
            );
            $meter_sale->delete();

            $this->updateSettlementTotalAmount($settlement_id);
            $table_html = $this->getMeterSaleTableHtml(
                $settlement_id,
                $this->extractRequestedSettlementShiftIds(request())
            );

            $pump->last_meter_reading = $starting_meter; // reset back to previous starting meter

            $previous_meter_sale = MeterSale::where('pump_id', $pump->id)

                ->orderBy('id', 'desc')

                ->first();

            if (! empty($previous_meter_sale)) {

                $pump->starting_meter = $previous_meter_sale->starting_meter;

            }

            $pump->save();

            $pump_name = $pump->pump_name;

            $pump_id = $pump->id;

            // delete pump operator commission

            PumpOperatorCommission::where('meter_sale_id', $id)->delete();

            $output = [

                'success' => true,

                'amount' => $amount,

                'pump_name' => $pump_name,

                'pump_id' => $pump_id,

                'msg' => __('petrogeneral::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**
     * save other sale data in db







     * @param product_id







     * @return Response
     */

    public function getMeterSaleForm($id)
    {

        $output = [

            'success' => false,

            'msg' => __('messages.something_went_wrong'),

        ];

        try {

            $meter_sale = MeterSale::where('id', $id)->first();

            if ($meter_sale) {

                $active_settlement = Settlement::where(

                    'id',

                    $meter_sale->settlement_no

                )->first();

                $discount_types = [

                    'fixed' => 'Fixed',

                    'percentage' => 'Percentage',

                ];

                $already_pumps = MeterSale::where(

                    'settlement_no',

                    $active_settlement->id

                )

                    ->pluck('pump_id')

                    ->toArray();

                $business_id = $meter_sale->business_id;

                if (request()->action_type == 'cancel') {

                    $meter_sale = [];

                } else {

                    $already_pumps = array_diff($already_pumps, [

                        $meter_sale->pump_id,

                    ]);

                    $already_pumps = array_values($already_pumps);

                    $pump = Pump::where('id', $meter_sale->pump_id)->first();
                    $meter_sale = $meter_sale->toArray();
                    // Show computed sold qty in form (closing - starting - testing) so edit form shows correct value
                    if (! $pump || $pump->bulk_sale_meter != 1) {
                        $meter_sale['qty'] = (float) $meter_sale['closing_meter'] - (float) $meter_sale['starting_meter'] - (float) ($meter_sale['testing_qty'] ?? 0);
                    }

                }

                $pump_nos = Pump::where('business_id', $business_id)

                    ->whereNotIn('id', $already_pumps)

                    ->pluck('pump_name', 'id');

                $show_mechanical_meter_too = $this->shouldShowMechanicalMeterToo($business_id);

                $html = view(

                    'petrogeneral::settlement.partials.meter_sale_form',

                    compact('meter_sale', 'pump_nos', 'discount_types', 'show_mechanical_meter_too')

                )->render();

                $output = [

                    'success' => true,

                    'html' => $html,

                    'msg' => __('petrogeneral::lang.success'),

                ];

            }

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

            ];

        }

        return $output;

    }

    public function updateSettlementMeterSale($id, Request $request)
    {

        try {

            $meter_sale = MeterSale::where('id', $id)->first();
            if (!$meter_sale) {
                return ['success' => false, 'msg' => 'Meter sale not found'];
            }

            if ($this->laterPumpSettlementExists($meter_sale)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'This pump is already used in a later settlement and cannot be edited.',
                ]);
            }

            $pumpOld = Pump::where('id', $meter_sale->pump_id)->first();

            $tank_id_old = $pumpOld->fuel_tank_id;

            FuelTank::where('id', $tank_id_old)->increment(

                'current_balance',

                $meter_sale->qty

            );

            $amount = $meter_sale->discount_amount;

            $starting_meter_old = $meter_sale->starting_meter;

            $closing_meter_old = $meter_sale->closing_meter;

            $pumpOld->last_meter_reading = $starting_meter_old; // reset back to previous starting meter

            $previous_meter_sale = MeterSale::where('pump_id', $pumpOld->id)

                ->orderBy('id', 'desc')

                ->first();

            if (! empty($previous_meter_sale)) {

                $pumpOld->starting_meter = $previous_meter_sale->starting_meter;

            }

            $pumpOld->save();

            $pumpNew = Pump::where('id', $request->pump_id)->first();

            $tank_id_new = $pumpNew->fuel_tank_id;

            FuelTank::where('id', $tank_id_new)->decrement(

                'current_balance',

                $request->qty

            );

            $pumpNew->starting_meter = $request->starting_meter;

            $pumpNew->last_meter_reading = $request->closing_meter;

            $pumpNew->save();

            $data = [

                'pump_id' => $pumpNew->id,
                'product_id' => $pumpNew->product_id,

                'starting_meter' => $request->starting_meter,

                'closing_meter' => $pumpNew->bulk_sale_meter == 0

                    ? $request->closing_meter

                    : '',

                'price' => $request->price,

                'qty' => $request->qty,

                'discount' => $request->discount,

                'discount_type' => $request->discount_type,

                'discount_amount' => $request->discount_amount,

                'testing_qty' => $request->testing_qty,

                'sub_total' => $request->sub_total,

            ];

            if (Schema::hasColumn('meter_sales', 'mechanical_last_meter')) {
                $data['mechanical_last_meter'] = $request->mechanical_last_meter;
            }

            if (Schema::hasColumn('meter_sales', 'mechanical_digital_last_meter')) {
                $data['mechanical_digital_last_meter'] = $request->mechanical_digital_last_meter;
            }

            if (Schema::hasColumn('meter_sales', 'mechanical_meter_difference')) {
                $data['mechanical_meter_difference'] = $request->mechanical_meter_difference;
            }

            MeterSale::where('id', $id)->update($data);

            $settlement_exist = Settlement::where(
                'id',
                $meter_sale->settlement_no
            )->first();

            $this->updateSettlementTotalAmount($settlement_exist->id);
            $table_html = $this->getMeterSaleTableHtml(
                $settlement_exist->id,
                $this->extractRequestedSettlementShiftIds($request)
            );

            $pump_operator = PumpOperator::find(
                $settlement_exist->pump_operator_id
            );
            
            // ... (commission logic remains)
            if (! empty($pump_operator)) {
                // ... (commission logic code)
                if (
                    ! empty($pump_operator->commission_type) &&
                    ! empty($pump_operator->commission_ap)
                ) {
                    $commission_amount = 0;
                    $discounted_amount = $request->discount_amount;
                    if ($pump_operator->commission_type == 'percentage') {
                        $commission_amount =
                            ($discounted_amount *
                                $pump_operator->commission_ap) /
                            100;
                    }
                    if ($pump_operator->commission_type == 'fixed') {
                        $commission_amount =
                            $request->qty * $pump_operator->commission_ap;
                    }
                    $commission_data = [
                        'transaction_date' => $settlement_exist->transaction_date,
                        'amount' => $commission_amount,
                        'type' => $pump_operator->commission_type,
                        'value' => $pump_operator->commission_ap,
                    ];
                    PumpOperatorCommission::where([
                        'pump_operator_id' => $settlement_exist->pump_operator_id,
                        'meter_sale_id' => $meter_sale->id,
                    ])
                        ->update($commission_data);
                }
            }

            $output = [
                'success' => true,
                'msg' => 'success',
                'meter_sale_id' => $meter_sale->id,
                'settlement_id' => $settlement_exist->id,
                'amount' => $request->discount_amount,
                'meter_sale_total' => (float) $settlement_exist->fresh()->meter_sales->sum('discount_amount'),
                'table_html' => $table_html
            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                'Line: '.

                $e->getLine().

                'Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return response()->json($output);

    }
}

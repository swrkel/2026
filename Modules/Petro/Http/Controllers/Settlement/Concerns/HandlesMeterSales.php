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
 * Meter sale entry, mechanical meter comparison, display and totals.
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
 * Methods here: meter_sales, editMeterSale, updateMeterSale, saveMeterSale, deleteMeterSale, mechanicalMeter, shouldShowMechanicalMeterToo, getMechanicalMeterDifferenceSummary, calculatePositiveMeterSaleAmounts, getMeterSaleForm, updateSettlementMeterSale, getMeterSaleTableHtml, attachUnsettledRteMeterSalesToSettlement
 */
trait HandlesMeterSales
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

                                    "\Modules\Petro\Http\Controllers\SettlementController@editMeterSale",

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

                    ->editColumn('transaction_date', function ($row) {
                        $selectedDate = $row->transaction_date ?: $row->created_at;

                        return ! empty($selectedDate)
                            ? $this->transactionUtil->format_date($selectedDate)
                            : '';
                    })

                    ->removeColumn('id');

                return $settlements

                    ->rawColumns(['action'])

                    ->make(true);

            } catch (\Exception $e) {

                \Log::emergency('Petro settlement list failed: ' . $e->getMessage());

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

        return view('petro::edit_settlement_date.edit')->with(

            compact('meter_sale', 'transaction')

        );

    }

    public function updateMeterSale($id, Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id')
                ?: $request->session()->get('user.business_id')
                ?: optional(auth()->user())->business_id;

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

            $business_id = $request->session()->get('business.id')
                ?: $request->session()->get('user.business_id')
                ?: optional(auth()->user())->business_id;

            $business_locations = BusinessLocation::forDropdown($business_id);

            $default_location = current(

                array_keys($business_locations->toArray())

            );

            DB::beginTransaction();

            $settlement_exist = $this->createSettlementIfNotExist($request);

            if (is_int($settlement_exist) && $settlement_exist == 406) {

                DB::rollBack();

                return [

                    'success' => false,

                    'msg' => __('petro::lang.date_greater_than_day_end'),

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


            if (empty($pump)) {
                DB::rollBack();

                return [
                    'success' => false,
                    'msg' => 'The selected pump could not be found. Please select the pump again.',
                ];
            }

            $discount_type = in_array($request->discount_type, ['fixed', 'percentage'], true)
                ? $request->discount_type
                : 'fixed';
            $discount = is_numeric($request->discount) ? (float) $request->discount : 0;

            $request->merge([
                'discount_type' => $discount_type,
                'discount' => $discount,
            ]);

            $tank_id = $pump->fuel_tank_id ?? '';

            // Modified by Engr. Alex -- task 7889: fallback — if frontend did not send product_id,
            // resolve it from the pump's fuel tank so sell lines are always created correctly
            $product_id = $request->product_id;
            if (empty($product_id) && !empty($tank_id)) {
                $fuel_tank = \Modules\Petro\Entities\FuelTank::find($tank_id);
                if ($fuel_tank && !empty($fuel_tank->product_id)) {
                    $product_id = $fuel_tank->product_id;
                }
            }

            if (empty($product_id)) {
                DB::rollBack();

                return [
                    'success' => false,
                    'msg' => 'The selected pump is not linked to a valid product. Please check the pump and fuel tank setup.',
                ];
            }

            $positive_meter_amounts = $this->calculatePositiveMeterSaleAmounts(
                $request->qty,
                $request->price,
                $request->discount,
                $request->discount_type
            );

            $data = [

                'business_id' => $business_id,

                'settlement_no' => $settlement_exist->id,

                'product_id' => $product_id,

                'pump_id' => $request->pump_id,

                'starting_meter' => $request->starting_meter,

                'closing_meter' => $pump->bulk_sale_meter == 0 ? $request->closing_meter : '',

                'price' => abs((float) $request->price),

                'qty' => $positive_meter_amounts['qty'],

                'discount' => $request->discount,

                'discount_type' => $discount_type,

                'discount_amount' => $positive_meter_amounts['discount_amount'],

                'testing_qty' => abs((float) $request->testing_qty),

                'sub_total' => $positive_meter_amounts['sub_total'],

                'shift_id' => $request->shift_id,

            ];

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_last_meter')) {
                $data['mechanical_last_meter'] = $request->mechanical_last_meter;
            }

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_digital_last_meter')) {
                $data['mechanical_digital_last_meter'] = $request->mechanical_digital_last_meter;
            }

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_meter_difference')) {
                $data['mechanical_meter_difference'] = $request->mechanical_meter_difference;
            }

            $existing_meter_sale = MeterSale::where('business_id', $business_id)
                ->where('settlement_no', $settlement_exist->id)
                ->where('pump_id', $request->pump_id)
                ->where('shift_id', $request->shift_id)
                ->where('starting_meter', $request->starting_meter)
                ->where('closing_meter', $pump->bulk_sale_meter == 0 ? $request->closing_meter : '')
                ->where('qty', $positive_meter_amounts['qty'])
                ->where('price', abs((float) $request->price))
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
                    'msg' => __('petro::lang.success'),
                    'meter_sale_id' => $existing_meter_sale->id,
                    'settlement_id' => $settlement_exist->id,
                    'settlement_no' => $settlement_exist->settlement_no,
                    'amount' => abs((float) $existing_meter_sale->discount_amount),
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

                    $discounted_amount = $positive_meter_amounts['discount_amount'];

                    if ($pump_operator->commission_type == 'percentage') {

                        $commission_amount =

                            ($discounted_amount *

                                $pump_operator->commission_ap) /

                            100;

                    }

                    if ($pump_operator->commission_type == 'fixed') {

                        $commission_amount =

                            $positive_meter_amounts['qty'] * $pump_operator->commission_ap;

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
                'amount' => $positive_meter_amounts['discount_amount'],
                'table_html' => $table_html

            ];

        } catch (\Exception $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

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

                'msg' => __('petro::lang.success'),

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

    public function mechanicalMeter($id)
    {
        $business_id = $this->getCurrentBusinessIdForDirectSettlement();

        abort_if(empty($business_id), 403, __('messages.unauthorized_action'));

        $settlement = Settlement::where('business_id', $business_id)
            ->with(['meter_sales.pump'])
            ->findOrFail($id);

        $meter_sales = $settlement->meter_sales;

        return view('petro::settlement.mechanical_meter_modal')
            ->with(compact('settlement', 'meter_sales'));
    }

    /**
     * Direct Settlement action permissions in the Petro module.
     *
     * Keep the Petro-specific permission as the primary rule and retain the
     * legacy settlement.edit permission as a compatibility fallback.
     */

    protected function shouldShowMechanicalMeterToo($business_id): bool
    {
        /*
         * ZIP 050:
         * Superadmin / All Business / Manage / Petro Module / Show Mechanical Meter
         * controls whether the mechanical meter feature and its conditions apply.
         *
         * Default must be enabled for old businesses where the key is missing.
         */
        $subscription = Subscription::where('business_id', $business_id)
            ->orderBy('end_date', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $package_details = [];
        if (! empty($subscription) && ! empty($subscription->package_details)) {
            $package_details = is_array($subscription->package_details)
                ? $subscription->package_details
                : (json_decode($subscription->package_details, true) ?: []);
        }

        if (array_key_exists('show_mechanical_meter', $package_details) && empty($package_details['show_mechanical_meter'])) {
            return false;
        }

        $business_ids = array_values(array_unique(array_filter([
            $business_id,
            request()->session()->get('business.id'),
            request()->session()->get('user.business_id'),
        ])));

        $latest_setting = CustomerBillVatPrefix::whereIn('business_id', $business_ids)
            ->where('prefix', self::SHOW_MECHANICAL_METER_SETTING)
            ->latest('id')
            ->first();

        return empty($latest_setting) || (int) $latest_setting->starting_no === 1;
    }

    protected function getMechanicalMeterDifferenceSummary(Settlement $settlement): string
    {
        if (! $this->shouldShowMechanicalMeterToo($settlement->business_id)) {
            return '';
        }

        if (! \Modules\Petro\Support\SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_meter_difference')) {
            return '';
        }

        return MeterSale::leftJoin('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
            ->where('meter_sales.settlement_no', $settlement->id)
            ->whereNotNull('meter_sales.mechanical_meter_difference')
            ->select('meter_sales.mechanical_meter_difference', 'pumps.pump_name', 'pumps.pump_no')
            ->get()
            ->map(function ($meter_sale) {
                $pump_name = $meter_sale->pump_name ?: $meter_sale->pump_no;

                return trim(($pump_name ?: 'Pump') . ': ' . number_format((float) $meter_sale->mechanical_meter_difference, 3, '.', ''));
            })
            ->filter()
            ->implode(', ');
    }

    private function calculatePositiveMeterSaleAmounts($qty, $price, $discount, $discount_type)
    {
        $qty = abs((float) $qty);
        $price = abs((float) $price);
        $sub_total = round($qty * $price, 4);
        $discount_value = abs((float) $discount);
        $discount_amount = 0.0;

        if ($discount_type === 'percentage') {
            $discount_amount = $sub_total * ($discount_value / 100);
        } elseif (!empty($discount_type) && strtolower((string) $discount_type) !== 'none') {
            $discount_amount = $discount_value;
        }

        $net_amount = max(0, $sub_total - $discount_amount);

        return [
            'qty' => $qty,
            'sub_total' => $sub_total,
            'discount_amount' => round($net_amount, 4),
        ];
    }

    /**
     * save meter sale to db







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

                    'petro::settlement.partials.meter_sale_form',

                    compact('meter_sale', 'pump_nos', 'discount_types', 'show_mechanical_meter_too')

                )->render();

                $output = [

                    'success' => true,

                    'html' => $html,

                    'msg' => __('petro::lang.success'),

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

            $positive_meter_amounts = $this->calculatePositiveMeterSaleAmounts(
                $request->qty,
                $request->price,
                $request->discount,
                $request->discount_type
            );

            FuelTank::where('id', $tank_id_new)->decrement(

                'current_balance',

                $positive_meter_amounts['qty']

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

                'price' => abs((float) $request->price),

                'qty' => $positive_meter_amounts['qty'],

                'discount' => $request->discount,

                'discount_type' => $request->discount_type,

                'discount_amount' => $positive_meter_amounts['discount_amount'],

                'testing_qty' => abs((float) $request->testing_qty),

                'sub_total' => $positive_meter_amounts['sub_total'],

            ];

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_last_meter')) {
                $data['mechanical_last_meter'] = $request->mechanical_last_meter;
            }

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_digital_last_meter')) {
                $data['mechanical_digital_last_meter'] = $request->mechanical_digital_last_meter;
            }

            if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_meter_difference')) {
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
                    $discounted_amount = $positive_meter_amounts['discount_amount'];
                    if ($pump_operator->commission_type == 'percentage') {
                        $commission_amount =
                            ($discounted_amount *
                                $pump_operator->commission_ap) /
                            100;
                    }
                    if ($pump_operator->commission_type == 'fixed') {
                        $commission_amount =
                            $positive_meter_amounts['qty'] * $pump_operator->commission_ap;
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
                'amount' => $positive_meter_amounts['discount_amount'],
                'meter_sale_total' => (float) $settlement_exist->fresh()->meter_sales->sum(function ($sale) { return abs((float) $sale->discount_amount); }),
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

    public function getMeterSaleTableHtml($settlement_id, array $shift_ids = [])
    {
        $active_settlement = Settlement::with([
            'meter_sales' => function ($query) use ($shift_ids) {
                if (! empty($shift_ids)) {
                    $query->whereIn('shift_id', $shift_ids);
                }
            },
            'meter_sales.pump',
            'meter_sales.product',
        ])->find($settlement_id);
        
        $business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->first();
        $pos_settings = json_decode($business->pos_settings, true);
        $currency_precision = !empty($pos_settings['currency_precision']) ? $pos_settings['currency_precision'] : 2;

        $discount_types = [
            '' => __('petro::lang.none'),
            'fixed' => __('petro::lang.fixed'),
            'percentage' => __('petro::lang.percentage'),
        ];

        $already_pumps = MeterSale::where('settlement_no', $settlement_id)
            ->when(! empty($shift_ids), function ($query) use ($shift_ids) {
                $query->whereIn('shift_id', $shift_ids);
            })
            ->pluck('pump_id')
            ->toArray();

        $pump_nos = Pump::where('business_id', $business_id)
            ->whereNotIn('id', $already_pumps)
            ->pluck('pump_name', 'id');

        $meeter_precision = 3;

        return view('petro::settlement.partials.meter_sale', compact('active_settlement', 'currency_precision', 'discount_types', 'pump_nos', 'meeter_precision'))->render();
    }

    /**
     * Link meter_sales created from Real Time / payment Enter Meters (settlement_no NULL) to this draft settlement
     * when pump/shift matches an assignment for the settlement's pump operator.
     */

    protected function attachUnsettledRteMeterSalesToSettlement(?Settlement $settlement): void
    {
        if (! $settlement || empty($settlement->id) || empty($settlement->pump_operator_id) || empty($settlement->business_id)) {
            return;
        }

        $business_id = (int) $settlement->business_id;

        MeterSale::where('business_id', $business_id)
            ->where(function ($q) {
                $q->whereNull('settlement_no')->orWhere('settlement_no', '');
            })
            ->whereExists(function ($sub) use ($settlement, $business_id) {
                $sub->select(DB::raw(1))
                    ->from('pump_operator_assignments')
                    ->whereColumn('pump_operator_assignments.pump_id', 'meter_sales.pump_id')
                    ->whereColumn('pump_operator_assignments.shift_id', 'meter_sales.shift_id')
                    ->where('pump_operator_assignments.business_id', $business_id)
                    ->where('pump_operator_assignments.pump_operator_id', $settlement->pump_operator_id);
            })
            ->update(['settlement_no' => $settlement->id]);
    }
}

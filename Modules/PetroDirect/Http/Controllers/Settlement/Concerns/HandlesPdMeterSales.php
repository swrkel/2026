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
 * Meter sale entry, display and totals.
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

                                    "\Modules\PetroDirect\Http\Controllers\SettlementController@editMeterSale",

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
                Log::error('Petro Direct meter sales list failed', [
                    'business_id' => $request->session()->get('business.id'),
                    'message' => $e->getMessage(),
                ]);

                return response()->json([
                    'draw' => (int) $request->input('draw', 0),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => __('messages.something_went_wrong'),
                ], 500);
            }

        }

    }

    public function editMeterSale($id)
    {

        $meter_sale = TankSellLine::findOrFail($id);

        $transaction = Transaction::findOrFail($meter_sale->transaction_id);

        return view('petrodirect::edit_settlement_date.edit')->with(

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
        // IS1761: Direct Settlement meter rows are manual PetroDirect rows only.
        $request->merge([
            'shift_id' => 0,
            'shift_ids' => [],
            'is_from_pumper' => 0,
            'assignment_id' => 0,
            'pumper_entry_id' => 0,
        ]);

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

                    'msg' => __('petrodirect::lang.date_greater_than_day_end'),

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

            $qty = abs((float) str_replace(',', '', (string) $request->qty));
            $price = abs((float) str_replace(',', '', (string) $request->price));
            $testing_qty = abs((float) str_replace(',', '', (string) $request->testing_qty));

            /*
             * IS1958 #2: derive the SOLD quantity here rather than trusting the
             * posted value.
             *
             * Dispensed qty is auto-calculated from the meters
             * (closing - starting) and testing is entered separately, so
             *     sold = dispensed - testing
             *
             * The form was posting the DISPENSED figure as qty, so testing was
             * being stored and reported as sold: the stock history Sold Qty,
             * the location Qty Out and the ledger row all included litres that
             * were only tested, never sold.
             *
             * The meters are the authoritative source and are already on the
             * request, so the value is recomputed from them. It is only applied
             * when both meters are present and give a sane result - bulk-sale
             * pumps have no closing meter, and those keep the posted qty.
             */
            if ($testing_qty > 0
                && $request->starting_meter !== null
                && $request->closing_meter !== null
                && $request->closing_meter !== '') {

                $startingMeter = (float) str_replace(',', '', (string) $request->starting_meter);
                $closingMeter = (float) str_replace(',', '', (string) $request->closing_meter);
                $dispensed = $closingMeter - $startingMeter;

                // Only trust the meters when they are consistent with what was
                // posted: dispensed must cover the testing, and qty must be
                // either the dispensed figure or the sold figure already.
                if ($dispensed > 0 && $dispensed >= $testing_qty) {
                    $soldFromMeters = round($dispensed - $testing_qty, 4);

                    if (abs($qty - $dispensed) < 0.0001) {
                        // Form sent dispensed - strip the testing litres.
                        $qty = $soldFromMeters;
                    }
                }
            }
            $sub_total = round($qty * $price, 4);
            $discount_value = $discount_type === 'percentage'
                ? $sub_total * ($discount / 100)
                : $discount;
            $discount_amount = round(max(0, $sub_total - $discount_value), 4);

            if ($qty <= 0 || $price <= 0) {
                DB::rollBack();

                return [
                    'success' => false,
                    'msg' => 'Sold Qty and Unit Price must be greater than zero.',
                ];
            }

            $request->merge([
                'qty' => $qty,
                'price' => $price,
                'testing_qty' => $testing_qty,
                'sub_total' => $sub_total,
                'discount_amount' => $discount_amount,
            ]);

            $tank_id = $pump->fuel_tank_id ?? '';

            // Modified by Engr. Alex -- task 7889: fallback — if frontend did not send product_id,
            // resolve it from the pump's fuel tank so sell lines are always created correctly
            $product_id = $request->product_id;
            if (empty($product_id) && !empty($tank_id)) {
                $fuel_tank = \Modules\PetroDirect\Entities\FuelTank::find($tank_id);
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

                'discount_type' => $discount_type,

                'discount_amount' => $request->discount_amount,

                'testing_qty' => $request->testing_qty,

                'sub_total' => $request->sub_total,

                // A Direct Settlement row must not carry a Pumper/Petro PD
                // operational shift id.  Direct's optional shift label is
                // retained on the settlement itself as a DST marker.
                'shift_id' => 0,

            ];

            if (SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_last_meter')) {
                $data['mechanical_last_meter'] = $request->mechanical_last_meter;
            }

            if (SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_digital_last_meter')) {
                $data['mechanical_digital_last_meter'] = $request->mechanical_digital_last_meter;
            }

            if (SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_meter_difference')) {
                $data['mechanical_meter_difference'] = $request->mechanical_meter_difference;
            }

            $existing_meter_sale = MeterSale::where('business_id', $business_id)
                ->where('settlement_no', $settlement_exist->id)
                ->where('pump_id', $request->pump_id)
                ->petroDirectOwned()
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
                    'msg' => __('petrodirect::lang.success'),
                    'meter_sale_id' => $existing_meter_sale->id,
                    'settlement_id' => $settlement_exist->id,
                    'settlement_no' => $settlement_exist->settlement_no,
                    'amount' => abs((float) $existing_meter_sale->discount_amount),
                    'table_html' => $table_html,
                    'pump_nos' => $this->getAvailableDirectSettlementPumps(
                        (int) $business_id,
                        ! empty($request->location_id) ? (int) $request->location_id : null,
                        $settlement_exist->id
                    ),
                ];
            }

            // S676: a pump may appear only once in a Direct Settlement.
            // The exact-match check above remains idempotent for a repeated POST,
            // while this broader guard blocks a second row for the same pump with
            // changed meter/price values or a stale browser dropdown.
            $pump_already_added = MeterSale::where('business_id', $business_id)
                ->where('settlement_no', $settlement_exist->id)
                ->where('pump_id', $request->pump_id)
                ->petroDirectOwned()
                ->exists();

            if ($pump_already_added) {
                DB::rollBack();

                return [
                    'success' => false,
                    'msg' => 'This Pump No. has already been added to this Direct Settlement. Please select another pump.',
                    'settlement_id' => $settlement_exist->id,
                    'settlement_no' => $settlement_exist->settlement_no,
                    'pump_nos' => $this->getAvailableDirectSettlementPumps(
                        (int) $business_id,
                        ! empty($request->location_id) ? (int) $request->location_id : null,
                        $settlement_exist->id
                    ),
                ];
            }

            $meter_sale = MeterSale::create($data);

            /*
             * IS1839-PENDING-ISOLATION:
             * Ignore legacy is_from_pumper linkage in Petro Direct. Direct meter
             * sales must never reopen or relink Pumper Dashboard/Petro PD rows.
             */

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
                'table_html' => $table_html,
                'pump_nos' => $this->getAvailableDirectSettlementPumps(
                    (int) $business_id,
                    ! empty($request->location_id) ? (int) $request->location_id : null,
                    $settlement_exist->id
                )

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

            $meter_sale = MeterSale::petroDirectOwned()->where('id', $id)->first();
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

            $previous_meter_sale = MeterSale::petroDirectOwned()->where('pump_id', $pump->id)

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

                'msg' => __('petrodirect::lang.success'),

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

            $meter_sale = MeterSale::petroDirectOwned()->where('id', $id)->first();

            if ($meter_sale) {

                $active_settlement = Settlement::where(

                    'id',

                    $meter_sale->settlement_no

                )->first();

                $discount_types = [

                    'fixed' => 'Fixed',

                    'percentage' => 'Percentage',

                ];

                $already_pumps = MeterSale::petroDirectOwned()->where(

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

                    'petrodirect::settlement.partials.meter_sale_form',

                    compact('meter_sale', 'pump_nos', 'discount_types', 'show_mechanical_meter_too')

                )->render();

                $output = [

                    'success' => true,

                    'html' => $html,

                    'msg' => __('petrodirect::lang.success'),

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

            $meter_sale = MeterSale::petroDirectOwned()->where('id', $id)->first();
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

            $previous_meter_sale = MeterSale::petroDirectOwned()->where('pump_id', $pumpOld->id)

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

            if (SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_last_meter')) {
                $data['mechanical_last_meter'] = $request->mechanical_last_meter;
            }

            if (SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_digital_last_meter')) {
                $data['mechanical_digital_last_meter'] = $request->mechanical_digital_last_meter;
            }

            if (SchemaCapabilityCache::hasColumn('meter_sales', 'mechanical_meter_difference')) {
                $data['mechanical_meter_difference'] = $request->mechanical_meter_difference;
            }

            MeterSale::petroDirectOwned()->where('id', $id)->update($data);

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

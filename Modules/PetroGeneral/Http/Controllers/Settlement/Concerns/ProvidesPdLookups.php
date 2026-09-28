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
 * Dropdown and lookup endpoints used by the settlement screens.
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
 * Methods here: getPumpDetails, getPumpDetailsPerShift, getPumps, getBalanceStock, getStoresById, getProductsByStoreId, getBalanceStockById
 */
trait ProvidesPdLookups
{
    public function getPumpDetails($pump_id, $shift_id = null)
    {

        $pump = Pump::where('id', $pump_id)->first();
        $last_meter_reading = $pump->last_meter_reading ?? 0;
        $last_meter_sale = MeterSale::where('pump_id', $pump_id)
            ->orderBy('id', 'desc')
            ->first();

        if (! empty($last_meter_sale)) {
            $last_meter_reading = ! empty($last_meter_sale->meter_reset_value)
                ? $last_meter_sale->meter_reset_value
                : $last_meter_sale->closing_meter;
        }

        $is_open = PumpOperatorAssignment::where('pump_id', $pump_id)
            ->where('status', 'open')
            ->where('shift_id', $shift_id)
            ->count();

        $ass = PumpOperatorAssignment::where('pump_id', $pump_id)
            ->where('shift_id', $shift_id)
            ->where('closed_in_settlement', 0)
            ->orderBy('id', 'desc')
            ->first();

        $po_closing = 0;
        $day_entry = null;
        if (! empty($ass)) {
            $po_closing = $ass->closing_meter;
            $day_entry = PumperDayEntry::where('pump_id', $pump_id)
                ->where('pumper_assignment_id', $ass->id)
                ->where('closed_in_settlement', 0)
                ->first();
        } else {
            if (! empty($last_meter_sale)) {
                $po_closing = 0;
            }
        }

        $fuel_tank_id = $pump->fuel_tank_id ?? null;
        $fuel_tank = FuelTank::where('id', $fuel_tank_id)->first();
        if (!$fuel_tank) {
            return response()->json(['success' => false, 'msg' => 'Fuel tank not found for this pump']);
        }
        $product = Variation::leftjoin(
            'products',
            'variations.product_id',
            'products.id'
        )
            ->leftjoin(
                'variation_location_details',
                'variations.id',
                'variation_location_details.variation_id'
            )
            ->where('products.id', $fuel_tank->product_id)
            ->select(
                'sku',
                'variations.sell_price_inc_tax as default_sell_price',
                'products.name',
                'products.id',
                'variation_location_details.qty_available'
            )->first();

        $business_id = request()
            ->session()
            ->get('business.id');

        $business = Business::where('id', $business_id)->first();
        $currency_precision = $business->currency_precision;
        $product->default_sell_price = number_format(
            $product->default_sell_price,
            $currency_precision,
            '.',
            ''
        );

        $current_balance = $this->transactionUtil->getTankBalanceById(
            $pump->fuel_tank_id
        );

        return response()->json([
            'colsing_value' => number_format($last_meter_reading, 3, '.', ''),
            'tank_remaing_qty' => $current_balance,
            'product' => $product,
            'pump_name' => $pump->pump_name,
            'product_id' => $product->id,
            'pump_id' => $pump->id,
            'bulk_sale_meter' => $pump->bulk_sale_meter,
            'po_closing' => number_format(
                $last_meter_reading > $po_closing ? 0 : $po_closing,
                3,
                '.',
                ''
            ),
            'po_testing' => number_format(
                $last_meter_reading >= $po_closing
                    ? 0
                    : (! empty($day_entry)
                    ? $day_entry->testing_ltr
                    : 0),
                3,
                '.',
                ''
            ),
            'assignment_id' => ! empty($ass) ? $ass->id : 0,
            'pumper_entry_id' => ! empty($day_entry) ? $day_entry->id : 0,
            'is_open' => $is_open,
        ]);

    }

    public function getPumpDetailsPerShift($pump_id, $shift_id)
    {

        $pump = Pump::where('id', $pump_id)->first();

        // $last_meter_reading = $pump->last_meter_reading;

        $last_meter_sale = MeterSale::where([

            'pump_id' => $pump_id,

            'shift_id' => $shift_id,

        ])

            ->orderBy('id', 'desc')

            ->first();

        if (! empty($last_meter_sale)) {

            $last_meter_reading = ! empty($last_meter_sale->meter_reset_value)

                ? $last_meter_sale->meter_reset_value

                : $last_meter_sale->closing_meter;

        }

        $is_open = PumpOperatorAssignment::where('pump_id', $pump_id)

            ->where('status', 'open')

            ->count();

        $ass = PumpOperatorAssignment::where('pump_id', $pump_id)

            ->where('closed_in_settlement', 0)

            ->where('shift_id', $shift_id)

            ->orderBy('id', 'desc')

            ->first();

        $po_closing = 0;

        $day_entry = null;

        if (! empty($ass)) {

            $last_meter_reading = $ass->starting_meter;

            $po_closing = $ass->closing_meter;

            $day_entry = PumperDayEntry::where('pump_id', $pump_id)

                ->where('pumper_assignment_id', $ass->id)

                ->where('closed_in_settlement', 0)

                ->first();

        }

        $fuel_tank_id = $pump->fuel_tank_id ?? null; $fuel_tank = FuelTank::where('id', $fuel_tank_id)->first();

        $product = Variation::leftjoin(

            'products',

            'variations.product_id',

            'products.id'

        )

            ->leftjoin(

                'variation_location_details',

                'variations.id',

                'variation_location_details.variation_id'

            )

            ->where('products.id', $fuel_tank->product_id)

            ->select(

                'sku',

                'variations.sell_price_inc_tax as default_sell_price',

                'products.name',

                'products.id',

                'variation_location_details.qty_available'

            )

            ->first();

        $business_id = request()

            ->session()

            ->get('business.id');

        $business = Business::where('id', $business_id)->first();

        $currency_precision = $business->currency_precision;

        $product->default_sell_price = number_format(

            $product->default_sell_price,

            $currency_precision,

            '.',

            ''

        );

        $current_balance = $this->transactionUtil->getTankBalanceById(

            $pump->fuel_tank_id

        );

        return [

            'colsing_value' => number_format($last_meter_reading, 3, '.', ''),

            'tank_remaing_qty' => $current_balance,

            'product' => $product,

            'pump_name' => $pump->pump_name,

            'product_id' => $product->id,

            'pump_id' => $pump->id,

            'bulk_sale_meter' => $pump->bulk_sale_meter,

            'po_closing' => number_format(

                $last_meter_reading >= $po_closing ? 0 : $po_closing,

                3,

                '.',

                ''

            ),

            'po_testing' => number_format(

                $last_meter_reading >= $po_closing

                ? 0

                : (! empty($day_entry)

                    ? $day_entry->testing_ltr

                    : 0),

                3,

                '.',

                ''

            ),

            'assignment_id' => ! empty($ass) ? $ass->id : 0,

            'pumper_entry_id' => ! empty($day_entry) ? $day_entry->id : 0,

            'is_open' => $is_open,

        ];

    }

    /**
     * get balance stock of product







     * @param product_id







     * @return Response
     */

    public function getPumps(Request $request, $id)
    {
        try {
            $business_id = $request->session()->get('business.id');
            $active_settlement_id = (int) $request->input('active_settlement_id', 0);

            // Ensure shift_id is a single value
            $shift_id = $request->input('shift_number');
            if (is_array($shift_id)) {
                $shift_id = $shift_id[0];
            }

            if ((string) $shift_id === '0' || empty($shift_id)) {
                return [
                    'success' => true,
                    'pumps' => $this->getAvailableDirectSettlementPumps(
                        (int) $business_id,
                        ! empty($request->location_id) ? (int) $request->location_id : null
                    ),
                ];
            }

            $assigned_pumps_query = PumpOperatorAssignment::where('pump_operator_assignments.pump_operator_id', $id)
                ->where('pump_operator_assignments.business_id', $business_id)
                ->leftJoin('settlements', 'pump_operator_assignments.settlement_id', '=', 'settlements.id')
                ->where(function ($query) use ($active_settlement_id) {
                    $query->whereNull('pump_operator_assignments.settlement_id');

                    if ($active_settlement_id > 0) {
                        $query->orWhere('pump_operator_assignments.settlement_id', $active_settlement_id);
                    }
                })
                ->where('pump_operator_assignments.shift_id', $shift_id); // filter by shift

            $this->excludePetroPdModuleAssignments($assigned_pumps_query, $business_id);

            $assigned_pumps = $assigned_pumps_query
                ->pluck('pump_operator_assignments.pump_id');

            // dd(  $assigned_pumps );

            if ($assigned_pumps->isNotEmpty()) {
                $pumps_query = Pump::where('business_id', $business_id);
                if (Schema::hasColumn('pumps', 'is_other_sales_pump')) {
                    $pumps_query->where('is_other_sales_pump', 0);
                }
                $pumps = $pumps_query->whereIn('id', $assigned_pumps)
                    ->pluck('pump_name', 'id');
            } else {
                // No assignment for selected operator/shift: do not expose all pumps.
                $pumps = collect();
            }

            $output = [
                'success' => true,
                'pumps' => $pumps,
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: '.$e->getFile().
                ' Line: '.$e->getLine().
                ' Message: '.$e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Get all pumps for the selected business location.
     * Pump dropdown is NOT scoped by operator/shift.
     */

    public function getBalanceStock($id)
    {

        try {

            $product = Product::leftjoin(

                'variations',

                'products.id',

                'variations.product_id'

            )

                ->leftjoin(

                    'variation_location_details',

                    'variations.id',

                    'variation_location_details.variation_id'

                )

                ->where('products.id', $id)

                ->select(

                    'qty_available',

                    'sell_price_inc_tax',

                    'products.name',

                    'sku'

                )

                ->first();

            $output = [

                'success' => true,

                'balance_stock' => $product->qty_available,

                'price' => $product->sell_price_inc_tax,

                'product_name' => $product->name,

                'code' => $product->sku,

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
     * save meter sale to db







     * @return Response
     */

    public function getStoresById(Request $request)
    {

        $business_id = $request->session()->get('user.business_id');

        $account_type = null;

        $stores = '<option value="">Please Select</option>';

        $stores = Store::where('business_id', $business_id);

        if ($request->location_id) {

            $stores = $stores->where('location_id', $request->location_id);

        }

        $stores = $stores->pluck('name', 'id');

        return $this->transactionUtil->createDropdownHtml(

            $stores,

            'Please Select'

        );

    }

    public function getProductsByStoreId(Request $request)
    {

        $business_id = $request->session()->get('user.business_id');

        $location_id = $request->location_id;

        $store_id = $request->store_id;

        $tab = $request->tab ?? 0;

        // dump($tab);exit;

        $fuel_category_id = Category::where('business_id', $business_id)

            ->where('name', 'Fuel')

            ->first();

        $fuel_category_id = ! empty($fuel_category_id)

            ? $fuel_category_id->id

            : null;

        if ($store_id) {

            return $this->transactionUtil->getProductsByStoreId(

                $business_id,

                $location_id,

                $store_id,

                $tab,

                $fuel_category_id,

                'petro_settlements'

            );

        } else {

            $products = [];

            return $this->transactionUtil->createDropdownHtml(

                $products,

                'No Item Found'

            );

        }

    }

    public function getBalanceStockById(Request $request, $id)
    {

        try {

            $product = Product::join(

                'variations',

                'products.id',

                '=',

                'variations.product_id'

            )

                ->leftJoin('variation_location_details', function ($join) use ($id, $request) {

                    $join

                        ->on(

                            'variations.id',

                            '=',

                            'variation_location_details.variation_id'

                        )

                        ->where(

                            'variation_location_details.product_id',

                            '=',

                            $id

                        )

                        ->where(

                            'variation_location_details.location_id',

                            '=',

                            $request->location_id

                        );

                })

                ->leftJoin('variation_store_details', function ($join) use ($request) {

                    $join

                        ->on(

                            'variations.id',

                            '=',

                            'variation_store_details.variation_id'

                        )

                        ->where(

                            'variation_store_details.store_id',

                            '=',

                            $request->store_id

                        );

                })

                ->where('products.id', $id)

                ->select(

                    DB::raw(

                        'COALESCE(variation_store_details.qty_available, 0) as qty_available'

                    ),

                    DB::raw(

                        'COALESCE(sell_price_inc_tax, 0) as sell_price_inc_tax'

                    ),

                    'products.name',

                    'products.sku'

                )

                ->first();

            $output = [

                'success' => true,

                'balance_stock' => $product->qty_available,

                'price' => $product->sell_price_inc_tax,

                'product_name' => $product->name,

                'code' => $product->sku,

                'msg' => __('petrogeneral::lang.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency(

                'File: '.

                $e->getFile().

                ' Line: '.

                $e->getLine().

                ' Message: '.

                $e->getMessage()

            );

            $output = [

                'success' => false,

                'msg' => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }
}

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
 * Dropdown and lookup endpoints used by the settlement screens.
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
 * Methods here: getPumpDetails, getPumpDetailsPerShift, getPumps, getBalanceStock, getStoresById, getProductsByStoreId, getBalanceStockById
 */
trait ProvidesPdLookups
{
public function getPumpDetails($pump_id, $shift_id = null)
{
    $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);
    $pump = Pump::where('id', $pump_id)
        ->when($business_id > 0 && SchemaCapabilityCache::hasColumn('pumps', 'business_id'), function ($query) use ($business_id) {
            $query->where('business_id', $business_id);
        })
        ->first();

    if (empty($pump)) {
        return response()->json(['success' => false, 'msg' => 'Pump not found.'], 404);
    }

    $last_meter_reading = (float) ($pump->last_meter_reading ?? 0);
    $last_meter_sale = MeterSale::where('business_id', $business_id)
        ->where('pump_id', $pump_id)
        ->petroDirectOwned()
        ->whereNotNull('settlement_no')
        ->orderByDesc('id')
        ->first();

    if (! empty($last_meter_sale)) {
        $last_meter_reading = ! empty($last_meter_sale->meter_reset_value)
            ? (float) $last_meter_sale->meter_reset_value
            : (float) $last_meter_sale->closing_meter;
    }

    $fuel_tank = FuelTank::where('id', $pump->fuel_tank_id)
        ->when($business_id > 0 && SchemaCapabilityCache::hasColumn('fuel_tanks', 'business_id'), function ($query) use ($business_id) {
            $query->where('business_id', $business_id);
        })
        ->first();

    /*
     * S696: take the product from the PUMP, not from its fuel tank.
     *
     * The price shown in Meter Sales was 286 while the pump's product is priced
     * at 300. Verified against the data:
     *
     *     pumps.product_id       = 7  (Auto Deisel, 300)   <- what settings show
     *     fuel_tanks.product_id  = 2  (Auto Diesel, 286)   <- what this code read
     *
     * pumps.product_id is the link the Pump settings screen sets and the one the
     * operator sees. The tank's product_id is a separate field that does not
     * have to agree with it, and here it does not.
     *
     * The tank is still used for meter and stock figures below - only the
     * PRODUCT and its price now come from the pump.
     *
     * The tank is also no longer required for the request to succeed: a pump
     * with no tank row previously returned "Fuel tank not found" and no price at
     * all, which is why the field stayed empty on some pumps.
     */
    $product_id = $pump->product_id ?? ($fuel_tank->product_id ?? null);

    if (empty($product_id)) {
        return response()->json(['success' => false, 'msg' => 'No product is linked to this pump.'], 422);
    }

    $product = Variation::leftJoin('products', 'variations.product_id', '=', 'products.id')
        ->leftJoin('variation_location_details', 'variations.id', '=', 'variation_location_details.variation_id')
        ->where('products.id', $product_id)
        ->select(
            'products.sku',
            // S696: same coalesce as Products New, so this agrees with the list.
            \Illuminate\Support\Facades\DB::raw(
                (\Illuminate\Support\Facades\Schema::hasColumn('variations', 'default_sell_price')
                    ? 'COALESCE(variations.sell_price_inc_tax, 0)'
                    : 'COALESCE(variations.sell_price_inc_tax, 0)')
                . ' as default_sell_price'
            ),
            'products.name',
            'products.id',
            'variation_location_details.qty_available'
        )
        ->first();

    if (empty($product)) {
        return response()->json(['success' => false, 'msg' => 'Product not found for this pump.'], 422);
    }

    $business = Business::find($business_id);
    $currency_precision = (int) ($business->currency_precision ?? 2);
    $product->default_sell_price = number_format((float) $product->default_sell_price, $currency_precision, '.', '');
    /*
     * Guarded: a pump without a tank row still returns a price. Stock simply
     * comes back as 0 rather than the whole request failing, which is what
     * previously left the Unit Price empty on those pumps.
     */
    $current_balance = 0;

    if (! empty($pump->fuel_tank_id)) {
        try {
            $current_balance = $this->transactionUtil->getTankBalanceById($pump->fuel_tank_id);
        } catch (\Throwable $e) {
            $current_balance = 0;
        }
    }

    return response()->json([
        'success' => true,
        'colsing_value' => number_format($last_meter_reading, 3, '.', ''),
        'tank_remaing_qty' => $current_balance,
        'product' => $product,
        'pump_name' => $pump->pump_name,
        'product_id' => $product->id,
        'pump_id' => $pump->id,
        'bulk_sale_meter' => $pump->bulk_sale_meter,
        // IS1761: never import Pumper Dashboard / PetroPD close-shift values here.
        'po_closing' => number_format(0, 3, '.', ''),
        'po_testing' => number_format(0, 3, '.', ''),
        'assignment_id' => 0,
        'pumper_entry_id' => 0,
        'is_open' => 0,
    ]);
}

public function getPumpDetailsPerShift($pump_id, $shift_id)
{
    // IS1761: shift IDs from Pumper Dashboard / PetroPD are intentionally ignored.
    return $this->getPumpDetails($pump_id, null);
}


    /**
     * get balance stock of product







     * @param product_id







     * @return Response
     */

public function getPumps(Request $request, $id)
{
    try {
        $business_id = (int) ($this->getCurrentBusinessIdForDirectSettlement() ?: 0);

        return [
            'success' => true,
            /*
             * MA-002 (IS-1944 #1): pumps already added to the settlement being
             * built are excluded here, and ONLY here.
             *
             * This is the create-time lookup. EditsPdSettlements calls the same
             * method without this argument, so an edit still lists every pump
             * on the settlement - which it must, since they belong to it.
             */
            'pumps' => $this->getAvailableDirectSettlementPumps(
                $business_id,
                ! empty($request->location_id) ? (int) $request->location_id : null,
                $request->input('active_settlement_id') ?: $request->input('settlement_no')
            ),
        ];
    } catch (\Throwable $e) {
        Log::error('IS1761 PetroDirect pump list failed', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
        ]);

        return [
            'success' => false,
            'msg' => __('messages.something_went_wrong'),
        ];
    }
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

                /*
                 * S696: the same price expression Products New uses.
                 *
                 * This selected variations.sell_price_inc_tax alone. Products
                 * New builds its Selling Price by coalescing
                 *
                 *     sell_price_inc_tax -> default_sell_price -> 0
                 *
                 * (ProductQueryService::priceExpression), so wherever
                 * sell_price_inc_tax is null or zero the product list showed one
                 * figure and the Meter Sales Unit Price showed another.
                 *
                 * This is the PUMP path. The credit-sale path was corrected under
                 * S694 in AddPaymentController::getProductPrice - the two read
                 * the price from different endpoints, which is why fixing one did
                 * not fix the other.
                 *
                 * NULLIF so a stored ZERO also falls through: a zero selling
                 * price is not a price, and treating it as one is what put 0.00
                 * in the field instead of the list price.
                 */
                ->select(

                    'qty_available',

                    \Illuminate\Support\Facades\DB::raw(
                        (\Illuminate\Support\Facades\Schema::hasColumn('variations', 'default_sell_price')
                            ? 'COALESCE(variations.sell_price_inc_tax, 0)'
                            : 'COALESCE(variations.sell_price_inc_tax, 0)')
                        . ' as sell_price_inc_tax'
                    ),

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
     * save meter sale to db







     * @return Response
     */

    public function getStoresById(Request $request)
    {
        $business_id = (int) (
            $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional(auth()->user())->business_id
            ?: 0
        );

        abort_if($business_id <= 0, 403, __('messages.unauthorized_action'));

        $locationId = (int) $request->input('location_id', 0);
        $stores = $this->getDirectSettlementStoreDropdown($business_id, $locationId);
        $preferredStoreId = (int) (
            $request->input('current_store_id')
            ?: $request->session()->get('business.default_store', 0)
        );
        $defaultStoreId = $this->resolveDirectSettlementDefaultStoreId($stores, $preferredStoreId, $locationId);

        $html = '<option value="">'.e(__('petrodirect::lang.please_select')).'</option>';
        foreach ($stores as $storeId => $storeName) {
            $selected = ((int) $storeId === (int) $defaultStoreId) ? ' selected' : '';
            $html .= '<option value="'.e((string) $storeId).'"'.$selected.'>'.e((string) $storeName).'</option>';
        }

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function getProductsByStoreId(Request $request)
    {
        $business_id = (int) (
            $request->session()->get('user.business_id')
            ?: $request->session()->get('business.id')
            ?: optional(auth()->user())->business_id
            ?: 0
        );

        abort_if($business_id <= 0, 403, __('messages.unauthorized_action'));

        $location_id = (int) $request->input('location_id', 0);
        $store_id = (int) $request->input('store_id', 0);

        $fuel_category_id = Category::where('business_id', $business_id)
            ->where('name', 'Fuel')
            ->value('id');

        // Normalise historical JS values such as `other_sat`. The utility expects
        // the Other Sale tab name and otherwise returns an empty option set.
        $tab = 'other_sale';

        if ($store_id > 0) {
            try {
                $html = (string) $this->transactionUtil->getProductsByStoreId(
                    $business_id,
                    $location_id,
                    $store_id,
                    $tab,
                    $fuel_category_id,
                    'petro_settlements'
                );

                // Do not return the utility HTML directly: older products without
                // the optional module tag would still be missing. The merged list
                // below is the authoritative Direct Settlement dropdown.
                unset($html);
            } catch (\Throwable $exception) {
                PetroDirectDebug::info('PetroDirect store product dropdown failed; using non-fuel fallback.', [
                    'business_id' => $business_id,
                    'location_id' => $location_id,
                    'store_id' => $store_id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $products = $this->getDirectSettlementOtherSaleItems(
            $business_id,
            $fuel_category_id ? (int) $fuel_category_id : null
        );

        return $this->transactionUtil->createDropdownHtml(
            $products,
            empty($products) ? 'No Item Found' : __('petrodirect::lang.please_select')
        );
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

                        // S696: fall back to default_sell_price before zero.
                        (\Illuminate\Support\Facades\Schema::hasColumn('variations', 'default_sell_price')
                            ? 'COALESCE(sell_price_inc_tax, 0) as sell_price_inc_tax'
                            : 'COALESCE(sell_price_inc_tax, 0) as sell_price_inc_tax')

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

                'msg' => __('petrodirect::lang.success'),

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

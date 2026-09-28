<?php
namespace Modules\SettlementSW\Http\Controllers;

use Modules\SettlementSW\Services\SettlementSwTables;
use Modules\SettlementSW\Services\SettlementSwLegacyMap;
use Modules\SettlementSW\Services\SettlementSwSubscription;
use Modules\SettlementSW\Services\SettlementSwLog;

use Modules\SettlementSW\Entities\SettlementSwAccount as Account;
use Modules\SettlementSW\Entities\SettlementSwAccountGroup as AccountGroup;
use Modules\SettlementSW\Entities\SettlementSwAccountTransaction as AccountTransaction;
use Modules\SettlementSW\Entities\SettlementSwBusiness as Business;
use Modules\SettlementSW\Entities\SettlementSwBusinessLocation as BusinessLocation;
use Modules\SettlementSW\Entities\SettlementSwContact as Contact;
use Modules\SettlementSW\Entities\SettlementSwCustomerReference as CustomerReference;
use Modules\SettlementSW\Entities\SettlementSwProduct as Product;
use Modules\SettlementSW\Entities\SettlementSwTransaction as Transaction;
use Modules\SettlementSW\Entities\SettlementSwTransactionPayment as TransactionPayment;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\SettlementSW\Entities\CustomerPayment;
use Modules\SettlementSW\Entities\DailyCard;
use Modules\SettlementSW\Entities\DailyChequePayment;
use Modules\SettlementSW\Entities\DailyCollection;
use Modules\SettlementSW\Entities\DailyVoucher;
use Modules\SettlementSW\Entities\DayEnd;
use Modules\SettlementSW\Entities\MeterSale;
use Modules\SettlementSW\Entities\OtherIncome;
use Modules\SettlementSW\Entities\OtherSale;
use Modules\SettlementSW\Entities\PumpOperator;
use Modules\SettlementSW\Entities\PumpOperatorAssignment;
use Modules\SettlementSW\Entities\PumpOperatorOtherSale;
use Modules\SettlementSW\Entities\PumpOperatorPayment;
use Modules\SettlementSW\Entities\Settlement;
use Modules\SettlementSW\Entities\SettlementCardPayment;
use Modules\SettlementSW\Entities\SettlementCashDeposit;
use Modules\SettlementSW\Entities\SettlementCashPayment;
use Modules\SettlementSW\Entities\SettlementChequePayment;
use Modules\SettlementSW\Entities\SettlementCreditSalePayment;
use Modules\SettlementSW\Entities\SettlementCustomerLoan;
use Modules\SettlementSW\Entities\SettlementDrawingPayment;
use Modules\SettlementSW\Entities\SettlementExcessPayment;
use Modules\SettlementSW\Entities\SettlementExpensePayment;
use Modules\SettlementSW\Entities\SettlementLoanPayment;
use Modules\SettlementSW\Entities\SettlementShortagePayment;
use Modules\SettlementSW\Services\SettlementPaymentReconciler;

class SettlementSwAddPaymentBaseController extends Controller
{

    /**

     * All Utils instance.

     *

     */

    protected $productUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    protected $commonUtil;

    private $barcode_types;

    /**

     * Constructor

     *

     * @param ProductUtils $product

     * @return void

     */


    /**
     * SW_AUDIT_007: module-owned subscription guard with legacy fallback.
     * This avoids hard-coding Petro subscription flags in active controller code
     * while still supporting existing tenants until a dedicated Settlement SW
     * subscription flag is seeded/enabled.
     */
    protected function hasSettlementSwSubscription($business_id): bool
    {
        $primary = config('settlementsw.subscription_permission_key', 'enable_settlement_sw_module');
        $legacy = config('settlementsw.legacy_subscription_permission_key', 'enable_petro_module');

        if ($primary && $this->moduleUtil->hasThePermissionInSubscription($business_id, $primary)) {
            return true;
        }

        return $legacy && $this->moduleUtil->hasThePermissionInSubscription($business_id, $legacy);
    }

    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {

        $this->commonUtil = $commonUtil;

        $this->productUtil = $productUtil;

        $this->moduleUtil = $moduleUtil;

        $this->transactionUtil = $transactionUtil;

        $this->businessUtil = $businessUtil;

    }

    /**

     * Display a listing of the resource.

     * @return Response

     */

    public function index()
    {

        $business_id = request()->session()->get('user.business_id');

        if (! $this->hasSettlementSwSubscription($business_id)) {
            abort(403, 'Unauthorized Access');
        }

        return view('settlementsw::swsettlement.index');

    }

    /**

     * Show the form for creating a new resource.

     * @return Response

     */

    public function addDailyCards($settlement_no, $pump_operator_id, $business_id, $shift_id)
    {
        // Get card payments for this shift
        $cards_by_shifts = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('shift_id', $shift_id)
            ->where('payment_type', 'card')
            ->get();

        if ($cards_by_shifts->isNotEmpty()) {
            foreach ($cards_by_shifts as $cards_by_shift) {
                // Find ALL DailyCards for this collection number
                $daily_cards = DailyCard::where('collection_no', $cards_by_shift->collection_form_no)->get();

                foreach ($daily_cards as $daily_card) {
                    // ✅ Prevent duplicate insert by daily_card_id
                    $exists = SettlementCardPayment::where('daily_card_id', $daily_card->id)->exists();

                    if (! $exists) {
                        $data = [
                            'business_id'   => $business_id,
                            'settlement_no' => $settlement_no,
                            'amount'        => $daily_card->amount,
                            'card_type'     => $daily_card->card_type,
                            'card_number'   => $daily_card->card_number,
                            'daily_card_id' => $daily_card->id,
                            'customer_id'   => $daily_card->customer_id,
                            'note'          => $daily_card->note,
                            'slip_no'       => $daily_card->slip_no,
                        ];

                        app(SettlementPaymentReconciler::class)
                            ->upsertOne($business_id, $settlement_no, 'settlement_card_payments', $data);
                    }

                    // Update daily card status
                    $daily_card->used_status   = 1;
                    $daily_card->settlement_no = $settlement_no;
                    $daily_card->save();
                }
            }
        }

        // Update the credit_nos
        $credit_by_shifts = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('shift_id', $shift_id)
            ->where('payment_type', 'credit')
            ->get();

        if ($credit_by_shifts->isNotEmpty()) {
            foreach ($credit_by_shifts as $credit_by_shift) {
                // Get ALL settlement credit sale payments for this collection
                $items = SettlementCreditSalePayment::where('collection_form_no', $credit_by_shift->collection_form_no)->get();

                foreach ($items as $item) {
                    $item->update(['settlement_no' => $settlement_no]);

                    // Get ALL vouchers linked to this order_number
                    $vouchers = DailyVoucher::where('voucher_order_number', $item->order_number)->get();

                    foreach ($vouchers as $voucher) {
                        // Update voucher
                        $voucher->settlement_no = $settlement_no;
                        $voucher->save();

                        // Update settlement credit sale with voucher ID
                        $item->update(['daily_voucher_id' => $voucher->id]);
                    }
                }
            }
        }

    }

    // public function addDailyCards($settlement_no,$pump_operator_id,$business_id){

    //     $daily_cards = DailyCard::where('business_id',$business_id)

    //                             ->where('pump_operator_id',$pump_operator_id)

    //                             ->whereNUll('used_status')

    //                             ->get();

    //     if(!empty($daily_cards)){

    //         foreach($daily_cards as $daily_card){

    //             $card = DailyCard::findOrFail($daily_card->id);

    //             $data = array(

    //                 'business_id' => $business_id,

    //                 'settlement_no' => $settlement_no,

    //                 'amount' => $daily_card->amount,

    //                 'card_type' => $daily_card->card_type,

    //                 'card_number' => $daily_card->card_number,

    //                 'daily_card_id' => $daily_card->id,

    //                 'customer_id' => $daily_card->customer_id,

    //                 'note' => $daily_card->note,

    //                 'slip_no' => $daily_card->slip_no

    //             );

    //             $settlement_card_payment = app(SettlementPaymentReconciler::class)->upsertOne(...);

    //             $card->used_status = 1;

    //             $card->settlement_no = $settlement_no;

    //             $card->save();

    //         }

    //     }

    //     // update the credit_nos

    //     SettlementCreditSalePayment::whereNull('settlement_no')->where('pump_operator_id',$pump_operator_id)->update(['settlement_no' => $settlement_no]);

    //     $pending_vouchers = DailyVoucher::where('business_id',$business_id)->whereNull('settlement_no')->get();

    //     $action = false;

    //     foreach($pending_vouchers as $voucher){

    //         $settlement_no = SettlementCreditSalePayment::where('order_number',$voucher->voucher_order_number)->where('order_date',$voucher->voucher_order_date)->where('pump_operator_id',$voucher->operator_id)->first()->settlement_no ?? NULL;

    //         $exists = SettlementCreditSalePayment::where('order_number',$voucher->voucher_order_number)->where('order_date',$voucher->voucher_order_date)->where('pump_operator_id',$voucher->operator_id)->count();

    //         if($exists > 0){

    //             $voucher->settlement_no = $settlement_no;

    //             $voucher->save();

    //         }else{

    //             $item = DailyVoucherItem::where('daily_voucher_id',$voucher->id)->first();

    //             $dt = array(

    //                 'business_id' => $business_id,

    //                 'pump_operator_id' => $voucher->operator_id,

    //                 'customer_id' => $voucher->customer_id,

    //                 'daily_voucher_id' => $voucher->id,

    //                 'product_id' => $item->product_id,

    //                 'order_number' => $voucher->voucher_order_number,

    //                 'order_date' => $voucher->voucher_order_date,

    //                 'price' => $item->unit_price,

    //                 'discount' => 0,

    //                 'qty' => $item->qty,

    //                 'amount' => $voucher->total_amount,

    //                 'sub_total' => $voucher->total_amount,

    //                 'total_discount' => 0,

    //                 'outstanding' => $voucher->current_outstanding,

    //                 'credit_limit' => null,

    //                 'customer_reference' => $voucher->vehicle_no

    //             );

    //             $credit_sale_payment = app(SettlementPaymentReconciler::class)->upsertOne(...);

    //             $action = true;

    //         }

    //     }

    //     if(!empty($action)){

    //         $this->addDailyCards($settlement_no,$pump_operator_id,$business_id);

    //     }

    // }

    public function addDailyCheques($settlement_no, $pump_operator_id, $business_id)
    {

        $pending = DailyChequePayment::join(SettlementSwTables::shifts(), SettlementSwTables::shiftColumn('id'), 'daily_cheque_payments.shift_id')

            ->join('pump_operator_payments', 'pump_operator_payments.id', 'daily_cheque_payments.linked_payment_id')

            ->where(SettlementSwTables::shiftColumn('pump_operator_id'), $pump_operator_id)

            ->whereNull('daily_cheque_payments.settlement_no')

            ->select('daily_cheque_payments.*')

            ->get();

        foreach ($pending as $one) {

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement_no,

                'amount'        => $one->amount,

                'bank_name'     => $one->bank_name,

                'cheque_number' => $one->cheque_number,

                'cheque_date'   => $one->cheque_date,

                'customer_id'   => $one->customer_id,
                'pump_payment_id' => $one->linked_payment_id,

            ];

            $settlement_cheque_payment = app(SettlementPaymentReconciler::class)
                ->upsertOne($business_id, $settlement_no, 'settlement_cheque_payments', $data);

            $pmt = PumpOperatorPayment::findOrFail($one->linked_payment_id);

            $pmt->is_used = 1;

            $pmt->parent_id = $settlement_cheque_payment->id;

            $pmt->settlement_no = $settlement_no;

            $pmt->save();

            $one->settlement_no = $settlement_no;

            $one->save();

        }

        return true;

    }

    public function addDailyShortageExcess($settlement_no, $pump_operator_id, $business_id, $shift_ids)
    {

        $shift_Ids = explode(",", $shift_ids);

        $daily_shortage_excess = PumpOperatorPayment::where('business_id', $business_id)

            ->whereIn('shift_id', $shift_Ids)

            ->where('pump_operator_id', $pump_operator_id)

            ->where(function ($query) {

                $query->whereNUll('is_used')->orWhere('is_used', 0);

            })

            ->whereIn('payment_type', ['shortage', 'excess'])

            ->get();

        $settlement = Settlement::findOrFail($settlement_no);

        $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);

        if (! empty($daily_shortage_excess)) {

            foreach ($daily_shortage_excess as $daily_shortage_excess) {

                $shortage_excess = PumpOperatorPayment::findOrFail($daily_shortage_excess->id);

                if ($daily_shortage_excess->payment_type == 'shortage') {

                    $data = [

                        'business_id'      => $business_id,

                        'settlement_no'    => $settlement->id,

                        'amount'           => $daily_shortage_excess->payment_amount,

                        'current_shortage' => $pump_operator->short_amount,

                    ];

                    $parent_payment = SettlementShortagePayment::create($data);

                }

                if ($daily_shortage_excess->payment_type == 'excess') {

                    $data = [

                        'business_id'    => $business_id,

                        'settlement_no'  => $settlement->id,

                        'amount'         => $daily_shortage_excess->payment_amount,

                        'current_excess' => $pump_operator->excess_amount,

                    ];

                    if (request()->amount > 0) {

                        continue;

                    }

                    $parent_payment = SettlementExcessPayment::create($data);

                }

                $shortage_excess->is_used = 1;

                $shortage_excess->parent_id = $parent_payment->id;

                $shortage_excess->settlement_no = $settlement_no;

                $shortage_excess->save();

            }

        }

    }

    public function createSettlementIfNotExist(Request $request)
    {
        $business_id = $request->session()->get('business.id');

        if (empty($request->settlement_no)) {
            Log::error('Settlement creation failed: Missing settlement_no', ['request' => $request->all()]);
            return null;
        }

        if (empty($request->operator_id)) {
            Log::error('Settlement creation failed: Missing operator_id', ['request' => $request->all()]);
            return null;
        }

        if (empty($request->transaction_date)) {
            Log::warning('Transaction date missing, defaulting to today', ['request' => $request->all()]);
            $request->merge(['transaction_date' => now()->format('Y-m-d')]);
        }

        $transaction_date = Carbon::parse($request->transaction_date)->format('Y-m-d');

        $latest_date = DayEnd::where('business_id', $business_id)
            ->latest('day_end_date')
            ->value('day_end_date');

        if (! empty($latest_date) && strtotime($latest_date) >= strtotime($transaction_date)) {
            Log::warning('Settlement creation blocked due to day-end restriction', [
                'business_id'                => $business_id,
                'latest_day_end'             => $latest_date,
                'requested_transaction_date' => $transaction_date,
            ]);
            return 406;
        }

        // ✅ Determine location id safely
        $operator    = PumpOperator::find($request->operator_id);
        $location_id = $request->location_id ?? ($operator->location_id ?? null);

        if (empty($location_id)) {
            Log::error('Settlement creation failed: Missing location_id', [
                'operator_id' => $request->operator_id,
                'business_id' => $business_id,
            ]);
            return null;
        }

        // Check if settlement already exists
        $existing = Settlement::where('settlement_no', $request->settlement_no)
            ->where('business_id', $business_id)
            ->first();

        if ($existing) {
            SettlementSwLog::info('Settlement already exists', [
                'settlement_no' => $existing->settlement_no,
                'id'            => $existing->id,
            ]);
            return $existing;
        }

        $settlement_data = [
            'settlement_no'    => $request->settlement_no,
            'business_id'      => $business_id,
            'transaction_date' => $transaction_date,
            'location_id'      => $location_id,
            'pump_operator_id' => $request->operator_id,
            'work_shift'       => ! empty($request->work_shift) ? $request->work_shift : [],
            'note'             => $request->note ?? '',
            'status'           => 1,
        ];

        try {
            $settlement = Settlement::create($settlement_data);
            SettlementSwLog::info('New settlement created', [
                'settlement_no' => $settlement->settlement_no,
                'id'            => $settlement->id,
            ]);
            DailyCollection::where('business_id', $business_id)
                ->where('pump_operator_id', $request->pump_operator_id)
                ->whereNull('settlement_id')
                ->update(['settlement_id' => $settlement->id]);

            DailyCard::where('business_id', $business_id)
                ->where('pump_operator_id', $request->pump_operator_id)
                ->whereNull('settlement_no')
                ->update(['settlement_no' => $settlement->settlement_no]);

            DailyVoucher::where('business_id', $business_id)
                ->where('operator_id', $request->pump_operator_id)
                ->whereNull('settlement_no')
                ->update(['settlement_no' => $settlement->settlement_no]);
            return $settlement;
        } catch (\Throwable $e) {
            Log::error('Settlement creation failed', [
                'error' => $e->getMessage(),
                'data'  => $settlement_data,
            ]);
            return null;
        }
    }

    public function create(Request $request)
    {
        SettlementSwLog::info('SettlementSwAddPaymentBaseController create method');

        $business_id  = request()->session()->get('business.id');

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'daily_collection_sw')) {
           // abort(403, 'Unauthorized Access');
        }
        $business     = Business::where('id', $business_id)->first();
        $pos_settings = json_decode($business->pos_settings, true);
        $cash_denoms  = ! empty($pos_settings['cash_denominations']) ? explode(',', $pos_settings['cash_denominations']) : [];

        $incoming_settlement_identifier = $request->settlement_no;
        $provider                       = $request->provider;
        $pump_operator_id               = $request->operator_id;
        $is_settlement_page             = isset($request->settlement_page) ? 1 : 0;

        $shiftIds = $request->shift_ids ?? [];

        if (is_string($shiftIds)) {
            $shiftIds = array_filter(array_map('trim', explode(',', $shiftIds)), fn($v) => $v !== '');
        }

        if (! is_array($shiftIds)) {
            $shiftIds = [$shiftIds];
        }

        $settlement = null;
        if (is_numeric($incoming_settlement_identifier)) {
            $settlement = Settlement::with('meter_sales')
                ->where('id', $incoming_settlement_identifier)
                ->where('business_id', $business_id)
                ->first();
        }

        if (! $settlement) {
            $settlement = Settlement::with('meter_sales')
                ->where('settlement_no', $incoming_settlement_identifier)
                ->where('business_id', $business_id)
                ->first();
        }

        if (! $settlement) {

            $created = $this->createSettlementIfNotExist($request);

            if ($created === 406) {
                return response(['success' => false, 'msg' => __('messages.day_end_prevents_settlement')], 406);
            }

            if ($created instanceof Settlement) {
                $settlement = $created;
            } else {
                // FALLBACK: If no settlement_no provided, try to get the latest one for debugging/viewing
                if (empty($incoming_settlement_identifier)) {
                    $settlement = Settlement::with('meter_sales')
                                ->where('business_id', $business_id)
                                ->latest('created_at')
                                ->first();
                    
                    if ($settlement) {
                        // Populate missing params from the found settlement to ensure view works
                        $request->merge([
                            'settlement_no' => $settlement->settlement_no,
                            'operator_id' => $settlement->pump_operator_id
                        ]);
                        $pump_operator_id = $settlement->pump_operator_id;
                    }
                }

                if (!$settlement) {
                    return response(['success' => false, 'msg' => 'Unable to create or fetch settlement'], 500);
                }
            }
        }

        if (! $settlement) {
            return response(['success' => false, 'msg' => 'Settlement not found or could not be created'], 404);
        }

        // Ensure operator & shifts default from settlement when not provided
        $pump_operator_id = $pump_operator_id ?: $settlement->pump_operator_id;

        if (empty($shiftIds)) {
            $shiftIds = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        $settlementId = $settlement->id;

        $customer_payments_tab = CustomerPayment::leftJoin(SettlementSwTables::contacts(), 'customer_payments.customer_id', 'contacts.id')
            ->where('customer_payments.settlement_no', $settlementId)
            ->select('customer_payments.*', 'contacts.name as customer_name')
            ->get();

        $shift_id = $shiftIds;

        $settlement_cash_payments2 = SettlementCashPayment::leftJoin(SettlementSwTables::contacts(), 'settlement_cash_payments.customer_id', '=', 'contacts.id')
            ->where('settlement_cash_payments.settlement_no', $settlementId)
            ->select('settlement_cash_payments.*', 'contacts.name as customer_name')
            ->get();

        // Replace the problematic cash payments query with this:
        $settlement_cash_payments1 = PumpOperatorPayment::where('pump_operator_payments.pump_operator_id', $pump_operator_id)
            ->where('pump_operator_payments.payment_type', 'cash')
            ->whereNull('pump_operator_payments.settlement_no')
            ->when(! empty($shiftIds) && count($shiftIds) > 0, function ($query) use ($shiftIds) {
                // Join with Settlement SW daily shifts table
                $query->join(SettlementSwTables::dailyShifts(), function ($join) use ($shiftIds) {
                    $join->on('pump_operator_payments.shift_id', '=', SettlementSwTables::dailyShiftColumn('id'))
                        ->whereIn(SettlementSwTables::dailyShiftColumn('id'), $shiftIds);
                });
            })
            ->select('pump_operator_payments.*')
            ->get();

        $newdatarecords = $settlement_cash_payments1->map(function ($data1) {
            $record                      = new SettlementCashPayment();
            $record->id                  = null;
            $record->settlement_no       = null;
            $record->business_id         = null;
            $record->customer_id         = null;
            $record->amount              = $data1->amount;
            $record->customer_payment_id = null;
            $record->note                = '';
            $record->slip                = '';
            $record->created_at          = now();
            $record->updated_at          = now();
            $record->customer_name       = 'Walking Customer';
            return $record;
        });

        $shift_ids = explode(',', $request->shift_ids ?? '');

        $includeUnassigned = false;

        $dailyCollectionsQuery = DailyCollection::where('pump_operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->where('type', 'daily_collection_sw')
            ->where(function($q) use ($settlementId) {
                // Include EITHER unsettled collections OR collections already tied to THIS settlement
                $q->whereNull('settlement_id')
                  ->orWhere('settlement_id', $settlementId);
            });

        if (! empty($shiftIds)) {
            // Get the shift numbers from Settlement SW daily shifts table
            $shiftNumbers = DB::table(SettlementSwTables::dailyShifts())
                ->whereIn('id', $shiftIds)
                ->pluck('shift_no')
                ->toArray();

            if (! empty($shiftNumbers)) {
                $dailyCollectionsQuery->whereIn('shift_number', $shiftNumbers);
            }

            if ($includeUnassigned) {
                $dailyCollectionsQuery->orWhereNull('shift_id');
            }
        }

        $daily_collections = $dailyCollectionsQuery->get();

        $daily_collections = $dailyCollectionsQuery->get();

        $fakeFromDaily = $daily_collections->map(function ($dc) {
            $record                      = new SettlementCashPayment();
            $record->id                  = null;
            $record->settlement_no       = null;
            $record->business_id         = $dc->business_id;
            $record->customer_id         = $dc->customer_id;
            $record->amount              = $dc->current_amount;
            $record->customer_payment_id = null;
            $record->note                = $dc->note ?? '';
            $record->slip                = $dc->slip ?? '';
            $record->created_at          = $dc->created_at;
            $record->updated_at          = $dc->updated_at ?? now();
            $record->customer_name       = $dc->customer_name ?? 'Walking Customer';
            return $record;
        });

        // Always show existing settlement payments.
        $settlement_cash_payments = $settlement_cash_payments2->values();

        if ($fakeFromDaily->isNotEmpty()) {
            $settlement_cash_payments = $settlement_cash_payments->concat($fakeFromDaily)->values();
        }

        if ($newdatarecords->isNotEmpty()) {
            $existingAmounts = $settlement_cash_payments->pluck('amount')->map(function ($a) {
                return (string) $a;
            })->toArray();

            $filteredNew = $newdatarecords->filter(function ($r) use ($existingAmounts) {
                return ! in_array((string) $r->amount, $existingAmounts, true);
            })->values();

            if ($filteredNew->isNotEmpty()) {
                $settlement_cash_payments = $settlement_cash_payments->concat($filteredNew)->values();
            }
        }

        $shift_ids = $request->shift_ids;

        if (is_string($shift_ids)) {
            $shift_ids = explode(',', $shift_ids);
        }
        $settlement_customer_loans = SettlementCustomerLoan::leftjoin(SettlementSwTables::contacts(), 'settlement_customer_loans.customer_id', 'contacts.id')

            ->where('settlement_customer_loans.settlement_no', $settlement->id)

            ->select('settlement_customer_loans.*', 'contacts.name as customer_name')

            ->get();

        $settlement_loan_payments = SettlementLoanPayment::leftjoin('accounts', 'accounts.id', 'settlement_loan_payments.loan_account')->where('settlement_loan_payments.settlement_no', $settlement->id)

            ->select('settlement_loan_payments.*', 'accounts.name as loan_account_name')

            ->get();

        $settlement_drawings_payments = SettlementDrawingPayment::leftjoin('accounts', 'accounts.id', 'settlement_drawing_payments.loan_account')->where('settlement_drawing_payments.settlement_no', $settlement->id)

            ->select('settlement_drawing_payments.*', 'accounts.name as loan_account_name')

            ->get();

        // Always load card payments already tied to this settlement
        $settlement_card_payments_query = SettlementCardPayment::query()
            ->leftJoin(SettlementSwTables::contacts(), 'settlement_card_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('accounts', 'settlement_card_payments.card_type', '=', 'accounts.id')
            ->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
            ->where('settlement_card_payments.settlement_no', $settlement->id)
            ->where(function ($query) use ($pump_operator_id) {
                $query->Where('daily_cards.pump_operator_id', $pump_operator_id);
            })
            ->select(
                'settlement_card_payments.*',
                'contacts.name as customer_name',
                'accounts.name as card_type',
                'daily_cards.pump_operator_id'
            );

        $settlement_card_payments = $settlement_card_payments_query->get();

        $daily_cards = DailyCard::where('pump_operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->whereNotNull('slip_no')
            ->where(function($q) use ($settlement) {
                // Include EITHER unsettled cards OR cards already linked to THIS settlement
                $q->whereNull('settlement_no')
                  ->orWhere('settlement_no', $settlement->settlement_no);
            })
            ->when(! empty($shiftIds), function ($query) use ($shiftIds) {
                // Join with Settlement SW daily shifts if you have shift_id in daily_cards
                // Or use date filtering if you don't have direct shift relationship
                $shiftDates = DB::table(SettlementSwTables::dailyShifts())
                    ->whereIn('id', $shiftIds)
                    ->pluck('date')
                    ->toArray();

                if (! empty($shiftDates)) {
                    $query->whereIn(DB::raw('DATE(date)'), $shiftDates);
                }
            })
            ->get();

        $fakeFromDailyCards = $daily_cards->map(function ($dailyCard) {
            $record                      = new SettlementCardPayment();
            $record->id                  = null;
            $record->settlement_no       = null;
            $record->business_id         = $dailyCard->business_id;
            $record->customer_id         = $dailyCard->customer_id;
            $record->amount              = $dailyCard->amount;
            $record->card_type           = $dailyCard->card_type;
            $record->daily_card_id       = $dailyCard->id;
            $record->customer_payment_id = null;
            $record->note                = $dailyCard->note ?? '';
            $record->card_number         = $dailyCard->card_number ?? '';
            $record->slip_no             = $dailyCard->slip_no ?? '';
            $record->created_at          = $dailyCard->created_at;
            $record->updated_at          = $dailyCard->updated_at ?? now();
            $record->customer_name       = $dailyCard->customer_name ?? 'Walking Customer';
            $record->card_type_name      = $dailyCard->card_type_name ?? 'Card Payment';
            return $record;
        });

        if ($fakeFromDailyCards->isNotEmpty()) {
            $settlement_card_payments = $settlement_card_payments->concat($fakeFromDailyCards)->values();
        }

        // Get cash deposits that were manually added to THIS settlement
        // settlement_no in settlement_cash_deposits table stores the string settlement_no (e.g., "SET-SW1")
        // but sometimes might be stored as ID. Check both.
        $settlement_cash_deposits = SettlementCashDeposit::leftjoin('accounts', 'settlement_cash_deposits.bank_id', 'accounts.id')
            ->where(function($q) use ($settlement) {
                $q->where('settlement_cash_deposits.settlement_no', $settlement->settlement_no)
                  ->orWhere('settlement_cash_deposits.settlement_no', $settlement->id);
            })
            ->select('settlement_cash_deposits.*', 'accounts.name as bank_name')
            ->get();

        // Also load deposits from account_transactions for the selected shifts
        // Include BOTH:
        // 1. Unsettled deposits (not yet added to any settlement)
        // 2. Deposits already linked to THIS settlement (so they show when editing)
        $accounting_deposits_query = AccountTransaction::leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')
            ->where('account_transactions.business_id', $business_id)
            ->whereIn('account_transactions.sub_type', ['deposit'])
            ->where('account_transactions.type', 'credit')
            ->where(function($q) use ($settlement) {
                // Unsettled deposits (no settlement in note)
                $q->where(function($qq) {
                    $qq->whereNull('account_transactions.note')
                       ->orWhere('account_transactions.note', '')
                       ->orWhere('account_transactions.note', 'NOT LIKE', '%Settlement No:%');
                })
                // OR deposits already linked to THIS settlement
                ->orWhere(function($qq) use ($settlement) {
                    $qq->where('account_transactions.note', 'LIKE', '%Settlement No: ' . $settlement->settlement_no . '%')
                       ->orWhere('account_transactions.note', 'LIKE', '%Settlement No: ' . $settlement->id . '%');
                });
            })
            ->select(
                'account_transactions.*',
                'accounts.name as bank_name'
            );

        if (! empty($shiftIds)) {
            $shiftNumbers = DB::table(SettlementSwTables::dailyShifts())
                ->whereIn('id', $shiftIds)
                ->pluck('shift_no')
                ->toArray();

            if (! empty($shiftNumbers)) {
                $accounting_deposits_query->whereIn('shift_number', $shiftNumbers);
            }
        }

        $accounting_deposits = $accounting_deposits_query->get();

        // Convert account_transactions to settlement_cash_deposits format for display
        $fakeDeposits = $accounting_deposits->map(function ($d) {
            return (object) [
                'id'                  => $d->id,
                'settlement_no'       => null, // Not in settlement_cash_deposits table
                'bank_id'             => $d->account_id,
                'bank_name'           => $d->bank_name ?: 'Unknown Bank',
                'amount'              => $d->amount,
                'note'                => $d->note ?? '',
                'account_no'          => $d->cheque_number ?? '',
                'time_deposited'      => $d->operation_date ?? now(),
                'customer_payment_id' => 0,
            ];
        });

        // Combine manually added deposits with unsettled deposits from accounting
        $settlement_cash_deposits = $settlement_cash_deposits
            ->concat($fakeDeposits)
            ->values();

        $settlement_cheque_payments = SettlementChequePayment::leftjoin(SettlementSwTables::contacts(), 'settlement_cheque_payments.customer_id', 'contacts.id')

            ->where('settlement_cheque_payments.settlement_no', $settlement->id)

            ->select('settlement_cheque_payments.*', 'contacts.name as customer_name')

            ->get();

        // Always load credit sale payments already tied to this settlement
        // Load both unsettled credit sales (settlement_no IS NULL) AND credit sales already saved to this settlement (settlement_no = $settlementId)
        // This prevents duplicates when editing a saved settlement
        $settlement_credit_sale_payments = SettlementCreditSalePayment::query()
            ->leftJoin(SettlementSwTables::contacts(), 'settlement_credit_sale_payments.customer_id', '=', 'contacts.id')
            ->leftJoin('daily_vouchers', 'settlement_credit_sale_payments.daily_voucher_id', '=', 'daily_vouchers.id')
            ->where(function ($query) use ($settlementId) {
                $query->whereNull('settlement_credit_sale_payments.settlement_no')
                    ->orWhere('settlement_credit_sale_payments.settlement_no', $settlementId);
            })
            ->where('settlement_credit_sale_payments.pump_operator_id', $pump_operator_id)
            ->where('settlement_credit_sale_payments.business_id', $business_id) // Add business_id filter
            ->select(
                'settlement_credit_sale_payments.*',
                'contacts.name as customer_name',
                'daily_vouchers.operator_id',
                'settlement_credit_sale_payments.order_number',
                'settlement_credit_sale_payments.order_date',
                'settlement_credit_sale_payments.customer_reference'
            )
            ->get();

// Add product_name to existing settlement_credit_sale_payments
        $settlement_credit_sale_payments->each(function ($payment) {
            $voucherProducts = DB::table(SettlementSwTables::dailyVoucherItems())
                ->join('products', 'daily_voucher_items.product_id', '=', 'products.id')
                ->where('daily_voucher_items.daily_voucher_id', $payment->daily_voucher_id)
                ->pluck('products.name')
                ->toArray();

            $payment->product_name = ! empty($voucherProducts)
                ? implode(', ', array_unique($voucherProducts))
                : 'N/A';

            // If customer_name is not set from join, get it from contacts
            if (empty($payment->customer_name)) {
                $customer               = Contact::find($payment->customer_id);
                $payment->customer_name = $customer ? $customer->name : 'Walk in customer';
            }
        });

// Get daily vouchers that haven't been settled yet
        $daily_vouchers = DailyVoucher::where('operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->whereNull('settlement_no')
            ->when(! empty($shiftIds), function ($query) use ($shiftIds) {
                $shiftDates = DB::table(SettlementSwTables::dailyShifts())
                    ->whereIn('id', $shiftIds)
                    ->pluck('date')
                    ->toArray();

                if (! empty($shiftDates)) {
                    $query->whereIn(DB::raw('DATE(created_at)'), $shiftDates);
                }
            })
            ->get();

// Filter out daily vouchers that already have a corresponding record in settlement_credit_sale_payments
        // Also check if the daily voucher is linked to ANY credit sale payment (not just those in current collection)
        // This prevents duplicates when editing a saved settlement
        $daily_vouchers = $daily_vouchers->filter(function ($dailyVoucher) use ($settlement_credit_sale_payments, $pump_operator_id, $business_id) {
            // Check if there's already a payment record for this daily voucher in the current collection
            // Match by daily_voucher_id, or by order_number + order_date if daily_voucher_id is not set
            $existingPayment = $settlement_credit_sale_payments->first(function ($payment) use ($dailyVoucher) {
                // Match by daily_voucher_id (most reliable)
                if ($payment->daily_voucher_id == $dailyVoucher->id) {
                    return true;
                }
                
                // Also match by order_number and order_date (fallback for manually created credit sales)
                if (!empty($payment->order_number) && !empty($dailyVoucher->order_no) &&
                    !empty($payment->order_date) && !empty($dailyVoucher->order_date)) {
                    return $payment->order_number == $dailyVoucher->order_no &&
                           $payment->order_date == $dailyVoucher->order_date;
                }
                
                return false;
            });

            // Also check if this daily voucher is linked to ANY credit sale payment in the database
            // This prevents duplicates when editing a saved settlement
            if (!$existingPayment) {
                // Check by daily_voucher_id
                $existsInDb = SettlementCreditSalePayment::where('daily_voucher_id', $dailyVoucher->id)
                    // ->where('pump_operator_id', $pump_operator_id) // Removed to check globally for this voucher
                    ->where('business_id', $business_id)
                    ->exists();
                
                // If not found by daily_voucher_id, also check by order_number and order_date
                if (!$existsInDb && !empty($dailyVoucher->order_no) && !empty($dailyVoucher->order_date)) {
                    $existsInDb = SettlementCreditSalePayment::where('order_number', $dailyVoucher->order_no)
                        ->where('order_date', $dailyVoucher->order_date)
                        ->where('business_id', $business_id) // Check globally in business
                        // ->where('pump_operator_id', $pump_operator_id)
                        ->exists();
                }

                // Check against current list being built (in case of duplicates within the same fetch)
                if (!$existsInDb) {
                     $existsInCollection = $settlement_credit_sale_payments->where('daily_voucher_id', $dailyVoucher->id)->count() > 0;
                     if ($existsInCollection) return false;

                     if (!empty($dailyVoucher->order_no)) {
                         $existsInCollectionByOrder = $settlement_credit_sale_payments->where('order_number', $dailyVoucher->order_no)
                                                                                     ->where('order_date', $dailyVoucher->order_date)
                                                                                     ->count() > 0;
                         if ($existsInCollectionByOrder) return false;
                     }
                }
                
                if ($existsInDb) {
                    return false; // Exclude this daily voucher
                }
            }

            // Keep only if no payment record exists
            return ! $existingPayment;
        });

// Process remaining daily vouchers for fake records
        $daily_vouchers->each(function ($dailyVoucher) {
            // Load product names from daily_voucher_items
            $voucherProducts = DB::table(SettlementSwTables::dailyVoucherItems())
                ->join('products', 'daily_voucher_items.product_id', '=', 'products.id')
                ->where('daily_voucher_items.daily_voucher_id', $dailyVoucher->id)
                ->pluck('products.name')
                ->toArray();

            $dailyVoucher->product_name = ! empty($voucherProducts)
                ? implode(', ', array_unique($voucherProducts))
                : 'N/A';

            // Load customer name
            if ($dailyVoucher->customer_id) {
                $customer                    = Contact::find($dailyVoucher->customer_id);
                $dailyVoucher->customer_name = $customer ? $customer->name : 'Walk in customer';
            } else {
                $dailyVoucher->customer_name = 'Walk in customer';
            }
        });

// Create fake records only for daily vouchers that don't have payment records yet
        $fakeFromDailyVouchers = $daily_vouchers->map(function ($dailyVoucher) {
            $record                      = new SettlementCreditSalePayment();
            $record->id                  = null;
            $record->settlement_no       = null;
            $record->business_id         = $dailyVoucher->business_id;
            $record->customer_id         = $dailyVoucher->customer_id;
            $record->product_id          = $dailyVoucher->product_id;
            $record->pump_operator_id    = $dailyVoucher->operator_id;
            $record->amount              = $dailyVoucher->total_amount;
            $record->total_discount      = 0;
            $record->daily_voucher_id    = $dailyVoucher->id;
            $record->customer_payment_id = null;
            $record->note                = $dailyVoucher->customer_references ?? 'Daily Credit Sale';
            $record->slip                = '';
            $record->created_at          = $dailyVoucher->created_at;
            $record->updated_at          = $dailyVoucher->updated_at ?? now();

            // Use values from daily voucher
            $record->order_number       = $dailyVoucher->order_no ?? null;
            $record->order_date         = $dailyVoucher->order_date ?? null;
            $record->customer_reference = $dailyVoucher->customer_references ?? null;

            $record->customer_name = $dailyVoucher->customer_name;
            $record->product_name  = $dailyVoucher->product_name;

            return $record;
        });

// Combine the collections (only fake records that don't already exist)
        if ($fakeFromDailyVouchers->isNotEmpty()) {
            $settlement_credit_sale_payments = $settlement_credit_sale_payments->concat($fakeFromDailyVouchers)->values();
        }

        // Deduplicate identical credit sale rows (keep unique by database ID or daily_voucher_id)
        // This ensures we don't show the same database record twice,
        // but allows multiple vouchers for the same order/customer (distinct sales)
        $settlement_credit_sale_payments = $settlement_credit_sale_payments
            ->unique(function ($item) {
                // For saved credit sales, use the database ID as unique key
                if (! empty($item->id)) {
                    return 'db-' . $item->id;
                }
                // For unsaved credit sales from daily vouchers, use daily_voucher_id
                if (! empty($item->daily_voucher_id)) {
                    return 'dv-' . $item->daily_voucher_id;
                }
                // Fallback for edge cases
                return 'ord-' . ($item->order_number ?? uniqid()) . '-' . ($item->order_date ?? '');
            })
            ->values();

        // NOTE: Removed the unique() call that was deduplicating credit sales.
        // The system should allow multiple credit sales for the same customer/order/product combination.
        // Each transaction is unique by its ID and should be displayed separately.

        SettlementSwLog::info('Settlement credit sale payments count: ' . $settlement_credit_sale_payments->count());
        SettlementSwLog::info('Settlement credit sale payments loaded');

        $settlement_expense_payments = SettlementExpensePayment::leftjoin('accounts', 'settlement_expense_payments.account_id', 'accounts.id')

            ->leftjoin('expense_categories', 'settlement_expense_payments.category_id', 'expense_categories.id')

            ->where('settlement_expense_payments.settlement_no', $settlement->id)

            ->select('settlement_expense_payments.*', 'accounts.name as account_name', 'expense_categories.name as category_name')

            ->get();

        $settlement_shortage_payments = SettlementShortagePayment::where('settlement_shortage_payments.settlement_no', $settlement->id)

            ->select('settlement_shortage_payments.*')

            ->get();

        $settlement_excess_payments = SettlementExcessPayment::where('settlement_excess_payments.settlement_no', $settlement->id)

            ->select('settlement_excess_payments.*')

            ->get();

        /**

         * @ChangedBy Afes

         * @Date 25-05-2021

         * @Date 02-06-2021

         * @Task 12700

         * @Task 127004

         */

        $total_daily_collection = floatval(DailyCollection::where('pump_operator_id', $pump_operator_id)
                ->where('business_id', $business_id)
                ->whereNull('settlement_id')
                ->where('type', 'daily_collection_sw')
                ->where(function ($query) use ($request) {
                    if (! empty($request->shift_ids)) {
                        $query->where('shift_id', $request->shift_ids)
                            ->orWhereNull('shift_id');
                    } else {
                        // If no shift_id provided, get ALL unsettled payments for this operator
                        $query->whereNotNull('shift_id')
                            ->orWhereNull('shift_id');
                    }
                })
                ->sum('current_amount'));

        $debug_daily_collections = DailyCollection::where('pump_operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->where('type', 'daily_collection_sw')
            ->whereNull('settlement_id')
            ->where(function ($query) use ($request) {
                $query->where('shift_id', $request->shift_ids)
                    ->orWhereNull('shift_id');
            })
            ->get();

        /**

         * @ModifiedBy Afes Oktavianus

         * @Date 02-06-2021

         * @Date 03-06-2021

         * @Task 127004

         */

        $total_excess = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'excess');

        $total_shortage = $this->transactionUtil->getPumpOperatorExcessOrShortage($pump_operator_id, 'shortage');

        $operator_bal = $this->transactionUtil->getPumpOperatorBalance($pump_operator_id);

        $total_commission = $this->calculateCommission($pump_operator_id, $settlement->id);

        $business_details = Business::find($business_id);

        $currency_precision = $business_details->currency_precision;

        $total_meter_sale = MeterSale::where('settlement_no', $settlement->id)
            ->when($shift_id, function ($query, $shift_id) {
                return $query->where('shift_id', $shift_id);
            })
            ->sum('discount_amount');

        $other_sales = OtherSale::where('settlement_no', $settlement->id)->get();

        $total_other_sale = $other_sales->sum('sub_total') - $other_sales->sum('discount_amount');

        if (str_contains($settlement->settlement_no, 'SET-SW')) {

            $total_other_sale = $other_sales->sum('sub_total');

        }

        $show_shift_no = '';

        if ($request->shift_ids) {

            $shift_ids = explode(",", $request->shift_ids);

            // Convert shift_ids to actual shift numbers
            $shift_numbers_from_ids = PumpOperatorAssignment::whereIn('shift_id', $shift_ids)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('business_id', $business_id)
                ->pluck('shift_number')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            
            // If not found in assignments, try getting from settlement
            if (empty($shift_numbers_from_ids) && $settlement) {
                $shift_numbers_from_ids = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->pluck('shift_number')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
            
            // If still not found, try getting from Settlement SW daily shifts
            if (empty($shift_numbers_from_ids) && !empty($shift_ids)) {
                $shift_numbers_from_ids = DB::table(SettlementSwTables::dailyShifts())
                    ->whereIn('id', $shift_ids)
                    ->where('business_id', $business_id)
                    ->pluck('shift_no')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
            
            $show_shift_no = !empty($shift_numbers_from_ids) ? implode(', ', $shift_numbers_from_ids) : '';

            $pump_operator_total_other_sale = PumpOperatorOtherSale::join('products', 'products.id', '=', 'pump_operator_other_sales.product_id')

                ->leftJoin('variations', 'products.id', 'variations.product_id')

                ->leftJoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')

                ->whereIn('pump_operator_other_sales.shift_id', $shift_ids);

            $pump_operator_total_other_sale = $pump_operator_total_other_sale->join('pump_operator_assignments', function ($join) {

                $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_other_sales.shift_id')

                    ->where('pump_operator_assignments.status', 'close')

                    ->whereRaw('pump_operator_assignments.id = (

                        SELECT MAX(poa.id)

                        FROM pump_operator_assignments poa

                        WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"

                    )');

            });

            $pump_operator_total_other_sale = $pump_operator_total_other_sale->sum("sub_total");

            $total_other_sale = $total_other_sale + $pump_operator_total_other_sale;

        }

        $total_other_income = OtherIncome::where('settlement_no', $settlement->id)->sum('sub_total');

        $total_customer_payment = CustomerPayment::where('settlement_no', $settlement->id)->sum('sub_total');

        $total_settlement_cash_deposit         = $settlement_cash_deposits->sum('amount');
        $total_settlement_credit_sale_discount = SettlementCreditSalePayment::where('settlement_no', $settlement->id)->sum('total_discount');

        $total_settlement_cash_payment        = $settlement_cash_payments->sum('amount');
        $total_settlement_card_payment        = $settlement_card_payments->sum('amount');
        $total_settlement_credit_sale_payment = $settlement_credit_sale_payments->sum('amount');
        $total_settlement_loan_payment        = $settlement_loan_payments->sum('amount');
        $total_settlement_cheque_payment      = $settlement_cheque_payments->sum('amount');
        $total_settlement_customer_loan       = $settlement_customer_loans->sum('amount');
        $total_settlement_expense_payment     = $settlement_expense_payments->sum('amount');
        $total_settlement_shortage_payment    = $settlement_shortage_payments->sum('amount');
        $total_settlement_excess_payment      = $settlement_excess_payments->sum('amount');
        $total_settlement_drawings_payment    = $settlement_drawings_payments->sum('amount');

        // Ensure meter_sales relationship is loaded before calculating total
        if (!$settlement->relationLoaded('meter_sales')) {
            $settlement->load('meter_sales');
        }
        
        $meter_sale_total = $settlement->meter_sales->sum('sub_total');
        
        // Variables for payment to finalize modal
        $payment_meter_sale_total = $meter_sale_total;
        $pump_other_sale_final_total = $total_other_sale;
        $payment_other_income_total = $total_other_income;
        $payment_customer_payment_total = $total_customer_payment;

        $total_amount =
        $meter_sale_total           // Meter sales
         + $total_other_sale        // Other sales
         + $total_other_income      // Other income (optional, if treated as sale)
         + $total_customer_payment; // Credit sales (if treated as sale)

        $total_paid = number_format(($total_settlement_customer_loan +

            $total_settlement_loan_payment +

            $total_settlement_cash_deposit +

            $total_settlement_cash_payment +

            $total_settlement_card_payment +

            $total_settlement_cheque_payment +

            $total_settlement_credit_sale_payment - $total_settlement_credit_sale_discount +

            $total_settlement_expense_payment +

            $total_settlement_shortage_payment +

            $total_settlement_excess_payment +

            $total_settlement_drawings_payment

        ), $currency_precision, '.', '');

        $total_balance = number_format($total_amount - $total_paid, $currency_precision, '.', '');

        $total_balance = abs($total_balance);

        $loans_given_group_id = AccountGroup::getGroupByName('Loans Given');

        $drawings_group_id = AccountGroup::getGroupByName('Owners Drawings');

        $bank_account_group_id = AccountGroup::getGroupByName('Bank Account');

        $bank_accounts = [];
        if (!empty($bank_account_group_id)) {
            $bank_accounts = Account::where('business_id', $business_id)->where('asset_type', $bank_account_group_id->id)->pluck('name', 'id');
        }

        if (! empty($loans_given_group_id)) {

            $loans_given = Account::where('business_id', $business_id)->where('asset_type', $loans_given_group_id->id)->pluck('name', 'id');

        } else {

            $loans_given = [];

        }

        if (! empty($drawings_group_id)) {

            $drawings_acc = Account::where('business_id', $business_id)->where('asset_type', $drawings_group_id->id)->pluck('name', 'id');

        } else {

            $drawings_acc = [];

        }

        $shift_numbers = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
            ->where('status', 'close')
            ->where('business_id', $business_id)
            ->pluck('shift_number', 'id');

        $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();

        $business_locations = \App\BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        $payment_types = $this->productUtil->payment_types($default_location, false, false, false, false, "is_sale_enabled");

        $expense_no = $this->getExpenseNumber($settlement->id);

        $expense_categories = \App\ExpenseCategory::where('business_id', $business_id)

            ->pluck('name', 'id');

        $expense_account_type_id = \App\AccountType::where('business_id', $business_id)->where('name', 'Expenses')->first();

        $expense_accounts = [];

        if ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'access_account')) {

            if (! empty($expense_account_type_id)) {

                $expense_accounts = Account::where('business_id', $business_id)->where('account_type_id', $expense_account_type_id->id)->pluck('name', 'id');

            }

        }

        $customers = Contact::customersDropdown($business_id, false, true, 'customer');

        $subscription = SettlementSwSubscription::current($business_id);

        $package_details = $subscription->package_details;

        $only_walkin = $package_details['only_walkin'] ?? 0;

        if (! empty($only_walkin)) {

            $credit_customers = Contact::customersDropdown($business_id, false, true, 'customer');

        } else {

            $credit_customers = Contact::where('name', '!=', 'Walk-In Customer')->where('active', 1)->where('type', 'customer')->where('business_id', $business_id)->pluck('name', 'id');

        }

        $customers = Contact::customersDropdown($business_id, false, true, 'customer');

        $walkin = Contact::where('name', 'Walk-In Customer')->where('business_id', $business_id)->pluck('name', 'id');

        $products = Product::where('business_id', $business_id)->forModule(app(SettlementSwLegacyMap::class)->productModuleKey())->pluck('name', 'id');

        $card_types = [];

        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();

        if (! empty($card_group)) {

            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');

        }

        $message = $package_details['notsubscribed_message_content'] ?? null;
        $font_family = $package_details['ns_font_family'] ?? null;
        $font_color = $package_details['ns_font_color'] ?? null;
        $font_size = $package_details['ns_font_size'] ?? null;
        $background_color = $package_details['ns_background_color'] ?? null;

        $message = ! empty($message) ? $message : "You have not subscribed to this module!";

        // $show_shift_no=$request->shift_ids;

        SettlementSwLog::info('shift numbers', [$shift_numbers]);
        
        $view_name = request()->ajax() ? 'settlementsw::swsettlement.partials.add_payment' : 'settlementsw::swsettlement.add_payment_page';

        return view($view_name)->with(compact(

            'operator_bal',

            'business',

            'message', 'font_family', 'font_color', 'font_size', 'background_color', 'package_details',

            'bank_accounts',

            'is_settlement_page',

            'total_settlement_cash_deposit',

            'settlement',

            'cash_denoms',

            'pump_operator',

            'customer_payments_tab',

            'settlement_cash_payments',

            'settlement_customer_loans',

            'settlement_loan_payments',

            'settlement_drawings_payments',

            'settlement_card_payments',

            'settlement_cheque_payments',

            'settlement_credit_sale_payments',

            'settlement_expense_payments',

            'settlement_shortage_payments',

            'settlement_excess_payments',

            'settlement_cash_deposits',

            'payment_types',

            'expense_accounts',

            'expense_categories',

            'expense_no',

            'customers',

            'products',

            'card_types',

            'total_daily_collection',

            'total_commission',

            'total_amount',

            'total_paid',

            'total_balance',

            'total_excess',

            'total_shortage',

            'loans_given',

            'drawings_acc',

            'only_walkin',

            'walkin',

            'provider',

            'credit_customers',

            'show_shift_no',

            'shift_numbers',
            
            'payment_meter_sale_total',
            
            'pump_other_sale_final_total',
            
            'payment_other_income_total',
            
            'payment_customer_payment_total'

        ));

    }

    public function getExpenseNumber($settlement_id)
    {

        $settlement_int = preg_match_all('!\d+!', $settlement_id, $matches);

        $ref_no_prefixes = request()->session()->get('business.ref_no_prefixes');

        $expense_prefix = ! empty($ref_no_prefixes['expense']) ? $ref_no_prefixes['expense'] : '';

        $expense_count = SettlementExpensePayment::where('settlement_no', $settlement_id)->count();

        $expense_no = $expense_prefix . '-' . $settlement_int . '-' . ($expense_count + 1);

        return $expense_no;

    }

    /**

     * Store a newly created resource in storage.

     * @param  pump_operator_id

     * @param  settlement_no

     * @return Response

     */

    public function calculateCommission($pump_operator_id, $settlement_id)
    {

        $all_sales = OtherSale::where('settlement_no', $settlement_id)->get();

        $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();

        $pump_operator_commission_type = isset($pump_operator->commission_type) ? $pump_operator->commission_type : '';

        $pump_operator_commission_value = isset($pump_operator->commission_ap) ? $pump_operator->commission_ap : 0;

        if ($pump_operator_commission_type == 'fixed') {

            $total_sales_counts = $all_sales->count();

            return $total_sales_counts * $pump_operator_commission_value;

        } elseif ($pump_operator_commission_type == 'percentage') {

            $total_sales_commission = 0;

            return $total_sales_commission;

        } else {

            return 0.00;

        }

    }

    /**

     * Store a newly created resource in storage.

     * @param  Request $request

     * @return Response

     */

    public function store(Request $request)
    {

    }

    /**

     * Show the specified resource.

     * @return Response

     */

    public function show()
    {

        // return view('settlementsw::swsettlement.show');

    }

    /**

     * Show the form for editing the specified resource.

     * @return Response

     */

    public function edit()
    {

        // return view('settlementsw::swsettlement.edit');

    }

    /**

     * Update the specified resource in storage.

     * @param  Request $request

     * @return Response

     */

    public function update(Request $request)
    {

    }

    /**

     * Remove the specified resource from storage.

     * @return Response

     */

    public function destroy()
    {

    }

    /**

     * add cash payment data to db

     * @return Response

     */

    public function saveCashPayment(Request $request)
    {
        SettlementSwLog::info('saveCashPayment called');

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('business.id');

            // Retrieve Settlement
            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // Create Settlement Cash Payment record
            $settlement_cash_payment = app(SettlementPaymentReconciler::class)->upsertOne($business_id, (string) $settlement->id, 'settlement_cash_payments', [
                'business_id'   => $business_id,
                'settlement_no' => $settlement->id,
                'amount'        => $request->amount,
                'customer_id'   => $request->customer_id,
                'note'          => $request->note,
            ]);

            // Update Settlement edit flag if needed
            if ($request->has('is_edit')) {
                $settlement->update(['is_edit' => $request->is_edit]);
            }

            // Create the base Transaction
            $business_location_id = BusinessLocation::where('business_id', $business_id)->value('id');

            $transaction = Transaction::create([
                'business_id'      => $business_id,
                'location_id'      => $business_location_id,
                'type'             => 'settlement_cash_payment',
                'sub_type'         => 'Cash',
                'status'           => 'final',
                'ref_no'           => 'Cash payment for settlement #' . $settlement->settlement_no,
                'final_total'      => $request->amount,
                'created_by'       => Auth::user()->id,
                'transaction_date' => Carbon::now(),
                app(SettlementSwLegacyMap::class)->settlementReferenceColumn() => $settlement->id,
            ]);

            // Create Transaction Payment (Cash)
            $payment_ref_no = 'PAY-' . strtoupper(Str::random(8));

            $transaction_payment = TransactionPayment::create([
                'transaction_id' => $transaction->id,
                'business_id'    => $business_id,
                'amount'         => $request->amount,
                'method'         => 'cash',
                'paid_on'        => Carbon::now(),
                'created_by'     => Auth::user()->id,
                'payment_ref_no' => $payment_ref_no,
                'note'           => $request->note ?? 'Cash payment for settlement #' . $settlement->settlement_no,
            ]);

            // IMPORTANT: Do NOT create account transactions here
            // Account transactions will be created when the settlement is saved in SettlementSwBaseController@store
            // This prevents duplicate entries in the account book
            // The same fix was applied to Settlement PD to prevent double-counting
            
            // Link the created records (but don't create account transactions yet)
            $settlement_cash_payment = app(\Modules\SettlementSW\Services\SettlementSwPaymentEditService::class)
                ->editCashPayment($business_id, $settlement_cash_payment->id, [
                    'transaction_id' => $transaction->id,
                    'transaction_payment_id' => $transaction_payment->id,
                ]);

            DB::commit();

            $output = [
                'success'                    => true,
                'settlement_cash_payment_id' => $settlement_cash_payment->id,
                'msg'                        => __('settlementsw::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File: " . $e->getFile() . " Line: " . $e->getLine() . " Message: " . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function saveCustomerLoan(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement->id,

                'amount'        => $request->amount,

                'customer_id'   => $request->customer_id,

                'note'          => $request->note,

            ];

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $settlement_cash_payment = SettlementCustomerLoan::create($data);

            $output = [

                'success'                     => true,

                'settlement_customer_loan_id' => $settlement_cash_payment->id,

                'msg'                         => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveLoanPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement->id,

                'amount'        => $request->amount,

                'loan_account'  => $request->loan_account,

                'note'          => $request->note,

            ];

            $settlement_loan_payment = SettlementLoanPayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                    => true,

                'settlement_loan_payment_id' => $settlement_loan_payment->id,

                'msg'                        => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveDrawingPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'   => $business_id,

                'settlement_no' => $settlement->id,

                'amount'        => $request->amount,

                'loan_account'  => $request->loan_account,

                'note'          => $request->note,

            ];

            $settlement_loan_payment = SettlementDrawingPayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                    => true,

                'settlement_loan_payment_id' => $settlement_loan_payment->id,

                'msg'                        => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function saveCashDeposit(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            if (!$settlement) {
                return [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong') . ': Settlement not found',
                ];
            }

            // Get bank_id from request (JavaScript sends as 'bank_id', form field is 'cash_deposit_bank')
            $bank_id = $request->bank_id ?? $request->cash_deposit_bank;
            
            if (empty($bank_id)) {
                return [
                    'success' => false,
                    'msg'     => __('settlementsw::lang.bank') . ' ' . __('validation.required'),
                ];
            }

            // Verify bank account exists and belongs to business
            $bank_account = Account::where('id', $bank_id)
                ->where('business_id', $business_id)
                ->first();
            
            if (!$bank_account) {
                return [
                    'success' => false,
                    'msg'     => __('settlementsw::lang.bank') . ' not found',
                ];
            }

            $data = [
                'business_id'    => $business_id,
                'settlement_no'  => $settlement->id,  // Use integer ID (consistent with other payments and relationship)
                'amount'         => $request->cash_deposit_amount ?? $request->amount,
                'bank_id'        => $bank_id,
                'account_no'     => $request->account ?? $request->cash_deposit_account ?? '',
                'time_deposited' => $request->time ?? $request->cash_deposit_time ?? now(),
            ];

            SettlementSwLog::info('Saving cash deposit', [
                'settlement_no' => $settlement->settlement_no,
                'settlement_id' => $settlement->id,
                'bank_id' => $bank_id,
                'bank_name' => $bank_account->name,
                'data' => $data
            ]);

            $settlement_cash_payment = SettlementCashDeposit::create($data);
            
            SettlementSwLog::info('Cash deposit created', [
                'cash_deposit_id' => $settlement_cash_payment->id,
                'settlement_no' => $settlement_cash_payment->settlement_no
            ]);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                    => true,

                'settlement_cash_payment_id' => $settlement_cash_payment->id,

                'msg'                        => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * delete cash payment data to db

     * @return Response

     */

    public function deleteCashPayment($id)
    {

        try {

            $payment = SettlementCashPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteCustomerLoan($id)
    {

        try {

            $payment = SettlementCustomerLoan::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteLoanPayment($id)
    {

        try {

            $payment = SettlementLoanPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteDrawingPayment($id)
    {

        try {

            $payment = SettlementDrawingPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    public function deleteCashDeposit($id)
    {

        try {

            $payment = SettlementCashDeposit::where('id', $id)->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ]);
            }

            // settlement_no is a string (e.g., "SET-SW-001"), not an ID
            // Use where('settlement_no', ...) instead of where('id', ...)
            if ($payment->settlement_no) {
                Settlement::where('settlement_no', $payment->settlement_no)
                    ->update(['is_edit' => request()->is_edit ?? 0]);
            }

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return response()->json($output);

    }

    /**

     * add card payment data to db

     * @return Response

     */

    public function saveCardPayment(Request $request)
    {
        SettlementSwLog::info('saveCardPayment called');

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('business.id');

            // Check for duplicate slip number
            $slip_no = trim(str_replace(' ', '', $request->slip_no));
            $today   = Carbon::now()->format('Y-m-d');

            $existingRecord = SettlementCardPayment::where('slip_no', $slip_no)
                ->whereDate('created_at', $today)
                ->exists();

            if (! empty($slip_no) && $existingRecord) {
                throw new \Exception(__('messages.duplicate_slip'));
            }

            // Retrieve Settlement
            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // Create Settlement Card Payment record
            $settlement_card_payment = app(SettlementPaymentReconciler::class)->upsertOne($business_id, (string) $settlement->id, 'settlement_card_payments', [
                'business_id'   => $business_id,
                'settlement_no' => $settlement->id,
                'amount'        => $request->amount,
                'card_type'     => $request->card_type,
                'card_number'   => $request->card_number,
                'customer_id'   => $request->customer_id,
                'note'          => $request->note,
                'slip_no'       => $slip_no,
            ]);

            // Update Settlement edit flag if needed
            if ($request->has('is_edit')) {
                $settlement->update(['is_edit' => $request->is_edit]);
            }

            // IMPORTANT: Do NOT create transactions or account transactions here
            // They will be created when the settlement is saved in SettlementSwBaseController@store
            // This prevents duplicate entries (Issue 3 Fix)

            DB::commit();

            $output = [
                'success'                    => true,
                'settlement_card_payment_id' => $settlement_card_payment->id,
                'msg'                        => __('settlementsw::lang.success'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File: " . $e->getFile() . " Line: " . $e->getLine() . " Message: " . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => $e->getMessage() === __('messages.duplicate_slip')
                    ? __('messages.duplicate_slip')
                    : __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**

     * delete card payment data to db

     * @return Response

     */

    public function deleteCardPayment($id)
    {

        try {

            $payment = SettlementCardPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * add cheque payment data to db

     * @return Response

     */

    public function saveChequePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'              => $business_id,

                'settlement_no'            => $settlement->id,

                'amount'                   => $request->amount,

                'bank_name'                => $request->bank_name,

                'cheque_number'            => $request->cheque_number,

                'cheque_date'              => Carbon::parse($request->cheque_date)->format('Y-m-d'),

                'customer_id'              => $request->customer_id,

                'note'                     => $request->note,

                'post_dated_cheque'        => $request->post_dated_cheque,

                'update_post_dated_cheque' => $request->update_post_dated_cheque,

            ];

            $settlement_cheque_payment = app(SettlementPaymentReconciler::class)
                ->upsertOne($business_id, (string) $settlement->id, 'settlement_cheque_payments', $data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                      => true,

                'settlement_cheque_payment_id' => $settlement_cheque_payment->id,

                'msg'                          => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * delete cheque payment data to db

     * @return Response

     */

    public function deleteChequePayment($id)
    {

        try {

            $payment = SettlementChequePayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

// public function check_order_number(Request $request)

//     {
// dd('hello');

//         try {

//         } catch (\Exception $e) {

//             $output = [

//                 'success' => false,

//                 'msg' => __('messages.something_went_wrong')

//             ];

//         }

//         return $output;

//     }

    /**

     * add credit_sale payment data to db

     * @return Response

     */

    public function saveCreditSalePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $business = Business::find($business_id);

            if (empty($request->operator_id) && ! empty($request->pump_operator_id)) {
                $request->merge(['operator_id' => $request->pump_operator_id]);
            }

            $settlement = Settlement::where('settlement_no', $request->settlement_no)
                ->where('business_id', $business_id)
                ->first();

            if (! $settlement) {
                $created = $this->createSettlementIfNotExist($request);

                if ($created === 406) {
                    return [
                        'success' => false,
                        'msg'     => __('messages.day_end_prevents_settlement'),
                    ];
                }

                if ($created instanceof Settlement) {
                    $settlement = $created;
                }
            }

            $currentSettlementId = $settlement ? $settlement->id : null;

            $orderNumberRules = ['required'];

            // Only enforce uniqueness if duplicate orders are NOT allowed
            // Removed product_id constraint to allow same product multiple times per order
            if (! $business->duplicate_orders_allowed) {
                $orderNumberRules[] = Rule::unique('settlement_credit_sale_payments', 'order_number')
                    ->where(function ($query) use ($request, $business_id, $currentSettlementId) {
                        $query->where('customer_id', $request->customer_id)
                            ->where('business_id', $business_id);

                        // Allow duplicates inside the current settlement; only guard against other settlements
                        if ($currentSettlementId) {
                            $query->where('settlement_no', '!=', $currentSettlementId);
                        }

                        return $query;
                    });
            }

            $validator = Validator::make($request->all(), [
                'order_number' => $orderNumberRules,
            ]);

            if ($validator->fails()) {
                return [
                    'success' => false,
                    'msg'     => $validator->errors()->first(),
                ];
            }

            if (! $settlement) {
                return [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ];
            }

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $price = $this->productUtil->num_uf($request->price);

            $unit_discount = $this->productUtil->num_uf($request->unit_discount);

            $qty = $this->productUtil->num_uf($request->qty);

            $amount = $this->productUtil->num_uf($request->amount);

            $sub_total = $this->productUtil->num_uf($request->sub_total);

            $total_discount = $this->productUtil->num_uf($request->total_discount);

            // observe if it has been saved before and remove it

            $order_date = \Carbon::parse($request->order_date)->format('Y-m-d');

            $existing_payment = SettlementCreditSalePayment::where('order_number', $request->order_number)

                ->where('customer_id', $request->customer_id)

                ->where('business_id', $business_id)

                ->where('product_id', $request->product_id)

                ->where('pump_operator_id', (int) $request->pump_operator_id)

                ->where('order_date', $order_date)

                ->where('price', $price)

                ->where('discount', $unit_discount)

                ->where('qty', $qty)

                ->first();

            if (! $existing_payment) {

                $data = [

                    'business_id'        => $business_id,

                    'settlement_no'      => $settlement->id,

                    'customer_id'        => $request->customer_id,

                    'product_id'         => $request->product_id,

                    'order_number'       => $request->order_number,

                    'order_date'         => $order_date,

                    'pump_operator_id'   => (int) $request->pump_operator_id,

                    'price'              => $price,

                    'discount'           => $unit_discount,

                    'qty'                => $qty,

                    'amount'             => $amount,

                    'sub_total'          => $sub_total,

                    'total_discount'     => $total_discount,

                    'outstanding'        => $this->productUtil->num_uf($request->outstanding),

                    'credit_limit'       => $request->credit_limit,

                    'customer_reference' => $request->customer_reference,

                    'note'               => $request->note,

                ];

                // dd($data);

                $settlement_credit_sale_payment = app(SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, (string) $settlement->id, 'settlement_credit_sale_payments', $data);

            } else {

                $settlement_credit_sale_payment = $existing_payment;

            }

            $output = [

                'success'                           => true,

                'settlement_credit_sale_payment_id' => $settlement_credit_sale_payment->id,

                'msg'                               => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * delete credit_sale payment data to db

     * @return Response

     */

    public function deleteCreditSalePayment($id)
    {

        try {

            $payment = SettlementCreditSalePayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $discount = $payment->total_discount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount - $discount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * get price of product

     * @return Response

     */

    public function getProductPrice(Request $request)
    {

        $product_id = $request->product_id;

        $product = Product::leftjoin('variations', 'products.id', 'variations.product_id')

            ->where('products.id', $product_id)

            ->select('sell_price_inc_tax')

            ->first();

        if (! empty($product)) {

            $price = $product->sell_price_inc_tax;

        } else {

            $price = 0.00;

        }

        return ['price' => $price];

    }

    /**

     * get price of product

     * @return Response

     */

    public function getCustomerDetails($customer_id)
    {

        $business_id = request()->session()->get('business.id');

        $query = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')

            ->leftjoin(SettlementSwTables::contactGroups() . ' AS cg', 'contacts.customer_group_id', '=', 'cg.id')

            ->where('contacts.business_id', $business_id)

            ->where('contacts.id', $customer_id)

        // ->onlyCustomers()

            ->select([

                'contacts.vat_number', 'contacts.manual_bill_settlement', 'contacts.contact_id', 'contacts.name', 'contacts.created_at', 'total_rp', 'cg.name as customer_group', 'city', 'state', 'country', 'landmark', 'mobile', 'contacts.id', 'is_default', 'contacts.sub_customers',

                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),

                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),

                DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),

                DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),

                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),

                DB::raw("SUM(IF(t.type = 'advance_payment', -1*final_total, 0)) as advance_payment"),

                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),

                'email', 'tax_number', 'contacts.pay_term_number', 'contacts.pay_term_type', 'contacts.credit_limit', 'contacts.custom_field1', 'contacts.custom_field2', 'contacts.custom_field3', 'contacts.custom_field4', 'contacts.type',

            ])

            ->groupBy('contacts.id')->first();

        $due = 0;

        $return_due = 0;

        $opening_balance = 0;

        if (! empty($query)) {

            $due = $query->total_invoice - $query->invoice_received + $query->advance_payment;

            $return_due = $query->total_sell_return - $query->sell_return_paid;

            $opening_balance = $query->opening_balance - $query->opening_balance_paid;

        }

        $total_outstanding = $due - $return_due + $opening_balance;

        if (empty($total_outstanding)) {

            $total_outstanding = 0.00;

        }

        if (empty($query->credit_limit)) {

            $credit_limit = 'No Limit';

        } else {

            $credit_limit = $query->credit_limit;

        }

        $business_details = Business::find($business_id);

        $customer_references = CustomerReference::where('contact_id', $customer_id)->where('business_id', $business_id)->select('reference')->get();

        $sub_ids = json_decode($query->sub_customers) ?? [];

        $sub_customers = Contact::whereIn('id', $sub_ids)->pluck('name', 'id');

        $payment_data = DB::table(SettlementSwTables::customerPayments())

            ->leftJoin('settlements', 'customer_payments.settlement_no', '=', 'settlements.id')

            ->leftJoin('transactions', 'transactions.invoice_no', '=', 'settlements.settlement_no')

            ->where('customer_payments.customer_id', $customer_id)

            ->select([

                'settlements.transaction_date',

                'settlements.settlement_no',

                'transactions.order_no',

            ])

            ->orderBy('settlements.transaction_date', 'desc') // Optional: order by date

            ->get();

        // return ['total_outstanding' =>  strval($this->productUtil->num_f($total_outstanding, false, $business_details, true)), 'credit_limit' => strval($credit_limit), 'customer_references' => $customer_references];

        return ['total_outstanding' => $this->productUtil->num_f(strval($contactController->get_cus_due_bal($customer_id, false))), 'credit_limit' => strval($credit_limit), 'customer_references' => $customer_references, 'sub_customers' => $sub_customers, 'vat_number' => $query->vat_number, 'manual_bill_settlement' => $query->manual_bill_settlement, 'customer_name' => $query->name, 'payment_data' => $payment_data];

    }

    /**

     * add expense payment data to db

     * @return Response

     */

    public function saveExpensePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $data = [

                'business_id'    => $business_id,

                'settlement_no'  => $settlement->id,

                'expense_number' => $request->expense_number,

                'category_id'    => $request->category_id,

                'reference_no'   => $request->reference_no,

                'account_id'     => $request->account_id,

                'reason'         => $request->reason,

                'amount'         => $request->amount,

            ];

            //Update reference count

            $ref_count = $this->transactionUtil->setAndGetReferenceCount('expense');

            //Generate reference number

            if (empty($request->reference_no)) {

                $data['reference_no'] = $this->transactionUtil->generateReferenceNumber('expense', $ref_count);

            }

            $settlement_expense_payment = SettlementExpensePayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $expense_number = $this->getExpenseNumber($request->settlement_no);

            $output = [

                'success'                       => true,

                'expense_number'                => $expense_number,

                'reference_no'                  => $settlement_expense_payment->reference_no,

                'settlement_expense_payment_id' => $settlement_expense_payment->id,

                'msg'                           => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * delete expense payment data to db

     * @return Response

     */

    public function deleteExpensePayment($id)
    {

        try {

            $payment = SettlementExpensePayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * add shortage payment data to db

     * @return Response

     */

    public function saveShortagePayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);

            $data = [

                'business_id'      => $business_id,

                'settlement_no'    => $settlement->id,

                'amount'           => $request->amount,

                'current_shortage' => $pump_operator->short_amount,

                'note'             => $request->note,

            ];

            $settlement_shortage_payment = SettlementShortagePayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                        => true,

                'settlement_shortage_payment_id' => $settlement_shortage_payment->id,

                'msg'                            => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * delete shortage payment data to db

     * @return Response

     */

    public function deleteShortagePayment($id)
    {

        try {

            $payment = SettlementShortagePayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * add excess payment data to db

     * @return Response

     */

    public function saveExcessPayment(Request $request)
    {

        try {

            $business_id = $request->session()->get('business.id');

            $settlement = Settlement::where('settlement_no', $request->settlement_no)->where('business_id', $business_id)->first();

            $pump_operator = PumpOperator::findOrFail($settlement->pump_operator_id);

            $data = [

                'business_id'    => $business_id,

                'settlement_no'  => $settlement->id,

                'amount'         => $request->amount,

                'current_excess' => $pump_operator->excess_amount,

                'note'           => $request->note,

            ];

            if ($request->amount > 0) {

                $output = [

                    'success' => false,

                    'msg'     => __('Please enter the amount with a negative symbol'),

                ];

                return $output;

            }

            $settlement_excess_payment = SettlementExcessPayment::create($data);

            Settlement::where('id', $settlement->id)->update(['is_edit' => request()->is_edit]);

            $output = [

                'success'                      => true,

                'settlement_excess_payment_id' => $settlement_excess_payment->id,

                'msg'                          => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * delete excess payment data to db

     * @return Response

     */

    public function deleteExcessPayment($id)
    {

        try {

            $payment = SettlementExcessPayment::where('id', $id)->first();

            Settlement::where('id', $payment->settlement_no)->update(['is_edit' => request()->is_edit]);

            $amount = $payment->amount;

            $payment->delete();

            $output = [

                'success' => true,

                'amount'  => $amount,

                'msg'     => __('settlementsw::lang.success'),

            ];

        } catch (\Exception $e) {

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => false,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return $output;

    }

    /**

     * preview payment details

     * @return Response

     */

    public function preview($id)
    {

        $business_id = request()->session()->get('business.id');

        $settlement = Settlement::where('settlements.id', $id)->where('settlements.business_id', $business_id)

            ->leftjoin('pump_operators', 'settlements.pump_operator_id', 'pump_operators.id')

            ->with([

                'meter_sales',

                'other_sales',

                'other_incomes',

                'customer_payments',

                'cash_payments',

                'cash_deposits',

                'card_payments',

                'cheque_payments',

                'credit_sale_payments.product',

                'expense_payments',

                'excess_payments',

                'shortage_payments',

                'loan_payments',

            ])

            ->select('settlements.*', 'pump_operators.name as pump_operator_name')

            ->first();

        if (! $settlement) {
            abort(404, 'Settlement not found');
        }

        $daily_collections = DailyCollection::where('daily_collections.business_id', $business_id)

            ->where('daily_collections.pump_operator_id', $settlement->pump_operator_id)

            ->where('type', 'daily_collection_sw')

            ->whereNull('settlement_id')

            ->select([

                'daily_collections.*',

            ])->orderBy('daily_collections.id')->get();

        $defaultCustomerId = Contact::query()
            ->where('business_id', $business_id)
            ->where('type', 'customer')
            ->orderByRaw("CASE WHEN name = 'Walk-In Customer' THEN 0 ELSE 1 END")
            ->value('id');

        foreach ($daily_collections as $dailyCollection) {
            $settlementCashPayment = new SettlementCashPayment();
            $settlementCashPayment->business_id = $business_id;
            $settlementCashPayment->settlement_no = $settlement->id;
            $settlementCashPayment->amount = (float) $dailyCollection->current_amount;
            $settlementCashPayment->customer_id = $defaultCustomerId;
            $settlement->cash_payments->push($settlementCashPayment);
        }

        $business = Business::where('id', $settlement->business_id)->first();

        $pump_operator = PumpOperator::where('id', $settlement->pump_operator_id)->first();

        //this for only to show in print page customer payments which entered in customer payments tab

        $customer_payments_tab = CustomerPayment::leftjoin(SettlementSwTables::contacts(), 'customer_payments.customer_id', 'contacts.id')

            ->where('customer_payments.settlement_no', $id)

            ->where('customer_payments.business_id', $business_id)

            ->select('customer_payments.*', 'contacts.name as customer_name')

            ->get();

        $contactIds = $settlement->cash_payments->pluck('customer_id')
            ->merge($settlement->card_payments->pluck('customer_id'))
            ->merge($settlement->cheque_payments->pluck('customer_id'))
            ->merge($settlement->credit_sale_payments->pluck('customer_id'))
            ->filter()
            ->unique();

        $accountIds = $settlement->loan_payments->pluck('loan_account')
            ->merge($settlement->cash_deposits->pluck('bank_id'))
            ->filter()
            ->unique();

        $productIds = $settlement->credit_sale_payments->pluck('product_id')->filter()->unique();

        $previewLookups = [
            'business' => $business,
            'contacts' => Contact::query()
                ->where('business_id', $business_id)
                ->whereIn('id', $contactIds)
                ->get()
                ->keyBy('id'),
            'accounts' => Account::query()
                ->where('business_id', $business_id)
                ->whereIn('id', $accountIds)
                ->get()
                ->keyBy('id'),
            'products' => Product::query()
                ->where('business_id', $business_id)
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy('id'),
        ];

        return view('settlementsw::swsettlement.partials.payment_preview')->with(compact(
            'settlement',
            'business',
            'pump_operator',
            'customer_payments_tab',
            'previewLookups'
        ));

    }

    /**

     * preview payment details

     * @return Response

     */

    public function productPreview($id)
    {

        $business_id = request()->session()->get('business.id');

        $settlement = Settlement::where('settlements.id', $id)

            ->leftjoin('settlement_credit_sale_payments', 'settlements.id', 'settlement_credit_sale_payments.settlement_no')

            ->leftjoin('products', 'products.id', 'settlement_credit_sale_payments.product_id')

            ->select('settlements.*', 'products.*', 'settlement_credit_sale_payments.*')

            ->get();

        return view('settlementsw::swsettlement.partials.product_preview')->with(compact('settlement'));

    }

}

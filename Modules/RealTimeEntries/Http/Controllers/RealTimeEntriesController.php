<?php

namespace Modules\RealTimeEntries\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\UserStorePermission;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Petro\Entities\DailyCard;
use Modules\Petro\Entities\DailyChequePayment;
use Modules\Petro\Entities\DailyCollection;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\PetroDailyShift;
use Modules\Petro\Entities\PetroShift;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PumpOperatorAssignment;
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\PumpOperatorMeterSaleDetail;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use Modules\Petro\Entities\PumpOperatorPayment;
use Modules\Petro\Entities\Settlement;
use Yajra\DataTables\Facades\DataTables;

class RealTimeEntriesController extends Controller
{

    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $commonUtil;
    protected $notificationUtil;
    protected $businessUtil;

    private $barcode_types;

    const FUEL_CATEGORY_ID = 1;

    /**
     * Constructor
     *
     * @param ProductUtil $product
     * @return void
     */
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil, NotificationUtil $notificationUtil)
    {
        $this->commonUtil       = $commonUtil;
        $this->productUtil      = $productUtil;
        $this->moduleUtil       = $moduleUtil;
        $this->transactionUtil  = $transactionUtil;
        $this->businessUtil     = $businessUtil;
        $this->notificationUtil = $notificationUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'real_time_entries')) {
            abort(403, 'Unauthorized Access');
        }

        if ($request->ajax()) {

            if ($request->has('get_shifts') && $request->pump_operator_id) {
                $operator_id = $request->pump_operator_id;

                $shifts = PumpOperatorAssignment::join('petro_shifts', 'petro_shifts.id', '=', 'pump_operator_assignments.shift_id')
                    ->where('pump_operator_assignments.pump_operator_id', $operator_id)
                    ->where('pump_operator_assignments.business_id', $business_id)
                    ->where('pump_operator_assignments.status', 'open')
                    ->where(function ($query) {
                        $query->where('petro_shifts.status', '0')
                            ->orWhereNull('petro_shifts.closed_time');
                    })
                    ->orderBy('pump_operator_assignments.shift_number', 'desc')
                    ->pluck('pump_operator_assignments.shift_number', 'pump_operator_assignments.shift_number');

                return response()->json($shifts);
            }

            if ($request->has('get_shifts_for_operator') && $request->pump_operator_id) {
                $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')
                    ->where('petro_shifts.pump_operator_id', $request->pump_operator_id)
                    ->where('petro_shifts.business_id', $business_id)
                    ->select('petro_shifts.id', 'pump_operators.name', 'petro_shifts.shift_date', 'petro_shifts.closed_time')
                    ->orderBy('petro_shifts.id', 'DESC')
                    ->get()
                    ->mapWithKeys(function ($shift) {
                        $label = $shift->name . ' (' . format_date($shift->shift_date) . ' to ' . (!empty($shift->closed_time) ? format_datetime($shift->closed_time) : 'Open') . ')';
                        return [$shift->id => $label];
                    });

                return response()->json($shifts);
            }

            // Add this new condition for fetching pending pumps

            if ($request->has('get_pending_pumps') && $request->pump_operator_id && $request->shift_id) {
                $operator_id  = $request->pump_operator_id;
                $shift_number = $request->shift_id; // Actually this is shift_number

                // Only assignments that have not had meter readings saved yet (Enter Meters sets pump_operator_other_sale_id).
                $pending_pumps = PumpOperatorAssignment::leftJoin('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
                    ->leftJoin('products', 'products.id', 'pumps.product_id')
                    ->leftJoin('variations', 'variations.product_id', 'products.id')
                    ->join('petro_shifts', 'petro_shifts.id', 'pump_operator_assignments.shift_id')
                    ->where('petro_shifts.status', '0')
                    ->where('pump_operator_assignments.pump_operator_id', $operator_id)
                    ->where('pump_operator_assignments.shift_number', $shift_number) // Use shift_number instead of shift_id
                    ->where('pump_operator_assignments.business_id', $business_id)
                    ->where('pump_operator_assignments.status', 'open')
                    ->whereNull('pump_operator_assignments.pump_operator_other_sale_id')
                    ->select(
                        'pump_operator_assignments.id',
                        'pump_operator_assignments.starting_meter',
                        'pump_operator_assignments.pump_id',
                        'variations.sell_price_inc_tax',
                        'pumps.pump_no',
                        'products.name as product_name'
                    )
                    ->get()
                    // One row per assignment (leftJoin variations can duplicate pumps)
                    ->unique('id')
                    ->values();

                Log::info('Fetched pending pumps count: ' . $pending_pumps->count());
                Log::info('Pending pumps data:', $pending_pumps->toArray());

                $balance_to_deposit = $this->calculateBalanceToDeposit($operator_id, $shift_number, $business_id);

                return response()->json([
                    'pending_pumps'      => $pending_pumps,
                    'balance_to_deposit' => $balance_to_deposit,
                ]);
            }

            if ($request->has('get_balance') && $request->customer_id) {
                $customer_id = $request->customer_id;
                $balance     = Contact::find($customer_id)->balance ?? 0;
                return response()->json($balance);
            }

            if ($request->has('submit_cheques') && $request->customer) {
                $count            = count($request->customer);
                $shift_number     = $request->input('shift_number');
                $pump_operator_id = $request->input('pump_operator_id') ?: Auth::user()->pump_operator_id;
                $txn_date_input   = $request->input('transaction_date');
                $operation_date   = ! empty($txn_date_input) ? $txn_date_input : now();

                // Resolve shift_id from pump_operator_assignments
                $assignment_query = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id);
                if (! empty($shift_number)) {
                    $assignment_query->where('shift_number', $shift_number);
                }
                $assignment = $assignment_query
                    ->orderBy('id', 'DESC')
                    ->first();
                $shift_id = $assignment->shift_id ?? null;

                // Resolve collection_form_no
                $lastCollection = PumpOperatorPayment::where('business_id', $business_id)
                    ->whereNotNull('collection_form_no')
                    ->orderBy('id', 'DESC')
                    ->value('collection_form_no');
                $collection_form_no = $lastCollection ? ((int) $lastCollection + 1) : 1;

                // Resolve account id for Cheques in Hand
                $cheque_account_id = $this->commonUtil->account_exist_return_id('Cheques in Hand');

                $settings = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();
                $dashboard_settings = (! is_null($settings)) ? json_decode($settings->dashboard_settings, true) : [];
                $update_account = ($dashboard_settings['real_time_update_account_books'] ?? 'no') === 'yes';

                for ($i = 0; $i < $count; $i++) {
                    $amount     = (float) $request->amount[$i];
                    $customerId = $request->customer[$i];
                    $bank       = $request->bank[$i] ?? null;
                    $chequeNo   = $request->cheque_no[$i] ?? null;
                    $chequeDate = $request->cheque_date[$i] ?? null;

                    // Save PumpOperatorPayment with correct field names
                    $pmt = PumpOperatorPayment::create([
                        'business_id'        => $business_id,
                        'pump_operator_id'   => $pump_operator_id,
                        'payment_type'       => 'cheque',
                        'payment_amount'     => $amount,
                        'shift_id'           => $shift_id,
                        'collection_form_no' => $collection_form_no,
                        'created_by'         => auth()->id(),
                    ]);

                    // Save DailyChequePayment so the join in paymentSummary works
                    DailyChequePayment::create([
                        'linked_payment_id'  => $pmt->id,
                        'business_id'        => $business_id,
                        'customer_id'        => $customerId,
                        'amount'             => $amount,
                        'bank_name'          => $bank,
                        'cheque_number'      => $chequeNo,
                        'cheque_date'        => $chequeDate,
                        'shift_id'           => $shift_id,
                        'collection_form_no' => $collection_form_no,
                    ]);

                    $collection_form_no++;

                    // Post debit to Cheques in Hand Account Book
                    if (! empty($cheque_account_id) && $amount > 0 && $update_account) {
                        AccountTransaction::createAccountTransaction([
                            'amount'         => $amount,
                            'account_id'     => $cheque_account_id,
                            'type'           => 'debit',
                            'operation_date' => $operation_date,
                            'note'           => 'Real Time Cheque - Shift No. ' . ($shift_number ?: '-') . ' - ' . ($bank ?: '') . ' / ' . ($chequeNo ?: ''),
                            'cheque_number'  => $chequeNo,
                            'bank_name'      => $bank,
                            'cheque_date'    => $chequeDate,
                            'shift_number'   => $shift_number,
                            'created_by'     => auth()->id(),
                        ]);
                    }
                }

                return response()->json([
                    'success' => true,
                    'collection_form_no' => $collection_form_no - 1
                ]);
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $pump_operators     = PumpOperator::where('business_id', $business_id)
            ->where('active', 1)
            ->pluck('name', 'id');

        $customers              = Contact::customersDropdown($business_id, false, true, 'customer');
        $pop_up                 = true;
        $settings               = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->select('dashboard_settings')->first();
        $dashboard_settings     = (! is_null($settings)) ? json_decode($settings->dashboard_settings, true) : [];
        $meter_sales_compulsory = $dashboard_settings['meter_sales_compulsory'] ?? 'no';
        $meter_sales_compulsory = ($meter_sales_compulsory == "yes");

        $shift_id         = $request->input('shift_number');
        $pump_operator_id = $request->input('pump_operator_id');

        $meter_sale = PumpOperatorMeterSale::where('shift_id', $shift_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            // ->whereNull('p_o_payment_id')
            ->orderBy('id', 'DESC')
            ->first();
        if (! is_null($meter_sale)) {
            $collection_form_no     = $meter_sale->collection_form_no;
            $meter_sales_compulsory = false;
        }

        $daily_cards = DailyCard::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->whereNull('used_status')
            ->sum('amount');
        $pending_vouchers = DailyVoucher::where('business_id', $business_id)
            ->where('operator_id', $pump_operator_id)
            ->whereNull('settlement_no')
            ->sum('total_amount');

        $daily_shortage_excess = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('shift_id', $shift_id)
            ->where(function ($query) {
                $query->whereNull('is_used')->orWhere('is_used', 0);
            })
            ->whereIn('payment_type', ['shortage', 'excess', 'other', 'cash', 'cheque'])
            ->sum('payment_amount');

        $all_pending_payments = $daily_cards + $pending_vouchers + $daily_shortage_excess;

        $already_entered = PumpOperatorMeterSaleDetail::join('pump_operator_assignments', 'pump_operator_assignments.pump_operator_other_sale_id', 'pump_operator_meter_sale_details.id')
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->where('pump_operator_assignments.closed_in_settlement', 0)
            ->where('pump_operator_assignments.status', 'open')
            ->sum('pump_operator_meter_sale_details.amount');
        $balance_to_deposit = abs($already_entered - $all_pending_payments);

        $card_pmt_type = 'bulk';
        if (! empty($settings) && ! empty($settings['card_amount_to_enter'])) {
            $card_pmt_type = $settings['card_amount_to_enter'];
        }

        $card_types = [];
        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (! empty($card_group)) {
            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        if (! empty($dashboard_settings) && ! empty($dashboard_settings['enter_card_numbers'])) {
            $enter_card_numbers = $dashboard_settings['enter_card_numbers'];
        } else {
            $enter_card_numbers = 'yes';
        }

        $bank_account_group_id = AccountGroup::getGroupByName('Bank Account');
        $bank_accounts         = Account::where('business_id', $business_id)->where('asset_type', $bank_account_group_id->id)->pluck('name', 'name');

        $business     = Business::where('id', $business_id)->first();
        $pos_settings = json_decode($business->pos_settings, true);
        $cash_denoms  = ! empty($pos_settings['cash_denominations']) ? explode(',', $pos_settings['cash_denominations']) : [];
        $products     = Product::where('business_id', $business_id)->pluck('name', 'id');

        // Remove the existing pending_pumps query from the non-AJAX section since we're now fetching via AJAX
        $pending_pumps = collect();

        return view('realtimeentries::index')->with(compact(
            'business_locations',
            'pump_operators',
            'customers',
            'pop_up',
            'meter_sales_compulsory',
            'balance_to_deposit',
            'card_pmt_type',
            'card_types',
            'enter_card_numbers',
            'bank_accounts',
            'cash_denoms',
            'business',
            'products',
            'pending_pumps'
        ));
    }

    private function calculateBalanceToDeposit($operator_id, $shift_id, $business_id)
    {
        $daily_cards = DailyCard::where('business_id', $business_id)
            ->where('pump_operator_id', $operator_id)
            ->whereNull('used_status')
            ->sum('amount');

        $pending_vouchers = DailyVoucher::where('business_id', $business_id)
            ->where('operator_id', $operator_id)
            ->whereNull('settlement_no')
            ->sum('total_amount');

        $daily_shortage_excess = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $operator_id)
            ->where('shift_id', $shift_id)
            ->where(function ($query) {
                $query->whereNull('is_used')->orWhere('is_used', 0);
            })
            ->whereIn('payment_type', ['shortage', 'excess', 'other', 'cash', 'cheque'])
            ->sum('payment_amount');

        $all_pending_payments = $daily_cards + $pending_vouchers + $daily_shortage_excess;

        $already_entered = PumpOperatorMeterSaleDetail::join('pump_operator_assignments', 'pump_operator_assignments.pump_operator_other_sale_id', 'pump_operator_meter_sale_details.id')
            ->where('pump_operator_assignments.pump_operator_id', $operator_id)
            ->where('pump_operator_assignments.closed_in_settlement', 0)
            ->where('pump_operator_assignments.status', 'open')
            ->sum('pump_operator_meter_sale_details.amount');

        return abs($already_entered - $all_pending_payments);
    }

    public function otherSales()
    {
        $user = auth()->user();

        if ($user->is_pump_operator) {
            $permission = UserStorePermission::where('user_id', $user->id)->first();
            if (! $permission || ! $permission->sell) {
                return back()->with('status', 'Please Request Sale Permission from Owner');
            }
        }

        // Get products that belong to the same business as the user
        // and exclude fuel products (based on category)
        $products = Product::leftJoin('variations', 'variations.product_id', '=', 'products.id')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('variation_location_details', 'variation_location_details.variation_id', '=', 'variations.id')
            ->where('products.business_id', $user->business_id) // Filter by user's business ID
            ->where(function ($q) {
                $q->whereNull('products.category_id')
                  ->orWhere('products.category_id', '!=', self::FUEL_CATEGORY_ID);
            })
            ->select(
                'products.*',
                'units.actual_name as unit',
                DB::raw('SUM(variation_location_details.qty_available) as current_stock'),
            )
            ->groupBy('products.id')
            ->get();

        $pump_operators = PumpOperator::where('business_id', $user->business_id)
            ->where('active', 1)
            ->pluck('name', 'id');

        return view('realtimeentries::other_sales')->with(compact('products', 'pump_operators'));
    }

    public function getShiftsByOperator(Request $request)
    {
        $business_id = Auth::user()->business_id;

        $shifts = PetroShift::leftJoin('pump_operators', 'pump_operators.id', '=', 'petro_shifts.pump_operator_id')
            ->leftJoin('pump_operator_assignments', function ($join) {
                $join->on('pump_operator_assignments.shift_id', '=', 'petro_shifts.id')
                     ->whereRaw('pump_operator_assignments.id = (
                         SELECT MAX(poa2.id) FROM pump_operator_assignments poa2
                         WHERE poa2.shift_id = petro_shifts.id
                     )');
            })
            ->where('petro_shifts.pump_operator_id', $request->pump_operator_id)
            ->where('petro_shifts.business_id', $business_id)
            ->select(
                'petro_shifts.id',
                'petro_shifts.shift_date',
                'petro_shifts.closed_time',
                'pump_operator_assignments.shift_number'
            )
            ->orderBy('petro_shifts.id', 'DESC')
            ->get()
            ->mapWithKeys(function ($shift) {
                $shiftLabel = $shift->shift_number ? 'Shift ' . $shift->shift_number : 'Shift #' . $shift->id;
                $date       = $shift->shift_date ? \Carbon\Carbon::parse($shift->shift_date)->format('d/m/Y') : '';
                $close      = $shift->closed_time ? \Carbon\Carbon::parse($shift->closed_time)->format('d/m/Y H:i') : 'Open';
                $label      = $shiftLabel . ' (' . $date . ' to ' . $close . ')';
                return [$shift->id => $label];
            });

        return response()->json($shifts);
    }

    public function otherSalesList(Request $request)
    {

        $business_id = Auth::user()->business_id;

        if (request()->ajax()) {
            $business_details = Business::find($business_id);

            $currency_precision = ! empty($business_details->currency_precision) ? $business_details->currency_precision : 2;

            $otherSaleFinalTotal = 0.00;

            $active_settlement = Settlement::where('status', 1)
                ->where('business_id', $business_id)
                ->select('settlements.*')
                ->with(['other_sales'])
                ->first();

            $userSales = [];

            $query = PumpOperatorOtherSale::join('products', 'products.id', '=', 'pump_operator_other_sales.product_id')
                ->leftJoin('variations', 'products.id', 'variations.product_id')
                ->leftJoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
                ->whereNull('pump_operator_other_sales.shift_id');

            $print = false;
            if ($request->print_other_sale_ids) {
                $print = ($request->print == "1");
                $query->whereIn('pump_operator_other_sales.id', $request->print_other_sale_ids);
            }

            $query->select(
                'pump_operator_other_sales.*',
                'products.name as product_name',
                'products.sku as product_sku',
                DB::raw('NULL as shift_number'),
                'qty_available'
            )
                ->groupBy('pump_operator_other_sales.id');

            // Safe check: Initialize userSales as empty collection first
            $userSales = collect();

            // Check and load other_sales only if active_settlement is not null
            // Filter out sales with shift_id (from pumper dashboard)
            if (! empty($active_settlement) && $active_settlement->other_sales) {
                $userSales = $active_settlement->other_sales
                    ->filter(function ($item) {
                        return empty($item->shift_id);
                    })
                    ->map(function ($item) use ($currency_precision, &$otherSaleFinalTotal) {
                        $product = \App\Product::find($item->product_id);

                        $discount_amount = $item->discount_amount ?? 0.00;
                        $withDiscount    = ($item->sub_total ?? 0.00) - $discount_amount;

                        //$pump_other_sale_final_total += $withDiscount;
                        $otherSaleFinalTotal += $withDiscount;

                        return (object) [
                            'id'            => $item->id ?? null,
                            'product_sku'   => $product->sku ?? '',
                            'product_name'  => $product->name ?? '',
                            'balance_stock' => number_format($item->balance_stock ?? 0, 4, '.', ','), // Safe number_format
                            'price'         => $item->price ?? 0,
                            'qty'           => $item->qty ?? 0,
                            'discount_type' => $item->discount_type ?? '',
                            'discount'      => $item->discount ?? 0,
                            'sub_total'     => $item->sub_total ?? 0,
                            'with_discount' => $withDiscount,
                            'created_at'    => $item->created_at ?? '',
                            'qty_available' => number_format($item->balance_stock ?? 0, 4, '.', ','), // Not applicable for user sales
                            'shift_number'  => null,                                                  // Settlement sales from Real Time Entries don't have shift
                            'user_check'    => 1,                                                     // Mark as user entry
                        ];
                    });
            }

            // Safe fetch: If query has result or not, will always be a collection
            $pumpSalesCollection = $query->get();

            // Safe check: If empty, keep as empty collection
            $pumpSales = collect();
            if (! $pumpSalesCollection->isEmpty()) {
                $pumpSales = $pumpSalesCollection->map(function ($item) use ($currency_precision, &$otherSaleFinalTotal) {

                    $discount_amount = $item->discount ?? 0;
                    $withDiscount    = ($item->sub_total ?? 0) - $discount_amount;

                    //$pump_other_sale_final_total += $withDiscount;
                    $otherSaleFinalTotal += $withDiscount;

                    return (object) [
                        'id'            => $item->id,
                        'product_sku'   => $item->product_sku ?? '',
                        'product_name'  => $item->product_name ?? '',
                        'balance_stock' => number_format((float) ($item->qty_available ?? 0), 4, '.', ','),
                        'price'         => $item->price ?? 0,
                        'qty'           => $item->qty ?? 0,
                        'discount_type' => $item->discount_type ?? '',
                        'discount'      => $item->discount ?? 0,
                        'sub_total'     => $item->sub_total ?? 0,
                        'with_discount' => $withDiscount,
                        'created_at'    => $item->created_at ?? '',
                        'qty_available' => $item->qty_available ?? '',
                        'shift_number'  => null, // Real Time Entries sales don't have shift
                        'user_check'    => 0,    // Mark as pump sale entry
                    ];
                });
            }
            $pump_nos = Pump::whereIn('id', function ($query) use ($request) {
                $query->select('pump_id')
                    ->from('pump_operator_assignments')
                    ->whereIn('shift_id', $request->shift_ids);
            })->pluck('pump_name', 'id');

            // If total requested
            if ($request->get_total) {
                return [
                    'success'  => 1,
                    'pump_nos' => $pump_nos,
                    'total'    => $otherSaleFinalTotal,
                ];
            }

            // ✅ Combine both collections safely
            $combinedSales = collect($userSales)->merge(collect($pumpSales));

            $other_sales = DataTables::of($combinedSales)
                ->addColumn('quantity', function ($row) {
                    return number_format($row->qty); // ✅ as object
                })
                ->editColumn(
                    'price',
                    function ($row) use ($business_details) {
                        return '<span class="display_currency amount" data-orig-value="' . $row->price . '" data-currency_symbol=false>' .
                            $this->productUtil->num_f($row->price, false, $business_details, true) .
                            '</span>';
                    }
                )
                ->editColumn('created_at', function ($row) {
                    return (new \DateTime($row->created_at))->format('Y-m-d H:i:s');
                })
                ->editColumn('qty_available', function ($row) {
                    return number_format((float) ($row->qty_available ?? 0), 4, '.', ',');
                })
                ->editColumn(
                    'sub_total',
                    function ($row) use ($business_details) {
                        return '<span class="display_currency sub_total" data-orig-value="' . $row->sub_total . '" data-currency_symbol=false>' .
                            $this->productUtil->num_f($row->sub_total, false, $business_details, true) .
                            '</span>';
                    }
                )
                ->editColumn(
                    'with_discount',
                    function ($row) use ($currency_precision) {
                        return '<span class="display_currency with_discount" data-orig-value="' . $row->with_discount . '" data-currency_symbol=false>' .
                            number_format($row->with_discount, $currency_precision) .
                            '</span>';
                    }
                )
                ->addColumn('action', function ($row) {
                    if (isset($row->user_check) && $row->user_check == 1) {
                        return '<button class="btn btn-xs btn-danger delete_other_sale" data-href="/petro/settlement/delete-other-sale/' . $row->id . '"><i class="fa fa-times"></i></button>';
                    }
                    return '';
                });
            return $other_sales->rawColumns(['price', 'sub_total', 'with_discount'])->make(true);
        }

        $print                = false;
        $print_other_sale_ids = [];
        if ($request->print_other_sale_ids) {
            // \Log::debug("otherSalesList", ["print_other_sale_ids" => $request->print_other_sale_ids]);
            $print_other_sale_ids = explode(",", $request->print_other_sale_ids);
            $print                = true;
        }

        $pump_operators = PumpOperator::where('business_id', $business_id)
            ->where('active', 1)
            ->pluck('name', 'id');

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')
            ->where('petro_shifts.business_id', $business_id)
            ->select('pump_operators.name', 'petro_shifts.*')
            ->orderBy('petro_shifts.id', 'DESC')
            ->get();

        $layout = 'pumper';

        return view('realtimeentries::other_sales_list')->with(compact('pump_operators', 'shifts', 'layout', 'print_other_sale_ids', 'print'));
    }

    public function paymentSummary()
    {
        $business_id      = Auth::user()->business_id;
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_details = Business::find($business_id);

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'real_time_entries')) {
            abort(403, 'Unauthorized Access');
        }

        // $only_pumper = request()->only_pumper;
        $only_pumper = filter_var(request()->only_pumper, FILTER_VALIDATE_BOOLEAN);
        $shift_id    = request()->shift_id;

        if (request()->ajax()) {

            $query = app(\Modules\Petro\Services\SettlementPaymentQueryService::class)
                ->paymentSummaryBaseQuery($business_id, false, true)
                ->select([
                    'pump_operator_payments.id',
                    'pump_operator_payments.date_and_time',
                    'pump_operator_payments.collection_form_no',
                    'pump_operator_payments.payment_type',
                    'pump_operator_payments.payment_amount',
                    'pump_operator_payments.note',

                    'pump_operators.name as pump_operator_name',
                    'edited_user.username as edited_by',
                    'business_locations.name as location_name',
                    'pump_operator_assignments.shift_number',

                    DB::raw('COALESCE(contacts_credit.name, contacts_card.name, contacts_cheque.name) as customer_name'),
                    'scsp.order_number as order_number',
                    'dc.slip_no',
                    'dcp.cheque_number',
                ]);

            if (!empty($shift_id)) {
                $query->where('pump_operator_payments.shift_id', $shift_id);
            }

            // filters
            if ($only_pumper && !empty($pump_operator_id) && $pump_operator_id > 0) {
                $query->where(
                    'pump_operator_payments.pump_operator_id',
                    $pump_operator_id
                );
            }
            if (!empty(request()->payment_method)) {
                $query->whereRaw(
                    'LOWER(TRIM(pump_operator_payments.payment_type)) = ?',
                    [strtolower(trim(request()->payment_method))]
                );
            }
            if (! empty(request()->location_id)) {
                $query->where('pump_operators.location_id', request()->location_id);
            }
            if (! empty(request()->pump_operator_id)) {
                $query->where('pump_operator_payments.pump_operator_id', request()->pump_operator_id);
            }
            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $query->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date);
                $query->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);
            }
            if (!empty(request()->customer_id)) {
                $query->where(function ($q) {
                    $q->where('scsp.customer_id', request()->customer_id)
                      ->orWhere('dc.customer_id', request()->customer_id);
                });
            }
            if (!empty(request()->slip_no)) {
                $query->where('dc.slip_no', 'like', '%' . request()->slip_no . '%');
            }
            if (!empty(request()->order_no)) {
                $query->where('scsp.order_number', 'like', '%' . request()->order_no . '%');
            }

            $query->groupBy(groups: 'pump_operator_payments.id');
            $query->orderBy('pump_operator_payments.id', 'asc');
            // $fuel_tanks = $query->get();

            \Log::info('Payment Summary Context', [
                'only_pumper' => $only_pumper,
                'auth_pump_operator_id' => Auth::user()->pump_operator_id,
                'requested_pump_operator_id' => request()->pump_operator_id,
            ]);

            // // // Debug
            // dd($fuel_tanks);
            $fuel_tanks = DataTables::of($query)
                ->filterColumn('shift_number', function ($query, $keyword) {
                    $query->where('pump_operator_assignments.shift_number', 'like', "%{$keyword}%");
                })
                ->filterColumn('customer_name', function ($query, $keyword) {
                    $query->whereRaw("COALESCE(contacts_credit.name, contacts_card.name, contacts_cheque.name) like ?", ["%{$keyword}%"]);
                })
                ->filterColumn('date', function ($query, $keyword) {
                    $query->where('pump_operator_payments.date_and_time', 'like', "%{$keyword}%");
                })
                ->filterColumn('time', function ($query, $keyword) {
                    $query->where('pump_operator_payments.date_and_time', 'like', "%{$keyword}%");
                })
                ->filterColumn('amount', function ($query, $keyword) {
                    $query->where('pump_operator_payments.payment_amount', 'like', "%{$keyword}%");
                })
                ->filterColumn('slip_no', function ($query, $keyword) {
                    $query->where('dc.slip_no', 'like', "%{$keyword}%");
                })
                ->filterColumn('order_no', function ($query, $keyword) {
                    $query->where('scsp.order_number', 'like', "%{$keyword}%");
                })
                ->filterColumn('cheque_no', function ($query, $keyword) {
                    $query->where('dcp.cheque_number', 'like', "%{$keyword}%");
                })
                ->filterColumn('collection_form_no', function ($query, $keyword) {
                    $query->where('pump_operator_payments.collection_form_no', 'like', "%{$keyword}%");
                })
                ->filterColumn('payment_type', function ($query, $keyword) {
                    $query->where('pump_operator_payments.payment_type', 'like', "%{$keyword}%");
                })
                ->addColumn('action', function ($row) {
                    if ($row->payment_type == 'Other Sale') {
                        return '';
                    }

                    $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                        data-toggle="dropdown" aria-expanded="false">' .
                        __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    $html .= '<li><a href="#" data-href="' .
                        action('\Modules\Petro\Http\Controllers\PumpOperatorPaymentController@edit', [$row->id]) .
                        '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' .
                        __("messages.edit") . '</a></li>';

                    return $html . '</ul></div>';
                })
                ->addColumn('date', '{{@format_date($date_and_time)}}')
                ->addColumn('time', '{{@format_time($date_and_time)}}')
                ->addColumn('customer', function ($row) {
                    if (strtolower(trim($row->payment_type)) == 'cash' || empty($row->customer_name)) {
                        return 'Walk-In Customer';
                    }
                    return $row->customer_name;
                })
                ->addColumn('customer_name', function ($row) {
                    if (strtolower(trim($row->payment_type)) == 'cash' || empty($row->customer_name)) {
                        return 'Walk-In Customer';
                    }
                    return $row->customer_name;
                })
                ->addColumn('slip_no', function ($row) {
                    return $row->slip_no ?? '—';
                })
                ->addColumn('order_no', function ($row) {
                    return $row->order_number ?? '—';
                })
                ->addColumn('cheque_no', function ($row) {
                    return $row->cheque_number ?? '—';
                })

                ->editColumn('payment_type', '{{ucfirst($payment_type)}}')
                ->editColumn('amount', function ($row) use ($business_details) {
                    return '<span class="display_currency amount" data-orig-value="' .
                        $row->payment_amount .
                        '" data-currency_symbol=false>' .
                        $this->productUtil->num_f($row->payment_amount, false, $business_details, true) .
                        '</span>';
                });

            return $fuel_tanks->rawColumns(['amount', 'action'])->make(true);
        }

        // Non-ajax section
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $payment_types  = \Modules\Petro\Entities\PumpOperatorPayment::getPaymentTypesArray();

        $layout = $only_pumper ? 'pumper' : 'app';

        $shifts = PetroShift::join(
            'pump_operators',
            'pump_operators.id',
            '=',
            'petro_shifts.pump_operator_id'
        )
            ->leftJoin('pump_operator_assignments', function ($join) {
                $join->on('pump_operator_assignments.shift_id', '=', 'petro_shifts.id')
                    ->on('pump_operator_assignments.pump_operator_id', '=', 'petro_shifts.pump_operator_id');
            })
            ->where('petro_shifts.business_id', $business_id)
            ->select(
                'petro_shifts.id',
                'pump_operator_assignments.shift_number',
                'pump_operators.name as pump_operator_name'
            )
            ->distinct()
            ->orderBy('petro_shifts.id', 'DESC');

        if ($only_pumper) {
            $shifts->where('pump_operator_id', $pump_operator_id);
        }

        $shifts             = $shifts->get();
        $business_locations = BusinessLocation::forDropdown($business_id);

        \Log::info('Shift filter', [
            'request_shift_id' => $shift_id,
            'db_shift_ids' => PumpOperatorPayment::pluck('shift_id')->unique()
        ]);

        $user         = Auth::user();
        // MA-002: numeric max - shift_number is varchar(50), so MAX() on
        // it compares as TEXT and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $user->pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');
        $customers    = Contact::customersDropdown($business_id, false, true, 'customer');

        return view('realtimeentries::payment_summary')->with(compact(
            'pump_operators',
            'only_pumper',
            'payment_types',
            'layout',
            'shifts',
            'customers',
            'shift_number',
            'business_locations'
        ));
    }

    public function metersWithPayments(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        // Filters
        $date_range       = $request->input('date_range');
        $pump_operator_id = $request->input('pump_operator_id');
        $shift_number     = $request->input('shift_number');
        $payment_method   = $request->input('payment_method');

        $query = \DB::table('current_meters as cm')
            ->join('pump_operators as po', 'cm.pump_operator_id', '=', 'po.id')
            ->join('pumps as p', 'cm.pump_id', '=', 'p.id')
            ->leftJoin('meter_sales as ms', function ($join) {
                $join->on('cm.pump_id', '=', 'ms.pump_id')
                    ->on('cm.business_id', '=', 'ms.business_id');
            })
            ->leftJoin('transactions as t', 'ms.transaction_id', '=', 't.id')
            ->leftJoin('transaction_payments as tp', 't.id', '=', 'tp.transaction_id')
            ->where('cm.business_id', $business_id);

        // Apply filters
        if (! empty($date_range)) {
            $dates = explode(' ~ ', $date_range);
            if (count($dates) == 2) {
                $query->whereBetween('cm.date_and_time', [trim($dates[0]), trim($dates[1])]);
            }
        }
        if (! empty($pump_operator_id)) {
            $query->where('cm.pump_operator_id', $pump_operator_id);
        }
        if (! empty($shift_number)) {
            $query->where('tp.shift_number', $shift_number);
        }
        if (! empty($payment_method)) {
            $query->where('tp.method', $payment_method);
        }

        // Select data (latest transaction first)
        $records = $query->select(
            \DB::raw('DATE(cm.date_and_time) as date'),
            \DB::raw('TIME(cm.date_and_time) as time'),
            'po.name as pump_operator',
            'tp.shift_number',
            'p.pump_no as pump_number',
            'cm.current_meter',
            'tp.method as payment_method',
            'tp.amount as payment_amount'
        )
            ->orderBy('cm.date_and_time', 'desc')
            ->get();

        // Dropdown filters
        // $pump_operators  = \DB::table('pump_operators')->pluck('name', 'id');
        $pump_operators  = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $shifts          = \DB::table('transaction_payments')->distinct()->pluck('shift_number', 'shift_number');
        $payment_methods = \DB::table('transaction_payments')
            ->where('business_id', $business_id)
            ->distinct()
            ->pluck('method', 'method');

        return view('realtimeentries::meters_with_payments', compact(
            'records',
            'pump_operators',
            'shifts',
            'payment_methods'
        ));
    }

    /**
     * Save Cash payment and post debit to Cash account.
     */
    public function saveCash(Request $request)
    {
        $business_id      = $request->session()->get('user.business_id');
        $amount           = (float) $request->input('amount');
        $shift_number     = $request->input('shift_number');
        $transaction_dt   = $request->input('transaction_date') ?: now();
        $pump_operator_id = $request->input('pump_operator_id') ?: (auth()->user()->pump_operator_id ?? null);
        $location_id      = $request->session()->get('user.current_location') ?? (auth()->user()->current_location ?? null);

        if ($amount <= 0) {
            return response()->json(['success' => false, 'msg' => 'Invalid amount']);
        }

        // Convert shift_number to shift_id
        $shift_id = null;
        if (! empty($shift_number) && ! empty($pump_operator_id)) {
            $assignment = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
                ->where('shift_number', $shift_number)
                ->where('status', 'open')
                ->first();
            if ($assignment) {
                $shift_id = $assignment->shift_id;
            }
        }

        // Generate collection_form_no
        $collection_form_no = null;
        $last_collection    = PumpOperatorPayment::where('business_id', $business_id)
            ->whereNotNull('collection_form_no')
            ->orderBy('id', 'DESC')
            ->select('collection_form_no')
            ->first();

        if ($last_collection) {
            $collection_form_no = (int) $last_collection->collection_form_no + 1;
        } else {
            $last_daily = DailyCollection::where('business_id', $business_id)
                ->whereNotNull('collection_form_no')
                ->orderBy('id', 'DESC')
                ->select('collection_form_no')
                ->first();
            $collection_form_no = $last_daily ? ((int) $last_daily->collection_form_no + 1) : 1;
        }

        // Create PumpOperatorPayment record
        $payment = PumpOperatorPayment::create([
            'business_id'        => $business_id,
            'payment_type'       => 'cash',
            'payment_amount'     => $amount,
            'created_by'         => auth()->id(),
            'pump_operator_id'   => $pump_operator_id,
            'shift_id'           => $shift_id,
            'collection_form_no' => $collection_form_no,
        ]);

        // Sync to Daily Collection
        $this->syncPaymentToDailyTables($payment, $shift_id, $shift_number, ['location_id' => $location_id]);

        // Post debit to Cash Account Book
        $cash_account_id = $this->commonUtil->account_exist_return_id('Cash');
        if (! empty($cash_account_id)) {
            $settings = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();
            $dashboard_settings = (! is_null($settings)) ? json_decode($settings->dashboard_settings, true) : [];
            $update_account = (($dashboard_settings['real_time_update_account_books'] ?? 'no') === 'yes') && 
                              (($dashboard_settings['real_time_entry_payments_in_accounts'] ?? 'day_entry') === 'day_entry');
            if ($update_account) {
                AccountTransaction::createAccountTransaction([
                    'amount'         => $amount,
                    'account_id'     => $cash_account_id,
                    'type'           => 'debit',
                    'operation_date' => $transaction_dt, // Selected date for transaction date column
                    'note'           => 'Real Time Cash - Shift No. ' . ($shift_number ?: '-'),
                    'shift_number'   => $shift_number,
                    'created_by'     => auth()->id(),
                ]);
            }
        }

        return response()->json([
            'success'            => true,
            'payment_id'         => $payment->id,
            'collection_form_no' => $collection_form_no,
            'msg'                => 'Cash payment saved successfully',
        ]);
    }

    /**
     * Save Card payment and post debit to Cards account.
     */
    public function saveCard(Request $request)
    {
        Log::info('save card request: ', $request->all());
        $business_id    = $request->session()->get('user.business_id');
        $amount         = (float) $request->input('amount');
        $shift_number   = $request->input('shift_number');
        $transaction_dt = $request->input('transaction_date') ?: now();
        $card_type      = $request->input('card_type');
        $slip_no        = $request->input('slip_no');

        if ($amount <= 0) {
            return response()->json(['success' => false, 'msg' => 'Invalid amount']);
        }

        $pump_operator_id = $request->input('pump_operator_id') ?: (auth()->user()->pump_operator_id ?? null);
        $shift_id = null;

        $assignment = PumpOperatorAssignment::where('business_id', $business_id)
            ->when(! empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                $query->where('pump_operator_id', $pump_operator_id);
            })
            ->when(! empty($shift_number), function ($query) use ($shift_number) {
                $query->where('shift_number', $shift_number);
            })
            ->where('status', 'open')
            ->orderBy('id', 'DESC')
            ->first();

        if ($assignment) {
            $pump_operator_id = $assignment->pump_operator_id;
            Log::info('Found assignment for shift_number ' . $shift_number . ': pump_operator_id = ' . $pump_operator_id);
            $shift_id = $assignment->shift_id;
        }

        // Convert shift_number to shift_id
        // $shift_id = null;
        // if (! empty($shift_number)) {
        //     // $pump_operator_id = auth()->user()->pump_operator_id;
        //     $assignment       = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
        //         ->where('shift_number', $shift_number)
        //         ->where('status', 'open')
        //         ->first();
        //     if ($assignment) {
        //         $shift_id = $assignment->shift_id;
        //     }
        // }

        // Get card type name if card_type is an ID
        $card_type_name = $card_type;
        if (is_numeric($card_type)) {
            $card_account = Account::find($card_type);
            if ($card_account) {
                $card_type_name = $card_account->name;
            }
        }

        // Generate collection_form_no
        $collection_form_no = null;
        $last_collection    = PumpOperatorPayment::where('business_id', $business_id)
            ->whereNotNull('collection_form_no')
            ->orderBy('id', 'DESC')
            ->select('collection_form_no')
            ->first();

        if ($last_collection) {
            $collection_form_no = (int) $last_collection->collection_form_no + 1;
        } else {
            $last_daily = DailyCollection::where('business_id', $business_id)
                ->whereNotNull('collection_form_no')
                ->orderBy('id', 'DESC')
                ->select('collection_form_no')
                ->first();
            $collection_form_no = $last_daily ? ((int) $last_daily->collection_form_no + 1) : 1;
        }

        // Create PumpOperatorPayment record
        $payment = PumpOperatorPayment::create([
            'business_id'        => $business_id,
            'payment_type'       => 'card',
            'payment_amount'     => $amount,
            'created_by'         => auth()->id(),
            'pump_operator_id'   => $pump_operator_id,
            'shift_id'           => $shift_id,
            'collection_form_no' => $collection_form_no,
        ]);

        // Prepare card data for sync
        $card_data              = new \stdClass();
        $card_data->card_type   = $card_type;
        $card_data->card_number = $request->input('card_number');
        $card_data->slip_no     = $slip_no;

        // Sync to Daily Collection
        $this->syncPaymentToDailyTables($payment, $shift_id, $shift_number, $card_data);

        // Post debit to Cards Account Book
        $cards_account_id = is_numeric($card_type) ? (int) $card_type : $this->commonUtil->account_exist_return_id('Cards');
        if (! empty($cards_account_id)) {
            $settings = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();
            $dashboard_settings = (! is_null($settings)) ? json_decode($settings->dashboard_settings, true) : [];
            $update_account = (($dashboard_settings['real_time_update_account_books'] ?? 'no') === 'yes') && 
                              (($dashboard_settings['real_time_entry_payments_in_accounts'] ?? 'day_entry') === 'day_entry');
            if ($update_account) {
                AccountTransaction::createAccountTransaction([
                    'amount'         => $amount,
                    'account_id'     => $cards_account_id,
                    'type'           => 'debit',
                    'operation_date' => $transaction_dt, // Selected date for transaction date column
                    'note'           => 'Real Time Card - Shift No. ' . ($shift_number ?: '-') . ' - ' . ($card_type_name ?: '') . ' ' . ($slip_no ?: ''),
                    'slip_no'        => $slip_no,
                    'shift_number'   => $shift_number,
                    'created_by'     => auth()->id(),
                ]);
            }
        }

        return response()->json([
            'success'            => true,
            'payment_id'         => $payment->id,
            'collection_form_no' => $collection_form_no,
            'msg'                => 'Card payment saved successfully',
        ]);
    }

    /**
     * Save Credit Sale: debit Accounts Receivable and create Contact Ledger debit.
     */
    public function saveCreditSale(Request $request)
    {
        $business_id    = $request->session()->get('user.business_id');
        $amount         = (float) $request->input('amount');
        $customer_id    = (int) $request->input('customer_id');
        $shift_number   = $request->input('shift_number');
        $transaction_dt = $request->input('transaction_date') ?: now();

        // if ($amount <= 0 || empty($customer_id)) {
        //     return response()->json(['success' => false, 'msg' => 'Invalid input']);
        // }

        // Create PumpOperatorPayment record
        $payment = PumpOperatorPayment::create([
            'business_id'      => $business_id,
            'payment_type'     => 'credit_sale',
            'customer_id'      => $customer_id,
            'amount'           => $amount,
            'created_by'       => auth()->id(),
            'shift_number'     => $shift_number,
            'transaction_date' => $transaction_dt,
        ]);

        $settings = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();
        $dashboard_settings = (! is_null($settings)) ? json_decode($settings->dashboard_settings, true) : [];
        $update_account = (($dashboard_settings['real_time_update_account_books'] ?? 'no') === 'yes') && 
                          (($dashboard_settings['real_time_entry_payments_in_accounts'] ?? 'day_entry') === 'day_entry');
        $update_ledger = ($dashboard_settings['real_time_update_customer_ledger'] ?? 'no') === 'yes';

        // Debit Accounts Receivable Account Book
        $ar_account_id = $this->commonUtil->account_exist_return_id('Accounts Receivable');
        if (! empty($ar_account_id) && $update_account) {
            AccountTransaction::createAccountTransaction([
                'amount'         => $amount,
                'account_id'     => $ar_account_id,
                'type'           => 'debit',
                'operation_date' => $transaction_dt, // Selected date for transaction date column
                'note'           => 'Real Time Credit Sale - Shift No. ' . ($shift_number ?: '-'),
                'shift_number'   => $shift_number,
                'created_by'     => auth()->id(),
            ]);
        }

        // Create Contact Ledger debit entry
        if ($update_ledger) {
            \App\ContactLedger::createContactLedger([
                'contact_id'     => $customer_id,
                'amount'         => $amount,
                'type'           => 'debit',
                'operation_date' => $transaction_dt, // Selected date for transaction date column
                'created_by'     => auth()->id(),
                'note'           => 'Real Time Credit Sale - Shift No. ' . ($shift_number ?: '-'),
            ]);
        }

        return response()->json(['success' => true, 'payment_id' => $payment->id]);
    }

    /**
     * Save Sales Income account entries when meters are saved.
     * This method should be called when meters are entered and saved in any payment form.
     */
    public function saveSalesIncome(Request $request)
    {
        $business_id    = $request->session()->get('user.business_id');
        $amount         = (float) $request->input('amount');
        $shift_number   = $request->input('shift_number');
        $transaction_dt = $request->input('transaction_date') ?: now();
        $payment_type   = $request->input('payment_type'); // cash, card, cheque, credit_sale

        if ($amount <= 0) {
            return response()->json(['success' => false, 'msg' => 'Invalid amount, amount must be greater than 0.']);
        }

        // Post credit to Sales Income Account Book
        $sales_income_account_id = $this->commonUtil->account_exist_return_id('Sales Income');
        if (! empty($sales_income_account_id)) {
            $settings = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();
            $dashboard_settings = (! is_null($settings)) ? json_decode($settings->dashboard_settings, true) : [];
            $update_account = (($dashboard_settings['real_time_update_account_books'] ?? 'no') === 'yes') && 
                              (($dashboard_settings['real_time_entry_payments_in_accounts'] ?? 'day_entry') === 'day_entry');
            if ($update_account) {
                AccountTransaction::createAccountTransaction([
                    'amount'         => $amount,
                    'account_id'     => $sales_income_account_id,
                    'type'           => 'credit',
                    'operation_date' => $transaction_dt, // Selected date for transaction date column
                    'note'           => 'Real Time ' . ucfirst($payment_type) . ' - Shift No. ' . ($shift_number ?: '-'),
                    'shift_number'   => $shift_number,
                    'created_by'     => auth()->id(),
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update settlement number in existing accounting entries.
     * This method should be called when a settlement is saved to update the description.
     */
    public function updateSettlementNumber(Request $request)
    {
        $payment_id        = $request->input('payment_id');
        $settlement_number = $request->input('settlement_number');
        $shift_number      = $request->input('shift_number');

        if (empty($payment_id) || empty($settlement_number)) {
            return response()->json(['success' => false, 'msg' => 'Missing required parameters']);
        }

        // Update AccountTransaction entries
        AccountTransaction::where('shift_number', $shift_number)
            ->where('note', 'LIKE', '%Real Time%')
            ->where('note', 'NOT LIKE', '%Settlement No.%') // Avoid duplicate updates
            ->update([
                'note' => DB::raw("CONCAT(note, ' - Settlement No. " . $settlement_number . "')"),
            ]);

        // Update ContactLedger entries
        \App\ContactLedger::where('shift_number', $shift_number)
            ->where('note', 'LIKE', '%Real Time%')
            ->where('note', 'NOT LIKE', '%Settlement No.%') // Avoid duplicate updates
            ->update([
                'note' => DB::raw("CONCAT(note, ' - Settlement No. " . $settlement_number . "')"),
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Helper method to automatically update settlement numbers when settlements are saved.
     * This can be called from settlement controllers.
     */
    public static function autoUpdateSettlementNumbers($shift_number, $settlement_number)
    {
        if (empty($shift_number) || empty($settlement_number)) {
            return false;
        }

        // Update AccountTransaction entries
        AccountTransaction::where('shift_number', $shift_number)
            ->where('note', 'LIKE', '%Real Time%')
            ->where('note', 'NOT LIKE', '%Settlement No.%') // Avoid duplicate updates
            ->update([
                'note' => DB::raw("CONCAT(note, ' - Settlement No. " . $settlement_number . "')"),
            ]);

        // Update ContactLedger entries
        \App\ContactLedger::where('shift_number', $shift_number)
            ->where('note', 'LIKE', '%Real Time%')
            ->where('note', 'NOT LIKE', '%Settlement No.%') // Avoid duplicate updates
            ->update([
                'note' => DB::raw("CONCAT(note, ' - Settlement No. " . $settlement_number . "')"),
            ]);

        return true;
    }

    protected function resolvePetroDailyShiftId($business_id, $pump_operator_id, $shift_number = null, $fallback_shift_id = null, $date = null)
    {
        if (! empty($fallback_shift_id)) {
            $existing_shift_id = PetroDailyShift::where('business_id', $business_id)
                ->where('id', $fallback_shift_id)
                ->value('id');

            if (! empty($existing_shift_id)) {
                return $existing_shift_id;
            }
        }

        $base_query = PetroDailyShift::where('business_id', $business_id);

        if (! empty($pump_operator_id)) {
            $base_query->whereRaw('FIND_IN_SET(?, pump_operator_assigned)', [(string) $pump_operator_id]);
        }

        if (! empty($shift_number)) {
            $exact_shift_id = (clone $base_query)
                ->where('shift_no', (string) $shift_number)
                ->orderBy('updated_at', 'DESC')
                ->value('id');

            if (! empty($exact_shift_id)) {
                return $exact_shift_id;
            }

            if (is_numeric($shift_number)) {
                $padded_shift_number = str_pad((string) $shift_number, 3, '0', STR_PAD_LEFT);
                $padded_shift_id = (clone $base_query)
                    ->where('shift_no', 'like', '%' . $padded_shift_number)
                    ->orderBy('updated_at', 'DESC')
                    ->value('id');

                if (! empty($padded_shift_id)) {
                    return $padded_shift_id;
                }
            }
        }

        if (! empty($date)) {
            $shift_date = date('Y-m-d', strtotime($date));
            $dated_shift_id = (clone $base_query)
                ->whereDate('date', $shift_date)
                ->orderBy('status', 'DESC')
                ->orderBy('updated_at', 'DESC')
                ->value('id');

            if (! empty($dated_shift_id)) {
                return $dated_shift_id;
            }
        }

        return (clone $base_query)
            ->orderBy('status', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->value('id');
    }

    /**
     * Sync PumpOperatorPayment to Daily Collection tables (DailyCollection, DailyCard, etc.)
     */
    protected function syncPaymentToDailyTables(PumpOperatorPayment $payment, $shift_id, $shift_number, $request_data = null)
    {
        $business_id      = $payment->business_id;
        $pump_operator_id = $payment->pump_operator_id;
        $amount           = $payment->payment_amount;
        $collection_no    = $payment->collection_form_no;

        if (empty($collection_no)) {
            return; // Skip if no collection_form_no
        }

        $pump_operator = PumpOperator::find($pump_operator_id);
        $location_id   = $pump_operator->location_id ?? null;

        // Fallback to provided location (e.g., current_location) when operator has no location
        if (empty($location_id) && $request_data && is_array($request_data) && ! empty($request_data['location_id'])) {
            $location_id = $request_data['location_id'];
        }

        // CASH: DailyCollection
        if ($payment->payment_type === 'cash') {
            $exists = DailyCollection::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('collection_form_no', $collection_no)
                ->where('current_amount', $amount)
                ->where('shift_id', $shift_id)
                ->where('type', 'daily_collection')
                ->whereDate('created_at', date('Y-m-d'))
                ->first();

            if (! $exists) {
                // Get shift_number from assignment if available
                $assignment = PumpOperatorAssignment::where('shift_id', $shift_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where('shift_number', '>', 0)
                    ->orderBy('id', 'DESC')
                    ->first();

                $shift_no = $shift_number ?? ($assignment->shift_number ?? null);

                DailyCollection::create([
                    'business_id'        => $business_id,
                    'collection_form_no' => $collection_no,
                    'pump_operator_id'   => $pump_operator_id,
                    'location_id'        => $location_id,
                    'balance_collection' => 0,
                    'current_amount'     => $amount,
                    'created_by'         => $payment->created_by ?? Auth::id(),
                    'shift_id'           => $shift_id,
                    'shift_no'           => $shift_no,
                    'shift_number'       => $shift_number,
                    'type'               => 'daily_collection',
                ]);
            }
        }

        // CARD: DailyCard
        if ($payment->payment_type === 'card') {
            $slip_no             = null;
            $card_type           = null;
            $card_number         = null;
            $daily_card_shift_id = $this->resolvePetroDailyShiftId(
                $business_id,
                $pump_operator_id,
                $shift_number,
                $shift_id,
                $payment->created_at
            );

            if ($request_data && is_object($request_data)) {
                $slip_no     = $request_data->slip_no ?? null;
                $card_type   = $request_data->card_type ?? null;
                $card_number = $request_data->card_number ?? null;
            }

            $exists = DailyCard::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('collection_no', $collection_no)
                ->where('amount', $amount);

            if (! empty($daily_card_shift_id) && Schema::hasColumn('daily_cards', 'shift_id')) {
                $exists->where('shift_id', $daily_card_shift_id);
            }

            if ($slip_no) {
                $exists->where('slip_no', $slip_no);
            }

            $exists = $exists->first();

            if (! $exists) {
                $walkin_customer = Contact::where('name', 'Walk-In Customer')
                    ->where('business_id', $business_id)
                    ->first();

                $daily_card_data = [
                    'location_id'      => $location_id,
                    'collection_no'    => $collection_no,
                    'business_id'      => $business_id,
                    'amount'           => $amount,
                    'card_type'        => is_numeric($card_type) ? $card_type : null,
                    'card_number'      => $card_number,
                    'customer_id'      => $walkin_customer->id ?? null,
                    'slip_no'          => $slip_no,
                    'date'             => date('Y-m-d'),
                    'pump_operator_id' => $pump_operator_id,
                    'type'             => 'daily_collection',
                ];

                if (Schema::hasColumn('daily_cards', 'shift_id')) {
                    $daily_card_data['shift_id'] = $daily_card_shift_id;
                }
                if (Schema::hasColumn('daily_cards', 'shift_no')) {
                    $daily_card_data['shift_no'] = $shift_number;
                }
                if (Schema::hasColumn('daily_cards', 'shift_number')) {
                    $daily_card_data['shift_number'] = $shift_number;
                }

                DailyCard::create($daily_card_data);
            }
        }
    }

    public function setting_dash()
    {
        $card_types  = [];
        $business_id = Auth::user()->business_id;
        $card_group  = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (! empty($card_group)) {
            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'name');

        $settings = $pump_operators = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();

        return view('realtimeentries::setting_dash')->with(compact(
            'card_types',
            'pump_operators',
            'business_id',
            'settings'
        ));
    }

    public function store_settings(Request $request)
    {
        try {
            DB::beginTransaction();

            // collect only the required settings
            $data = $request->only(
                'created_at',
                'user_added',
                'show_bulk_pumps',
                'card_type',
                // 'credit_sales_direct_to_customer',
                // 'logoff_time',
                // 'logoff',
                'meter_sales_compulsory',
                'enter_cash_denominations',
                'card_amount_to_enter',
                'enter_card_numbers',
                'real_time_update_customer_ledger',
                'real_time_update_account_books',
                'real_time_entry_payments_in_accounts'
            );

            if ($request->is_admin == 1) {
                PumpOperator::where('business_id', Auth::user()->business_id)
                    ->update(['dashboard_settings' => json_encode($data)]);
            } else {
                if (Auth::user()->pump_operator_id != 0) {
                    $pump_operator = PumpOperator::findOrFail(Auth::user()->pump_operator_id);
                } else {
                    $pump_operator = PumpOperator::where('business_id', Auth::user()->business_id)
                        ->where('is_default', '1')
                        ->first() ?? PumpOperator::findOrFail(1);
                }
                $pump_operator->dashboard_settings = json_encode($data);
                $pump_operator->save();
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() .
                ' Line: ' . $e->getLine() .
                ' Message: ' . $e->getMessage());

            DB::rollBack();

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }
}

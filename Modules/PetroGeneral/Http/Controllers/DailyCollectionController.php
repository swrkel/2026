<?php
namespace Modules\PetroGeneral\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\PetroGeneral\Entities\CustomerPayment;
use Modules\PetroGeneral\Entities\DailyCard;
use Modules\PetroGeneral\Entities\DailyCollection;
use Modules\PetroGeneral\Entities\DailyVoucher;
use Modules\PetroGeneral\Entities\DayCountSetting;
use Modules\PetroGeneral\Entities\PetroDailyShift;
use Modules\PetroGeneral\Entities\PetroShift;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\PumpOperator;
use Modules\PetroGeneral\Entities\PumpOperatorAssignment;
use Modules\PetroGeneral\Entities\PumpOperatorPayment;
use Yajra\DataTables\Facades\DataTables;

class DailyCollectionController extends Controller
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

    /**

     * Constructor

     *

     * @param ProductUtils $product

     * @return void

     */

    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil, NotificationUtil $notificationUtil)
    {

        $this->commonUtil = $commonUtil;

        $this->productUtil = $productUtil;

        $this->moduleUtil = $moduleUtil;

        $this->transactionUtil = $transactionUtil;

        $this->businessUtil = $businessUtil;

        $this->notificationUtil = $notificationUtil;

    }

    /**

     * Display a listing of the resource.

     * @return Response

     */

    public function index(Request $request)
    {

        $business_id = request()->session()->get('user.business_id');

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'daily_collection')) {

            abort(403, 'Unauthorized Access');

        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');
            $is_pumper_dashboard_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

            if (request()->ajax()) {

                $query = DailyCollection::leftjoin('business_locations', 'daily_collections.location_id', 'business_locations.id')

                    ->leftjoin('pump_operators', 'daily_collections.pump_operator_id', 'pump_operators.id')

                    ->leftjoin('users', 'daily_collections.created_by', 'users.id')

                    ->leftjoin('settlements', 'daily_collections.settlement_id', 'settlements.id')

                    ->leftJoin('petro_shifts', 'daily_collections.shift_id', '=', 'petro_shifts.id')

                    ->where('daily_collections.business_id', $business_id)

                    ->where('daily_collections.type', 'daily_collection')

                    ->select(
                        'daily_collections.id',
                        'daily_collections.collection_form_no',
                        'daily_collections.current_amount',
                        'daily_collections.balance_collection',
                        'daily_collections.shift_id',
                        'daily_collections.shift_number',
                        'daily_collections.shift_no',
                        'daily_collections.created_at',
                        'daily_collections.type',
                        'daily_collections.added_to_account',
                        'daily_collections.pump_operator_id',
                        'daily_collections.settlement_id',
                        'business_locations.name as location_name',
                        'pump_operators.name as pump_operator_name',
                        'settlements.settlement_no as settlement_no',
                        'settlements.status as settlement_status',
                        'users.username as user',
                        'settlements.transaction_date as settlement_dates'
                    );

                if (! empty(request()->location_id)) {

                    $query->where('daily_collections.location_id', request()->location_id);

                }

                if (! empty(request()->status)) {
                    if (request()->status == 'completed') {
                        $query->where(function ($q) {
                            $q->where(function ($subQ) {
                                $subQ->whereNotNull('settlements.settlement_no')
                                    ->where('settlements.status', 0);
                            })
                            ->orWhereNotNull('daily_collections.added_to_account');
                        });
                    }

                    if (request()->status == 'pending') {
                        $query->where(function ($q) {
                            $q->where(function ($subQ) {
                                $subQ->whereNull('settlements.settlement_no')
                                ->orWhere('settlements.status', 1);
                            })
                            ->whereNull('daily_collections.added_to_account');
                        });
                    }
                }

                // if (! empty(request()->status)) {

                //     if (request()->status == 'completed') {

                //         $query->where(function ($q) {

                //             $q->whereNotNull('settlements.settlement_no')->where('settlements.status', 0)

                //                 ->orWhereNotNull('settlements.added_to_account');

                //         });

                //     }

                //     if (request()->status == 'pending') {

                //         $query->where(function ($q) {

                //             $q->whereNull('settlements.settlement_no')

                //                 ->orWhere('settlements.status', 1);

                //         });

                //         $query->whereNull('settlements.added_to_account');

                //     }

                // }

                if (! empty(request()->settlement_id)) {

                    $query->where('settlements.id', request()->settlement_id);

                }

                if (! empty(request()->pump_operator)) {

                    $query->where('daily_collections.pump_operator_id', request()->pump_operator);
                    
                    // Filter to show only open shifts for this pump operator
                    // Exclude closed shifts (status != 0) and show only current/open shifts
                    $query->where(function($q) {
                        $q->where('petro_shifts.status', 0) // Only open shifts
                          ->orWhereNull('daily_collections.shift_id'); // Or collections without assigned shifts
                    });

                }

                if (! empty(request()->settlement_no)) {

                    $query->where('daily_collections.id', request()->settlement_no);

                }

                if (! empty(request()->start_date) && ! empty(request()->end_date)) {

                    $query->whereDate('daily_collections.created_at', '>=', request()->start_date);

                    $query->whereDate('daily_collections.created_at', '<=', request()->end_date);

                }

                // $query->orderBy(DB::raw('CAST(daily_collections.collection_form_no AS UNSIGNED)'), 'desc');

                // $query->groupBy('daily_collections.id');
                // $query->distinct('daily_collections.id');

                $query->groupBy('daily_collections.id');
                $query->orderBy('daily_collections.created_at', 'desc');

                try {
                $fuel_tanks = Datatables::of($query)

                    ->addColumn(
                        'action',
                        function($row) use ($is_pumper_dashboard_enabled) {
                            $buttons = '<button class="btn btn-primary btn-xs print_btn_pump_operator" data-href="' . action('\\Modules\\PetroGeneral\\Http\\Controllers\\DailyCollectionController@print', [$row->id]) . '"><i class="fa fa-print" aria-hidden="true"></i> ' . __('petrogeneral::lang.print') . '</button>';
                            
                            if (!$is_pumper_dashboard_enabled && empty($row->settlement_no) && empty($row->added_to_account) && empty(auth()->user()->pump_operator_id)) {
                                if (auth()->user()->can('daily_collection.edit')) {
                                    $buttons .= ' &nbsp; <button data-href="' . action('\\Modules\\PetroGeneral\\Http\\Controllers\\DailyCollectionController@edit', [$row->id]) . '" data-container=".pump_modal" class="btn btn-success btn-xs btn-modal edit_reference_button"><i class="fa fa-pencil" aria-hidden="true"></i> ' . __('lang_v1.edit') . '</button> &nbsp; ';
                                }
                                if (auth()->user()->can('daily_collection.delete')) {
                                    $buttons .= '<a class="btn btn-danger btn-xs delete_daily_collection" href="' . action('\\Modules\\PetroGeneral\\Http\\Controllers\\DailyCollectionController@destroy', [$row->id]) . '"><i class="fa fa-trash" aria-hidden="true"></i> ' . __('petrogeneral::lang.delete') . '</a>';
                                }
                            }
                            
                            return $buttons;
                        }
                    )

                /**

                 * @ChangedBy Afes

                 * @Date 25-05-2021

                 * @Task 12700

                 */

                    ->editColumn('current_amount', function ($row) {
                        return $this->productUtil->num_f((float) ($row->current_amount ?? 0));
                    })

                    ->editColumn('balance_collection', function ($row) {
                        return $this->productUtil->num_f((float) ($row->balance_collection ?? 0));
                    })

                    ->addColumn('total_collection', function ($row) {
                        $total = DB::table('daily_collections')
                            ->where('pump_operator_id', $row->pump_operator_id)
                            ->where('id', '<=', $row->id)
                        // ->whereNull('added_to_account')
                            ->sum('current_amount');

                        return $this->productUtil->num_f($total ?? 0);
                    })

                    ->addColumn('status', function ($row) {
                        if (empty($row->shift_id) && empty($row->shift_number) && empty($row->shift_no) && empty($row->assigned_shift_number)) {
                            return 'Pending';
                        }

                        if (!empty($row->settlement_id) || !empty($row->settlement_no)) {
                            return 'Completed';
                        }
                        
                        return 'Pending';
                    })

                    ->editColumn('collection_form_no', function ($row) {

                        return '<button data-id="' . $row->id . '" class="btn btn-success btn-xs btn-modal open-shift-modal">Shift</button> ' . e($row->collection_form_no);

                    })

                // ->editColumn('shift_number', function ($row) {

                //     if (empty($row->shift_number)) {

                //         $assigned_pumps = PumpOperatorAssignment::where('pump_operator_id', $row->pump_operator_id)

                //             ->select('shift_number')

                //             ->orderBy('id', 'DESC')

                //             ->first();

                //         return ! is_null($assigned_pumps) ? $assigned_pumps->shift_number : '';

                //     } else {

                //         return $row->shift_number;

                //     }

                // })

                    ->addColumn('shift_number', function ($row) {
                        // Match DailyCollectionSW behavior: just return the stored value with same priority
                        if (!empty($row->assigned_shift_number) && $row->assigned_shift_number > 0) {
                            return $row->assigned_shift_number;
                        } elseif (!empty($row->shift_number)) {
                            return $row->shift_number;
                        } elseif (!empty($row->shift_no)) {
                            return $row->shift_no;
                        }
                        return '';
                    })
                    
                    ->addColumn('shift_number_old', function ($row) {
                        // Keep old logic as fallback
                        if (!empty($row->shift_number)) {
                            return $row->shift_number;
                        }

                        if (!empty($row->shift_id)) {
                            $shift = PetroShift::find($row->shift_id);
                            if (!empty($shift) && !empty($shift->name)) {
                                return $shift->name;
                            }
                        }

                        return '';
                    })

                    ->addColumn(

                        'created_at',

                        '{{@format_date($created_at)}}'

                    )

                    ->editColumn(

                        'settlement_dates',

                        '{{!empty($settlement_dates) ? @format_date($settlement_dates) : ""}}'

                    )

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['action', 'total_collection', 'collection_form_no'])

                    ->make(true);
                } catch (\Exception $e) {
                    Log::error('DailyCollection DataTables Error: ' . $e->getMessage());
                    
                    return response()->json([
                        'error' => 'An error occurred while loading data.'
                    ], 500);
                }

            }

        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $pump_operators = PumpOperator::where('business_id', $business_id)
        ->where('active', 1)
        ->pluck('name', 'id');

        $settlement_nos = [];

        $message = $this->transactionUtil->getGeneralMessage('general_message_pump_management_checkbox');

        $customers = Contact::customersDropdown($business_id, false, true, 'customer');

        $card_types = [];

        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();

        if (! empty($card_group)) {

            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');

        }

        $slip_nos = DailyCard::where('business_id', $business_id)->distinct('slip_no')->get()->pluck('slip_no', 'slip_no');

        $card_numbers = DailyCard::where('business_id', $business_id)->distinct('card_number')->get()->pluck('card_number', 'card_number');

        $daily_card_settlements = DailyCard::leftjoin('settlements', 'settlements.id', 'daily_cards.settlement_no')

            ->where('daily_cards.business_id', $business_id)

            ->whereNotNull('daily_cards.settlement_no')

            ->whereNotNull('daily_cards.slip_no')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $daily_collection_settlements = DailyCollection::leftjoin('settlements', 'settlements.id', 'daily_collections.settlement_id')

            ->where('daily_collections.business_id', $business_id)

            ->where('daily_collections.type', 'daily_collection')

            ->whereNotNull('daily_collections.settlement_id')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $daily_voucher_settlements = DailyVoucher::leftjoin('settlements', 'settlements.id', 'daily_vouchers.settlement_no')

            ->where('daily_vouchers.business_id', $business_id)

            ->whereNotNull('daily_vouchers.settlement_no')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $shortage_settlements = PumpOperatorPayment::leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

            ->where('pump_operator_payments.business_id', $business_id)

            ->whereIn('payment_type', ['shortage', 'excess'])

            ->whereNotNull('pump_operator_payments.settlement_no')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $cheques_settlements = PumpOperatorPayment::leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

            ->where('pump_operator_payments.business_id', $business_id)

            ->whereIn('payment_type', ['cheque'])

            ->whereNotNull('pump_operator_payments.settlement_no')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $others_settlements = PumpOperatorPayment::leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

            ->where('pump_operator_payments.business_id', $business_id)

            ->whereIn('payment_type', ['other'])

            ->whereNotNull('pump_operator_payments.settlement_no')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $is_pending_shift = false;

        $dailyShift = PetroDailyShift::where('business_id', $business_id)

            ->where('status', 1)

            ->orderBy('updated_at', 'desc')

        // ->latest()

            ->first();

        if (! $dailyShift) {

            $dailyShift = PetroDailyShift::where('business_id', $business_id)

                ->where('status', 0)

                ->orderBy('updated_at', 'desc')

            // ->latest()

                ->first();

            if ($dailyShift) {

                $is_pending_shift = true;

            }

        }

        if ($dailyShift) {

            $daily_shift_id = $dailyShift->id;

        } else {

            $dailyShift = PetroDailyShift::create([

                'business_id'             => $business_id,

                'shift_no'                => $this->incrementCode((PetroDailyShift::orderBy('id', 'DESC')->first())->shift_no ?? 'DSN-000'),

                'date'                    => now()->format('Y-m-d'),

                'time'                    => now()->format('H:i:s'),

                'user'                    => auth()->id(),

                'status'                  => 1, // Assuming 1 means "open"

                'pump_operators_assigned' => explode(',', ''),

                'pump_operators_pending'  => $pump_operators,

            ]);

            $daily_shift_id = $dailyShift->id;

        }

        $all_pump_operators = PumpOperator::where('business_id', $business_id)

            ->where('status', 1)

            ->where('active', 1)

            ->pluck('name', 'id')

            ->toArray();

        $daily_shift_no = $dailyShift->shift_no;

//        if($dailyShift->status != 1){

//            $daily_shift_no = $this->incrementCode($dailyShift->shift_no);

//        }

        $pump_operators_shift = $all_pump_operators;

        $pending_ids = explode(',', $dailyShift->pump_operator_pending);

        $assigned_ids = explode(',', $dailyShift->pump_operator_assigned);

        $pending_operators = PumpOperator::whereIn('id', $pending_ids)
            ->where('active', 1)

            ->pluck('name', 'id')

            ->toArray();

        $assigned_operators = PumpOperator::whereIn('id', $assigned_ids)
            ->where('active', 1)

            ->pluck('name', 'id')

            ->toArray();

        $pump_operators_shift = $dailyShift->status == 1 ? $all_pump_operators : $pending_operators;

        $dailyCashShiftNumbers = PetroDailyShift::where('business_id', $business_id)

            ->where('status', 0)

            ->pluck('shift_no')->toArray();

        return view('petrogeneral::daily_collection.index')->with(compact(

            'dailyCashShiftNumbers', 'card_types', 'customers', 'pump_operators_shift', 'pending_operators', 'assigned_operators',

            'business_locations',

            'pump_operators',

            'settlement_nos',

            'message',

            'slip_nos',

            'dailyShift',

            'card_numbers', 'is_pending_shift',

            'daily_card_settlements', 'daily_shift_no', 'daily_shift_id',

            'daily_collection_settlements', 'daily_voucher_settlements', 'shortage_settlements', 'cheques_settlements', 'others_settlements'

        ));

    }

    private function incrementCode(string $code): string
    {

        $parts = explode('-', $code);

        if (count($parts) !== 2) {

            error_log('Invalid code format');

            return $code;

        }

        $prefix = $parts[0];

        $number = $parts[1];

        $numberLength = strlen($number);

        $incremented = str_pad((intval($number) + 1), $numberLength, '0', STR_PAD_LEFT);

        return $prefix . '-' . $incremented;

    }

    public function extractLastInteger($text)
    {

        if (preg_match('/\d+$/', $text, $matches)) {

            return intval($matches[0]);

        } else {

            return 0;

        }

    }

    public function getDailyCashStatusData(Request $request)
    {

        Log::info('DB connection at getDailyCashStatusData:', [\DB::connection()->getDatabaseName()]);

        $validator = Validator::make($request->all(), [

            'shift' => 'required',

        ]);

        if ($validator->fails()) {

            return response()->json([

                'success' => false,

                'msg'     => $validator->errors()->first() ?? 'Please provide valid details.',

            ], 422);

        }

        try {

            $business_id = request()->session()->get('user.business_id');

            $dailyCollection = DailyCollection::with('operator')->where('business_id', $business_id)

                ->whereShiftNumber($request->shift)->get();

            $collections = DailyCollection::join('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')

                ->where('daily_collections.business_id', $business_id)

                ->where('daily_collections.type', 'daily_collection')

                ->when(! empty($request->shift), function ($query) use ($request) {

                    return $query->where('daily_collections.shift_number', $request->shift);

                })

                ->orderBy('daily_collections.id', 'desc')

                ->select([

                    'daily_collections.current_amount as amount', 'pump_operator_id',

                    'pump_operators.*',

                ])

                ->get();

            $operatorPayment = $collections->groupBy('pump_operator_id')->map(function ($group) {

                return [

                    'pump_operator_id'    => $group->first()->pump_operator_id,

                    'name'                => $group->first()->name,

                    'total_amount'        => $group->sum('amount'),

                    'total_short_amount'  => $group->sum('short_amount'),

                    'total_excess_amount' => $group->sum('excess_amount'),

                ];

            })->values();

            $daily_collection_total = $collections->sum('amount');

            // $operatorPayment = PumpOperatorPayment::join('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')

            //         ->where('pump_operator_payments.business_id', $business_id)

            //         ->groupBy('pump_operator_payments.pump_operator_id', 'pump_operators.name')

            //         ->select(

            //             'pump_operator_payments.pump_operator_id',

            //             'pump_operators.name as operator_name',

            //             DB::raw('SUM(pump_operator_payments.payment_amount) as total_payment')

            //         )

            //         ->get();

            $totalAmount = $operatorPayment->sum('total_amount');

            $other_income_cash = 0;

            $operators = [

                'total_amount'      => $totalAmount,

                'operators_payment' => $operatorPayment,

            ];

            $expenses = Transaction::join('expense_categories', 'transactions.expense_category_id', '=', 'expense_categories.id')

                ->where([

                    'transactions.type'         => 'expense',

                    'transactions.shift_number' => $request->shift,

                    'transactions.business_id'  => $business_id,

                ])

            // ->whereDate('transactions.created_at', '>=', $date)

                ->groupBy('expense_categories.id', 'expense_categories.name')

                ->select(

                    // 'transactions.id as id',

                    'expense_categories.name as expense_name',

                    DB::raw('SUM(transactions.final_total) as amount')

                )

                ->get();

            $expense_amount = $expenses->sum('amount');

            $expense_total = [

                'expense_all' => $expense_amount,

                'expenses'    => $expenses,

            ];

            $customer_payments = TransactionPayment::join('contacts', 'transaction_payments.payment_for', '=', 'contacts.id')

                ->where([

                    'transaction_payments.shift_number' => $request->shift,

                ])

            // ->whereDate('created_at', '>=', $date)

                ->groupBy('contacts.id', 'contacts.name')

                ->select(

                    // 'transactions.id as id',

                    'contacts.name as customer_name',

                    'transaction_payments.payment_for',

                    'transaction_payments.shift_number',

                    'contacts.id',

                    DB::raw('SUM(transaction_payments.amount) as amount')

                )

                ->get();

            $customer_payment = $customer_payments->sum('amount');

            $customer_payment_list = [

                'total' => $customer_payment,

                'list'  => $customer_payments,

            ];

            $account_receivable = Account::where('business_id', $business_id)->where('name', 'Accounts Receivable')->where('is_closed', 0)->first();

            $account_receivable_id = ! empty($account_receivable) ? $account_receivable->id : 0;

            $totalCustomerCashPayments = AccountTransaction::leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')

                ->where('account_transactions.account_id', $account_receivable_id)

                ->where('account_transactions.type', 'debit')

            // ->whereDate('account_transactions.operation_date', '=', $request->date)

                ->sum('account_transactions.amount');

            // $cash_deposit = AccountTransaction::where(['business_id' => $business_id, 'sub_type' => 'deposit'])

            //     ->where('type','debit')

            //     ->where('shift_number', $request->shift)

            //     ->sum('amount');

            $cash_account_id = Account::getAccountByAccountName('Cash')->id;

            $bank_group_id = AccountGroup::getGroupByName('Bank Account', true);

            $bank_account_ids = DB::table('accounts')

                ->where('asset_type', $bank_group_id)

                ->pluck('id');

            $dailyShift = PetroDailyShift::where('business_id', $business_id)->where('shift_no', $request->shift)->first();
            $shiftDate = $dailyShift ? $dailyShift->date : \Carbon\Carbon::now()->format('Y-m-d');

            $cash_deposit = DB::table('account_transactions')

                ->where('business_id', $business_id)

                ->whereDate('operation_date', $shiftDate)

                ->where('account_id', $cash_account_id)

            // ->whereIn('related_account_id', $bank_account_ids)

                ->where('type', 'credit')

                ->sum('amount');

            $cash_account_id = Account::getAccountByAccountName('Cash')->id;

            $expense_account_type_id = DB::table('account_types')

                ->where('name', 'Expenses')

                ->value('id');

            $totalCashExpenses = AccountTransaction::leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')

                ->where('accounts.account_type_id', $expense_account_type_id) // Corrected line

                ->where('account_transactions.type', 'credit')

                ->where('account_transactions.account_id', $cash_account_id) // Paid from cash

            // ->whereDate('account_transactions.operation_date', '=', $request->date)

                ->sum('account_transactions.amount');

            $bank_group_id = AccountGroup::getGroupByName('Bank Account', true);

            $bank_account_ids = DB::table('accounts')

                ->where('asset_type', $bank_group_id) // Replace '2' with the actual asset_type value for "Bank"

                ->pluck('id');

            $total_cash_deposits = DB::table('account_transactions')

                ->where('account_transactions.account_id', $cash_account_id) // Cash account is credited

                ->whereIn('account_transactions.related_account_id', $bank_account_ids) // Deposited into Bank accounts

                ->where('account_transactions.type', 'credit') // Money going out from cash

                ->sum('account_transactions.amount');

            // $balance_in_hand = $daily_collection_total + $totalCustomerCashPayments - $totalCashExpenses - $total_cash_deposits;

            $balance_in_hand = $totalAmount + $customer_payment - $expense_amount - $cash_deposit;

            $totalCustomerCashPayments = AccountTransaction::leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')

                ->where('account_transactions.account_id', $account_receivable_id)

                ->where('account_transactions.type', 'debit')

            // ->whereDate('account_transactions.operation_date', '=', $request->date)

            // ->sum('account_transactions.amount');

                ->first();

            return response()->json([

                'cash_collection'             => $daily_collection_total,

                'other_income_cash'           => $other_income_cash,

                'cash_deposit'                => $cash_deposit,

                'cash_deposit_expense_table'  => $total_cash_deposits,

                'operators'                   => $operators,

                'expense_total'               => $expense_total,

                'balance_in_hand'             => $balance_in_hand,

                'customer_payment_list'       => $customer_payment_list,

                'customer_payment'            => $totalCustomerCashPayments,

                'cash_expenses_account_table' => $totalCashExpenses,

                'cash_expenses'               => $expense_amount,

                'shifts'                      => $request->shift,

                'customer_details'            => $customer_payments[0] ?? null,

            ], 200);

        } catch (\Throwable $th) {

            return response()->json([

                'success' => false,

                'msg'     => $th->getMessage(),

                // 'msg' => 'Some error occurred on the server.'

            ], 500);

        }

    }

    public function getByDate(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'date' => 'required',

        ]);

        if ($validator->fails()) {

            return response()->json([

                'success' => false,

                'msg'     => $validator->errors()->first() ?? 'Please provide valid details.',

            ], 422);

        }

        try {

            $business_id = request()->session()->get('user.business_id');

            $account_receivable = Account::where('business_id', $business_id)->where('name', 'Accounts Receivable')->where('is_closed', 0)->first();

            $account_receivable_id = ! empty($account_receivable) ? $account_receivable->id : 0;

            $totalCustomerCashPayments = AccountTransaction::leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')

                ->where('account_transactions.account_id', $account_receivable_id)

                ->where('account_transactions.type', 'debit')

                ->whereDate('account_transactions.operation_date', '>=', $request->date)

                ->sum('account_transactions.amount');

            return response()->json([

                // 'expense_total' => $expense_total,

                // 'balance_in_hand' => $balance_in_hand,

                // 'customer_payment_list'=> $customer_payment_list,

                'customer_payment' => $totalCustomerCashPayments,

                // 'cash_expenses' => $expense_amount,

                // 'shifts' => $shifts

            ], 200);

        } catch (\Throwable $th) {

            return response()->json([

                'success' => false,

                'msg'     => $th->getMessage(),

                // 'msg' => 'Some error occurred on the server.'

            ], 500);

        }

    }

    public function settings()
    {

        $business_id = request()->session()->get('user.business_id');

        $user = auth()->user();

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {

            abort(403, 'Unauthorized Access');

        }

        // Get settings created by current user

        $query = DayCountSetting::where('created_by', $user->id)

            ->orderBy('created_at', 'desc') // Changed 'dec' to 'created_at' for clarity

            ->get();

        $settings = Datatables::of($query)

            ->addColumn('action', function ($row) {

                $checked = $row->status ? 'checked' : '';

                return '

                    <input type="checkbox" class="toggle-status-switch"

                        data-id="' . $row->id . '"

                        ' . $checked . '

                        data-toggle="toggle"

                        data-on="Enabled"

                        data-off="Disabled"

                        data-onstyle="success"

                        data-offstyle="danger">';

            })

        // ->addColumn('ending_', '{{@row->day_counted_from == same_day ? Same Day : Following Day)}}')

            ->editColumn('ending_date_type', function ($row) {

                return $row->ending_date_type === 'same_day' ? 'Same Day' : 'Following Day';

            })

            ->addColumn('start_time', '{{@format_time($day_counted_from)}}')

            ->editColumn('time_till', '{{@format_time($time_till)}}')

        // ->addColumn('start_time', function ($row) {

        //     return format_time($row->day_counted_from);

        // })

            ->addColumn('date', function ($row) {

                return $row->created_at;

            })

            ->addColumn('user_entered', function ($row) use ($user) {

                return $user->username;

            })

            ->removeColumn('updated_at')

            ->rawColumns(['action']);

        return $settings->make(true);

    }

    public function saveSettings(Request $request)
    {

        if ($request->ajax()) {

            $validator = Validator::make($request->all(), [

                'day_counted_from' => 'required|date_format:H:i',

                'time_till'        => 'required|date_format:H:i',

                'ending_date_type' => 'required|in:same_day,following_day',

            ]);

            if ($validator->fails()) {

                return response()->json([

                    'success' => false,

                    'msg'     => $validator->errors()->first() ?? 'Please provide valid details.',

                ], 422);

            }

            try {

                // Disable previous settings

                DayCountSetting::query()->update(['status' => 0]);

                // Save new setting as enabled

                $data = [

                    'day_counted_from' => $request->day_counted_from,

                    'time_till'        => $request->time_till,

                    'ending_date_type' => $request->ending_date_type,

                    'status'           => 1,

                    'created_by'       => auth()->id(),

                ];

                DayCountSetting::create($data);

                return response()->json([

                    'success' => true,

                    'msg'     => 'Settings saved successfully.',

                ], 201);

            } catch (\Throwable $th) {

                \Log::emergency('File: ' . $th->getFile() . ' Line: ' . $th->getLine() . ' Message: ' . $th->getMessage());

                return response()->json([

                    'success' => false,

                    'msg'     => 'Some error occurred on the server.',

                ], 500);

            }

        }

        // If request is not AJAX

        abort(403, 'Unauthorized');

    }

    public function getDailyCashStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'date'  => 'required|date_format:Y-m-d',

            'shift' => 'nullable',

        ]);

        if ($validator->fails()) {

            return response()->json([

                'success' => false,

                'msg'     => $validator->errors()->first() ?? 'Please provide valid details.',

            ], 422);

        }

        $business_id = request()->session()->get('user.business_id');

        $date = $request->date;

        $setting = DayCountSetting::orderBy('id', 'desc')->first();

        if (true) {

            try {

                // $start_time = $setting->day_counted_from;

                // $end_time =  $setting->time_till;

                $collections = DailyCollection::join('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')

                    ->where('daily_collections.business_id', $business_id)

                    ->where('daily_collections.type', 'daily_collection')

                // ->when(!empty($request->shift), function ($query) use ($request) {

                //     return $query->where('daily_collections.shift_no', $request->shift);

                // })

                    ->whereDate('daily_collections.created_at', '=', $request->date)

                    ->orderBy('daily_collections.id', 'desc')

                    ->select([

                        'daily_collections.current_amount as amount',

                    ])

                    ->get();

                $collections = DailyCollection::join('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')

                    ->where('daily_collections.business_id', $business_id)

                    ->where('daily_collections.type', 'daily_collection')

                    ->when(! empty($request->shift), function ($query) use ($request) {

                        return $query->where('daily_collections.shift_number', $request->shift);

                    })

                    ->whereDate('daily_collections.created_at', '=', $request->date)

                    ->orderBy('daily_collections.id', 'desc')

                    ->select([

                        'daily_collections.current_amount as amount', 'pump_operator_id',

                        'pump_operators.*',

                    ])

                    ->get();

                $operatorPayment = $collections->groupBy('pump_operator_id')->map(function ($group) {

                    return [

                        'pump_operator_id'    => $group->first()->pump_operator_id,

                        'name'                => $group->first()->name,

                        'total_amount'        => $group->sum('amount'),

                        'total_short_amount'  => $group->sum('short_amount'),

                        'total_excess_amount' => $group->sum('excess_amount'),

                    ];

                })->values();

                $daily_collection_total = $collections->sum('amount');

                $shifts = $collections->pluck('shift_number')->unique()->values()->toArray();

                // $shifts = $collections->pluck('shift_number')->filter()->values()->toArray();

                $operatorPayment = PumpOperatorPayment::where(['business_id' => $business_id])

                    ->whereDate('created_at', '>=', $date)

                    ->get();

                $operatorPayment = PumpOperatorPayment::join('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')

                    ->where('pump_operator_payments.business_id', $business_id)

                    ->whereDate('pump_operator_payments.created_at', $request->date)

                    ->groupBy('pump_operator_payments.pump_operator_id', 'pump_operators.name')

                    ->select(

                        'pump_operator_payments.pump_operator_id',

                        'pump_operators.name as operator_name',

                        DB::raw('SUM(pump_operator_payments.payment_amount) as total_payment')

                    )

                    ->get();

                $operatorPayment = PumpOperatorPayment::join('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')

                    ->where('pump_operator_payments.business_id', $business_id)

                    ->groupBy('pump_operator_payments.pump_operator_id', 'pump_operators.name')

                    ->select(

                        'pump_operator_payments.pump_operator_id',

                        'pump_operators.name as operator_name',

                        DB::raw('SUM(pump_operator_payments.payment_amount) as total_payment')

                    )

                    ->get();

                $totalAmount = $operatorPayment->sum('total_payment');

                $other_income_cash = 0;

                $operators = [

                    'total_amount'      => $totalAmount,

                    'operators_payment' => $operatorPayment,

                ];

                $expenses = Transaction::join('expense_categories', 'transactions.expense_category_id', '=', 'expense_categories.id')

                    ->where([

                        'transactions.type'        => 'expense',

                        'transactions.business_id' => $business_id,

                    ])

                    ->when(! empty($request->shift), function (Builder $query) use ($request) {

                        $query->where('transactions.shift_number', $request->shift);

                    })

                    ->whereDate('transactions.created_at', '=', $date)

                    ->groupBy('expense_categories.id', 'expense_categories.name')

                    ->select(

                        // 'transactions.id as id',

                        'expense_categories.name as expense_name',

                        DB::raw('SUM(transactions.final_total) as amount')

                    )

                    ->get();

                $expense_amount = $expenses->sum('amount');

                $expense_total = [

                    'expense_all' => $expense_amount,

                    'expenses'    => $expenses,

                ];

                $other_sale_cash = 0;

                $customer_payments = CustomerPayment::join('customers', 'customer_payments.customer_id', '=', 'customers.id')

                    ->where([

                        'customer_payments.payment_method' => 'cash',

                        'customer_payments.business_id'    => $business_id,

                    ])

                    ->whereDate('customer_payments.created_at', '>=', $date)

                    ->groupBy('customers.id', 'customers.first_name')

                    ->select(

                        // 'transactions.id as id',

                        'customers.first_name as customer_name',

                        DB::raw('SUM(customer_payments.amount) as amount')

                    )

                    ->get();

                $customer_payments = TransactionPayment::join('customers', 'transaction_payments.payment_for', '=', 'customers.id')

                    ->where([

                        'transaction_payments.method'      => 'cash',

                        'transaction_payments.business_id' => $business_id,

                    ])

                    ->when(! empty($request->shift), function (Builder $query) use ($request) {

                        $query->where('transaction_payments.shift_number', $request->shift);

                    })

                    ->whereDate('transaction_payments.created_at', '=', $date)

                    ->groupBy('customers.id', 'customers.first_name')

                    ->select(

                        // 'transactions.id as id',

                        'customers.first_name as customer_name',

                        DB::raw('SUM(transaction_payments.amount) as amount')

                    )

                    ->get();

                $customer_payment = $customer_payments->sum('amount');

                $customer_payment_list = [

                    'total' => $customer_payment,

                    'list'  => $customer_payments,

                ];

                $cash_deposit = AccountTransaction::where(['business_id' => $business_id, 'sub_type' => 'deposit'])

                    ->whereDate('created_at', '>=', $date)

                    ->sum('amount');

                // $balance_in_hand= $daily_collection_total + $other_sale_cash - $expense_amount - $cash_deposit;

                $account_receivable = Account::where('business_id', $business_id)->where('name', 'Accounts Receivable')->where('is_closed', 0)->first();

                $account_receivable_id = ! empty($account_receivable) ? $account_receivable->id : 0;

                $totalCustomerCashPayments = AccountTransaction::leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')

                    ->where('account_transactions.account_id', $account_receivable_id)

                    ->where('account_transactions.type', 'debit')

                    ->whereDate('account_transactions.operation_date', '=', $request->date)

                    ->sum('account_transactions.amount');

                $cash_account_id = Account::getAccountByAccountName('Cash')->id;

                $expense_account_type_id = DB::table('account_types')

                    ->where('name', 'Expenses')

                    ->value('id');

                $totalCashExpenses = AccountTransaction::leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')

                    ->where('accounts.account_type_id', $expense_account_type_id) // Corrected line

                    ->where('account_transactions.type', 'credit')

                    ->where('account_transactions.account_id', $cash_account_id) // Paid from cash

                    ->whereDate('account_transactions.operation_date', '=', $request->date)

                    ->sum('account_transactions.amount');

                $bank_group_id = AccountGroup::getGroupByName('Bank Account', true);

                $bank_account_ids = DB::table('accounts')

                    ->where('asset_type', $bank_group_id) // Replace '2' with the actual asset_type value for "Bank"

                    ->pluck('id');

                $total_cash_deposits = DB::table('account_transactions')

                    ->where('account_transactions.account_id', $cash_account_id) // Cash account is credited

                    ->whereIn('account_transactions.related_account_id', $bank_account_ids) // Deposited into Bank accounts

                    ->where('account_transactions.type', 'credit') // Money going out from cash

                    ->whereDate('account_transactions.operation_date', '=', $request->date)

                    ->sum('account_transactions.amount');

                $balance_in_hand = $daily_collection_total + $totalCustomerCashPayments - $totalCashExpenses - $total_cash_deposits;

                return response()->json([

                    'cash_collection'             => $daily_collection_total,

                    'other_income_cash'           => $other_income_cash,

                    'cash_deposit'                => $cash_deposit,

                    'cash_deposit_expense_table'  => $total_cash_deposits,

                    'operators'                   => $operators,

                    'expense_total'               => $expense_total,

                    'balance_in_hand'             => $balance_in_hand,

                    'customer_payment_list'       => $customer_payment_list,

                    'customer_payment'            => $totalCustomerCashPayments,

                    'cash_expenses'               => $expense_amount,

                    'cash_expenses_account_table' => $totalCashExpenses,

                    'shifts'                      => $shifts,

                ], 200);

            } catch (\Throwable $th) {

                return response()->json([

                    'success' => false,

                    'msg'     => $th->getMessage(),

                    // 'msg' => 'Some error occurred on the server.'

                ], 500);

            }

        }

    }
    public function collectionSummary()
{
    $business_id = request()->session()->get('user.business_id');

    if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {
        abort(403, 'Unauthorized Access');
    }

    if (request()->ajax()) {
        $business_id = request()->session()->get('user.business_id');

        // DAILY CASH - FIXED VERSION
        $daily_cash = DailyCollection::leftJoin('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')
            ->leftJoin('settlements', function($join) {
                $join->on('daily_collections.settlement_id', '=', 'settlements.id')
                     ->whereNotNull('daily_collections.settlement_id');
            })
            ->leftJoin('pump_operator_assignments', 'pump_operators.id', '=', 'pump_operator_assignments.pump_operator_id')
            ->where('daily_collections.business_id', $business_id)
            ->where('daily_collections.type', 'daily_collection_sw')
            ->select([
                DB::raw('CONVERT("daily_cash" USING utf8mb4) as type'),
                DB::raw('CONVERT(MAX(daily_collections.collection_form_no) USING utf8mb4) as collection_form_no'),
                DB::raw('DATE(daily_collections.created_at) as date'),
                DB::raw('CONVERT(COALESCE(pump_operator_assignments.shift_number, daily_collections.shift_number) USING utf8mb4) as shift_number'),
                DB::raw('CONVERT(pump_operators.name USING utf8mb4) as pump_operator_name'),
                DB::raw('SUM(daily_collections.current_amount) as total_amount'),
                DB::raw('CONVERT(COALESCE(GROUP_CONCAT(DISTINCT settlements.settlement_no ORDER BY settlements.settlement_no SEPARATOR ", "), "") USING utf8mb4) as settlement_nos'),
            ])
            ->groupBy(DB::raw('DATE(daily_collections.created_at)'), 'shift_number', 'pump_operators.id');

        // DAILY CASH - PD (Settlement PD) - include cash from daily_collections with type = 'daily_collection'
        $daily_cash_pd = DailyCollection::leftJoin('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')
            ->leftJoin('settlements', function ($join) {
                $join->on('daily_collections.settlement_id', '=', 'settlements.id')
                     ->whereNotNull('daily_collections.settlement_id');
            })
            ->leftJoin('pump_operator_assignments', 'pump_operators.id', '=', 'pump_operator_assignments.pump_operator_id')
            ->where('daily_collections.business_id', $business_id)
            ->where('daily_collections.type', 'daily_collection')
            ->select([
                DB::raw('CONVERT("daily_cash" USING utf8mb4) as type'),
                DB::raw('CONVERT(MAX(daily_collections.collection_form_no) USING utf8mb4) as collection_form_no'),
                DB::raw('DATE(daily_collections.created_at) as date'),
                DB::raw('CONVERT(COALESCE(pump_operator_assignments.shift_number, daily_collections.shift_number) USING utf8mb4) as shift_number'),
                DB::raw('CONVERT(pump_operators.name USING utf8mb4) as pump_operator_name'),
                DB::raw('SUM(daily_collections.current_amount) as total_amount'),
                DB::raw('CONVERT(COALESCE(GROUP_CONCAT(DISTINCT settlements.settlement_no ORDER BY settlements.settlement_no SEPARATOR ", "), "") USING utf8mb4) as settlement_nos'),
            ])
            ->groupBy(DB::raw('DATE(daily_collections.created_at)'), 'shift_number', 'pump_operators.id');

            // Walking Customer Cash Payments (PumpOperatorPayment type='cash')
            $walking_cash = PumpOperatorPayment::leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', 'pump_operator_payments.settlement_no', '=', 'settlements.id')
                ->leftJoin('petro_daily_shifts', 'pump_operator_payments.shift_id', '=', 'petro_daily_shifts.id')
                ->where('pump_operator_payments.business_id', $business_id)
                ->where('payment_type', 'cash')
                ->select([
                    DB::raw('CONVERT("daily_cash" USING utf8mb4) as type'),
                    DB::raw('CONVERT("" USING utf8mb4) as collection_form_no'),
                    DB::raw('DATE(pump_operator_payments.date_and_time) as date'),
                    DB::raw('CONVERT(COALESCE(petro_daily_shifts.shift_no, "-") USING utf8mb4) as shift_number'),
                    DB::raw('CONVERT(pump_operators.name USING utf8mb4) as pump_operator_name'),
                    DB::raw('SUM(pump_operator_payments.payment_amount) as total_amount'),
                    DB::raw('CONVERT(COALESCE(GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", "), "") USING utf8mb4) as settlement_nos'),
                ])
                ->groupBy(DB::raw('DATE(pump_operator_payments.date_and_time)'), 'shift_number', 'pump_operators.id');
            // DAILY CARDS (join settlement by both text settlement_no AND numeric ID)
            $daily_cards = DailyCard::leftJoin('pump_operators', 'daily_cards.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', function ($join) {
                    $join->on(DB::raw('BINARY settlements.settlement_no'), '=', DB::raw('BINARY daily_cards.settlement_no'))
                         ->orWhereColumn('settlements.id', 'daily_cards.settlement_no');
                })
                ->leftJoin('petro_daily_shifts', 'pump_operators.id', '=', 'petro_daily_shifts.pump_operator_assigned')
                ->where('daily_cards.business_id', $business_id)
                ->select([
                    DB::raw('CONVERT("daily_cards" USING utf8mb4) as type'),
                    DB::raw('CONVERT(MAX(daily_cards.collection_no) USING utf8mb4) as collection_form_no'),
                    DB::raw('DATE(daily_cards.date) as date'),
                    DB::raw('CONVERT(COALESCE(petro_daily_shifts.shift_no, "-") USING utf8mb4) as shift_number'),
                    DB::raw('CONVERT(pump_operators.name USING utf8mb4) as pump_operator_name'),
                    DB::raw('SUM(daily_cards.amount) as total_amount'),
                    DB::raw('CONVERT(COALESCE(GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", "), "-") USING utf8mb4) as settlement_nos'),
                ])
                ->groupBy(DB::raw('DATE(daily_cards.date)'), 'shift_number', 'pump_operators.id');

            // DAILY CREDIT SALES (join settlement by both text settlement_no AND numeric ID)
            $daily_credit_sales = DailyVoucher::leftJoin('pump_operators', 'daily_vouchers.operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', function ($join) {
                    $join->on(DB::raw('CONVERT(daily_vouchers.settlement_no USING utf8mb4)'),
                        '=',
                        DB::raw('CONVERT(settlements.settlement_no USING utf8mb4)'))
                        ->orWhereColumn('settlements.id', 'daily_vouchers.settlement_no');
                })
                ->leftJoin('petro_daily_shifts', 'pump_operators.id', '=', 'petro_daily_shifts.pump_operator_assigned')
                ->where('daily_vouchers.business_id', $business_id)
                ->select([
                    DB::raw('CONVERT("daily_credit_sales" USING utf8mb4) as type'),
                    DB::raw('CONVERT(MAX(daily_vouchers.daily_vouchers_no) USING utf8mb4) as collection_form_no'),
                    DB::raw('DATE(daily_vouchers.transaction_date) as date'),
                    DB::raw('CONVERT(COALESCE(petro_daily_shifts.shift_no, "-") USING utf8mb4) as shift_number'),
                    DB::raw('CONVERT(pump_operators.name USING utf8mb4) as pump_operator_name'),
                    DB::raw('SUM(daily_vouchers.total_amount) as total_amount'),
                    DB::raw('CONVERT(COALESCE(GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", "), "-") USING utf8mb4) as settlement_nos'),
                ])
                ->groupBy(DB::raw('DATE(daily_vouchers.transaction_date)'), 'shift_number', 'pump_operators.id')
                ->orderBy(DB::raw('DATE(daily_vouchers.transaction_date)'), 'desc')
                ->orderBy('daily_vouchers.created_at', 'desc');

            // SHORTAGE/EXCESS (pump_operator_payments.settlement_no stores settlement id in PD flow)
            $shortage_excess = PumpOperatorPayment::leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', 'pump_operator_payments.settlement_no', '=', 'settlements.id')
                ->leftJoin('pump_operator_assignments', 'pump_operators.id', '=', 'pump_operator_assignments.pump_operator_id')
                ->where('pump_operator_payments.business_id', $business_id)
                ->whereIn('payment_type', ['shortage', 'excess'])
                ->select([
                    DB::raw('CONVERT("shortage_excess" USING utf8mb4) as type'),
                    DB::raw('CONVERT("" USING utf8mb4) as collection_form_no'),
                    DB::raw('DATE(pump_operator_payments.date_and_time) as date'),
                    DB::raw('CONVERT(COALESCE(pump_operator_assignments.shift_number, "-") USING utf8mb4) as shift_number'),
                    DB::raw('CONVERT(pump_operators.name USING utf8mb4) as pump_operator_name'),
                    DB::raw('SUM(pump_operator_payments.payment_amount) as total_amount'),
                    DB::raw('CONVERT(COALESCE(GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", "), "-") USING utf8mb4) as settlement_nos'),
                ])
                ->groupBy(DB::raw('DATE(pump_operator_payments.date_and_time)'), 'shift_number', 'pump_operators.id');

            // OTHER PAYMENTS (pump_operator_payments.settlement_no stores settlement id in PD flow)
            $other = PumpOperatorPayment::leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', 'pump_operator_payments.settlement_no', '=', 'settlements.id')
                ->leftJoin('pump_operator_assignments', 'pump_operators.id', '=', 'pump_operator_assignments.pump_operator_id')
                ->where('pump_operator_payments.business_id', $business_id)
                ->where('payment_type', 'other')
                ->select([
                    DB::raw('CONVERT("other_payments" USING utf8mb4) as type'),
                    DB::raw('CONVERT("" USING utf8mb4) as collection_form_no'),
                    DB::raw('DATE(pump_operator_payments.date_and_time) as date'),
                    DB::raw('CONVERT(COALESCE(pump_operator_assignments.shift_number, "-") USING utf8mb4) as shift_number'),
                    DB::raw('CONVERT(pump_operators.name USING utf8mb4) as pump_operator_name'),
                    DB::raw('SUM(pump_operator_payments.payment_amount) as total_amount'),
                    DB::raw('CONVERT(COALESCE(GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", "), "-") USING utf8mb4) as settlement_nos'),
                ])
                ->groupBy(DB::raw('DATE(pump_operator_payments.date_and_time)'), 'shift_number', 'pump_operators.id');

            // CHEQUE PAYMENTS (pump_operator_payments.settlement_no stores settlement id in PD flow)
            $cheque = PumpOperatorPayment::leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', 'pump_operator_payments.settlement_no', '=', 'settlements.id')
                ->leftJoin('pump_operator_assignments', 'pump_operators.id', '=', 'pump_operator_assignments.pump_operator_id')
                ->where('pump_operator_payments.business_id', $business_id)
                ->where('payment_type', 'cheque')
                ->select([
                    DB::raw('CONVERT("cheque" USING utf8mb4) as type'),
                    DB::raw('CONVERT("" USING utf8mb4) as collection_form_no'),
                    DB::raw('DATE(pump_operator_payments.date_and_time) as date'),
                    DB::raw('CONVERT(COALESCE(pump_operator_assignments.shift_number, "-") USING utf8mb4) as shift_number'),
                    DB::raw('CONVERT(pump_operators.name USING utf8mb4) as pump_operator_name'),
                    DB::raw('SUM(pump_operator_payments.payment_amount) as total_amount'),
                    DB::raw('CONVERT(COALESCE(GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", "), "-") USING utf8mb4) as settlement_nos'),
                ])
                ->groupBy(DB::raw('DATE(pump_operator_payments.date_and_time)'), 'shift_number', 'pump_operators.id');

            if (! empty(request()->pump_operator_id)) {
                $daily_cash->where('daily_collections.pump_operator_id', request()->pump_operator_id);
                $daily_cash_pd->where('daily_collections.pump_operator_id', request()->pump_operator_id);
                $walking_cash->where('pump_operator_payments.pump_operator_id', request()->pump_operator_id);
                $daily_cards->where('daily_cards.pump_operator_id', request()->pump_operator_id);
                $daily_credit_sales->where('daily_vouchers.operator_id', request()->pump_operator_id);
                $shortage_excess->where('pump_operator_payments.pump_operator_id', request()->pump_operator_id);
                $other->where('pump_operator_payments.pump_operator_id', request()->pump_operator_id);
                $cheque->where('pump_operator_payments.pump_operator_id', request()->pump_operator_id);
            }

            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $daily_cash->whereDate('daily_collections.created_at', '>=', request()->start_date)->whereDate('daily_collections.created_at', '<=', request()->end_date);
                $daily_cash_pd->whereDate('daily_collections.created_at', '>=', request()->start_date)->whereDate('daily_collections.created_at', '<=', request()->end_date);
                $walking_cash->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date)->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);
                $daily_cards->whereDate('daily_cards.date', '>=', request()->start_date)->whereDate('daily_cards.date', '<=', request()->end_date);
                $daily_credit_sales->whereDate('daily_vouchers.transaction_date', '>=', request()->start_date)->whereDate('daily_vouchers.transaction_date', '<=', request()->end_date);
                $shortage_excess->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date)->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);
                $other->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date)->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);
                $cheque->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date)->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);
            }

            switch (request()->daily_collection_type) {
                case 'daily_cash':
                    $query = $daily_cash->unionAll($daily_cash_pd)->unionAll($walking_cash);
                    break;
                case 'daily_voucher':
                    $query = $daily_credit_sales;
                    break;
                case 'daily_card':
                    $query = $daily_cards;
                    break;
                case 'shortage_excess':
                    $query = $shortage_excess;
                    break;
                case 'other':
                    $query = $other;
                    break;
                case 'cheque':
                    $query = $cheque;
                    break;
                default:
                    $query = $daily_cash->unionAll($daily_cash_pd)->unionAll($walking_cash)
                        ->unionAll($daily_cards)
                        ->unionAll($daily_credit_sales)
                        ->unionAll($shortage_excess)
                        ->unionAll($other)
                        ->unionAll($cheque);
                    break;
            }

            $query->orderBy('date', 'desc');

            // DATATABLE OUTPUT
            $fuel_tanks = Datatables::of($query)
                ->editColumn('total_amount', function ($row) {
                    return $this->productUtil->num_f((float) ($row->total_amount ?? 0));
                })
                ->editColumn('type', fn($row) => ucfirst(str_replace('_', ' ', $row->type)))
                ->editColumn('date', '{{@format_date($date)}}')
                ->removeColumn('id');

            return $fuel_tanks->make(true);
        }
    }

    // public function collectionSummary()

    // {

    //     $business_id = request()->session()->get('user.business_id');

    //     if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {

    //         abort(403, 'Unauthorized Access');

    //     }

    //     if (request()->ajax()) {

    //         $business_id = request()->session()->get('user.business_id');

    //         $daily_cash = DailyCollection::leftJoin('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')

    //             ->leftjoin('settlements', 'daily_collections.settlement_id', 'settlements.id')

    //             ->leftjoin('pump_operator_assignments', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')

    //             ->where('daily_collections.business_id', $business_id)

    //             ->select([

    //                 DB::raw('"daily_cash" as type'),

    //                 DB::raw('MAX(daily_collections.collection_form_no) as collection_form_no'),

    //                 'daily_collections.created_at as date',

    //                 'daily_collections.shift_number as shift_number',

    //                 'pump_operators.name as pump_operator_name',

    //                 DB::raw('SUM(DISTINCT daily_collections.current_amount) as total_amount'),

    //                 DB::raw('GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", ") as settlement_nos'),

    //             ])

    //             ->groupBy(DB::raw('DATE(daily_collections.created_at)'), 'pump_operators.id');

    //         $daily_cards = DailyCard::leftJoin('pump_operators', 'daily_cards.pump_operator_id', '=', 'pump_operators.id')

    //             ->leftJoin('settlements', 'daily_cards.settlement_no', '=', 'settlements.id')

    //             ->leftJoin('pump_operator_assignments', 'pump_operators.id', '=', 'pump_operator_assignments.pump_operator_id')

    //             ->where('daily_cards.business_id', $business_id)

    //             ->select([

    //                 'pump_operator_assignments.shift_number as shift_number',

    //                 DB::raw('"daily_cards" as type'),

    //                 DB::raw('MAX(daily_cards.collection_no) as collection_form_no'),

    //                 'daily_cards.date as date',

    //                 'pump_operators.name as pump_operator_name',

    //                 DB::raw('SUM(DISTINCT daily_cards.amount) as total_amount'),                                  // Use DISTINCT to avoid duplicates

    //                 DB::raw('GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", ") as settlement_nos'), // Use DISTINCT to avoid duplicates

    //             ])

    //             ->groupBy('daily_cards.date', 'pump_operators.id');

    //         $daily_credit_sales = DailyVoucher::leftJoin('pump_operators', 'daily_vouchers.operator_id', '=', 'pump_operators.id')

    //             ->leftjoin('settlements', 'daily_vouchers.settlement_no', 'settlements.id')

    //             ->leftjoin('pump_operator_assignments', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')

    //             ->where('daily_vouchers.business_id', $business_id)

    //             ->select([

    //                 DB::raw('"daily_credit_sales" as type'),

    //                 DB::raw('MAX(daily_vouchers.daily_vouchers_no) as collection_form_no'),

    //                 'daily_vouchers.transaction_date as date',

    //                 'pump_operators.name as pump_operator_name',

    //                 'pump_operator_assignments.shift_number as shift_number',

    //                 DB::raw('SUM(DISTINCT daily_vouchers.total_amount) as total_amount'),

    //                 DB::raw('GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", ") as settlement_nos'),

    //             ])

    //             ->groupBy('daily_vouchers.transaction_date', 'pump_operators.id');

    //         $shortage_excess = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')

    //             ->leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

    //             ->leftjoin('pump_operator_assignments', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')

    //             ->where('pump_operator_payments.business_id', $business_id)

    //             ->whereIn('payment_type', ['shortage', 'excess'])

    //             ->select([

    //                 DB::raw('"shortage_excess" as type'),

    //                 DB::raw('"" as collection_form_no'),

    //                 'pump_operator_payments.date_and_time as date',

    //                 'pump_operators.name as pump_operator_name',

    //                 'pump_operator_assignments.shift_number as shift_number',

    //                 DB::raw('SUM(DISTINCT pump_operator_payments.payment_amount) as total_amount'),

    //                 DB::raw('GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", ") as settlement_nos'),

    //             ])->groupBy(DB::raw('DATE(pump_operator_payments.date_and_time)'), 'pump_operators.id');

    //         $other = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')

    //             ->leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

    //             ->leftjoin('pump_operator_assignments', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')

    //             ->where('pump_operator_payments.business_id', $business_id)

    //             ->whereIn('payment_type', ['other'])

    //             ->select([

    //                 DB::raw('"other_payments" as type'),

    //                 DB::raw('"" as collection_form_no'),

    //                 'pump_operator_payments.date_and_time as date',

    //                 'pump_operators.name as pump_operator_name',

    //                 'pump_operator_assignments.shift_number as shift_number',

    //                 DB::raw('SUM(DISTINCT pump_operator_payments.payment_amount) as total_amount'),

    //                 DB::raw('GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", ") as settlement_nos'),

    //             ])->groupBy(DB::raw('DATE(pump_operator_payments.date_and_time)'), 'pump_operators.id');

    //         $cheque = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')

    //             ->leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

    //             ->leftjoin('pump_operator_assignments', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')

    //             ->where('pump_operator_payments.business_id', $business_id)

    //             ->whereIn('payment_type', ['cheque'])

    //             ->select([

    //                 DB::raw('"cheque" as type'),

    //                 DB::raw('"" as collection_form_no'),

    //                 'pump_operator_payments.date_and_time as date',

    //                 'pump_operators.name as pump_operator_name',

    //                 'pump_operator_assignments.shift_number as shift_number',

    //                 DB::raw('SUM(DISTINCT pump_operator_payments.payment_amount) as total_amount'),

    //                 DB::raw('GROUP_CONCAT(DISTINCT settlements.settlement_no SEPARATOR ", ") as settlement_nos'),

    //             ])->groupBy(DB::raw('DATE(pump_operator_payments.date_and_time)'), 'pump_operators.id');

    //         if (! empty(request()->pump_operator_id)) {

    //             $daily_cash->where('daily_collections.pump_operator_id', request()->pump_operator_id);

    //             $daily_cards->where('daily_cards.pump_operator_id', request()->pump_operator_id);

    //             $shortage_excess->where('pump_operator_payments.pump_operator_id', request()->pump_operator_id);

    //             $other->where('pump_operator_payments.pump_operator_id', request()->pump_operator_id);

    //             $cheque->where('pump_operator_payments.pump_operator_id', request()->pump_operator_id);

    //             $daily_credit_sales->where('daily_vouchers.operator_id', request()->pump_operator_id);

    //         }

    //         if (! empty(request()->start_date) && ! empty(request()->end_date)) {

    //             $daily_cash->whereDate('daily_collections.created_at', '>=', request()->start_date)->whereDate('daily_collections.created_at', '<=', request()->end_date);

    //             $daily_cards->whereDate('daily_cards.date', '>=', request()->start_date)->whereDate('daily_cards.date', '<=', request()->end_date);

    //             $daily_credit_sales->whereDate('daily_vouchers.transaction_date', '>=', request()->start_date)->whereDate('daily_vouchers.transaction_date', '<=', request()->end_date);

    //             $shortage_excess->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date)->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);

    //             $other->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date)->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);

    //             $cheque->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date)->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);

    //         }

    //         switch (request()->daily_collection_type) {

    //             case 'daily_cash':

    //                 $query = $daily_cash->orderBy('date', 'DESC');

    //                 break;

    //             case 'daily_voucher';

    //                 $query = $daily_credit_sales->orderBy('date', 'DESC');

    //                 break;

    //             case 'daily_card':

    //                 $query = $daily_cards->orderBy('date', 'DESC');

    //                 break;

    //             case 'shortage_excess';

    //                 $query = $shortage_excess->orderBy('date', 'DESC');

    //                 break;

    //             case 'other';

    //                 $query = $other->orderBy('date', 'DESC');

    //                 break;

    //             case 'cheque';

    //                 $query = $cheque->orderBy('date', 'DESC');

    //                 break;

    //             default:

    //                 $query = $daily_cash->unionAll($daily_cards)->unionAll($shortage_excess)->unionAll($daily_credit_sales)->unionAll($other)->unionAll($cheque)->orderBy('date', 'DESC');

    //         }

    //         $fuel_tanks = Datatables::of($query)

    //             ->editColumn('total_amount', '{{@num_format($total_amount)}}')

    //             ->editColumn('type', function ($row) {

    //                 return ucfirst(str_replace('_', ' ', $row->type));

    //             })

    //             ->editColumn(

    //                 'date',

    //                 '{{@format_date($date)}}'

    //             )

    //             ->removeColumn('id');

    //         return $fuel_tanks->rawColumns(['action', 'total_collection'])

    //             ->make(true);

    //     }

    // }

    public function indexShortageExcess()
    {

        $business_id = request()->session()->get('user.business_id');
        $is_pumper_dashboard_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {

            abort(403, 'Unauthorized Access');

        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            if (request()->ajax()) {

                $query = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')

                    ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')

                    ->leftjoin('users', 'pump_operator_payments.created_by', 'users.id')

                    ->leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

                    ->where('pump_operator_payments.business_id', $business_id)

                    ->whereIn('payment_type', ['shortage', 'excess'])

                    ->select([

                        'pump_operator_payments.*',

                        'business_locations.name as location_name',

                        'pump_operators.name as pump_operator_name',

                        'settlements.settlement_no as settlement_noo',

                        'settlements.transaction_date as settlement_date',

                        'settlements.status as settlement_status',

                        'users.username as user',

                    ]);

                if (! empty(request()->location_id)) {

                    $query->where('pump_operators.location_id', request()->location_id);

                }

                if (! empty(request()->settlement_id)) {

                    $query->where('settlements.id', request()->settlement_id);

                }

                if (! empty(request()->status)) {

                    if (request()->status == 'completed') {

                        $query->whereNotNull('settlements.settlement_no')->where('settlements.status', 0);

                    }

                    if (request()->status == 'pending') {

                        $query->where(function ($q) {

                            $q->whereNull('settlements.settlement_no')

                                ->orWhere('settlements.status', 1);

                        });

                    }

                }

                if (! empty(request()->pump_operator)) {

                    $query->where('pump_operator_payments.pump_operator_id', request()->pump_operator);

                }

                if (! empty(request()->settlement_no)) {

                    $query->where('pump_operator_payments.settlement_no', request()->settlement_no);

                }

                if (! empty(request()->start_date) && ! empty(request()->end_date)) {

                    $query->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date);

                    $query->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);

                }

                $query->orderBy('pump_operator_payments.date_and_time', 'desc');

                try {
                    $fuel_tanks = Datatables::of($query)

                    ->addColumn(
                        'action',
                        function($row) use ($is_pumper_dashboard_enabled) {
                            $buttons = '';
                            
                            if (!$is_pumper_dashboard_enabled && empty($row->settlement_no) && empty(auth()->user()->pump_operator_id)) {
                                if (auth()->user()->can('daily_shortage.edit')) {
                                    $buttons .= ' &nbsp; <button data-href="' . action('\\Modules\\PetroGeneral\\Http\\Controllers\\DailyCollectionController@editShortage', [$row->id]) . '" data-container=".pump_modal" class="btn btn-success btn-xs btn-modal edit_reference_button"><i class="fa fa-pencil" aria-hidden="true"></i> ' . __('lang_v1.edit') . '</button> &nbsp; ';
                                }
                            }

                            if (!$is_pumper_dashboard_enabled && empty($row->is_used) && empty(auth()->user()->pump_operator_id)) {
                                if (auth()->user()->can('daily_collection.delete')) {
                                    $buttons .= '<a class="btn btn-danger btn-xs delete_daily_collection" href="' . action('\\Modules\\PetroGeneral\\Http\\Controllers\\DailyCollectionController@destroyShortageExcess', [$row->id]) . '"><i class="fa fa-trash" aria-hidden="true"></i> ' . __('petrogeneral::lang.delete') . '</a>';
                                }
                            }
                            
                            return $buttons;
                        }
                    )

                /**

                 * @ChangedBy Afes

                 * @Date 25-05-2021

                 * @Task 12700

                 */

                    ->addColumn('shortage_amount', function ($row) {
                        return $row->payment_type == 'shortage'
                            ? $this->productUtil->num_f((float) ($row->payment_amount ?? 0))
                            : '';
                    })

                    ->addColumn('excess_amount', function ($row) {
                        return $row->payment_type == 'excess'
                            ? $this->productUtil->num_f((float) ($row->payment_amount ?? 0))
                            : '';
                    })

                    ->editColumn(

                        'date_and_time',

                        '{{@format_date($date_and_time)}}'

                    )

                    ->addColumn('total_collection', function ($id) {

                        $total = DB::table('pump_operator_payments')

                            ->where('pump_operator_id', $id->pump_operator_id)

                            ->where('id', '<=', $id->id)

                            ->whereNull('settlement_no')

                            ->whereIn('payment_type', ['shortage', 'excess'])

                            ->sum('payment_amount') ?? 0;

                        return $this->productUtil->num_f($total);

                    })

                    ->editColumn(

                        'settlement_date',

                        '{{!empty($settlement_date) ? @format_date($settlement_date) : ""}}'

                    )

                    ->addColumn('status', function ($row) {

                        if (empty($row->settlement_noo) || $row->settlement_status == 1) {

                            return 'Pending';

                        } else {

                            return 'Completed';

                        }

                    })

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['action'])

                    ->make(true);
                } catch (\Exception $e) {
                    Log::error('Shortage/Excess DataTables Error: ' . $e->getMessage());
                    
                    return response()->json([
                        'error' => 'An error occurred while loading data: ' . $e->getMessage()
                    ], 500);
                }

            }

        }

    }

    public function indexCheque()
    {

        $business_id = request()->session()->get('user.business_id');
        $is_pumper_dashboard_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {

            abort(403, 'Unauthorized Access');

        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            if (request()->ajax()) {

                try {

                $query = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')

                    ->leftjoin('daily_cheque_payments', 'daily_cheque_payments.linked_payment_id', 'pump_operator_payments.id')

                    ->leftjoin('contacts', 'contacts.id', 'daily_cheque_payments.customer_id')

                    ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')

                    ->leftjoin('users', 'pump_operator_payments.created_by', 'users.id')

                    ->leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

                    ->where('pump_operator_payments.business_id', $business_id)

                    ->whereIn('pump_operator_payments.payment_type', ['cheque'])

                    ->select([

                        'pump_operator_payments.*',

                        'business_locations.name as location_name',

                        'pump_operators.name as pump_operator_name',

                        'settlements.settlement_no as settlement_noo',

                        'settlements.status as settlement_status',

                        'settlements.transaction_date as settlement_date',

                        'users.username as user',

                        'contacts.name as customer',

                        'daily_cheque_payments.cheque_date',

                        'daily_cheque_payments.bank_name',

                        'daily_cheque_payments.cheque_number',

                    ]);

                if (! empty(request()->location_id)) {

                    $query->where('pump_operators.location_id', request()->location_id);

                }

                if (! empty(request()->status)) {

                    if (request()->status == 'completed') {

                        $query->whereNotNull('settlements.settlement_no')->where('settlements.status', 0);

                    }

                    if (request()->status == 'pending') {

                        $query->where(function ($q) {

                            $q->whereNull('settlements.settlement_no')

                                ->orWhere('settlements.status', 1);

                        });

                    }

                }

                if (! empty(request()->settlement_id)) {

                    $query->where('settlements.id', request()->settlement_id);

                }

                if (! empty(request()->pump_operator)) {

                    $query->where('pump_operator_payments.pump_operator_id', request()->pump_operator);

                }

                if (! empty(request()->settlement_no)) {

                    $query->where('pump_operator_payments.settlement_no', request()->settlement_no);

                }

                if (! empty(request()->start_date) && ! empty(request()->end_date)) {

                    $query->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date);

                    $query->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);

                }

                $query->orderBy('pump_operator_payments.date_and_time', 'desc');

                $fuel_tanks = Datatables::of($query)

                    ->addColumn(
                        'action',
                        function($row) use ($is_pumper_dashboard_enabled) {
                            $buttons = '';
                            
                            if (!$is_pumper_dashboard_enabled && empty($row->is_used) && empty(auth()->user()->pump_operator_id)) {
                                if (auth()->user()->can('daily_collection.delete')) {
                                    $buttons .= '<a class="btn btn-danger btn-xs delete_daily_collection" href="' . action('\\Modules\\PetroGeneral\\Http\\Controllers\\DailyCollectionController@destroyCheque', [$row->id]) . '"><i class="fa fa-trash" aria-hidden="true"></i> ' . __('petrogeneral::lang.delete') . '</a>';
                                }
                            }
                            
                            return $buttons;
                        }
                    )

                /**

                 * @ChangedBy Afes

                 * @Date 25-05-2021

                 * @Task 12700

                 */

                    ->addColumn('payment_amount', function ($row) {
                        return $this->productUtil->num_f((float) ($row->payment_amount ?? 0));
                    })

                    ->editColumn(

                        'date_and_time',

                        '{{@format_datetime($date_and_time)}}'

                    )

                    ->editColumn(

                        'cheque_date',

                        '{{!empty($cheque_date) ? @format_date($cheque_date) : ""}}'

                    )

                // ->addColumn('total_collection', function ($id) {

                //     $total = DB::table('pump_operator_payments')

                //         ->where('pump_operator_id', $id->pump_operator_id)

                //         ->where('id', '<=', $id->id)

                //         ->whereNull('settlement_no')

                //         ->whereIn('payment_type', ['cheque'])

                //         ->sum('payment_amount') ?? 0;

                //     return $this->productUtil->num_f($total);

                // })

                    ->addColumn('total_collection', function ($row) {
                        // If the cheque has been settled, sum all records under that settlement
                        if (! empty($row->settlement_no)) {
                            $total = DB::table('pump_operator_payments')
                                ->where('settlement_no', $row->settlement_no)
                                ->whereIn('payment_type', ['cheque'])
                                ->sum('payment_amount') ?? 0;
                        } else {
                            // Otherwise, sum all unsettled records for that operator up to this point
                            $total = DB::table('pump_operator_payments')
                                ->where('pump_operator_id', $row->pump_operator_id)
                                ->where('id', '<=', $row->id)
                                ->whereIn('payment_type', ['cheque'])
                                ->whereNull('settlement_no')
                                ->sum('payment_amount') ?? 0;
                        }

                        return $this->productUtil->num_f($total);
                    })

                    ->editColumn(

                        'settlement_date',

                        '{{!empty($settlement_date) ? @format_date($settlement_date) : ""}}'

                    )

                    ->addColumn('status', function ($row) {

                        if (empty($row->settlement_noo) || $row->settlement_status == 1) {

                            return 'Pending';

                        } else {

                            return 'Completed';

                        }

                    })

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['action'])

                    ->make(true);

                } catch (\Exception $e) {
                    Log::error('Daily cheques DataTables error: ' . $e->getMessage(), [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]);

                    return response()->json([
                        'draw' => (int) request()->input('draw'),
                        'recordsTotal' => 0,
                        'recordsFiltered' => 0,
                        'data' => [],
                    ]);
                }

            }

        }

    }

    public function indexOther()
    {

        $business_id = request()->session()->get('user.business_id');
        $is_pumper_dashboard_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {

            abort(403, 'Unauthorized Access');

        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            if (request()->ajax()) {

                $query = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')

                    ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')

                    ->leftjoin('users', 'pump_operator_payments.created_by', 'users.id')

                    ->leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

                    ->where('pump_operator_payments.business_id', $business_id)

                    ->whereIn('payment_type', ['other'])

                    ->select([

                        'pump_operator_payments.*',

                        'business_locations.name as location_name',

                        'pump_operators.name as pump_operator_name',

                        'settlements.settlement_no as settlement_noo',

                        'settlements.status as settlement_status',

                        'settlements.transaction_date as settlement_date',

                        'users.username as user',

                    ]);

                if (! empty(request()->location_id)) {

                    $query->where('pump_operators.location_id', request()->location_id);

                }

                if (! empty(request()->status)) {

                    if (request()->status == 'completed') {

                        $query->whereNotNull('settlements.settlement_no')->where('settlements.status', 0);

                    }

                    if (request()->status == 'pending') {

                        $query->where(function ($q) {

                            $q->whereNull('settlements.settlement_no')

                                ->orWhere('settlements.status', 1);

                        });

                    }

                }

                if (! empty(request()->settlement_id)) {

                    $query->where('settlements.id', request()->settlement_id);

                }

                if (! empty(request()->pump_operator)) {

                    $query->where('pump_operator_payments.pump_operator_id', request()->pump_operator);

                }

                if (! empty(request()->settlement_no)) {

                    $query->where('pump_operator_payments.settlement_no', request()->settlement_no);

                }

                if (! empty(request()->start_date) && ! empty(request()->end_date)) {

                    $query->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date);

                    $query->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);

                }

                $query->orderBy('pump_operator_payments.date_and_time', 'desc');

                $fuel_tanks = Datatables::of($query)

                    ->addColumn(
                        'action',
                        function($row) use ($is_pumper_dashboard_enabled) {
                            $buttons = '';
                            
                            if (!$is_pumper_dashboard_enabled && empty($row->is_used) && empty(auth()->user()->pump_operator_id)) {
                                if (auth()->user()->can('daily_collection.delete')) {
                                    $buttons .= '<a class="btn btn-danger btn-xs delete_daily_collection" href="' . action('\\Modules\\PetroGeneral\\Http\\Controllers\\DailyCollectionController@destroyOther', [$row->id]) . '"><i class="fa fa-trash" aria-hidden="true"></i> ' . __('petrogeneral::lang.delete') . '</a>';
                                }
                            }
                            
                            return $buttons;
                        }
                    )

                /**

                 * @ChangedBy Afes

                 * @Date 25-05-2021

                 * @Task 12700

                 */

                    ->addColumn('payment_amount', function ($row) {
                        return $this->productUtil->num_f((float) ($row->payment_amount ?? 0));
                    })

                    ->editColumn(

                        'date_and_time',

                        '{{@format_datetime($date_and_time)}}'

                    )

                    ->addColumn('total_collection', function ($id) {

                        $total = DB::table('pump_operator_payments')

                            ->where('pump_operator_id', $id->pump_operator_id)

                            ->where('id', '<=', $id->id)

                            ->whereNull('settlement_no')

                            ->whereIn('payment_type', ['other'])

                            ->sum('payment_amount') ?? 0;

                        return $this->productUtil->num_f($total);

                    })

                    ->editColumn(

                        'settlement_date',

                        '{{!empty($settlement_date) ? @format_date($settlement_date) : ""}}'

                    )

                    ->addColumn('status', function ($row) {

                        if (empty($row->settlement_noo) || $row->settlement_status == 1) {

                            return 'Pending';

                        } else {

                            return 'Completed';

                        }

                    })

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['action'])

                    ->make(true);

            }

        }

    }

    /**

     * Show the form for creating a new resource.

     * @return Response

     */

    public function create()
    {
        $business_id      = request()->session()->get('user.business_id');
        $locations        = BusinessLocation::forDropdown($business_id);
        $locationsArray   = is_object($locations) ? $locations->toArray() : (array) $locations;
        $default_location = current(array_keys($locationsArray));

        // Get the currently active daily shift
        $dailyShift = PetroDailyShift::where('business_id', $business_id)
            ->where('status', 1)                   // Active shift
            ->where('type', 'daily_collection') // STRICTLY daily_collection
            ->latest()
            ->first();

        $assigned_operators  = [];
        $dailyShiftOperators = collect();
        $shiftNumbers = collect();

        if ($dailyShift) {
            $assigned_ids = explode(',', $dailyShift->pump_operator_assigned);
            $assigned_ids = array_filter($assigned_ids);

            if (! empty($assigned_ids)) {
                $dailyShiftOperators = PumpOperator::whereIn('id', $assigned_ids)
                    ->pluck('name', 'id');

                $assigned_operators = $dailyShiftOperators->toArray();

                // FIX: Always use PetroDailyShift shift_no
                foreach ($assigned_ids as $operatorId) {
                    $shiftNumbers[$operatorId] = $dailyShift->shift_no;
                }
            }
        }
        Log::info('Daily Shift Operators: ', ['operators' => $dailyShiftOperators]);
        Log::info('Shift Numbers: ', ['shiftNumbers' => $shiftNumbers]);

        // If no active shift, get the most recent closed shift
        if (! $dailyShift) {
            $dailyShift = PetroDailyShift::where('business_id', $business_id)
                ->where('status', 0)
                ->latest()
                ->first();

            if ($dailyShift) {
                $assigned_ids = explode(',', $dailyShift->pump_operator_assigned);
                $assigned_ids = array_filter($assigned_ids);

                if (! empty($assigned_ids)) {
                    $dailyShiftOperators = PumpOperator::whereIn('id', $assigned_ids)
                        ->pluck('name', 'id');

                    $assigned_operators = $dailyShiftOperators->toArray();

                    // Get shift numbers for the assigned operators from closed shift
                    $shiftNumbers = PumpOperatorAssignment::where('business_id', $business_id)
                        ->whereIn('pump_operator_id', $assigned_ids)
                        ->pluck('shift_number', 'pump_operator_id');
                }
            }
        }

        $pump_operators = $dailyShiftOperators->toArray();

        $PumpOperatorPayment = PumpOperatorPayment::where('business_id', $business_id)
            ->whereNotNull('collection_form_no')
            ->orderBy('id', 'DESC')
            ->select('collection_form_no')
            ->first();

        if (! is_null($PumpOperatorPayment)) {
            $collection_form_no = (int) $PumpOperatorPayment->collection_form_no + 1;
        } else {
            $collection_form_no = 1;
        }

        // Remove the old shift_numbers query as it's not operator-specific
        // $shift_numbers = PumpOperatorAssignment::where('business_id', $business_id)
        //     ->pluck('shift_number', 'id');

        $DailyCollection = DailyCollection::where('business_id', $business_id)
            ->whereNotNull('collection_form_no')
            ->where('daily_collections.type', 'daily_collection')
            ->orderBy('id', 'DESC')
            ->select('collection_form_no')
            ->first();

        if (! is_null($DailyCollection)) {
            if ($DailyCollection->collection_form_no >= $collection_form_no) {
                $collection_form_no = (int) $DailyCollection->collection_form_no + 1;
            }
        }

        $operatorShiftsMap = $shiftNumbers->toArray(); // Map operator IDs to shift numbers

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'daily_shift_page')) {
            $all                = true;
            $all_pump_operators = PumpOperator::where('business_id', $business_id)
                ->pluck('name', 'id')
                ->toArray();
        } else {
            $all                = false;
            $all_pump_operators = PumpOperator::where('business_id', $business_id)
                ->where('status', 1)
                ->pluck('name', 'id')
                ->toArray();
        }

        $pumps = Pump::where('pumps.business_id', $business_id)
            ->select('pumps.*')
            ->orderBy('pumps.id')
            ->get();

        $assigned = [];
        foreach ($pumps as $pump) {
            $po_assign = PumpOperatorAssignment::leftjoin('pump_operators', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')
                ->where('pump_operator_assignments.business_id', $business_id)
                ->where('pump_operator_assignments.pump_id', $pump->id)
                ->where('pump_operator_assignments.status', 'open')
                ->select('pump_operator_assignments.pump_operator_id', 'pump_operators.name as pumper_name')
                ->first();

            if (! empty($po_assign)) {
                $pump->pumper_name      = $po_assign->pumper_name;
                $pump->pump_operator_id = $po_assign->pump_operator_id;
                $assigned[]             = ["name" => $po_assign->pumper_name, "id" => $po_assign->pump_operator_id];
            }
        }

        // Keep your existing pending logic but ensure it uses the correct operators
        $pending = DailyCollection::leftJoin('business_locations', 'daily_collections.location_id', '=', 'business_locations.id')
            ->leftJoin('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')
            ->leftJoin('users', 'daily_collections.created_by', '=', 'users.id')
            ->leftJoin('settlements', 'daily_collections.settlement_id', '=', 'settlements.id')
            ->leftJoin('pump_operator_assignments', 'pump_operator_assignments.settlement_id', '=', 'settlements.id')
            ->where('daily_collections.business_id', $business_id)
            ->where('daily_collections.type', 'daily_collection')
            ->whereNull('settlements.settlement_no')
            ->select([
                'daily_collections.id',
                'daily_collections.pump_operator_id',
                'pump_operator_assignments.shift_number',
            ])
            ->orderBy('daily_collections.created_at', 'desc')
            ->get();

        $pendingShiftNumbers = $pending
            ->map(function ($row) {
                if (! empty($row->shift_number)) {
                    return $row->shift_number;
                }

                $assigned = PumpOperatorAssignment::where('pump_operator_id', $row->pump_operator_id)
                    ->select('shift_number')
                    ->latest('id')
                    ->first();

                return optional($assigned)->shift_number;
            })
            ->filter(function ($shift) {
                return ! empty($shift);
            })
            ->values();

        return view('petrogeneral::daily_collection.create')->with(compact(
            'dailyShiftOperators',
            'operatorShiftsMap',
            'all',
            'all_pump_operators',
            'assigned',
            'shiftNumbers', // This now contains operator-specific shift numbers
            'assigned_operators',
            'locations',
            'pump_operators',
            'collection_form_no',
            'default_location',
            'pendingShiftNumbers' // Renamed for clarity
        ));
    }

    public function getByOperator(Request $request, $operator)
    {

        $business_id = request()->session()->get('user.business_id');

        if (! $business_id) {

            return response()->json(['error' => 'business_id is required'], 400);

        }

        // Get all open shifts that include the operator in the pending list

        $openShiftsOperator = PetroDailyShift::where('business_id', $business_id)

            ->where('status', 0)

            ->whereRaw("FIND_IN_SET(?, pump_operator_assigned)", [$operator])

            ->pluck('shift_no');

        if ($openShiftsOperator->isNotEmpty()) {

            return response()->json($openShiftsOperator->toArray());

        }

        $openShifts = PetroDailyShift::where('business_id', $business_id)->where('status', 1)->pluck('shift_no');

        if ($openShifts->isNotEmpty()) {

            return response()->json($openShifts->toArray());

        } else {

            return response()->json([]);

        }

    }

    private function generateShiftNumber()
    {

        $lastShift = PetroDailyShift::orderBy('id', 'desc')->first();

        return 'DSN-' . str_pad($lastShift ? (int) $lastShift->id + 1 : 1, 3, '0', STR_PAD_LEFT);

    }

    /**

     * Store a newly created resource in storage.

     * @param  Request $request

     * @return Response

     */

    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [

            'collection_form_no' => 'required',

            'pump_operator_id'   => 'required',

            'daily_shift_no'     => 'required',

            'balance_collection' => 'required',

            'current_amount'     => 'required',

            'location_id'        => 'required',

        ]);

        if ($validator->fails()) {

            $output = [

                'success' => 0,

                'msg'     => $validator->errors()->all()[0],

            ];

            return redirect()->back()->with('status', $output);

        }

        $business_id = request()->session()->get('business.id');

        $has_reviewed = $this->transactionUtil->hasReviewed($request->input('transaction_date'));

        if (! empty($has_reviewed)) {

            $output = [

                'success' => 0,

                'msg'     => __('lang_v1.review_first'),

            ];

            return redirect()->back()->with(['status' => $output]);

        }

        $reviewed = $this->transactionUtil->get_review($request->input('transaction_date'), $request->input('transaction_date'));

        if (! empty($reviewed)) {

            $output = [

                'success' => 0,

                'msg'     => "You can't add a collection for an already reviewed date",

            ];

            return redirect()->back()->with(['status' => $output]);

        }

        try {

            $data = [

                'business_id'        => $business_id,

//                'daily_shift' => $request->daily_shift_no,

                'shift_number'       => $request->daily_shift_no ?? '',

                'collection_form_no' => $request->collection_form_no,

                'pump_operator_id'   => $request->pump_operator_id,

                'location_id'        => $request->location_id,

                'balance_collection' => $request->balance_collection,

                'current_amount'     => $request->current_amount,

                'created_by'         => Auth::user()->id,

                'type'               => 'daily_collection',

            ];

            $DailyCollection = DailyCollection::create($data);

            // $customers = Contact::customersDropdown($business_id, false, true, 'customer');

            // $account_id = $this->transactionUtil->account_exist_return_id('Cash');

            // $expense_transaction_data = [

            //     'amount' => $DailyCollection->current_amount,

            //     'account_id' => $account_id,

            //     'contact_id' => array_key_first($customers->toArray()),

            //     'type' => 'debit',

            //     'sub_type' => null,

            //     'operation_date' => \Carbon::parse($DailyCollection->created_at)->format('Y-m-d'),

            //     'created_by' => $DailyCollection->created_by,

            //     'transaction_id' => null,

            //     'transaction_payment_id' => null,

            //     'note' => 'daily_collection'

            // ];

            // AccountTransaction::createAccountTransaction($expense_transaction_data);

            // $DailyCollection->added_to_account = $account_id;

            // $DailyCollection->update();

            $pump_operator = PumpOperator::where('id', $request->pump_operator_id)->first();

            $balance_collection = DailyCollection::where('business_id', $business_id)->where('pump_operator_id', $request->pump_operator_id)->sum('current_amount');

            $total_amount = DailyCollection::where('business_id', $business_id)->where('pump_operator_id', $request->pump_operator_id)->whereNull('settlement_id')->whereNull('added_to_account')->sum('current_amount');

            $settlement_collection = DailyCollection::where('business_id', $business_id)->where('pump_operator_id', $request->pump_operator_id)->sum('balance_collection');

            $cum_amount = $balance_collection - $settlement_collection;

            $sms_data = [

                'date'                      => $request->input('transaction_date'),

                'time'                      => date('H:i'),

                'pump_operator'             => $pump_operator->name,

                'amount'                    => $this->transactionUtil->num_f($request->current_amount),

                'pumper_cummulative_amount' => $this->transactionUtil->num_f($cum_amount),

                'total_amount'              => $this->transactionUtil->num_f($total_amount),

            ];

            $this->notificationUtil->sendPetroNotification('daily_collection', $sms_data);

// assign pump operator to shift

            if (! is_null($request->daily_shift_no)) {

                $shift_number = $request->daily_shift_no;

                $petro_shift = PetroShift::updateOrCreate([

                    'shift_date'       => date('Y-m-d', strtotime($request->transaction_date)),

                    'status'           => 0,

                    'pump_operator_id' => $request->pump_operator_id,

                ],

                    [

                        'business_id'      => $business_id,

                        'pump_operator_id' => $request->pump_operator_id,

                        'status'           => 0,

                        'shift_date'       => date('Y-m-d', strtotime($request->transaction_date)),

                    ]);

                $assignment = PumpOperatorAssignment::where('shift_number', $shift_number)->first();

                if ($assignment) {

                    $assignment->pump_operator_id = $request->pump_operator_id;

                    $assignment->save();

                }

                // $shift_number = PumpOperatorAssignment::max('shift_number');

                // foreach($request->pump as $one){

                //     $pump = Pump::findOrFail($one);

                //     $starting_meter = !empty($pump->pod_last_meter) ? ($pump->pod_last_meter >= $pump->last_meter_reading ?  ($pump->pod_last_meter) : ($pump->last_meter_reading)) :  ($pump->last_meter_reading);

                //     $input = array(

                //         'business_id' => $business_id,

                //         'pump_id' => $one,

                //         'pump_operator_id' => $request->pump_operator_id,

                //         'starting_meter' => $starting_meter,

                //         'date_and_time' => $request->transaction_date,

                //         'status' => 'open',

                //         'assigned_by' => auth()->user()->id,

                //         'shift_id' => $petro_shift->id,

                //         'shift_number' => $shift_number + 1

                //     );

                //     PumpOperatorAssignment::create($input);

                // }

            }

            // assign pump operator to shift ends

            $output = [

                'success' => 1,

                'msg'     => __('petrogeneral::lang.daily_collection_add_success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            dd($e);

            $output = [

                'success' => 0,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return redirect()->back()->with('status', $output);

    }

    /**

     * Show the specified resource.

     * @return Response

     */

    public function show()
    {

    }

    /**

     * Show the form for editing the specified resource.

     * @return Response

     */

    public function edit($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id');

            $locations = BusinessLocation::forDropdown($business_id);

            $data = DailyCollection::findOrFail($id);

            $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');

            $dailyShift = PetroDailyShift::where('business_id', $business_id)

                ->where('status', 1)

                ->latest()

                ->first();

            $assigned_operators = [];

            $all_pump_operators = PumpOperator::where('business_id', $business_id)

                ->where('status', 1)

                ->pluck('name', 'id')

                ->toArray();

            if ($dailyShift) {

                if ($dailyShift->status == 0) {

                    $pump_operators_shift = $all_pump_operators;

                } else {

                    if (!empty($dailyShift->pump_operator_assigned)) {
                        $assigned_ids = explode(',', $dailyShift->pump_operator_assigned);
                        $assigned_ids = array_filter(array_map('trim', $assigned_ids));
                        
                        if (!empty($assigned_ids)) {
                            $assigned_operators = PumpOperator::whereIn('id', $assigned_ids)
                                ->pluck('name', 'id')
                                ->toArray();
                        } else {
                            $assigned_operators = [];
                        }
                    } else {
                        $assigned_operators = [];
                    }

                }

            } else {

                $dailyShift = PetroDailyShift::where('business_id', $business_id)

                    ->where('status', 0)

                    ->latest()

                    ->first();

                if ($dailyShift && !empty($dailyShift->pump_operator_assigned)) {

                    $assigned_ids = explode(',', $dailyShift->pump_operator_assigned);
                    $assigned_ids = array_filter(array_map('trim', $assigned_ids));
                    
                    if (!empty($assigned_ids)) {
                        $assigned_operators = PumpOperator::whereIn('id', $assigned_ids)
                            ->pluck('name', 'id')
                            ->toArray();
                    } else {
                        $assigned_operators = [];
                    }

                } else {

                    $assigned_operators = [];

                }

            }

            $pump_operators_shift = $all_pump_operators;

            // $pump_assign

            $pumps = Pump::where('pumps.business_id', $business_id)

                ->select('pumps.*')

                ->orderBy('pumps.id')

                ->get();

            $assigned = [];

            foreach ($pumps as $pump) {

                $po_assign = PumpOperatorAssignment::leftjoin('pump_operators', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')
                    ->where('pump_operator_assignments.business_id', $business_id)->where('pump_operator_assignments.pump_id', $pump->id)
                    ->where('pump_operator_assignments.status', 'open')
                    ->select('pump_operator_assignments.pump_operator_id', 'pump_operators.name as pumper_name')
                    ->first();

                if (! empty($po_assign)) {

                    // dd($po_assign);

                    $pump->pumper_name = $po_assign->pumper_name;

                    $pump->pump_operator_id = $po_assign->pump_operator_id;

                    $assigned[] = ["name" => $po_assign->pumper_name, "id" => $po_assign->pump_operator_id];

                }

            }

            // 1. Build the core query exactly like the one in index()…

            $pending = DailyCollection::leftJoin('business_locations', 'daily_collections.location_id', '=', 'business_locations.id')

                ->leftJoin('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')

                ->leftJoin('users', 'daily_collections.created_by', '=', 'users.id')

                ->leftJoin('settlements', 'daily_collections.settlement_id', '=', 'settlements.id')

                ->leftJoin('pump_operator_assignments', 'pump_operator_assignments.settlement_id', '=', 'settlements.id')

                ->where('daily_collections.business_id', $business_id)

                ->where('daily_collections.type', 'daily_collection')

            // --- the only extra filter you really need ---

                ->whereNull('settlements.settlement_no')

            // ------------------------------------------------

                ->select([

                    'daily_collections.id',

                    'daily_collections.pump_operator_id',

                    // grab any shift number that already exists in the join

                    'pump_operator_assignments.shift_number',

                ])

                ->orderBy('daily_collections.created_at', 'desc')

                ->get();

            // dd($pending);

            // 2. Map each record to the "effective" shift number --------------------------

            $shiftNumbers = $pending

                ->map(function ($row) {

                    if (! empty($row->shift_number)) {

                        return $row->shift_number;

                    }

                    $assigned = PumpOperatorAssignment::where('pump_operator_id', $row->pump_operator_id)

                        ->select('shift_number')

                        ->latest('id')

                        ->first();

                    return optional($assigned)->shift_number;

                })

                ->filter(function ($shift) {

                    return ! empty($shift);

                })

                ->values()

                ->unique()

                ->mapWithKeys(function ($value) {

                    return [$value => $value]; // 👈 transforms to ['DSN-023' => 'DSN-023']

                });

            //   dd($shiftNumbers);

//dd($dailyShift->shift_no);

            return view('petrogeneral::daily_collection.edit')->with(compact('assigned', 'shiftNumbers', 'assigned_operators', 'locations', 'pump_operators', 'data', 'dailyShift'));

        } catch (\Exception $e) {
            \Log::error('Error in DailyCollectionController@edit: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    public function editShortage($id)
    {

        $business_id = request()->session()->get('user.business_id');

        $data = PumpOperatorPayment::findOrFail($id);

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');

        return view('petrogeneral::daily_collection.edit_shortage')->with(compact('pump_operators', 'data'));

    }

    /**

     * Update the specified resource in storage.

     * @param  Request $request

     * @return Response

     */

    public function update(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [

            'collection_form_no' => 'required',

            'pump_operator_id'   => 'required',

            'balance_collection' => 'required',

            'current_amount'     => 'required',

            'location_id'        => 'required',

        ]);

        if ($validator->fails()) {

            $output = [

                'succcess' => 0,

                'msg'      => $validator->errors()->all()[0],

            ];

            return redirect()->back()->with('status', $output);

        }

        $business_id = request()->session()->get('business.id');

        $has_reviewed = $this->transactionUtil->hasReviewed($request->input('transaction_date'));

        if (! empty($has_reviewed)) {

            $output = [

                'success' => 0,

                'msg'     => __('lang_v1.review_first'),

            ];

            return redirect()->back()->with(['status' => $output]);

        }

        $reviewed = $this->transactionUtil->get_review($request->input('transaction_date'), $request->input('transaction_date'));

        if (! empty($reviewed)) {

            $output = [

                'success' => 0,

                'msg'     => "You can't add a collection for an already reviewed date",

            ];

            return redirect()->back()->with(['status' => $output]);

        }

        try {

            $data = [

                'business_id'        => $business_id,

                'daily_shift'        => $request->daily_shift_number ?? '',

                'shift_no'           => $request->shift_number ?? '',

                'collection_form_no' => $request->collection_form_no,

                'pump_operator_id'   => $request->pump_operator_id,

                'location_id'        => $request->location_id,

                'balance_collection' => $request->balance_collection,

                'current_amount'     => $request->current_amount,

                'created_by'         => Auth::user()->id,

            ];

            DailyCollection::where('id', $id)->update($data);

            $output = [

                'success' => 1,

                'msg'     => __('petrogeneral::lang.daily_collection_add_success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => 0,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return redirect()->back()->with('status', $output);

    }

    public function updateShortage(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [

            'pump_operator_id' => 'required',

            'payment_amount'   => 'required',

        ]);

        if ($validator->fails()) {

            $output = [

                'succcess' => 0,

                'msg'      => $validator->errors()->all()[0],

            ];

            return redirect()->back()->with('status', $output);

        }

        $business_id = request()->session()->get('business.id');

        $has_reviewed = $this->transactionUtil->hasReviewed($request->input('transaction_date'));

        if (! empty($has_reviewed)) {

            $output = [

                'success' => 0,

                'msg'     => __('lang_v1.review_first'),

            ];

            return redirect()->back()->with(['status' => $output]);

        }

        $reviewed = $this->transactionUtil->get_review($request->input('transaction_date'), $request->input('transaction_date'));

        if (! empty($reviewed)) {

            $output = [

                'success' => 0,

                'msg'     => "You can't add a collection for an already reviewed date",

            ];

            return redirect()->back()->with(['status' => $output]);

        }

        try {

            $data = [

                'pump_operator_id' => $request->pump_operator_id,

                'payment_amount'   => $request->payment_amount,

            ];

            PumpOperatorPayment::where('id', $id)->update($data);

            $output = [

                'success' => 1,

                'msg'     => __('lang_v1.success'),

            ];

        } catch (\Exception $e) {

            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [

                'success' => 0,

                'msg'     => __('messages.something_went_wrong'),

            ];

        }

        return redirect()->back()->with('status', $output);

    }

    /**

     * Remove the specified resource from storage.

     * @return Response

     */

    public function destroy($id)
    {

        try {

            DailyCollection::where('id', $id)->delete();

            $output = [

                'success' => true,

                'msg'     => __('petrogeneral::lang.daily_collection_delete_success'),

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

    public function destroyShortageExcess($id)
    {

        try {

            PumpOperatorPayment::where('id', $id)->delete();

            $output = [

                'success' => true,

                'msg'     => __('lang_v1.success'),

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

     * Remove the specified resource from storage.

     * @return Response

     */

    public function print($pump_operator_id)
    {

        $daily_collection = DailyCollection::findOrFail($pump_operator_id);

        $pump_operator = PumpOperator::findOrFail($daily_collection->pump_operator_id);

        $business_details = Business::where('id', $pump_operator->business_id)->first();

        return view('petrogeneral::daily_collection.partials.print')->with(compact('pump_operator', 'business_details', 'daily_collection'));

    }

    /**

     * get Balance Collection for pump operator

     * @return Response

     */

    public function getBalanceCollection($pump_operator_id)
    {

        $business_id = request()->session()->get('business.id');

        $balance_collection = DB::table('daily_collections')

            ->where('pump_operator_id', $pump_operator_id)

            ->whereNull('settlement_id')

            ->whereNull('added_to_account')

            ->sum('current_amount') ?? 0;

        return ['balance_collection' => $balance_collection];

    }

    // In your Controller (e.g., CashController.php)

    // CashController.php

    // In your Controller (e.g., DailyCollectionController.php)

    public function showDailyCashStatus()
    {

        // Initialize empty array if no transactions exist

        $cashGivenAmounts = [];

        try {

            $cashGivenAmounts = DB::table('transactions')

                ->where('group', 'cash_given')

                ->orderBy('created_at', 'desc')

                ->get()

                ->map(function ($item) {

                    $value = json_decode($item->value, true);

                    return [

                        'user'     => Auth::user()->name ?? $value['user'] ?? 'System',

                        'amount'   => $value['amount'] ?? 0,

                        'datetime' => $value['datetime'] ?? $item->created_at,

                    ];

                })

                ->toArray();

        } catch (\Exception $e) {

            // Log error but continue with empty array

            \Log::error('Error fetching cash transactions: ' . $e->getMessage());

        }

        $business_id = request()->session()->get('user.business_id');
        $dailyCashShiftNumbers = PetroDailyShift::where('business_id', $business_id)
            ->where('status', 0)
            ->pluck('shift_no')
            ->toArray();

        $isShiftRequired = config('petrogeneral.require_shift_selection', false);

        return view('petrogeneral::daily_collection.partials.daily_cash_status', [

            'cashGivenAmounts'      => $cashGivenAmounts,

            'dailyCashShiftNumbers' => $dailyCashShiftNumbers,

            'isShiftRequired'       => $isShiftRequired,

        ]);

    }

    public function storeCashGiven(Request $request)
    {

        $validated = $request->validate([

            'amount' => 'required|numeric|min:0.01',

            'notes'  => 'nullable|string',

        ]);

        $transactionValue = [

            'user'     => auth()->user()->name,

            'amount'   => $validated['amount'],

            'datetime' => now()->toDateTimeString(),

            'notes'    => $validated['notes'] ?? null,

        ];

        DB::table('transactions')->insert([

            'language'   => app()->getLocale(),

            'group'      => 'cash_given',

            'key'        => 'transaction_' . time(),

            'value'      => json_encode($transactionValue),

            'created_at' => now(),

            'updated_at' => now(),

        ]);

        return response()->json([

            'success'     => true,

            'transaction' => $transactionValue,

        ]);

    }

}

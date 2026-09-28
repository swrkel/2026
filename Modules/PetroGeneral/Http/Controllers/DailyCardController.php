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
use Modules\PetroGeneral\Entities\DailyCard;
use Modules\PetroGeneral\Entities\PetroDailyShift;
use Modules\PetroGeneral\Entities\PetroShift;
use Modules\PetroGeneral\Entities\PumpOperator;
use Modules\PetroGeneral\Entities\PumpOperatorAssignment;
use Yajra\DataTables\Facades\DataTables;

class DailyCardController extends Controller
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
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)
    {
        $this->commonUtil      = $commonUtil;
        $this->productUtil     = $productUtil;
        $this->moduleUtil      = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil    = $businessUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        $type = request()->get('type', 'daily_collection');
        $is_pumper_dashboard_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $query = DailyCard::leftjoin('pump_operators', 'daily_cards.pump_operator_id', 'pump_operators.id')
                ->leftjoin('business_locations', 'business_locations.id', 'pump_operators.location_id')
                ->leftjoin('contacts', 'daily_cards.customer_id', 'contacts.id')
                ->leftJoin('settlements', function ($join) {
                    $join->on(DB::raw('BINARY settlements.settlement_no'), '=', DB::raw('BINARY daily_cards.settlement_no'))
                         ->orWhereColumn('settlements.id', 'daily_cards.settlement_no');
                })
                ->leftjoin('accounts', 'daily_cards.card_type', 'accounts.id')
                ->leftJoin('pump_operator_assignments', function ($join) {
                    $join->on('pump_operator_assignments.pump_operator_id', '=', 'daily_cards.pump_operator_id')
                        ->whereRaw('pump_operator_assignments.date_and_time <= daily_cards.created_at')
                        ->whereRaw('(pump_operator_assignments.close_date_and_time >= daily_cards.created_at OR pump_operator_assignments.close_date_and_time IS NULL)');
                })
                ->where('daily_cards.business_id', $business_id)
                ->whereNotNull('daily_cards.slip_no')
                ->select([
                    'daily_cards.*',
                    'accounts.name as type_name',
                    'pump_operators.name as pump_operator_name',
                    'contacts.name as customer_name',
                    'business_locations.name as location_name',
                    'settlements.settlement_no as settlement_nos',
                    'pump_operator_assignments.shift_number as shift_number',
                    DB::raw('settlements.status as settlement_status'),

                ])
                // Group by unique id so bulk entries appear individually
                ->groupBy('daily_cards.id');
            $query->orderBy('daily_cards.date', 'desc');

            $fuel_tanks = Datatables::of($query)
                ->addColumn('action', function ($row) use ($type, $is_pumper_dashboard_enabled) {
                    $html = '';

                    if (!$is_pumper_dashboard_enabled && empty($row->settlement_no)) {
                        if (auth()->user()->can('daily_card.edit') && empty(auth()->user()->pump_operator_id)) {
                            $html .= '&nbsp;
                <button
                    data-href="' . action("\Modules\PetroGeneral\Http\Controllers\DailyCardController@edit", [$row->id]) . '?type=' . $type . '"
                    data-container=".pump_modal"
                    class="btn btn-success btn-xs btn-modal edit_reference_button">
                    <i class="fa fa-pencil" aria-hidden="true"></i> ' . __("lang_v1.edit") . '
                </button>
                &nbsp;';
                        }
                    }

                    if (!$is_pumper_dashboard_enabled && $row->used_status == 0 && empty(auth()->user()->pump_operator_id)) {
                        $html .= '<a class="btn btn-danger btn-xs delete_daily_card"
            data-href="' . action("\Modules\PetroGeneral\Http\Controllers\DailyCardController@destroy", [$row->id]) . '">
            <i class="fa fa-trash" aria-hidden="true"></i> ' . __("petrogeneral::lang.delete") . '
        </a>';
                    }

                    return $html;
                })

                ->addColumn('total_collection', function ($row) {
                    $total = DB::table('daily_cards')
                        ->where('pump_operator_id', $row->pump_operator_id)
                        ->where('id', '<=', $row->id)
                    // ->whereNull('added_to_account')
                        ->sum('amount');

                    return $this->productUtil->num_f($total ?? 0);
                })
                ->editColumn('amount', '{{@num_format($amount)}}')
                ->addColumn('status', function ($row) {
                    // Show Completed if settlement_no exists (settlement SW has been saved)
                    // regardless of settlement status, as per requirement
                    if (!empty($row->settlement_no)) {
                        return 'Completed';
                    }
                    return 'Pending';
                })
                ->editColumn('shift_number', '{{ $shift_number }}')
                ->editColumn('settlement_no', function ($row) {
                    // Use settlement_nos from join if available, otherwise use daily_cards.settlement_no
                    return $row->settlement_nos ?? ($row->settlement_no ?? '-');
                })

                ->filterColumn('shift_number', function ($query, $keyword) {
                    $query->leftJoin('pump_operator_assignments as poa_filter', function ($join) {
                        $join->on('poa_filter.pump_operator_id', '=', 'daily_cards.pump_operator_id')
                            ->whereRaw('poa_filter.date_and_time <= daily_cards.created_at')
                            ->whereRaw('(poa_filter.close_date_and_time >= daily_cards.created_at OR poa_filter.close_date_and_time IS NULL)');
                    });
                    $query->orWhere('poa_filter.shift_number', 'like', "%{$keyword}%");
                })
                ->editColumn('date', '{{@format_date($date)}}');

            return $fuel_tanks->rawColumns(['action'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create(Request $request)
    {
        $business_id      = request()->session()->get('user.business_id');
        $locations        = BusinessLocation::forDropdown($business_id);
        $default_location = current(array_keys($locations->toArray()));

        $open_shifts  = PetroShift::where('business_id', $business_id)->where('status', '0')->pluck('pump_operator_id')->toArray();
        $closedShifts = PetroDailyShift::where('business_id', $business_id)
            ->where('status', 0)
            ->pluck('pump_operator_assigned');
//dd($closedShifts );
        $closedOperatorIds = $closedShifts
            ->flatMap(fn($item) => array_filter(explode(',', $item)))
            ->unique()
            ->values();

        // Get operators with open shifts
        $openShifts = PetroDailyShift::where('business_id', $business_id)
            ->where('status', 1)
            ->pluck('pump_operator_assigned');

        $openOperatorIds = $openShifts
            ->flatMap(fn($item) => array_filter(explode(',', $item)))
            ->unique()
            ->values();

        // Exclude operators with open shifts
        $finalOperatorIds = $closedOperatorIds->diff($openOperatorIds);

        $dailyShiftOperators = PumpOperator::whereIn('id', $closedOperatorIds)->pluck('name', 'id');
        // $pump_operators = PumpOperator::where('business_id', $business_id)->whereNotIn('id',$open_shifts)->pluck('name', 'id');

        $pump_operators = PumpOperator::where('business_id', $business_id)
            ->where('active', 1)
            ->pluck('name', 'id');

        $collection_form_no = (int) DailyCard::where('business_id', $business_id)->max(DB::raw('CAST(collection_no AS UNSIGNED)')) + 1;

        $customers  = Contact::customersDropdown($business_id, false, true, 'customer');
        $card_types = [];
        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (! empty($card_group)) {
            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        if ($request->type == 'daily_collection_sw') {
            // IS1581: use the current Daily Collection SW shift and show the shift number
            // as the select value.  The old code used shift id as value, so the form did
            // not display/load the expected Daily Shift No for the operator.
            $dailyShift = PetroDailyShift::where('business_id', $business_id)
                ->where('status', 1)
                ->where('type', 'daily_collection_sw')
                ->orderBy('updated_at', 'desc')
                ->first();

            if (!$dailyShift) {
                $dailyShift = PetroDailyShift::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where('type', 'daily_collection_sw')
                    ->orderBy('updated_at', 'desc')
                    ->first();
            }

            $daily_shift_no = [];
            if ($dailyShift && !empty($dailyShift->shift_no)) {
                $daily_shift_no = [$dailyShift->shift_no => $dailyShift->shift_no];
            }

            if ($pump_operators->isEmpty()) {
                $pump_operators = PumpOperator::where('business_id', $business_id)
                    ->where('active', 1)
                    ->pluck('name', 'id');
            }

            return view('dailycollectionsw::partials.create_daily_cards')->with(compact('dailyShiftOperators', 'card_types', 'customers', 'locations', 'pump_operators', 'collection_form_no', 'default_location', 'daily_shift_no'));
        } else{
            // Get the CURRENT active daily shift for regular daily_collection
            $dailyShift = PetroDailyShift::where('business_id', $business_id)
                ->where('status', 1)
                ->where('type', 'daily_collection')
                ->orderBy('updated_at', 'desc')
                ->first();
            
            // Format as array with id => shift_no (for select dropdown)
            $daily_shift_no = [];
            if ($dailyShift) {
                $daily_shift_no = [$dailyShift->id => $dailyShift->shift_no];
            }
            
        return view('petrogeneral::daily_collection.partials.create_daily_cards')->with(compact('dailyShiftOperators', 'card_types', 'customers', 'locations', 'pump_operators', 'collection_form_no', 'default_location', 'daily_shift_no'));
        }
    }

    public function getDailyShiftsByOperator(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $operator_id = $request->get('operator_id');

        Log::info('getDailyShiftsByOperator called', [
            'business_id' => $business_id,
            'operator_id' => $operator_id
        ]);

        if (! $operator_id) {
            Log::warning('No operator_id provided');
            return response()->json([]);
        }

        // First, check if there are any active shifts at all
        $all_active_shifts = PetroDailyShift::where('business_id', $business_id)
            ->where('status', 1)
            ->where('type', 'daily_collection_sw')
            ->get();
        
        Log::info('All active daily_collection_sw shifts in database', [
            'count' => $all_active_shifts->count(),
            'shifts' => $all_active_shifts->map(function($shift) {
                return [
                    'id' => $shift->id,
                    'shift_no' => $shift->shift_no,
                    'pump_operator_assigned' => $shift->pump_operator_assigned,
                    'status' => $shift->status,
                    'type' => $shift->type
                ];
            })->toArray()
        ]);

        // Get settled shift numbers for this operator (to exclude them)
        $settled_shift_numbers = DB::table('pump_operator_assignments')
            ->join('settlements', 'pump_operator_assignments.settlement_id', '=', 'settlements.id')
            ->where('settlements.business_id', $business_id)
            ->where('settlements.status', 0) // 0 = finished/settled
            ->where('pump_operator_assignments.pump_operator_id', $operator_id)
            ->whereNotNull('pump_operator_assignments.shift_number')
            ->pluck('pump_operator_assignments.shift_number')
            ->toArray();

        Log::info('Settled shift numbers for operator', ['settled' => $settled_shift_numbers]);

        // Get shifts: Show both active (status = 1) and pending (status = 0)
        // This matches the flow: Open shift (status 1) -> Save shift (status 0)
        $dailyShift = PetroDailyShift::where('business_id', $business_id)
            ->where('type', 'daily_collection_sw')
            ->whereIn('status', [1, 0])  // Show both active AND pending shifts
            ->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END')  // Prioritize status 1
            ->orderBy('updated_at', 'desc')
            ->first();

        $daily_shifts = collect();
        
        if ($dailyShift) {
            // Check if this shift is settled
            $is_settled = in_array($dailyShift->shift_no, $settled_shift_numbers);
            
            if (!$is_settled) {
                // Return only the current active shift
                $daily_shifts = collect([$dailyShift->id => $dailyShift->shift_no]);
                Log::info('Current active shift found', [
                    'shift_id' => $dailyShift->id,
                    'shift_no' => $dailyShift->shift_no,
                    'status' => $dailyShift->status,
                    'is_settled' => false
                ]);
            } else {
                Log::info('Current active shift is settled, returning empty', [
                    'shift_id' => $dailyShift->id,
                    'shift_no' => $dailyShift->shift_no
                ]);
            }
        } else {
            Log::warning('No shift found for daily_collection_sw', [
                'business_id' => $business_id,
                'active_count' => PetroDailyShift::where('business_id', $business_id)
                    ->where('status', 1)
                    ->where('type', 'daily_collection_sw')
                    ->count(),
                'pending_count' => PetroDailyShift::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where('type', 'daily_collection_sw')
                    ->count()
            ]);
        }

        Log::info('Final Daily Shifts for Operator ' . $operator_id . ': ' . json_encode($daily_shifts));

        return response()->json($daily_shifts);
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        Log::info('Daily Card Store Request: ' . json_encode($request->all()));
        $validator = Validator::make($request->all(), [
            'collection_no'    => 'required',
            'pump_operator_id' => 'required',
        ]);

        $date = $this->productUtil->uf_date($request->date);

        if ($validator->fails()) {
            $output = [
                'success' => 0,
                'msg'     => $validator->errors()->all()[0],
            ];

            return redirect()->back()->with('status', $output);
        }

        $business_id = request()->session()->get('business.id') ?: request()->session()->get('user.business_id');

        $dynamic_customer_id = $request->dynamic_customer_id;
        $dynamic_card_type   = $request->dynamic_card_type;
        $dynamic_card_number = $request->dynamic_card_number;
        $dynamic_slip_no     = $request->dynamic_slip_no;
        $dynamic_card_note   = $request->dynamic_card_note;
        $dynamic_amount      = $request->dynamic_amount;

        // Check for duplicate slip numbers
        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'same_order_no_daily_collection')) {
            foreach ($dynamic_slip_no as $slip_no) {
                $slip_no        = trim(str_replace(' ', '', $slip_no));
                $existingRecord = DailyCard::where('slip_no', $slip_no)->exists();

                if (! empty($slip_no) && $existingRecord) {
                    $output = [
                        'success' => false,
                        'msg'     => __('messages.duplicate_slip'),
                    ];
                    return redirect()->back()->with(['status' => $output]);
                }
            }
        }

        // Check if date has been reviewed
        $has_reviewed = $this->transactionUtil->hasReviewed($request->input('date'));
        if (! empty($has_reviewed)) {
            $output = [
                'success' => 0,
                'msg'     => __('lang_v1.review_first'),
            ];
            return redirect()->back()->with(['status' => $output]);
        }

        $reviewed = $this->transactionUtil->get_review($request->input('date'), $request->input('date'));
        if (! empty($reviewed)) {
            $output = [
                'success' => 0,
                'msg'     => "You can't add a collection for an already reviewed date",
            ];
            return redirect()->back()->with(['status' => $output]);
        }

        DB::beginTransaction();

        try {
            // Get business location and account IDs once
            $business_location_id = BusinessLocation::where('business_id', $business_id)->value('id');

            $daily_shift_id = PetroDailyShift::where('business_id', $business_id)
                ->where(function ($q) use ($request) {
                    $q->where('id', $request->daily_shift_no)
                      ->orWhere('shift_no', $request->daily_shift_no);
                })
                ->value('id');

            foreach ($dynamic_slip_no as $key => $slip_no) {
                $cleaned_slip_no = trim(str_replace(' ', '', $slip_no));
                $amount          = $dynamic_amount[$key];

                $card_account_id = $dynamic_card_type[$key];

                // Optional fallback
                if (empty($card_account_id)) {
                    $card_account_id = Account::where('business_id', $business_id)
                        ->where(function ($query) {
                            $query->where('name', 'like', '%card%')
                                ->orWhere('name', 'like', '%bank%')
                                ->orWhere('name', 'like', '%credit card%');
                        })
                        ->where('is_closed', 0)
                        ->value('id');
                }

                if (empty($card_account_id)) {
                    $card_account_id = Account::where('business_id', $business_id)
                        ->where('name', 'Cash')
                        ->where('is_closed', 0)
                        ->value('id');
                }

                if (empty($card_account_id)) {
                    throw new \Exception('Suitable account for card payments not found.');
                }

                // Create DailyCard record
                $daily_card = DailyCard::create([
                    'location_id'      => $request->location_id,
                    'collection_no'    => $request->collection_no,
                    'business_id'      => $business_id,
                    'amount'           => $amount,
                    'card_type'        => $dynamic_card_type[$key],
                    'card_number'      => $dynamic_card_number[$key],
                    'customer_id'      => $dynamic_customer_id[$key] ?? null,
                    'note'             => $dynamic_card_note[$key],
                    'slip_no'          => $cleaned_slip_no,
                    'date'             => $date,
                    'pump_operator_id' => $request->pump_operator_id,
                    'shift_id'           => $daily_shift_id,
                ]);

                // Create the base Transaction
                $transaction = Transaction::create([
                    'business_id'      => $business_id,
                    'location_id'      => $business_location_id,
                    'type'             => 'daily_card_payment',
                    'sub_type'         => 'Card',
                    'status'           => 'final',
                    'ref_no'           => 'Daily card payment - Collection #' . $request->collection_no . ' - Slip: ' . $cleaned_slip_no,
                    'final_total'      => $amount,
                    'created_by'       => Auth::user()->id,
                    'transaction_date' => Carbon::now(),
                ]);

                // Create Transaction Payment (Card)
                $payment_ref_no = 'PAY-' . strtoupper(Str::random(8));

                $transaction_payment = TransactionPayment::create([
                    'transaction_id'          => $transaction->id,
                    'business_id'             => $business_id,
                    'amount'                  => $amount,
                    'method'                  => 'card',
                    'paid_on'                 => Carbon::now(),
                    'created_by'              => Auth::user()->id,
                    'payment_ref_no'          => $payment_ref_no,
                    'card_type'               => $dynamic_card_type[$key],
                    'card_number'             => $dynamic_card_number[$key],
                    'card_transaction_number' => $cleaned_slip_no,
                    'note'                    => $dynamic_card_note[$key] ?? 'Daily card payment - Collection #' . $request->collection_no,
                ]);

                // Create Account Transaction (linking with payment)
                $account_transaction_data = [
                    'amount'                 => $amount,
                    'type'                   => 'debit',
                    'sub_type'               => 'daily_card_payment',
                    'operation_date'         => Carbon::now(),
                    'created_by'             => Auth::user()->id,
                    'note'                   => $dynamic_card_note[$key] ?? 'Daily card payment - Collection #' . $request->collection_no . ' - Slip: ' . $cleaned_slip_no,
                    'transaction_id'         => $transaction->id,
                    'transaction_payment_id' => $transaction_payment->id,
                    'account_id'             => $card_account_id,
                ];

                $account_transaction = AccountTransaction::createAccountTransaction($account_transaction_data);

                // Link the created records
                $transaction->update(['account_transaction_id' => $account_transaction->id]);
                $daily_card->update([
                    'transaction_id'         => $transaction->id,
                    'transaction_payment_id' => $transaction_payment->id,
                ]);
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('petrogeneral::lang.daily_card_add_success'),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

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
    public function edit($id, Request $request)
    {
        $business_id      = request()->session()->get('user.business_id');
        $locations        = BusinessLocation::forDropdown($business_id);
        $default_location = current(array_keys($locations->toArray()));
        $data             = DailyCard::findOrFail($id);

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');

        $collection_form_no = (int) DailyCard::where('business_id', $business_id)->max(DB::raw('CAST(collection_no AS UNSIGNED)')) + 1;

        $customers  = Contact::customersDropdown($business_id, false, true, 'customer');
        $card_types = [];
        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (! empty($card_group)) {
            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        if ($request->get('type') == 'daily_collection_sw') {
            $daily_shift_no = PetroDailyShift::where('business_id', $business_id)
                ->where('status', 1)
                ->where('type', 'daily_collection_sw')
                ->pluck('shift_no', 'id');

            // Find the current daily shift ID for this card based on pump operator and date
            $selected_daily_shift_id = null;
            if (!empty($data->pump_operator_id)) {
                $card_date = \Carbon\Carbon::parse($data->date)->format('Y-m-d');
                
                // Find shifts assigned to this operator that were active on the card's date
                $current_shift = PetroDailyShift::where('business_id', $business_id)
                    ->where('type', 'daily_collection_sw')
                    ->whereRaw('FIND_IN_SET(?, pump_operator_assigned)', [$data->pump_operator_id])
                    ->whereDate('date', '<=', $card_date)
                    ->where(function($query) use ($card_date) {
                        $query->whereDate('updated_at', '>=', $card_date)
                              ->orWhereNull('updated_at');
                    })
                    ->orderBy('date', 'desc')
                    ->orderBy('id', 'desc')
                    ->first();

                if ($current_shift) {
                    $selected_daily_shift_id = $current_shift->id;
                }
            }

            return view('dailycollectionsw::partials.edit_daily_cards')
                ->with(compact('data', 'card_types', 'customers', 'locations', 'pump_operators', 'collection_form_no', 'default_location', 'daily_shift_no', 'selected_daily_shift_id'));
        }

        return view('petrogeneral::daily_collection.partials.edit_daily_cards')->with(compact('data', 'card_types', 'customers', 'locations', 'pump_operators', 'collection_form_no', 'default_location'));
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'collection_no'    => 'required',
            'pump_operator_id' => 'required',
            'amount'           => 'required',
        ]);

        $date = $this->productUtil->uf_date($request->date);

        if ($validator->fails()) {
            $output = [
                'success' => 0,
                'msg'     => $validator->errors()->all()[0],
            ];

            return redirect()->back()->with('status', $output);
        }
        $business_id = request()->session()->get('business.id') ?: request()->session()->get('user.business_id');

        $has_reviewed = $this->transactionUtil->hasReviewed($request->input('date'));

        if (! empty($has_reviewed)) {
            $output = [
                'success' => 0,
                'msg'     => __('lang_v1.review_first'),
            ];

            return redirect()->back()->with(['status' => $output]);
        }

        $reviewed = $this->transactionUtil->get_review($request->input('date'), $request->input('date'));

        if (! empty($reviewed)) {
            $output = [
                'success' => 0,
                'msg'     => "You can't add a collection for an already reviewed date",
            ];

            return redirect()->back()->with(['status' => $output]);
        }

        try {

            $slip_no = trim(str_replace(' ', '', $request->slip_no));

            $data = [
                'collection_no'    => $request->collection_no,
                'business_id'      => $business_id,
                'amount'           => $request->amount,
                'card_type'        => $request->card_type,
                'card_number'      => $request->card_number,
                'customer_id'      => $request->customer_id,
                'note'             => $request->note,
                'slip_no'          => $slip_no,
                'date'             => $date,
                'pump_operator_id' => $request->pump_operator_id,
            ];

            DailyCard::where('id', $id)->update($data);

            $output = [
                'success' => 1,
                'msg'     => __('petrogeneral::lang.daily_card_add_success'),
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
            DailyCard::where('id', $id)->delete();
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

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function print($pump_operator_id)
    {
        $daily_collection = DailyCard::findOrFail($pump_operator_id);
        $pump_operator    = PumpOperator::findOrFail($daily_collection->pump_operator_id);
        $business_details = Business::where('id', $pump_operator->business_id)->first();

        return view('petrogeneral::daily_collection.partials.print')->with(compact('pump_operator', 'business_details', 'daily_collection'));
    }

    /**
     * get Balance Collection for pump operator
     * @return Response
     */
    public function getBalanceCollection($pump_operator_id)
    {
        $business_id = request()->session()->get('business.id') ?: request()->session()->get('user.business_id');

        $balance_collection    = DailyCard::where('business_id', $business_id)->where('pump_operator_id', $pump_operator_id)->sum('current_amount');
        $settlement_collection = DailyCard::where('business_id', $business_id)->where('pump_operator_id', $pump_operator_id)->sum('balance_collection');

        return ['balance_collection' => $balance_collection - $settlement_collection];
    }
}

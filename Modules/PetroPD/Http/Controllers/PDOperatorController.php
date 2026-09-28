<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Product;
use App\Transaction;
use App\User;
use App\PumperLoginAttempt;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use Modules\PetroPD\Utils\PDTransactionUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Facades\Excel;
use Modules\PetroPD\Entities\FuelTank;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Entities\Settlement;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;
use Modules\Superadmin\Entities\Subscription;
use Yajra\DataTables\Facades\DataTables;

/*
|--------------------------------------------------------------------------
| PetroPD Entity Autoload Safety Bridge
|--------------------------------------------------------------------------
| Some production servers still have Composer/module autoload cache that does
| not immediately load the new PetroPD entity wrappers.  These aliases prevent
| the PD Operators page from crashing while keeping controller references under
| Modules\PetroPD\Entities.  Once the PetroPD entity wrappers are fully loaded,
| these aliases are ignored automatically.
*/
$__petropd_entity_aliases = [
    'FuelTank' => 'FuelTank',
    'PetroShift' => 'PetroShift',
    'Pump' => 'Pump',
    'PumpOperator' => 'PumpOperator',
    'PumpOperatorAssignment' => 'PumpOperatorAssignment',
    'PumperDayEntry' => 'PumperDayEntry',
    'Settlement' => 'Settlement',
];

foreach ($__petropd_entity_aliases as $__petropd_entity => $__petro_entity) {
    $__petropd_class = 'Modules\\PetroPD\\Entities\\' . $__petropd_entity;
    $__petro_class = 'Modules\\Petro\\Entities\\' . $__petro_entity;

    if (! class_exists($__petropd_class, false) && class_exists($__petro_class)) {
        class_alias($__petro_class, $__petropd_class);
    }
}

unset($__petropd_entity_aliases, $__petropd_entity, $__petro_entity, $__petropd_class, $__petro_class);

class PDOperatorController extends Controller
{
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $commonUtil;
    private $periodBalanceCache = [];

    public function __construct(
        Util $commonUtil,
        ProductUtil $productUtil,
        ModuleUtil $moduleUtil,
        PDTransactionUtil $transactionUtil
    ) {
        $this->commonUtil = $commonUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Resolve the tenant business id safely for PetroPD operator workflows.
     * Some modal/ajax requests may not carry session('business.id'), so fall
     * back to the authenticated user's business id.
     */
    private function resolveBusinessId(): int
    {
        $business_id = request()->session()->get('business.id')
            ?? request()->session()->get('user.business_id')
            ?? optional(Auth::user())->business_id;

        if (empty($business_id)) {
            abort(403, 'Business context not found. Please logout and login again.');
        }

        return (int) $business_id;
    }

public function index()
    {
        return $this->pdOperators();
    }

public function pdOperators()
    {

        $business_id = Auth::user()->business_id;
        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_pd_module')) {
            abort(403, 'Unauthorized Access');
        }

        if (! auth()->user()->can('petro_pd.view_operators')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {

            $business_id = Auth::user()->business_id;
            if (request()->ajax()) {
                $query = PumpOperator::withoutGlobalScope('active')
                ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                    ->leftjoin('settlements', 'pump_operators.id', 'settlements.pump_operator_id')
                    ->where('pump_operators.business_id', $business_id)
                    ->select([
                        'pump_operators.*',
                        'settlements.settlement_no as st_no',
                        'pump_operators.id as pump_operator_id',
                        'business_locations.name as location_name',
                    ])->groupBy('pump_operators.id');

                if (! empty(request()->location_id)) {
                    $query->where('pump_operators.location_id', request()->location_id);
                }
                if (! empty(request()->pump_operator)) {
                    $query->where('pump_operators.id', request()->pump_operator);
                }
                if (! empty(request()->settlement_no)) {
                    $query->where('settlements.settlement_no', request()->settlement_no);
                }
                if (! empty(request()->status)) {
                    if (request()->status == 'active') {
                        $query->where('pump_operators.active', 1);
                    } else {
                        $query->where('pump_operators.active', 0);
                    }
                }
                if (! empty(request()->type)) {
                }

                $start_date       = request()->start_date;
                $end_date         = request()->end_date;
                
                // FIX: Provide default date range if not provided (today)
                if (empty($start_date)) {
                    $start_date = now()->format('Y-m-d');
                }
                if (empty($end_date)) {
                    $end_date = now()->format('Y-m-d');
                }
                
                $business_details = Business::find($business_id);
                $period_balances  = $this->getPeriodBalancesForRange($business_id, $start_date, $end_date, [
                    'location_id' => request()->location_id,
                ]);
                
                // DEBUG: Log period balances to verify data
                Log::info('Pump Operator List - Date Range: ' . $start_date . ' to ' . $end_date);
                Log::info('Pump Operator List - Period Balances Count: ' . count($period_balances));
                Log::info('Pump Operator List - Period Balances: ', $period_balances);

                $fuel_tanks = Datatables::of($query)
->addColumn(
                        'action',
                        function ($row) {
                            $business_id           = session()->get('user.business_id');
                            $pay_excess_commission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pay_excess_commission');
                            $recover_shortage      = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'recover_shortage');
                            $pump_operator_ledger  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_ledger');

                            $html = '<div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                    data-toggle="dropdown" aria-expanded="false">' .
                                    __('messages.actions') .
                                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-left" role="menu">';


                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $row->id) . '">
                                        <i class="fa fa-eye" aria-hidden="true"></i> ' . __('messages.view') . '
                                    </a></li>';

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $row->id . '/edit') . '" class="js-petropd-modal">
                                        <i class="fa fa-pencil-square-o"></i> ' . __('messages.edit') . '
                                    </a></li>';

                            if (auth()->user()->can('pum_operator.active_inactive')) {
                                if (! $row->active) {
                                    $html .= '<li><a href="' . url('/petropd/pd-operators/' . $row->id . '/toggle-active') . '" class="toggle_active_button">
                                                <i class="fa fa-check"></i> ' . __('lang_v1.activate') . '
                                            </a></li>';
                                } else {
                                    $html .= '<li><a href="' . url('/petropd/pd-operators/' . $row->id . '/toggle-active') . '" class="toggle_active_button">
                                                <i class="fa fa-times"></i> ' . __('lang_v1.deactivate') . '
                                            </a></li>';
                                }
                            }

                            /*
                             * S774: expose only the due action that matches the same
                             * canonical Current Balance displayed in this row.
                             * Positive = shortage; negative = excess/commission.
                             */
                            $current_balance = (float) $this->transactionUtil->getPumpOperatorBalance($row->pump_operator_id);
                            $has_due_action = false;

                            if ($current_balance < -0.000001 && $pay_excess_commission) {
                                $html .= '<li class="divider"></li>';
                                $html .= '<li><a href="' . url('/petropd/excess-comission/create?pump_operator_id=' . $row->id) . '" class="js-petropd-modal">
                                            ' . __('petropd::lang.pay_excess_and_commission') . '
                                        </a></li>';
                                $has_due_action = true;
                            } elseif ($current_balance > 0.000001 && $recover_shortage) {
                                $html .= '<li class="divider"></li>';
                                $html .= '<li><a href="' . url('/petropd/recover-shortage/create?pump_operator_id=' . $row->id) . '" class="js-petropd-modal">
                                            ' . __('petropd::lang.recover_shortage') . '
                                        </a></li>';
                                $has_due_action = true;
                            }

                            if ($has_due_action) {
                                $html .= '<li class="divider"></li>';
                            }

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $row->id) . '?view=contact_info">
                                        <i class="fa fa-user" aria-hidden="true"></i> ' . __('contact.contact_info', ['contact' => __('contact.contact')]) . '
                                    </a></li>';

                            if ($pump_operator_ledger) {
                                $html .= '<li><a href="' . url('/petropd/pd-operators/' . $row->id) . '?view=ledger">
                                            <i class="fa fa-anchor" aria-hidden="true"></i> ' . __('lang_v1.ledger') . '
                                        </a></li>';
                            }

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $row->id . '/commission') . '">
                                        <i class="fa fa-anchor" aria-hidden="true"></i> ' . __('petro::lang.list_commission') . '
                                    </a></li>';

                            $html .= '<li><a href="' . url('/petropd/pd-operators/' . $row->id) . '?view=documents_and_notes">
                                        <i class="fa fa-paperclip" aria-hidden="true"></i> ' . __('lang_v1.documents_and_notes') . '
                                    </a></li>';

                            $html .= '</ul></div>';

                            return $html;
                        }
                    )
                   ->editColumn('name', function ($row) {
                        $html = $row->name;

                        // show default badge
                        if ($row->is_default == 1) {
                            $html .= " <span class='badge bg-danger'>Default</span>";
                        }

                        // show deactivated badge
                        if ($row->active == 0) {
                            $html .= " <span class='badge bg-secondary'>Deactivated</span>";
                        }

                        return $html;
                    })

                    ->addColumn(
                        'pump_no',
                        ''
                    )
                    ->addColumn(
                        'settlement_no',
                        ''
                    )
                    ->addColumn(
                        'sold_fuel_qty',
                        function ($row) use ($business_details, $start_date, $end_date, $business_id) {
                            /*
                             * S269 fix:
                             * PD Operators Sold Qty Fuel must come from the saved pumper dashboard meter entries,
                             * not from sales transactions. After settlement finalization, accounting/sales transaction
                             * rows can be duplicated or split, so using transactions here gives incorrect totals.
                             */
                            $sold_fuel_qty = PumperDayEntry::where('business_id', $business_id)
                                ->where('pump_operator_id', $row->pump_operator_id)
                                ->whereDate('date', '>=', $start_date)
                                ->whereDate('date', '<=', $end_date)
                                ->sum('sold_ltr');

                            $sold_fuel_qty = (float) $sold_fuel_qty;

                            return '<span class="sold_fuel_qty" data-orig-value="' . $sold_fuel_qty . '" data-currency_symbol="false">' .
                                $this->productUtil->num_f($sold_fuel_qty, false, $business_details, true) .
                                '</span>';
                        }
                    )
                    ->addColumn(
                        'sale_amount_fuel',
                        function ($row) use ($business_details, $start_date, $end_date, $business_id) {
                            /*
                             * S269 fix:
                             * PD Operators Sale Amount Fuel must come from pumper_day_entries.amount,
                             * because that is the saved meter sale value from Pumper Dashboard.
                             */
                            $sale_amount_fuel = PumperDayEntry::where('business_id', $business_id)
                                ->where('pump_operator_id', $row->pump_operator_id)
                                ->whereDate('date', '>=', $start_date)
                                ->whereDate('date', '<=', $end_date)
                                ->sum('amount');

                            $sale_amount_fuel = (float) $sale_amount_fuel;

                            return '<span class="display_currency sale_amount_fuel" data-orig-value="' . $sale_amount_fuel . '" data-currency_symbol="true">' .
                                $this->productUtil->num_f($sale_amount_fuel, false, $business_details, false) .
                                '</span>';
                        }
                    )
                    ->addColumn(
                        'current_balance',
                        function ($row) {
                            //$balance_due = $this->getLedgerDetailsForDateRange($row->pump_operator_id, $start_date,$end_date)['balance_due'];
                            $balance_due = $this->transactionUtil->getPumpOperatorBalance($row->pump_operator_id);
                            return '<span class="display_currency current_balance" data-orig-value="' . $balance_due . '" data-currency_symbol = true>' . $this->productUtil->num_f($balance_due, false) . '</span>';
                        }
                    )
                    ->addColumn('balance_for_period', function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [];

                        /*
                         * PD Operators - Balance For The Period
                         *
                         * Show only the actual shortage/excess position created for
                         * the selected period. Payments made through "Pay Excess"
                         * and recoveries made through "Recover Shortage" create
                         * separate ledger_show entries and must not change this
                         * period balance.
                         *
                         * total_debit_for_period  = original shortage entries
                         * total_credit_for_period = original excess entries
                         */
                        $period_shortage = (float) ($summary['total_debit_for_period'] ?? 0);
                        $period_excess   = (float) ($summary['total_credit_for_period'] ?? 0);
                        $balance_for_period = $period_shortage - $period_excess;

                        return '<span class="display_currency text-right balance_for_period" style="display:block" data-orig-value="' . $balance_for_period . '" data-currency_symbol = true>' . $this->productUtil->num_f($balance_for_period, false, $business_details, true) . '</span>';
                    })

                ->editColumn(
                    'excess_amount',
                    function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [];
                        $total_excess = $summary['total_credit_for_period'] ?? 0;
                        return  '<span class="display_currency excess_amount" data-orig-value="' .  $total_excess . '" data-currency_symbol = true>' . $this->productUtil->num_f($total_excess, false, $business_details, true) . '</span>';
                    }
                )
                ->editColumn(
                    'short_amount',
                    function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [];
                        $total_shortage = $summary['total_debit_for_period'] ?? 0;
                        return  '<span class="display_currency short_amount" data-orig-value="' . $total_shortage . '" data-currency_symbol = true>' . $this->productUtil->num_f($total_shortage, false, $business_details, true) . '</span>';
                    }
                )
                    ->editColumn(
                        'commission_type',
                        function ($row) {
                            return ucfirst($row->commission_type);
                        }
                    )
                    ->editColumn(
                        'commission_rate',
                        function ($row) use ($business_details) {
                            $commission_type = strtolower(trim((string) $row->commission_type));
                            $commission_value = (float) $row->commission_ap;

                            if ($commission_type === 'percentage') {
                                return '<span class="commission_ap" data-orig-value="' . $commission_value . '">' .
                                    $this->productUtil->num_f($commission_value, false, $business_details, false) . '%</span>';
                            }

                            if ($commission_type === 'fixed') {
                                return '<span class="display_currency commission_ap" data-orig-value="' . $commission_value . '" data-currency_symbol="true">' .
                                    $this->productUtil->num_f($commission_value, false, $business_details, false) . '</span>';
                            }

                            return '-';
                        }
                    )
                    ->addColumn(
                        'commission_amount',
                        function ($row) use ($business_details, $start_date, $end_date) {
                            $amount = $this->transactionUtil->getPumpOperatorCommission($row->pump_operator_id, $start_date, $end_date);
                            return '<span class="display_currency commission_amount" data-orig-value="' . $amount . '" data-currency_symbol = true>' . $this->productUtil->num_f($amount, false, $business_details, true) . '</span>';
                        }
                    )

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['name', 'action', 'sold_fuel_qty', 'sale_amount_fuel', 'excess_amount', 'short_amount', 'commission_rate', 'commission_amount', 'current_balance', 'balance_for_period'])
                    ->make(true);
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $pump_operators     = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');

        // dd($pump_operators);

        $pumps = Pump::where('pumps.business_id', $business_id)
            ->select('pumps.*')
            ->orderBy('pumps.id')
            ->get();

        foreach ($pumps as $pump) {

            $po_assign = PumpOperatorAssignment::leftjoin('pump_operators', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')
                ->where('pump_operator_assignments.business_id', $business_id)
                ->where('pump_operator_assignments.pump_id', $pump->id)
                ->where('pump_operator_assignments.status', 'open')
                ->select(
                    'pump_operator_assignments.id as assignment_id',
                    'pump_operator_assignments.pump_operator_id',
                    'pump_operator_assignments.shift_number',
                    'pump_operator_assignments.shift_id',
                    'pump_operator_assignments.is_confirmed',
                    'pump_operator_assignments.status as assignment_status',
                    'pump_operators.name as pumper_name'
                )
                ->first();
            if (! empty($po_assign)) {
                $pump->pumper_name       = $po_assign->pumper_name;
                $pump->pump_operator_id  = $po_assign->pump_operator_id;
                $pump->shift_number      = $po_assign->shift_number;
                $pump->shift_id          = $po_assign->shift_id;
                $pump->is_confirmed      = $po_assign->is_confirmed;
                $pump->assignment_id     = $po_assign->assignment_id;
                $pump->assignment_status = $po_assign->assignment_status;
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $default_location   = !is_array($business_locations) ? current(array_keys($business_locations->toArray())) : current(array_keys($business_locations));
        $payment_types      = $this->productUtil->payment_types($default_location);
        $tanks              = FuelTank::where('business_id', $business_id)->pluck('fuel_tank_number', 'id');
        $products           = Product::leftjoin('categories', 'products.category_id', 'categories.id')->where('products.business_id', $business_id)->where('categories.name', 'Fuel')->pluck('products.name', 'products.id');
        $settlement_nos     = [];

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')->where('petro_shifts.business_id', $business_id)->select('pump_operators.name', 'petro_shifts.*')->orderBy('id', 'DESC');

        $shifts = $shifts->get();

        $message = $this->transactionUtil->getGeneralMessage('general_message_pump_management_checkbox');

        $pumperLoginAttempts = PumperLoginAttempt::where('business_id', $business_id)
            ->where('status', "Blocked")
            ->get();
        $customers = Contact::customersDropdown($business_id, false, true);
    
        return view('petropd::pd_operators.index')
        ->with(compact(
            'business_locations',
            'pump_operators',
            'settlement_nos',
            'message',
            'payment_types',
            'pumps',
            'tanks',
            'products',
            'shifts',
            'customers',
            'pumperLoginAttempts',
            'default_location'
        ));;
    }






    public function create()
    {
        $business_id = $this->resolveBusinessId();
        $locations   = BusinessLocation::forDropdown($business_id);

        $commission_type_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'commission_type');
        $pump_operator_dashboard    = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        $generate_passcode = sprintf("%04d", rand(0, 9999));

        return view('petropd::pd_operators.create')->with(compact('locations', 'commission_type_permission', 'pump_operator_dashboard', 'generate_passcode'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'             => 'required',
            'address'          => 'required',
            'location_id'      => 'required',
            'email'            => 'required|unique:users',
            'cnic'             => 'required',
            'dob'              => 'required',
            'commission_type'  => 'required|in:none,fixed,percentage',
            'commission_ap'    => 'required_unless:commission_type,none|nullable|numeric|min:0',
            'mobile'           => 'required',
            'username'         => 'required|unique:users',
            'transaction_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            $output = [
                'success' => 0,
                'msg'     => $validator->errors()->all()[0],
            ];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($output, 422);
            }

            return redirect()->back()->with('status', $output);
        }

        $business_id = $this->resolveBusinessId();
        try {
            //Check if subscribed or not, then check for users quota
            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            } else if (! $this->moduleUtil->isQuotaAvailable('users', $business_id)) {
                return $this->moduleUtil->quotaExpiredResponse('users', $business_id, action('ManageUserController@index'));
            }

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
                    'msg'     => "You can't add a pump operator for an already reviewed date",
                ];

                return redirect()->back()->with(['status' => $output]);
            }

            if ($request->is_default == 1) {
                PumpOperator::where('business_id', $business_id)->update(['is_default' => 0]);
            }

            $data = [
                'business_id'      => $business_id,
                'name'             => $request->name,
                'address'          => $request->address,
                'location_id'      => $request->location_id,
                'cnic'             => $request->cnic,
                'dob'              => \Carbon::parse($request->dob)->format('Y-m-d'),
                'commission_type'  => $request->commission_type,
                'commission_ap'    => $request->commission_type === 'none' ? 0.00 : (float) $request->commission_ap,
                'mobile'           => $request->mobile,
                'landline'         => $request->landline,
                'status'           => 1,
                'is_default'       => $request->is_default,
                'can_fullscreen'   => $request->can_fullscreen,
                'transaction_date' => $request->transaction_date,
            ];

            if (! Schema::hasColumn('pump_operators', 'hide_in_direct_settlement_if_pending_shifts')) {
                $output = [
                    'success' => 0,
                    'msg'     => 'Pump operator pending-shift visibility setting cannot be saved because the database migration is pending.',
                ];

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json($output, 422);
                }

                return redirect()->back()->with('status', $output);
            }

            $data['hide_in_direct_settlement_if_pending_shifts'] = $request->boolean('hide_in_direct_settlement_if_pending_shifts');

            // IS2271: this is the controller actually used by /petropd/pd-operators.
            // The checkbox was already present in the Add/Edit views, but this
            // controller did not persist it (the legacy PumpOperatorController did).
            // Keep the flag on the shared pump_operators row so Petro Direct and
            // SW can reliably exclude PD-only operators from their settlement
            // operator dropdowns.
            if (! Schema::hasColumn('pump_operators', 'is_petro_pd_only')) {
                $output = [
                    'success' => 0,
                    'msg'     => 'Petro PD only setting cannot be saved because the database migration is pending.',
                ];

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json($output, 422);
                }

                return redirect()->back()->with('status', $output);
            }

            $data['is_petro_pd_only'] = $request->boolean('is_petro_pd_only');

            if (! empty($request->input('opening_balance'))) {
                if ($request->input('opening_balance_type') == 'shortage') {
                    $data['short_amount'] = $request->input('opening_balance');
                }

                if ($request->input('opening_balance_type') == 'excess') {
                    $data['excess_amount'] = $request->input('opening_balance');
                }
            }

            DB::beginTransaction();
            $pump_operator = PumpOperator::create($data);
            $this->createUser($request, $pump_operator);

            //Add opening balance
            if (! empty($request->input('opening_balance'))) {
                $this->transactionUtil->createOpeningBalanceTransactionForPumpOperator($business_id, $pump_operator->id, $request->input('opening_balance'), $request->input('opening_balance_type'), $request->location_id, $request->input('transaction_date'));
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('petro::lang.pump_operator_add_success'),
            ];
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg'     => $e->getMessage() ?: __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output, ! empty($output['success']) ? 200 : 422);
        }

        return redirect()->back()->with('status', $output);
    }


    public function createUser($request, $pump_operator)
    {
        $business_id        = $this->resolveBusinessId();
        $username           = trim((string) $request->username);
        $passcode           = trim((string) $request->password);
        $email              = trim((string) $request->email);

        if ($username === '') {
            $username = $this->generatePumpOperatorUsername($pump_operator, $business_id);
        }

        if ($passcode === '') {
            $passcode = sprintf('%04d', random_int(0, 9999));
        }

        if ($email === '' || User::where('email', $email)->exists()) {
            $email_base = 'pump-operator-'.$pump_operator->id;
            $email = $email_base.'@example.invalid';
            $counter = 1;

            while (User::where('email', $email)->exists()) {
                $email = $email_base.'-'.$counter.'@example.invalid';
                $counter++;
            }
        }

        $pump_operator_data = [
            'business_id'            => $business_id,
            'surname'                => '',
            'first_name'             => $request->name,
            'last_name'              => '',
            'email'                  => $email,
            'username'               => $username,
            'password'               => Hash::make($passcode),
            'contact_number'         => $request->mobile,
            'address'                => $request->address,
            'is_pump_operator'       => 1,
            'pump_operator_id'       => $pump_operator->id,
            'pump_operator_passcode' => $passcode,
        ];

        $user = User::create($pump_operator_data);
        $role = Role::where('name', 'Pump Operator#' . $business_id)->first();

        if (empty($role)) {
            $role = Role::create([
                'name'             => 'Pump Operator#' . $business_id,
                'business_id'      => $business_id,
                'is_service_staff' => 0,
            ]);
            $role->givePermissionTo('pump_operator.dashboard');
        }
        $user->assignRole($role->name);

        return true;
    }


    private function generatePumpOperatorUsername($pump_operator, $business_id): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', (string) $pump_operator->name));
        $base = $base !== '' ? $base : 'pumper';
        $base = $base.$business_id;
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base.$pump_operator->id.$counter;
            $counter++;
        }

        return $username;
    }



    public function importPumps()
    {
        $business_id        = $this->resolveBusinessId();
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('petropd::pd_operators.import_operators')->with(compact('business_locations'));
    }

    public function saveImport(Request $request)
    {
        $notAllowed = $this->productUtil->notAllowedInDemo();
        if (! empty($notAllowed)) {
            return $notAllowed;
        }
        $business_id = $this->resolveBusinessId();
        $location_id = $request->location_id;
        $type        = $request->commission_type;

        try {
            //Set maximum php execution time
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);

            if ($request->hasFile('pumps_csv')) {
                $file = $request->file('pumps_csv');

                $parsed_array = Excel::toArray([], $file);

                //Remove header row
                $imported_data = array_splice($parsed_array[0], 1);

                $formated_data = [];

                $is_valid  = true;
                $error_msg = '';

                $total_rows = count($imported_data);

                $row_no = 0;
                DB::beginTransaction();
                foreach ($imported_data as $key => $value) {

                    $pump_operator                    = [];
                    $pump_operator['business_id']     = $business_id;
                    $pump_operator['location_id']     = $location_id;
                    $pump_operator['commission_type'] = $type;

                    //Check if any column is missing
                    if (count($value) < 5) {
                        $is_valid  = false;
                        $error_msg = "Some of the columns are missing. Please, use latest CSV file template.";
                    }

                    $name = (trim($value[0]));
                    if ($name) {
                        $pump_operator['name'] = $name;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for pump operator name in row no. $row_no";
                    }

                    $address = (trim($value[1]));
                    if ($address) {
                        $pump_operator['address'] = $address;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for address in row no. $row_no";
                    }

                    $mobile = (trim($value[2]));
                    if ($mobile) {
                        $pump_operator['mobile'] = $mobile;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for mobile in row no. $row_no";
                    }

                    $landline = (trim($value[3]));
                    if ($landline) {
                        $pump_operator['landline'] = $landline;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for landline in row no. $row_no";
                    }

                    $dob = (trim($value[4]));
                    if ($dob) {
                        $pump_operator['dob'] = \Carbon::parse(strtotime($dob))->format('Y-m-d');
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for date of birth in row no. $row_no";
                    }

                    $cnic = (trim($value[5]));
                    if ($cnic) {
                        $pump_operator['cnic'] = $cnic;
                    } else {
                        $is_valid  = false;
                        $error_msg = "Invalid value for national identity number in row no. $row_no";
                    }

                    $ob               = (trim($value[6]));
                    $transaction_date = (trim(strtotime($value[7]))) ?? date('Y-m-d');
                    $ob_type          = strtolower(trim($value[8])) ?? 'excess';

                    $email = (trim($value[9]));

                    if (! $is_valid) {
                        throw new \Exception($error_msg);
                        break;
                    }

                    $pump_operator['status'] = 1;

                    $pump_op = PumpOperator::create($pump_operator);

                    if (! empty($email)) {
                        $pass = rand(1111, 9999);

                        $pump_operator_data = [
                            'business_id'            => $business_id,
                            'surname'                => '',
                            'first_name'             => $pump_operator['name'],
                            'last_name'              => '',
                            'email'                  => $email,
                            'username'               => $email,
                            'password'               => Hash::make($pass),
                            'contact_number'         => $mobile,
                            'address'                => $address,
                            'is_pump_operator'       => 1,
                            'pump_operator_id'       => $pump_op->id,
                            'pump_operator_passcode' => $pass,
                        ];

                        $user = User::create($pump_operator_data);
                        $role = Role::where('name', 'Pump Operator#' . $business_id)->first();

                        if (empty($role)) {
                            $role = Role::create([
                                'name'             => 'Pump Operator#' . $business_id,
                                'business_id'      => $business_id,
                                'is_service_staff' => 0,
                            ]);
                            $role->givePermissionTo('pump_operator.dashboard');
                        }
                        $user->assignRole($role->name);
                    }

                    if (! empty($ob)) {
                        $this->transactionUtil->createOpeningBalanceTransactionForPumpOperator(
                            $business_id,
                            $pump_op->id,
                            $ob,
                            $ob_type,
                            $request->location_id,
                            $transaction_date
                        );
                    }

                    $row_no++;
                }
                DB::commit();
            }

            $output = [
                'success' => 1,
                'msg'     => __('petro::lang.pump_operator_import_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg'     => $e->getMessage(),
            ];

            return redirect()->back()->with('notification', $output);
        }

        return redirect('/petropd/pd-operators')->with('status', $output);
    }



public function destroy($id)
    {
        $business_id = $this->resolveBusinessId();

        try {
            $pump_operator = PumpOperator::where('business_id', $business_id)->findOrFail($id);
            $pump_operator->active = 0;
            $pump_operator->save();

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.deleted_success'),
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }


public function show($id)
    {
        $business_id = Auth::user()->business_id;

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_pd_module')) {
            abort(403, 'Unauthorized Access');
        }

        $pump_operator = PumpOperator::where('business_id', $business_id)->findOrFail($id);
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $view_type = request()->get('view');
        if (is_null($view_type)) {
            $view_type = 'contact_info';
        }

        $pump_operator_ledger_permission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_ledger');

        return view('petropd::pd_operators.show')
            ->with(compact('pump_operator', 'pump_operators', 'business_locations', 'view_type', 'pump_operator_ledger_permission'));
    }

public function edit($id)
    {
        $business_id = $this->resolveBusinessId();

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_pd_module')) {
            abort(403, 'Unauthorized Access');
        }

        $locations = BusinessLocation::forDropdown($business_id);
        $pump_operator = PumpOperator::where('business_id', $business_id)->findOrFail($id);

        $transaction = Transaction::where([
            'type' => 'opening_balance',
            'pump_operator_id' => $pump_operator->id,
            'business_id' => $business_id,
        ])->first();

        $user = User::where('business_id', $business_id)
            ->where('pump_operator_id', $id)
            ->first();

        $pump_operator_dashboard = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');
        $fallback_username = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', (string) $pump_operator->name)) . $business_id;
        $generate_passcode = sprintf('%04d', rand(0, 9999));

        return view('petropd::pd_operators.edit')->with(compact(
            'locations',
            'pump_operator',
            'user',
            'pump_operator_dashboard',
            'transaction',
            'fallback_username',
            'generate_passcode'
        ));
    }

public function update($id, Request $request)
    {
        $business_id = $this->resolveBusinessId();

        $validator = Validator::make($request->all(), [
            'commission_type' => 'required|in:none,fixed,percentage',
            'commission_ap'   => 'required_unless:commission_type,none|nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            $output = [
                'success' => 0,
                'msg'     => $validator->errors()->all()[0],
            ];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($output, 422);
            }

            return redirect()->back()->with('status', $output);
        }

        try {
            $pump_operator = PumpOperator::where('business_id', $business_id)->findOrFail($id);

            if ($request->is_default == 1) {
                PumpOperator::where('business_id', $business_id)->update(['is_default' => 0]);
            }

            $data = [
                'business_id' => $business_id,
                'name' => $request->name,
                'address' => $request->address,
                'location_id' => $request->location_id,
                'commission_type' => $request->commission_type,
                'commission_ap' => $request->commission_type === 'none' ? 0.00 : (float) $request->commission_ap,
                'mobile' => $request->mobile,
                'landline' => $request->landline,
                'status' => 1,
                'is_default' => $request->is_default,
                'can_fullscreen' => $request->can_fullscreen,
            ];

            if (Schema::hasColumn('pump_operators', 'hide_in_direct_settlement_if_pending_shifts')) {
                $data['hide_in_direct_settlement_if_pending_shifts'] = $request->boolean('hide_in_direct_settlement_if_pending_shifts');
            }

            // IS2271: persist the PD-only checkbox on Edit as well. Do not
            // silently ignore it because that makes downstream settlement
            // dropdowns treat the operator as a normal shared operator.
            if (! Schema::hasColumn('pump_operators', 'is_petro_pd_only')) {
                $output = [
                    'success' => 0,
                    'msg'     => 'Petro PD only setting cannot be saved because the database migration is pending.',
                ];

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json($output, 422);
                }

                return redirect()->back()->with('status', $output);
            }

            $data['is_petro_pd_only'] = $request->boolean('is_petro_pd_only');

            DB::beginTransaction();

            $pump_operator->update($data);

            $user = User::where('pump_operator_id', $id)
                ->where('business_id', $business_id)
                ->first();

            $username = trim((string) $request->input('username'));
            $passcode = trim((string) $request->input('password'));
            $email = trim((string) $request->input('email'));

            if ($username === '') {
                $username = $this->generatePumpOperatorUsername($pump_operator, $business_id);
            }

            if ($passcode === '') {
                $passcode = ! empty($user) && ! empty($user->pump_operator_passcode)
                    ? (string) $user->pump_operator_passcode
                    : sprintf('%04d', random_int(0, 9999));
            }

            $username_exists = User::where('business_id', $business_id)
                ->where('username', $username)
                ->when(! empty($user), function ($query) use ($user) {
                    $query->where('id', '!=', $user->id);
                })
                ->exists();

            if ($username_exists) {
                throw new \Exception('This username is already used.');
            }

            $passcode_exists = User::where('business_id', $business_id)
                ->where('pump_operator_passcode', $passcode)
                ->when(! empty($user), function ($query) use ($user) {
                    $query->where('id', '!=', $user->id);
                })
                ->exists();

            if ($passcode_exists) {
                throw new \Exception('This passcode is already used.');
            }

            if ($user) {
                $user->first_name = $request->name;
                $user->email = $email;
                $user->username = $username;
                $user->contact_number = $request->mobile;
                $user->address = $request->address;
                $user->is_pump_operator = 1;
                $user->pump_operator_id = $pump_operator->id;
                $user->password = Hash::make($passcode);
                $user->pump_operator_passcode = $passcode;
                $user->save();
            } else {
                $request->merge([
                    'username' => $username,
                    'password' => $passcode,
                    'email' => $email,
                ]);

                $this->createUser($request, $pump_operator);
            }

            Log::warning('PetroPD PDOperatorController update reached and saved', [
                'business_id' => $business_id,
                'pump_operator_id' => $pump_operator->id,
                'user_id' => ! empty($user) ? $user->id : null,
                'username' => $username,
                'passcode_length' => strlen($passcode),
                'passcode_saved_matches_request' => ! empty($user) ? ((string) $user->pump_operator_passcode === (string) $passcode) : null,
            ]);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('petro::lang.pump_operator_update_success'),
            ];
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => $e->getMessage() ?: __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->back()->with('status', $output);
    }

public function toggleActivate($id)
    {
        $business_id = Auth::user()->business_id;
        $pump_operator = PumpOperator::where('business_id', $business_id)->findOrFail($id);
        $pump_operator->active = ! (bool) $pump_operator->active;
        $pump_operator->save();

        return [
            'success' => true,
            'msg' => $pump_operator->active ? __('lang_v1.activated_successfully') : __('lang_v1.deactivated_successfully'),
        ];
    }

public function listCommission($id)
    {
        return redirect(url('/petropd/pd-operators/' . $id . '?view=ledger')); // PetroPD separation: keep inside PetroPD
    }

public function checUsername(Request $request)
    {
        $username = trim((string) $request->username);

        $exists = User::where('username', $username)
            ->when(! empty($request->user_id), function ($query) use ($request) {
                $query->where('id', '!=', $request->user_id);
            })
            ->exists();

        if ($exists) {
            return ['success' => false, 'msg' => __('validation.unique', ['attribute' => 'username'])];
        }

        return ['success' => true];
    }

public function checPasscode(Request $request)
    {
        $passcode = trim((string) $request->passcode);

        $exists = User::where('pump_operator_passcode', $passcode)
            ->when(! empty($request->user_id), function ($query) use ($request) {
                $query->where('id', '!=', $request->user_id);
            })
            ->exists();

        if ($exists) {
            return ['success' => false, 'msg' => 'This passcode is already used.'];
        }

        return ['success' => true];
    }


public function update_passcode()
    {
        if (! Auth::user()->can('pump_operator.access_code')) {
            abort(403, 'Unauthorized Access');
        }

        $user = User::find(auth()->user()->id);

        return view('petropd::pd_operators.update_passcode')->with(compact('user'));
    }

public function store_passcode(Request $request)
    {
        try {
            $user = User::find(auth()->user()->id);

            if (strlen($request->current_pass) < 4) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => __('petro::lang.need_longer_pass'),
                ]);
            }

            if ($request->current_pass == $user->pump_operator_passcode) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => __('petro::lang.need_new_password'),
                ]);
            }

            DB::beginTransaction();
            $user->pump_operator_passcode = $request->current_pass;
            $user->pump_operator_pass_changed = 1;
            $user->save();
            DB::commit();

            return redirect()->back()->with('status', [
                'success' => 1,
                'msg' => __('lang_v1.success'),
            ]);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

public function getOperatorShifts(Request $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $pump_operator_id = (int) $request->pump_operator_id;

        if (empty($pump_operator_id)) {
            return response()->json([
                'success' => false,
                'optionHtml' => '',
                'oldest_shift_id' => null,
                'count' => 0,
            ]);
        }

        $pendingShifts = PetroPdClosedShiftQuery::pendingClosedBase($business_id, $pump_operator_id)
            ->select(
                'pump_operator_assignments.shift_id',
                'pump_operator_assignments.pump_operator_id',
                'pump_operator_assignments.shift_number'
            )
            ->groupBy(
                'pump_operator_assignments.shift_id',
                'pump_operator_assignments.pump_operator_id',
                'pump_operator_assignments.shift_number'
            )
            ->orderByRaw('CAST(pump_operator_assignments.shift_number AS UNSIGNED) ASC')
            ->orderBy('pump_operator_assignments.shift_id')
            ->get();

        $oldest = $pendingShifts->first();
        $optionHtml = '<option value="">' . e(__('petro::lang.please_select')) . '</option>';

        foreach ($pendingShifts as $shift) {
            $selected = $oldest && (int) $oldest->shift_id === (int) $shift->shift_id ? ' selected' : '';
            $optionHtml .= '<option value="' . (int) $shift->shift_id . '"' . $selected . '>'
                . e($shift->shift_number) . '</option>';
        }

        return response()->json([
            'success' => true,
            'optionHtml' => $optionHtml,
            'oldest_shift_id' => $oldest ? (int) $oldest->shift_id : null,
            'count' => $pendingShifts->count(),
        ]);
    }

public function getShiftWorkShift(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        // 7988 FINAL SOURCE RULE:
        // The PD Settlement screen is driven by the PD Shift Number shown in
        // pump_operator_assignments.shift_number. Do not derive the operator from
        // the selected operator dropdown, stale option metadata, or work_shift_id.
        $pd_shift_number = $request->input('pd_shift_number', $request->input('shift_number'));
        $shift_id = $request->input('shift_id');

        if (empty($pd_shift_number) && empty($shift_id)) {
            return response()->json([
                'success'              => false,
                'work_shift_id'        => null,
                'pump_operator_id'     => null,
                'pump_operator_name'   => null,
                'assignment_id'        => null,
                'underlying_shift_id'  => null,
                'pd_shift_number'      => null,
                'transaction_date'     => null,
            ]);
        }

        $assignmentQuery = PumpOperatorAssignment::with('pumpOperator')
            ->where('business_id', $business_id)
            ->where('status', 'close')
            ->whereNotNull('close_date_and_time')
            ->where(function ($q) {
                $q->where('closed_in_settlement', 0)
                    ->orWhereNull('closed_in_settlement');
            });

        if (! empty($pd_shift_number)) {
            $assignmentQuery->where('shift_number', (string) $pd_shift_number);
        } else {
            $assignmentQuery->where('shift_id', $shift_id);
        }

        $assignment = $assignmentQuery
            ->orderByRaw('CAST(shift_number AS UNSIGNED) ASC')
            ->orderBy('id', 'asc')
            ->first();

        // Safe fallback for old rows where pending flags were already modified by old code.
        if (empty($assignment)) {
            $fallbackQuery = PumpOperatorAssignment::with('pumpOperator')
                ->where('business_id', $business_id)
                ->where('status', 'close')
                ->whereNotNull('close_date_and_time');

            if (! empty($pd_shift_number)) {
                $fallbackQuery->where('shift_number', (string) $pd_shift_number);
            } else {
                $fallbackQuery->where('shift_id', $shift_id);
            }

            $assignment = $fallbackQuery
                ->orderByRaw('CAST(shift_number AS UNSIGNED) ASC')
                ->orderBy('id', 'asc')
                ->first();
        }

        if (empty($assignment)) {
            return response()->json([
                'success'              => false,
                'work_shift_id'        => null,
                'pump_operator_id'     => null,
                'pump_operator_name'   => null,
                'assignment_id'        => null,
                'underlying_shift_id'  => null,
                'pd_shift_number'      => $pd_shift_number,
                'transaction_date'     => null,
                'msg'                  => 'No closed pending assignment found for selected PD Shift Number.',
            ]);
        }

        $shift = PetroShift::where('id', $assignment->shift_id)
            ->where('business_id', $business_id)
            ->first();

        return response()->json([
            'success'              => true,
            'work_shift_id'        => $shift ? $shift->work_shift_id : null,
            'pump_operator_id'     => (int) $assignment->pump_operator_id,
            'pump_operator_name'   => optional($assignment->pumpOperator)->name,
            'assignment_id'        => (int) $assignment->id,
            'underlying_shift_id'  => (int) $assignment->shift_id,
            'pd_shift_number'      => $assignment->shift_number,
            'transaction_date'     => ! empty($assignment->close_date_and_time)
                ? date('Y-m-d', strtotime($assignment->close_date_and_time))
                : null,
        ]);
    }

public function setting_dash()
    {
        $card_types  = [];
        $business_id = Auth::user()->business_id;
        $card_group  = \App\AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (! empty($card_group)) {
            $card_types = \App\Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'name');

        $settings = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();

        if (!empty($settings) && !empty($settings->dashboard_settings)) {
            $decodedSettings = json_decode($settings->dashboard_settings, true);
            
            if (!is_array($decodedSettings)) {
                $decodedSettings = [];
            }
            
            $defaults = [
                'credit_sales_direct_to_customer' => 'no',
                'show_bulk_pumps' => 'no',
                'meter_sales_compulsory' => 'no',
                'enter_cash_denominations' => 'no',
                'enter_card_numbers' => 'no',
                'card_amount_to_enter' => 'bulk',
                'logoff_time' => '',
                'logoff' => '',
                'bill_prefix' => '',
                'starting_bill_number' => '',
                'pumper_ledger_update' => 'no',
            ];
            
            $decodedSettings = array_merge($defaults, $decodedSettings);
            $settings->dashboard_settings = json_encode($decodedSettings);
        }

        return view('petropd::pd_operators.setting_dash')->with(compact(
            'card_types',
            'pump_operators',
            'business_id',
            'settings'
        ));
    }

public function dashboard_settings()
    {
        if (! Auth::user()->can('pumper_dashboard_settings')) {
            abort(403, 'Unauthorized Access');
        }

        $business_id    = Auth::user()->business_id;
        $pump_operator  = PumpOperator::findOrFail(Auth::user()->pump_operator_id);
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'name');

        $card_types = [];
        $card_group = \App\AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (! empty($card_group)) {
            $card_types = \App\Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        return view('petropd::pd_operators.dashboard_settings')->with(compact(
            'business_id',
            'pump_operator',
            'card_types',
            'pump_operators'
        ));
    }

public function store_settings(Request $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->only(
                'created_at',
                'user_added',
                'show_bulk_pumps',
                'card_type',
                'credit_sales_direct_to_customer',
                'logoff_time',
                'logoff',
                'meter_sales_compulsory',
                'enter_cash_denominations',
                'card_amount_to_enter',
                'enter_card_numbers',
                'bill_prefix',
                'starting_bill_number',
                'pumper_ledger_update'
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
            \Log::emergency('File: ' . $e->getFile() .
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


public function getPumperExcessShortagePayments()
    {
        $business_id = Auth::user()->business_id;

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_pd_module')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {
            $payment_types = $this->productUtil->payment_types();

            $query = PumpOperator::leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                ->leftjoin('transactions', 'pump_operators.id', 'transactions.pump_operator_id')
                ->leftjoin('transaction_payments', function ($join) {
                    $join->on('transactions.id', 'transaction_payments.transaction_id')->whereNull('transaction_payments.deleted_at');
                })
                ->where('pump_operators.business_id', $business_id)
                ->where('transactions.type', 'settlement')
                ->whereIn('transactions.sub_type', ['shortage', 'excess'])
                ->select([
                    'pump_operators.*',
                    'transactions.id as t_id',
                    'transactions.type',
                    'transactions.sub_type',
                    'transactions.final_total',
                    'transactions.transaction_date',
                    'pump_operators.id as pump_operator_id',
                    'business_locations.name as location_name',
                    'transaction_payments.amount',
                    'transaction_payments.method',
                    'transaction_payments.id as tp_id',
                    'transaction_payments.paid_on',
                    'transaction_payments.payment_ref_no',
                ])->groupBy('transaction_payments.id');

            if (! empty(request()->location_id)) {
                $query->where('transactions.location_id', request()->location_id);
            }
            if (! empty(request()->pump_operator)) {
                $query->where('transactions.pump_operator_id', request()->pump_operator);
            }
            if (! empty(request()->type)) {
                $query->where('transactions.sub_type', request()->type);
            }
            if (! empty(request()->payment_type)) {
                $query->where('transaction_payments.method', request()->payment_type);
            }
            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $query->whereDate('transaction_payments.paid_on', '>=', request()->start_date);
                $query->whereDate('transaction_payments.paid_on', '<=', request()->end_date);
            }

            $business_details = Business::find($business_id);

            $fuel_tanks = Datatables::of($query)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group dropup">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' .
                        __('messages.actions') .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if (! empty($row->tp_id)) {
                        $html .= '<li><a href="' . action('TransactionPaymentController@show', [$row->t_id]) . '" class="view_payment_modal"><i class="fa fa-eye"></i> ' . __('messages.view') . '</a></li>';

                        if ($row->sub_type == 'shortage') {
                            $html .= '<li><a href="#" data-href="' . url('/petropd/recover-shortage/' . $row->tp_id . '/edit') . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit" aria-hidden="true"></i> ' . __('messages.edit') . '</a></li>
                                <li><a href="#" data-href="' . url('/petropd/recover-shortage/' . $row->tp_id) . '" class="delete_payment"><i class="fa fa-trash" aria-hidden="true"></i> ' . __('messages.delete') . '</a></li>';
                        }

                        if ($row->sub_type == 'excess') {
                            $html .= '<li><a href="#" data-href="' . url('/petropd/excess-comission/' . $row->tp_id . '/edit') . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit" aria-hidden="true"></i> ' . __('messages.edit') . '</a></li>
                                <li><a href="#" data-href="' . url('/petropd/excess-comission/' . $row->tp_id) . '" class="delete_payment"><i class="fa fa-trash" aria-hidden="true"></i> ' . __('messages.delete') . '</a></li>';
                        }
                    }

                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('paid_on', '{{@format_date($paid_on)}}')
                ->editColumn('excess_amount', function ($row) use ($business_details) {
                    if ($row->sub_type == 'excess') {
                        return '<span class="display_currency excess_amount" data-orig-value="' . $row->final_total . '" data-currency_symbol="true">' . $this->productUtil->num_f($row->final_total, false, $business_details, false) . '</span>';
                    }
                    return $this->productUtil->num_f(0, false, $business_details, false);
                })
                ->editColumn('short_amount', function ($row) use ($business_details) {
                    if ($row->sub_type == 'shortage') {
                        return '<span class="display_currency short_amount" data-orig-value="' . $row->final_total . '" data-currency_symbol="true">' . $this->productUtil->num_f($row->final_total, false, $business_details, false) . '</span>';
                    }
                    return $this->productUtil->num_f(0, false, $business_details, false);
                })
                ->addColumn('shortage_recover', function ($row) use ($business_details) {
                    if ($row->sub_type == 'shortage') {
                        return $this->productUtil->num_f($row->amount, false, $business_details, false);
                    }
                    return $this->productUtil->num_f(0, false, $business_details, false);
                })
                ->addColumn('excess_paid', function ($row) use ($business_details, $payment_types) {
                    $method = '';
                    if (! empty($row->method) && isset($payment_types[$row->method])) {
                        $method = $payment_types[$row->method];
                    }
                    if ($row->sub_type == 'excess') {
                        return $this->productUtil->num_f($row->amount, false, $business_details, false) . ' ' . $method;
                    }
                    return $this->productUtil->num_f(0, false, $business_details, false);
                })
                ->removeColumn('id');

            return $fuel_tanks->rawColumns(['action', 'excess_amount', 'short_amount'])
                ->make(true);
        }
    }

private function getPeriodBalancesForRange($business_id, $start_date, $end_date, $filters = [])
    {
        $locationKey = ! empty($filters['location_id']) ? $filters['location_id'] : 'all';
        $cacheKey    = implode('_', ['v2', $business_id, $start_date, $end_date, $locationKey]);
        
        if (! isset($this->periodBalanceCache[$cacheKey])) {
            $pump_operators_query = PumpOperator::where('business_id', $business_id);
            
            if (! empty($filters['location_id'])) {
                $pump_operators_query->where('location_id', $filters['location_id']);
            }
            
            $pump_operators = $pump_operators_query->pluck('id');
            
            $result = [];
            foreach ($pump_operators as $pump_operator_id) {
                $result[$pump_operator_id] = $this->transactionUtil->getPumpOperatorLedgerSummary(
                    $business_id,
                    $start_date,
                    $end_date,
                    $pump_operator_id,
                    $filters
                );
            }
            
            $this->periodBalanceCache[$cacheKey] = $result;
        }

        return $this->periodBalanceCache[$cacheKey];
    }
}

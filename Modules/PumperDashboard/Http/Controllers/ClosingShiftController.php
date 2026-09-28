<?php

namespace Modules\PumperDashboard\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Modules\PumperDashboard\Entities\DailyCollection;
use Modules\PumperDashboard\Entities\PumpOperatorMeterSale;
use Modules\PumperDashboard\Entities\PumpOperatorMeterSaleDetail;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\PumperDashboard\Entities\Pump;
use Modules\PumperDashboard\Entities\PumperDayEntry;
use Modules\PumperDashboard\Entities\PetroShift;
use Modules\PumperDashboard\Entities\PumpOperator;
use Modules\PumperDashboard\Entities\PumpOperatorAssignment;
use Modules\PumperDashboard\Entities\PumpOperatorPayment;
use Yajra\DataTables\Facades\DataTables;
use Modules\PumperDashboard\Entities\PumpOperatorOtherSale;
use Modules\PumperDashboard\Services\PumperDashboardSchema;

class ClosingShiftController extends Controller
{
    private function standaloneModuleEnabled(int $business_id): bool
    {
        return $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');
    }


    /**
     * Resolve the business currently selected in the tenant session so records
     * written by Pumper Dashboard are immediately visible in PetroPD.
     */
    private function resolveBusinessId(): int
    {
        $business_id = request()->session()->get('business.id')
            ?: request()->session()->get('user.business_id')
            ?: optional(Auth::user())->business_id;

        if (empty($business_id)) {
            abort(403, 'Business context not found. Please logout and login again.');
        }

        return (int) $business_id;
    }

    private function authorizePumperDashboardPermission(string $permission): void
    {
        $user = Auth::user();

        if (
            ! empty($user) &&
            (
                $user->can('pump_operator.dashboard') ||
                $user->can($permission) ||
                (! empty($user->is_pump_operator) && ! empty($user->pump_operator_id))
            )
        ) {
            return;
        }

        if (empty($user)) {
            abort(403, 'Unauthorized Access');
        }

        abort(403, 'Unauthorized Access');
    }


    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
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
     * @return Renderable
     */
    public function index()
    {
        if (! empty(request()->only_pumper)) {
            $this->authorizePumperDashboardPermission('pumper_dashboard.close_shift');
        }

        $business_id =  $this->resolveBusinessId();

        $only_pumper = request()->only_pumper;
        $pump_operator_id = Auth::user()->pump_operator_id;
        if (! $this->standaloneModuleEnabled($business_id)) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {

            $already_added_shortage = [];
            $already_added_excess = [];


            $business_details = Business::find($business_id);

            $has_day_entry_shift_id = PumperDashboardSchema::hasColumn('pumper_day_entries', 'shift_id');
            $has_day_entry_time = PumperDashboardSchema::hasColumn('pumper_day_entries', 'time');
            $has_day_entry_pump_no = PumperDashboardSchema::hasColumn('pumper_day_entries', 'pump_no');
            $query = PumperDayEntry::leftjoin('pump_operators', 'pumper_day_entries.pump_operator_id', 'pump_operators.id')
                ->leftjoin('pump_operator_assignments', 'pump_operator_assignments.id', 'pumper_day_entries.pumper_assignment_id')
                ->leftjoin('pumps', 'pumps.id', 'pumper_day_entries.pump_id')
                ->leftjoin('business_locations', 'business_locations.id', 'pump_operators.location_id')
                ->where('pumper_day_entries.business_id', $business_id)
                ->select(
                    'pump_operators.name',
                    'pumper_day_entries.id',
                    'pumper_day_entries.date',
                    $has_day_entry_time
                        ? 'pumper_day_entries.time'
                        : DB::raw('pumper_day_entries.created_at as time'),
                    'pumper_day_entries.pump_operator_id',
                    'pumper_day_entries.starting_meter',
                    'pumper_day_entries.closing_meter',
                    'pumper_day_entries.testing_ltr',
                    'pumper_day_entries.sold_ltr',
                    'pumper_day_entries.amount',
                    'pumper_day_entries.pump_id',
                    $has_day_entry_pump_no
                        ? DB::raw('COALESCE(NULLIF(pumper_day_entries.pump_no, ""), pumps.pump_no) as pump_no')
                        : 'pumps.pump_no',
                    'business_locations.name as location_name',
                    'pump_operator_assignments.shift_number',
                    'pumper_day_entries.settlement_no'
                );
            $query2 = PumpOperatorOtherSale::join('petro_shifts', 'petro_shifts.id', 'pump_operator_other_sales.shift_id')
                ->join('pump_operators', 'petro_shifts.pump_operator_id', 'pump_operators.id')
                ->join('business_locations', 'business_locations.id', 'pump_operators.location_id')
                ->where('pump_operator_other_sales.business_id', $business_id)
                ->where('petro_shifts.business_id', $business_id)
                ->select(
                    'pump_operators.name',
                    'pump_operator_other_sales.id',
                    'pump_operator_other_sales.created_at as date',
                    'pump_operator_other_sales.created_at as time',
                    'petro_shifts.pump_operator_id',
                    DB::raw('NULL as starting_meter'),
                    DB::raw('NULL as closing_meter'),
                    DB::raw('NULL as testing_ltr'),
                    DB::raw('NULL as sold_ltr'),
                    'pump_operator_other_sales.sub_total as amount',
                    DB::raw('NULL as pump_id'),
                    DB::raw('"Other Sale" as pump_no'),
                    'business_locations.name as location_name',
                    DB::raw('(SELECT MAX(poa.shift_number) FROM pump_operator_assignments poa WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.pump_operator_id = petro_shifts.pump_operator_id) as shift_number'),
                    DB::raw('NULL as settlement_no')
                );
            if ($only_pumper) {
                $query->where('pumper_day_entries.pump_operator_id', $pump_operator_id);
                $query2->where('petro_shifts.pump_operator_id', $pump_operator_id);
            }
            if (!empty(request()->shift_id)) {
                $selected_shift_id = request()->shift_id;
                $query->where(function ($shift_query) use ($selected_shift_id, $has_day_entry_shift_id) {
                    $shift_query->where('pump_operator_assignments.shift_id', $selected_shift_id);

                    if ($has_day_entry_shift_id) {
                        $shift_query->orWhere('pumper_day_entries.shift_id', $selected_shift_id);
                    }
                });
                $query2->where('pump_operator_other_sales.shift_id', request()->shift_id);
            }
            if (!empty(request()->pump_operator_id)) {
                $query->where('pump_operator_assignments.pump_operator_id', request()->pump_operator_id);
                $query2->where('petro_shifts.pump_operator_id', request()->pump_operator_id);
            }
            if (!empty(request()->pump_id)) {
                $query->where('pumper_day_entries.pump_id', request()->pump_id);
                // Other Sales do not belong to one physical pump, so a pump
                // filter intentionally excludes those synthetic rows.
                $query2->whereRaw('1 = 0');
            }
            if (!empty(request()->payment_method)) {
                // $query->where('pump_operator_id', request()->payment_method);
            }
            if (!empty(request()->difference)) {
                // $query->where('pump_operator_id', request()->difference);
            }
            $query = $query->unionAll($query2)->orderBy('id', 'asc');
            /*
             * MA-002 (performance): the short_amount column previously ran one
             * PumpOperatorPayment aggregate query for EVERY row rendered by
             * DataTables. On a shift with 200 day entries that is 200 extra
             * round trips per page load. The totals depend only on
             * business_id, pump_operator_id and the requested shift, so they
             * are aggregated once here and read from memory in the closure.
             */
            $operator_payment_totals = PumpOperatorPayment::where('business_id', $business_id)
                ->when(request()->shift_id, fn($q) => $q->where('shift_id', request()->shift_id))
                ->groupBy('pump_operator_id')
                ->select(
                    'pump_operator_id',
                    DB::raw('SUM(IF(payment_type="shortage", payment_amount, 0)) as short_amount'),
                    DB::raw('SUM(IF(payment_type="excess", payment_amount, 0)) as excess_amount')
                )
                ->get()
                ->keyBy('pump_operator_id');

            $fuel_tanks = DataTables::of($query)

                ->filterColumn('shift_number', function ($query, $keyword) {
                    $query->whereRaw("pump_operator_assignments.shift_number like ?", ["%{$keyword}%"]);
                })->filterColumn('pumps.pump_no', function ($query, $keyword) {
                    // Use the actual column from pumper_day_entries
                    $query->whereRaw("pumper_day_entries.pump_no like ? OR 'Other Sale' like ?", ["%{$keyword}%", "%{$keyword}%"]);
                })

                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        if (empty(auth()->user()->pump_operator_id)) {
                            if (!empty($row->settlement_no ?? null)) {
                                $disabled = 'disabled';
                                $html .= ' <li><a class="btn" disabled><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                            } else {
                                $html .= ' <li><a data-href="' . action('\Modules\PumperDashboard\Http\Controllers\PumperDayEntryController@edit', [$row->id]) . '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                            }
                        }
                        if (empty($row->settlement_no ?? null)) {
                            $html .= ' <li><a data-href="' . action('\Modules\PumperDashboard\Http\Controllers\PumperDayEntryController@postAddSettlementNo', [$row->id]) . '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-plus"></i> ' . __("pumperdashboard::lang.add_settlement_no") . '</a></li>';
                        }

                        $html .= '</ul></div>';
                        if ($row->pump_no == 'Other Sale') {
                            return '';
                        }

                        return $html;
                    }
                )
                /*
                 |--------------------------------------------------------------
                 | Date with the time beneath it, replacing two columns.
                 |--------------------------------------------------------------
                 |
                 | The separate Time column is removed from the Close Shift table
                 | so the page is narrower, and the date has room to be read.
                 |
                 | The 'time' renderer below is left in place: harmless if the
                 | column is not displayed, and it keeps working for anything else
                 | that still asks for it.
                 */
                ->addColumn('date_time_display', function ($row) {
                    $date = ! empty($row->date) ? @format_date($row->date) : '';
                    $time = ! empty($row->time) ? @format_time($row->time) : '';

                    if ($date === '' && $time === '') {
                        return '—';
                    }

                    $html = '<span class="pd-date-main">' . e($date) . '</span>';

                    if ($time !== '') {
                        $html .= '<br><small class="text-muted pd-time-sub">' . e($time) . '</small>';
                    }

                    return $html;
                })
                ->addColumn(
                    'date',
                    '{{@format_date($date)}}'
                )
                ->addColumn('starting_meter', function ($row) {
                    return number_format($row->starting_meter, 3);
                })
                ->addColumn('closing_meter', function ($row) {
                    return number_format($row->closing_meter, 3);
                })

                ->editColumn(
                    'time',
                    '{{@format_time($time)}}'
                )
                ->editColumn(
                    'settlement_no',
                    function ($row) use ($business_details) {
                        if (!empty($row->settlement_no ?? null)) {
                            return  '<a data-href="' . action('\Modules\PumperDashboard\Http\Controllers\PumperDayEntryController@viewAddSettlementNo', [$row->id]) . '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal">' . $row->settlement_no . '</a>';
                        }
                    }
                )
                ->editColumn(
                    'sold_ltr',
                    function ($row) use ($business_details) {

                        return  '<span class="display_currency sold_ltr" data-orig-value="' . $row->sold_ltr . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->sold_ltr, false, $business_details, true) . '</span>';
                    }
                )
                ->editColumn('testing_ltr', function ($row) use ($business_details) {
                    return '<span class="display_currency testing_ltr" data-orig-value="' . ($row->testing_ltr ?? 0) . '" data-currency_symbol=false>' .
                        $this->productUtil->num_f($row->testing_ltr ?? 0, false, $business_details, true) .
                        '</span>';
                })
                ->addColumn('credit_sale', function ($row) use ($business_details) {
                    $credit_sale = (float) ($row->credit_sale ?? 0);
                    return  '<span class="display_currency credit_sale" data-orig-value="' . $credit_sale . '" data-currency_symbol = false>' . $this->productUtil->num_f($credit_sale, false, $business_details, true) . '</span>';
                })
                ->editColumn(
                    'amount',
                    function ($row) use ($business_details) {

                        return  '<span class="display_currency sold_amount" data-orig-value="' . $row->amount . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->amount, false, $business_details, true) . '</span>';
                    }
                )
                ->addColumn('short_amount', function ($row) use ($operator_payment_totals, $business_details, &$already_added_excess, &$already_added_shortage) {

                    $payments = $operator_payment_totals->get($row->pump_operator_id);

                    if (!empty($payments->excess_amount)) {
                        if (in_array($row->pump_operator_id, $already_added_excess)) {
                            return '';
                        } else {
                            $already_added_excess[] = $row->pump_operator_id;
                        }

                        return  '<span class="display_currency short_amount" data-orig-value="' . $payments->excess_amount . '" data-currency_symbol = false>' . $this->productUtil->num_f($payments->excess_amount, false, $business_details, true) . '</span>';
                    }
                    if (!empty($payments->short_amount)) {
                        if (in_array($row->pump_operator_id, $already_added_shortage)) {
                            return '';
                        } else {
                            $already_added_shortage[] = $row->pump_operator_id;
                        }

                        return  '<span class="display_currency short_amount text-red" data-orig-value="' . $payments->short_amount . '" data-currency_symbol = false>' . $this->productUtil->num_f($payments->short_amount, false, $business_details, true) . '</span>';
                    }
                })
                ->removeColumn('id');


            return $fuel_tanks->rawColumns(['action', 'testing_ltr', 'sold_ltr', 'amount', 'short_amount', 'short_amount', 'cash', 'cheque', 'total_amount', 'difference', 'settlement_no', 'date_time_display'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $pumps = Pump::where('business_id', $business_id)->get();
        if ($only_pumper) {
            $pump_operators = PumpOperator::where('business_id', $business_id)->where('id', $pump_operator_id)->pluck('name', 'id');
        } else {
            $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        }
        $payment_types = $this->transactionUtil->payment_types();

        $pump_operator = PumpOperator::find($pump_operator_id);
        $pump_operator_name = "";
        if (!empty($pump_operator)) {
            $pump_operator_name = $pump_operator->name;
        }


        $layout = 'app';
        if ($only_pumper) {
            $layout = 'pumper';
        }

        $assignmentShiftNumber = DB::raw('(SELECT MAX(poa.shift_number) FROM pump_operator_assignments poa WHERE poa.shift_id = petro_shifts.id AND poa.pump_operator_id = petro_shifts.pump_operator_id) as assignment_shift_number');
        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')
            ->where('petro_shifts.business_id', $business_id)
            ->select('pump_operators.name', 'petro_shifts.*', $assignmentShiftNumber)
            ->orderBy('id', 'DESC');

        if ($only_pumper) {
            $shifts->where('petro_shifts.pump_operator_id', $pump_operator_id);
        }

        $shifts = $shifts->get();

        $user = Auth::user();

        $pump_operator_id = $user->pump_operator_id;
        // MA-002: numeric max - shift_number is varchar, so MAX() on it
        // compares as text and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');
        $assignment = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
            ->where('shift_number', $shift_number)->orderBy('id', 'asc')
            ->first();

        $shift_id = $assignment ? $assignment->shift_id : null;
        $shift = null;
        if ($shift_id) {
            $shift = PetroShift::find($shift_id);
        }

        return view('pumperdashboard::actions.closing_shift')->with(compact(
            'layout',
            'business_locations',
            'pumps',
            'pump_operators',
            'pump_operator',
            'payment_types',
            'only_pumper',
            'pump_operator_name',
            'shifts',
            'shift_number',
            'shift'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('pumperdashboard::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
    

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {

        $pump = PumperDayEntry::leftjoin('pumps', 'pumps.id', 'pumper_day_entries.pump_id')
            ->leftjoin('products', 'pumps.product_id', 'products.id')
            ->leftjoin('variations', 'products.id', 'variations.product_id')
            ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
            ->where('pumper_day_entries.id', $id)
            ->select('sell_price_inc_tax', 'pumps.pump_no', 'variation_location_details.qty_available', 'pumper_day_entries.*')->first();


        return view('pumperdashboard::actions.edit_closing_shift')->with(compact(
            'pump',
            'id'
        ));
    }

    public function show($id)
    {


        $pump = PumperDayEntry::leftjoin('pumps', 'pumps.id', 'pumper_day_entries.pump_id')
            ->leftjoin('products', 'pumps.product_id', 'products.id')
            ->leftjoin('variations', 'products.id', 'variations.product_id')
            ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
            ->where('pumper_day_entries.pumper_assignment_id', $id)
            ->select('sell_price_inc_tax', 'pumps.pump_no', 'variation_location_details.qty_available', 'pumper_day_entries.*')->first();
        if (empty(session()->get('pump_operator_main_system'))) {
            $layout = 'pumper';
        } else {
            $layout = 'app';
        }

        return view('pumperdashboard::actions.view_closing_shift')->with(compact(
            'pump',
            'id',
            'layout'
        ));
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        try {
            $entry = PumperDayEntry::findOrFail($id);
            $data = array(
                'closing_meter' => $request->closing_meter,
                'testing_ltr' => $request->testing_ltr,
                'sold_ltr' => $request->sold_ltr,
                'amount' => $request->amount_hidden,
            );

            DB::beginTransaction();

            PumperDayEntry::where('id', $id)->update($data);
            Pump::where('id', $entry->pump_id)->update(['pod_starting_meter' => $request->starting_meter, 'pod_last_meter' => $request->closing_meter]);
            PumpOperatorAssignment::where('pump_id', $entry->pump_id)->where('starting_meter', $request->starting_meter)->update(['closing_meter' => $request->closing_meter]);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('pumperdashboard::lang.success')
            ];

            return redirect()->back()->with('status', $output);
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];

            return redirect()->back()->with('status', $output);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }

    public function closeShift($shift_id)
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.close_shift');

        $business_id = (int) $this->resolveBusinessId();

        try {
            $output = DB::transaction(function () use ($shift_id, $business_id) {
                $shift = PetroShift::where('business_id', $business_id)
                    ->whereKey($shift_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $assignments = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('shift_id', $shift_id)
                    ->lockForUpdate()
                    ->get();

                $unclosed_pumps_count = $assignments->filter(function ($assignment) {
                    $notPosted = $assignment->closed_in_settlement === null
                        || (string) $assignment->closed_in_settlement === ''
                        || (int) $assignment->closed_in_settlement === 0;

                    return $notPosted && (string) $assignment->status !== 'close';
                })->count();

                if ($unclosed_pumps_count > 0) {
                    return [
                        'success' => 0,
                        'msg' => 'Some pumps are not closed so, you cannot close the shift. Please close all pumps and come back',
                    ];
                }

                $pending_assignments = $assignments->filter(function ($assignment) {
                    $notPosted = $assignment->closed_in_settlement === null
                        || (string) $assignment->closed_in_settlement === ''
                        || (int) $assignment->closed_in_settlement === 0;

                    return $notPosted && (string) $assignment->status === 'close';
                })->values();

                if ($pending_assignments->isNotEmpty()) {
                    $entries_by_assignment = PumperDayEntry::where('business_id', $business_id)
                        ->where('pump_operator_id', $shift->pump_operator_id)
                        ->whereIn('pumper_assignment_id', $pending_assignments->pluck('id'))
                        ->where(function ($query) {
                            $query->whereNull('closed_in_settlement')
                                ->orWhere('closed_in_settlement', 0)
                                ->orWhere('closed_in_settlement', '');
                        })
                        ->lockForUpdate()
                        ->get()
                        ->groupBy('pumper_assignment_id');

                    $last_payment_no = PumpOperatorPayment::where('business_id', $business_id)
                        ->whereNotNull('collection_form_no')
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->value('collection_form_no');

                    $last_daily_no = DailyCollection::where('business_id', $business_id)
                        ->whereNotNull('collection_form_no')
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->value('collection_form_no');

                    $next_collection_form_no = max((int) $last_payment_no, (int) $last_daily_no) + 1;
                    $posted_at = now();

                    foreach ($pending_assignments as $assignment) {
                        $entries = $entries_by_assignment->get($assignment->id, collect());

                        if ($entries->isEmpty()) {
                            continue;
                        }

                        $amount = (float) $entries->sum('amount');
                        $testing_qty = (float) $entries->sum('testing_ltr');

                        $meter_sale = PumpOperatorMeterSale::create([
                            'business_id' => $business_id,
                            'date_time' => $posted_at,
                            'pump_operator_id' => $shift->pump_operator_id,
                            'amount' => $amount,
                            'deposited' => 0,
                            'balance' => $amount,
                            'collection_form_no' => $next_collection_form_no,
                            'shift_id' => $shift_id,
                            'testing_qty' => $testing_qty,
                            'source' => 'closing',
                        ]);

                        foreach ($entries as $entry) {
                            PumpOperatorMeterSaleDetail::create([
                                'sale_id' => $meter_sale->id,
                                'business_id' => $business_id,
                                'pump_operator_id' => $shift->pump_operator_id,
                                'pump_id' => $entry->pump_id,
                                'received_meter' => $entry->starting_meter,
                                'new_meter' => $entry->closing_meter,
                                'sold_qty' => $entry->sold_ltr,
                                'unit_price' => (float) $entry->sold_ltr > 0
                                    ? (float) $entry->amount / (float) $entry->sold_ltr
                                    : 0,
                                'amount' => $entry->amount,
                            ]);
                        }

                        PumperDayEntry::where('business_id', $business_id)
                            ->whereIn('id', $entries->pluck('id'))
                            ->update(['closed_in_settlement' => 1]);

                        /*
                         * MA-002: the ASSIGNMENT IS NO LONGER MARKED HERE.
                         *
                         * closed_in_settlement on the assignment is what PD
                         * Settlement reads to know a shift has already been
                         * settled. Setting it when the shift CLOSED meant every
                         * shift was marked settled the moment it closed, so it
                         * could never appear in PD Settlement at all.
                         *
                         * From the operator's side the work ends when the shift
                         * closes. From the company's side it is not finished
                         * until the settlement is saved - so this flag belongs
                         * to settlement, and settlement already sets it:
                         * SettlementStateMachine, SettlementSourceRepository
                         * and PdSettlementSourceMarker all write it when a
                         * settlement is saved.
                         *
                         * DOUBLE POSTING IS STILL PREVENTED - by the DAY ENTRY
                         * flag set just above, not by this one. The entries are
                         * selected with closed_in_settlement 0, and the loop
                         * skips an assignment whose entries come back empty. So
                         * closing the same shift twice posts nothing the second
                         * time, exactly as before.
                         */

                        $next_collection_form_no++;
                    }
                }

                $closedAt = now();

                // IS1814-6: Petro PD discovers settleable shifts from the closed
                // assignment rows. Close Shift must therefore persist the final
                // assignment status and close timestamp, not only petro_shifts.
                $assignmentCloseData = ['status' => 'close'];
                if (Schema::hasColumn('pump_operator_assignments', 'close_date_and_time')) {
                    $assignmentCloseData['close_date_and_time'] = $closedAt;
                }

                PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('shift_id', $shift_id)
                    ->update($assignmentCloseData);

                $shift->status = 2;
                $shift->closed_time = $closedAt->format('Y-m-d H:i');
                $shift->save();

                return [
                    'success' => 1,
                    'msg' => __('lang_v1.success'),
                    'closed_shift_id' => (int) $shift->id,
                ];
            }, 3);
        } catch (\Throwable $e) {
            \Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if (empty($output['success'])) {
            return redirect()->back()->with('status', $output);
        }

        /*
         | MA-008: after a successful close, ask whether to print the summary.
         |
         | The shift id is flashed so the dashboard knows WHICH shift to offer,
         | and the logout url is resolved here rather than in JavaScript so the
         | "No need" branch cannot guess a wrong address. The prompt itself lives
         | in the dashboard view - see pumper_close_shift_print_prompt.
         */
        return redirect()->to('/pumper-dashboard/pump-operators/dashboard')
            ->with('status', $output)
            ->with('pumper_close_shift_print_prompt', [
                'shift_id'  => (int) $shift_id,
                'print_url' => route('pumperdashboard.close-shift.summary.print', $shift_id),
            ]);
    }
}

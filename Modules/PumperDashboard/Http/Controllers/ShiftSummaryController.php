<?php

namespace Modules\PumperDashboard\Http\Controllers;

use App\Business;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Petro\Entities\PumperDayEntry;
use Modules\PumperDashboard\Services\PumperDashboardSchema;
use Yajra\DataTables\Facades\DataTables;

class ShiftSummaryController extends Controller
{
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
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil   $businessUtil)
    {
        $this->commonUtil = $commonUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;

        //barcode types
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {

        $business_id = request()->session()->get('user.business_id');
        if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_module')) {
            abort(403, 'Unauthorized Access');
        }

        $previous_date = \Carbon\Carbon::now()->subDay()->format('Y-m-d');

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $business_details = Business::find($business_id);
                $target_date = date('Y-m-d');
                if (empty(request()->start_date) || empty(request()->end_date)) {
                    $targetStart = \Carbon\Carbon::parse($target_date)->startOfDay();
                    $targetEnd = $targetStart->copy()->endOfDay();
                    $today_count = PumperDayEntry::where('business_id', $business_id)
                        ->whereBetween('date', [$targetStart, $targetEnd])
                        ->count();
                    if ($today_count === 0) {
                        $target_date = $previous_date;
                    }
                }

                # 02-06-2026
                //  \DB::raw('(SELECT SUM(sub_total - discount_amount) FROM pump_operator_other_sales WHERE pump_operator_other_sales.shift_id = poa.shift_id) as other_sales'),

                /*
                 * IS2321: this page is shared by tenant databases with slightly
                 * different historical schemas. Optional payment-link columns
                 * must never be referenced blindly inside raw SQL because one
                 * missing column makes the hidden Shift Summary request return
                 * HTTP 500 and DataTables shows a blocking Ajax warning on the
                 * PD Operators page.
                 */
                $has_payment_pump_no = PumperDashboardSchema::hasColumn('pump_operator_payments', 'pump_no');
                $has_credit_master_link = PumperDashboardSchema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id');
                $has_credit_daily_voucher_link = PumperDashboardSchema::hasColumn('settlement_credit_sale_payments', 'daily_voucher_id');
                $has_daily_voucher_no = PumperDashboardSchema::hasColumn('daily_vouchers', 'daily_vouchers_no');
                $has_daily_voucher_pump_id = PumperDashboardSchema::hasColumn('daily_vouchers', 'pump_id');

                // Legacy payment tables without pump_no can only be allocated at
                // shift level. Put such a payment on one day-entry row so it is
                // not repeated for every pump in the same operator/shift.
                $legacy_first_entry_scope = '(pumper_day_entries.id = (
                    SELECT MIN(pde_scope.id)
                    FROM pumper_day_entries pde_scope
                    LEFT JOIN pump_operator_assignments poa_scope ON pde_scope.pumper_assignment_id = poa_scope.id
                    WHERE poa_scope.shift_id = poa.shift_id
                      AND pde_scope.pump_operator_id = pumper_day_entries.pump_operator_id
                ))';

                $payment_pump_scope = $has_payment_pump_no
                    ? '(pump_operator_payments.pump_no = pumps.pump_no OR pump_operator_payments.pump_no IS NULL OR pump_operator_payments.pump_no = "")'
                    : $legacy_first_entry_scope;

                if ($has_credit_master_link
                    && $has_credit_daily_voucher_link
                    && $has_daily_voucher_no
                    && $has_daily_voucher_pump_id) {
                    $credit_pump_scope = $has_payment_pump_no
                        ? '(pop.pump_no = pumps.pump_no OR dv.pump_id = pumper_day_entries.pump_id OR ((pop.pump_no IS NULL OR pop.pump_no = "") AND (
                            SELECT COUNT(*)
                            FROM pumper_day_entries pde2
                            LEFT JOIN pump_operator_assignments poa2 ON pde2.pumper_assignment_id = poa2.id
                            WHERE poa2.shift_id = poa.shift_id
                              AND pde2.pump_operator_id = pumper_day_entries.pump_operator_id
                        ) = 1))'
                        : '(dv.pump_id = pumper_day_entries.pump_id OR ' . $legacy_first_entry_scope . ')';

                    $credit_sale_sql = '(SELECT SUM(pop.payment_amount)
                        FROM pump_operator_payments pop
                        LEFT JOIN settlement_credit_sale_payments scsp ON scsp.pump_payment_id = pop.id
                        LEFT JOIN daily_vouchers dv ON (dv.id = scsp.daily_voucher_id OR (dv.daily_vouchers_no = pop.collection_form_no AND dv.business_id = pop.business_id))
                        WHERE pop.business_id = pumper_day_entries.business_id
                          AND pop.shift_id = poa.shift_id
                          AND pop.pump_operator_id = pumper_day_entries.pump_operator_id
                          AND pop.payment_type = "credit"
                          AND ' . $credit_pump_scope . ') as credit_sale';
                } else {
                    $legacy_credit_scope = $has_payment_pump_no
                        ? '(pop.pump_no = pumps.pump_no OR pop.pump_no IS NULL OR pop.pump_no = "")'
                        : $legacy_first_entry_scope;

                    $credit_sale_sql = '(SELECT SUM(pop.payment_amount)
                        FROM pump_operator_payments pop
                        WHERE pop.business_id = pumper_day_entries.business_id
                          AND pop.shift_id = poa.shift_id
                          AND pop.pump_operator_id = pumper_day_entries.pump_operator_id
                          AND pop.payment_type = "credit"
                          AND ' . $legacy_credit_scope . ') as credit_sale';
                }

                $query = PumperDayEntry::leftjoin('pump_operators', 'pumper_day_entries.pump_operator_id', 'pump_operators.id')
                    ->leftjoin('pumps', 'pumper_day_entries.pump_id', 'pumps.id')
                    ->leftjoin('pump_operator_assignments as poa', 'pumper_day_entries.pumper_assignment_id', 'poa.id')
                    ->where('pumper_day_entries.business_id', $business_id)
                    ->select(
                        'pump_operators.name',
                        'pumper_day_entries.*',
                        'pumps.pump_no',
                        \DB::raw($credit_sale_sql),
                        \DB::raw('(SELECT SUM(payment_amount) FROM pump_operator_payments 
                                    WHERE pump_operator_payments.business_id = pumper_day_entries.business_id 
                                      AND pump_operator_payments.shift_id = poa.shift_id 
                                      AND pump_operator_payments.pump_operator_id = pumper_day_entries.pump_operator_id 
                                      AND pump_operator_payments.payment_type = "card"
                                      AND ' . $payment_pump_scope . ') as cards'),
                        \DB::raw('(SELECT SUM(payment_amount) FROM pump_operator_payments 
                                    WHERE pump_operator_payments.business_id = pumper_day_entries.business_id 
                                      AND pump_operator_payments.shift_id = poa.shift_id 
                                      AND pump_operator_payments.pump_operator_id = pumper_day_entries.pump_operator_id 
                                      AND pump_operator_payments.payment_type = "cash"
                                      AND ' . $payment_pump_scope . ') as cash'),
                        \DB::raw('(SELECT SUM(payment_amount) FROM pump_operator_payments 
                                    WHERE pump_operator_payments.business_id = pumper_day_entries.business_id 
                                      AND pump_operator_payments.shift_id = poa.shift_id 
                                      AND pump_operator_payments.pump_operator_id = pumper_day_entries.pump_operator_id 
                                      AND pump_operator_payments.payment_type = "cheque"
                                      AND ' . $payment_pump_scope . ') as cheque'),
                        \DB::raw('(SELECT SUM(sub_total - discount_amount) FROM pump_operator_other_sales 
                                    WHERE pump_operator_other_sales.business_id = pumper_day_entries.business_id 
                                      AND pump_operator_other_sales.shift_id = poa.shift_id
                                      AND pumper_day_entries.id = (
                                          SELECT MIN(pde2.id) 
                                          FROM pumper_day_entries pde2 
                                          LEFT JOIN pump_operator_assignments poa2 ON pde2.pumper_assignment_id = poa2.id
                                          WHERE poa2.shift_id = poa.shift_id 
                                            AND pde2.pump_operator_id = pumper_day_entries.pump_operator_id
                                      )) as other_sales'),
                        \DB::raw('(SELECT SUM(payment_amount) FROM pump_operator_payments 
                                    WHERE pump_operator_payments.business_id = pumper_day_entries.business_id 
                                      AND pump_operator_payments.shift_id = poa.shift_id 
                                      AND pump_operator_payments.pump_operator_id = pumper_day_entries.pump_operator_id 
                                      AND pump_operator_payments.payment_type = "other"
                                      AND ' . $payment_pump_scope . ') as other'),
                        \DB::raw('(SELECT SUM(payment_amount) FROM pump_operator_payments 
                                    WHERE pump_operator_payments.business_id = pumper_day_entries.business_id 
                                      AND pump_operator_payments.shift_id = poa.shift_id 
                                      AND pump_operator_payments.pump_operator_id = pumper_day_entries.pump_operator_id 
                                      AND pump_operator_payments.payment_type = "shortage"
                                      AND ' . $payment_pump_scope . ') as shortage'),
                        \DB::raw('(SELECT SUM(payment_amount) FROM pump_operator_payments 
                                    WHERE pump_operator_payments.business_id = pumper_day_entries.business_id 
                                      AND pump_operator_payments.shift_id = poa.shift_id 
                                      AND pump_operator_payments.pump_operator_id = pumper_day_entries.pump_operator_id 
                                      AND pump_operator_payments.payment_type = "excess"
                                      AND ' . $payment_pump_scope . ') as excess'),
                        'poa.shift_id'
                    );

                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $rangeStart = \Carbon\Carbon::parse(request()->start_date)->startOfDay();
                    $rangeEnd = \Carbon\Carbon::parse(request()->end_date)->endOfDay();
                    $query->whereBetween('pumper_day_entries.date', [$rangeStart, $rangeEnd]);
                } else {
                    $targetStart = \Carbon\Carbon::parse($target_date)->startOfDay();
                    $targetEnd = $targetStart->copy()->endOfDay();
                    $query->whereBetween('pumper_day_entries.date', [$targetStart, $targetEnd]);
                }

                if (!empty(request()->location_id)) {
                    $query->where('pump_operators.location_id', request()->location_id);
                }
                if (!empty(request()->pump_operator_id)) {
                    $query->where('pumper_day_entries.pump_operator_id', request()->pump_operator_id);
                }
                if (!empty(request()->pump_id)) {
                    $query->where('pumper_day_entries.pump_id', request()->pump_id);
                }
                if (!empty(request()->shift_id)) {
                    $query->where('poa.shift_id', request()->shift_id);
                }
                if (!empty(request()->payment_method)) {
                    $payment_method = request()->payment_method;
                    $type_map = [
                        'cash' => 'cash',
                        'card' => 'card',
                        'cheque' => 'cheque',
                        'credit' => 'credit',
                        'other' => 'other',
                        'shortage' => 'shortage',
                        'excess' => 'excess'
                    ];
                    $db_type = $type_map[$payment_method] ?? $payment_method;
                    $query->whereRaw('(SELECT SUM(payment_amount) FROM pump_operator_payments WHERE pump_operator_payments.business_id = pumper_day_entries.business_id AND pump_operator_payments.shift_id = poa.shift_id AND pump_operator_payments.pump_operator_id = pumper_day_entries.pump_operator_id AND pump_operator_payments.payment_type = ?) > 0', [$db_type]);
                }
                if (!empty(request()->difference)) {
                    $diff_expr = '((SELECT COALESCE(SUM(payment_amount), 0) FROM pump_operator_payments WHERE pump_operator_payments.business_id = pumper_day_entries.business_id AND pump_operator_payments.shift_id = poa.shift_id AND pump_operator_payments.pump_operator_id = pumper_day_entries.pump_operator_id AND pump_operator_payments.payment_type IN ("credit", "card", "cash", "cheque", "other", "shortage", "excess")) - (pumper_day_entries.amount + COALESCE((SELECT SUM(sub_total - discount_amount) FROM pump_operator_other_sales WHERE pump_operator_other_sales.business_id = pumper_day_entries.business_id AND pump_operator_other_sales.shift_id = poa.shift_id), 0)))';
                    if (request()->difference == 'positive') {
                        $query->whereRaw("$diff_expr > 0");
                    } elseif (request()->difference == 'negative') {
                        $query->whereRaw("$diff_expr < 0");
                    }
                }

                $total_query = clone $query;
                $records = $total_query->get();

                $total_sold_ltr = $records->sum('sold_ltr');
                $total_sold_amount = $records->sum('amount');

                // Group by shift_id and pump_operator_id to deduplicate shift-level values
                $unique_shifts = $records->groupBy(function ($item) {
                    return $item->shift_id . '_' . $item->pump_operator_id;
                });

                $total_cash = 0;
                $total_cards = 0;
                $total_credit_sale = 0;
                $total_cheque = 0;
                $total_other_sales = 0;
                $total_other = 0;
                $total_shortage = 0;
                $total_excess = 0;

                foreach ($unique_shifts as $group) {
                    $first = $group->first();
                    $total_cash += $first->cash ?? 0;
                    $total_cards += $first->cards ?? 0;
                    $total_credit_sale += $first->credit_sale ?? 0;
                    $total_cheque += $first->cheque ?? 0;
                    $total_other_sales += $first->other_sales ?? 0;
                    $total_other += $first->other ?? 0;
                    $total_shortage += $first->shortage ?? 0;
                    $total_excess += $first->excess ?? 0;
                }

                $total_payment = $total_cash + $total_cards + $total_credit_sale + $total_cheque + $total_other + $total_shortage + $total_excess;
                $total_difference = $total_payment - ($total_sold_amount + $total_other_sales);

                $totals = [
                    'sold_ltr' => $total_sold_ltr,
                    'sold_amount' => $total_sold_amount,
                    'other_sales' => $total_other_sales,
                    'credit_sale' => $total_credit_sale,
                    'cards' => $total_cards,
                    'cash' => $total_cash,
                    'cheque' => $total_cheque,
                    'other' => $total_other,
                    'shortage' => $total_shortage,
                    'excess' => $total_excess,
                    'total_amount' => $total_payment,
                    'difference' => $total_difference,
                ];

                $fuel_tanks = DataTables::of($records)
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
                            $html .= '</ul></div>';

                            return $html;
                        }
                    )
                    ->addColumn(
                        'date',
                        '{{@format_date($date)}}'
                    )
                    ->editColumn(
                        'sold_ltr',
                        function ($row) use ($business_details) {

                            return  '<span class="display_currency sold_ltr" data-orig-value="' . $row->sold_ltr . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->sold_ltr, false, $business_details, true) . '</span>';
                        }
                    )
                    ->editColumn('testing_ltr', '{{@format_quantity($testing_ltr)}}')
                    ->addColumn('credit_sale', function ($row) use ($business_details) {
                        return  '<span class="display_currency credit_sale" data-orig-value="' . $row->credit_sale . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->credit_sale, false, $business_details, true) . '</span>';
                    })
                    ->addColumn('cards', function ($row) use ($business_details) {
                        return  '<span class="display_currency cards" data-orig-value="' . $row->cards . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->cards, false, $business_details, true) . '</span>';
                    })
                    ->addColumn('cash', function ($row) use ($business_details) {
                        return  '<span class="display_currency cash" data-orig-value="' . $row->cash . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->cash, false, $business_details, true) . '</span>';
                    })
                    ->addColumn('cheque', function ($row) use ($business_details) {
                        return  '<span class="display_currency cheque" data-orig-value="' . $row->cheque . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->cheque, false, $business_details, true) . '</span>';
                    })
                    ->addColumn('other_sales', function ($row) use ($business_details) {
                        return  '<span class="display_currency other_sales" data-orig-value="' . ($row->other_sales ?? 0) . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->other_sales ?? 0, false, $business_details, true) . '</span>';
                    })
                    ->addColumn('other', function ($row) use ($business_details) {
                        return  '<span class="display_currency other" data-orig-value="' . ($row->other ?? 0) . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->other ?? 0, false, $business_details, true) . '</span>';
                    })
                    ->addColumn('shortage', function ($row) use ($business_details) {
                        return  '<span class="display_currency shortage" data-orig-value="' . ($row->shortage ?? 0) . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->shortage ?? 0, false, $business_details, true) . '</span>';
                    })
                    ->addColumn('excess', function ($row) use ($business_details) {
                        return  '<span class="display_currency excess" data-orig-value="' . ($row->excess ?? 0) . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->excess ?? 0, false, $business_details, true) . '</span>';
                    })
                    ->addColumn('total_amount', function ($row) use ($business_details) {
                        $total_amount = $row->credit_sale +  $row->cards + $row->cash + $row->cheque + ($row->other ?? 0) + ($row->shortage ?? 0) + ($row->excess ?? 0);
                        return  '<span class="display_currency total_amount" data-orig-value="' .  $total_amount . '" data-currency_symbol = false>' . $this->productUtil->num_f($total_amount, false, $business_details, true) . '</span>';
                    })
                    ->addColumn('difference', function ($row) use ($business_details) {
                        $total_amount = $row->credit_sale +  $row->cards + $row->cash + $row->cheque + ($row->other ?? 0) + ($row->shortage ?? 0) + ($row->excess ?? 0);
                        $sold_amount = $row->amount + ($row->other_sales ?? 0);
                        $difference = $total_amount - $sold_amount;
                        if ($difference < 0) {
                            return  '<span class="display_currency difference text-red" data-orig-value="' . $difference . '" data-currency_symbol = false>' . $this->productUtil->num_f($difference, false, $business_details, true) . '</span>';
                        }
                        return  '<span class="display_currency difference" data-orig-value="' . $difference . '" data-currency_symbol = false>' . $this->productUtil->num_f($difference, false, $business_details, true) . '</span>';
                    })
                    ->editColumn(
                        'amount',
                        function ($row) use ($business_details) {

                            return  '<span class="display_currency sold_amount" data-orig-value="' . $row->amount . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->amount, false, $business_details, true) . '</span>';
                        }
                    )
                    ->removeColumn('id');


                return $fuel_tanks->rawColumns(['action', 'sold_ltr', 'amount', 'credit_sale', 'cards', 'cash', 'cheque', 'other_sales', 'other', 'shortage', 'excess', 'total_amount', 'difference'])
                    ->with('totals', $totals)
                    ->make(true);
            }
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
     */
    public function show($id)
    {
        return view('pumperdashboard::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('pumperdashboard::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
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
}

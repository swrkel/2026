<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Business;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Entities\PumperDayEntry;
use Yajra\DataTables\Facades\DataTables;

class PDShiftSummaryController extends Controller
{
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $commonUtil;
    protected $businessUtil;
    protected $barcode_types;

    public function __construct(
        Util $commonUtil,
        ProductUtil $productUtil,
        ModuleUtil $moduleUtil,
        TransactionUtil $transactionUtil,
        BusinessUtil $businessUtil
    ) {
        $this->commonUtil = $commonUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    /**
     * Resolve the currently selected tenant business.  The PD Operators page
     * uses business.id when the user switches business/location context, while
     * some older AJAX endpoints used user.business_id only.
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

    /**
     * Shift Summary ajax list.
     *
     * Important fix:
     * Payment totals are shift/operator level totals. If we show those totals on every pump row,
     * the summary boxes and footer become duplicated. Therefore, payment totals and other sales
     * are shown only on the first row of each date + operator + shift group.
     */
    public function index()
    {
        $business_id = $this->resolveBusinessId();

        $previous_date = \Carbon::now()->subDay(1)->format('Y-m-d');

        if (request()->ajax()) {
            $business_details = Business::find($business_id);
            $target_date = date('Y-m-d');

            if (empty(request()->start_date) || empty(request()->end_date)) {
                $today_count = PumperDayEntry::where('business_id', $business_id)
                    ->whereDate('date', $target_date)
                    ->count();

                if ((int) $today_count === 0) {
                    $target_date = $previous_date;
                }
            }

            $paymentSum = function ($type) {
                return '(SELECT COALESCE(SUM(pop.payment_amount), 0)
                    FROM pump_operator_payments AS pop
                    WHERE pop.business_id = pumper_day_entries.business_id
                        AND pop.shift_id = poa.shift_id
                        AND pop.pump_operator_id = pumper_day_entries.pump_operator_id
                        AND pop.payment_type = "' . $type . '")';
            };

            $paymentTotalExpr = '(' . implode(' + ', [
                $paymentSum('credit'),
                $paymentSum('card'),
                $paymentSum('cash'),
                $paymentSum('cheque'),
                $paymentSum('other'),
                $paymentSum('shortage'),
                $paymentSum('excess'),
            ]) . ')';

            $otherSalesExpr = '(SELECT COALESCE(SUM(pos.sub_total - pos.discount_amount), 0)
                FROM pump_operator_other_sales AS pos
                WHERE pos.business_id = pumper_day_entries.business_id
                    AND pos.shift_id = poa.shift_id)';

            $firstEntryExpr = '(SELECT MIN(pde2.id)
                FROM pumper_day_entries AS pde2
                LEFT JOIN pump_operator_assignments AS poa2 ON pde2.pumper_assignment_id = poa2.id
                WHERE pde2.business_id = pumper_day_entries.business_id
                    AND pde2.pump_operator_id = pumper_day_entries.pump_operator_id
                    AND poa2.shift_id = poa.shift_id
                    AND DATE(pde2.date) = DATE(pumper_day_entries.date))';

            $query = PumperDayEntry::leftJoin('pump_operators', 'pumper_day_entries.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('pumps', 'pumper_day_entries.pump_id', '=', 'pumps.id')
                ->leftJoin('pump_operator_assignments as poa', 'pumper_day_entries.pumper_assignment_id', '=', 'poa.id')
                ->leftJoin('pumps as assigned_pumps', 'poa.pump_id', '=', 'assigned_pumps.id')
                ->leftJoin('settlements as s', function ($join) use ($business_id) {
                    $join->whereRaw(
                        's.business_id = ? AND (CONVERT(s.id USING utf8mb4) = CONVERT(pumper_day_entries.settlement_no USING utf8mb4) OR CONVERT(s.settlement_no USING utf8mb4) = CONVERT(pumper_day_entries.settlement_no USING utf8mb4) OR s.id = poa.settlement_id)',
                        [$business_id]
                    );
                })
                ->where('pumper_day_entries.business_id', $business_id)
                ->select(
                    'pump_operators.name',
                    'pumper_day_entries.id',
                    'pumper_day_entries.business_id',
                    'pumper_day_entries.pump_operator_id',
                    'pumper_day_entries.date',
                    'pumper_day_entries.time',
                    DB::raw('COALESCE(pumper_day_entries.pump_id, poa.pump_id) AS pump_id'),
                    DB::raw('COALESCE(pumps.pump_no, assigned_pumps.pump_no, pumper_day_entries.pump_no) AS pump_no'),
                    DB::raw('COALESCE(pumps.pump_no, assigned_pumps.pump_no, pumper_day_entries.pump_no) as pump_no_display'),
                    'pumper_day_entries.starting_meter',
                    'pumper_day_entries.closing_meter',
                    'pumper_day_entries.testing_ltr',
                    'pumper_day_entries.sold_ltr',
                    'pumper_day_entries.amount',
                    'pumper_day_entries.settlement_datetime',
                    DB::raw('COALESCE(s.settlement_no, pumper_day_entries.settlement_no) AS settlement_no'),
                    'pumper_day_entries.settlement_added_by',
                    'pumper_day_entries.closed_in_settlement',
                    'pumper_day_entries.pumper_assignment_id',
                    'pumper_day_entries.created_at',
                    'pumper_day_entries.updated_at',
                    'poa.shift_id',
                    DB::raw($paymentSum('credit') . ' AS credit_sale'),
                    DB::raw($paymentSum('card') . ' AS cards'),
                    DB::raw($paymentSum('cash') . ' AS cash'),
                    DB::raw($paymentSum('cheque') . ' AS cheque'),
                    DB::raw($otherSalesExpr . ' AS other_sales'),
                    DB::raw($paymentSum('other') . ' AS other'),
                    DB::raw($paymentSum('shortage') . ' AS shortage'),
                    DB::raw($paymentSum('excess') . ' AS excess'),
                    DB::raw($firstEntryExpr . ' AS first_entry_id')
                )
                ->groupBy('pumper_day_entries.id');

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $query->whereDate('pumper_day_entries.date', '>=', request()->start_date)
                    ->whereDate('pumper_day_entries.date', '<=', request()->end_date);
            } else {
                $query->whereDate('pumper_day_entries.date', $target_date);
            }

            if (!empty(request()->location_id)) {
                $query->where('pump_operators.location_id', request()->location_id);
            }

            if (!empty(request()->pump_operator_id)) {
                $query->where('pumper_day_entries.pump_operator_id', request()->pump_operator_id);
            }

            if (! empty(request()->pump_id)) {
                $pump_id = (int) request()->pump_id;

                /*
                 * IS1752-2: legacy day-entry rows are not uniform.  Some rows
                 * keep pump_id directly, some keep only pumper_assignment_id,
                 * and copied databases may retain only pump_no.  Resolve all
                 * exact ownership keys for the selected pump without broadening
                 * the business scope.  This makes the Pumps filter return the
                 * same rows that are visible when All is selected.
                 */
                $assignmentIds = DB::table('pump_operator_assignments')
                    ->where('business_id', $business_id)
                    ->where('pump_id', $pump_id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->values()
                    ->all();

                $selectedPumpNo = DB::table('pumps')
                    ->where('id', $pump_id)
                    ->value('pump_no');

                $query->where(function ($pumpQuery) use ($pump_id, $assignmentIds, $selectedPumpNo) {
                    // The joined assignment is always safe and exact.
                    $pumpQuery->where('poa.pump_id', $pump_id);

                    if (Schema::hasColumn('pumper_day_entries', 'pump_id')) {
                        $pumpQuery->orWhere('pumper_day_entries.pump_id', $pump_id);
                    }

                    if (! empty($assignmentIds)
                        && Schema::hasColumn('pumper_day_entries', 'pumper_assignment_id')) {
                        $pumpQuery->orWhereIn('pumper_day_entries.pumper_assignment_id', $assignmentIds);
                    }

                    if (! empty($selectedPumpNo)
                        && Schema::hasColumn('pumper_day_entries', 'pump_no')) {
                        $pumpQuery->orWhere('pumper_day_entries.pump_no', (string) $selectedPumpNo);
                    }
                });
            }

            if (!empty(request()->shift_id)) {
                $query->where('poa.shift_id', request()->shift_id);
            }

            if (!empty(request()->payment_method)) {
                $type_map = [
                    'cash' => 'cash',
                    'card' => 'card',
                    'cheque' => 'cheque',
                    'credit' => 'credit',
                    'other' => 'other',
                    'shortage' => 'shortage',
                    'excess' => 'excess',
                ];

                $db_type = $type_map[request()->payment_method] ?? request()->payment_method;
                $query->whereRaw($paymentSum($db_type) . ' > 0');
            }

            if (!empty(request()->difference)) {
                $diffExpr = '(' . $paymentTotalExpr . ' - (pumper_day_entries.amount + ' . $otherSalesExpr . '))';

                if (request()->difference == 'positive') {
                    $query->whereRaw($diffExpr . ' > 0');
                } elseif (request()->difference == 'negative') {
                    $query->whereRaw($diffExpr . ' < 0');
                }
            }

            $query->orderByDesc('pumper_day_entries.date')
                ->orderByDesc('pumper_day_entries.time')
                ->orderByDesc('pumper_day_entries.id');

            $fuel_tanks = DataTables::of($query)
                ->addColumn('action', function () {
                    return '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                        . __("messages.actions") .
                        '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu"></ul>
                    </div>';
                })
                ->editColumn('date', function ($row) {
                    return ! empty($row->date) ? $this->commonUtil->format_date($row->date) : '—';
                })
                ->editColumn('testing_ltr', '{{@format_quantity($testing_ltr)}}')
                ->editColumn('sold_ltr', function ($row) use ($business_details) {
                    $value = (float) ($row->sold_ltr ?? 0);
                    return '<span class="display_currency sold_ltr" data-orig-value="' . $value . '" data-currency_symbol="false">'
                        . $this->productUtil->num_f($value, false, $business_details, true) . '</span>';
                })
                ->editColumn('amount', function ($row) use ($business_details) {
                    $value = (float) ($row->amount ?? 0);
                    return '<span class="display_currency sold_amount" data-orig-value="' . $value . '" data-currency_symbol="false">'
                        . $this->productUtil->num_f($value, false, $business_details, true) . '</span>';
                })
                ->addColumn('other_sales', function ($row) use ($business_details) {
                    $value = $this->isFirstShiftSummaryRow($row) ? (float) ($row->other_sales ?? 0) : 0;
                    return $this->formatShiftSummaryCurrency($value, 'other_sales', $business_details);
                })
                ->addColumn('credit_sale', function ($row) use ($business_details) {
                    $value = $this->isFirstShiftSummaryRow($row) ? (float) ($row->credit_sale ?? 0) : 0;
                    return $this->formatShiftSummaryCurrency($value, 'credit_sale', $business_details);
                })
                ->addColumn('cards', function ($row) use ($business_details) {
                    $value = $this->isFirstShiftSummaryRow($row) ? (float) ($row->cards ?? 0) : 0;
                    return $this->formatShiftSummaryCurrency($value, 'cards', $business_details);
                })
                ->addColumn('cash', function ($row) use ($business_details) {
                    $value = $this->isFirstShiftSummaryRow($row) ? (float) ($row->cash ?? 0) : 0;
                    return $this->formatShiftSummaryCurrency($value, 'cash', $business_details);
                })
                ->addColumn('cheque', function ($row) use ($business_details) {
                    $value = $this->isFirstShiftSummaryRow($row) ? (float) ($row->cheque ?? 0) : 0;
                    return $this->formatShiftSummaryCurrency($value, 'cheque', $business_details);
                })
                ->addColumn('other', function ($row) use ($business_details) {
                    $value = $this->isFirstShiftSummaryRow($row) ? (float) ($row->other ?? 0) : 0;
                    return $this->formatShiftSummaryCurrency($value, 'other', $business_details);
                })
                ->addColumn('shortage', function ($row) use ($business_details) {
                    $value = $this->isFirstShiftSummaryRow($row) ? (float) ($row->shortage ?? 0) : 0;
                    return $this->formatShiftSummaryCurrency($value, 'shortage', $business_details);
                })
                ->addColumn('excess', function ($row) use ($business_details) {
                    $value = $this->isFirstShiftSummaryRow($row) ? (float) ($row->excess ?? 0) : 0;
                    return $this->formatShiftSummaryCurrency($value, 'excess', $business_details);
                })
                ->addColumn('total_amount', function ($row) use ($business_details) {
                    $value = 0;

                    if ($this->isFirstShiftSummaryRow($row)) {
                        $value = (float) ($row->credit_sale ?? 0)
                            + (float) ($row->cards ?? 0)
                            + (float) ($row->cash ?? 0)
                            + (float) ($row->cheque ?? 0)
                            + (float) ($row->other ?? 0)
                            + (float) ($row->shortage ?? 0)
                            + (float) ($row->excess ?? 0);
                    }

                    return $this->formatShiftSummaryCurrency($value, 'total_amount', $business_details);
                })
                ->editColumn('settlement_no', function ($row) {
                    return ! empty($row->settlement_no) ? e($row->settlement_no) : '-';
                })
                ->addColumn('difference', function ($row) use ($business_details) {
                    $payments = 0;
                    $other_sales = 0;

                    if ($this->isFirstShiftSummaryRow($row)) {
                        $payments = (float) ($row->credit_sale ?? 0)
                            + (float) ($row->cards ?? 0)
                            + (float) ($row->cash ?? 0)
                            + (float) ($row->cheque ?? 0)
                            + (float) ($row->other ?? 0)
                            + (float) ($row->shortage ?? 0)
                            + (float) ($row->excess ?? 0);
                        $other_sales = (float) ($row->other_sales ?? 0);
                    }

                    $sold_amount = (float) ($row->amount ?? 0) + $other_sales;
                    $difference = $payments - $sold_amount;
                    $class = $difference < 0 ? 'difference text-red' : 'difference';

                    return $this->formatShiftSummaryCurrency($difference, $class, $business_details);
                })
                ->removeColumn('id');

            return $fuel_tanks->rawColumns([
                'action',
                'sold_ltr',
                'amount',
                'other_sales',
                'credit_sale',
                'cards',
                'cash',
                'cheque',
                'other',
                'shortage',
                'excess',
                'total_amount',
                'settlement_no',
                'difference',
            ])->make(true);
        }
    }

    private function isFirstShiftSummaryRow($row): bool
    {
        return (int) ($row->id ?? 0) === (int) ($row->first_entry_id ?? 0);
    }

    private function formatShiftSummaryCurrency(float $value, string $class, $business_details): string
    {
        return '<span class="display_currency ' . e($class) . '" data-orig-value="' . $value . '" data-currency_symbol="false">'
            . $this->productUtil->num_f($value, false, $business_details, true) . '</span>';
    }

    public function create()
    {
        abort(404);
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        abort(404);
    }

    public function edit($id)
    {
        abort(404);
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}

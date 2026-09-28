<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;;
use Modules\PetroPD\Entities\DailyCollection;
use Modules\PetroPD\Entities\PumpOperatorMeterSale;
use Modules\PetroPD\Entities\PumpOperatorMeterSaleDetail;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorPayment;
use Yajra\DataTables\Facades\DataTables;
use Modules\PetroPD\Entities\PumpOperatorOtherSale;
use Modules\PetroPD\Services\PdOperatorReportScopeService;
use Modules\PetroPD\Services\PdOperatorReportRowService;

class PDClosingShiftController extends Controller
{
    private function authorizePumperDashboardPermission(string $permission): void
    {
        $user = Auth::user();

        if (
            ! $user->can('pump_operator.dashboard') &&
            ! $user->can($permission) &&
            (empty($user->is_pump_operator) || empty($user->pump_operator_id))
        ) {
            abort(403, 'Unauthorized Access');
        }
    }


    /**
     * Resolve the active business selected in the ERP session.  This must be
     * identical to the business used by the PD Operators parent page.
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
     * Resolve the requested Close Shift date range. The PD Operators tab must
     * default to the current date, while the pump-operator standalone page can
     * keep loading an explicitly selected historical shift without silently
     * applying today's date.
     */
    private function resolveDateRange(Request $request, bool $defaultToToday = true): array
    {
        $startDate = trim((string) $request->input('start_date', ''));
        $endDate = trim((string) $request->input('end_date', ''));

        if ($startDate === '' && $defaultToToday) {
            $startDate = Carbon::today()->toDateString();
        }
        if ($endDate === '' && $defaultToToday) {
            $endDate = Carbon::today()->toDateString();
        }

        try {
            $startDate = $startDate !== '' ? Carbon::parse($startDate)->toDateString() : '';
        } catch (\Throwable $e) {
            $startDate = Carbon::today()->toDateString();
        }

        try {
            $endDate = $endDate !== '' ? Carbon::parse($endDate)->toDateString() : '';
        } catch (\Throwable $e) {
            $endDate = Carbon::today()->toDateString();
        }

        if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    /**
     * Build the authoritative list of shifts available to the Close Shift tab.
     * A shift is considered to have worked in a date range when its assignment
     * date is in that range, falling back safely to whichever Petro Shift date
     * column exists in the tenant database.
     */
    private function filteredShiftQuery(
        int $businessId,
        string $startDate = '',
        string $endDate = '',
        int $pumpOperatorId = 0,
        bool $activeOperatorsOnly = true
    ) {
        /*
         * A Petro Shift can carry the operator directly, while historical
         * records can carry the authoritative operator only on the pump
         * assignments. Build one assignment summary row per shift first so the
         * same resolved operator and shift number are used by filters and data.
         */
        $assignmentSummary = DB::table('pump_operator_assignments as poa_scope')
            ->where('poa_scope.business_id', $businessId)
            ->select('poa_scope.shift_id')
            ->selectRaw('MAX(NULLIF(poa_scope.pump_operator_id, 0)) AS assignment_operator_id');

        if (Schema::hasColumn('pump_operator_assignments', 'shift_number')) {
            $assignmentSummary->selectRaw('MAX(NULLIF(poa_scope.shift_number, 0)) AS assignment_shift_number');
        } else {
            $assignmentSummary->selectRaw('NULL AS assignment_shift_number');
        }

        $assignmentDateParts = [];
        if (Schema::hasColumn('pump_operator_assignments', 'date_and_time')) {
            $assignmentDateParts[] = 'MAX(DATE(poa_scope.date_and_time))';
        }
        if (Schema::hasColumn('pump_operator_assignments', 'created_at')) {
            $assignmentDateParts[] = 'MAX(DATE(poa_scope.created_at))';
        }

        $assignmentSummary->selectRaw(
            ! empty($assignmentDateParts)
                ? 'COALESCE(' . implode(', ', $assignmentDateParts) . ') AS assignment_date'
                : 'NULL AS assignment_date'
        )->groupBy('poa_scope.shift_id');

        $resolvedOperatorExpression = 'COALESCE(NULLIF(poa_filter.assignment_operator_id, 0), NULLIF(ps.pump_operator_id, 0))';

        $dateParts = ['poa_filter.assignment_date'];
        foreach (['transaction_date', 'shift_date', 'date', 'start_date_and_time', 'date_and_time', 'created_at'] as $column) {
            if (Schema::hasColumn('petro_shifts', $column)) {
                $dateParts[] = 'DATE(ps.' . $column . ')';
            }
        }
        $dateExpression = 'COALESCE(' . implode(', ', $dateParts) . ', CURRENT_DATE())';

        $shiftNumberParts = ['NULLIF(poa_filter.assignment_shift_number, 0)'];
        foreach (['shift_number', 'shift_no'] as $column) {
            if (Schema::hasColumn('petro_shifts', $column)) {
                $shiftNumberParts[] = 'NULLIF(ps.' . $column . ', 0)';
            }
        }
        $shiftNumberParts[] = 'ps.id';
        $shiftNumberExpression = 'COALESCE(' . implode(', ', $shiftNumberParts) . ')';

        $query = DB::table('petro_shifts as ps')
            ->leftJoinSub($assignmentSummary, 'poa_filter', function ($join) {
                $join->on('poa_filter.shift_id', '=', 'ps.id');
            })
            ->join('pump_operators as po_filter', function ($join) use ($resolvedOperatorExpression, $businessId) {
                $join->on('po_filter.id', '=', DB::raw($resolvedOperatorExpression))
                    ->where('po_filter.business_id', $businessId);
            })
            ->where('ps.business_id', $businessId)
            ->selectRaw('ps.id')
            ->selectRaw($resolvedOperatorExpression . ' AS pump_operator_id')
            ->selectRaw('po_filter.name AS operator_name')
            ->selectRaw($shiftNumberExpression . ' AS display_shift_number')
            ->selectRaw((Schema::hasColumn('petro_shifts', 'status') ? 'ps.status' : "''") . ' AS status')
            ->selectRaw($dateExpression . ' AS filter_date');

        if ($activeOperatorsOnly && Schema::hasColumn('pump_operators', 'active')) {
            $query->where('po_filter.active', 1);
        }

        if ($pumpOperatorId > 0) {
            $query->whereRaw($resolvedOperatorExpression . ' = ?', [$pumpOperatorId]);
        }

        if ($startDate !== '' && $endDate !== '') {
            $query->whereRaw($dateExpression . ' BETWEEN ? AND ?', [$startDate, $endDate]);
        }

        return $query
            ->orderByDesc('filter_date')
            ->orderByDesc('display_shift_number')
            ->orderByDesc('ps.id');
    }

    /**
     * Resolve one explicitly selected shift without relying on a stale direct
     * operator link. This is a guarded fallback for historical tenants where
     * the assignment is the authoritative shift/operator relationship.
     */
    private function selectedShiftContext(
        int $businessId,
        int $shiftId,
        int $requestedOperatorId = 0
    ): ?object {
        $shift = DB::table('petro_shifts')
            ->where('business_id', $businessId)
            ->where('id', $shiftId)
            ->first();

        if (! $shift) {
            return null;
        }

        $assignments = DB::table('pump_operator_assignments')
            ->where('business_id', $businessId)
            ->where('shift_id', $shiftId)
            ->get();

        $assignmentOperatorIds = $assignments
            ->pluck('pump_operator_id')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $directOperatorId = (int) ($shift->pump_operator_id ?? 0);

        if ($requestedOperatorId > 0) {
            $belongsToRequestedOperator = $directOperatorId === $requestedOperatorId
                || $assignmentOperatorIds->contains($requestedOperatorId);

            if (! $belongsToRequestedOperator) {
                return null;
            }
            $operatorId = $requestedOperatorId;
        } else {
            $operatorId = (int) ($assignmentOperatorIds->first() ?: $directOperatorId);
        }

        if ($operatorId <= 0) {
            return null;
        }

        $operator = DB::table('pump_operators')
            ->where('business_id', $businessId)
            ->where('id', $operatorId)
            ->first();

        if (! $operator) {
            return null;
        }

        $displayShiftNumber = $assignments
            ->pluck('shift_number')
            ->map(static fn ($number) => (int) $number)
            ->filter()
            ->max();

        foreach (['shift_number', 'shift_no'] as $column) {
            if (empty($displayShiftNumber) && isset($shift->{$column}) && (int) $shift->{$column} > 0) {
                $displayShiftNumber = (int) $shift->{$column};
            }
        }

        return (object) [
            'id' => $shiftId,
            'pump_operator_id' => $operatorId,
            'operator_name' => (string) ($operator->name ?? ''),
            'display_shift_number' => $displayShiftNumber ?: $shiftId,
            'status' => $shift->status ?? '',
            'filter_date' => $shift->created_at ?? null,
        ];
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
        $only_pumper = request()->boolean('only_pumper');

        if ($only_pumper) {
            $this->authorizePumperDashboardPermission('pumper_dashboard.close_shift');
        }

        $business_id = $this->resolveBusinessId();
        $pump_operator_id = (int) (Auth::user()->pump_operator_id ?? 0);

        if (request()->ajax()) {
            $business_details = Business::find($business_id);
            $already_added_balance = [];
            $selected_shift_id = (int) request()->input('shift_id', 0);
            $requested_operator_id = (int) request()->input('pump_operator_id', 0);

            if ($only_pumper && $pump_operator_id > 0) {
                $requested_operator_id = $pump_operator_id;
            }

            $default_to_today = ! $only_pumper || $selected_shift_id <= 0;
            $scope_service = app(PdOperatorReportScopeService::class);
            [$start_date, $end_date] = $scope_service->resolveDateRange(
                request(),
                $default_to_today
            );

            /*
             * The report scope is resolved from the actual operational rows
             * written by Pumper Dashboard (assignments, day entries, meter
             * sales, payments and other sales). A specifically selected shift
             * is validated directly and is not lost because one historical
             * date/operator column is stale.
             */
            $selected_shifts = $scope_service->shifts(
                $business_id,
                $start_date,
                $end_date,
                $requested_operator_id,
                ! $only_pumper,
                $selected_shift_id
            );
            $selected_shift_ids = $selected_shifts
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($selected_shift_ids)) {
                return DataTables::of(collect())->make(true);
            }

            /*
             * One canonical loader is shared with Pumper Day Entries. It reads
             * assignment-linked and direct shift-linked rows separately and
             * resolves them in PHP, avoiding the fragile multi-join SQL path
             * which previously returned an empty DataTable.
             */
            $strict_operator_scope = $selected_shift_id <= 0 && $requested_operator_id > 0;
            try {
                $day_entry_rows = app(PdOperatorReportRowService::class)->dayEntryRows(
                    $business_id,
                    $selected_shift_ids,
                    $requested_operator_id,
                    $strict_operator_scope,
                    (int) request()->input('pump_id', 0)
                );
            } catch (\Throwable $e) {
                Log::error('PetroPD Close Shift operational rows could not be loaded', [
                    'business_id' => $business_id,
                    'shift_ids' => $selected_shift_ids,
                    'error' => $e->getMessage(),
                ]);
                $day_entry_rows = collect();
            }

            $other_sale_discount_column = null;
            if (Schema::hasColumn('pump_operator_other_sales', 'discount_amount')) {
                $other_sale_discount_column = 'discount_amount';
            } elseif (Schema::hasColumn('pump_operator_other_sales', 'discount')) {
                $other_sale_discount_column = 'discount';
            }

            $other_sale_selects = [
                'id',
                'business_id',
                'shift_id',
                'created_at',
                'sub_total',
            ];
            $other_sale_selects[] = $other_sale_discount_column
                ? $other_sale_discount_column . ' as discount_value'
                : DB::raw('0 as discount_value');

            $other_sale_rows = collect();
            if (empty(request()->pump_id) && Schema::hasTable('pump_operator_other_sales')) {
                try {
                    $other_sale_rows = DB::table('pump_operator_other_sales')
                        ->where('business_id', $business_id)
                        ->whereIn('shift_id', $selected_shift_ids)
                        ->select($other_sale_selects)
                        ->get();
                } catch (\Throwable $e) {
                    Log::warning('PetroPD Close Shift Other Sales rows were skipped', [
                        'business_id' => $business_id,
                        'shift_ids' => $selected_shift_ids,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $shift_meta = $selected_shifts->keyBy(static fn ($row) => (int) $row->id);

            $meter_rows = $day_entry_rows->map(static function ($row) {
                return (object) [
                    'id' => (int) ($row->id ?? $row->entry_id ?? $row->detail_id ?? 0),
                    'entry_id' => (int) ($row->entry_id ?? 0),
                    'name' => (string) ($row->pump_operator_name ?? 'Pump Operator'),
                    'date' => $row->date ?? $row->pde_date ?? $row->created_at,
                    'time' => $row->time ?? $row->created_at,
                    'pump_operator_id' => (int) ($row->pump_operator_id ?? 0),
                    'starting_meter' => $row->starting_meter ?? 0,
                    'closing_meter' => $row->closing_meter ?? 0,
                    'testing_ltr' => $row->testing_ltr ?? 0,
                    'sold_ltr' => $row->sold_ltr ?? $row->sold_qty ?? 0,
                    'amount' => $row->amount ?? 0,
                    'pump_id' => (int) ($row->pump_id ?? 0),
                    'pump_no' => (string) ($row->pump_no ?? ''),
                    'location_id' => (int) ($row->location_id ?? 0),
                    'location_name' => (string) ($row->location_name ?? ''),
                    'shift_number' => $row->shift_number ?? $row->shift_id,
                    'shift_id' => (int) ($row->shift_id ?? 0),
                    'settlement_no' => $row->settlement_no ?? null,
                    'entry_type' => 'meter',
                ];
            });

            $other_operator_ids = $selected_shifts
                ->pluck('pump_operator_id')
                ->push($requested_operator_id)
                ->map(static fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();
            $operators = $other_operator_ids->isEmpty()
                ? collect()
                : DB::table('pump_operators')
                    ->where('business_id', $business_id)
                    ->whereIn('id', $other_operator_ids)
                    ->select('id', 'name', 'location_id')
                    ->get()
                    ->keyBy(static fn ($row) => (int) $row->id);
            $location_ids = $operators->pluck('location_id')->filter()->unique()->values();
            $locations = $location_ids->isEmpty()
                ? collect()
                : DB::table('business_locations')
                    ->where('business_id', $business_id)
                    ->whereIn('id', $location_ids)
                    ->pluck('name', 'id');

            $other_rows = $other_sale_rows->map(function ($row) use ($shift_meta, $operators, $locations) {
                $row_shift_id = (int) $row->shift_id;
                $meta = $shift_meta->get($row_shift_id);
                $operator_id = (int) ($meta->pump_operator_id ?? 0);
                $operator = $operators->get($operator_id);

                return (object) [
                    'id' => (int) $row->id,
                    'entry_id' => 0,
                    'name' => (string) ($operator->name ?? $meta->operator_name ?? 'Pump Operator'),
                    'date' => $row->created_at,
                    'time' => $row->created_at,
                    'pump_operator_id' => $operator_id,
                    'starting_meter' => null,
                    'closing_meter' => null,
                    'testing_ltr' => null,
                    'sold_ltr' => null,
                    'amount' => (float) ($row->sub_total ?? 0) - (float) ($row->discount_value ?? 0),
                    'pump_id' => null,
                    'pump_no' => 'Other Sale',
                    'location_id' => (int) ($operator->location_id ?? 0),
                    'location_name' => (string) ($locations[(int) ($operator->location_id ?? 0)] ?? ''),
                    'shift_number' => $meta->display_shift_number ?? $row_shift_id,
                    'shift_id' => $row_shift_id,
                    'settlement_no' => null,
                    'entry_type' => 'other_sale',
                ];
            });

            $combined = $meter_rows
                ->concat($other_rows)
                ->filter(function ($row) {
                    /*
                     * Operator/date filtering has already been applied to the
                     * authoritative selected Shift IDs. Do not filter the final
                     * rows again using stale direct day-entry operator values.
                     */
                    if (! empty(request()->location_id)
                        && (int) $row->location_id !== (int) request()->location_id) {
                        return false;
                    }

                    return true;
                })
                ->sortBy(function ($row) {
                    $value = trim((string) ($row->date ?? '') . ' ' . (string) ($row->time ?? ''));
                    $timestamp = strtotime($value);

                    return sprintf('%020d-%010d', $timestamp === false ? 0 : $timestamp, (int) $row->id);
                })
                ->values();

            $fuel_tanks = DataTables::of($combined)
                ->addColumn('action', function ($row) {
                    if (($row->entry_type ?? '') === 'other_sale'
                        || (int) ($row->entry_id ?? 0) <= 0) {
                        return '';
                    }

                    $html = '<div class="btn-group">'
                        . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                        . __('messages.actions') . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>'
                        . '<ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if (empty(auth()->user()->pump_operator_id)) {
                        if (! empty($row->settlement_no)) {
                            $html .= '<li class="disabled"><a href="#" onclick="return false;"><i class="fa fa-lock"></i> ' . __('messages.edit') . '</a></li>';
                        } else {
                            $html .= '<li><a data-href="' . action('\\Modules\\PetroPD\\Http\\Controllers\\PDPumperDayEntryController@edit', [$row->id]) . '" class="btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-pencil-square-o"></i> ' . __('messages.edit') . '</a></li>';
                            $html .= '<li><a data-href="' . action('\\Modules\\PetroPD\\Http\\Controllers\\PDPumperDayEntryController@getAddSettlementNo', [$row->id]) . '" class="btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-plus"></i> ' . __('petropd::lang.add_settlement_no') . '</a></li>';
                        }
                    }

                    return $html . '</ul></div>';
                })
                ->editColumn('date', function ($row) {
                    return ! empty($row->date) ? $this->commonUtil->format_date($row->date) : '—';
                })
                ->editColumn('time', function ($row) {
                    if (empty($row->time)) {
                        return '—';
                    }
                    try {
                        return Carbon::parse($row->time)->format('h:i A');
                    } catch (\Throwable $e) {
                        return (string) $row->time;
                    }
                })
                ->editColumn('starting_meter', function ($row) {
                    return is_null($row->starting_meter) ? '—' : number_format((float) $row->starting_meter, 3);
                })
                ->editColumn('closing_meter', function ($row) {
                    return is_null($row->closing_meter) ? '—' : number_format((float) $row->closing_meter, 3);
                })
                ->editColumn('testing_ltr', function ($row) use ($business_details) {
                    if (is_null($row->testing_ltr)) {
                        return '—';
                    }
                    $value = (float) $row->testing_ltr;
                    return '<span class="display_currency testing_ltr" data-orig-value="' . $value . '" data-currency_symbol="false">'
                        . $this->productUtil->num_f($value, false, $business_details, true) . '</span>';
                })
                ->editColumn('sold_ltr', function ($row) use ($business_details) {
                    if (is_null($row->sold_ltr)) {
                        return '—';
                    }
                    $value = (float) $row->sold_ltr;
                    return '<span class="display_currency sold_ltr" data-orig-value="' . $value . '" data-currency_symbol="false">'
                        . $this->productUtil->num_f($value, false, $business_details, true) . '</span>';
                })
                ->editColumn('amount', function ($row) use ($business_details) {
                    $value = (float) ($row->amount ?? 0);
                    return '<span class="display_currency sold_amount" data-orig-value="' . $value . '" data-currency_symbol="false">'
                        . $this->productUtil->num_f($value, false, $business_details, true) . '</span>';
                })
                ->addColumn('short_amount', function ($row) use ($business_id, $business_details, &$already_added_balance) {
                    $operator_id = (int) ($row->pump_operator_id ?? 0);
                    $row_shift_id = (int) ($row->shift_id ?? 0);
                    $key = $operator_id . ':' . $row_shift_id;

                    if ($operator_id <= 0 || $row_shift_id <= 0 || isset($already_added_balance[$key])) {
                        return '';
                    }
                    $already_added_balance[$key] = true;

                    try {
                        $payments = PumpOperatorPayment::where('business_id', $business_id)
                            ->where('pump_operator_id', $operator_id)
                            ->where('shift_id', $row_shift_id)
                            ->selectRaw('SUM(CASE WHEN LOWER(payment_type) = "shortage" THEN payment_amount ELSE 0 END) as short_amount')
                            ->selectRaw('SUM(CASE WHEN LOWER(payment_type) = "excess" THEN payment_amount ELSE 0 END) as excess_amount')
                            ->first();
                    } catch (\Throwable $e) {
                        Log::warning('PetroPD Close Shift shortage/excess row was skipped', [
                            'business_id' => $business_id,
                            'pump_operator_id' => $operator_id,
                            'shift_id' => $row_shift_id,
                            'error' => $e->getMessage(),
                        ]);
                        return '';
                    }

                    $excess = (float) ($payments->excess_amount ?? 0);
                    $shortage = (float) ($payments->short_amount ?? 0);
                    if ($excess != 0.0) {
                        return '<span class="display_currency short_amount" data-orig-value="' . $excess . '" data-currency_symbol="false">'
                            . $this->productUtil->num_f($excess, false, $business_details, true) . '</span>';
                    }
                    if ($shortage != 0.0) {
                        return '<span class="display_currency short_amount text-red" data-orig-value="' . $shortage . '" data-currency_symbol="false">'
                            . $this->productUtil->num_f($shortage, false, $business_details, true) . '</span>';
                    }

                    return '';
                })
                ->removeColumn('location_id');

            return $fuel_tanks
                ->rawColumns(['action', 'testing_ltr', 'sold_ltr', 'amount', 'short_amount'])
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

        $assignment_shift_number = DB::raw('(SELECT MAX(poa.shift_number) FROM pump_operator_assignments poa WHERE poa.shift_id = petro_shifts.id AND poa.business_id = petro_shifts.business_id) AS assignment_shift_number');
        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', '=', 'petro_shifts.pump_operator_id')
            ->where('petro_shifts.business_id', $business_id)
            ->select('pump_operators.name', 'petro_shifts.*', $assignment_shift_number)
            ->orderByDesc('petro_shifts.id');

        if ($only_pumper) {
            $shifts->where('pump_operator_id', $pump_operator_id);
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

        return view('petropd::pd_operators.actions.closing_shift')->with(compact(
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
     * Dependent filter options for Petro PD > PD Operators > Close Shift.
     * Pump Operators are limited to Active operators who worked in the selected
     * range. Shift No is then limited to the selected operator, or all matching
     * shifts when Pump Operator is set to All.
     */
    public function filterOptions(Request $request)
    {
        $business_id = $this->resolveBusinessId();
        $only_pumper = $request->boolean('only_pumper');
        $authenticated_operator_id = (int) (Auth::user()->pump_operator_id ?? 0);

        if ($only_pumper) {
            $this->authorizePumperDashboardPermission('pumper_dashboard.close_shift');
        }

        $scope_service = app(PdOperatorReportScopeService::class);
        [$start_date, $end_date] = $scope_service->resolveDateRange($request, true);
        $selected_operator_id = (int) $request->input('pump_operator_id', 0);

        if ($only_pumper && $authenticated_operator_id > 0) {
            $selected_operator_id = $authenticated_operator_id;
        }

        $active_only = ! $only_pumper;
        $operator_shift_rows = $scope_service->shifts(
            $business_id,
            $start_date,
            $end_date,
            $only_pumper ? $selected_operator_id : 0,
            $active_only
        );

        $operators = $operator_shift_rows
            ->map(static function ($row) {
                return [
                    'id' => (int) $row->pump_operator_id,
                    'text' => (string) $row->operator_name,
                ];
            })
            ->filter(static function ($row) {
                return $row['id'] > 0 && $row['text'] !== '';
            })
            ->unique('id')
            ->sortBy('text', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        if (
            ! $only_pumper
            && $selected_operator_id > 0
            && ! $operators->contains('id', $selected_operator_id)
        ) {
            $selected_operator_id = 0;
        }

        $shift_rows = $selected_operator_id > 0
            ? $scope_service->shifts(
                $business_id,
                $start_date,
                $end_date,
                $selected_operator_id,
                $active_only
            )
            : $operator_shift_rows;

        $shifts = $shift_rows
            ->map(static function ($row) use ($selected_operator_id) {
                $status_value = strtolower(trim((string) ($row->status ?? '')));
                $is_closed = ((int) ($row->status ?? 0) === 2)
                    || in_array($status_value, ['closed', 'close', 'shift closed'], true);
                $number = (string) ($row->display_shift_number ?? $row->id);
                $operator_name = trim((string) ($row->operator_name ?? ''));
                $text = 'Shift ' . $number;

                if ($selected_operator_id <= 0 && $operator_name !== '') {
                    $text = $operator_name . ' - ' . $text;
                }

                $text .= $is_closed ? ' (Shift Closed)' : ' (Shift Open)';

                return [
                    'id' => (int) $row->id,
                    'pump_operator_id' => (int) $row->pump_operator_id,
                    'text' => $text,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'selected_operator_id' => $selected_operator_id,
            'operators' => $operators,
            'shifts' => $shifts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        abort(404);
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


        return view('petropd::pd_operators.actions.edit_closing_shift')->with(compact(
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

        return view('petropd::pd_operators.actions.view_closing_shift')->with(compact(
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
                'msg' => __('petropd::lang.success')
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

    /**
     * IS1759-09: printable A4 landscape statement for one closed PD shift.
     *
     * The old browser/DataTable print included the application page, browser
     * title/footer and large blank areas.  This endpoint builds the statement
     * directly from the selected immutable Petro Shift ID and renders only the
     * meter and other-sale details that belong to that shift.
     */
    public function printClosedShiftStatement($shift_id)
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.close_shift');

        $business_id = $this->resolveBusinessId();
        $shift_id = (int) $shift_id;

        $shift = PetroShift::where('business_id', $business_id)
            ->where('id', $shift_id)
            ->firstOrFail();

        if ((int) ($shift->status ?? 0) !== 2) {
            abort(409, 'The shift must be closed before printing this statement.');
        }

        $assignment_operator_ids = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            ->whereNotNull('pump_operator_id')
            ->where('pump_operator_id', '>', 0)
            ->pluck('pump_operator_id')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $logged_operator_id = (int) (Auth::user()->pump_operator_id ?? 0);
        if (
            $logged_operator_id > 0
            && (int) ($shift->pump_operator_id ?? 0) !== $logged_operator_id
            && ! $assignment_operator_ids->contains($logged_operator_id)
        ) {
            abort(403, 'Unauthorized Access');
        }

        $business = Business::findOrFail($business_id);

        $requested_operator_id = (int) request()->input('pump_operator_id', 0);
        $assignment_operator_id = (int) ($assignment_operator_ids->first() ?? 0);
        $requested_operator_belongs_to_shift = $requested_operator_id > 0
            && (
                $assignment_operator_ids->contains($requested_operator_id)
                || (int) ($shift->pump_operator_id ?? 0) === $requested_operator_id
            );
        $resolved_operator_id = $requested_operator_belongs_to_shift
            ? $requested_operator_id
            : ($assignment_operator_id ?: (int) ($shift->pump_operator_id ?? 0));
        $pump_operator = PumpOperator::where('business_id', $business_id)
            ->where('id', $resolved_operator_id)
            ->first();
        $business_location = ! empty($pump_operator?->location_id)
            ? BusinessLocation::where('business_id', $business_id)
                ->where('id', $pump_operator->location_id)
                ->first()
            : null;

        $shift_number = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            ->min('shift_number');
        $shift_number = $shift_number ?: $shift_id;

        $has_direct_shift_id = Schema::hasColumn('pumper_day_entries', 'shift_id');

        $meter_sales = PumperDayEntry::query()
            ->leftJoin('pump_operator_assignments as statement_assignments', 'statement_assignments.id', '=', 'pumper_day_entries.pumper_assignment_id')
            ->leftJoin('pumps', 'pumps.id', '=', 'pumper_day_entries.pump_id')
            ->leftJoin('pumps as assigned_pumps', 'assigned_pumps.id', '=', 'statement_assignments.pump_id')
            ->leftJoin('products', 'products.id', '=', 'pumps.product_id')
            ->leftJoin('products as assigned_products', 'assigned_products.id', '=', 'assigned_pumps.product_id')
            ->where(function ($business_query) use ($business_id) {
                $business_query->where('pumper_day_entries.business_id', $business_id)
                    ->orWhere('statement_assignments.business_id', $business_id);
            })
            ->where(function ($query) use ($shift_id, $has_direct_shift_id) {
                $query->where('statement_assignments.shift_id', $shift_id);
                if ($has_direct_shift_id) {
                    $query->orWhere('pumper_day_entries.shift_id', $shift_id);
                }
            })
            ->select(
                'pumper_day_entries.id',
                DB::raw('COALESCE(NULLIF(pumper_day_entries.pump_no, ""), NULLIF(pumps.pump_no, ""), NULLIF(assigned_pumps.pump_no, ""), pumps.pump_name, assigned_pumps.pump_name, CONCAT("Pump #", COALESCE(pumper_day_entries.pump_id, statement_assignments.pump_id))) as pump_display'),
                DB::raw('COALESCE(products.name, assigned_products.name) as product_name'),
                'pumper_day_entries.starting_meter',
                'pumper_day_entries.closing_meter',
                'pumper_day_entries.testing_ltr',
                'pumper_day_entries.sold_ltr',
                'pumper_day_entries.amount'
            )
            ->orderBy('statement_assignments.id')
            ->orderBy('pumper_day_entries.id')
            ->get();

        $other_sales = PumpOperatorOtherSale::query()
            ->leftJoin('products', 'products.id', '=', 'pump_operator_other_sales.product_id')
            ->leftJoin('units', 'units.id', '=', 'products.unit_id')
            ->where('pump_operator_other_sales.business_id', $business_id)
            ->where('pump_operator_other_sales.shift_id', $shift_id)
            ->select(
                'pump_operator_other_sales.id',
                'products.name as product_name',
                DB::raw('COALESCE(units.short_name, units.actual_name, "") as unit_name'),
                'pump_operator_other_sales.qty',
                'pump_operator_other_sales.price',
                'pump_operator_other_sales.sub_total',
                DB::raw('COALESCE(pump_operator_other_sales.discount_amount, 0) as discount_amount'),
                DB::raw('(COALESCE(pump_operator_other_sales.sub_total, 0) - COALESCE(pump_operator_other_sales.discount_amount, 0)) as net_amount')
            )
            ->orderBy('pump_operator_other_sales.id')
            ->get();

        $meter_total = (float) $meter_sales->sum('amount');
        $other_total = (float) $other_sales->sum('net_amount');
        $grand_total = $meter_total + $other_total;

        return view('petropd::pd_operators.print.closed_pumps_statement', compact(
            'business',
            'business_location',
            'pump_operator',
            'shift',
            'shift_number',
            'meter_sales',
            'other_sales',
            'meter_total',
            'other_total',
            'grand_total'
        ));
    }

    public function closeShift($shift_id)
    {
        $business_id = $this->resolveBusinessId();
          // 1️⃣ Close assignments
       PumpOperatorAssignment::where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            ->update(['status' => 'close', 'close_date_and_time' => \Carbon::now(), 'is_manually_closed' => 1]);
       
            $assignments = PumpOperatorAssignment::where('business_id', $business_id)
    ->where('shift_id', $shift_id)
    ->where('closed_in_settlement', 0)
    ->get();
   // 2️⃣ Close shift 
        $shift = PetroShift::where('business_id', $business_id)->findOrFail($shift_id);
        $shift->status = 2;
        $shift->closed_time = date('Y-m-d H:i');
        $shift->save();
 
        foreach ($assignments as $ass) {
            $pump_operator_id = $ass->pump_operator_id ?: $shift->pump_operator_id;
            if (empty($pump_operator_id)) {
                continue;
            }
   // 3️⃣ Get all day entries for this shift
        $entries = PumperDayEntry::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('pumper_assignment_id', $ass->id)
                ->where('closed_in_settlement', 0)
            ->get();
 if ($entries->isNotEmpty()) {

            // 4️⃣ Generate collection_form_no
            $lastPayment = PumpOperatorPayment::where('business_id', $business_id)
                ->whereNotNull('collection_form_no')
                ->latest('id')
                ->value('collection_form_no');

            $lastDaily = DailyCollection::where('business_id', $business_id)
                ->whereNotNull('collection_form_no')
                ->latest('id')
                ->value('collection_form_no');

            $collection_form_no = max((int)$lastPayment, (int)$lastDaily) + 1;

            // Prevent duplicate meter sale creation if the same shift is closed more than once.
            $existingMeterSale = PumpOperatorMeterSale::where('business_id', $business_id)
                ->where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->first();

            if ($existingMeterSale) {
                continue;
            }

            // 5️⃣ Create master meter sale
            $meterSale = PumpOperatorMeterSale::create([
                'business_id'        => $business_id,
                'date_time'          => now(),
                'pump_operator_id'   => $pump_operator_id,
                'amount'             => $entries->sum('amount'),
                'deposited'          => 0,
                'balance'            => $entries->sum('amount'),
                'collection_form_no' => $collection_form_no,
                'shift_id'           => $shift_id,
                'testing_qty'        => $entries->sum('testing_ltr'),
                'source'             => 'closing',
            ]);

            // 6️⃣ Create detail rows automatically
            foreach ($entries as $entry) {

                PumpOperatorMeterSaleDetail::create([
                    'sale_id'          => $meterSale->id,
                    'business_id'      => $business_id,
                    'pump_operator_id' => $pump_operator_id,
                    'pump_id'          => $entry->pump_id,
                    'received_meter'   => $entry->starting_meter,
                    'new_meter'        => $entry->closing_meter,
                    'sold_qty'         => $entry->sold_ltr,
                    'unit_price'       => $entry->sold_ltr > 0
                        ? $entry->amount / $entry->sold_ltr
                        : 0,
                    'amount'           => $entry->amount,
                ]);
            }
        }
        }
        $output = [
            'success' => 1,
            'msg' => __('lang_v1.success')
        ];
        return redirect()->to('/petropd/pd-operators')->with('status', $output);
    }
}

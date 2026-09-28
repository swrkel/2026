<?php
namespace Modules\PetroPD\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Product;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorOtherSale;
use Modules\PetroPD\Entities\PumpOperatorPayment;
use Modules\PetroPD\Services\PdOperatorReportRowService;
use Modules\PetroPD\Services\PdOperatorReportScopeService;
use Yajra\DataTables\Facades\DataTables;

class PDPumperDayEntryController extends Controller
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


    private function authorizeDailyStatusMaintenance(string $permission): void
    {
        $user = Auth::user();

        // Preserve the existing endpoint permission contract because these
        // edit/delete methods are also used by the Pumper Day Entries workflow.
        // IS1799 restricts only the Daily Pump Status action buttons below.
        if (empty($user)
            || (! $user->can('superadmin')
                && ! $user->can($permission))) {
            abort(403, 'Unauthorized Access');
        }
    }

    /**
     * Resolve the business currently selected in the tenant session.
     * Every PD Operators tab must read the same business used by the
     * Pumper Dashboard and Assign Pumps workflow.
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
     * Resolve the Pumper Day Entries date range. The management tab defaults
     * to the current date. The standalone pump-operator page keeps its
     * existing selected-shift behaviour unless a date range is supplied.
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
            $startDate = $defaultToToday ? Carbon::today()->toDateString() : '';
        }

        try {
            $endDate = $endDate !== '' ? Carbon::parse($endDate)->toDateString() : '';
        } catch (\Throwable $e) {
            $endDate = $defaultToToday ? Carbon::today()->toDateString() : '';
        }

        if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate];
    }

    /**
     * Return a safe DATE expression using only columns that exist in the
     * active tenant database.
     */
    private function dateExpression(string $table, string $alias, array $columns): string
    {
        $parts = [];

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                $parts[] = 'DATE(' . $alias . '.' . $column . ')';
            }
        }

        return empty($parts)
            ? 'CURRENT_DATE()'
            : 'COALESCE(' . implode(', ', $parts) . ')';
    }

    /**
     * Build the dependent Operator / Shift option source used by the Pumper
     * Day Entries management tab. Operators are limited to active operators
     * who worked in the selected date range.
     */
    private function filteredDayEntryShiftQuery(
        int $businessId,
        string $startDate = '',
        string $endDate = '',
        int $pumpOperatorId = 0,
        bool $activeOperatorsOnly = true
    ) {
        $dateParts = [];
        if (Schema::hasColumn('pump_operator_assignments', 'date_and_time')) {
            $dateParts[] = 'MAX(DATE(poa_filter.date_and_time))';
        }
        if (Schema::hasColumn('pump_operator_assignments', 'created_at')) {
            $dateParts[] = 'MAX(DATE(poa_filter.created_at))';
        }

        foreach (['transaction_date', 'shift_date', 'date', 'start_date_and_time', 'date_and_time', 'created_at'] as $column) {
            if (Schema::hasColumn('petro_shifts', $column)) {
                $dateParts[] = 'MAX(DATE(ps.' . $column . '))';
            }
        }

        if (empty($dateParts)) {
            $dateParts[] = 'CURRENT_DATE()';
        }

        $dateExpression = 'COALESCE(' . implode(', ', $dateParts) . ')';

        $shiftNumberParts = [];
        if (Schema::hasColumn('pump_operator_assignments', 'shift_number')) {
            $shiftNumberParts[] = 'NULLIF(MAX(poa_filter.shift_number), 0)';
        }
        foreach (['shift_number', 'shift_no'] as $column) {
            if (Schema::hasColumn('petro_shifts', $column)) {
                $shiftNumberParts[] = 'NULLIF(MAX(ps.' . $column . '), 0)';
            }
        }
        $shiftNumberParts[] = 'ps.id';
        $shiftNumberExpression = 'COALESCE(' . implode(', ', $shiftNumberParts) . ')';

        $statusExpression = Schema::hasColumn('petro_shifts', 'status')
            ? 'MAX(ps.status)'
            : "''";

        $query = DB::table('petro_shifts as ps')
            ->join('pump_operators as po_filter', 'po_filter.id', '=', 'ps.pump_operator_id')
            ->leftJoin('pump_operator_assignments as poa_filter', function ($join) use ($businessId) {
                $join->on('poa_filter.shift_id', '=', 'ps.id')
                    ->where('poa_filter.business_id', $businessId);
            })
            ->where('ps.business_id', $businessId)
            ->where('po_filter.business_id', $businessId)
            ->selectRaw('ps.id')
            ->selectRaw('ps.pump_operator_id')
            ->selectRaw('MAX(po_filter.name) AS operator_name')
            ->selectRaw($shiftNumberExpression . ' AS display_shift_number')
            ->selectRaw($statusExpression . ' AS status')
            ->selectRaw($dateExpression . ' AS filter_date')
            ->groupBy('ps.id', 'ps.pump_operator_id');

        if ($activeOperatorsOnly && Schema::hasColumn('pump_operators', 'active')) {
            $query->where('po_filter.active', 1);
        }

        if ($pumpOperatorId > 0) {
            $query->where('ps.pump_operator_id', $pumpOperatorId);
        }

        if ($startDate !== '' && $endDate !== '') {
            $query->havingRaw($dateExpression . ' BETWEEN ? AND ?', [$startDate, $endDate]);
        }

        return $query
            ->orderByDesc('filter_date')
            ->orderByDesc('display_shift_number')
            ->orderByDesc('ps.id');
    }

    /**
     * Resolve the exact shift IDs represented by the management filters. Once
     * the date/operator filters have selected shifts, all report sources are
     * filtered by those IDs instead of applying different date columns to each
     * table. This keeps Meter Sales, Day Entries and Payments in one scope.
     */
    private function matchingShiftIds(
        Request $request,
        int $businessId,
        int $pumpOperatorId,
        bool $onlyPumper
    ): array {
        $requestedShiftId = (int) $request->input('shift_id', 0);
        $shiftFieldWasSent = $request->exists('shift_id');

        if ($onlyPumper && ! $shiftFieldWasSent && $requestedShiftId <= 0) {
            $latest = PetroShift::where('business_id', $businessId)
                ->when($pumpOperatorId > 0, function ($query) use ($pumpOperatorId) {
                    $query->where(function ($operatorScope) use ($pumpOperatorId) {
                        $operatorScope->where('pump_operator_id', $pumpOperatorId)
                            ->orWhereExists(function ($assignmentQuery) use ($pumpOperatorId) {
                                $assignmentQuery->selectRaw('1')
                                    ->from('pump_operator_assignments as poa_latest')
                                    ->whereColumn('poa_latest.shift_id', 'petro_shifts.id')
                                    ->where('poa_latest.pump_operator_id', $pumpOperatorId);
                            });
                    });
                })
                ->orderByDesc('id')
                ->value('id');

            return $latest ? [(int) $latest] : [];
        }

        [$startDate, $endDate] = $this->resolveDateRange($request, ! $onlyPumper);

        return app(PdOperatorReportScopeService::class)
            ->shifts(
                $businessId,
                $startDate,
                $endDate,
                $pumpOperatorId,
                ! $onlyPumper,
                $requestedShiftId
            )
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * All Utils instance.
     *
     */
    protected $productUtil;
    protected $moduleUtil;
    protected $transactionUtil;
    protected $commonUtil;
    protected $businessUtil;

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
            $this->authorizePumperDashboardPermission('pumper_dashboard.day_entries');
        }

        $business_id = $this->resolveBusinessId();

        $only_pumper = filter_var(request()->only_pumper, FILTER_VALIDATE_BOOLEAN);
        $pump_operator_id = (int) (Auth::user()->pump_operator_id ?? 0);
        $selected_pump_operator_id = $only_pumper
            ? $pump_operator_id
            : (int) request()->input('pump_operator_id', 0);
        if (request()->ajax()) {

            $already_added_shortage = [];
            $already_added_excess = [];

            $business_details = Business::find($business_id);

            $selected_shift_ids = $this->matchingShiftIds(
                request(),
                $business_id,
                $selected_pump_operator_id,
                $only_pumper
            );

            if (empty($selected_shift_ids)) {
                return DataTables::of(collect())->make(true);
            }

            $data = new Collection();

            // IS1806: load operational Meter Sale / Day Entry records through one
            // canonical relationship resolver. Tenant databases contain a mixture
            // of direct shift_id rows, assignment-linked rows and meter-sale rows;
            // the former SQL join path could silently discard valid entries.
            $operational_rows = app(PdOperatorReportRowService::class)->dayEntryRows(
                $business_id,
                $selected_shift_ids,
                $selected_pump_operator_id,
                false,
                (int) request()->input('pump_id', 0)
            );
            $data->put('pumper_day_entries', $operational_rows);

            // Parcel 3: all financial rows are read from the one-row-per-master
            // payment read model. Supporting tables provide metadata only.
            $has_card_meta_columns = Schema::hasColumn('pump_operator_payments', 'customer_id')
                && Schema::hasColumn('pump_operator_payments', 'slip_no');
            $payment_read = app(\Modules\PetroPD\Services\SettlementPaymentQueryService::class);
            $authoritative_payment_amount = $payment_read->authoritativeAmountSql();
            $payment_order_date = $this->dateExpression(
                'pump_operator_payments',
                'pump_operator_payments',
                ['transaction_date', 'date_and_time', 'created_at']
            );

            try {
                $credit_payments = $payment_read->paymentSummaryBaseQuery($business_id, $has_card_meta_columns)
                    ->when($selected_pump_operator_id > 0, function ($query) use ($selected_pump_operator_id) {
                        $query->where('pump_operator_payments.pump_operator_id', $selected_pump_operator_id);
                    })
                    ->whereIn('pump_operator_payments.shift_id', $selected_shift_ids)
                    ->whereIn(DB::raw('LOWER(pump_operator_payments.payment_type)'), ['credit', 'multiple_credit'])
                    ->select(
                        'scsp.id',
                        'pump_operator_payments.business_id',
                        'pump_operator_payments.pump_operator_id',
                        'pump_operator_payments.shift_id',
                        DB::raw('COALESCE(pump_operator_assignments.shift_number, pump_operator_payments.shift_id) as shift_number'),
                        'pump_operator_payments.settlement_no as pop_settlement_no',
                        'pump_operator_payments.settlement_no',
                        DB::raw($authoritative_payment_amount . ' as amount'),
                        DB::raw($payment_order_date . ' as order_date'),
                        'pump_operator_payments.created_at',
                        'pump_operator_payments.updated_at',
                        'scsp.order_number',
                        'scsp.customer_id',
                        'scsp.customer_reference',
                        'contacts_credit.name as customer_name',
                        'pump_operators.name as pump_operator_name',
                        'business_locations.name as location_name'
                    )
                    ->orderBy('pump_operator_payments.id')
                    ->get();

            } catch (\Throwable $paymentException) {
                Log::warning('PetroPD Pumper Day Entries credit rows could not be loaded; operational rows will still be shown.', [
                    'business_id' => $business_id,
                    'shift_ids' => $selected_shift_ids,
                    'error' => $paymentException->getMessage(),
                ]);
                $credit_payments = collect();
            }

            $data->put('settlement_credit_sale_payments', $credit_payments);

            try {
                $card_payments = $payment_read->paymentSummaryBaseQuery($business_id, $has_card_meta_columns)
                    ->when($selected_pump_operator_id > 0, function ($query) use ($selected_pump_operator_id) {
                        $query->where('pump_operator_payments.pump_operator_id', $selected_pump_operator_id);
                    })
                    ->whereIn('pump_operator_payments.shift_id', $selected_shift_ids)
                    ->whereIn(DB::raw('LOWER(pump_operator_payments.payment_type)'), ['card', 'cards'])
                    ->select(
                        'dc.id',
                        'dc.slip_no',
                        DB::raw($authoritative_payment_amount . ' as amount'),
                        DB::raw($authoritative_payment_amount . ' as pop_amount'),
                        'pump_operator_payments.business_id',
                        'pump_operator_payments.pump_operator_id',
                        'pump_operator_payments.shift_id',
                        DB::raw('COALESCE(pump_operator_assignments.shift_number, pump_operator_payments.shift_id) as shift_number'),
                        'pump_operator_payments.settlement_no as pop_settlement_no',
                        'pump_operator_payments.settlement_no',
                        'pump_operator_payments.created_at',
                        'pump_operator_payments.updated_at',
                        DB::raw('COALESCE(contacts_card.name, contacts_card_legacy.name) as customer_name'),
                        'pump_operators.name as pump_operator_name',
                        'business_locations.name as location_name'
                    )
                    ->orderBy('pump_operator_payments.id')
                    ->get();

            } catch (\Throwable $paymentException) {
                Log::warning('PetroPD Pumper Day Entries card rows could not be loaded; operational rows will still be shown.', [
                    'business_id' => $business_id,
                    'shift_ids' => $selected_shift_ids,
                    'error' => $paymentException->getMessage(),
                ]);
                $card_payments = collect();
            }

            $data->put('settlement_card_payments', $card_payments);

            try {
                $cash_payments = $payment_read->paymentSummaryBaseQuery($business_id, $has_card_meta_columns)
                    ->when($selected_pump_operator_id > 0, function ($query) use ($selected_pump_operator_id) {
                        $query->where('pump_operator_payments.pump_operator_id', $selected_pump_operator_id);
                    })
                    ->whereIn('pump_operator_payments.shift_id', $selected_shift_ids)
                    ->where('pump_operator_payments.payment_type', 'cash')
                    ->select(
                        'pump_operator_payments.id',
                        'pump_operator_payments.business_id',
                        'pump_operator_payments.shift_id',
                        'pump_operator_payments.date_and_time',
                        'pump_operator_payments.collection_form_no',
                        'pump_operator_payments.collection_form_no as slip_no',
                        'pump_operator_payments.settlement_no',
                        'pump_operator_payments.payment_type',
                        DB::raw($authoritative_payment_amount . ' as payment_amount'),
                        'pump_operator_payments.note',
                        'pump_operator_payments.created_at',
                        'pump_operator_payments.updated_at',
                        'pump_operators.name as pump_operator_name',
                        'pump_operators.id as pump_operator_id',
                        'business_locations.name as location_name',
                        DB::raw('NULL as customer_name'),
                        DB::raw('COALESCE(pump_operator_assignments.shift_number, pump_operator_payments.shift_id) as shift_number')
                    )
                    ->orderBy('pump_operator_payments.id')
                    ->get();

            } catch (\Throwable $paymentException) {
                Log::warning('PetroPD Pumper Day Entries cash rows could not be loaded; operational rows will still be shown.', [
                    'business_id' => $business_id,
                    'shift_ids' => $selected_shift_ids,
                    'error' => $paymentException->getMessage(),
                ]);
                $cash_payments = collect();
            }

            $data->put('pump_cash_payments', $cash_payments);

            $data->put('pump_assignments', collect());

            if (env('PETRO_PUMPER_DAY_ENTRIES_DEBUG', false)) {
                Log::debug('PumperDayEntryController@index data', $data->toArray());
            }

            $finalCollection = collect();

            $mappedDayEntries = $data['pumper_day_entries']->map(function ($item) {

                if (env('PETRO_PUMPER_DAY_ENTRIES_DEBUG', false)) {
                    Log::debug('Pumper day entry row', [
                        'pump_operator_id' => $item->pump_operator_id,
                        'pump_id' => $item->pump_id,
                        'testing_ltr' => $item->testing_ltr,
                    ]);
                }

                return (object)[
                'id' => $item->entry_id ?? $item->detail_id ?? $item->sale_id ?? $item->id ?? null,
                'business_id' => $item->business_id,
                'pump_operator_id' => $item->pump_operator_id,
                'date' => $item->settlement_datetime ?? $item->settlement_transaction_date ?? $item->pde_date ?? $item->created_at,
                'time' => $item->settlement_datetime ?? $item->settlement_transaction_date ?? $item->pde_date ?? $item->created_at,
                'date_and_time' => $item->settlement_datetime ?? $item->settlement_transaction_date ?? $item->pde_date ?? $item->created_at,
                'pump_id' => $item->pump_id,
                'pump_no' => $item->pump_no ?? '',

                'starting_meter' => number_format((float)($item->starting_meter ?? 0), 3, '.', ''),
                'closing_meter' => number_format((float)($item->closing_meter ?? 0), 3, '.', ''),

                'sold_ltr' => $item->sold_qty,
                'testing_ltr' => number_format((float)$item->testing_ltr, 3, '.', ''),
                'amount' => $item->amount,
                'settlement_datetime' => $item->settlement_datetime ?? null,
                'settlement_no' => $item->settlement_no,
                'settlement_added_by' => '',
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at ?? $item->created_at,
                'closed_in_settlement' => '',
                'pumper_assignment_id' => $item->pumper_assignment_id ?? null,
                'order_number' => '-',
                'slip_no' => '-',
                'customer_name' => $item->customer_name ?? '-',
                'vehicle_number' => $item->customer_reference ?? '-',
                'pump_operator_name' => $item->pump_operator_name,
                'location_name' => $item->location_name ?? '-',
                'shift_id' => $item->shift_id,
                'shift_number' => $item->shift_number ?? $item->shift_id,
                'credit_sale' => 0,
                'pumper_assignment_id' => $item->assignment_id ?? $item->pumper_assignment_id,
                'assignment_status' => $item->assignment_status ?? null,
                'is_confirmed' => $item->is_confirmed ?? null,
                'shift_status' => $item->shift_status ?? null,
                'row_type' => (($item->source_type ?? '') === 'assignment' ? 'assignment' : 'day_entry'),
                ];
            });

            if (env('PETRO_PUMPER_DAY_ENTRIES_DEBUG', false)) {
                Log::debug('Mapped pumper day entries', $mappedDayEntries->toArray());
            }

            $assignment_ids_with_entries = $mappedDayEntries->pluck('pumper_assignment_id')->filter()->unique()->toArray();

            $mappedAssignments = collect($data->get('pump_assignments', collect()))
                ->filter(function ($item) use ($assignment_ids_with_entries) {
                if (empty($item->assignment_id)) {
                    return true;
                }
                return !in_array($item->assignment_id, $assignment_ids_with_entries);
            })
                ->map(function ($item) {
                return (object)[
                'id' => $item->assignment_id,
                'business_id' => $item->business_id,
                'pump_operator_id' => $item->pump_operator_id,
                'date' => $item->date_and_time,
                'time' => $item->date_and_time,
                'date_and_time' => $item->date_and_time,
                'pump_id' => $item->pump_id,
                'pump_no' => $item->pump_no ?? '',
                'starting_meter' => number_format((float)($item->starting_meter ?? 0), 3, '.', ''),
                'closing_meter' => number_format((float)($item->closing_meter ?? 0), 3, '.', ''),
                'sold_ltr' => 0,
                'testing_ltr' => number_format(0, 3, '.', ''),
                'amount' => 0,
                'settlement_datetime' => '',
                'settlement_no' => '',
                'settlement_added_by' => '',
                'created_at' => $item->date_and_time,
                'updated_at' => $item->date_and_time,
                'closed_in_settlement' => '',
                'pumper_assignment_id' => $item->assignment_id,
                'order_number' => '-',
                'slip_no' => '-',
                'customer_name' => '-',
                'vehicle_number' => '-',
                'pump_operator_name' => $item->pump_operator_name,
                'location_name' => $item->location_name ?? '-',
                'shift_id' => $item->shift_id,
                'shift_number' => $item->shift_number ?? $item->shift_id,
                'credit_sale' => 0,
                'assignment_status' => $item->assignment_status,
                'is_confirmed' => $item->is_confirmed,
                'shift_status' => $item->shift_status,
                'row_type' => 'assignment',
                ];
            });

            $mappedCreditSales = $data['settlement_credit_sale_payments']->map(function ($item) {
                $settlement_no = $item->settlement_no ?? $item->pop_settlement_no ?? null;

                return (object)[
                'id' => $item->id,
                'business_id' => $item->business_id,
                'pump_operator_id' => $item->pump_operator_id,
                'date' => $item->order_date,
                'time' => $item->updated_at,
                'date_and_time' => $item->order_date ?? $item->updated_at,
                'pump_id' => '',
                'pump_no' => '',
                'starting_meter' => 0,
                'closing_meter' => 0,
                'testing_ltr' => 0,
                'sold_ltr' => 0,
                'amount' => $item->amount,
                'settlement_datetime' => '',
                'settlement_no' => $settlement_no,
                'settlement_added_by' => '',
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'closed_in_settlement' => '',
                'pumper_assignment_id' => '',
                'order_number' => $item->order_number,
                'slip_no' => '-',
                'customer_name' => $item->customer_name,
                'vehicle_number' => $item->customer_reference,
                'pump_operator_name' => $item->pump_operator_name,
                'location_name' => $item->location_name ?? '-',
                'shift_id' => $item->shift_id,
                'shift_number' => $item->shift_number,
                'credit_sale' => 1, // mark as credit
                'pumper_assignment_id' => null,
                'assignment_status' => null,
                'is_confirmed' => null,
                'shift_status' => null,
                'row_type' => 'credit_sale',
                ];
            });

            $mappedCardPayments = $data['settlement_card_payments']
                // ->unique('slip_no')
                ->map(function ($item) {
                $settlement_no = $item->settlement_no ?? $item->pop_settlement_no ?? null;

                return (object)[
                'id' => $item->id,
                'business_id' => $item->business_id,
                'pump_operator_id' => $item->pump_operator_id ?? null,
                'date' => $item->created_at,
                'time' => $item->updated_at,
                'date_and_time' => $item->created_at,
                'pump_id' => '',
                'pump_no' => '',
                'starting_meter' => 0,
                'closing_meter' => 0,
                'testing_ltr' => 0,
                'sold_ltr' => 0,
                'amount' => $item->pop_amount ?? $item->amount,
                'settlement_datetime' => '',
                'settlement_no' => $settlement_no,
                'settlement_added_by' => '',
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'closed_in_settlement' => '',
                'pumper_assignment_id' => '',
                'order_number' => '-',
                'slip_no' => $item->slip_no,
                'customer_name' => $item->customer_name,
                'vehicle_number' => '-',
                'pump_operator_name' => $item->pump_operator_name,
                'location_name' => $item->location_name ?? '-',
                'shift_id' => $item->shift_id,
                'shift_number' => $item->shift_number,
                'credit_sale' => 0,
                'pumper_assignment_id' => null,
                'assignment_status' => null,
                'is_confirmed' => null,
                'shift_status' => null,
                'row_type' => 'card_payment',
                ];
            });

            $mappedCashPayments = $data['pump_cash_payments']->map(function ($item) {
                return (object)[
                'id' => $item->id,
                'business_id' => $item->business_id,
                'pump_operator_id' => $item->pump_operator_id ?? null,
                'date' => $item->date_and_time,
                'time' => $item->date_and_time,
                'date_and_time' => $item->date_and_time,
                'pump_id' => '',
                'pump_no' => '',
                'starting_meter' => 0,
                'closing_meter' => 0,
                'testing_ltr' => 0,
                'sold_ltr' => 0,
                'amount' => $item->payment_amount,
                'settlement_datetime' => '',
                'settlement_no' => $item->settlement_no,
                'settlement_added_by' => '',
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
                'closed_in_settlement' => '',
                'pumper_assignment_id' => '',
                'order_number' => '-',
                'slip_no' => $item->slip_no,
                'customer_name' => $item->customer_name,
                'vehicle_number' => '-',
                'pump_operator_name' => $item->pump_operator_name,
                'location_name' => $item->location_name ?? '-',
                'shift_id' => $item->shift_id ?? $item->shift_number,
                'shift_number' => $item->shift_number,
                'credit_sale' => 0,
                'pumper_assignment_id' => null,
                'assignment_status' => null,
                'is_confirmed' => null,
                'shift_status' => null,
                'row_type' => 'cash_payment',
                ];
            });

            $finalCollection = collect()
                ->merge($mappedAssignments)
                ->merge($mappedDayEntries)
                ->merge($mappedCreditSales)
                ->merge($mappedCardPayments)
                ->merge($mappedCashPayments);

            $finalCollection = $finalCollection->filter(function ($item) use (
                $selected_shift_ids,
                $selected_pump_operator_id
            ) {
                if (! in_array((int) $item->shift_id, $selected_shift_ids, true)) {
                    return false;
                }
                if ($selected_pump_operator_id > 0
                    && (int) ($item->pump_operator_id ?? 0) > 0
                    && (int) $item->pump_operator_id !== $selected_pump_operator_id) {
                    return false;
                }
                if (!empty(request()->pump_id) && $item->pump_id != request()->pump_id) {
                    return false;
                }

                return true;
            });

            $all_settlement_identifiers = $finalCollection->pluck('settlement_no')
                ->filter()
                ->unique()
                ->values();

            $saved_settlement_ids = [];
            $saved_settlement_nos = [];
            if ($all_settlement_identifiers->isNotEmpty()) {
                $numeric_ids = $all_settlement_identifiers->filter(function ($v) {
                    return is_numeric($v);
                })->map(function ($v) {
                    return (int)$v;
                })->all();

                $string_nos = $all_settlement_identifiers->filter(function ($v) {
                    return !is_numeric($v);
                })->all();

                $query_settlements = DB::table('settlements')->where('status', 0);

                if (!empty($numeric_ids) && !empty($string_nos)) {
                    $query_settlements->where(function ($q) use ($numeric_ids, $string_nos) {
                        $q->whereIn('id', $numeric_ids)
                          ->orWhereIn('settlement_no', $string_nos);
                    });
                } elseif (!empty($numeric_ids)) {
                    $query_settlements->whereIn('id', $numeric_ids);
                } elseif (!empty($string_nos)) {
                    $query_settlements->whereIn('settlement_no', $string_nos);
                }

                $saved_settlements = $query_settlements->select('id', 'settlement_no')->get();
                $saved_settlement_ids = $saved_settlements->pluck('settlement_no', 'id')->toArray();
                $saved_settlement_nos = $saved_settlements->pluck('settlement_no')->unique()->toArray();
            }

            $finalCollection = $finalCollection->map(function ($item) use ($saved_settlement_ids, $saved_settlement_nos) {
                if (!empty($item->settlement_no)) {
                    if (is_numeric($item->settlement_no)) {
                        $settlement_id = (int)$item->settlement_no;
                        $item->settlement_no = $saved_settlement_ids[$settlement_id] ?? null;
                    } else {
                        if (!in_array($item->settlement_no, $saved_settlement_nos)) {
                            $item->settlement_no = null;
                        }
                    }
                }
                return $item;
            });

            $shortage_excess_query = PumpOperatorPayment::query()
                ->where('business_id', $business_id)
                ->whereIn('shift_id', $selected_shift_ids);

            $shortage_excess_by_operator = $shortage_excess_query
                ->whereIn('pump_operator_id', $finalCollection->pluck('pump_operator_id')->filter()->unique()->values())
                ->select(
                    'pump_operator_id',
                    DB::raw('SUM(IF(payment_type="shortage", payment_amount, 0)) as short_amount'),
                    DB::raw('SUM(IF(payment_type="excess", payment_amount, 0)) as excess_amount')
                )
                ->groupBy('pump_operator_id')
                ->get()
                ->keyBy('pump_operator_id');

            $fuel_tanks = DataTables::of($finalCollection)
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

                $is_admin_user = empty(auth()->user()->pump_operator_id);
                if ($is_admin_user && !empty($row->pumper_assignment_id)) {
                    $shift_closed = !empty($row->shift_status) && (int)$row->shift_status === 2;
                    $assignment_closed = !empty($row->assignment_status) && $row->assignment_status === 'close';
                    $received_by_pumper = !empty($row->is_confirmed);
                    $allow_assignment_edit = !$received_by_pumper && !$shift_closed && !$assignment_closed;

                    $tooltip = __('petropd::lang.edit_not_allowed_shift_open');
                    if ($received_by_pumper) {
                        $tooltip = __('petropd::lang.edit_not_allowed_after_receive');
                    }
                    elseif ($shift_closed || $assignment_closed) {
                        $tooltip = __('petropd::lang.edit_not_allowed_shift_closed');
                    }

                    if ($allow_assignment_edit) {
                        $html .= ' <li><a class="btn-modal" data-container=".pump_operator_modal" data-href="' .
                            action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorAssignmentController@edit', $row->pumper_assignment_id) .
                            '"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                    }
                    else {
                        $html .= ' <li class="disabled"><a href="#" class="text-muted" style="pointer-events: none; cursor: not-allowed;" title="' . e($tooltip) . '"><i class="fa fa-ban"></i> ' . __("messages.edit") . '</a></li>';
                    }
                }
                elseif ($is_admin_user && $row->row_type === 'day_entry' && empty($row->settlement_no)) {
                    $html .= ' <li><a data-href="' . url('/petropd/pumper-day-entry/' . $row->id . '/edit') . '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                }

                if ($row->row_type === 'day_entry' && empty($row->settlement_no)) {
                    $html .= ' <li><a data-href="' . url('/petropd/pumper-day-entry/' . $row->id . '/add-settlement-no') . '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-plus"></i> ' . __("petropd::lang.add_settlement_no") . '</a></li>';
                }

                $html .= '</ul></div>';

                return $html;
            }
            )
                ->addColumn('name', function ($row) {
                return $row->pump_operator_name ?? '—';

            })
                ->addColumn(
                'date',
                '{{@format_date($date)}}'
            )
                ->addColumn('slip_no', function ($row) {
                return $row->slip_no ?? '—';
            })
                ->editColumn(
                'time',
                '{{@format_time($time)}}'
            )
                ->editColumn(
                'settlement_no',
                function ($row) use ($business_details) {
                if (empty($row->settlement_no)) {
                    return '-';
                }
                // Only create link for day_entry rows that have a valid entry_id
                // For other row types (credit_sale, card_payment, cash_payment), just show the settlement_no
                if ($row->row_type === 'day_entry' && !empty($row->id)) {
                    return '<a data-href="' . url('/petropd/pumper-day-entry/' . $row->id . '/view-settlement-no') . '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal">' . $row->settlement_no . '</a>';
                }
                return $row->settlement_no;
            }
            )
                ->editColumn(
                'sold_ltr',
                function ($row) use ($business_details) {

                return '<span class="display_currency sold_ltr" data-orig-value="' . $row->sold_ltr . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->sold_ltr, false, $business_details, true) . '</span>';
            }
            )
                ->editColumn('testing_ltr', function ($row) use ($business_details) {
                return $this->productUtil->num_f($row->testing_ltr, false, $business_details, true);
            })->addColumn('credit_sale', function ($row) use ($business_details) {
                return '<span class="display_currency credit_sale" data-orig-value="' . $row->credit_sale . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->credit_sale, false, $business_details, true) . '</span>';
            })
                ->editColumn(
                'amount',
                function ($row) use ($business_details) {
                $amount_total_class = in_array($row->row_type, ['credit_sale', 'card_payment', 'cash_payment'])
                    ? 'payment_amount'
                    : 'sold_amount';
                $is_payment_row = in_array($row->row_type, ['credit_sale', 'card_payment', 'cash_payment']) ? 1 : 0;

                return '<span class="display_currency ' . $amount_total_class . '" data-row-type="' . e($row->row_type) . '" data-day-entry-payment="' . $is_payment_row . '" data-orig-value="' . $row->amount . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->amount, false, $business_details, true) . '</span>';
            }
            )
                ->addColumn('payment_amount_for_total', function ($row) {
                return in_array($row->row_type, ['credit_sale', 'card_payment', 'cash_payment', 'cheque_payment', 'other_payment'])
                    ? (float) $row->amount
                    : 0;
            })
                ->addColumn('short_amount', function ($row) use ($business_details, &$already_added_excess, &$already_added_shortage, $shortage_excess_by_operator) {

                $payments = $shortage_excess_by_operator[$row->pump_operator_id] ?? null;

                if (!empty($payments->excess_amount)) {
                    if (in_array($row->pump_operator_id, $already_added_excess)) {
                        return '';
                    }
                    else {
                        $already_added_excess[] = $row->pump_operator_id;
                    }

                    return '<span class="display_currency short_amount" data-orig-value="' . $payments->excess_amount . '" data-currency_symbol = false>' . $this->productUtil->num_f($payments->excess_amount, false, $business_details, true) . '</span>';
                }
                if (!empty($payments->short_amount)) {
                    if (in_array($row->pump_operator_id, $already_added_shortage)) {
                        return '';
                    }
                    else {
                        $already_added_shortage[] = $row->pump_operator_id;
                    }

                    return '<span class="display_currency short_amount text-red" data-orig-value="' . $payments->short_amount . '" data-currency_symbol = false>' . $this->productUtil->num_f($payments->short_amount, false, $business_details, true) . '</span>';
                }
            })
                ->addColumn('pump', function ($row) {
                return !empty($row->pump_no) ? $row->pump_no : '—';
            })

                ->removeColumn('id');

            return $fuel_tanks->rawColumns(['action', 'sold_ltr', 'amount', 'short_amount', 'short_amount', 'cash', 'cheque', 'total_amount', 'difference', 'settlement_no'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id);
        $pumps = Pump::where('business_id', $business_id)->get();
        if ($only_pumper) {
            $pump_operators = PumpOperator::where('business_id', $business_id)->where('id', $pump_operator_id)->pluck('name', 'id');
        }
        else {
            $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        }
        $payment_types = $this->transactionUtil->payment_types();

        $pump_operator = PumpOperator::find($pump_operator_id);

        $layout = 'app';
        if ($only_pumper) {
            $layout = 'pumper';
        }

        $assignmentShiftNumber = DB::raw('(SELECT MAX(CAST(poa.shift_number AS UNSIGNED)) FROM pump_operator_assignments poa WHERE poa.shift_id = petro_shifts.id AND poa.pump_operator_id = petro_shifts.pump_operator_id) as assignment_shift_number');
        $assignmentDate = DB::raw('(SELECT DATE(MIN(poa.date_and_time)) FROM pump_operator_assignments poa WHERE poa.shift_id = petro_shifts.id AND poa.pump_operator_id = petro_shifts.pump_operator_id) as assignment_date');
        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')->where('petro_shifts.business_id', $business_id)->select('pump_operators.name', 'petro_shifts.*', $assignmentShiftNumber, $assignmentDate)->orderBy('id', 'DESC');

        if ($only_pumper) {
            $shifts->where('pump_operator_id', $pump_operator_id);
        }

        $shifts = $shifts->get();

        $user = Auth::user();

        $pump_operator_id = $user->pump_operator_id;
        $selected_assignment = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
            ->where('business_id', $business_id)
            ->where('status', 'close')
            ->orderByRaw('CAST(shift_number AS UNSIGNED) DESC')
            ->orderBy('id', 'DESC')
            ->first();
        $selected_shift_id = $selected_assignment->shift_id ?? ($shifts->first()->id ?? null);
        $shift_number = $selected_assignment->shift_number
            ?? optional($shifts->firstWhere('id', $selected_shift_id))->assignment_shift_number
            ?? optional($shifts->firstWhere('id', $selected_shift_id))->shift_number
            ?? null;

        return view('petropd::pd_operators.pumper_day_entries')->with(compact(
            'layout',
            'business_locations',
            'pumps',
            'pump_operators',
            'pump_operator',
            'payment_types',
            'only_pumper',
            'shifts',
            'shift_number',
            'selected_shift_id'
        ));
    }

    /**
     * Dependent filter options for Petro PD > PD Operators > Pumper Day Entries.
     * Pump Operators are active operators who worked in the selected range.
     * Shift No is restricted to the selected operator, or shows all matching
     * shifts when Pump Operator remains All.
     */
    public function filterOptions(Request $request)
    {
        $business_id = $this->resolveBusinessId();
        [$start_date, $end_date] = $this->resolveDateRange($request, true);
        $selected_operator_id = (int) $request->input('pump_operator_id', 0);

        $operator_shift_rows = app(PdOperatorReportScopeService::class)->shifts(
            $business_id,
            $start_date,
            $end_date,
            0,
            true
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

        if ($selected_operator_id > 0 && ! $operators->contains('id', $selected_operator_id)) {
            $selected_operator_id = 0;
        }

        $shift_rows = $selected_operator_id > 0
            ? app(PdOperatorReportScopeService::class)->shifts(
                $business_id,
                $start_date,
                $end_date,
                $selected_operator_id,
                true
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

    public function getPumperDayEntrySummary()
    {
        $request = request();
        $only_pumper = filter_var($request->input('only_pumper'), FILTER_VALIDATE_BOOLEAN);
        $business_id = $this->resolveBusinessId();
        $authenticated_operator_id = (int) (Auth::user()->pump_operator_id ?? 0);
        $selected_operator_id = $only_pumper
            ? $authenticated_operator_id
            : (int) $request->input('pump_operator_id', 0);

        $selected_shift_ids = $this->matchingShiftIds(
            $request,
            $business_id,
            $selected_operator_id,
            $only_pumper
        );
        $has_day_entry_shift_id = Schema::hasColumn('pumper_day_entries', 'shift_id');

        $day_entries_query = PumperDayEntry::leftjoin('pump_operators', 'pumper_day_entries.pump_operator_id', 'pump_operators.id')
            ->leftjoin('pump_operator_assignments', 'pumper_day_entries.pumper_assignment_id', 'pump_operator_assignments.id')
            ->leftjoin('pumps', 'pumper_day_entries.pump_id', 'pumps.id')
            ->where('pumper_day_entries.business_id', $business_id)
            ->where(function ($query) use ($selected_shift_ids, $has_day_entry_shift_id) {
                if (empty($selected_shift_ids)) {
                    $query->whereRaw('1 = 0');
                    return;
                }

                $query->whereIn('pump_operator_assignments.shift_id', $selected_shift_ids);
                if ($has_day_entry_shift_id) {
                    $query->orWhereIn('pumper_day_entries.shift_id', $selected_shift_ids);
                }
            })
            ->when($selected_operator_id > 0, function ($query) use ($selected_operator_id) {
                $query->where(function ($operatorQuery) use ($selected_operator_id) {
                    $operatorQuery->where('pumper_day_entries.pump_operator_id', $selected_operator_id)
                        ->orWhere('pump_operator_assignments.pump_operator_id', $selected_operator_id);
                });
            })
            ->select('pump_operators.name', 'pumper_day_entries.*', 'pumps.pump_name');

        $day_entries = $day_entries_query->get()->unique('id')->values();
        $today_pumps = implode(', ', $day_entries->pluck('pump_name')->filter()->unique()->toArray());
        $meter_sales_total = (float) $day_entries->sum('amount');

        $other_sale_query = PumpOperatorOtherSale::query()
            ->where('business_id', $business_id);

        if (empty($selected_shift_ids)) {
            $other_sale_query->whereRaw('1 = 0');
        } else {
            $other_sale_query->whereIn('shift_id', $selected_shift_ids);
        }

        $discount_expression = Schema::hasColumn('pump_operator_other_sales', 'discount_amount')
            ? 'COALESCE(discount_amount, 0)'
            : (Schema::hasColumn('pump_operator_other_sales', 'discount')
                ? 'COALESCE(discount, 0)'
                : '0');

        $other_sale = (float) $other_sale_query
            ->select(DB::raw('SUM(COALESCE(sub_total, 0) - ' . $discount_expression . ') as total'))
            ->value('total');

        if (empty($selected_shift_ids)) {
            $payment_totals = [
                'cash' => 0,
                'card' => 0,
                'cheque' => 0,
                'credit' => 0,
                'other' => 0,
                'shortage_excess' => 0,
                'total' => 0,
                'total_paid' => 0,
            ];
        } else {
            $payment_totals = app(\Modules\PetroPD\Services\SettlementPaymentQueryService::class)
                ->totalsForScope(
                    $business_id,
                    $selected_operator_id > 0 ? $selected_operator_id : null,
                    $selected_shift_ids,
                    null,
                    null
                );
        }

        $payments = (object) [
            'cash' => $payment_totals['cash'],
            'card' => $payment_totals['card'],
            'cheque' => $payment_totals['cheque'],
            'credit' => $payment_totals['credit'],
            'other' => $payment_totals['other'],
            'shortage_excess' => $payment_totals['shortage_excess'],
            'total' => $payment_totals['total'],
            'total_paid' => $payment_totals['total_paid'],
        ];

        return view()->file(
            module_path('PetroPD', 'Resources/views/pd_operators/partials/pumper_day_entry_summary.blade.php'),
            compact(
                'day_entries',
                'today_pumps',
                'payments',
                'only_pumper',
                'other_sale',
                'meter_sales_total'
            )
        );
    }

    public function getClosingShiftSummary()
    {
        $business_id = $this->resolveBusinessId();
        $only_pumper = filter_var(request()->only_pumper, FILTER_VALIDATE_BOOLEAN);
        $selected_shift_id = (int) request()->shift_id;

        if ($selected_shift_id <= 0) {
            return response('', 204);
        }

        $this_shift = PetroShift::where('business_id', $business_id)
            ->where('id', $selected_shift_id)
            ->first();

        if (! $this_shift) {
            return response()->json([
                'message' => 'The selected shift was not found for the active business.',
            ], 404);
        }

        /*
         * Historical records can hold the authoritative operator on the pump
         * assignment even when petro_shifts.pump_operator_id is empty or stale.
         * Use the same resolved operator for summary, table, and print.
         */
        $assignments = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('shift_id', $selected_shift_id)
            ->get();

        $assignment_operator_ids = $assignments
            ->pluck('pump_operator_id')
            ->map(static fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $shift_operator_id = (int) ($this_shift->pump_operator_id ?? 0);
        $authenticated_operator_id = (int) (optional(Auth::user())->pump_operator_id ?? 0);

        if ($only_pumper) {
            $belongs_to_authenticated_operator = $authenticated_operator_id > 0
                && (
                    $shift_operator_id === $authenticated_operator_id
                    || $assignment_operator_ids->contains($authenticated_operator_id)
                );

            if (! $belongs_to_authenticated_operator) {
                abort(403, 'Unauthorized Access');
            }

            $pump_operator_id = $authenticated_operator_id;
        } else {
            $pump_operator_id = (int) ($assignment_operator_ids->first() ?: $shift_operator_id);
        }

        $pump_operator = $pump_operator_id > 0
            ? PumpOperator::where('business_id', $business_id)
                ->where('id', $pump_operator_id)
                ->first()
            : null;
        $pump_operator_name = (string) ($pump_operator->name ?? '');
        $business_name = (string) (Business::where('id', $business_id)->value('name') ?? '');

        $assignment_query = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('shift_id', $selected_shift_id)
            ->when($pump_operator_id > 0, function ($query) use ($pump_operator_id) {
                $query->where('pump_operator_id', $pump_operator_id);
            });

        $has_day_entry_shift_id = Schema::hasColumn('pumper_day_entries', 'shift_id');

        $day_entries_query = PumperDayEntry::from('pumper_day_entries as pde')
            ->leftJoin(
                'pump_operator_assignments as poa',
                'pde.pumper_assignment_id',
                '=',
                'poa.id'
            )
            ->leftJoin('pump_operators as direct_operator', 'pde.pump_operator_id', '=', 'direct_operator.id')
            ->leftJoin('pump_operators as assignment_operator', 'poa.pump_operator_id', '=', 'assignment_operator.id')
            ->leftJoin('pumps as direct_pump', 'pde.pump_id', '=', 'direct_pump.id')
            ->leftJoin('pumps as assigned_pump', 'poa.pump_id', '=', 'assigned_pump.id')
            ->where(function ($business_query) use ($business_id) {
                $business_query->where('pde.business_id', $business_id)
                    ->orWhere('poa.business_id', $business_id);
            })
            ->where(function ($query) use ($selected_shift_id, $has_day_entry_shift_id) {
                $query->where('poa.shift_id', $selected_shift_id);
                if ($has_day_entry_shift_id) {
                    $query->orWhere('pde.shift_id', $selected_shift_id);
                }
            })
            ->when($pump_operator_id > 0, function ($query) use ($pump_operator_id) {
                $query->where(function ($operator_query) use ($pump_operator_id) {
                    $operator_query->where('pde.pump_operator_id', $pump_operator_id)
                        ->orWhere('poa.pump_operator_id', $pump_operator_id);
                });
            })
            ->select(
                'pde.*',
                DB::raw("COALESCE(NULLIF(direct_operator.name, ''), NULLIF(assignment_operator.name, '')) as name"),
                DB::raw("COALESCE(NULLIF(direct_pump.pump_name, ''), NULLIF(direct_pump.pump_no, ''), NULLIF(assigned_pump.pump_name, ''), NULLIF(assigned_pump.pump_no, ''), CONCAT('Pump #', COALESCE(NULLIF(pde.pump_id, 0), NULLIF(poa.pump_id, 0)))) as pump_name")
            );

        $day_entries = $day_entries_query->get()->unique('id')->values();
        $meter_sales_total = (float) $day_entries->sum('amount');

        $pump_names = $day_entries->pluck('pump_name')->filter()->unique()->values();

        if ($pump_names->isEmpty() && Schema::hasTable('pump_operator_assignments')) {
            $assignment_pump_query = DB::table('pump_operator_assignments as poa')
                ->leftJoin('pumps as p', 'p.id', '=', 'poa.pump_id')
                ->where('poa.business_id', $business_id)
                ->where('poa.shift_id', $selected_shift_id)
                ->when($pump_operator_id > 0, function ($query) use ($pump_operator_id) {
                    $query->where('poa.pump_operator_id', $pump_operator_id);
                });

            $pump_label = Schema::hasColumn('pumps', 'pump_name')
                ? "COALESCE(NULLIF(p.pump_name, ''), NULLIF(p.pump_no, ''), CONCAT('Pump #', poa.pump_id))"
                : "COALESCE(NULLIF(p.pump_no, ''), CONCAT('Pump #', poa.pump_id))";

            $pump_names = $assignment_pump_query
                ->selectRaw($pump_label . ' as pump_name')
                ->pluck('pump_name')
                ->filter()
                ->unique()
                ->values();
        }

        $today_pumps = $pump_names->implode(', ');

        $payment_totals = app(\Modules\PetroPD\Services\SettlementPaymentQueryService::class)
            ->totalsForScope(
                $business_id,
                $pump_operator_id > 0 ? $pump_operator_id : null,
                [$selected_shift_id]
            );

        $payments = (object) [
            'cash' => $payment_totals['cash'],
            'card' => $payment_totals['card'],
            'cheque' => $payment_totals['cheque'],
            'credit' => $payment_totals['credit'],
            'other' => $payment_totals['other'],
            'shortage_excess' => $payment_totals['shortage_excess'],
            'total' => $payment_totals['total'],
            'total_paid' => $payment_totals['total_paid'],
        ];

        $other_sale_query = PumpOperatorOtherSale::where('shift_id', $selected_shift_id);
        if (Schema::hasColumn('pump_operator_other_sales', 'business_id')) {
            $other_sale_query->where('business_id', $business_id);
        }
        if ($pump_operator_id > 0 && Schema::hasColumn('pump_operator_other_sales', 'pump_operator_id')) {
            $other_sale_query->where('pump_operator_id', $pump_operator_id);
        }

        $other_sale_discount_expression = Schema::hasColumn('pump_operator_other_sales', 'discount_amount')
            ? 'COALESCE(discount_amount, 0)'
            : (Schema::hasColumn('pump_operator_other_sales', 'discount')
                ? 'COALESCE(discount, 0)'
                : '0');

        $other_sale = (float) $other_sale_query
            ->select(DB::raw(
                'SUM(COALESCE(sub_total, 0) - ' . $other_sale_discount_expression . ') as total'
            ))
            ->value('total');

        $shift_number = (int) (
            $assignments->pluck('shift_number')
                ->map(static fn ($number) => (int) $number)
                ->filter()
                ->max()
            ?: ($this_shift->shift_number ?? $this_shift->shift_no ?? $selected_shift_id)
        );

        $unconfirmed_pumps_count = Schema::hasColumn('pump_operator_assignments', 'is_confirmed')
            ? (clone $assignment_query)->where(function ($query) {
                $query->where('is_confirmed', 0)->orWhereNull('is_confirmed');
            })->count()
            : 0;

        $unclosed_pumps_count = (clone $assignment_query)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhereNotIn(DB::raw('LOWER(status)'), ['close', 'closed']);
            })
            ->count();

        return view()->file(
            module_path('PetroPD', 'Resources/views/pd_operators/partials/closing_shift_summary.blade.php'),
            compact(
                'day_entries',
                'today_pumps',
                'payments',
                'only_pumper',
                'this_shift',
                'other_sale',
                'unconfirmed_pumps_count',
                'unclosed_pumps_count',
                'shift_number',
                'selected_shift_id',
                'pump_operator',
                'pump_operator_name',
                'business_name',
                'meter_sales_total'
            )
        );
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
     */
    public function show($id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_id = $this->resolveBusinessId();
        $this->authorizeDailyStatusMaintenance('daily_pump_status.edit');

        $day_entry = PumperDayEntry::where('business_id', $business_id)
            ->findOrFail((int) $id);

        if (! empty($day_entry->settlement_no)) {
            abort(403, 'A settled day entry cannot be edited.');
        }

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $pumps = Pump::where('business_id', $business_id)->pluck('pump_no', 'id');
        $pump_details = Pump::leftJoin('products', 'pumps.product_id', '=', 'products.id')
            ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
            ->leftJoin('variation_location_details', 'variations.id', '=', 'variation_location_details.variation_id')
            ->where('pumps.business_id', $business_id)
            ->where('pumps.id', $day_entry->pump_id)
            ->select('default_sell_price', 'pumps.*', 'variation_location_details.qty_available')
            ->first();

        return view('petropd::pd_operators.partials.edit_pumper_day_entry')->with(compact(
            'day_entry',
            'pump_operators',
            'pumps',
            'pump_details'
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
        $business_id = $this->resolveBusinessId();
        $this->authorizeDailyStatusMaintenance('daily_pump_status.edit');

        try {
            $dayEntry = PumperDayEntry::where('business_id', $business_id)
                ->findOrFail((int) $id);

            if (! empty($dayEntry->settlement_no)) {
                return redirect()->back()->with('status', [
                    'success' => false,
                    'tab' => 'pumper_day_entries',
                    'msg' => 'A settled day entry cannot be edited.',
                ]);
            }

            $pump = Pump::where('business_id', $business_id)
                ->where('id', (int) $request->pump_id)
                ->firstOrFail();

            $operatorExists = PumpOperator::where('business_id', $business_id)
                ->where('id', (int) $request->pump_operator_id)
                ->exists();

            if (! $operatorExists) {
                return redirect()->back()->with('status', [
                    'success' => false,
                    'tab' => 'pumper_day_entries',
                    'msg' => 'The selected pump operator does not belong to the active business.',
                ]);
            }

            $data = [
                'date' => $this->transactionUtil->uf_date($request->date),
                'pump_operator_id' => (int) $request->pump_operator_id,
                'pump_id' => (int) $request->pump_id,
                'pump_no' => $pump->pump_no,
                'starting_meter' => $request->starting_meter,
                'closing_meter' => $request->closing_meter,
                'testing_ltr' => $this->transactionUtil->num_uf($request->testing_ltr),
                'sold_ltr' => $this->transactionUtil->num_uf($request->sold_ltr),
                'amount' => $this->transactionUtil->num_uf($request->amount),
            ];

            $dayEntry->update($data);

            $output = [
                'success' => true,
                'tab' => 'pumper_day_entries',
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Throwable $e) {
            Log::error('PetroPD day entry update failed', [
                'business_id' => $business_id,
                'day_entry_id' => (int) $id,
                'message' => $e->getMessage(),
            ]);
            $output = [
                'success' => false,
                'tab' => 'pumper_day_entries',
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $business_id = $this->resolveBusinessId();
        $this->authorizeDailyStatusMaintenance('daily_pump_status.delete');

        try {
            $entry = PumperDayEntry::where('business_id', $business_id)
                ->findOrFail((int) $id);

            if (! empty($entry->settlement_no)
                || (Schema::hasColumn('pumper_day_entries', 'closed_in_settlement')
                    && (int) $entry->closed_in_settlement === 1)) {
                return [
                    'success' => false,
                    'msg' => 'A settled day entry cannot be deleted.',
                ];
            }

            // Delete only the requested row. Removing every entry from the same
            // shift was unsafe in a multi-pump shift and could erase valid data.
            $entry->delete();

            return [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Throwable $e) {
            Log::error('PetroPD day entry delete failed', [
                'business_id' => $business_id,
                'day_entry_id' => (int) $id,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    public function viewAddSettlementNo($id)
    {
        $day_entry = PumperDayEntry::leftjoin('users', 'pumper_day_entries.settlement_added_by', 'users.id')->where('pumper_day_entries.id', $id)->select('pumper_day_entries.settlement_no', 'pumper_day_entries.settlement_datetime', 'users.username')->first();

        return view('petropd::pd_operators.partials.view_settlement_no')->with(compact('day_entry'));
    }

    public function getAddSettlementNo($id)
    {
        return view('petropd::pd_operators.partials.add_settlement_no')->with(compact('id'));
    }

    public function postAddSettlementNo($id, Request $request)
    {

        try {
            $data = [
                'settlement_datetime' => \Carbon::now(),
                'settlement_no' => $request->settlement_no,
                'settlement_added_by' => Auth::user()->id,
            ];

            PumperDayEntry::where('id', $id)->update($data);
            $output = [
                'success' => true,
                'tab' => 'pumper_day_entries',
                'msg' => __('lang_v1.success'),
            ];
        }
        catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'tab' => 'pumper_day_entries',
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }
    public function getDailyCollection()
    {
        // Use the same active-business context as Assign Pumps. In a
        // multi-business tenant, user.business_id may differ from the business
        // currently selected in the session, which previously made a newly
        // saved assignment disappear from this DataTable.
        $business_id = request()->session()->get('business.id')
            ?: request()->session()->get('user.business_id')
            ?: optional(Auth::user())->business_id;

        if (empty($business_id)) {
            abort(403, 'Business context not found. Please logout and login again.');
        }

        $business_id = (int) $business_id;
        $business_details = $this->businessUtil->getDetails($business_id);
        $pump_operator_id = Auth::user()->pump_operator_id;
        $only_pumper = request()->only_pumper;
        $date = Carbon::now()->toDateString();

        if (request()->ajax()) {

            // ✅ Totals calculation
            $totals = DB::table('pumper_day_entries')
                ->leftJoin('pumps', 'pumper_day_entries.pump_id', '=', 'pumps.id')
                ->where('pumper_day_entries.business_id', $business_id)
                ->whereDate('pumper_day_entries.date', $date)
                ->when($only_pumper, function ($q) use ($pump_operator_id) {
                return $q->where('pumper_day_entries.pump_operator_id', $pump_operator_id);
            })
                ->select(
                DB::raw('SUM(pumper_day_entries.sold_ltr) as total_sold_ltr'),
                DB::raw('SUM(pumper_day_entries.testing_ltr) as total_testing_ltr')
            )
                ->first();

            if (!$totals) {
                $totals = (object)[
                    'total_sold_ltr' => 0,
                    'total_testing_ltr' => 0,
                ];
            }

            // ✅ Calculate total sold amount
            $total_sold_amount = 0;
            if ($totals->total_sold_ltr > 0) {
                $product = Product::leftJoin('variations', 'products.id', '=', 'variations.product_id')
                    ->where('products.business_id', $business_id)
                    ->select('variations.sell_price_inc_tax')
                    ->first();
                if ($product) {
                    $total_sold_amount = $totals->total_sold_ltr * $product->sell_price_inc_tax;
                }
            }

            // ✅ Pump assignments + day entries for today
            // $pumps = DB::table('pump_operator_assignments as t1')
            //     ->leftJoin('pumps as t2', 't1.pump_id', '=', 't2.id')
            //     ->leftJoin('pump_operators as t3', 't1.pump_operator_id', '=', 't3.id')
            //     ->leftJoin('business_locations as t4', 't3.location_id', '=', 't4.id')
            //     ->leftJoin('pumper_day_entries as t5', function ($join) use ($date) {
            //         $join->on('t1.id', '=', 't5.pumper_assignment_id')
            //              ->whereDate('t5.date', $date);
            //     })
            //     ->where('t2.business_id', $business_id)
            //     // ->whereDate('t1.date_and_time', $date)
            //     ->when($only_pumper, function ($q) use ($pump_operator_id) {
            //         return $q->where('t1.pump_operator_id', $pump_operator_id);
            //     })
            //     ->select(
            //         't1.*',
            //         't5.date',
            //         't5.testing_ltr',
            //         't5.sold_ltr',
            //         't5.amount',
            //         't2.product_id',
            //         't2.pump_no',
            //         't3.name',
            //         't3.settlement_no',
            //         't4.name as location_name'
            //     )
            //     ->groupBy('t1.id')
            //     ->orderBy('t2.id')
            //     ->get();

            // Query assignments first so newly assigned pumps show even before day entry is created.
            // Copied tenant databases do not all use the same assignment date column,
            // so accept every supported date field and fall back to created_at.
            $assignmentDateColumns = [];
            foreach (['date_and_time', 'assignment_date', 'transaction_date', 'created_at'] as $column) {
                if (Schema::hasColumn('pump_operator_assignments', $column)) {
                    $assignmentDateColumns[] = 't1.' . $column;
                }
            }

            $dayEntryDateColumns = [];
            foreach (['date', 'date_and_time', 'transaction_date', 'created_at'] as $column) {
                if (Schema::hasColumn('pumper_day_entries', $column)) {
                    $dayEntryDateColumns[] = 't5.' . $column;
                }
            }

            $pumps = DB::table('pump_operator_assignments as t1')
                ->leftJoin('pumper_day_entries as t5', function ($join) use ($date, $dayEntryDateColumns) {
                $join->on('t5.pumper_assignment_id', '=', 't1.id');

                if (! empty($dayEntryDateColumns)) {
                    $join->where(function ($dateQuery) use ($date, $dayEntryDateColumns) {
                        foreach ($dayEntryDateColumns as $index => $column) {
                            if ($index === 0) {
                                $dateQuery->whereDate($column, $date);
                            } else {
                                $dateQuery->orWhereDate($column, $date);
                            }
                        }
                    });
                }
            })
                ->leftJoin('pumps as t2', 't1.pump_id', '=', 't2.id')
                ->leftJoin('pump_operators as t3', 't1.pump_operator_id', '=', 't3.id')
                ->leftJoin('business_locations as t4', 't3.location_id', '=', 't4.id')
                ->leftJoin('petro_shifts as t6', 't6.id', '=', 't1.shift_id')
                // The assignment row is the authoritative business scope.
                // Do not discard a valid current-business assignment merely because
                // a copied tenant's legacy pump row carries a different business_id.
                ->where('t1.business_id', $business_id)
                ->when(! empty($assignmentDateColumns), function ($query) use ($date, $assignmentDateColumns) {
                    $query->where(function ($dateQuery) use ($date, $assignmentDateColumns) {
                        foreach ($assignmentDateColumns as $index => $column) {
                            if ($index === 0) {
                                $dateQuery->whereDate($column, $date);
                            } else {
                                $dateQuery->orWhereDate($column, $date);
                            }
                        }
                    });
                })
                ->when($only_pumper, function ($q) use ($pump_operator_id) {
                return $q->where('t1.pump_operator_id', $pump_operator_id);
            })
                ->select(
                't1.id as assignment_id',
                DB::raw('COALESCE(t5.pump_operator_id, t1.pump_operator_id) as pump_operator_id'),
                DB::raw('COALESCE(t5.pump_id, t1.pump_id) as pump_id'),
                't1.status as assignment_status',
                't1.is_confirmed',
                't1.shift_id',
                't1.shift_number',
                DB::raw('COALESCE(t5.date, t1.date_and_time) as date_and_time'),
                't2.product_id',
                DB::raw("COALESCE(NULLIF(t2.pump_no, ''), CONCAT('Pump #', t1.pump_id)) as pump_no"),
                DB::raw("COALESCE(NULLIF(t3.name, ''), CONCAT('Operator #', t1.pump_operator_id)) as name"),
                't4.name as location_name',
                't5.id as day_entry_id',
                DB::raw("COALESCE(
                    t5.settlement_no,
                    (
                        SELECT s2.settlement_no
                        FROM settlements AS s2
                        WHERE s2.business_id = {$business_id}
                            AND s2.pump_operator_id = t1.pump_operator_id
                            AND (
                                s2.id = t1.settlement_id
                                OR s2.work_shift LIKE CONCAT('%', t1.shift_id, '%')
                            )
                            AND s2.settlement_no IS NOT NULL
                            AND s2.settlement_no != ''
                        ORDER BY s2.id DESC
                        LIMIT 1
                    ),
                    (
                        SELECT COALESCE(s.settlement_no, ms.settlement_no)
                        FROM meter_sales AS ms
                        LEFT JOIN settlements AS s
                            ON (s.id = ms.settlement_no OR s.settlement_no = ms.settlement_no)
                            AND s.business_id = {$business_id}
                        WHERE ms.business_id = {$business_id}
                            AND ms.shift_id = t1.shift_id
                            AND ms.pump_id = t1.pump_id
                            AND ms.settlement_no IS NOT NULL
                            AND ms.settlement_no != ''
                        ORDER BY ms.id DESC
                        LIMIT 1
                    )
                ) as settlement_no"),
                DB::raw('COALESCE(t6.status, 0) as shift_status'),
                DB::raw('COALESCE(t5.starting_meter, t1.starting_meter, 0) as starting_meter'),
                DB::raw('COALESCE(t5.closing_meter, t1.closing_meter, 0) as closing_meter'),
                DB::raw('COALESCE(t5.testing_ltr, 0) as testing_ltr'),
                DB::raw('COALESCE(t5.sold_ltr, 0) as sold_ltr'),
                DB::raw('COALESCE(t5.amount, 0) as total_amount')
            )
                ->orderBy('t2.id')
                ->orderByDesc('t1.id');

            // ✅ Datatables - pass query builder, not executed collection
            $daily_collections = DataTables::of($pumps)
                ->addColumn('action', function ($row) {
                $user = auth()->user();
                $is_superadmin = !empty($user) && $user->can('superadmin');

                // IS1799: Business users and Business Admins must not see any
                // Daily Pump Status maintenance controls.
                if (! $is_superadmin) {
                    return '';
                }

                $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                        data-toggle="dropdown" aria-expanded="false">'
                    . __("messages.actions") .
                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                $can_edit = true;
                $assignment_id = $row->assignment_id ?? null;
                $day_entry_id = $row->day_entry_id ?? null;
                $shift_closed = !empty($row->shift_status) && (int)$row->shift_status === 2;
                $assignment_closed = !empty($row->assignment_status) && $row->assignment_status === 'close';
                $received_by_pumper = !empty($row->is_confirmed);

                if ($can_edit && !empty($assignment_id)) {
                    $allow_assignment_edit = !$received_by_pumper && !$shift_closed && !$assignment_closed;
                    $tooltip = __('petropd::lang.edit_not_allowed_shift_open');
                    if ($received_by_pumper) {
                        $tooltip = __('petropd::lang.edit_not_allowed_after_receive');
                    }
                    elseif ($shift_closed || $assignment_closed) {
                        $tooltip = __('petropd::lang.edit_not_allowed_shift_closed');
                    }

                    if ($allow_assignment_edit) {
                        $html .= '<li><a class="btn-modal" data-container=".pump_operator_modal" data-href="' .
                            route('petropd.pump-assignments.edit', ['id' => $assignment_id]) .
                            '"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                    }
                    else {
                        $html .= '<li class="disabled"><a href="#" class="text-muted" style="pointer-events: none; cursor: not-allowed;" title="' . e($tooltip) . '"><i class="fa fa-ban"></i> ' . __("messages.edit") . '</a></li>';
                    }
                }

                if ($can_edit && !empty($day_entry_id) && empty($row->settlement_no) && empty(auth()->user()->pump_operator_id)) {
                    $html .= '<li><a data-href="' . route('petropd.day-entries.edit', ['id' => $day_entry_id]) .
                        '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                }

                if (!empty($assignment_id)) {
                    $html .= '<li><a href="' .
                        route('petropd.pump-assignments.destroy', ['id' => $assignment_id]) .
                        '" class="delete_daily_collection"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                }

                // if this row has an associated day entry id we also provide
                // a delete button specifically for that entry.  The JS handler
                // for `delete_daily_collection` will POST a DELETE request to
                // the controller below which will purge any related records
                // for both shift number and shift id.
                if (!empty($day_entry_id)) {
                    $html .= '<li><a href="' .
                        route('petropd.day-entries.destroy', ['id' => $day_entry_id]) .
                        '" class="delete_daily_collection"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                }

                $html .= '</ul></div>';
                return $html;
            })
                ->addColumn('date_and_time', function ($row) {
                if (!empty($row->date_and_time)) {
                    return Carbon::parse($row->date_and_time)->format('Y-m-d H:i');
                }
                return '';
            })
                ->addColumn('sold_ltr', fn($row) =>
            '<span class="display_currency footer_sold_fuel_qty" data-orig-value="' . $row->sold_ltr . '" data-currency_symbol=false>' .
            $this->productUtil->num_f($row->sold_ltr) . '</span>'
            )
                ->addColumn('testing_ltr', fn($row) =>
            '<span class="display_currency footer_testing_qty" data-orig-value="' . $row->testing_ltr . '" data-currency_symbol=false>' .
            $this->productUtil->num_f($row->testing_ltr) . '</span>'
            )
                ->addColumn('sold_amount', function ($row) use ($business_details) {
                $product = Product::leftJoin('variations', 'products.id', '=', 'variations.product_id')
                    ->where('products.id', $row->product_id)
                    ->select('variations.sell_price_inc_tax')
                    ->first();
                $amt = !empty($row->total_amount) ? $row->total_amount : ($row->sold_ltr * ($product ? $product->sell_price_inc_tax : 0));
                return '<span class="display_currency footer_sold_fuel_amount sold_amount" data-orig-value="' . $amt . '" data-currency_symbol=false>' .
                $this->commonUtil->num_f($amt, false, $business_details, false) . '</span>';
            })
                ->addColumn('shift_closed', function ($row) {
                $shift = PetroShift::find($row->shift_id);
                $closed = ($shift && (int) $shift->status === 2);
                $label = $closed ? 'Shift Closed' : 'Shift Open';
                $class = $closed ? 'label-success' : 'label-warning';
                return '<span class="label ' . $class . '" style="font-size:120%; padding:7px 10px; display:inline-block;">' . $label . '</span>';
            });

            return $daily_collections->rawColumns(['action', 'sold_ltr', 'testing_ltr', 'sold_amount', 'shift_closed'])
                ->with('total_sold_ltr', $this->round_normal($totals->total_sold_ltr))
                ->with('total_testing_ltr', $this->round_normal($totals->total_testing_ltr))
                ->with('total_sold_amount', $this->round_normal($total_sold_amount))
                ->make(true);
        }
    }

    // public function getDailyCollection()
// {
//     // dump("wetwet");exit;
//     $business_id = request()->session()->get('user.business_id');
//     $business_details = $this->businessUtil->getDetails($business_id);

    //     $only_pumper = request()->only_pumper;
//     $pump_operator_id = Auth::user()->pump_operator_id;

    //     // dump($only_pumper);exit;

    //     if (request()->ajax()) {
//         // Calculate total sold_ltr, testing_ltr and sold_amount
//         $totals = Pump::leftjoin('pumper_day_entries', function ($join) {
//                 $join->on('pumps.id', 'pumper_day_entries.pump_id')->whereDate('date', date('Y-m-d'));
//             })
//             ->where('pumps.business_id', $business_id)
//             ->whereDate('pumper_day_entries.date', date('Y-m-d'));

    //         if(!empty($only_pumper)){
//             $totals->where('pumper_day_entries.pump_operator_id', $pump_operator_id);
//         }

    //         $totals = $totals->select(
//             DB::raw('SUM(pumper_day_entries.sold_ltr) as total_sold_ltr'),
//             DB::raw('SUM(pumper_day_entries.testing_ltr) as total_testing_ltr')
//         )->first();

    //         // Calculate total sold_amount
//         $total_sold_amount = 0;
//         if ($totals->total_sold_ltr > 0) {
//             $product = Product::leftjoin('variations', 'products.id', 'variations.product_id')
//                 ->where('products.business_id', $business_id)
//                 ->first();
//             // $total_sold_amount = $totals->total_sold_ltr * $product->sell_price_inc_tax;
//         }

    //         // $pumps = Pump::leftjoin('pumper_day_entries', function ($join) {
//         //     $join->on('pumps.id', 'pumper_day_entries.pump_id')->whereDate('date', date('Y-m-d'));
//         // })->leftjoin('pump_operators', 'pumper_day_entries.pump_operator_id', 'pump_operators.id')
//         //     ->leftjoin('business_locations','business_locations.id','pump_operators.location_id')
//         //     ->leftjoin('pump_operator_assignments','pumper_day_entries.pump_operator_id','pump_operator_assignments.pump_operator_id')
//         //     ->leftjoin('pump_operator_assignments','pumper_day_entries.pump_id','pump_operator_assignments.pump_id')
//         //     ->where('pumps.business_id', $business_id)
//         //     ->whereDate('pumper_day_entries.date', date('Y-m-d'))
//         //     ->select('pumps.product_id', 'pumper_day_entries.*', 'pump_operators.name','business_locations.name as location_name', 'pump_operator_assignments.shift_number', 'pump_operator_assignments.id as assignment_id')
//         //     ->groupBy('pumper_day_entries.id')
//         //     ->orderBy('pumps.id');

    //          $date = date('Y-m-d');

    //         $pumps = DB::select("select t1.*,t5.date,t5.testing_ltr,t5.sold_ltr,t5.amount,t2.product_id,t3.name,t3.settlement_no,t2.pump_no,t4.name as location_name from pump_operator_assignments as t1 left join pumps as t2 on t1.pump_id = t2.id left join pump_operators as t3 on t1.pump_operator_id = t3.id left join business_locations as t4 on t3.location_id = t4.id left join pumper_day_entries as t5 on t1.id = t5.pumper_assignment_id group by t1.id order by t2.id");

    //         if(!empty($only_pumper)){
//             $pumps = collect($pumps)->filter(function ($pump) use ($pump_operator_id) {
//                 return $pump->pump_operator_id == $pump_operator_id &&
//                     date('Y-m-d', strtotime($pump->date)) === date('Y-m-d');
//             });
//         }

    //         $daily_collections = DataTables::of($pumps)
//             ->addColumn('action', function ($row) {
//                 $html = '<div class="btn-group">
//                         <button type="button" class="btn btn-info dropdown-toggle btn-xs"
//                             data-toggle="dropdown" aria-expanded="false">' .
//                     __("messages.actions") .
//                     '<span class="caret"></span><span class="sr-only">Toggle Dropdown
//                             </span>
//                         </button>
//                         <ul class="dropdown-menu dropdown-menu-left" role="menu"> ';
//                 if (auth()->user()->can('daily_pump_status.edit')) {
//                     $html .= '<li><a class="btn-modal" data-container=".pump_operator_modal" data-href="' . action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorAssignmentController@edit', $row->id) . '"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>' . __("messages.edit") . '</a></li> ';
//                 }
//                 if (auth()->user()->can('daily_pump_status.delete')) {
//                     $html .= '<li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorAssignmentController@destroy', $row->id) . '" class="delete_daily_collection"><i class="fa fa-trash"></i>' . __("messages.delete") . '</a></li>';
//                 }

    //                 $html .= '</ul></div>';
//                 return $html;
//             })
//             ->addColumn('sold_ltr', function($row){
//                 return '<span class="display_currency footer_sold_fuel_qty" data-orig-value="' . $row->sold_ltr . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->sold_ltr) . '</span>';
//             })
//             ->addColumn('testing_ltr', function($row){
//                 return '<span class="display_currency footer_testing_qty" data-orig-value="' . $row->testing_ltr . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->testing_ltr) . '</span>';
//             })
//             ->addColumn('sold_amount', function ($row) use ($business_details) {
//                 $product = Product::leftjoin('variations', 'products.id', 'variations.product_id')
//                     ->where('products.id', $row->product_id)->select('variations.sell_price_inc_tax')->first();
//                 $amt = ($row->sold_ltr) * $product->sell_price_inc_tax;
//                 return '<span class="display_currency footer_sold_fuel_amount sold_amount" data-orig-value="' . $amt . '" data-currency_symbol = false>' . $this->commonUtil->num_f($amt, false, $business_details, false) . '</span>';
//             })
//             ->removeColumn('id')
//             ->addColumn('date_and_time', function($row) {
//                 return $row->date_and_time;
//             })
//             ->addColumn('shift_closed', function($row){
//                 $shift = PetroShift::where("id", $row->shift_id)->select("status")->first();
//                 return (isset($shift->status) && $shift->status == "2") ? "Yes" : "No";
//             });

    //             // dump($daily_collections);exit;

    //         return $daily_collections->rawColumns(['action', 'sold_ltr', 'testing_ltr', 'sold_amount'])
//             ->with('total_sold_ltr', $this->round_normal($totals->total_sold_ltr))
//             ->with('total_testing_ltr', $this->round_normal($totals->total_testing_ltr))
//             ->with('total_sold_amount', $this->round_normal($total_sold_amount))
//             ->make(true);
//     }
// }

    private function round_normal($value)
    {
        return number_format((float)$value, 2, '.', '');
    }

// /**
//  * get the specified resource from storage.
//  *
//  * @return Renderable
//  */
// public function getDailyCollection()
// {

//     $business_id = request()->session()->get('user.business_id');
//     $business_details = $this->businessUtil->getDetails($business_id);

//     $only_pumper = request()->only_pumper;
//     $pump_operator_id = Auth::user()->pump_operator_id;

//     if (request()->ajax()) {
//         $pumps = Pump::leftjoin('pumper_day_entries', function ($join) {
//             $join->on('pumps.id', 'pumper_day_entries.pump_id')->whereDate('date', date('Y-m-d'));
//         })->leftjoin('pump_operators', 'pumper_day_entries.pump_operator_id', 'pump_operators.id')
//             ->leftjoin('business_locations','business_locations.id','pump_operators.location_id')
//             ->where('pumps.business_id', $business_id)
//             ->whereDate('pumper_day_entries.date', date('Y-m-d'))
//             ->select('pumps.product_id', 'pumper_day_entries.*', 'pump_operators.name','business_locations.name as location_name')->groupBy('pumper_day_entries.id')
//             ->orderBy('pumps.id');

//         if(!empty($only_pumper)){
//             $pumps->where('pumper_day_entries.pump_operator_id',$pump_operator_id);
//         }

//         $daily_collections = DataTables::of($pumps)
//             ->addColumn(
//                 'action',
//                 function ($row) {

//                     $html = '<div class="btn-group">
//                             <button type="button" class="btn btn-info dropdown-toggle btn-xs"
//                                 data-toggle="dropdown" aria-expanded="false">' .
//                         __("messages.actions") .
//                         '<span class="caret"></span><span class="sr-only">Toggle Dropdown
//                                 </span>
//                             </button>
//                             <ul class="dropdown-menu dropdown-menu-left" role="menu"> ';
//                     if (auth()->user()->can('daily_pump_status.edit')) {
//                         $html .= '<li><a class="btn-modal" data-container=".pump_operator_modal" data-href="' . action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorAssignmentController@edit', $row->id) . '"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>' . __("messages.edit") . '</a></li> ';
//                     }
//                     if (auth()->user()->can('daily_pump_status.delete')) {
//                         $html .= '<li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorAssignmentController@destroy', $row->id) . '" class="delete_daily_collection"><i class="fa fa-trash"></i>' . __("messages.delete") . '</a></li>';
//                     }

//                     $html .= '</ul></div>';

//                     return $html;
//                 }
//             )
//             ->addColumn('sold_ltr', function($row){
//                 return  '<span class="display_currency footer_sold_fuel_qty" data-orig-value="' . $row->sold_ltr . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->sold_ltr) . '</span>';
//             })
//             ->addColumn('testing_ltr', function($row){
//                 return  '<span class="display_currency footer_testing_qty" data-orig-value="' . $row->testing_ltr . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->testing_ltr) . '</span>';
//             })
//             ->addColumn('sold_amount', function ($row) use ($business_details) {

//                 $product = Product::leftjoin('variations', 'products.id', 'variations.product_id')
//                     ->where('products.id', $row->product_id)->select('variations.sell_price_inc_tax')->first();

//                 $amt = ($row->sold_ltr) * $product->sell_price_inc_tax;

//                 return  '<span class="display_currency footer_sold_fuel_amount" data-orig-value="' . $amt . '" data-currency_symbol = false>' . $this->commonUtil->num_f($amt, false, $business_details, false) . '</span>';

//             })
//             ->removeColumn('id')
//             ->addColumn('date_and_time', '{{@format_date($date)}}');

//         return $daily_collections->rawColumns(['action','sold_ltr','testing_ltr','sold_amount'])
//             ->make(true);
//     }
// }
}

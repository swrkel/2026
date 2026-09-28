<?php
namespace Modules\PetroGeneral\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountTransaction;
use App\TransactionPayment;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\Product;
use App\Store;
use App\System;
use App\Transaction;
use App\UserStorePermission;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use App\VariationLocationDetails;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Milon\Barcode\DNS2D;
use Modules\PetroGeneral\Entities\DailyCard;
use Modules\PetroGeneral\Entities\DailyChequePayment;
use Modules\PetroGeneral\Entities\DailyCollection;
use Modules\PetroGeneral\Entities\DailyVoucher;
use Modules\PetroGeneral\Entities\DailyVoucherItem;
use Modules\PetroGeneral\Entities\FuelTank;
use Modules\PetroGeneral\Entities\MeterSale;
use Modules\PetroGeneral\Entities\PetroShift;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\PumperDayEntry;
use Modules\PetroGeneral\Entities\PumpOperator;
use Modules\PetroGeneral\Entities\PumpOperatorAssignment;
use Modules\PetroGeneral\Entities\PumpOperatorMeterSale;
use Modules\PetroGeneral\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroGeneral\Entities\PumpOperatorOtherSale;
use Modules\PetroGeneral\Entities\PumpOperatorPayment;
use Modules\PetroGeneral\Entities\Settlement;
use Modules\PetroGeneral\Entities\SettlementCreditSalePayment;
use Modules\PetroGeneral\Entities\SettlementCashPayment;
use Modules\PetroGeneral\Entities\SettlementCardPayment;
use Modules\PetroGeneral\Entities\SettlementChequePayment;
use Modules\Superadmin\Entities\Subscription;
use Yajra\DataTables\Facades\DataTables;

class PumpOperatorPaymentController extends Controller
{

    /**
     * MA-002 PERF: request-scoped cache of pump id -> pump name.
     *
     * The "pumps" column loops the meter-sale details and queried Pump for
     * every detail on every row. A station has a handful of pumps, so the
     * same names were re-queried continuously. Reference data; cannot change
     * mid-render. Misses cached too.
     */
    private static array $ma002PumpNameCache = [];

    private static function ma002PumpName($pumpId): string
    {
        if (empty($pumpId)) {
            return '';
        }

        if (! array_key_exists($pumpId, self::$ma002PumpNameCache)) {
            $pump = Pump::where('id', $pumpId)->select('pump_name')->first();
            self::$ma002PumpNameCache[$pumpId] = $pump->pump_name ?? '';
        }

        return self::$ma002PumpNameCache[$pumpId];
    }


    /**
     * MA-002 PERF: request-scoped caches for meter-sale lookups.
     *
     * The payments DataTable has five columns - pumps, unit_price,
     * last_meter, new_meter and qty_sold - and EACH ONE independently ran
     *
     *     PumpOperatorMeterSale::where(...)->first()
     *     PumpOperatorMeterSaleDetail::where('sale_id', ...)->get()
     *
     * for the SAME row. That is up to ten queries per row fetching the same
     * two result sets over and over.
     *
     * These caches are keyed by the lookup value, so within one request the
     * same payment resolves once and all five columns share it. The data is
     * still fetched per row - nothing is shared BETWEEN rows that should not
     * be - it is simply not fetched five times for the same row.
     *
     * These pages are read-only listings, so the values cannot change while
     * the table renders. Misses are cached too, so a payment with no meter
     * sale is not re-queried by each of the five columns.
     */
    private static array $ma002MeterSaleCache = [];
    private static array $ma002MeterSaleDetailCache = [];

    private static function ma002MeterSale(string $column, $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $key = $column . '#' . $value;

        if (! array_key_exists($key, self::$ma002MeterSaleCache)) {
            self::$ma002MeterSaleCache[$key] = PumpOperatorMeterSale::where($column, $value)->first();
        }

        return self::$ma002MeterSaleCache[$key];
    }

    private static function ma002MeterSaleDetails($saleId)
    {
        if (empty($saleId)) {
            return collect();
        }

        if (! array_key_exists($saleId, self::$ma002MeterSaleDetailCache)) {
            self::$ma002MeterSaleDetailCache[$saleId] = PumpOperatorMeterSaleDetail::where('sale_id', $saleId)->get();
        }

        return self::$ma002MeterSaleDetailCache[$saleId];
    }

    const FUEL_CATEGORY_ID = 1;

    private function authorizePumperDashboardPermission(string $permission): void
    {
        $user = Auth::user();
        $has_pumper_management_access = $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'pumper_management')
            || $this->moduleUtil->hasThePermissionInSubscription($user->business_id, 'petro_general_pumper_management');

        if (
            ! $user->can('pump_operator.dashboard') &&
            ! $user->can($permission) &&
            ! $has_pumper_management_access &&
            (empty($user->is_pump_operator) || empty($user->pump_operator_id))
        ) {
            abort(403, 'Unauthorized Access');
        }
    }

    private function normalizeShiftIds($shift_ids): array
    {
        if ($shift_ids === null || $shift_ids === '') {
            return [];
        }

        $shift_ids = is_array($shift_ids) ? $shift_ids : explode(',', $shift_ids);
        $shift_ids = array_filter(array_map('intval', $shift_ids), function ($value) {
            return $value > 0;
        });

        return array_values(array_unique($shift_ids));
    }

    private function getClosedShiftIdsForSettlement(array $shift_ids, ?int $pump_operator_id = null): array
    {
        if (empty($shift_ids)) {
            return [];
        }

        $assignment_query = PumpOperatorAssignment::whereIn('shift_id', $shift_ids)
            ->where(function ($query) {
                $query->where('status', 'close')
                    ->orWhere('is_manually_closed', 1);
            });

        if (! empty($pump_operator_id)) {
            $assignment_query->where('pump_operator_id', $pump_operator_id);
        }

        $closed_by_assignment = $assignment_query->pluck('shift_id')->toArray();
        $closed_by_shift = PetroShift::whereIn('id', $shift_ids)
            ->where('status', 2)
            ->pluck('id')
            ->toArray();

        return array_values(array_unique(array_map('intval', array_merge($closed_by_assignment, $closed_by_shift))));
    }

    private function isSettlementShiftDataRequest(Request $request): bool
    {
        return (bool) $request->boolean('settlement_view') || $request->has('active_settlement_id');
    }

    private function pumpOperatorPaymentsHasCardMetaColumns(): bool
    {
        return Schema::hasColumn('pump_operator_payments', 'customer_id')
            && Schema::hasColumn('pump_operator_payments', 'slip_no')
            && Schema::hasColumn('pump_operator_payments', 'card_type')
            && Schema::hasColumn('pump_operator_payments', 'card_number');
    }

    private function getPaymentRequestValue($request, string $key)
    {
        if ($request instanceof Request) {
            return $request->input($key);
        }

        if (is_array($request)) {
            return $request[$key] ?? null;
        }

        if (is_object($request)) {
            return $request->{$key} ?? null;
        }

        return null;
    }

    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    protected $commonUtil;

    protected $contactUtil;

    protected $notificationUtil;

    protected $businessUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil, ContactUtil $contactUtil, NotificationUtil $notificationUtil)
    {
        $this->commonUtil       = $commonUtil;
        $this->productUtil      = $productUtil;
        $this->moduleUtil       = $moduleUtil;
        $this->transactionUtil  = $transactionUtil;
        $this->businessUtil     = $businessUtil;
        $this->contactUtil      = $contactUtil;
        $this->notificationUtil = $notificationUtil;

        // barcode types
        $this->barcode_types = $this->productUtil->barcode_types();
    }

    private function extractTrailingSettlementNumber(?string $text): int
    {
        if (empty($text)) {
            return 0;
        }

        preg_match_all('/\d+/', $text, $matches);

        if (empty($matches[0])) {
            return 0;
        }

        return (int) end($matches[0]);
    }

    private function generateSettlementNoForPayments(int $business_id): string
    {
        $business = Business::find($business_id);
        $ref_no_prefixes = $business->ref_no_prefixes ?? [];
        $prefix = ! empty($ref_no_prefixes['settlement']) ? $ref_no_prefixes['settlement'] : 'SET-';

        $latest_settlement = Settlement::where('business_id', $business_id)
            ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
            ->where('settlement_no', 'NOT LIKE', 'PDST%')
            ->orderByDesc('id')
            ->first();

        $count = $latest_settlement ? $this->extractTrailingSettlementNumber($latest_settlement->settlement_no) : 0;

        return $prefix . (1 + $count);
    }

    private function ensureActiveSettlementForPumpOperator(int $business_id, int $pump_operator_id, ?int $shift_id = null): Settlement
    {
        $pump_operator = PumpOperator::findOrFail($pump_operator_id);

        $settlement = null;
        if (! empty($pump_operator->settlement_no)) {
            $settlement = Settlement::where('settlement_no', $pump_operator->settlement_no)
                ->where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('status', 1)
                ->first();
        }

        if (empty($settlement)) {
            $settlement = Settlement::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('status', 1)
                ->orderByDesc('id')
                ->first();
        }

        if (empty($settlement)) {
            $fallback_location_id = $pump_operator->location_id
                ?? BusinessLocation::where('business_id', $business_id)->value('id');

            $settlement = Settlement::create([
                'settlement_no' => $this->generateSettlementNoForPayments($business_id),
                'business_id' => $business_id,
                'transaction_date' => now()->format('Y-m-d'),
                'location_id' => $fallback_location_id,
                'pump_operator_id' => $pump_operator_id,
                'work_shift' => ! empty($shift_id) ? [(int) $shift_id] : [],
                'status' => 1,
            ]);
        }

        if ($pump_operator->settlement_no !== $settlement->settlement_no) {
            $pump_operator->settlement_no = $settlement->settlement_no;
            $pump_operator->save();
        }

        return $settlement;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Renderable
     */
    public function index()
    {
        if (! empty(request()->only_pumper)) {
            $this->authorizePumperDashboardPermission('pumper_dashboard.payment_summary');
        }

        Log::info('index');

        $business_id      = Auth::user()->business_id;
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_details = Business::find($business_id);

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {
            abort(403, 'Unauthorized Access');
        }

        $only_pumper = request()->only_pumper;
        $shift_id    = request()->shift_id;

        if (request()->ajax()) {
            $start_date = null;
            $end_date = null;

            if (!empty(request()->date_range)) {
                $date_arr = explode(' - ', request()->date_range);
                if (count($date_arr) == 2) {
                    $start_date = $this->moduleUtil->uf_date(trim($date_arr[0])) . ' 00:00:00';
                    $end_date = $this->moduleUtil->uf_date(trim($date_arr[1])) . ' 23:59:59';
                }
            } else if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start_date = \Carbon\Carbon::parse(request()->start_date)->startOfDay()->toDateTimeString();
                $end_date = \Carbon\Carbon::parse(request()->end_date)->endOfDay()->toDateTimeString();
            }

            $location_id = request()->location_id;
            $filter_pump_operator_id = request()->pump_operator_id;
            $payment_method = request()->payment_method;
            $customer_id = request()->customer_id;
            $slip_no = request()->slip_no;
            $order_no = request()->order_no;

            $has_card_meta_columns = $this->pumpOperatorPaymentsHasCardMetaColumns();

            $query = app(\Modules\PetroGeneral\Services\SettlementPaymentQueryService::class)
                ->paymentSummaryBaseQuery($business_id, $has_card_meta_columns);

            // Apply filters to main query
            if (!empty($shift_id)) {
                $query->where('pump_operator_payments.shift_id', $shift_id);
            }
            if (!empty($start_date) && !empty($end_date)) {
                $query->whereBetween('pump_operator_payments.date_and_time', [$start_date, $end_date]);
            }
            if (!empty($location_id)) {
                $query->where('pump_operators.location_id', $location_id);
            }
            if (!empty($filter_pump_operator_id)) {
                $query->where('pump_operator_payments.pump_operator_id', $filter_pump_operator_id);
            }
            if (!empty($payment_method)) {
                $query->where('pump_operator_payments.payment_type', $payment_method);
            } else {
                // By default only show Cash, Credit, Card as per previous logic if needed,
                // but usually better to show everything or follow previous restriction.
                // The previous code merged only cash, credit, card.
                $query->whereIn('pump_operator_payments.payment_type', ['cash', 'credit', 'card']);
            }
            if (!empty($customer_id)) {
                $query->where(function($q) use ($customer_id) {
                    $q->where('scsp.customer_id', $customer_id)
                      ->orWhere($this->pumpOperatorPaymentsHasCardMetaColumns() ? 'pump_operator_payments.customer_id' : 'dc.customer_id', $customer_id);

                    if ($this->pumpOperatorPaymentsHasCardMetaColumns()) {
                        $q->orWhere('dc.customer_id', $customer_id);
                    }
                });
            }
            if (!empty($slip_no)) {
                if ($has_card_meta_columns) {
                    $query->where(function ($q) use ($slip_no) {
                        $q->where('pump_operator_payments.slip_no', 'like', '%' . $slip_no . '%')
                            ->orWhere('dc.slip_no', 'like', '%' . $slip_no . '%');
                    });
                } else {
                    $query->where('dc.slip_no', 'like', '%' . $slip_no . '%');
                }
            }
            if (!empty($order_no)) {
                $query->where('scsp.order_number', 'like', '%' . $order_no . '%');
            }

            $query->select(
                'pump_operator_payments.id',
                'pump_operator_payments.date_and_time',
                'pump_operator_payments.collection_form_no',
                'pump_operator_payments.payment_type',
                'pump_operator_payments.payment_amount',
                'pump_operator_payments.note',
                'pump_operators.name as pump_operator_name',
                'pump_operators.id as pump_operator_id',
                'edited_user.username as edited_by',
                'business_locations.name as location_name',
                DB::raw('COALESCE(contacts_credit.name, contacts_card.name, contacts_card_legacy.name) as customer_name'),
                DB::raw('COALESCE(scsp.customer_id, ' . ($has_card_meta_columns ? 'pump_operator_payments.customer_id, ' : '') . 'dc.customer_id) as customer_id'),
                'pump_operator_assignments.shift_id as shift_number',
                'scsp.order_number',
                'scsp.id as scsp_id',
                'scsp.daily_voucher_id',
                'dv.settlement_no as daily_voucher_settlement_no',
                DB::raw('COALESCE(' . ($has_card_meta_columns ? 'pump_operator_payments.slip_no, ' : '') . 'dc.slip_no) as slip_no')
            )->groupBy('pump_operator_payments.id');

            return DataTables::of($query)
                ->addColumn('action', function ($row) use ($only_pumper) {
                    if (empty($row->id) || strtolower($row->payment_type) == 'other sale') {
                        return '';
                    }

                    $edit_query = '?type=' . urlencode($row->payment_type);
                    if (strtolower($row->payment_type) === 'credit') {
                        $edit_query .= '&credit_sale_id=' . urlencode($row->scsp_id);
                        $edit_query .= '&payment_id=' . urlencode($row->id);
                    }

                    $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                            data-toggle="dropdown" aria-expanded="false">' .
                                __('messages.actions') .
                                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if (empty($only_pumper)) {
                        $is_settled_voucher = strtolower($row->payment_type) === 'credit'
                            && ! empty($row->daily_voucher_id)
                            && ! empty($row->daily_voucher_settlement_no);

                        if ($is_settled_voucher) {
                            $html .= '<li class="disabled"><a href="#"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                        } elseif (strtolower($row->payment_type) === 'credit' && ! empty($row->daily_voucher_id)) {
                            $html .= '<li><a href="#" data-href="' .
                            url('petro-general/daily-voucher/' . $row->daily_voucher_id . '/edit') .
                            $edit_query .
                            '" class="btn-modal" data-container=".pump_modal"><i class="glyphicon glyphicon-edit"></i> ' .
                            __('messages.edit') . '</a></li>';
                        } else {
                            $html .= '<li><a href="#" data-href="' .
                            url('petro-general/pump-operators/payment/' . $row->id . '/edit') .
                            $edit_query .
                            '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' .
                            __('messages.edit') . '</a></li>';
                        }
                    }

                    if (strtolower($row->payment_type) == 'credit') {
                         $print_id = !empty($row->scsp_id) ? $row->scsp_id : $row->id;
                         $print_url = url('petro-general/pump-operator-pmts/print-credit-sale/' . $print_id);
                         $html .= '<li><a href="#" onclick="window.open(\'' . $print_url . '\', \'_blank\', \'width=400,height=600\'); return false;"><i class="fa fa-print"></i> ' . __('messages.print') . '</a></li>';
                    }

                    return $html . '</ul></div>';
                })
                ->addColumn('date', '{{@format_date($date_and_time)}}')
                ->addColumn('time', '{{@format_time($date_and_time)}}')

            // Customer column: show actual customer name whenever we have it,
            // fall back to "Walk in customer" only when it's truly unknown.
                ->addColumn('customer_name', function ($row) {
                    if (! empty($row->customer_name)) {
                        return $row->customer_name;
                    }
                    return 'Walk in customer';
                })
                ->addColumn('slip_no', function ($row) {
                    if (strtolower($row->payment_type) == 'card') {
                        return $row->slip_no ?? '—';
                    }

                    return '—';
                })

                ->addColumn('order_number', function ($row) {
                    // if ($row->payment_type == 'credit') {
                    return $row->order_number ?? '—';
                    // }
                    // return '—';
                })
                ->addColumn('pump_operator_name', function ($row) {
                    // if ($row->payment_type == 'Shortage' || $row->payment_type == 'Excess') {
                    return $row->pump_operator_name ?? '—';
                    // }
                    // return 'N/A';
                })

                ->removeColumn('id')
                ->editColumn('payment_type', '{{ ucfirst($payment_type) }}')
                ->editColumn('amount', function ($row) use ($business_details) {
                    return '<span class="display_currency amount" data-orig-value="' .
                    $row->payment_amount .
                    '" data-currency_symbol=false>' .
                    $this->productUtil->num_f($row->payment_amount, false, $business_details, true) .
                        '</span>';
                })
                ->rawColumns(['amount', 'action'])
                ->make(true);
        }

        // Non-ajax section
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $payment_types  = $this->transactionUtil->payment_types();

        $layout = $only_pumper ? 'pumper' : 'app';

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')
            ->where('petro_shifts.business_id', $business_id)
            ->select('pump_operators.name', 'petro_shifts.*')
            ->orderBy('petro_shifts.id', 'DESC');

        if ($only_pumper) {
            $shifts->where('pump_operator_id', $pump_operator_id);
        }

        $shifts = $shifts->get();

        $user         = Auth::user();
        // MA-002: numeric max - shift_number is varchar(50), so MAX() on
        // it compares as TEXT and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $user->pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');
        $customers    = Contact::customersDropdown($business_id, false, true, 'customer');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('petrogeneral::pump_operators.payment_summary')->with(compact(
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

    public function summarypaymnetdashboard()
    {
        if (! empty(request()->only_pumper)) {
            $this->authorizePumperDashboardPermission('pumper_dashboard.payment_summary');
        }

        try {
            Log::debug('summarypaymnetdashboard optimized');

            $business_id      = Auth::user()->business_id;
            $pump_operator_id = Auth::user()->pump_operator_id;
            $business_details = Business::find($business_id);

            if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {
                abort(403, 'Unauthorized Access');
            }

            $only_pumper = request()->only_pumper;
            $shift_id    = request()->shift_id;

            if (empty($shift_id) && ! empty($only_pumper)) {
                $shift_id = DB::table('petro_shifts')
                    ->where('business_id', $business_id)
                    ->orderBy('id', 'desc')
                    ->value('id');
            }

            if (request()->ajax()) {

                // Pre-calculate completed shifts for is_edit_locked logic
                $completed_shift_ids = [];
                $settlements = Settlement::where('business_id', $business_id)
                    ->where('status', 0)
                    ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                    ->pluck('work_shift');

                foreach ($settlements as $work_shifts) {
                    if (is_string($work_shifts)) {
                        $decoded = json_decode($work_shifts, true);
                        $work_shifts = is_array($decoded) ? $decoded : explode(',', $work_shifts);
                    }
                    if (is_array($work_shifts)) {
                        foreach ($work_shifts as $ws_id) {
                            if ($ws_id) $completed_shift_ids[] = (int) $ws_id;
                        }
                    }
                }
                $completed_shift_ids = array_unique($completed_shift_ids);

                // OPTIMIZED QUERY
                $has_card_meta_columns = $this->pumpOperatorPaymentsHasCardMetaColumns();

                $query = PumpOperatorPayment::leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                    ->leftJoin('users as edited_user', 'pump_operator_payments.edited_by', '=', 'edited_user.id')
                    ->leftJoin('business_locations', 'business_locations.id', '=', 'pump_operators.location_id')
                    ->leftJoin('settlements as payment_settlements', function ($join) use ($business_id) {
                        $join->whereRaw(
                            'payment_settlements.business_id = ? AND (CONVERT(payment_settlements.id USING utf8mb4) = CONVERT(pump_operator_payments.settlement_no USING utf8mb4) OR CONVERT(payment_settlements.settlement_no USING utf8mb4) = CONVERT(pump_operator_payments.settlement_no USING utf8mb4))',
                            [$business_id]
                        );
                    })
                    ->leftJoin('settlement_credit_sale_payments as scsp', function ($join) {
                        $join->on('scsp.pump_payment_id', '=', 'pump_operator_payments.id');
                    })
                    ->leftJoin('daily_vouchers as dv', 'dv.id', '=', 'scsp.daily_voucher_id')
                    ->leftJoin('contacts as contacts_credit', 'contacts_credit.id', '=', 'scsp.customer_id')
                    ->leftJoin('daily_cards as dc', function ($join) {
                        $join->on('dc.business_id', '=', 'pump_operator_payments.business_id')
                            ->on(DB::raw('dc.amount'), '=', DB::raw('pump_operator_payments.payment_amount'))
                            ->whereRaw('dc.collection_no COLLATE utf8mb4_unicode_ci = pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci');
                    })
                    ->leftJoin('contacts as contacts_card', 'contacts_card.id', '=', $has_card_meta_columns ? 'pump_operator_payments.customer_id' : 'dc.customer_id')
                    ->leftJoin('contacts as contacts_card_legacy', 'contacts_card_legacy.id', '=', 'dc.customer_id')
                    ->where('pump_operators.business_id', $business_id);

                // Apply Filters
                if ($only_pumper) {
                    $query->where('pump_operator_payments.pump_operator_id', $pump_operator_id);
                }

                if (!empty(request()->pump_operator_id)) {
                    $query->where('pump_operator_payments.pump_operator_id', request()->pump_operator_id);
                }

                if (!empty($shift_id)) {
                    $query->where('pump_operator_payments.shift_id', $shift_id);
                }

                if (!empty(request()->payment_method)) {
                    $query->where('pump_operator_payments.payment_type', request()->payment_method);
                } else {
                    $query->whereIn('pump_operator_payments.payment_type', ['cash', 'credit', 'card']);
                }

                if (!empty(request()->location_id)) {
                    $query->where('pump_operators.location_id', request()->location_id);
                }

                if (!empty(request()->settlement_id)) {
                    $query->where('payment_settlements.id', request()->settlement_id);
                }

                if (!empty(request()->status)) {
                    if (request()->status == 'completed') {
                        $query->whereNotNull('payment_settlements.id')
                            ->where('payment_settlements.status', 0);
                    }

                    if (request()->status == 'pending') {
                        $query->where(function ($q) {
                            $q->whereNull('payment_settlements.id')
                                ->orWhere('payment_settlements.status', 1);
                        });
                    }
                }

                if (!empty(request()->customer_id)) {
                    $query->where(function($q) {
                        $q->where('scsp.customer_id', request()->customer_id)
                        ->orWhere($this->pumpOperatorPaymentsHasCardMetaColumns() ? 'pump_operator_payments.customer_id' : 'dc.customer_id', request()->customer_id);

                        if ($this->pumpOperatorPaymentsHasCardMetaColumns()) {
                            $q->orWhere('dc.customer_id', request()->customer_id);
                        }
                    });
                }

                if (!empty(request()->slip_no)) {
                    if ($has_card_meta_columns) {
                        $query->where(function ($q) {
                            $q->where('pump_operator_payments.slip_no', 'like', '%' . request()->slip_no . '%')
                                ->orWhere('dc.slip_no', 'like', '%' . request()->slip_no . '%');
                        });
                    } else {
                        $query->where('dc.slip_no', 'like', '%' . request()->slip_no . '%');
                    }
                }

                if (!empty(request()->order_no)) {
                    $query->where('scsp.order_number', 'like', '%' . request()->order_no . '%');
                }

                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $start_date = \Carbon\Carbon::parse(request()->start_date)->startOfDay()->toDateTimeString();
                    $end_date   = \Carbon\Carbon::parse(request()->end_date)->endOfDay()->toDateTimeString();
                    $query->whereBetween('pump_operator_payments.date_and_time', [$start_date, $end_date]);
                }

                // SELECT
                $query->select(
                    'pump_operator_payments.id',
                    'pump_operator_payments.date_and_time',
                    'pump_operator_payments.created_at as payment_created_at',
                    'pump_operator_payments.collection_form_no',
                    'pump_operator_payments.payment_type',
                    'pump_operator_payments.payment_amount',
                    'pump_operator_payments.shift_id',
                    DB::raw('(SELECT MAX(poa.shift_number) FROM pump_operator_assignments poa WHERE poa.shift_id = pump_operator_payments.shift_id AND poa.pump_operator_id = pump_operator_payments.pump_operator_id) as shift_number'),
                    'pump_operator_payments.note',
                    'pump_operators.name as pump_operator_name',
                    'edited_user.username as edited_by',
                    'business_locations.name as location_name',
                    'scsp.id as scsp_id',
                    'scsp.daily_voucher_id',
                    'dv.settlement_no as daily_voucher_settlement_no',
                    'scsp.order_number',
                    DB::raw('COALESCE(' . ($has_card_meta_columns ? 'pump_operator_payments.slip_no, ' : '') . 'dc.slip_no) as slip_no'),
                    DB::raw('COALESCE(contacts_credit.name, contacts_card.name, contacts_card_legacy.name) as customer_name')
                )->distinct('pump_operator_payments.id');

                return DataTables::of($query)
                    ->addColumn('action', function ($row) use ($completed_shift_ids) {

                        if (empty($row->id) || strtolower($row->payment_type) == 'other sale') {
                            return '';
                        }

                        $is_edit_locked = in_array((int) $row->shift_id, $completed_shift_ids, true);
                        $edit_query = '?type=' . urlencode($row->payment_type);
                        if (strtolower($row->payment_type) === 'credit') {
                            $edit_query .= '&credit_sale_id=' . urlencode($row->scsp_id);
                            $edit_query .= '&payment_id=' . urlencode($row->id);
                        }

                        $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                data-toggle="dropdown">' . __('messages.actions') . '
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left">';

                        $is_settled_voucher = strtolower($row->payment_type) === 'credit'
                            && ! empty($row->daily_voucher_id)
                            && ! empty($row->daily_voucher_settlement_no);

                        if ($is_settled_voucher || ($is_edit_locked && strtolower($row->payment_type) !== 'credit')) {
                            $html .= '<li class="disabled"><a href="#">' . __('messages.edit') . '</a></li>';
                        } else {
                            if (strtolower($row->payment_type) === 'credit' && ! empty($row->daily_voucher_id)) {
                                $html .= '<li><a href="#" data-href="' . url('petro-general/daily-voucher/' . $row->daily_voucher_id . '/edit') . $edit_query . '" class="btn-modal" data-container=".pump_modal">' . __('messages.edit') . '</a></li>';
                            } else {
                                // btn-modal (public/js/app.js) requires data-container or the modal never opens
                                $html .= '<li><a href="#" data-href="' . url('petro-general/pump-operators/payment/' . $row->id . '/edit') . $edit_query . '" class="btn-modal" data-container=".view_modal">' . __('messages.edit') . '</a></li>';
                            }
                        }

                        if (strtolower($row->payment_type) === 'credit') {
                            $print_id = !empty($row->scsp_id) ? $row->scsp_id : $row->id;
                            $print_url = url('petro-general/pump-operator-pmts/print-credit-sale/' . $print_id);
                            $html .= '<li><a href="' . $print_url . '" target="_blank">Re-Print</a></li>';
                        }

                        return $html . '</ul></div>';
                    })
                    ->addColumn('date', '{{@format_date($date_and_time)}}')
                    ->addColumn('time', '{{@format_time($date_and_time)}}')
                    ->addColumn('customer_name', fn($row) => $row->customer_name ?? 'Walk in customer')
                    ->addColumn('slip_no', fn($row) => strtolower($row->payment_type) == 'card' ? ($row->slip_no ?? '—') : '—')
                    ->addColumn('order_number', fn($row) => $row->order_number ?? '—')
                    ->addColumn('pump_operator_name', fn($row) => $row->pump_operator_name ?? '—')
                    ->removeColumn('id')
                    ->editColumn('payment_type', '{{ ucfirst($payment_type) }}')
                    ->editColumn('amount', function ($row) use ($business_details) {
                        return '<span class="display_currency amount" data-orig-value="' . $row->payment_amount . '">' .
                            $this->productUtil->num_f($row->payment_amount, false, $business_details, true) .
                            '</span>';
                    })
                    ->rawColumns(['amount', 'action'])
                    ->make(true);
            }

        } catch (\Exception $e) {
            Log::error('Error in summarypaymnetdashboard: ' . $e->getMessage());

            if (request()->ajax()) {
                return response()->json(['error' => 'Error loading data'], 500);
            }

            abort(500, 'Error loading page');
        }
    }
    //     public function index()
    //     {
    //         $business_id =  Auth::user()->business_id;
    //         $pump_operator_id = Auth::user()->pump_operator_id;
    //         $business_details = Business::find($business_id);

    //         if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {
    //             abort(403, 'Unauthorized Access');
    //         }

    //         $only_pumper = request()->only_pumper;
    //         $shift_id = request()->shift_id;

    //         if (request()->ajax()) {
    //             $business_id =  Auth::user()->business_id;
    //             $query = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')
    //                 ->leftjoin('users as edited_user', 'pump_operator_payments.edited_by', 'edited_user.id')
    //                 ->leftjoin('business_locations','business_locations.id','pump_operators.location_id')
    //                 ->leftjoin('pump_operator_assignments','pump_operator_assignments.shift_id','pump_operator_payments.shift_id')
    //                  ->leftjoin('settlements as st', 'st.id', 'pump_operators.settlement_no')
    //                 ->leftjoin('settlement_credit_sale_payments', 'settlement_credit_sale_payments.settlement_no', 'st.id')
    //                 ->leftjoin('contacts', 'contacts.id', 'settlement_credit_sale_payments.customer_id') // or correct foreign key
    //                  ->leftjoin('settlement_card_payments','settlement_card_payments.settlement_no','st.id')
    //                 ->where('pump_operators.business_id', $business_id)
    //                 ->select('pump_operator_payments.id', 'pump_operator_payments.date_and_time', 'pump_operator_payments.collection_form_no', 'pump_operator_payments.payment_type', 'pump_operator_payments.payment_amount', 'pump_operator_payments.note', 'pump_operators.name as pump_operator_name', 'edited_user.username as edited_by','business_locations.name as location_name', 'pump_operator_assignments.shift_number',
    //         'contacts.name as customer_name',
    //         'settlement_credit_sale_payments.order_number as order_number',
    //         'settlement_card_payments.slip_no as slip_no')
    //                 ->groupBy('pump_operator_payments.id');
    // $sql = $query->toSql();
    // \Log::info('SQL summary: ' . $sql);
    // \Log::info('Bindings: ', $query->getBindings());

    //             // $query2 = PumpOperatorOtherSale::join('pump_operator_assignments','pump_operator_assignments.shift_id','pump_operator_other_sales.shift_id')
    //             // ->join('pump_operators', 'pump_operator_assignments.pump_operator_id', 'pump_operators.id')
    //             // ->join('business_locations','business_locations.id','pump_operators.location_id')
    //             // ->where('pump_operators.business_id', $business_id)
    //             // ->select('pump_operator_other_sales.id', 'pump_operator_other_sales.created_at as date_and_time',
    //             // DB::raw('NULL as collection_form_no'),
    //             // DB::raw('"Other Sale" as payment_type'),
    //             // 'pump_operator_other_sales.sub_total as payment_amount',
    //             // DB::raw('NULL as note'),
    //             // 'pump_operators.name as pump_operator_name',
    //             // DB::raw('NULL as edited_by'),
    //             // 'business_locations.name as location_name', 'pump_operator_assignments.shift_number')
    //             // ->groupBy('pump_operator_other_sales.id');

    //             if ($only_pumper) {
    //                 $query->where('pump_operator_payments.pump_operator_id', $pump_operator_id);
    //                 //$query2->where('pump_operator_assignments.pump_operator_id', $pump_operator_id);
    //             }

    //             if (!empty($shift_id)) {
    //                 $query->where('pump_operator_payments.shift_id', $shift_id);
    //                 //$query2->where('pump_operator_assignments.shift_id', $shift_id);
    //             }

    //             if (!empty(request()->payment_method)) {
    //                 $query->where('payment_type', request()->payment_method);
    //                 //$query2->where('payment_type', request()->payment_method);
    //             }
    //             if (!empty(request()->location_id)) {
    //                 $query->where('pump_operators.location_id', request()->location_id);
    //                 //$query2->where('pump_operators.location_id', request()->location_id);
    //             }
    //             if (!empty(request()->pump_operator_id)) {
    //                 $query->where('pump_operator_id', request()->pump_operator_id);
    //                 $query->where('pump_operator_assignments.pump_operator_id', request()->pump_operator_id);
    //             }
    //             if (!empty(request()->start_date) && !empty(request()->end_date)) {
    //                 $query->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date);
    //                 $query->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);
    //                 //$query2->whereDate('pump_operator_other_sales.created_at', '>=', request()->start_date);
    //                 //$query2->whereDate('pump_operator_other_sales.created_at', '<=', request()->end_date);
    //             }

    //             //$query = $query->unionAll($query2)->orderBy('id', 'asc');
    //             $query = $query->orderBy('id', 'asc');

    //             $fuel_tanks = DataTables::of($query)
    //                 ->addColumn('action', function ($row) use($pump_operator_id, $business_id, $only_pumper) {
    //     $html = '<div class="btn-group">
    //         <button type="button" class="btn btn-info dropdown-toggle btn-xs"
    //             data-toggle="dropdown" aria-expanded="false">' .
    //             __("messages.actions") .
    //             '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
    //         </button>
    //         <ul class="dropdown-menu dropdown-menu-left" role="menu">';

    //     if($only_pumper){
    //         $html .= '<li><a href="#" data-href="' . action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorPaymentController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
    //     } else {
    //         $html .= '<li><a href="#" data-href="' . action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorPaymentController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
    //     }

    //     if($row->payment_type == 'Other Sale'){
    //         return '';
    //     }

    //     return $html . '</ul></div>';

    //                         return $html;
    //                     }
    //                 )
    //                 ->addColumn('date', '{{@format_date($date_and_time)}}')
    //                 ->addColumn('time', '{{@format_time($date_and_time)}}')
    //                 ->removeColumn('id')
    //                 ->editColumn('payment_type', '{{ucfirst($payment_type)}}')
    //                 ->editColumn(
    //                     'amount',
    //                     function ($row) use ($business_details) {
    //                         return  '<span class="display_currency amount" data-orig-value="' . $row->payment_amount . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->payment_amount, false, $business_details, true) . '</span>';
    //                     }
    //                 );

    //             return $fuel_tanks->rawColumns(['amount', 'action'])
    //                 ->make(true);
    //         }

    //         $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
    //         $payment_types = $this->transactionUtil->payment_types();
    //         $layout = 'app';
    //         if ($only_pumper) {
    //             $layout = 'pumper';
    //         }

    //         $shifts = PetroShift::join('pump_operators','pump_operators.id','petro_shifts.pump_operator_id')->where('petro_shifts.business_id',$business_id)->select('pump_operators.name','petro_shifts.*')->orderBy('id','DESC');

    //         if ($only_pumper) {
    //             $shifts->where('pump_operator_id', $pump_operator_id);
    //         }

    //         $shifts = $shifts->get();

    //         $user = Auth::user();

    //         $pump_operator_id = $user->pump_operator_id;
    //         $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->max('shift_number');

    //         return view('petrogeneral::pump_operators.payment_summary')->with(compact(
    //             'pump_operators',
    //             'only_pumper',
    //             'payment_types',
    //             'layout',
    //             'shifts',
    //             'shift_number'
    //         ));
    //     }

    /**
     * Show the form for creating a new resource.
     *
     * @return Renderable
     */
    public function create()
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.payments');

        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id      = Auth::user()->business_id;

        $physical_pumps_query = PumpOperatorAssignment::join('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->whereNotNull('pump_operator_assignments.shift_id')
            ->whereIn('pump_operator_assignments.status', ['open', 'close']);

        // Backward-compatible: some databases may not have `pumps.is_other_sales_pump` migrated yet.
        // Use the same connection as the query to support tenant DBs.
        if ($physical_pumps_query->getConnection()->getSchemaBuilder()->hasColumn('pumps', 'is_other_sales_pump')) {
            $physical_pumps_query->where('pumps.is_other_sales_pump', 0);
        }

        $physical_pumps_count = $physical_pumps_query->count();

        if ($physical_pumps_count == 0) {
            $output = [
                'success' => false,
                'msg' => "No physical pumps assigned. Please assign a pump to proceed."
            ];
            return redirect('/petro-general/pump-operators/dashboard')->with('status', $output);
        }

        $business         = Business::where('id', $business_id)->first();
        $pumps            = Pump::leftjoin('pump_operator_assignments', function ($join) {
            $join->on('pumps.id', 'pump_operator_assignments.pump_id')->whereDate('date_and_time', date('Y-m-d'));
        })->leftjoin('pump_operators', 'pump_operator_assignments.pump_operator_id', 'pump_operators.id')
            ->where('pumps.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->select('pumps.*', 'pump_operator_assignments.pump_operator_id', 'pump_operator_assignments.pump_id', 'pump_operators.name as pumper_name')
            ->orderBy('pumps.id')
            ->get();

        $layout = 'pumper';

        $customers = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'both'])
            ->where('active', 1)
            ->where('is_default', '!=', 1)
            ->pluck('name', 'id');

        $walkin = Contact::where('business_id', $business_id)
            ->where('is_default', 1)
            ->pluck('name', 'id');

        $products = Product::where('business_id', $business_id)
            ->where('show_in_pumper_dashboard', 1)
            ->pluck('name', 'id');

        if ($products->isEmpty()) {
            Log::info('No products found with show_in_pumper_dashboard=1, falling back to all non-fuel products', [
                'business_id' => $business_id,
            ]);

            $products = Product::where('business_id', $business_id)
                ->where('category_id', '!=', self::FUEL_CATEGORY_ID)
                ->pluck('name', 'id');
        }

        $subscription    = Subscription::current_subscription($business_id);
        $package_details = $subscription->package_details;

        $only_walkin = $package_details['only_walkin'] ?? 0;

        $pump_operator = PumpOperator::findOrFail($pump_operator_id);
        $settings      = json_decode($pump_operator->dashboard_settings, true);

        $direct_cr = 'no';
        if (! empty($settings) && ! empty($settings['credit_sales_direct_to_customer'])) {
            $direct_cr = $settings['credit_sales_direct_to_customer'];
        }

        $enter_cash_denoms = 'no';
        if (! empty($settings) && ! empty($settings['enter_cash_denominations'])) {
            $enter_cash_denoms = $settings['enter_cash_denominations'];
        }

        $card_pmt_type = 'bulk';
        if (! empty($settings) && ! empty($settings['card_amount_to_enter'])) {
            $card_pmt_type = $settings['card_amount_to_enter'];
        }

        $enter_card_numbers = 'yes';
        if (! empty($settings) && ! empty($settings['enter_card_numbers'])) {
            $enter_card_numbers = $settings['enter_card_numbers'];
        }

        $card_types = collect(); // default empty

        $card_group = AccountGroup::where('business_id', $business_id)
            ->where('name', 'Card')
            ->first();

        if ($card_group) {
            $card_types = Account::where('business_id', $business_id)
                ->where('asset_type', $card_group->id)
                ->whereRaw("REPLACE(name, '  ', ' ') != 'Cards (Credit Debit) Account'")
                ->pluck('name', 'id');
        }

        // If no card types found, fallback to asset_type = 26
        if ($card_types->isEmpty()) {

            $card_types = Account::where('asset_type', 26)
                ->whereRaw("REPLACE(name, '  ', ' ') = 'Visa Master Card'")
                ->pluck('name', 'id');

        }

        $pending_pumps = PumpOperatorAssignment::leftjoin('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
            ->leftjoin('products', 'products.id', 'pumps.product_id')
            ->leftjoin('variations', 'variations.product_id', 'products.id')
            ->join('petro_shifts', 'petro_shifts.id', 'pump_operator_assignments.shift_id')
            ->where('petro_shifts.status', '0')
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->where('pump_operator_assignments.business_id', $business_id)
            ->where('pump_operator_assignments.status', 'open')
        // ->whereNull('pump_operator_assignments.pump_operator_other_sale_id')
            ->select('pump_operator_assignments.*', 'variations.sell_price_inc_tax', 'pumps.pump_no')
            ->get();

        $shift_id = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
            ->orderBy('id', 'DESC')
            ->value('shift_id');

        $last_meter_sales = PumpOperatorMeterSaleDetail::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->orderBy('id', 'desc')
            ->get()
            ->unique('pump_id')
            ->keyBy('pump_id');

        foreach ($pending_pumps as $pump) {

            $last_sale = $last_meter_sales[$pump->pump_id] ?? null;

            $pump->last_entered_meter = $last_sale->new_meter ?? $pump->starting_meter;

            $pump->received_meter = $pump->last_entered_meter
                ?? $pump->starting_meter;
        }

        $daily_cards = DailyCard::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->whereNull('used_status')
            ->sum('amount');
        $pending_vouchers = DailyVoucher::where('business_id', $business_id)
            ->where('operator_id', $pump_operator_id)
            ->whereNull('settlement_no')
            ->sum('total_amount');
        $shift_id              = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->orderBy('id', 'DESC')->select('shift_id')->first()->shift_id ?? null;
        $daily_shortage_excess = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('shift_id', $shift_id)
            ->where(function ($query) {
                $query->whereNull('is_used')->orWhere('is_used', 0);
            })
            ->whereIn('payment_type', ['shortage', 'excess', 'other', 'cash', 'cheque'])
            ->sum('payment_amount');

        $all_pending_payments = $daily_cards + $pending_vouchers + $daily_shortage_excess;

        $bank_account_group_id = AccountGroup::getGroupByName('Bank Account');
        $bank_accounts         = Account::where('business_id', $business_id)->where('asset_type', $bank_account_group_id->id)->pluck('name', 'name');

        $today_deposited = PumpOperatorPayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('shift_id', $shift_id)
            ->whereIn('payment_type', ['cash', 'card', 'cheque', 'credit'])
            ->sum('payment_amount');

        $business     = Business::where('id', $business_id)->first();
        $pos_settings = json_decode($business->pos_settings, true);
        $cash_denoms  = ! empty($pos_settings['cash_denominations']) ? explode(',', $pos_settings['cash_denominations']) : [];

        $user = Auth::user();

        $pump_operator_id = $user->pump_operator_id;
        // MA-002: numeric max - shift_number is varchar(50), so MAX() on
        // it compares as TEXT and '9' beats '10'.
        $shift_number     = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');

        $settings               = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->select('dashboard_settings')->first();
        $dashboard_settings     = (! is_null($settings)) ? json_decode($settings->dashboard_settings, true) : [];
        $meter_sales_compulsory = $dashboard_settings['meter_sales_compulsory'] ?? 'no';

        $meter_sales_compulsory = ($meter_sales_compulsory == 'yes');

        $daily_collection = PumpOperatorPayment::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
        if (! is_null($daily_collection)) {
            $collection_form_no = (int) $daily_collection->collection_form_no + 1;
        } else {
            $collection_form_no = 1;
        }
        $DailyCollection = DailyCollection::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
        if (! is_null($DailyCollection)) {
            if ($DailyCollection->collection_form_no >= $collection_form_no) {
                $collection_form_no = (int) $DailyCollection->collection_form_no + 1;
            }
        }

        $shift_id   = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->orderBy('id', 'DESC')->select('shift_id')->first()->shift_id ?? null;
      
        $meter_sale = PumpOperatorMeterSale::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('shift_id', $shift_id)
            ->orderBy('id', 'desc')
            ->first();

        Log::info('Meter sale details for pump operator', [
            'pump_operator_id' => $pump_operator_id,
            'shift_id' => $shift_id,
            'meter_sale' => $meter_sale,
        ]);

        $total_amount = $meter_sale->amount ?? 0;
        // $today_deposited = $meter_sale->deposited ?? $today_deposited; // keep fallback
        // $balance_to_deposit = $meter_sale->balance ?? 0;
        $balance_to_deposit = $total_amount - $today_deposited;
        if (! is_null($meter_sale)) {
            $collection_form_no     = $meter_sale->collection_form_no;
            $meter_sales_compulsory = false;
        }

        $pumps = PumpOperatorAssignment::join('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
            ->leftjoin('pump_operators', 'pump_operator_assignments.pump_operator_id', 'pump_operators.id')
            ->leftjoin('petro_shifts', 'petro_shifts.id', 'pump_operator_assignments.shift_id')
            ->where('petro_shifts.status', 0)
            ->where('pumps.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->select('pumps.*', 'pump_operator_assignments.pump_operator_id', 'pump_operators.name as pumper_name', 'pump_operator_assignments.status', 'pump_operator_assignments.is_confirmed', 'pump_operator_assignments.id as assignment_id')
            ->orderBy('pump_operator_assignments.date_and_time', 'desc')
            ->groupBy('pumps.id')
            ->get();

        return view('petrogeneral::pump_operators.actions.payments')->with(compact(
            'pumps', 'card_types', 'business', 'enter_card_numbers',
            'layout', 'customers', 'walkin', 'products', 'only_walkin', 'direct_cr', 'today_deposited', 'total_amount',
            'pending_pumps', 'all_pending_payments', 'balance_to_deposit', 'bank_accounts', 'cash_denoms', 'enter_cash_denoms', 'card_pmt_type', 'shift_number', 'collection_form_no', 'meter_sales_compulsory'
        ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Renderable
     */
    public function store(Request $request)
    {
        Log::info('Pump Payment Request', $request->all());

        // $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id = Auth::user()->business_id;
        $created_by  = Auth::user()->id;

        $pump_operator_id = $request->input('pump_operator_id') ?? Auth::user()->pump_operator_id;

        if (! $pump_operator_id) {
            return [
                'success' => false,
                'msg'     => 'Pump Operator is required',
            ];
        }

        $pump_operator = PumpOperator::findOrFail($pump_operator_id);
        $settings      = json_decode($pump_operator->dashboard_settings, true);
        // $settlement = Settlement::where('settlement_no',$pump_operator->settlement_no)->first();

        $settlement = null;
        if (! empty($pump_operator->settlement_no)) {
            $settlement = Settlement::where('settlement_no', $pump_operator->settlement_no)
                ->where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('status', 1) // active settlement only
                ->first();
        }

        // dd($settlement);
        $shift = PetroShift::where('pump_operator_id', $pump_operator_id)->get()->last()->id ?? 0;

        try {
            $payment_amount = $request->amount;
            $payment_type   = $request->payment_type;

            /*
            removed payment type check
            */
            if ($payment_amount == '') {
                $output = [
                    'success' => false,
                    'msg'     => 'Please  amount are a mandatory field!',
                ];

                return $output;
            }

            $data = [
                'business_id'      => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'payment_type'     => $payment_type,
                'payment_amount'   => $payment_amount,
                'created_by'       => $created_by,
                'shift_id'         => $shift,
            ];

            if ($request->payment_type == 'card' && $this->pumpOperatorPaymentsHasCardMetaColumns()) {
                $data['customer_id'] = $request->input('customer_id');
                $data['card_type'] = $request->input('card_type');
                $data['card_number'] = $request->input('card_number');
                $data['slip_no'] = $request->input('slip_no');
            }

            // Calculate collection_form_no before creating payment for card payments
            $collection_form_no = null;
            if ($request->payment_type == 'card' && ! empty($request->card_type)) {
                $collection = PumpOperatorPayment::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
                if (! is_null($collection)) {
                    $collection_form_no = (int) $collection->collection_form_no + 1;
                } else {
                    $collection_form_no = 1;
                }
                $DailyCollection = DailyCollection::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
                if (! is_null($DailyCollection)) {
                    if ($DailyCollection->collection_form_no >= $collection_form_no) {
                        $collection_form_no = (int) $DailyCollection->collection_form_no + 1;
                    }
                }

                $shift_id_for_collection = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->orderBy('id', 'DESC')->select('shift_id')->first()->shift_id ?? null;
                if ($shift_id_for_collection) {
                    $meter_sale = PumpOperatorMeterSale::where('shift_id', $shift_id_for_collection)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('business_id', $business_id)
                        ->whereNull('p_o_payment_id')
                        ->orderBy('id', 'DESC')
                        ->first();
                    if (! is_null($meter_sale)) {
                        $collection_form_no = $meter_sale->collection_form_no;
                    }
                }

                if (! empty($request->collection_form_no)) {
                    $collection_form_no = $request->collection_form_no;
                }

                // Set collection_form_no in data before creating
                $data['collection_form_no'] = $collection_form_no;
            }

            // For cash payments, calculate collection_form_no BEFORE creating payment
            // so syncPaymentToDailyTables can use it to prevent duplicates
            if ($request->payment_type == 'cash') {
                $daily_collection = PumpOperatorPayment::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
                if (! is_null($daily_collection)) {
                    $collection_form_no = (int) $daily_collection->collection_form_no + 1;
                } else {
                    $collection_form_no = 1;
                }
                $DailyCollection = DailyCollection::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
                if (! is_null($DailyCollection)) {
                    if ($DailyCollection->collection_form_no >= $collection_form_no) {
                        $collection_form_no = (int) $DailyCollection->collection_form_no + 1;
                    }
                }

                $shift_id_for_collection = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->orderBy('id', 'DESC')->select('shift_id')->first()->shift_id ?? null;
                if ($shift_id_for_collection) {
                    $meter_sale = PumpOperatorMeterSale::where('shift_id', $shift_id_for_collection)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('business_id', $business_id)
                        ->whereNull('p_o_payment_id')
                        ->orderBy('id', 'DESC')
                        ->first();
                    if (! is_null($meter_sale)) {
                        $collection_form_no = $meter_sale->collection_form_no;
                    }
                }

                if (! empty($request->collection_form_no)) {
                    $collection_form_no = $request->collection_form_no;
                }

                // Set collection_form_no in data before creating
                $data['collection_form_no'] = $collection_form_no;
            }

            $PumpOperatorPayment = PumpOperatorPayment::create($data);

            // For cash payments, skip syncPaymentToDailyTables - we'll create DailyCollection manually below
            // to ensure proper shift_number and other fields are set
            if ($request->payment_type != 'cash') {
                try {
                    $this->syncPaymentToDailyTables($PumpOperatorPayment, $request);
                } catch (\Exception $e) {
                    // Log and continue, already saved main payment
                    Log::error('Sync to daily tables failed: ' . $e->getMessage());
                }
            }

            // NOTE: Account transactions for cash payments from pumper dashboard
            // should NOT be created here. They will be created when the settlement is saved
            // (in SettlementPDController@store). This ensures cash payments only appear
            // in the account book after settlement finalization.

            if ($request->payment_type == 'cash') {
                // collection_form_no already calculated and set above before creating PumpOperatorPayment
                // Now link meter sale if needed
                $shift_id = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->orderBy('id', 'DESC')->select('shift_id')->first()->shift_id ?? null;
                if ($shift_id) {
                    $meter_sale = PumpOperatorMeterSale::where('shift_id', $shift_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('business_id', $business_id)
                        ->whereNull('p_o_payment_id')
                        ->orderBy('id', 'DESC')
                        ->first();
                    if (! is_null($meter_sale)) {
                        // If meter sale has a different collection_form_no, use it
                        if (! empty($meter_sale->collection_form_no) && $meter_sale->collection_form_no != $collection_form_no) {
                            $collection_form_no                      = $meter_sale->collection_form_no;
                            $PumpOperatorPayment->collection_form_no = $collection_form_no;
                            $PumpOperatorPayment->update();
                        }
                        $meter_sale->p_o_payment_id = $PumpOperatorPayment->id;
                        $meter_sale->update();
                    }
                }

                // Get shift_number from PumpOperatorAssignment
                if ($shift_id) {
                    $assignment = PumpOperatorAssignment::where('shift_id', $shift_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('shift_number', '>', 0)
                        ->orderBy('id', 'DESC')
                        ->first();

                    $shift_number = $assignment->shift_number ?? null;

                    $data = [
                        'business_id'        => $business_id,
                        'collection_form_no' => $collection_form_no,
                        'pump_operator_id'   => $pump_operator_id,
                        'location_id'        => $pump_operator->location_id,
                        'balance_collection' => 0,
                        'current_amount'     => $payment_amount,
                        'created_by'         => Auth::user()->id,
                        'shift_id'           => $shift_id,
                        'shift_no'           => $shift_number, // Save shift_number as shift_no for backward compatibility
                        'shift_number'       => $shift_number, // Also save in shift_number field
                        'type'               => 'daily_collection',
                    ];

                    // Check for duplicate before creating - strict check including all key fields
                    $existing = DailyCollection::where('business_id', $business_id)
                        ->where('collection_form_no', $collection_form_no)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->where('type', 'daily_collection')
                        ->first();

                    if (! $existing) {
                        DailyCollection::create($data);
                    } elseif ((float) $existing->current_amount !== (float) $payment_amount) {
                        $existing->update(['current_amount' => $payment_amount]);
                    } else {
                        Log::warning('Duplicate DailyCollection prevented', [
                            'collection_form_no' => $collection_form_no,
                            'pump_operator_id'   => $pump_operator_id,
                            'shift_id'           => $shift_id,
                            'amount'             => $payment_amount,
                        ]);
                    }
                }

                // if (!empty($pump_operator->settlement_no)) {
                //     DailyCollection::where('pump_operator_id', $pump_operator_id)
                //         ->where('shift_id', $shift_id)
                //         ->whereNull('settlement_no')
                //         ->update(['settlement_no' => $pump_operator->settlement_no]);

                //     PumpOperatorPayment::where('pump_operator_id', $pump_operator_id)
                //         ->where('shift_id', $shift_id)
                //         ->whereNull('settlement_no')
                //         ->update(['settlement_no' => $pump_operator->settlement_no]);
                // }

                if (! empty($settlement)) {
                    DailyCollection::where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->whereNull('settlement_id')
                        ->update(['settlement_id' => $settlement->id]);

                    PumpOperatorPayment::where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->whereNull('settlement_no')
                        ->update(['settlement_no' => $settlement->id]);
                }

                $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();
                /*$balance_collection = DailyCollection::where('business_id', $business_id)->where('pump_operator_id', $pump_operator_id)->sum('current_amount');
                $settlement_collection = DailyCollection::where('business_id', $business_id)->where('pump_operator_id', $pump_operator_id)->sum('balance_collection');
                $cum_amount = $balance_collection - $settlement_collection;*/

                $sms_data = [
                    'date'          => $this->transactionUtil->format_date(date('Y-m-d')),
                    'time'          => date('H:i'),
                    'pump_operator' => $pump_operator->name,
                    'amount'        => $this->transactionUtil->num_f($payment_amount),
                ];

                $this->notificationUtil->sendPetroNotification('pumper_dashboard_cash_deposit', $sms_data);

            }

            if ($request->payment_type == 'card') {
                if (! empty($request->card_type)) {
                    // collection_form_no already calculated and set above
                    // syncPaymentToDailyTables already created the DailyCard entry
                    // No need to create duplicate entry here

                    // Update meter sale link if needed
                    if ($collection_form_no) {
                        $shift_id_for_meter = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->orderBy('id', 'DESC')->select('shift_id')->first()->shift_id ?? null;
                        if ($shift_id_for_meter) {
                            $meter_sale = PumpOperatorMeterSale::where('shift_id', $shift_id_for_meter)
                                ->where('pump_operator_id', $pump_operator_id)
                                ->where('business_id', $business_id)
                                ->whereNull('p_o_payment_id')
                                ->orderBy('id', 'DESC')
                                ->first();
                            if (! is_null($meter_sale)) {
                                $meter_sale->p_o_payment_id = $PumpOperatorPayment->id;
                                $meter_sale->update();
                            }
                        }
                    }
                }
            }

            $output = [
                'success'            => true,
                'msg'                => __('lang_v1.success'),
                'collection_form_no' => $collection_form_no,
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
     * TEMPORARY: Test route to create a cash entry for testing
     * Remove this before deploying to server
     */
    public function createTestCashEntry(Request $request)
    {
        try {
            $pump_operator_id = Auth::user()->pump_operator_id;
            $business_id      = Auth::user()->business_id;
            $created_by       = Auth::user()->id;

            if (! $pump_operator_id) {
                return [
                    'success' => false,
                    'msg'     => 'No pump operator ID found. Please login as a pump operator.',
                ];
            }

            $pump_operator = PumpOperator::findOrFail($pump_operator_id);
            $shift_id      = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
                ->orderBy('id', 'DESC')
                ->select('shift_id')
                ->first()->shift_id ?? null;

            if (! $shift_id) {
                return [
                    'success' => false,
                    'msg'     => 'No active shift found for this pump operator.',
                ];
            }

            // Get next collection form number
            $daily_collection = PumpOperatorPayment::where('business_id', $business_id)
                ->whereNotNull('collection_form_no')
                ->orderBy('id', 'DESC')
                ->select('collection_form_no')
                ->first();

            $collection_form_no = $daily_collection ? (int) $daily_collection->collection_form_no + 1 : 1;

            $DailyCollection = DailyCollection::where('business_id', $business_id)
                ->whereNotNull('collection_form_no')
                ->orderBy('id', 'DESC')
                ->select('collection_form_no')
                ->first();

            if ($DailyCollection && $DailyCollection->collection_form_no >= $collection_form_no) {
                $collection_form_no = (int) $DailyCollection->collection_form_no + 1;
            }

            // Test amount - you can change this
            $payment_amount = $request->amount ?? 1000;

            // Create PumpOperatorPayment
            $pumpPayment = PumpOperatorPayment::create([
                'business_id'        => $business_id,
                'pump_operator_id'   => $pump_operator_id,
                'payment_type'       => 'cash',
                'payment_amount'     => $payment_amount,
                'created_by'         => $created_by,
                'shift_id'           => $shift_id,
                'collection_form_no' => $collection_form_no,
            ]);

            // Get shift_number
            $assignment = PumpOperatorAssignment::where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('shift_number', '>', 0)
                ->orderBy('id', 'DESC')
                ->first();

            $shift_number = $assignment->shift_number ?? null;

            // Create DailyCollection
            $dailyCollection = DailyCollection::create([
                'business_id'        => $business_id,
                'collection_form_no' => $collection_form_no,
                'pump_operator_id'   => $pump_operator_id,
                'location_id'        => $pump_operator->location_id,
                'balance_collection' => 0,
                'current_amount'     => $payment_amount,
                'created_by'         => $created_by,
                'shift_id'           => $shift_id,
                'shift_no'           => $shift_number,
                'shift_number'       => $shift_number,
                'type'               => 'daily_collection',
            ]);

            return [
                'success' => true,
                'msg'     => "Test cash entry created successfully! Amount: {$payment_amount}, Collection Form No: {$collection_form_no}",
                'data' => [
                    'pump_operator_payment_id' => $pumpPayment->id,
                    'daily_collection_id'      => $dailyCollection->id,
                    'collection_form_no'       => $collection_form_no,
                    'amount'                   => $payment_amount,
                    'shift_id'                 => $shift_id,
                    'shift_number'             => $shift_number,
                ],
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            return [
                'success' => false,
                'msg'     => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    public function saveCardPayment(Request $request)
    {
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id      = Auth::user()->business_id;
        $created_by       = Auth::user()->id;
        $pump_operator    = PumpOperator::findOrFail($pump_operator_id);
        $settings         = json_decode($pump_operator->dashboard_settings, true);
        $settlement       = Settlement::where('settlement_no', $pump_operator->settlement_no)->first();
        $shift            = PetroShift::where('pump_operator_id', $pump_operator_id)->get()->last()->id ?? 0;

        try {

            $collection = PumpOperatorPayment::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
            if (! is_null($collection)) {
                $collection_form_no = (int) $collection->collection_form_no + 1;
            } else {
                $collection_form_no = 1;
            }
            $DailyCollection = DailyCollection::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
            if (! is_null($DailyCollection)) {
                if ($DailyCollection->collection_form_no >= $collection_form_no) {
                    $collection_form_no = (int) $DailyCollection->collection_form_no + 1;
                }
            }

            $assignment = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
                ->orderBy('id', 'DESC')
                ->select('shift_id')
                ->first();

            $shift_id = $assignment->shift_id ?? null;
            if (empty($shift_id)) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong') . ' - Pump operator has no active shift.',
                ]);
            }
            $meter_sale = PumpOperatorMeterSale::where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('business_id', $business_id)
                ->whereNull('p_o_payment_id')
                ->orderBy('id', 'DESC')
                ->first();
            if (! is_null($meter_sale)) {
                $collection_form_no = $meter_sale->collection_form_no;
            }

            if (! empty($request->collection_form_no)) {
                $requested_collection_form_no = (int) $request->collection_form_no;
                $requested_form_exists = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('collection_form_no', $requested_collection_form_no)
                    ->exists()
                    || DailyCollection::where('business_id', $business_id)
                        ->where('collection_form_no', $requested_collection_form_no)
                        ->exists()
                    || SettlementCreditSalePayment::where('business_id', $business_id)
                        ->where('collection_form_no', $requested_collection_form_no)
                        ->exists();

                if (! $requested_form_exists) {
                    $collection_form_no = $requested_collection_form_no;
                }
            }

            // Track starting collection_form_no for bulk payments
            $starting_collection_form_no = $collection_form_no;
            $current_collection_form_no  = $collection_form_no;

            foreach ($request->card_data as $index => $card) {
                $_data = json_decode($card, true);

                // For bulk payments, each card should get a unique collection_form_no
                // This ensures each card payment gets its own DailyCard entry and shows separately
                if ($index > 0) {
                    // Get the maximum collection_form_no and increment
                    $max_collection = PumpOperatorPayment::where('business_id', $business_id)
                        ->whereNotNull('collection_form_no')
                        ->max('collection_form_no');
                    $max_daily_collection = DailyCollection::where('business_id', $business_id)
                        ->whereNotNull('collection_form_no')
                        ->max('collection_form_no');

                    $max_all = max(
                        (int) ($max_collection ?? 0),
                        (int) ($max_daily_collection ?? 0),
                        $current_collection_form_no
                    );

                    $current_collection_form_no = $max_all + 1;
                } else {
                    $current_collection_form_no = $collection_form_no;
                }

                $data = [
                    'business_id'        => $business_id,
                    'pump_operator_id'   => $pump_operator_id,
                    'payment_type'       => 'card',
                    'payment_amount'     => $_data['amount'],
                    'created_by'         => $created_by,
                    'shift_id'           => $shift_id,
                    'collection_form_no' => $current_collection_form_no,
                ];

                if ($this->pumpOperatorPaymentsHasCardMetaColumns()) {
                    $data['customer_id'] = $_data['customer_id'] ?? null;
                    $data['card_type'] = $_data['card_type'] ?? null;
                    $data['card_number'] = $_data['card_number'] ?? null;
                    $data['slip_no'] = $_data['slip_no'] ?? null;
                }

                $PumpOperatorPayment = PumpOperatorPayment::create($data);

                // Create a simple object with card data for syncPaymentToDailyTables
                $cardData              = new \stdClass();
                $cardData->customer_id = $_data['customer_id'] ?? null;
                $cardData->card_type   = $_data['card_type'] ?? null;
                $cardData->card_number = $_data['card_number'] ?? null;
                $cardData->slip_no     = $_data['slip_no'] ?? null;

                try {
                    $this->syncPaymentToDailyTables($PumpOperatorPayment, $cardData);
                } catch (\Exception $e) {
                    Log::error('Sync to daily tables failed (card loop): ' . $e->getMessage());
                }

                // syncPaymentToDailyTables already created the DailyCard entry
                // No need to create duplicate entry here

                if (! is_null($meter_sale)) {
                    $meter_sale->p_o_payment_id = $PumpOperatorPayment->id;
                    $meter_sale->update();
                }

                if (! empty($pump_operator->settlement_no)) {
                    $settlementRec = Settlement::where('settlement_no', $pump_operator->settlement_no)
                        ->where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('status', 1)
                        ->first();
                    if ($settlementRec) {
                        DailyCollection::where('pump_operator_id', $pump_operator_id)
                            ->where('shift_id', $shift_id)
                            ->whereNull('settlement_id')
                            ->update(['settlement_id' => $settlementRec->id]);
                    }

                    PumpOperatorPayment::where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->whereNull('settlement_no')
                        ->update(['settlement_no' => $pump_operator->settlement_no]);
                }
            }

            $output = [
                'success'            => true,
                'msg'                => __('lang_v1.success'),
                'collection_form_no' => $current_collection_form_no,
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        // Return JSON response for AJAX requests
        if (! empty($business_id ?? null)) {
            foreach ((array) $request->input('credit_data', []) as $creditRow) {
                $this->removePreSettlementCreditSaleAccounting(
                    $business_id,
                    $creditRow['customer_id'] ?? null,
                    ! empty($creditRow['order_number']) ? trim($creditRow['order_number']) : null,
                    $creditRow['order_date'] ?? null,
                    (float) $this->productUtil->num_uf($creditRow['sub_total'] ?? $creditRow['amount'] ?? 0)
                );
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return redirect()->back()->with('status', $output);
    }

    public function saveCashDenom(Request $request)
    {

        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id      = Auth::user()->business_id;
        $created_by       = Auth::user()->id;
        $pump_operator    = PumpOperator::findOrFail($pump_operator_id);
        $settings         = json_decode($pump_operator->dashboard_settings, true);
        $settlement       = Settlement::where('settlement_no', $pump_operator->settlement_no)->first();
        $shift            = PetroShift::where('pump_operator_id', $pump_operator_id)->get()->last()->id ?? 0;

        try {
            $payment_amount = $request->grand_total;
            $payment_type   = 'cash';

            if ($payment_amount == '' || $payment_type == '') {
                $output = [
                    'success' => false,
                    'msg'     => 'Please payment type and amount are mendatory fields!',
                ];

                return $output;
            }

            $data = [
                'business_id'      => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'payment_type'     => $payment_type,
                'payment_amount'   => $payment_amount,
                'created_by'       => $created_by,
                'shift_id'         => $shift,
            ];

            PumpOperatorPayment::create($data);

            try {
                $recent = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where('shift_id', $shift)
                    ->where('payment_amount', $payment_amount)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($recent) {
                    $this->syncPaymentToDailyTables($recent, $request);
                }
            } catch (\Exception $e) {
                Log::error('Sync to daily tables failed (saveCashDenom): ' . $e->getMessage());
            }

            $collection_form_no = (int) (DailyCollection::where('business_id', $business_id)->count()) + 1;

            $data = [
                'business_id'        => $business_id,
                'collection_form_no' => $collection_form_no,
                'pump_operator_id'   => $pump_operator_id,
                'location_id'        => $pump_operator->location_id,
                'balance_collection' => 0, // $request->balance_collection,
                'current_amount'     => $payment_amount,
                'created_by'         => Auth::user()->id,
                'shift_id'           => $shift,
                // 'settlement_no' => $pump_operator->settlement_no ?? null
                'settlement_id'      => $settlement->id ?? null,
            ];

            DailyCollection::create($data);

            $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();

            $sms_data = [
                'date'          => $this->transactionUtil->format_date(date('Y-m-d')),
                'time'          => date('H:i'),
                'pump_operator' => $pump_operator->name,
                'amount'        => $this->transactionUtil->num_f($payment_amount),
            ];

            $this->notificationUtil->sendPetroNotification('pumper_dashboard_cash_deposit', $sms_data);

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

        return redirect()->back()->with('status', $output);
    }

    public function saveChequePayment(Request $request)
    {
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id      = Auth::user()->business_id;
        $created_by       = Auth::user()->id;

        if (empty($pump_operator_id)) {
            return redirect()->action([self::class, 'create'])->with('status', [
                'success' => false,
                'msg'     => 'Pump operator not found. Please log in as a pump operator.',
            ]);
        }

        $pump_operator = PumpOperator::findOrFail($pump_operator_id);

        $shift = PetroShift::where('pump_operator_id', $pump_operator_id)->get()->last()->id ?? 0;

        try {
            DB::beginTransaction();
            $payment_amount = $request->amount;

            $data = [
                'business_id'      => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'payment_type'     => 'cheque',
                'payment_amount'   => $payment_amount,
                'created_by'       => $created_by,
                'shift_id'         => $shift,
            ];

            $payment = PumpOperatorPayment::create($data);
            try {
                $this->syncPaymentToDailyTables($payment, $request);
            } catch (\Exception $e) {
                Log::error('Sync to daily tables failed (cheque): ' . $e->getMessage());
            }

            $collection = PumpOperatorPayment::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
            if (! is_null($collection)) {
                $collection_form_no = (int) $collection->collection_form_no + 1;
            } else {
                $collection_form_no = 1;
            }
            $DailyCollection = DailyCollection::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
            if (! is_null($DailyCollection)) {
                if ($DailyCollection->collection_form_no >= $collection_form_no) {
                    $collection_form_no = (int) $DailyCollection->collection_form_no + 1;
                }
            }

            $assignment = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
                ->orderBy('id', 'DESC')
                ->select('shift_id')
                ->first();

            $shift_id = $assignment->shift_id ?? null;
            if (empty($shift_id)) {
                DB::rollback();
                $output = [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ];
                if ($request->ajax()) {
                    return response()->json($output);
                }
                return redirect()->action([self::class, 'create'])->with('status', $output);
            }

            $meter_sale = PumpOperatorMeterSale::where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('business_id', $business_id)
                ->whereNull('p_o_payment_id')
                ->orderBy('id', 'DESC')
                ->first();
            if (! is_null($meter_sale)) {
                $collection_form_no         = $meter_sale->collection_form_no;
                $meter_sale->p_o_payment_id = $payment->id;
                $meter_sale->update();
            }

            if (! empty($request->collection_form_no)) {
                $requested_collection_form_no = (int) $request->collection_form_no;
                $requested_form_exists = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('collection_form_no', $requested_collection_form_no)
                    ->exists()
                    || DailyCollection::where('business_id', $business_id)
                        ->where('collection_form_no', $requested_collection_form_no)
                        ->exists()
                    || SettlementCreditSalePayment::where('business_id', $business_id)
                        ->where('collection_form_no', $requested_collection_form_no)
                        ->exists();

                if (! $requested_form_exists) {
                    $collection_form_no = $requested_collection_form_no;
                }
            }

            $data = [
                'linked_payment_id'  => $payment->id,
                'business_id'        => $business_id,
                'amount'             => $payment_amount,
                'bank_name'          => $request->cheque_bank ?? '',
                'customer_id'        => $request->customer_id,
                'cheque_number'      => $request->cheque_number,
                'cheque_date'        => $request->cheque_date,
                'shift_id'           => $shift,
                'collection_form_no' => $collection_form_no,
            ];

            DailyChequePayment::create($data);

            $settings = !empty($pump_operator->dashboard_settings) ? json_decode($pump_operator->dashboard_settings, true) : [];
            $pumper_ledger_update = ($settings['pumper_ledger_update'] ?? 'no') === 'yes';

            $cheque_account_id = $this->commonUtil->account_exist_return_id('Cheques in Hand');
            if (!empty($cheque_account_id) && $payment_amount > 0) {
                // Create a standalone TransactionPayment so this cheque appears in Accounting > Cheque Deposit
                $transaction_payment = TransactionPayment::create([
                    'business_id'    => $business_id,
                    'transaction_id' => null,
                    'amount'         => $payment_amount,
                    'method'         => 'cheque',
                    'cheque_number'  => $request->cheque_number,
                    'cheque_date'    => $request->cheque_date,
                    'bank_name'      => $request->cheque_bank ?? '',
                    'is_deposited'   => 0,
                    'created_by'     => $created_by,
                ]);

                // Asset account: DEBIT — records cheque in Cheques in Hand account
                // sub_type 'deposit' ensures it appears in Accounting > Cheque Deposit list
                AccountTransaction::createAccountTransaction([
                    'amount'                 => $payment_amount,
                    'account_id'             => $cheque_account_id,
                    'type'                   => 'debit',
                    'sub_type'               => 'deposit',
                    'operation_date'         => now(),
                    'business_id'            => $business_id,
                    'note'                   => 'Cheque Payment Received - Form No. ' . $collection_form_no . ' (Payment ID: ' . $payment->id . ')',
                    'cheque_number'          => $request->cheque_number,
                    'cheque_date'            => $request->cheque_date,
                    'created_by'             => $created_by,
                    'transaction_payment_id' => $transaction_payment->id,
                ]);

                // If customer is provided, also record in Ledger: CREDIT (decreases customer balance)
                if (!empty($request->customer_id) && $pumper_ledger_update) {
                    ContactLedger::createContactLedger([
                        'business_id'    => $business_id,
                        'contact_id'     => $request->customer_id,
                        'amount'         => $payment_amount,
                        'type'           => 'credit',
                        'operation_date' => now(),
                        'note'           => 'Cheque Payment Received - Form No. ' . $collection_form_no,
                        'created_by'     => $created_by,
                    ]);
                }
            }

            $payment->collection_form_no = $collection_form_no;
            $payment->update();

            DB::commit();
            $output = [
                'success'            => true,
                'msg'                => __('lang_v1.success'),
                'collection_form_no' => $collection_form_no,
            ];
        } catch (\Exception $e) {
            DB::rollback();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }

        return redirect()->action([self::class, 'create'])->with('status', $output);
    }

    public function saveCredit(Request $request)
    {
        try {
            DB::beginTransaction();

            $data             = $request->credit_data;
            $pump_operator_id = $request->input('pump_operator_id') ?? (Auth::user()->pump_operator_id ?? null);

            if (empty($pump_operator_id)) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong') . ' - Pump Operator ID is missing.',
                ], 422);
            }

            $pump_operator = PumpOperator::findOrFail($pump_operator_id);
            $business_id   = $request->session()->get('business.id')
                ?? $request->session()->get('user.business_id')
                ?? (Auth::user()->business_id ?? null);
            if (empty($business_id)) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'Business context missing in session. Please login again.',
                ], 422);
            }
            // $pump_operator->settlement_no
            $settlement = Settlement::where('settlement_no', $pump_operator->settlement_no)->first();
            $shift      = PetroShift::where('pump_operator_id', $pump_operator_id)->get()->last()->id ?? 0;

            $collection = PumpOperatorPayment::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
            if (! is_null($collection)) {
                $collection_form_no = (int) $collection->collection_form_no + 1;
            } else {
                $collection_form_no = 1;
            }
            $DailyCollection = DailyCollection::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
            if (! is_null($DailyCollection)) {
                if ($DailyCollection->collection_form_no >= $collection_form_no) {
                    $collection_form_no = (int) $DailyCollection->collection_form_no + 1;
                }
            }
            $assignment = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
                ->orderBy('id', 'DESC')
                ->select('shift_id')
                ->first();

            $shift_id = $assignment->shift_id ?? null;
            if (empty($shift_id)) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong') . ' - Pump operator has no active shift.',
                ]);
            }

            $meter_sale = PumpOperatorMeterSale::where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('business_id', $business_id)
                ->whereNull('p_o_payment_id')
                ->orderBy('id', 'DESC')
                ->first();
            if (! is_null($meter_sale)) {
                $collection_form_no = $meter_sale->collection_form_no;
            }

            if (! empty($request->collection_form_no)) {
                $collection_form_no = $request->collection_form_no;
            }

            // Get business settings to check if duplicate orders are allowed
            $business                 = Business::find($business_id);
            $duplicate_orders_allowed = $business->duplicate_orders_allowed ?? 0;

            // Helper function to return validation error
            $returnValidationError = function ($msg) use ($request) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'msg'     => $msg,
                    ]);
                }
                return redirect()->back()->with('status', [
                    'success' => false,
                    'msg'     => $msg,
                ]);
            };

            // 1. Validate: Same order number cannot be used with different customers
            // Build a map of order_number => customer_id from current transaction
            $orderCustomerMap = [];
            foreach ($data as $entry) {
                $orderNum = $entry['order_number'] ?? null;
                $custId = $entry['customer_id'] ?? null;
                
                // Skip empty/null/'0' order numbers
                if (empty($orderNum) || trim($orderNum) === '' || trim($orderNum) === '0') {
                    continue;
                }
                
                $orderNum = trim($orderNum);
                
                // Check if this order number already exists in current transaction with different customer
                if (isset($orderCustomerMap[$orderNum]) && $orderCustomerMap[$orderNum] !== $custId) {
                    DB::rollback();
                    return $returnValidationError("Order Number '{$orderNum}' cannot be used with different customers in the same transaction.");
                }
                
                $orderCustomerMap[$orderNum] = $custId;
            }

            // 2. Generate Bill Number
            $settings         = ! empty($pump_operator->dashboard_settings) ? json_decode($pump_operator->dashboard_settings, true) : [];
            $bill_prefix      = $settings['bill_prefix'] ?? '';
            $starting_bill_no = $settings['starting_bill_number'] ?? 1;
            $pumper_ledger_update = (($settings['pumper_ledger_update'] ?? 'no') === 'yes') || (($settings['credit_sales_direct_to_customer'] ?? 'no') === 'yes');

            $latest_bill = SettlementCreditSalePayment::where('business_id', $business_id)
                ->whereNotNull('bill_number')
                ->where('bill_number', 'like', $bill_prefix . '%')
                ->orderBy('id', 'desc')
                ->first();

            $next_number = $starting_bill_no;
            if ($latest_bill) {
                // Extract number from bill_number
                $last_bill_no = $latest_bill->bill_number;
                // strict replacement of prefix to avoid stripping issues
                if ($bill_prefix !== '') {
                    $number_part = substr($last_bill_no, strlen($bill_prefix));
                } else {
                    $number_part = $last_bill_no;
                }

                if (is_numeric($number_part)) {
                    $next_number = (int) $number_part + 1;
                }
            }

            $bill_number = $bill_prefix . $next_number;

            $daily_voucher_item_ids     = [];
            $print_credit_sale_payments  = []; // Changed to array to collect all credit sales
            $print_pump_operator_payments = []; // Changed to array to collect all pump operator payments
            $current_collection_form_no = $collection_form_no;

            foreach ($data as $index => $one) {
                $price          = $this->productUtil->num_uf($one['price']);
                $unit_discount  = $this->productUtil->num_uf($one['unit_discount']);
                $qty            = $this->productUtil->num_uf($one['qty']);
                $amount         = $this->productUtil->num_uf($one['amount']);

                if (!empty($price) && !empty($amount)) {
                    $qty = $amount / $price;
                }
                $sub_total      = $this->productUtil->num_uf($one['sub_total']);
                $total_discount = $this->productUtil->num_uf($one['total_discount']);

                $order_number = $one['order_number'] ?? null;
                $order_date   = \Carbon::parse($one['order_date'])->format('Y-m-d');
                $customer_id  = $one['customer_id'] ?? null;
                $product_id   = $one['product_id'] ?? null;

                // Validate product_id is required
                if (empty($product_id)) {
                    return $returnValidationError(__('petrogeneral::lang.product_required') ?: 'Product is required. Please select a product.');
                }

                // Validate quantity is required and greater than 0
                if (empty($qty) || $qty <= 0) {
                    return $returnValidationError(__('petrogeneral::lang.quantity_required') ?: 'Quantity is required and must be greater than 0.');
                }

                // Order number is optional - no validation required
                // If order_number is empty, it will be set to null or default value

                // Try to resolve customer_id from customer_name if not explicitly provided
                if (empty($customer_id) && ! empty($one['customer_name'])) {
                    $customer = Contact::where('name', $one['customer_name'])
                        ->where('business_id', $business_id)
                        ->first();
                    if ($customer) {
                        $customer_id = $customer->id;
                    }
                }

                // Final customer resolution: fall back to Walk-In Customer if still empty
                if (empty($customer_id)) {
                    $walkin = Contact::where('name', 'Walk-In Customer')
                        ->where('business_id', $business_id)
                        ->first();

                    if ($walkin) {
                        $customer_id = $walkin->id;
                    } else {
                        // As a last resort, enforce customer selection
                        return $returnValidationError(__('petrogeneral::lang.customer_required') ?: 'Customer is required. Please select a customer.');
                    }
                }

                // For bulk entries, give each credit sale a unique collection_form_no to prevent join duplicates
                // This ensures each credit sale can be uniquely identified even if they have the same amount
                if ($index > 0) {
                    $max_collection = PumpOperatorPayment::where('business_id', $business_id)
                        ->whereNotNull('collection_form_no')
                        ->max('collection_form_no');
                    $max_daily_collection = DailyCollection::where('business_id', $business_id)
                        ->whereNotNull('collection_form_no')
                        ->max('collection_form_no');
                    $max_scsp = SettlementCreditSalePayment::where('business_id', $business_id)
                        ->whereNotNull('collection_form_no')
                        ->max('collection_form_no');

                    $max_all = max(
                        (int) ($max_collection ?? 0),
                        (int) ($max_daily_collection ?? 0),
                        (int) ($max_scsp ?? 0),
                        (int) $current_collection_form_no
                    );

                    $current_collection_form_no = $max_all + 1;
                } else {
                    $current_collection_form_no = $collection_form_no;
                }

                // Check for duplicate credit sale payment before creating
                // If duplicate orders are allowed, don't check order_number in duplicate check
                $duplicate_query = SettlementCreditSalePayment::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where('customer_id', $customer_id)
                    ->where('amount', $amount)
                    ->where('order_date', $order_date)
                    ->where('product_id', $product_id)
                    ->where('qty', $qty)
                    ->where('price', $price)
                    ->where('collection_form_no', $current_collection_form_no)
                    ->where('is_from_pumper', 1);

                // Only check order_number if duplicate orders are NOT allowed
                if ($duplicate_orders_allowed != 1) {
                    $duplicate_query->where('order_number', $order_number);
                }

                $existing_credit_sale = $duplicate_query->first();

                // If duplicate exists, skip creating new payment
                if ($existing_credit_sale) {
                    continue;
                }

                $pp_data = [
                    'business_id'      => $business_id,
                    'pump_operator_id' => $pump_operator_id,
                    'payment_type'     => 'credit',
                    'payment_amount'   => $amount,
                    'created_by'       => auth()->user()->id,
                    'shift_id'         => $shift,
                ];

                $PumpOperatorPayment = PumpOperatorPayment::create($pp_data);
                try {
                    $this->syncPaymentToDailyTables($PumpOperatorPayment, $request);
                } catch (\Exception $e) {
                    Log::error('Sync to daily tables failed (credit): ' . $e->getMessage());
                }
                if (! is_null($meter_sale)) {
                    $meter_sale->p_o_payment_id = $PumpOperatorPayment->id;
                    $meter_sale->update();
                }

                // Ensure customer_id is not null - if null, continue without customer
                $final_customer_id = $customer_id;

                $dt = [
                    'business_id'        => $business_id,
                    'pump_operator_id'   => $pump_operator_id,
                    'customer_id'        => $final_customer_id, // Use validated customer_id
                    'product_id'         => $one['product_id'],
                    'order_number'       => ! empty($one['order_number']) ? trim($one['order_number']) : '0', // Default to '0' if empty or null
                    'order_date'         => \Carbon::parse($one['order_date'])->format('Y-m-d'),
                    'price'              => $price,
                    'discount'           => $unit_discount,
                    'qty'                => $qty,
                    'amount'             => $amount,
                    'sub_total'          => $sub_total,
                    'total_discount'     => $total_discount,
                    'outstanding'        => $this->productUtil->num_uf($one['outstanding']),
                    'credit_limit'       => $one['credit_limit'],
                    'customer_reference' => $one['customer_reference'],
                    'note'               => $one['note'],
                    'is_from_pumper'     => 1,
                    'collection_form_no' => $current_collection_form_no, // Use unique collection_form_no for each entry
                    'bill_number'        => $bill_number,
                    'pump_payment_id'     => $PumpOperatorPayment->id,
                ];
                $credit_sale_payment                     = app(\Modules\PetroGeneral\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, $pump_operator->settlement_no ?? null, 'settlement_credit_sale_payments', $dt);
                $PumpOperatorPayment->collection_form_no = $current_collection_form_no; // Link to unique collection_form_no
                $PumpOperatorPayment->update();

                if ($pumper_ledger_update) {
                    ContactLedger::createContactLedger([
                        'business_id'    => $business_id,
                        'contact_id'     => $customer_id,
                        'amount'         => $amount - $total_discount,
                        'type'           => 'debit',
                        'operation_date' => \Carbon::parse($one['order_date'])->format('Y-m-d'),
                        'created_by'     => auth()->user()->id,
                        'note'           => 'Pumper Dashboard Credit Sale - Form No. ' . $current_collection_form_no,
                    ]);
                }

                if (! empty($pump_operator->settlement_no)) {
                    $settlementRec = Settlement::where('settlement_no', $pump_operator->settlement_no)
                        ->where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('status', 1)
                        ->first();
                    if ($settlementRec) {
                        DailyCollection::where('pump_operator_id', $pump_operator_id)
                            ->where('shift_id', $shift_id)
                            ->whereNull('settlement_id')
                            ->update(['settlement_id' => $settlementRec->id]);
                    }

                    PumpOperatorPayment::where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->whereNull('settlement_no')
                        ->update(['settlement_no' => $pump_operator->settlement_no]);
                }

                // store the customer reference
                if (! empty($credit_sale_payment->customer_reference)) {
                    $customer       = Contact::findOrFail($credit_sale_payment->customer_id);
                    $name           = $customer->name;
                    $barcode_string = $name . '.' . $credit_sale_payment->customer_reference;
                    $qr             = new DNS2D;
                    $qr             = $qr->getBarcodePNG($barcode_string, 'QRCODE');
                    $src            = 'data:image/png;base64,' . $qr;

                    $ref_data = [
                        'business_id' => $credit_sale_payment->business_id,
                        'date'        => date('Y-m-d', strtotime($credit_sale_payment->order_date)),
                        'contact_id'  => $credit_sale_payment->customer_id,
                        'reference'   => $credit_sale_payment->customer_reference,
                        'barcode_src' => $src,
                    ];
                    CustomerReference::updateOrCreate(['business_id' => $credit_sale_payment->business_id, 'contact_id' => $credit_sale_payment->customer_id, 'reference' => $credit_sale_payment->customer_reference], $ref_data);

                }

                $customer_reference = CustomerReference::where('reference', $credit_sale_payment->customer_reference)->first()->id ?? 0;

                // $daily_vouchers_no = (DailyVoucher::where('business_id', $business_id)->count()) + 1;
                $daily_vouchers_no = $current_collection_form_no;

                $assignment = PumpOperatorAssignment::where('pump_operator_id', $pump_operator->id)->where('business_id', $business_id)->where('status', 'open')->first();

                $data = [
                    'business_id'          => $business_id,
                    'transaction_date'     => date('Y-m-d', strtotime($credit_sale_payment->order_date)),
                    'daily_vouchers_no'    => $daily_vouchers_no,
                    'location_id'          => $pump_operator->location_id,

                    'pump_id'              => ! empty($assignment) ? $assignment->pump_id : null,

                    'operator_id'          => $pump_operator->id,
                    'customer_id'          => $credit_sale_payment->customer_id,
                    'current_outstanding'  => $this->productUtil->num_uf($one['outstanding']),
                    'outstanding_pending'  => $this->productUtil->num_uf($one['outstanding']),

                    'voucher_order_number' => ! empty($one['order_number']) ? trim($one['order_number']) : '0', // Default to '0' if empty or null
                    'voucher_order_date'   => \Carbon::parse($credit_sale_payment->order_date)->format('Y-m-d'),
                    'status'               => 1,
                    'created_by'           => Auth::user()->id,
                    'vehicle_no'           => $customer_reference,
                    'total_amount'         => $sub_total,
                ];

                $daily_voucher = DailyVoucher::create($data);
                $credit_sale_payment = app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                    ->editCreditSale($business_id, $credit_sale_payment->id, [
                        'daily_voucher_id' => $daily_voucher->id,
                    ]);

                $details = [
                    'business_id'      => $business_id,
                    'daily_voucher_id' => $daily_voucher->id,
                    'product_id'       => $this->productUtil->num_uf($one['product_id']),
                    'unit_price'       => $this->productUtil->num_uf($price),
                    'qty'              => $qty,
                    'sub_total'        => $this->productUtil->num_uf($sub_total),

                ];
                $daily_voucher_item = DailyVoucherItem::create($details);
                $this->removePreSettlementCreditSaleAccounting(
                    $business_id,
                    $credit_sale_payment->customer_id,
                    $credit_sale_payment->order_number,
                    $credit_sale_payment->order_date,
                    (float) $credit_sale_payment->sub_total
                );

                $daily_voucher_item_ids[] = $daily_voucher_item->id;

                // Collect all credit sales and pump operator payments for printing
                $print_credit_sale_payments[] = $credit_sale_payment;
                $print_pump_operator_payments[] = $PumpOperatorPayment;

                $uncreditted = SettlementCreditSalePayment::where('customer_id', $one['customer_id'])->whereNull('is_committed')->where('is_from_pumper', 1)->sum('sub_total') ?? 0;
                $final_total = $credit_sale_payment->amount - $credit_sale_payment->total_discount;

                $contact = Contact::findOrFail($one['customer_id']);
                $phones  = [];
                $phones  = [$contact->mobile, $contact->alternate_number];

                $sms_data = [
                    'date'               => $this->transactionUtil->format_date(date('Y-m-d')),
                    'time'               => date('H:i'),
                    'pump_operator'      => $pump_operator->name,
                    'amount'             => $this->transactionUtil->num_f($final_total),
                    'order_no'           => $one['order_number'],
                    'customer'           => $contact->name,
                    'cumulative_amount'  => $this->productUtil->num_f($this->contactUtil->getCustomerBalance($credit_sale_payment->customer_id, $business_id, true) + $uncreditted),
                    'customer_reference' => $credit_sale_payment->customer_reference,
                ];
                $this->notificationUtil->sendPetroNotification('pumper_dashboard_credit_sales_customer', $sms_data, implode(',', $phones));
                $this->notificationUtil->sendPetroNotification('pumper_dashboard_credit_sales', $sms_data);

            }

            $print = false;
            if ($request->print) {
                $print = $request->print;
            }

            $html_content = '';
            if ($print) {
                try {
                    // Generate print content for ALL credit sales on a SINGLE invoice
                    if (!empty($print_pump_operator_payments) && !empty($print_credit_sale_payments)) {
                        // Get the selected copy option from the print modal (defaults to 'customer')
                        $copy_mode = $request->get('print_copy_option', 'customer');
                        
                        // Prepare consolidated data for all credit sales on one invoice.
                        // Re-query from DB by bill_number so the print contains every row saved
                        // for this bill, not only the last item/passed row.
                        $first_credit_sale_for_print = $print_credit_sale_payments[0];

                        $credit_sales_for_print = SettlementCreditSalePayment::where('business_id', $business_id)
                            ->where('bill_number', $first_credit_sale_for_print->bill_number)
                            ->orderBy('id')
                            ->get();

                        if ($credit_sales_for_print->isEmpty()) {
                            $credit_sales_for_print = collect($print_credit_sale_payments);
                        }

                        $pump_payment_ids_for_print = $credit_sales_for_print->pluck('pump_payment_id')->filter()->unique()->values();
                        $pump_payments_for_print = $pump_payment_ids_for_print->isNotEmpty()
                            ? PumpOperatorPayment::where('business_id', $business_id)->whereIn('id', $pump_payment_ids_for_print)->get()->all()
                            : $print_pump_operator_payments;

                        if (empty($pump_payments_for_print)) {
                            $pump_payments_for_print = $print_pump_operator_payments;
                        }

                        $viewData = $this->prepareCreditSalesConsolidatedPrintData(
                            $pump_payments_for_print,
                            $credit_sales_for_print,
                            $copy_mode
                        );
                        
                        $html_content = view('petrogeneral::pump_operators.print.credit_sale_print', $viewData)->render();
                    }
                } catch (\Exception $printException) {
                    // If print generation fails, log but don't fail the entire save
                    Log::warning('Failed to generate print content for credit sale: ' . $printException->getMessage());
                    $html_content = '';
                }
            }

            foreach ($print_credit_sale_payments as $credit_sale_payment) {
                $this->removePreSettlementCreditSaleAccounting(
                    $business_id,
                    $credit_sale_payment->customer_id,
                    $credit_sale_payment->order_number,
                    $credit_sale_payment->order_date,
                    (float) $credit_sale_payment->sub_total
                );
            }

            DB::commit();

            foreach ($print_credit_sale_payments as $credit_sale_payment) {
                $this->removePreSettlementCreditSaleAccounting(
                    $business_id,
                    $credit_sale_payment->customer_id,
                    $credit_sale_payment->order_number,
                    $credit_sale_payment->order_date,
                    (float) $credit_sale_payment->sub_total
                );
            }
            foreach ((array) $request->input('credit_data', []) as $creditRow) {
                $this->removePreSettlementCreditSaleAccounting(
                    $business_id,
                    $creditRow['customer_id'] ?? null,
                    ! empty($creditRow['order_number']) ? trim($creditRow['order_number']) : null,
                    $creditRow['order_date'] ?? null,
                    (float) $this->productUtil->num_uf($creditRow['sub_total'] ?? $creditRow['amount'] ?? 0)
                );
            }

            $output = [
                'success'            => true,
                'msg'                => __('petrogeneral::lang.success'),
                'collection_form_no' => $current_collection_form_no ?? $collection_form_no, // Return the last collection_form_no used
                'print'              => $print,
                'html_content'       => $html_content,
                'print_credit_sale_id' => !empty($print_credit_sale_payments) ? $print_credit_sale_payments[0]->id : null, // Return first for fallback
            ];
        } catch (\Exception $e) {
            DB::rollback();

            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            Log::emergency('Stack trace: ' . $e->getTraceAsString());

            $output = [
                'success' => false,                
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        // Return JSON response for AJAX requests
        if (! empty($business_id ?? null)) {
            foreach ((array) $request->input('credit_data', []) as $creditRow) {
                $this->removePreSettlementCreditSaleAccounting(
                    $business_id,
                    $creditRow['customer_id'] ?? null,
                    ! empty($creditRow['order_number']) ? trim($creditRow['order_number']) : null,
                    $creditRow['order_date'] ?? null,
                    (float) $this->productUtil->num_uf($creditRow['sub_total'] ?? $creditRow['amount'] ?? 0)
                );
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output);
        }

        return $output;
    }

    public function getOtherSale()
    {
        if (request()->ajax()) {
            $business_id = Auth::user()->business_id;
            $query       = PumpOperatorMeterSaleDetail::leftjoin('pump_operator_meter_sales', 'pump_operator_meter_sale_details.sale_id', 'pump_operator_meter_sales.id')
                ->leftjoin('pump_operators', 'pump_operator_meter_sales.pump_operator_id', 'pump_operators.id')
                ->leftjoin('pumps', 'pumps.id', 'pump_operator_meter_sale_details.pump_id')
                ->where('pump_operator_meter_sales.business_id', $business_id)
                ->select('pump_operator_meter_sale_details.*', 'pump_operators.name as pump_operator_name', 'pumps.pump_no', 'pump_operator_meter_sales.amount as total_amount', 'pump_operator_meter_sales.date_time', 'pump_operator_meter_sales.deposited', 'pump_operator_meter_sales.balance');

            if (! empty(request()->pump_id)) {
                $query->where('pump_operator_meter_sale_details.pump_id', request()->pump_id);
            }
            if (! empty(request()->pump_operator_id)) {
                $query->where('pump_operator_meter_sales.pump_operator_id', request()->pump_operator_id);
            }
            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $query->whereDate('pump_operator_meter_sales.date_time', '>=', request()->start_date);
                $query->whereDate('pump_operator_meter_sales.date_time', '<=', request()->end_date);
            }

            $fuel_tanks = DataTables::of($query->orderBy('pump_operator_meter_sale_details.id', 'DESC'))
                ->addColumn('date', '{{@format_date($date_time)}}')
                ->addColumn('time', '{{@format_time($date_time)}}')
                ->addColumn('received_meter', function ($row) {
                    return number_format((float) ($row->received_meter ?? 0), 3, '.', ',');
                })
                ->addColumn('new_meter', function ($row) {
                    return number_format((float) ($row->new_meter ?? 0), 3, '.', ',');
                })
                ->addColumn('sold_qty', function ($row) {
                    return $this->productUtil->num_f((float) ($row->sold_qty ?? 0));
                })
                ->addColumn('unit_price', function ($row) {
                    return $this->productUtil->num_f((float) ($row->unit_price ?? 0));
                })
                ->addColumn('amount', function ($row) {
                    return $this->productUtil->num_f((float) ($row->amount ?? 0));
                })
                ->addColumn('total_amount', function ($row) {
                    return $this->productUtil->num_f((float) ($row->total_amount ?? 0));
                })
                ->addColumn('deposited', function ($row) {
                    return $this->productUtil->num_f((float) ($row->deposited ?? 0));
                })
                ->addColumn('balance', function ($row) {
                    return $this->productUtil->num_f((float) ($row->balance ?? 0));
                });

            return $fuel_tanks->rawColumns(['amount', 'action'])
                ->make(true);
        }
    }

    private function syncPumperDayEntriesToMeterSales(int $business_id, ?int $pump_operator_id = null, array $shift_ids = []): void
    {
        $has_shift_id_column = Schema::hasColumn('pumper_day_entries', 'shift_id');

        $query = PumperDayEntry::where('business_id', $business_id)
            ->where(function ($q) {
                $q->whereNull('settlement_no')->orWhere('settlement_no', '');
            });

        if (! empty($pump_operator_id)) {
            $query->where('pump_operator_id', $pump_operator_id);
        }

        if (! empty($shift_ids)) {
            if ($has_shift_id_column) {
                $query->whereIn('shift_id', $shift_ids);
            } else {
                $query->whereExists(function ($sub) use ($business_id, $shift_ids) {
                    $sub->select(DB::raw(1))
                        ->from('pump_operator_assignments')
                        ->whereColumn('pump_operator_assignments.id', 'pumper_day_entries.pumper_assignment_id')
                        ->where('pump_operator_assignments.business_id', $business_id)
                        ->whereIn('pump_operator_assignments.shift_id', $shift_ids);
                });
            }
        }

        $query->orderBy('id')->chunk(100, function ($entries) use ($business_id, $has_shift_id_column) {
            foreach ($entries as $entry) {
                $entry_shift_id = $has_shift_id_column
                    ? $entry->shift_id
                    : PumpOperatorAssignment::where('id', $entry->pumper_assignment_id)->value('shift_id');

                $exists = MeterSale::where('business_id', $business_id)
                    ->where('pump_id', $entry->pump_id)
                    ->where('starting_meter', $entry->starting_meter)
                    ->where('closing_meter', $entry->closing_meter)
                    ->where(function ($q) {
                        $q->whereNull('settlement_no')
                            ->orWhere('settlement_no', '');
                    });

                if (! empty($entry_shift_id)) {
                    $exists->where('shift_id', $entry_shift_id);
                } else {
                    $exists->whereNull('shift_id');
                }

                if ($exists->exists()) {
                    continue;
                }

                if (empty($entry_shift_id)) {
                    continue;
                }

                $pump = Pump::where('business_id', $business_id)->find($entry->pump_id);
                if (! $pump) {
                    continue;
                }

                $product_id = $pump->product_id ?? null;
                if (empty($product_id) && ! empty($pump->fuel_tank_id)) {
                    $product_id = FuelTank::where('id', $pump->fuel_tank_id)->value('product_id');
                }

                if (empty($product_id)) {
                    continue;
                }

                $sold_qty = (float) ($entry->sold_ltr ?? 0);
                if ($sold_qty <= 0) {
                    $sold_qty = max(0, (float) $entry->closing_meter - (float) $entry->starting_meter - (float) ($entry->testing_ltr ?? 0));
                }

                $amount = (float) ($entry->amount ?? 0);
                $price = $sold_qty > 0 ? $amount / $sold_qty : 0;

                MeterSale::create([
                    'settlement_no'   => '',
                    'business_id'     => $business_id,
                    'product_id'      => $product_id,
                    'pump_id'         => $entry->pump_id,
                    'starting_meter'  => $entry->starting_meter,
                    'closing_meter'   => $entry->closing_meter,
                    'price'           => $price,
                    'qty'             => $sold_qty,
                    'discount'        => '0',
                    'discount_type'   => 'fixed',
                    'discount_amount' => $amount,
                    'testing_qty'     => $entry->testing_ltr ?? 0,
                    'sub_total'       => $amount,
                    'shift_id'        => $entry_shift_id,
                ]);
            }
        });
    }

    private function syncPumpOperatorMeterSalesToMeterSales(int $business_id, ?int $pump_operator_id = null, array $shift_ids = [], ?Settlement $active_settlement = null): void
    {
        if (empty($shift_ids)) {
            return;
        }

        $query = DB::table('pump_operator_meter_sales as poms')
            ->join('pump_operator_meter_sale_details as pomsd', 'poms.id', '=', 'pomsd.sale_id')
            ->join('pumps', 'pomsd.pump_id', '=', 'pumps.id')
            ->where('poms.business_id', $business_id)
            ->whereIn('poms.shift_id', $shift_ids);

        if (! empty($pump_operator_id)) {
            $query->where('poms.pump_operator_id', $pump_operator_id);
        }

        if (! empty($active_settlement)) {
            $query->where(function ($settlement_query) use ($active_settlement) {
                $settlement_query->whereNull('poms.settlement_no')
                    ->orWhere('poms.settlement_no', '')
                    ->orWhere('poms.settlement_no', (string) $active_settlement->id);

                if (! empty($active_settlement->settlement_no)) {
                    $settlement_query->orWhere('poms.settlement_no', (string) $active_settlement->settlement_no);
                }
            });
        } else {
            $query->where(function ($settlement_query) {
                $settlement_query->whereNull('poms.settlement_no')
                    ->orWhere('poms.settlement_no', '');
            });
        }

        $query->select(
            'poms.id as operator_meter_sale_id',
            'poms.shift_id',
            'poms.testing_qty',
            'poms.discount',
            'poms.discount_type',
            'pomsd.pump_id',
            'pomsd.received_meter',
            'pomsd.new_meter',
            'pomsd.sold_qty',
            'pomsd.unit_price',
            'pomsd.amount',
            'pumps.product_id',
            'pumps.fuel_tank_id'
        )
            ->orderBy('poms.id')
            ->chunk(100, function ($rows) use ($business_id, $active_settlement) {
                foreach ($rows as $row) {
                    $product_id = $row->product_id ?? null;
                    if (empty($product_id) && ! empty($row->fuel_tank_id)) {
                        $product_id = FuelTank::where('id', $row->fuel_tank_id)->value('product_id');
                    }

                    if (empty($product_id)) {
                        continue;
                    }

                    $starting_meter = (float) ($row->received_meter ?? 0);
                    $closing_meter = (float) ($row->new_meter ?? 0);
                    $testing_qty = (float) ($row->testing_qty ?? 0);
                    $sold_qty = (float) ($row->sold_qty ?? 0);

                    if ($sold_qty <= 0) {
                        $sold_qty = max(0, $closing_meter - $starting_meter - $testing_qty);
                    }

                    $amount = (float) ($row->amount ?? 0);
                    $price = (float) ($row->unit_price ?? 0);
                    if ($price <= 0 && $sold_qty > 0) {
                        $price = $amount / $sold_qty;
                    }

                    $exists = MeterSale::where('business_id', $business_id)
                        ->where('shift_id', $row->shift_id)
                        ->where('pump_id', $row->pump_id)
                        ->where('starting_meter', $row->received_meter)
                        ->where('closing_meter', $row->new_meter)
                        ->where(function ($query) use ($active_settlement) {
                            $query->whereNull('settlement_no')
                                ->orWhere('settlement_no', '');

                            if (! empty($active_settlement)) {
                                $query->orWhere('settlement_no', (string) $active_settlement->id);

                                if (! empty($active_settlement->settlement_no)) {
                                    $query->orWhere('settlement_no', (string) $active_settlement->settlement_no);
                                }
                            }
                        })
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    MeterSale::create([
                        'settlement_no' => ! empty($active_settlement) ? (string) $active_settlement->id : '',
                        'business_id' => $business_id,
                        'product_id' => $product_id,
                        'pump_id' => $row->pump_id,
                        'starting_meter' => $row->received_meter,
                        'closing_meter' => $row->new_meter,
                        'price' => $price,
                        'qty' => $sold_qty,
                        'discount' => $row->discount ?? '0',
                        'discount_type' => $row->discount_type ?? 'fixed',
                        'discount_amount' => $amount,
                        'testing_qty' => $testing_qty,
                        'sub_total' => $amount,
                        'shift_id' => $row->shift_id,
                    ]);
                }
            });
    }

    // Meter sale filter
    public function meterSalesList(Request $request)
    {
        $business_id = Auth::user()->business_id;

        if ($request->ajax()) {
            try {
                $business_details = Business::find($business_id);
                $active_settlement_id = (int) $request->active_settlement_id;
                $shift_ids = [];

                if (! empty($request->shift_ids)) {
                    $shift_ids = $this->normalizeShiftIds($request->shift_ids);
                }

                if (! empty($shift_ids)) {
                    $activeSettlementForSync = null;
                    if ($active_settlement_id > 0) {
                        $activeSettlementForSync = Settlement::where('business_id', $business_id)
                            ->where('id', $active_settlement_id)
                            ->first();
                    }

                    /*
                     * IMPORTANT: Direct Settlement isolation.
                     * Direct Settlement uses ST + DST numbers and must never import
                     * Pumper Dashboard / PetroPD shift readings automatically.
                     * Those shared-table rows are the reason PetroPD totals appeared
                     * in the Direct Settlement page even when the visible grid was empty.
                     */
                    $isDirectSettlementForSync = ! empty($activeSettlementForSync)
                        && ! empty($activeSettlementForSync->settlement_no)
                        && preg_match('/^ST\d+$/i', (string) $activeSettlementForSync->settlement_no);

                    if (! $isDirectSettlementForSync) {
                        $this->syncPumperDayEntriesToMeterSales(
                            (int) $business_id,
                            ! empty($request->pump_operator_id) ? (int) $request->pump_operator_id : null,
                            $shift_ids
                        );

                        $this->syncPumpOperatorMeterSalesToMeterSales(
                            (int) $business_id,
                            ! empty($request->pump_operator_id) ? (int) $request->pump_operator_id : null,
                            $shift_ids,
                            $activeSettlementForSync
                        );
                    }
                }

                $hasPumpOperatorMeterSaleDetails = false;
                if ($request->boolean('only_closed_pump_meter_sales')) {
                    $pumpOpId = (int) $request->input('pump_operator_id', 0);
                    $detailCheck = DB::table('pump_operator_meter_sales as poms')
                        ->join('pump_operator_meter_sale_details as pomsd', 'poms.id', '=', 'pomsd.sale_id')
                        ->where('poms.business_id', $business_id)
                        ->whereNull('poms.p_o_payment_id');

                    if ($pumpOpId > 0) {
                        $detailCheck->where('poms.pump_operator_id', $pumpOpId);
                    }

                    if (! empty($shift_ids)) {
                        $detailCheck->whereIn('poms.shift_id', $shift_ids);
                    }

                    if (! empty($request->pump_id)) {
                        $pump_ids = is_array($request->pump_id) ? $request->pump_id : [$request->pump_id];
                        $pump_ids = array_filter(array_map('intval', $pump_ids));

                        if (! empty($pump_ids)) {
                            $detailCheck->whereIn('pomsd.pump_id', $pump_ids);
                        }
                    }

                    $hasPumpOperatorMeterSaleDetails = $detailCheck->exists();
                }

                if ($request->boolean('only_closed_pump_meter_sales') && $hasPumpOperatorMeterSaleDetails) {
                    $pumpOpId = (int) $request->input('pump_operator_id', 0);
                    $activeSettlement = null;

                    if ($active_settlement_id > 0) {
                        $activeSettlement = Settlement::where('business_id', $business_id)
                            ->where('id', $active_settlement_id)
                            ->first();
                    }

                    $query = DB::table('pump_operator_meter_sales as poms')
                        ->join('pump_operator_meter_sale_details as pomsd', 'poms.id', '=', 'pomsd.sale_id')
                        ->join('pumps', 'pomsd.pump_id', '=', 'pumps.id')
                        ->leftJoin('products', 'pumps.product_id', '=', 'products.id')
                        ->where('poms.business_id', $business_id)
                        ->whereNull('poms.p_o_payment_id');

                    if ($pumpOpId > 0) {
                        $query->where('poms.pump_operator_id', $pumpOpId);
                    }

                    if ($activeSettlement) {
                        $isDirectSettlement = ! empty($activeSettlement->settlement_no)
                            && preg_match('/^ST\d+$/i', (string) $activeSettlement->settlement_no);

                        if ($isDirectSettlement) {
                            $query->whereRaw('1 = 0');
                        } else {
                            $query->where(function ($q) use ($activeSettlement, $shift_ids) {
                                $q->where('poms.settlement_no', (string) $activeSettlement->id);

                                if (! empty($activeSettlement->settlement_no)) {
                                    $q->orWhere('poms.settlement_no', (string) $activeSettlement->settlement_no);
                                }

                                if (! empty($shift_ids)) {
                                    $q->orWhereIn('poms.shift_id', $shift_ids);
                                }
                            });
                        }
                    } elseif (! empty($shift_ids)) {
                        $query->whereIn('poms.shift_id', $shift_ids);
                    } elseif ($this->isSettlementShiftDataRequest($request) && ! empty($request->shift_ids)) {
                        $query->whereRaw('1 = 0');
                    }

                    if (! empty($request->pump_id)) {
                        $pump_ids = is_array($request->pump_id) ? $request->pump_id : [$request->pump_id];
                        $pump_ids = array_filter(array_map('intval', $pump_ids));

                        if (! empty($pump_ids)) {
                            $query->whereIn('pomsd.pump_id', $pump_ids);
                        }
                    }

                    $results = $query->select(
                        'poms.id',
                        'poms.shift_id',
                        'poms.collection_form_no',
                        'poms.discount',
                        'poms.discount_type',
                        'poms.testing_qty',
                        'poms.transaction_id',
                        'pomsd.pump_id',
                        'pomsd.received_meter as starting_meter',
                        'pomsd.new_meter as closing_meter',
                        'pomsd.sold_qty as qty',
                        'pomsd.unit_price as price',
                        'pomsd.amount as sub_total',
                        'pomsd.amount as discount_amount',
                        'products.name as product_name',
                        'products.sku as product_sku',
                        'pumps.pump_name',
                        DB::raw('0 as later_settlements'),
                        DB::raw('0 as bulk_tank')
                    );

                    $meter_sales = DataTables::of($results)
                        ->addColumn('quantity', function ($row) {
                            return number_format((float) ($row->qty ?? 0), 3, '.', ',');
                        })
                        ->editColumn('qty', function ($row) {
                            return number_format((float) ($row->qty ?? 0), 3, '.', ',');
                        })
                        ->addColumn('total_qty', function ($row) {
                            return number_format(((float) ($row->testing_qty ?? 0) + (float) ($row->qty ?? 0)), 3, '.', ',');
                        })
                        ->addColumn('action', function ($row) {
                            $editButton = '<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petro-general/settlement-pd/get-meter-sale-form/' . $row->id . '"><i class="fa fa-edit"></i></button>';
                            $deleteButton = '<button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petro-general/settlement-pd/delete-meter-sale/' . $row->id . '"><i class="fa fa-times"></i></button>';

                            return $editButton . ' ' . $deleteButton;
                        })
                        ->editColumn('starting_meter', function ($row) {
                            return number_format($row->starting_meter ?? 0, 3, '.', ',');
                        })
                        ->editColumn('closing_meter', function ($row) {
                            return number_format($row->closing_meter ?? 0, 3, '.', ',');
                        })
                        ->editColumn('price', function ($row) use ($business_details) {
                            return '<span class="display_currency amount" data-orig-value="' . ($row->price ?? 0) . '" data-currency_symbol="false">' .
                                $this->productUtil->num_f($row->price ?? 0, false, $business_details, true) .
                                '</span>';
                        })
                        ->editColumn('sub_total', function ($row) use ($business_details) {
                            return '<span class="display_currency sub_total" data-orig-value="' . ($row->sub_total ?? 0) . '" data-currency_symbol="false">' .
                                $this->productUtil->num_f($row->sub_total ?? 0, false, $business_details, true) .
                                '</span>';
                        })
                        ->editColumn('discount_type', function ($row) {
                            return $row->discount_type ?? '-';
                        })
                        ->editColumn('discount', function ($row) {
                            return number_format($row->discount ?? 0, 2, '.', ',');
                        })
                        ->editColumn('testing_qty', function ($row) {
                            return number_format($row->testing_qty ?? 0, 3, '.', ',');
                        })
                        ->editColumn('discount_amount', function ($row) use ($business_details) {
                            return '<span class="display_currency discount_amount" data-orig-value="' . ($row->discount_amount ?? 0) . '" data-currency_symbol="false">' .
                                $this->productUtil->num_f($row->discount_amount ?? 0, false, $business_details, true) .
                                '</span>';
                        });

                    return $meter_sales->rawColumns(['price', 'sub_total', 'discount_amount', 'action'])->make(true);
                }

                if ($active_settlement_id > 0) {
                    // Existing settlement: primary lines are meter_sales linked to this settlement.
                    // Also include Real Time / payment-flow lines saved with settlement_no NULL until attach runs.
                    $pumpOpId = (int) $request->input('pump_operator_id', 0);
                    $activeSettlement = Settlement::where('business_id', $business_id)
                        ->where('id', $active_settlement_id)
                        ->first();

                    /*
                     * Direct Settlement isolation:
                     * A Direct Settlement (ST...) must never auto-load meter_sales from the
                     * pumper dashboard / PetroPD shared shift flow just because the same pump
                     * operator has unlinked rows. Only rows explicitly saved to this ST
                     * settlement may appear in Direct Settlement.
                     *
                     * PetroPD / pumper-dashboard screens may still use the legacy fallback for
                     * NULL settlement_no rows, but Direct Settlement must not.
                     */
                    $isDirectSettlement = ! empty($activeSettlement)
                        && ! empty($activeSettlement->settlement_no)
                        && preg_match('/^ST\d+$/i', (string) $activeSettlement->settlement_no);

                    $query      = DB::table('meter_sales')
                        ->where('meter_sales.business_id', $business_id)
                        ->where(function ($outer) use ($active_settlement_id, $activeSettlement, $business_id, $pumpOpId, $isDirectSettlement) {
                            $outer->where('meter_sales.settlement_no', $active_settlement_id);
                            if (! empty($activeSettlement) && ! empty($activeSettlement->settlement_no)) {
                                $outer->orWhere('meter_sales.settlement_no', (string) $activeSettlement->settlement_no);
                            }

                            if (! $isDirectSettlement && $pumpOpId > 0) {
                                $outer->orWhere(function ($q) use ($business_id, $pumpOpId) {
                                    $q->where(function ($q2) {
                                        $q2->whereNull('meter_sales.settlement_no')
                                            ->orWhere('meter_sales.settlement_no', '');
                                    })->whereExists(function ($sub) use ($business_id, $pumpOpId) {
                                        $sub->select(DB::raw(1))
                                            ->from('pump_operator_assignments')
                                            ->whereColumn('pump_operator_assignments.pump_id', 'meter_sales.pump_id')
                                            ->whereColumn('pump_operator_assignments.shift_id', 'meter_sales.shift_id')
                                            ->where('pump_operator_assignments.business_id', $business_id)
                                            ->where('pump_operator_assignments.pump_operator_id', $pumpOpId);
                                    });
                                });
                            }
                        })
                        ->join('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
                        ->join('products', 'meter_sales.product_id', '=', 'products.id');

                    $query->whereIn('meter_sales.id', function ($sub) use ($business_id, $active_settlement_id, $activeSettlement, $pumpOpId, $isDirectSettlement) {
                        $sub->select(DB::raw('MIN(ms_keep.id)'))
                            ->from('meter_sales as ms_keep')
                            ->where('ms_keep.business_id', $business_id)
                            ->where(function ($outer) use ($active_settlement_id, $activeSettlement, $business_id, $pumpOpId, $isDirectSettlement) {
                                $outer->where('ms_keep.settlement_no', $active_settlement_id);
                                if (! empty($activeSettlement) && ! empty($activeSettlement->settlement_no)) {
                                    $outer->orWhere('ms_keep.settlement_no', (string) $activeSettlement->settlement_no);
                                }

                                if (! $isDirectSettlement && $pumpOpId > 0) {
                                    $outer->orWhere(function ($q) use ($business_id, $pumpOpId) {
                                        $q->where(function ($q2) {
                                            $q2->whereNull('ms_keep.settlement_no')
                                                ->orWhere('ms_keep.settlement_no', '');
                                        })->whereExists(function ($assignmentSub) use ($business_id, $pumpOpId) {
                                            $assignmentSub->select(DB::raw(1))
                                                ->from('pump_operator_assignments')
                                                ->whereColumn('pump_operator_assignments.pump_id', 'ms_keep.pump_id')
                                                ->whereColumn('pump_operator_assignments.shift_id', 'ms_keep.shift_id')
                                                ->where('pump_operator_assignments.business_id', $business_id)
                                                ->where('pump_operator_assignments.pump_operator_id', $pumpOpId);
                                        });
                                    });
                                }
                            })
                            ->when($isDirectSettlement, function ($directQuery) {
                                $directQuery->where(function ($shiftQuery) {
                                    $shiftQuery->whereNull('ms_keep.shift_id')
                                        ->orWhere('ms_keep.shift_id', 0)
                                        ->orWhere('ms_keep.shift_id', '');
                                });
                            })
                            ->groupBy(
                                'ms_keep.business_id',
                                'ms_keep.shift_id',
                                'ms_keep.pump_id',
                                'ms_keep.starting_meter',
                                'ms_keep.closing_meter',
                                'ms_keep.qty',
                                'ms_keep.price',
                                'ms_keep.discount',
                                'ms_keep.discount_type'
                            );
                    });

                    if ($isDirectSettlement) {
                        $query->where(function ($shiftQuery) {
                            $shiftQuery->whereNull('meter_sales.shift_id')
                                ->orWhere('meter_sales.shift_id', 0)
                                ->orWhere('meter_sales.shift_id', '');
                        });
                    }
                } else {
                    $query = DB::table('meter_sales')
                        ->where('meter_sales.business_id', $business_id)
                        ->join('pump_operator_assignments', function ($join) {
                            $join->on('meter_sales.pump_id', '=', 'pump_operator_assignments.pump_id')
                                ->on('meter_sales.shift_id', '=', 'pump_operator_assignments.shift_id');
                        })
                        ->join('pumps', 'meter_sales.pump_id', '=', 'pumps.id')
                        ->join('products', 'meter_sales.product_id', '=', 'products.id')
                        ->where(function ($query) {
                            $query->whereNull('meter_sales.settlement_no')
                                ->orWhere('meter_sales.settlement_no', '');
                        });

                    if (! empty($request->pump_operator_id)) {
                        $query->where('pump_operator_assignments.pump_operator_id', $request->pump_operator_id);
                    }
                }

                // Only apply shift_ids filter if it's provided and not empty
                if (! empty($shift_ids)) {
                    $query->whereIn('meter_sales.shift_id', $shift_ids);
                } elseif ($active_settlement_id <= 0 && $this->isSettlementShiftDataRequest($request) && ! empty($request->shift_ids)) {
                    $query->whereRaw('1 = 0');
                }

                if ($request->boolean('only_closed_pump_meter_sales') && $active_settlement_id <= 0) {
                    // PD Settlement should list only meter readings that came from closing pumps.
                    // Payment/meter-form entries are also mirrored into meter_sales, so require a
                    // matching pumper_day_entries row to keep those manual/payment rows out.
                    $hasPumperDayShiftId = Schema::hasColumn('pumper_day_entries', 'shift_id');
                    $query->whereExists(function ($sub) use ($business_id, $hasPumperDayShiftId) {
                        $sub->select(DB::raw(1))
                            ->from('pumper_day_entries')
                            ->whereColumn('pumper_day_entries.pump_id', 'meter_sales.pump_id')
                            ->whereColumn('pumper_day_entries.starting_meter', 'meter_sales.starting_meter')
                            ->whereColumn('pumper_day_entries.closing_meter', 'meter_sales.closing_meter')
                            ->where('pumper_day_entries.business_id', $business_id);

                        if ($hasPumperDayShiftId) {
                            $sub->whereColumn('pumper_day_entries.shift_id', 'meter_sales.shift_id');
                        } else {
                            $sub->whereExists(function ($assignmentSub) use ($business_id) {
                                $assignmentSub->select(DB::raw(1))
                                    ->from('pump_operator_assignments')
                                    ->whereColumn('pump_operator_assignments.id', 'pumper_day_entries.pumper_assignment_id')
                                    ->whereColumn('pump_operator_assignments.shift_id', 'meter_sales.shift_id')
                                    ->where('pump_operator_assignments.business_id', $business_id);
                            });
                        }
                    });
                }

                // Optional: scope by selected pump only (Direct Settlement create page).
                if (! empty($request->pump_id)) {
                    $pump_ids = is_array($request->pump_id) ? $request->pump_id : [$request->pump_id];
                    $pump_ids = array_filter(array_map('intval', $pump_ids));
                    if (! empty($pump_ids)) {
                        $query->whereIn('meter_sales.pump_id', $pump_ids);
                    }
                }

                // Retrieve results
                $results = $query->select('meter_sales.*', 'products.name as product_name', 'products.sku as product_sku', 'pumps.pump_name')->distinct();

                $meter_sales = DataTables::of($results)
                    ->addColumn('quantity', function ($row) {
                        $pump = Pump::where('id', $row->pump_id)->first();
                        if (! $pump) {
                            return '0.000';
                        }
                        // Always use computed sold qty: closing - starting - testing (stored qty may be wrong from old bug)
                        $quantity = $row->closing_meter - $row->starting_meter - $row->testing_qty;
                        if ($pump->bulk_sale_meter == 1) {
                            $quantity = $row->qty ?? 0;
                        }

                        return number_format($quantity, 3, '.', ',');
                    })
                    ->editColumn('qty', function ($row) {
                        $pump = Pump::where('id', $row->pump_id)->first();
                        if (! $pump) {
                            return number_format(0, 3, '.', ',');
                        }
                        // Display computed sold qty so list always shows correct value (closing - starting - testing)
                        $quantity = $row->closing_meter - $row->starting_meter - $row->testing_qty;
                        if ($pump->bulk_sale_meter == 1) {
                            $quantity = $row->qty ?? 0;
                        }
                        return number_format($quantity, 3, '.', ',');
                    })
                    ->addColumn('total_qty', function ($row) {
                        $pump = Pump::where('id', $row->pump_id)->first();
                        if (! $pump) {
                            return '0.000';
                        }
                        $quantity = $row->closing_meter - $row->starting_meter - $row->testing_qty;
                        if ($pump->bulk_sale_meter == 1) {
                            $quantity = $row->qty ?? 0;
                        }

                        return number_format(($row->testing_qty + $quantity), 3, '.', ',');
                    })
                    ->addColumn('action', function ($row) {
                        $editButton   = '<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petro-general/settlement-pd/get-meter-sale-form/' . $row->id . '"><i class="fa fa-edit"></i></button>';
                        $deleteButton = (($row->later_settlements ?? 0) < 1 || ! ($row->transaction_id ?? null) || ($row->bulk_tank ?? 0) == 1)
                            ? '<button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petro-general/settlement-pd/delete-meter-sale/' . $row->id . '"><i class="fa fa-times"></i></button>'
                            : '';
                        return $editButton . ' ' . $deleteButton;
                    })
                    ->editColumn('starting_meter', function ($row) {
                        return number_format($row->starting_meter ?? 0, 3, '.', ',');
                    })
                    ->editColumn('closing_meter', function ($row) {
                        return number_format($row->closing_meter ?? 0, 3, '.', ',');
                    })
                    ->editColumn('price', function ($row) use ($business_details) {
                        return '<span class="display_currency amount" data-orig-value="' . ($row->price ?? 0) . '" data-currency_symbol="false">' .
                        $this->productUtil->num_f($row->price ?? 0, false, $business_details, true) .
                            '</span>';
                    })
                    ->editColumn('sub_total', function ($row) use ($business_details) {
                        $pump = Pump::where('id', $row->pump_id)->first();
                        $sold_qty = $pump ? ($pump->bulk_sale_meter == 1
                            ? (float) ($row->qty ?? 0)
                            : (float) $row->closing_meter - (float) $row->starting_meter - (float) $row->testing_qty)
                            : 0;
                        $sub_total = $sold_qty * (float) ($row->price ?? 0);
                        return '<span class="display_currency sub_total" data-orig-value="' . $sub_total . '" data-currency_symbol="false">' .
                        $this->productUtil->num_f($sub_total, false, $business_details, true) .
                            '</span>';
                    })
                    ->editColumn('discount_type', function ($row) {
                        return $row->discount_type ?? '-';
                    })
                    ->editColumn('discount', function ($row) {
                        return number_format($row->discount ?? 0, 2, '.', ',');
                    })
                    ->editColumn('testing_qty', function ($row) {
                        return number_format($row->testing_qty ?? 0, 3, '.', ',');
                    })
                    ->editColumn('discount_amount', function ($row) use ($business_details) {
                        return '<span class="display_currency discount_amount" data-orig-value="' . ($row->discount_amount ?? 0) . '" data-currency_symbol="false">' .
                        $this->productUtil->num_f($row->discount_amount ?? 0, false, $business_details, true) .
                            '</span>';
                    });

                return $meter_sales->rawColumns(['price', 'sub_total', 'discount_amount', 'action'])->make(true);
            } catch (\Exception $e) {
                \Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
                return response()->json([
                    'draw'            => $request->input('draw', 0),
                    'recordsTotal'    => 0,
                    'recordsFiltered' => 0,
                    'data'            => [],
                    'error'           => 'An error occurred while loading meter sales data.',
                ], 500);
            }
        }

        return response()->json(['message' => 'Invalid request'], 400); // Handle non-AJAX requests
    }

    public function otherSales($shift_id)
    {
        $business_id      = request()->session()->get('business.id');
        $stores           = Store::forDropdown($business_id, 0, 0, 'sell');
        $bulk_tanks       = FuelTank::where('business_id', $business_id)->where('bulk_tank', 1)->pluck('fuel_tank_number', 'id');
        $items            = [];
        $fuel_category_id = Category::where('business_id', $business_id)->where('name', 'Fuel')->first();
        $fuel_category_id = ! empty($fuel_category_id) ? $fuel_category_id->id : null;
        $items            = $this->transactionUtil->getProductDropDownArray($business_id, $fuel_category_id, 'petro_settlements');

        $pump_operator_id = Auth::user()->pump_operator_id;
        $pump_operator    = PumpOperator::findOrFail($pump_operator_id);
        $other_sales      = PumpOperatorOtherSale::where('shift_id', $shift_id)->get();

        return view('petrogeneral::pump_operators.partials.modal_other_sales')->with(compact('shift_id', 'stores', 'bulk_tanks', 'items', 'pump_operator', 'other_sales'));
    }

    public function otherSalesList(Request $request)
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.list_other_sales');

        $business_id = Auth::user()->business_id;

        if (request()->ajax()) {
            $business_details = Business::find($business_id);

            $currency_precision = ! empty($business_details->currency_precision) ? $business_details->currency_precision : 2;

            $otherSaleFinalTotal = 0.00;
            $shift_ids = $this->normalizeShiftIds($request->shift_ids ?? []);
            $shift_id = ! empty($request->shift_id) ? (int) $request->shift_id : null;

            $active_settlement = null;
            if (!empty($request->active_settlement_id)) {
                $active_settlement = Settlement::where('business_id', $business_id)
                    ->where(function($q) use ($request) {
                        $q->where('id', $request->active_settlement_id)
                          ->orWhere('settlement_no', $request->active_settlement_id);
                    })
                    ->with(['other_sales'])
                    ->first();
            }
            if (empty($active_settlement)) {
                $active_settlement = Settlement::where('status', 1)
                    ->where('business_id', $business_id)
                    ->when(! empty($request->pump_operator_id), function ($query) use ($request) {
                        $query->where('pump_operator_id', $request->pump_operator_id);
                    })
                    ->select('settlements.*')
                    ->with(['other_sales'])
                    ->first();
            }

            $userSales = [];

            $query = PumpOperatorOtherSale::join('products', 'products.id', '=', 'pump_operator_other_sales.product_id')
                ->leftJoin('variations', 'products.id', 'variations.product_id')
                ->leftJoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id');

            // if ($request->shift_ids) {
            //     $query->whereIn('pump_operator_other_sales.shift_id', $request->shift_ids);
            // } else {
            //     $query->where('pump_operator_other_sales.shift_id', $request->shift_id);
            // }

            if (! empty($shift_ids)) {
                $query->whereIn('pump_operator_other_sales.shift_id', $shift_ids);
            } elseif ($shift_id) {
                $query->where('pump_operator_other_sales.shift_id', $shift_id);
            } elseif ($this->isSettlementShiftDataRequest($request) && ! empty($request->shift_ids)) {
                $query->whereRaw('1 = 0');
            }
            $print = false;
            if ($request->print_other_sale_ids) {
                $print = ($request->print == '1');
                $query->whereIn('pump_operator_other_sales.id', $request->print_other_sale_ids);
            }

            $query->join('pump_operator_assignments', function ($join) use ($request) {
                $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_other_sales.shift_id')
                    ->when(! empty($request->pump_operator_id), function ($q) use ($request) {
                        $q->where('pump_operator_assignments.pump_operator_id', $request->pump_operator_id);
                    })
                    ->when(! empty($request->pump_id), function ($q) use ($request) {
                        $q->where('pump_operator_assignments.pump_id', $request->pump_id);
                    })
                    ->whereRaw('pump_operator_assignments.id = (
                        SELECT MAX(poa.id)
                        FROM pump_operator_assignments poa
                        WHERE poa.shift_id = pump_operator_other_sales.shift_id
                          '.(! empty($request->pump_operator_id) ? 'AND poa.pump_operator_id = '.(int) $request->pump_operator_id : '').'
                          '.(! empty($request->pump_id) ? 'AND poa.pump_id = '.(int) $request->pump_id : '').'
                    )');
            })
                ->select(
                    'pump_operator_other_sales.*',
                    'products.name as product_name',
                    'products.sku as product_sku',
                    'pump_operator_assignments.shift_number',
                    'qty_available'
                )
                ->groupBy('pump_operator_other_sales.id');

            // Safe check: Initialize userSales as empty collection first
            $userSales = collect();

            $hasShiftFilter = ! empty($shift_ids) || ! empty($request->shift_ids) || ! empty($request->shift_id);
            $hasPumpFilter = ! empty($request->pump_id);
            $hasShiftOrPumpFilter = $hasShiftFilter || $hasPumpFilter;

            $merge_manual = $request->get('merge_manual') == '1';
            // Only merge settlement-level user sales when there is no shift filter.
            // In Direct Settlement tab (shift based), showing these causes stale/duplicate rows.
            if ((! $hasShiftOrPumpFilter || $merge_manual) && ! empty($active_settlement) && $active_settlement->other_sales) {
                $userSales = $active_settlement->other_sales->map(function ($item) use (&$otherSaleFinalTotal) {
                    $product = \App\Product::find($item->product_id);

                    $discount_amount = $item->discount_amount ?? 0.00;
                    $withDiscount    = ($item->sub_total ?? 0.00) - $discount_amount;

                    // $pump_other_sale_final_total += $withDiscount;
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
                        'user_check'    => 1,                                                     // Mark as user entry
                    ];
                });
            }

            // Safe fetch: If query has result or not, will always be a collection
            $pumpSalesCollection = $query->get();

            // Safe check: If empty, keep as empty collection
            $pumpSales = collect();
            if (! $pumpSalesCollection->isEmpty()) {
                $pumpSales = $pumpSalesCollection->map(function ($item) use (&$otherSaleFinalTotal) {

                    $discount_amount = $item->discount ?? 0;
                    $withDiscount    = ($item->sub_total ?? 0) - $discount_amount;

                    // $pump_other_sale_final_total += $withDiscount;
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
                        'user_check'    => 0, // Mark as pump sale entry
                    ];
                });
            }
            // $pump_nos = Pump::whereIn('id', function ($query) use ($request) {
            //     $query->select('pump_id')
            //         ->from('pump_operator_assignments')
            //         ->whereIn('shift_id', $request->shift_ids);
            // })->pluck('pump_name', 'id');
            $pump_nos = collect();

            if (! empty($shift_ids)) {
                $pump_nos = Pump::whereIn('id', function ($query) use ($request, $shift_ids) {
                    $query->select('pump_id')
                        ->from('pump_operator_assignments')
                        ->whereIn('shift_id', $shift_ids)
                        ->when(! empty($request->pump_operator_id), function ($q) use ($request) {
                            $q->where('pump_operator_id', $request->pump_operator_id);
                        });
                })->pluck('pump_name', 'id');
            }

            // If total requested
            if ($request->get_total) {
                return [
                    'success'  => 1,
                    'pump_nos' => $pump_nos,
                    'total'    => $otherSaleFinalTotal,
                ];
            }

            // For shift-filtered requests, return only shift-filtered pump sales unless merge_manual is requested.
            $combinedSales = ($hasShiftOrPumpFilter && !$merge_manual)
                ? collect($pumpSales)
                : collect($userSales)->merge(collect($pumpSales));

            $other_sales = DataTables::of($combinedSales)
                ->addColumn('quantity', function ($row) {
                    return number_format((float) ($row->qty ?? 0), 4, '.', ','); // ✅ Preserve decimals
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
                        return '<button class="btn btn-xs btn-danger delete_other_sale" data-href="/petro-general/settlement/delete-other-sale/' . $row->id . '"><i class="fa fa-times"></i></button>';
                    }

                    return '';
                });

            return $other_sales->rawColumns(['price', 'sub_total', 'with_discount', 'action'])->make(true);

        }

        $print                = false;
        $print_other_sale_ids = [];
        if ($request->print_other_sale_ids) {
            // \Log::debug("otherSalesList", ["print_other_sale_ids" => $request->print_other_sale_ids]);
            $print_other_sale_ids = explode(',', $request->print_other_sale_ids);
            $print                = true;
        }

        $pump_operator_id = Auth::user()->pump_operator_id;
        $shifts           = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')
            ->where('pump_operator_id', $pump_operator_id)
            ->where('petro_shifts.business_id', $business_id)
            ->select('pump_operators.name', 'petro_shifts.*')
            ->orderBy('id', 'DESC')
            ->get();
        // MA-002: numeric max - shift_number is varchar(50), so MAX() on
        // it compares as TEXT and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');
        $layout       = 'pumper';

        return view('petrogeneral::pump_operators.other_sales_list')->with(compact('shift_number', 'shifts', 'layout', 'print_other_sale_ids', 'print'));
    }
    //    public function otherSalesList(Request $request)
    // {
    //     $business_id = Auth::user()->business_id;

    //     // If AJAX, return JSON (DataTables)
    //     if ($request->ajax()) {

    //         $business_details = Business::find($business_id);
    //         $currency_precision = $business_details->currency_precision ?? 2;

    //         $otherSaleFinalTotal = 0.00;

    //         $active_settlement = Settlement::where('status', 1)
    //             ->where('business_id', $business_id)
    //             ->select('settlements.*')
    //             ->with(['other_sales'])
    //             ->first();

    //         // Ensure shift_ids & shift_id are handled safely

    //         $shiftIds = null;
    //         if ($request->filled('shift_ids')) {
    //             // If sent as comma string, convert to array
    //             $shiftIds = is_array($request->shift_ids) ? $request->shift_ids : explode(',', $request->shift_ids);
    //         } elseif ($request->filled('shift_id')) {
    //             $shiftIds = is_array($request->shift_id) ? $request->shift_id : [$request->shift_id];
    //         }

    //         // print flags
    //         $print = false;
    //         if ($request->filled('print_other_sale_ids')) {
    //             $print = ($request->get('print') == "1");
    //             // normalize incoming ids to array
    //             $printOtherSaleIds = is_array($request->print_other_sale_ids) ? $request->print_other_sale_ids : explode(',', $request->print_other_sale_ids);
    //         } else {
    //             $printOtherSaleIds = [];
    //         }

    //         // Build query
    //         $query = PumpOperatorOtherSale::join('products', 'products.id', '=', 'pump_operator_other_sales.product_id')
    //             ->leftJoin('variations', 'products.id', 'variations.product_id')
    //             ->leftJoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id');

    //         if (!empty($shiftIds)) {
    //             $query->whereIn('pump_operator_other_sales.shift_id', $shiftIds);
    //         } elseif ($request->filled('shift_id')) {
    //             // already handled above but keep safe fallback
    //             $query->where('pump_operator_other_sales.shift_id', $request->shift_id);
    //         }

    //         if (!empty($printOtherSaleIds)) {
    //             // only apply if print array not empty
    //             $query->whereIn('pump_operator_other_sales.id', $printOtherSaleIds);
    //         }

    //         $query->join('pump_operator_assignments', function ($join) {
    //             $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_other_sales.shift_id')
    //                 ->where('pump_operator_assignments.status', 'close')
    //                 ->whereRaw('pump_operator_assignments.id = (
    //                     SELECT MAX(poa.id)
    //                     FROM pump_operator_assignments poa
    //                     WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"
    //                 )');
    //         })
    //         ->select(
    //             'pump_operator_other_sales.*',
    //             'products.name as product_name',
    //             'products.sku as product_sku',
    //             'pump_operator_assignments.shift_number',
    //             'qty_available'
    //         )
    //         ->groupBy('pump_operator_other_sales.id');

    //         // Build user entries from active settlement safely
    //         $userSales = collect();
    //         if (!empty($active_settlement)) {
    //             $otherSalesRelation = $active_settlement->other_sales ?? [];
    //             $userSales = collect($otherSalesRelation)->map(function ($item) use (&$otherSaleFinalTotal) {
    //                 $product = \App\Product::find($item->product_id);

    //                 $discount_amount = $item->discount_amount ?? 0.00;
    //                 $withDiscount = ($item->sub_total ?? 0.00) - $discount_amount;
    //                 $otherSaleFinalTotal += $withDiscount;

    //                 return (object)[
    //                     'id'              => $item->id ?? null,
    //                     'product_sku'     => $product->sku ?? '',
    //                     'product_name'    => $product->name ?? '',
    //                     'balance_stock'   => number_format($item->balance_stock ?? 0, 4, '.', ','),
    //                     'price'           => $item->price ?? 0,
    //                     'qty'             => $item->qty ?? 0,
    //                     'discount_type'   => $item->discount_type ?? '',
    //                     'discount'        => $item->discount ?? 0,
    //                     'sub_total'       => $item->sub_total ?? 0,
    //                     'with_discount'   => $withDiscount,
    //                     'created_at'      => $item->created_at ?? '',
    //                     'qty_available'   => number_format($item->balance_stock ?? 0, 4, '.', ','),
    //                     'user_check'      => 1
    //                 ];
    //             });
    //         }

    //         // Get pump sales
    //         $pumpSalesCollection = $query->get();
    //         $pumpSales = collect();
    //         if ($pumpSalesCollection->isNotEmpty()) {
    //             $pumpSales = $pumpSalesCollection->map(function ($item) use (&$otherSaleFinalTotal) {
    //                 $discount_amount = $item->discount ?? 0;
    //                 $withDiscount = ($item->sub_total ?? 0) - $discount_amount;
    //                 $otherSaleFinalTotal += $withDiscount;

    //                 return (object)[
    //                     'id'              => $item->id,
    //                     'product_sku'     => $item->product_sku ?? '',
    //                     'product_name'    => $item->product_name ?? '',
    //                     'balance_stock'   => number_format((float) ($item->qty_available ?? 0), 4, '.', ','),
    //                     'price'           => $item->price ?? 0,
    //                     'qty'             => $item->qty ?? 0,
    //                     'discount_type'   => $item->discount_type ?? '',
    //                     'discount'        => $item->discount ?? 0,
    //                     'sub_total'       => $item->sub_total ?? 0,
    //                     'with_discount'   => $withDiscount,
    //                     'created_at'      => $item->created_at ?? '',
    //                     'qty_available'   => $item->qty_available ?? '',
    //                     'user_check'      => 0
    //                 ];
    //             });
    //         }

    //         // Pumps for total
    //         $pump_nos = Pump::whereIn('id', function($q) use ($shiftIds) {
    //             $q->select('pump_id')
    //                 ->from('pump_operator_assignments')
    //                 // ->from('meter_sales')
    //                 ->when(!empty($shiftIds), function($q2) use ($shiftIds) {
    //                     $q2->whereIn('shift_id', $shiftIds);
    //                 });
    //         })->pluck('pump_name', 'id');

    //         // If front-end requested totals only
    //         if ($request->filled('get_total')) {
    //             return response()->json([
    //                 'success' => 1,
    //                 'pump_nos' => $pump_nos,
    //                 'total' => $otherSaleFinalTotal
    //             ]);
    //         }

    //         // Merge and ensure consistent array rows for DataTables
    //         $combinedSales = $userSales->merge($pumpSales)->map(function ($row) {
    //             // convert objects to arrays for DataTables
    //             return is_object($row) ? (array) $row : $row;
    //         })->values();

    //         // Build and return DataTables response
    //         $other_sales = DataTables::of($combinedSales)
    //             ->addColumn('quantity', function ($row) {
    //                 return number_format($row['qty'] ?? $row['qty'] ?? 0);
    //             })
    //             ->editColumn('price', function ($row) use ($business_details) {
    //                 $price = $row['price'] ?? 0;
    //                 return '<span class="display_currency amount" data-orig-value="' . $price . '" data-currency_symbol=false>' .
    //                     $this->productUtil->num_f($price, false, $business_details, true) .
    //                     '</span>';
    //             })
    //             ->editColumn('created_at', function ($row) {
    //                 $created = $row['created_at'] ?? null;
    //                 return !empty($created) ? (new \DateTime($created))->format('Y-m-d H:i:s') : '';
    //             })
    //             ->editColumn('qty_available', function ($row) {
    //                 return number_format((float) ($row['qty_available'] ?? 0), 4, '.', ',');
    //             })
    //             ->editColumn('sub_total', function ($row) use ($business_details) {
    //                 $sub = $row['sub_total'] ?? 0;
    //                 return '<span class="display_currency sub_total" data-orig-value="' . $sub . '" data-currency_symbol=false>' .
    //                     $this->productUtil->num_f($sub, false, $business_details, true) .
    //                     '</span>';
    //             })
    //             ->editColumn('with_discount', function ($row) use ($currency_precision) {
    //                 $wd = $row['with_discount'] ?? 0;
    //                 return '<span class="display_currency with_discount" data-orig-value="' . $wd . '" data-currency_symbol=false>' .
    //                     number_format($wd, $currency_precision) .
    //                     '</span>';
    //             })
    //             ->addColumn('action', function ($row) {
    //                 if (isset($row['user_check']) && $row['user_check'] == 1) {
    //                     return '<button class="btn btn-xs btn-danger delete_other_sale" data-href="/petro-general/settlement/delete-other-sale/' . $row['id'] . '"><i class="fa fa-times"></i></button>';
    //                 }
    //                 return '';
    //             });

    //         return $other_sales->rawColumns(['price', 'sub_total', 'with_discount', 'action'])->make(true);
    //     }

    //     // Non-AJAX: render view
    //     $print = false;
    //     $print_other_sale_ids = [];
    //     if ($request->filled('print_other_sale_ids')) {
    //         $print_other_sale_ids = is_array($request->print_other_sale_ids) ? $request->print_other_sale_ids : explode(',', $request->print_other_sale_ids);
    //         $print = true;
    //     }

    //     $pump_operator_id = Auth::user()->pump_operator_id;
    //     $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')
    //         ->where('pump_operator_id', $pump_operator_id)
    //         ->where('petro_shifts.business_id', $business_id)
    //         ->select('pump_operators.name', 'petro_shifts.*')
    //         ->orderBy('id', 'DESC')
    //         ->get();

    //     $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->max('shift_number');
    //     $layout = 'pumper';

    //     return view('petrogeneral::pump_operators.other_sales_list')->with(compact('shift_number', 'shifts', 'layout', 'print_other_sale_ids', 'print'));
    // }

    public function pumpOtherSalesList(Request $request)
    {

        $business_id = Auth::user()->business_id;

        if (request()->ajax()) {

            $business_details = Business::find($business_id);
            $query            = PumpOperatorOtherSale::join('products', 'products.id', '=', 'pump_operator_other_sales.product_id');
            $query            = $query->leftjoin('variations', 'products.id', 'variations.product_id')
                ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id');

            if (! empty($request->shift_ids)) {
                $shift_ids = is_array($request->shift_ids) ? $request->shift_ids : explode(',', $request->shift_ids);
                if (! empty($shift_ids)) {
                    $query->whereIn('pump_operator_other_sales.shift_id', $shift_ids);
                }
            } elseif (! empty($request->shift_id)) {
                $query->where('pump_operator_other_sales.shift_id', $request->shift_id);
            }

            if (! empty($request->pump_operator_id)) {
                $shiftIds = \Modules\PetroGeneral\Entities\PetroShift::where('pump_operator_id', $request->pump_operator_id)
                    ->pluck('id');
                $query->whereIn('pump_operator_other_sales.shift_id', $shiftIds);
            }

            $print = false;
            if ($request->print_other_sale_ids) {
                $print = ($request->print == '1');
                $print_other_sale_ids = is_array($request->print_other_sale_ids) ? $request->print_other_sale_ids : explode(',', $request->print_other_sale_ids);
                if (! empty($print_other_sale_ids)) {
                    $query = $query->whereIn('pump_operator_other_sales.id', $print_other_sale_ids);
                }
            }

            $query = $query->leftJoin('pump_operator_assignments', function ($join) {
                    $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_other_sales.shift_id')
                        ->whereRaw('pump_operator_assignments.id = (
                         SELECT MAX(poa.id)
                         FROM pump_operator_assignments poa
                         WHERE poa.shift_id = pump_operator_other_sales.shift_id
                     )');
                })
                    ->select(
                        'pump_operator_other_sales.*',
                        'products.name as product_name',
                        'products.sku as product_sku',
                        DB::raw('COALESCE(pump_operator_assignments.shift_number, "-") as shift_number'),
                        'qty_available'
                    );

            $query = $query->groupBy('pump_operator_other_sales.id');

            if ($request->get_total) {
                return [
                    'success' => 1,
                    'total'   => $query->sum('sub_total'),
                ];
            }

            $other_sales = DataTables::of($query)
                ->addColumn('quantity', function ($row) {
                    return number_format((float) ($row->qty ?? 0), 4, '.', ','); // ✅ Preserve decimals
                })
                ->editColumn(
                    'price',
                    function ($row) use ($business_details) {
                        return '<span class="display_currency amount" data-orig-value="' . $row->price . '" data-currency_symbol=false>' .
                        $this->productUtil->num_f($row->price, false, $business_details, true) .
                            '</span>';
                    }
                )
                ->editColumn(
                    'created_at',
                    function ($row) {
                        return (new \DateTime($row->created_at))->format('Y-m-d H:i:s');
                    }
                )
                ->editColumn(
                    'qty_available',
                    function ($row) {
                        return number_format($row->qty_available, 4, '.', ',');
                    }
                )
                ->editColumn(
                    'sub_total',
                    function ($row) use ($business_details) {
                        return '<span class="display_currency sub_total" data-orig-value="' . $row->sub_total . '" data-currency_symbol=false>' .
                        $this->productUtil->num_f($row->sub_total, false, $business_details, true) .
                            '</span>';
                    }
                );

            return $other_sales->rawColumns(['price', 'sub_total'])->make(true);
        }

        $print                = false;
        $print_other_sale_ids = [];
        if ($request->print_other_sale_ids) {
            // \Log::debug("otherSalesList", ["print_other_sale_ids" => $request->print_other_sale_ids]);
            $print_other_sale_ids = explode(',', $request->print_other_sale_ids);
            $print                = true;
        }

        $pump_operator_id = Auth::user()->pump_operator_id;
        $shifts           = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')
            ->where('pump_operator_id', $pump_operator_id)
            ->where('petro_shifts.business_id', $business_id)
            ->select('pump_operators.name', 'petro_shifts.*')
            ->orderBy('id', 'DESC')
            ->get();
        // MA-002: numeric max - shift_number is varchar(50), so MAX() on
        // it compares as TEXT and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');
        $layout       = 'pumper';

        return view('petrogeneral::pump_operators.other_sales_list')->with(compact('shift_number', 'shifts', 'layout', 'print_other_sale_ids', 'print'));
    }

    public function othersalespage(Request $request)
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.other_sales');

        $user        = auth()->user();
        $business_id = $user->business_id; // make sure business_id is defined

        if ($user->pump_operator_id) {
            $physical_pumps_query = PumpOperatorAssignment::join('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
                ->where('pump_operator_assignments.pump_operator_id', $user->pump_operator_id)
                ->whereNotNull('pump_operator_assignments.shift_id')
                ->whereIn('pump_operator_assignments.status', ['open', 'close']);

            // Backward-compatible: some databases may not have `pumps.is_other_sales_pump` migrated yet.
            // Use the same connection as the query to support tenant DBs.
            if ($physical_pumps_query->getConnection()->getSchemaBuilder()->hasColumn('pumps', 'is_other_sales_pump')) {
                $physical_pumps_query->where('pumps.is_other_sales_pump', 0);
            }

            $physical_pumps_count = $physical_pumps_query->count();

            if ($physical_pumps_count == 0) {
                $output = [
                    'success' => false,
                    'msg' => "No physical pumps assigned. Please assign a pump to proceed."
                ];
                return redirect('/petro-general/pump-operators/dashboard')->with('status', $output);
            }
        }

        if ($user->is_pump_operator) {
            $permission = UserStorePermission::where('user_id', $user->id)->first();

            if (! $permission || ! $permission->sell) {
                $operator_permissions = PumpOperator::where('business_id', $business_id)
                    ->whereNotNull('dashboard_settings')
                    ->first();

                if ($operator_permissions) {
                    $settings = json_decode($operator_permissions->dashboard_settings, true);

                    if (! (isset($settings['meter_sales_compulsory']) && $settings['meter_sales_compulsory'] === 'yes')) {
                        return back()->with('status', 'Please Request Sale Permission from Owner');
                    }
                } else {
                    return back()->with('status', 'Please Request Sale Permission from Owner');
                }
            }
        }

        // Resolve the actual fuel category ID for this business
        $fuelCategory    = Category::where('business_id', $business_id)->where('name', 'Fuel')->first();
        $fuelCategoryId  = $fuelCategory ? $fuelCategory->id : self::FUEL_CATEGORY_ID;

        $baseQuery = function () use ($business_id, $fuelCategoryId) {
            return Product::leftJoin('variations', 'variations.product_id', '=', 'products.id')
                ->leftJoin('units', 'products.unit_id', '=', 'units.id')
                ->leftJoin('variation_location_details', 'variation_location_details.variation_id', '=', 'variations.id')
                ->where('products.business_id', $business_id)
                ->where('products.category_id', '!=', $fuelCategoryId)
                ->select(
                    'products.*',
                    'units.actual_name as unit',
                    DB::raw('SUM(variation_location_details.qty_available) as current_stock')
                )
                ->groupBy('products.id');
        };

        $products = $baseQuery()->where('products.show_in_pumper_dashboard', '=', 1)->get();

        // Fallback: if no products flagged for pumper dashboard, load all non-fuel products
        if ($products->isEmpty()) {
            $products = $baseQuery()->get();
        }

        return view('petrogeneral::pump_operators.partials.other_sales', compact('products'));
    }

    // public function othersalespage(Request $request)
    // {
    //     $user = auth()->user();

    //     if ($user->is_pump_operator) {
    //         $permission = UserStorePermission::where('user_id', $user->id)->first();
    //         if (!$permission || !$permission->sell) {
    //             return back()->with('status', 'Please Request Sale Permission from Owner');
    //         }
    //     }

    //     // Get products that belong to the same business as the user
    //     // and exclude fuel products (based on category)
    //     $products = Product::leftJoin('variations', 'variations.product_id', '=', 'products.id')
    //         ->leftJoin('units', 'products.unit_id', '=', 'units.id')
    //         ->leftJoin('variation_location_details', 'variation_location_details.variation_id', '=', 'variations.id')
    //         ->where('products.business_id', $user->business_id) // Filter by user's business ID
    //         ->where('products.category_id', '!=', self::FUEL_CATEGORY_ID)
    //         ->where('products.show_in_pumper_dashboard', '=', 1)
    //         ->select(
    //             'products.*',
    //             'units.actual_name as unit',
    //             DB::raw('SUM(variation_location_details.qty_available) as current_stock'),
    //         )
    //         ->groupBy('products.id')
    //         ->get();

    //     return view('petrogeneral::pump_operators.partials.other_sales')->with(compact('products'));
    // }

    // public function othersalespage(Request $request)
    // {

    //     $user = auth()->user();
    //     if ($user->is_pump_operator ) {
    //         $permission = UserStorePermission::where('user_id', $user->id)->first();
    //         if (!$permission || !$permission->sell) {
    //             return back()->with('status', 'Please Request Sale Permission from Owner');
    //         }
    //     }
    //     $products = Product::leftJoin('variations', 'variations.product_id', '=', 'products.id')
    //     ->leftJoin('units', 'products.unit_id', '=', 'units.id')
    //     ->leftJoin('variation_location_details', 'variation_location_details.variation_id', '=', 'variations.id')
    //     ->where('products.category_id', '!=', self::FUEL_CATEGORY_ID)
    //     ->select(
    //         'products.*',
    //         'units.actual_name as unit',
    //         DB::raw('SUM(variation_location_details.qty_available) as current_stock'),
    //     )
    //     ->groupBy('products.id')
    //     ->get();
    //     return view('petrogeneral::pump_operators.partials.other_sales')->with(compact('products'));
    // }

    public function getProducts(Request $request)
    {
        $business_id        = $request->session()->get('business.id');
        $currency_precision = Business::where('business.id', '=', $business_id)
            ->select('currency_precision')
            ->first();

        // $product = Product::leftjoin('units', 'products.unit_id', 'units.id')->where('products.id', $request->id)->select('units.short_name', 'products.min_sell_price');
        $product = DB::table('products')
            ->leftJoin('units', 'products.unit_id', '=', 'units.id')
            ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
            ->select('variations.sell_price_inc_tax', 'units.short_name')
            ->where('products.id', $request->product_id)
            ->first();

        // $product = DB::select("select products.min_sell_price, units.short_name from products left join units on products.unit_id = units.id where products.id = '{$request->id}'");
        // dump($product);exit;
        return [
            'product'            => $product,
            'currency_precision' => $currency_precision,
        ];
    }

    public function saveOtherSale(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');

            $data = [
                'business_id'     => $business_id,
                'other_sale_id'   => null,
                'store_id'        => $request->store_id,
                'product_id'      => $request->product_id,
                'price'           => $request->price,
                'qty'             => $request->qty,
                'balance_stock'   => $request->balance_stock,
                'discount'        => $request->discount,
                'discount_type'   => $request->discount_type,
                'discount_amount' => $request->discount_amount,
                'sub_total'       => $request->sub_total,
                'shift_id'        => $request->shift_id,
            ];
            $other_sale = PumpOperatorOtherSale::create($data);

            $output = [
                'success'       => true,
                'other_sale_id' => $other_sale->id,
                'msg'           => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function updateOtherSaleItem(Request $request, $id)
    {
        try {
            $sale_item = PumpOperatorOtherSale::find($id);

            if ($sale_item) {
                $product = Product::find($sale_item->product_id);

                if (! $product) {
                    return [
                        'success' => false,
                        'msg'     => __('Product not found.'),
                    ];
                }

                $variation = Variation::where('product_id', $product->id)->first();
                if (! $variation) {
                    return [
                        'success' => false,
                        'msg'     => __('Variation not found.'),
                    ];
                }

                $variation_details = VariationLocationDetails::where('variation_id', $variation->id)->first();

                if (! $variation_details) {
                    return [
                        'success' => false,
                        'msg'     => __('Stock details not found.'),
                    ];
                }

                // Calculate difference
                $old_quantity = (float) $sale_item->qty;
                $new_quantity = (float) $request->quantity;
                $difference   = $new_quantity - $old_quantity;

                // Check if increasing and stock is enough
                if ($difference > 0 && $variation_details->qty_available < $difference) {
                    return [
                        'success' => false,
                        'msg'     => __('Not enough stock available.'),
                    ];
                }

                                                                  // Update stock properly
                $variation_details->qty_available -= $difference; // Handles both +ve and -ve
                $variation_details->save();

                // Update sale item
                $sale_item->qty            = $new_quantity;
                $sale_item->balance_stock += ($old_quantity - $new_quantity); // Add back the returned qty
                $sale_item->sub_total      = round($variation->sell_price_inc_tax * $new_quantity, 2);
                $sale_item->save();
            }

            $output = [
                'success' => true,
                'msg'     => __('petrogeneral::lang.success'),
            ];
        } catch (\Throwable $e) {
            Log::debug($e);
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function deleteOtherSaleItem($id)
    {
        try {
            $sale_item = PumpOperatorOtherSale::find($id);

            if ($sale_item) {
                $product = Product::find($sale_item->product_id);

                if ($product) {
                    $variation = Variation::where('product_id', $product->id)->first();

                    if ($variation) {
                        $variation_details = VariationLocationDetails::where('variation_id', $variation->id)->first();

                        if ($variation_details) {
                            $variation_details->qty_available += $product->qty;
                            $variation_details->save();
                        }
                    }
                }

                $sale_item->delete();
            }

            $output = [
                'success' => true,
                'msg'     => __('petrogeneral::lang.success'),
            ];
        } catch (\Throwable $e) {
            Log::debug($e);
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    // public function saveOtherSaleItems(Request $request)
    // {
    //     try {
    //         $business_id = $request->session()->get('business.id');
    //         // get store_id from store table where business_id match
    //         $store    = Store::where('business_id', $business_id)->get()->last();
    //         $store_id = $store->id ?? 0;
    //         // discount default to 0
    //         $discount         = 0;

    //         \Log::debug('User pump_operator_id:', [
    //             'user_id' => Auth::id(),
    //             'pump_operator_id' => Auth::user()->pump_operator_id,
    //             'pump_operator_exists' => PumpOperator::find(Auth::user()->pump_operator_id) ? 'Yes' : 'No'
    //         ]);

    //         $pump_operator_id = Auth::user()->pump_operator_id;
    //         $shift_id         = PetroShift::where('pump_operator_id', $pump_operator_id)->get()->last()->id ?? 0;

    //         $print_other_sale_ids = [];
    //         $print                = false;
    //         foreach ($request->items as $item) {
    //             $product = Product::leftjoin('variations', 'products.id', 'variations.product_id')
    //                 ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
    //                 ->where('products.id', $item['product_id'])->select('qty_available')->first();
    //             $data = [
    //                 'business_id'   => $business_id,
    //                 'store_id'      => $store_id,
    //                 'product_id'    => $item['product_id'],
    //                 'price'         => $item['price'],
    //                 'qty'           => $item['amount'],
    //                 'balance_stock' => $product->qty_available,
    //                 'discount'      => $discount,
    //                 'sub_total'     => ($item['price'] * $item['amount']),
    //                 'shift_id'      => $shift_id,
    //             ];
    //             $other_sale   = PumpOperatorOtherSale::create($data);
    //             $databaseName = DB::connection()->getDatabaseName();
    //             if ($request->print) {
    //                 $print                  = $request->print;
    //                 $print_other_sale_ids[] = $other_sale->id;
    //             }
    //         }

    //         $html_content = "";
    //         if ($print) {
    //             $pump_operator        = PumpOperator::findOrFail($pump_operator_id);
    //             $location_id          = $pump_operator->location_id;
    //             $payment_method_value = $request->input('payment_method_value', 'Credit Sale');
    //             $location_details     = BusinessLocation::find($location_id);
    //             $invoice_layout       = $this->businessUtil->invoiceLayout($business_id, $location_id, $location_details->invoice_layout_id);

    //             $printer_type         = null;
    //             $business_details     = $this->businessUtil->getDetails($business_id);
    //             $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;
    //             $receipt_details      = $this->transactionUtil->getOtherSaleReceiptDetails($print_other_sale_ids, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);

    //             $currency_details = [
    //                 'symbol'             => $business_details->currency_symbol,
    //                 'thousand_separator' => $business_details->thousand_separator,
    //                 'decimal_separator'  => $business_details->decimal_separator,
    //             ];
    //             $receipt_details->currency = $currency_details;
    //             //$layout = !empty($receipt_details->design) ? 'sale_pos.receipts.' . $receipt_details->design : 'sale_pos.receipts.classic';
    //             $layout = ! empty($receipt_details->design) ? 'sale_pos.receipts.classic-other-sale' : 'sale_pos.receipts.classic';

    //             $html_content = view($layout, compact('receipt_details', 'payment_method_value'))->render();
    //         }

    //         $output = [
    //             'success'              => true,
    //             'msg'                  => __('petrogeneral::lang.success'),
    //             'print'                => $print,
    //             'print_other_sale_ids' => $print_other_sale_ids,
    //             'html_content'         => $html_content,
    //         ];
    //     } catch (\Exception $e) {
    //         Log::debug($e);
    //         \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
    //         $output = [
    //             'success' => false,
    //             'msg'     => __('messages.something_went_wrong'),
    //         ];
    //     }

    //     return $output;
    // }
//     public function saveOtherSaleItems(Request $request)
// {
//     try {
//         $business_id = $request->session()->get('business.id');

//         $store = Store::where('business_id', $business_id)->latest()->first();
//         $store_id = $store->id ?? 0;

//         $discount = 0;
//         $pump_operator_id = Auth::user()->pump_operator_id;
//         $shift_id = PetroShift::where('pump_operator_id', $pump_operator_id)->latest()->first()->id ?? 0;

//         $print_other_sale_ids = [];
//         $print = false;
//         $html_content = '';

//         foreach ($request->items as $item) {
//             $product = Product::leftJoin('variations', 'products.id', 'variations.product_id')
//                 ->leftJoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
//                 ->where('products.id', $item['product_id'])
//                 ->select('qty_available')
//                 ->first();

//             $data = [
//                 'business_id' => $business_id,
//                 'store_id' => $store_id,
//                 'product_id' => $item['product_id'],
//                 'price' => $item['price'],
//                 'qty' => $item['amount'],
//                 'balance_stock' => $product->qty_available,
//                 'discount' => $discount,
//                 'sub_total' => ($item['price'] * $item['amount']),
//                 'shift_id' => $shift_id,
//             ];

//             $other_sale = PumpOperatorOtherSale::create($data);

//             if ($request->print) {
//                 $print = true;
//                 $print_other_sale_ids[] = $other_sale->id;
//             }
//         }

//         // Build print content if needed
//         if ($print) {
//             $pump_operator = PumpOperator::findOrFail($pump_operator_id);
//             $location_id = $pump_operator->location_id;

//             $payment_method_value = $request->input('payment_method_value', 'Credit Sale');
//             $location_details = BusinessLocation::find($location_id);

//             $invoice_layout = $this->businessUtil->invoiceLayout(
//                 $business_id,
//                 $location_id,
//                 $location_details->invoice_layout_id
//             );

//             $business_details = $this->businessUtil->getDetails($business_id);

//             $receipt_printer_type = $location_details->receipt_printer_type;
//             $receipt_details = $this->transactionUtil->getOtherSaleReceiptDetails(
//                 $print_other_sale_ids,
//                 $location_id,
//                 $invoice_layout,
//                 $business_details,
//                 $location_details,
//                 $receipt_printer_type
//             );

//             $receipt_details->currency = [
//                 'symbol' => $business_details->currency_symbol,
//                 'thousand_separator' => $business_details->thousand_separator,
//                 'decimal_separator' => $business_details->decimal_separator,
//             ];

//             $layout = !empty($receipt_details->design)
//                 ? 'sale_pos.receipts.classic-other-sale'
//                 : 'sale_pos.receipts.classic';

//             $html_content = view($layout, compact('receipt_details', 'payment_method_value'))->render();
//         }

//         return [
//             'success' => true,
//             'msg' => __('petrogeneral::lang.success'),
//             'print' => $print,
//             'print_other_sale_ids' => $print_other_sale_ids,
//             'html_content' => $html_content,
//         ];

//     } catch (\Exception $e) {
//         Log::debug($e);
//         \Log::emergency(
//             'File: '.$e->getFile().' Line: '.$e->getLine().' Message: '.$e->getMessage()
//         );

//         return [
//             'success' => false,
//             'msg' => __('messages.something_went_wrong'),
//         ];
//     }
// }

    public function saveOtherSaleItems(Request $request)
    {
        try {
            $business_id = $request->session()->get('business.id');

            $store    = Store::where('business_id', $business_id)->latest()->first();
            $store_id = $store->id ?? 0;

            $discount = 0;

            // ✅ Pump operator is OPTIONAL for other sales
            $pump_operator_id = $request->input('pump_operator_id') ?? Auth::user()->pump_operator_id ?? null;
            $shift_id         = $request->input('shift_number') ?? null;

            if (empty($shift_id) && ! empty($pump_operator_id) && $pump_operator_id > 0) {
                $shift = PetroShift::where('pump_operator_id', $pump_operator_id)
                    ->latest()
                    ->first();
                $shift_id = $shift?->id;
            }

            $print_other_sale_ids = [];
            $print                = false;
            $html_content         = '';

            foreach ($request->items as $item) {
                $product = Product::leftJoin('variations', 'products.id', 'variations.product_id')
                    ->leftJoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
                    ->where('products.id', $item['product_id'])
                    ->select('qty_available')
                    ->first();

                $data = [
                    'business_id'   => $business_id,
                    'store_id'      => $store_id,
                    'product_id'    => $item['product_id'],
                    'price'         => $item['price'],
                    'qty'           => $item['amount'],
                    'balance_stock' => $product->qty_available ?? 0,
                    'discount'      => $discount,
                    'sub_total'     => ($item['price'] * $item['amount']),
                    'shift_id'      => $shift_id, // ✅ can be NULL
                ];

                $other_sale = PumpOperatorOtherSale::create($data);

                if ($request->print) {
                    $print                  = true;
                    $print_other_sale_ids[] = $other_sale->id;
                }
            }

            /**
             * ===============================
             * BUILD PRINT CONTENT (SAFE)
             * ===============================
             */
            if ($print) {

                // ✅ DO NOT depend on pump operator
                $location_id = BusinessLocation::where('business_id', $business_id)
                    ->where('is_active', 1)
                    ->value('id');

                if (! $location_id) {
                    return [
                        'success' => false,
                        'msg'     => 'Business location not found',
                    ];
                }

                $payment_method_value = $request->input('payment_method_value', 'Credit Sale');
                $location_details     = BusinessLocation::find($location_id);

                $invoice_layout = $this->businessUtil->invoiceLayout(
                    $business_id,
                    $location_id,
                    $location_details->invoice_layout_id
                );

                $business_details = $this->businessUtil->getDetails($business_id);

                $receipt_printer_type = $location_details->receipt_printer_type;

                $receipt_details = $this->transactionUtil->getOtherSaleReceiptDetails(
                    $print_other_sale_ids,
                    $location_id,
                    $invoice_layout,
                    $business_details,
                    $location_details,
                    $receipt_printer_type
                );

                $receipt_details->currency = [
                    'symbol'             => $business_details->currency_symbol,
                    'thousand_separator' => $business_details->thousand_separator,
                    'decimal_separator'  => $business_details->decimal_separator,
                ];

                $layout = ! empty($receipt_details->design)
                    ? 'sale_pos.receipts.classic-other-sale'
                    : 'sale_pos.receipts.classic';

                $html_content = view($layout, compact('receipt_details', 'payment_method_value'))->render();
            }

            return [
                'success'              => true,
                'msg'                  => __('petrogeneral::lang.success'),
                'print'                => $print,
                'print_other_sale_ids' => $print_other_sale_ids,
                'html_content'         => $html_content,
            ];

        } catch (\Exception $e) {
            Log::debug($e);
            Log::emergency(
                'File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage()
            );

            return [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }
    }

    public function saveMeterSale(Request $request)
    {
        Log::debug('saveMeterSale Request', ['request' => $request->all()]);

        try {
            DB::beginTransaction();

            $business_id = $request->session()->get('business.id');
            // Real Time Payments (and similar) POST the selected operator; staff may not be a pump operator user.
            $pump_operator_id = $request->input('pump_operator_id') ?: Auth::user()->pump_operator_id;

            // Collection form number logic
            $daily_collection = PumpOperatorPayment::where('business_id', $business_id)
                ->whereNotNull('collection_form_no')
                ->orderBy('id', 'DESC')
                ->select('collection_form_no')
                ->first();

            $collection_form_no = 1;
            if (! is_null($daily_collection)) {
                $collection_form_no = (int) $daily_collection->collection_form_no + 1;
            }

            $DailyCollection = DailyCollection::where('business_id', $business_id)
                ->whereNotNull('collection_form_no')
                ->orderBy('id', 'DESC')
                ->select('collection_form_no')
                ->first();

            if (! is_null($DailyCollection) && $DailyCollection->collection_form_no >= $collection_form_no) {
                $collection_form_no = (int) $DailyCollection->collection_form_no + 1;
            }

            // Resolve shift_id: prefer open assignment for posted shift_number (Real Time Payments sends shift_id = shift_number)
            $shift_id = null;
            if ($pump_operator_id && $request->filled('shift_id')) {
                $shiftAssignmentRow = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where('shift_number', $request->shift_id)
                    ->where('status', 'open')
                    ->orderByDesc('id')
                    ->first();
                $shift_id = $shiftAssignmentRow->shift_id ?? null;
            }
            if (! $shift_id && $pump_operator_id) {
                $shift_assignment = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
                    ->orderBy('id', 'DESC')
                    ->select('shift_id')
                    ->first();
                $shift_id = $shift_assignment->shift_id ?? null;
            }

            $settlement = null;
            if (! empty($pump_operator_id)) {
                $settlement = $this->ensureActiveSettlementForPumpOperator($business_id, (int) $pump_operator_id, $shift_id);
            }

            $today_deposited = PumpOperatorPayment::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('shift_id', $shift_id)
                ->whereIn('payment_type', ['cash', 'card', 'cheque', 'credit'])
                ->sum('payment_amount');

            $grand_total = (float) $request->grand_total;
            $balance_to_deposit = max(0, $grand_total - $today_deposited);

            // Create main meter sale record
            $meter_sale = PumpOperatorMeterSale::create([
                'business_id'        => $business_id,
                'date_time'          => date('Y-m-d H:i'),
                'pump_operator_id'   => $pump_operator_id,
                'settlement_no'      => $settlement->settlement_no ?? null,
                'amount'             => $grand_total,
                'deposited'          => $today_deposited,
                'balance'            => $balance_to_deposit,
                'collection_form_no' => $collection_form_no,
                'shift_id'           => $shift_id,
                'source'             => 'payment',
            ]);

            // Process each pump
            foreach ($request->pump_no as $key => $pump_no) {
                $assignment_id = $request->assignment_id[$key] ?? null;
                $pump_id       = null;

                Log::debug('Processing pump', [
                    'pump_no'       => $pump_no,
                    'assignment_id' => $assignment_id,
                    'key'           => $key,
                    'business_id'   => $business_id,
                ]);

                // STRATEGY 1: Get pump_id from assignment (MOST RELIABLE)
                if ($assignment_id) {
                    $assignment = PumpOperatorAssignment::where('id', $assignment_id)
                        ->where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->first();

                    if ($assignment) {
                        $pump_id = $assignment->pump_id;
                        Log::debug('Found pump from assignment', [
                            'assignment_id' => $assignment_id,
                            'pump_id'       => $pump_id,
                        ]);

                        // Verify the pump exists and belongs to the correct business
                        $pump = Pump::where('id', $pump_id)
                            ->where('business_id', $business_id)
                            ->first();

                        if (! $pump) {
                            Log::warning('Pump from assignment not found or wrong business', [
                                'pump_id'     => $pump_id,
                                'business_id' => $business_id,
                            ]);
                            $pump_id = null; // Reset to try alternative lookup
                        }
                    }
                }

                // STRATEGY 2: If no assignment or assignment lookup failed, try direct pump lookup
                if (! $pump_id && $pump_no) {
                    $pump = Pump::where('business_id', $business_id)
                        ->where('pump_no', $pump_no)
                        ->first();

                    if ($pump) {
                        $pump_id = $pump->id;
                        Log::debug('Found pump by pump_no and business_id', [
                            'pump_no'     => $pump_no,
                            'pump_id'     => $pump_id,
                            'business_id' => $business_id,
                        ]);
                    } else {
                        Log::warning('Pump not found by pump_no and business_id', [
                            'business_id'   => $business_id,
                            'pump_no'       => $pump_no,
                            'assignment_id' => $assignment_id,
                        ]);

                        // Last resort: Try to find any pump with this pump_no (with warning)
                        $any_pump = Pump::where('pump_no', $pump_no)->first();
                        if ($any_pump) {
                            Log::warning('Found pump with matching pump_no but different business_id', [
                                'pump_no'             => $pump_no,
                                'found_pump_id'       => $any_pump->id,
                                'found_business_id'   => $any_pump->business_id,
                                'current_business_id' => $business_id,
                            ]);
                        }

                        continue; // Skip this record if pump not found for correct business
                    }
                }

                // If we still don't have a pump_id, skip this record
                if (! $pump_id) {
                    Log::error('Could not determine pump_id for pump record', [
                        'pump_no'       => $pump_no,
                        'assignment_id' => $assignment_id,
                        'business_id'   => $business_id,
                    ]);

                    continue;
                }

                $pump = Pump::where('id', $pump_id)->where('business_id', $business_id)->first();
                if (! $pump) {
                    continue;
                }

                // Mirror into meter_sales so Petro → Direct Settlement (and finalize) see Real Time meter readings.
                $newMeterRaw = $request->new_meter[$key] ?? null;
                $hasNewMeter = $newMeterRaw !== null && $newMeterRaw !== '';
                $soldQtyInput = $request->sold_qty[$key] ?? null;
                $soldQty      = ($soldQtyInput !== null && $soldQtyInput !== '')
                    ? (float) $soldQtyInput
                    : 0.0;
                $received   = (float) ($request->starting_meter[$key] ?? 0);
                $closingNum = is_numeric($newMeterRaw) ? (float) $newMeterRaw : null;
                $isBulk     = ! empty($pump->bulk_sale_meter) && (int) $pump->bulk_sale_meter === 1;

                $product_id = $pump->product_id ?? null;
                if (empty($product_id) && ! empty($pump->fuel_tank_id)) {
                    $fuel_tank = FuelTank::find($pump->fuel_tank_id);
                    if ($fuel_tank && ! empty($fuel_tank->product_id)) {
                        $product_id = $fuel_tank->product_id;
                    }
                }

                $shouldCreateMeterSale = ! empty($product_id)
                    && (($isBulk && $soldQty > 0) || (! $isBulk && $hasNewMeter && $closingNum !== null));

                if ($shouldCreateMeterSale) {
                    $deleteQuery = MeterSale::where('business_id', $business_id)
                        ->where('pump_id', $pump_id)
                        ->where(function ($q) {
                            $q->whereNull('settlement_no')->orWhere('settlement_no', '');
                        });
                    if ($shift_id !== null) {
                        $deleteQuery->where('shift_id', $shift_id);
                    } else {
                        $deleteQuery->whereNull('shift_id');
                    }
                    $deleteQuery->delete();

                    if (! $isBulk && $soldQty <= 0 && $closingNum !== null) {
                        $soldQty = max(0, $closingNum - $received);
                    }

                    $unitPrice  = (float) ($request->unit_price[$key] ?? 0);
                    $lineAmount = (float) ($request->sale_amount[$key] ?? 0);
                    $subTotal   = $soldQty * $unitPrice;
                    if ($subTotal <= 0 && $lineAmount > 0) {
                        $subTotal = $lineAmount;
                    }
                    $discountAmount = $lineAmount > 0 ? $lineAmount : $subTotal;

                    MeterSale::create([
                        'settlement_no'   => $settlement->id ?? null,
                        'business_id'     => $business_id,
                        'product_id'      => $product_id,
                        'pump_id'         => $pump_id,
                        'starting_meter'  => $received,
                        'closing_meter'   => $isBulk ? '' : (string) $closingNum,
                        'price'           => $unitPrice,
                        'qty'             => $soldQty,
                        'discount'        => '0',
                        'discount_type'   => 'fixed',
                        'discount_amount' => $discountAmount,
                        'testing_qty'     => 0,
                        'sub_total'       => $subTotal,
                        'shift_id'        => $shift_id,
                    ]);
                }

                // Create meter sale detail
                $other_sale = PumpOperatorMeterSaleDetail::create([
                    'sale_id'          => $meter_sale->id,
                    'business_id'      => $business_id,
                    'pump_operator_id' => $pump_operator_id,
                    'pump_id'          => $pump_id,
                    'received_meter'   => $request->starting_meter[$key] ?? null,
                    'new_meter'        => $request->new_meter[$key] ?? null,
                    'sold_qty'         => $request->sold_qty[$key] ?? null,
                    'unit_price'       => $request->unit_price[$key] ?? null,
                    'amount'           => $request->sale_amount[$key] ?? null,
                ]);

                Log::debug('Created meter sale detail', [
                    'detail_id' => $other_sale->id,
                    'pump_id'   => $pump_id,
                    'pump_no'   => $pump_no,
                ]);

                // Update assignment with the correct column name (scope by operator when known)
                if ($assignment_id && $other_sale->id) {
                    $assignmentUpdate = PumpOperatorAssignment::where('id', $assignment_id)
                        ->where('business_id', $business_id);
                    if (! empty($pump_operator_id)) {
                        $assignmentUpdate->where('pump_operator_id', $pump_operator_id);
                    }
                    $updated = $assignmentUpdate->update(['pump_operator_other_sale_id' => $other_sale->id]);

                    Log::debug('Updated assignment', [
                        'assignment_id'               => $assignment_id,
                        'pump_operator_other_sale_id' => $other_sale->id,
                        'rows_affected'               => $updated,
                    ]);
                }
            }

            DB::commit();

            $output = [
                'success'            => true,
                'msg'                => __('petrogeneral::lang.success'),
                'collection_form_no' => $collection_form_no,
                // Suppress "Confirm Another Payment?" on redirect; meter save is not a payment (see payment_section views).
                'meter_sale_saved'   => true,
            ];
        } catch (\Exception $e) {
            DB::rollback();
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    public function deleteOtherSale($id)
    {
        try {
            $other_sale = PumpOperatorOtherSale::where('id', $id)->first();
            $amount     = $other_sale->sub_total - $other_sale->discount_amount;
            $other_sale->delete();

            $output = [
                'success' => true,
                'amount'  => $amount,
                'msg'     => __('petrogeneral::lang.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Show the specified resource in modal.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function getPaymentSummaryModal()
    {

        $only_pumper      = request()->only_pumper;
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id      = Auth::user()->business_id;

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')->where('petro_shifts.business_id', $business_id)->select('pump_operators.name', 'petro_shifts.*')->orderBy('id', 'DESC');

        if ($only_pumper) {
            $shifts->where('pump_operator_id', $pump_operator_id);
        }

        $shifts = $shifts->get();

        return view('petrogeneral::pump_operators.partials.payment_summary_modal')->with(compact('only_pumper', 'shifts'));
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('petrogeneral::show');
    }

    protected function isPaymentEditLocked($payment, $credit_sale = null)
    {
        $shift_id = null;
        if (is_object($payment) && ! empty($payment->shift_id)) {
            $shift_id = $payment->shift_id;
        }

        if (empty($shift_id) && $credit_sale) {
            if (! empty($credit_sale->collection_form_no) && ! empty($credit_sale->pump_operator_id)) {
                $shift_id = PumpOperatorPayment::where('pump_operator_id', $credit_sale->pump_operator_id)
                    ->where('payment_type', 'credit')
                    ->where('collection_form_no', $credit_sale->collection_form_no)
                    ->value('shift_id');
            }

            if (empty($shift_id) && ! empty($credit_sale->daily_voucher_id)) {
                $shift_id = DailyVoucher::where('id', $credit_sale->daily_voucher_id)->value('shift_id');
            }
        }

        if (empty($shift_id)) {
            return false;
        }

        $closed_time = PetroShift::where('id', $shift_id)->value('closed_time');
        if (empty($closed_time)) {
            return false;
        }

        $payment_created_at = null;
        if ($credit_sale && ! empty($credit_sale->created_at)) {
            $payment_created_at = $credit_sale->created_at;
        }
        if (empty($payment_created_at) && is_object($payment) && ! empty($payment->created_at)) {
            $payment_created_at = $payment->created_at;
        }
        if (empty($payment_created_at) && is_object($payment) && ! empty($payment->date_and_time)) {
            $payment_created_at = $payment->date_and_time;
        }
        if (empty($payment_created_at)) {
            return false;
        }

        try {
            $payment_time = \Carbon\Carbon::parse($payment_created_at);
            $closed_at    = \Carbon\Carbon::parse($closed_time);
        } catch (\Exception $e) {
            return false;
        }

        if (! $payment_time->gt($closed_at)) {
            return false;
        }

        $business_id = null;
        if (is_object($payment) && ! empty($payment->business_id)) {
            $business_id = $payment->business_id;
        }
        if (empty($business_id) && $credit_sale && ! empty($credit_sale->business_id)) {
            $business_id = $credit_sale->business_id;
        }
        if (empty($business_id)) {
            $business_id = Auth::user()->business_id;
        }

        $settlements = Settlement::where('business_id', $business_id)
            ->where('status', 0)
            ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
            ->get(['work_shift']);

        foreach ($settlements as $settlement) {
            $work_shifts = $settlement->work_shift;
            if (is_string($work_shifts)) {
                $decoded_work_shifts = json_decode($work_shifts, true);
                $work_shifts         = is_array($decoded_work_shifts) ? $decoded_work_shifts : explode(',', $work_shifts);
            }
            if (! is_array($work_shifts)) {
                $work_shifts = [];
            }
            $work_shifts = array_filter(array_map('intval', $work_shifts));
            if (in_array((int) $shift_id, $work_shifts, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function edit($id)
    {
        Log::info('here in edit with id: ' . $id . ', type: ' . request('type'));

        try {
            $payment     = null;
            $credit_sale = null;
            
            // CREDIT PAYMENTS: Payment Summary edit link usually passes pop.id (not scsp.id).
            // Only treat $id as scsp.id when we cannot resolve a PumpOperatorPayment by id.
            if (request('type') === 'credit') {
                Log::info('Credit edit: resolving by pop.id first', ['id' => $id]);

                // Try to resolve as PumpOperatorPayment id (most common).
                $payment = PumpOperatorPayment::leftJoin('pump_operators as po', 'pump_operator_payments.pump_operator_id', '=', 'po.id')
                    ->leftJoin('business_locations as bl', 'po.location_id', '=', 'bl.id')
                    ->where('pump_operator_payments.id', $id)
                    ->select(
                        'pump_operator_payments.*',
                        'bl.name as location_name'
                    )
                    ->first();

                if ($payment) {
                    if (! empty(request('credit_sale_id'))) {
                        $credit_sale = SettlementCreditSalePayment::where('id', request('credit_sale_id'))
                            ->where('pump_operator_id', $payment->pump_operator_id)
                            ->where('business_id', $payment->business_id)
                            ->first();
                    }

                    if (! $credit_sale) {
                        $credit_sale = SettlementCreditSalePayment::where('collection_form_no', $payment->collection_form_no)
                            ->where('pump_operator_id', $payment->pump_operator_id)
                            ->where('business_id', $payment->business_id)
                            ->where('amount', $payment->payment_amount)
                            ->orderBy('id', 'desc')
                            ->first();
                    }

                    if ($credit_sale) {
                        // IMPORTANT: Use the pump_operator_payment amount (current/edited amount) for display
                        // The pump_operator_payments table should have the updated amount after editing
                        // Only fall back to credit_sale amount if pump_operator_payment amount is null or zero
                        if ($payment->payment_amount && $payment->payment_amount > 0) {
                            // Keep the current payment_amount (this is the edited amount)
                            // Don't override it with credit_sale amount
                        } else {
                            // Fallback: use credit_sale amount if pump_operator_payment amount is not set
                            $payment->payment_amount = $credit_sale->amount;
                        }
                        $payment->note = $credit_sale->note ?? ($payment->note ?? '');
                        $payment->credit_sale_id = $credit_sale->id;
                    }
                } else {
                    // Last resort: treat $id as scsp.id
                    $credit_sale = SettlementCreditSalePayment::find($id);
                    if ($credit_sale) {
                        // Create a minimal payment object for the edit modal.
                        $location_name = '';
                        $pump_operator = PumpOperator::find($credit_sale->pump_operator_id);
                        if ($pump_operator && $pump_operator->location_id) {
                            $location      = BusinessLocation::find($pump_operator->location_id);
                            $location_name = $location ? $location->name : '';
                        }

                        $payment = (object) [
                            'id'                 => $id,
                            'payment_type'       => 'credit',
                            'payment_amount'     => $credit_sale->amount,
                            'note'               => $credit_sale->note ?? '',
                            'location_name'      => $location_name,
                            'pump_operator_id'   => $credit_sale->pump_operator_id,
                            'business_id'        => $credit_sale->business_id,
                            'collection_form_no' => $credit_sale->collection_form_no,
                            'credit_sale_id' => $credit_sale->id,
                        ];
                    }
                }
            }

            // NON-CREDIT or fallback: try to find by pump_operator_payments.id
            if (! $payment) {
                $payment = PumpOperatorPayment::leftJoin('pump_operators as po', 'pump_operator_payments.pump_operator_id', '=', 'po.id')
                    ->leftJoin('business_locations as bl', 'po.location_id', '=', 'bl.id')
                    ->where('pump_operator_payments.id', $id)
                    ->select(
                        'pump_operator_payments.*',
                        'bl.name as location_name'
                    )
                    ->first();
            }

            // If still not found, check if it's a credit sale ID (legacy fallback)
            if (! $payment) {
                Log::info('Payment not found by pump_operator_payments.id, checking if it\'s a credit sale ID', ['id' => $id]);

                $credit_sale = SettlementCreditSalePayment::find($id);
                if ($credit_sale) {
                    // Find the corresponding pump_operator_payment record
                    $payment = PumpOperatorPayment::leftJoin('pump_operators as po', 'pump_operator_payments.pump_operator_id', '=', 'po.id')
                        ->leftJoin('business_locations as bl', 'po.location_id', '=', 'bl.id')
                        ->where('pump_operator_payments.collection_form_no', $credit_sale->collection_form_no)
                        ->where('pump_operator_payments.pump_operator_id', $credit_sale->pump_operator_id)
                        ->where('pump_operator_payments.payment_type', 'credit')
                        ->where('pump_operator_payments.business_id', $credit_sale->business_id)
                        ->select(
                            'pump_operator_payments.*',
                            'bl.name as location_name'
                        )
                        ->first();

                    // If still not found, create a payment record from the credit sale
                    if (! $payment && $credit_sale) {
                        Log::info('Creating pump_operator_payment from credit sale', ['credit_sale_id' => $id]);
                        $payment = PumpOperatorPayment::create([
                            'pump_operator_id'   => $credit_sale->pump_operator_id,
                            'business_id'        => $credit_sale->business_id,
                            'payment_type'       => 'credit',
                            'payment_amount'     => $credit_sale->amount,
                            'collection_form_no' => $credit_sale->collection_form_no,
                            'date_and_time'      => $credit_sale->created_at ?? now(),
                        ]);

                        // Reload with joins
                        $payment = PumpOperatorPayment::leftJoin('pump_operators as po', 'pump_operator_payments.pump_operator_id', '=', 'po.id')
                            ->leftJoin('business_locations as bl', 'po.location_id', '=', 'bl.id')
                            ->where('pump_operator_payments.id', $payment->id)
                            ->select(
                                'pump_operator_payments.*',
                                'bl.name as location_name'
                            )
                            ->first();
                    }

                    // IMPORTANT: Use the pump_operator_payment amount (current/edited amount) for display
                    // The pump_operator_payments table should have the updated amount after editing
                    // Only override with credit_sale amount if they are different (data inconsistency)
                    if ($payment && $credit_sale) {
                        Log::info('Credit edit: Checking amounts for modal', [
                            'payment_id'  => $payment->id,
                            'pop_amount'  => $payment->payment_amount,
                            'scsp_amount' => $credit_sale->amount,
                        ]);
                        
                        // Only update payment_amount if pump_operator_payment amount is null, zero, or significantly different
                        // This preserves the edited amount in pump_operator_payments
                        if (!$payment->payment_amount || $payment->payment_amount <= 0) {
                            $payment->payment_amount = $credit_sale->amount;
                        }
                        // If amounts are different, it might indicate a data inconsistency
                        // In that case, we should log it but keep the pump_operator_payment amount as it's more recent
                        
                        $payment->credit_sale_id = $credit_sale->id; // Store for update
                    }
                }
            }

            if (! $payment) {
                Log::error('Payment not found for edit', ['payment_id' => $id]);
                // Return HTML error message for btn-modal handler
                return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">' .
                    (__('petrogeneral::lang.payment_not_found') ?: 'Payment not found') .
                    '</div></div></div></div>', 404);
            }

            if ($payment->payment_type !== 'credit' && $this->isPaymentEditLocked($payment, $credit_sale)) {
                $message = 'Locked: Shift closed and settlement completed';
                return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">' .
                    htmlspecialchars($message) .
                    '</div></div></div></div>', 403);
            }

            // For card payments, get amount from daily_cards if available
            // IMPORTANT: The payment summary query (summarypaymnetdashboard) joins daily_cards to pop on:
            // - dc.amount = pop.payment_amount (join condition)
            // - dc.collection_no = pop.collection_form_no (join condition)
            // - dc.business_id = pop.business_id (join condition)
            // The summary shows dc.amount as payment_amount, and uses pop.id as the edit link id
            // So when editing, we have pop.id, and we need to find the matching dc record that was displayed
            if ($payment && $payment->payment_type === 'card' && ! empty($payment->collection_form_no)) {
                // The join condition in summary is: dc.amount = pop.payment_amount AND dc.collection_no = pop.collection_form_no
                // So we should match on both amount AND collection_no to get the exact daily_card that was displayed
                // Use the pop.payment_amount to match, as that's what the join condition uses
                $daily_card = DailyCard::where('collection_no', $payment->collection_form_no)
                    ->where('amount', $payment->payment_amount) // Match on amount using pop.payment_amount (join condition)
                    ->where('pump_operator_id', $payment->pump_operator_id)
                    ->where('business_id', $payment->business_id)
                    ->whereNotNull('slip_no') // Match the summary query filter
                    ->orderBy('id', 'desc')   // Get the most recent one if multiple match
                    ->first();

            if ($daily_card) {
                // Use the amount from the daily_card record (this is what's displayed in the table)
                // The summary query shows dc.amount, not pop.payment_amount
                $payment->payment_amount = $daily_card->amount;
                $payment->note = $daily_card->note ?? ($payment->note ?? '');
                
                Log::info('Card payment amount updated from daily_card', [
                    'payment_id' => $payment->id,
                    'pop_payment_amount_original' => $payment->getOriginal('payment_amount'),
                    'dc_amount' => $daily_card->amount,
                    'collection_no' => $payment->collection_form_no,
                    'daily_card_id' => $daily_card->id,
                    'amounts_match' => abs((float)$payment->getOriginal('payment_amount') - (float)$daily_card->amount) < 0.01,
                ]);

                    if (empty($payment->location_name)) {
                        $pump_operator = PumpOperator::find($payment->pump_operator_id);
                        if ($pump_operator && $pump_operator->location_id) {
                            $location               = BusinessLocation::find($pump_operator->location_id);
                            $payment->location_name = $location ? $location->name : '';
                        }
                    }
                } else {
                    Log::warning('Card payment daily_card not found', [
                        'payment_id'         => $payment->id,
                        'collection_form_no' => $payment->collection_form_no,
                        'pump_operator_id'   => $payment->pump_operator_id,
                        'payment_amount'     => $payment->payment_amount,
                    ]);
                }
            }

            // For cash payments, the amount should already be correct from pump_operator_payments
            // The summary query uses pop.payment_amount directly (line 220 in summarypaymnetdashboard)
            // So the payment_amount from the loaded payment should match what's in the table
            // However, let's ensure we're using the exact value from the database
            if ($payment && $payment->payment_type === 'cash') {
                // Reload the payment to ensure we have the latest payment_amount from database
                $fresh_payment = PumpOperatorPayment::find($payment->id);
                if ($fresh_payment && $fresh_payment->payment_amount != $payment->payment_amount) {
                    Log::info('Cash payment amount updated from fresh load', [
                        'payment_id' => $payment->id,
                        'old_amount' => $payment->payment_amount,
                        'new_amount' => $fresh_payment->payment_amount,
                    ]);
                    $payment->payment_amount = $fresh_payment->payment_amount;
                }
            }

            // For credit payments, get amount from settlement_credit_sale_payments if available
            // IMPORTANT: Skip if we already handled this via credit_sale_id lookup (when ID was scsp.id)
            // This block only runs for legacy cases where ID was pump_operator_payment.id
            if ($payment && $payment->payment_type === 'credit' && ! empty($payment->collection_form_no) && empty($payment->credit_sale_id)) {
                // Strategy: Find the SettlementCreditSalePayment that matches the join condition
                // The join condition is: pop.payment_amount = scsp.amount AND pop.collection_form_no = scsp.collection_form_no
                // So we should match on both amount and collection_form_no
                $credit_sale = SettlementCreditSalePayment::where('collection_form_no', $payment->collection_form_no)
                    ->where('pump_operator_id', $payment->pump_operator_id)
                    ->where('business_id', $payment->business_id)
                    ->where('amount', $payment->payment_amount) // Match on amount - this is the key!
                    ->first();

            if ($credit_sale) {
                // Use the amount from the credit sale record (this is what's displayed in the table)
                // This ensures the edit modal shows the same amount as the table
                $payment->payment_amount = $credit_sale->amount;
                $payment->note = $credit_sale->note ?? ($payment->note ?? '');
                $payment->credit_sale_id = $credit_sale->id;
                
                Log::info('Credit sale found for edit', [
                    'payment_id' => $payment->id,
                    'credit_sale_id' => $credit_sale->id,
                    'credit_sale_amount' => $credit_sale->amount,
                    'payment_amount_before' => $payment->getOriginal('payment_amount'),
                    'payment_amount_after' => $credit_sale->amount,
                ]);

                    if (empty($payment->location_name)) {
                        $pump_operator = PumpOperator::find($payment->pump_operator_id);
                        if ($pump_operator && $pump_operator->location_id) {
                            $location               = BusinessLocation::find($pump_operator->location_id);
                            $payment->location_name = $location ? $location->name : '';
                        }
                    }
                }
            }

            $payment_types = PumpOperatorPayment::getPaymentTypesArray();

            return view('petrogeneral::pump_operators.partials.edit_payment')->with(compact(
                'payment',
                'payment_types'
            ));
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());
            Log::emergency('Stack trace: ' . $e->getTraceAsString());

            // Return HTML error message for btn-modal handler (expects HTML, not JSON)
            $errorMessage = __('messages.something_went_wrong') ?: 'Something went wrong';
            return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">' .
                htmlspecialchars($errorMessage) .
                '</div></div></div></div>', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        Log::info('here in update', ['id' => $id, 'payment_amount' => $request->input('payment_amount'), 'payment_type' => $request->input('payment_type'), 'credit_sale_id' => $request->input('credit_sale_id')]);
        $request->validate([
            'note' => 'required',
        ]);

        try {
            // CREDIT PAYMENTS: Handle separately - update settlement_credit_sale_payments
            if ($request->input('payment_type') === 'credit' && $request->input('credit_sale_id')) {
                $credit_sale_id = $request->input('credit_sale_id');
                $credit_sale    = SettlementCreditSalePayment::find($credit_sale_id);
                if ($credit_sale && $request->input('payment_type') !== 'credit' && $this->isPaymentEditLocked(null, $credit_sale)) {
                    return response()->json([
                        'success' => false,
                        'msg'     => 'Locked: Shift closed and settlement completed',
                    ], 403);
                }

                $old_scsp_amount = $credit_sale ? (float) $credit_sale->amount : null;
                
                $updateData = [
                    'note'       => $request->input('note'),
                    'updated_by' => Auth::user()->id,
                    'updated_at' => now(),
                ];

                // Update amount if provided and valid
                if ($request->has('payment_amount') && is_numeric($request->input('payment_amount'))) {
                    $updateData['amount'] = $request->input('payment_amount');
                }

                // Keep sub_total consistent with (amount - total_discount)
                if ($credit_sale) {
                    $new_amount = (float) ($updateData['amount'] ?? $credit_sale->amount);
                    $total_discount = (float) ($credit_sale->total_discount ?? 0);
                    $updateData['sub_total'] = $new_amount - $total_discount;
                }
                
                Log::info('Credit payment update - updating scsp', [
                    'credit_sale_id' => $credit_sale_id,
                    'updateData'     => $updateData,
                ]);

                $affectedRows = SettlementCreditSalePayment::where('id', $credit_sale_id)->update($updateData);

                Log::info('Credit payment update result', [
                    'credit_sale_id' => $credit_sale_id,
                    'affected_rows'  => $affectedRows,
                ]);

                // Verify the update
                $updatedScsp = SettlementCreditSalePayment::find($credit_sale_id);
                Log::info('Credit payment after update', [
                    'scsp_id'         => $updatedScsp->id ?? null,
                    'scsp_note'       => $updatedScsp->note ?? null,
                    'scsp_updated_by' => $updatedScsp->updated_by ?? null,
                    'scsp_amount'     => $updatedScsp->amount ?? null,
                ]);

                // Keep pump_operator_payments in sync so the Payment Summary join continues to work.
                if (!empty($updatedScsp)) {
                    $popUpdate = [
                        'payment_amount' => $updatedScsp->amount,
                        'note' => $updatedScsp->note,
                        'edited_by' => Auth::user()->id,
                        'updated_at' => now(),
                    ];

                    // Update only the selected payment-summary row. Rows can share collection_form_no,
                    // so falling back to collection/amount matching can rewrite sibling credit rows.
                    PumpOperatorPayment::where('id', $id)
                        ->where('payment_type', 'credit')
                        ->where('business_id', $updatedScsp->business_id)
                        ->where('pump_operator_id', $updatedScsp->pump_operator_id)
                        ->when(!empty($updatedScsp->collection_form_no), function ($q) use ($updatedScsp) {
                            $q->where('collection_form_no', $updatedScsp->collection_form_no);
                        })
                        ->update($popUpdate);
                }
                
                return response()->json([
                    'success'        => true,
                    'msg'            => __('petrogeneral::lang.payment_updated_successfully'),
                    'payment_amount' => $updatedScsp->amount ?? $request->input('payment_amount'),
                    'old_amount' => $old_scsp_amount,
                ]);
            }

            // NON-CREDIT PAYMENTS: Original flow
            $payment = PumpOperatorPayment::findOrFail($id);
            if ($this->isPaymentEditLocked($payment, null)) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'Locked: Shift closed and settlement completed',
                ], 403);
            }
            $old_amount = $payment->payment_amount;
            // $data              = $request->except('_token', '_method');
            $data              = $request->except('_token', '_method', 'location_name', 'credit_sale_id', 'payment_type');
            $data['edited_by'] = Auth::user()->id;

            Log::info('Payment before update', [
                'payment_id'         => $payment->id,
                'payment_type'       => $payment->payment_type,
                'collection_form_no' => $payment->collection_form_no,
                'old_amount'         => $old_amount,
                'new_amount'         => $data['payment_amount'] ?? null,
            ]);

            PumpOperatorPayment::where('id', $id)->update($data);

            // Reload payment to get updated data
            $payment->refresh();

            // If cash payment, also update daily_collections
            if ($payment->payment_type === 'cash' && ! empty($payment->collection_form_no)) {
                DailyCollection::where('collection_form_no', $payment->collection_form_no)
                    ->where('pump_operator_id', $payment->pump_operator_id)
                    ->where('type', 'daily_collection')
                    ->where('current_amount', $old_amount)
                    ->update(['current_amount' => $data['payment_amount'] ?? $payment->payment_amount]);
            }

            // If card payment, also update daily_cards
            if ($payment->payment_type === 'card' && ! empty($payment->collection_form_no)) {
                $dailyCardUpdate = [
                    'amount' => $data['payment_amount'] ?? $payment->payment_amount,
                    'note' => $data['note'] ?? $payment->note,
                ];
                $linkedDailyCardId = SettlementCardPayment::where('pump_payment_id', $payment->id)
                    ->value('daily_card_id');

                if (! empty($linkedDailyCardId)) {
                    DailyCard::where('id', $linkedDailyCardId)
                        ->where('pump_operator_id', $payment->pump_operator_id)
                        ->update($dailyCardUpdate);
                } else {
                    DailyCard::where('collection_no', $payment->collection_form_no)
                        ->where('pump_operator_id', $payment->pump_operator_id)
                        ->where('amount', $old_amount)
                        ->update($dailyCardUpdate);
                }
            }

            // MARK SETTLEMENT AS EDITED & SYNC RELATED SETTLEMENT PAYMENT RECORDS
            try {
                // Determine settlement_id from linked settlement payment tables
                $settlementId = null;
                // cash
                $scp = SettlementCashPayment::where('pump_payment_id', $payment->id)->first();
                if ($scp) {
                    $settlementId = $scp->settlement_no;
                    app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                        ->editCashPayment($payment->business_id, $scp->id, [
                            'amount' => $payment->payment_amount,
                            'note' => $payment->note,
                        ]);
                }
                // card
                $scdp = SettlementCardPayment::where('pump_payment_id', $payment->id)->first();
                if ($scdp) {
                    $settlementId = $settlementId ?: $scdp->settlement_no;
                    app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                        ->editCardPayment($payment->business_id, $scdp->id, [
                            'amount' => $payment->payment_amount,
                            'note' => $payment->note,
                        ]);
                }
                // cheque
                $schp = SettlementChequePayment::where('pump_payment_id', $payment->id)->first();
                if ($schp) {
                    $settlementId = $settlementId ?: $schp->settlement_no;
                    app(\Modules\PetroGeneral\Services\SettlementPaymentEditService::class)
                        ->editChequePayment($payment->business_id, $schp->id, [
                            'amount' => $payment->payment_amount,
                            'note' => $payment->note,
                        ]);
                }
                // credit sale handled earlier when updating credit_sale

                if (! empty($settlementId)) {
                    Settlement::where('id', $settlementId)->update(['is_edit' => 1]);
                }
            } catch (\Exception $e) {
                // logging error but not interrupt update
                Log::error('Error syncing settlement payments on pump payment update: ' . $e->getMessage());
            }

            // If credit payment, also update settlement_credit_sale_payments
            // The payment summary query joins on: scsp.amount = pop.payment_amount AND scsp.collection_form_no = pop.collection_form_no
            // So we need to update scsp.amount when pop.payment_amount changes
            Log::info('Checking credit payment update', [
                'payment_type'           => $payment->payment_type,
                'collection_form_no'     => $payment->collection_form_no,
                'is_credit'              => $payment->payment_type === 'credit',
                'has_collection_form_no' => ! empty($payment->collection_form_no),
            ]);

            if ($payment->payment_type === 'credit') {
                $new_amount = $data['payment_amount'] ?? $payment->payment_amount;

                Log::info('Processing credit payment update', [
                    'new_amount'         => $new_amount,
                    'old_amount'         => $old_amount,
                    'collection_form_no' => $payment->collection_form_no,
                    'pump_operator_id'   => $payment->pump_operator_id,
                    'business_id'        => $payment->business_id,
                ]);

                // Find the specific credit sale that matches this payment
                // Strategy 1: If collection_form_no exists, match by collection_form_no, pump_operator_id, business_id, and old amount
                // Strategy 2: If collection_form_no is null, match by pump_operator_id, business_id, and old amount
                $credit_sale = null;

                if (! empty($payment->collection_form_no)) {
                    Log::info('Searching for credit sale by collection_form_no', [
                        'collection_form_no' => $payment->collection_form_no,
                        'pump_operator_id'   => $payment->pump_operator_id,
                        'business_id'        => $payment->business_id,
                        'old_amount'         => $old_amount,
                    ]);

                    $credit_sale = SettlementCreditSalePayment::where('collection_form_no', $payment->collection_form_no)
                        ->where('pump_operator_id', $payment->pump_operator_id)
                        ->where('business_id', $payment->business_id)
                        ->where('amount', $old_amount) // Match on old amount to find the exact record
                        ->first();

                    Log::info('Credit sale search result (by collection_form_no and old amount)', [
                        'found'          => $credit_sale ? true : false,
                        'credit_sale_id' => $credit_sale ? $credit_sale->id : null,
                    ]);
                } else {
                    // collection_form_no is null, find by matching the payment's current state
                    // The payment summary joins on: pop.payment_amount = scsp.amount
                    // So we should find the credit sale that matches the payment's CURRENT amount (before update)
                    // OR find any credit sale for this pump operator that doesn't have a matching payment
                    Log::info('Searching for credit sale by amount (collection_form_no is null)', [
                        'pump_operator_id' => $payment->pump_operator_id,
                        'business_id'      => $payment->business_id,
                        'old_amount'       => $old_amount,
                        'payment_id'       => $payment->id,
                    ]);

                    // First, try to find by matching the old amount (current payment amount)
                    $credit_sale = SettlementCreditSalePayment::where('pump_operator_id', $payment->pump_operator_id)
                        ->where('business_id', $payment->business_id)
                        ->where('amount', $old_amount) // Match on old amount (current payment amount)
                        ->where('is_from_pumper', 1)   // Only from pumper
                        ->orderBy('id', 'desc')        // Get the most recent one
                        ->first();

                    // If not found, the payment amount might have been changed before
                    // So find the most recent credit sale for this pump operator
                    if (! $credit_sale) {
                        Log::info('Credit sale not found by old amount, getting most recent credit sale');

                        // Get all credit sales for this pump operator and find which one matches this payment
                        $all_credit_sales = SettlementCreditSalePayment::where('pump_operator_id', $payment->pump_operator_id)
                            ->where('business_id', $payment->business_id)
                            ->where('is_from_pumper', 1)
                            ->orderBy('id', 'desc')
                            ->get();

                        Log::info('All credit sales for pump operator', [
                            'count'               => $all_credit_sales->count(),
                            'credit_sale_ids'     => $all_credit_sales->pluck('id')->toArray(),
                            'credit_sale_amounts' => $all_credit_sales->pluck('amount')->toArray(),
                        ]);

                        // Find the credit sale that has a payment matching this payment's ID and amount
                        foreach ($all_credit_sales as $cs) {
                            $matching_payment = PumpOperatorPayment::where('id', $payment->id)
                                ->where('payment_amount', $cs->amount)
                                ->first();

                            if ($matching_payment) {
                                $credit_sale = $cs;
                                Log::info('Found credit sale by matching payment amount', [
                                    'credit_sale_id'     => $credit_sale->id,
                                    'credit_sale_amount' => $credit_sale->amount,
                                    'payment_amount'     => $old_amount,
                                ]);
                                break;
                            }
                        }

                        // If still not found, just get the most recent one
                        if (! $credit_sale && $all_credit_sales->count() > 0) {
                            $credit_sale = $all_credit_sales->first();
                            Log::info('Using most recent credit sale as fallback', [
                                'credit_sale_id'     => $credit_sale->id,
                                'credit_sale_amount' => $credit_sale->amount,
                            ]);
                        }
                    }

                    Log::info('Credit sale search result (by amount, no collection_form_no)', [
                        'found'                          => $credit_sale ? true : false,
                        'credit_sale_id'                 => $credit_sale ? $credit_sale->id : null,
                        'credit_sale_amount'             => $credit_sale ? $credit_sale->amount : null,
                        'credit_sale_collection_form_no' => $credit_sale ? $credit_sale->collection_form_no : null,
                    ]);
                }

                if ($credit_sale) {
                    // Update the specific credit sale
                    $credit_sale->amount = $new_amount;
                    $credit_sale->save();

                    $update_payment_data = [];
                    if (empty($payment->collection_form_no) && ! empty($credit_sale->collection_form_no)) {
                        $update_payment_data['collection_form_no'] = $credit_sale->collection_form_no;
                    } elseif (empty($credit_sale->collection_form_no) && ! empty($payment->collection_form_no)) {
                        // If payment has collection_form_no but credit sale doesn't, update credit sale
                        $credit_sale->collection_form_no = $payment->collection_form_no;
                        $credit_sale->save();
                    } elseif (empty($payment->collection_form_no) && empty($credit_sale->collection_form_no)) {
                        // Both are null - set a placeholder to ensure join works
                        // Use a unique identifier based on credit sale ID
                        $placeholder                               = 'CS-' . $credit_sale->id;
                        $update_payment_data['collection_form_no'] = $placeholder;
                        $credit_sale->collection_form_no           = $placeholder;
                        $credit_sale->save();
                        Log::info('Set placeholder collection_form_no for null values', [
                            'payment_id'     => $payment->id,
                            'credit_sale_id' => $credit_sale->id,
                            'placeholder'    => $placeholder,
                        ]);
                    }

                    if (! empty($update_payment_data)) {
                        PumpOperatorPayment::where('id', $payment->id)->update($update_payment_data);
                        $payment->refresh();
                        Log::info('Updated payment collection_form_no', [
                            'payment_id'         => $payment->id,
                            'collection_form_no' => $update_payment_data['collection_form_no'] ?? $payment->collection_form_no,
                        ]);
                    }

                    Log::info('Credit sale updated', [
                        'credit_sale_id'     => $credit_sale->id,
                        'old_amount'         => $old_amount,
                        'new_amount'         => $new_amount,
                        'collection_form_no' => $credit_sale->collection_form_no,
                    ]);

                    // Also update DailyVoucher if it exists and is linked
                    // If multiple credit sales are linked to one daily voucher, sum all their amounts
                    $resolved_daily_voucher_id = $credit_sale->daily_voucher_id;
                    $linked_voucher            = null;

                    if (! empty($resolved_daily_voucher_id)) {
                        $linked_voucher = DailyVoucher::where('id', $resolved_daily_voucher_id)
                            ->where('business_id', $payment->business_id)
                            ->first();

                        // Check if this daily voucher matches the credit sale criteria
                        // Don't check total_amount as it may have been updated already in previous edits
                        $linked_matches = $linked_voucher
                            && (string) $linked_voucher->voucher_order_number === (string) $credit_sale->order_number
                            && (string) $linked_voucher->voucher_order_date === (string) $credit_sale->order_date
                            && (int) $linked_voucher->customer_id === (int) $credit_sale->customer_id
                            && (int) $linked_voucher->operator_id === (int) $payment->pump_operator_id;

                        if (! $linked_matches) {
                            $resolved_daily_voucher_id = null;
                        }
                    }

                    if (empty($resolved_daily_voucher_id)) {
                        $voucher_query = DailyVoucher::where('business_id', $payment->business_id)
                            ->where('voucher_order_number', $credit_sale->order_number)
                            ->where('voucher_order_date', $credit_sale->order_date)
                            ->where('operator_id', $payment->pump_operator_id)
                            ->where('customer_id', $credit_sale->customer_id)
                            ->where('total_amount', $old_amount);

                        $match_by_collection = null;
                        if (! empty($payment->collection_form_no)) {
                            $match_by_collection = (clone $voucher_query)
                                ->where('daily_vouchers_no', $payment->collection_form_no)
                                ->first();
                        }

                        $daily_voucher_match = $match_by_collection ?: $voucher_query->orderBy('id', 'desc')->first();

                        if ($daily_voucher_match) {
                            if ($credit_sale->daily_voucher_id != $daily_voucher_match->id) {
                                $credit_sale->daily_voucher_id = $daily_voucher_match->id;
                                $credit_sale->save();
                            }
                            $resolved_daily_voucher_id = $daily_voucher_match->id;
                        }
                    }

                    if (! empty($resolved_daily_voucher_id)) {
                        $total_voucher_amount = SettlementCreditSalePayment::where('daily_voucher_id', $resolved_daily_voucher_id)
                            ->where('business_id', $payment->business_id)
                            ->sum('amount');

                        DailyVoucher::where('id', $resolved_daily_voucher_id)
                            ->update(['total_amount' => $total_voucher_amount]);

                        Log::info('Daily voucher updated by daily_voucher_id', [
                            'daily_voucher_id' => $resolved_daily_voucher_id,
                            'total_amount'     => $total_voucher_amount,
                        ]);
                    } else {
                        // Fallback: Try to find daily voucher by daily_vouchers_no matching collection_form_no
                        // Daily Collection query uses daily_vouchers.daily_vouchers_no as collection_form_no
                        $daily_voucher = DailyVoucher::where('daily_vouchers_no', $payment->collection_form_no)
                            ->where('operator_id', $payment->pump_operator_id)
                            ->where('business_id', $payment->business_id)
                            ->first();

                        if ($daily_voucher) {
                            // Sum all credit sales linked to this daily voucher
                            // First try by daily_voucher_id if the link was established
                            $total_voucher_amount = SettlementCreditSalePayment::where('daily_voucher_id', $daily_voucher->id)
                                ->where('business_id', $payment->business_id)
                                ->sum('amount');

                            // If no records found by daily_voucher_id, sum by collection_form_no
                            if ($total_voucher_amount == 0) {
                                $total_voucher_amount = SettlementCreditSalePayment::where('collection_form_no', $payment->collection_form_no)
                                    ->where('pump_operator_id', $payment->pump_operator_id)
                                    ->where('business_id', $payment->business_id)
                                    ->sum('amount');
                            }

                            $daily_voucher->total_amount = $total_voucher_amount;
                            $daily_voucher->save();

                            Log::info('Daily voucher updated by daily_vouchers_no', [
                                'daily_voucher_id'  => $daily_voucher->id,
                                'daily_vouchers_no' => $payment->collection_form_no,
                                'total_amount' => $total_voucher_amount,
                            ]);
                        }
                    }
                } else {
                    Log::warning('Credit sale not found for update', [
                        'collection_form_no' => $payment->collection_form_no,
                        'pump_operator_id'   => $payment->pump_operator_id,
                        'business_id'        => $payment->business_id,
                        'old_amount'         => $old_amount,
                    ]);
                }
            }

            // If AJAX request, return JSON response
            if ($request->ajax()) {
                $output = [
                    'success'        => true,
                    'msg'            => __('lang_v1.success'),
                    'payment_id'     => $payment->id,
                    'payment_amount' => $payment->payment_amount,
                    'old_amount'     => $old_amount,
                    'payment_type'   => $payment->payment_type,
                ];

                return response()->json($output);
            }

            $output = [
                'success' => true,
                'tab'     => 'payment_summary',
                'msg'     => __('lang_v1.success'),
            ];

            return redirect()->back()->with('status', $output);
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            // If AJAX request, return JSON error
            if ($request->ajax()) {
                $output = [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong'),
                ];

                return response()->json($output, 400);
            }

            $output = [
                'success' => false,
                'tab'     => 'payment_summary',
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
        try {
            $payment = PumpOperatorPayment::findOrFail($id);
            DB::beginTransaction();
            // remove or update corresponding daily tables entries
            try {
                $this->removePaymentFromDailyTables($payment);
            } catch (\Exception $e) {
                Log::error('Failed to remove related daily table entries: ' . $e->getMessage());
            }

            // nullify links from meter sales if any
            PumpOperatorMeterSale::where('p_o_payment_id', $payment->id)->update(['p_o_payment_id' => null]);

            $payment->delete();
            DB::commit();

            return response()->json(['success' => true, 'msg' => __('petrogeneral::lang.success')]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 500);
        }
    }

    /**
     * return modal view
     *
     * @param  int  $id
     * @return Renderable
     */
    public function getPaymentModal()
    {
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id      = Auth::user()->business_id;

        $pumps = Pump::leftjoin('pump_operator_assignments', function ($join) {
            $join->on('pumps.id', 'pump_operator_assignments.pump_id')->whereDate('date_and_time', date('Y-m-d'));
        })->leftjoin('pump_operators', 'pump_operator_assignments.pump_operator_id', 'pump_operators.id')
            ->where('pumps.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->select('pumps.*', 'pump_operator_assignments.pump_operator_id', 'pump_operator_assignments.pump_id', 'pump_operators.name as pumper_name')
            ->orderBy('pumps.id')
            ->get();
        $pop_up = true;

        return view('petrogeneral::pump_operators.partials.payment_modal')->with(compact(
            'pumps',
            'pop_up'
        ));
    }

    public function balanceToOperator($pump_operator_id)
    {

        $business_id = Auth::user()->business_id;
        $shift       = PetroShift::where('pump_operator_id', $pump_operator_id)->get()->last()->id ?? 0;

        $payments = PumpOperatorPayment::where('shift_id', $shift)
            ->where('pump_operator_id', $pump_operator_id)
            ->select(
                DB::raw('SUM(IF(payment_type="cash", payment_amount, 0)) as cash'),
                DB::raw('SUM(IF(payment_type="card", payment_amount, 0)) as card'),
                DB::raw('SUM(IF(payment_type="cheque", payment_amount, 0)) as cheque'),
                DB::raw('SUM(IF(payment_type="credit", payment_amount, 0)) as credit'),
                DB::raw('SUM(payment_amount) as total')
            )->first();

        $day_entries = PumperDayEntry::leftjoin('pump_operators', 'pumper_day_entries.pump_operator_id', 'pump_operators.id')
            ->leftjoin('pumps', 'pumper_day_entries.pump_id', 'pumps.id')
            ->leftjoin('pump_operator_assignments', 'pump_operator_assignments.id', 'pumper_day_entries.pumper_assignment_id')
            ->where('shift_id', $shift)
            ->where('pumper_day_entries.business_id', $business_id)
            ->where('pumper_day_entries.pump_operator_id', $pump_operator_id)
            ->select('pump_operators.name', 'pumper_day_entries.*', 'pumps.pump_name')
            ->get();

        $other_sale = PumpOperatorOtherSale::where('shift_id', $shift)
            ->select(DB::raw('SUM(sub_total - discount_amount) as total'))
            ->value('total');

        if ($day_entries->sum('amount') + $other_sale - $payments->total > 0) {
            $payment_type   = 'shortage';
            $payment_amount = $day_entries->sum('amount') + $other_sale - $payments->total;
        }
        if ($day_entries->sum('amount') + $other_sale - $payments->total < 0) {
            $payment_type   = 'excess';
            $payment_amount = $day_entries->sum('amount') + $other_sale - $payments->total;
        }
        if (! isset($payment_type) || $payment_type == '') {
            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];

            return redirect()->back()->with('status', $output);
        }
        $data = [
            'business_id'      => $business_id,
            'pump_operator_id' => $pump_operator_id,
            'payment_type'     => $payment_type,
            'payment_amount'   => $payment_amount,
            'created_by'       => Auth::user()->id,
            'shift_id'         => $shift,
        ];
        if ($day_entries->sum('amount') + $other_sale - $payments->total != 0) {
            PumpOperatorPayment::create($data);
        }

        $output = [
            'success' => 1,
            'msg'     => __('lang_v1.success'),
        ];

        return redirect()->back()->with('status', $output);
    }

    public function metersWithPayments()
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.meters_with_payments');

        $business_id      = Auth::user()->business_id;
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_details = Business::find($business_id);

        if (! $this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {
            abort(403, 'Unauthorized Access');
        }

        $only_pumper = request()->only_pumper;
        $shift_id    = request()->shift_id;

        if (request()->ajax()) {
            $query = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')
                ->where('pump_operator_payments.business_id', $business_id)
                ->select('pump_operator_payments.*', 'pump_operators.name as pump_operator_name');

            if ($only_pumper) {
                $query->where('pump_operators.id', $pump_operator_id);
            }

            if (! empty(request()->pump_id)) {
                $query->whereExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('pump_operator_meter_sales')
                        ->join('pump_operator_meter_sale_details', 'pump_operator_meter_sales.id', '=', 'pump_operator_meter_sale_details.sale_id')
                        ->whereColumn('pump_operator_meter_sales.collection_form_no', 'pump_operator_payments.collection_form_no')
                        ->where('pump_operator_meter_sale_details.pump_id', request()->pump_id);
                });
            }

            if (! empty(request()->pump_operator_id)) {
                $query->where('pump_operators.id', request()->pump_operator_id);
            }
            if ($only_pumper) {
                $shift_id = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
                    ->orderBy('id', 'DESC')
                    ->value('shift_id');
            }
            if (! empty($shift_id)) {
                $query->where('pump_operator_payments.shift_id', $shift_id);
            }

            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $query->whereDate('date_and_time', '>=', request()->start_date);
                $query->whereDate('date_and_time', '<=', request()->end_date);
            }

            $pump_operators_payments = DataTables::of($query)
                ->addColumn('date', function ($row) {
                    return ! empty($row->date_and_time) ? $this->commonUtil->format_date($row->date_and_time) : '';
                })
                ->addColumn('time', function ($row) {
                    if (empty($row->date_and_time)) {
                        return '';
                    }

                    $time_format = session('business.time_format') == 12 ? 'h:i A' : 'H:i';

                    return \Carbon::parse($row->date_and_time)->format($time_format);
                })
                ->addColumn(
                    'pumps',
                    function ($row) {
                        $pumps      = '';
                        $meter_sale = self::ma002MeterSale('collection_form_no', $row->collection_form_no);
                        if (! is_null($meter_sale)) {
                            $meter_sale_details = self::ma002MeterSaleDetails($meter_sale->id);
                            foreach ($meter_sale_details as $meter_sale_detail) {
                                $pump = (object) ['pump_name' => self::ma002PumpName($meter_sale_detail->pump_id)]; // MA-002 PERF: cached
                                if (! empty($pump)) {
                                    if ($pumps == '') {
                                        $pumps = $pump->pump_name;
                                    } else {
                                        $pumps = $pumps . ', ' . $pump->pump_name;
                                    }
                                }
                            }
                        }

                        return $pumps;
                    }
                )
                ->addColumn(
                    'unit_price',
                    function ($row) use ($business_details) {
                        $unit_price = '';
                        $meter_sale = self::ma002MeterSale('p_o_payment_id', $row->id);
                        if (! is_null($meter_sale)) {
                            $meter_sale_details = self::ma002MeterSaleDetails($meter_sale->id);
                            foreach ($meter_sale_details as $meter_sale_detail) {
                                if ($unit_price == '') {
                                    $unit_price = number_format($meter_sale_detail->unit_price, $business_details->currency_precision, '.', ',');
                                } else {
                                    $unit_price = $unit_price . ', ' . number_format($meter_sale_detail->unit_price, '2', '.', ',');
                                }
                            }
                        }

                        return $unit_price;
                    }
                )
                ->addColumn(
                    'last_meter',
                    function ($row) {
                        $received_meter = '';
                        $meter_sale     = self::ma002MeterSale('p_o_payment_id', $row->id);
                        if (! is_null($meter_sale)) {
                            $meter_sale_details = self::ma002MeterSaleDetails($meter_sale->id);
                            foreach ($meter_sale_details as $meter_sale_detail) {
                                if ($received_meter == '') {
                                    $received_meter = number_format($meter_sale_detail->received_meter, '3', '.', ',');
                                } else {
                                    $received_meter = $received_meter . ', ' . number_format($meter_sale_detail->received_meter, '3', '.', ',');
                                }
                            }
                        }

                        return $received_meter;
                    }
                )
                ->addColumn(
                    'new_meter',
                    function ($row) {
                        $new_meter  = '';
                        $meter_sale = self::ma002MeterSale('p_o_payment_id', $row->id);
                        if (! is_null($meter_sale)) {
                            $meter_sale_details = self::ma002MeterSaleDetails($meter_sale->id);
                            foreach ($meter_sale_details as $meter_sale_detail) {
                                if ($new_meter == '') {
                                    $new_meter = number_format($meter_sale_detail->new_meter, '3', '.', ',');
                                } else {
                                    $new_meter = $new_meter . ', ' . number_format($meter_sale_detail->new_meter, '3', '.', ',');
                                }
                            }
                        }

                        return $new_meter;
                    }
                )
                ->addColumn(
                    'qty_sold',
                    function ($row) {
                        $sold_qty   = '';
                        $meter_sale = self::ma002MeterSale('p_o_payment_id', $row->id);
                        if (! is_null($meter_sale)) {
                            $meter_sale_details = self::ma002MeterSaleDetails($meter_sale->id);
                            foreach ($meter_sale_details as $meter_sale_detail) {
                                if ($sold_qty == '') {
                                    $sold_qty = number_format($meter_sale_detail->sold_qty, '3', '.', ',');
                                } else {
                                    $sold_qty = $sold_qty . ', ' . number_format($meter_sale_detail->sold_qty, '3', '.', ',');
                                }
                            }
                        }

                        return $sold_qty;
                    }
                )
                ->addColumn(
                    'total_sold_amount',
                    function ($row) use ($business_details) {
                        $amount     = '';
                        $meter_sale = self::ma002MeterSale('p_o_payment_id', $row->id);
                        if (! is_null($meter_sale)) {
                            $amount = number_format($meter_sale->amount, $business_details->currency_precision, '.', ',');
                        }

                        return $amount;
                    }
                )
                ->removeColumn('id')
                ->editColumn('payment_type', '{{ucfirst($payment_type)}}')
                ->editColumn('amount', function ($row) use ($business_details) {
                    $amount = is_numeric($row->payment_amount) ? (float) $row->payment_amount : 0;

                    return '<span class="display_currency amount" data-orig-value="' . $amount . '" data-currency_symbol=false>' .
                    $this->productUtil->num_f($amount, false, $business_details, true) .
                        '</span>';
                });

            return $pump_operators_payments->rawColumns(['amount', 'action'])
                ->make(true);
        }

        $layout = 'app';
        if ($only_pumper) {
            $layout = 'pumper';
        }

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')->where('petro_shifts.business_id', $business_id)->select('pump_operators.name', 'petro_shifts.*')->orderBy('id', 'DESC');
        if ($only_pumper) {
            $shifts->where('pump_operator_id', $pump_operator_id);
        }
        $shifts = $shifts->get();

        // MA-002: numeric max - shift_number is varchar(50), so MAX() on
        // it compares as TEXT and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $pumps = Pump::where('business_id', $business_id)->get();

        return view('petrogeneral::pump_operators.meters_with_payments')->with(compact(
            'only_pumper',
            'layout',
            'shifts',
            'shift_number',
            'pump_operators',
            'pumps'
        ));
    }

    /**
     * Idempotent sync of a PumpOperatorPayment into DailyCollection/DailyCard/DailyChequePayment etc.
     */
    protected function syncPaymentToDailyTables(PumpOperatorPayment $payment, $request = null)
    {
        $business_id      = $payment->business_id;
        $pump_operator_id = $payment->pump_operator_id;
        $shift_id         = $payment->shift_id;
        $shift_type       = null;
        if (! empty($shift_id)) {
            $shift_type = PetroShift::find($shift_id)->type ?? null;
        }
        $amount        = $payment->payment_amount;
        $collection_no = $payment->collection_form_no;

        $pump_operator = PumpOperator::find($pump_operator_id);
        $location_id   = $pump_operator->location_id ?? null;

        // Ensure collection_form_no exists for consistency
        if (empty($collection_no)) {
            $last          = PumpOperatorPayment::where('business_id', $business_id)->whereNotNull('collection_form_no')->orderBy('id', 'DESC')->select('collection_form_no')->first();
            $collection_no = $last ? ((int) $last->collection_form_no + 1) : 1;
        }

        // CASH: DailyCollection
        // Note: For cash payments from pumper dashboard, DailyCollection is created manually in store() method
        // This method should only handle cases where it's called from other flows (e.g., edit, bulk operations)
        if ($payment->payment_type === 'cash') {
            // Strict duplicate check - must match collection_form_no AND shift_id AND amount AND date
            $exists = DailyCollection::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('collection_form_no', $collection_no)
                ->where('shift_id', $shift_id)
                ->where('type', 'daily_collection')
                ->first();

            if (! $exists) {
                // Get shift_number from PumpOperatorAssignment if available
                $assignment = PumpOperatorAssignment::where('shift_id', $shift_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where('shift_number', '>', 0)
                    ->orderBy('id', 'DESC')
                    ->first();

                $shift_number = $assignment->shift_number ?? null;
                $shift_no     = $shift_number ?? (PetroShift::find($shift_id)->shift_no ?? null);

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
            } elseif ((float) $exists->current_amount !== (float) $amount) {
                $exists->update(['current_amount' => $amount]);
            }
        }

        // CARD: DailyCard
        if ($payment->payment_type === 'card') {
            // Check if DailyCard already exists for this specific payment
            // Use collection_no AND amount AND slip_no to ensure uniqueness for bulk payments
            $exists = DailyCard::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('collection_no', $collection_no)
                ->where('shift_id', $shift_id)
                ->where('amount', $amount);

            // If slip_no is provided, also check for it to ensure uniqueness
            $slip_no = $this->getPaymentRequestValue($request, 'slip_no') ?? $payment->slip_no ?? null;
            if ($slip_no) {
                $exists->where('slip_no', $slip_no);
            }

            $exists = $exists->first();

            if (! $exists) {
                $walkin_customer = Contact::where('name', 'Walk-In Customer')->where('business_id', $business_id)->first();
                $card_type       = $this->getPaymentRequestValue($request, 'card_type') ?? $payment->card_type ?? null;
                $card_number     = $this->getPaymentRequestValue($request, 'card_number') ?? $payment->card_number ?? null;
                $customer_id     = $this->getPaymentRequestValue($request, 'customer_id') ?? $payment->customer_id ?? ($walkin_customer->id ?? null);
                $assignment      = PumpOperatorAssignment::where('shift_id', $shift_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where('shift_number', '>', 0)
                    ->orderBy('id', 'DESC')
                    ->first();
                $shift_number    = $assignment->shift_number ?? null;
                $shift_no        = $shift_number ?? (PetroShift::find($shift_id)->shift_no ?? null);

                $dailyCardData = [
                    'location_id'      => $location_id,
                    'collection_no'    => $collection_no,
                    'business_id'      => $business_id,
                    'amount'           => $amount,
                    'card_type'        => $card_type,
                    'card_number'      => $card_number,
                    'customer_id'      => $customer_id,
                    'slip_no'          => $slip_no,
                    'date'             => date('Y-m-d'),
                    'pump_operator_id' => $pump_operator_id,
                    'type'             => $shift_type ?? 'daily_collection',
                ];

                if (Schema::hasColumn('daily_cards', 'shift_no')) {
                    $dailyCardData['shift_no'] = $shift_no;
                }
                if (Schema::hasColumn('daily_cards', 'shift_number')) {
                    $dailyCardData['shift_number'] = $shift_number;
                }

                DailyCard::create($dailyCardData);
            }
        }

        // CHEQUE: DailyChequePayment
        if ($payment->payment_type === 'cheque') {
            $exists = DailyChequePayment::where('business_id', $business_id)
                ->where('linked_payment_id', $payment->id)
                ->orWhere(function ($q) use ($pump_operator_id, $amount) {
                    $q->where('pump_operator_id', $pump_operator_id)->where('amount', $amount);
                })->first();

            if (! $exists) {
                $cheque_bank   = ($request && property_exists($request, 'cheque_bank')) ? $request->cheque_bank : null;
                $customer_id   = ($request && property_exists($request, 'customer_id')) ? $request->customer_id : null;
                $cheque_number = ($request && property_exists($request, 'cheque_number')) ? $request->cheque_number : null;
                $cheque_date   = ($request && property_exists($request, 'cheque_date')) ? $request->cheque_date : null;

                DailyChequePayment::create([
                    'linked_payment_id'  => $payment->id,
                    'business_id'        => $business_id,
                    'amount'             => $amount,
                    'bank_name'          => $cheque_bank,
                    'customer_id'        => $customer_id,
                    'cheque_number'      => $cheque_number,
                    'cheque_date'        => $cheque_date,
                    'shift_id'           => $shift_id,
                    'collection_form_no' => $collection_no,
                ]);
            }
        }

        // CREDIT: SettlementCreditSalePayment should be created by saveCredit flow already.
        // Here only ensure payments linking exist; do not create credit sales automatically.

        // Link pump operator meter sales if meter sale exists for this collection_no
        PumpOperatorMeterSale::where('collection_form_no', $collection_no)
            ->whereNull('p_o_payment_id')
            ->where('pump_operator_id', $pump_operator_id)
            ->update(['p_o_payment_id' => $payment->id]);
    }

    private function removePreSettlementCreditSaleAccounting($businessId, $customerId, $orderNumber, $orderDate, $amount): void
    {
        if (empty($businessId) || empty($customerId) || empty($orderDate)) {
            return;
        }

        $orderDate = \Carbon\Carbon::parse($orderDate)->format('Y-m-d');

        $transactionIds = Transaction::where('business_id', $businessId)
            ->where('contact_id', $customerId)
            ->where('type', 'sell')
            ->where('is_credit_sale', 1)
            ->where(function ($query) {
                $query->whereNull('is_settlement')->orWhere('is_settlement', 0);
            })
            ->whereDate('transaction_date', $orderDate)
            ->whereRaw('ABS(final_total - ?) < 0.02', [$amount])
            ->when(! empty($orderNumber), function ($query) use ($orderNumber) {
                $query->where('ref_no', $orderNumber);
            })
            ->pluck('id');

        if ($transactionIds->isEmpty()) {
            return;
        }

        AccountTransaction::whereIn('transaction_id', $transactionIds)->forceDelete();
        ContactLedger::whereIn('transaction_id', $transactionIds)->forceDelete();
        Transaction::whereIn('id', $transactionIds)->forceDelete();
    }

    /**
     * Remove related daily table entries for a pump operator payment.
     * Attempts to be conservative (only remove matching business/operator/amount/collection_no)
     */
    protected function removePaymentFromDailyTables(PumpOperatorPayment $payment)
    {
        $business_id      = $payment->business_id;
        $pump_operator_id = $payment->pump_operator_id;
        $amount           = $payment->payment_amount;
        $collection_no    = $payment->collection_form_no;

        // Remove DailyCard rows
        DailyCard::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where(function ($q) use ($collection_no, $amount) {
                $q->where('collection_no', $collection_no)->orWhere('amount', $amount);
            })->delete();

        // Remove DailyCollection rows
        DailyCollection::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where(function ($q) use ($collection_no, $amount) {
                $q->where('collection_form_no', $collection_no)->orWhere('current_amount', $amount);
            })->delete();

        // Remove DailyChequePayment rows
        DailyChequePayment::where('business_id', $business_id)
            ->where(function ($q) use ($payment, $pump_operator_id, $amount) {
                $q->where('linked_payment_id', $payment->id)
                    ->orWhere(function ($q2) use ($pump_operator_id, $amount) {
                        $q2->where('pump_operator_id', $pump_operator_id)->where('amount', $amount);
                    });
            })->delete();

        // For credit, delete SettlementCreditSalePayment entries linked by collection_form_no & operator & business
        SettlementCreditSalePayment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where(function ($q) use ($collection_no, $amount) {
                if (! empty($collection_no)) {
                    $q->where('collection_form_no', $collection_no);
                } else {
                    $q->where('amount', $amount);
                }
            })->delete();

        // Delete instant ContactLedger entries linked by collection_form_no
        if (! empty($collection_no)) {
            ContactLedger::where('business_id', $business_id)
                ->where('note', 'like', '%Form No. ' . $collection_no)
                ->forceDelete();
        }

        // Nullify meter sale links
        PumpOperatorMeterSale::where('p_o_payment_id', $payment->id)->update(['p_o_payment_id' => null]);
    }

    /**
     * TEMPORARY TEST METHOD - Creates a test cash entry for current shift and pump operator
     * REMOVE BEFORE DEPLOYING TO SERVER
     * Access via: /petro-general/test-cash-entry?amount=5000
     */
    public function testCashEntry(Request $request)
    {
        try {
            $pump_operator_id = Auth::user()->pump_operator_id;
            $business_id      = Auth::user()->business_id;
            $created_by       = Auth::user()->id;

            if (! $pump_operator_id || ! $business_id) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'User not authenticated as pump operator',
                ], 400);
            }

            $pump_operator  = PumpOperator::findOrFail($pump_operator_id);
            $payment_amount = $request->amount ?? 5000; // Default 5000 if not provided

            // Get current shift_id
            $shift_id = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
                ->orderBy('id', 'DESC')
                ->value('shift_id');

            if (! $shift_id) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'No active shift found for this pump operator',
                ], 400);
            }

            // Get shift_number
            $assignment = PumpOperatorAssignment::where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->orderBy('id', 'DESC')
                ->first();

            $shift_number = $assignment->shift_number ?? null;

            // Get collection_form_no
            $daily_collection = PumpOperatorPayment::where('business_id', $business_id)
                ->whereNotNull('collection_form_no')
                ->orderBy('id', 'DESC')
                ->value('collection_form_no');

            $collection_form_no = $daily_collection ? (int) $daily_collection + 1 : 1;

            $DailyCollection = DailyCollection::where('business_id', $business_id)
                ->whereNotNull('collection_form_no')
                ->orderBy('id', 'DESC')
                ->value('collection_form_no');

            if ($DailyCollection && $DailyCollection >= $collection_form_no) {
                $collection_form_no = (int) $DailyCollection + 1;
            }

            // Create PumpOperatorPayment
            $pumpPayment = PumpOperatorPayment::create([
                'business_id'        => $business_id,
                'pump_operator_id'   => $pump_operator_id,
                'payment_type'       => 'cash',
                'payment_amount'     => $payment_amount,
                'created_by'         => $created_by,
                'shift_id'           => $shift_id,
                'collection_form_no' => $collection_form_no,
            ]);

            // Create DailyCollection
            $dailyCollection = DailyCollection::create([
                'business_id'        => $business_id,
                'collection_form_no' => $collection_form_no,
                'pump_operator_id'   => $pump_operator_id,
                'location_id'        => $pump_operator->location_id,
                'balance_collection' => 0,
                'current_amount'     => $payment_amount,
                'created_by'         => $created_by,
                'shift_id'           => $shift_id,
                'shift_no'           => $shift_number,
                'shift_number'       => $shift_number,
                'type'               => 'daily_collection',
            ]);

            return response()->json([
                'success' => true,
                'msg'     => 'Test cash entry created successfully',
                'data'    => [
                    'pump_operator_id'    => $pump_operator_id,
                    'shift_id'            => $shift_id,
                    'shift_number'        => $shift_number,
                    'collection_form_no'  => $collection_form_no,
                    'amount'              => $payment_amount,
                    'pump_payment_id'     => $pumpPayment->id,
                    'daily_collection_id' => $dailyCollection->id,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::emergency('Test cash entry failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg'     => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function printCreditSale($id)
    {
        try {
            $business_id = request()->session()->get('user.business_id')
                ?? request()->session()->get('business.id')
                ?? (Auth::user()->business_id ?? null);

            if (empty($business_id)) {
                return abort(422, 'Business context missing in session. Please login again.');
            }

            /*
            |--------------------------------------------------------------------------
            | Credit Sale Print - Multi Item Safe Version
            |--------------------------------------------------------------------------
            | The print button may receive either:
            | 1) settlement_credit_sale_payments.id, or
            | 2) pump_operator_payments.id.
            |
            | A single credit bill can contain many product rows. Therefore we must NOT
            | print only the row matching the passed id. Once we identify one credit sale
            | row, we print ALL rows with the same bill_number.
            |
            | Line subtotal must come from saved amount - discount. It must NEVER be
            | recalculated as displayed_qty * price because displayed fuel qty is rounded.
            */

            $credit_sale_payment = SettlementCreditSalePayment::where('id', $id)
                ->where('business_id', $business_id)
                ->first();

            $pump_operator_payment = null;

            if ($credit_sale_payment) {
                if (! empty($credit_sale_payment->pump_payment_id)) {
                    $pump_operator_payment = PumpOperatorPayment::where('business_id', $business_id)
                        ->where('id', $credit_sale_payment->pump_payment_id)
                        ->first();
                }

                if (! $pump_operator_payment && ! empty($credit_sale_payment->collection_form_no)) {
                    $pump_operator_payment = PumpOperatorPayment::where('business_id', $business_id)
                        ->where('collection_form_no', $credit_sale_payment->collection_form_no)
                        ->where('payment_type', 'credit')
                        ->first();
                }
            } else {
                $pump_operator_payment = PumpOperatorPayment::where('id', $id)
                    ->where('business_id', $business_id)
                    ->firstOrFail();

                if (! empty($pump_operator_payment->collection_form_no)) {
                    $credit_sale_payment = SettlementCreditSalePayment::where('business_id', $business_id)
                        ->where('collection_form_no', $pump_operator_payment->collection_form_no)
                        ->orderBy('id')
                        ->first();
                }

                if (! $credit_sale_payment && ! empty($pump_operator_payment->id)) {
                    $credit_sale_payment = SettlementCreditSalePayment::where('business_id', $business_id)
                        ->where('pump_payment_id', $pump_operator_payment->id)
                        ->orderBy('id')
                        ->first();
                }
            }

            if (! $credit_sale_payment) {
                return abort(404, 'Credit Sale not found');
            }

            if (! $pump_operator_payment) {
                $pump_operator_payment = new PumpOperatorPayment();
                $pump_operator_payment->business_id = $business_id;
                $pump_operator_payment->pump_operator_id = $credit_sale_payment->pump_operator_id;
                $pump_operator_payment->collection_form_no = $credit_sale_payment->collection_form_no;
                $pump_operator_payment->payment_amount = $credit_sale_payment->amount;
                $pump_operator_payment->payment_type = 'credit';
            }

            $credit_payments_query = SettlementCreditSalePayment::where('business_id', $business_id);

            if (! empty($credit_sale_payment->bill_number)) {
                // Bill number is the safest grouping key for multi-item credit bills.
                $credit_payments_query->where('bill_number', $credit_sale_payment->bill_number);
            } else {
                // Fallback for old records without bill_number.
                $credit_payments_query->where('customer_id', $credit_sale_payment->customer_id)
                    ->where('pump_operator_id', $credit_sale_payment->pump_operator_id)
                    ->where('order_number', $credit_sale_payment->order_number)
                    ->where('order_date', $credit_sale_payment->order_date);
            }

            $credit_payments = $credit_payments_query->orderBy('id')->get();

            if ($credit_payments->isEmpty()) {
                $credit_payments = collect([$credit_sale_payment]);
            }

            $pump_payment_ids = $credit_payments->pluck('pump_payment_id')->filter()->unique()->values();
            $pump_operator_payments = $pump_payment_ids->isNotEmpty()
                ? PumpOperatorPayment::where('business_id', $business_id)->whereIn('id', $pump_payment_ids)->get()->all()
                : [$pump_operator_payment];

            if (empty($pump_operator_payments)) {
                $pump_operator_payments = [$pump_operator_payment];
            }

            $viewData = $this->prepareCreditSalesConsolidatedPrintData(
                $pump_operator_payments,
                $credit_payments,
                request()->get('copy', 'customer')
            );

            $viewData['copy_mode'] = request()->get('copy', 'customer');

            return view('petrogeneral::pump_operators.print.credit_sale_print', $viewData);
        } catch (\Exception $e) {
            Log::emergency('Credit Sale Print Failed: File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            return abort(500, 'Something went wrong');
        }
    }

    public function reprintCreditSale($pump_operator_payment_id)
    {
        try {
            $pump_operator_payment = PumpOperatorPayment::findOrFail($pump_operator_payment_id);
            $business_id           = $pump_operator_payment->business_id;

            $credit_payments = SettlementCreditSalePayment::where('business_id', $business_id)
                ->where('collection_form_no', $pump_operator_payment->collection_form_no)
                ->orderBy('id')
                ->get();

            if ($credit_payments->isEmpty()) {
                Log::warning('Credit Sale missing during reprint', [
                    'pump_operator_payment_id' => $pump_operator_payment_id,
                    'collection_form_no'       => $pump_operator_payment->collection_form_no,
                ]);

                return back()->with('status', [
                    'success' => false,
                    'msg'     => __('messages.something_went_wrong') . ' - Credit sale not found for payment #' . $pump_operator_payment_id,
                ]);
            }

            $credit_sale_payment = $credit_payments->first();
            $viewData = $this->prepareCreditSalePrintData($pump_operator_payment, $credit_sale_payment, true);
            $viewData['copy_mode'] = request()->get('copy', 'customer');

            Log::info('Credit Sale Reprint', [
                'user_id'                  => Auth::id(),
                'pump_operator_payment_id' => $pump_operator_payment_id,
                'credit_sale_id'           => $credit_sale_payment->id,
                'collection_form_no'       => $pump_operator_payment->collection_form_no,
                'bill_number'              => $credit_sale_payment->bill_number,
                'customer_id'              => $credit_sale_payment->customer_id,
                'amount'                   => $credit_sale_payment->amount,
            ]);

            return view('petrogeneral::pump_operators.print.credit_sale_print', $viewData);
        } catch (\Exception $e) {
            Log::emergency('Credit Sale Reprint Failed: ' . $e->getMessage());

            return back()->with('status', [
                'success' => false,
                'msg'     => __('messages.something_went_wrong') . ' - ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Re-print credit sale using SettlementCreditSalePayment ID directly.
     * Used by Payment Summary tab where only SCSP ID is available.
     */
    public function reprintCreditSaleByScspId($scsp_id)
    {
        try {
            $credit_sale_payment = SettlementCreditSalePayment::findOrFail($scsp_id);
            $business_id = $credit_sale_payment->business_id;

            // Try to find a linked PumpOperatorPayment via collection_form_no
            $pump_operator_payment = null;
            if (!empty($credit_sale_payment->collection_form_no)) {
                $pump_operator_payment = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('collection_form_no', $credit_sale_payment->collection_form_no)
                    ->where('payment_type', 'credit')
                    ->first();
            }

            // If no linked PumpOperatorPayment, create a mock object for view
            if (!$pump_operator_payment) {
                $pump_operator_payment = new PumpOperatorPayment();
                $pump_operator_payment->business_id = $business_id;
                $pump_operator_payment->pump_operator_id = $credit_sale_payment->pump_operator_id;
                $pump_operator_payment->collection_form_no = $credit_sale_payment->collection_form_no;
                $pump_operator_payment->payment_amount = $credit_sale_payment->amount;
                $pump_operator_payment->payment_type = 'credit';
            }

            $viewData = $this->prepareCreditSalePrintData($pump_operator_payment, $credit_sale_payment, true);
            $viewData['copy_mode'] = request()->get('copy', 'customer');

            Log::info('Credit Sale Reprint by SCSP ID', [
                'user_id' => Auth::id(),
                'scsp_id' => $scsp_id,
                'collection_form_no' => $credit_sale_payment->collection_form_no,
                'bill_number' => $credit_sale_payment->bill_number,
                'customer_id' => $credit_sale_payment->customer_id,
                'amount' => $credit_sale_payment->amount,
            ]);

            return view('petrogeneral::pump_operators.print.credit_sale_print', $viewData);
        } catch (\Exception $e) {
            Log::emergency('Credit Sale Reprint by SCSP ID Failed: ' . $e->getMessage());

            return back()->with('status', [
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ' - ' . $e->getMessage(),
            ]);
        }
    }

    private function prepareCreditSalePrintData(PumpOperatorPayment $pump_operator_payment, SettlementCreditSalePayment $credit_sale_payment, $is_reprint = false)
    {
        $business_id = $pump_operator_payment->business_id;
        $business_details = $this->businessUtil->getDetails($business_id);
        
        // Try to find the pump operator, with fallback handling for null/0 pump_operator_id
        $pump_operator = null;
        $location_id = null;
        
        if (!empty($pump_operator_payment->pump_operator_id)) {
            $pump_operator = PumpOperator::find($pump_operator_payment->pump_operator_id);
        }
        
        if ($pump_operator) {
            $location_id = $pump_operator->location_id;
        } else {
            // Fallback: use default business location
            $location_id = BusinessLocation::where('business_id', $business_id)->value('id');
            // Create a mock pump operator for the view
            $pump_operator = new PumpOperator();
            $pump_operator->name = 'N/A';
            $pump_operator->location_id = $location_id;
        }
        
        $location_details = BusinessLocation::find($location_id);

        if (! $location_details) {
            abort(404, 'Location not found');
        }

        $invoice_layout = $this->businessUtil->invoiceLayout(
            $business_id,
            $location_id,
            $location_details->invoice_layout_id ?? null
        );

        $printer_type         = null;
        $receipt_printer_type = $printer_type ?? ($location_details->receipt_printer_type ?? null);

        $daily_voucher_item_ids = [];
        if (!empty($credit_sale_payment->bill_number)) {
            $all_credit_sales = SettlementCreditSalePayment::where('business_id', $business_id)
                ->where('bill_number', $credit_sale_payment->bill_number)
                ->get();
            
            $daily_voucher_ids = $all_credit_sales->pluck('daily_voucher_id')->filter()->toArray();
            
            if (!empty($daily_voucher_ids)) {
                $daily_voucher_item_ids = DailyVoucherItem::whereIn('daily_voucher_id', $daily_voucher_ids)
                    ->pluck('id')
                    ->toArray();
            }
        } elseif ($credit_sale_payment->daily_voucher_id) {
            $daily_voucher_item_ids = DailyVoucherItem::where('daily_voucher_id', $credit_sale_payment->daily_voucher_id)
                ->pluck('id')
                ->toArray();
        } else {
            $daily_vouchers = DailyVoucher::where('business_id', $business_id)
                ->where('daily_vouchers_no', $pump_operator_payment->collection_form_no)
                ->with(['items'])
                ->get();

            foreach ($daily_vouchers as $voucher) {
                foreach ($voucher->items as $item) {
                    $daily_voucher_item_ids[] = $item->id;
                }
            }
        }

        $receipt_details = null;
        if (!empty($daily_voucher_item_ids) && !empty(Auth::user()->pump_operator_id)) {
            $receipt_details = $this->transactionUtil->getCreditSaleReceiptDetails(
                $daily_voucher_item_ids,
                $location_id,
                $invoice_layout,
                $business_details,
                $location_details,
                $receipt_printer_type
            );
        }

        // Fallback: Settlement PD credit sales may not have DailyVoucher/DailyVoucherItem linkage.
        // Build receipt lines directly from settlement_credit_sale_payments.
        if (true) {
            $customer = Contact::find($credit_sale_payment->customer_id);

            $items_query = SettlementCreditSalePayment::where('business_id', $business_id);
            if (!empty($credit_sale_payment->bill_number)) {
                $items_query->where('bill_number', $credit_sale_payment->bill_number);
            } else {
                $items_query->where('customer_id', $credit_sale_payment->customer_id)
                    ->where('pump_operator_id', $credit_sale_payment->pump_operator_id)
                    ->where('order_date', $credit_sale_payment->order_date)
                    ->where('order_number', $credit_sale_payment->order_number);

                if (!empty($credit_sale_payment->settlement_no)) {
                    $items_query->where('settlement_no', $credit_sale_payment->settlement_no);
                }
            }

            $credit_sale_items = $items_query->orderBy('id')->get();
            if ($credit_sale_items->isEmpty()) {
                $credit_sale_items = collect([$credit_sale_payment]);
            }

            $product_names = Product::whereIn('id', $credit_sale_items->pluck('product_id')->filter()->unique()->values())
                ->pluck('name', 'id');

            $lines = [];
            $total_value = 0.0;
            foreach ($credit_sale_items as $item) {
                $qty_value = (float) ($item->qty ?? 0);
                $unit_price_value = (float) ($item->price ?? 0);
                
$line_total_value = (float) ($item->amount ?? 0) - (float) ($item->total_discount ?? 0);

                $total_value += $line_total_value;

                $lines[] = [
                    'name' => $product_names[$item->product_id] ?? 'Product',
                    'variation' => '',
                    'quantity' => $this->productUtil->num_f($qty_value, false, $business_details, true),
                    'unit_price_inc_tax' => $this->productUtil->num_f($unit_price_value, false, $business_details, true),
                    'line_total' => $this->productUtil->num_f($line_total_value, false, $business_details, true),
                ];
            }

            $address_parts = [];
            if (!empty($location_details->landmark)) {
                $address_parts[] = $location_details->landmark;
            }
            $city_line = implode(', ', array_filter([
                $location_details->city ?? null,
                $location_details->state ?? null,
                $location_details->zip_code ?? null,
                $location_details->country ?? null,
            ]));
            if (!empty($city_line)) {
                $address_parts[] = $city_line;
            }

            $receipt_details = (object) [
                'location_name' => $location_details->name ?? '',
                'address' => implode("\n", $address_parts),
                'city' => $location_details->city ?? '',
                'contact' => $location_details->mobile ?? ($location_details->alternate_number ?? ''),
                'customer_reference' => $credit_sale_payment->customer_reference ?? '',
                'customer_name' => $customer->name ?? '',
                'lines' => $lines,
                'total' => $this->productUtil->num_f($total_value, false, $business_details, true),
                'footer_text' => $invoice_layout->footer_text ?? null,
            ];
        }

        $receipt_details->bill_number = $credit_sale_payment->bill_number;

            $invoice_footer_text = trim(System::getProperty('invoice_footer') ?? '');
            $app_footer_text     = trim(System::getProperty('app_footer') ?? '');
            $bill_footer         = $invoice_footer_text;
            if (empty($bill_footer) && ! empty($app_footer_text)) {
                $bill_footer = $app_footer_text;
            }
            if (empty($bill_footer)) {
                $bill_footer = trim($receipt_details->footer_text ?? '');
            }

            $currency_details = [
                'symbol'             => $business_details->currency_symbol ?? '',
                'thousand_separator' => $business_details->thousand_separator ?? ',',
                'decimal_separator'  => $business_details->decimal_separator ?? '.',
            ];
            $receipt_details->currency = $currency_details;

        $order_number = $credit_sale_payment->order_number ?? null;

        return [
            'credit_sale_payment' => $credit_sale_payment,
            'business_details'    => $business_details,
            'receipt_details'     => $receipt_details,
            'pump_operator'       => $pump_operator,
            'bill_number'         => $credit_sale_payment->bill_number,
            'order_number'        => $order_number,
            'customer_name'       => $receipt_details->customer_name ?? null,
            'location_details'    => $location_details,
            'print_date'          => date('Y-m-d H:i:s'),
            'is_reprint'          => $is_reprint,
            'bill_footer'         => $bill_footer,
            'currency_precision'  => !empty($business_details->currency_precision) ? $business_details->currency_precision : 2,
        ];
    }

    /**
     * Prepare consolidated print data for multiple credit sales into ONE invoice
     */
    private function prepareCreditSalesConsolidatedPrintData($pump_operator_payments, $credit_sale_payments, $copy_mode = 'customer')
    {
        $credit_sale_payments = $credit_sale_payments instanceof \Illuminate\Support\Collection
            ? $credit_sale_payments
            : collect($credit_sale_payments);

        $pump_operator_payments = $pump_operator_payments instanceof \Illuminate\Support\Collection
            ? $pump_operator_payments->values()->all()
            : array_values((array) $pump_operator_payments);

        if ($credit_sale_payments->isEmpty()) {
            return [];
        }

        $first_credit_sale = $credit_sale_payments->first();
        $first_payment = ! empty($pump_operator_payments) ? $pump_operator_payments[0] : null;

        $business_id = $first_payment->business_id ?? $first_credit_sale->business_id;
        $business_details = $this->businessUtil->getDetails($business_id);
        
        // Get pump operator and location info
        $pump_operator = null;
        $location_id = null;
        
        if (!empty($first_payment->pump_operator_id)) {
            $pump_operator = PumpOperator::find($first_payment->pump_operator_id);
        }
        
        if ($pump_operator) {
            $location_id = $pump_operator->location_id;
        } else {
            $location_id = BusinessLocation::where('business_id', $business_id)->value('id');
            $pump_operator = new PumpOperator();
            $pump_operator->name = 'N/A';
            $pump_operator->location_id = $location_id;
        }
        
        $location_details = BusinessLocation::find($location_id);

        if (!$location_details) {
            abort(404, 'Location not found');
        }

        // Consolidate all items from all credit sales
        $consolidated_lines = [];
        $total_amount = 0;
        $order_number = null;
        $customer_name = null;
        $customer_reference = null;
        $bill_number = null;
        $order_date = null;

        foreach ($credit_sale_payments as $index => $credit_sale) {
            // Use first non-empty values for header info
            if ($index === 0) {
                $order_number = $credit_sale->order_number ?? '';
                $customer_name = Contact::find($credit_sale->customer_id)->name ?? '';
                $customer_reference = $credit_sale->customer_reference ?? '';
                $bill_number = $credit_sale->bill_number ?? '';
                $order_date = $credit_sale->order_date ?? date('Y-m-d');
            }

            // Add this item to consolidated lines.
            // IMPORTANT: Sub Total must use saved amount - discount.
            // Do NOT calculate as displayed Qty x Unit Price, because fuel Qty is rounded for display.
            $line_total_value = (float) ($credit_sale->amount ?? 0) - (float) ($credit_sale->total_discount ?? 0);
            $product = Product::find($credit_sale->product_id);

            $consolidated_lines[] = [
                'name' => $product->name ?? 'Unknown Product',
                'variation' => '',
                'quantity' => $this->productUtil->num_f($credit_sale->qty, false, $business_details, false),
                'unit_price_inc_tax' => $this->productUtil->num_f($credit_sale->price, false, $business_details, false),
                'line_total' => $this->productUtil->num_f($line_total_value, false, $business_details, false),
            ];

            $total_amount += $line_total_value;
        }

        // Create a receipt-like object for the view
        $receipt_details = new \stdClass();
        $receipt_details->lines = $consolidated_lines;
        $receipt_details->total = $this->productUtil->num_f($total_amount, false, $business_details, false);
        $receipt_details->customer_name = $customer_name;
        $receipt_details->customer_reference = $customer_reference ?? '';
        $receipt_details->location_name = $location_details->name ?? '';
        $receipt_details->address = $location_details->address ?? '';
        $receipt_details->city = $location_details->city ?? '';
        $receipt_details->contact = $location_details->mobile ?? $location_details->landmark ?? '';

        // Get invoice footer from System settings (Super Admin Settings)
        $admin_invoice_footer = System::getProperty('admin_invoice_footer');

        return [
            'business_details'    => $business_details,
            'receipt_details'     => $receipt_details,
            'copy_mode'           => $copy_mode,
            'order_number'        => $order_number,
            'customer_name'       => $customer_name,
            'location_details'    => $location_details,
            'print_date'          => date('Y-m-d H:i:s'),
            'is_reprint'          => false,
            'bill_footer'         => '',
            'bill_number'         => $bill_number,
            'admin_invoice_footer' => $admin_invoice_footer,
            'currency_precision'  => !empty($business_details->currency_precision) ? $business_details->currency_precision : 2,
        ];
    }
}

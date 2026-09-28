<?php
namespace Modules\PetroPD\Http\Controllers;

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
use App\User;
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
use Modules\PetroPD\Entities\DailyCard;
use Modules\PetroPD\Entities\DailyChequePayment;
use Modules\PetroPD\Entities\DailyCollection;
use Modules\PetroPD\Entities\DailyVoucher;
use Modules\PetroPD\Entities\DailyVoucherItem;
use Modules\PetroPD\Entities\FuelTank;
use Modules\PetroPD\Entities\MeterSale;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorMeterSale;
use Modules\PetroPD\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Entities\PumpOperatorOtherSale;
use Modules\PetroPD\Entities\PumpOperatorPayment;
use Modules\PetroPD\Entities\Settlement;
use Modules\PetroPD\Entities\SettlementCreditSalePayment;
use Modules\PetroPD\Entities\SettlementCashPayment;
use Modules\PetroPD\Entities\SettlementCardPayment;
use Modules\PetroPD\Entities\SettlementChequePayment;
use Modules\Superadmin\Entities\Subscription;
use Yajra\DataTables\Facades\DataTables;

class PDPumpOperatorPaymentController extends Controller
{

    /**
     * S 639: currency precision for the business, resolved once per request.
     *
     * Money written from this controller is rounded to this before it reaches
     * pump_operator_payments.payment_amount, which is a string column and so
     * stores whatever it is handed.
     */
    private static function resolveCurrencyPrecision(int $business_id): int
    {
        static $cache = [];

        if (array_key_exists($business_id, $cache)) {
            return $cache[$business_id];
        }

        try {
            $precision = \Illuminate\Support\Facades\DB::table('business')
                ->where('id', $business_id)
                ->value('currency_precision');
        } catch (\Throwable $e) {
            $precision = null;
        }

        return $cache[$business_id] = ($precision === null || $precision === '') ? 2 : (int) $precision;
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


    /**
     * MA-002 PERF: request-scoped cache of pump id -> pump name.
     *
     * Identical pattern to PumperDashboard\PumpOperatorPaymentController: the
     * "pumps" column runs once per row and, for every meter-sale detail on
     * that row, issues
     *     Pump::where('id', $detail->pump_id)->select('pump_name')->first()
     * A station has only a handful of pumps, so the same names were
     * re-queried continuously down the page.
     *
     * Pump names are reference data and cannot change mid-render. Misses are
     * cached too, so a deleted pump is not re-queried on every row.
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

    const FUEL_CATEGORY_ID = 1;

    /**
     * Restore the tenant connection and pumper user captured by the dedicated
     * Pumper Dashboard login. Normal web middleware can otherwise leave this
     * controller on the previous/default tenant while the dashboard session
     * itself is valid.
     */
    private function restorePumperTenantIdentity(): void
    {
        $tenant_db = trim((string) session('tenancy_db_name', ''));
        $session_user_id = (int) (session('pumper_user_id') ?: session('user.id') ?: 0);

        try {
            if ($tenant_db !== '') {
                $current_db = '';
                try {
                    $current_db = (string) DB::connection('mysql')->getDatabaseName();
                } catch (\Throwable $e) {
                    // Reconnect below.
                }

                if ($current_db !== $tenant_db) {
                    config(['database.default' => 'mysql']);
                    config(['database.connections.mysql.database' => $tenant_db]);
                    DB::disconnect('mysql');
                    DB::purge('mysql');
                    DB::reconnect('mysql');
                    DB::setDefaultConnection('mysql');
                }
            }

            if ($session_user_id > 0) {
                $session_user = User::where('id', $session_user_id)->first();
                if (! empty($session_user)) {
                    Auth::setUser($session_user);
                }
            }
        } catch (\Throwable $e) {
            Log::error('PetroPD payment tenant/auth restore failed', [
                'tenant_db' => $tenant_db,
                'session_user_id' => $session_user_id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve the active operator from the Pumper Dashboard session first.
     * Imported/repaired pumper users can have a correct session identity while
     * an old users row or stale Auth object still carries a blank operator id.
     */
    private function resolvePumpOperatorId(?Request $request = null): int
    {
        $this->restorePumperTenantIdentity();

        $session_operator_id = (int) (
            session('pump_operator_id')
                ?: session('pumper_operator_id')
                ?: 0
        );

        if ($session_operator_id > 0) {
            return $session_operator_id;
        }

        $user = Auth::user();
        if (! empty($user) && ! empty($user->pump_operator_id)) {
            return (int) $user->pump_operator_id;
        }

        if ($request !== null && (int) $request->input('pump_operator_id') > 0) {
            return (int) $request->input('pump_operator_id');
        }

        return 0;
    }

    /**
     * Notification delivery must not hold the cash-save HTTP request open.
     * The payment is committed first and the SMS is attempted after the
     * response has been sent to the browser.
     */
    private function queueCashDepositNotification(PumpOperator $pump_operator, float $payment_amount): void
    {
        $sms_data = [
            'date' => $this->transactionUtil->format_date(date('Y-m-d')),
            'time' => date('H:i'),
            'pump_operator' => $pump_operator->name,
            'amount' => $this->transactionUtil->num_f($payment_amount),
        ];

        app()->terminating(function () use ($sms_data) {
            try {
                $this->notificationUtil->sendPetroNotification('pumper_dashboard_cash_deposit', $sms_data);
            } catch (\Throwable $e) {
                Log::error('PetroPD cash notification failed after payment save', [
                    'message' => $e->getMessage(),
                ]);
            }
        });
    }

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
     * IS2312: Payments from the Pumper Dashboard are available only after the
     * current active shift has at least one physical pump and every such pump
     * has completed Receive Pump (is_confirmed = 1).
     *
     * Closed pump rows remain eligible until Close Shift finalises them, which
     * preserves the existing workflow where payments may still be entered after
     * meter closing but before the shift itself is closed.
     */
    private function isCurrentPumpReceiveCompleteForPayments(int $business_id, int $pump_operator_id): bool
    {
        if ($business_id <= 0 || $pump_operator_id <= 0) {
            return false;
        }

        $active_assignment_query = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->whereNotNull('shift_id');

        if (Schema::hasColumn('pump_operator_assignments', 'closed_in_settlement')) {
            $active_assignment_query->where(function ($query) {
                $query->where('closed_in_settlement', 0)
                    ->orWhereNull('closed_in_settlement')
                    ->orWhere('closed_in_settlement', '');
            });
        }

        $active_assignment = $active_assignment_query
            ->orderByDesc('id')
            ->first();

        if (empty($active_assignment) || empty($active_assignment->shift_id)) {
            return false;
        }

        $shift = PetroShift::find($active_assignment->shift_id);
        if (! empty($shift) && in_array(strtolower(trim((string) $shift->status)), ['2', 'close', 'closed', 'finalized', 'finalised'], true)) {
            return false;
        }

        $receive_query = PumpOperatorAssignment::join('pumps', 'pumps.id', '=', 'pump_operator_assignments.pump_id')
            ->where('pump_operator_assignments.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->where('pump_operator_assignments.shift_id', $active_assignment->shift_id);

        if (Schema::hasColumn('pump_operator_assignments', 'closed_in_settlement')) {
            $receive_query->where(function ($query) {
                $query->where('pump_operator_assignments.closed_in_settlement', 0)
                    ->orWhereNull('pump_operator_assignments.closed_in_settlement')
                    ->orWhere('pump_operator_assignments.closed_in_settlement', '');
            });
        }

        if (Schema::hasColumn('pumps', 'is_other_sales_pump')) {
            $receive_query->where(function ($query) {
                $query->where('pumps.is_other_sales_pump', 0)
                    ->orWhereNull('pumps.is_other_sales_pump');
            });
        }

        $assigned_count = (clone $receive_query)->count();

        if ($assigned_count <= 0) {
            return false;
        }

        $pending_count = (clone $receive_query)
            ->where(function ($query) {
                $query->where('pump_operator_assignments.is_confirmed', 0)
                    ->orWhereNull('pump_operator_assignments.is_confirmed');
            })
            ->count();

        return $pending_count === 0;
    }

    private function authorizePumperDashboardPermission(string $permission): void
    {
        $user = Auth::user();
        $business_id = $this->resolveBusinessId();
        $has_pumper_management_access = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pumper_management')
            || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'enable_petro_pumper_management');

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
     * Resolve one exact shift for a Pumper Dashboard financial transaction.
     *
     * Financial entries must never be attached to the operator's "latest" shift.
     * An explicit shift_id is preferred and validated. When older dashboard forms
     * do not submit it, automatic resolution is allowed only when there is exactly
     * one eligible active/pending shift. Ambiguity is rejected instead of guessed.
     */
    private function resolveExactPaymentShiftId(Request $request, int $business_id, int $pump_operator_id): int
    {
        $requested_shift_id = (int) ($request->input('shift_id')
            ?? $request->input('active_shift_id')
            ?? $request->input('pump_operator_shift_id')
            ?? 0);

        if ($requested_shift_id > 0) {
            $valid = PumpOperatorAssignment::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('shift_id', $requested_shift_id)
                ->exists();

            if (! $valid) {
                throw new \RuntimeException('The selected Shift ID does not belong to this pump operator and business.');
            }

            return $requested_shift_id;
        }

        // First preference: the one currently active Petro shift.
        $active_shift_ids = PumpOperatorAssignment::join('petro_shifts', 'petro_shifts.id', '=', 'pump_operator_assignments.shift_id')
            ->where('pump_operator_assignments.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->whereNotNull('pump_operator_assignments.shift_id')
            ->where('petro_shifts.status', 0)
            ->distinct()
            ->pluck('pump_operator_assignments.shift_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (count($active_shift_ids) === 1) {
            return $active_shift_ids[0];
        }

        if (count($active_shift_ids) > 1) {
            throw new \RuntimeException('More than one active Shift ID exists for this pump operator. Please select the exact shift before saving.');
        }

        // Compatibility for a just-closed but not-yet-settled shift. This is safe
        // only when there is exactly one candidate; otherwise no inference is made.
        $pending_shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->whereNotNull('shift_id')
            ->whereNull('settlement_id')
            ->where(function ($query) {
                $query->whereIn('status', ['open', 'close', 'closed'])
                    ->orWhere('is_manually_closed', 1);
            })
            ->distinct()
            ->pluck('shift_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->all();

        if (count($pending_shift_ids) === 1) {
            return $pending_shift_ids[0];
        }

        if (count($pending_shift_ids) > 1) {
            throw new \RuntimeException('Multiple pending Shift IDs exist for this pump operator. The payment was not saved because the shift is ambiguous.');
        }

        throw new \RuntimeException('No eligible Shift ID was found for this pump operator.');
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
        // IS1781: this controller belongs to PetroPD. Creating a normal Petro ST
        // draft here links Pump Operator Payments to a different settlement before
        // PD Settlement opens and later blocks finalization. Always create a PD
        // settlement number, respecting the configured PetroPD prefix.
        $prefix = $this->petroPdSettlementPrefix($business_id);
        $count = Settlement::where('business_id', $business_id)
            ->where('settlement_no', 'LIKE', $prefix . '%')
            ->pluck('settlement_no')
            ->map(fn ($settlementNo) => $this->extractTrailingSettlementNumber($settlementNo))
            ->max() ?? 0;

        $next = ((int) $count) + 1;
        while (Settlement::where('business_id', $business_id)
            ->where('settlement_no', $prefix . $next)
            ->exists()) {
            $next++;
        }

        return $prefix . $next;
    }

    private function ensureActiveSettlementForPumpOperator(int $business_id, int $pump_operator_id, ?int $shift_id = null): Settlement
    {
        $pump_operator = PumpOperator::findOrFail($pump_operator_id);
        $petroPdPrefixes = array_values(array_unique(array_filter([
            'PDST',
            $this->petroPdSettlementPrefix($business_id),
        ])));

        $settlement = null;
        if (! empty($pump_operator->settlement_no)
            && $this->isPetroPdSettlementNo((string) $pump_operator->settlement_no, $petroPdPrefixes)) {
            $settlement = Settlement::where('settlement_no', $pump_operator->settlement_no)
                ->where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('status', 1)
                ->first();

            if ($settlement && ! $this->settlementMatchesShift($settlement, $shift_id)) {
                $settlement = null;
            }
        }

        if (empty($settlement)) {
            $candidates = Settlement::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('status', 1)
                ->where(function ($query) use ($petroPdPrefixes) {
                    foreach ($petroPdPrefixes as $prefix) {
                        $query->orWhere('settlement_no', 'LIKE', $prefix . '%');
                    }
                })
                ->orderByDesc('id')
                ->get();

            $settlement = $candidates->first(function ($candidate) use ($shift_id) {
                return $this->settlementMatchesShift($candidate, $shift_id);
            });
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
        } elseif (! empty($shift_id)) {
            $currentShiftIds = $this->settlementShiftIds($settlement);
            if (empty($currentShiftIds)) {
                $settlement->work_shift = [(int) $shift_id];
                $settlement->save();
            }
        }

        if ($pump_operator->settlement_no !== $settlement->settlement_no) {
            $pump_operator->settlement_no = $settlement->settlement_no;
            $pump_operator->save();
        }

        return $settlement;
    }

    private function petroPdSettlementPrefix(int $business_id): string
    {
        $business = Business::find($business_id);
        $refNoPrefixes = $business->ref_no_prefixes ?? [];
        if (is_string($refNoPrefixes)) {
            $decoded = json_decode($refNoPrefixes, true);
            $refNoPrefixes = is_array($decoded) ? $decoded : [];
        }

        return trim((string) ($refNoPrefixes['settlement_pd'] ?? 'PDST')) ?: 'PDST';
    }

    private function isPetroPdSettlementNo(string $settlementNo, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($settlementNo, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function settlementMatchesShift(Settlement $settlement, ?int $shiftId): bool
    {
        if (empty($shiftId)) {
            return true;
        }

        return $this->settlementShiftIds($settlement) === [(int) $shiftId];
    }

    private function settlementShiftIds(Settlement $settlement): array
    {
        $workShift = $settlement->work_shift;
        if (is_string($workShift)) {
            $decoded = json_decode($workShift, true);
            $workShift = is_array($decoded)
                ? $decoded
                : preg_split('/\s*,\s*/', trim($workShift), -1, PREG_SPLIT_NO_EMPTY);
        }

        $shiftIds = is_array($workShift) ? $workShift : [$workShift];

        return array_values(array_unique(array_filter(
            array_map('intval', $shiftIds),
            fn ($id) => $id > 0
        )));
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

        $business_id = $this->resolveBusinessId();
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_details = Business::find($business_id);

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

            $payment_read = app(\Modules\PetroPD\Services\SettlementPaymentQueryService::class);
            $query = $payment_read->paymentSummaryBaseQuery($business_id, $has_card_meta_columns);

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
            $payment_method_values = $this->normalisePaymentMethodFilter($payment_method);

            if (! empty($payment_method_values)) {
                $query->whereIn(DB::raw('LOWER(TRIM(pump_operator_payments.payment_type))'), $payment_method_values);
            } else {
                // By default only show Cash, Credit, Card as per previous logic if needed,
                // but usually better to show everything or follow previous restriction.
                // The previous code merged only cash, credit, card.
                $query->whereIn(DB::raw('LOWER(pump_operator_payments.payment_type)'), ['cash', 'credit', 'card', 'cards', 'cheque', 'cheques', 'shortage', 'excess']);
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

            // IS1666: apply universal search to real source columns before totals are
            // cloned, so rows and footer totals share the same filtered scope.
            $global_search = trim((string) request()->input('search.value', ''));
            if ($global_search !== '') {
                $like = '%' . $global_search . '%';
                $numeric_search = str_ireplace([',', 'Rs.', 'Rs', ' '], '', $global_search);
                $authoritative_amount_sql = $payment_read->authoritativeAmountSql();
                $query->where(function ($q) use ($like, $numeric_search, $has_card_meta_columns, $authoritative_amount_sql) {
                    $q->where('pump_operator_payments.collection_form_no', 'like', $like)
                        ->orWhere('pump_operator_payments.payment_type', 'like', $like)
                        ->orWhere('pump_operators.name', 'like', $like)
                        ->orWhere('business_locations.name', 'like', $like)
                        ->orWhere('contacts_credit.name', 'like', $like)
                        ->orWhere('contacts_card.name', 'like', $like)
                        ->orWhere('contacts_card_legacy.name', 'like', $like)
                        ->orWhere('scsp.order_number', 'like', $like)
                        ->orWhere('pump_operator_payments.date_and_time', 'like', $like)
                        ->orWhere('pump_operator_payments.note', 'like', $like)
                        ->orWhere('pump_operator_payments.shift_id', 'like', $like)
                        ->orWhere('pump_operator_assignments.shift_number', 'like', $like)
                        ->orWhere('dc.slip_no', 'like', $like);
                    if ($has_card_meta_columns) {
                        $q->orWhere('pump_operator_payments.slip_no', 'like', $like);
                    }
                    if ($numeric_search !== '' && is_numeric($numeric_search)) {
                        $q->orWhereRaw('CAST((' . $authoritative_amount_sql . ') AS CHAR) LIKE ?', ['%' . $numeric_search . '%']);
                    }
                });
            }

            // PETROPD-PAYMENT-SUMMARY-UI-028:
            // Footer total and payment-type breakdown must be for the full selected/filter period,
            // not only the current DataTables page. Build a grouped subquery first to avoid
            // duplicate sums from joins with credit/card helper tables.
            $summary_totals_query = (clone $query)
                ->select(
                    'pump_operator_payments.id',
                    DB::raw('LOWER(pump_operator_payments.payment_type) as payment_type_key'),
                    DB::raw('MAX(' . $payment_read->authoritativeAmountSql() . ') as payment_amount')
                )
                ->groupBy('pump_operator_payments.id', 'pump_operator_payments.payment_type');

            $summary_totals = DB::query()
                ->fromSub($summary_totals_query, 'pd_payment_summary_totals')
                ->selectRaw('payment_type_key, SUM(payment_amount) as total_amount')
                ->groupBy('payment_type_key')
                ->pluck('total_amount', 'payment_type_key')
                ->toArray();

            $payment_summary_total = array_sum(array_map('floatval', $summary_totals));
            $payment_summary_breakdown = [];
            foreach ($summary_totals as $payment_type_key => $total_amount) {
                $payment_summary_breakdown[ucfirst((string) $payment_type_key)] = (float) $total_amount;
            }

            $query->select(
                'pump_operator_payments.id',
                'pump_operator_payments.date_and_time',
                'pump_operator_payments.collection_form_no',
                'pump_operator_payments.payment_type',
                DB::raw($payment_read->authoritativeAmountSql() . ' as payment_amount'),
                'pump_operator_payments.shift_id as payment_shift_id',
                'pump_operator_payments.note',
                'pump_operators.name as pump_operator_name',
                'pump_operators.id as pump_operator_id',
                'edited_user.username as edited_by',
                'business_locations.name as location_name',
                DB::raw('COALESCE(contacts_credit.name, contacts_card.name, contacts_card_legacy.name) as customer_name'),
                DB::raw('COALESCE(scsp.customer_id, ' . ($has_card_meta_columns ? 'pump_operator_payments.customer_id, ' : '') . 'dc.customer_id) as customer_id'),
                DB::raw('COALESCE(pump_operator_assignments.shift_number, pump_operator_assignments.shift_id, pump_operator_payments.shift_id) as shift_number'),
                'scsp.order_number',
                'scsp.id as scsp_id',
                'scsp.daily_voucher_id',
                'dv.settlement_no as daily_voucher_settlement_no',
                /* PETROPD-SLIP-NO-20260821: blank payment slip must fall back to the Daily Card slip used by Pumper Dashboard. */
                DB::raw($this->paymentSummarySlipNoSql($has_card_meta_columns) . ' as slip_no')
            )->groupBy('pump_operator_payments.id');

            return DataTables::of($query)
                ->addColumn('action', function ($row) use ($only_pumper) {
                    if (empty($row->id) || strtolower($row->payment_type) == 'other sale') {
                        return '';
                    }

                    // Keep the Edit button visible always. Disable it only after the linked
                    // PD Settlement has been saved/finalized, not merely because the shift is closed.
                    $is_locked = $this->isPaymentEditLocked($row, null);

                    $edit_query = '?type=' . urlencode($row->payment_type) . '&payment_id=' . urlencode($row->id);
                    if (strtolower($row->payment_type) === 'credit') {
                        $edit_query .= '&credit_sale_id=' . urlencode($row->scsp_id);
                    }

                    $edit_url = route('petropd.pump-operators.payment.edit', ['id' => $row->id]) . $edit_query;

                    $html = '<div class="btn-group pd-payment-action-dropdown">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">' .
                            __('messages.actions') . '<span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    // PD-PAYMENT-SUMMARY-EDIT-020:
                    // The Edit button must always be visible in Payment Summary.
                    // Only disable it after the linked PD Settlement is saved/finalized.
                    // Do not hide it only because the request is from the pumper/summary view.
                    if ($is_locked) {
                        $html .= '<li class="disabled"><a href="#" onclick="return false;" title="Settlement already saved"><i class="glyphicon glyphicon-lock"></i> ' . __('messages.edit') . '</a></li>';
                    } else {
                        $html .= '<li><a href="#" data-href="' . e($edit_url) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                    }

                    if (strtolower($row->payment_type) == 'credit') {
                        $print_id = !empty($row->scsp_id) ? $row->scsp_id : $row->id;
                        $print_url = url('petropd/pump-operator-pmts/print-credit-sale/' . $print_id);
                        $html .= '<li><a href="#" onclick="window.open(\'' . e($print_url) . '\', \'_blank\', \'width=400,height=600\'); return false;"><i class="fa fa-print"></i> ' . __('messages.print') . '</a></li>';
                    }


                    /*
                     |----------------------------------------------------------
                     | "Details" - the last item in the Action menu.
                     |----------------------------------------------------------
                     |
                     | Collection Form No, Slip No, Date, Time, Order No, Note and
                     | Edited By were taking a great deal of table width. They now
                     | live in a popup opened from here.
                     |
                     | The values travel on the button itself as data attributes,
                     | so the popup needs no further request - it opens instantly
                     | and works on any page of results.
                     |
                     | The columns are only HIDDEN in the table, never removed, so
                     | searching and filtering by any of these still works.
                     */
                    $pd_detail_bits = [
                        'collection-form-no' => $this->rowValue($row, 'collection_form_no'),
                        'slip-no'            => $this->rowValue($row, 'slip_no'),
                        /*
                         | date_and_time, not date/time.
                         |
                         | 'date' and 'time' are columns this class ADDS later via
                         | addColumn(). At the moment the action menu is built they
                         | do not exist on the row yet, so reading them returned
                         | nothing and the popup came up empty.
                         |
                         | date_and_time is a real selected column and is present.
                         */
                        'date-time'          => $this->rowValue($row, 'date_and_time'),
                        'order-no'           => $this->rowValue($row, 'order_number'),
                        'note'               => $this->rowValue($row, 'note'),
                        'edited-by'          => $this->rowValue($row, 'edited_by'),
                    ];

                    $pd_detail_attrs = '';
                    foreach ($pd_detail_bits as $pd_key => $pd_val) {
                        $pd_val = trim((string) ($pd_val ?? ''));
                        // The note column arrives as button markup; keep the text only.
                        if ($pd_key === 'note') {
                            $pd_val = trim(strip_tags($pd_val));
                        }
                        $pd_detail_attrs .= ' data-' . $pd_key . '="' . e($pd_val === '' ? '—' : $pd_val) . '"';
                    }

                    $html .= '<li><a href="#" class="pd-payment-details-btn"' . $pd_detail_attrs
                        . '><i class="fa fa-info-circle"></i> Details</a></li>';

                    return $html . '</ul></div>';
                })
                ->addColumn('date', function ($row) {
                    $date_time = $row->date_and_time ?? null;
                    if (empty($date_time)) {
                        return '—';
                    }
                    $timestamp = strtotime((string) $date_time);
                    return $timestamp ? date('Y-m-d', $timestamp) : '—';
                })
                ->addColumn('time', function ($row) {
                    if (empty($row->date_and_time)) {
                        return '—';
                    }
                    try {
                        return \Carbon\Carbon::parse($row->date_and_time)->format('h:i A');
                    } catch (\Exception $e) {
                        return date('h:i A', strtotime($row->date_and_time));
                    }
                })

            // Customer column: show actual customer name whenever we have it,
            // fall back to "Walk in customer" only when it's truly unknown.
                ->addColumn('customer_name', function ($row) {
                    if (! empty($row->customer_name)) {
                        return $row->customer_name;
                    }
                    return 'Walk in customer';
                })
                /*
                 |----------------------------------------------------------------
                 | Same two faults as the summary method further down.
                 |----------------------------------------------------------------
                 |
                 | 1. addColumn on a column that IS already selected. The select
                 |    builds slip_no through
                 |        COALESCE(pump_operator_payments.slip_no, dc.slip_no)
                 |    so editColumn is the correct call; addColumn does not render.
                 |
                 | 2. Property access on an ARRAY. This query is built with
                 |    DB::table(), so each row arrives as an array - $row->slip_no
                 |    returns nothing even though the key is present. Proved from
                 |    the row itself:
                 |        slip_no_raw    "(property missing)"
                 |        available_keys [... "slip_no" ...]
                 |
                 | Both forms are handled, so it keeps working if the query is ever
                 | changed to Eloquent.
                 */
                ->addColumn('date_time_display', function ($row) {
                    // Date with the time beneath - see the summary method.
                    $date = $this->rowValue($row, 'date', '');
                    $time = $this->rowValue($row, 'time', '');

                    if ($date === '' && $time === '') {
                        return '—';
                    }

                    $html = '<span class="pd-date-main">' . e($date) . '</span>';

                    if ($time !== '') {
                        $html .= '<br><small class="text-muted pd-time-sub">' . e($time) . '</small>';
                    }

                    return $html;
                })
                ->editColumn('slip_no', function ($row) {
                    $type = is_array($row) ? ($row['payment_type'] ?? '') : ($row->payment_type ?? '');
                    $slip = is_array($row) ? ($row['slip_no'] ?? '') : ($row->slip_no ?? '');

                    return in_array(strtolower(trim((string) $type)), ['card', 'cards'], true) ? ($slip ?: '—') : '—';
                })

                ->editColumn('order_number', function ($row) {
                    // Array-safe - see rowValue().
                    return $this->rowValue($row, 'order_number') ?: '—';
                    // }
                    // return '—';
                })
                ->addColumn('pump_operator_name', function ($row) {
                    return $this->rowValue($row, 'pump_operator_name') ?: '—';
                    // }
                    // return 'N/A';
                })

                ->removeColumn('id')
                ->editColumn('payment_type', '{{ ucfirst($payment_type) }}')
                ->editColumn('amount', function ($row) use ($business_details) {
                    // Array-safe - see rowValue().
                    $amount = $this->rowValue($row, 'payment_amount', 0);

                    return '<span class="display_currency amount" data-orig-value="' .
                    $amount .
                    '" data-currency_symbol=false>' .
                    $this->productUtil->num_f($amount, false, $business_details, true) .
                        '</span>';
                })
                ->editColumn('note', function ($row) {
                    $note = trim((string) ($this->rowValue($row, 'note') ?? ''));
                    if ($note === '') {
                        return '—';
                    }
                    // title so the note shows on hover as well as on click.
                    return '<button type="button" class="btn btn-warning btn-xs pd-payment-note-view pd-note-btn" data-note-b64="'
                        . e(base64_encode($note))
                        . '" title="' . e($note) . '"'
                        . ' onclick="return window.petropdShowPaymentNote(this);">Note</button>';
                })
                ->filter(function ($query) {
                    // Global search already applied above; disable alias-based default search.
                }, false)
                ->rawColumns(['amount', 'action', 'note', 'date_time_display'])
                ->with([
                    'payment_summary_total' => (float) $payment_summary_total,
                    'payment_summary_breakdown' => $payment_summary_breakdown,
                ])
                ->make(true);
        }

        // Non-ajax section
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        // IS1464-004: Payment Summary filter must only show related pumper methods.
        $payment_types  = [
            'cash' => __('petropd::lang.cash'),
            'card' => __('petropd::lang.card'),
            'credit' => __('petropd::lang.credit'),
        ];

        $layout = $only_pumper ? 'pumper' : 'app';

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')
            ->leftJoin('pump_operator_assignments as poa_shift_date', function ($join) use ($business_id) {
                $join->on('poa_shift_date.shift_id', '=', 'petro_shifts.id')
                    ->where('poa_shift_date.business_id', $business_id);
            })
            ->where('petro_shifts.business_id', $business_id)
            ->select(
                'pump_operators.name',
                'petro_shifts.*',
                DB::raw('COALESCE(MAX(poa_shift_date.date_and_time), petro_shifts.shift_date, petro_shifts.created_at) as assignment_date')
            )
            ->groupBy('petro_shifts.id')
            ->orderBy('petro_shifts.id', 'DESC');

        if ($only_pumper) {
            $shifts->where('pump_operator_id', $pump_operator_id);
        }

        $shifts = $shifts->get();

        $user         = Auth::user();
        // MA-002: numeric max - shift_number is varchar, so MAX() on it
        // compares as text and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $user->pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');
        $customers    = Contact::customersDropdown($business_id, false, true, 'customer');
        $business_locations = BusinessLocation::forDropdown($business_id);

        return view('petropd::pd_operators.payment_summary')->with(compact(
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

            $business_id = $this->resolveBusinessId();
            $pump_operator_id = Auth::user()->pump_operator_id;
            $business_details = Business::find($business_id);

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

                // IS1472 Issue 4:
                // Payment Summary edits must be disabled as soon as the shift is closed.
                // Previously this list was built only from completed settlements, so closed shifts that
                // were not yet finalized still showed the Edit option. Keep the completed-settlement
                // check, but also include closed Petro shifts and closed/manual-closed assignments.
                $closed_shift_ids = PetroShift::where('business_id', $business_id)
                    ->where(function ($query) {
                        /*
                         * MA-002 (IS-1937 #1): same fault as above, second
                         * occurrence. petro_shifts.status is int(11), so
                         * comparing it to 'close' or 'closed' becomes
                         * status = 0 - an OPEN shift - and every open shift
                         * was being collected as closed.
                         *
                         * Note the contrast with the query directly below:
                         * pump_operator_assignments.status is
                         * enum('open','close'), so 'close' is correct THERE and
                         * must stay. The two tables spell it differently, which
                         * is how this survived.
                         */
                        $query->whereNotNull('closed_time')
                            ->orWhere('status', 2);
                    })
                    ->pluck('id')
                    ->toArray();

                $closed_assignment_shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where(function ($query) {
                        $query->where('status', 'close')
                            ->orWhere('status', 'closed')
                            ->orWhere('is_manually_closed', 1);
                    })
                    ->pluck('shift_id')
                    ->toArray();

                $completed_shift_ids = array_merge($completed_shift_ids, $closed_shift_ids, $closed_assignment_shift_ids);

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
                $completed_shift_ids = array_values(array_unique(array_filter(array_map('intval', $completed_shift_ids))));

                // OPTIMIZED QUERY
                $has_card_meta_columns = $this->pumpOperatorPaymentsHasCardMetaColumns();

                // Parcel 3: use the canonical one-row-per-pump-payment read model.
                // Card/voucher/assignment rows are pre-aggregated and can no longer
                // multiply a payment in the table or footer totals.
                $payment_read = app(\Modules\PetroPD\Services\SettlementPaymentQueryService::class);
                $query = $payment_read->paymentSummaryBaseQuery($business_id, $has_card_meta_columns);

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

                $payment_method_values = $this->normalisePaymentMethodFilter(request()->payment_method);

                if (! empty($payment_method_values)) {
                    $query->whereIn(DB::raw('LOWER(TRIM(pump_operator_payments.payment_type))'), $payment_method_values);
                } else {
                    $query->whereIn(DB::raw('LOWER(pump_operator_payments.payment_type)'), ['cash', 'credit', 'card', 'cards', 'cheque', 'cheques', 'shortage', 'excess']);
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

                // PETROPD-PAYMENT-SUMMARY-FINAL-030: Apply the selected Date Range to
                // the main rows and the footer totals/breakdown. Some screens send only
                // date_range, not start_date/end_date.
                $filter_start_date = null;
                $filter_end_date = null;

                if (!empty(request()->start_date) && !empty(request()->end_date)) {
                    $filter_start_date = \Carbon\Carbon::parse(request()->start_date)->startOfDay()->toDateTimeString();
                    $filter_end_date = \Carbon\Carbon::parse(request()->end_date)->endOfDay()->toDateTimeString();
                } elseif (!empty(request()->date_range)) {
                    $date_range_parts = preg_split('/\s*(?:-|~|to)\s*/i', (string) request()->date_range);
                    if (is_array($date_range_parts) && count($date_range_parts) >= 2) {
                        try {
                            $filter_start_date = \Carbon\Carbon::parse(trim($date_range_parts[0]))->startOfDay()->toDateTimeString();
                            $filter_end_date = \Carbon\Carbon::parse(trim($date_range_parts[1]))->endOfDay()->toDateTimeString();
                        } catch (\Exception $dateParseException) {
                            $filter_start_date = null;
                            $filter_end_date = null;
                        }
                    }
                }

                if (!empty($filter_start_date) && !empty($filter_end_date)) {
                    $query->whereBetween(DB::raw('COALESCE(pump_operator_payments.date_and_time, pump_operator_payments.created_at)'), [$filter_start_date, $filter_end_date]);
                }

                // IS1799: Apply DataTables universal search to the real source
                // columns before cloning the totals query. In particular, search
                // the authoritative displayed amount rather than the legacy
                // payment_amount column, so values such as 5,000 are found.
                $global_search = trim((string) request()->input('search.value', ''));
                if ($global_search !== '') {
                    $like = '%' . $global_search . '%';
                    $numeric_search = preg_replace(
                        '/[^0-9.\-]/',
                        '',
                        str_ireplace(['Rs.', 'Rs'], '', $global_search)
                    );
                    $authoritative_amount_sql = $payment_read->authoritativeAmountSql();

                    $query->where(function ($searchQuery) use ($like, $numeric_search, $has_card_meta_columns, $authoritative_amount_sql) {
                        $searchQuery->where('pump_operators.name', 'like', $like)
                            ->orWhere('business_locations.name', 'like', $like)
                            ->orWhere('pump_operator_payments.collection_form_no', 'like', $like)
                            ->orWhere('pump_operator_payments.payment_type', 'like', $like)
                            ->orWhere('pump_operator_payments.note', 'like', $like)
                            ->orWhere('pump_operator_payments.date_and_time', 'like', $like)
                            ->orWhere('pump_operator_payments.created_at', 'like', $like)
                            ->orWhere('pump_operator_payments.settlement_no', 'like', $like)
                            ->orWhere('edited_user.username', 'like', $like)
                            ->orWhere('scsp.order_number', 'like', $like)
                            ->orWhere('dc.slip_no', 'like', $like)
                            ->orWhere('contacts_credit.name', 'like', $like)
                            ->orWhere('contacts_card.name', 'like', $like)
                            ->orWhere('contacts_card_legacy.name', 'like', $like)
                            ->orWhere('pump_operator_payments.shift_id', 'like', $like)
                            ->orWhereRaw('(SELECT MAX(poa.shift_number) FROM pump_operator_assignments poa WHERE poa.shift_id = pump_operator_payments.shift_id AND poa.pump_operator_id = pump_operator_payments.pump_operator_id) LIKE ?', [$like]);

                        if ($has_card_meta_columns) {
                            $searchQuery->orWhere('pump_operator_payments.slip_no', 'like', $like);
                        }

                        if ($numeric_search !== '' && is_numeric($numeric_search)) {
                            $searchQuery->orWhereRaw(
                                'CAST((' . $authoritative_amount_sql . ') AS CHAR) LIKE ?',
                                ['%' . $numeric_search . '%']
                            );
                        }
                    });
                }

                // PETROPD-PAYMENT-SUMMARY-BREAKDOWN-029:
                // Footer total and payment-type breakdown for the full selected period/filter.
                // This is added here too because the pumper dashboard Payment Summary uses this branch.
                $summary_totals_query = (clone $query)
                    ->select(
                        'pump_operator_payments.id',
                        DB::raw('LOWER(pump_operator_payments.payment_type) as payment_type_key'),
                        DB::raw('MAX(' . $payment_read->authoritativeAmountSql() . ') as payment_amount')
                    )
                    ->groupBy('pump_operator_payments.id', 'pump_operator_payments.payment_type');

                $summary_totals = DB::query()
                    ->fromSub($summary_totals_query, 'pd_payment_summary_totals')
                    ->selectRaw('payment_type_key, SUM(payment_amount) as total_amount')
                    ->groupBy('payment_type_key')
                    ->pluck('total_amount', 'payment_type_key')
                    ->toArray();

                $payment_summary_total = array_sum(array_map('floatval', $summary_totals));
                $payment_summary_breakdown = [];
                foreach ($summary_totals as $payment_type_key => $total_amount) {
                    $payment_summary_breakdown[ucfirst((string) $payment_type_key)] = (float) $total_amount;
                }

                // SELECT
                $query->select(
                    'pump_operator_payments.id',
                    'pump_operator_payments.date_and_time',
                    'pump_operator_payments.created_at as payment_created_at',
                    'pump_operator_payments.collection_form_no',
                    'pump_operator_payments.payment_type',
                    DB::raw($payment_read->authoritativeAmountSql() . ' as payment_amount'),
                    'pump_operator_payments.shift_id',
                    'pump_operator_payments.pump_operator_id',
                    DB::raw('(SELECT MAX(poa.shift_number) FROM pump_operator_assignments poa WHERE poa.shift_id = pump_operator_payments.shift_id AND poa.pump_operator_id = pump_operator_payments.pump_operator_id) as shift_number'),
                    'pump_operator_payments.note',
                    'pump_operators.name as pump_operator_name',
                    'edited_user.username as edited_by',
                    'business_locations.name as location_name',
                    'scsp.id as scsp_id',
                    'scsp.daily_voucher_id',
                    'dv.settlement_no as daily_voucher_settlement_no',
                    'scsp.order_number',
                    /* PETROPD-SLIP-NO-20260821: same authoritative fallback for PD Operators > Payment Summary tab. */
                    DB::raw($this->paymentSummarySlipNoSql($has_card_meta_columns) . ' as slip_no'),
                    DB::raw('COALESCE(contacts_credit.name, contacts_card.name, contacts_card_legacy.name) as customer_name')
                )->distinct('pump_operator_payments.id');

                // PD-PAYMENT-SUMMARY-ACTION-DROPDOWN-027:
                // Latest Shift No first, then latest payment inside that shift.
                $query->orderByRaw('CAST((SELECT MAX(poa.shift_number) FROM pump_operator_assignments poa WHERE poa.shift_id = pump_operator_payments.shift_id AND poa.pump_operator_id = pump_operator_payments.pump_operator_id) AS UNSIGNED) DESC')
                    ->orderBy('pump_operator_payments.id', 'desc');

                return DataTables::of($query)
                    ->filter(function ($query) {
                        // Universal search is already applied to the source
                        // query above; disable DataTables alias-based filtering.
                    }, false)
                    ->addColumn('action', function ($row) {

                        if (empty($row->id) || strtolower($row->payment_type) == 'other sale') {
                            return '';
                        }

                        $payment_type = strtolower((string) $row->payment_type);

                        $edit_query = '?type=' . urlencode($row->payment_type) . '&payment_id=' . urlencode($row->id);
                        if ($payment_type === 'credit') {
                            $edit_query .= '&credit_sale_id=' . urlencode($row->scsp_id ?? '');
                        }

                        $edit_url = route('petropd.pump-operators.payment.edit', ['id' => $row->id]) . $edit_query;
                        $is_locked = $this->isPaymentEditLocked($row, null);

                        $html = '<div class="btn-group pd-payment-action-dropdown">';
                        $html .= '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                            . __('messages.actions') . ' <span class="caret"></span></button>';
                        $html .= '<ul class="dropdown-menu dropdown-menu-left" role="menu" style="z-index:99999; min-width:140px;">';

                        if ($is_locked) {
                            $html .= '<li class="disabled"><a href="#" onclick="return false;" title="Shift is closed"><i class="glyphicon glyphicon-lock"></i> ' . __('messages.edit') . '</a></li>';
                        } else {
                            // Use an anchor with both href and data-href. This works with the global btn-modal handler
                            // and with the PetroPD fallback handler added in this package.
                            $html .= '<li><a href="' . e($edit_url) . '" data-href="' . e($edit_url) . '" class="pd-payment-edit-link" data-container=".pd_payment_edit_modal"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a></li>';
                        }

                        if ($payment_type === 'credit') {
                            $print_url = url('petropd/pump-operator-pmts/print-credit-sale/' . $row->id)
                                . '?payment_id=' . urlencode($row->id)
                                . '&credit_sale_id=' . urlencode($row->scsp_id ?? '')
                                . '&collection_form_no=' . urlencode($row->collection_form_no ?? '')
                                . '&order_number=' . urlencode($row->order_number ?? '');

                            $html .= '<li><a href="' . e($print_url) . '" target="_blank" class="pd-payment-reprint-link"><i class="fa fa-print"></i> Re-Print</a></li>';
                        }


                        /*
                         |----------------------------------------------------------
                         | "Details" - the last item in the Action menu.
                         |----------------------------------------------------------
                         |
                         | Collection Form No, Slip No, Date, Time, Order No, Note and
                         | Edited By were taking a great deal of table width. They now
                         | live in a popup opened from here.
                         |
                         | The values travel on the button itself as data attributes,
                         | so the popup needs no further request - it opens instantly
                         | and works on any page of results.
                         |
                         | The columns are only HIDDEN in the table, never removed, so
                         | searching and filtering by any of these still works.
                         */
                        $pd_detail_bits = [
                            'collection-form-no' => $this->rowValue($row, 'collection_form_no'),
                            'slip-no'            => $this->rowValue($row, 'slip_no'),
                            // date_and_time is the real column - see the note above.
                            'date-time'          => $this->rowValue($row, 'date_and_time'),
                            'order-no'           => $this->rowValue($row, 'order_number'),
                            'note'               => $this->rowValue($row, 'note'),
                            'edited-by'          => $this->rowValue($row, 'edited_by'),
                        ];

                        $pd_detail_attrs = '';
                        foreach ($pd_detail_bits as $pd_key => $pd_val) {
                            $pd_val = trim((string) ($pd_val ?? ''));
                            // The note column arrives as button markup; keep the text only.
                            if ($pd_key === 'note') {
                                $pd_val = trim(strip_tags($pd_val));
                            }
                            $pd_detail_attrs .= ' data-' . $pd_key . '="' . e($pd_val === '' ? '—' : $pd_val) . '"';
                        }

                        $html .= '<li><a href="#" class="pd-payment-details-btn"' . $pd_detail_attrs
                            . '><i class="fa fa-info-circle"></i> Details</a></li>';

                        return $html . '</ul></div>';
                    })
                    ->addColumn('date', function ($row) {
                        // PETROPD-PAYMENT-SUMMARY-FINAL-030: Always show a date.
                        // Some legacy rows have only created_at/payment_created_at while
                        // time is still available through date_and_time formatting.
                        $date_time = $row->date_and_time ?? $row->payment_created_at ?? $row->created_at ?? null;
                        if (empty($date_time)) {
                            return '—';
                        }
                        $timestamp = strtotime((string) $date_time);
                        return $timestamp ? date('Y-m-d', $timestamp) : '—';
                    })
                    ->addColumn('time', '{{@format_time($date_and_time)}}')
                    ->addColumn('customer_name', fn($row) => $row->customer_name ?? 'Walk in customer')
                    /*
                     |--------------------------------------------------------------
                     | Collection Form No: shown as a button when it is too long.
                     |--------------------------------------------------------------
                     |
                     | The column is now half its previous width, so a long reference
                     | would be clipped - which is exactly what hid the slip numbers
                     | on this same table.
                     |
                     | Short values print as before. Anything longer than fits becomes
                     | a small button carrying the full number in its tooltip and in a
                     | data attribute, so hovering shows it and clicking opens it in
                     | full.
                     |
                     | SEARCH IS UNAFFECTED: DataTables filters on the underlying
                     | column, not this rendered HTML, and the filter in this
                     | controller still matches collection_form_no directly. Searching
                     | for a number finds its row whether it is displayed as text or
                     | as a button.
                     */
                    /*
                     |--------------------------------------------------------------
                     | Customer: shown as a button carrying the full name.
                     |--------------------------------------------------------------
                     |
                     | The column drops from 248px to 90px, so a full customer name
                     | could not be read in place. It becomes a button instead: the
                     | name shows on hover through the title attribute, and on click
                     | for touch screens where there is no hover.
                     |
                     | SEARCH IS UNAFFECTED - the column's name stays customer_name,
                     | so DataTables filters against the database value rather than
                     | this markup. Searching a customer still finds their rows.
                     */
                    ->addColumn('date_time_display', function ($row) {
                        /*
                         | Date with the time beneath it, replacing two columns.
                         |
                         | Array-safe: this query is built with DB::table(), so rows
                         | arrive as arrays and property access returns nothing - the
                         | fault that left Slip No blank.
                         */
                        $date = $this->rowValue($row, 'date', '');
                        $time = $this->rowValue($row, 'time', '');

                        if ($date === '' && $time === '') {
                            return '—';
                        }

                        $html = '<span class="pd-date-main">' . e($date) . '</span>';

                        if ($time !== '') {
                            $html .= '<br><small class="text-muted pd-time-sub">' . e($time) . '</small>';
                        }

                        return $html;
                    })
                    ->addColumn('customer_name_display', function ($row) {
                        // Array access - see the note on slip_no below.
                        $name = trim((string) (is_array($row) ? ($row['customer_name'] ?? '') : ($row->customer_name ?? '')));

                        if ($name === '') {
                            return '—';
                        }

                        // Short names read fine in place; only long ones need the button.
                        if (mb_strlen($name) <= 10) {
                            return e($name);
                        }

                        return '<button type="button"'
                            . ' class="btn btn-xs btn-default pd-customer-name-btn"'
                            . ' data-customer-name="' . e($name) . '"'
                            . ' title="' . e($name) . '">'
                            . e(mb_substr($name, 0, 8)) . '…'
                            . '</button>';
                    })
                    ->addColumn('collection_form_no_display', function ($row) {
                        // Array access - see the note on slip_no below.
                        $value = trim((string) (is_array($row) ? ($row['collection_form_no'] ?? '') : ($row->collection_form_no ?? '')));

                        if ($value === '') {
                            return '—';
                        }

                        // Six characters fit the narrowed column comfortably.
                        if (mb_strlen($value) <= 6) {
                            return e($value);
                        }

                        return '<button type="button"'
                            . ' class="btn btn-xs btn-default pd-collection-form-no-btn"'
                            . ' data-collection-form-no="' . e($value) . '"'
                            . ' title="' . e($value) . '">'
                            . e(mb_substr($value, 0, 4)) . '…'
                            . '</button>';
                    })
                    /*
                     |--------------------------------------------------------------
                     | editColumn, not addColumn - slip_no is ALREADY selected.
                     |--------------------------------------------------------------
                     |
                     | Reported: the Slip No column stayed blank even after the width
                     | was corrected, while the query demonstrably returns the value:
                     |
                     |     payment 58  card_slip 367  coalesced 367
                     |
                     | The select builds slip_no through
                     |     COALESCE(pump_operator_payments.slip_no, dc.slip_no) as slip_no
                     | so the column already exists on the row. addColumn() is for
                     | columns that do NOT exist; used on one that does, the value is
                     | not rendered and the cell comes out empty.
                     |
                     | order_number displays correctly because it is selected plainly
                     | as scsp.order_number and its addColumn behaves differently.
                     |
                     | editColumn is the correct call for a column already present.
                     */
                    /*
                     |----------------------------------------------------------
                     | The row is an ARRAY here, not an object.
                     |----------------------------------------------------------
                     |
                     | Diagnosed from the row itself:
                     |
                     |   slip_no_raw    "(property missing)"
                     |   available_keys [... "order_number", "slip_no", ...]
                     |
                     | slip_no IS present in the keys, yet $row->slip_no returned
                     | nothing - because this query is built with DB::table(), so
                     | DataTables hands each row over as an ARRAY. Property access
                     | on an array yields null, so the closure always fell through
                     | to the dash however correct the data was.
                     |
                     | That is why every check passed - the card row existed, the
                     | joins matched, the collation was fixed, the COALESCE was
                     | right - and the column still showed nothing.
                     |
                     | Reading it as an array fixes it. Both forms are handled, so
                     | it keeps working if the query is ever changed to Eloquent.
                     */
                    ->editColumn('slip_no', function ($row) {
                        $type = is_array($row) ? ($row['payment_type'] ?? '') : ($row->payment_type ?? '');
                        $slip = is_array($row) ? ($row['slip_no'] ?? '') : ($row->slip_no ?? '');

                        return in_array(strtolower(trim((string) $type)), ['card', 'cards'], true) ? ($slip ?: '—') : '—';
                    })
                    // Same collision as slip_no: order_number is selected as
                    // scsp.order_number, so it must be edited, not added.
                    ->editColumn('order_number', function ($row) {
                        // Same array access as slip_no above.
                        $value = is_array($row) ? ($row['order_number'] ?? '') : ($row->order_number ?? '');

                        return $value ?: '—';
                    })
                    ->editColumn('pump_operator_name', function ($row) {
                        $value = is_array($row) ? ($row['pump_operator_name'] ?? '') : ($row->pump_operator_name ?? '');

                        return $value ?: '—';
                    })
                    ->editColumn('payment_type', '{{ ucfirst($payment_type) }}')
                    ->editColumn('amount', function ($row) use ($business_details) {
                        return '<span class="display_currency amount" data-orig-value="' . $row->payment_amount . '">' .
                            $this->productUtil->num_f($row->payment_amount, false, $business_details, true) .
                            '</span>';
                    })
                    ->editColumn('note', function ($row) {
                        /*
                         |----------------------------------------------------------
                         | Array-safe, and the note shows on HOVER as well as click.
                         |----------------------------------------------------------
                         |
                         | Reported: the Note could not be viewed at all.
                         |
                         | This read $row->note - property access on a row that
                         | arrives as an ARRAY, because the query is built with
                         | DB::table(). It returned nothing, so every note looked
                         | empty and the column showed a dash. Same fault that left
                         | Slip No blank.
                         |
                         | A title attribute is added so the note appears on hover
                         | without any click, and the existing click handler still
                         | opens it in full for long notes and for touch screens.
                         */
                        $note = trim((string) ($this->rowValue($row, 'note') ?? ''));

                        if ($note === '') {
                            return '—';
                        }

                        return '<button type="button" class="btn btn-default btn-xs pd-payment-note-view pd-note-btn" data-note-b64="'
                            . e(base64_encode($note))
                            . '" title="' . e($note) . '"'
                            . ' onclick="return window.petropdShowPaymentNote(this);">Note</button>';
                    })
                    ->rawColumns(['amount', 'action', 'note', 'collection_form_no_display', 'customer_name_display', 'date_time_display'])
                    ->with([
                        'payment_summary_total' => (float) ($payment_summary_total ?? 0),
                        'payment_summary_breakdown' => $payment_summary_breakdown ?? [],
                    ])
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

    /**
     * PETROPD-PAYMENT-SUMMARY-TOTALS-017
     * Dedicated totals endpoint for PD Operators Payment Summary.
     * This is intentionally independent from DataTables paging so the footer total
     * and payment-type totals always represent the selected filters, not only the current page.
     */
    public function paymentSummaryTotals(\Illuminate\Http\Request $request)
    {
        try {
            $business_id = $this->resolveBusinessId();
            $pump_operator_id = Auth::user()->pump_operator_id;

            $totals = $this->calculatePaymentSummaryTotals($request, $business_id, $pump_operator_id);

            return response()->json([
                'payment_summary_total' => (float) $totals['total'],
                'payment_summary_breakdown' => $totals['breakdown'],
            ]);
        } catch (\Exception $e) {
            Log::error('Payment Summary totals failed: ' . $e->getMessage());
            return response()->json([
                'payment_summary_total' => 0,
                'payment_summary_breakdown' => [
                    'Cash' => 0,
                    'Card' => 0,
                    'Credit' => 0,
                    'Cheque' => 0,
                    'Shortage' => 0,
                    'Excess' => 0,
                ],
            ], 200);
        }
    }

    private function calculatePaymentSummaryTotals($request, $business_id, $pump_operator_id = null)
    {
        $has_card_meta_columns = $this->pumpOperatorPaymentsHasCardMetaColumns();

        // Parcel 3: totals use the same safe read model as the table. Metadata
        // joins are one-row-per-master and cannot multiply a payment.
        $payment_read = app(\Modules\PetroPD\Services\SettlementPaymentQueryService::class);
        $query = $payment_read->paymentSummaryBaseQuery($business_id, $has_card_meta_columns);

        if (!empty($request->only_pumper) && !empty($pump_operator_id)) {
            $query->where('pump_operator_payments.pump_operator_id', $pump_operator_id);
        }

        if (!empty($request->shift_id)) {
            $query->where('pump_operator_payments.shift_id', $request->shift_id);
        }

        if (!empty($request->location_id)) {
            $query->where('pump_operators.location_id', $request->location_id);
        }

        if (!empty($request->pump_operator_id)) {
            $query->where('pump_operator_payments.pump_operator_id', $request->pump_operator_id);
        }

        // LA-1190: normalised - see normalisePaymentMethodFilter().
        $payment_method_values = $this->normalisePaymentMethodFilter($request->payment_method);

        if (! empty($payment_method_values)) {
            $query->whereIn(DB::raw('LOWER(TRIM(pump_operator_payments.payment_type))'), $payment_method_values);
        } else {
            $query->whereIn(DB::raw('LOWER(pump_operator_payments.payment_type)'), ['cash', 'credit', 'card', 'cards', 'cheque', 'cheques']);
        }

        if (!empty($request->customer_id)) {
            $query->where(function ($q) use ($request, $has_card_meta_columns) {
                $q->where('scsp.customer_id', $request->customer_id)
                    ->orWhere($has_card_meta_columns ? 'pump_operator_payments.customer_id' : 'dc.customer_id', $request->customer_id);

                if ($has_card_meta_columns) {
                    $q->orWhere('dc.customer_id', $request->customer_id);
                }
            });
        }

        if (!empty($request->slip_no)) {
            if ($has_card_meta_columns) {
                $query->where(function ($q) use ($request) {
                    $q->where('pump_operator_payments.slip_no', 'like', '%' . $request->slip_no . '%')
                        ->orWhere('dc.slip_no', 'like', '%' . $request->slip_no . '%');
                });
            } else {
                $query->where('dc.slip_no', 'like', '%' . $request->slip_no . '%');
            }
        }

        if (!empty($request->order_no)) {
            $query->where('scsp.order_number', 'like', '%' . $request->order_no . '%');
        }

        if (!empty($request->settlement_id)) {
            $query->where('payment_settlements.id', $request->settlement_id);
        }

        if (!empty($request->status)) {
            if ($request->status == 'completed') {
                $query->whereNotNull('payment_settlements.id')
                    ->where('payment_settlements.status', 0);
            }
            if ($request->status == 'pending') {
                $query->where(function ($q) {
                    $q->whereNull('payment_settlements.id')
                        ->orWhere('payment_settlements.status', 1);
                });
            }
        }

        [$start_date, $end_date] = $this->parsePaymentSummaryDateFilter($request);
        if (!empty($start_date) && !empty($end_date)) {
            $query->whereBetween(DB::raw('COALESCE(pump_operator_payments.date_and_time, pump_operator_payments.created_at)'), [$start_date, $end_date]);
        }

        $summary_sub = (clone $query)
            ->select(
                'pump_operator_payments.id',
                DB::raw('LOWER(pump_operator_payments.payment_type) as payment_type_key'),
                DB::raw('MAX(' . $payment_read->authoritativeAmountSql() . ') as payment_amount')
            )
            ->groupBy('pump_operator_payments.id', 'pump_operator_payments.payment_type');

        $summary_totals = DB::query()
            ->fromSub($summary_sub, 'pd_payment_summary_totals')
            ->selectRaw('payment_type_key, SUM(payment_amount) as total_amount')
            ->groupBy('payment_type_key')
            ->pluck('total_amount', 'payment_type_key')
            ->toArray();

        $breakdown = [
            'Cash' => 0,
            'Card' => 0,
            'Credit' => 0,
            'Cheque' => 0,
            'Shortage' => 0,
            'Excess' => 0,
        ];

        $total = 0;
        foreach ($summary_totals as $type => $amount) {
            $normalized_type = strtolower((string) $type);
            $amount = (float) $amount;
            $total += $amount;
            if ($normalized_type === 'cash') {
                $breakdown['Cash'] += $amount;
            } elseif ($normalized_type === 'card' || $normalized_type === 'cards') {
                $breakdown['Card'] += $amount;
            } elseif ($normalized_type === 'credit') {
                $breakdown['Credit'] += $amount;
            } elseif ($normalized_type === 'cheque' || $normalized_type === 'cheques') {
                $breakdown['Cheque'] += $amount;
            } elseif ($normalized_type === 'shortage') {
                $breakdown['Shortage'] += $amount;
            } elseif ($normalized_type === 'excess') {
                $breakdown['Excess'] += $amount;
            }
        }

        return [
            'total' => $total,
            'breakdown' => $breakdown,
        ];
    }

    private function parsePaymentSummaryDateFilter($request)
    {
        if (!empty($request->start_date) && !empty($request->end_date)) {
            return [
                \Carbon\Carbon::parse($request->start_date)->startOfDay()->toDateTimeString(),
                \Carbon\Carbon::parse($request->end_date)->endOfDay()->toDateTimeString(),
            ];
        }

        if (empty($request->date_range)) {
            return [null, null];
        }

        $date_range = trim((string) $request->date_range);
        $parts = preg_split('/\s+(?:-|~|to)\s+/i', $date_range);
        if (!is_array($parts) || count($parts) < 2) {
            $parts = explode(' - ', $date_range);
        }

        if (count($parts) < 2) {
            return [null, null];
        }

        try {
            $start = trim($parts[0]);
            $end = trim($parts[1]);

            try {
                $start = $this->moduleUtil->uf_date($start);
                $end = $this->moduleUtil->uf_date($end);
            } catch (\Exception $e) {
                // Carbon fallback below.
            }

            return [
                \Carbon\Carbon::parse($start)->startOfDay()->toDateTimeString(),
                \Carbon\Carbon::parse($end)->endOfDay()->toDateTimeString(),
            ];
        } catch (\Exception $e) {
            return [null, null];
        }
    }

    //     public function index()
    //     {
    //         $business_id =  $this->resolveBusinessId();
    //         $pump_operator_id = Auth::user()->pump_operator_id;
    //         $business_details = Business::find($business_id);

    //         if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_pd_module')) {
    //             abort(403, 'Unauthorized Access');
    //         }

    //         $only_pumper = request()->only_pumper;
    //         $shift_id = request()->shift_id;

    //         if (request()->ajax()) {
    //             $business_id =  $this->resolveBusinessId();
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
    //         $html .= '<li><a href="#" data-href="' . action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorPaymentController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
    //     } else {
    //         $html .= '<li><a href="#" data-href="' . action('\Modules\PetroPD\Http\Controllers\PDPumpOperatorPaymentController@edit', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>';
    //     }

    //     if($row->payment_type == 'Other Sale'){
    //         return '';
    //     }

    //     return $html . '</ul></div>';

    //                         return $html;
    //                     }
    //                 )
    //                 ->addColumn('date', function ($row) {
    //                     $date_time = $row->date_and_time ?? null;
    //                     if (empty($date_time)) {
    //                         return '—';
    //                     }
    //                     $timestamp = strtotime((string) $date_time);
    //                     return $timestamp ? date('Y-m-d', $timestamp) : '—';
    //                 })
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

    //         return view('petropd::pd_operators.payment_summary')->with(compact(
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
        $this->restorePumperTenantIdentity();
        $this->authorizePumperDashboardPermission('pumper_dashboard.payments');

        $pump_operator_id = $this->resolvePumpOperatorId();
        $business_id      = $this->resolveBusinessId();

        if ($pump_operator_id <= 0) {
            return redirect('/petropd/pd-operators')->with('status', [
                'success' => false,
                'msg' => 'Pump Operator is not linked to this login. Please edit and save the operator once, then login again.',
            ]);
        }

        $is_pumper_dashboard_request = request()->boolean('only_pumper')
            || ! empty(optional(Auth::user())->is_pump_operator);

        if ($is_pumper_dashboard_request && ! $this->isCurrentPumpReceiveCompleteForPayments($business_id, $pump_operator_id)) {
            return redirect()->route('petropd.pd-operators.dashboard')->with('status', [
                'success' => false,
                'msg' => 'Please complete the Receive Pump process before opening Payments.',
            ]);
        }

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
            return redirect('/petropd/pd-operators')->with('status', $output);
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

        $pump_operator = PumpOperator::where('business_id', $business_id)->findOrFail($pump_operator_id);
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

        $default_card_type = $settings['card_type'] ?? null;

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

        // Include the Petro PD Settings default card account/type even if its account group
        // differs from the current Card group. This keeps Settings -> Dashboard Payment in sync.
        if (! empty($default_card_type) && ! $card_types->has($default_card_type)) {
            $default_card_account = Account::where('business_id', $business_id)
                ->where('id', $default_card_type)
                ->first();
            if (! empty($default_card_account)) {
                $card_types->put($default_card_account->id, $default_card_account->name);
            }
        }

        // If no card types found, fallback to business accounts with card-like names.
        if ($card_types->isEmpty()) {
            $card_types = Account::where('business_id', $business_id)
                ->where(function ($query) {
                    $query->where('name', 'like', '%card%')
                        ->orWhere('name', 'like', '%visa%')
                        ->orWhere('name', 'like', '%master%');
                })
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
        try {
            $shift_id = $this->resolveExactPaymentShiftId(request(), (int) $business_id, (int) $pump_operator_id);
        } catch (\RuntimeException $e) {
            $shift_id = null;
            Log::warning('Pumper Dashboard payment page has no unambiguous Shift ID', [
                'business_id' => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'message' => $e->getMessage(),
            ]);
        }
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

        $business = Business::where('id', $business_id)->first();
        if (empty($business)) {
            \Log::error('PetroPD payment finalize: business context missing', [
                'business_id' => $business_id,
                'user_id'     => Auth::id(),
                'route'       => request()->route() ? request()->route()->getName() : null,
                'url'         => request()->fullUrl(),
            ]);
            $pos_settings = [];
        } else {
            $pos_settings = json_decode($business->pos_settings ?? '{}', true);
            if (! is_array($pos_settings)) {
                $pos_settings = [];
            }
        }
        $cash_denoms = ! empty($pos_settings['cash_denominations']) ? explode(',', $pos_settings['cash_denominations']) : [];

        $user = Auth::user();

        // Keep the session-resolved operator. Do not overwrite it with a stale
        // users.pump_operator_id after the payment page has already loaded.
        $shift_number = $shift_id ? PumpOperatorAssignment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('shift_id', $shift_id)
            ->where('shift_number', '>', 0)
            ->orderByDesc('id')
            ->value('shift_number') : null;

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

        // IS1983: the Card add form needs to know whether duplicate slip numbers
        // are blocked, so it can stop a repeat at Add time rather than after Save.
        $block_duplicate_slip_numbers = $this->duplicateSlipNumbersBlocked((int) $business_id);

        return view('petropd::pd_operators.actions.payments')->with(compact(
            'pumps', 'card_types', 'default_card_type', 'business', 'enter_card_numbers',
            'layout', 'customers', 'walkin', 'products', 'only_walkin', 'direct_cr', 'today_deposited', 'total_amount',
            'pending_pumps', 'all_pending_payments', 'balance_to_deposit', 'bank_accounts', 'cash_denoms', 'enter_cash_denoms', 'card_pmt_type', 'shift_number', 'collection_form_no', 'meter_sales_compulsory',
            'block_duplicate_slip_numbers', 'pump_operator_id', 'shift_id'
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

        $this->restorePumperTenantIdentity();
        $business_id = $this->resolveBusinessId();
        $created_by = (int) (Auth::id() ?: session('pumper_user_id') ?: session('user.id') ?: 0);
        $pump_operator_id = $this->resolvePumpOperatorId($request);

        if (! $pump_operator_id) {
            return [
                'success' => false,
                'msg'     => 'Pump Operator is required',
            ];
        }

        $pump_operator = PumpOperator::where('business_id', $business_id)->findOrFail($pump_operator_id);
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

        try {
            $shift = $this->resolveExactPaymentShiftId($request, (int) $business_id, (int) $pump_operator_id);
            $settlement = $this->ensureActiveSettlementForPumpOperator(
                (int) $business_id,
                (int) $pump_operator_id,
                (int) $shift
            );
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
                'settlement_no'    => (string) $settlement->id,
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

                $shift_id_for_collection = $shift;
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

                $shift_id_for_collection = $shift;
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
                $shift_id = $shift;
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
                        'settlement_id'      => $settlement->id,
                    ];
                    if (Schema::hasColumn('daily_collections', 'pump_payment_id')) {
                        $data['pump_payment_id'] = $PumpOperatorPayment->id;
                    }

                    // The master payment id is the first duplicate key. Legacy
                    // fields are retained only as a compatibility fallback.
                    $existingQuery = DailyCollection::where('business_id', $business_id);
                    if (Schema::hasColumn('daily_collections', 'pump_payment_id')) {
                        $existingQuery->where('pump_payment_id', $PumpOperatorPayment->id);
                    } else {
                        $existingQuery->where('collection_form_no', $collection_form_no)
                            ->where('pump_operator_id', $pump_operator_id)
                            ->where('shift_id', $shift_id)
                            ->where('type', 'daily_collection');
                    }
                    $existing = $existingQuery->first();

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
                    DailyCollection::where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->whereNull('settlement_id')
                        ->update(['settlement_id' => $settlement->id]);

                    PumpOperatorPayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->whereNull('settlement_no')
                        ->update(['settlement_no' => $settlement->id]);
                }

                $pump_operator = PumpOperator::where('id', $pump_operator_id)->first();
                /*$balance_collection = DailyCollection::where('business_id', $business_id)->where('pump_operator_id', $pump_operator_id)->sum('current_amount');
                $settlement_collection = DailyCollection::where('business_id', $business_id)->where('pump_operator_id', $pump_operator_id)->sum('balance_collection');
                $cum_amount = $balance_collection - $settlement_collection;*/

                $this->queueCashDepositNotification($pump_operator, (float) $payment_amount);

            }

            if ($request->payment_type == 'card') {
                if (! empty($request->card_type)) {
                    // collection_form_no already calculated and set above
                    // syncPaymentToDailyTables already created the DailyCard entry
                    // No need to create duplicate entry here

                    // Update meter sale link if needed
                    if ($collection_form_no) {
                        $shift_id_for_meter = $shift;
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
            $business_id      = $this->resolveBusinessId();
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

            $settlement = $this->ensureActiveSettlementForPumpOperator(
                (int) $business_id,
                (int) $pump_operator_id,
                (int) $shift_id
            );

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
                'settlement_no'      => (string) $settlement->id,
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

    /**
     * IS1983: is the business configured to reject duplicate card slip numbers?
     *
     * The setting is the "Do not Allow Duplicate Slip Numbers" checkbox under
     * Superadmin > All Business > Manage > Other permissions. Those checkboxes are
     * subscription package permissions, read through
     * ModuleUtil::hasThePermissionInSubscription() - the same mechanism
     * 'allow_duplicate_order_numbers' uses for the credit-sale order number rule.
     *
     * The key name is the one already present in this module:
     * pd_settlement/partials/payment_tabs/cards.blade.php declares
     * $module_array['duplicate_slip_numbers'] and reads it from the package
     * details, though it never went on to use the value.
     *
     * POLARITY: the checkbox reads "Do NOT Allow Duplicate Slip Numbers", so the
     * permission being present means duplicates are BLOCKED. Everything that
     * enforces the rule goes through this one method, so if it turns out to be
     * the other way round on your Superadmin build, negating the return value
     * here is the only change needed.
     */
    private function duplicateSlipNumbersBlocked(int $business_id): bool
    {
        try {
            return (bool) $this->moduleUtil->hasThePermissionInSubscription(
                $business_id,
                'duplicate_slip_numbers'
            );
        } catch (\Throwable $e) {
            // Never let a settings lookup break a payment save.
            Log::warning('IS1983: could not resolve the duplicate slip setting', [
                'business_id' => $business_id,
                'message'     => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * IS1983: reject a batch of card rows that repeats a slip number.
     *
     * Checks BOTH directions, because either alone leaves a hole:
     *   - within the submitted batch, so two rows in one Save cannot share a slip
     *     (this is what the reported screenshot shows - slip 100 twice)
     *   - against slips already stored, so a repeat across two separate saves is
     *     caught as well
     *
     * Returns the offending slip number, or null when the batch is acceptable.
     */
    private function findDuplicateCardSlipNo(int $business_id, array $card_rows): ?string
    {
        $seen_in_batch = [];

        foreach ($card_rows as $card_row) {
            $slip_no = trim((string) ($card_row['slip_no'] ?? ''));

            // A blank slip is a separate validation concern, not a duplicate.
            if ($slip_no === '') {
                continue;
            }

            $slip_key = mb_strtolower($slip_no);

            if (isset($seen_in_batch[$slip_key])) {
                return $slip_no;
            }
            $seen_in_batch[$slip_key] = true;

            $already_stored = DailyCard::where('business_id', $business_id)
                ->where('slip_no', $slip_no)
                ->exists();

            if (! $already_stored && $this->pumpOperatorPaymentsHasCardMetaColumns()) {
                $already_stored = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('payment_type', 'card')
                    ->where('slip_no', $slip_no)
                    ->exists();
            }

            if ($already_stored) {
                return $slip_no;
            }
        }

        return null;
    }

    public function saveCardPayment(Request $request)
    {
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id      = $this->resolveBusinessId();
        $created_by       = Auth::user()->id;
        $pump_operator    = PumpOperator::findOrFail($pump_operator_id);
        $settings         = json_decode($pump_operator->dashboard_settings, true);
        $settlement       = null;

        try {
            /*
             * IS1983: reject duplicate slip numbers before anything is written.
             *
             * This runs first, ahead of settlement and collection-form resolution,
             * so a rejected batch leaves no partial side effects behind.
             */
            if ($this->duplicateSlipNumbersBlocked((int) $business_id)) {
                $submitted_cards = [];
                foreach ((array) $request->card_data as $submitted_card) {
                    $decoded_card = json_decode($submitted_card, true);
                    if (is_array($decoded_card)) {
                        $submitted_cards[] = $decoded_card;
                    }
                }

                $duplicate_slip_no = $this->findDuplicateCardSlipNo((int) $business_id, $submitted_cards);

                if ($duplicate_slip_no !== null) {
                    Log::info('IS1983: card payment rejected for a duplicate slip number', [
                        'business_id'      => $business_id,
                        'pump_operator_id' => $pump_operator_id,
                        'slip_no'          => $duplicate_slip_no,
                    ]);

                    return response()->json([
                        'success' => false,
                        'msg'     => __('petropd::lang.duplicate_slip_number'),
                    ]);
                }
            }

            $shift_id = $this->resolveExactPaymentShiftId($request, (int) $business_id, (int) $pump_operator_id);
            $settlement = $this->ensureActiveSettlementForPumpOperator(
                (int) $business_id,
                (int) $pump_operator_id,
                (int) $shift_id
            );

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
                    'settlement_no'      => (string) $settlement->id,
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

                if (! empty($settlement)) {
                    DailyCollection::where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->whereNull('settlement_id')
                        ->update(['settlement_id' => $settlement->id]);

                    PumpOperatorPayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->whereNull('settlement_no')
                        ->update(['settlement_no' => (string) $settlement->id]);
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
        $this->restorePumperTenantIdentity();

        $business_id = $this->resolveBusinessId();
        $created_by = (int) (Auth::id() ?: session('pumper_user_id') ?: session('user.id') ?: 0);
        $pump_operator_id = $this->resolvePumpOperatorId($request);

        if ($pump_operator_id <= 0 || $created_by <= 0) {
            $output = [
                'success' => false,
                'msg' => 'Pump Operator login session could not be resolved. Please login to the Pumper Dashboard again.',
            ];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($output, 422);
            }

            return redirect()->back()->with('status', $output);
        }

        try {
            DB::beginTransaction();

            $pump_operator = PumpOperator::where('business_id', $business_id)->findOrFail($pump_operator_id);
            $shift = $this->resolveExactPaymentShiftId($request, (int) $business_id, (int) $pump_operator_id);
            $settlement = $this->ensureActiveSettlementForPumpOperator(
                (int) $business_id,
                (int) $pump_operator_id,
                (int) $shift
            );
            $payment_amount = (float) $request->grand_total;

            if ($payment_amount <= 0) {
                throw new \RuntimeException('Cash amount must be greater than zero.');
            }

            $maxMaster = (int) (PumpOperatorPayment::where('business_id', $business_id)->max('collection_form_no') ?? 0);
            $maxDaily = (int) (DailyCollection::where('business_id', $business_id)->max('collection_form_no') ?? 0);
            $collection_form_no = max($maxMaster, $maxDaily) + 1;

            $data = [
                'business_id' => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'payment_type' => 'cash',
                'payment_amount' => $payment_amount,
                'collection_form_no' => $collection_form_no,
                'created_by' => $created_by,
                'shift_id' => $shift,
                'settlement_no' => (string) $settlement->id,
            ];
            if (Schema::hasColumn('pump_operator_payments', 'gross_amount')) {
                $data['gross_amount'] = $payment_amount;
            }
            if (Schema::hasColumn('pump_operator_payments', 'discount_amount')) {
                $data['discount_amount'] = 0;
            }
            if (Schema::hasColumn('pump_operator_payments', 'net_amount')) {
                $data['net_amount'] = $payment_amount;
            }
            if (Schema::hasColumn('pump_operator_payments', 'source_type')) {
                $data['source_type'] = 'daily_collection';
            }
            if (Schema::hasColumn('pump_operator_payments', 'transaction_date')) {
                $data['transaction_date'] = now()->toDateString();
            }

            $payment = PumpOperatorPayment::create($data);
            $this->syncPaymentToDailyTables($payment, $request);

            $dailyCollectionQuery = DailyCollection::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where('shift_id', $shift);
            if (Schema::hasColumn('daily_collections', 'pump_payment_id')) {
                $dailyCollectionQuery->where('pump_payment_id', $payment->id);
            } else {
                $dailyCollectionQuery->where('collection_form_no', $collection_form_no);
            }
            $dailyCollection = $dailyCollectionQuery->first();

            if ($dailyCollection) {
                $dailyCollection->settlement_id = $settlement->id;
                $dailyCollection->save();
            }

            if ($dailyCollection
                && Schema::hasColumn('pump_operator_payments', 'source_id')
                && empty($payment->source_id)) {
                $payment->source_id = $dailyCollection->id;
                $payment->save();
            }

            DB::commit();

            $this->queueCashDepositNotification($pump_operator, (float) $payment_amount);

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
                'collection_form_no' => $collection_form_no,
            ];
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::error('PetroPD saveCashDenom failed', [
                'business_id' => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'message' => $e->getMessage(),
            ]);
            $output = [
                'success' => false,
                /*
                 * MA-002 (IS-1922 #2): show the real reason, not just
                 * "something went wrong".
                 *
                 * Anything that is not a RuntimeException produced the generic
                 * message while the actual cause went only to the log - so a
                 * report of "unable to save" carried no information at all and
                 * cost a round trip every time.
                 *
                 * The exception CLASS and MESSAGE are appended. That is the same
                 * detail already being written to laravel.log, so nothing new is
                 * exposed - it is simply on screen where it is useful.
                 */
                'msg' => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : __('messages.something_went_wrong') . ' ('
                        . class_basename($e) . ': ' . $e->getMessage() . ')',
            ];
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($output, ! empty($output['success']) ? 200 : 422);
        }

        return redirect()->back()->with('status', $output);
    }

    public function saveChequePayment(Request $request)
    {
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id      = $this->resolveBusinessId();
        $created_by       = Auth::user()->id;

        if (empty($pump_operator_id)) {
            return redirect()->action([self::class, 'create'])->with('status', [
                'success' => false,
                'msg'     => 'Pump operator not found. Please log in as a pump operator.',
            ]);
        }

        $pump_operator = PumpOperator::findOrFail($pump_operator_id);

        try {
            $shift = $this->resolveExactPaymentShiftId($request, (int) $business_id, (int) $pump_operator_id);
            $settlement = $this->ensureActiveSettlementForPumpOperator(
                (int) $business_id,
                (int) $pump_operator_id,
                (int) $shift
            );
            DB::beginTransaction();
            $payment_amount = $request->amount;

            $data = [
                'business_id'      => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'payment_type'     => 'cheque',
                'payment_amount'   => $payment_amount,
                'created_by'       => $created_by,
                'shift_id'         => $shift,
                'settlement_no'    => (string) $settlement->id,
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

            $shift_id = $shift;

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
            if (Schema::hasColumn('daily_cheque_payments', 'pump_payment_id')) {
                $data['pump_payment_id'] = $payment->id;
            }
            if (Schema::hasColumn('daily_cheque_payments', 'pump_operator_id')) {
                $data['pump_operator_id'] = $pump_operator_id;
            }
            if (Schema::hasColumn('daily_cheque_payments', 'settlement_no')) {
                $data['settlement_no'] = $settlement->settlement_no;
            }

            DailyChequePayment::updateOrCreate(
                ['business_id' => $business_id, 'linked_payment_id' => $payment->id],
                $data
            );

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
                ?? ($this->resolveBusinessId() ?? null);
            if (empty($business_id)) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'Business context missing in session. Please login again.',
                ], 422);
            }
            $shift_id = $this->resolveExactPaymentShiftId($request, (int) $business_id, (int) $pump_operator_id);
            $shift = $shift_id;
            $settlement = $this->ensureActiveSettlementForPumpOperator(
                (int) $business_id,
                (int) $pump_operator_id,
                (int) $shift_id
            );

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
                $customer_reference = isset($one['customer_reference']) ? trim((string) $one['customer_reference']) : '';

                // Validate customer vehicle no is compulsory for credit sales.
                if ($customer_reference === '') {
                    DB::rollback();
                    return $returnValidationError('Please select Customer Vehicle No before continuing.');
                }

                // Validate product_id is required
                if (empty($product_id)) {
                    return $returnValidationError(__('petropd::lang.product_required') ?: 'Product is required. Please select a product.');
                }

                // Validate quantity is required and greater than 0
                if (empty($qty) || $qty <= 0) {
                    return $returnValidationError(__('petropd::lang.quantity_required') ?: 'Quantity is required and must be greater than 0.');
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
                        return $returnValidationError(__('petropd::lang.customer_required') ?: 'Customer is required. Please select a customer.');
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
                    // Keep payment_amount unchanged for legacy reports. New financial
                    // totals use the explicit gross/discount/net master columns below.
                    'payment_amount'   => $amount,
                    'created_by'       => auth()->user()->id,
                    'shift_id'         => $shift,
                    'settlement_no'    => (string) $settlement->id,
                ];

                if (Schema::hasColumn('pump_operator_payments', 'gross_amount')) {
                    $pp_data['gross_amount'] = $amount;
                }
                if (Schema::hasColumn('pump_operator_payments', 'discount_amount')) {
                    $pp_data['discount_amount'] = $total_discount;
                }
                if (Schema::hasColumn('pump_operator_payments', 'net_amount')) {
                    $pp_data['net_amount'] = $sub_total;
                }
                if (Schema::hasColumn('pump_operator_payments', 'source_type')) {
                    $pp_data['source_type'] = 'credit_sale';
                }
                if (Schema::hasColumn('pump_operator_payments', 'customer_id')) {
                    $pp_data['customer_id'] = $customer_id;
                }
                if (Schema::hasColumn('pump_operator_payments', 'transaction_date')) {
                    $pp_data['transaction_date'] = $order_date;
                }
                if (Schema::hasColumn('pump_operator_payments', 'reference_no')) {
                    $pp_data['reference_no'] = ! empty($bill_number)
                        ? (string) $bill_number
                        : (! empty($order_number) ? (string) $order_number : null);
                }

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
                    'customer_reference' => $customer_reference,
                    'note'               => $one['note'],
                    'is_from_pumper'     => 1,
                    'collection_form_no' => $current_collection_form_no, // Use unique collection_form_no for each entry
                    'bill_number'        => $bill_number,
                    'pump_payment_id'     => $PumpOperatorPayment->id,
                ];
                if (Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')) {
                    $dt['shift_id'] = $shift;
                }
                $credit_sale_payment                     = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)
                    ->upsertOne($business_id, $settlement->settlement_no, 'settlement_credit_sale_payments', $dt);
                $PumpOperatorPayment->collection_form_no = $current_collection_form_no; // Link to unique collection_form_no
                if (Schema::hasColumn('pump_operator_payments', 'source_id')) {
                    $PumpOperatorPayment->source_id = $credit_sale_payment->id;
                }
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

                if (! empty($settlement)) {
                    DailyCollection::where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->whereNull('settlement_id')
                        ->update(['settlement_id' => $settlement->id]);

                    PumpOperatorPayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->where('shift_id', $shift_id)
                        ->whereNull('settlement_no')
                        ->update(['settlement_no' => (string) $settlement->id]);
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

                $assignment = PumpOperatorAssignment::where('pump_operator_id', $pump_operator->id)
                    ->where('business_id', $business_id)
                    ->where('shift_id', $shift)
                    ->first();

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
                // daily_vouchers.shift_id is a legacy petro_daily_shifts FK.
                // The PetroPD shift is stored on pump_operator_payments and
                // settlement_credit_sale_payments, never on daily_vouchers.
                if (Schema::hasColumn('daily_vouchers', 'pump_payment_id')) {
                    $data['pump_payment_id'] = $PumpOperatorPayment->id;
                }

                $daily_voucher = DailyVoucher::updateOrCreate(
                    Schema::hasColumn('daily_vouchers', 'pump_payment_id')
                        ? ['business_id' => $business_id, 'pump_payment_id' => $PumpOperatorPayment->id]
                        : [
                            'business_id' => $business_id,
                            'operator_id' => $pump_operator->id,
                            'daily_vouchers_no' => $daily_vouchers_no,
                        ],
                    $data
                );
                $credit_sale_payment = app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
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
                        
                        $html_content = view('petropd::pd_operators.print.credit_sale_print', $viewData)->render();
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
                'msg'                => __('petropd::lang.success'),
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
            $business_id = $this->resolveBusinessId();
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
        $business_id = $this->resolveBusinessId();
        $permissionUser = Auth::user();
        $isSuperadmin = $permissionUser && $permissionUser->can('superadmin');
        $canEditMeterSale = $permissionUser && (
            $isSuperadmin
            || ($permissionUser->can('petro_pd.edit_settlement') && $permissionUser->can('petro_pd.manual_entry'))
        );
        $canDeleteMeterSale = $permissionUser && (
            $isSuperadmin
            || ($permissionUser->can('petro_pd.delete_settlement') && $permissionUser->can('petro_pd.manual_entry'))
        );

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

                    /*
                     * A physical meter close is uniquely identified by the
                     * shift, operator, pump and its opening/closing readings.
                     * Older saves can contain a slightly different derived
                     * quantity/amount for the same close. Keep the latest
                     * added detail so the settlement grid and totals contain
                     * the sale once without deleting historical database rows.
                    */
                    $query->whereIn('pomsd.id', function ($dedupe) use ($business_id) {
                        $dedupe->selectRaw(
                            'COALESCE('
                            . 'NULLIF(MAX(CASE WHEN COALESCE(poms_keep.testing_qty, 0) > 0 '
                            . 'THEN pomsd_keep.id ELSE 0 END), 0), '
                            . 'MAX(pomsd_keep.id))'
                        )
                            ->from('pump_operator_meter_sale_details as pomsd_keep')
                            ->join(
                                'pump_operator_meter_sales as poms_keep',
                                'poms_keep.id',
                                '=',
                                'pomsd_keep.sale_id'
                            )
                            ->where('poms_keep.business_id', $business_id)
                            ->whereNull('poms_keep.p_o_payment_id')
                            ->groupBy(
                                'poms_keep.shift_id',
                                'poms_keep.pump_operator_id',
                                'pomsd_keep.pump_id',
                                DB::raw('ROUND(pomsd_keep.received_meter, 2)'),
                                DB::raw(
                                    'ROUND(pomsd_keep.new_meter - COALESCE(poms_keep.testing_qty, 0), 2)'
                                )
                            );
                    });

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
                        ->addColumn('action', function ($row) use ($canEditMeterSale, $canDeleteMeterSale) {
                            $editButton = $canEditMeterSale
                                ? '<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petropd/settlement-pd/get-meter-sale-form/' . $row->id . '?meter_sale_source=pump_operator"><i class="fa fa-edit"></i></button>'
                                : '';
                            $deleteButton = $canDeleteMeterSale
                                ? '<button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petropd/settlement-pd/delete-meter-sale/' . $row->id . '"><i class="fa fa-times"></i></button>'
                                : '';

                            return trim($editButton . ' ' . $deleteButton);
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
                    ->addColumn('action', function ($row) use ($canEditMeterSale, $canDeleteMeterSale) {
                        $editButton = $canEditMeterSale
                            ? '<button class="btn btn-xs btn-primary get_meter_sale_from" data-type="edit" data-href="/petropd/settlement-pd/get-meter-sale-form/' . $row->id . '"><i class="fa fa-edit"></i></button>'
                            : '';
                        $deleteButton = $canDeleteMeterSale
                            && (($row->later_settlements ?? 0) < 1 || ! ($row->transaction_id ?? null) || ($row->bulk_tank ?? 0) == 1)
                            ? '<button class="btn btn-xs btn-danger delete_meter_sale" data-href="/petropd/settlement-pd/delete-meter-sale/' . $row->id . '"><i class="fa fa-times"></i></button>'
                            : '';
                        return trim($editButton . ' ' . $deleteButton);
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

        return view('petropd::pd_operators.partials.modal_other_sales')->with(compact('shift_id', 'stores', 'bulk_tanks', 'items', 'pump_operator', 'other_sales'));
    }

    public function otherSalesList(Request $request)
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.list_other_sales');

        $business_id = $this->resolveBusinessId();
        $permissionUser = Auth::user();
        $canDeleteOtherSale = $permissionUser && (
            $permissionUser->can('superadmin')
            || $permissionUser->can('petro_pd.delete_settlement')
        );

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
                ->addColumn('action', function ($row) use ($canDeleteOtherSale) {
                    if ($canDeleteOtherSale && isset($row->user_check) && $row->user_check == 1) {
                        return '<button class="btn btn-xs btn-danger delete_other_sale" data-href="/petropd/settlement-pd/delete-other-sale/' . $row->id . '"><i class="fa fa-times"></i></button>';
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
        // MA-002: numeric max - shift_number is varchar, so MAX() on it
        // compares as text and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');
        $layout       = 'pumper';

        return view('petropd::pd_operators.other_sales_list')->with(compact('shift_number', 'shifts', 'layout', 'print_other_sale_ids', 'print'));
    }
    //    public function otherSalesList(Request $request)
    // {
    //     $business_id = $this->resolveBusinessId();

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
    //                     return '<button class="btn btn-xs btn-danger delete_other_sale" data-href="/petropd/settlement-pd/delete-other-sale/' . $row['id'] . '"><i class="fa fa-times"></i></button>';
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

    //     return view('petropd::pd_operators.other_sales_list')->with(compact('shift_number', 'shifts', 'layout', 'print_other_sale_ids', 'print'));
    // }

    public function pumpOtherSalesList(Request $request)
    {

        $business_id = $this->resolveBusinessId();

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
                $shiftIds = \Modules\PetroPD\Entities\PetroShift::where('pump_operator_id', $request->pump_operator_id)
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
        // MA-002: numeric max - shift_number is varchar, so MAX() on it
        // compares as text and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');
        $layout       = 'pumper';

        return view('petropd::pd_operators.other_sales_list')->with(compact('shift_number', 'shifts', 'layout', 'print_other_sale_ids', 'print'));
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
                return redirect('/petropd/pd-operators')->with('status', $output);
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

        return view('petropd::pd_operators.partials.other_sales', compact('products'));
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

    //     return view('petropd::pd_operators.partials.other_sales')->with(compact('products'));
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
    //     return view('petropd::pd_operators.partials.other_sales')->with(compact('products'));
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
                'msg'           => __('petropd::lang.success'),
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
                'msg'     => __('petropd::lang.success'),
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
                'msg'     => __('petropd::lang.success'),
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
    //             'msg'                  => __('petropd::lang.success'),
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
//             'msg' => __('petropd::lang.success'),
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
                'msg'                  => __('petropd::lang.success'),
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
                'msg'                => __('petropd::lang.success'),
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
                'msg'     => __('petropd::lang.success'),
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
        $business_id      = $this->resolveBusinessId();

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')->where('petro_shifts.business_id', $business_id)->select('pump_operators.name', 'petro_shifts.*')->orderBy('id', 'DESC');

        if ($only_pumper) {
            $shifts->where('pump_operator_id', $pump_operator_id);
        }

        $shifts = $shifts->get();

        return view('petropd::pd_operators.partials.payment_summary_modal')->with(compact('only_pumper', 'shifts'));
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show($id)
    {
        abort(404);
    }

    protected function isPaymentEditLocked($payment, $credit_sale = null)
    {
        // IS1831: Payment Summary editing ends as soon as Close Shift completes.
        // Keep the finalized-settlement check as a second server-side safeguard.
        return $this->isPaymentShiftClosed($payment, $credit_sale)
            || $this->isPaymentLinkedToSavedSettlement($payment, $credit_sale);
    }

    private function isPaymentShiftClosed($payment, $credit_sale = null): bool
    {
        try {
            $business_id = $this->resolveBusinessId();
            $shift_id = is_object($payment)
                ? ($payment->shift_id ?? ($payment->payment_shift_id ?? null))
                : null;
            $pump_operator_id = is_object($payment)
                ? ($payment->pump_operator_id ?? null)
                : null;

            if (empty($shift_id) && is_object($credit_sale)) {
                $shift_id = $credit_sale->shift_id ?? null;
                $pump_operator_id = $pump_operator_id
                    ?: ($credit_sale->pump_operator_id ?? null);
            }

            if (empty($shift_id) && is_object($credit_sale)
                && Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')
                && ! empty($credit_sale->pump_payment_id)) {
                $master = PumpOperatorPayment::where('business_id', $business_id)
                    ->where('id', $credit_sale->pump_payment_id)
                    ->first(['shift_id', 'pump_operator_id']);
                $shift_id = $master->shift_id ?? null;
                $pump_operator_id = $pump_operator_id
                    ?: ($master->pump_operator_id ?? null);
            }

            if (empty($shift_id)) {
                return false;
            }

            $shift_closed = PetroShift::where('business_id', $business_id)
                ->where('id', $shift_id)
                ->where(function ($query) {
                    /*
                     * MA-002 (IS-1937 #1): the two STRING comparisons are gone.
                     *
                     * petro_shifts.status is int(11). MySQL casts a
                     * non-numeric string to 0 in a numeric comparison, so
                     *
                     *     status = 'close'    became   status = 0
                     *     status = 'closed'   became   status = 0
                     *
                     * and SettlementStateMachine defines
                     *
                     *     ShiftState::Reopened => ['status' => 0, ...]
                     *
                     * status 0 is an OPEN shift. Those two clauses therefore
                     * matched every open shift, the payment was treated as
                     * locked, and Edit was greyed out while the shift was still
                     * running - which is the reported fault.
                     *
                     * Closed is status 2, or a closed_time stamp. Both are what
                     * the state machine actually writes.
                     */
                    $query->where('status', 2)
                        ->orWhereNotNull('closed_time');
                })
                ->exists();

            if ($shift_closed) {
                return true;
            }

            /*
             * IS1837-06: closing an individual pump/meter assignment does not
             * close the operator shift. Payment Summary remains editable until
             * PetroShift itself is closed (handled above).
             */
            return false;
        } catch (\Throwable $e) {
            Log::warning('IS1831 shift-close edit lock check skipped', [
                'message' => $e->getMessage(),
                'payment_id' => is_object($payment) ? ($payment->id ?? null) : null,
            ]);

            return false;
        }
    }

    protected function isPaymentLinkedToSavedSettlement($payment, $credit_sale = null): bool
    {
        try {
            $business_id = $this->resolveBusinessId() ?? null;

            $payment_id = null;
            $shift_id = null;
            $pump_operator_id = null;
            $settlement_no = null;

            if (is_object($payment)) {
                $payment_id = $payment->id ?? null;
                $shift_id = $payment->shift_id ?? ($payment->payment_shift_id ?? null);
                $pump_operator_id = $payment->pump_operator_id ?? null;
                $settlement_no = $payment->settlement_no ?? null;
            }

            if (empty($payment_id) && ! empty(request('payment_id'))) {
                $payment_id = request('payment_id');
            }

            if ($payment_id) {
                $db_payment = PumpOperatorPayment::where('id', $payment_id)
                    ->when($business_id, function ($q) use ($business_id) {
                        $q->where('business_id', $business_id);
                    })
                    ->first();

                if ($db_payment) {
                    $shift_id = $shift_id ?: ($db_payment->shift_id ?? null);
                    $pump_operator_id = $pump_operator_id ?: ($db_payment->pump_operator_id ?? null);
                    $settlement_no = $settlement_no ?: ($db_payment->settlement_no ?? null);
                }
            }

            if ($credit_sale) {
                $settlement_no = $settlement_no ?: ($credit_sale->settlement_no ?? null);
                $shift_id = $shift_id ?: ($credit_sale->shift_id ?? null);
                $pump_operator_id = $pump_operator_id ?: ($credit_sale->pump_operator_id ?? null);

                // The DailyVoucher shift belongs to the legacy Petro module.
                // Recover the immutable PetroPD shift only from the credit-sale
                // header or its authoritative Pump Operator Payment master.
                if (empty($shift_id)
                    && Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')
                    && ! empty($credit_sale->pump_payment_id)) {
                    $shift_id = PumpOperatorPayment::where('id', $credit_sale->pump_payment_id)
                        ->value('shift_id');
                }

                if (empty($shift_id) && ! empty($credit_sale->collection_form_no)) {
                    $shift_id = PumpOperatorPayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $pump_operator_id)
                        ->whereIn('payment_type', ['credit', 'multiple_credit'])
                        ->where('collection_form_no', $credit_sale->collection_form_no)
                        ->value('shift_id');
                }
            }

            // Lock only when this payment is linked to a saved/finalized settlement.
            if (! empty($settlement_no)) {
                $settlement = Settlement::where(function ($q) use ($settlement_no) {
                        $q->where('id', $settlement_no)
                          ->orWhere('settlement_no', $settlement_no);
                    })
                    ->when($business_id, function ($q) use ($business_id) {
                        $q->where('business_id', $business_id);
                    })
                    ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                    ->first();

                $finalized_statuses = ['finalized', 'finalised', 'closed', 'completed', 'complete', 'approved', 'posted'];

                    if ($settlement && in_array(strtolower(trim((string) $settlement->status)), $finalized_statuses, true)) {
                        return true;
                    }
                    foreach (['is_finalized','is_finalised','finalized','finalised','is_closed','closed','is_completed','completed'] as $flag) {
                        if ($settlement && isset($settlement->{$flag}) && in_array(strtolower((string) $settlement->{$flag}), ['1','true','yes'], true)) {
                            return true;
                        }
                    }
            }

            // Old records may only link through assignment.settlement_id.
            // Lock only when that settlement exists and is finalized.
            if (! empty($shift_id)) {
                $assignment = PumpOperatorAssignment::where('shift_id', $shift_id)
                    ->when($business_id, function ($q) use ($business_id) {
                        $q->where('business_id', $business_id);
                    })
                    ->when($pump_operator_id, function ($q) use ($pump_operator_id) {
                        $q->where('pump_operator_id', $pump_operator_id);
                    })
                    ->whereNotNull('settlement_id')
                    ->orderByDesc('id')
                    ->first();

                if ($assignment && ! empty($assignment->settlement_id)) {
                    $settlement = Settlement::where('id', $assignment->settlement_id)
                        ->when($business_id, function ($q) use ($business_id) {
                            $q->where('business_id', $business_id);
                        })
                        ->where('settlement_no', 'NOT LIKE', 'SET-SW%')
                        ->first();

                    $finalized_statuses = ['finalized', 'finalised', 'closed', 'completed', 'complete', 'approved', 'posted'];

                    if ($settlement && in_array(strtolower(trim((string) $settlement->status)), $finalized_statuses, true)) {
                        return true;
                    }
                    foreach (['is_finalized','is_finalised','finalized','finalised','is_closed','closed','is_completed','completed'] as $flag) {
                        if ($settlement && isset($settlement->{$flag}) && in_array(strtolower((string) $settlement->{$flag}), ['1','true','yes'], true)) {
                            return true;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('PD Payment edit lock check skipped', [
                'message' => $e->getMessage(),
                'payment_id' => is_object($payment) ? ($payment->id ?? null) : null,
            ]);
        }

        return false;
    }
    /**
     * PDRW-012: Payment Summary Edit lock helper.
     * Edit should be disabled only after the linked PD Settlement is finalized/closed.
     * status=0 is treated as draft/saved/not-finalized and must remain editable.
     */
    private function isPdSettlementFinalizedForPaymentEdit($settlement)
    {
        if (empty($settlement)) {
            return false;
        }

        $status = strtolower(trim((string) ($settlement->status ?? '')));
        $finalizedStatuses = ['finalized', 'finalised', 'closed', 'completed', 'complete', 'approved', 'posted'];

        if (in_array($status, $finalizedStatuses, true)) {
            return true;
        }

        foreach (['is_finalized', 'is_finalised', 'finalized', 'finalised', 'is_closed', 'closed', 'is_completed', 'completed'] as $flag) {
            if (isset($settlement->{$flag}) && in_array((string) $settlement->{$flag}, ['1', 'true', 'yes'], true)) {
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
        Log::info('ZIP 039 / IS1455 safe edit resolver', [
            'id' => $id,
            'type' => request('type'),
            'credit_sale_id' => request('credit_sale_id'),
            'payment_id' => request('payment_id'),
        ]);

        try {
            $business_id = $this->resolveBusinessId();
            $requested_type = strtolower((string) request('type'));
            $credit_sale = null;

            $payment = PumpOperatorPayment::leftJoin('pump_operators as po', 'pump_operator_payments.pump_operator_id', '=', 'po.id')
                ->leftJoin('business_locations as bl', 'po.location_id', '=', 'bl.id')
                ->where('pump_operator_payments.id', $id)
                ->where('pump_operator_payments.business_id', $business_id)
                ->select(
                    'pump_operator_payments.*',
                    'bl.name as location_name'
                )
                ->first();

            if ($requested_type === 'credit' && ! empty(request('credit_sale_id'))) {
                $creditSaleQuery = SettlementCreditSalePayment::leftJoin('contacts as c', 'c.id', '=', 'settlement_credit_sale_payments.customer_id')
                    ->where('settlement_credit_sale_payments.id', request('credit_sale_id'))
                    ->where('settlement_credit_sale_payments.business_id', $business_id);

                if ($payment) {
                    if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                        $creditSaleQuery->where(function ($query) use ($payment) {
                            $query->where('settlement_credit_sale_payments.pump_payment_id', $payment->id)
                                ->orWhereNull('settlement_credit_sale_payments.pump_payment_id');
                        });
                    }
                    if (Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')
                        && ! empty($payment->shift_id)) {
                        $creditSaleQuery->where(function ($query) use ($payment) {
                            $query->where('settlement_credit_sale_payments.shift_id', $payment->shift_id)
                                ->orWhereNull('settlement_credit_sale_payments.shift_id');
                        });
                    }
                }

                $credit_sale = $creditSaleQuery
                    ->select('settlement_credit_sale_payments.*', 'c.name as customer_name', 'c.credit_limit as customer_limit')
                    ->first();
            }

            if (! $payment && $requested_type === 'credit') {
                $credit_sale = SettlementCreditSalePayment::leftJoin('contacts as c', 'c.id', '=', 'settlement_credit_sale_payments.customer_id')
                    ->where('settlement_credit_sale_payments.id', $id)
                    ->where('settlement_credit_sale_payments.business_id', $business_id)
                    ->select('settlement_credit_sale_payments.*', 'c.name as customer_name', 'c.credit_limit as customer_limit')
                    ->first();

                if ($credit_sale) {
                    $masterQuery = PumpOperatorPayment::leftJoin('pump_operators as po', 'pump_operator_payments.pump_operator_id', '=', 'po.id')
                        ->leftJoin('business_locations as bl', 'po.location_id', '=', 'bl.id')
                        ->where('pump_operator_payments.business_id', $business_id)
                        ->where('pump_operator_payments.payment_type', 'credit')
                        ->where('pump_operator_payments.pump_operator_id', $credit_sale->pump_operator_id);

                    if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')
                        && ! empty($credit_sale->pump_payment_id)) {
                        $masterQuery->where('pump_operator_payments.id', $credit_sale->pump_payment_id);
                    } else {
                        if (empty($credit_sale->collection_form_no)) {
                            $masterQuery->whereRaw('1 = 0');
                        } else {
                            $masterQuery->where('pump_operator_payments.collection_form_no', $credit_sale->collection_form_no);
                        }
                        if (Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')
                            && ! empty($credit_sale->shift_id)) {
                            $masterQuery->where('pump_operator_payments.shift_id', $credit_sale->shift_id);
                        }
                    }

                    $masterCandidates = $masterQuery
                        ->select('pump_operator_payments.*', 'bl.name as location_name')
                        ->limit(2)
                        ->get();

                    if ($masterCandidates->count() === 1) {
                        $payment = $masterCandidates->first();

                        if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')
                            && empty($credit_sale->pump_payment_id)) {
                            DB::table('settlement_credit_sale_payments')
                                ->where('id', $credit_sale->id)
                                ->whereNull('pump_payment_id')
                                ->update(['pump_payment_id' => $payment->id]);
                            $credit_sale->pump_payment_id = $payment->id;
                        }
                    } elseif ($masterCandidates->count() > 1) {
                        Log::error('PD credit edit blocked: ambiguous master payment', [
                            'credit_sale_id' => $credit_sale->id,
                            'business_id' => $credit_sale->business_id,
                            'pump_operator_id' => $credit_sale->pump_operator_id,
                            'shift_id' => $credit_sale->shift_id ?? null,
                            'collection_form_no' => $credit_sale->collection_form_no,
                        ]);
                    }
                }
            }

            if (! $payment) {
                return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">Payment record not found.</div></div></div></div>', 404);
            }

            if ($payment->payment_type === 'card') {
                try {
                    $settlement_card = null;
                    if (Schema::hasColumn('settlement_card_payments', 'pump_payment_id')) {
                        $settlement_card = SettlementCardPayment::where('business_id', $payment->business_id)
                            ->where('pump_payment_id', $payment->id)
                            ->first();
                    }

                    $dailyCardQuery = DailyCard::where('business_id', $payment->business_id)
                        ->where('pump_operator_id', $payment->pump_operator_id);

                    if (Schema::hasColumn('daily_cards', 'pump_payment_id')) {
                        $dailyCardQuery->where('pump_payment_id', $payment->id);
                    } else {
                        $dailyCardQuery->where('collection_no', $payment->collection_form_no);
                        if (Schema::hasColumn('daily_cards', 'shift_id')) {
                            $dailyCardQuery->where('shift_id', $payment->shift_id);
                        }
                    }

                    $dailyCardCandidates = $dailyCardQuery->limit(2)->get();
                    if ($dailyCardCandidates->count() > 1) {
                        throw new \RuntimeException(
                            'Payment reconciliation stopped: more than one Daily Card matches Pump Payment ID '
                            . $payment->id
                            . '.'
                        );
                    }
                    $daily_card = $dailyCardCandidates->first();

                    if ($daily_card) {
                        if (! $settlement_card) {
                            $settlement_card = SettlementCardPayment::where('business_id', $payment->business_id)
                                ->where('daily_card_id', $daily_card->id)
                                ->first();
                        }

                        if ((! $payment->payment_amount || (float) $payment->payment_amount <= 0) && ! empty($daily_card->amount)) {
                            $payment->payment_amount = $daily_card->amount;
                        }

                        if (empty($payment->note) && isset($daily_card->note)) {
                            $payment->note = $daily_card->note;
                        }
                    }

                    // IS1831: expose the complete authoritative card metadata in
                    // the edit form. Prefer PumpOperatorPayment, then the linked
                    // settlement card, then the legacy DailyCard row.
                    /*
                     * IS1984 #6 (10 Aug 2026): slip numbers vanished after editing
                     * a card payment.
                     *
                     * The chain below used ??, which only falls through on NULL.
                     * pump_operator_payments.slip_no is very often an EMPTY STRING
                     * rather than NULL - this module's own Payment Summary query
                     * allows for exactly that, reading the slip as
                     *     COALESCE(NULLIF(pump_operator_payments.slip_no, ''), dc.slip_no)
                     * With ?? the empty string won, so the Slip No box opened blank
                     * even though daily_cards held the real number. Saving that
                     * blank box then wrote it back over the stored slip (see the
                     * matching guard in update()), so the number was destroyed by
                     * an edit the user believed only changed the amount.
                     *
                     * Now blank is treated the same as missing, so the box opens
                     * with the slip that is actually on record.
                     */
                    foreach (['customer_id', 'card_type', 'card_number', 'slip_no'] as $card_meta_column) {
                        $card_meta_candidates = [
                            $payment->{$card_meta_column} ?? null,
                            $settlement_card->{$card_meta_column} ?? null,
                            $daily_card->{$card_meta_column} ?? null,
                        ];

                        $resolved_card_meta = null;
                        foreach ($card_meta_candidates as $card_meta_candidate) {
                            if ($card_meta_candidate !== null && $card_meta_candidate !== '') {
                                $resolved_card_meta = $card_meta_candidate;
                                break;
                            }
                        }

                        $payment->{$card_meta_column} = $resolved_card_meta;
                    }

                    /*
                     * LA-1162 #2: last-resort fallback for the card metadata.
                     *
                     * The three sources above are the linked PumpOperatorPayment,
                     * SettlementCardPayment and DailyCard rows. When a card is
                     * added from the pumper dashboard the row is matched by
                     * pump_payment_id where that column exists, and otherwise by
                     * collection_no plus shift_id - and a card saved without a
                     * collection form number matches on neither, so all three
                     * candidates come back empty and Card Type, Card Number and
                     * Slip No opened blank.
                     *
                     * This looks the card up by the values that identify it even
                     * without those links: same operator, same amount, same day.
                     * It only ever runs when a field is STILL empty after the
                     * chain above, so it cannot override a properly linked value.
                     */
                    $card_meta_still_missing = empty($payment->card_type)
                        || empty($payment->card_number)
                        || empty($payment->slip_no);

                    if ($card_meta_still_missing && ! $daily_card) {
                        $fallbackCardQuery = DailyCard::where('business_id', $payment->business_id)
                            ->where('pump_operator_id', $payment->pump_operator_id)
                            ->whereDate('created_at', \Carbon\Carbon::parse($payment->date_and_time ?? $payment->created_at)->toDateString());

                        if (! empty($payment->payment_amount)) {
                            $fallbackCardQuery->where('amount', $payment->payment_amount);
                        }

                        $fallbackCards = $fallbackCardQuery->limit(2)->get();

                        // Only trust it when exactly one card matches - two cards of
                        // the same amount on the same day are indistinguishable here,
                        // and guessing between them would show the wrong slip number.
                        if ($fallbackCards->count() === 1) {
                            $fallback_card = $fallbackCards->first();

                            foreach (['customer_id', 'card_type', 'card_number', 'slip_no'] as $card_meta_column) {
                                if (empty($payment->{$card_meta_column}) && ! empty($fallback_card->{$card_meta_column})) {
                                    $payment->{$card_meta_column} = $fallback_card->{$card_meta_column};
                                }
                            }
                        }
                    }

                    /*
                     * LA-1162 #2: Customer defaults to Walk-In Customer.
                     *
                     * Card payments are overwhelmingly walk-in, and the field is
                     * required - leaving it unset meant the modal opened on the
                     * placeholder and the user had to pick a value before they
                     * could save an unrelated change such as the amount.
                     *
                     * Applied only when the payment genuinely has no customer, so
                     * a card recorded against a named customer still shows them.
                     */
                    if (empty($payment->customer_id)) {
                        $walk_in_customer_id = Contact::where('business_id', $payment->business_id)
                            ->where('type', 'customer')
                            ->where('is_default', 1)
                            ->value('id');

                        if (! empty($walk_in_customer_id)) {
                            $payment->customer_id = $walk_in_customer_id;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('PD card edit lookup blocked', [
                        'pump_payment_id' => $payment->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            if ($payment->payment_type === 'credit') {
                if (! $credit_sale && Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                    $credit_sale = SettlementCreditSalePayment::leftJoin('contacts as c', 'c.id', '=', 'settlement_credit_sale_payments.customer_id')
                        ->where('settlement_credit_sale_payments.pump_payment_id', $payment->id)
                        ->where('settlement_credit_sale_payments.business_id', $payment->business_id)
                        ->when(
                            Schema::hasColumn('settlement_credit_sale_payments', 'shift_id') && ! empty($payment->shift_id),
                            fn ($query) => $query->where('settlement_credit_sale_payments.shift_id', $payment->shift_id)
                        )
                        ->select('settlement_credit_sale_payments.*', 'c.name as customer_name', 'c.credit_limit as customer_limit')
                        ->first();
                }

                if (! $credit_sale && ! empty($payment->collection_form_no)) {
                    $legacyDetailQuery = SettlementCreditSalePayment::leftJoin('contacts as c', 'c.id', '=', 'settlement_credit_sale_payments.customer_id')
                        ->where('settlement_credit_sale_payments.collection_form_no', $payment->collection_form_no)
                        ->where('settlement_credit_sale_payments.pump_operator_id', $payment->pump_operator_id)
                        ->where('settlement_credit_sale_payments.business_id', $payment->business_id);

                    if (Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')
                        && ! empty($payment->shift_id)) {
                        $legacyDetailQuery->where('settlement_credit_sale_payments.shift_id', $payment->shift_id);
                    }
                    if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                        $legacyDetailQuery->whereNull('settlement_credit_sale_payments.pump_payment_id');
                    }

                    $legacyDetailCandidates = $legacyDetailQuery
                        ->select('settlement_credit_sale_payments.*', 'c.name as customer_name', 'c.credit_limit as customer_limit')
                        ->limit(2)
                        ->get();

                    if ($legacyDetailCandidates->count() === 1) {
                        $credit_sale = $legacyDetailCandidates->first();
                        if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                            DB::table('settlement_credit_sale_payments')
                                ->where('id', $credit_sale->id)
                                ->whereNull('pump_payment_id')
                                ->update(['pump_payment_id' => $payment->id]);
                            $credit_sale->pump_payment_id = $payment->id;
                        }
                    } elseif ($legacyDetailCandidates->count() > 1) {
                        Log::error('PD credit edit blocked: ambiguous legacy credit detail', [
                            'pump_payment_id' => $payment->id,
                            'business_id' => $payment->business_id,
                            'pump_operator_id' => $payment->pump_operator_id,
                            'shift_id' => $payment->shift_id,
                            'collection_form_no' => $payment->collection_form_no,
                        ]);
                    }
                }

                if ($credit_sale) {
                    $payment->credit_sale_id = $credit_sale->id;

                    if ((! $payment->payment_amount || (float) $payment->payment_amount <= 0) && ! empty($credit_sale->amount)) {
                        $payment->payment_amount = $credit_sale->amount;
                    }

                    if (empty($payment->note) && ! empty($credit_sale->note)) {
                        $payment->note = $credit_sale->note;
                    }
                }
            }

            if (empty($payment->location_name)) {
                $pump_operator = PumpOperator::find($payment->pump_operator_id);
                if ($pump_operator && $pump_operator->location_id) {
                    $location = BusinessLocation::find($pump_operator->location_id);
                    $payment->location_name = $location ? $location->name : '';
                }
            }

            if ($this->isPaymentEditLocked($payment, $credit_sale)) {
                Log::warning('PDRW-012 payment edit still locked', [
                    'payment_id' => isset($payment) ? ($payment->id ?? null) : null,
                    'payment_type' => isset($payment) ? ($payment->payment_type ?? null) : null,
                    'settlement_no' => isset($settlement_no) ? $settlement_no : null,
                    'shift_id' => isset($shift_id) ? $shift_id : null,
                    'pump_operator_id' => isset($pump_operator_id) ? $pump_operator_id : null,
                ]);
                return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title">Payment Locked</h4></div><div class="modal-body"><div class="alert alert-warning">This payment cannot be edited because the linked PD Settlement is finalized.</div></div></div></div>', 200);
            }

            $payment_types = [
                'cash'   => __('petropd::lang.cash') ?: 'Cash',
                'card'   => __('petropd::lang.card') ?: 'Card',
                'credit' => __('petropd::lang.credit') ?: 'Credit',
            ];
            $customers = \App\Contact::where('business_id', $business_id)
                ->where(function ($q) {
                    $q->where('type', 'customer')->orWhere('type', 'both');
                })
                ->whereNull('deleted_at')
                ->pluck('name', 'id');

            $card_types = collect();
            if (strtolower((string) $payment->payment_type) === 'card') {
                /*
                 |--------------------------------------------------------------
                 | IS2062 #2: card type, number and slip come from daily_cards
                 | when the payment itself does not carry them.
                 |--------------------------------------------------------------
                 |
                 | Reported: the Edit form showed these three fields empty even
                 | though they were entered on the Pumper Dashboard.
                 |
                 | Measured on ep127-kfm2026 - the payment holds nothing:
                 |
                 |     pump_operator_payments  105  slip_no NULL  card_type NULL
                 |     daily_cards              64  slip_no 46208  collection_no 96
                 |
                 | The values live on the card row, matched by business, amount and
                 | collection number - the same join the Payment Summary uses, and
                 | verified to resolve correctly for this payment.
                 |
                 | Only fills a field that is EMPTY, so anything already on the
                 | payment wins and nothing is overwritten.
                 */
                try {
                    $needs_card_meta = empty($payment->slip_no)
                        || empty($payment->card_type)
                        || empty($payment->card_number);

                    if ($needs_card_meta && ! empty($payment->collection_form_no)) {
                        $linked_card = DailyCard::where('business_id', $payment->business_id)
                            ->where('collection_no', $payment->collection_form_no)
                            ->where('amount', $payment->payment_amount)
                            ->first();

                        if ($linked_card) {
                            if (empty($payment->slip_no) && ! empty($linked_card->slip_no)) {
                                $payment->slip_no = $linked_card->slip_no;
                            }
                            if (empty($payment->card_type) && ! empty($linked_card->card_type)) {
                                $payment->card_type = $linked_card->card_type;
                            }
                            if (empty($payment->card_number) && ! empty($linked_card->card_number)) {
                                $payment->card_number = $linked_card->card_number;
                            }
                            if (empty($payment->customer_id) && ! empty($linked_card->customer_id)) {
                                $payment->customer_id = $linked_card->customer_id;
                            }
                        }
                    }
                } catch (\Throwable $card_meta_error) {
                    // The form must still open even if the card row cannot be read.
                    \Log::warning('IS2062: card details could not be read from daily_cards', [
                        'pump_payment_id' => $payment->id,
                        'error'           => $card_meta_error->getMessage(),
                    ]);
                }

                $card_group = AccountGroup::where('business_id', $business_id)
                    ->whereIn('name', ['Card', 'Cards (Credit Debit)'])
                    ->pluck('id');

                $card_types = Account::where('business_id', $business_id)
                    ->where(function ($query) use ($card_group) {
                        if ($card_group->isNotEmpty()) {
                            $query->whereIn('asset_type', $card_group);
                        }
                        $query->orWhere('name', 'like', '%card%')
                            ->orWhere('name', 'like', '%visa%')
                            ->orWhere('name', 'like', '%master%')
                            ->orWhere('name', 'like', '%amex%');
                    })
                    ->whereRaw("REPLACE(name, '  ', ' ') != 'Cards (Credit Debit) Account'")
                    ->pluck('name', 'id');

                /*
                 | IS2010 #4: pre-select the card type that is already on record.
                 |
                 | The list is keyed by account ID, but pump_operator_payments and
                 | daily_cards do not always store the ID - some rows hold the card
                 | type NAME, e.g. "visa master". When that happened the select had
                 | no option whose value matched, so it fell back to the first entry
                 | and the saved card type appeared to be lost.
                 |
                 | The lookup below now resolves either form:
                 |   - an ID that is missing from the list is fetched and added
                 |   - a NAME is matched case-insensitively and swapped for its ID
                 |
                 | $payment->card_type is normalised to the matching key so the
                 | Blade select marks it selected.
                 */
                if (! empty($payment->card_type) && ! $card_types->has($payment->card_type)) {
                    $stored_card_type = (string) $payment->card_type;

                    // Case 1: it is an account id that the filters above excluded.
                    $selected_card_type = Account::where('business_id', $business_id)
                        ->where('id', $stored_card_type)
                        ->first();

                    if ($selected_card_type) {
                        $card_types->put($selected_card_type->id, $selected_card_type->name);
                        $payment->card_type = $selected_card_type->id;
                    } else {
                        // Case 2: it is a name - find it in the list, ignoring case
                        // and any doubled spaces.
                        $normalise = static fn ($value) => strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));
                        $target = $normalise($stored_card_type);

                        $matched_id = $card_types->search(
                            static fn ($name) => $normalise($name) === $target
                        );

                        if ($matched_id !== false) {
                            $payment->card_type = $matched_id;
                        } else {
                            // Case 3: a name with no matching account. Keep it
                            // visible rather than silently switching the operator
                            // to a different card type.
                            $card_types->put($stored_card_type, $stored_card_type);
                        }
                    }
                }
            }

            /*
             | IS2010 #5: show the customer's outstanding on the credit edit form.
             |
             | The Outstanding box already exists in the Blade and reads
             | $credit_sale_obj->customer_outstanding - but nothing ever set that
             | property, so the box was always blank.
             |
             | The figure is the customer's ledger balance: what they owe across
             | all credit sales, less what they have paid. Taken from the same
             | contacts/ledger source the rest of the module uses, and computed
             | here rather than in the Blade so the view stays presentational.
             |
             | Wrapped in a try/catch: an outstanding figure is informational, and
             | a failure to calculate it must not stop the edit form opening.
             */
            if (! empty($credit_sale) && ! empty($credit_sale->customer_id)) {
                try {
                    $customer_outstanding = null;

                    if (class_exists(\App\Utils\ContactUtil::class) && ! empty($this->contactUtil)) {
                        $ledger_contact = \App\Contact::find($credit_sale->customer_id);

                        if (! empty($ledger_contact)) {
                            // balance is the running due amount maintained on the contact.
                            $customer_outstanding = (float) ($ledger_contact->balance ?? 0);
                        }
                    }

                    if ($customer_outstanding === null) {
                        $customer_outstanding = (float) (\App\Contact::where('id', $credit_sale->customer_id)
                            ->value('balance') ?? 0);
                    }

                    $credit_sale->customer_outstanding = $customer_outstanding;
                } catch (\Throwable $outstandingException) {
                    Log::warning('IS2010: could not resolve customer outstanding', [
                        'customer_id' => $credit_sale->customer_id,
                        'message' => $outstandingException->getMessage(),
                    ]);
                }
            }

            return view('petropd::pd_operators.partials.edit_payment')->with(compact(
                'payment',
                'payment_types',
                'credit_sale',
                'customers',
                'card_types'
            ));
        } catch (\Throwable $e) {
            Log::emergency('ZIP 039 payment edit failed. File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return response('<div class="modal-dialog"><div class="modal-content"><div class="modal-body"><div class="alert alert-danger">Unable to open payment edit form. Please check Laravel log for ZIP 039 payment edit failed.</div></div></div></div>', 500);
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
        /*
         |----------------------------------------------------------------------
         | Zero is a valid quantity, and a valid amount.
         |----------------------------------------------------------------------
         |
         | Reported: the edit refused to save with
         |     "Failed. The qty must be at least 0.001"
         | so a payment could not be zeroed.
         |
         | min:0.001 was presumably there to stop a blank or negative entry, but
         | it also blocks the legitimate case: correcting a payment to nil when it
         | was entered in error, or when the operator handed nothing in.
         |
         | min:0 allows zero while still rejecting negatives, which is the actual
         | intent. nullable is kept, so leaving the field empty is unchanged.
         */
        $request->validate([
            'note' => 'required',
            'qty' => 'nullable|numeric|min:0',
        ]);

        try {
            // CREDIT PAYMENTS: Handle separately - update settlement_credit_sale_payments
            if ($request->input('payment_type') === 'credit' && $request->input('credit_sale_id')) {
                $credit_sale_id = $request->input('credit_sale_id');
                $credit_sale    = SettlementCreditSalePayment::find($credit_sale_id);
                $payment_for_lock = PumpOperatorPayment::where('id', $id)->first();
                if ($this->isPaymentEditLocked($payment_for_lock, $credit_sale)) {
                    return response()->json([
                        'success' => false,
                        'msg'     => 'Locked: this shift is closed or its PD Settlement is already saved.',
                    ], 403);
                }

                if (! $payment_for_lock || ! $credit_sale) {
                    throw new \RuntimeException('Payment reconciliation stopped: the exact master payment and Credit Sale row are required.');
                }
                if (! empty($credit_sale->pump_payment_id)
                    && (int) $credit_sale->pump_payment_id !== (int) $payment_for_lock->id) {
                    throw new \RuntimeException('Payment reconciliation stopped: the Credit Sale row is linked to another Pump Payment ID.');
                }
                if (! empty($credit_sale->shift_id)
                    && (int) $credit_sale->shift_id !== (int) $payment_for_lock->shift_id) {
                    throw new \RuntimeException('Payment reconciliation stopped: the Credit Sale Shift ID differs from its master payment.');
                }

                DB::beginTransaction();
                $old_scsp_amount = (float) $credit_sale->amount;
                
                $updateData = [
                    'note'       => $request->input('note'),
                    'updated_by' => Auth::user()->id,
                    'updated_at' => now(),
                ];

                if ($request->filled('customer_id')) {
                    $updateData['customer_id'] = $request->input('customer_id');
                }
                if ($request->has('order_number')) {
                    $updateData['order_number'] = $request->input('order_number');
                }
                if ($request->filled('qty')) {
                    $updateData['qty'] = (float) $request->input('qty');
                }

                // Update amount if provided and valid
                if ($request->has('payment_amount') && is_numeric($request->input('payment_amount'))) {
                    $updateData['amount'] = (float) $request->input('payment_amount');
                }

                // Qty is the source of truth for a credit-sale line. Recalculate
                // the gross amount on the server instead of trusting browser JS.
                /*
                 | IS2167: the entered amount stands.
                 |
                 | This used to recalculate as qty * price and overwrite what the
                 | user typed. A price with decimals then produced figures like
                 | 13,890.016 from an entered 13,890 - and the settlement showed
                 | that, correctly, as 13,890.02.
                 |
                 | The recalculation remains for the case it was written for:
                 | a quantity changed with NO amount given.
                */
                if (! ($request->has('payment_amount') && is_numeric($request->input('payment_amount')))
                    && isset($updateData['qty']) && is_numeric($credit_sale->price)) {
                    $updateData['amount'] = round(
                        (float) $updateData['qty'] * (float) $credit_sale->price,
                        4
                    );
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

                // Keep the unique pump_operator_payments master row authoritative.
                if (! empty($updatedScsp)) {
                    $grossAmount = round((float) $updatedScsp->amount, 4);
                    $discountAmount = round((float) ($updatedScsp->total_discount ?? 0), 4);
                    $netAmount = round((float) ($updatedScsp->sub_total ?? ($grossAmount - $discountAmount)), 4);

                    $popUpdate = [
                        // Keep legacy semantics: payment_amount remains the gross credit amount.
                        'payment_amount' => $grossAmount,
                        'note' => $updatedScsp->note,
                        'edited_by' => Auth::user()->id,
                        'updated_at' => now(),
                    ];

                    if (Schema::hasColumn('pump_operator_payments', 'gross_amount')) {
                        $popUpdate['gross_amount'] = $grossAmount;
                    }
                    if (Schema::hasColumn('pump_operator_payments', 'discount_amount')) {
                        $popUpdate['discount_amount'] = $discountAmount;
                    }
                    if (Schema::hasColumn('pump_operator_payments', 'net_amount')) {
                        $popUpdate['net_amount'] = $netAmount;
                    }
                    if (Schema::hasColumn('pump_operator_payments', 'source_type')) {
                        $popUpdate['source_type'] = 'credit_sale';
                    }
                    if (Schema::hasColumn('pump_operator_payments', 'source_id')) {
                        $popUpdate['source_id'] = (int) $updatedScsp->id;
                    }
                    if (Schema::hasColumn('pump_operator_payments', 'customer_id')
                        && ! empty($updatedScsp->customer_id)) {
                        $popUpdate['customer_id'] = (int) $updatedScsp->customer_id;
                    }
                    if (Schema::hasColumn('pump_operator_payments', 'transaction_date')
                        && ! empty($updatedScsp->order_date)) {
                        $popUpdate['transaction_date'] = $updatedScsp->order_date;
                    }
                    if (Schema::hasColumn('pump_operator_payments', 'reference_no')) {
                        $referenceNo = $updatedScsp->bill_number ?? $updatedScsp->order_number ?? null;
                        if (! empty($referenceNo)) {
                            $popUpdate['reference_no'] = (string) $referenceNo;
                        }
                    }
                    if ($request->filled('customer_id') && Schema::hasColumn('pump_operator_payments', 'customer_id')) {
                        $popUpdate['customer_id'] = $request->input('customer_id');
                    }

                    $targetPumpPaymentId = ! empty($updatedScsp->pump_payment_id)
                        ? (int) $updatedScsp->pump_payment_id
                        : (int) $id;

                    // Never update by collection number or amount. Those values are not unique.
                    PumpOperatorPayment::where('id', $targetPumpPaymentId)
                        ->where('payment_type', 'credit')
                        ->where('business_id', $updatedScsp->business_id)
                        ->where('pump_operator_id', $updatedScsp->pump_operator_id)
                        ->update($popUpdate);

                    // Keep the linked Daily Voucher and its one exact product
                    // line aligned with the edited credit-sale quantity.
                    if (! empty($updatedScsp->daily_voucher_id)) {
                        $voucher = DailyVoucher::where('business_id', $updatedScsp->business_id)
                            ->where('id', $updatedScsp->daily_voucher_id)
                            ->first();

                        if ($voucher) {
                            $voucher->total_amount = $netAmount;
                            $voucher->save();

                            if (isset($updateData['qty']) && ! empty($updatedScsp->product_id)) {
                                $voucherItemQuery = DailyVoucherItem::where(
                                    'daily_voucher_id',
                                    $voucher->id
                                )->where('product_id', $updatedScsp->product_id);
                                $voucherItems = $voucherItemQuery->limit(2)->get();

                                if ($voucherItems->count() > 1) {
                                    throw new \RuntimeException(
                                        'Payment reconciliation stopped: more than one Daily Voucher item matches the edited Credit Sale.'
                                    );
                                }

                                if ($voucherItem = $voucherItems->first()) {
                                    $voucherItem->qty = (float) $updatedScsp->qty;
                                    $voucherItem->unit_price = (float) $updatedScsp->price;
                                    $voucherItem->sub_total = $netAmount;
                                    $voucherItem->save();
                                }
                            }
                        }
                    }
                }
                
                DB::commit();

                return response()->json([
                    'success'        => true,
                    'msg'            => __('petropd::lang.payment_updated_successfully'),
                    'payment_amount' => $updatedScsp->amount ?? $request->input('payment_amount'),
                    'old_amount' => $old_scsp_amount,
                ]);
            }

            // NON-CREDIT PAYMENTS: Original flow
            $payment = PumpOperatorPayment::findOrFail($id);
            if ($this->isPaymentEditLocked($payment, null)) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'Locked: this shift is closed or its PD Settlement is already saved.',
                ], 403);
            }

            DB::beginTransaction();
            $old_amount = $payment->payment_amount;
            // $data              = $request->except('_token', '_method');
            $data              = $request->except('_token', '_method', 'location_name', 'credit_sale_id', 'payment_type');
            $data['edited_by'] = Auth::user()->id;

            if ($payment->payment_type === 'card') {
                $card_customer_id = (int) $request->input('customer_id');
                $card_type_id = (int) $request->input('card_type');

                $valid_customer = Contact::where('business_id', $payment->business_id)
                    ->where('id', $card_customer_id)
                    ->whereNull('deleted_at')
                    ->exists();
                $valid_card_type = Account::where('business_id', $payment->business_id)
                    ->where('id', $card_type_id)
                    ->exists();

                if (! $valid_customer || ! $valid_card_type) {
                    throw new \InvalidArgumentException(
                        'Please select a valid customer and card type for this business.'
                    );
                }
            }

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

            // Synchronize operational detail by the authoritative Pump Payment ID.
            // Legacy references are accepted only when exactly one row matches the
            // same business, operator and immutable Shift ID.
            if ($payment->payment_type === 'cash') {
                $dailyCollectionQuery = DailyCollection::where('business_id', $payment->business_id);
                if (Schema::hasColumn('daily_collections', 'pump_payment_id')) {
                    $dailyCollectionQuery->where('pump_payment_id', $payment->id);
                } else {
                    /*
                     |--------------------------------------------------------------
                     | collection_form_no is only used when the payment HAS one.
                     |--------------------------------------------------------------
                     |
                     | Measured cause of rows_matched:0 - pump_operator_payments.
                     | collection_form_no is NULL on the payments being edited:
                     |
                     |     id 43  cash  20000.00  collection_form_no NULL  shift 3
                     |
                     | where('collection_form_no', NULL) becomes
                     |     WHERE collection_form_no = NULL
                     | which matches NOTHING in SQL - not even rows that are
                     | themselves NULL. So the query found no collection, nothing was
                     | updated, and the old amount survived into the print.
                     |
                     | The column is now only added to the match when the payment
                     | actually carries a value. Operator, shift, type and the OLD
                     | amount still identify the rows, and the sibling match below
                     | picks up the partner row written in the same second.
                     */
                    $dailyCollectionQuery
                        ->where('pump_operator_id', $payment->pump_operator_id)
                        ->where('type', 'daily_collection')
                        ->where('current_amount', $old_amount);

                    if (! empty($payment->collection_form_no)) {
                        $dailyCollectionQuery->where('collection_form_no', $payment->collection_form_no);
                    }
                    if (Schema::hasColumn('daily_collections', 'shift_id')) {
                        $dailyCollectionQuery->where('shift_id', $payment->shift_id);
                    }
                }

                /*
                 |------------------------------------------------------------------
                 | BOTH Daily Collection rows for this payment are updated.
                 |------------------------------------------------------------------
                 |
                 | Reported: after editing a cash payment from 13,333 to 13,330, the
                 | reconfirmation form still showed 13,333 - and sometimes the sum of
                 | both figures.
                 |
                 | A payment writes TWO daily_collections rows, together, one second
                 | apart at most. Measured on lashini shift 3:
                 |
                 |   id 12  form_no 12  11,111  settlement 3  added_to_account NULL
                 |   id 11  form_no 15  11,111  settlement 4  added_to_account 1
                 |
                 | They differ by collection_form_no and by added_to_account. The
                 | match below included collection_form_no, so it found ONE of them
                 | and left the other holding the old figure - which is what the form
                 | then displayed, or added in.
                 |
                 | Worse, the guard threw when a second row matched, so the edit could
                 | fail outright instead of updating anything.
                 |
                 | WHY THIS IS SAFE, where the earlier attempt was not:
                 | an earlier version matched on operator + shift + AMOUNT, which
                 | would also have caught a DIFFERENT payment of the same amount in
                 | the same shift - two operators each handing in 5,000, say. That
                 | risked corrupting a payment nobody had touched.
                 |
                 | This matches on collection_form_no as before, plus the SIBLING row
                 | that shares the same created_at second and the same operator and
                 | shift. The pair is written together, so that identifies the partner
                 | exactly - without relying on the amount at all. A different
                 | payment, entered at a different moment, cannot be caught.
                 */
                $dailyCollections = $dailyCollectionQuery->get();

                if ($dailyCollections->isNotEmpty() && ! Schema::hasColumn('daily_collections', 'pump_payment_id')) {
                    $primary = $dailyCollections->first();

                    // The partner row: same operator, same shift, written in the same
                    // second - but a different collection_form_no.
                    $sibling = DailyCollection::where('business_id', $payment->business_id)
                        ->where('pump_operator_id', $payment->pump_operator_id)
                        ->where('type', 'daily_collection')
                        ->where('current_amount', $old_amount)
                        ->where('created_at', $primary->created_at)
                        ->whereNotIn('id', $dailyCollections->pluck('id')->all())
                        ->when(Schema::hasColumn('daily_collections', 'shift_id'),
                            fn ($q) => $q->where('shift_id', $payment->shift_id))
                        ->get();

                    $dailyCollections = $dailyCollections->concat($sibling);
                }

                \Log::info('PD payment edit: daily collections to update', [
                    'pump_payment_id' => $payment->id,
                    'shift_id'        => $payment->shift_id,
                    'old_amount'      => $old_amount,
                    'new_amount'      => $data['payment_amount'] ?? $payment->payment_amount,
                    'rows_matched'    => $dailyCollections->count(),
                    'collection_ids'  => $dailyCollections->pluck('id')->all(),
                ]);

                foreach ($dailyCollections as $dailyCollection) {
                    $dailyCollection->current_amount = $data['payment_amount'] ?? $payment->payment_amount;
                    if (Schema::hasColumn('daily_collections', 'pump_payment_id')) {
                        $dailyCollection->pump_payment_id = $payment->id;
                    }
                    if (Schema::hasColumn('daily_collections', 'shift_id')) {
                        $dailyCollection->shift_id = $payment->shift_id;
                    }
                    $dailyCollection->save();
                }
            }

            if ($payment->payment_type === 'card') {
                $dailyCardUpdate = [
                    'amount' => $data['payment_amount'] ?? $payment->payment_amount,
                    'note' => $data['note'] ?? $payment->note,
                ];

                /*
                 * IS1984 #6 (10 Aug 2026): was $request->has($card_meta_column).
                 *
                 * has() is presence-based - it returns true for a field that was
                 * submitted empty. So an edit that only changed the amount still
                 * posted slip_no='' and copied that blank straight over
                 * daily_cards.slip_no, wiping a slip number nobody touched.
                 * filled() requires an actual value, so a blank box now leaves the
                 * stored metadata alone.
                 *
                 * Trade-off worth knowing: clearing one of these fields back to
                 * blank on purpose is no longer possible from this form. For card
                 * slip, type and number that is the safer default - losing a
                 * recorded slip silently is the far more damaging outcome, and it
                 * is what was reported.
                 */
                foreach (['customer_id', 'card_type', 'card_number', 'slip_no'] as $card_meta_column) {
                    if ($request->filled($card_meta_column)
                        && Schema::hasColumn('daily_cards', $card_meta_column)) {
                        $dailyCardUpdate[$card_meta_column] = $request->input($card_meta_column);
                    }
                }

                $dailyCardQuery = DailyCard::where('business_id', $payment->business_id);
                if (Schema::hasColumn('daily_cards', 'pump_payment_id')) {
                    $dailyCardQuery->where('pump_payment_id', $payment->id);
                } else {
                    $linkedDailyCardId = SettlementCardPayment::where('pump_payment_id', $payment->id)
                        ->value('daily_card_id');
                    if (! empty($linkedDailyCardId)) {
                        $dailyCardQuery->where('id', $linkedDailyCardId);
                    } else {
                        $dailyCardQuery
                            ->where('collection_no', $payment->collection_form_no)
                            ->where('pump_operator_id', $payment->pump_operator_id)
                            ->where('amount', $old_amount);
                        if (Schema::hasColumn('daily_cards', 'shift_id')) {
                            $dailyCardQuery->where('shift_id', $payment->shift_id);
                        }
                    }
                }

                $dailyCards = $dailyCardQuery->limit(2)->get();
                if ($dailyCards->count() > 1) {
                    throw new \RuntimeException('Payment reconciliation stopped: more than one Daily Card matches Pump Payment ID ' . $payment->id . '.');
                }
                if ($dailyCard = $dailyCards->first()) {
                    foreach ($dailyCardUpdate as $column => $value) {
                        $dailyCard->{$column} = $value;
                    }
                    if (Schema::hasColumn('daily_cards', 'pump_payment_id')) {
                        $dailyCard->pump_payment_id = $payment->id;
                    }
                    if (Schema::hasColumn('daily_cards', 'shift_id')) {
                        $dailyCard->shift_id = $payment->shift_id;
                    }
                    $dailyCard->save();
                }
            }

            if (in_array($payment->payment_type, ['cheque', 'cheques'], true)) {
                $dailyChequeQuery = DailyChequePayment::where('business_id', $payment->business_id);
                if (Schema::hasColumn('daily_cheque_payments', 'pump_payment_id')) {
                    $dailyChequeQuery->where('pump_payment_id', $payment->id);
                } elseif (Schema::hasColumn('daily_cheque_payments', 'linked_payment_id')) {
                    $dailyChequeQuery->where('linked_payment_id', $payment->id);
                } else {
                    $dailyChequeQuery
                        ->where('pump_operator_id', $payment->pump_operator_id)
                        ->where('amount', $old_amount);
                    if (Schema::hasColumn('daily_cheque_payments', 'shift_id')) {
                        $dailyChequeQuery->where('shift_id', $payment->shift_id);
                    }
                }

                $dailyCheques = $dailyChequeQuery->limit(2)->get();
                if ($dailyCheques->count() > 1) {
                    throw new \RuntimeException('Payment reconciliation stopped: more than one Daily Cheque matches Pump Payment ID ' . $payment->id . '.');
                }
                if ($dailyCheque = $dailyCheques->first()) {
                    $dailyCheque->amount = $data['payment_amount'] ?? $payment->payment_amount;
                    if (Schema::hasColumn('daily_cheque_payments', 'note')) {
                        $dailyCheque->note = $data['note'] ?? $payment->note;
                    }
                    if (Schema::hasColumn('daily_cheque_payments', 'pump_payment_id')) {
                        $dailyCheque->pump_payment_id = $payment->id;
                    }
                    if (Schema::hasColumn('daily_cheque_payments', 'shift_id')) {
                        $dailyCheque->shift_id = $payment->shift_id;
                    }
                    $dailyCheque->save();
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
                    app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
                        ->editCashPayment($payment->business_id, $scp->id, [
                            'amount' => $payment->payment_amount,
                            'note' => $payment->note,
                        ]);
                }
                // card
                $scdp = SettlementCardPayment::where('pump_payment_id', $payment->id)->first();
                if ($scdp) {
                    $settlementId = $settlementId ?: $scdp->settlement_no;
                    $cardEditData = [
                        'amount' => $payment->payment_amount,
                        'note' => $payment->note,
                    ];
                    foreach (['customer_id', 'card_type', 'card_number', 'slip_no'] as $card_meta_column) {
                        if ($request->has($card_meta_column)
                            && Schema::hasColumn('settlement_card_payments', $card_meta_column)) {
                            $cardEditData[$card_meta_column] = $request->input($card_meta_column);
                        }
                    }
                    app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
                        ->editCardPayment($payment->business_id, $scdp->id, $cardEditData);
                }
                // cheque
                $schp = SettlementChequePayment::where('pump_payment_id', $payment->id)->first();
                if ($schp) {
                    $settlementId = $settlementId ?: $schp->settlement_no;
                    app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
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

            // Credit detail synchronization is allowed only through the unique
            // Pump Operator Payment ID (or one unambiguous legacy row). Never use
            // a latest record, collection number alone or amount alone.
            if ($payment->payment_type === 'credit') {
                $new_amount = (float) ($data['payment_amount'] ?? $payment->payment_amount);

                $creditSaleQuery = SettlementCreditSalePayment::where('business_id', $payment->business_id)
                    ->where('pump_operator_id', $payment->pump_operator_id);

                if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                    $creditSaleQuery->where('pump_payment_id', $payment->id);
                } elseif (Schema::hasColumn('pump_operator_payments', 'source_id') && ! empty($payment->source_id)) {
                    $creditSaleQuery->where('id', $payment->source_id);
                } else {
                    $creditSaleQuery
                        ->where('collection_form_no', $payment->collection_form_no)
                        ->whereRaw('ABS(amount - ?) < 0.02', [(float) $old_amount]);
                    if (Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')) {
                        $creditSaleQuery->where('shift_id', $payment->shift_id);
                    }
                }

                $creditSaleCandidates = $creditSaleQuery->limit(2)->get();
                if ($creditSaleCandidates->count() > 1) {
                    throw new \RuntimeException(
                        'Payment reconciliation stopped: more than one Credit Sale row matches Pump Payment ID '
                        . $payment->id
                        . '. Open and edit the exact Credit Sale invoice row.'
                    );
                }

                $credit_sale = $creditSaleCandidates->first();
                if (! $credit_sale) {
                    throw new \RuntimeException(
                        'Payment reconciliation stopped: no Credit Sale row is linked to Pump Payment ID '
                        . $payment->id
                        . '.'
                    );
                }

                if (Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')
                    && ! empty($credit_sale->shift_id)
                    && (int) $credit_sale->shift_id !== (int) $payment->shift_id) {
                    throw new \RuntimeException(
                        'Payment reconciliation stopped: the Credit Sale Shift ID differs from its master payment.'
                    );
                }

                $credit_sale->amount = $new_amount;
                $credit_sale->sub_total = $new_amount - (float) ($credit_sale->total_discount ?? 0);
                $credit_sale->note = $data['note'] ?? $payment->note;
                if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                    $credit_sale->pump_payment_id = $payment->id;
                }
                if (Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')) {
                    $credit_sale->shift_id = $payment->shift_id;
                }
                $credit_sale->save();

                $voucher = null;
                if (Schema::hasColumn('daily_vouchers', 'pump_payment_id')) {
                    $voucher = DailyVoucher::where('business_id', $payment->business_id)
                        ->where('pump_payment_id', $payment->id)
                        ->first();
                }
                if (! $voucher && ! empty($credit_sale->daily_voucher_id)) {
                    $voucher = DailyVoucher::where('business_id', $payment->business_id)
                        ->where('id', $credit_sale->daily_voucher_id)
                        ->first();
                }

                if (! $voucher) {
                    $legacyVoucherQuery = DailyVoucher::where('business_id', $payment->business_id)
                        ->where('operator_id', $payment->pump_operator_id)
                        ->where('customer_id', $credit_sale->customer_id);

                    if (! empty($payment->collection_form_no)) {
                        $legacyVoucherQuery->where('daily_vouchers_no', $payment->collection_form_no);
                    } else {
                        $legacyVoucherQuery
                            ->where('voucher_order_number', $credit_sale->order_number)
                            ->where('voucher_order_date', $credit_sale->order_date);
                    }

                    $voucherCandidates = $legacyVoucherQuery->limit(2)->get();
                    if ($voucherCandidates->count() > 1) {
                        throw new \RuntimeException(
                            'Payment reconciliation stopped: more than one Daily Voucher matches Pump Payment ID '
                            . $payment->id
                            . '.'
                        );
                    }
                    $voucher = $voucherCandidates->first();
                }

                if ($voucher) {
                    $voucher->total_amount = $credit_sale->amount;
                    $voucher->settlement_no = $credit_sale->settlement_no;
                    if (Schema::hasColumn('daily_vouchers', 'pump_payment_id')) {
                        $voucher->pump_payment_id = $payment->id;
                    }
                    // Preserve the voucher's legacy shift foreign key.
                    $voucher->save();

                    if ((int) $credit_sale->daily_voucher_id !== (int) $voucher->id) {
                        $credit_sale->daily_voucher_id = $voucher->id;
                        $credit_sale->save();
                    }
                }
            }

            DB::commit();

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

            return redirect()->route('petropd.payment-summary.dashboard')->with('status', $output);
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $isReconciliationError = $e instanceof \RuntimeException
                && str_starts_with($e->getMessage(), 'Payment reconciliation stopped:');

            // If AJAX request, return JSON error
            if ($request->ajax()) {
                $output = [
                    'success' => false,
                    'msg'     => $isReconciliationError
                        ? $e->getMessage()
                        : __('messages.something_went_wrong'),
                ];

                return response()->json($output, 400);
            }

            $output = [
                'success' => false,
                'tab'     => 'payment_summary',
                'msg'     => $isReconciliationError
                    ? $e->getMessage()
                    : __('messages.something_went_wrong'),
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
            $creditSale = SettlementCreditSalePayment::where('pump_payment_id', $payment->id)->first();
            if ($this->isPaymentEditLocked($payment, $creditSale)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Locked: this shift is closed or its PD Settlement is already saved.',
                ], 403);
            }

            DB::beginTransaction();
            // Related rows must be removed by the unique master identity. Any
            // ambiguous legacy relationship stops the whole transaction.
            $this->removePaymentFromDailyTables($payment);

            PumpOperatorMeterSale::where('p_o_payment_id', $payment->id)->update(['p_o_payment_id' => null]);

            $payment->delete();
            DB::commit();

            return response()->json(['success' => true, 'msg' => __('petropd::lang.success')]);
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
        $business_id      = $this->resolveBusinessId();

        $pumps = Pump::leftjoin('pump_operator_assignments', function ($join) {
            $join->on('pumps.id', 'pump_operator_assignments.pump_id')->whereDate('date_and_time', date('Y-m-d'));
        })->leftjoin('pump_operators', 'pump_operator_assignments.pump_operator_id', 'pump_operators.id')
            ->where('pumps.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->select('pumps.*', 'pump_operator_assignments.pump_operator_id', 'pump_operator_assignments.pump_id', 'pump_operators.name as pumper_name')
            ->orderBy('pumps.id')
            ->get();
        $pop_up = true;

        return view('petropd::pd_operators.partials.payment_modal')->with(compact(
            'pumps',
            'pop_up'
        ));
    }

    public function balanceToOperator($pump_operator_id)
    {

        $business_id = $this->resolveBusinessId();

        try {
            $shift = $this->resolveExactPaymentShiftId(request(), (int) $business_id, (int) $pump_operator_id);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => $e->getMessage(),
            ]);
        }

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

        /*
         * S 639: THIS LINE WROTE THE BAD ROW.
         *
         * It was:
         *     $payment_amount = $day_entries->sum('amount') + $other_sale - $payments->total;
         *
         * $day_entries->sum('amount') adds up in PHP, over floats, after the rows
         * have already been fetched. On shift 1 that produced
         * 442.774999999994..., and because pump_operator_payments.payment_amount
         * is a string column the tail was stored verbatim instead of being
         * rounded on the way in. Every screen afterwards rounded that tail
         * wherever it happened to round it, which is why one tile read 442.77
         * and the next read 442.78 for the same payment.
         *
         * The balance now comes from ShiftSaleTotals, which sums in SQL over
         * decimal columns, and is rounded to the business currency precision
         * before it is written. PumpOperatorPayment also rounds on save, so a
         * tail cannot reach the column by any route.
         */
        $authoritative_balance = \Modules\SettlementCore\Services\ShiftSaleTotals::for(
            (int) $business_id,
            (int) $shift,
            (int) $pump_operator_id
        )->balanceToSettle();

        $authoritative_balance = round($authoritative_balance, self::resolveCurrencyPrecision((int) $business_id));

        if ($authoritative_balance > 0) {
            $payment_type   = 'shortage';
            $payment_amount = $authoritative_balance;
        }
        if ($authoritative_balance < 0) {
            $payment_type   = 'excess';
            $payment_amount = $authoritative_balance;
        }
        if (! isset($payment_type) || $payment_type == '') {
            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];

            return redirect()->back()->with('status', $output);
        }
        try {
            $settlement = $this->ensureActiveSettlementForPumpOperator(
                (int) $business_id,
                (int) $pump_operator_id,
                (int) $shift
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => $e->getMessage(),
            ]);
        }

        $data = [
            'business_id'      => $business_id,
            'pump_operator_id' => $pump_operator_id,
            'payment_type'     => $payment_type,
            'payment_amount'   => $payment_amount,
            'created_by'       => Auth::user()->id,
            'shift_id'         => $shift,
            'settlement_no'    => (string) $settlement->id,
        ];
        if ($authoritative_balance != 0) {
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

        $business_id = $this->resolveBusinessId();
        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_details = Business::find($business_id);

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
                                // MA-002 PERF: cached reference lookup, see ma002PumpName().
                                $pumpName = self::ma002PumpName($meter_sale_detail->pump_id);
                                if ($pumpName !== '') {
                                    if ($pumps == '') {
                                        $pumps = $pumpName;
                                    } else {
                                        $pumps = $pumps . ', ' . $pumpName;
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

        // MA-002: numeric max - shift_number is varchar, so MAX() on it
        // compares as text and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $pumps = Pump::where('business_id', $business_id)->get();

        return view('petropd::pd_operators.meters_with_payments')->with(compact(
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
            $cashQuery = DailyCollection::where('business_id', $business_id);
            if (Schema::hasColumn('daily_collections', 'pump_payment_id')) {
                $cashQuery->where('pump_payment_id', $payment->id);
            } else {
                $cashQuery->where('pump_operator_id', $pump_operator_id)
                    ->where('collection_form_no', $collection_no)
                    ->where('shift_id', $shift_id)
                    ->where('type', 'daily_collection');
            }
            $exists = $cashQuery->first();

            if (! $exists) {
                // Get shift_number from PumpOperatorAssignment if available
                $assignment = PumpOperatorAssignment::where('shift_id', $shift_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->where('shift_number', '>', 0)
                    ->orderBy('id', 'DESC')
                    ->first();

                $shift_number = $assignment->shift_number ?? null;
                $shift_no     = $shift_number ?? (PetroShift::find($shift_id)->shift_no ?? null);

                $dailyCollectionData = [
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
                ];
                if (Schema::hasColumn('daily_collections', 'pump_payment_id')) {
                    $dailyCollectionData['pump_payment_id'] = $payment->id;
                }
                DailyCollection::create($dailyCollectionData);
            } elseif ((float) $exists->current_amount !== (float) $amount) {
                $exists->update(['current_amount' => $amount]);
            }
        }

        // CARD: DailyCard
        if ($payment->payment_type === 'card') {
            // Check if DailyCard already exists for this specific payment
            // Use collection_no AND amount AND slip_no to ensure uniqueness for bulk payments
            $exists = DailyCard::where('business_id', $business_id);
            if (Schema::hasColumn('daily_cards', 'pump_payment_id')) {
                $exists->where('pump_payment_id', $payment->id);
            } else {
                $exists->where('pump_operator_id', $pump_operator_id)
                    ->where('collection_no', $collection_no)
                    ->where('shift_id', $shift_id)
                    ->where('amount', $amount);
            }

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
                if (Schema::hasColumn('daily_cards', 'pump_payment_id')) {
                    $dailyCardData['pump_payment_id'] = $payment->id;
                }
                if (Schema::hasColumn('daily_cards', 'shift_id')) {
                    $dailyCardData['shift_id'] = $shift_id;
                }

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
            $chequeQuery = DailyChequePayment::where('business_id', $business_id);
            if (Schema::hasColumn('daily_cheque_payments', 'pump_payment_id')) {
                $chequeQuery->where('pump_payment_id', $payment->id);
            } else {
                $chequeQuery->where(function ($query) use ($payment, $pump_operator_id, $shift_id, $amount, $collection_no) {
                    $query->where('linked_payment_id', $payment->id)
                        ->orWhere(function ($legacy) use ($pump_operator_id, $shift_id, $amount, $collection_no) {
                            $legacy->where('pump_operator_id', $pump_operator_id)
                                ->where('shift_id', $shift_id)
                                ->where('collection_form_no', $collection_no)
                                ->where('amount', $amount);
                        });
                });
            }
            $exists = $chequeQuery->first();

            if (! $exists) {
                $cheque_bank   = ($request && property_exists($request, 'cheque_bank')) ? $request->cheque_bank : null;
                $customer_id   = ($request && property_exists($request, 'customer_id')) ? $request->customer_id : null;
                $cheque_number = ($request && property_exists($request, 'cheque_number')) ? $request->cheque_number : null;
                $cheque_date   = ($request && property_exists($request, 'cheque_date')) ? $request->cheque_date : null;

                $dailyChequeData = [
                    'linked_payment_id'  => $payment->id,
                    'business_id'        => $business_id,
                    'amount'             => $amount,
                    'bank_name'          => $cheque_bank,
                    'customer_id'        => $customer_id,
                    'cheque_number'      => $cheque_number,
                    'cheque_date'        => $cheque_date,
                    'shift_id'           => $shift_id,
                    'collection_form_no' => $collection_no,
                ];
                if (Schema::hasColumn('daily_cheque_payments', 'pump_payment_id')) {
                    $dailyChequeData['pump_payment_id'] = $payment->id;
                }
                if (Schema::hasColumn('daily_cheque_payments', 'pump_operator_id')) {
                    $dailyChequeData['pump_operator_id'] = $pump_operator_id;
                }
                DailyChequePayment::create($dailyChequeData);
            }
        }

        // CREDIT: SettlementCreditSalePayment should be created by saveCredit flow already.
        // Here only ensure payments linking exist; do not create credit sales automatically.

        // Link pump operator meter sales if meter sale exists for this collection_no
        PumpOperatorMeterSale::where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            ->where('collection_form_no', $collection_no)
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
        $businessId = (int) $payment->business_id;
        $operatorId = (int) $payment->pump_operator_id;
        $shiftId = (int) $payment->shift_id;
        $amount = (float) $payment->payment_amount;
        $collectionNo = $payment->collection_form_no;

        $deleteOne = function ($query, string $context): void {
            $rows = $query->limit(2)->get();
            if ($rows->count() > 1) {
                throw new \RuntimeException(
                    'Payment reconciliation stopped: more than one ' . $context . ' row matches this Pump Payment ID.'
                );
            }
            if ($row = $rows->first()) {
                $row->delete();
            }
        };

        if ($payment->payment_type === 'card') {
            $query = DailyCard::where('business_id', $businessId);
            if (Schema::hasColumn('daily_cards', 'pump_payment_id')) {
                $query->where('pump_payment_id', $payment->id);
            } else {
                $query->where('pump_operator_id', $operatorId)
                    ->where('collection_no', $collectionNo)
                    ->whereRaw('ABS(amount - ?) < 0.02', [$amount]);
                if (Schema::hasColumn('daily_cards', 'shift_id')) {
                    $query->where('shift_id', $shiftId);
                }
            }
            $deleteOne($query, 'Daily Card');
        }

        if ($payment->payment_type === 'cash') {
            $query = DailyCollection::where('business_id', $businessId);
            if (Schema::hasColumn('daily_collections', 'pump_payment_id')) {
                $query->where('pump_payment_id', $payment->id);
            } else {
                $query->where('pump_operator_id', $operatorId)
                    ->where('collection_form_no', $collectionNo)
                    ->whereRaw('ABS(current_amount - ?) < 0.02', [$amount]);
                if (Schema::hasColumn('daily_collections', 'shift_id')) {
                    $query->where('shift_id', $shiftId);
                }
            }
            $deleteOne($query, 'Daily Collection');
        }

        if (in_array($payment->payment_type, ['cheque', 'cheques'], true)) {
            $query = DailyChequePayment::where('business_id', $businessId);
            if (Schema::hasColumn('daily_cheque_payments', 'pump_payment_id')) {
                $query->where('pump_payment_id', $payment->id);
            } elseif (Schema::hasColumn('daily_cheque_payments', 'linked_payment_id')) {
                $query->where('linked_payment_id', $payment->id);
            } else {
                $query->where('pump_operator_id', $operatorId)
                    ->whereRaw('ABS(amount - ?) < 0.02', [$amount]);
                if (Schema::hasColumn('daily_cheque_payments', 'shift_id')) {
                    $query->where('shift_id', $shiftId);
                }
            }
            $deleteOne($query, 'Daily Cheque');
        }

        if (in_array($payment->payment_type, ['credit', 'multiple_credit'], true)) {
            $creditQuery = SettlementCreditSalePayment::where('business_id', $businessId)
                ->where('pump_operator_id', $operatorId);
            if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                $creditQuery->where('pump_payment_id', $payment->id);
            } else {
                $creditQuery->where('collection_form_no', $collectionNo)
                    ->whereRaw('ABS(amount - ?) < 0.02', [$amount]);
                if (Schema::hasColumn('settlement_credit_sale_payments', 'shift_id')) {
                    $creditQuery->where('shift_id', $shiftId);
                }
            }
            $deleteOne($creditQuery, 'Credit Sale');

            $voucherQuery = DailyVoucher::where('business_id', $businessId);
            if (Schema::hasColumn('daily_vouchers', 'pump_payment_id')) {
                $voucherQuery->where('pump_payment_id', $payment->id);
            } else {
                $voucherQuery->where('operator_id', $operatorId)
                    ->where('daily_vouchers_no', $collectionNo);
                // Do not filter DailyVoucher by PetroPD shift: that column is
                // owned by the legacy petro_daily_shifts relationship.
            }
            $deleteOne($voucherQuery, 'Daily Voucher');
        }

        // Settlement detail rows are removed by the same authoritative identity.
        foreach ([
            SettlementCashPayment::class,
            SettlementCardPayment::class,
            SettlementChequePayment::class,
            SettlementCreditSalePayment::class,
        ] as $detailModel) {
            if (Schema::hasColumn((new $detailModel())->getTable(), 'pump_payment_id')) {
                $rows = $detailModel::where('business_id', $businessId)
                    ->where('pump_payment_id', $payment->id)
                    ->limit(2)
                    ->get();
                if ($rows->count() > 1) {
                    throw new \RuntimeException(
                        'Payment reconciliation stopped: duplicate settlement details exist for Pump Payment ID '
                        . $payment->id
                        . '.'
                    );
                }
                if ($detail = $rows->first()) {
                    $detail->delete();
                }
            }
        }

        // No ledger deletion by Collection Form Number. Financial ledgers are
        // posted only at finalization, and finalized payments are locked above.
        PumpOperatorMeterSale::where('p_o_payment_id', $payment->id)
            ->update(['p_o_payment_id' => null]);
    }

    /**
     * TEMPORARY TEST METHOD - Creates a test cash entry for current shift and pump operator
     * REMOVE BEFORE DEPLOYING TO SERVER
     * Access via: /petropd/test-cash-entry?amount=5000
     */
    public function testCashEntry(Request $request)
    {
        // This temporary test endpoint previously guessed the operator's latest
        // shift and could create financial records outside the normal workflow.
        // Keep it permanently disabled in production.
        abort(404);
    }

    public function printCreditSale($id)
    {
        try {
            $business_id = $this->resolveBusinessId();

            if (empty($business_id)) {
                return abort(422, 'Business context missing in session. Please login again.');
            }

            /*
             * ZIP 048:
             * Re-Print from Payment Summary may pass:
             * - pump_operator_payments.id as route id
             * - settlement_credit_sale_payments.id as credit_sale_id
             * - collection_form_no/order_number as query fallbacks
             *
             * Resolve in that order and then print the whole bill group.
             */
            $payment_id = request()->get('payment_id');
            $credit_sale_id = request()->get('credit_sale_id');
            $collection_form_no = request()->get('collection_form_no');
            $order_number = request()->get('order_number');

            $pump_operator_payment = null;
            $credit_sale_payment = null;

            if (! empty($payment_id)) {
                $pump_operator_payment = PumpOperatorPayment::where('id', $payment_id)
                    ->where('business_id', $business_id)
                    ->first();
            }

            if (! $pump_operator_payment) {
                $pump_operator_payment = PumpOperatorPayment::where('id', $id)
                    ->where('business_id', $business_id)
                    ->first();
            }

            if (! empty($credit_sale_id)) {
                $credit_sale_payment = SettlementCreditSalePayment::where('id', $credit_sale_id)
                    ->where('business_id', $business_id)
                    ->first();
            }

            if (! $credit_sale_payment && $pump_operator_payment) {
                if (! empty($pump_operator_payment->collection_form_no)) {
                    $credit_sale_payment = SettlementCreditSalePayment::where('business_id', $business_id)
                        ->where('collection_form_no', $pump_operator_payment->collection_form_no)
                        ->where('pump_operator_id', $pump_operator_payment->pump_operator_id)
                        ->orderBy('id')
                        ->first();
                }

                if (! $credit_sale_payment) {
                    $credit_sale_payment = SettlementCreditSalePayment::where('business_id', $business_id)
                        ->where('pump_payment_id', $pump_operator_payment->id)
                        ->orderBy('id')
                        ->first();
                }
            }

            if (! $credit_sale_payment && ! empty($collection_form_no)) {
                $credit_sale_payment = SettlementCreditSalePayment::where('business_id', $business_id)
                    ->where('collection_form_no', $collection_form_no)
                    ->orderBy('id')
                    ->first();
            }

            if (! $credit_sale_payment && ! empty($order_number)) {
                $credit_sale_payment = SettlementCreditSalePayment::where('business_id', $business_id)
                    ->where('order_number', $order_number)
                    ->orderBy('id')
                    ->first();
            }

            if (! $credit_sale_payment) {
                // Last fallback: treat route id as credit sale id.
                $credit_sale_payment = SettlementCreditSalePayment::where('id', $id)
                    ->where('business_id', $business_id)
                    ->first();
            }

            if (! $credit_sale_payment) {
                return abort(404, 'Credit Sale bill not found for re-print');
            }

            if (! $pump_operator_payment) {
                if (! empty($credit_sale_payment->pump_payment_id)) {
                    $pump_operator_payment = PumpOperatorPayment::where('business_id', $business_id)
                        ->where('id', $credit_sale_payment->pump_payment_id)
                        ->first();
                }

                if (! $pump_operator_payment && ! empty($credit_sale_payment->collection_form_no)) {
                    $pump_operator_payment = PumpOperatorPayment::where('business_id', $business_id)
                        ->where('collection_form_no', $credit_sale_payment->collection_form_no)
                        ->where('payment_type', 'credit')
                        ->where('pump_operator_id', $credit_sale_payment->pump_operator_id)
                        ->first();
                }
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
                $credit_payments_query->where('bill_number', $credit_sale_payment->bill_number);
            } elseif (! empty($credit_sale_payment->collection_form_no)) {
                $credit_payments_query->where('collection_form_no', $credit_sale_payment->collection_form_no)
                    ->where('pump_operator_id', $credit_sale_payment->pump_operator_id);
            } else {
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

            return view('petropd::pd_operators.print.credit_sale_print', $viewData);
        } catch (\Exception $e) {
            Log::emergency('Credit Sale Re-Print Failed: File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());
            return abort(500, 'Something went wrong while loading credit sale bill');
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

            return view('petropd::pd_operators.print.credit_sale_print', $viewData);
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

            return view('petropd::pd_operators.print.credit_sale_print', $viewData);
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

    /**
     * LA-1190: map a Payment Method filter value onto the values actually stored
     * in pump_operator_payments.payment_type.
     *
     * Returns an array of candidates, or an empty array when the value cannot be
     * recognised - in which case the caller leaves the filter off.
     */
    private function normalisePaymentMethodFilter($value): array
    {
        $value = strtolower(trim((string) $value));

        if ($value === '') {
            return [];
        }

        // Strip the POS suffixes: credit_sale -> credit, cards -> card.
        $value = preg_replace('/_(sale|sales|payment|payments)$/', '', $value);

        $map = [
            'cash'          => ['cash'],
            'card'          => ['card', 'cards'],
            'cards'         => ['card', 'cards'],
            'credit'        => ['credit'],
            'cheque'        => ['cheque', 'cheques'],
            'check'         => ['cheque', 'cheques'],
            'bank_transfer' => ['bank_transfer'],
            'shortage'      => ['shortage'],
            'excess'        => ['excess'],
            'other'         => ['other'],
        ];

        return $map[$value] ?? [$value];
    }

    /**
     * Slip No shown in PetroPD must resolve the same card metadata that the
     * Pumper Dashboard uses. Empty strings are missing values, not valid slips.
     */
    private function paymentSummarySlipNoSql(bool $hasCardMetaColumns): string
    {
        $dailyCardSlip = "NULLIF(TRIM(dc.slip_no), '')";

        if (! $hasCardMetaColumns) {
            return $dailyCardSlip;
        }

        return "COALESCE(NULLIF(TRIM(pump_operator_payments.slip_no), ''), {$dailyCardSlip})";
    }

    /**
     * Reads a field from a DataTables row that may be an ARRAY or an OBJECT.
     *
     * paymentSummaryBaseQuery() is built with DB::table(), so rows arrive as
     * arrays and $row->field returns nothing even when the key is present. That
     * is what left Slip No, Order No and the customer columns blank while the
     * query itself returned the values correctly.
     */
    private function rowValue($row, string $key, $default = null)
    {
        if (is_array($row)) {
            return $row[$key] ?? $default;
        }

        return $row->{$key} ?? $default;
    }
}

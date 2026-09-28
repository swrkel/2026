<?php

namespace Modules\PetroGeneral\Http\Controllers;

;
use App\Store;
use App\Transaction;
use App\Business;
use Illuminate\Http\Request;
use Response;
use Illuminate\Routing\Controller;
use DB;
use Modules\PetroGeneral\Entities\DayEnd;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\Util;
use App\Utils\ProductUtil;
use App\Utils\ModuleUtil;
use App\Utils\TransactionUtil;
use App\Utils\NotificationUtil;
use App\Utils\BusinessUtil;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\MeterSale;
use Session;
use Spatie\Activitylog\Models\Activity;
use Modules\PetroGeneral\Entities\Settlement;
use Modules\PetroGeneral\Entities\PetroNotificationTemplate;
use Modules\PetroGeneral\Entities\PetroWhatsAppTemplate;

use Modules\PetroGeneral\Entities\PumpOperator;

class DayEndSettlementController extends Controller
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
     * Resolve the active business consistently for tenant and central-host businesses.
     * Petro General already supports both session keys elsewhere in the module.
     */
    private function resolveBusinessId(Request $request): int
    {
        // IS2348: on tenant requests user.business_id can still contain the
        // central-login business while business.id contains the active tenant
        // business.  Resolve against the *current database* and prefer the
        // explicit active-business session key used throughout Petro General.
        $candidates = [
            $request->session()->get('business.id'),
            optional($request->user())->business_id,
            $request->session()->get('user.business_id'),
        ];

        foreach (array_values(array_unique(array_filter(array_map('intval', $candidates)))) as $candidate) {
            try {
                if (Schema::hasTable('business') && DB::table('business')->where('id', $candidate)->exists()) {
                    return (int) $candidate;
                }
            } catch (\Throwable $exception) {
                Log::warning('PetroGeneral Day End: unable to validate business candidate.', [
                    'candidate' => $candidate,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        abort(403, 'Unable to resolve the active business for Day End Settlement.');
    }

    /**
     * Parse a date sent by the day-end screen into Y-m-d.
     * Falls back to Carbon when the value is not in the business date format.
     */
    private function resolveRequestDate($value): string
    {
        if (empty($value)) {
            return date('Y-m-d');
        }

        $value = trim((string) $value);

        // IS2348-B: ISO dates are unambiguous and must never be passed through
        // BusinessUtil/TransactionUtil date-format conversion first. The Day End
        // modal now sends this exact format in a hidden field.
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
            } catch (\Throwable $exception) {
                // Continue to the legacy parser for malformed values.
            }
        }

        try {
            $parsed = $this->transactionUtil->uf_date($value);
            if (!empty($parsed)) {
                return \Carbon\Carbon::parse($parsed)->format('Y-m-d');
            }
        } catch (\Throwable $exception) {
            // fall through to the generic parser below
        }

        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $exception) {
            return date('Y-m-d');
        }
    }

    /**
     * Use the ISO value emitted by the Petro General Day End form whenever it
     * is available. This keeps the Add form, AJAX pump lookup, duplicate check
     * and SMS summary on exactly the same calendar date regardless of the
     * business display date format.
     */
    private function submittedDayEndDate(Request $request): string
    {
        $iso = trim((string) $request->input('day_end_date_iso', ''));
        if ($iso !== '') {
            return $this->resolveRequestDate($iso);
        }

        return $this->resolveRequestDate($request->input('day_end_date'));
    }

    /**
     * Extract the shift ids owned by the selected settlements. PD child rows
     * can be linked by settlement_no, shift_id, or both depending on age of the
     * tenant database. Keeping the shift ids as a second authoritative key lets
     * Day End SMS totals work for both current and historical PD data.
     */
    private function dayEndSettlementShiftIds($settlements): array
    {
        $ids = [];

        foreach ($settlements as $settlement) {
            $value = $settlement->work_shift ?? [];

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                } else {
                    $value = preg_split('/\s*,\s*/', trim($value, "[] \t\n\r\0\x0B"), -1, PREG_SPLIT_NO_EMPTY);
                }
            }

            foreach ((array) $value as $id) {
                if (is_numeric($id) && (int) $id > 0) {
                    $ids[] = (int) $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * IS2345: Build the valid settlement references for a Day End date.
     *
     * Historical tenant databases store child-table settlement_no values in
     * either of these two forms:
     *   - settlements.id (numeric)
     *   - settlements.settlement_no (text, e.g. ST123 / PDST123)
     *
     * Keep both forms so the Day End SMS does not silently lose Meter Sales
     * or payment totals on older tenants.
     */
    private function dayEndSettlementReferences($settlements): array
    {
        $references = [];

        foreach ($settlements as $settlement) {
            if (!empty($settlement->id)) {
                $references[] = (string) $settlement->id;
            }

            if (!empty($settlement->settlement_no)) {
                $references[] = (string) $settlement->settlement_no;
            }
        }

        return array_values(array_unique(array_filter($references, function ($value) {
            return $value !== '';
        })));
    }

    /**
     * IS2345: Sum a settlement-linked table using both supported settlement
     * reference formats. The settlements themselves have already been scoped
     * to the active business and exact Day End date before these references
     * are created.
     */
    private function sumDayEndSettlementTable(
        string $table,
        int $businessId,
        array $references,
        string $amountColumn = 'amount',
        array $shiftIds = []
    ): float {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $amountColumn)) {
            return 0.0;
        }

        $canUseSettlement = !empty($references) && Schema::hasColumn($table, 'settlement_no');
        $canUseShift = !empty($shiftIds) && Schema::hasColumn($table, 'shift_id');

        if (! $canUseSettlement && ! $canUseShift) {
            return 0.0;
        }

        $query = DB::table($table);
        if (Schema::hasColumn($table, 'business_id')) {
            $query->where($table . '.business_id', $businessId);
        }

        $query->where(function ($scope) use ($table, $references, $shiftIds, $canUseSettlement, $canUseShift) {
            if ($canUseSettlement) {
                $scope->whereIn($table . '.settlement_no', $references);
            }

            if ($canUseShift) {
                if ($canUseSettlement) {
                    $scope->orWhereIn($table . '.shift_id', $shiftIds);
                } else {
                    $scope->whereIn($table . '.shift_id', $shiftIds);
                }
            }
        });

        return (float) $query->sum($table . '.' . $amountColumn);
    }

    /**
     * IS2326: pumps that already have a settlement on the given date, with the
     * settlement numbers they were sold in  =>  [pump_id => [settlement_no, ...]].
     *
     * meter_sales.settlement_no holds EITHER the numeric settlements.id OR the
     * settlement code (e.g. "ST12" / the PD settlement no) depending on which
     * screen wrote the row, so both must be matched. The old id-only join missed
     * every meter sale saved with the settlement code, so the pumps never showed
     * up for the selected day-end date.
     */
    private function pumpSettlementNumbersForDate(int $business_id, $date): array
    {
        $rows = collect();

        // Legacy/manual meter sales.
        if (Schema::hasTable('meter_sales')) {
            $rows = $rows->concat(
                DB::table('meter_sales')
                    ->join('settlements', function ($join) {
                        $join->whereRaw(
                            '(CAST(settlements.id AS CHAR) = CAST(meter_sales.settlement_no AS CHAR)'
                            . ' OR settlements.settlement_no = meter_sales.settlement_no)'
                        );
                    })
                    ->where('settlements.business_id', $business_id)
                    ->where('meter_sales.business_id', $business_id)
                    ->whereDate('settlements.transaction_date', $date)
                    ->whereNotNull('meter_sales.settlement_no')
                    ->where('meter_sales.settlement_no', '!=', '')
                    ->select('meter_sales.pump_id', 'settlements.settlement_no')
                    ->get()
            );
        }

        // Modern Petro PD meter sales. Day End previously ignored these rows,
        // which is why current PD settlements could show on settlement screens
        // but disappear from Day End/SMS.
        if (
            Schema::hasTable('pump_operator_meter_sales')
            && Schema::hasTable('pump_operator_meter_sale_details')
        ) {
            $rows = $rows->concat(
                DB::table('pump_operator_meter_sales as poms')
                    ->join('pump_operator_meter_sale_details as pomd', 'pomd.sale_id', '=', 'poms.id')
                    ->join('settlements', function ($join) {
                        $join->whereRaw(
                            '(CAST(settlements.id AS CHAR) = CAST(poms.settlement_no AS CHAR)'
                            . ' OR settlements.settlement_no = poms.settlement_no)'
                        );
                    })
                    ->where('settlements.business_id', $business_id)
                    ->where('poms.business_id', $business_id)
                    ->where('pomd.business_id', $business_id)
                    ->whereDate('settlements.transaction_date', $date)
                    ->whereNotNull('poms.settlement_no')
                    ->where('poms.settlement_no', '!=', '')
                    ->select('pomd.pump_id', 'settlements.settlement_no')
                    ->get()
            );
        }

        $map = [];
        foreach ($rows as $row) {
            $pumpId = (int) ($row->pump_id ?? 0);
            if ($pumpId <= 0) {
                continue;
            }

            $map[$pumpId] = $map[$pumpId] ?? [];
            if (!empty($row->settlement_no) && !in_array($row->settlement_no, $map[$pumpId], true)) {
                $map[$pumpId][] = $row->settlement_no;
            }
        }

        return $map;
    }

    /**
     * IS2348: send the Day End SMS with an explicit active business id.
     *
     * App\Utils\NotificationUtil::sendPetroNotification() re-resolves the
     * business from session('user.business_id') and imports the legacy Petro
     * template model. In a multi-database request that can point at a different
     * business than the Day End data we just calculated. Keep this path owned
     * by Petro General and send exactly the message prepared for $businessId.
     */
    private function sendDayEndNotificationForBusiness(int $businessId, array $smsData): void
    {
        $template = PetroNotificationTemplate::where('business_id', $businessId)
            ->where('template_for', 'day_end_settlement')
            ->where('auto_send_sms', 1)
            ->first();

        if (empty($template)) {
            $template = PetroWhatsAppTemplate::where('business_id', $businessId)
                ->where('template_for', 'day_end_settlement')
                ->where('auto_send_sms', 1)
                ->first();
        }

        if (empty($template)) {
            Log::info('PetroGeneral Day End: SMS skipped because no enabled template exists.', [
                'business_id' => $businessId,
            ]);
            return;
        }

        $business = Business::where('id', $businessId)->first();
        if (empty($business)) {
            Log::warning('PetroGeneral Day End: SMS skipped because business was not found.', [
                'business_id' => $businessId,
            ]);
            return;
        }

        $message = (string) $template->sms_body;
        foreach ($smsData as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $message = str_replace('{' . $key . '}', (string) ($value ?? ''), $message);
        }

        $smsSettings = empty($business->sms_settings)
            ? $this->businessUtil->defaultSmsSettings()
            : $business->sms_settings;

        $phones = [];
        if (!empty($template->phone_nos)) {
            $phones = explode(',', str_replace(' ', '', (string) $template->phone_nos));
        } elseif (!empty($business->sms_settings) && !empty($business->sms_settings['msg_phone_nos'])) {
            $phones = explode(',', str_replace(' ', '', (string) $business->sms_settings['msg_phone_nos']));
        }

        $phones = array_values(array_unique(array_filter(array_map('trim', $phones))));
        if (empty($phones)) {
            Log::info('PetroGeneral Day End: SMS skipped because no recipient phone is configured.', [
                'business_id' => $businessId,
            ]);
            return;
        }

        $payload = [
            'business_id' => $businessId,
            'sms_settings' => $smsSettings,
            'mobile_number' => implode(',', $phones),
            'sms_body' => $message,
        ];

        // BusinessUtil::sendSms is the same gateway used elsewhere in Petro,
        // but unlike sendPetroNotification it does not re-select the business.
        $this->businessUtil->sendSms($payload);
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    
    public function index(Request $request)
    {
        $business_id = $this->resolveBusinessId($request);

        if ($request->ajax()) {
            $settledByDate = [];
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            $query = DayEnd::query()
                ->leftJoin('users as created', 'created.id', '=', 'day_ends.created_by')
                ->leftJoin('users as editted', 'editted.id', '=', 'day_ends.updated_by')
                ->where('day_ends.business_id', $business_id)
                ->select([
                    'day_ends.*',
                    'created.username as user_added',
                    'editted.username as user_editted',
                ]);

            if (!empty($startDate) && !empty($endDate)) {
                $query->whereDate('day_ends.day_end_date', '>=', $startDate)
                    ->whereDate('day_ends.day_end_date', '<=', $endDate);
            }

            // Keep a deterministic server-side order. The old page attempted to
            // order by the generated Action column, which is not a DB column.
            $query->orderBy('day_ends.id', 'desc');

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">'
                        . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" '
                        . 'data-toggle="dropdown" aria-expanded="false">'
                        . __('messages.actions')
                        . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>'
                        . '</button><ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    if (auth()->user()->can('edit_day_end_settlement')) {
                        $html .= '<li><a href="#" data-href="'
                            . action("\\Modules\\PetroGeneral\\Http\\Controllers\\DayEndSettlementController@edit", [$row->id])
                            . '" class="edit_dip"><i class="fa fa-pencil-square-o"></i> '
                            . __('messages.edit') . '</a></li>';
                    }

                    $html .= '</ul></div>';
                    return $html;
                })
                ->editColumn('user_added', function ($row) {
                    return !empty($row->user_added) ? e($row->user_added) : '--';
                })
                ->editColumn('user_editted', function ($row) {
                    if (!empty($row->user_editted)) {
                        return e($row->user_editted) . '<br>' . $this->productUtil->format_date($row->updated_at, true);
                    }
                    return '--';
                })
                ->editColumn('pumps', function ($row) use ($business_id) {
                    $pumpIds = json_decode($row->pumps, true);
                    if (!is_array($pumpIds) || empty($pumpIds)) {
                        return '--';
                    }

                    $names = [];
                    foreach ($pumpIds as $pumpId) {
                        $pump = Pump::where('business_id', $business_id)->find($pumpId);
                        if (!empty($pump)) {
                            $names[] = e($pump->pump_name);
                        }
                    }

                    return !empty($names) ? implode('<br>', $names) : '--';
                })
                ->editColumn('sold_pumps', function ($row) use ($business_id, &$settledByDate) {
                    $pumpIds = json_decode($row->sold_pumps, true);
                    if (!is_array($pumpIds) || empty($pumpIds)) {
                        return '--';
                    }

                    $dateKey = date('Y-m-d', strtotime($row->day_end_date));
                    if (!isset($settledByDate[$dateKey])) {
                        $settledByDate[$dateKey] = $this->pumpSettlementNumbersForDate($business_id, $dateKey);
                    }

                    $items = [];
                    foreach ($pumpIds as $pumpId) {
                        // Historical day-end rows can reference a pump that was
                        // later deleted/deactivated. Never dereference it first.
                        $pump = Pump::where('business_id', $business_id)->find($pumpId);
                        if (empty($pump)) {
                            continue;
                        }

                        $settlements = $settledByDate[$dateKey][(int) $pump->id] ?? [];

                        $label = e($pump->pump_name);
                        if (!empty($settlements)) {
                            $label .= '(<b>' . e(implode(', ', $settlements)) . '</b>)';
                        }
                        $items[] = $label;
                    }

                    return !empty($items) ? implode('<br>', $items) : '--';
                })
                ->editColumn('created_at', '{{@format_datetime($created_at)}}')
                ->editColumn('day_end_date', '{{@format_date($day_end_date)}}')
                ->removeColumn('id')
                ->rawColumns(['action', 'user_editted', 'pumps', 'sold_pumps'])
                ->make(true);
        }

        return view('petrogeneral::petro_settings.index');
    }

    public function create(Request $request){
        $business_id = $this->resolveBusinessId($request);
        
        $date = date('Y-m-d'); // Use current date
        $pump_settlement_nos = $this->pumpSettlementNumbersForDate($business_id, $date);
        $isolate_pumps = array_keys($pump_settlement_nos);
        
        $pumps = Pump::where('business_id', $business_id)->whereNotIn('id',$isolate_pumps)->pluck('pump_name', 'id');
        
        $html = (string) view('petrogeneral::petro_settings.partials.pending_pumps')->with(compact(
                'pumps'
            ));
            
        $pumps_sold_list = Pump::where('business_id', $business_id)->whereIn('id',$isolate_pumps)->pluck('pump_name', 'id');
            
        $html_sold = (string) view('petrogeneral::petro_settings.partials.pumps_in_settlement')->with(compact(
                'pumps_sold_list','pump_settlement_nos','date'
            ));
        
        $pos_transactions = Transaction::where('business_id', $business_id)
            ->whereIn('type', ['sell', 'fpos_sale'])
            ->where('status', 'final')
            ->whereDate('transaction_date', $date)
            ->select('id', 'invoice_no', 'ref_no', 'final_total')
            ->orderBy('id', 'desc')
            ->get();

        $selected_pos_transaction_ids = $pos_transactions->pluck('id')->toArray();

        $total_pos_sales = $pos_transactions->sum('final_total');

        $total_pos_payments = DB::table('transaction_payments')
            ->whereIn('transaction_id', $selected_pos_transaction_ids)
            ->sum('amount');

        return view('petrogeneral::petro_settings.partials.add_day_end')->with(compact(
            'html',
            'html_sold',
            'total_pos_sales',
            'total_pos_payments',
            'pumps',
            'pos_transactions',
            'selected_pos_transaction_ids'
        ));
    }
    
    public function pendingPumps(Request $request){
        $date = $this->resolveRequestDate($request->date);
        
        $business_id = $this->resolveBusinessId($request);
        $pump_settlement_nos = $this->pumpSettlementNumbersForDate($business_id, $date);
        $isolate_pumps = array_keys($pump_settlement_nos);
        
        $pumps = Pump::where('business_id', $business_id)->whereNotIn('id',$isolate_pumps)->pluck('pump_name', 'id');
        
        $html = (string) view('petrogeneral::petro_settings.partials.pending_pumps')->with(compact(
                'pumps'
            ));
            
        $pumps_sold_list = Pump::where('business_id', $business_id)
            ->whereIn('id', $isolate_pumps)
            ->pluck('pump_name', 'id');

        $html_sold = (string) view('petrogeneral::petro_settings.partials.pumps_in_settlement')->with(compact(
                'pumps_sold_list', 'pump_settlement_nos', 'date'
            ));
        
        $pos_transactions = Transaction::where('business_id', $business_id)
            ->whereIn('type', ['sell', 'fpos_sale'])
            ->where('status', 'final')
            ->whereDate('transaction_date', $date)
            ->select('id', 'invoice_no', 'ref_no', 'final_total')
            ->orderBy('id', 'desc')
            ->get();

        $selected_pos_transaction_ids = $pos_transactions->pluck('id')->toArray();

        $pos_transactions_options = (string) view('petrogeneral::petro_settings.partials.pos_transactions_options')
            ->with(compact('pos_transactions', 'selected_pos_transaction_ids'));

        $total_pos_sales = $pos_transactions->sum('final_total');

        $total_pos_payments = DB::table('transaction_payments')
            ->whereIn('transaction_id', $selected_pos_transaction_ids)
            ->sum('amount');

        return array(
            'pending' => $html,
            'sold' => $html_sold,
            'total_pos_payments' => number_format($total_pos_payments, 2),
            'total_pos_sales' => number_format($total_pos_sales, 2),
            'pos_transactions_options' => $pos_transactions_options,
            'selected_pos_transaction_ids' => $selected_pos_transaction_ids
        );
    }

    public function posTotals(Request $request)
    {
        $business_id = $this->resolveBusinessId($request);

        $date = $this->resolveRequestDate($request->date);

        $selected_ids = $request->pos_transaction_ids;
        if (empty($selected_ids) || !is_array($selected_ids)) {
            $selected_ids = Transaction::where('business_id', $business_id)
                ->whereIn('type', ['sell', 'fpos_sale'])
                ->where('status', 'final')
                ->whereDate('transaction_date', $date)
                ->pluck('id')
                ->toArray();
        }

        $total_pos_sales = Transaction::where('business_id', $business_id)
            ->whereIn('id', $selected_ids)
            ->sum('final_total');

        $total_pos_payments = DB::table('transaction_payments')
            ->whereIn('transaction_id', $selected_ids)
            ->sum('amount');

        return [
            'total_pos_payments' => number_format($total_pos_payments, 2),
            'total_pos_sales' => number_format($total_pos_sales, 2),
        ];
    }
    

        public function store(Request $request)
    {
        try {

            $business_id = $this->resolveBusinessId($request);
            $dayEndDate = $this->submittedDayEndDate($request);

            Log::info('PetroGeneral Day End selected date resolved.', [
                'business_id' => $business_id,
                'display_date' => $request->input('day_end_date'),
                'iso_date' => $request->input('day_end_date_iso'),
                'resolved_date' => $dayEndDate,
            ]);

            $selected_ids = $request->pos_transaction_ids;
            if (empty($selected_ids) || !is_array($selected_ids)) {
                $selected_ids = Transaction::where('business_id', $business_id)
                    ->whereIn('type', ['sell', 'fpos_sale'])
                    ->where('status', 'final')
                    ->whereDate('transaction_date', $dayEndDate)
                    ->pluck('id')
                    ->toArray();
            }

            $total_pos_sales_today = Transaction::where('business_id', $business_id)
                ->whereIn('type', ['sell', 'fpos_sale'])
                ->where('status', 'final')
                ->whereIn('id', $selected_ids)
                ->sum('final_total');

            $total_pos_cash_amount_today = DB::table('transaction_payments')
                ->join('transactions', 'transactions.id', '=', 'transaction_payments.transaction_id')
                ->where('transactions.business_id', $business_id)
                ->whereIn('transactions.type', ['sell', 'fpos_sale'])
                ->where('transactions.status', 'final')
                ->whereIn('transaction_payments.transaction_id', $selected_ids)
                ->where('transaction_payments.method', 'cash')
                ->sum('transaction_payments.amount');

            $data = array('business_id' => $business_id,
                'day_end_date' => $dayEndDate,
                'pumps' => json_encode($request->pumps),
                'sold_pumps' => $request->sold_pumps,
                'pos_transaction_ids' => json_encode($selected_ids),
                'created_by' =>auth()->user()->id ,
            );

            // IS2348-B: Day End is unique only for the exact selected calendar
            // date. A Day End completed for 2026-09-01 must not block 09-02,
            // 09-03, etc. The former "latest date >= selected date" rule made
            // one Day End act as a permanent/global lock for other dates.
            $alreadyDoneForSelectedDate = DayEnd::where('business_id', $business_id)
                ->whereDate('day_end_date', $dayEndDate)
                ->exists();

            if ($alreadyDoneForSelectedDate) {
                return redirect()->back()->with('status', [
                    'success' => false,
                    'msg' => __('petrogeneral::lang.day_end_already_done_for_selected_date'),
                ]);
            }

            DayEnd::create($data);


            // IS2348: build the SMS from the same Day End settlement/pump
            // authority used by the Add form, and support both legacy meter_sales
            // and modern Petro PD pump_operator_meter_sales.
            $pump_settlement_nos = $this->pumpSettlementNumbersForDate(
                $business_id,
                $data['day_end_date']
            );

            $form_settlement_nos = collect($pump_settlement_nos)
                ->flatten()
                ->filter()
                ->map(function ($value) { return (string) $value; })
                ->unique()
                ->values()
                ->all();

            $settlements = Settlement::where('settlements.business_id', $business_id)
                ->where(function ($query) use ($data, $form_settlement_nos) {
                    $query->whereDate('settlements.transaction_date', $data['day_end_date']);

                    // The Add form already resolved these settlement numbers from
                    // the selected Day End date. Keeping them as an OR fallback
                    // prevents a tenant-specific date representation from making
                    // the SMS summary empty while the form itself shows pumps.
                    if (!empty($form_settlement_nos)) {
                        $query->orWhereIn('settlements.settlement_no', $form_settlement_nos);
                    }
                })
                ->select([
                    'settlements.id',
                    'settlements.settlement_no',
                    'settlements.pump_operator_id',
                    'settlements.transaction_date',
                    'settlements.work_shift',
                    'settlements.total_amount',
                ])
                ->get();

            $settlement_references = $this->dayEndSettlementReferences($settlements);
            $settlement_shift_ids = $this->dayEndSettlementShiftIds($settlements);

            // Legacy/manual meter sales can be linked by settlement number/id
            // or by the exact PD shift. Use both authorities so old tenant data
            // is not lost while remaining strictly inside this Day End date.
            if (Schema::hasTable('meter_sales') && (!empty($settlement_references) || !empty($settlement_shift_ids))) {
                $meter_sales_query = DB::table('meter_sales')
                    ->where('meter_sales.business_id', $business_id)
                    ->where(function ($query) use ($settlement_references, $settlement_shift_ids) {
                        $hasSettlement = !empty($settlement_references);
                        if ($hasSettlement) {
                            $query->whereIn('meter_sales.settlement_no', $settlement_references);
                        }
                        if (!empty($settlement_shift_ids) && Schema::hasColumn('meter_sales', 'shift_id')) {
                            if ($hasSettlement) {
                                $query->orWhereIn('meter_sales.shift_id', $settlement_shift_ids);
                            } else {
                                $query->whereIn('meter_sales.shift_id', $settlement_shift_ids);
                            }
                        }
                    });

                $meter_sales = $meter_sales_query
                    ->select(['meter_sales.id', 'meter_sales.pump_id', 'meter_sales.sub_total'])
                    ->get();
            } else {
                $meter_sales = collect();
            }

            // Modern PD meter sales are stored in pump_operator_meter_sales and
            // their pump ids live in pump_operator_meter_sale_details. Current
            // tenants frequently have shift_id populated before/without a legacy
            // settlement reference, so shift_id is an equally valid link here.
            if (Schema::hasTable('pump_operator_meter_sales') && (!empty($settlement_references) || !empty($settlement_shift_ids))) {
                $pd_meter_sales_query = DB::table('pump_operator_meter_sales')
                    ->where('pump_operator_meter_sales.business_id', $business_id)
                    ->where(function ($query) use ($settlement_references, $settlement_shift_ids) {
                        $hasSettlement = !empty($settlement_references);
                        if ($hasSettlement) {
                            $query->whereIn('pump_operator_meter_sales.settlement_no', $settlement_references);
                        }
                        if (!empty($settlement_shift_ids) && Schema::hasColumn('pump_operator_meter_sales', 'shift_id')) {
                            if ($hasSettlement) {
                                $query->orWhereIn('pump_operator_meter_sales.shift_id', $settlement_shift_ids);
                            } else {
                                $query->whereIn('pump_operator_meter_sales.shift_id', $settlement_shift_ids);
                            }
                        }
                    });

                $pd_meter_sales = $pd_meter_sales_query
                    ->select([
                        'pump_operator_meter_sales.id',
                        'pump_operator_meter_sales.amount',
                        'pump_operator_meter_sales.balance',
                    ])
                    ->get();
            } else {
                $pd_meter_sales = collect();
            }

            $pd_meter_sale_ids = $pd_meter_sales->pluck('id')
                ->filter()
                ->map(function ($id) { return (int) $id; })
                ->unique()
                ->values()
                ->all();

            $pd_pump_ids = empty($pd_meter_sale_ids) || !Schema::hasTable('pump_operator_meter_sale_details')
                ? []
                : DB::table('pump_operator_meter_sale_details')
                    ->where('business_id', $business_id)
                    ->whereIn('sale_id', $pd_meter_sale_ids)
                    ->pluck('pump_id')
                    ->filter()
                    ->map(function ($id) { return (int) $id; })
                    ->unique()
                    ->values()
                    ->all();

            $other_sales_total = 0.0;
            if (!empty($settlement_references) && Schema::hasTable('other_sales')) {
                $other_sales_total += (float) DB::table('other_sales')
                    ->where('other_sales.business_id', $business_id)
                    ->whereIn('other_sales.settlement_no', $settlement_references)
                    ->sum('other_sales.sub_total');
            }

            // PD/Pumper Dashboard other sales are shift-linked and do not carry
            // settlement_no in current schemas. They are part of the same Day End
            // sale amount and must therefore be included from the selected shifts.
            if (!empty($settlement_shift_ids) && Schema::hasTable('pump_operator_other_sales')) {
                $other_sales_total += (float) DB::table('pump_operator_other_sales')
                    ->where('pump_operator_other_sales.business_id', $business_id)
                    ->whereIn('pump_operator_other_sales.shift_id', $settlement_shift_ids)
                    ->sum('pump_operator_other_sales.sub_total');
            }

            $meter_sales_total = (float) $meter_sales->sum('sub_total');
            $pd_meter_sales_total = (float) $pd_meter_sales->sum(function ($sale) {
                return (float) ($sale->amount ?? $sale->balance ?? 0);
            });
            $total_sale = $meter_sales_total + $pd_meter_sales_total + $other_sales_total;

            // A finalized settlement already carries its confirmed sale total.
            // Keep this as the last-resort fallback for old tenant records whose
            // child Meter Sale rows were archived/relinked after finalization.
            if ((float) $total_sale === 0.0 && $settlements->isNotEmpty()) {
                $total_sale = (float) $settlements->sum(function ($settlement) {
                    $value = preg_replace('/[^0-9.\-]/', '', (string) ($settlement->total_amount ?? 0));
                    return is_numeric($value) ? (float) $value : 0.0;
                });
            }

            // Payment rows are scoped by both active business and settlement
            // references. This prevents matching ids/codes in another business
            // from leaking into the Day End totals.
            $total_cash = $this->sumDayEndSettlementTable('settlement_cash_payments', $business_id, $settlement_references, 'amount', $settlement_shift_ids);
            $total_cards = $this->sumDayEndSettlementTable('settlement_card_payments', $business_id, $settlement_references, 'amount', $settlement_shift_ids);
            $total_credit_sales = $this->sumDayEndSettlementTable('settlement_credit_sale_payments', $business_id, $settlement_references, 'amount', $settlement_shift_ids);
            $total_short = $this->sumDayEndSettlementTable('settlement_shortage_payments', $business_id, $settlement_references, 'amount', $settlement_shift_ids);
            $total_loans = $this->sumDayEndSettlementTable('settlement_customer_loans', $business_id, $settlement_references, 'amount', $settlement_shift_ids);
            $total_cheques = $this->sumDayEndSettlementTable('settlement_cheque_payments', $business_id, $settlement_references, 'amount', $settlement_shift_ids);
            $cash_deposit = $this->sumDayEndSettlementTable('settlement_cash_deposits', $business_id, $settlement_references, 'amount', $settlement_shift_ids);
            $total_expenses = $this->sumDayEndSettlementTable('settlement_expense_payments', $business_id, $settlement_references, 'amount', $settlement_shift_ids);
            $total_excess = $this->sumDayEndSettlementTable('settlement_excess_payments', $business_id, $settlement_references, 'amount', $settlement_shift_ids);
            $loan_payments = $this->sumDayEndSettlementTable('settlement_loan_payments', $business_id, $settlement_references, 'amount', $settlement_shift_ids);
            $owners_drawings = $this->sumDayEndSettlementTable('settlement_drawing_payments', $business_id, $settlement_references, 'amount', $settlement_shift_ids);

            $request_sold_pumps = json_decode((string) $request->sold_pumps, true);
            if (!is_array($request_sold_pumps)) {
                $request_sold_pumps = [];
            }

            $shiftAssignments = collect();
            if (!empty($settlement_shift_ids) && Schema::hasTable('pump_operator_assignments')) {
                $shiftAssignments = DB::table('pump_operator_assignments')
                    ->where('business_id', $business_id)
                    ->whereIn('shift_id', $settlement_shift_ids)
                    ->select(['pump_id', 'pump_operator_id'])
                    ->get();
            }

            $pump_ids = collect($meter_sales->pluck('pump_id')->all())
                ->merge($pd_pump_ids)
                ->merge(array_keys($pump_settlement_nos))
                ->merge($request_sold_pumps)
                ->merge($shiftAssignments->pluck('pump_id')->all())
                ->filter()
                ->map(function ($id) { return (int) $id; })
                ->unique()
                ->values()
                ->all();

            $meter_sales_id = $meter_sales->pluck('id')
                ->filter()
                ->map(function ($id) { return (int) $id; })
                ->unique()
                ->values()
                ->all();

            $pumpers_id = $settlements->pluck('pump_operator_id')
                ->merge($shiftAssignments->pluck('pump_operator_id'))
                ->filter()
                ->map(function ($id) { return (int) $id; })
                ->unique()
                ->values()
                ->all();

            $pumps = Pump::where('business_id', $business_id)
                ->whereIn('id', $pump_ids)
                ->orderBy('id')
                ->pluck('pump_name')
                ->filter()
                ->values()
                ->toArray();

            $pump_operator = PumpOperator::where('business_id', $business_id)
                ->whereIn('id', $pumpers_id)
                ->orderBy('id')
                ->pluck('name')
                ->filter()
                ->values()
                ->toArray();

            // Historical settlement snapshots are an additional safe fallback
            // for totals/names that were already confirmed at finalization time.
            // Use them only when the live linked rows are empty/zero.
            if (Schema::hasTable('settlement_edit_history') && $settlements->isNotEmpty()) {
                $history = DB::table('settlement_edit_history')
                    ->whereIn('settlement_id', $settlements->pluck('id')->all())
                    ->get();

                $historyNumber = function ($column) use ($history) {
                    return (float) $history->sum(function ($row) use ($column) {
                        if (!isset($row->{$column})) {
                            return 0;
                        }

                        $value = preg_replace('/[^0-9.\-]/', '', (string) $row->{$column});
                        return is_numeric($value) ? (float) $value : 0;
                    });
                };

                if ($total_sale == 0.0 && Schema::hasColumn('settlement_edit_history', 'total_sale_amount')) {
                    $total_sale = $historyNumber('total_sale_amount');
                }
                if ($total_cash == 0.0 && Schema::hasColumn('settlement_edit_history', 'total_cash')) {
                    $total_cash = $historyNumber('total_cash');
                }
                if ($total_cards == 0.0 && Schema::hasColumn('settlement_edit_history', 'total_cards')) {
                    $total_cards = $historyNumber('total_cards');
                }
                if ($total_credit_sales == 0.0 && Schema::hasColumn('settlement_edit_history', 'total_credit_sales')) {
                    $total_credit_sales = $historyNumber('total_credit_sales');
                }
                if ($total_short == 0.0 && Schema::hasColumn('settlement_edit_history', 'total_short')) {
                    $total_short = $historyNumber('total_short');
                }
                if ($total_loans == 0.0 && Schema::hasColumn('settlement_edit_history', 'total_loans')) {
                    $total_loans = $historyNumber('total_loans');
                }
                if ($total_cheques == 0.0 && Schema::hasColumn('settlement_edit_history', 'total_cheques')) {
                    $total_cheques = $historyNumber('total_cheques');
                }

                if (empty($pump_operator) && Schema::hasColumn('settlement_edit_history', 'pump_operator_name')) {
                    $pump_operator = $history->pluck('pump_operator_name')
                        ->filter()
                        ->map(function ($value) { return trim((string) $value); })
                        ->unique()
                        ->values()
                        ->all();
                }

                if (empty($pumps) && Schema::hasColumn('settlement_edit_history', 'settlement_pumps')) {
                    $pumps = $history->pluck('settlement_pumps')
                        ->filter()
                        ->flatMap(function ($value) {
                            return preg_split('/\s*,\s*/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
                        })
                        ->map(function ($value) { return trim((string) $value); })
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                }
            }

            Log::info('PetroGeneral Day End SMS summary resolved.', [
                'business_id' => $business_id,
                'day_end_date' => $data['day_end_date'],
                'settlement_count' => $settlements->count(),
                'settlement_references' => $settlement_references,
                'settlement_shift_ids' => $settlement_shift_ids,
                'regular_meter_sales_count' => $meter_sales->count(),
                'pd_meter_sales_count' => $pd_meter_sales->count(),
                'pumpers' => $pump_operator,
                'pumps' => $pumps,
                'total_sale' => $total_sale,
                'total_cash' => $total_cash,
                'total_cheques' => $total_cheques,
                'total_credit_sales' => $total_credit_sales,
                'total_short' => $total_short,
                'total_excess' => $total_excess,
                'total_expenses' => $total_expenses,
                'loan_payments' => $loan_payments,
                'owners_drawings' => $owners_drawings,
            ]);

            $dip_readings = DB::table('dip_readings')
                ->leftJoin('fuel_tanks', 'dip_readings.tank_id', '=', 'fuel_tanks.id')
                ->leftJoin('products', 'fuel_tanks.product_id', '=', 'products.id')
                ->where('dip_readings.business_id', $business_id)
                ->whereRaw("STR_TO_DATE(date_and_time, '%m/%d/%Y') = ?", [$data['day_end_date']])
                ->select([
                    'dip_readings.*',
                    'fuel_tanks.fuel_tank_number as tank_name',
                    'products.name as product_name',
                    'products.id as productID'
                ])
                ->get();

            $tank_qty_diff = '';
            foreach($dip_readings as $one){
                $diff = $this->productUtil->num_f($one->fuel_balance_dip_reading - $one->current_qty);
                $tank_qty_diff .= PHP_EOL.$one->tank_name." (".$one->product_name.") ---> ".$diff;
            }

            $tank_summary = DB::table('fuel_tanks')
                ->select('fuel_tanks.*',
                    'products.name as product_name','products.id as product_id')
                ->leftJoin('products', 'fuel_tanks.product_id', '=', 'products.id')
                ->leftjoin('business_locations','business_locations.id','fuel_tanks.location_id')
                ->where('fuel_tanks.business_id', $business_id)->select('fuel_tanks.*','business_locations.name as location_name','products.name as product_name')->get();

            $prod_summary = '';
            foreach($tank_summary as $tank){
                $date_obj = \Carbon::parse($data['day_end_date']);
                $tank->start_date = $date_obj;
                $tank->end_date = $date_obj->endOfDay();

                $tank->starting_qty = $this->transactionUtil->num_f($this->transactionUtil->getTankBalanceByDate($tank->id,$tank->start_date));
                $tank->sold_qty = $this->transactionUtil->num_f($this->transactionUtil->__totalSellAndTransferOut($business_id,$tank->start_date,$tank->end_date,$tank->id));
                $tank->purchase_qty = $this->transactionUtil->num_f($this->transactionUtil->__totalPurchaseAndTransferIn($business_id,$tank->start_date,$tank->end_date,$tank->id));
                $tank->balance_qty = $this->transactionUtil->num_f($this->transactionUtil->getTankBalanceByDateInclude($tank->id,$tank->end_date));

                $prod_summary .= PHP_EOL.$tank->fuel_tank_number." (".$tank->product_name.") [Starting Qty = ".$tank->starting_qty.", Received Qty = ".$tank->purchase_qty.", Sold Qty = ".$tank->sold_qty.", Balance Qty = ".$tank->balance_qty."]";
            }

            $bulk_sales = MeterSale::leftJoin('pumps', 'pumps.id', '=', 'meter_sales.pump_id')
                ->leftJoin('products', 'products.id', '=', 'meter_sales.product_id')
                ->whereIn('meter_sales.id', $meter_sales_id)
                ->where('pumps.bulk_sale_meter', '>', 0)
                ->select(
                    'products.name',
                    DB::raw('SUM(meter_sales.closing_meter - meter_sales.starting_meter - meter_sales.testing_qty) as total_sales')
                )
                ->groupBy('meter_sales.product_id')
                ->get();

            $product_sales = MeterSale::leftJoin('pumps', 'pumps.id', '=', 'meter_sales.pump_id')
                ->leftJoin('products', 'products.id', '=', 'meter_sales.product_id')
                ->whereIn('meter_sales.id', $meter_sales_id)
                ->where('pumps.bulk_sale_meter', 0)
                ->select(
                    'products.name',
                    DB::raw('SUM(meter_sales.closing_meter - meter_sales.starting_meter - meter_sales.testing_qty) as total_sales')
                )
                ->groupBy('meter_sales.product_id')
                ->get();

            $psale_html = "";
            foreach($product_sales as $psale){
                $psale_html .= $psale->name." Sold Qty ".$this->transactionUtil->num_f($psale->total_sales)."lts".PHP_EOL;
            }

            $pbulk_html = "";
            foreach($bulk_sales as $pbulk){
                $pbulk_html .= $pbulk->name." Sold Qty ".$this->transactionUtil->num_f($pbulk->total_sales)."lts".PHP_EOL;
            }



            $sms_data = array(
                'date' => $request->day_end_date,
                'settlement_date' => $request->day_end_date,
                'total_sale' => $this->transactionUtil->num_f($total_sale),
                'total_sale_amount' => $this->transactionUtil->num_f($total_sale),
                'total_pos_amount_today' => $this->transactionUtil->num_f($total_pos_sales_today),
                'total_pos_cash_amount_today' => $this->transactionUtil->num_f($total_pos_cash_amount_today),
                'total_pos_cash_sales_today' => $this->transactionUtil->num_f($total_pos_cash_amount_today),
                'total_pos_sales' => $this->transactionUtil->num_f($total_pos_sales_today),
                'total_pos_sales_today' => $this->transactionUtil->num_f($total_pos_sales_today),
                'pumpers_worked' => implode(',',$pump_operator),
                'pump_operator_name' => implode(',',$pump_operator),
                'pumps' => implode(',',$pumps),
                'settlement_pumps' => implode(',',$pumps),
                'total_cash' => $this->transactionUtil->num_f($total_cash),
                'total_cards' => $this->transactionUtil->num_f($total_cards),
                'total_credit_sales' => $this->transactionUtil->num_f($total_credit_sales),
                'total_short' => $this->transactionUtil->num_f($total_short),
                'total_loans' => $this->transactionUtil->num_f($total_loans),
                'total_cheques' => $this->transactionUtil->num_f($total_cheques),

                'cash_deposit' => $this->transactionUtil->num_f($cash_deposit),
                'total_expenses' => $this->transactionUtil->num_f($total_expenses),
                'total_excess' => $this->transactionUtil->num_f($total_excess),
                'loan_payments' => $this->transactionUtil->num_f($loan_payments),
                'owners_drawings' => $this->transactionUtil->num_f($owners_drawings),

                'tank_product_qty_difference' => $tank_qty_diff,
                'fuel_category_products' => $prod_summary,
                'product_sold_qty' => $psale_html,
                'bulk_sale_qty' => $pbulk_html
            );

            $this->sendDayEndNotificationForBusiness($business_id, $sms_data);


            $output = [
                'success' => true,
                'msg' => __('petrogeneral::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status',$output);
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    
    public function show()
    {
        return view('petrogeneral::show');
    }
    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    
    public function edit($id)
    {
        $business_id = $this->resolveBusinessId(request());
        
        $day_end = DayEnd::findOrFail($id);
        $pumps_ = Pump::whereIn('id',json_decode($day_end->pumps,true) ?: [])->pluck('pump_name','id');
        
        $pumps = Pump::where('business_id', $business_id)->whereIn('id',json_decode($day_end->sold_pumps,true) ?: [])->pluck('pump_name', 'id');
        $date = $day_end->day_end_date;

        $pos_transactions = Transaction::where('business_id', $business_id)
            ->whereIn('type', ['sell', 'fpos_sale'])
            ->where('status', 'final')
            ->whereDate('transaction_date', $date)
            ->select('id', 'invoice_no', 'ref_no', 'final_total')
            ->orderBy('id', 'desc')
            ->get();

        $selected_pos_transaction_ids = json_decode($day_end->pos_transaction_ids, true);
        if (empty($selected_pos_transaction_ids) || !is_array($selected_pos_transaction_ids)) {
            $selected_pos_transaction_ids = $pos_transactions->pluck('id')->toArray();
        }

        $total_pos_sales = Transaction::where('business_id', $business_id)
            ->whereIn('id', $selected_pos_transaction_ids)
            ->sum('final_total');

        $total_pos_payments = DB::table('transaction_payments')
            ->whereIn('transaction_id', $selected_pos_transaction_ids)
            ->sum('amount');
            
        $pumps_sold_list = $pumps;
        $pump_settlement_nos = $this->pumpSettlementNumbersForDate($business_id, $date);
        $html_sold = (string) view('petrogeneral::petro_settings.partials.pumps_in_settlement')->with(compact(
                'pumps_sold_list','pump_settlement_nos','date'
            ));
        
        return view('petrogeneral::petro_settings.partials.edit_day_end')->with(compact(
            'pumps_',
            'day_end',
            'html_sold',
            'total_pos_sales',
            'total_pos_payments',
            'pos_transactions',
            'selected_pos_transaction_ids'
        ));

    }
    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    
    public function update(Request $request, $id)
    {
        try {
            
            $business_id = $this->resolveBusinessId($request);
            $day_end = DayEnd::where('business_id', $business_id)->findOrFail($id);
            $dayEndDate = $this->submittedDayEndDate($request);

            $duplicateForSelectedDate = DayEnd::where('business_id', $business_id)
                ->whereDate('day_end_date', $dayEndDate)
                ->where('id', '!=', $id)
                ->exists();

            if ($duplicateForSelectedDate) {
                return redirect()->back()->with('status', [
                    'success' => false,
                    'msg' => __('petrogeneral::lang.day_end_already_done_for_selected_date'),
                ]);
            }
            
            $changed = "";
            if(date('Y-m-d',strtotime($day_end->day_end_date)) != $dayEndDate){
                $changed .= "Day end date changed from ".$this->productUtil->format_date($day_end->day_end_date)." to ".$request->day_end_date;
            }
            
           
            
            if(!empty($changed)){
                $changed .= PHP_EOL."By ".auth()->user()->username;
                $changed .= PHP_EOL."At ".$this->productUtil->format_date(date('Y-m-d H:i'),true);
                
                $activity = new Activity();
                $activity->log_name = "Day End Settlement";
                $activity->description = "update";
                $activity->subject_id = $id;
                $activity->subject_type = "Modules\PetroGeneral\Entities\DayEnd";
                $activity->causer_id = auth()->user()->id;
                $activity->causer_type = 'App\User';
                $activity->properties = $changed;
                $activity->created_at = date('Y-m-d H:i');
                $activity->updated_at = date('Y-m-d H:i');
                $activity->save();
            }
            
            $data = array('business_id' => $business_id,
                'day_end_date' => $dayEndDate,
                'pumps' => json_encode($request->pumps),
                'pos_transaction_ids' => !empty($request->pos_transaction_ids) ? json_encode($request->pos_transaction_ids) : null,
                'updated_by' =>auth()->user()->id ,
            );
            
            DayEnd::where('id',$id)->update($data);
            $output = [
                'success' => true,
                'msg' => __('petrogeneral::lang.success')
            ];
        } catch (\Exception $e) {
            logger($e);
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status',$output);
    }
    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    
    public function destroy()
    {
    }
}

<?php
namespace Modules\DailyCollectionSW\Http\Controllers;

use App\Account;
use App\AccountGroup;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\CustomerReference;
use App\Product;
use Carbon\Carbon;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Petro\Entities\CustomerPayment;
use Modules\Petro\Entities\DailyCard;
use Modules\Petro\Entities\DailyCollection;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\DayCountSetting;
use Modules\Petro\Entities\PetroDailyShift;
use Modules\Petro\Entities\PetroShift;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PumpOperatorAssignment;
use Modules\Petro\Entities\PumpOperatorPayment;
use Modules\Petro\Http\Controllers\DailyCardController as PetroDailyCardController;
use Modules\Petro\Http\Controllers\DailyVoucherController as PetroDailyVoucherController;
use Modules\Petro\Http\Controllers\DailyShiftController as PetroDailyShiftController;
use Modules\DailyCollectionSW\Support\SchemaCapabilities;
use Yajra\DataTables\Facades\DataTables;

class DailyCollectionSWController extends Controller
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

    protected $businessUtil;

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

        // TEMPORARY RECOVERY: Daily Collection SW is force-enabled while the
        // central Sidebar/Manage permission framework is being stabilized.
        // Authentication and tenant middleware still protect every route.

    }

    /**

     * Display a listing of the resource.

     * @return Response

     */

    public function index(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        // Daily Collection SW standalone Settings DataTable JSON endpoint.
        if ($request->query('dcsw_data') === 'settings') {
            return $this->settings();
        }

        // Keep Daily Collection SW Ajax endpoints on its already-registered
        // tenant index route. This avoids depending on legacy Petro routes or
        // optional named routes that may not be registered in every tenant.
        if ($request->ajax() && $request->get('dcsw_data') === 'credit-sales') {
            return $this->dailyVoucherData($request);
        }

        if ($request->ajax() && $request->get('dcsw_data') === 'daily-cards') {
            return $this->dailyCardData($request);
        }

        if ($request->ajax() && $request->get('dcsw_data') === 'collection-summary') {
            return $this->collectionSummary();
        }

        if (request()->ajax()) {
            $query = DailyCollection::leftJoin('business_locations', 'daily_collections.location_id', 'business_locations.id')
                ->leftJoin('pump_operators', 'daily_collections.pump_operator_id', 'pump_operators.id')
                ->leftJoin('users', 'daily_collections.created_by', 'users.id')
                ->leftJoin('settlements', 'daily_collections.settlement_id', 'settlements.id')
                ->where('daily_collections.business_id', $business_id)
                ->where('daily_collections.type', 'daily_collection_sw')
                ->select([
                    'daily_collections.id',
                    'daily_collections.location_id',
                    'daily_collections.pump_operator_id',
                    'daily_collections.current_amount',
                    'daily_collections.balance_collection',
                    'daily_collections.collection_form_no',
                    'daily_collections.shift_id',
                    'daily_collections.shift_number',
                    'daily_collections.shift_no',
                    'daily_collections.created_at',
                    'daily_collections.added_to_account',
                    'daily_collections.settlement_id',
                    'daily_collections.settlement_date',
                    'daily_collections.type',
                    'business_locations.name as location_name',
                    'pump_operators.name as pump_operator_name',
                    'settlements.id as settlements_id',
                    'settlements.settlement_no as settlement_no',
                    'settlements.status as settlement_status',
                    'settlements.transaction_date as settlement_transaction_date',
                    'users.username as user',
                    DB::raw('EXISTS(SELECT 1 FROM settlement_cash_payments scp WHERE scp.settlement_no = settlements.id) as cash_settled'),
                    DB::raw('(SELECT poa.shift_number FROM pump_operator_assignments poa
                        WHERE poa.pump_operator_id = daily_collections.pump_operator_id
                          AND (poa.shift_id = daily_collections.shift_id
                               OR (DATE(poa.date_and_time) = DATE(daily_collections.created_at)
                                   AND poa.shift_number > 0))
                        ORDER BY (poa.shift_id = daily_collections.shift_id) DESC, poa.id DESC
                        LIMIT 1) as assigned_shift_number'),
                    DB::raw('(SELECT COALESCE(SUM(dc2.current_amount), 0) FROM daily_collections dc2 WHERE dc2.business_id = daily_collections.business_id AND dc2.type = daily_collections.type AND dc2.pump_operator_id = daily_collections.pump_operator_id AND dc2.id <= daily_collections.id) as total_collection_raw'),
                ]);

            // Apply filters before executing query
            if (! empty(request()->location_id)) {

                $query->where('daily_collections.location_id', request()->location_id);

            }

            if (!empty(request()->status)) {
                if (request()->status == 'completed') {
                    $query->where(function ($q) {
                        $q->where('settlements.status', 0) // 0 means completed
                            ->orWhereNotNull('daily_collections.added_to_account');
                    });
                }

                if (request()->status == 'pending') {
                    $query->where(function ($q) {
                        $q->whereNull('settlements.settlement_no')
                            ->orWhere('settlements.status', 1) // 1 means pending
                            ->orWhereNull('settlements.status');
                    });
                    $query->whereNull('daily_collections.added_to_account');
                }
            }

            if (! empty(request()->settlement_id)) {

                $query->where('settlements.id', request()->settlement_id);

            }

            if (! empty(request()->pump_operator)) {

                $query->where('daily_collections.pump_operator_id', request()->pump_operator);

            }

            if (! empty(request()->settlement_no)) {

                $query->where('daily_collections.id', request()->settlement_no);

            }

            if (! empty(request()->start_date) && ! empty(request()->end_date)) {

                $query->where('daily_collections.created_at', '>=', request()->start_date . ' 00:00:00');

                $query->where('daily_collections.created_at', '<=', request()->end_date . ' 23:59:59');

            }

            $query->orderBy('daily_collections.created_at', 'desc');

            $fuel_tanks = Datatables::of($query)

                ->addColumn(

                    'action',

                    '<button class="btn btn-primary btn-xs print_btn_pump_operator" data-href="{{action(\'\Modules\\DailyCollectionSW\Http\Controllers\DailyCollectionSWController@print\', [$id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("petro::lang.print")</button>

                        @if(empty($settlement_no) && empty($added_to_account))@can("daily_collection.edit") &nbsp; <button data-href="{{action(\'\Modules\\DailyCollectionSW\Http\Controllers\DailyCollectionSWController@edit\', [$id])}}" data-container=".pump_operator_modal" class="btn btn-success btn-xs btn-modal edit_reference_button"><i class="fa fa-pencil" aria-hidden="true"></i> @lang("lang_v1.edit")</button> &nbsp; @endcan @endif

                        @if(empty($settlement_no) && empty($added_to_account))@can("daily_collection.delete")<a class="btn btn-danger btn-xs delete_daily_collection" href="{{action(\'\Modules\\DailyCollectionSW\Http\Controllers\DailyCollectionSWController@destroy\', [$id])}}"><i class="fa fa-trash" aria-hidden="true"></i> @lang("petro::lang.delete")</a>@endcan @endif'

                )

                ->editColumn('current_amount', '{{@num_format($current_amount)}}')

                ->editColumn('balance_collection', '{{@num_format($balance_collection)}}')

                ->addColumn('total_collection', function ($row) {
                    return $this->productUtil->num_f($row->total_collection_raw ?? 0);
                })
                ->addColumn('status', function ($row) {
                    // Check if settlement exists and is completed (status = 0 means completed)
                    if (!empty($row->settlement_id) || !empty($row->settlement_no)) {
                        // Check settlement status - 0 = completed, 1 = pending
                        if ($row->settlement_status === 0 || $row->settlement_status === '0') {
                            return 'Completed';
                        } else {
                            return 'Pending';
                        }
                    }
                    
                    // No settlement exists - show Pending
                    return 'Pending';
                })

                ->editColumn('collection_form_no', function ($row) {

                    return '<button data-id="' . $row->id . '" class="btn btn-success btn-xs btn-modal open-shift-modal">Shift</button> ' . e($row->collection_form_no);

                })

                ->addColumn('shift_number', function ($row) {
                    // Priority 1: assigned_shift_number from pump_operator_assignments join
                    if (!empty($row->assigned_shift_number) && $row->assigned_shift_number > 0) {
                        return $row->assigned_shift_number;
                    }
                    // Priority 2: shift_number from daily_collections
                    elseif (!empty($row->shift_number)) {
                        return $row->shift_number;
                    }
                    // Priority 3: shift_no from daily_collections
                    elseif (!empty($row->shift_no)) {
                        return $row->shift_no;
                    }

                    return '';
                })

                ->editColumn('created_at', function ($row) {
                    if (empty($row->created_at)) {
                        return '';
                    }

                    // IS1684: Daily Cash must show the saved date and time, not
                    // only the date (and never an empty computed column).
                    return Carbon::parse($row->created_at)->format('Y-m-d H:i:s');
                })

                ->editColumn('settlement_no', function ($row) {
                    return $row->settlement_no ?? '';
                })
                ->editColumn('settlement_dates', function ($row) {
                    $date = $row->settlement_date ?: $row->settlement_transaction_date;
                    return $date ? $this->commonUtil->format_date($date, false) : '';
                })

                ->removeColumn('id');

            $result = $fuel_tanks->rawColumns(['action', 'total_collection', 'collection_form_no'])->make(true);

            return $result;

        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $pump_operators = $this->dailySwPumpOperatorDropdown((int) $business_id, true, false);

        $settlement_nos = [];

        $message = $this->transactionUtil->getGeneralMessage('general_message_pump_management_checkbox');

        $customers = Contact::customersDropdown($business_id, false, true, 'customer');

        $card_types = [];

        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();

        if (! empty($card_group)) {

            $card_types = Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');

        }

        $slip_nos = DailyCard::where('business_id', $business_id)->whereNotNull('slip_no')->distinct()->orderBy('slip_no')->pluck('slip_no', 'slip_no');

        $card_numbers = DailyCard::where('business_id', $business_id)->whereNotNull('card_number')->distinct()->orderBy('card_number')->pluck('card_number', 'card_number');

        $daily_card_settlements = DailyCard::leftjoin('settlements', 'settlements.id', 'daily_cards.settlement_no')

            ->where('daily_cards.business_id', $business_id)

            ->whereNotNull('daily_cards.settlement_no')

            ->whereNotNull('daily_cards.slip_no')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $daily_collection_settlements = DailyCollection::leftjoin('settlements', 'settlements.id', 'daily_collections.settlement_id')

            ->where('daily_collections.business_id', $business_id)

            ->where('daily_collections.type', 'daily_collection_sw')

            ->whereNotNull('daily_collections.settlement_id')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $daily_voucher_settlements = DailyVoucher::leftjoin('settlements', 'settlements.id', 'daily_vouchers.settlement_no')

            ->where('daily_vouchers.business_id', $business_id)

            ->whereNotNull('daily_vouchers.settlement_no')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $shortage_settlements = PumpOperatorPayment::leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

            ->where('pump_operator_payments.business_id', $business_id)

            ->whereIn('payment_type', ['shortage', 'excess'])

            ->whereNotNull('pump_operator_payments.settlement_no')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $cheques_settlements = PumpOperatorPayment::leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

            ->where('pump_operator_payments.business_id', $business_id)

            ->whereIn('payment_type', ['cheque'])

            ->whereNotNull('pump_operator_payments.settlement_no')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $others_settlements = PumpOperatorPayment::leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

            ->where('pump_operator_payments.business_id', $business_id)

            ->whereIn('payment_type', ['other'])

            ->whereNotNull('pump_operator_payments.settlement_no')

            ->orderBy('settlements.id', 'DESC')

            ->distinct('settlements.settlement_no')

            ->pluck('settlements.settlement_no', 'settlements.id');

        $is_pending_shift = false;

        $dailyShift = PetroDailyShift::where('business_id', $business_id)

            ->where('status', 1)

            ->where('type', 'daily_collection_sw')

            ->orderBy('updated_at', 'desc')

            ->first();

        if (! $dailyShift) {

            $dailyShift = PetroDailyShift::where('business_id', $business_id)

                ->where('status', 0)

                ->where('type', 'daily_collection_sw')

                ->orderBy('updated_at', 'desc')

                ->first();

            if ($dailyShift) {

                $is_pending_shift = true;

            }

        }

        if ($dailyShift) {

            $daily_shift_id = $dailyShift->id;

        } else {

            $dailyShift = PetroDailyShift::create([

                'business_id'             => $business_id,

                'shift_no'                => $this->generateShiftNumber('DCSW', 'daily_collection_sw', $business_id),

                'date'                    => now()->format('Y-m-d'),

                'time'                    => now()->format('H:i:s'),

                'user'                    => auth()->id(),

                'status'                  => 1,

                'pump_operators_assigned' => explode(',', ''),

                'pump_operators_pending'  => $pump_operators,

                'type'                    => 'daily_collection_sw',

            ]);

            $daily_shift_id = $dailyShift->id;

        }

        $all_pump_operators = $this->dailySwPumpOperatorDropdown((int) $business_id, true, true);

        $daily_shift_no = $dailyShift->shift_no;

        $pump_operators_shift = $all_pump_operators;

        $pending_ids = explode(',', $dailyShift->pump_operator_pending);

        $assigned_ids = explode(',', $dailyShift->pump_operator_assigned);

        $pending_operators = PumpOperator::whereIn('id', $pending_ids)

            ->where('active', 1)

            ->pluck('name', 'id')

            ->toArray();

        $assigned_operators = PumpOperator::whereIn('id', $assigned_ids)

            ->where('active', 1)

            ->pluck('name', 'id')

            ->toArray();

        $pump_operators_shift = $dailyShift->status == 1 ? $all_pump_operators : $pending_operators;

        // Get shifts for daily cash status: Include both active (status = 1) and pending (status = 0)
        // Prioritize active shifts, then pending shifts
        $business_details = Business::find($business_id);
        $businessCurrencyPrecise = (int) ($business_details->currency_precision ?? 2);
        $businessLocation = BusinessLocation::where('business_id', $business_id)->orderBy('id')->first();

        $dailyCashShiftNumbers = PetroDailyShift::where('business_id', $business_id)
            ->where('type', 'daily_collection_sw')  // Only daily_collection_sw shifts
            ->whereIn('status', [1, 0])  // Both active and pending
            ->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END')  // Active first
            ->orderBy('updated_at', 'desc')
            ->pluck('shift_no')
            ->toArray();

        return view('dailycollectionsw::index')->with(compact(

            'dailyCashShiftNumbers', 'business_details', 'businessCurrencyPrecise', 'businessLocation', 'card_types', 'customers', 'pump_operators_shift', 'pending_operators', 'assigned_operators',

            'business_locations',

            'pump_operators',

            'settlement_nos',

            'message',

            'slip_nos',

            'dailyShift',

            'card_numbers', 'is_pending_shift',

            'daily_card_settlements', 'daily_shift_no', 'daily_shift_id',

            'daily_collection_settlements', 'daily_voucher_settlements', 'shortage_settlements', 'cheques_settlements', 'others_settlements'

        ));

    }


    /**
     * Tenant-safe pump operator dropdown for Daily Collection SW.
     *
     * Rewritten for IS1545.  The old code depended on one set of shift/operator
     * columns and could therefore return an empty dropdown in copied tenants.
     * This method first respects the tenant business_id and then safely falls
     * back to active operators if legacy rows were saved without business_id.
     */
    protected function dailySwPumpOperatorDropdown(int $business_id, bool $activeOnly = true, bool $statusOnly = false)
    {
        $build = function ($filterBusiness) use ($business_id, $activeOnly, $statusOnly) {
            $query = DB::table('pump_operators');

            if ($filterBusiness && SchemaCapabilities::hasColumn('pump_operators', 'business_id')) {
                $query->where('business_id', $business_id);
            }

            if ($activeOnly && SchemaCapabilities::hasColumn('pump_operators', 'active')) {
                $query->where(function ($q) {
                    $q->where('active', 1)->orWhereNull('active');
                });
            }

            if ($statusOnly && SchemaCapabilities::hasColumn('pump_operators', 'status')) {
                $query->where(function ($q) {
                    $q->where('status', 1)->orWhereNull('status');
                });
            }

            if (SchemaCapabilities::hasColumn('pump_operators', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            return $query->orderBy('name')->pluck('name', 'id')->toArray();
        };

        $rows = $build(true);
        return ! empty($rows) ? $rows : $build(false);
    }

    /**
     * Daily shift operator columns used by the older and newer tenant schemas.
     */
    protected function dailySwShiftOperatorColumns(): array
    {
        if (! SchemaCapabilities::hasTable('petro_daily_shifts')) {
            return [];
        }

        return array_values(array_filter([
            SchemaCapabilities::hasColumn('petro_daily_shifts', 'pump_operator_assigned') ? 'pump_operator_assigned' : null,
            SchemaCapabilities::hasColumn('petro_daily_shifts', 'pump_operators_assigned') ? 'pump_operators_assigned' : null,
            SchemaCapabilities::hasColumn('petro_daily_shifts', 'pump_operator_pending') ? 'pump_operator_pending' : null,
            SchemaCapabilities::hasColumn('petro_daily_shifts', 'pump_operators_pending') ? 'pump_operators_pending' : null,
            SchemaCapabilities::hasColumn('petro_daily_shifts', 'pump_operator_id') ? 'pump_operator_id' : null,
        ]));
    }

    protected function normalizeDailySwIdList($value): array
    {
        if (is_null($value) || $value === '') {
            return [];
        }

        if (is_array($value)) {
            $items = $value;
        } else {
            $items = preg_split('/[,|;]/', (string) $value);
        }

        return collect($items)
            ->map(function ($item) {
                return (int) trim((string) $item);
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function dailySwOperatorIdsFromShift($shift): array
    {
        if (empty($shift)) {
            return [];
        }

        $ids = [];
        foreach ($this->dailySwShiftOperatorColumns() as $column) {
            if ($column === 'pump_operator_id') {
                if (! empty($shift->{$column})) {
                    $ids[] = (int) $shift->{$column};
                }
            } else {
                $ids = array_merge($ids, $this->normalizeDailySwIdList($shift->{$column} ?? null));
            }
        }

        return collect($ids)->filter()->unique()->values()->all();
    }

    protected function dailySwOperatorsForShift(int $business_id, $shift): array
    {
        $ids = $this->dailySwOperatorIdsFromShift($shift);

        if (empty($ids)) {
            return [];
        }

        $query = DB::table('pump_operators')->whereIn('id', $ids);

        if (SchemaCapabilities::hasColumn('pump_operators', 'business_id')) {
            $query->where(function ($q) use ($business_id) {
                $q->where('business_id', $business_id)->orWhereNull('business_id');
            });
        }

        if (SchemaCapabilities::hasColumn('pump_operators', 'active')) {
            $query->where(function ($q) {
                $q->where('active', 1)->orWhereNull('active');
            });
        }

        if (SchemaCapabilities::hasColumn('pump_operators', 'status')) {
            $query->where(function ($q) {
                $q->where('status', 1)->orWhereNull('status');
            });
        }

        return $query->orderBy('name')->pluck('name', 'id')->toArray();
    }

    /**
     * Tenant-safe Daily Shift dropdown.  It supports:
     * - old/new petro_daily_shifts operator columns,
     * - pump_operator_assignments rows used by some Daily Shift screens,
     * - current active/pending shifts when no operator is selected.
     */
    protected function dailySwShiftOptionsForOperatorSafe(int $business_id, int $operator_id = 0)
    {
        $rows = collect();

        if (SchemaCapabilities::hasTable('petro_daily_shifts')) {
            $query = DB::table('petro_daily_shifts');

            if (SchemaCapabilities::hasColumn('petro_daily_shifts', 'business_id')) {
                $query->where(function ($q) use ($business_id) {
                    $q->where('business_id', $business_id)->orWhereNull('business_id');
                });
            }

            if (SchemaCapabilities::hasColumn('petro_daily_shifts', 'type')) {
                $query->where(function ($q) {
                    $q->where('type', 'daily_collection_sw')->orWhereNull('type');
                });
            }

            if (SchemaCapabilities::hasColumn('petro_daily_shifts', 'status')) {
                $query->whereIn('status', [0, 1]);
            }

            $operatorColumns = $this->dailySwShiftOperatorColumns();
            if ($operator_id > 0 && ! empty($operatorColumns)) {
                $query->where(function ($q) use ($operatorColumns, $operator_id) {
                    foreach ($operatorColumns as $column) {
                        if ($column === 'pump_operator_id') {
                            $q->orWhere($column, $operator_id);
                        } else {
                            $q->orWhereRaw('FIND_IN_SET(?, ' . $column . ')', [$operator_id]);
                        }
                    }
                });
            }

            $shiftColumn = SchemaCapabilities::hasColumn('petro_daily_shifts', 'shift_no') ? 'shift_no' : 'id';

            if (SchemaCapabilities::hasColumn('petro_daily_shifts', 'status')) {
                $query->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END');
            }

            $rows = $query->orderBy('id', 'desc')
                ->pluck($shiftColumn)
                ->filter()
                ->values();
        }

        // Fallback: some Daily Shift screens save only to pump_operator_assignments.
        if ($operator_id > 0 && SchemaCapabilities::hasTable('pump_operator_assignments')) {
            $assignmentQuery = DB::table('pump_operator_assignments')
                ->where('pump_operator_id', $operator_id);

            if (SchemaCapabilities::hasColumn('pump_operator_assignments', 'business_id')) {
                $assignmentQuery->where(function ($q) use ($business_id) {
                    $q->where('business_id', $business_id)->orWhereNull('business_id');
                });
            }

            if (SchemaCapabilities::hasColumn('pump_operator_assignments', 'status')) {
                $assignmentQuery->where(function ($q) {
                    $q->where('status', 'open')->orWhere('status', 1)->orWhereNull('status');
                });
            }

            foreach (['shift_number', 'shift_no', 'shift_id'] as $assignmentColumn) {
                if (SchemaCapabilities::hasColumn('pump_operator_assignments', $assignmentColumn)) {
                    $rows = $rows->merge(
                        $assignmentQuery->clone()
                            ->orderBy('id', 'desc')
                            ->pluck($assignmentColumn)
                            ->filter()
                            ->values()
                    );
                }
            }
        }

        return $rows->filter()->unique()->values();
    }

    private function generateShiftNumber(string $prefix, string $type, int $business_id)
    {
        $lastShift = PetroDailyShift::where('business_id', $business_id)
            ->where('type', $type)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;

        if ($lastShift && ! empty($lastShift->shift_no)) {
            $parts = explode('-', $lastShift->shift_no);
            if (isset($parts[0], $parts[1]) && $parts[0] === $prefix) {
                $nextNumber = intval($parts[1]) + 1;
            }
        }

        return sprintf("%s-%03d", $prefix, $nextNumber);
    }

    private function incrementCode(string $code): string
    {

        $parts = explode('-', $code);

        if (count($parts) !== 2) {

            error_log('Invalid code format');

            return $code;

        }

        $prefix = $parts[0];

        $number = $parts[1];

        $numberLength = strlen($number);

        $incremented = str_pad((intval($number) + 1), $numberLength, '0', STR_PAD_LEFT);

        return $prefix . '-' . $incremented;

    }

    public function extractLastInteger($text)
    {

        if (preg_match('/\d+$/', $text, $matches)) {

            return intval($matches[0]);

        } else {

            return 0;

        }

    }

    public function getDailyCashStatusData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shift' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => $validator->errors()->first() ?? 'Please provide valid details.',
            ], 422);
        }

        $businessId = (int) $request->session()->get('user.business_id');
        if ($businessId <= 0) {
            return response()->json(['success' => false, 'msg' => 'Business context is missing.'], 403);
        }

        $shift = $request->input('shift');

        try {
            $operatorCollections = DailyCollection::query()
                ->join('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')
                ->where('daily_collections.business_id', $businessId)
                ->where('daily_collections.type', 'daily_collection_sw')
                ->where('daily_collections.shift_number', $shift)
                ->groupBy('daily_collections.pump_operator_id', 'pump_operators.name')
                ->select([
                    'daily_collections.pump_operator_id',
                    'pump_operators.name',
                    DB::raw('SUM(daily_collections.current_amount) as total_amount'),
                ])
                ->get();

            $dailyCollectionTotal = (float) $operatorCollections->sum('total_amount');

            $customerPayments = TransactionPayment::query()
                ->join('contacts', 'transaction_payments.payment_for', '=', 'contacts.id')
                ->where('transaction_payments.business_id', $businessId)
                ->where('transaction_payments.shift_number', $shift)
                ->where('transaction_payments.method', 'cash')
                ->groupBy('contacts.id', 'contacts.name')
                ->select('contacts.name as customer_name', DB::raw('SUM(transaction_payments.amount) as amount'))
                ->get();

            $customerPaymentTotal = (float) $customerPayments->sum('amount');

            $cashDepositQuery = AccountTransaction::query()
                ->where('business_id', $businessId)
                ->where('type', 'debit')
                ->where('sub_type', 'deposit');
            if (SchemaCapabilities::hasColumn('account_transactions', 'shift_number')) {
                $cashDepositQuery->where('shift_number', $shift);
            }
            $cashDeposit = (float) $cashDepositQuery->sum('amount');

            $expenses = Transaction::query()
                ->join('expense_categories', 'transactions.expense_category_id', '=', 'expense_categories.id')
                ->where('transactions.type', 'expense')
                ->where('transactions.business_id', $businessId)
                ->where('transactions.shift_number', $shift)
                ->groupBy('expense_categories.id', 'expense_categories.name')
                ->select('expense_categories.name as expense_name', DB::raw('SUM(transactions.final_total) as amount'))
                ->get();

            $expenseAmount = (float) $expenses->sum('amount');
            $balanceInHand = $dailyCollectionTotal + $customerPaymentTotal - $expenseAmount - $cashDeposit;

            return response()->json([
                'cash_collection' => $dailyCollectionTotal,
                'other_income_cash' => 0.0,
                'cash_deposit' => $cashDeposit,
                'operators' => [
                    'total_amount' => $dailyCollectionTotal,
                    'operators_payment' => $operatorCollections->map(function ($row) {
                        return [
                            'pump_operator_id' => $row->pump_operator_id,
                            'name' => $row->name,
                            'total_amount' => (float) $row->total_amount,
                            'total_short_amount' => 0.0,
                            'total_excess_amount' => 0.0,
                        ];
                    })->values(),
                ],
                'expense_total' => ['expense_all' => $expenseAmount, 'expenses' => $expenses],
                'balance_in_hand' => $balanceInHand,
                'customer_payment_list' => ['total' => $customerPaymentTotal, 'list' => $customerPayments],
                'cash_expenses' => $expenseAmount,
                'shifts' => $shift,
                'customer_details' => $customerPayments->first(),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Daily Collection SW shift cash status failed', [
                'business_id' => $businessId,
                'shift' => $shift,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function getByDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => $validator->errors()->first() ?? 'Please provide valid details.',
            ], 422);
        }

        $businessId = (int) $request->session()->get('user.business_id');

        try {
            $accountReceivableId = Account::where('business_id', $businessId)
                ->where('name', 'Accounts Receivable')
                ->where('is_closed', 0)
                ->value('id');

            $totalCustomerCashPayments = 0.0;
            if ($accountReceivableId) {
                $totalCustomerCashPayments = (float) AccountTransaction::query()
                    ->where('business_id', $businessId)
                    ->where('account_id', $accountReceivableId)
                    ->where('type', 'debit')
                    ->whereDate('operation_date', $request->input('date'))
                    ->sum('amount');
            }

            return response()->json(['customer_payment' => $totalCustomerCashPayments]);
        } catch (\Throwable $exception) {
            Log::error('Daily Collection SW date summary failed', [
                'business_id' => $businessId,
                'date' => $request->input('date'),
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function settings()
    {
        $user = auth()->user();

        $query = DayCountSetting::where('created_by', $user->id)
            ->orderByDesc('created_at');

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                $checked = $row->status ? 'checked' : '';
                return '<input type="checkbox" class="toggle-status-switch" data-id="' . $row->id . '" ' . $checked . ' data-toggle="toggle" data-on="Enabled" data-off="Disabled" data-onstyle="success" data-offstyle="danger">';
            })
            ->editColumn('ending_date_type', function ($row) {
                return $row->ending_date_type === 'same_day' ? 'Same Day' : 'Following Day';
            })
            ->addColumn('start_time', '{{@format_time($day_counted_from)}}')
            ->editColumn('time_till', '{{@format_time($time_till)}}')
            ->addColumn('date', function ($row) {
                return $row->created_at;
            })
            ->addColumn('user_entered', function () use ($user) {
                return $user->username;
            })
            ->removeColumn('updated_at')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function saveSettings(Request $request)
    {
        if (! $request->ajax()) {
            abort(403, 'Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'day_counted_from' => 'required|date_format:H:i',
            'time_till' => 'required|date_format:H:i',
            'ending_date_type' => 'required|in:same_day,following_day',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => $validator->errors()->first() ?? 'Please provide valid details.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($request) {
                DayCountSetting::where('created_by', auth()->id())->update(['status' => 0]);

                DayCountSetting::create([
                    'day_counted_from' => $request->input('day_counted_from'),
                    'time_till' => $request->input('time_till'),
                    'ending_date_type' => $request->input('ending_date_type'),
                    'status' => 1,
                    'created_by' => auth()->id(),
                ]);
            });

            return response()->json([
                'success' => true,
                'msg' => 'Settings saved successfully.',
            ], 201);
        } catch (\Throwable $exception) {
            Log::error('Daily Collection SW settings save failed', [
                'user_id' => auth()->id(),
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function getDailyCashStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date'  => 'required|date_format:Y-m-d',
            'shift' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg'     => $validator->errors()->first() ?? 'Please provide valid details.',
            ], 422);
        }

        $businessId = (int) $request->session()->get('user.business_id');
        if ($businessId <= 0) {
            return response()->json(['success' => false, 'msg' => 'Business context is missing.'], 403);
        }

        $date = $request->input('date');
        $shift = $request->input('shift');

        try {
            $collectionQuery = DailyCollection::query()
                ->join('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')
                ->where('daily_collections.business_id', $businessId)
                ->where('daily_collections.type', 'daily_collection_sw')
                ->whereDate('daily_collections.created_at', $date)
                ->when($shift, function ($query) use ($shift) {
                    $query->where('daily_collections.shift_number', $shift);
                });

            $collections = $collectionQuery
                ->groupBy('daily_collections.pump_operator_id', 'pump_operators.name')
                ->select([
                    'daily_collections.pump_operator_id',
                    'pump_operators.name',
                    DB::raw('SUM(daily_collections.current_amount) as total_amount'),
                ])
                ->get();

            $dailyCollectionTotal = (float) $collections->sum('total_amount');
            $operators = [
                'total_amount' => $dailyCollectionTotal,
                'operators_payment' => $collections->map(function ($row) {
                    return [
                        'pump_operator_id' => $row->pump_operator_id,
                        'name' => $row->name,
                        'total_amount' => (float) $row->total_amount,
                        'total_short_amount' => 0.0,
                        'total_excess_amount' => 0.0,
                    ];
                })->values(),
            ];

            $expenses = Transaction::query()
                ->join('expense_categories', 'transactions.expense_category_id', '=', 'expense_categories.id')
                ->where('transactions.type', 'expense')
                ->where('transactions.business_id', $businessId)
                ->whereDate('transactions.transaction_date', $date)
                ->when($shift, function ($query) use ($shift) {
                    $query->where('transactions.shift_number', $shift);
                })
                ->groupBy('expense_categories.id', 'expense_categories.name')
                ->select('expense_categories.name as expense_name', DB::raw('SUM(transactions.final_total) as amount'))
                ->get();

            $expenseAmount = (float) $expenses->sum('amount');

            $customerPayments = TransactionPayment::query()
                ->join('contacts', 'transaction_payments.payment_for', '=', 'contacts.id')
                ->where('transaction_payments.business_id', $businessId)
                ->where('transaction_payments.method', 'cash')
                ->whereDate('transaction_payments.created_at', $date)
                ->when($shift, function ($query) use ($shift) {
                    $query->where('transaction_payments.shift_number', $shift);
                })
                ->groupBy('contacts.id', 'contacts.name')
                ->select('contacts.name as customer_name', DB::raw('SUM(transaction_payments.amount) as amount'))
                ->get();

            $customerPaymentTotal = (float) $customerPayments->sum('amount');

            $cashAccount = Account::where('business_id', $businessId)
                ->where('name', 'Cash')
                ->where('is_closed', 0)
                ->first();
            $cashAccountId = (int) optional($cashAccount)->id;

            $expenseAccountTypeId = DB::table('account_types')->where('name', 'Expenses')->value('id');
            $cashExpenseQuery = AccountTransaction::query()
                ->leftJoin('accounts', 'account_transactions.account_id', '=', 'accounts.id')
                ->leftJoin('transactions', 'account_transactions.transaction_id', '=', 'transactions.id')
                ->where('account_transactions.business_id', $businessId)
                ->where('accounts.account_type_id', $expenseAccountTypeId)
                ->where('account_transactions.type', 'credit')
                ->where('account_transactions.account_id', $cashAccountId)
                ->whereDate('account_transactions.operation_date', $date);

            if ($shift) {
                $cashExpenseQuery->where(function ($query) use ($shift) {
                    $query->where('account_transactions.shift_number', $shift)
                        ->orWhere('transactions.shift_number', $shift);
                });
            }
            $totalCashExpenses = (float) $cashExpenseQuery->sum('account_transactions.amount');

            $bankGroupId = AccountGroup::getGroupByName('Bank Account', true);
            $bankAccountIds = Account::where('business_id', $businessId)
                ->where('asset_type', $bankGroupId)
                ->pluck('id');

            $totalCashDeposits = 0.0;
            if ($cashAccountId > 0 && $bankAccountIds->isNotEmpty()) {
                $totalCashDeposits = (float) AccountTransaction::query()
                    ->where('business_id', $businessId)
                    ->where('account_id', $cashAccountId)
                    ->whereIn('related_account_id', $bankAccountIds)
                    ->where('type', 'credit')
                    ->whereDate('operation_date', $date)
                    ->when($shift && SchemaCapabilities::hasColumn('account_transactions', 'shift_number'), function ($query) use ($shift) {
                        $query->where('shift_number', $shift);
                    })
                    ->sum('amount');
            }

            $balanceInHand = $dailyCollectionTotal + $customerPaymentTotal - $totalCashExpenses - $totalCashDeposits;

            return response()->json([
                'cash_collection'             => $dailyCollectionTotal,
                'other_income_cash'           => 0.0,
                'cash_deposit'                => $totalCashDeposits,
                'cash_deposit_expense_table'  => $totalCashDeposits,
                'operators'                   => $operators,
                'expense_total'               => ['expense_all' => $expenseAmount, 'expenses' => $expenses],
                'balance_in_hand'             => $balanceInHand,
                'customer_payment_list'       => ['total' => $customerPaymentTotal, 'list' => $customerPayments],
                'customer_payment'            => $customerPaymentTotal,
                'cash_expenses'               => $expenseAmount,
                'cash_expenses_account_table' => $totalCashExpenses,
                'shifts'                      => $shift ? [$shift] : [],
            ]);
        } catch (\Throwable $exception) {
            Log::error('Daily Collection SW cash status failed', [
                'business_id' => $businessId,
                'date' => $date,
                'shift' => $shift,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    /**
     * Server-side data source for the Daily Credit Sales table.
     *
     * This endpoint intentionally lives inside DailyCollectionSW.  The page
     * must not depend on the legacy Petro controller because that controller
     * can be unavailable or protected by different module permissions, which
     * makes DataTables display "Ajax error".
     */
    public function storeDailyCard(Request $request)
    {
        return app(PetroDailyCardController::class)->store($request);
    }

    public function updateDailyCard(Request $request, $id)
    {
        return app(PetroDailyCardController::class)->update($request, $id);
    }

    public function storeDailyVoucher(Request $request)
    {
        return app(PetroDailyVoucherController::class)->store($request);
    }

    public function openDailyShift(Request $request)
    {
        return app(PetroDailyShiftController::class)->OpenShift($request);
    }

    public function storeDailyShift(Request $request)
    {
        return app(PetroDailyShiftController::class)->store($request);
    }

    public function saveDailyShift(Request $request)
    {
        return app(PetroDailyShiftController::class)->saveShift($request);
    }

    public function fetchOpenDailyShift()
    {
        return app(PetroDailyShiftController::class)->fetchOpenShift();
    }

    public function checkSlipNo(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $slipNo = trim(str_replace(' ', '', (string) $request->input('slip_no')));

        if ($slipNo === '') {
            return response()->json(['exists' => false, 'allow_duplicates' => false]);
        }

        $exists = AccountTransaction::where('business_id', $businessId)
            ->where('slip_no', $slipNo)
            ->exists();

        return response()->json(['exists' => $exists, 'allow_duplicates' => false]);
    }

    public function getProductPrice(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');
        $productId = (int) $request->input('product_id');

        $price = Product::query()
            ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
            ->where('products.business_id', $businessId)
            ->where('products.id', $productId)
            ->value('variations.sell_price_inc_tax');

        return response()->json(['price' => (float) ($price ?? 0)]);
    }

    public function getCustomerDetails(Request $request, $customerId)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        $contact = Contact::where('business_id', $businessId)
            ->where('id', (int) $customerId)
            ->firstOrFail();

        $customerReferences = CustomerReference::where('business_id', $businessId)
            ->where('contact_id', $contact->id)
            ->orderBy('reference')
            ->get(['reference']);

        $due = app('App\Http\Controllers\ContactController')->get_cus_due_bal($contact->id, false);

        return response()->json([
            'total_outstanding' => $this->productUtil->num_f((float) $due),
            'credit_limit' => empty($contact->credit_limit) ? 'No Limit' : (string) $contact->credit_limit,
            'customer_references' => $customerReferences,
            'manual_bill_settlement' => (int) ($contact->manual_bill_settlement ?? 0),
            'customer_name' => $contact->name,
        ]);
    }

    public function dailyVoucherData(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        if (! $request->ajax()) {
            abort(404);
        }

        try {
            $table = 'daily_vouchers';
            $query = DB::table($table)->where($table . '.business_id', $businessId);

            $has = static function (string $column) use ($table): bool {
                return SchemaCapabilities::hasColumn($table, $column);
            };

            $customerColumn = $has('customer_id') ? 'customer_id' : ($has('contact_id') ? 'contact_id' : null);
            $locationColumn = $has('location_id') ? 'location_id' : ($has('business_location_id') ? 'business_location_id' : null);

            if ($locationColumn) {
                $query->leftJoin('business_locations', $table . '.' . $locationColumn, '=', 'business_locations.id');
            }
            if ($customerColumn) {
                $query->leftJoin('contacts', $table . '.' . $customerColumn, '=', 'contacts.id');
            }
            if ($has('operator_id')) {
                $query->leftJoin('pump_operators', $table . '.operator_id', '=', 'pump_operators.id');
            }
            if ($has('shift_id')) {
                $query->leftJoin('petro_daily_shifts', $table . '.shift_id', '=', 'petro_daily_shifts.id');
            }
            if ($has('created_by')) {
                $query->leftJoin('users', $table . '.created_by', '=', 'users.id');
            }

            $transactionDate = $has('transaction_date') ? $table . '.transaction_date' : ($has('date') ? $table . '.date' : $table . '.created_at');
            $orderDate = $has('voucher_order_date') ? $table . '.voucher_order_date' : ($has('order_date') ? $table . '.order_date' : $transactionDate);
            $orderNumber = $has('voucher_order_number') ? $table . '.voucher_order_number' : ($has('order_number') ? $table . '.order_number' : null);
            $formNumber = $has('daily_vouchers_no') ? $table . '.daily_vouchers_no' : ($has('collection_form_no') ? $table . '.collection_form_no' : null);
            $amount = $has('total_amount') ? $table . '.total_amount' : ($has('amount') ? $table . '.amount' : null);
            $settlementNo = $has('settlement_no') ? $table . '.settlement_no' : null;
            $status = $has('status') ? $table . '.status' : null;

            $selects = [
                $table . '.id',
                DB::raw($orderDate . ' as voucher_order_date'),
                DB::raw($transactionDate . ' as transaction_date'),
                DB::raw(($orderNumber ?: "''") . ' as voucher_order_number'),
                DB::raw(($formNumber ?: "''") . ' as daily_vouchers_no'),
                DB::raw(($amount ?: '0') . ' as total_amount'),
                DB::raw(($settlementNo ?: "''") . ' as settlement_nos'),
                DB::raw(($status ?: "'Pending'") . ' as status'),
            ];

            $selects[] = $locationColumn ? DB::raw("COALESCE(business_locations.name, '-') as location_name") : DB::raw("'-' as location_name");
            $selects[] = $customerColumn ? DB::raw("COALESCE(contacts.name, '-') as customer_name") : DB::raw("'-' as customer_name");
            $selects[] = $customerColumn && SchemaCapabilities::hasColumn('contacts', 'credit_limit')
                ? DB::raw('COALESCE(contacts.credit_limit, 0) as credit_limit')
                : DB::raw('0 as credit_limit');
            $selects[] = $has('operator_id') ? DB::raw("COALESCE(pump_operators.name, '-') as operator_name") : DB::raw("'-' as operator_name");
            $selects[] = $has('shift_id') ? DB::raw("COALESCE(petro_daily_shifts.shift_no, '-') as shift_number") : DB::raw("'-' as shift_number");
            $selects[] = $has('created_by') ? DB::raw("COALESCE(users.username, '-') as username") : DB::raw("'-' as username");

            $query->select($selects);

            if ($locationColumn && $request->filled('location_id')) {
                $query->where($table . '.' . $locationColumn, $request->input('location_id'));
            }
            if ($customerColumn && $request->filled('customer_id')) {
                $query->where($table . '.' . $customerColumn, $request->input('customer_id'));
            }
            if ($settlementNo && $request->filled('settlement_id')) {
                $query->where($settlementNo, $request->input('settlement_id'));
            }
            if ($status && $request->filled('status')) {
                $query->where($status, $request->input('status'));
            }
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween($transactionDate, [
                    $request->input('start_date') . ' 00:00:00',
                    $request->input('end_date') . ' 23:59:59',
                ]);
            }

            return DataTables::of($query)
                ->editColumn('voucher_order_date', function ($row) {
                    return empty($row->voucher_order_date) ? '-' : $this->commonUtil->format_date($row->voucher_order_date, true);
                })
                ->editColumn('transaction_date', function ($row) {
                    return empty($row->transaction_date) ? '-' : $this->commonUtil->format_date($row->transaction_date, true);
                })
                ->editColumn('credit_limit', function ($row) {
                    return number_format((float) $row->credit_limit, 2, '.', ',');
                })
                ->addColumn('current_outstanding', function () {
                    return number_format(0, 2, '.', ',');
                })
                ->editColumn('total_amount', function ($row) {
                    return number_format((float) $row->total_amount, 2, '.', ',');
                })
                ->addColumn('balance_available', function ($row) {
                    return number_format((float) $row->credit_limit - (float) $row->total_amount, 2, '.', ',');
                })
                ->addColumn('total_collection', function ($row) {
                    return number_format((float) $row->total_amount, 2, '.', ',');
                })
                ->addColumn('action', function () {
                    return '';
                })
                ->rawColumns(['action'])
                ->make(true);
        } catch (\Throwable $e) {
            Log::error('Daily Collection SW credit-sales DataTable failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'draw' => (int) $request->input('draw', 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'Unable to load Daily Credit Sales data.',
            ], 200);
        }
    }

    public function collectionSummary()
    {
        $business_id = request()->session()->get('user.business_id');

        // Daily Collection SW is a standalone tenant module. Do not block its
        // summary endpoint behind the legacy Petro subscription permission;
        // that returned an HTML 403 response and triggered DataTables tn/7.
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            // DAILY CASH - Show individual records
            $daily_cash = DB::table('daily_collections')
                ->leftJoin('pump_operators', 'daily_collections.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', function ($join) {
                    $join->on('daily_collections.settlement_id', '=', 'settlements.id')
                        ->whereNotNull('daily_collections.settlement_id');
                })
                ->leftJoin('pump_operator_assignments', function($join) {
                    $join->on('daily_collections.pump_operator_id', '=', 'pump_operator_assignments.pump_operator_id')
                        ->where(function($q) {
                            $q->whereColumn('daily_collections.shift_id', 'pump_operator_assignments.shift_id')
                              ->orWhere(function($subQ) {
                                  $subQ->whereRaw('DATE(daily_collections.created_at) = DATE(pump_operator_assignments.date_and_time)')
                                       ->where('pump_operator_assignments.shift_number', '>', 0);
                              });
                        });
                })
                ->leftJoin('petro_daily_shifts', function($join) {
                    $join->whereRaw('FIND_IN_SET(daily_collections.pump_operator_id, petro_daily_shifts.pump_operator_assigned)')
                        ->whereRaw('DATE(daily_collections.created_at) = DATE(petro_daily_shifts.date)');
                })
                ->where('daily_collections.business_id', $business_id)
                ->where('daily_collections.type', 'daily_collection_sw')
                ->select([
                    DB::raw('"daily_cash" as type'),
                    'daily_collections.id',
                    DB::raw('CAST(daily_collections.collection_form_no AS CHAR) as collection_form_no'),
                    DB::raw('DATE(daily_collections.created_at) as date'),
                    DB::raw('COALESCE(
                        CASE WHEN pump_operator_assignments.shift_number > 0 THEN CAST(pump_operator_assignments.shift_number AS CHAR) ELSE NULL END,
                        NULLIF(CAST(daily_collections.shift_number AS CHAR), ""),
                        NULLIF(CAST(daily_collections.shift_no AS CHAR), ""),
                        CAST(petro_daily_shifts.shift_no AS CHAR),
                        CAST("-" AS CHAR)
                    ) as shift_number'),
                    'pump_operators.name as pump_operator_name',
                    'daily_collections.pump_operator_id',
                    'daily_collections.current_amount as total_amount', // Individual amount, not SUM
                    DB::raw('COALESCE(CAST(settlements.settlement_no AS CHAR), "No Settlement") as settlement_nos'),
                    DB::raw('daily_collections.created_at as order_date'),
                ])
                ->distinct();
                // Group by id to prevent duplicates

            // DAILY CARDS - Show individual records
            $daily_cards = DB::table('daily_cards')
                ->leftJoin('pump_operators', 'daily_cards.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', function ($join) {
                    $join->on(DB::raw('BINARY daily_cards.settlement_no'), '=', DB::raw('BINARY settlements.settlement_no'));
                })
                ->leftJoin('petro_daily_shifts', function($join) {
                    $join->on('daily_cards.shift_id', '=', 'petro_daily_shifts.id');
                })
                ->where('daily_cards.business_id', $business_id)
                ->select([
                    DB::raw('"daily_cards" as type'),
                    'daily_cards.id',
                    DB::raw('CAST(daily_cards.collection_no AS CHAR) as collection_form_no'),
                    DB::raw('DATE(daily_cards.date) as date'),
                    DB::raw('COALESCE(CAST(petro_daily_shifts.shift_no AS CHAR), CAST("-" AS CHAR)) as shift_number'),
                    'pump_operators.name as pump_operator_name',
                    'daily_cards.pump_operator_id',
                    'daily_cards.amount as total_amount', // Individual amount, not SUM
                    DB::raw('COALESCE(CAST(settlements.settlement_no AS CHAR), "-") as settlement_nos'),
                    DB::raw('daily_cards.date as order_date'),
                ])
                ->distinct();
                // Group by id to prevent duplicates

            // DAILY CREDIT SALES - Show individual records
            $daily_credit_sales = DB::table('daily_vouchers')
                ->leftJoin('pump_operators', 'daily_vouchers.operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', function ($join) {
                    $join->on(DB::raw('BINARY daily_vouchers.settlement_no'), '=', DB::raw('BINARY settlements.settlement_no'));
                })
                ->leftJoin('petro_daily_shifts', function($join) {
                    $join->on('daily_vouchers.shift_id', '=', 'petro_daily_shifts.id');
                })
                ->where('daily_vouchers.business_id', $business_id)
                ->select([
                    DB::raw('"daily_credit_sales" as type'),
                    'daily_vouchers.id',
                    DB::raw('CAST(daily_vouchers.daily_vouchers_no AS CHAR) as collection_form_no'),
                    DB::raw('DATE(daily_vouchers.transaction_date) as date'),
                    DB::raw('COALESCE(CAST(petro_daily_shifts.shift_no AS CHAR), CAST("-" AS CHAR)) as shift_number'),
                    'pump_operators.name as pump_operator_name',
                    DB::raw('daily_vouchers.operator_id as pump_operator_id'),
                    'daily_vouchers.total_amount as total_amount', // Individual amount, not SUM
                    DB::raw('COALESCE(CAST(settlements.settlement_no AS CHAR), "-") as settlement_nos'),
                    DB::raw('daily_vouchers.transaction_date as order_date'),
                ])
                 ->distinct();
                // Group by id to prevent duplicates

            // SHORTAGE/EXCESS - Show individual records
            $shortage_excess = DB::table('pump_operator_payments')
                ->leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', function ($join) {
                    $join->on(DB::raw('BINARY pump_operator_payments.settlement_no'), '=', DB::raw('BINARY settlements.settlement_no'));
                })
                ->leftJoin('petro_daily_shifts', function($join) {
                    $join->whereRaw('FIND_IN_SET(pump_operator_payments.pump_operator_id, petro_daily_shifts.pump_operator_assigned)')
                        ->whereRaw('DATE(pump_operator_payments.date_and_time) = DATE(petro_daily_shifts.date)');
                })
                ->where('pump_operator_payments.business_id', $business_id)
                ->whereIn('payment_type', ['shortage', 'excess'])
                ->select([
                    DB::raw('"shortage_excess" as type'),
                    'pump_operator_payments.id',
                    DB::raw('CAST("" AS CHAR) as collection_form_no'),
                    DB::raw('DATE(pump_operator_payments.date_and_time) as date'),
                    DB::raw('COALESCE(CAST(petro_daily_shifts.shift_no AS CHAR), CAST("-" AS CHAR)) as shift_number'),
                    'pump_operators.name as pump_operator_name',
                    'pump_operator_payments.pump_operator_id',
                    'pump_operator_payments.payment_amount as total_amount', // Individual amount, not SUM
                    DB::raw('COALESCE(CAST(settlements.settlement_no AS CHAR), "-") as settlement_nos'),
                    DB::raw('pump_operator_payments.date_and_time as order_date'),
                ])
                ->distinct();
                // Group by id to prevent duplicates

            // OTHER PAYMENTS - Show individual records
            $other = DB::table('pump_operator_payments')
                ->leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', function ($join) {
                    $join->on(DB::raw('BINARY pump_operator_payments.settlement_no'), '=', DB::raw('BINARY settlements.settlement_no'));
                })
                ->leftJoin('petro_daily_shifts', function($join) {
                    $join->whereRaw('FIND_IN_SET(pump_operator_payments.pump_operator_id, petro_daily_shifts.pump_operator_assigned)')
                        ->whereRaw('DATE(pump_operator_payments.date_and_time) = DATE(petro_daily_shifts.date)');
                })
                ->where('pump_operator_payments.business_id', $business_id)
                ->where('payment_type', 'other')
                ->select([
                    DB::raw('"other_payments" as type'),
                    'pump_operator_payments.id',
                    DB::raw('CAST("" AS CHAR) as collection_form_no'),
                    DB::raw('DATE(pump_operator_payments.date_and_time) as date'),
                    DB::raw('COALESCE(CAST(petro_daily_shifts.shift_no AS CHAR), CAST("-" AS CHAR)) as shift_number'),
                    'pump_operators.name as pump_operator_name',
                    'pump_operator_payments.pump_operator_id',
                    'pump_operator_payments.payment_amount as total_amount', // Individual amount, not SUM
                    DB::raw('COALESCE(CAST(settlements.settlement_no AS CHAR), "-") as settlement_nos'),
                    DB::raw('pump_operator_payments.date_and_time as order_date'),
                ])
                ->distinct();
                // Group by id to prevent duplicates

            // CHEQUE PAYMENTS - Show individual records
            $cheque = DB::table('pump_operator_payments')
                ->leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('settlements', function ($join) {
                    $join->on(DB::raw('BINARY pump_operator_payments.settlement_no'), '=', DB::raw('BINARY settlements.settlement_no'));
                })
                ->leftJoin('petro_daily_shifts', function($join) {
                    $join->whereRaw('FIND_IN_SET(pump_operator_payments.pump_operator_id, petro_daily_shifts.pump_operator_assigned)')
                        ->whereRaw('DATE(pump_operator_payments.date_and_time) = DATE(petro_daily_shifts.date)');
                })
                ->where('pump_operator_payments.business_id', $business_id)
                ->where('payment_type', 'cheque')
                ->select([
                    DB::raw('"cheque" as type'),
                    'pump_operator_payments.id',
                    DB::raw('CAST("" AS CHAR) as collection_form_no'),
                    DB::raw('DATE(pump_operator_payments.date_and_time) as date'),
                    DB::raw('COALESCE(CAST(petro_daily_shifts.shift_no AS CHAR), CAST("-" AS CHAR)) as shift_number'),
                    'pump_operators.name as pump_operator_name',
                    'pump_operator_payments.pump_operator_id',
                    'pump_operator_payments.payment_amount as total_amount', // Individual amount, not SUM
                    DB::raw('COALESCE(CAST(settlements.settlement_no AS CHAR), "-") as settlement_nos'),
                    DB::raw('pump_operator_payments.date_and_time as order_date'),
                ])
                ->distinct();
                // Group by id to prevent duplicates

            // FILTERS
            if (! empty(request()->pump_operator_id)) {
                $id = request()->pump_operator_id;
                $daily_cash->where('daily_collections.pump_operator_id', $id);
                $daily_cards->where('daily_cards.pump_operator_id', $id);
                $daily_credit_sales->where('daily_vouchers.operator_id', $id);
                $shortage_excess->where('pump_operator_payments.pump_operator_id', $id);
                $other->where('pump_operator_payments.pump_operator_id', $id);
                $cheque->where('pump_operator_payments.pump_operator_id', $id);
            }

            if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                $start = Carbon::parse(request()->start_date)->startOfDay();
                $end   = Carbon::parse(request()->end_date)->endOfDay();

                $daily_cash->whereBetween('daily_collections.created_at', [$start, $end]);
                $daily_cards->whereBetween('daily_cards.date', [$start, $end]);
                $daily_credit_sales->whereBetween('daily_vouchers.transaction_date', [$start, $end]);
                $shortage_excess->whereBetween('pump_operator_payments.date_and_time', [$start, $end]);
                $other->whereBetween('pump_operator_payments.date_and_time', [$start, $end]);
                $cheque->whereBetween('pump_operator_payments.date_and_time', [$start, $end]);
            }

            // TYPE FILTER - SIMPLIFIED UNION APPROACH
            $queries = [];

            switch (request()->daily_collection_type) {
                case 'daily_cash':
                    $queries[] = $daily_cash;
                    break;
                case 'daily_card':
                    $queries[] = $daily_cards;
                    break;
                case 'daily_voucher':
                    $queries[] = $daily_credit_sales;
                    break;
                case 'shortage_excess':
                    $queries[] = $shortage_excess;
                    break;
                case 'other':
                    $queries[] = $other;
                    break;
                case 'cheque':
                    $queries[] = $cheque;
                    break;
                default:
                    $queries = [
                        $daily_cash,
                        $daily_cards,
                        $daily_credit_sales,
                        $shortage_excess,
                        $other,
                        $cheque,
                    ];
            }

            // Build the final query
            $finalQuery = null;
            foreach ($queries as $index => $query) {
                if ($index === 0) {
                    $finalQuery = $query;
                } else {
                    $finalQuery->unionAll($query);
                }
            }

            // If no queries were set (shouldn't happen), use daily_cash as default
            if (! $finalQuery) {
                $finalQuery = $daily_cash;
            }

            // Add order by date descending and shift number
            $finalQuery->orderBy('order_date', 'desc')
                    ->orderBy('shift_number', 'desc')
                    ->orderBy('pump_operator_name', 'asc');

            // DATATABLE OUTPUT
            $fuel_tanks = Datatables::of($finalQuery)
                ->editColumn('total_amount', '{{@num_format($total_amount)}}')
                ->editColumn('type', function ($row) {
                    return ucfirst(str_replace('_', ' ', $row->type));
                })
                ->editColumn('date', '{{@format_date($date)}}')
                ->editColumn('shift_number', function ($row) {
                    return $row->shift_number ?? '-';
                })
                ->removeColumn('id')
                ->removeColumn('pump_operator_id');

            return $fuel_tanks->make(true);
        }
    }

    public function indexShortageExcess()
    {

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            if (request()->ajax()) {

                $query = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')

                    ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')

                    ->leftjoin('users', 'pump_operator_payments.created_by', 'users.id')

                    ->leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

                    ->where('pump_operator_payments.business_id', $business_id)

                    ->whereIn('payment_type', ['shortage', 'excess'])

                    ->select([

                        'pump_operator_payments.*',

                        'business_locations.name as location_name',

                        'pump_operators.name as pump_operator_name',

                        'settlements.settlement_no as settlement_noo',

                        'settlements.transaction_date as settlement_date',

                        'settlements.status as settlement_status',

                        'users.username as user',
                        DB::raw('(SELECT COALESCE(SUM(pop2.payment_amount), 0)
                            FROM pump_operator_payments pop2
                            WHERE pop2.business_id = pump_operator_payments.business_id
                              AND pop2.pump_operator_id = pump_operator_payments.pump_operator_id
                              AND pop2.id <= pump_operator_payments.id
                              AND pop2.settlement_no IS NULL
                              AND pop2.payment_type IN ("shortage", "excess")) as total_collection_raw'),

                    ]);

                if (! empty(request()->location_id)) {

                    $query->where('pump_operators.location_id', request()->location_id);

                }

                if (! empty(request()->settlement_id)) {

                    $query->where('settlements.id', request()->settlement_id);

                }

                if (! empty(request()->status)) {

                    if (request()->status == 'completed') {

                        $query->whereNotNull('settlements.settlement_no')->where('settlements.status', 0);

                    }

                    if (request()->status == 'pending') {

                        $query->where(function ($q) {

                            $q->whereNull('settlements.settlement_no')

                                ->orWhere('settlements.status', 1);

                        });

                    }

                }

                if (! empty(request()->pump_operator)) {

                    $query->where('pump_operator_payments.pump_operator_id', request()->pump_operator);

                }

                if (! empty(request()->settlement_no)) {

                    $query->where('pump_operator_payments.settlement_no', request()->settlement_no);

                }

                if (! empty(request()->start_date) && ! empty(request()->end_date)) {

                    $query->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date);

                    $query->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);

                }

                $query->orderBy('pump_operator_payments.date_and_time', 'desc');

                $fuel_tanks = Datatables::of($query)

                    ->addColumn(

                        'action',

                        '

                        @if(empty($settlement_no))@can("daily_shortage.edit") &nbsp; <button data-href="{{action(\'\Modules\DailyCollectionSW\Http\Controllers\DailyCollectionSWController@editShortage\', [$id])}}" data-container=".pump_modal" class="btn btn-success btn-xs btn-modal edit_reference_button"><i class="fa fa-pencil" aria-hidden="true"></i> @lang("lang_v1.edit")</button> &nbsp; @endcan @endif



                        @if(empty($is_used))@can("daily_collection.delete")<a class="btn btn-danger btn-xs delete_daily_collection" href="{{action(\'\Modules\DailyCollectionSW\Http\Controllers\DailyCollectionSWController@destroyShortageExcess\', [$id])}}"><i class="fa fa-trash" aria-hidden="true"></i> @lang("petro::lang.delete")</a>@endcan @endif'

                    )

                /**

                 * @ChangedBy Afes

                 * @Date 25-05-2021

                 * @Task 12700

                 */

                    ->addColumn('shortage_amount', '{{$payment_type == "shortage" ? @num_format($payment_amount) : ""}}')

                    ->addColumn('excess_amount', '{{$payment_type == "excess" ? @num_format(abs($payment_amount)) : ""}}')

                    ->editColumn(

                        'date_and_time',

                        '{{@format_date($date_and_time)}}'

                    )

                    ->addColumn('total_collection', function ($row) {
                        return $this->productUtil->num_f($row->total_collection_raw ?? 0);
                    })

                    ->editColumn(

                        'settlement_date',

                        '{{!empty($settlement_date) ? @format_date($settlement_date) : ""}}'

                    )

                    ->addColumn('status', function ($row) {

                        if (empty($row->settlement_noo) || $row->settlement_status == 1) {

                            return 'Pending';

                        } else {

                            return 'Completed';

                        }

                    })

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['action'])

                    ->make(true);

            }

        }

    }

    public function indexCheque()
    {

        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');

            if (request()->ajax()) {

                $query = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')

                    ->leftjoin('daily_cheque_payments', 'daily_cheque_payments.linked_payment_id', 'pump_operator_payments.id')

                    ->leftjoin('contacts', 'contacts.id', 'daily_cheque_payments.customer_id')

                    ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')

                    ->leftjoin('users', 'pump_operator_payments.created_by', 'users.id')

                    ->leftjoin('settlements', 'pump_operator_payments.settlement_no', 'settlements.id')

                    ->where('pump_operator_payments.business_id', $business_id)

                    ->whereIn('payment_type', ['cheque'])

                    ->select([

                        'pump_operator_payments.*',

                        'business_locations.name as location_name',

                        'pump_operators.name as pump_operator_name',

                        'settlements.settlement_no as settlement_noo',

                        'settlements.status as settlement_status',

                        'settlements.transaction_date as settlement_date',

                        'users.username as user',

                        'contacts.name as customer',

                        'daily_cheque_payments.cheque_date',

                        'daily_cheque_payments.bank_name',

                        'daily_cheque_payments.cheque_number',
                        DB::raw('(CASE
                            WHEN pump_operator_payments.settlement_no IS NOT NULL THEN
                                (SELECT COALESCE(SUM(pop2.payment_amount), 0)
                                 FROM pump_operator_payments pop2
                                 WHERE pop2.business_id = pump_operator_payments.business_id
                                   AND pop2.settlement_no = pump_operator_payments.settlement_no
                                   AND pop2.payment_type = "cheque")
                            ELSE
                                (SELECT COALESCE(SUM(pop2.payment_amount), 0)
                                 FROM pump_operator_payments pop2
                                 WHERE pop2.business_id = pump_operator_payments.business_id
                                   AND pop2.pump_operator_id = pump_operator_payments.pump_operator_id
                                   AND pop2.id <= pump_operator_payments.id
                                   AND pop2.settlement_no IS NULL
                                   AND pop2.payment_type = "cheque")
                        END) as total_collection_raw'),

                    ]);

                if (! empty(request()->location_id)) {

                    $query->where('pump_operators.location_id', request()->location_id);

                }

                if (! empty(request()->status)) {

                    if (request()->status == 'completed') {

                        $query->whereNotNull('settlements.settlement_no')->where('settlements.status', 0);

                    }

                    if (request()->status == 'pending') {

                        $query->where(function ($q) {

                            $q->whereNull('settlements.settlement_no')

                                ->orWhere('settlements.status', 1);

                        });

                    }

                }

                if (! empty(request()->settlement_id)) {

                    $query->where('settlements.id', request()->settlement_id);

                }

                if (! empty(request()->pump_operator)) {

                    $query->where('pump_operator_payments.pump_operator_id', request()->pump_operator);

                }

                if (! empty(request()->settlement_no)) {

                    $query->where('pump_operator_payments.settlement_no', request()->settlement_no);

                }

                if (! empty(request()->start_date) && ! empty(request()->end_date)) {

                    $query->whereDate('pump_operator_payments.date_and_time', '>=', request()->start_date);

                    $query->whereDate('pump_operator_payments.date_and_time', '<=', request()->end_date);

                }

                $query->orderBy('pump_operator_payments.date_and_time', 'desc');

                $fuel_tanks = Datatables::of($query)

                    ->addColumn(

                        'action',

                        ''

                    )

                /**

                 * @ChangedBy Afes

                 * @Date 25-05-2021

                 * @Task 12700

                 */

                    ->addColumn('payment_amount', '{{ @num_format($payment_amount) }}')

                    ->editColumn(

                        'date_and_time',

                        '{{@format_datetime($date_and_time)}}'

                    )

                    ->editColumn(

                        'cheque_date',

                        '{{!empty($cheque_date) ? @format_date($cheque_date) : ""}}'

                    )

                // ->addColumn('total_collection', function ($id) {

                //     $total = DB::table('pump_operator_payments')

                //         ->where('pump_operator_id', $id->pump_operator_id)

                //         ->where('id', '<=', $id->id)

                //         ->whereNull('settlement_no')

                //         ->whereIn('payment_type', ['cheque'])

                //         ->sum('payment_amount') ?? 0;

                //     return $this->productUtil->num_f($total);

                // })

                    ->addColumn('total_collection', function ($row) {
                        return $this->productUtil->num_f($row->total_collection_raw ?? 0);
                    })

                    ->editColumn(

                        'settlement_date',

                        '{{!empty($settlement_date) ? @format_date($settlement_date) : ""}}'

                    )

                    ->addColumn('status', function ($row) {

                        if (empty($row->settlement_noo) || $row->settlement_status == 1) {

                            return 'Pending';

                        } else {

                            return 'Completed';

                        }

                    })

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['action'])

                    ->make(true);

            }

        }

    }

    public function indexOther()
    {
        $business_id = request()->session()->get('user.business_id');

        if (request()->ajax()) {
            try {
                $query = DB::table('pump_operator_payments as pop')
                    ->leftJoin('pump_operators as po', 'pop.pump_operator_id', '=', 'po.id')
                    ->leftJoin('business_locations as bl', 'po.location_id', '=', 'bl.id')
                    ->leftJoin('users as u', 'pop.created_by', '=', 'u.id')
                    ->leftJoin('settlements as s', 'pop.settlement_no', '=', 's.id')
                    ->where('pop.business_id', $business_id)
                    ->where('pop.payment_type', 'other')
                    ->select([
                        'pop.id',
                        'pop.payment_amount',
                        'pop.date_and_time',
                        'pop.pump_operator_id',
                        'pop.settlement_no',
                        'pop.payment_type',
                        'bl.name as location_name',
                        'po.name as pump_operator_name',
                        's.settlement_no as settlement_noo',
                        's.status as settlement_status',
                        's.transaction_date as settlement_date',
                        'u.username as user',
                        DB::raw('(SELECT COALESCE(SUM(pop2.payment_amount), 0)
                            FROM pump_operator_payments pop2
                            WHERE pop2.business_id = pop.business_id
                              AND pop2.pump_operator_id = pop.pump_operator_id
                              AND pop2.id <= pop.id
                              AND pop2.settlement_no IS NULL
                              AND pop2.payment_type = "other") as total_collection_raw'),
                    ]);

                // Apply filters
                if (! empty(request()->location_id)) {
                    $query->where('po.location_id', request()->location_id);
                }

                if (! empty(request()->status)) {
                    if (request()->status == 'completed') {
                        $query->whereNotNull('s.settlement_no')->where('s.status', 0);
                    }
                    if (request()->status == 'pending') {
                        $query->where(function ($q) {
                            $q->whereNull('s.settlement_no')
                                ->orWhere('s.status', 1);
                        });
                    }
                }

                if (! empty(request()->settlement_id)) {
                    $query->where('s.id', request()->settlement_id);
                }

                if (! empty(request()->pump_operator)) {
                    $query->where('pop.pump_operator_id', request()->pump_operator);
                }

                if (! empty(request()->settlement_no)) {
                    $query->where('pop.settlement_no', request()->settlement_no);
                }

                if (! empty(request()->start_date) && ! empty(request()->end_date)) {
                    $query->whereDate('pop.date_and_time', '>=', request()->start_date);
                    $query->whereDate('pop.date_and_time', '<=', request()->end_date);
                }

                $query->orderBy('pop.date_and_time', 'desc');

                return Datatables::of($query)
                    ->addColumn('action', function ($row) {
                        $actionHtml = '';
                        // Add your permission check logic here
                        $actionHtml = '';
                        return $actionHtml;
                    })
                    ->addColumn('payment_amount', function ($row) {
                        return $this->productUtil->num_f($row->payment_amount);
                    })
                    ->editColumn('date_and_time', function ($row) {
                        return ! empty($row->date_and_time) ? $this->productUtil->format_datetime($row->date_and_time) : '';
                    })
                    ->addColumn('total_collection', function ($row) {
                        return $this->productUtil->num_f($row->total_collection_raw ?? 0);
                    })
                    ->editColumn('settlement_date', function ($row) {
                        return ! empty($row->settlement_date) ? $this->productUtil->format_date($row->settlement_date) : '';
                    })
                    ->addColumn('status', function ($row) {
                        if (empty($row->settlement_noo) || $row->settlement_status == 1) {
                            return 'Pending';
                        } else {
                            return 'Completed';
                        }
                    })
                    ->rawColumns(['action'])
                    ->make(true);

            } catch (\Exception $e) {
                Log::error('Daily Collection SW other payments DataTable failed', [
                    'business_id' => $business_id,
                    'message' => $e->getMessage(),
                ]);
                return response()->json(['error' => __('messages.something_went_wrong')], 500);
            }
        }
    }

    /**

     * Show the form for creating a new resource.

     * @return Response

     */

    public function create()
    {
        $businessId = (int) request()->session()->get('user.business_id');
        abort_if($businessId <= 0, 403, 'Business context is missing.');

        $locations = BusinessLocation::forDropdown($businessId);
        $locationsArray = is_object($locations) ? $locations->toArray() : (array) $locations;
        $default_location = array_key_first($locationsArray);

        $dailyShift = PetroDailyShift::where('business_id', $businessId)
            ->where('type', 'daily_collection_sw')
            ->whereIn('status', [1, 0])
            ->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END')
            ->orderByDesc('updated_at')
            ->first();

        $assigned_operators = $this->dailySwOperatorsForShift($businessId, $dailyShift);
        $all_pump_operators = $this->dailySwPumpOperatorDropdown($businessId, true, false);
        $pump_operators = ! empty($assigned_operators) ? $assigned_operators : $all_pump_operators;
        $all = empty($assigned_operators);

        $settledShiftNumbers = DB::table('pump_operator_assignments')
            ->join('settlements', 'pump_operator_assignments.settlement_id', '=', 'settlements.id')
            ->where('settlements.business_id', $businessId)
            ->where('settlements.status', 0)
            ->whereNotNull('pump_operator_assignments.shift_number')
            ->pluck('pump_operator_assignments.shift_number')
            ->all();

        $operatorShiftsMap = [];
        if ($dailyShift && ! empty($dailyShift->shift_no) && ! in_array($dailyShift->shift_no, $settledShiftNumbers, true)) {
            foreach ($pump_operators as $operatorId => $operatorName) {
                $operatorShiftsMap[$operatorId] = $dailyShift->shift_no;
            }
        }

        if (empty($operatorShiftsMap) && ! empty($pump_operators)) {
            $latestAssignments = PumpOperatorAssignment::where('business_id', $businessId)
                ->whereIn('pump_operator_id', array_keys((array) $pump_operators))
                ->whereNotNull('shift_number')
                ->where(function ($query) {
                    $query->where('status', 'open')->orWhere('status', 1)->orWhereNull('status');
                })
                ->orderByDesc('id')
                ->get(['pump_operator_id', 'shift_number'])
                ->unique('pump_operator_id');

            foreach ($latestAssignments as $assignment) {
                if (! in_array($assignment->shift_number, $settledShiftNumbers, true)) {
                    $operatorShiftsMap[$assignment->pump_operator_id] = $assignment->shift_number;
                }
            }
        }

        $lastCollectionFormNo = DailyCollection::where('business_id', $businessId)
            ->where('type', 'daily_collection_sw')
            ->whereNotNull('collection_form_no')
            ->selectRaw('MAX(CAST(collection_form_no AS UNSIGNED)) as max_form_no')
            ->value('max_form_no');
        $collection_form_no = max(1, ((int) $lastCollectionFormNo) + 1);

        $pumps = Pump::where('business_id', $businessId)
            ->orderBy('id')
            ->get();

        $openAssignments = PumpOperatorAssignment::query()
            ->leftJoin('pump_operators', 'pump_operators.id', '=', 'pump_operator_assignments.pump_operator_id')
            ->where('pump_operator_assignments.business_id', $businessId)
            ->where('pump_operator_assignments.status', 'open')
            ->select([
                'pump_operator_assignments.pump_id',
                'pump_operator_assignments.pump_operator_id',
                'pump_operators.name as pumper_name',
            ])
            ->orderByDesc('pump_operator_assignments.id')
            ->get()
            ->unique('pump_id')
            ->keyBy('pump_id');

        $assigned = [];
        foreach ($pumps as $pump) {
            $assignment = $openAssignments->get($pump->id);
            if ($assignment) {
                $pump->pumper_name = $assignment->pumper_name;
                $pump->pump_operator_id = $assignment->pump_operator_id;
                $assigned[] = ['name' => $assignment->pumper_name, 'id' => $assignment->pump_operator_id];
            }
        }

        $pendingRows = DailyCollection::query()
            ->leftJoin('settlements', 'daily_collections.settlement_id', '=', 'settlements.id')
            ->where('daily_collections.business_id', $businessId)
            ->where('daily_collections.type', 'daily_collection_sw')
            ->whereNull('settlements.settlement_no')
            ->select([
                'daily_collections.pump_operator_id',
                'daily_collections.shift_number',
                'daily_collections.shift_no',
            ])
            ->get();

        $missingOperatorIds = $pendingRows
            ->filter(function ($row) {
                return empty($row->shift_number) && empty($row->shift_no);
            })
            ->pluck('pump_operator_id')
            ->filter()
            ->unique()
            ->values();

        $latestPendingAssignments = collect();
        if ($missingOperatorIds->isNotEmpty()) {
            $latestPendingAssignments = PumpOperatorAssignment::where('business_id', $businessId)
                ->whereIn('pump_operator_id', $missingOperatorIds)
                ->whereNotNull('shift_number')
                ->orderByDesc('id')
                ->get(['pump_operator_id', 'shift_number'])
                ->unique('pump_operator_id')
                ->pluck('shift_number', 'pump_operator_id');
        }

        $pendingShiftNumbers = $pendingRows
            ->map(function ($row) use ($latestPendingAssignments) {
                return $row->shift_number
                    ?: $row->shift_no
                    ?: $latestPendingAssignments->get($row->pump_operator_id);
            })
            ->filter()
            ->unique()
            ->values();

        $dailyShiftOperators = collect($pump_operators);
        $shiftNumbers = collect($operatorShiftsMap);

        return view('dailycollectionsw::create', compact(
            'dailyShiftOperators', 'operatorShiftsMap', 'all', 'all_pump_operators',
            'assigned', 'shiftNumbers', 'assigned_operators', 'locations',
            'pump_operators', 'collection_form_no', 'default_location', 'pendingShiftNumbers'
        ));
    }

    public function dailyCardData(Request $request)
    {
        $businessId = (int) $request->session()->get('user.business_id');

        $query = DailyCard::query()
            ->leftJoin('pump_operators', 'daily_cards.pump_operator_id', '=', 'pump_operators.id')
            ->leftJoin('business_locations', 'business_locations.id', '=', 'pump_operators.location_id')
            ->leftJoin('contacts', 'daily_cards.customer_id', '=', 'contacts.id')
            ->leftJoin('accounts', 'daily_cards.card_type', '=', 'accounts.id')
            ->where('daily_cards.business_id', $businessId)
            ->whereNotNull('daily_cards.slip_no')
            ->select([
                'daily_cards.id',
                'daily_cards.date',
                'daily_cards.created_at',
                'daily_cards.pump_operator_id',
                'daily_cards.customer_id',
                'daily_cards.card_type',
                'daily_cards.card_number',
                'daily_cards.amount',
                'daily_cards.slip_no',
                'daily_cards.collection_no',
                'daily_cards.note',
                'daily_cards.settlement_no',
                'daily_cards.used_status',
                'accounts.name as type_name',
                'pump_operators.name as pump_operator_name',
                'contacts.name as customer_name',
                'business_locations.name as location_name',
                DB::raw('(SELECT s.settlement_no FROM settlements s
                    WHERE s.business_id = daily_cards.business_id
                      AND (BINARY s.settlement_no = BINARY daily_cards.settlement_no OR s.id = daily_cards.settlement_no)
                    ORDER BY (s.id = daily_cards.settlement_no) DESC, s.id DESC LIMIT 1) as settlement_nos'),
                DB::raw('(SELECT s.status FROM settlements s
                    WHERE s.business_id = daily_cards.business_id
                      AND (BINARY s.settlement_no = BINARY daily_cards.settlement_no OR s.id = daily_cards.settlement_no)
                    ORDER BY (s.id = daily_cards.settlement_no) DESC, s.id DESC LIMIT 1) as settlement_status'),
                DB::raw('(SELECT poa.shift_number FROM pump_operator_assignments poa
                    WHERE poa.pump_operator_id = daily_cards.pump_operator_id
                      AND poa.date_and_time <= daily_cards.created_at
                      AND (poa.close_date_and_time >= daily_cards.created_at OR poa.close_date_and_time IS NULL)
                    ORDER BY poa.date_and_time DESC, poa.id DESC LIMIT 1) as shift_number'),
                DB::raw('(SELECT COALESCE(SUM(dc_total.amount), 0) FROM daily_cards dc_total WHERE dc_total.business_id = daily_cards.business_id AND dc_total.pump_operator_id = daily_cards.pump_operator_id AND dc_total.id <= daily_cards.id) as total_collection_raw'),
            ])
            ->orderByDesc('daily_cards.date')
            ->orderByDesc('daily_cards.id');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('daily_cards.date', [$request->start_date, $request->end_date]);
        }
        if ($request->filled('pump_operator_id')) {
            $query->where('daily_cards.pump_operator_id', $request->pump_operator_id);
        }
        if ($request->filled('customer_id')) {
            $query->where('daily_cards.customer_id', $request->customer_id);
        }
        if ($request->filled('card_type')) {
            $query->where('daily_cards.card_type', $request->card_type);
        }
        if ($request->filled('slip_no')) {
            $query->where('daily_cards.slip_no', 'like', '%' . $request->slip_no . '%');
        }
        if ($request->filled('card_number')) {
            $query->where('daily_cards.card_number', 'like', '%' . $request->card_number . '%');
        }
        if ($request->filled('location_id')) {
            $query->where('business_locations.id', $request->location_id);
        }
        if ($request->filled('settlement_id')) {
            $settlementId = $request->input('settlement_id');
            $query->where(function ($builder) use ($settlementId) {
                $builder->where('daily_cards.settlement_no', $settlementId)
                    ->orWhereExists(function ($subQuery) use ($settlementId) {
                        $subQuery->select(DB::raw(1))
                            ->from('settlements as settlement_filter')
                            ->whereColumn('settlement_filter.business_id', 'daily_cards.business_id')
                            ->where('settlement_filter.id', $settlementId)
                            ->where(function ($match) {
                                $match->whereColumn('settlement_filter.id', 'daily_cards.settlement_no')
                                    ->orWhereRaw('BINARY settlement_filter.settlement_no = BINARY daily_cards.settlement_no');
                            });
                    });
            });
        }
        if ($request->filled('status')) {
            if ($request->status === 'completed') {
                $query->whereNotNull('daily_cards.settlement_no');
            } elseif ($request->status === 'pending') {
                $query->whereNull('daily_cards.settlement_no');
            }
        }

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                // Keep the column present for DataTables. Existing edit/delete
                // actions remain available through the modal forms themselves.
                return '';
            })
            ->editColumn('date', function ($row) {
                return $row->date ? $this->commonUtil->format_date($row->date) : '';
            })
            ->editColumn('amount', function ($row) {
                return $this->productUtil->num_f($row->amount ?? 0);
            })
            ->addColumn('total_collection', function ($row) {
                return $this->productUtil->num_f($row->total_collection_raw ?? 0);
            })
            ->addColumn('status', function ($row) {
                return !empty($row->settlement_no) ? 'Completed' : 'Pending';
            })
            ->editColumn('shift_number', function ($row) {
                return $row->shift_number ?? '-';
            })
            ->editColumn('settlement_nos', function ($row) {
                return $row->settlement_nos ?? ($row->settlement_no ?? '-');
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function createDailyCardsModal(Request $request)
    {
        $business_id = (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));

        abort_if($business_id <= 0, 403, 'Business context is missing.');

        $pump_operators = $this->dailySwPumpOperatorDropdown($business_id, true, false);
        $customers = Contact::customersDropdown($business_id, false, true, 'customer');

        $card_types = collect();
        $card_group = AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if ($card_group) {
            $card_types = Account::where('business_id', $business_id)
                ->where('asset_type', $card_group->id)
                ->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        $last_collection = DailyCard::where('business_id', $business_id)
            ->whereNotNull('collection_form_no')
            ->orderByDesc('id')
            ->value('collection_form_no');
        $collection_form_no = ((int) $last_collection) + 1;
        if ($collection_form_no < 1) {
            $collection_form_no = 1;
        }

        $dailyShift = PetroDailyShift::where('business_id', $business_id)
            ->where('type', 'daily_collection_sw')
            ->whereIn('status', [1, 0])
            ->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END')
            ->orderByDesc('updated_at')
            ->first();
        $daily_shift_no = optional($dailyShift)->shift_no;

        return view('dailycollectionsw::partials.create_daily_cards', compact(
            'pump_operators', 'customers', 'card_types', 'collection_form_no', 'daily_shift_no'
        ));
    }

    /** Open the Credit Sales modal under the Daily Collection SW route context. */
    public function createCreditSalesModal(Request $request)
    {
        $business_id = (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));

        abort_if($business_id <= 0, 403, 'Business context is missing.');

        $pump_operators = $this->dailySwPumpOperatorDropdown($business_id, true, false);

        // IS1684: Build tenant dropdowns directly so copied tenant databases
        // cannot return empty Select2 lists because of helper-specific filters.
        $customersQuery = Contact::where('business_id', $business_id)
            ->where(function ($query) {
                $query->where('type', 'customer')->orWhere('type', 'both');
            });
        if (SchemaCapabilities::hasColumn('contacts', 'is_inactive')) {
            $customersQuery->where(function ($query) {
                $query->where('is_inactive', 0)->orWhereNull('is_inactive');
            });
        }
        if (SchemaCapabilities::hasColumn('contacts', 'deleted_at')) {
            $customersQuery->whereNull('deleted_at');
        }
        $customers = $customersQuery->orderBy('name')->pluck('name', 'id');

        $productsQuery = Product::where('business_id', $business_id);
        if (SchemaCapabilities::hasColumn('products', 'is_inactive')) {
            $productsQuery->where(function ($query) {
                $query->where('is_inactive', 0)->orWhereNull('is_inactive');
            });
        }
        if (SchemaCapabilities::hasColumn('products', 'deleted_at')) {
            $productsQuery->whereNull('deleted_at');
        }
        $products = $productsQuery->orderBy('name')->pluck('name', 'id');

        $dailyShift = PetroDailyShift::where('business_id', $business_id)
            ->where('type', 'daily_collection_sw')
            ->whereIn('status', [1, 0])
            ->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END')
            ->orderByDesc('updated_at')
            ->first();
        $daily_shift_no = optional($dailyShift)->shift_no;
        $default_shift_id = $daily_shift_no;

        return view('dailycollectionsw::partials.create_daily_voucher', compact(
            'pump_operators', 'customers', 'products', 'daily_shift_no', 'default_shift_id'
        ));
    }

    public function getByOperator(Request $request, $operator)
    {
        $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');

        if (! $business_id) {
            return response()->json(['error' => 'business_id is required'], 400);
        }

        $shifts = $this->dailyShiftOptionsForOperator((int) $business_id, (int) $operator);

        return response()->json($shifts->values()->all());
    }

    public function getDailyShiftOptions(Request $request)
    {
        $business_id = $request->session()->get('business.id') ?: $request->session()->get('user.business_id');
        $operator_id = (int) ($request->input('pump_operator_id') ?: $request->input('operator_id'));

        if (! $business_id) {
            return response()->json(['results' => []]);
        }

        $shiftRows = $this->dailyShiftOptionsForOperator((int) $business_id, $operator_id);

        // IS1583 fallback: if current assignment columns are inconsistent, expose latest current SW shifts.
        if ($shiftRows->isEmpty() && SchemaCapabilities::hasTable('petro_daily_shifts')) {
            $query = DB::table('petro_daily_shifts');
            if (SchemaCapabilities::hasColumn('petro_daily_shifts', 'business_id')) {
                $query->where(function ($q) use ($business_id) {
                    $q->where('business_id', $business_id)->orWhereNull('business_id');
                });
            }
            if (SchemaCapabilities::hasColumn('petro_daily_shifts', 'type')) {
                $query->where(function ($q) {
                    $q->where('type', 'daily_collection_sw')->orWhereNull('type');
                });
            }
            if (SchemaCapabilities::hasColumn('petro_daily_shifts', 'status')) {
                $query->whereIn('status', [0, 1]);
                $query->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END');
            }
            $shiftColumn = SchemaCapabilities::hasColumn('petro_daily_shifts', 'shift_no') ? 'shift_no' : 'id';
            $shiftRows = $query->orderBy('id', 'desc')->limit(10)->pluck($shiftColumn)->filter()->values();
        }

        $results = $shiftRows
            ->map(function ($shift_no) {
                return ['id' => $shift_no, 'text' => $shift_no];
            })
            ->values()
            ->all();

        return response()->json(['results' => $results]);
    }

    protected function dailyShiftOptionsForOperator(int $business_id, int $operator_id = 0)
    {
        return $this->dailySwShiftOptionsForOperatorSafe((int) $business_id, (int) $operator_id);
    }

    /**

     * Store a newly created resource in storage.

     * @param  Request $request

     * @return Response

     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required',
            'collection_form_no' => 'required|string|max:100',
            'pump_operator_id' => 'required|integer',
            'daily_shift_no' => 'required|string|max:100',
            'balance_collection' => 'required|numeric',
            'current_amount' => 'required|numeric|min:0',
            'location_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => $validator->errors()->first(),
            ])->withInput();
        }

        $businessId = (int) ($request->session()->get('user.business_id') ?: $request->session()->get('business.id'));
        if ($businessId <= 0) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => 'Business context is missing. Please refresh and try again.',
            ])->withInput();
        }

        if ($this->transactionUtil->hasReviewed($request->input('transaction_date'))) {
            return redirect()->back()->with('status', ['success' => 0, 'msg' => __('lang_v1.review_first')])->withInput();
        }

        if ($this->transactionUtil->get_review($request->input('transaction_date'), $request->input('transaction_date'))) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => "You can't add a collection for an already reviewed date",
            ])->withInput();
        }

        $collectionFormNo = trim((string) $request->input('collection_form_no'));
        $notificationData = null;

        try {
            DB::transaction(function () use ($request, $businessId, $collectionFormNo, &$notificationData) {
                $duplicate = DailyCollection::where('business_id', $businessId)
                    ->where('type', 'daily_collection_sw')
                    ->where('collection_form_no', $collectionFormNo)
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new \RuntimeException('duplicate_collection_form');
                }

                $dailyShiftId = PetroDailyShift::where('business_id', $businessId)
                    ->where('shift_no', $request->input('daily_shift_no'))
                    ->where('type', 'daily_collection_sw')
                    ->value('id');

                $pumpOperator = PumpOperator::where('business_id', $businessId)
                    ->where('id', (int) $request->input('pump_operator_id'))
                    ->firstOrFail();

                BusinessLocation::where('business_id', $businessId)
                    ->where('id', (int) $request->input('location_id'))
                    ->firstOrFail();

                $collection = DailyCollection::create([
                    'business_id' => $businessId,
                    'shift_number' => $request->input('daily_shift_no'),
                    'shift_id' => $dailyShiftId,
                    'collection_form_no' => $collectionFormNo,
                    'pump_operator_id' => $pumpOperator->id,
                    'location_id' => (int) $request->input('location_id'),
                    'balance_collection' => (float) $request->input('balance_collection'),
                    'current_amount' => (float) $request->input('current_amount'),
                    'created_by' => auth()->id(),
                    'type' => 'daily_collection_sw',
                ]);

                $transaction = Transaction::create([
                    'business_id' => $businessId,
                    'location_id' => $collection->location_id,
                    'type' => 'daily_collection',
                    'sub_type' => 'Cash',
                    'status' => 'final',
                    'ref_no' => 'Daily Collection #' . $collection->collection_form_no,
                    'final_total' => $collection->current_amount,
                    'created_by' => auth()->id(),
                    'transaction_date' => Carbon::parse($request->input('transaction_date')),
                ]);

                $cashAccountId = (int) $this->transactionUtil->account_exist_return_id('Cash');
                if ($cashAccountId <= 0) {
                    throw new \RuntimeException('cash_account_missing');
                }

                $contactId = Contact::where('business_id', $businessId)
                    ->where(function ($query) {
                        $query->where('type', 'customer')->orWhere('type', 'both');
                    })
                    ->orderBy('id')
                    ->value('id');

                AccountTransaction::createAccountTransaction([
                    'amount' => $collection->current_amount,
                    'account_id' => $cashAccountId,
                    'contact_id' => $contactId,
                    'type' => 'debit',
                    'sub_type' => null,
                    'operation_date' => Carbon::parse($request->input('transaction_date'))->format('Y-m-d'),
                    'created_by' => $collection->created_by,
                    'transaction_id' => $transaction->id,
                    'transaction_payment_id' => null,
                    'note' => 'Daily Collection #' . $collection->collection_form_no,
                ]);

                $collection->added_to_account = $cashAccountId;
                $collection->save();

                $totals = DailyCollection::where('business_id', $businessId)
                    ->where('type', 'daily_collection_sw')
                    ->where('pump_operator_id', $pumpOperator->id)
                    ->selectRaw('COALESCE(SUM(current_amount), 0) as current_total')
                    ->selectRaw('COALESCE(SUM(balance_collection), 0) as balance_total')
                    ->selectRaw('COALESCE(SUM(CASE WHEN settlement_id IS NULL AND added_to_account IS NULL THEN current_amount ELSE 0 END), 0) as pending_total')
                    ->first();

                $notificationData = [
                    'date' => $request->input('transaction_date'),
                    'time' => now()->format('H:i'),
                    'pump_operator' => $pumpOperator->name,
                    'amount' => $this->transactionUtil->num_f($collection->current_amount),
                    'pumper_cummulative_amount' => $this->transactionUtil->num_f((float) $totals->current_total - (float) $totals->balance_total),
                    'total_amount' => $this->transactionUtil->num_f((float) $totals->pending_total),
                ];

                PetroShift::updateOrCreate([
                    'business_id' => $businessId,
                    'shift_date' => Carbon::parse($request->input('transaction_date'))->format('Y-m-d'),
                    'status' => 0,
                    'pump_operator_id' => $pumpOperator->id,
                ], [
                    'business_id' => $businessId,
                    'pump_operator_id' => $pumpOperator->id,
                    'status' => 0,
                    'shift_date' => Carbon::parse($request->input('transaction_date'))->format('Y-m-d'),
                ]);

                $assignmentQuery = PumpOperatorAssignment::where('shift_number', $request->input('daily_shift_no'));
                if (SchemaCapabilities::hasColumn('pump_operator_assignments', 'business_id')) {
                    $assignmentQuery->where('business_id', $businessId);
                }
                $assignment = $assignmentQuery->latest('id')->first();
                if ($assignment) {
                    $assignment->pump_operator_id = $pumpOperator->id;
                    $assignment->save();
                }
            });

            if ($notificationData) {
                try {
                    $this->notificationUtil->sendPetroNotification('daily_collection', $notificationData);
                } catch (\Throwable $notificationException) {
                    Log::warning('Daily Collection SW notification failed', [
                        'business_id' => $businessId,
                        'message' => $notificationException->getMessage(),
                    ]);
                }
            }

            return redirect()->back()->with('status', [
                'success' => 1,
                'msg' => __('petro::lang.daily_collection_add_success'),
            ]);
        } catch (\RuntimeException $exception) {
            $message = $exception->getMessage() === 'duplicate_collection_form'
                ? 'This collection form number already exists.'
                : ($exception->getMessage() === 'cash_account_missing'
                    ? 'Cash account is not configured.'
                    : __('messages.something_went_wrong'));

            return redirect()->back()->with('status', ['success' => 0, 'msg' => $message])->withInput();
        } catch (\Throwable $exception) {
            Log::error('Daily Collection SW save failed', [
                'business_id' => $businessId,
                'collection_form_no' => $collectionFormNo,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ])->withInput();
        }
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

    public function edit($id)
    {
        $businessId = (int) request()->session()->get('user.business_id');

        $data = DailyCollection::where('business_id', $businessId)
            ->where('type', 'daily_collection_sw')
            ->findOrFail($id);

        $locations = BusinessLocation::forDropdown($businessId);
        $pump_operators = $this->dailySwPumpOperatorDropdown($businessId, true, false);

        $dailyShift = PetroDailyShift::where('business_id', $businessId)
            ->where('type', 'daily_collection_sw')
            ->whereIn('status', [1, 0])
            ->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END')
            ->orderByDesc('updated_at')
            ->first();

        $assigned_operators = $this->dailySwOperatorsForShift($businessId, $dailyShift);

        $pumps = Pump::where('business_id', $businessId)
            ->orderBy('id')
            ->get();

        $openAssignments = PumpOperatorAssignment::query()
            ->leftJoin('pump_operators', 'pump_operators.id', '=', 'pump_operator_assignments.pump_operator_id')
            ->where('pump_operator_assignments.business_id', $businessId)
            ->where('pump_operator_assignments.status', 'open')
            ->select([
                'pump_operator_assignments.pump_id',
                'pump_operator_assignments.pump_operator_id',
                'pump_operators.name as pumper_name',
            ])
            ->orderByDesc('pump_operator_assignments.id')
            ->get()
            ->unique('pump_id')
            ->keyBy('pump_id');

        $assigned = [];
        foreach ($pumps as $pump) {
            $assignment = $openAssignments->get($pump->id);
            if ($assignment) {
                $pump->pumper_name = $assignment->pumper_name;
                $pump->pump_operator_id = $assignment->pump_operator_id;
                $assigned[] = ['name' => $assignment->pumper_name, 'id' => $assignment->pump_operator_id];
            }
        }

        $pendingRows = DailyCollection::query()
            ->leftJoin('settlements', 'daily_collections.settlement_id', '=', 'settlements.id')
            ->where('daily_collections.business_id', $businessId)
            ->where('daily_collections.type', 'daily_collection_sw')
            ->whereNull('settlements.settlement_no')
            ->select([
                'daily_collections.pump_operator_id',
                'daily_collections.shift_number',
                'daily_collections.shift_no',
            ])
            ->get();

        $missingOperatorIds = $pendingRows
            ->filter(function ($row) {
                return empty($row->shift_number) && empty($row->shift_no);
            })
            ->pluck('pump_operator_id')
            ->filter()
            ->unique()
            ->values();

        $latestAssignmentShifts = collect();
        if ($missingOperatorIds->isNotEmpty()) {
            $latestAssignmentShifts = PumpOperatorAssignment::where('business_id', $businessId)
                ->whereIn('pump_operator_id', $missingOperatorIds)
                ->whereNotNull('shift_number')
                ->orderByDesc('id')
                ->get(['pump_operator_id', 'shift_number'])
                ->unique('pump_operator_id')
                ->pluck('shift_number', 'pump_operator_id');
        }

        $settledShiftNumbers = DB::table('pump_operator_assignments')
            ->join('settlements', 'pump_operator_assignments.settlement_id', '=', 'settlements.id')
            ->where('settlements.business_id', $businessId)
            ->where('settlements.status', 0)
            ->whereNotNull('pump_operator_assignments.shift_number')
            ->pluck('pump_operator_assignments.shift_number')
            ->all();

        $shiftNumbers = $pendingRows
            ->map(function ($row) use ($latestAssignmentShifts) {
                return $row->shift_number
                    ?: $row->shift_no
                    ?: $latestAssignmentShifts->get($row->pump_operator_id);
            })
            ->filter(function ($shift) use ($settledShiftNumbers) {
                return ! empty($shift) && ! in_array($shift, $settledShiftNumbers, true);
            })
            ->unique()
            ->values()
            ->mapWithKeys(function ($shift) {
                return [$shift => $shift];
            });

        return view('dailycollectionsw::edit', compact(
            'assigned', 'shiftNumbers', 'assigned_operators', 'locations',
            'pump_operators', 'data', 'dailyShift'
        ));
    }

    public function editShortage($id)
    {
        $businessId = (int) request()->session()->get('user.business_id');

        $data = PumpOperatorPayment::where('business_id', $businessId)
            ->whereIn('payment_type', ['shortage', 'excess'])
            ->findOrFail($id);

        $pump_operators = $this->dailySwPumpOperatorDropdown($businessId, true, false);

        return view('dailycollectionsw::edit_shortage', compact('pump_operators', 'data'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'collection_form_no' => 'required|string|max:100',
            'pump_operator_id' => 'required|integer',
            'balance_collection' => 'required|numeric',
            'current_amount' => 'required|numeric|min:0',
            'location_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => $validator->errors()->first(),
            ])->withInput();
        }

        $businessId = (int) request()->session()->get('user.business_id');

        if ($this->transactionUtil->hasReviewed($request->input('transaction_date'))
            || $this->transactionUtil->get_review($request->input('transaction_date'), $request->input('transaction_date'))) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('lang_v1.review_first'),
            ])->withInput();
        }

        try {
            $collection = DailyCollection::where('business_id', $businessId)
                ->where('type', 'daily_collection_sw')
                ->findOrFail($id);

            if (! empty($collection->settlement_id)) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => 'A settled collection cannot be edited.',
                ]);
            }

            $duplicate = DailyCollection::where('business_id', $businessId)
                ->where('type', 'daily_collection_sw')
                ->where('collection_form_no', trim((string) $request->input('collection_form_no')))
                ->where('id', '!=', $collection->id)
                ->exists();

            if ($duplicate) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => 'This collection form number already exists.',
                ])->withInput();
            }

            PumpOperator::where('business_id', $businessId)->where('id', $request->input('pump_operator_id'))->firstOrFail();
            BusinessLocation::where('business_id', $businessId)->where('id', $request->input('location_id'))->firstOrFail();

            $collection->update([
                'daily_shift' => $request->input('daily_shift_number', ''),
                'shift_no' => $request->input('shift_number', ''),
                'shift_number' => $request->input('shift_number', ''),
                'collection_form_no' => trim((string) $request->input('collection_form_no')),
                'pump_operator_id' => (int) $request->input('pump_operator_id'),
                'location_id' => (int) $request->input('location_id'),
                'balance_collection' => (float) $request->input('balance_collection'),
                'current_amount' => (float) $request->input('current_amount'),
            ]);

            return redirect()->back()->with('status', [
                'success' => 1,
                'msg' => __('petro::lang.daily_collection_add_success'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Daily Collection SW update failed', [
                'business_id' => $businessId,
                'collection_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ])->withInput();
        }
    }

    public function updateShortage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'pump_operator_id' => 'required|integer',
            'payment_amount' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => $validator->errors()->first(),
            ])->withInput();
        }

        $businessId = (int) request()->session()->get('user.business_id');

        if ($this->transactionUtil->hasReviewed($request->input('transaction_date'))
            || $this->transactionUtil->get_review($request->input('transaction_date'), $request->input('transaction_date'))) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('lang_v1.review_first'),
            ])->withInput();
        }

        try {
            $payment = PumpOperatorPayment::where('business_id', $businessId)
                ->whereIn('payment_type', ['shortage', 'excess'])
                ->findOrFail($id);

            if (! empty($payment->settlement_no) || ! empty($payment->is_used)) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => 'A used or settled payment cannot be edited.',
                ]);
            }

            PumpOperator::where('business_id', $businessId)
                ->where('id', (int) $request->input('pump_operator_id'))
                ->firstOrFail();

            $payment->update([
                'pump_operator_id' => (int) $request->input('pump_operator_id'),
                'payment_amount' => (float) $request->input('payment_amount'),
            ]);

            return redirect()->back()->with('status', [
                'success' => 1,
                'msg' => __('lang_v1.success'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Daily Collection SW shortage/excess update failed', [
                'business_id' => $businessId,
                'payment_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ])->withInput();
        }
    }

    public function destroy($id)
    {
        $businessId = (int) request()->session()->get('user.business_id');

        try {
            $collection = DailyCollection::where('business_id', $businessId)
                ->where('type', 'daily_collection_sw')
                ->findOrFail($id);

            if (! empty($collection->settlement_id)) {
                return ['success' => false, 'msg' => 'A settled collection cannot be deleted.'];
            }

            DB::transaction(function () use ($collection, $businessId) {
                $transaction = Transaction::where('business_id', $businessId)
                    ->where('type', 'daily_collection')
                    ->where('ref_no', 'Daily Collection #' . $collection->collection_form_no)
                    ->first();

                if ($transaction) {
                    AccountTransaction::where('business_id', $businessId)
                        ->where('transaction_id', $transaction->id)
                        ->delete();
                    $transaction->delete();
                }

                $collection->delete();
            });

            return [
                'success' => true,
                'msg' => __('petro::lang.daily_collection_delete_success'),
            ];
        } catch (\Throwable $exception) {
            Log::error('Daily Collection SW delete failed', [
                'business_id' => $businessId,
                'collection_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    public function destroyShortageExcess($id)
    {
        $businessId = (int) request()->session()->get('user.business_id');

        try {
            $payment = PumpOperatorPayment::where('business_id', $businessId)
                ->whereIn('payment_type', ['shortage', 'excess'])
                ->findOrFail($id);

            if (! empty($payment->settlement_no) || ! empty($payment->is_used)) {
                return ['success' => false, 'msg' => 'A used or settled payment cannot be deleted.'];
            }

            $payment->delete();

            return ['success' => true, 'msg' => __('lang_v1.success')];
        } catch (\Throwable $exception) {
            Log::error('Daily Collection SW shortage/excess delete failed', [
                'business_id' => $businessId,
                'payment_id' => $id,
                'message' => $exception->getMessage(),
            ]);

            return ['success' => false, 'msg' => __('messages.something_went_wrong')];
        }
    }

    /**

     * Remove the specified resource from storage.

     * @return Response

     */

    public function print($pump_operator_id)
    {
        $businessId = (int) request()->session()->get('user.business_id');

        $daily_collection = DailyCollection::where('business_id', $businessId)
            ->where('type', 'daily_collection_sw')
            ->findOrFail($pump_operator_id);

        $pump_operator = PumpOperator::where('business_id', $businessId)
            ->findOrFail($daily_collection->pump_operator_id);

        $business_details = Business::findOrFail($businessId);

        return view('dailycollectionsw::partials.print', compact(
            'pump_operator', 'business_details', 'daily_collection'
        ));
    }

    public function getBalanceCollection($pump_operator_id, Request $request)
    {
        $business_id = session()->get('user.business_id');
        $shift_no    = $request->input('shift_no');

        if (!$shift_no) {
            return ['balance_collection' => 0];
        }

        $operatorExists = PumpOperator::where('business_id', $business_id)
            ->where('id', $pump_operator_id)
            ->exists();
        if (! $operatorExists) {
            abort(404);
        }

        $balance_collection = DailyCollection::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('shift_number', $shift_no)
            ->where('type', 'daily_collection_sw')
            ->sum('current_amount') ?? 0;

        return ['balance_collection' => $balance_collection];
    }

    // In your Controller (e.g., CashController.php)

    // CashController.php

    // In your Controller (e.g., DailyCollectionController.php)

    public function showDailyCashStatus()
    {

        // Initialize empty array if no transactions exist

        $cashGivenAmounts = [];

        try {

            $cashGivenAmounts = DB::table('transactions')

                ->where('group', 'cash_given')

                ->orderBy('created_at', 'desc')

                ->get()

                ->map(function ($item) {

                    $value = json_decode($item->value, true);

                    return [

                        'user'     => Auth::user()->name ?? $value['user'] ?? 'System',

                        'amount'   => $value['amount'] ?? 0,

                        'datetime' => $value['datetime'] ?? $item->created_at,

                    ];

                })

                ->toArray();

        } catch (\Exception $e) {

            // Log error but continue with empty array

            \Log::error('Error fetching cash transactions: ' . $e->getMessage());

        }

        $dailyCashShiftNumbers = range(1, 3);

        $isShiftRequired = config('petro.require_shift_selection', false);

        return view('dailycollectionsw::partials.daily_cash_status', [

            'cashGivenAmounts'      => $cashGivenAmounts,

            'dailyCashShiftNumbers' => $dailyCashShiftNumbers,

            'isShiftRequired'       => $isShiftRequired,

        ]);

    }

    public function storeCashGiven(Request $request)
    {

        $validated = $request->validate([

            'amount' => 'required|numeric|min:0.01',

            'notes'  => 'nullable|string',

        ]);

        $transactionValue = [

            'user'     => auth()->user()->name,

            'amount'   => $validated['amount'],

            'datetime' => now()->toDateTimeString(),

            'notes'    => $validated['notes'] ?? null,

        ];

        DB::table('transactions')->insert([

            'language'   => app()->getLocale(),

            'group'      => 'cash_given',

            'key'        => 'transaction_' . time(),

            'value'      => json_encode($transactionValue),

            'created_at' => now(),

            'updated_at' => now(),

        ]);

        return response()->json([

            'success'     => true,

            'transaction' => $transactionValue,

        ]);

    }


    /**
     * DailyCollectionSW-owned shift close action.
     * Keeps this module independent from Petro/PetroGeneral/PetroDirect controllers.
     */
    public function shiftcloseStatus(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|string',
        ]);

        $businessId = auth()->user()->business_id;

        $shift = PetroDailyShift::whereRaw(
                'LOWER(shift_no) = ?',
                [strtolower($request->shift_id)]
            )
            ->where('business_id', $businessId)
            ->where('type', 'daily_collection_sw')
            ->first();

        if (! $shift) {
            return response()->json([
                'message' => 'Shift not found or not saved.',
            ], 404);
        }

        $shift->update([
            'status' => 2,
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Shift closed successfully.',
        ]);
    }
}

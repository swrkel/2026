<?php
namespace Modules\PetroGeneral\Http\Controllers;

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
use Carbon\Carbon;
use Modules\PetroGeneral\Entities\PetroShift;
use Modules\PetroGeneral\Entities\Pump;
use Modules\PetroGeneral\Entities\PumperDayEntry;
use Modules\PetroGeneral\Entities\PumpOperator;
use Modules\PetroGeneral\Entities\PumpOperatorAssignment;
use Modules\PetroGeneral\Entities\PumpOperatorOtherSale;
use Modules\PetroGeneral\Entities\PumpOperatorPayment;
use Yajra\DataTables\Facades\DataTables;

class PumperDayEntryController extends Controller
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

        $business_id = Auth::user()->business_id;

        $only_pumper = request()->only_pumper;
        $pump_operator_id = Auth::user()->pump_operator_id;

        if (!$this->moduleUtil->hasThePermissionInSubscription($business_id, 'petro_general')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {

            $already_added_shortage = [];
            $already_added_excess = [];

            $business_details = Business::find($business_id);

            $shift_id = request()->shift_id;
            $shift_filter_exists = request()->exists('shift_id');
            $all_shifts_selected = $shift_filter_exists && empty($shift_id);
            if (!$shift_filter_exists && empty($shift_id)) {
                $latest_shift_query = PetroShift::where('business_id', $business_id)->orderBy('id', 'desc');
                if ($only_pumper) {
                    $latest_shift_query->where('pump_operator_id', $pump_operator_id);
                }
                $shift_id = $latest_shift_query->value('id');
            }
            if (!$all_shifts_selected && empty($shift_id)) {
                return DataTables::of(collect())->make(true);
            }

            $data = new Collection();

            // Query day entries from meter sales (existing flow)
            $meter_sale_entries = DB::table('pump_operator_meter_sale_details as pomsd')
                ->leftJoin('pump_operator_meter_sales as poms', 'poms.id', '=', 'pomsd.sale_id')
                ->leftJoin('pump_operators as po', 'po.id', '=', 'poms.pump_operator_id')
                ->leftJoin('pumps', 'pumps.id', '=', 'pomsd.pump_id')
                ->leftJoin('pump_operator_assignments as poa', function ($join) {
                $join->on('poa.pump_operator_id', '=', 'poms.pump_operator_id')
                    ->on('poa.pump_id', '=', 'pomsd.pump_id')
                    ->on('poa.shift_id', '=', 'poms.shift_id');
            })
                ->leftJoin('pumper_day_entries as pde', 'pde.pumper_assignment_id', '=', 'poa.id')
                ->leftJoin('settlements as s', 's.id', '=', 'poa.settlement_id')
                ->leftJoin('petro_shifts as ps', 'ps.id', '=', 'poa.shift_id')
                ->leftJoin('business_locations as bl', 'bl.id', '=', 'po.location_id')
                ->where('poms.business_id', $business_id)
                ->where(function ($q) {
                $q->whereNull('poms.source')
                    ->orWhere('poms.source', '!=', 'payment');
            })
                ->when(!empty($shift_id), function ($q) use ($shift_id) {
                return $q->where('poms.shift_id', $shift_id);
            })
                // For pumper view, restrict to their own entries; for management view, show all
                ->when($only_pumper, function ($q) use ($pump_operator_id) {
                return $q->where('poms.pump_operator_id', $pump_operator_id);
            })
                ->select(
                'pde.id as entry_id',
                'pomsd.id as detail_id',
                'poms.id as sale_id',
                'poms.business_id',
                'poms.pump_operator_id',
                'poms.shift_id',
                'po.name as pump_operator_name',
                'pomsd.created_at',
                'pomsd.pump_id',
                'pomsd.received_meter as starting_meter',
                'pomsd.new_meter as closing_meter',
                'pomsd.sold_qty',
                'pomsd.amount',
                DB::raw('COALESCE(pde.testing_ltr, 0) as testing_ltr'),
                DB::raw('COALESCE(s.settlement_no, pde.settlement_no) as settlement_no'),
                'poa.id as assignment_id',
                'poa.status as assignment_status',
                'poa.is_confirmed',
                'ps.status as shift_status',
                'poa.shift_number',
                'pde.pump_id as pde_pump_id',
                'pde.pumper_assignment_id',
                'pumps.pump_no',
                'bl.name as location_name'
            )
                ->groupBy('pomsd.id')
                ->get();

            // Also query day entries created directly (when pump is closed without meter sales)
            // Get assignment IDs that already have entries from meter sales
            $assignment_ids_from_meter_sales = $meter_sale_entries->pluck('assignment_id')->filter()->unique()->toArray();

            $direct_day_entries = DB::table('pumper_day_entries as pde')
                ->leftJoin('pump_operator_assignments as poa', 'pde.pumper_assignment_id', '=', 'poa.id')
                ->leftJoin('pump_operators as po', 'pde.pump_operator_id', '=', 'po.id')
                ->leftJoin('pumps', 'pde.pump_id', '=', 'pumps.id')
                ->leftJoin('settlements as s', 's.id', '=', 'poa.settlement_id')
                ->leftJoin('petro_shifts as ps', 'ps.id', '=', 'poa.shift_id')
                ->leftJoin('business_locations as bl', 'bl.id', '=', 'po.location_id')
                ->where('pde.business_id', $business_id)
                // For pumper view, restrict to their own entries; for management view, show all
                ->when($only_pumper, function ($q) use ($pump_operator_id) {
                return $q->where('pde.pump_operator_id', $pump_operator_id);
            })
                ->when(!empty($shift_id), function ($q) use ($shift_id) {
                return $q->where('poa.shift_id', $shift_id);
            })
                ->when(!empty($assignment_ids_from_meter_sales), function ($q) use ($assignment_ids_from_meter_sales) {
                // Exclude entries that are already included from meter sales
                return $q->whereNotIn('poa.id', $assignment_ids_from_meter_sales);
            })
                ->select(
                'pde.id as entry_id',
                DB::raw('NULL as detail_id'),
                DB::raw('NULL as sale_id'),
                'pde.business_id',
                'pde.pump_operator_id',
                'poa.shift_id',
                'po.name as pump_operator_name',
                'pde.created_at',
                'pde.pump_id',
                'pde.starting_meter',
                'pde.closing_meter',
                'pde.sold_ltr as sold_qty',
                'pde.amount',
                DB::raw('COALESCE(pde.testing_ltr, 0) as testing_ltr'),
                DB::raw('COALESCE(s.settlement_no, pde.settlement_no) as settlement_no'),
                'poa.id as assignment_id',
                'poa.status as assignment_status',
                'poa.is_confirmed',
                'ps.status as shift_status',
                'poa.shift_number',
                'pde.pump_id as pde_pump_id',
                'pde.pumper_assignment_id',
                'pumps.pump_no',
                'bl.name as location_name'
            )
                ->get();

            // Include both meters entered when closing the pump AND meter sales from payment dashboard
            $data->put('pumper_day_entries', $meter_sale_entries->merge($direct_day_entries));

            $data->put('settlement_credit_sale_payments', DB::table('pump_operator_payments as pop')
                ->leftJoin('settlement_credit_sale_payments as scsp', function ($join) {
                $join->on('scsp.pump_operator_id', '=', 'pop.pump_operator_id')
                    ->on('scsp.business_id', '=', 'pop.business_id')
                    ->whereRaw('scsp.amount = pop.payment_amount')
                    ->whereRaw('scsp.collection_form_no COLLATE utf8mb4_unicode_ci = pop.collection_form_no COLLATE utf8mb4_unicode_ci');
            })

                ->leftJoin('contacts', 'contacts.id', '=', 'scsp.customer_id')
                ->leftJoin('pump_operators as po', 'scsp.pump_operator_id', '=', 'po.id')
                ->where('pop.business_id', $business_id)
                // For pumper view, restrict to their own entries; for management view, show all
                ->when($only_pumper, function ($q) use ($pump_operator_id) {
                return $q->where('pop.pump_operator_id', $pump_operator_id);
            })
                ->when(!empty($shift_id), function ($q) use ($shift_id) {
                return $q->where('pop.shift_id', $shift_id);
            })
                ->where('pop.payment_type', 'credit')
                ->select(
                'scsp.*',
                'pop.settlement_no as pop_settlement_no',
                'contacts.name as customer_name',
                'po.name as pump_operator_name',
                'pop.shift_id',
                'pop.shift_id as  shift_number'
            )
                ->get());

            $data->put('settlement_card_payments', DB::table('pump_operator_payments as pop')
                ->leftJoin('daily_cards as scp', function ($join) {
                $join->on('scp.pump_operator_id', '=', 'pop.pump_operator_id')
                    ->on('scp.business_id', '=', 'pop.business_id')
                    ->on(DB::raw('scp.amount'), '=', DB::raw('pop.payment_amount'))
                    ->whereRaw('scp.collection_no COLLATE utf8mb4_unicode_ci = pop.collection_form_no COLLATE utf8mb4_unicode_ci');
            })
                ->leftJoin('contacts', 'contacts.id', '=', 'scp.customer_id')
                ->leftJoin('pump_operators as po', 'pop.pump_operator_id', '=', 'po.id')
                ->where('pop.business_id', $business_id)
                // For pumper view, restrict to their own entries; for management view, show all
                ->when($only_pumper, function ($q) use ($pump_operator_id) {
                return $q->where('pop.pump_operator_id', $pump_operator_id);
            })
                ->when(!empty($shift_id), function ($q) use ($shift_id) {
                return $q->where('pop.shift_id', $shift_id);
            })
                ->where('pop.payment_type', 'card')
                ->select(
                'scp.id',
                'scp.slip_no',
                'scp.amount',
                'scp.settlement_no',
                'pop.payment_amount as pop_amount', // Added fallback amount
                'pop.business_id',
                'pop.pump_operator_id',
                'pop.settlement_no as pop_settlement_no',
                'scp.created_at',
                'scp.updated_at',
                'contacts.name as customer_name',
                'po.name as pump_operator_name',
                'pop.shift_id',
                'pop.shift_id as shift_number'
            )
                ->get());

            $data->put('pump_cash_payments',
                PumpOperatorPayment::leftJoin('pump_operators', 'pump_operator_payments.pump_operator_id', '=', 'pump_operators.id')
                ->leftJoin('pump_operator_assignments', function ($join) {
                $join->on('pump_operator_assignments.shift_id', '=', 'pump_operator_payments.shift_id')
                    ->on('pump_operator_assignments.pump_operator_id', '=', 'pump_operator_payments.pump_operator_id');
            })
                ->where('pump_operators.business_id', $business_id)
                ->where('pump_operator_payments.business_id', $business_id)
                ->when(!empty($shift_id), function ($q) use ($shift_id) {
                return $q->where('pump_operator_payments.shift_id', $shift_id);
            })
                // For pumper view, restrict to their own entries; for management view, show all
                ->when($only_pumper, function ($q) use ($pump_operator_id) {
                return $q->where('pump_operator_payments.pump_operator_id', $pump_operator_id);
            })
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
                'pump_operator_payments.payment_amount',
                'pump_operator_payments.note',
                'pump_operator_payments.created_at',
                'pump_operator_payments.updated_at',
                'pump_operators.name as pump_operator_name',
                'pump_operators.id as pump_operator_id',
                DB::raw('NULL as customer_name'),
                DB::raw('COALESCE(pump_operator_assignments.shift_id, pump_operator_payments.shift_id) as shift_number')
            )
                ->groupBy('pump_operator_payments.id')
                ->get()
            );

            $assignment_query = DB::table('pump_operator_assignments as poa')
                ->leftJoin('pumps as p', 'p.id', '=', 'poa.pump_id')
                ->leftJoin('pump_operators as po', 'po.id', '=', 'poa.pump_operator_id')
                ->leftJoin('business_locations as bl', 'bl.id', '=', 'po.location_id')
                ->leftJoin('petro_shifts as ps', 'ps.id', '=', 'poa.shift_id')
                ->where('poa.business_id', $business_id)
                ->where('poa.status', 'open');

            if (!empty($shift_id)) {
                $assignment_query->where('poa.shift_id', $shift_id);
            }
            if ($only_pumper) {
                $assignment_query->where('poa.pump_operator_id', $pump_operator_id);
            }

            if (!empty(request()->pump_operator_id)) {
                $assignment_query->where('poa.pump_operator_id', request()->pump_operator_id);
            }
            if (!empty(request()->pump_id)) {
                $assignment_query->where('poa.pump_id', request()->pump_id);
            }

            $data->put('pump_assignments', $assignment_query->select(
                'poa.id as assignment_id',
                'poa.business_id',
                'poa.pump_operator_id',
                'poa.pump_id',
                'poa.date_and_time',
                'poa.starting_meter',
                'poa.closing_meter',
                'poa.shift_id',
                'poa.shift_number',
                'poa.status as assignment_status',
                'poa.is_confirmed',
                'p.pump_no',
                'po.name as pump_operator_name',
                'bl.name as location_name',
                'ps.status as shift_status'
            )->get());

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
                'id' => $item->entry_id ?? $item->detail_id ?? null,
                'business_id' => $item->business_id,
                'pump_operator_id' => $item->pump_operator_id,
                'date' => $item->created_at,
                'time' => $item->created_at,
                'date_and_time' => $item->created_at,
                'pump_id' => $item->pump_id,
                'pump_no' => $item->pump_no ?? '',

                'starting_meter' => number_format((float)($item->starting_meter ?? 0), 3, '.', ''),
                'closing_meter' => number_format((float)($item->closing_meter ?? 0), 3, '.', ''),

                'sold_ltr' => $item->sold_qty,
                'testing_ltr' => number_format((float)$item->testing_ltr, 3, '.', ''),
                'amount' => $item->amount,
                'settlement_datetime' => '',
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
                'row_type' => 'day_entry',
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
                'location_name' => '-',
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
                'location_name' => '-',
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
                'location_name' => '-',
                'shift_id' => $item->shift_number,
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

            $finalCollection = $finalCollection->filter(function ($item) use ($only_pumper, $pump_operator_id, $shift_id) {

                if (!empty($shift_id) && $item->shift_id != $shift_id) {
                    return false;
                }
                if (!empty(request()->pump_operator_id) && $item->pump_operator_id != request()->pump_operator_id) {
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

            $shortage_excess_by_operator = PumpOperatorPayment::whereDate('date_and_time', date('Y-m-d'))
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

                    $tooltip = __('petrogeneral::lang.edit_not_allowed_shift_open');
                    if ($received_by_pumper) {
                        $tooltip = __('petrogeneral::lang.edit_not_allowed_after_receive');
                    }
                    elseif ($shift_closed || $assignment_closed) {
                        $tooltip = __('petrogeneral::lang.edit_not_allowed_shift_closed');
                    }

                    if ($allow_assignment_edit) {
                        $html .= ' <li><a class="btn-modal" data-container=".pump_operator_modal" data-href="' .
                            action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController@edit', $row->pumper_assignment_id) .
                            '"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                    }
                    else {
                        $html .= ' <li class="disabled"><a href="#" class="text-muted" style="pointer-events: none; cursor: not-allowed;" title="' . e($tooltip) . '"><i class="fa fa-ban"></i> ' . __("messages.edit") . '</a></li>';
                    }
                }
                elseif ($is_admin_user && $row->row_type === 'day_entry' && empty($row->settlement_no)) {
                    $html .= ' <li><a data-href="' . action('\Modules\PetroGeneral\Http\Controllers\PumperDayEntryController@edit', [$row->id]) . '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                }

                if ($row->row_type === 'day_entry' && empty($row->settlement_no)) {
                    $html .= ' <li><a data-href="' . action('\Modules\PetroGeneral\Http\Controllers\PumperDayEntryController@postAddSettlementNo', [$row->id]) . '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-plus"></i> ' . __("petrogeneral::lang.add_settlement_no") . '</a></li>';
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
                    return '<a data-href="' . action('\Modules\PetroGeneral\Http\Controllers\PumperDayEntryController@viewAddSettlementNo', [$row->id]) . '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal">' . $row->settlement_no . '</a>';
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

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')->where('petro_shifts.business_id', $business_id)->select('pump_operators.name', 'petro_shifts.*')->orderBy('id', 'DESC');

        if ($only_pumper) {
            $shifts->where('pump_operator_id', $pump_operator_id);
        }

        $shifts = $shifts->get();

        $user = Auth::user();

        $pump_operator_id = $user->pump_operator_id;
        // MA-002: numeric max - shift_number is varchar(50), so MAX() on
        // it compares as TEXT and '9' beats '10'.
        $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->selectRaw('MAX(CAST(shift_number AS UNSIGNED)) as n')->value('n');

        return view('petrogeneral::pump_operators.pumper_day_entries')->with(compact(
            'layout',
            'business_locations',
            'pumps',
            'pump_operators',
            'pump_operator',
            'payment_types',
            'only_pumper',
            'shifts',
            'shift_number'
        ));
    }

    public function getPumperDayEntrySummary()
    {
        $only_pumper = request()->only_pumper;
        $shift_id = request()->shift_id;
        $business_id = Auth::user()->business_id;
        $pump_operator_id = Auth::user()->pump_operator_id;
        if (!request()->exists('shift_id') && empty($shift_id)) {
            $latest_shift_query = PetroShift::where('business_id', $business_id)->orderBy('id', 'desc');
            if ($only_pumper) {
                $latest_shift_query->where('pump_operator_id', $pump_operator_id);
            }
            $shift_id = $latest_shift_query->value('id');
        }

        $day_entries_query = PumperDayEntry::leftjoin('pump_operators', 'pumper_day_entries.pump_operator_id', 'pump_operators.id')
            ->leftjoin('pump_operator_assignments', 'pumper_day_entries.pumper_assignment_id', 'pump_operator_assignments.id')
            ->leftjoin('pumps', 'pumper_day_entries.pump_id', 'pumps.id')
            ->when($shift_id, fn($query) => $query->where('shift_id', $shift_id))->where('pumper_day_entries.business_id', $business_id)
            ->where('pump_operator_assignments.status', 'close')
            ->select('pump_operators.name', 'pumper_day_entries.*', 'pumps.pump_name');
        if ($only_pumper) {
            $day_entries_query->where('pumper_day_entries.pump_operator_id', $pump_operator_id);
        }

        $day_entries = $day_entries_query->get();

        $today_pumps = implode(', ', $day_entries->pluck('pump_name')->unique()->toArray());

        $other_sale = PumpOperatorOtherSale::when($shift_id, fn($query) => $query->where('shift_id', $shift_id))
            ->where('business_id', $business_id)
            ->select(DB::raw('SUM(sub_total - discount_amount) as total'))
            ->value('total');

        $payments = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')
            ->where('pump_operator_payments.business_id', $business_id)
            ->when($shift_id, fn($query) => $query->where('shift_id', $shift_id))->select(
            DB::raw('SUM(IF(payment_type="cash", payment_amount, 0)) as cash'),
            DB::raw('SUM(IF(payment_type="card", payment_amount, 0)) as card'),
            DB::raw('SUM(IF(payment_type="cheque", payment_amount, 0)) as cheque'),
            DB::raw('SUM(IF(payment_type="credit", payment_amount, 0)) as credit'),
            DB::raw('SUM(IF(payment_type="other", payment_amount, 0)) as other'),
            DB::raw('SUM(IF(payment_type="shortage" OR payment_type="excess", payment_amount, 0)) as shortage_excess'),
            DB::raw('SUM(payment_amount) as total')
        );
        if ($only_pumper) {
            $payments->where('pump_operator_payments.pump_operator_id', $pump_operator_id);
        }
        $payments = $payments->first();

        return view('petrogeneral::pump_operators.partials.pumper_day_entry_summary')->with(compact(
            'day_entries',
            'today_pumps',
            'payments',
            'only_pumper',
            'other_sale'
        ));
    }

    public function getClosingShiftSummary()
    {

        $only_pumper = request()->only_pumper;
        $shift_id = request()->shift_id;
        $business_id = Auth::user()->business_id;
        $pump_operator_id = Auth::user()->pump_operator_id;

        $day_entries_query = PumperDayEntry::leftjoin('pump_operators', 'pumper_day_entries.pump_operator_id', 'pump_operators.id')
            ->leftjoin('pump_operator_assignments', 'pumper_day_entries.pumper_assignment_id', 'pump_operator_assignments.id')
            ->leftjoin('pumps', 'pumper_day_entries.pump_id', 'pumps.id')
            ->when($shift_id, fn($query) => $query->where('shift_id', $shift_id))
            ->where('pumper_day_entries.business_id', $business_id)
            ->where('pump_operator_assignments.status', 'close')
            ->select('pump_operators.name', 'pumper_day_entries.*', 'pumps.pump_name');
        if ($only_pumper) {
            $day_entries_query->where('pumper_day_entries.pump_operator_id', $pump_operator_id);
        }

        $day_entries = $day_entries_query->get();

        $today_pumps = implode(', ', $day_entries->pluck('pump_name')->unique()->toArray());

        if (empty($shift_id)) {
            return '';
        }

        $payments = PumpOperatorPayment::leftjoin('pump_operators', 'pump_operator_payments.pump_operator_id', 'pump_operators.id')
            ->where('shift_id', $shift_id)->select(
            DB::raw('SUM(IF(payment_type="cash", payment_amount, 0)) as cash'),
            DB::raw('SUM(IF(payment_type="card", payment_amount, 0)) as card'),
            DB::raw('SUM(IF(payment_type="cheque", payment_amount, 0)) as cheque'),
            DB::raw('SUM(IF(payment_type="credit", payment_amount, 0)) as credit'),
            DB::raw('SUM(IF(payment_type="other", payment_amount, 0)) as other'),
            DB::raw('SUM(IF(payment_type="shortage" OR payment_type="excess", payment_amount, 0)) as shortage_excess'),
            DB::raw('SUM(payment_amount) as total')
        );

        if ($only_pumper) {
            $payments->where('pump_operator_payments.pump_operator_id', $pump_operator_id);
        }
        $payments = $payments->first();
        if ($payments) {
            $payments->total_paid =
                ($payments->cash ?? 0) +
                ($payments->card ?? 0) +
                ($payments->cheque ?? 0) +
                ($payments->credit ?? 0) +
                ($payments->other ?? 0);
        }

        $this_shift = PetroShift::findOrFail($shift_id);

        $other_sale = PumpOperatorOtherSale::where('shift_id', $shift_id)
            ->select(DB::raw('SUM(sub_total - discount_amount) as total'))
            ->value('total');

        // $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->max('shift_number');
        $shift_number = $shift_id;
        $unconfirmed_pumps_count = PumpOperatorAssignment::join('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
            ->where('pump_operator_assignments.shift_number', $shift_number)
            ->where('pumps.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->where('pump_operator_assignments.is_confirmed', 0)
            ->count();
        $unclosed_pumps_count = PumpOperatorAssignment::join('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
            ->join('petro_shifts', 'petro_shifts.id', 'pump_operator_assignments.shift_id')
            ->join('pump_operators', 'pump_operator_assignments.pump_operator_id', 'pump_operators.id')
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->where('petro_shifts.status', '0')
            ->where('pump_operator_assignments.business_id', $business_id)
            ->where('pump_operator_assignments.status', '!=', "close")
            ->select('pumps.*', 'pump_operator_assignments.pump_operator_id', 'pump_operator_assignments.pump_id', 'pump_operators.name AS pumper_name', 'pump_operator_assignments.status', 'pump_operator_assignments.id as assignment_id', 'pump_operator_assignments.is_confirmed')
            ->count();

        return view('petrogeneral::pump_operators.partials.closing_shift_summary')->with(compact(
            'day_entries',
            'today_pumps',
            'payments',
            'only_pumper',
            'this_shift',
            'other_sale',
            'unconfirmed_pumps_count',
            'unclosed_pumps_count',
            'shift_number'
        ));
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('petrogeneral::create');
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
        return view('petrogeneral::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        $business_id = Auth::user()->business_id;
        $day_entry = PumperDayEntry::find($id);
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');
        $pumps = Pump::where('business_id', $business_id)->pluck('pump_no', 'id');
        $pump_details = Pump::leftjoin('products', 'pumps.product_id', 'products.id')
            ->leftjoin('variations', 'products.id', 'variations.product_id')
            ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
            ->where('pumps.id', $day_entry->pump_id)
            ->select('default_sell_price', 'pumps.*', 'variation_location_details.qty_available')->first();

        return view('petrogeneral::pump_operators.partials.edit_pumper_day_entry')->with(compact(
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
        try {
            $pump = Pump::find($request->pump_id);
            $data = [
                'date' => $this->transactionUtil->uf_date($request->date),
                'pump_operator_id' => $request->pump_operator_id,
                'pump_id' => $request->pump_id,
                'pump_no' => !empty($pump) ? $pump->pump_no : null,
                'starting_meter' => $request->starting_meter,
                'closing_meter' => $request->closing_meter,
                'testing_ltr' => $this->transactionUtil->num_uf($request->testing_ltr),
                'sold_ltr' => $this->transactionUtil->num_uf($request->sold_ltr),
                'amount' => $this->transactionUtil->num_uf($request->amount),
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

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        // When a pumper day entry is removed from the dashboard we need to
        // ensure that the record is eliminated no matter how the user
        // is filtering the list (by shift number or by shift id).
        //
        // In earlier versions the table only stored a reference to the
        // pump_operator_assignment (which carried a shift_number). After the
        // introduction of PetroShift the assignment also stores a shift_id.
        // The UI can be filtered by either identifier. If we simply delete the
        // row by its primary key it may continue to surface via a different
        // filter (for example, an orphaned entry where the assignment is gone
        // but the shift number remains). To be safe we delete any entries that
        // are linked to the same shift identifier(s).
        try {
            $entry = PumperDayEntry::findOrFail($id);

            // collect identifiers from related assignment (if available)
            $shift_id = null;
            $shift_number = null;
            if (!empty($entry->pumper_assignment_id)) {
                $assignment = PumpOperatorAssignment::find($entry->pumper_assignment_id);
                if ($assignment) {
                    $shift_id = $assignment->shift_id;
                    $shift_number = $assignment->shift_number;
                }
            }

            // delete the requested entry first
            PumperDayEntry::where('id', $id)->delete();

            // remove other entries belonging to the same shift id (if any)
            if (!empty($shift_id)) {
                $assignment_ids = PumpOperatorAssignment::where('shift_id', $shift_id)
                    ->pluck('id')
                    ->toArray();
                if (!empty($assignment_ids)) {
                    PumperDayEntry::whereIn('pumper_assignment_id', $assignment_ids)->delete();
                }
            }

            // also remove entries belonging to the same shift number (legacy)
            if (!empty($shift_number)) {
                $assignment_ids = PumpOperatorAssignment::where('shift_number', $shift_number)
                    ->pluck('id')
                    ->toArray();
                if (!empty($assignment_ids)) {
                    PumperDayEntry::whereIn('pumper_assignment_id', $assignment_ids)->delete();
                }
            }

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        }
        catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function viewAddSettlementNo($id)
    {
        $day_entry = PumperDayEntry::leftjoin('users', 'pumper_day_entries.settlement_added_by', 'users.id')->where('pumper_day_entries.id', $id)->select('pumper_day_entries.settlement_no', 'pumper_day_entries.settlement_datetime', 'users.username')->first();

        return view('petrogeneral::pump_operators.partials.view_settlement_no')->with(compact('day_entry'));
    }

    public function getAddSettlementNo($id)
    {
        return view('petrogeneral::pump_operators.partials.add_settlement_no')->with(compact('id'));
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
        $business_id = request()->session()->get('user.business_id');
        $business_details = $this->businessUtil->getDetails($business_id);
        $pump_operator_id = Auth::user()->pump_operator_id;
        $only_pumper = request()->only_pumper;
        $date = date('Y-m-d');

        if (request()->ajax()) {

            // ✅ Totals calculation
            $totals = DB::table('pumper_day_entries')
                ->join('pumps', 'pumper_day_entries.pump_id', '=', 'pumps.id')
                ->where('pumps.business_id', $business_id)
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

            // Query assignments first so newly assigned pumps show even before day entry is created
            $pumps = DB::table('pump_operator_assignments as t1')
                ->leftJoin('pumper_day_entries as t5', function ($join) use ($date) {
                $join->on('t5.pumper_assignment_id', '=', 't1.id')
                    ->whereDate('t5.date', $date);
            })
                ->leftJoin('pumps as t2', 't1.pump_id', '=', 't2.id')
                ->leftJoin('pump_operators as t3', 't1.pump_operator_id', '=', 't3.id')
                ->leftJoin('business_locations as t4', 't3.location_id', '=', 't4.id')
                ->leftJoin('petro_shifts as t6', 't6.id', '=', 't1.shift_id')
                ->where('t1.business_id', $business_id)
                ->where('t2.business_id', $business_id)
                ->whereDate('t1.date_and_time', $date)
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
                't2.pump_no',
                't3.name',
                't4.name as location_name',
                't5.id as day_entry_id',
                't5.settlement_no',
                DB::raw('COALESCE(t6.status, 0) as shift_status'),
                DB::raw('COALESCE(t5.starting_meter, t1.starting_meter, 0) as starting_meter'),
                DB::raw('COALESCE(t5.closing_meter, t1.closing_meter, 0) as closing_meter'),
                DB::raw('COALESCE(t5.testing_ltr, 0) as testing_ltr'),
                DB::raw('COALESCE(t5.sold_ltr, 0) as sold_ltr'),
                DB::raw('COALESCE(t5.amount, 0) as total_amount')
            )
                ->orderBy('t2.id')
                ->orderBy('t1.date_and_time');

            // ✅ Datatables - pass query builder, not executed collection
            $daily_collections = DataTables::of($pumps)
                ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                        data-toggle="dropdown" aria-expanded="false">'
                    . __("messages.actions") .
                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                $can_edit = auth()->user()->can('daily_pump_status.edit');
                $assignment_id = $row->assignment_id ?? null;
                $day_entry_id = $row->day_entry_id ?? null;
                $shift_closed = !empty($row->shift_status) && (int)$row->shift_status === 2;
                $assignment_closed = !empty($row->assignment_status) && $row->assignment_status === 'close';
                $received_by_pumper = !empty($row->is_confirmed);

                if ($can_edit && !empty($assignment_id)) {
                    $allow_assignment_edit = !$received_by_pumper && !$shift_closed && !$assignment_closed;
                    $tooltip = __('petrogeneral::lang.edit_not_allowed_shift_open');
                    if ($received_by_pumper) {
                        $tooltip = __('petrogeneral::lang.edit_not_allowed_after_receive');
                    }
                    elseif ($shift_closed || $assignment_closed) {
                        $tooltip = __('petrogeneral::lang.edit_not_allowed_shift_closed');
                    }

                    if ($allow_assignment_edit) {
                        $html .= '<li><a class="btn-modal" data-container=".pump_operator_modal" data-href="' .
                            action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController@edit', $assignment_id) .
                            '"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                    }
                    else {
                        $html .= '<li class="disabled"><a href="#" class="text-muted" style="pointer-events: none; cursor: not-allowed;" title="' . e($tooltip) . '"><i class="fa fa-ban"></i> ' . __("messages.edit") . '</a></li>';
                    }
                }

                if ($can_edit && !empty($day_entry_id) && empty($row->settlement_no) && empty(auth()->user()->pump_operator_id)) {
                    $html .= '<li><a data-href="' . action('\Modules\PetroGeneral\Http\Controllers\PumperDayEntryController@edit', [$day_entry_id]) .
                        '" class="btn btn-modal edit_day_entry_button" data-container=".view_modal"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                }

                if (auth()->user()->can('daily_pump_status.delete') && !empty($assignment_id)) {
                    $html .= '<li><a href="' .
                        action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController@destroy', $assignment_id) .
                        '" class="delete_daily_collection"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>';
                }

                // if this row has an associated day entry id we also provide
                // a delete button specifically for that entry.  The JS handler
                // for `delete_daily_collection` will POST a DELETE request to
                // the controller below which will purge any related records
                // for both shift number and shift id.
                if (auth()->user()->can('daily_pump_status.delete') && !empty($day_entry_id)) {
                    $html .= '<li><a href="' .
                        action('\\Modules\\PetroGeneral\\Http\\Controllers\\PumperDayEntryController@destroy', $day_entry_id) .
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
                return ($shift && $shift->status == 2) ? 'Yes' : 'No';
            });

            return $daily_collections->rawColumns(['action', 'sold_ltr', 'testing_ltr', 'sold_amount'])
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
//                     $html .= '<li><a class="btn-modal" data-container=".pump_operator_modal" data-href="' . action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController@edit', $row->id) . '"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>' . __("messages.edit") . '</a></li> ';
//                 }
//                 if (auth()->user()->can('daily_pump_status.delete')) {
//                     $html .= '<li><a href="' . action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController@destroy', $row->id) . '" class="delete_daily_collection"><i class="fa fa-trash"></i>' . __("messages.delete") . '</a></li>';
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
//                         $html .= '<li><a class="btn-modal" data-container=".pump_operator_modal" data-href="' . action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController@edit', $row->id) . '"><i class="fa fa-pencil-square-o" aria-hidden="true"></i>' . __("messages.edit") . '</a></li> ';
//                     }
//                     if (auth()->user()->can('daily_pump_status.delete')) {
//                         $html .= '<li><a href="' . action('\Modules\PetroGeneral\Http\Controllers\PumpOperatorAssignmentController@destroy', $row->id) . '" class="delete_daily_collection"><i class="fa fa-trash"></i>' . __("messages.delete") . '</a></li>';
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

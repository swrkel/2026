<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Business;
use App\User;
use Spatie\Activitylog\Models\Activity;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\Product;
use App\PumperLoginAttempt;
use Modules\PetroPD\Entities\Settlement;
use Modules\PetroPD\Entities\SettlementCreditSalePayment;
use App\Store;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\SidebarPermissionUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Essentials\Entities\WorkShift;
use Modules\PetroPD\Entities\FuelTank;
use Modules\PetroPD\Entities\MeterSale;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorMeterSale;
use Modules\PetroPD\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Entities\PumpOperatorOtherSale;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;
use Modules\Superadmin\Entities\Subscription;
use Yajra\DataTables\Facades\DataTables;
use App\Transaction;
use App\TransactionPayment;
use Illuminate\Support\Str;

class PetroPDController extends Controller
{

    /**
     * MA-002 PERF: request-scoped cache of pump id -> pump_no.
     *
     * The "pump_nos" column runs once per row and ends with
     *     Pump::whereIn('id', $_pump_ids)->pluck('pump_no')
     * so every row issues its own query even though a station has only a
     * handful of pumps and the same ids recur down the page.
     *
     * The nested relation meter_sales_pd.details is already eager loaded on
     * the base query (line ~1926), so this lookup was the remaining per-row
     * query. Pump numbers are reference data and cannot change mid-render.
     *
     * Misses are cached as well, so a deleted pump is not re-queried.
     *
     * @param  array<int, mixed>  $pumpIds
     * @return array<int, string>
     */
    private static array $ma002PumpNoCache = [];

    private static function ma002PumpNos(array $pumpIds): array
    {
        $pumpIds = array_values(array_unique(array_filter($pumpIds)));
        if ($pumpIds === []) {
            return [];
        }

        $unknown = array_values(array_diff($pumpIds, array_keys(self::$ma002PumpNoCache)));
        if ($unknown !== []) {
            foreach (Pump::whereIn('id', $unknown)->pluck('pump_no', 'id')->toArray() as $id => $no) {
                self::$ma002PumpNoCache[$id] = $no;
            }
            foreach ($unknown as $id) {
                if (! array_key_exists($id, self::$ma002PumpNoCache)) {
                    self::$ma002PumpNoCache[$id] = null;
                }
            }
        }

        $out = [];
        foreach ($pumpIds as $id) {
            $no = self::$ma002PumpNoCache[$id] ?? null;
            if ($no !== null && $no !== '') {
                $out[] = $no;
            }
        }

        return $out;
    }

    
    /**
     * Resolve business id safely for multi-tenant PetroPD requests.
     */
    private function resolvePetroPdBusinessId()
    {
        $business_id = request()->session()->get('business.id');

        if (empty($business_id)) {
            $business_id = request()->session()->get('user.business_id');
        }

        if (empty($business_id) && auth()->check()) {
            $business_id = auth()->user()->business_id ?? null;
        }

        if (empty($business_id)) {
            $business_id = request()->input('business_id');
        }

        if (! empty($business_id)) {
            request()->session()->put('business.id', $business_id);
        }

        return $business_id;
    }

protected $productUtil;

    protected $moduleUtil;

    protected $transactionUtil;

    protected $commonUtil;

    protected $notificationUtil;

    private $barcode_types;
    private $periodBalanceCache = [];

    public function __construct(

        Util $commonUtil,

        ProductUtil $productUtil,

        ModuleUtil $moduleUtil,

        TransactionUtil $transactionUtil,

        BusinessUtil $businessUtil,

        NotificationUtil $notificationUtil

    ) {

        $this->commonUtil = $commonUtil;

        $this->productUtil = $productUtil;

        $this->moduleUtil = $moduleUtil;

        $this->transactionUtil = $transactionUtil;

        $this->businessUtil = $businessUtil;

        $this->notificationUtil = $notificationUtil;
    }

    public function pdSettlement()
    {
        $business_id = request()

            ->session()

            ->get("user.business_id");

        if (

            ! $this->moduleUtil->hasThePermissionInSubscription(

                $business_id,

                "petro_pd_module"

            )

        ) {

            abort(403, "Unauthorized Access");
        }

        if (! auth()->user()->can('petro_pd.access')) {
            abort(403, "Unauthorized Access");
        }

        /* // Check if pumper dashboard is enabled and operator has open shifts
        $is_pumper_dashboard_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        if ($is_pumper_dashboard_enabled && ! empty(request()->pump_operator)) {
            $pump_operator_id = request()->pump_operator;

            // Check if operator has any open shifts (status = 0 or 1 in petro_shifts)
            $open_shift_count = PumpOperatorAssignment::join('petro_shifts', 'pump_operator_assignments.shift_id', '=', 'petro_shifts.id')
                ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
                ->whereIn('petro_shifts.status', [0, 1]) // 0 = open, 1 = open/pending
                ->count();

            if ($open_shift_count > 0) {
                $output = [
                    'success' => false,
                    'msg'     => 'The shift is not yet closed, please close the shift and continue',
                ];
                return redirect()->back()->with('status', $output);
            }
        } */

        // Closed in Pumper Dashboard, pending in Petro PD — oldest numeric shift number first (FIFO).
        $requestedSettlementId = ! empty(request()->settlement_id)
            ? (int) request()->settlement_id
            : (! empty(request()->view_settlement_id) ? (int) request()->view_settlement_id : null);
        $filterOperatorId = ! empty(request()->pump_operator) ? (int) request()->pump_operator : null;

        // IS1668: On a fresh PD Settlement screen the oldest closed-but-unsettled
        // Pumper Dashboard shift must drive the Shift No and Pump Operator autoload.
        // A legacy pending PDST row without linked assignments previously overrode that
        // closed shift and left both dropdowns blank. Only resume a pending settlement
        // automatically when it is genuinely linked to at least one assignment.
        $returningAfterFinalize = request()->boolean('after_finalize');

        if (empty($requestedSettlementId) && ! $returningAfterFinalize) {
            $pendingPdSettlement = Settlement::where('business_id', $business_id)
                ->where('status', 1)
                ->where(function ($query) use ($business_id) {
                    foreach ($this->getPdSettlementPrefixes($business_id) as $prefix) {
                        $query->orWhere('settlement_no', 'LIKE', $prefix . '%');
                    }
                })
                ->whereExists(function ($query) use ($business_id) {
                    $query->select(DB::raw(1))
                        ->from('pump_operator_assignments as poa_pending')
                        ->whereColumn('poa_pending.settlement_id', 'settlements.id')
                        ->where('poa_pending.business_id', $business_id);
                })
                ->orderByDesc('id')
                ->first();

            if (! empty($pendingPdSettlement)) {
                $requestedSettlementId = (int) $pendingPdSettlement->id;
            }
        }

        // Fetch all closed-but-unsettled shifts for the business to show in the dropdown list
        $shiftQuery = PumpOperatorAssignment::query()
            ->where('pump_operator_assignments.business_id', $business_id)
            ->leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
            ->whereIn('pump_operator_assignments.status', ['close', 'closed'])
            /*
             |------------------------------------------------------------------
             | Pending until the settlement is FINISHED - not merely started.
             |------------------------------------------------------------------
             |
             | This was ->whereNull("pump_operator_assignments.settlement_id"),
             | which treated a shift as settled the moment ANY settlement row
             | referenced it, including an empty draft.
             |
             | Shift 13 was the case that exposed it: five pumps assigned, all
             | linked to settlement 17, which is PDST13 - total_amount 0 and
             | finish_date NULL, an abandoned draft. The shift vanished from the
             | pending list, the page auto-loaded a different shift, and the
             | operator saw that other shift's single pump with no explanation.
             |
             | Closing the shift in the Pumper Dashboard is what makes it eligible
             | to settle. It stays pending here until the settlement is FINISHED,
             | so a half-made draft no longer strands the shift.
             |
             | Everything establishing that the shift is CLOSED is untouched -
             | this changes only what counts as already settled.
             */
            ->where(function ($pendingQuery) {
                $pendingQuery
                    ->whereNull("pump_operator_assignments.settlement_id")
                    ->orWhereExists(function ($draftQuery) {
                        $draftQuery
                            ->select(DB::raw(1))
                            ->from("settlements")
                            ->whereColumn(
                                "settlements.id",
                                "pump_operator_assignments.settlement_id"
                            )
                            ->whereNull("settlements.finish_date");
                    });
            })
            ->where(function ($q) {
                $q->where('pump_operator_assignments.closed_in_settlement', 0)
                    ->orWhereNull('pump_operator_assignments.closed_in_settlement');
            })
            ->whereNotNull('pump_operator_assignments.close_date_and_time')
            ->where(function ($q) {
                $q->where('pump_operator_assignments.is_manually_closed', 1)
                    ->orWhere('ps.status', 2)
                    ->orWhereNotNull('ps.closed_time');
            });

        if ($filterOperatorId) {
            $shiftQuery->where('pump_operator_assignments.pump_operator_id', $filterOperatorId);
        }

        $pendingShifts = $shiftQuery->select(
                'pump_operator_assignments.shift_id',
                DB::raw('MIN(pump_operator_assignments.shift_number) as shift_number'),
                DB::raw(
                    'COALESCE(' .
                    'MAX(NULLIF(ps.pump_operator_id, 0)), ' .
                    'MAX(NULLIF(pump_operator_assignments.pump_operator_id, 0))' .
                    ') as pump_operator_id'
                )
            )
            ->groupBy('pump_operator_assignments.shift_id')
            ->orderByRaw('CAST(MIN(pump_operator_assignments.shift_number) AS UNSIGNED) ASC')
            ->orderByRaw('MIN(pump_operator_assignments.shift_number) ASC')
            ->orderBy('pump_operator_assignments.shift_id', 'asc')
            ->get();


        $shift_numbers = $pendingShifts->pluck('shift_number', 'shift_id')->toArray();
        $shift_operator_map = $pendingShifts
            ->pluck('pump_operator_id', 'shift_id')
            ->map(fn ($operatorId) => (int) $operatorId)
            ->toArray();

        // Autoload the oldest closed-but-unsettled shift
        $oldestPending = $pendingShifts->first();

        $settleable_shift_id = $oldestPending->shift_id ?? null;

        $shift_assignments = collect();
        if (! empty($settleable_shift_id)) {
            $shift_assignments = PumpOperatorAssignment::with('pumpOperator')
                ->where('business_id', $business_id)
                ->whereNull('settlement_id')
                ->where(function ($q) {
                    $q->where('closed_in_settlement', 0)
                        ->orWhereNull('closed_in_settlement');
                })
                ->where('shift_id', $settleable_shift_id)
                ->get();
        }
        $firstAssignment = $shift_assignments->first();

        $shift_id   = $settleable_shift_id;
        $shift_number = $oldestPending->shift_number ?? optional($firstAssignment)->shift_number;

        $pump_operator_id   = $oldestPending->pump_operator_id ?? optional($firstAssignment?->pumpOperator)->id;
        $pump_operator_name = PumpOperator::find($pump_operator_id, ['name'])?->name ?? optional($firstAssignment?->pumpOperator)->name;






        $next_shift_number = $shift_number
            ? ((int) $shift_number)
            : null;



        /* $reviewed = $this->transactionUtil->get_review(

            date("Y-m-d"),

            date("Y-m-d")

        );

        if (! empty($reviewed)) {

            $output = [

                "success" => 0,

                "msg"     =>

                "You can't add a settlement for an already reviewed date",

            ];

            return redirect()

                ->back()

                ->with(["status" => $output]);
        } */

        $business_id = $this->resolvePetroPdBusinessId();

        if (empty($business_id)) {
            \Log::error('PetroPDController@pdSettlement - business_id could not be resolved', [
                'user_id' => auth()->id(),
                'request' => request()->all(),
            ]);

            return redirect()->back()->with('error', __('messages.something_went_wrong'));
        }

        $business = Business::where("id", $business_id)->first();

        if (!$business) {
            \Log::error('PetroPDController@pdSettlement - Business not found', [
                'business_id' => $business_id,
                'user_id' => auth()->id(),
            ]);

            $pos_settings = [];
        } else {
            $pos_settings = json_decode($business->pos_settings ?? '{}', true);

            if (! is_array($pos_settings)) {
                $pos_settings = [];
            }
        }

        $check_qty = ! empty($pos_settings["allow_overselling"]) ? false : true;

        $cash_denoms = ! empty($pos_settings["cash_denominations"])

            ? explode(",", $pos_settings["cash_denominations"])

            : [];

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = !is_array($business_locations) ? current(array_keys($business_locations->toArray())) : current(array_keys($business_locations));

        $payment_types = $this->productUtil->payment_types(

            $default_location,

            false,

            false,

            false,

            false,

            "is_sale_enabled"

        );

        $customers = Contact::customersDropdown($business_id, false);

        $pump_operators = PumpOperator::where(

            "business_id",

            $business_id

        )->pluck("name", "id");

        $items = [];

        $ref_no_prefixes = request()

            ->session()

            ->get("business.ref_no_prefixes");

        $ref_no_starting_number = request()

            ->session()

            ->get("business.ref_no_starting_number");

        $prefix = $this->getPrimaryPdSettlementPrefix($business_id);

        $starting_no = ! empty($ref_no_starting_number["settlement_pd"])

            ? (int) $ref_no_starting_number["settlement_pd"]

            : 1;

        // S 268 FIX (2026-05-31): Always generate the next available PetroPD
        // settlement number from all existing PetroPD settlement numbers.
        // Previously this page could still show PDST1 after another settlement
        // was completed because the display number used only the latest row
        // instead of calculating the maximum PDST sequence safely.
        $settlement_no = $this->getNextPdSettlementNo($business_id);

        // S269 PERMANENT GUARD: PD screen must never display ST.
        if (! Str::startsWith((string) $settlement_no, $this->getPrimaryPdSettlementPrefix($business_id))) {
            $settlement_no = $this->getNextPdSettlementNo($business_id);
        }

        $currency_precision = ! empty($business->currency_precision)

            ? $business->currency_precision

            : 2;

        $meeter_precision = 3;

        // S 268 FOLLOW-UP FIX (2026-05-31):
        // Do not automatically load the oldest/latest pending PDST settlement when the user opens
        // a fresh PD Settlement screen. Auto-loading an existing pending settlement made the page
        // keep displaying PDST1 even when PDST1 and PDST2 already existed. Only load an existing
        // pending settlement when the user clicks Finish Settlement from the list and the request
        // carries settlement_id/view_settlement_id.
        $active_settlement = null;

        if (! empty($requestedSettlementId)) {
            $active_settlement = Settlement::where("status", 1)

                ->where(function ($query) use ($business_id) {
                    foreach ($this->getPdSettlementPrefixes($business_id) as $prefix) {
                        $query->orWhere("settlement_no", "LIKE", $prefix . "%");
                    }
                })

                ->where("business_id", $business_id)

                ->where('id', $requestedSettlementId)

                ->select("settlements.*")

                ->orderByDesc("settlements.id")

                ->with([

                    "meter_sales",

                    "other_sales",

                    "other_incomes",

                    "customer_payments",

                ])

                ->first();
        }

        $is_finishing_existing_settlement = ! empty($requestedSettlementId) && ! empty($active_settlement);

        if (! empty($active_settlement)) {
            $settlementAssignments = PumpOperatorAssignment::with('pumpOperator')
                ->where('business_id', $business_id)
                ->where('settlement_id', $active_settlement->id)
                ->orderBy(DB::raw('CAST(shift_number AS UNSIGNED)'))
                ->get();

            if ($settlementAssignments->isNotEmpty()) {
                $shift_assignments = $settlementAssignments;
                $firstAssignment = $shift_assignments->first();
                $shift_id = $firstAssignment->shift_id;
                $shift_number = $firstAssignment->shift_number;
                $shift_numbers = $shift_assignments
                    ->mapWithKeys(function ($assignment) {
                        return [$assignment->shift_id => $assignment->shift_number];
                    })
                    ->toArray();
                $pump_operator_id = $active_settlement->pump_operator_id ?: optional($firstAssignment?->pumpOperator)->id;
                $shift_operator_map = collect($shift_numbers)
                    ->mapWithKeys(fn ($number, $assignmentShiftId) => [
                        (string) $assignmentShiftId => (int) $pump_operator_id,
                    ])
                    ->toArray();
                $pump_operator_name = optional($firstAssignment?->pumpOperator)->name;
                $next_shift_number = $shift_number ? ((int) $shift_number) : null;
            } elseif (! empty($active_settlement->pump_operator_id)) {
                $activePending = PetroPdClosedShiftQuery::oldestPendingForOperator(
                    (int) $business_id,
                    (int) $active_settlement->pump_operator_id
                );

                if ($activePending) {
                    $shift_numbers = [
                        $activePending->shift_id => $activePending->shift_number,
                    ];
                    $shift_operator_map = [
                        (string) $activePending->shift_id => (int) $active_settlement->pump_operator_id,
                    ];
                    $shift_id = $activePending->shift_id;
                    $shift_assignments = PumpOperatorAssignment::with('pumpOperator')
                        ->where('business_id', $business_id)
                        ->whereNull('settlement_id')
                        ->where(function ($q) {
                            $q->where('closed_in_settlement', 0)
                                ->orWhereNull('closed_in_settlement');
                        })
                        ->where('shift_id', $shift_id)
                        ->where('pump_operator_id', $active_settlement->pump_operator_id)
                        ->get();
                    $firstAssignment = $shift_assignments->first();
                    $shift_number = optional($firstAssignment)->shift_number;
                    $pump_operator_id = $active_settlement->pump_operator_id;
                    $pump_operator_name = optional($firstAssignment?->pumpOperator)->name;
                    $next_shift_number = $shift_number ? ((int) $shift_number) : null;
                } else {
                    $shift_numbers = [];
                    $shift_operator_map = [];
                    $shift_id = null;
                    $shift_assignments = collect();
                    $firstAssignment = null;
                    $shift_number = null;
                    $pump_operator_id = $active_settlement->pump_operator_id;
                    $pump_operator_name = null;
                    $next_shift_number = null;
                }
            }
        } else {
            // Keep the autoloaded oldest eligible shift and operator computed earlier:
            $shift_id = $settleable_shift_id;
            $shift_number = $shift_number;
            $pump_operator_id = $pump_operator_id;
            $pump_operator_name = $pump_operator_name;
            $next_shift_number = $shift_number ? ((int) $shift_number) : null;
        }

        $other_sale_final_total = 0.0;

        $pump_other_sale_final_total = 0.0;

        $combinedOtherSales = [];

        if ($active_settlement) {

            $shift_number = PumpOperatorAssignment::where(

                "pump_operator_assignments.pump_operator_id",

                $active_settlement->pump_operator_id

            )

                ->leftJoin(

                    "settlements",

                    "pump_operator_assignments.settlement_id",

                    "=",

                    "settlements.id"

                )

                ->where(function ($query) {

                    $query

                        ->where("settlements.status", 1)

                        ->orWhereNull(

                            "pump_operator_assignments.settlement_id"

                        );
                })

                ->select("pump_operator_assignments.shift_number")

                ->groupBy("pump_operator_assignments.shift_number")

                ->get()

                ->toarray();

            $userOtherDetails = [];

            foreach ($active_settlement->other_sales as $ot_item) {

                $product = \App\Product::find($ot_item->product_id);

                $discount_amount = $ot_item->discount_amount ?? 0;

                $withDiscount = ($ot_item->sub_total ?? 0) - $discount_amount;

                $pump_other_sale_final_total += $withDiscount;

                $userOtherDetails[] = [

                    "id"            => $ot_item->id,

                    "sku"           => ! empty($product) ? $product->sku : "",

                    "name"          => ! empty($product) ? $product->name : "",

                    "balance_stock" => number_format(

                        $ot_item->balance_stock,

                        4,

                        ".",

                        ","

                    ),

                    "price"         => number_format(

                        $ot_item->price,

                        $currency_precision

                    ),

                    // Derive qty from sub_total/price to preserve fractional values even if stored qty was rounded
                    "qty"           => number_format(
                        (! empty($ot_item->price) && $ot_item->price != 0)
                            ? (($ot_item->sub_total ?? 0) / $ot_item->price)
                            : $ot_item->qty,
                        4,
                        ".",
                        ","
                    ),

                    "discount_type" => $ot_item->discount_type,

                    "discount"      => number_format(

                        $ot_item->discount,

                        $currency_precision

                    ),

                    "sub_total"     => number_format(

                        $ot_item->sub_total,

                        $currency_precision

                    ),

                    "with_discount" => number_format(

                        $withDiscount,

                        $currency_precision

                    ),

                    "user_check"    => 1,

                ];
            }

            // $shiftIds = array_column($shift_number, "shift_id");
            $shiftIds = [];

            if ($shift_id) {
                $shiftIds = [$shift_id];
            }

            $query = PumpOperatorOtherSale::join(

                "products",

                "products.id",

                "=",

                "pump_operator_other_sales.product_id"

            )

                ->leftJoin("variations", "products.id", "variations.product_id")

                ->leftJoin(

                    "variation_location_details",

                    "variations.id",

                    "variation_location_details.variation_id"

                )

                ->whereIn("pump_operator_other_sales.shift_id", $shiftIds)

                ->join("pump_operator_assignments", function ($join) {

                    $join

                        ->on(

                            "pump_operator_assignments.shift_id",

                            "=",

                            "pump_operator_other_sales.shift_id"

                        )

                        ->whereIn(

                            "pump_operator_assignments.status",

                            ["close", "closed"]

                        )->whereRaw('pump_operator_assignments.id = (

                             SELECT MAX(poa.id)

                             FROM pump_operator_assignments poa

                             WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status IN ("close", "closed")

                         )');
                })

                ->select(

                    "pump_operator_other_sales.*",

                    "products.name as product_name",

                    "products.sku as product_sku",

                    "pump_operator_assignments.shift_number",

                    "qty_available"

                )

                ->groupBy("pump_operator_other_sales.id");

            $pumperOthersaleDetails = [];

            $pumpSales = $query->get();

            foreach ($pumpSales as $pumpSale) {

                $discount_amount = $pumpSale->discount ?? 0;

                $withDiscount = ($pumpSale->sub_total ?? 0) - $discount_amount;

                $pump_other_sale_final_total += $withDiscount;

                $pumperOthersaleDetails[] = [

                    "sku"           => $pumpSale->product_sku,

                    "name"          => $pumpSale->product_name,

                    "balance_stock" => number_format(

                        $pumpSale->qty_available,

                        4,

                        ".",

                        ","

                    ),

                    "price"         => number_format(

                        $pumpSale->price,

                        $currency_precision

                    ),

                    // Preserve fractional quantities: derive from sub_total/price when present
                    "qty"           => number_format(
                        (! empty($pumpSale->price) && $pumpSale->price != 0)
                            ? ($pumpSale->sub_total / $pumpSale->price)
                            : $pumpSale->qty,
                        4,
                        ".",
                        ","
                    ),

                    "discount_type" => $pumpSale->discount_type,

                    "discount"      => number_format(

                        $pumpSale->discount,

                        $currency_precision

                    ),

                    "sub_total"     => number_format(

                        $pumpSale->sub_total,

                        $currency_precision

                    ),

                    "with_discount" => number_format(

                        $withDiscount,

                        $currency_precision

                    ),

                    "user_check"    => 0, // 0 for pump operator entry

                ];
            }

            $combinedOtherSales = array_merge(

                $userOtherDetails,

                $pumperOthersaleDetails

            );

            $final_other_sale_total =

                $other_sale_final_total + $pump_other_sale_final_total;
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = !is_array($business_locations) ? current(array_keys($business_locations->toArray())) : current(array_keys($business_locations));

        if (! empty($active_settlement)) {

            // $already_pumps = PumpOperatorMeterSale::where(

            //     "settlement_no",

            //     $active_settlement->id

            // )

            //     ->pluck("pump_id")

            //     ->toArray();
                        /*
             |------------------------------------------------------------------
             | The pumps assigned to THIS SHIFT, less the ones already entered.
             |------------------------------------------------------------------
             |
             | This previously listed every pump in the BUSINESS minus those
             | already used, and never referred to the shift at all. On shift 13
             | it produced an empty dropdown - five pumps assigned, none offered -
             | and on any other shift it would have offered pumps belonging to
             | other operators.
             |
             | A settlement covers one shift, so the pumps available to it are
             | that shift's pumps. That is now where the list starts, matching how
             | the no-settlement-yet path already works.
             |
             | $already_pumps is read straight from the detail table as well. It
             | used to do ->pluck('details.pump_id') on a hasMany relation, but
             | 'details' is a COLLECTION of rows rather than a single row, so a
             | dotted key does not reach the ids through it.
             */
            $already_pumps = PumpOperatorMeterSaleDetail::whereIn(
                'sale_id',
                PumpOperatorMeterSale::where('settlement_no', $active_settlement->id)
                    ->pluck('id')
            )
                ->pluck('pump_id')
                ->filter()        // removes nulls
                ->unique()        // avoids duplicates
                ->values()
                ->toArray();

            $shift_assigned_pump_ids = PumpOperatorAssignment::where(
                'shift_id',
                $settleable_shift_id
            )->distinct()->pluck('pump_id');

            $pump_nos = Pump::where("business_id", $business_id)
                ->whereIn("id", $shift_assigned_pump_ids)
                ->whereNotIn("id", $already_pumps)
                ->pluck("pump_name", "id");
        } else {

            $pump_nos = collect();
        }

        $stores = Store::forDropdown($business_id, 0, 1, "sell");

        $fuel_category_id = Category::where("business_id", $business_id)

            ->where("name", "Fuel")

            ->first();

        $fuel_category_id = ! empty($fuel_category_id)

            ? $fuel_category_id->id

            : null;

        $items = $this->transactionUtil->getProductDropDownArray(

            $business_id,

            $fuel_category_id,

            "petro_settlements"

        );

        $services = Product::where("business_id", $business_id)

            ->forModule("petro_settlements")

            ->where("enable_stock", 0)

            ->pluck("name", "id");

        $subscription = Subscription::active_subscription($business_id);

        if (is_null($subscription)) {

            $show_shift_no = false;
        } else {

            if ($subscription->customer_credit_notification_type == []) {

                $show_shift_no = false;
            } else {

                $firstDecode = json_decode(

                    $subscription->customer_credit_notification_type,

                    true

                );

                if (is_string($firstDecode)) {

                    $decodedData = json_decode($firstDecode, true);

                    $show_shift_no = in_array("pumper_dashboard", $decodedData)

                        ? true

                        : false;
                } else {

                    $show_shift_no = false;
                }
            }
        }

        $payment_meter_sale_total = $this->getMeterSaleTotalByShift(
            (int) $business_id,
            $shift_id,
            $pump_operator_id,
            $active_settlement,
            $settlement_no
        );

        $payment_other_sale_total = ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum("sub_total")

            : 0.0;

        $payment_other_sale_discount = ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum("discount_amount")

            : 0.0;

        $payment_other_sale_total -= $payment_other_sale_discount;

        $payment_other_income_total = ! empty($active_settlement->other_incomes)

            ? $active_settlement->other_incomes->sum("sub_total")

            : 0.0;

        $payment_customer_payment_total = ! empty($active_settlement->customer_payments)

            ? $active_settlement->customer_payments->sum("sub_total")

            : 0.0;

        // Read the selected shift's Pumper Dashboard payments without requiring
        // the newer authoritative amount columns. The query service falls back
        // to payment_amount on tenant databases where net_amount is unavailable.
        $shift_payment_details = collect();
        if (! empty($shift_id) && ! empty($pump_operator_id)) {
            try {
                $shift_payment_details = app(
                    \Modules\PetroPD\Services\SettlementPaymentQueryService::class
                )->paymentsForOperator(
                    (int) $business_id,
                    (int) $pump_operator_id,
                    (int) $shift_id
                );
            } catch (\Throwable $paymentLoadException) {
                Log::warning('PetroPD settlement shift payments could not be loaded.', [
                    'business_id' => (int) $business_id,
                    'pump_operator_id' => (int) $pump_operator_id,
                    'shift_id' => (int) $shift_id,
                    'error' => $paymentLoadException->getMessage(),
                ]);
            }
        }

        $work_shifts = WorkShift::where("business_id", $business_id)->pluck(

            "shift_name",

            "id"

        );

        $bulk_tanks = FuelTank::where("business_id", $business_id)

            ->where("bulk_tank", 1)

            ->pluck("fuel_tank_number", "id");

        $select_pump_operator_in_settlement = $this->moduleUtil->hasThePermissionInSubscription(

            $business_id,

            "select_pump_operator_in_settlement"

        );

        $manual_entry_permission = auth()->user()->can('petro_pd.manual_entry');

        $message = $this->transactionUtil->getGeneralMessage(

            "general_message_pump_management_checkbox"

        );

        $closed_shift = PumpOperatorAssignment::where('business_id', $business_id)
            ->whereNotNull('close_date_and_time')
            ->latest('id')
            ->first();

        $meter_sales = collect();
        $display_meter_sales = collect();

        if ($closed_shift && ! empty($shift_id) && $pump_operator_id !== null) {
            //   dd($settlement_no);
            // $pump_ids = $shift_assignments->pluck('pump_id')->filter()->unique();
            if (empty($active_settlement)) {
                PumpOperatorMeterSale::where('business_id', $business_id)
                    ->where('shift_id', $shift_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->when(true, function ($query) {
                        $this->onlyClosedPumpMeterSales($query);
                    })
                    ->where(function ($q) {
                        $q->whereNull('settlement_no')
                            ->orWhere('settlement_no', '');
                    })
                    ->update([
                        'settlement_no' => $settlement_no
                    ]);
            }
            $allowed_meter_sale_settlement_refs = $this->getAllowedMeterSaleSettlementRefs(
                (int) $business_id,
                $active_settlement,
                $settlement_no
            );

            $meter_sales = PumpOperatorMeterSale::with([
                'details',
                'details.pump',
            ])
                ->where('business_id', $business_id)
                ->where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where(function ($query) use ($allowed_meter_sale_settlement_refs) {
                    $query->whereNull('settlement_no')
                        ->orWhere('settlement_no', '');

                    if (! empty($allowed_meter_sale_settlement_refs)) {
                        $query->orWhereIn('settlement_no', $allowed_meter_sale_settlement_refs);
                    }
                })
                ->get();

            $display_meter_sales = $this->buildPdMeterSaleDisplayRows(
                $meter_sales,
                (int) $business_id,
                (int) $shift_id,
                (int) $pump_operator_id
            );

            Log::info('meter sales in create method of Settlement PD:', $meter_sales->toArray());

            /* PD-AUTOLOAD-DIAG - temporary, remove when the cause is found. */
            try {
                Log::warning('PD-AUTOLOAD-DIAG', [
                    'shift_id'          => $shift_id,
                    'pump_operator_id'  => $pump_operator_id,
                    'closed_shift'      => ! empty($closed_shift),
                    'active_settlement' => $active_settlement->id ?? null,
                    'allowed_refs'      => $allowed_meter_sale_settlement_refs ?? null,
                    'meter_sales_count' => $meter_sales->count(),
                    'meter_sale_ids'    => $meter_sales->pluck('id')->toArray(),
                    'meter_sale_refs'   => $meter_sales->pluck('settlement_no')->toArray(),
                    'display_rows'      => $display_meter_sales->count(),
                    'pump_nos'          => $pump_nos ?? null,
                ]);

                /*
                 | The same query WITHOUT the settlement_no condition, so we can
                 | see whether that condition is what removes the four rows.
                 | Read only - it is logged and discarded.
                 */
                $diag_all = PumpOperatorMeterSale::where('business_id', $business_id)
                    ->where('shift_id', $shift_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->get(['id', 'settlement_no']);

                Log::warning('PD-AUTOLOAD-DIAG without the settlement_no filter', [
                    'count' => $diag_all->count(),
                    'rows'  => $diag_all->toArray(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('PD-AUTOLOAD-DIAG failed', ['error' => $e->getMessage()]);
            }


            $pump_nos = Pump::whereIn(
                'id',
                PumpOperatorAssignment::where('shift_id', $shift_id)
                    ->pluck('pump_id')
            )->pluck('pump_name', 'id');
        }

        // If there is no active settlement, load other sales for the autoloaded closed shift
        if (empty($combinedOtherSales) && ! empty($shift_id)) {
            $otherQuery = PumpOperatorOtherSale::join(
                "products",
                "products.id",
                "=",
                "pump_operator_other_sales.product_id"
            )
                ->leftJoin("variations", "products.id", "variations.product_id")
                ->leftJoin(
                    "variation_location_details",
                    "variations.id",
                    "variation_location_details.variation_id"
                )
                ->where('pump_operator_other_sales.shift_id', $shift_id)
                ->join("pump_operator_assignments", function ($join) {
                    $join
                        ->on(
                            "pump_operator_assignments.shift_id",
                            "=",
                            "pump_operator_other_sales.shift_id"
                        )
                        ->whereIn(
                            "pump_operator_assignments.status",
                            ["close", "closed"]
                        )->whereRaw('pump_operator_assignments.id = (
                             SELECT MAX(poa.id)
                             FROM pump_operator_assignments poa
                             WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status IN ("close", "closed")
                         )');
                })
                ->select(
                    "pump_operator_other_sales.*",
                    "products.name as product_name",
                    "products.sku as product_sku",
                    "pump_operator_assignments.shift_number",
                    "qty_available"
                )
                ->groupBy("pump_operator_other_sales.id");

            $pumperOthersaleDetails = [];
            $pumpOtherRows = $otherQuery->get();

            foreach ($pumpOtherRows as $pumpSale) {
                $discount_amount = $pumpSale->discount ?? 0;
                $withDiscount = ($pumpSale->sub_total ?? 0) - $discount_amount;
                $pump_other_sale_final_total += $withDiscount;

                $pumperOthersaleDetails[] = [
                    "sku"           => $pumpSale->product_sku,
                    "name"          => $pumpSale->product_name,
                    "balance_stock" => number_format(
                        $pumpSale->qty_available,
                        4,
                        ".",
                        ","
                    ),
                    "price"         => number_format(
                        $pumpSale->price,
                        $currency_precision
                    ),
                    "qty"           => number_format(
                        (! empty($pumpSale->price) && $pumpSale->price != 0)
                            ? ($pumpSale->sub_total / $pumpSale->price)
                            : $pumpSale->qty,
                        4,
                        ".",
                        ","
                    ),
                    "discount_type" => $pumpSale->discount_type,
                    "discount"      => number_format(
                        $pumpSale->discount,
                        $currency_precision
                    ),
                    "sub_total"     => number_format(
                        $pumpSale->sub_total,
                        $currency_precision
                    ),
                    "with_discount" => number_format(
                        $withDiscount,
                        $currency_precision
                    ),
                    "user_check"    => 0,
                ];
            }

            $combinedOtherSales = array_merge($combinedOtherSales, $pumperOthersaleDetails);
        }

        Log::info('combined other sales: ', $combinedOtherSales);

        $discount_types = ["fixed" => "Fixed", "percentage" => "Percentage"];

        // Fetch settlement credit sale payments for the Credit Sales tab
        $settlement_credit_sale_payments = collect();
        if (! empty($active_settlement)) {
            $settlement_credit_sale_payments = SettlementCreditSalePayment::leftJoin(
                'contacts',
                'settlement_credit_sale_payments.customer_id',
                '=',
                'contacts.id'
            )
                ->leftJoin('products', 'settlement_credit_sale_payments.product_id', '=', 'products.id')
                ->where(function ($q) use ($active_settlement) {
                    $q->where('settlement_credit_sale_payments.settlement_no', $active_settlement->settlement_no)
                        ->orWhere('settlement_credit_sale_payments.settlement_no', $active_settlement->id);
                })
                ->select(
                    'settlement_credit_sale_payments.*',
                    'contacts.name as customer_name',
                    'products.name as product_name'
                )
                ->get();
        }

        $can_edit_details = [1, ""];
        if (! empty($active_settlement) && ! empty($active_settlement->id)) {
            $can_edit_details = $this->canEditSettlement($active_settlement->id);
        }

        return view('petropd::pd_settlement.create')->with(

            compact(

                "select_pump_operator_in_settlement",

                "message",

                "shift_numbers",
                "shift_operator_map",

                "business_locations",

                "payment_types",

                "customers",

                "pump_operators",

                "work_shifts",

                "pump_nos",

                "items",

                "settlement_no",

                "default_location",

                "active_settlement",

                "stores",

                "payment_meter_sale_total",

                "payment_other_sale_total",

                "payment_other_income_total",

                "payment_customer_payment_total",

                "bulk_tanks",

                "services",

                "discount_types",

                "cash_denoms",

                "check_qty",

                "payment_other_sale_discount",

                "show_shift_no",

                "combinedOtherSales",

                "pump_other_sale_final_total",

                "settlement_credit_sale_payments",

                "can_edit_details",

                "next_shift_number",

                "pump_operator_name",
                "meter_sales",
                "display_meter_sales",
                "shift_payment_details",
                "pump_operator_id",
                "is_finishing_existing_settlement",
                "shift_id",
                "manual_entry_permission"

            )

        );
    }

    /**
     * Return the settlement references that are safe to use while reading
     * closed-pump meter sales for the current PD Settlement screen.
     *
     * A fresh screen assigns the generated PDST number to its closed-pump
     * rows before the settlement master record is saved. That generated
     * number is therefore a valid provisional reference and must remain
     * readable on the same screen and after a browser refresh.
     */
    private function getAllowedMeterSaleSettlementRefs(
        int $business_id,
        ?Settlement $active_settlement = null,
        ?string $requested_settlement_no = null
    ): array {
        $references = [];

        if (! empty($active_settlement)) {
            if (! empty($active_settlement->settlement_no)) {
                $references[] = (string) $active_settlement->settlement_no;
            }

            if (! empty($active_settlement->id)) {
                $references[] = (string) $active_settlement->id;
            }
        }

        $requested_settlement_no = trim((string) $requested_settlement_no);

        if ($requested_settlement_no !== '' && empty($active_settlement)) {
            $has_valid_pd_prefix = collect($this->getPdSettlementPrefixes($business_id))
                ->contains(function ($prefix) use ($requested_settlement_no) {
                    return $prefix !== '' && Str::startsWith($requested_settlement_no, (string) $prefix);
                });

            $already_saved = Settlement::where('business_id', $business_id)
                ->where('settlement_no', $requested_settlement_no)
                ->exists();

            if ($has_valid_pd_prefix && ! $already_saved) {
                $references[] = $requested_settlement_no;
            }
        }

        return array_values(array_unique(array_filter($references, function ($reference) {
            return $reference !== null && $reference !== '';
        })));
    }

    /*
     * S 639: ONE SOURCE. Reads pumper_day_entries through ShiftSaleTotals.
     *
     * $settlement and $requested_settlement_no are kept in the signature so no
     * caller has to change, but they are no longer used to pick rows: they
     * existed only to work out which settlement stamp the copied meter sale
     * rows were carrying. A day entry belongs to a shift, so the shift is
     * enough.
     */
    private function getMeterSaleTotalByShift(
        int $business_id,
        ?int $shift_id,
        ?int $pump_operator_id = null,
        ?Settlement $settlement = null,
        ?string $requested_settlement_no = null
    ): float {
        if (empty($shift_id)) {
            return 0.0;
        }

        $totals = \Modules\SettlementCore\Services\ShiftSaleTotals::for(
            (int) $business_id,
            (int) $shift_id,
            $pump_operator_id
        );

        $totals->logDivergence(
            'PetroPDController::getMeterSaleTotalByShift',
            $this->getMeterSaleTotalByShiftLegacy(
                $business_id,
                $shift_id,
                $pump_operator_id,
                $settlement,
                $requested_settlement_no
            )
        );

        return $totals->meterSales();
    }

    private function getMeterSaleTotalByShiftLegacy(
        int $business_id,
        ?int $shift_id,
        ?int $pump_operator_id = null,
        ?Settlement $settlement = null,
        ?string $requested_settlement_no = null
    ): float {
        if (empty($shift_id)) {
            return 0.0;
        }

        $allowed_settlement_refs = $this->getAllowedMeterSaleSettlementRefs(
            $business_id,
            $settlement,
            $requested_settlement_no
        );

        $meterSales = PumpOperatorMeterSale::with('details')
            ->where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            ->when(! empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                $query->where('pump_operator_id', $pump_operator_id);
            })
            ->where(function ($query) use ($allowed_settlement_refs) {
                $query->whereNull('settlement_no')
                    ->orWhere('settlement_no', '');

                if (! empty($allowed_settlement_refs)) {
                    $query->orWhereIn('settlement_no', $allowed_settlement_refs);
                }
            })
            ->get();

        $total = 0.0;
        $seenRows = [];

        foreach ($meterSales as $sale) {
            $details = collect($sale->details);

            if ($details->isEmpty()) {
                $saleKey = 'sale:' . (int) $sale->id;
                if (! isset($seenRows[$saleKey])) {
                    $seenRows[$saleKey] = true;
                    $total += (float) ($sale->balance ?? $sale->amount ?? 0);
                }
                continue;
            }

            foreach ($details as $detail) {
                $rowKey = implode('|', [
                    $detail->pump_id ?? '',
                    number_format((float) ($detail->received_meter ?? 0), 3, '.', ''),
                    number_format((float) ($detail->new_meter ?? 0), 3, '.', ''),
                    number_format((float) ($detail->unit_price ?? 0), 4, '.', ''),
                    number_format((float) ($detail->sold_qty ?? 0), 3, '.', ''),
                    number_format((float) ($detail->amount ?? 0), 4, '.', ''),
                ]);

                if (isset($seenRows[$rowKey])) {
                    continue;
                }

                $seenRows[$rowKey] = true;
                $total += (float) ($detail->amount ?? 0);
            }
        }

        return $total;
    }

    public function pdOperators()
    {

        $business_id = Auth::user()->business_id;
        // TEMPORARY RECOVERY BYPASS (SIDEBAR_063): keep role/page permission checks active.
        $petroPdTemporaryRecoveryEnabled = true;

        if (! auth()->user()->can('petro_pd.view_operators')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {

            $business_id = Auth::user()->business_id;
            if (request()->ajax()) {
                $query = PumpOperator::withoutGlobalScope('active')
                ->leftjoin('business_locations', 'pump_operators.location_id', 'business_locations.id')
                    ->leftjoin('settlements', 'pump_operators.id', 'settlements.pump_operator_id')
                    ->where('pump_operators.business_id', $business_id)
                    ->select([
                        'pump_operators.*',
                        'settlements.settlement_no as st_no',
                        'pump_operators.id as pump_operator_id',
                        'business_locations.name as location_name',
                    ])->groupBy('pump_operators.id');

                if (! empty(request()->location_id)) {
                    $query->where('pump_operators.location_id', request()->location_id);
                }
                if (! empty(request()->pump_operator)) {
                    $query->where('pump_operators.id', request()->pump_operator);
                }
                if (! empty(request()->settlement_no)) {
                    $query->where('settlements.settlement_no', request()->settlement_no);
                }
                if (! empty(request()->status)) {
                    if (request()->status == 'active') {
                        $query->where('pump_operators.active', 1);
                    } else {
                        $query->where('pump_operators.active', 0);
                    }
                }
                if (! empty(request()->type)) {
                }

                $start_date       = request()->start_date;
                $end_date         = request()->end_date;
                
                // FIX: Provide default date range if not provided (today)
                if (empty($start_date)) {
                    $start_date = now()->format('Y-m-d');
                }
                if (empty($end_date)) {
                    $end_date = now()->format('Y-m-d');
                }
                
                $business_details = Business::find($business_id);
                $period_balances  = $this->getPeriodBalancesForRange($business_id, $start_date, $end_date, [
                    'location_id' => request()->location_id,
                ]);
                
                // DEBUG: Log period balances to verify data
                Log::info('Pump Operator List - Date Range: ' . $start_date . ' to ' . $end_date);
                Log::info('Pump Operator List - Period Balances Count: ' . count($period_balances));
                Log::info('Pump Operator List - Period Balances: ', $period_balances);

                $fuel_tanks = Datatables::of($query)
                    ->addColumn(
                        'action',
                        function ($row) {
                            $business_id           = session()->get('user.business_id');
                            $pay_excess_commission = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pay_excess_commission');
                            $recover_shortage      = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'recover_shortage');
                            $pump_operator_ledger  = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_ledger');

                            $html = '<div class="btn-group">
                            <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-left" role="menu">

                            <li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@show', [$row->id]) . '"><i class="fa fa-eye" aria-hidden="true"></i>' . __("messages.view") . '</a></li>
                            <li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@edit', [$row->id]) . '" class="edit_contact_button"><i class="fa fa-pencil-square-o"></i> ' . __("messages.edit") . '</a></li>';
                            if (auth()->user()->can('pum_operator.active_inactive')) {
                                $html .= '<li class="divider"></li>';
                                if (! $row->active) {
                                    $html .= '<li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@toggleActivate', [$row->id]) . '" class="toggle_active_button"><i class="fa fa-check"></i> ' . __("lang_v1.activate") . '</a></li>';
                                } else {
                                    $html .= '<li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@toggleActivate', [$row->id]) . '" class="toggle_active_button"><i class="fa fa-times"></i> ' . __("lang_v1.deactivate") . '</a></li>';
                                }
                            }

                            $html .= '<li class="divider"></li>';
                            if ($pay_excess_commission) {
                                $html .= '<li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDExcessComissionController@create', ['pump_operator_id' => $row->id]) . '" class="edit_contact_button"> ' . __("petropd::lang.pay_excess_and_commission") . '</a></li>';
                            }
                            if ($recover_shortage) {
                                $html .= '<li><a href="' . action('\Modules\PetroPD\Http\Controllers\PDRecoverShortageController@create', ['pump_operator_id' => $row->id]) . '" class="edit_contact_button"> ' . __("petropd::lang.recover_shortages") . '</a></li>';
                            }
                            $html .= '<li class="divider"></li>
                            <li>
                                <a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@show', [$row->id]) . "?view=contact_info" . '">
                                    <i class="fa fa-user" aria-hidden="true"></i>
                                    ' . __("contact.contact_info", ["contact" => __("contact.contact")]) . '
                                </a>
                            </li>
                            ';

                            if ($pump_operator_ledger) {
                                $html .= '<li>
                                    <a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@show', [$row->id]) . "?view=ledger" . '">
                                        <i class="fa fa-anchor" aria-hidden="true"></i>
                                        ' . __("lang_v1.ledger") . '
                                    </a>
                                </li>';
                            }

                            $html .= '<li>
                                    <a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@listCommission', [$row->id]) . '">
                                        <i class="fa fa-anchor" aria-hidden="true"></i>
                                        ' . __("petropd::lang.list_commission") . '
                                    </a>
                                </li>';

                            $html .= '<li>
                                <a href="' . action('\Modules\PetroPD\Http\Controllers\PDOperatorController@show', [$row->id]) . "?view=documents_and_notes" . '">
                                    <i class="fa fa-paperclip" aria-hidden="true"></i>
                                     ' . __("lang_v1.documents_and_notes") . '
                                </a>
                            </li>

                        </ul></div>';

                            return $html;
                        }
                    )
                   ->editColumn('name', function ($row) {
                        $html = $row->name;

                        // show default badge
                        if ($row->is_default == 1) {
                            $html .= " <span class='badge bg-danger'>Default</span>";
                        }

                        // show deactivated badge
                        if ($row->active == 0) {
                            $html .= " <span class='badge bg-secondary'>Deactivated</span>";
                        }

                        return $html;
                    })

                    ->addColumn(
                        'pump_no',
                        ''
                    )
                    ->addColumn(
                        'settlement_no',
                        ''
                    )
                    ->addColumn(
                        'sold_fuel_qty',
                        function ($row) use ($business_details, $start_date, $end_date, $business_id) {
                            /*
                             * S269 fix:
                             * PD Operators Sold Qty Fuel must come from the saved pumper dashboard meter entries,
                             * not from sales transactions. After settlement finalization, accounting/sales transaction
                             * rows can be duplicated or split, so using transactions here gives incorrect totals.
                             */
                            $sold_fuel_qty = PumperDayEntry::where('business_id', $business_id)
                                ->where('pump_operator_id', $row->pump_operator_id)
                                ->whereDate('date', '>=', $start_date)
                                ->whereDate('date', '<=', $end_date)
                                ->sum('sold_ltr');

                            $sold_fuel_qty = (float) $sold_fuel_qty;

                            return '<span class="sold_fuel_qty" data-orig-value="' . $sold_fuel_qty . '" data-currency_symbol="false">' .
                                $this->productUtil->num_f($sold_fuel_qty, false, $business_details, true) .
                                '</span>';
                        }
                    )
                    ->addColumn(
                        'sale_amount_fuel',
                        function ($row) use ($business_details, $start_date, $end_date, $business_id) {
                            /*
                             * S269 fix:
                             * PD Operators Sale Amount Fuel must come from pumper_day_entries.amount,
                             * because that is the saved meter sale value from Pumper Dashboard.
                             */
                            $sale_amount_fuel = PumperDayEntry::where('business_id', $business_id)
                                ->where('pump_operator_id', $row->pump_operator_id)
                                ->whereDate('date', '>=', $start_date)
                                ->whereDate('date', '<=', $end_date)
                                ->sum('amount');

                            $sale_amount_fuel = (float) $sale_amount_fuel;

                            return '<span class="display_currency sale_amount_fuel" data-orig-value="' . $sale_amount_fuel . '" data-currency_symbol="true">' .
                                $this->productUtil->num_f($sale_amount_fuel, false, $business_details, false) .
                                '</span>';
                        }
                    )
                    ->addColumn(
                        'current_balance',
                        function ($row) {
                            //$balance_due = $this->getLedgerDetailsForDateRange($row->pump_operator_id, $start_date,$end_date)['balance_due'];
                            $balance_due = $this->transactionUtil->getPumpOperatorBalance($row->pump_operator_id);
                            return '<span class="display_currency current_balance" data-orig-value="' . $balance_due . '" data-currency_symbol = true>' . $this->productUtil->num_f($balance_due, false) . '</span>';
                        }
                    )
                    ->addColumn('balance_for_period', function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [
                            'balance_for_period' => 0,
                        ];
                        $balance_for_period = $summary['balance_for_period'] ?? 0;
                        return '<span class="display_currency text-right balance_for_period" style="display:block" data-orig-value="' . $balance_for_period . '" data-currency_symbol = true>' . $this->productUtil->num_f($balance_for_period, false, $business_details, true) . '</span>';
                    })

                ->editColumn(
                    'excess_amount',
                    function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [];
                        $total_excess = $summary['total_credit_for_period'] ?? 0;
                        return  '<span class="display_currency excess_amount" data-orig-value="' .  $total_excess . '" data-currency_symbol = true>' . $this->productUtil->num_f($total_excess, false, $business_details, true) . '</span>';
                    }
                )
                ->editColumn(
                    'short_amount',
                    function ($row) use ($period_balances, $business_details) {
                        $summary = $period_balances[$row->pump_operator_id] ?? [];
                        $total_shortage = $summary['total_debit_for_period'] ?? 0;
                        return  '<span class="display_currency short_amount" data-orig-value="' . $total_shortage . '" data-currency_symbol = true>' . $this->productUtil->num_f($total_shortage, false, $business_details, true) . '</span>';
                    }
                )
                    ->editColumn(
                        'commission_type',
                        function ($row) {
                            return ucfirst($row->commission_type);
                        }
                    )
                    ->editColumn(
                        'commission_rate',
                        function ($row) use ($business_details) {
                            return '<span class="display_currency commission_ap" data-orig-value="' . $row->commission_ap . '" data-currency_symbol = false>' . $this->productUtil->num_f($row->commission_ap, false, $business_details, false) . '</span>';
                        }
                    )
                    ->addColumn(
                        'commission_amount',
                        function ($row) use ($business_details, $start_date, $end_date) {
                            $amount = $this->transactionUtil->getPumpOperatorCommission($row->pump_operator_id, $start_date, $end_date);
                            return '<span class="display_currency commission_amount" data-orig-value="' . $amount . '" data-currency_symbol = true>' . $this->productUtil->num_f($amount, false, $business_details, true) . '</span>';
                        }
                    )

                    ->removeColumn('id');

                return $fuel_tanks->rawColumns(['name', 'action', 'sold_fuel_qty', 'sale_amount_fuel', 'excess_amount', 'short_amount', 'commission_rate', 'commission_amount', 'current_balance', 'balance_for_period'])
                    ->make(true);
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $pump_operators     = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');

        // dd($pump_operators);

        $pumps = Pump::where('pumps.business_id', $business_id)
            ->select('pumps.*')
            ->orderBy('pumps.id')
            ->get();

        foreach ($pumps as $pump) {

            $po_assign = PumpOperatorAssignment::leftjoin('pump_operators', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')
                ->where('pump_operator_assignments.business_id', $business_id)
                ->where('pump_operator_assignments.pump_id', $pump->id)
                ->where('pump_operator_assignments.status', 'open')
                ->select(
                    'pump_operator_assignments.id as assignment_id',
                    'pump_operator_assignments.pump_operator_id',
                    'pump_operator_assignments.shift_number',
                    'pump_operator_assignments.shift_id',
                    'pump_operator_assignments.is_confirmed',
                    'pump_operator_assignments.status as assignment_status',
                    'pump_operators.name as pumper_name'
                )
                ->first();
            if (! empty($po_assign)) {
                $pump->pumper_name       = $po_assign->pumper_name;
                $pump->pump_operator_id  = $po_assign->pump_operator_id;
                $pump->shift_number      = $po_assign->shift_number;
                $pump->shift_id          = $po_assign->shift_id;
                $pump->is_confirmed      = $po_assign->is_confirmed;
                $pump->assignment_id     = $po_assign->assignment_id;
                $pump->assignment_status = $po_assign->assignment_status;
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $default_location   = !is_array($business_locations) ? current(array_keys($business_locations->toArray())) : current(array_keys($business_locations));
        $payment_types      = $this->productUtil->payment_types($default_location);
        $tanks              = FuelTank::where('business_id', $business_id)->pluck('fuel_tank_number', 'id');
        $products           = Product::leftjoin('categories', 'products.category_id', 'categories.id')->where('products.business_id', $business_id)->where('categories.name', 'Fuel')->pluck('products.name', 'products.id');
        $settlement_nos     = [];

        $shifts = PetroShift::join('pump_operators', 'pump_operators.id', 'petro_shifts.pump_operator_id')->where('petro_shifts.business_id', $business_id)->select('pump_operators.name', 'petro_shifts.*')->orderBy('id', 'DESC');

        $shifts = $shifts->get();

        $message = $this->transactionUtil->getGeneralMessage('general_message_pump_management_checkbox');

        $pumperLoginAttempts = PumperLoginAttempt::where('business_id', $business_id)
            ->where('status', "Blocked")
            ->get();
        $customers = Contact::customersDropdown($business_id, false, true);
    
        return view('petropd::pd_operators.index')
        ->with(compact(
            'business_locations',
            'pump_operators',
            'settlement_nos',
            'message',
            'payment_types',
            'pumps',
            'tanks',
            'products',
            'shifts',
            'customers',
            'pumperLoginAttempts',
            'default_location'
        ));;
    }

    public function extractLastInteger($text)
    {

        if (preg_match('/\d+$/', $text, $matches)) {

            return intval($matches[0]);
        } else {

            return 0;
        }
    }

    public function getUserActivityReport(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        // TEMPORARY RECOVERY BYPASS (SIDEBAR_063): keep role/page permission checks active.
        $petroPdTemporaryRecoveryEnabled = true;

        if (! auth()->user()->can('superadmin') && ! auth()->user()->can('petro_pd.view_report')) {
            abort(403, 'Unauthorized Access');
        }

        if (request()->ajax()) {
            $business_users = User::where('business_id', $business_id)
                ->pluck('id')
                ->toArray();

            // Petro PD activity report must show Petro PD logs only.
            // log_name = Settlement PD is already module-specific. The previous subject_type/subject_id
            // filters were too strict and could hide valid rows because older logs used different
            // subject_type values or saved details only in properties.
            $activity = Activity::query()
                ->whereIn('causer_id', $business_users)
                ->where('log_name', 'Settlement PD')
                ->whereIn('description', ['update', 'delete']);

            if (! empty(request()->user) && request()->user != 'All') {
                $activity->where('causer_id', request()->user);
            }

            if (! empty(request()->type) && request()->type != 'All') {
                $activity->where('description', request()->type);
            }

            if (! empty(request()->startDate) && ! empty(request()->endDate)) {
                $activity->whereDate('created_at', '>=', request()->startDate);
                $activity->whereDate('created_at', '<=', request()->endDate);
            }

            $datatable = DataTables::of($activity)
                ->editColumn('created_at', '{{ @format_datetime($created_at) }}')
                ->removeColumn('id')
                ->editColumn('causer_id', function ($row) {
                    $user = User::where('id', $row->causer_id)->select('username')->first();

                    return $user ? $user->username : '';
                })
                ->addColumn('ref_no', function ($row) {
                    $attributes = json_decode($row->properties, true);
                    if (! empty($attributes['attributes']['settlement_no'])) {
                        return $attributes['attributes']['settlement_no'];
                    }
                    $s = Settlement::where('business_id', session('user.business_id'))
                        ->where('id', $row->subject_id)
                        ->first();

                    return $s->settlement_no ?? '';
                })
                ->editColumn('description', function ($row) {
                    if ($row->description === 'update') {
                        return __('petropd::lang.edit');
                    }
                    if ($row->description === 'delete') {
                        return __('petropd::lang.delete');
                    }

                    return $row->description;
                })
                ->addColumn('description_details', function ($row) {
                    $html = '';
                    if ($row->description == 'update') {
                        $raw = $row->properties;
                        if (is_string($raw) && $raw !== '') {
                            $decoded = json_decode($raw, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $html .= e($decoded[0] ?? '');
                            } else {
                                $html .= nl2br(e($raw));
                            }
                        }
                    } elseif ($row->description == 'delete') {
                        $attributes = json_decode($row->properties, true);
                        $new          = is_array($attributes) ? ($attributes['attributes'] ?? []) : [];
                        $html .= 'Deleted settlement no: '
                            . e($new['settlement_no'] ?? (string) $row->subject_id);
                    }

                    return $html;
                });

            return $datatable->rawColumns(['description_details'])->make(true);
        }

        $users = User::where('business_id', $business_id)->pluck('username', 'id');
        $type  = [
            'update' => __('petropd::lang.edit'),
            'delete' => __('petropd::lang.delete'),
        ];

        return view('petropd::report.user_activity', compact('users', 'type'));
    }

    public function listPdSettlement()
    {
        $business_id = request()->session()->get('user.business_id');

        // TEMPORARY RECOVERY BYPASS (SIDEBAR_063): keep role/page permission checks active.
        $petroPdTemporaryRecoveryEnabled = true;

        // S 268 FIX (2026-05-31): Do NOT auto-create a pending PD settlement from the List page.
        // The List page must only display settlements that were actually started/saved from PD Settlement.
        // Auto-draft creation here caused open/not-finished shifts to appear as Pending settlements.

        $business = Business::find($business_id);
        $ref_no_prefixes = !empty($business->ref_no_prefixes) ? $business->ref_no_prefixes : [];
        if (is_string($ref_no_prefixes)) {
            $decoded = json_decode($ref_no_prefixes, true);
            $ref_no_prefixes = is_array($decoded) ? $decoded : [];
        }
        $std_prefix = $ref_no_prefixes['settlement'] ?? 'ST';

        $query_prefixes = $this->getPdSettlementPrefixes($business_id);

        $primary_prefix = $this->getPrimaryPdSettlementPrefix($business_id);

        if (request()->ajax()) {
            // IS1831: Always keep the settlement named in the post-save redirect
            // visible. This also protects newly saved PD settlements when an
            // older business prefix configuration has not yet been refreshed.
            $savedSettlementId = (int) request()->input('saved_settlement_id', 0);

            // dd('masok');
            $query = Settlement::leftJoin(
                'business_locations',
                'settlements.location_id',
                '=',
                'business_locations.id'
            )
                ->leftJoin(
                    'pump_operators',
                    'settlements.pump_operator_id',
                    '=',
                    'pump_operators.id'
                )
                ->leftJoin('business_locations as po_location', 'pump_operators.location_id', '=', 'po_location.id')
                ->leftJoin('business_locations as fallback_location', function ($join) use ($business_id) {
                    $join->on('fallback_location.business_id', '=', DB::raw((int) $business_id));
                })
                ->leftJoin('pump_operator_assignments', function ($join) {
                    $join->on('settlements.id', '=', 'pump_operator_assignments.settlement_id')
                        ->orOn(function ($q) {
                            $q->on('settlements.pump_operator_id', '=', 'pump_operator_assignments.pump_operator_id')
                                ->whereNull('pump_operator_assignments.settlement_id')
                                ->where('settlements.status', 1);
                        });
                })
                ->where('settlements.business_id', $business_id);

            $this->scopePetroPdOwnedSettlements($query, (int) $business_id);

            $query->select([
                    'pump_operators.name as pump_operator_name',
                    DB::raw('COALESCE(business_locations.name, po_location.name, fallback_location.name) as location_name'),
                    'settlements.*',
                    DB::raw('GROUP_CONCAT(DISTINCT pump_operator_assignments.shift_number ORDER BY CAST(pump_operator_assignments.shift_number AS UNSIGNED) SEPARATOR ",") as shift_number'),
                ])
                ->with([
                    'meter_sales',
                    'other_sales',
                    'meter_sales_pd.details',
                    'loan_payments',
                    'cash_payments',
                    'cash_deposits',
                    'card_payments',
                    'cheque_payments',
                    'credit_sale_payments',
                    'expense_payments',
                    'shortage_payments',
                    'excess_payments',
                    'customer_loans',
                ]);

            // dd(request()->location_id);

            // A just-finalized settlement must be shown even when DataTables has
            // restored filters from an earlier visit to the list page.
            /*
             |------------------------------------------------------------------
             | LA-1159: a saved settlement sometimes did not appear in the list.
             |------------------------------------------------------------------
             |
             | The LOCATION column is displayed with a fallback chain:
             |
             |     COALESCE(business_locations.name, po_location.name,
             |              fallback_location.name)
             |
             | so a settlement whose own location_id is empty still SHOWS a
             | location - the pump operator's, or any location of the business.
             | The filter below matched on settlements.location_id alone, so those
             | same rows were filtered straight back out again. The settlement
             | looked correctly located whenever it was visible, and vanished as
             | soon as a Business Location filter was applied.
             |
             | Settlements can genuinely carry an empty location_id: AddPaymentController
             | writes 'location_id' => $request->location_id ?? '' when a payment
             | creates the settlement without one.
             |
             | That is also why the row reappears straight after saving - the
             | post-save redirect carries saved_settlement_id, which skips this
             | filter entirely. Exactly the reported "sometimes it is there,
             | sometimes it is not".
             |
             | The filter now accepts a row when its own location matches OR when
             | it has no location of its own and the pump operator's location
             | matches - the same first two links the column already displays. A
             | row belonging to another location is still excluded.
             */
            if ($savedSettlementId === 0 && !empty(request()->location_id)) {
                $requestedLocationId = request()->location_id;

                $query->where(function ($locationQuery) use ($requestedLocationId) {
                    $locationQuery->where('settlements.location_id', $requestedLocationId)
                        ->orWhere(function ($fallbackQuery) use ($requestedLocationId) {
                            $fallbackQuery->where(function ($emptyOwnLocation) {
                                $emptyOwnLocation->whereNull('settlements.location_id')
                                    ->orWhere('settlements.location_id', '')
                                    ->orWhere('settlements.location_id', 0);
                            })
                                ->where('pump_operators.location_id', $requestedLocationId);
                        });
                });
            }

            if ($savedSettlementId === 0 && !empty(request()->pump_operator)) {
                $query->where('settlements.pump_operator_id', request()->pump_operator);
            }

            if ($savedSettlementId === 0 && !empty(request()->settlement_no)) {
                $query->where('settlements.id', request()->settlement_no);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                // IS1814-8: Keep the just-finalized settlement visible even when a
                // stale browser date-range state would otherwise exclude it.
                $query->where(function ($dateQuery) use ($savedSettlementId) {
                    $dateQuery->where(function ($rangeQuery) {
                        $rangeQuery->whereDate('settlements.transaction_date', '>=', request()->start_date)
                            ->whereDate('settlements.transaction_date', '<=', request()->end_date);
                    });

                    if ($savedSettlementId > 0) {
                        $dateQuery->orWhere('settlements.id', $savedSettlementId);
                    }
                });
            }

            //  dd($query->get());
            $query->groupBy('settlements.id');
            $query->orderBy('settlements.id', 'desc');
           

            $first = Settlement::where('business_id', $business_id)
                ->where(function ($query) use ($query_prefixes) {
                    foreach ($query_prefixes as $prefix) {
                        $query->orWhere('settlement_no', 'LIKE', $prefix . '%');
                    }
                })
                ->where('status', 0)
                ->orderBy('id', 'desc')
                ->first();

            $delete_settlement = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'delete_settlement');
            $edit_settlement = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'edit_settlement');
            $edit_settlement_no_change = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'edit_settlement_no_change');
            $superAdminBypass = class_exists(SidebarPermissionUtil::class)
                && SidebarPermissionUtil::hasSuperAdminBypass();
            $canEditSettlement = $superAdminBypass
                || (auth()->user()->can('petro_pd.edit_settlement') && $edit_settlement);
            $canEditSettlementNoChange = $superAdminBypass
                || (auth()->user()->can('petro_pd.edit_settlement') && $edit_settlement_no_change);
            $canDeleteSettlement = $superAdminBypass
                || (auth()->user()->can('petro_pd.delete_settlement') && $delete_settlement);

            $settlements = DataTables::of($query)
                ->filterColumn('location_name', function ($query, $keyword) {
                    $keyword = '%' . $keyword . '%';
                    $query->where(function ($locationQuery) use ($keyword) {
                        $locationQuery
                            ->where('business_locations.name', 'like', $keyword)
                            ->orWhere('po_location.name', 'like', $keyword)
                            ->orWhere('fallback_location.name', 'like', $keyword);
                    });
                })
                ->addColumn('action', function ($row) use (
                    $first,
                    $canEditSettlement,
                    $canEditSettlementNoChange,
                    $canDeleteSettlement
                ) {
                    $settlementId = (int) $row->id;
                    $viewUrl = route('petropd.settlement-pd.show', [$settlementId]);
                    $editUrl = route('petropd.settlement-pd.edit', [$settlementId]);
                    $editNoChangeUrl = $editUrl . '?no_change=1&source=petropd';
                    $deleteUrl = route('petropd.settlement-pd.destroy', [$settlementId]);
                    $printUrl = route('petropd.settlement-pd.print', [$settlementId]);

                    $menuItems = '<li><a href="#" data-href="' . $viewUrl .
                        '" class="petropd-view-settlement"><i class="fa fa-eye" aria-hidden="true"></i> ' .
                        __('messages.view') . '</a></li>';

                    if ($canEditSettlement && (int) $row->status === 0) {
                        $menuItems .= '<li><a href="' . $editUrl .
                            '" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> ' .
                            __('messages.edit') . '</a></li>';
                    }

                    if ($canEditSettlementNoChange && (int) $row->status === 0) {
                        $menuItems .= '<li><a href="' . $editNoChangeUrl .
                            '" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> ' .
                            __('petropd::lang.edit_no_change') . '</a></li>';
                    }

                    if ((int) $row->status === 0
                        && ! empty($first)
                        && (int) $first->id === $settlementId
                        && $canDeleteSettlement) {
                        $menuItems .= '<li><a href="' . $deleteUrl .
                            '" class="delete_settlement_button"><i class="fa fa-trash"></i> ' .
                            __('messages.delete') . '</a></li>';
                    }

                    $menuItems .= '<li><a href="#" data-href="' . $printUrl .
                        '" class="print_settlement_button"><i class="fa fa-print"></i> ' .
                        __('petropd::lang.print') . '</a></li>';

                    $menu = '<ul class="petropd-settlement-action-menu" role="menu" hidden>' .
                        $menuItems . '</ul>';

                    if ((int) $row->status === 1) {
                        $finishUrl = Str::startsWith((string) $row->settlement_no, 'SET-SW')
                            ? action('\Modules\SettlementSW\Http\Controllers\SettlementSWController@index')
                            : route('petropd.pd-settlement', ['settlement_id' => $settlementId]);

                        return '<div class="petropd-settlement-action-shell" data-settlement-id="' . $settlementId . '">' .
                            '<a class="btn btn-danger btn-sm petropd-settlement-primary-action" href="' . $finishUrl . '">' .
                            __('petropd::lang.finish_settlement') . '</a>' .
                            '<button type="button" class="btn btn-danger btn-sm petropd-settlement-action-trigger" ' .
                            'aria-haspopup="true" aria-expanded="false" title="' . __('messages.actions') . '">' .
                            '<span class="caret"></span><span class="sr-only">' . __('messages.actions') . '</span></button>' .
                            $menu . '</div>';
                    }

                    if ((int) $row->is_edit === 1) {
                        return '<div class="petropd-settlement-action-shell" data-settlement-id="' . $settlementId . '">' .
                            '<a class="btn btn-warning btn-sm petropd-settlement-primary-action" href="' . $editUrl . '">' .
                            __('petropd::lang.finish_editting') . '</a>' .
                            '<button type="button" class="btn btn-warning btn-sm petropd-settlement-action-trigger" ' .
                            'aria-haspopup="true" aria-expanded="false" title="' . __('messages.actions') . '">' .
                            '<span class="caret"></span><span class="sr-only">' . __('messages.actions') . '</span></button>' .
                            $menu . '</div>';
                    }

                    return '<div class="petropd-settlement-action-shell" data-settlement-id="' . $settlementId . '">' .
                        '<button type="button" class="btn btn-info btn-sm petropd-settlement-action-trigger" ' .
                        'aria-haspopup="true" aria-expanded="false">' .
                        __('messages.actions') . ' <span class="caret"></span></button>' .
                        $menu . '</div>';
                })
                ->editColumn('status', function ($row) {
                    // Editing must take priority over the underlying settlement status.
                    // Some historical rows retain status = 0 while they are reopened.
                    if ((int) ($row->is_edit ?? 0) === 1) {
                        return '<span class="petropd-status-badge petropd-status-editing">Editing</span>';
                    }

                    if ((int) $row->status === 0) {
                        return '<span class="petropd-status-badge petropd-status-completed">Completed</span>';
                    }

                    return '<span class="petropd-status-badge petropd-status-pending">Pending</span>';
                })
                ->addColumn('pump_nos', function ($row) {
                    /*
                     | S776 (2026-09-26): the Pump No column must be shift-owned.
                     |
                     | Do NOT read the broad meter_sales_pd / meter_sales Eloquent
                     | relationships here.  Historical bad links can attach a meter
                     | sale from another shift to the same settlement number, which
                     | made unrelated pumps appear in List PD Settlement.
                     |
                     | Resolve pumps from the settlement-linked assignments first,
                     | then from the settlement's own work_shift.  Only if historical
                     | assignment data is missing do we fall back to meter-sale rows,
                     | and that fallback is still business + operator + exact-shift
                     | scoped.
                     */
                    $pumpIds = $this->getPdSettlementListPumpIds($row);

                    if (empty($pumpIds)) {
                        return '';
                    }

                    return implode(', ', self::ma002PumpNos($pumpIds));
                })
                ->editColumn('shift', function ($row) {
                    $workShift = $row->work_shift;
                    if (is_string($workShift)) {
                        $decoded = json_decode($workShift, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $workShift = $decoded;
                        }
                    }

                    if (!empty($workShift) && is_array($workShift)) {
                        $shifts = WorkShift::whereIn('id', $workShift)->pluck('shift_name')->toArray();
                        return implode(',', $shifts);
                    }

                    return '';
                })
                ->addColumn('created_by', function ($row) {
                    $transaction = Transaction::where('invoice_no', $row->settlement_no)
                        ->leftJoin('users', 'users.id', 'transactions.created_by')
                        ->select('users.username')
                        ->first();

                    if (!empty($transaction)) {
                        return $transaction->username;
                    }
                })
                ->editColumn('transaction_date', function ($row) {
                    return $this->transactionUtil->format_date($row->transaction_date);
                })
                ->editColumn('total_amount', function ($row) {
                    $adjusted_total = $this->calculatePdSettlementListTotal($row);

                    return '<span class="total_amount">' .
                        number_format((float) $adjusted_total, 2, '.', ',') .
                        '</span>';
                })
                ->editColumn('settlement_no', function ($row) {
                    return $row->settlement_no;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        return route('petropd.settlement-pd.show', [$row->id]);
                    },
                ])
                ->removeColumn('id');

            return $settlements
                ->rawColumns(['action', 'status', 'total_amount'])
                ->make(true)
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'id');

        $settlement_nos_query = Settlement::where('business_id', $business_id);
        $this->scopePetroPdOwnedSettlements(
            $settlement_nos_query,
            (int) $business_id,
            'settlement_no',
            'settlements.id'
        );

        $settlement_nos_collection = $settlement_nos_query
            ->with(['meter_sales', 'meter_sales_pd'])
            ->orderByDesc('id')
            ->get();

        $settlement_nos = [];
        foreach ($settlement_nos_collection as $settlement) {
            $settlement_nos[$settlement->id] = $settlement->settlement_no;
        }

        $message = $this->transactionUtil->getGeneralMessage('general_message_pump_management_checkbox');

        return view('petropd::pd_settlement_list.index', compact('business_locations', 'pump_operators', 'settlement_nos', 'message'));
    }

    private function calculatePdSettlementListTotal($settlement): float
    {
        // IMPORTANT: The PD List Settlement Total column must match the
        // Settlement Preview / Payment Details total.
        // Do not use meter sales here; meter sales is the sales side and is
        // already settled by the payment rows. Adding it here duplicates the
        // amount and makes the list total higher than the preview total.
        $paymentTotal = 0.0;

        $paymentTotal += $this->sumPdSettlementRelationAmount($settlement, 'loan_payments');
        $paymentTotal += $this->sumPdSettlementRelationAmount($settlement, 'cash_payments');
        $paymentTotal += $this->sumPdSettlementRelationAmount($settlement, 'cash_deposits');
        $paymentTotal += $this->sumPdSettlementRelationAmount($settlement, 'card_payments');
        $paymentTotal += $this->sumPdSettlementRelationAmount($settlement, 'cheque_payments');
        $paymentTotal += $this->sumPdSettlementRelationNetAmount($settlement, 'credit_sale_payments');
        $paymentTotal += $this->sumPdSettlementRelationAmount($settlement, 'expense_payments');
        $paymentTotal += $this->sumPdSettlementRelationAmount($settlement, 'shortage_payments');
        $paymentTotal += $this->sumPdSettlementRelationAmount($settlement, 'excess_payments');
        $paymentTotal += $this->sumPdSettlementRelationAmount($settlement, 'customer_loans');

        if ($paymentTotal > 0) {
            return $paymentTotal;
        }

        // Fallback only for very old records with no payment rows loaded.
        return (float) ($settlement->total_amount ?? 0);
    }

    private function sumPdSettlementRelationAmount($settlement, string $relation): float
    {
        if (!method_exists($settlement, $relation) && !$settlement->relationLoaded($relation)) {
            return 0.0;
        }

        $items = $settlement->relationLoaded($relation)
            ? $settlement->getRelation($relation)
            : $settlement->{$relation};

        return !empty($items) ? (float) $items->sum('amount') : 0.0;
    }

    private function sumPdSettlementRelationNetAmount($settlement, string $relation): float
    {
        if (!method_exists($settlement, $relation) && !$settlement->relationLoaded($relation)) {
            return 0.0;
        }

        $items = $settlement->relationLoaded($relation)
            ? $settlement->getRelation($relation)
            : $settlement->{$relation};

        if (empty($items)) {
            return 0.0;
        }

        return (float) $items->sum('amount') - (float) $items->sum('total_discount');
    }

    private function calculatePdSettlementListShiftMeterSalesTotal($settlement): float
    {
        $shiftIds = $this->getPdSettlementListShiftIds($settlement);

        if (empty($shiftIds) || empty($settlement->pump_operator_id)) {
            return 0.0;
        }

        return (float) PumpOperatorMeterSale::with('details')
            ->where('business_id', $settlement->business_id)
            ->where('pump_operator_id', $settlement->pump_operator_id)
            ->whereIn('shift_id', $shiftIds)
            ->when(Schema::hasColumn('pump_operator_meter_sales', 'source'), function ($query) {
                $this->onlyClosedPumpMeterSales($query);
            })
            ->get()
            ->sum(function ($sale) {
                $detailTotal = !empty($sale->details)
                    ? (float) $sale->details->sum('amount')
                    : 0.0;

                return $detailTotal > 0
                    ? $detailTotal
                    : (float) ($sale->amount ?? 0);
            });
    }

    private function calculatePdSettlementListPumpOtherSalesTotal($settlement): float
    {
        $shiftIds = $this->getPdSettlementListShiftIds($settlement);

        if (empty($shiftIds)) {
            return 0.0;
        }

        return (float) PumpOperatorOtherSale::where('business_id', $settlement->business_id)
            ->whereIn('shift_id', $shiftIds)
            ->get()
            ->sum(function ($sale) {
                return (float) ($sale->sub_total ?? 0) - (float) ($sale->discount_amount ?? 0);
            });
    }

    /**
     * S776: Return only pumps that belong to THIS settlement's shift(s).
     *
     * Priority:
     *   1. pump_operator_assignments explicitly linked to settlement_id;
     *   2. assignments for the settlement's own work_shift ids;
     *   3. exact-shift meter-sale rows as a historical-data fallback.
     *
     * Broad settlement_no relationships are deliberately not used because a
     * stale/incorrect relationship is the defect S776 is protecting against.
     */
    private function getPdSettlementListPumpIds($settlement): array
    {
        $businessId = (int) ($settlement->business_id ?? 0);
        $operatorId = (int) ($settlement->pump_operator_id ?? 0);
        $settlementId = (int) ($settlement->id ?? 0);

        if ($businessId <= 0 || $operatorId <= 0) {
            return [];
        }

        $shiftIds = $this->getPdSettlementListOwnShiftIds($settlement);
        if (empty($shiftIds)) {
            return [];
        }

        // Strongest authority: assignments linked directly to this settlement,
        // but ONLY inside the settlement's own work_shift.  The shift condition
        // is intentional: even a stale settlement_id link must not pull a pump
        // from another shift into this row.
        if ($settlementId > 0) {
            $linkedPumpIds = PumpOperatorAssignment::where('business_id', $businessId)
                ->where('pump_operator_id', $operatorId)
                ->where('settlement_id', $settlementId)
                ->whereIn('shift_id', $shiftIds)
                ->pluck('pump_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            if (! empty($linkedPumpIds)) {
                return $linkedPumpIds;
            }
        }

        // Normal path for pending/legacy settlements whose assignments have
        // not yet been stamped with settlement_id: exact operator + exact shift.
        $assignmentPumpIds = PumpOperatorAssignment::where('business_id', $businessId)
            ->where('pump_operator_id', $operatorId)
            ->whereIn('shift_id', $shiftIds)
            ->pluck('pump_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        if (! empty($assignmentPumpIds)) {
            return $assignmentPumpIds;
        }

        // Historical fallback: some old rows have no usable assignment link.
        // Keep this fallback exact-shift scoped; never use settlement_no alone.
        $pdPumpIds = [];
        $pdSales = PumpOperatorMeterSale::with('details')
            ->where('business_id', $businessId)
            ->where('pump_operator_id', $operatorId)
            ->whereIn('shift_id', $shiftIds)
            ->get();

        foreach ($pdSales as $sale) {
            if (! empty($sale->details)) {
                $pdPumpIds = array_merge($pdPumpIds, $sale->details->pluck('pump_id')->toArray());
            }
        }

        $pdPumpIds = array_values(array_unique(array_filter(array_map('intval', $pdPumpIds))));
        if (! empty($pdPumpIds)) {
            return $pdPumpIds;
        }

        // Final legacy fallback for manual meter_sales.  Both the settlement
        // link AND exact shift must match, so a bad link from another shift is
        // still excluded.
        $settlementLinks = array_values(array_unique(array_filter([
            (string) $settlementId,
            (string) ($settlement->settlement_no ?? ''),
        ], static fn ($value) => $value !== '' && $value !== '0')));

        if (empty($settlementLinks)) {
            return [];
        }

        return MeterSale::where('business_id', $businessId)
            ->whereIn('settlement_no', $settlementLinks)
            ->whereIn('shift_id', $shiftIds)
            ->pluck('pump_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * S776: Resolve the settlement's own shift ids without learning shifts from
     * meter-sale relationships.  Those relationships are exactly where stale
     * cross-shift links can exist.
     */
    private function getPdSettlementListOwnShiftIds($settlement): array
    {
        // The settlement's saved work_shift is the primary authority.  It is
        // what the PD Settlement workflow selected for this settlement and it
        // must not be widened by meter-sale or stale settlement links.
        $workShift = $settlement->work_shift ?? [];

        if (is_string($workShift)) {
            $decoded = json_decode($workShift, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $workShift = $decoded;
            } else {
                $workShift = array_filter(array_map('trim', explode(',', $workShift)));
            }
        }

        if (is_array($workShift)) {
            $workShiftIds = array_values(array_unique(array_filter(array_map('intval', $workShift))));
            if (! empty($workShiftIds)) {
                return $workShiftIds;
            }
        }

        // Historical fallback only: old settlements may not have work_shift
        // populated.  In that case use assignments explicitly linked to the
        // settlement; do not derive shift ids from meter-sale relationships.
        $businessId = (int) ($settlement->business_id ?? 0);
        $operatorId = (int) ($settlement->pump_operator_id ?? 0);
        $settlementId = (int) ($settlement->id ?? 0);

        if ($businessId <= 0 || $operatorId <= 0 || $settlementId <= 0) {
            return [];
        }

        return PumpOperatorAssignment::where('business_id', $businessId)
            ->where('pump_operator_id', $operatorId)
            ->where('settlement_id', $settlementId)
            ->pluck('shift_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();
    }

    private function getPdSettlementListShiftIds($settlement): array
    {
        $workShift = $settlement->work_shift ?? [];

        if (is_string($workShift)) {
            $decoded = json_decode($workShift, true);
            $workShift = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (!is_array($workShift)) {
            $workShift = [];
        }

        $shiftIds = array_filter(array_map('intval', $workShift));

        if (!empty($settlement->meter_sales_pd) && $settlement->meter_sales_pd->count() > 0) {
            $shiftIds = array_merge($shiftIds, $settlement->meter_sales_pd->pluck('shift_id')->filter()->map(fn ($id) => (int) $id)->toArray());
        }

        if (!empty($settlement->meter_sales) && $settlement->meter_sales->count() > 0) {
            $shiftIds = array_merge($shiftIds, $settlement->meter_sales->pluck('shift_id')->filter()->map(fn ($id) => (int) $id)->toArray());
        }

        return array_values(array_unique(array_filter($shiftIds)));
    }

    protected function getPdSettlementPrefixes($business_id): array
    {
        $prefixes = request()->session()->get('business.ref_no_prefixes', []);

        if (empty($prefixes) && ! empty($business_id)) {
            $business = Business::find($business_id);
            $prefixes = $business->ref_no_prefixes ?? [];
        }

        if (is_string($prefixes)) {
            $decoded = json_decode($prefixes, true);
            $prefixes = is_array($decoded) ? $decoded : [];
        }

        return array_values(array_unique(array_filter([
            'PDST',
            $this->getConfiguredPdPrefixIfExclusive($prefixes),
        ])));
    }

    protected function getPrimaryPdSettlementPrefix($business_id): string
    {
        $prefixes = $this->getPdSettlementPrefixes($business_id);

        return $prefixes[0] ?? 'PDST';
    }

    /**
     * IS1840: A Petro PD settlement is owned either by the PD number prefix or
     * by an authoritative closed Pumper Dashboard assignment with meter sales.
     * This restores legacy PD settlements that were saved with an ST number.
     */
    protected function scopePetroPdOwnedSettlements(
        $query,
        int $businessId,
        string $settlementNoColumn = 'settlements.settlement_no',
        string $settlementIdColumn = 'settlements.id'
    ) {
        $prefixes = $this->getPdSettlementPrefixes($businessId);

        return $query->where(function ($owned) use (
            $prefixes,
            $businessId,
            $settlementNoColumn,
            $settlementIdColumn
        ) {
            foreach ($prefixes as $prefix) {
                $owned->orWhere($settlementNoColumn, 'LIKE', $prefix . '%');
            }

            $owned->orWhereExists(function ($assignment) use ($businessId, $settlementIdColumn) {
                $assignment->selectRaw('1')
                    ->from('pump_operator_assignments as pd_owned_assignments')
                    ->whereColumn('pd_owned_assignments.settlement_id', $settlementIdColumn)
                    ->where('pd_owned_assignments.business_id', $businessId)
                    ->whereIn('pd_owned_assignments.status', ['close', 'closed'])
                    ->whereNotNull('pd_owned_assignments.close_date_and_time')
                    ->whereExists(function ($meterSale) {
                        $meterSale->selectRaw('1')
                            ->from('pump_operator_meter_sales as pd_owned_meter_sales')
                            ->whereColumn(
                                'pd_owned_meter_sales.shift_id',
                                'pd_owned_assignments.shift_id'
                            );
                    });
            });
        });
    }

    protected function getConfiguredPdPrefixIfExclusive(array $prefixes): ?string
    {
        $pdPrefix = $prefixes['settlement_pd'] ?? null;
        $petroPrefix = $prefixes['settlement'] ?? 'ST';

        if (empty($pdPrefix) || $pdPrefix === $petroPrefix) {
            return null;
        }

        return $pdPrefix;
    }

    protected function onlyClosedPumpMeterSales($query)
    {
        if (Schema::hasColumn('pump_operator_meter_sales', 'source')) {
            // Older Close Pump rows were saved before the source column was
            // consistently populated.  They are still valid closed-shift sales
            // and must remain visible in Petro PD.  Exact business/operator/shift
            // scoping protects this compatibility fallback from unrelated rows.
            $query->where(function ($sourceQuery) {
                $sourceQuery->where('source', 'closing')
                    ->orWhereNull('source')
                    ->orWhere('source', '');
            });
        }

        return $query;
    }

    /**
     * Build the first-render Meter Sales rows from the same closed-shift source
     * used by the AJAX refresh.  This prevents a blank table when the page is
     * still loading scripts or when a deferred script is blocked by another
     * page-level JavaScript error.
     */
    private function buildPdMeterSaleDisplayRows($meterSales, int $businessId, int $shiftId, int $pumpOperatorId)
    {
        $meterSales = collect($meterSales);
        if ($meterSales->isEmpty()) {
            return collect();
        }

        $productIds = $meterSales->flatMap(function ($sale) {
            return collect($sale->details)->map(function ($detail) {
                return optional($detail->pump)->product_id;
            });
        })->filter()->unique()->values();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $rows = collect();
        $seenRows = [];

        foreach ($meterSales as $sale) {
            $detailCount = max(1, collect($sale->details)->count());
            foreach (collect($sale->details) as $detail) {
                $pump = $detail->pump;
                $product = $pump ? $products->get($pump->product_id) : null;
                $beforeDiscount = (float) ($detail->amount ?? 0);
                $rowKey = implode('|', [
                    $detail->pump_id ?? '',
                    number_format((float) ($detail->received_meter ?? 0), 3, '.', ''),
                    number_format((float) ($detail->new_meter ?? 0), 3, '.', ''),
                    number_format((float) ($detail->unit_price ?? 0), 4, '.', ''),
                    number_format((float) ($detail->sold_qty ?? 0), 3, '.', ''),
                    number_format($beforeDiscount, 4, '.', ''),
                ]);

                if (isset($seenRows[$rowKey])) {
                    continue;
                }
                $seenRows[$rowKey] = true;

                $hasStoredAfterDiscount = $sale->discount_amount !== null && $sale->discount_amount !== '';
                $storedAfterDiscount = (float) ($sale->discount_amount ?? 0);
                $afterDiscount = ($detailCount === 1 && $hasStoredAfterDiscount)
                    ? $storedAfterDiscount
                    : $beforeDiscount;
                $testingQty = (float) ($sale->testing_qty ?? 0);

                if ($testingQty == 0.0) {
                    $testingQty = (float) (PumperDayEntry::join(
                        'pump_operator_assignments as poa_display',
                        'pumper_day_entries.pumper_assignment_id',
                        '=',
                        'poa_display.id'
                    )
                        ->where('pumper_day_entries.business_id', $businessId)
                        ->where('pumper_day_entries.pump_operator_id', $pumpOperatorId)
                        ->where('pumper_day_entries.pump_id', $detail->pump_id)
                        ->where('poa_display.shift_id', $shiftId)
                        ->where('pumper_day_entries.starting_meter', $detail->received_meter)
                        ->where('pumper_day_entries.closing_meter', $detail->new_meter)
                        ->value('pumper_day_entries.testing_ltr') ?? 0);
                }

                $row = new \stdClass();
                $row->sku = $product->sku ?? '-';
                $row->product_name = $product->name ?? '-';
                $row->pump_no = $pump->pump_no ?? '-';
                $row->starting_meter = (float) ($detail->received_meter ?? 0);
                $row->closing_meter = (float) ($detail->new_meter ?? 0);
                $row->price = (float) ($detail->unit_price ?? 0);
                $row->quantity = (float) ($detail->sold_qty ?? 0);
                $row->discount_type = $sale->discount_type ?: '-';
                $row->discount = (float) ($sale->discount ?? 0);
                $row->testing_qty = $testingQty;
                $row->total_qty = $row->quantity + $testingQty;
                $row->sub_total = $beforeDiscount;
                $row->discount_amount = $afterDiscount;
                $row->form_url = url('/petropd/settlement-pd/get-meter-sale-form/' . $sale->id)
                    . '?meter_sale_source=pump_operator&detail_id=' . $detail->id;
                $rows->push($row);
            }
        }

        return $rows;
    }

    /**
     * Generate the next PetroPD settlement number safely.
     *
     * This checks every existing PetroPD prefix for this business, extracts the
     * ending number, takes the highest value, then skips any existing number.
     * It avoids duplicate/display-reset issues such as PDST1 appearing again
     * after PDST1 was already completed.
     */
    protected function getNextPdSettlementNo(int $business_id): string
    {
        $primaryPrefix = $this->getPrimaryPdSettlementPrefix($business_id);
        $prefixes = $this->getPdSettlementPrefixes($business_id);

        $maxNumber = Settlement::where('business_id', $business_id)
            ->where(function ($query) use ($prefixes) {
                foreach ($prefixes as $prefix) {
                    $query->orWhere('settlement_no', 'LIKE', $prefix . '%');
                }
            })
            ->pluck('settlement_no')
            ->map(function ($settlementNo) {
                return $this->extractLastInteger($settlementNo);
            })
            ->max();

        $nextNumber = ((int) $maxNumber) + 1;

        while (Settlement::where('business_id', $business_id)
            ->where('settlement_no', $primaryPrefix . $nextNumber)
            ->exists()) {
            $nextNumber++;
        }

        return $primaryPrefix . $nextNumber;
    }

    private function ensureNextPendingPdSettlementDraft(int $business_id): void
    {
        $activeDraft = Settlement::where('business_id', $business_id)
            ->where(function ($query) use ($business_id) {
                foreach ($this->getPdSettlementPrefixes($business_id) as $prefix) {
                    $query->orWhere('settlement_no', 'LIKE', $prefix . '%');
                }
            })
            ->where('status', 1)
            ->exists();

        if ($activeDraft) {
            return;
        }

        $pending = PetroPdClosedShiftQuery::oldestPendingGlobally($business_id);
        if (! $pending) {
            return;
        }

        $pumpOperator = PumpOperator::where('business_id', $business_id)
            ->where('id', $pending->pump_operator_id)
            ->first();

        if (! $pumpOperator) {
            return;
        }

        DB::transaction(function () use ($business_id, $pending, $pumpOperator) {
            $stillPending = PetroPdClosedShiftQuery::isNextAllowedShift($business_id, (int) $pending->shift_id);
            if (! $stillPending) {
                return;
            }

            $existingDraft = Settlement::where('business_id', $business_id)
                ->where(function ($query) use ($business_id) {
                    foreach ($this->getPdSettlementPrefixes($business_id) as $prefix) {
                        $query->orWhere('settlement_no', 'LIKE', $prefix . '%');
                    }
                })
                ->where('status', 1)
                ->lockForUpdate()
                ->first();

            if ($existingDraft) {
                return;
            }

            $settlement_no = $this->getNextPdSettlementNo($business_id);

        // S269 PERMANENT GUARD: PD screen must never display ST.
        if (! Str::startsWith((string) $settlement_no, $this->getPrimaryPdSettlementPrefix($business_id))) {
            $settlement_no = $this->getNextPdSettlementNo($business_id);
        }

            $meterTotal = PumpOperatorMeterSale::where('business_id', $business_id)
                ->where('pump_operator_id', $pending->pump_operator_id)
                ->where('shift_id', $pending->shift_id)
                ->when(true, function ($query) {
                    $this->onlyClosedPumpMeterSales($query);
                })
                ->where(function ($query) {
                    $query->whereNull('settlement_no')
                        ->orWhere('settlement_no', '');
                })
                ->sum('amount');

            $testingQty = PumperDayEntry::join('pump_operator_assignments as poa', 'pumper_day_entries.pumper_assignment_id', '=', 'poa.id')
                ->where('pumper_day_entries.business_id', $business_id)
                ->where('pumper_day_entries.pump_operator_id', $pending->pump_operator_id)
                ->where('poa.shift_id', $pending->shift_id)
                ->sum('pumper_day_entries.testing_ltr');

            $settlement = Settlement::create([
                'settlement_no'    => $settlement_no,
                'business_id'      => $business_id,
                'transaction_date' => date('Y-m-d'),
                'location_id'      => $pumpOperator->location_id,
                'pump_operator_id' => $pending->pump_operator_id,
                'work_shift'       => [(string) $pending->shift_id],
                'total_amount'     => $meterTotal,
                'status'           => 1,
            ]);

            PumpOperatorAssignment::where('business_id', $business_id)
                ->where('pump_operator_id', $pending->pump_operator_id)
                ->where('shift_id', $pending->shift_id)
                ->whereNull('settlement_id')
                ->update(['settlement_id' => $settlement->id]);

            PumpOperatorMeterSale::where('business_id', $business_id)
                ->where('pump_operator_id', $pending->pump_operator_id)
                ->where('shift_id', $pending->shift_id)
                ->when(true, function ($query) {
                    $this->onlyClosedPumpMeterSales($query);
                })
                ->where(function ($query) {
                    $query->whereNull('settlement_no')
                        ->orWhere('settlement_no', '');
                })
                ->update([
                    'settlement_no' => $settlement->settlement_no,
                    'testing_qty'   => $testingQty,
                ]);
        });
    }

    public function getOperatorShifts(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $pump_operator_id = $request->pump_operator_id;

        if (empty($pump_operator_id)) {
            return response()->json(['success' => false, 'optionHtml' => '']);
        }

        $oldest = PetroPdClosedShiftQuery::oldestPendingForOperator($business_id, (int) $pump_operator_id);

        $optionHtml = '';
        if ($oldest) {
            $optionHtml = '<option value="' . (int) $oldest->shift_id . '" selected>'
                . e($oldest->shift_number) . '</option>';
        }

        return response()->json([
            'success'    => true,
            'optionHtml' => $optionHtml,
            'count'      => $oldest ? 1 : 0,
        ]);
    }

    public function getShiftWorkShift(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $shift_id = $request->shift_id;

        if (empty($shift_id)) {
            return response()->json([
                'success'            => false,
                'work_shift_id'      => null,
                'pump_operator_id'   => null,
                'pump_operator_name' => null,
            ]);
        }

        $shift = PetroShift::where('id', $shift_id)
            ->where('business_id', $business_id)
            ->first();

        // Used by Settlement PD create: when user picks shift first, sync pump operator so meter sale lists / filters load.
        $pump_operator_id   = null;
        $pump_operator_name = null;
        $assignment         = PumpOperatorAssignment::with('pumpOperator')
            ->where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            ->orderBy('id')
            ->first();
        if ($assignment) {
            $pump_operator_id = (int) $assignment->pump_operator_id;
            $pump_operator_name = optional($assignment->pumpOperator)->name;
        }

        return response()->json([
            'success'              => true,
            'work_shift_id'        => $shift ? $shift->work_shift_id : null,
            'pump_operator_id'     => $pump_operator_id,
            'pump_operator_name'   => $pump_operator_name,
        ]);
    }

    public function getManualEntryMeterSales(Request $request)
    {
        $business_id = $this->resolvePetroPdBusinessId();
        $pump_operator_id = $request->input('pump_operator_id');
        $shift_id = $request->input('shift_id');

        if (empty($pump_operator_id) || empty($shift_id)) {
            return response()->json(['success' => false, 'rows' => []]);
        }

        $requested_settlement_no = trim((string) $request->input('settlement_no', ''));
        $active_settlement = null;

        if (! empty($request->active_settlement_id)) {
            $active_settlement = Settlement::where('business_id', $business_id)
                ->where('id', $request->active_settlement_id)
                ->first();
        }

        if (empty($active_settlement) && $requested_settlement_no !== '') {
            $active_settlement = Settlement::where('business_id', $business_id)
                ->where('settlement_no', $requested_settlement_no)
                ->first();
        }

        // Only use the legacy latest-draft fallback when the caller did not
        // provide the settlement number currently displayed on the screen.
        // Otherwise a different draft for the same operator can hide the
        // closed-pump rows belonging to this fresh PDST number.
        if (empty($active_settlement) && $requested_settlement_no === '') {
            $active_settlement = Settlement::where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->where(function ($query) use ($business_id) {
                    foreach ($this->getPdSettlementPrefixes((int) $business_id) as $prefix) {
                        $query->orWhere('settlement_no', 'LIKE', $prefix . '%');
                    }
                })
                ->where('status', 1)
                ->orderByDesc('id')
                ->first();
        }

        // IS1821: The selected business, shift and operator are the authoritative
        // source for this screen. A valid closed-pump sale may already carry a
        // provisional or historical settlement reference, so filtering by the
        // currently displayed settlement number can incorrectly hide it.
        $meter_sales = PumpOperatorMeterSale::with(['details', 'details.pump'])
            ->where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            ->where('pump_operator_id', $pump_operator_id)
            /*
             * MA-002: keep Direct Settlement's rows out of PD Settlement.
             *
             * This query fills the Meter Sales table on the PD Settlement screen. It
             * filtered only on business, shift and operator - so ANY sale taken
             * through the payments page during that shift appeared here too, on top
             * of the real closing reading.
             *
             * That is why the autoload has been unreliable: it goes wrong whenever
             * the operator took a payment mid-shift, which is most shifts.
             *
             * TWO CONDITIONS, AND DELIBERATELY NOT THREE:
             *
             *   p_o_payment_id IS NULL
             *       a sale already attached to a payment has been collected and
             *       settled on the Direct side. It must not be charged again here.
             *
             *   settlement_no is unset, or carries a PD prefix
             *       PDST belongs to PD Settlement, ST to Direct Settlement. An ST row
             *       is Direct's and is excluded outright.
             *
             * I DID NOT FILTER ON source, and that matters. A meter sale added by
             * hand in this screen's Manual Entry is saved with NO source at all - so
             * a source filter would have made every hand-added row vanish the moment
             * it was saved, breaking the tool the admin uses to correct settlements.
             * An unset settlement_no keeps those rows visible.
             */
            // MA-002: one shared rule - see scopeForPdSettlement on the model.
            ->forPdSettlement($this->getPdSettlementPrefixes((int) $business_id))
            // When the same sale exists both with and without its testing
            // quantity, retain the complete entered row.
            ->orderByRaw('CASE WHEN COALESCE(testing_qty, 0) > 0 THEN 0 ELSE 1 END')
            // Process the most recently added sale first. If an older save of
            // the same physical meter reading still exists, the latest added
            // detail is the one retained by the natural-key guard below.
            ->orderByDesc('id')
            ->get();

        $pumpIds = $meter_sales->flatMap(function ($sale) {
            return $sale->details->pluck('pump_id');
        })->filter()->unique()->values();

        $productIds = $meter_sales->flatMap(function ($sale) {
            return $sale->details->map(function ($detail) {
                return optional($detail->pump)->product_id;
            });
        })->filter()->unique()->values();

        $productsById = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $meterSales = MeterSale::where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            ->whereIn('pump_id', $pumpIds)
            ->orderByDesc('id')
            ->get()
            ->keyBy(function ($meterSale) {
                return implode('|', [
                    (int) $meterSale->pump_id,
                    number_format((float) $meterSale->starting_meter, 3, '.', ''),
                    number_format((float) $meterSale->closing_meter, 3, '.', ''),
                    number_format((float) $meterSale->qty, 3, '.', ''),
                ]);
            });

        // Load all testing quantities for the selected shift in one query.
        // The previous per-detail lookup (and save) caused an N+1 query delay
        // that became noticeable as the number of pumps increased.
        $testingQuantities = PumperDayEntry::join(
            'pump_operator_assignments as poa',
            'pumper_day_entries.pumper_assignment_id',
            '=',
            'poa.id'
        )
            ->where('pumper_day_entries.business_id', $business_id)
            ->where('pumper_day_entries.pump_operator_id', $pump_operator_id)
            ->where('poa.shift_id', $shift_id)
            ->whereIn('pumper_day_entries.pump_id', $pumpIds)
            ->select(
                'pumper_day_entries.pump_id',
                'pumper_day_entries.starting_meter',
                'pumper_day_entries.closing_meter',
                'pumper_day_entries.testing_ltr'
            )
            ->get()
            ->keyBy(function ($entry) {
                return implode('|', [
                    (int) $entry->pump_id,
                    number_format((float) $entry->starting_meter, 3, '.', ''),
                    number_format((float) $entry->closing_meter, 3, '.', ''),
                ]);
            });

        $rows = [];
        $seenRows = [];
        foreach ($meter_sales as $sale) {
            foreach ($sale->details as $detail) {
                $pump    = $detail->pump;
                $product = $pump ? $productsById->get($pump->product_id) : null;
                $testingQty = $sale->testing_qty ?? null;
                if ((float) ($testingQty ?? 0) == 0.0) {
                    $testingKey = implode('|', [
                        (int) $detail->pump_id,
                        number_format((float) $detail->received_meter, 3, '.', ''),
                        number_format((float) $detail->new_meter, 3, '.', ''),
                    ]);
                    $testingEntry = $testingQuantities->get($testingKey);
                    $testingQty = $testingEntry ? (float) $testingEntry->testing_ltr : 0;
                }
                $effectiveClosingMeter = (float) $detail->new_meter - (float) ($testingQty ?? 0);
                $rowKey = implode('|', [
                    $detail->pump_id,
                    number_format((float) $detail->received_meter, 2, '.', ''),
                    number_format($effectiveClosingMeter, 2, '.', ''),
                ]);

                if (isset($seenRows[$rowKey])) {
                    continue;
                }
                $seenRows[$rowKey] = true;

                $meterSaleKey = implode('|', [
                    (int) $detail->pump_id,
                    number_format((float) $detail->received_meter, 3, '.', ''),
                    number_format((float) $detail->new_meter, 3, '.', ''),
                    number_format((float) $detail->sold_qty, 3, '.', ''),
                ]);
                $meterSaleRow = $meterSales->get($meterSaleKey);
                $rows[]  = [
                    'id'              => $detail->id,
                    'sale_id'         => $sale->id,
                    'meter_sale_id'   => $meterSaleRow ? $meterSaleRow->id : null,
                    'form_load_id'    => $sale->id,
                    'pump_id'         => $detail->pump_id,
                    'sku'             => $product->sku ?? '-',
                    'product'         => $product->name ?? '-',
                    'pump'            => $pump->pump_no ?? '-',
                    'starting_meter'  => number_format($detail->received_meter, 2),
                    'closing_meter'   => number_format($detail->new_meter, 2),
                    'unit_price'      => number_format($detail->unit_price, 2),
                    'sold_qty'        => number_format($detail->sold_qty, 2),
                    'discount_type'   => '-',
                    'discount'        => '0.00',
                    'testing_qty'     => $testingQty ?? '0.00',
                    'total_qty'       => number_format((float) $detail->sold_qty + (float) ($testingQty ?? 0), 2),
                    'amount_raw'      => (float) $detail->amount,
                    'before_discount' => number_format($detail->amount, 2),
                    'after_discount'  => number_format($detail->amount, 2),
                ];
            }
        }

        $day_entries = collect();
        if (empty($rows)) {
            $day_entries = PumperDayEntry::leftJoin('pumps', 'pumper_day_entries.pump_id', '=', 'pumps.id')
                ->where('pumper_day_entries.business_id', $business_id)
                ->where('pumper_day_entries.pump_operator_id', $pump_operator_id)
                ->where(function ($query) use ($shift_id) {
                    $query->whereExists(function ($subQuery) use ($shift_id) {
                        $subQuery->select(DB::raw(1))
                            ->from('pump_operator_assignments')
                            ->whereColumn('pump_operator_assignments.id', 'pumper_day_entries.pumper_assignment_id')
                            ->where('pump_operator_assignments.shift_id', $shift_id);
                    });

                    if (Schema::hasColumn('pumper_day_entries', 'shift_id')) {
                        $query->orWhere('pumper_day_entries.shift_id', $shift_id);
                    }
                })
                ->select(
                    'pumper_day_entries.*',
                    'pumps.product_id',
                    'pumps.pump_no'
                )
                ->get();
        }

        $dayEntryProductsById = Product::whereIn('id', $day_entries->pluck('product_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        foreach ($day_entries as $entry) {
            $amount = (float) ($entry->amount ?? 0);
            $soldQty = (float) ($entry->sold_ltr ?? 0);
            $testingQty = (float) ($entry->testing_ltr ?? 0);
            if ($amount == 0.0 && $soldQty == 0.0 && $testingQty == 0.0) {
                continue;
            }

            $unitPrice = $soldQty > 0 ? $amount / $soldQty : 0;
            $rowKey = implode('|', [
                $entry->pump_id,
                number_format((float) ($entry->starting_meter ?? 0), 2, '.', ''),
                number_format(
                    (float) ($entry->closing_meter ?? 0) - $testingQty,
                    2,
                    '.',
                    ''
                ),
            ]);

            if (isset($seenRows[$rowKey])) {
                continue;
            }
            $seenRows[$rowKey] = true;

            $product = $dayEntryProductsById->get($entry->product_id);
            $rows[] = [
                'id'              => $entry->id,
                'sale_id'         => null,
                'meter_sale_id'   => null,
                'form_load_id'    => null,
                'pump_id'         => $entry->pump_id,
                'sku'             => $product->sku ?? '-',
                'product'         => $product->name ?? '-',
                'pump'            => $entry->pump_no ?? '-',
                'starting_meter'  => number_format((float) ($entry->starting_meter ?? 0), 2),
                'closing_meter'   => number_format((float) ($entry->closing_meter ?? 0), 2),
                'unit_price'      => number_format($unitPrice, 2),
                'sold_qty'        => number_format($soldQty, 2),
                'discount_type'   => '-',
                'discount'        => '0.00',
                'testing_qty'     => number_format($testingQty, 2),
                'total_qty'       => number_format($soldQty + $testingQty, 2),
                'amount_raw'      => $amount,
                'before_discount' => number_format($amount, 2),
                'after_discount'  => number_format($amount, 2),
            ];
        }

        return response()->json([
            'success' => true,
            'rows' => $rows,
        ]);
    }

    public function canEditSettlement($id)
    {
        $settlement = Settlement::findOrFail($id);

        $transaction = Transaction::where('invoice_no', $settlement->settlement_no)
            ->where('type', 'sell')
            ->first();

        $paid_customer_loan = Transaction::where('invoice_no', $settlement->settlement_no)
            ->where('sub_type', 'customer_loan')
            ->whereIn('payment_status', ['partial', 'paid'])
            ->count();

        $deposited_cheques = 0;
        if (! empty($transaction)) {
            $deposited_cheques = TransactionPayment::where('transaction_id', $transaction->id)
                ->where('method', 'cheque')
                ->where('is_deposited', 1)
                ->count();
        }

        $can_edit = 1;
        $reasons = '';

        if ($paid_customer_loan > 0 || $deposited_cheques > 0) {
            $can_edit = 0;
            $reasons .= '<ol>';
            if ($paid_customer_loan > 0) {
                $reasons .= '<li>'.__('petropd::lang.paid_customer_loan').'</li>';
            }
            if ($deposited_cheques > 0) {
                $reasons .= '<li>'.__('petropd::lang.deposited_cheque').'</li>';
            }
            $reasons .= '</ol>';
        }

        return [$can_edit, $reasons];
    }

    private function getPeriodBalancesForRange($business_id, $start_date, $end_date, $filters = [])
    {
        $locationKey = ! empty($filters['location_id']) ? $filters['location_id'] : 'all';
        $cacheKey    = implode('_', ['v2', $business_id, $start_date, $end_date, $locationKey]);
        
        if (! isset($this->periodBalanceCache[$cacheKey])) {
            $pump_operators_query = PumpOperator::where('business_id', $business_id);
            
            if (! empty($filters['location_id'])) {
                $pump_operators_query->where('location_id', $filters['location_id']);
            }
            
            $pump_operators = $pump_operators_query->pluck('id');
            
            $result = [];
            foreach ($pump_operators as $pump_operator_id) {
                $result[$pump_operator_id] = $this->transactionUtil->getPumpOperatorLedgerSummary(
                    $business_id,
                    $start_date,
                    $end_date,
                    $pump_operator_id,
                    $filters
                );
            }
            
            $this->periodBalanceCache[$cacheKey] = $result;
        }

        return $this->periodBalanceCache[$cacheKey];
    }

    public function setting_dash()
    {
        $card_types  = [];
        $business_id = Auth::user()->business_id;
        $card_group  = \App\AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (! empty($card_group)) {
            $card_types = \App\Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'name');

        $settings = PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();

        if (!empty($settings) && !empty($settings->dashboard_settings)) {
            $decodedSettings = json_decode($settings->dashboard_settings, true);
            
            if (!is_array($decodedSettings)) {
                $decodedSettings = [];
            }
            
            $defaults = [
                'credit_sales_direct_to_customer' => 'no',
                'show_bulk_pumps' => 'no',
                'meter_sales_compulsory' => 'no',
                'enter_cash_denominations' => 'no',
                'enter_card_numbers' => 'no',
                'card_amount_to_enter' => 'bulk',
                'logoff_time' => '',
                'logoff' => '',
                'bill_prefix' => '',
                'starting_bill_number' => '',
                'pumper_ledger_update' => 'no',
            ];
            
            $decodedSettings = array_merge($defaults, $decodedSettings);
            $settings->dashboard_settings = json_encode($decodedSettings);
        }

        return view('petropd::pd_operators.setting_dash')->with(compact(
            'card_types',
            'pump_operators',
            'business_id',
            'settings'
        ));
    }

    public function dashboard_settings()
    {
        if (! Auth::user()->can('pumper_dashboard_settings')) {
            abort(403, 'Unauthorized Access');
        }

        $business_id    = Auth::user()->business_id;
        $pump_operator  = PumpOperator::findOrFail(Auth::user()->pump_operator_id);
        $pump_operators = PumpOperator::where('business_id', $business_id)->pluck('name', 'name');

        $card_types = [];
        $card_group = \App\AccountGroup::where('business_id', $business_id)->where('name', 'Card')->first();
        if (! empty($card_group)) {
            $card_types = \App\Account::where('business_id', $business_id)->where('asset_type', $card_group->id)->where(DB::raw("REPLACE(`name`, '  ', ' ')"), '!=', 'Cards (Credit Debit) Account')->pluck('name', 'id');
        }

        return view('petropd::pd_operators.dashboard_settings')->with(compact(
            'business_id',
            'pump_operator',
            'card_types',
            'pump_operators'
        ));
    }

    public function store_settings(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'logoff' => 'required|in:yes,no',
                'logoff_time' => 'required_if:logoff,yes|nullable|date_format:H:i',
            ]);

            $data = $request->only(
                'created_at',
                'user_added',
                'show_bulk_pumps',
                'card_type',
                'credit_sales_direct_to_customer',
                'logoff_time',
                'logoff',
                'meter_sales_compulsory',
                'enter_cash_denominations',
                'card_amount_to_enter',
                'enter_card_numbers',
                'bill_prefix',
                'starting_bill_number',
                'pumper_ledger_update'
            );

            $data['logoff'] = in_array(strtolower((string) ($data['logoff'] ?? 'no')), ['yes', '1', 'true', 'on'], true) ? 'yes' : 'no';
            $data['logoff_time'] = $data['logoff'] === 'yes'
                ? substr((string) ($data['logoff_time'] ?? ''), 0, 5)
                : '';


            // IS1504: Normalize all dashboard setting values before saving.
            // This keeps explicit "No" selections from being read as the old default "Yes" behavior.
            $yesNoFields = [
                'show_bulk_pumps',
                'credit_sales_direct_to_customer',
                'meter_sales_compulsory',
                'enter_cash_denominations',
                'enter_card_numbers',
                'logoff',
                'pumper_ledger_update',
            ];

            foreach ($yesNoFields as $field) {
                $value = strtolower((string) ($data[$field] ?? 'no'));
                $data[$field] = in_array($value, ['yes', '1', 'true', 'on'], true) ? 'yes' : 'no';
            }

            if (($data['card_amount_to_enter'] ?? null) === 'individual') {
                $data['card_amount_to_enter'] = 'one_by_one';
            }
            $data['card_amount_to_enter'] = in_array(($data['card_amount_to_enter'] ?? 'bulk'), ['bulk', 'one_by_one'], true)
                ? $data['card_amount_to_enter']
                : 'bulk';

            $data['card_type'] = $data['card_type'] ?? '';
            $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
            $data['user_added'] = $data['user_added'] ?? (Auth::user()->username ?? Auth::user()->first_name ?? '');

            if ($request->is_admin == 1 || $request->input('apply_to_all_operators') == 1) {
                PumpOperator::where('business_id', Auth::user()->business_id)
                    ->update(['dashboard_settings' => json_encode($data)]);
            } else {
                if (Auth::user()->pump_operator_id != 0) {
                    $pump_operator = PumpOperator::findOrFail(Auth::user()->pump_operator_id);
                } else {
                    $pump_operator = PumpOperator::where('business_id', Auth::user()->business_id)
                        ->where('is_default', '1')
                        ->first() ?? PumpOperator::findOrFail(1);
                }
                $pump_operator->dashboard_settings = json_encode($data);
                $pump_operator->save();
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg'     => __('lang_v1.success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File: ' . $e->getFile() .
                ' Line: ' . $e->getLine() .
                ' Message: ' . $e->getMessage());

            DB::rollBack();

            $output = [
                'success' => 0,
                'msg'     => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with('status', $output);
    }
}

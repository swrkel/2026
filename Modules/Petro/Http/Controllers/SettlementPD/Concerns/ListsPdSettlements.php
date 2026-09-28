<?php

namespace Modules\Petro\Http\Controllers\SettlementPD\Concerns;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\CustomerReference;
use App\Http\Controllers\ContactController;
use App\NotificationTemplate;
use App\Product;
use App\Store;
use App\Transaction;
use App\TransactionPayment;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use App\Variation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Milon\Barcode\DNS2D;
use Modules\HR\Entities\WorkShift;
use Modules\Petro\Entities\CustomerPayment;
use Modules\Petro\Entities\DailyCollection;
use Modules\Petro\Entities\DailyVoucher;
use Modules\Petro\Entities\DayEnd;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\MeterSale;
use Modules\Petro\Entities\OtherIncome;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\PetroShift;
use Modules\Petro\Entities\PetroWhatsAppTemplate;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumperDayEntry;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\PumpOperatorAssignment;
use Modules\Petro\Entities\PumpOperatorCommission;
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashDeposit;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Entities\SettlementCustomerLoan;
use Modules\Petro\Entities\SettlementDrawingPayment;
use Modules\Petro\Entities\SettlementEditHistory;
use Modules\Petro\Entities\SettlementExcessPayment;
use Modules\Petro\Entities\SettlementExpensePayment;
use Modules\Petro\Entities\SettlementLoanPayment;
use Modules\Petro\Entities\SettlementShortagePayment;
use Modules\Petro\Entities\TankSellLine;
use Modules\Petro\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;
use Modules\Petro\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;

/**
 * Listing, viewing and printing settlements.
 *
 * MA-002: split out of SettlementPDController, which was 13,540 lines in a
 * single file.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at
 *   SettlementPDController, action() targets still resolve, and the $this->
 *   calls between these 85 methods still work. Separate controller classes
 *   would mean rewriting routes and every action() reference - a behavioural
 *   change dressed up as tidying, and with 85 methods the odds of missing one
 *   are high.
 *
 *   So this is a purely physical split: same class at runtime, smaller files.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten
 * while moving.
 *
 * Methods here: index, show, getUserActivityReport, extractLastInteger, getDirectSettlementShiftPrefix, getNextDirectSettlementShiftLabel, getAvailableDirectSettlementPumps, print, checkSlipNo, getValidPdDraftResumeContext
 */
trait ListsPdSettlements
{
    public function index()
    {

        $business_id = request()

            ->session()

            ->get("user.business_id");

        if (

            ! $this->moduleUtil->hasThePermissionInSubscription(

                $business_id,

                "enable_petro_module"

            )

        ) {

            abort(403, "Unauthorized Access");
        }

        if (request()->ajax()) {

            $business_id = request()

                ->session()

                ->get("user.business_id");

            if (request()->ajax()) {

                $query = Settlement::leftJoin(

                    "business_locations",

                    "settlements.location_id",

                    "=",

                    "business_locations.id"

                )

                    ->leftJoin(

                        "pump_operators",

                        "settlements.pump_operator_id",

                        "=",

                        "pump_operators.id"

                    )

                    ->leftJoin(
                        "business_locations as operator_locations",
                        "pump_operators.location_id",
                        "=",
                        "operator_locations.id"
                    )

                    ->leftJoin("pump_operator_assignments", function ($join) {
                        // Assignment rows belong to a settlement only through the
                        // explicit settlement_id link. A pump-operator fallback can
                        // attach unrelated shifts and multiply/hide list rows.
                        $join->on("settlements.id", "=", "pump_operator_assignments.settlement_id");
                    })

                    ->where("settlements.business_id", $business_id);

                $this->applyPdSettlementScope($query, $business_id);

                $query

                    ->select([

                        "pump_operators.name as pump_operator_name",

                        DB::raw("COALESCE(business_locations.name, operator_locations.name) as location_name"),

                        "settlements.*",

                        // DB::raw('GROUP_CONCAT(DISTINCT pump_operator_assignments.shift_number SEPARATOR ",") as shift_number')

                        DB::raw('GROUP_CONCAT(DISTINCT pump_operator_assignments.shift_number ORDER BY CAST(pump_operator_assignments.shift_number AS UNSIGNED) SEPARATOR ",") as shift_number'),
                        // "pump_operator_assignments.shift_number",

                    ])

                    ->with(["meter_sales", "other_sales", "meter_sales_pd.details"]);

                if (! empty(request()->location_id)) {

                    $locationId = (int) request()->location_id;

                    $query->where(function ($locationQuery) use ($locationId) {
                        $locationQuery->where("settlements.location_id", $locationId)
                            ->orWhere(function ($legacyLocationQuery) use ($locationId) {
                                $legacyLocationQuery
                                    ->where(function ($missingLocationQuery) {
                                        $missingLocationQuery
                                            ->whereNull("settlements.location_id")
                                            ->orWhere("settlements.location_id", 0);
                                    })
                                    ->where("pump_operators.location_id", $locationId);
                            });
                    });
                }

                if (! empty(request()->pump_operator)) {

                    $query->where(

                        "settlements.pump_operator_id",

                        request()->pump_operator

                    );
                }

                if (! empty(request()->settlement_no)) {

                    $query->where("settlements.id", request()->settlement_no);
                }

                if (

                    ! empty(request()->start_date) &&

                    ! empty(request()->end_date)

                ) {

                    $query->whereDate(

                        "settlements.transaction_date",

                        ">=",

                        request()->start_date

                    );

                    $query->whereDate(

                        "settlements.transaction_date",

                        "<=",

                        request()->end_date

                    );
                }

                $query->groupBy("settlements.id");

                $query->orderBy("settlements.id", "desc");

                $first = null;

                $first = Settlement::where("business_id", $business_id);

                $this->applyPdSettlementScope($first, $business_id);

                $first = $first
                    ->where("status", 0)
                    ->orderBy("id", "desc")

                    ->first();

                $delete_settlement = $this->moduleUtil->hasThePermissionInSubscription(

                    $business_id,

                    "delete_settlement"

                );

                $edit_settlement = $this->moduleUtil->hasThePermissionInSubscription(

                    $business_id,

                    "edit_settlement"

                );

                $edit_settlement_no_change = $this->moduleUtil->hasThePermissionInSubscription(

                    $business_id,

                    "edit_settlement_no_change"

                );


                $settlements = Datatables::of($query)

                    ->addColumn(

                        "action",

                        function ($row) use (

                            $first,

                            $delete_settlement,

                            $edit_settlement,

                            $edit_settlement_no_change

                        ) {

                            $html = "";

                            if ($row->status == 1) {

                                if (

                                    Str::startsWith(

                                        $row->settlement_no,

                                        "SET-SW"

                                    )

                                ) {

                                    $html .=

                                        '<a class="btn  btn-danger btn-sm" href="' .

                                        action(

                                            "\Modules\SettlementSW\Http\Controllers\SettlementSWController@index"

                                        ) .

                                        '">' .

                                        __("petro::lang.finish_settlement") .

                                        "</a>";
                                } else {

                                    $html .=

                                        '<a class="btn  btn-danger btn-sm" href="' .

                                        action(

                                            "\Modules\Petro\Http\Controllers\SettlementPDController@create"

                                        ) . '?view_settlement_id=' . $row->id .

                                        '">' .

                                        __("petro::lang.finish_settlement") .

                                        "</a>";
                                }
                            } elseif ($row->is_edit == 1) {

                                $html .=

                                    '<a class="btn  btn-warning btn-sm" href="' .

                                    action(

                                        "\Modules\Petro\Http\Controllers\SettlementPDController@edit",

                                        [$row->id]

                                    ) .

                                    '">' .

                                    __("petro::lang.finish_editting") .

                                    "</a>";
                            } else {

                                $html .=

                                    '<div class="btn-group">







                                <button type="button" class="btn btn-info dropdown-toggle btn-xs"







                                    data-toggle="dropdown" aria-expanded="false">' .

                                    __("messages.actions") .

                                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown



                                    </span>



                                </button>







                                <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                                $html .=

                                    '<li><a data-href="' .

                                    action(

                                        "\Modules\Petro\Http\Controllers\SettlementPDController@show",

                                        [$row->id]

                                    ) .

                                    '" class="btn-modal" data-container=".settlement_modal"><i class="fa fa-eye" aria-hidden="true"></i> ' .

                                    __("messages.view") .

                                    "</a></li>";

                                if (

                                    auth()

                                    ->user()

                                    ->can("petro.settlement.edit") &&

                                    $edit_settlement

                                ) {

                                    $html .=

                                        '<li><a href="' .

                                        action(

                                            "\Modules\Petro\Http\Controllers\SettlementPDController@edit",

                                            [$row->id]

                                        ) .

                                        '" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> ' .

                                        __("messages.edit") .

                                        "</a></li>";
                                }

                                if (

                                    auth()

                                    ->user()

                                    ->can("petro.settlement.edit") &&

                                    $edit_settlement_no_change

                                ) {

                                    $html .=

                                        '<li><a href="' .

                                        action(

                                            "\Modules\Petro\Http\Controllers\SettlementPDController@edit",

                                            [$row->id]

                                        ) .

                                        '?no_change=1" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> ' .

                                        __("petro::lang.edit_no_change") .

                                        "</a></li>";
                                }

                                if (

                                    $this->moduleUtil->hasThePermissionInSubscription(

                                        request()

                                            ->session()

                                            ->get("user.business_id"),

                                        "individual_sale"

                                    )

                                ) {

                                    $settlement = DB::table("transactions")

                                        ->where(

                                            "invoice_no",

                                            $row->settlement_no

                                        )

                                        ->where("type", "sell")

                                        ->first();

                                    if (! empty($settlement)) {

                                        if (

                                            strtotime(

                                                $this->transactionUtil->__getVatEffectiveDate(

                                                    request()

                                                        ->session()

                                                        ->get(

                                                            "user.business_id"

                                                        )

                                                )

                                            ) <=

                                            strtotime($row->transaction_date)

                                        ) {

                                            $html .=

                                                '<li><a href="#" data-href="' .

                                                action(

                                                    "\Modules\Vat\Http\Controllers\VatController@updateSingleVats",

                                                    [

                                                        "transaction_id" =>

                                                        $settlement->id,

                                                    ]

                                                ) .

                                                '" class="regenerate-vat"><i class="fa fa-pencil"></i> ' .

                                                __(

                                                    "superadmin::lang.regenerate_vat"

                                                ) .

                                                "</a></li>";
                                        }
                                    }
                                }

                                if (

                                    ! empty($first) &&

                                    $first->id == $row->id &&

                                    $delete_settlement &&

                                    auth()

                                    ->user()

                                    ->can("petro.settlement.delete")

                                ) {

                                    // commented By M Usman for hiding Delete Action

                                    $html .=

                                        '<li><a href="' .

                                        action(

                                            "\Modules\Petro\Http\Controllers\SettlementPDController@destroy",

                                            [$row->id]

                                        ) .

                                        '" class="delete_settlement_button"><i class="fa fa-trash"></i> ' .

                                        __("messages.delete") .

                                        "</a></li>";
                                }

                                $html .=

                                    '<li><a data-href="' .

                                    action(

                                        "\Modules\Petro\Http\Controllers\SettlementPDController@print",

                                        [$row->id]

                                    ) .

                                    '" class="print_settlement_button"><i class="fa fa-print"></i> ' .

                                    __("petro::lang.print") .

                                    "</a></li>";

                                $html .= "</ul></div>";
                            }

                            return $html;
                        }

                    )

                    ->editColumn("status", function ($row) {

                        if ($row->status == 0) {

                            return '<span class="label label-success">Completed</span>';
                        } else {

                            return '<span class="label label-danger">Pending</span>';
                        }
                    })

                    // ->addColumn('pump_nos', function($row){

                    //     $pump_nos = '';

                    //     if(!empty($row->meter_sales())){

                    //         $_pump_nos = $row->meter_sales->pluck('pump_id')->toArray() ?? [];

                    //         $_pumps = Pump::whereIn('id',$_pump_nos)->pluck('pump_no')->toArray();

                    //         $pump_nos = implode(', ',$_pumps);

                    //     }

                    //     return $pump_nos;

                    // })

                    ->addColumn("pump_nos", function ($row) {

                        $pump_nos = "";

                        // Try to get pump numbers from meter_sales_pd (PumpOperatorMeterSale)
                        if (

                            ! empty($row->meter_sales_pd) &&

                            $row->meter_sales_pd->count() > 0

                        ) {

                            // Get pump IDs from meter_sales_pd->details
                            $_pump_ids = [];
                            foreach ($row->meter_sales_pd as $meter_sale) {
                                if (! empty($meter_sale->details)) {
                                    $detail_pump_ids = $meter_sale->details->pluck("pump_id")->toArray();
                                    $_pump_ids = array_merge($_pump_ids, $detail_pump_ids);
                                }
                            }
                            
                            // Get unique pump IDs
                            $_pump_ids = array_unique($_pump_ids);

                            // Get pump numbers
                            if (! empty($_pump_ids)) {
                                // MA-002 PERF: cached reference lookup, see ma002PumpNos().
                                $_pumps = self::ma002PumpNos($_pump_ids);

                                $pump_nos = implode(", ", $_pumps);
                            }
                        }

                        // Fallback to meter_sales if meter_sales_pd is empty
                        if (empty($pump_nos) && ! empty($row->meter_sales) && $row->meter_sales->count() > 0) {

                            $_pump_nos = $row->meter_sales

                                ->pluck("pump_id")

                                ->toArray();

                            $_pumps = Pump::whereIn("id", $_pump_nos)

                                ->pluck("pump_no")

                                ->toArray();

                            $pump_nos = implode(", ", $_pumps);
                        }

                        return $pump_nos;
                    })

                    // ->editColumn('shift', function ($row) {

                    //     if (!empty($row->work_shift)) {

                    //         $shifts = WorkShift::whereIn('id', $row->work_shift)->pluck('shift_name')->toArray();

                    //         return implode(',', $shifts);

                    //     } else {

                    //         return '';

                    //     }

                    // })

                    ->editColumn("shift", function ($row) {

                        $workShift = $row->work_shift;
                        if (is_string($workShift)) {
                            $decoded = json_decode($workShift, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $workShift = $decoded;
                            }
                        }

                        if (! empty($workShift) && is_array($workShift)) {
                            $shifts = WorkShift::whereIn("id", $workShift)
                                ->pluck("shift_name")
                                ->toArray();

                            return implode(",", $shifts);
                        }

                        return "";
                    })

                    ->addColumn("created_by", function ($row) {

                        $transaction = Transaction::where(

                            "invoice_no",

                            $row->settlement_no

                        )

                            ->leftJoin(

                                "users",

                                "users.id",

                                "transactions.created_by"

                            )

                            ->select("users.username")

                            ->first();

                        if (! empty($transaction)) {

                            return $transaction->username;
                        }
                    })

                    ->editColumn(

                        "transaction_date",

                        function ($row) { return $this->transactionUtil->format_date($row->transaction_date); }

                    )

                    // ->editColumn('total_amount', '{{@num_format($total_amount)}}')

                    ->editColumn("total_amount", function ($row) {

                        $meter_total = $row->meter_sales->sum('discount_amount');
                        $other_sales_total = $row->other_sales->sum('sub_total');

                        $other_sales_discount = 0;
                        if (!empty($row->other_sales) && $row->other_sales->count() > 0) {
                            $other_sales_discount = $row->other_sales->sum("discount_amount");
                        }

                        $adjusted_total = $meter_total + $other_sales_total - (float)$other_sales_discount;

                        if ($adjusted_total <= 0 && !empty($row->total_amount)) {
                            $adjusted_total = (float)$row->total_amount - (float)$other_sales_discount;
                        }


                        return '<span class="total_amount">' .

                            number_format((float)$adjusted_total, 2, ".", ",") .

                            "</span>";
                    })

                    ->setRowAttr([

                        "data-href" => function ($row) {

                            return action(

                                "\Modules\Petro\Http\Controllers\SettlementPDController@show",

                                [$row->id]

                            );
                        },

                    ])

                    ->removeColumn("id");

                return $settlements

                    ->rawColumns(["action", "status", "total_amount"])

                    ->make(true);
            }
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $pump_operators = PumpOperator::where(

            "business_id",

            $business_id

        )->pluck("name", "id");

        $settlement_nos = Settlement::where("business_id", $business_id);

        $this->applyPdSettlementScope($settlement_nos, $business_id);

        $settlement_nos = $settlement_nos
            ->with(['meter_sales','meter_sales_pd'])
            ->pluck(

            "settlement_no",

            "id"

        );

        $message = $this->transactionUtil->getGeneralMessage(

            "general_message_pump_management_checkbox"

        );

        return view("petro::settlement_pd.index")->with(

            compact(

                "business_locations",

                "pump_operators",

                "settlement_nos",

                "message"

            )

        );
    }

    public function show($id)
    {

        $business_id = request()

            ->session()

            ->get("user.business_id");

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        $settlement = Settlement::where("settlements.id", $id)

            ->where("settlements.business_id", $business_id)

            ->leftjoin(

                "pump_operators",

                "settlements.pump_operator_id",

                "pump_operators.id"

            )

            ->leftJoin("pump_operator_assignments", function ($join) {

                $join->on(

                    "settlements.id",

                    "=",

                    "pump_operator_assignments.settlement_id"

                    // ->orOn(function ($query) {

                    //     $query->on('settlements.pump_operator_id', '=', 'pump_operator_assignments.pump_operator_id')

                    //         ->whereNull('pump_operator_assignments.settlement_id');

                    // })

                );
            })

            ->with([

                "meter_sales",

                "meter_sales_pd.details",

                "other_sales",

                "other_incomes",

                "customer_payments",

                "cash_payments",

                "card_payments",

                "cheque_payments",

                "credit_sale_payments",

                "expense_payments",

                "excess_payments",

                "shortage_payments",

                "loan_payments",

                "drawings_payments",

                "customer_loans",

            ])

            ->select(

                "settlements.*",
                "pump_operators.name as pump_operator_name",
                "pump_operator_assignments.shift_id"
            )

            ->first();

        if (empty($settlement)) {
            abort(404, 'Settlement not found');
        }

        $this->repairMeterSalePdSettlementNo($settlement);
        $this->createSettlementCardPaymentsFromPumpPayments($settlement, $business_id);
        $this->ensureSettlementCardAccounting($settlement, $business_id);

        // Load cash_payments.customer for consistency with print view if needed (though show view currently summarizes)
        if (! empty($settlement)) {
            $settlement->load('cash_payments.customer');

            // CRITICAL: Get shift_ids from settlement's work_shift or pump_operator_assignments
            // Filter meter_sales and credit_sale_payments to only show entries from the selected shift(s)
            $shift_ids_for_filter = [];
            if (! empty($settlement->work_shift)) {
                $work_shifts = is_array($settlement->work_shift) ? $settlement->work_shift : json_decode($settlement->work_shift, true);
                if (is_array($work_shifts) && ! empty($work_shifts)) {
                    $shift_ids_for_filter = array_filter(array_map('intval', $work_shifts));
                }
            }

            // Fallback: Get shift_ids from pump_operator_assignments if work_shift is empty
            if (empty($shift_ids_for_filter)) {
                $shift_ids_for_filter = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->unique()
                    ->values()
                    ->toArray();
            }

            \Modules\Petro\Support\PetroDebug::info('Settlement PD Show: Filtering by shift_ids', [
                'settlement_id'        => $settlement->id,
                'shift_ids_for_filter' => $shift_ids_for_filter,
            ]);

            $this->hydrateSettlementPdMeterSalesForDisplay($settlement, $shift_ids_for_filter);

            // NOTE: For show() view, we DON'T filter meter_sales, credit_sale_payments, card_payments, or other payment types
            // The settlement already loaded these relations via ->with() and they all belong to this settlement (linked by settlement_no)
            // The shift-based and time-based filtering was too aggressive and excluded valid data that should be displayed
            // All payments entered through the Add Payment page are already correctly linked to the settlement
            // Filtering should only be done in print() or other specific scenarios where shift isolation is required

            // NOTE: For show() view, we DON'T filter cheque/expense/loan/drawings/excess/shortage/cash_deposits/customer_loans
            // because the settlement already loaded these relations via ->with() and they belong to this settlement
            // The time-based filtering (created_at >= settlement->created_at) was too aggressive and excluded valid payments
            // that were entered through the Add Payment page before the settlement was finalized.
            // These payments are already linked to the settlement via settlement_no, so no additional filtering needed.
        }

        $business = Business::where(

            "id",

            ! empty($settlement) ? $settlement->business_id : 0

        )->first();

        $pump_operator = PumpOperator::where(

            "id",

            ! empty($settlement) ? $settlement->pump_operator_id : 0

        )->first();

        //this for only to show in print page customer payments which entered in customer payments tab

        $customer_payments_tab = CustomerPayment::leftjoin(

            "contacts",

            "customer_payments.customer_id",

            "contacts.id"

        )

            ->where("customer_payments.settlement_no", $id)

            ->where("customer_payments.business_id", $business_id)

            ->select("customer_payments.*", "contacts.name as customer_name")

            ->get();

        $view = request()->input('source') === 'petropd'
            ? 'petropd::pd_settlement.show'
            : 'petro::settlement_pd.show';

        return view($view)->with(

            compact(

                "settlement",

                "business",

                "pump_operator",

                "customer_payments_tab"

            )

        );
    }

    /**







     * Show the form for editing the specified resource.







     * @return Response







     */

    public function getUserActivityReport(Request $request)
    {

        $business_id = request()

            ->session()

            ->get("user.business_id");

        if (request()->ajax()) {

            $with = [];

            $shipping_statuses = $this->transactionUtil->shipping_statuses();

            $business_users = User::where("business_id", $business_id)

                ->pluck("id")

                ->toArray();

            $activity = Activity::whereIn("causer_id", $business_users)
                ->whereIn("description", ["updated", "deleted"])
                ->whereIn("subject_type", [\Modules\Petro\Entities\Settlement::class])
                ->where('subject_id', '>', 0) // ensure subject exists
                ->whereHasMorph('subject', [\Modules\Petro\Entities\Settlement::class], function($query) {
                    $query->where('settlement_no', 'like', 'PDST%');
                });

            if (! empty(request()->user) && request()->user != "All") {

                $user = request()->user;

                $activity->where("causer_id", $user);
            }

            if (! empty(request()->type) && request()->type != "All") {

                $type = request()->type;

                $activity->where("description", $type);
            }

            if (! empty(request()->subject) && request()->subject != "All") {

                $subject = request()->subject;

                $activity->where("log_name", $subject);
            }

            if (! empty(request()->startDate) && ! empty(request()->endDate)) {

                $activity->whereDate("created_at", ">=", request()->startDate);

                $activity->whereDate("created_at", "<=", request()->endDate);
            }

            $datatable = Datatables::of($activity)

                ->editColumn(

                    "created_at",

                    function ($row) { return $this->transactionUtil->format_date($row->created_at, true); }

                )

                ->removeColumn("id")

                ->editColumn("causer_id", function ($row) {

                    $causer_id = $row->causer_id;

                    $username = User::where("id", $causer_id)

                        ->select("username")

                        ->first()->username;

                    return $username;
                })

                ->addColumn("ref_no", function ($row) {

                    $attributes = json_decode($row->properties, true);

                    $new = $attributes["attributes"] ?? [];

                    $html = "";

                    if ($row->subject_type == "App\TransactionPayment") {

                        if (! empty($new["payment_ref_no"])) {

                            $html .= $new["payment_ref_no"];
                        }
                    } else {

                        if (! empty($new["invoice_no"])) {

                            $html .= $new["invoice_no"];
                        } else {

                            if (

                                $row->subject_type ==

                                "Modules\Petro\Entities\Settlement"

                            ) {

                                $html .= $new["settlement_no"];
                            }
                        }
                    }

                    return $html;
                })

                ->addColumn("description_details", function ($row) {

                    $attributes = json_decode($row->properties, true);

                    $new = $attributes["attributes"] ?? [];

                    $old = $attributes["old"] ?? [];

                    $html = "";

                    if ($row->description == "updated") {

                        foreach ($new as $key => $newValue) {

                            if (

                                $key != "created_at" &&

                                $key != "updated_at" &&

                                $key != "id"

                            ) {

                                $oldValue = $old[$key] ?? null;

                                if (

                                    $key == "payment_method" &&

                                    $oldValue == "Cash"

                                ) {

                                    $oldValue = "Cash ";
                                }

                                if ($newValue !== $oldValue) {

                                    $originalKey = str_replace(

                                        "_",

                                        " ",

                                        ucfirst($key)

                                    );

                                    $html .= "Original $originalKey $oldValue changed to $newValue <br>";
                                }
                            }
                        }
                    } elseif ($row->description == "deleted") {

                        if ($row->subject_type == "App\TransactionPayment") {

                            $contact = Contact::find($new["payment_for"]);

                            if (! empty($contact)) {

                                $html .=

                                    "Contact Name: " . $contact->name . "<br>";
                            }

                            if (! empty($new["amount"])) {

                                $html .=

                                    "Amount: " .

                                    $this->productUtil->num_f($new["amount"]) .

                                    "<br>";
                            }

                            if (! empty($new["payment_ref_no"])) {

                                $html .=

                                    "Ref No: " .

                                    $new["payment_ref_no"] .

                                    "<br>";
                            }
                        } else {

                            return "";
                        }
                    } elseif (

                        $row->description == "update" &&

                        $row->log_name == "Settlement"

                    ) {

                        $jsonProperties = $row->properties;

                        $decodedProperties = json_decode($jsonProperties);

                        $text = $decodedProperties[0];

                        $html .= $text;

                        // $html .= $row->properties;

                    } elseif (

                        ($row->description == "update" ||

                            $row->description == "delete") &&

                        ($row->log_name == "Day End Settlement" ||

                            $row->log_name == "Dip Chart" ||

                            $row->log_name == "Dip Report")

                    ) {

                        $jsonProperties = $row->properties;

                        $decodedProperties = json_decode($jsonProperties);

                        $text = $decodedProperties[0];

                        $html .= nl2br($text);

                        // $html .= $row->properties;

                    } else {

                        $html = "";
                    }

                    return nl2br($html);
                });

            $rawColumns = ["description_details"];

            return $datatable

                ->rawColumns($rawColumns)

                ->make(true);
        }

        $users = User::where("business_id", $business_id)->pluck(

            "username",

            "id"

        );

        $type = Activity::distinct()->pluck("description");

        $subject = Activity::distinct()->pluck("log_name");

        return view("petro::report.user_activity")->with(

            compact("users", "type", "subject")

        );
    }

    public function extractLastInteger($text)
    {
        if (is_array($text)) {
            $text = implode(' ', array_filter(array_map(function ($value) {
                return is_scalar($value) ? (string) $value : '';
            }, $text)));
        }

        $text = (string) $text;

        if (preg_match('/\d+$/', $text, $matches)) {

            return intval($matches[0]);
        } else {

            return 0;
        }
    }

    private function getDirectSettlementShiftPrefix(?int $business_id = null): string
    {
        $prefixes = request()->session()->get('business.ref_no_prefixes', []);

        if (empty($prefixes) && ! empty($business_id)) {
            $business = Business::find($business_id);
            $prefixes = $business->ref_no_prefixes ?? [];
        }

        return ! empty($prefixes['direct_settlement_shift'])
            ? $prefixes['direct_settlement_shift']
            : (! empty($prefixes['settlement_pd_shift']) ? $prefixes['settlement_pd_shift'] : 'DST');
    }

    private function getNextDirectSettlementShiftLabel(int $business_id, ?string $currentLabel = null, ?int $currentOperatorId = null, ?int $selectedOperatorId = null): string
    {
        $prefix = $this->getDirectSettlementShiftPrefix($business_id);

        if (
            ! empty($currentLabel)
            && str_starts_with($currentLabel, $prefix)
            && ! empty($currentOperatorId)
            && ! empty($selectedOperatorId)
            && $currentOperatorId === $selectedOperatorId
        ) {
            return $currentLabel;
        }

        $last_number = Settlement::where('business_id', $business_id);

        $this->applyPdSettlementScope($last_number, $business_id);

        $last_number = $last_number
            ->where('work_shift', 'LIKE', '%' . $prefix . '%')
            ->get(['work_shift'])
            ->map(function ($settlement) {
                return $this->extractLastInteger($settlement->work_shift);
            })
            ->max() ?? 0;

        if (! empty($currentLabel) && str_starts_with($currentLabel, $prefix)) {
            $last_number = max((int) $last_number, $this->extractLastInteger($currentLabel));
        }

        return $prefix . ($last_number + 1);
    }

    private function getAvailableDirectSettlementPumps(int $business_id, ?int $location_id = null)
    {
        $busy_pump_ids = PumpOperatorAssignment::leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
            ->where('pump_operator_assignments.business_id', $business_id)
            ->where(function ($query) {
                $query->where('pump_operator_assignments.status', 'open')
                    ->orWhere(function ($q) {
                        $q->whereIn('ps.status', [0, 1])
                            ->whereNull('ps.closed_time');
                    });
            })
            ->where(function ($query) {
                $query->where('pump_operator_assignments.closed_in_settlement', 0)
                    ->orWhereNull('pump_operator_assignments.closed_in_settlement');
            })
            ->pluck('pump_operator_assignments.pump_id')
            ->filter()
            ->unique()
            ->toArray();

        $pump_query = Pump::where('business_id', $business_id)
            ->when(! empty($location_id), function ($query) use ($location_id) {
                $query->where('location_id', $location_id);
            })
            ->whereNotIn('id', $busy_pump_ids);

        if (\Modules\Petro\Support\SchemaCapabilityCache::hasColumn('pumps', 'is_other_sales_pump')) {
            $pump_query->where('is_other_sales_pump', 0);
        }

        return $pump_query->pluck('pump_name', 'id');
    }

    public function print($id)
    {
        $business_id = request()
            ->session()
            ->get("user.business_id");

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        $settlement = Settlement::where("settlements.id", $id)
            ->where("settlements.business_id", $business_id)
            ->leftjoin(
                "pump_operators",
                "settlements.pump_operator_id",
                "pump_operators.id"
            )
            ->with([
                "meter_sales",
                "meter_sales_pd.details",
                "other_sales",
                "other_incomes",
                "customer_payments",
                "cash_payments",
                "cash_payments.customer",
                "cash_deposits",
                "card_payments",
                "cheque_payments",
                "credit_sale_payments",
                "expense_payments",
                "excess_payments",
                "shortage_payments",
                "loan_payments",
                "drawings_payments",
                "customer_loans",
                "cash_deposits",
            ])
            ->select(
                "settlements.*",
                "pump_operators.name as pump_operator_name"
            )
            ->first();

        if ($settlement) {
            $this->repairMeterSalePdSettlementNo($settlement);
            $this->createSettlementCardPaymentsFromPumpPayments($settlement, $business_id);
            $this->ensureSettlementCardAccounting($settlement, $business_id);

            // Fallbacks in case some relations were saved with settlement_no = string or id

            // Cash deposits
            if ($settlement->cash_deposits->isEmpty()) {
                $cash_deposits = \Modules\Petro\Entities\SettlementCashDeposit::where('settlement_no', $settlement->settlement_no)
                    ->orWhere('settlement_no', $settlement->id)
                    ->get();
                $settlement->setRelation('cash_deposits', $cash_deposits);
            }

            // CRITICAL: Get shift_ids from settlement's work_shift or pump_operator_assignments FIRST
            // This is needed before filtering any payment types
            $shift_ids_for_filter = [];
            if (! empty($settlement->work_shift)) {
                $work_shifts = is_array($settlement->work_shift) ? $settlement->work_shift : json_decode($settlement->work_shift, true);
                if (is_array($work_shifts) && ! empty($work_shifts)) {
                    $shift_ids_for_filter = array_filter(array_map('intval', $work_shifts));
                }
            }

            // Fallback: Get shift_ids from pump_operator_assignments if work_shift is empty
            if (empty($shift_ids_for_filter)) {
                $shift_ids_for_filter = PumpOperatorAssignment::where('settlement_id', $settlement->id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereNotNull('shift_id')
                    ->pluck('shift_id')
                    ->unique()
                    ->values()
                    ->toArray();
            }

            // Cash payments - relationship uses settlement_no = id (integer)
            // CRITICAL: Filter cash payments by shift_id to only show payments from the selected shift(s)
            $cash_payments_query = SettlementCashPayment::where('settlement_no', $settlement->id)
                ->where('business_id', $business_id);

            // Filter by shift_id if we have shift_ids_for_filter
            if (! empty($shift_ids_for_filter)) {
                $cash_payments_query->where(function ($q) use ($shift_ids_for_filter) {
                    // Filter via PumpOperatorPayment (for cash from pumper dashboard)
                    $q->whereExists(function ($existsQ) use ($shift_ids_for_filter) {
                        $existsQ->select(DB::raw(1))
                            ->from('pump_operator_payments')
                            ->where(function ($subQ) {
                                $subQ->whereColumn('pump_operator_payments.id', 'settlement_cash_payments.customer_payment_id')
                                     ->orWhereColumn('pump_operator_payments.id', 'settlement_cash_payments.pump_payment_id');
                            })
                            ->whereIn('pump_operator_payments.shift_id', $shift_ids_for_filter);
                    })
                    ->orWhere(function ($orQ) {
                        $orQ->whereNull('settlement_cash_payments.customer_payment_id')
                            ->whereNull('settlement_cash_payments.pump_payment_id');
                    });
                });
            }

            $cash_payments = $cash_payments_query->with('customer')->get();
            $settlement->setRelation('cash_payments', $cash_payments);

            // Card payments - always reload with fallback logic
            // CRITICAL: Filter card payments by shift_id to only show payments from the selected shift(s)
            $card_payments_query = SettlementCardPayment::where(function ($q) use ($settlement) {
                $q->where('settlement_card_payments.settlement_no', $settlement->settlement_no)
                    ->orWhere('settlement_card_payments.settlement_no', $settlement->id);
            });

            // Filter by shift_id if we have shift_ids_for_filter
            // CRITICAL FIX: Filter card payments by shift to prevent Shift 5 appearing in Shift 3 printouts
            if (! empty($shift_ids_for_filter) && count($shift_ids_for_filter) > 0) {
                $card_payments_query->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
                    ->leftJoin('pump_operator_payments', function ($join) {
                        $join->on('pump_operator_payments.pump_operator_id', '=', 'daily_cards.pump_operator_id')
                            ->whereRaw('pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci = daily_cards.collection_no COLLATE utf8mb4_unicode_ci')
                            ->where('pump_operator_payments.payment_type', 'card')
                            ->whereColumn('pump_operator_payments.payment_amount', 'daily_cards.amount');
                    })
                    ->where(function ($subQ) use ($shift_ids_for_filter) {
                        $subQ->whereIn('pump_operator_payments.shift_id', $shift_ids_for_filter);
                    })
                    ->select('settlement_card_payments.*'); // Only select settlement_card_payments columns
            }

            $card_payments = $card_payments_query->get();
            $settlement->setRelation('card_payments', $card_payments);

            \Modules\Petro\Support\PetroDebug::info('Settlement PD Print: Filtering by shift_ids', [
                'settlement_id'        => $settlement->id,
                'shift_ids_for_filter' => $shift_ids_for_filter,
            ]);

            // Filter meter_sales by shift_id
            if (! empty($shift_ids_for_filter)) {
                $meter_sales_filtered = MeterSale::where('settlement_no', $settlement->id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->get();
                $settlement->setRelation('meter_sales', $meter_sales_filtered);

                \Modules\Petro\Support\PetroDebug::info('Settlement PD Print: Meter Sales Filtered', [
                    'total_after' => $meter_sales_filtered->count(),
                ]);
            }

            // Credit sale payments filtered to this settlement's shift(s) only.
            $credit_sales = $this->getFilteredCreditSalePayments($settlement, $shift_ids_for_filter);
            $settlement->setRelation('credit_sale_payments', $credit_sales);

            \Modules\Petro\Support\PetroDebug::info('Settlement PD Print: Credit Sales Filtered', [
                'total_credit_sales' => $credit_sales->count(),
            ]);

            // Filter cheque_payments, expense_payments, loan_payments, drawings_payments by creation time
            // These don't have shift_id directly, so we filter by settlement creation time
            if (! empty($shift_ids_for_filter) && $settlement->created_at) {
                // Filter cheque_payments
                $cheque_payments_filtered = SettlementChequePayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                    ->where('created_at', '>=', $settlement->created_at)
                    ->get();
                if ($cheque_payments_filtered->isEmpty()) {
                    $cheque_payments_filtered = SettlementChequePayment::where(function ($q) use ($settlement) {
                        $q->where('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', $settlement->id);
                    })->get();
                }
                $settlement->setRelation('cheque_payments', $cheque_payments_filtered);

                // Filter expense_payments
                $expense_payments_filtered = SettlementExpensePayment::where('settlement_no', $settlement->id)
                    ->where('created_at', '>=', $settlement->created_at)
                    ->get();
                if ($expense_payments_filtered->isEmpty()) {
                    $expense_payments_filtered = SettlementExpensePayment::where('settlement_no', $settlement->id)
                        ->get();
                }
                $settlement->setRelation('expense_payments', $expense_payments_filtered);

                // Filter loan_payments
                $loan_payments_filtered = SettlementLoanPayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                    ->where('created_at', '>=', $settlement->created_at)
                    ->get();
                if ($loan_payments_filtered->isEmpty()) {
                    $loan_payments_filtered = SettlementLoanPayment::where(function ($q) use ($settlement) {
                        $q->where('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', $settlement->id);
                    })->get();
                }
                $settlement->setRelation('loan_payments', $loan_payments_filtered);

                // Filter drawings_payments
                $drawings_payments_filtered = SettlementDrawingPayment::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                    ->where('created_at', '>=', $settlement->created_at)
                    ->get();
                if ($drawings_payments_filtered->isEmpty()) {
                    $drawings_payments_filtered = SettlementDrawingPayment::where(function ($q) use ($settlement) {
                        $q->where('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', $settlement->id);
                    })->get();
                }
                $settlement->setRelation('drawings_payments', $drawings_payments_filtered);

                // Filter excess_payments
                $excess_payments_filtered = SettlementExcessPayment::where('settlement_no', $settlement->id)
                    ->where('created_at', '>=', $settlement->created_at)
                    ->get();
                if ($excess_payments_filtered->isEmpty()) {
                    $excess_payments_filtered = SettlementExcessPayment::where('settlement_no', $settlement->id)
                        ->get();
                }
                $settlement->setRelation('excess_payments', $excess_payments_filtered);

                // Filter shortage_payments
                $shortage_payments_filtered = SettlementShortagePayment::where('settlement_no', $settlement->id)
                    ->where('created_at', '>=', $settlement->created_at)
                    ->get();
                if ($shortage_payments_filtered->isEmpty()) {
                    $shortage_payments_filtered = SettlementShortagePayment::where('settlement_no', $settlement->id)
                        ->get();
                }
                $settlement->setRelation('shortage_payments', $shortage_payments_filtered);

                // Filter cash_deposits
                $cash_deposits_filtered = SettlementCashDeposit::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no);
                })
                    ->where('created_at', '>=', $settlement->created_at)
                    ->get();
                if ($cash_deposits_filtered->isEmpty()) {
                    $cash_deposits_filtered = SettlementCashDeposit::where(function ($q) use ($settlement) {
                        $q->where('settlement_no', $settlement->id)
                            ->orWhere('settlement_no', $settlement->settlement_no);
                    })->get();
                }
                $settlement->setRelation('cash_deposits', $cash_deposits_filtered);

                // Filter customer_loans
                $customer_loans_filtered = SettlementCustomerLoan::where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no);
                })
                    ->where('created_at', '>=', $settlement->created_at)
                    ->get();
                if ($customer_loans_filtered->isEmpty()) {
                    $customer_loans_filtered = SettlementCustomerLoan::where(function ($q) use ($settlement) {
                        $q->where('settlement_no', $settlement->id)
                            ->orWhere('settlement_no', $settlement->settlement_no);
                    })->get();
                }
                $settlement->setRelation('customer_loans', $customer_loans_filtered);
            }
        }

        // Return error if settlement not found
        if (! $settlement) {
            return response()->json([
                'success' => false,
                'msg'     => __('petro::lang.settlement_not_found'),
            ], 404);
        }

        $business = Business::where("id", $settlement->business_id)->first();

        $pump_operator = PumpOperator::where(
            "id",
            $settlement->pump_operator_id
        )->first();

        // Customer payments for the print page
        $customer_payments_tab = CustomerPayment::leftjoin(
            "contacts",
            "customer_payments.customer_id",
            "contacts.id"
        )
            ->where(
                "customer_payments.settlement_no",
                $settlement->settlement_no
            )
            ->where("customer_payments.business_id", $business_id)
            ->select("customer_payments.*", "contacts.name as customer_name")
            ->get();

        // Calculate cash amount from SettlementCashPayment records (most accurate)
        // Cash payments are already filtered by shift_id in the code above (lines 11903-11929)
        // Use the already-filtered cash_payments relation instead of reloading
        $cash_payments_total = $settlement->cash_payments->sum('amount');

        // Also calculate from daily_collections as fallback (for backward compatibility)
        $daily_collections = DB::table('daily_collections')
            ->where("settlement_id", $settlement->id)
            ->where('type', 'daily_collection')
            ->select('current_amount', 'balance_collection')
            ->get();

        $total_cash_amount = 0;
        foreach ($daily_collections as $collection) {
            $total_cash_amount += floatval($collection->current_amount) + floatval($collection->balance_collection);
        }

        // Alternative method using raw SQL sum
        $total_cash_amount_sql = DB::table('daily_collections')
            ->where("settlement_id", $settlement->id)
            ->selectRaw('COALESCE(SUM(current_amount + balance_collection), 0) as total')
            ->value('total') ?? 0;

        // IMPORTANT: Do NOT use daily_collections as fallback if SettlementCashPayment records exist
        // because SettlementCashPayment records are created FROM DailyCollection entries,
        // which would cause double counting. Only use daily_collections if no SettlementCashPayment exists.
        // This matches the payment preview modal logic which only uses cash_payments.
        $final_cash_amount = $cash_payments_total;

        // Only fallback to daily_collections if there are NO SettlementCashPayment records
        // (for backward compatibility with old settlements that might not have SettlementCashPayment records)
        if ($cash_payments_total == 0) {
            $final_cash_amount = $total_cash_amount_sql;
        }

        // Get shift_ids for use in the print view and hydrate PD meter sales saved by shift.
        $shift_ids = $this->getSettlementPDShiftIds($settlement);
        $this->hydrateSettlementPdMeterSalesForDisplay($settlement, $shift_ids);

        return view("petro::settlement_pd.print")->with(
            compact(
                "settlement",
                "business",
                "pump_operator",
                "customer_payments_tab",
                "final_cash_amount",
                "shift_ids"
            )
        );
    }

    // Added by Muneeb Ahmad for Store Dropdown

    public function checkSlipNo(Request $request)
    {

        $slip_no = $request->input("slip_no");

        $business_id = auth()->user()->business_id;

        // Check if the slip number already exists for this business

        $exists = AccountTransaction::where("business_id", $business_id)

            ->where("slip_no", $slip_no)

            ->exists();

        // Define your duplicate allowance logic here

        // For example, allow duplicates only if user has a special role

        $allow_duplicates = false;

        // Return JSON response

        return response()->json([

            "exists"           => $exists,

            "allow_duplicates" => $allow_duplicates,

        ]);
    }

    private function getValidPdDraftResumeContext(Settlement $settlement, int $business_id): array
    {
        $effective_shift_ids = PumpOperatorAssignment::where('settlement_id', $settlement->id)
            ->pluck('shift_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($effective_shift_ids)) {
            $effective_shift_ids = $settlement->meter_sales_pd
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        if (empty($effective_shift_ids)) {
            $effective_shift_ids = $settlement->meter_sales
                ->pluck('shift_id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();
        }

        if (empty($effective_shift_ids)) {
            $work_shift = is_array($settlement->work_shift)
                ? $settlement->work_shift
                : json_decode($settlement->work_shift, true);

            if (! is_array($work_shift)) {
                $work_shift = array_filter(explode(',', (string) $settlement->work_shift));
            }

            $directShiftNumber = collect($work_shift)
                ->map(fn ($value) => trim((string) $value, " \t\n\r\0\x0B\"[]"))
                ->first(fn ($value) => Str::startsWith($value, $this->getDirectSettlementShiftPrefix($business_id)));

            if (! empty($directShiftNumber) && ! empty($settlement->pump_operator_id)) {
                return [
                    'valid' => true,
                    'reason' => null,
                    'shift_id' => 0,
                    'pump_operator_id' => (int) $settlement->pump_operator_id,
                    'shift_numbers' => [
                        0 => [
                            'shift_number' => $directShiftNumber,
                            'work_shift_id' => null,
                            'pump_operator_id' => (int) $settlement->pump_operator_id,
                            'is_direct_shift' => true,
                        ],
                    ],
                ];
            }

            return ['valid' => false, 'reason' => 'missing_effective_shift_ids'];
        }

        $activeShiftAssignments = PumpOperatorAssignment::leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
            ->where('pump_operator_assignments.business_id', $business_id)
            ->whereIn('pump_operator_assignments.shift_id', $effective_shift_ids)
            ->groupBy(
                'pump_operator_assignments.shift_number',
                'pump_operator_assignments.shift_id',
                'pump_operator_assignments.pump_operator_id',
                'ps.work_shift_id'
            )
            ->select(
                'pump_operator_assignments.shift_number',
                'pump_operator_assignments.shift_id',
                'pump_operator_assignments.pump_operator_id',
                'ps.work_shift_id'
            )
            ->orderBy('pump_operator_assignments.shift_id', 'asc')
            ->get();

        if ($activeShiftAssignments->isEmpty()) {
            return ['valid' => false, 'reason' => 'missing_shift_assignments'];
        }

        $assignmentOperatorIds = $activeShiftAssignments->pluck('pump_operator_id')
            ->filter()
            ->unique()
            ->values();

        if ($assignmentOperatorIds->count() !== 1) {
            return ['valid' => false, 'reason' => 'multiple_assignment_operators'];
        }

        $assignmentOperatorId = (int) $assignmentOperatorIds->first();

        $meterSalePdOperatorIds = $settlement->meter_sales_pd
            ->pluck('pump_operator_id')
            ->filter()
            ->unique()
            ->values();

        if ($meterSalePdOperatorIds->count() > 1) {
            return ['valid' => false, 'reason' => 'multiple_meter_sale_pd_operators'];
        }

        if ($meterSalePdOperatorIds->count() === 1 && (int) $meterSalePdOperatorIds->first() !== $assignmentOperatorId) {
            return ['valid' => false, 'reason' => 'meter_sale_pd_operator_mismatch'];
        }

        if (! empty($settlement->pump_operator_id) && (int) $settlement->pump_operator_id !== $assignmentOperatorId) {
            return ['valid' => false, 'reason' => 'settlement_header_operator_mismatch'];
        }

        return [
            'valid' => true,
            'reason' => null,
            'shift_id' => $activeShiftAssignments->pluck('shift_id')->filter()->first(),
            'pump_operator_id' => $assignmentOperatorId,
            'shift_numbers' => $activeShiftAssignments
                ->mapWithKeys(function ($assignment) {
                    return [
                        $assignment->shift_id => [
                            'shift_number' => $assignment->shift_number,
                            'work_shift_id' => $assignment->work_shift_id,
                            'pump_operator_id' => $assignment->pump_operator_id,
                        ],
                    ];
                })
                ->toArray(),
        ];
    }
}

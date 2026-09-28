<?php

namespace Modules\PetroPD\Http\Controllers\Settlement\Concerns;

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
use Modules\PetroPD\Entities\CustomerPayment;
use Modules\PetroPD\Entities\DailyCollection;
use Modules\PetroPD\Entities\DailyVoucher;
use Modules\PetroPD\Entities\DayEnd;
use Modules\PetroPD\Entities\FuelTank;
use Modules\PetroPD\Entities\MeterSale;
use Modules\PetroPD\Entities\OtherIncome;
use Modules\PetroPD\Entities\OtherSale;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\PetroWhatsAppTemplate;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Entities\PumpOperator;
use Modules\PetroPD\Entities\PumpOperatorAssignment;
use Modules\PetroPD\Entities\PumpOperatorCommission;
use Modules\PetroPD\Entities\PumpOperatorMeterSale;
use Modules\PetroPD\Entities\PumpOperatorOtherSale;
use Modules\PetroPD\Entities\PumpOperatorPayment;
use Modules\PetroPD\Entities\Settlement;
use Modules\PetroPD\Entities\SettlementCardPayment;
use Modules\PetroPD\Entities\SettlementCashDeposit;
use Modules\PetroPD\Entities\SettlementCashPayment;
use Modules\PetroPD\Entities\SettlementChequePayment;
use Modules\PetroPD\Entities\SettlementCreditSalePayment;
use Modules\PetroPD\Entities\SettlementCustomerLoan;
use Modules\PetroPD\Entities\SettlementDrawingPayment;
use Modules\PetroPD\Entities\SettlementEditHistory;
use Modules\PetroPD\Entities\SettlementExcessPayment;
use Modules\PetroPD\Entities\SettlementExpensePayment;
use Modules\PetroPD\Entities\SettlementLoanPayment;
use Modules\PetroPD\Entities\SettlementShortagePayment;
use Modules\PetroPD\Entities\TankSellLine;
use Modules\PetroPD\Entities\TanksTransactionDetail;
use Modules\PetroPD\Http\Controllers\Traits\UpdatesSettlementTransactions;
use Modules\Superadmin\Entities\Subscription;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\DataTables;
use Modules\PetroPD\Entities\PumpOperatorMeterSaleDetail;
use Modules\PetroPD\Services\PetroPdClosedShiftQuery;
use Modules\PetroPD\Services\PetroPdSmsNotificationService;

/**
 * Listing, viewing and printing settlements.
 *
 * MA-002: split out of PetroPDSettlementController, which was 15,639 lines in
 * a single file - the largest controller in the application after core's
 * ReportController.
 *
 * WHY A TRAIT AND NOT A SEPARATE CONTROLLER
 *   Method resolution is unchanged. Routes still point at
 *   PetroPDSettlementController, action() targets still resolve, and $this->
 *   calls between these 111 methods still work. Splitting into separate
 *   controller classes would mean rewriting routes and every action()
 *   reference - a behavioural change dressed up as tidying.
 *
 *   So this is a purely physical split: same class at runtime, smaller files.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten
 * while moving.
 *
 * Methods here: index, show, getUserActivityReport, buildSettlementViewLookups, extractLastInteger, getDirectSettlementShiftPrefix, getNextDirectSettlementShiftLabel, getAvailableDirectSettlementPumps, normalizePdShiftNumbersForBlade, getPdShiftNumberLabelForBlade, getPdShiftNumbersTextForBlade, print, checkSlipNo
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

                "petro_pd_module"

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

                    ->leftJoin("pump_operator_assignments", function ($join) {

                        $join->on("settlements.id", "=", "pump_operator_assignments.settlement_id")
                            ->orOn(function ($q) {
                                $q->on('settlements.pump_operator_id', '=', 'pump_operator_assignments.pump_operator_id')
                                    ->whereNull('pump_operator_assignments.settlement_id')
                                    ->where('settlements.status', 1);
                            });
                    })

                    ->where("settlements.business_id", $business_id);

                $this->applyPdSettlementScope($query, $business_id);

                $query

                    ->select([

                        "pump_operators.name as pump_operator_name",

                        "business_locations.name as location_name",

                        "settlements.*",

                        // DB::raw('GROUP_CONCAT(DISTINCT pump_operator_assignments.shift_number SEPARATOR ",") as shift_number')

                        DB::raw('GROUP_CONCAT(DISTINCT pump_operator_assignments.shift_number ORDER BY CAST(pump_operator_assignments.shift_number AS UNSIGNED) SEPARATOR ",") as shift_number'),
                        // "pump_operator_assignments.shift_number",

                    ])

                    ->with(["meter_sales", "other_sales", "meter_sales_pd.details"]);

                if (! empty(request()->location_id)) {

                    $query->where(

                        "settlements.location_id",

                        request()->location_id

                    );
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

                                        __("petropd::lang.finish_settlement") .

                                        "</a>";
                                } else {

                                    $html .=

                                        '<a class="btn  btn-danger btn-sm" href="' .

                                        route('petropd.settlement-pd.create') . '?view_settlement_id=' . $row->id .

                                        '">' .

                                        __("petropd::lang.finish_settlement") .

                                        "</a>";
                                }
                            } elseif ($row->is_edit == 1) {

                                $html .=

                                    '<a class="btn  btn-warning btn-sm" href="' .

                                    route('petropd.settlement-pd.edit', [$row->id]) .

                                    '">' .

                                    __("petropd::lang.finish_editting") .

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

                                    route('petropd.settlement-pd.show', [$row->id]) .

                                    '" class="btn-modal" data-container=".settlement_modal"><i class="fa fa-eye" aria-hidden="true"></i> ' .

                                    __("messages.view") .

                                    "</a></li>";

                                if (

                                    auth()

                                    ->user()

                                    ->can("settlement.edit") &&

                                    $edit_settlement

                                ) {

                                    $html .=

                                        '<li><a href="' .

                                        route('petropd.settlement-pd.edit', [$row->id]) .

                                        '" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> ' .

                                        __("messages.edit") .

                                        "</a></li>";
                                }

                                if (

                                    auth()

                                    ->user()

                                    ->can("settlement.edit") &&

                                    $edit_settlement_no_change

                                ) {

                                    $html .=

                                        '<li><a href="' .

                                        route('petropd.settlement-pd.edit', [$row->id]) .

                                        '?no_change=1" class="edit_settlement_button"><i class="fa fa-pencil-square-o"></i> ' .

                                        __("petropd::lang.edit_no_change") .

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

                                    ->can("settlement.delete")

                                ) {

                                    // commented By M Usman for hiding Delete Action

                                    $html .=

                                        '<li><a href="' .

                                        route('petropd.settlement-pd.destroy', [$row->id]) .

                                        '" class="delete_settlement_button"><i class="fa fa-trash"></i> ' .

                                        __("messages.delete") .

                                        "</a></li>";
                                }

                                $html .=

                                    '<li><a data-href="' .

                                    route('petropd.settlement-pd.print', [$row->id]) .

                                    '" class="print_settlement_button"><i class="fa fa-print"></i> ' .

                                    __("petropd::lang.print") .

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
                                $_pumps = Pump::whereIn("id", $_pump_ids)

                                    ->pluck("pump_no")

                                    ->toArray();

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

                            return route('petropd.settlement-pd.show', [$row->id]);
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

        return view("petropd::pd_settlement.index")->with(

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

            \Log::info('Settlement PD Show: Filtering by shift_ids', [
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

        $settlementLookups = $this->buildSettlementViewLookups($settlement, $business_id);

        return view('petropd::pd_settlement.show')->with(

            compact(

                "settlement",

                "business",

                "pump_operator",

                "customer_payments_tab",
                "settlementLookups"

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
                ->whereIn("subject_type", [\Modules\PetroPD\Entities\Settlement::class])
                ->where('subject_id', '>', 0) // ensure subject exists
                ->whereHasMorph('subject', [\Modules\PetroPD\Entities\Settlement::class], function($query) {
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

                                "Modules\PetroPD\Entities\Settlement"

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

        return view("petropd::report.user_activity")->with(

            compact("users", "type", "subject")

        );
    }

    private function buildSettlementViewLookups($settlement, int $businessId): array
    {
        $collectRelation = static function ($model, string $relation) {
            return $model->relationLoaded($relation) ? $model->getRelation($relation) : collect();
        };

        $meterSales = $collectRelation($settlement, 'meter_sales');
        $meterSalesPd = $collectRelation($settlement, 'meter_sales_pd');
        $otherSales = $collectRelation($settlement, 'other_sales');
        $otherIncomes = $collectRelation($settlement, 'other_incomes');
        $creditSales = $collectRelation($settlement, 'credit_sale_payments');
        $expensePayments = $collectRelation($settlement, 'expense_payments');
        $customerLoans = $collectRelation($settlement, 'customer_loans');
        $loanPayments = $collectRelation($settlement, 'loan_payments');
        $drawingPayments = $collectRelation($settlement, 'drawings_payments');
        $customerPayments = $collectRelation($settlement, 'customer_payments');

        $pdDetails = $meterSalesPd->flatMap(function ($sale) {
            return $sale->relationLoaded('details') ? $sale->getRelation('details') : collect();
        });

        $pumpIds = $meterSales->pluck('pump_id')
            ->merge($pdDetails->pluck('pump_id'))
            ->filter()->unique()->values();

        $pumps = Pump::query()->whereIn('id', $pumpIds)->get()->keyBy('id');

        $productIds = $meterSales->pluck('product_id')
            ->merge($otherSales->pluck('product_id'))
            ->merge($otherIncomes->pluck('product_id'))
            ->merge($creditSales->pluck('product_id'))
            ->merge($pumps->pluck('product_id'))
            ->filter()->unique()->values();

        $customerIds = $customerPayments->pluck('customer_id')
            ->merge($creditSales->pluck('customer_id'))
            ->merge($customerLoans->pluck('customer_id'))
            ->filter()->unique()->values();

        $accountIds = $loanPayments->pluck('loan_account')
            ->merge($drawingPayments->pluck('loan_account'))
            ->filter()->unique()->values();

        $workShiftIds = collect($settlement->work_shift ?? []);
        if ($workShiftIds->count() === 1 && is_string($workShiftIds->first())) {
            $decoded = json_decode((string) $workShiftIds->first(), true);
            if (is_array($decoded)) {
                $workShiftIds = collect($decoded);
            }
        }
        $workShiftIds = $workShiftIds->push($settlement->shift_id ?? null)->filter()->unique()->values();

        /*
         * IS1984 #5 (10 Aug 2026): the Other Sale table printed its headers and a
         * 0.00 sub total, with none of the added other sales.
         *
         * settlements.work_shift holds shift NUMBERS, not shift ids. The view gates
         * that section on $shift_ids, which print() builds with
         * getSettlementPDShiftIds() - and that resolver maps the numbers to real
         * shift ids through pump_operator_assignments.shift_number. The lookup
         * below did not: it fed the raw work_shift values straight into
         * whereIn('shift_id', ...), so it was matching shift numbers against shift
         * ids and finding nothing.
         *
         * The gate therefore opened while the collection behind it was empty, which
         * is why the section rendered but had no rows. Merging in the resolver's
         * output puts both halves on the same ids. This only ever ADDS ids, so it
         * cannot drop a row that previously appeared.
         */
        $workShiftIds = $workShiftIds
            ->merge($this->getSettlementPDShiftIds($settlement))
            ->filter()
            ->unique()
            ->values();

        $operatorOtherSales = PumpOperatorOtherSale::query()
            ->whereIn('shift_id', $workShiftIds)
            ->get();

        $productIds = $productIds->merge($operatorOtherSales->pluck('product_id'))->filter()->unique()->values();

        $settlementIds = $otherSales->pluck('settlement_no')->filter()->unique()->values();

        return [
            'business' => Business::query()->whereKey($businessId)->first(),
            'pumps' => $pumps,
            'products' => Product::query()->where('business_id', $businessId)->whereIn('id', $productIds)->get()->keyBy('id'),
            'contacts' => Contact::query()->where('business_id', $businessId)->whereIn('id', $customerIds)->get()->keyBy('id'),
            'expense_categories' => \App\ExpenseCategory::query()->where('business_id', $businessId)->whereIn('id', $expensePayments->pluck('category_id')->filter()->unique())->get()->keyBy('id'),
            'accounts' => Account::query()->where('business_id', $businessId)->whereIn('id', $accountIds)->get()->keyBy('id'),
            'work_shifts' => WorkShift::query()->whereIn('id', $workShiftIds)->get()->keyBy('id'),
            'settlements' => Settlement::query()->where('business_id', $businessId)->whereIn('id', $settlementIds)->get()->keyBy('id'),
            'operator_other_sales_by_shift' => $operatorOtherSales->groupBy('shift_id'),
            'operator_other_sales' => $operatorOtherSales,
        ];
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

        if (Schema::hasColumn('pumps', 'is_other_sales_pump')) {
            $pump_query->where('is_other_sales_pump', 0);
        }

        return $pump_query->pluck('pump_name', 'id');
    }

    private function normalizePdShiftNumbersForBlade($shiftNumbers): array
    {
        if ($shiftNumbers instanceof \Illuminate\Support\Collection) {
            $shiftNumbers = $shiftNumbers->toArray();
        }

        if (empty($shiftNumbers) || ! is_array($shiftNumbers)) {
            return [];
        }

        $normalized = [];
        foreach ($shiftNumbers as $key => $value) {
            $label = $this->getPdShiftNumberLabelForBlade($value);
            if ($label !== '') {
                $normalized[$key] = $label;
            }
        }

        return $normalized;
    }

    private function getPdShiftNumberLabelForBlade($value): string
    {
        if ($value instanceof \Illuminate\Support\Collection) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            foreach (['shift_number', 'number', 'name', 'label'] as $field) {
                if (isset($value[$field]) && ! is_array($value[$field])) {
                    return (string) $value[$field];
                }
            }

            $firstScalar = collect($value)->first(function ($item) {
                return ! is_array($item) && ! is_object($item);
            });

            return $firstScalar === null ? '' : (string) $firstScalar;
        }

        if (is_object($value)) {
            foreach (['shift_number', 'number', 'name', 'label'] as $field) {
                if (isset($value->{$field}) && ! is_array($value->{$field})) {
                    return (string) $value->{$field};
                }
            }

            return '';
        }

        return (string) $value;
    }

    private function getPdShiftNumbersTextForBlade($shiftNumbers): string
    {
        return implode(', ', array_values($this->normalizePdShiftNumbersForBlade($shiftNumbers)));
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
                $cash_deposits = \Modules\PetroPD\Entities\SettlementCashDeposit::where('settlement_no', $settlement->settlement_no)
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
            /*
             |------------------------------------------------------------------
             | LA-1169 #2: cash and card totals printed as 0.00.
             |------------------------------------------------------------------
             |
             | settlement_cash_payments.settlement_no is not consistent. Depending
             | on which screen wrote the row it holds EITHER the numeric
             | settlements.id OR the printed number such as "PDST8".
             |
             | This query matched only $settlement->id, so every row saved with the
             | printed number was invisible here and the total came out 0.00 - even
             | though Add Payment showed it correctly.
             |
             | Add Payment is the proof: it builds $settlement_keys from BOTH forms
             |     [ (string) $settlement_no, (string) $settlement->settlement_no ]
             | and matches with whereIn. The card query immediately below already
             | does the same thing with an orWhere. Cash was the odd one out.
             |
             | Matching both forms here makes the printout agree with the screen.
             */
            $settlement_keys = array_values(array_filter([
                (string) $settlement->id,
                (string) $settlement->settlement_no,
            ], static fn ($key) => $key !== ''));

            $cash_payments_query = SettlementCashPayment::whereIn('settlement_no', $settlement_keys)
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
            /*
             * IS1984 #4 (10 Aug 2026): print-path copy of the card shift filter -
             * see the full note in CreatesPdSettlements. The old LEFT JOIN +
             * whereIn on pump_operator_payments.shift_id dropped every card
             * payment that could not be walked back to a pump payment, because
             * NULL IN (...) is not true. All three copies of this filter must stay
             * in step: this one feeds a reprint from the settlement list, the
             * finalize copy feeds the report shown straight after saving, and the
             * ProvidesPdLookups copy feeds the shared fallback loader.
             */
            if (! empty($shift_ids_for_filter) && count($shift_ids_for_filter) > 0) {
                $card_payments_query->where(function ($q) use ($shift_ids_for_filter) {
                    $q->whereExists(function ($sub) use ($shift_ids_for_filter) {
                        $sub->select(DB::raw(1))
                            ->from('pump_operator_payments')
                            ->whereColumn('pump_operator_payments.id', 'settlement_card_payments.pump_payment_id')
                            ->whereIn('pump_operator_payments.shift_id', $shift_ids_for_filter);
                    })
                        ->orWhereExists(function ($sub) use ($shift_ids_for_filter) {
                            $sub->select(DB::raw(1))
                                ->from('daily_cards')
                                ->join('pump_operator_payments', function ($join) {
                                    $join->on('pump_operator_payments.pump_operator_id', '=', 'daily_cards.pump_operator_id')
                                        ->whereRaw('pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci = daily_cards.collection_no COLLATE utf8mb4_unicode_ci')
                                        ->where('pump_operator_payments.payment_type', 'card')
                                        ->whereColumn('pump_operator_payments.payment_amount', 'daily_cards.amount');
                                })
                                ->whereColumn('daily_cards.id', 'settlement_card_payments.daily_card_id')
                                ->whereIn('pump_operator_payments.shift_id', $shift_ids_for_filter);
                        })
                        ->orWhere(function ($orQ) {
                            $orQ->whereNull('settlement_card_payments.daily_card_id')
                                ->whereNull('settlement_card_payments.pump_payment_id');
                        });
                });
            }

            $card_payments = $card_payments_query->get();
            $settlement->setRelation('card_payments', $card_payments);

            \Log::info('Settlement PD Print: Filtering by shift_ids', [
                'settlement_id'        => $settlement->id,
                'shift_ids_for_filter' => $shift_ids_for_filter,
            ]);

            // Filter meter_sales by shift_id
            if (! empty($shift_ids_for_filter)) {
                $meter_sales_filtered = MeterSale::where('settlement_no', $settlement->id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->get();
                $settlement->setRelation('meter_sales', $meter_sales_filtered);

                \Log::info('Settlement PD Print: Meter Sales Filtered', [
                    'total_after' => $meter_sales_filtered->count(),
                ]);
            }

            // Credit sale payments filtered to this settlement's shift(s) only.
            $credit_sales = $this->getFilteredCreditSalePayments($settlement, $shift_ids_for_filter);
            $settlement->setRelation('credit_sale_payments', $credit_sales);

            \Log::info('Settlement PD Print: Credit Sales Filtered', [
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
                'msg'     => __('petropd::lang.settlement_not_found'),
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

        // Calculate the legacy fallback in one aggregate query only.
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

        $settlementLookups = $this->buildSettlementViewLookups($settlement, $business_id);

        return view("petropd::pd_settlement.print")->with(
            compact(
                "settlement",
                "business",
                "pump_operator",
                "customer_payments_tab",
                "final_cash_amount",
                "shift_ids",
                "settlementLookups"
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
    /**
     * PDRW-007: create.blade.php prints shift numbers inside Blade {{ }}.
     * Therefore every value passed as shift number must be a string, not a metadata array.
     */
}

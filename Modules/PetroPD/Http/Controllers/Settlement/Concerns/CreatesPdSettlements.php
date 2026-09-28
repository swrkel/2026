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
 * Creating a settlement. store() is the large one - see the note in this file.
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
 * A NOTE ON store()
 *   store() is 3,272 lines on its own - larger than most files should be, and
 *   larger than this split can fix. Breaking it up means understanding the
 *   order in which it posts transactions, account transactions, contact ledger
 *   rows and stock, and that is a refactor with real risk to money, not a
 *   file-tidying exercise. It belongs in its own piece of work with a
 *   before/after comparison on a real settlement. Grouping it here at least
 *   means you are not scrolling past it to reach anything else.
 *
 * Method bodies are byte-identical to the original. Nothing was rewritten
 * while moving.
 *
 * Methods here: create, store, createSettlementIfNotExist, getRequestedPetroPdShiftIds, linkPetroPdDraftToRequestedShift, getValidPdDraftResumeContext
 */
trait CreatesPdSettlements
{
    public function create()
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

        $requestedSettlementId = ! empty(request()->view_settlement_id)
            ? (int) request()->view_settlement_id
            : (! empty(request()->settlement_id) ? (int) request()->settlement_id : null);

        // Check if pumper dashboard is enabled and operator has open shifts
        $is_pumper_dashboard_enabled = $this->moduleUtil->hasThePermissionInSubscription($business_id, 'pump_operator_dashboard');

        if ($is_pumper_dashboard_enabled && ! empty(request()->pump_operator)) {
            $pump_operator_id = request()->pump_operator;

            /*
             * MA-002: the guard now asks whether there is anything TO settle,
             * rather than whether any shift is open.
             *
             * It used to block the operator if they had ANY open shift. With
             * settlement following closed shifts, that broke the normal way of
             * working:
             *
             *     operator closes shift 1   -> ready to settle
             *     operator starts shift 2   -> now open
             *     you try to settle shift 1 -> blocked, wrongly
             *
             * Shift 1 is closed and waiting, but the old guard refused because
             * shift 2 existed. Where operators start a new shift straight
             * away, that would block settlement almost every time.
             *
             * So it now counts CLOSED, UNSETTLED shifts. The message "please
             * close the shift and continue" is right only when there are none,
             * which is exactly when it now appears.
             */
            $settleable_shift_count = PumpOperatorAssignment::join('petro_shifts', 'pump_operator_assignments.shift_id', '=', 'petro_shifts.id')
                ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
                ->where('pump_operator_assignments.business_id', $business_id)
                /*
                 * MA-002: the same shift-closed test as the main query -
                 * status 2 OR a stamped closed_time. Using status 2 alone
                 * would never match, because on this system it appears only
                 * on shifts that are already settled.
                 */
                ->where(function ($shiftClosed) {
                    $shiftClosed->where('petro_shifts.status', 2)
                        ->orWhereNotNull('petro_shifts.closed_time');
                })
                ->where(function ($notSettled) {
                    $notSettled->whereNull('pump_operator_assignments.closed_in_settlement')
                        ->orWhere('pump_operator_assignments.closed_in_settlement', 0);
                })
                ->count();

            if ($settleable_shift_count === 0) {
                $output = [
                    'success' => false,
                    'msg'     => 'The shift is not yet closed, please close the shift and continue',
                ];
                return redirect()->back()->with('status', $output);
            }
        }

        $shiftQuery = PumpOperatorAssignment::query()
            ->where('pump_operator_assignments.business_id', $business_id)
            /*
             * MA-002: reverted to a leftJoin and the pump-close test.
             *
             * I briefly made this an inner join with ps.status = 2. That was
             * wrong, and your data showed why: on this system status 2 appears
             * ONLY on shifts that are already settled. Asking for status 2 AND
             * not settled is a pair of conditions that never occur together,
             * so the page could only ever show nothing.
             *
             * The real shift-closed test is the IS-1930 condition further
             * down, which accepts status 2 OR a stamped closed_time OR a
             * missing shift row. That one was already correct and I should not
             * have added a second, stricter one above it.
             */
            ->leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
            ->where('pump_operator_assignments.status', 'close')
            /*
             * MA-002: a shift is SETTLED when closed_in_settlement = 1.
             *
             * That is the only column in this operation that means settled.
             * SettlementStateMachine writes it on the Settled state, and the
             * settlement flow writes it directly at lines 3911 and 3943.
             *
             * THE settlement_id TEST IS REMOVED. It belongs to SettlementSW,
             * which is a separate operation with no part in the Pumper
             * Dashboard or Petro PD - and keeping it here was doing real harm,
             * not merely sitting inert:
             *
             *     Draft         settlement_id null
             *     InSettlement  settlement_id 0     <- NOT null
             *     Settled       settlement_id 0     <- NOT null
             *     Reopened      settlement_id null
             *
             * whereNull('settlement_id') therefore excluded IN-SETTLEMENT rows
             * as well. A shift part-way through settlement vanished from the
             * dropdown, which is not the same thing as being settled and made
             * a half-finished settlement impossible to resume.
             *
             * One column, one meaning.
             */
            ->where(function ($notSettled) {
                $notSettled->whereNull('pump_operator_assignments.closed_in_settlement')
                    ->orWhere('pump_operator_assignments.closed_in_settlement', 0);
            })
            // IS1715: Close Shift sets closed_in_settlement=1 after posting its meter-sale
            // batch. The shift remains pending until a Petro PD settlement_id is linked.
            ->whereNotNull('pump_operator_assignments.close_date_and_time')
            /*
             * MA-002 (IS-1930): the SHIFT must be closed, not just its pumps.
             *
             * Closing a pump sets pump_operator_assignments.status = 'close'.
             * Closing the SHIFT is a separate act, and SettlementStateMachine
             * records it as petro_shifts.status = 2 with closed_time set:
             *
             *     ShiftState::PumperClosed, InSettlement, Settled
             *         => ['status' => 2, 'closed_time' => $now]
             *
             * Without this condition a shift appeared in PD Settlement the
             * moment its last pump was closed, while the operator was still
             * working - which is what was reported.
             *
             * THE IS1666 NOTE BELOW IS RESPECTED. It warns that some builds
             * write the assignment first and petro_shifts a moment later, so a
             * just-closed shift must not vanish. Hence closed_time is accepted
             * as well as status 2 - either is proof the shift was closed - and
             * a shift row that is missing entirely still passes, exactly as it
             * did before, because this is a leftJoin.
             */
            ->where(function ($shiftClosed) {
                $shiftClosed->whereNull('ps.id')                 // no shift row - unchanged behaviour
                    ->orWhere('ps.status', 2)                    // closed by the state machine
                    ->orWhereNotNull('ps.closed_time');          // or stamped closed
            });

        // IS1666: assignment close state is the settlement source of truth. Some
        // tenant builds save the assignment first and update petro_shifts later.
        // Do not hide a valid just-closed shift while that secondary update catches up.

        // If form should show shifts only for a selected operator (optional)
        if (! empty(request()->pump_operator)) {
            $shiftQuery->where('pump_operator_assignments.pump_operator_id', request()->pump_operator);
        }

        $shift_numbers = $shiftQuery->select(
            DB::raw('MIN(pump_operator_assignments.shift_number) as shift_number'),
            'pump_operator_assignments.shift_id',
            DB::raw(
                'COALESCE(' .
                'MAX(NULLIF(ps.pump_operator_id, 0)), ' .
                'MAX(NULLIF(pump_operator_assignments.pump_operator_id, 0))' .
                ') as pump_operator_id'
            ),
            DB::raw('MAX(ps.work_shift_id) as work_shift_id'),
            DB::raw('MIN(pump_operator_assignments.close_date_and_time) as closed_at')
        )
            ->groupBy('pump_operator_assignments.shift_id')
            // LA1086: PD Settlement is FIFO by the displayed Shift Number.
            // A later shift may be closed earlier, so close time must not decide
            // which pending shift is autoloaded first.
            ->orderByRaw('CAST(MIN(pump_operator_assignments.shift_number) AS UNSIGNED) ASC')
            ->orderByRaw('MIN(pump_operator_assignments.shift_number) ASC')
            ->orderBy('closed_at', 'asc')
            ->orderBy('pump_operator_assignments.shift_id', 'asc')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->shift_id => [
                    'shift_number' => $item->shift_number,
                    'work_shift_id' => $item->work_shift_id,
                    'pump_operator_id' => $item->pump_operator_id,
                ]];
            })
            ->toArray();

        /*
         * MA-002 (URGENT): the OLDEST-SHIFT-FIRST RESTRICTION IS REMOVED.
         *
         * Parcels 268 and 275 filtered the dropdown to the lowest unsettled
         * shift number, so only one shift could be settled at a time and the
         * rest were hidden. In practice that left only shift 1 selectable and
         * blocked the operation.
         *
         * EVERY PENDING SHIFT IS NOW OFFERED. The list is still ordered
         * oldest-first - that ordering was here before my work and is
         * untouched - so the oldest still appears at the top and is selected
         * by default. The difference is that the others are no longer hidden.
         *
         * WHAT IS KEPT: shifts already settled are still excluded, which was
         * asked for separately and is not what caused this. That filter is on
         * the query above, testing closed_in_settlement.
         */
        $initial_shift_numbers = $shift_numbers;

        // $open_shift = PumpOperatorAssignment::where('business_id', $business_id)
        //     ->where(function ($q) {
        //         $q->whereNull('close_date_and_time')
        //         ->orWhere('status', '!=', 'close');
        //     })
        //     ->orderByRaw('CAST(shift_number AS UNSIGNED)')
        //     ->first();

        // if ($open_shift) {
        //     return redirect()->back()->with('status', [
        //         'success' => false,
        //         'msg' => "Shift No {$open_shift->shift_number} is not yet closed in the Pumper Dashboard. Please complete it first",
        //     ]);
        // }

        // $active_shift = PumpOperatorAssignment::with(['pumpOperator', 'shift'])
        //     ->where('business_id', $business_id)
        //     ->where('status', 'close')
        //     ->whereNotNull('close_date_and_time')
        //     ->orderByDesc('close_date_and_time')
        //     ->first();


        // $shift_number = optional($active_shift)->shift_number;
        // $shift_id     = optional($active_shift)->shift_id;
        // $pump_operator_id = optional($active_shift)->pump_operator_id;
        // $pump_operator_name = optional($active_shift->pumpOperator)->name;

        // $settleable_shift_id = PumpOperatorAssignment::where('business_id', $business_id)
        //     ->where('status', 'close')
        //     ->whereNotNull('close_date_and_time')
        //     ->groupBy('shift_id')
        //     ->orderByRaw('MAX(close_date_and_time) DESC')
        //     ->value('shift_id');
        // Use the first option from the already FIFO-sorted list above. This keeps
        // the dropdown order and the automatically selected shift on one source
        // of truth and prevents close-time ordering from selecting a newer shift.
        /*
         * MA-002 (URGENT): auto-select the OLDEST PENDING shift, explicitly.
         *
         * This took array_key_first($shift_numbers) - the first key of the
         * list. That is only the oldest if the array's ORDER survives every
         * step between the query and here, and the array is rebuilt in more
         * than one place further down. When the order was lost, or the first
         * key was not the oldest, the page opened on the wrong shift or on
         * none at all - which is why reopening PD Settlement after settling
         * shift 1 did not land on shift 2.
         *
         * The oldest is now chosen by comparing the shift NUMBERS in the list,
         * as UNSIGNED so "10" sorts after "9". It no longer depends on array
         * order at all.
         *
         * Only shifts already in $shift_numbers are considered, so everything
         * the query filtered out - settled shifts, unclosed shifts - stays
         * filtered out.
         */
        /*
         * MA-002: the page now HONOURS THE SHIFT YOU SELECT, and defaults to
         * the oldest.
         *
         * It used to ignore the dropdown entirely and always load the lowest
         * shift number. So the dropdown could show shift 6 while the meter
         * sales and payments came from shift 5 - which is why the same shift
         * number appeared to have different sales.
         *
         * The rule now:
         *
         *   nothing selected   the OLDEST pending shift, as before
         *   a shift selected   THAT shift, provided it is one of this
         *                      operator's own pending shifts
         *
         * THE SELECTION IS VALIDATED against $shift_numbers - the list the
         * page itself built from closed, unsettled assignments for this
         * business and operator. A shift id that is not in that list is
         * ignored and the oldest used instead, so an edited URL cannot load a
         * shift that is not eligible, or one belonging to someone else.
         */
        $settleable_shift_id = null;
        $lowestShiftNumber = null;

        if (! empty($shift_numbers)) {
            foreach ($shift_numbers as $candidateShiftId => $candidateRow) {
                $candidateNumber = (int) ($candidateRow['shift_number'] ?? 0);

                if ($lowestShiftNumber === null || $candidateNumber < $lowestShiftNumber) {
                    $lowestShiftNumber = $candidateNumber;
                    $settleable_shift_id = (int) $candidateShiftId;
                }
            }

            // A shift chosen in the dropdown wins, if it is genuinely eligible.
            $requestedShiftId = request()->shift_number ?? request()->shift_id ?? null;

            if (! empty($requestedShiftId) && array_key_exists((int) $requestedShiftId, $shift_numbers)) {
                $settleable_shift_id = (int) $requestedShiftId;
            }
        }

        /*
         * MA-002 (URGENT): judge "not settled" by the same column as the
         * dropdown above.
         *
         * This tested settlement_id, which Petro PD never writes - it marks a
         * settled assignment with closed_in_settlement = 1. So this could load
         * the assignments of a shift that had already been settled, while the
         * dropdown had correctly dropped it. The two disagreed about what
         * "settled" means; they now use the same rule.
         */
        $shift_assignments = PumpOperatorAssignment::with('pumpOperator')
            ->where('business_id', $business_id)
            ->where(function ($notSettled) {
                $notSettled->whereNull('closed_in_settlement')
                    ->orWhere('closed_in_settlement', 0);
            })
            ->where('shift_id', $settleable_shift_id)
            ->where('status', 'close')
            ->get();

        $shift_id = $settleable_shift_id;
        $initialShiftContext = ! empty($shift_id) && isset($shift_numbers[$shift_id])
            ? $shift_numbers[$shift_id]
            : null;
        $shift_number = is_array($initialShiftContext)
            ? ($initialShiftContext['shift_number'] ?? null)
            : optional($shift_assignments->first())->shift_number;
        $pump_operator_id = is_array($initialShiftContext)
            ? (int) ($initialShiftContext['pump_operator_id'] ?? 0)
            : (int) optional($shift_assignments->first())->pump_operator_id;
        $initialPumpOperator = $pump_operator_id > 0
            ? PumpOperator::where('business_id', $business_id)->where('id', $pump_operator_id)->first()
            : null;
        $pump_operator_name = optional($initialPumpOperator)->name;






        $next_shift_number = $shift_number
            ? ((int) $shift_number)
            : null;



        $reviewed = $this->transactionUtil->get_review(

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
        }

        $business_id = request()->session()->get("business.id")
            ?: request()->session()->get("user.business_id")
            ?: (Auth::user()->business_id ?? null);

        $business = ! empty($business_id) ? Business::where("id", $business_id)->first() : null;

        if (empty($business)) {
            \Log::error("PetroPD Settlement business context missing", [
                "business_id" => $business_id,
                "user_id" => Auth::id(),
                "route" => request()->route() ? request()->route()->getName() : null,
                "url" => request()->fullUrl(),
            ]);

            $business = (object) [
                "id" => $business_id,
                "pos_settings" => "{}",
                "currency_precision" => 2,
            ];
        }

        $pos_settings = json_decode($business->pos_settings ?? "{}", true);
        if (! is_array($pos_settings)) {
            $pos_settings = [];
        }

        $check_qty = ! empty($pos_settings["allow_overselling"]) ? false : true;

        $cash_denoms = ! empty($pos_settings["cash_denominations"])

            ? explode(",", $pos_settings["cash_denominations"])

            : [];

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

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

        // Petro / Direct Settlement must use ST.
        // PetroPD / PD Settlement must use PDST.
        // The same Blade view is used, so decide by current request/referrer.
        $is_petropd_module_screen = $this->isPetroPdModuleRequest(request());

        $prefix = $is_petropd_module_screen
            ? $this->getPrimaryPetroPdModuleSettlementPrefix($business_id)
            : (! empty($ref_no_prefixes["settlement"])
                ? $ref_no_prefixes["settlement"]
                : "ST");

        $starting_no = ! empty($ref_no_starting_number["settlement_pd"])

            ? (int) $ref_no_starting_number["settlement_pd"]

            : 1;

        // S 268 FIX FOLLOW-UP (2026-05-31): Generate the next display number
        // from the highest existing number with the current prefix. Do NOT exclude
        // PDST here, because some businesses use PDST as the active Settlement PD
        // prefix. Excluding PDST caused the create screen to keep showing PDST1
        // even when PDST1 and PDST2 already existed in the list.
        $count_query = Settlement::where("business_id", $business_id)
            ->where("settlement_no", "LIKE", $prefix . "%")
            ->where("settlement_no", "NOT LIKE", "SET-SW%");

        if ($is_petropd_module_screen) {
            // PetroPD numbering must ignore normal ST direct-settlement numbers.
            $this->applyPetroPdModuleSettlementScope($count_query, $business_id, 'settlement_no');
        } else {
            // Normal Petro / Direct Settlement numbering must ignore PetroPD numbers.
            $this->excludePetroPdModuleSettlements($count_query, $business_id, 'settlement_no');
        }

        $count = $count_query
            ->pluck("settlement_no")
            ->map(function ($settlementNo) {
                return $this->extractLastInteger($settlementNo);
            })
            ->max();

        $nextNumber = ((int) $count) + 1;
        while (Settlement::where('business_id', $business_id)->where('settlement_no', $prefix . $nextNumber)->exists()) {
            $nextNumber++;
        }
        $settlement_no = $prefix . $nextNumber;

        $currency_precision = ! empty($business->currency_precision)

            ? $business->currency_precision

            : 2;

        $meeter_precision = 3;

        // PetroPD business rule:
        // Only one pending PD settlement can be active at a time. If the user refreshes
        // the PD Settlement screen after adding payments, do not generate/open another
        // draft. Resume the existing pending PD settlement so Card/Cash/etc. payment
        // rows stay visible and linked to the same settlement.
        $active_settlement = null;

        if (empty($requestedSettlementId)) {
            $pendingPdSettlement = Settlement::where("status", 1)
                ->where("business_id", $business_id);

            $this->applyPdSettlementScope($pendingPdSettlement, $business_id);

            $pendingPdSettlement = $pendingPdSettlement
                ->orderBy("id", "asc")
                ->first();

            if (! empty($pendingPdSettlement)) {
                $requestedSettlementId = (int) $pendingPdSettlement->id;
                $settlement_no = $pendingPdSettlement->settlement_no;
            }
        }

        if (! empty($requestedSettlementId)) {
            $active_settlement = Settlement::where("status", 1)

                ->where("business_id", $business_id)

                ->where('id', $requestedSettlementId);

            $this->applyPdSettlementScope($active_settlement, $business_id);

            $active_settlement = $active_settlement

                ->select("settlements.*")

                ->with([

                    "meter_sales",

                    "meter_sales_pd",

                    "other_sales",

                    "other_incomes",

                    "customer_payments",

                ])

                ->first();
        }

        $is_finishing_existing_settlement = ! empty($requestedSettlementId) && ! empty($active_settlement);
        $can_resume_active_settlement = false;

        $other_sale_final_total = 0.0;

        $pump_other_sale_final_total = 0.0;

        $combinedOtherSales = [];

        if ($active_settlement) {
            $draft_resume_context = $this->getValidPdDraftResumeContext($active_settlement, (int) $business_id);

            if ($draft_resume_context['valid']) {
                $can_resume_active_settlement = true;
                $active_settlement->pump_operator_id = $draft_resume_context['pump_operator_id'];
                $shift_id = $draft_resume_context['shift_id'];

                // PDRW-005 FIX:
                // getValidPdDraftResumeContext() returns each shift option as a detail array:
                // [shift_id => ['shift_number' => ..., 'work_shift_id' => ..., ...]].
                // The create blade prints the option label with {{ $shiftOptionNumber }}.
                // Passing the detail array directly causes:
                // htmlspecialchars(): Argument #1 ($string) must be of type string, array given.
                // Keep only scalar option labels for the view, and keep the selected shift id separately.
                $draft_shift_numbers = $draft_resume_context['shift_numbers'] ?? [];
                $shift_numbers = collect($draft_shift_numbers)
                    ->mapWithKeys(function ($shift_option, $shift_option_id) {
                        if (is_array($shift_option)) {
                            return [$shift_option_id => (string) ($shift_option['shift_number'] ?? reset($shift_option) ?? $shift_option_id)];
                        }

                        return [$shift_option_id => (string) $shift_option];
                    })
                    ->toArray();

                $shift_number = collect($shift_numbers)->first();
            } else {
                Log::warning('Ignoring inconsistent PD draft on create', [
                    'settlement_id' => $active_settlement->id,
                    'settlement_no' => $active_settlement->settlement_no,
                    'reason' => $draft_resume_context['reason'],
                ]);

                $active_settlement = null;
                $shift_id = null;
                $shift_number = null;
                $shift_numbers = $initial_shift_numbers;
                $pump_operator_id = null;
                $pump_operator_name = null;
            }

            if ($can_resume_active_settlement) {
                // Ensure refresh/resume shows the same payment rows in Payment to Finalize.
                $this->reloadSettlementPayments($active_settlement);

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

                        ->where(

                            "pump_operator_assignments.status",

                            "close"

                        )->whereRaw('pump_operator_assignments.id = (

                             SELECT MAX(poa.id)

                             FROM pump_operator_assignments poa

                             WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"

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
        }

        if (! $can_resume_active_settlement) {
            $shift_id = $settleable_shift_id;
            $initialShiftContext = ! empty($shift_id) && isset($initial_shift_numbers[$shift_id])
                ? $initial_shift_numbers[$shift_id]
                : null;
            $shift_number = is_array($initialShiftContext)
                ? ($initialShiftContext['shift_number'] ?? null)
                : null;
            $pump_operator_id = is_array($initialShiftContext)
                ? (int) ($initialShiftContext['pump_operator_id'] ?? 0)
                : null;
            $initialPumpOperator = ! empty($pump_operator_id)
                ? PumpOperator::where('business_id', $business_id)->where('id', $pump_operator_id)->first()
                : null;
            $pump_operator_name = optional($initialPumpOperator)->name;
            $shift_numbers = $initial_shift_numbers;
        }

        $business_locations = BusinessLocation::forDropdown($business_id);

        $default_location = current(array_keys($business_locations->toArray()));

        if (! empty($active_settlement)) {

            // $already_pumps = PumpOperatorMeterSale::where(

            //     "settlement_no",

            //     $active_settlement->id

            // )

            //     ->pluck("pump_id")

            //     ->toArray();
                $already_pumps = PumpOperatorMeterSale::where(function ($q) use ($active_settlement) {
                        $q->where('settlement_no', $active_settlement->settlement_no)
                          ->orWhere('settlement_no', $active_settlement->id);
                    })
    ->with('details:id,pump_id')
    ->get()
    ->pluck('details.pump_id')
    ->filter()
    ->unique()
    ->values()
    ->toArray();

            $pump_nos = collect();
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

        $pump_operator_id = ! empty($active_settlement)
            ? (int) $active_settlement->pump_operator_id
            : (! empty(request()->pump_operator)
                ? (int) request()->pump_operator
                : (! empty($pump_operator_id) ? (int) $pump_operator_id : null));
        $payment_meter_sale_total = (! empty($shift_id) && ! empty($pump_operator_id))
            ? $this->getMeterSaleTotalByShift(
                (int) $business_id,
                $shift_id,
                $pump_operator_id
            )
            : 0.0;

        $payment_other_sale_total = ! empty($active_settlement) && ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum("sub_total")

            : 0.0;

        $payment_other_sale_discount = ! empty($active_settlement) && ! empty($active_settlement->other_sales)

            ? $active_settlement->other_sales->sum("sub_total")

            : 0.0;

        $payment_other_sale_total -= $payment_other_sale_discount;

        $payment_other_income_total = ! empty($active_settlement) && ! empty($active_settlement->other_incomes)

            ? $active_settlement->other_incomes->sum("sub_total")

            : 0.0;

        $payment_customer_payment_total = ! empty($active_settlement) && ! empty($active_settlement->customer_payments)

            ? $active_settlement->customer_payments->sum("sub_total")

            : 0.0;

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

        $message = $this->transactionUtil->getGeneralMessage(

            "general_message_pump_management_checkbox"

        );

        $closed_shift = PumpOperatorAssignment::where('business_id', $business_id)
            ->whereNotNull('close_date_and_time')
            ->latest('id')
            ->first();

        $meter_sales = [];

        if ($closed_shift && ! empty($shift_id) && ! empty($pump_operator_id)) {
         //   dd($settlement_no);
            // $pump_ids = $shift_assignments->pluck('pump_id')->filter()->unique();
            if (empty($active_settlement)) {
                // Only assign meters that were added via Settlement PD, not from Pumper Dashboard / Payments page
                PumpOperatorMeterSale::where('business_id', $business_id)
                    ->where('shift_id', $shift_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->when(true, function ($query) {
                        $this->onlyClosedPumpMeterSales($query);
                    })
                    ->whereNull('p_o_payment_id')
                    ->where(function ($q) {
                        $q->whereNull('settlement_no')
                            ->orWhere('settlement_no', '');
                    })
                    ->update([
                        'settlement_no' => $settlement_no
                    ]);
            }
            // Only show meters added via Settlement PD; exclude those from Pumper Dashboard / Payments page
            $meter_sales = PumpOperatorMeterSale::with([
                'details',
                'details.pump',
            ])
                ->where('business_id', $business_id)
                ->where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->when(true, function ($query) {
                    $this->onlyClosedPumpMeterSales($query);
                })
                /*
                 |--------------------------------------------------------------
                 | Exclude a payment-linked reading only when that payment was
                 | actually SETTLED - not merely because a link exists.
                 |--------------------------------------------------------------
                 |
                 | This was a flat ->whereNull('p_o_payment_id'), which dropped
                 | every reading the pumper had recorded. Shift 13 was the case
                 | that exposed it: two pumps worked, LAD2's reading present and
                 | unsettled, and the shift could not be settled at all.
                 |
                 | The exclusion is right in principle and is kept. A sale entered
                 | on the Payments page has already been handed over and is
                 | settled in Petro Direct, so counting it here as well would
                 | charge the operator TWICE - see the note in HandlesPdMeterSales.
                 |
                 | What changed is that the settled-ness is now TESTED rather than
                 | assumed from the presence of a link:
                 |
                 |     is_used = 1   settled elsewhere  -> still excluded
                 |     is_used = 0   settled nowhere    -> loaded; counting it
                 |                                        here is the first and
                 |                                        only time
                 |     no payment row                   -> loaded; nothing can
                 |                                        have settled a payment
                 |                                        that does not exist
                 |
                 | NOTE: the UPDATE further up this method keeps its plain
                 | whereNull. That one stamps settlement_no and is unrelated.
                 */
                ->where(function ($readingQuery) {
                    $readingQuery
                        ->whereNull('p_o_payment_id')
                        ->orWhereNotExists(function ($settledQuery) {
                            $settledQuery
                                ->select(DB::raw(1))
                                ->from('pump_operator_payments')
                                ->whereColumn(
                                    'pump_operator_payments.id',
                                    'pump_operator_meter_sales.p_o_payment_id'
                                )
                                ->where('pump_operator_payments.is_used', 1);
                        });
                })
                ->get();
            Log::info('meter sales in create method of Settlement PD:', $meter_sales->toArray());

            /* PD-PROBE - temporary */
            try {
                $probeRaw = \Modules\PetroPD\Entities\PumpOperatorMeterSale::where('business_id', $business_id)
                    ->where('shift_id', $shift_id)
                    ->where('pump_operator_id', $pump_operator_id)
                    ->get(['id', 'source', 'p_o_payment_id']);

                Log::warning('PD-PROBE', [
                    'business_id' => $business_id,
                    'shift_id' => $shift_id,
                    'pump_operator_id' => $pump_operator_id,
                    'returned_ids' => $meter_sales->pluck('id')->toArray(),
                    'raw_rows_for_shift' => $probeRaw->toArray(),
                    'has_new_code' => true,
                ]);
            } catch (\Throwable $e) {
                Log::warning('PD-PROBE failed', ['error' => $e->getMessage()]);
            }


            $pump_nos = Pump::whereIn(
                'id',
                PumpOperatorAssignment::where('shift_id', $shift_id)
                    ->where('business_id', $business_id)
                    ->when(! empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                        $query->where('pump_operator_id', $pump_operator_id);
                    })
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
                        ->where(
                            "pump_operator_assignments.status",
                            "close"
                        )->whereRaw('pump_operator_assignments.id = (
                             SELECT MAX(poa.id)
                             FROM pump_operator_assignments poa
                             WHERE poa.shift_id = pump_operator_other_sales.shift_id AND poa.status = "close"
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
            $settlement_credit_sale_payments = SettlementCreditSalePayment::where('settlement_credit_sale_payments.business_id', $active_settlement->business_id)
                ->leftJoin(
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

        // PDRW-007: Final safety before Blade render. create.blade.php must receive strings, not arrays.
        $shift_operator_map = collect($shift_numbers ?? [])
            ->mapWithKeys(function ($shiftOption, $shiftOptionId) use ($pump_operator_id) {
                $operatorId = is_array($shiftOption)
                    ? (int) ($shiftOption['pump_operator_id'] ?? 0)
                    : 0;

                return [(string) $shiftOptionId => $operatorId ?: (int) ($pump_operator_id ?? 0)];
            })
            ->toArray();
        $shift_numbers = $this->normalizePdShiftNumbersForBlade($shift_numbers ?? []);
        $shift_number = is_array($shift_number ?? null) || is_object($shift_number ?? null)
            ? $this->getPdShiftNumbersTextForBlade($shift_number)
            : (string) ($shift_number ?? '');

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
                "shift_payment_details",
                "pump_operator_id",
                "is_finishing_existing_settlement",
                "shift_id"

            )

        );
    }

    /**
     * Some common POS/transaction utilities expect these keys in the
     * session business array.  A few PD Settlement finalize flows run from
     * the Petro PD module without the full POS business array loaded, which
     * can throw "Undefined array key enable_product_expiry" while saving the
     * settlement.  Keep the defaults conservative and only fill missing keys.
     */

    /*
     * S 639: finalising a settlement runs inside the reconciler write context.
     *
     * SettlementCashPayment (and its sibling payment detail models) carry
     * RequiresReconcilerContext, which refuses any create or update unless a
     * settlement payment reconciler owns the write. This method writes those
     * rows directly, so finalising threw:
     *
     *   "SettlementCashPayment updates are restricted to SettlementPaymentReconciler"
     *
     * The guard had never fired before because Finalize could not reach the
     * server at all - the preview modal it hands off to never opened. Once that
     * was fixed the save arrived here and was refused.
     *
     * withBypass() is the reconciler's own sanctioned entry point for paths that
     * must write directly. It is depth-aware and releases in a finally, so it
     * cannot leak the context if this method throws.
     *
     * NOTE FOR THE NEXT CHANGE: this restores finalisation without weakening the
     * guard anywhere else - the context is open only for the duration of this
     * one call. The cleaner end state is for these writes to go through the
     * reconciler's upsertOne()/reconcileSet() rather than being written straight
     * from the controller, which would remove the need for a bypass at all.
     * That is a larger change and belongs with the single-source work.
     */
    public function store(Request $request, ContactController $contactController)
    {
        return \Modules\PetroPD\Services\SettlementPaymentReconciler::withBypass(function () use ($request, $contactController) {
            return $this->storeSettlement($request, $contactController);
        });
    }

    private function storeSettlement(Request $request, ContactController $contactController)
    {
        $finalizationLock = null;
        $transactionCommitted = false;
        $settlement = null;

        try {

            $denom_qty     = $request->denom_qty;
            $denom_value   = $request->denom_value;
            $denom_enabled = $request->denom_enabled;
            $denom_data    = [];

            $business_id = request()->session()->get("user.business_id");
            $this->ensureBusinessSessionDefaults($request, $business_id);

            $settings = \Modules\PetroPD\Entities\PumpOperator::where('business_id', $business_id)->whereNotNull('dashboard_settings')->first();
            $dashboard_settings = (! is_null($settings)) ? json_decode($settings->dashboard_settings, true) : [];
            $update_ledger = ($dashboard_settings['real_time_update_customer_ledger'] ?? 'no') === 'yes';
            $update_account = ($dashboard_settings['real_time_update_account_books'] ?? 'no') === 'yes';
            $pumper_ledger_update = ($dashboard_settings['pumper_ledger_update'] ?? 'no') === 'yes';

            if ($denom_enabled > 0) {
                $i = 0;
                foreach ($denom_qty as $one) {
                    $denom_data[] = [
                        "value" => $denom_value[$i],
                        "qty"   => $denom_qty[$i],
                    ];
                    $i++;
                }
            }

            $settlement_no = $request->settlement_no;
            $no_change     = $request->no_change;
            $business_id   = $request->session()->get("business.id");
            $this->ensureBusinessSessionDefaults($request, $business_id);

            // Try to find settlement by settlement_no first
            $settlement = Settlement::where("settlement_no", $request->settlement_no)
                ->where("business_id", $business_id)
                ->first();

            // If not found, try to find by ID (in case settlement_no is actually an ID)
            if (empty($settlement) && is_numeric($request->settlement_no)) {
                $settlement = Settlement::where("id", $request->settlement_no)
                    ->where("business_id", $business_id)
                    ->first();
            }

            // If no active settlement found, log and return a friendly validation error
            // IS1508: keep the Transaction Date selected on PD Settlement/Meter Sales
            // when opening Payment to Finalize/Add Payment and finalizing the settlement.
            if (! empty($settlement) && ! empty($request->transaction_date)) {
                try {
                    $selected_transaction_date = $this->moduleUtil->uf_date($request->transaction_date);
                } catch (\Exception $e) {
                    try {
                        $selected_transaction_date = \Carbon\Carbon::parse($request->transaction_date)->format('Y-m-d');
                    } catch (\Exception $e2) {
                        $selected_transaction_date = null;
                    }
                }

                if (! empty($selected_transaction_date) && $settlement->transaction_date != $selected_transaction_date) {
                    $settlement->transaction_date = $selected_transaction_date;
                    $settlement->save();
                }
            }

            if (empty($settlement)) {
                \Log::error('Settlement PD store: Settlement not found for finalize', [
                    'settlement_no'           => $request->settlement_no,
                    'business_id'             => $business_id,
                    'url'                     => $request->fullUrl(),
                    'input'                   => $request->all(),
                    'all_settlements_with_no' => Settlement::where('business_id', $business_id)->pluck('settlement_no', 'id')->toArray(),
                ]);

                return response()->json([
                    "success" => 0,
                    "msg"     => __("petropd::lang.settlement_not_found_for_finalize") ?: "Unable to finalize: settlement not found or already closed. Please ensure you have created the settlement before saving Payment to Finalize.",
                ]);
            }

            // PETROPD-PAYMENT-INTEGRITY-P4: server-side finalization lock.
            // Browser button disabling is not sufficient: two simultaneous HTTP
            // requests must never post the same settlement twice.
            try {
                $finalizationLock = \Illuminate\Support\Facades\Cache::lock(
                    'petropd:settlement-finalize:' . (int) $business_id . ':' . (int) $settlement->id,
                    300
                );

                if (! $finalizationLock->get()) {
                    return response()->json([
                        'success' => 0,
                        'msg' => 'This settlement is already being finalized by another request. Please wait and refresh the settlement list.',
                    ], 409);
                }
            } catch (\Throwable $lockException) {
                Log::error('PETROPD unable to acquire settlement finalization lock', [
                    'business_id' => $business_id,
                    'settlement_id' => $settlement->id,
                    'settlement_no' => $settlement->settlement_no,
                    'error' => $lockException->getMessage(),
                ]);

                return response()->json([
                    'success' => 0,
                    'msg' => 'Unable to obtain the settlement finalization safety lock. No financial posting was made. Please try again.',
                ], 503);
            }

            $edit = Settlement::where("id", $settlement->id)
                ->where("business_id", $business_id)
                ->where("status", 0)
                ->first();

            $pump_operator_total_other_sale = 0;
            $pump_operator_other_sales      = [];

            if ($request->shift_ids) {
                $shift_ids = is_array($request->shift_ids)
                    ? $request->shift_ids
                    : explode(",", $request->shift_ids);

                $pump_operator_total_other_sale = PumpOperatorOtherSale::join(
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
                    ->whereIn("pump_operator_other_sales.shift_id", $shift_ids);

                $pump_operator_total_other_sale = $pump_operator_total_other_sale->join(
                    "pump_operator_assignments",
                    function ($join) {
                        $join
                            ->on(
                                "pump_operator_assignments.shift_id",
                                "=",
                                "pump_operator_other_sales.shift_id"
                            )
                            ->where("pump_operator_assignments.status", "close")
                            ->whereRaw(
                                'pump_operator_assignments.id = (
                                    SELECT MAX(poa.id)
                                    FROM pump_operator_assignments poa
                                    WHERE poa.shift_id = pump_operator_other_sales.shift_id
                                    AND poa.status = "close"
                                )'
                            );
                    }
                );

                $pump_operator_other_sales = $pump_operator_total_other_sale
                    ->select("pump_operator_other_sales.*")
                    ->get();

                // Net after discount (must match other_sales and sell lines — was gross sub_total only).
                $pump_operator_total_other_sale = $pump_operator_other_sales->sum(function ($row) {
                    $sub = (float) ($row->sub_total ?? 0);
                    if (empty($row->discount_type)) {
                        return $sub;
                    }
                    if ($row->discount_type === "percentage") {
                        return max(0, $sub - ($sub * (float) ($row->discount ?? 0) / 100));
                    }
                    $off = (float) ($row->discount ?? 0);
                    if ($off <= 0) {
                        $off = (float) ($row->discount_amount ?? 0);
                    }

                    return max(0, $sub - $off);
                });
            } else {
                $shift_ids = [];
            }
            if (empty($shift_ids)) {
                $shift_ids = PumpOperatorMeterSale::where('business_id', $business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->when(true, function ($query) {
                        $this->onlyClosedPumpMeterSales($query);
                    })
                    ->where(function ($query) use ($settlement) {
                        $query->where('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', (string) $settlement->id);
                    })
                    ->pluck('shift_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
            if (empty($shift_ids)) {
                $shift_ids = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->where('settlement_id', $settlement->id)
                    ->pluck('shift_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->toArray();
            }
            // PETROPD-PAYMENT-SNAPSHOT-20260723: before any posting, verify every
            // Pumper Dashboard payment type against one immutable master payment and
            // the exact business/operator/Shift scope. A mismatch must never become
            // an artificial Shortage, Excess, invoice value or accounting posting.
            if (empty($settlement->pump_operator_id) || count((array) $shift_ids) !== 1) {
                return response()->json([
                    'success' => 0,
                    'msg' => 'Payment reconciliation stopped: finalization requires one Pump Operator and exactly one immutable Shift ID.',
                ], 422);
            }

            try {
                $pdPaymentSnapshotService = app(\Modules\PetroPD\Services\PetroPdSettlementPaymentSnapshotService::class);
                $legacyDraftLinkResult = $pdPaymentSnapshotService->normalizeLegacyDraftSettlementLinks(
                    (int) $business_id,
                    (int) $settlement->pump_operator_id,
                    (array) $shift_ids,
                    (int) $settlement->id,
                    (string) $settlement->settlement_no
                );

                // When this request itself repaired an old active ST-draft link,
                // the browser fingerprint was calculated before that safe repair.
                // Reconcile the freshly normalized authoritative rows instead of
                // reporting false payment drift. Normal requests still keep the
                // strict fingerprint comparison.
                $expectedPaymentFingerprint = empty($legacyDraftLinkResult['updated_payment_ids'])
                    ? ($request->filled('payment_snapshot_fingerprint')
                        ? (string) $request->payment_snapshot_fingerprint
                        : null)
                    : null;

                $pdPaymentSnapshotService->assertCanFinalize(
                        (int) $business_id,
                        (int) $settlement->pump_operator_id,
                        (array) $shift_ids,
                        (int) $settlement->id,
                        (string) $settlement->settlement_no,
                        $expectedPaymentFingerprint
                    );
            } catch (\RuntimeException $e) {
                return response()->json([
                    'success' => 0,
                    'msg' => $e->getMessage(),
                ], 422);
            }

            if (empty($pump_operator_other_sales) && ! empty($shift_ids)) {
                $pump_operator_other_sales = $this->getPumpOperatorOtherSalesForFinalization($business_id, $shift_ids);
                $pump_operator_total_other_sale = $pump_operator_other_sales->sum(function ($row) {
                    $sub = (float) ($row->sub_total ?? 0);
                    if (empty($row->discount_type)) {
                        return $sub;
                    }
                    if ($row->discount_type === "percentage") {
                        return max(0, $sub - ($sub * (float) ($row->discount ?? 0) / 100));
                    }
                    $off = (float) ($row->discount ?? 0);
                    if ($off <= 0) {
                        $off = (float) ($row->discount_amount ?? 0);
                    }

                    return max(0, $sub - $off);
                });
            }

            $pd_meter_sales_for_transaction = $this->getPdMeterSalesForFinalization($settlement, $shift_ids);
            $pd_meter_sales_total = $pd_meter_sales_for_transaction->sum(function ($sale) {
                return (float) ($sale->amount ?? $sale->balance ?? 0);
            });
            $regular_meter_sales_for_transaction = $this->getRegularMeterSalesForFinalization(
                $settlement,
                $pd_meter_sales_for_transaction,
                $shift_ids
            );

            // Adding daily collection to cash payments
            // Modified by Engr. Alex -- task 7889: use post-discount amounts
            // meter_sales.discount_amount = net after discount; other_sales.discount_amount = discount value
            $settlement_total =
                $regular_meter_sales_for_transaction->sum("discount_amount") +
                $pd_meter_sales_total +
                ($settlement->other_sales->sum("sub_total") - $settlement->other_sales->sum("discount_amount")) +
                $settlement->other_incomes->sum("sub_total") +
                $settlement->customer_payments->sum("sub_total") +
                $pump_operator_total_other_sale;

            // Get daily collections
            // Include both unlinked records AND records already linked to this settlement
            $shift_ids_for_collections = $shift_ids;
            if (empty($shift_ids_for_collections) && ! empty($settlement->work_shift)) {
                $decoded_work_shift = is_array($settlement->work_shift)
                    ? $settlement->work_shift
                    : json_decode($settlement->work_shift, true);
                $shift_ids_for_collections = is_array($decoded_work_shift)
                    ? $decoded_work_shift
                    : explode(",", $settlement->work_shift);
            }
            $shift_ids_for_collections = array_filter(array_map('intval', (array) $shift_ids_for_collections));

            if (empty($shift_ids_for_collections)) {
                \Log::warning('Settlement PD: Skipping daily collection updates due to missing shift_ids', [
                    'settlement_id'    => $settlement->id,
                    'settlement_no'    => $settlement->settlement_no,
                    'pump_operator_id' => $settlement->pump_operator_id,
                ]);
                $daily_collections = collect();
            } else {
                $daily_collections = DailyCollection::leftJoin(
                    "business_locations",
                    "daily_collections.location_id",
                    "business_locations.id"
                )
                    ->leftJoin("pump_operators", "daily_collections.pump_operator_id", "pump_operators.id")
                    ->leftJoin("users", "daily_collections.created_by", "users.id")
                    ->leftJoin("settlements", "daily_collections.settlement_id", "settlements.id")
                    ->where("daily_collections.business_id", $business_id)
                    ->where("daily_collections.type", "daily_collection")
                    // PETROPD-CASHSAVE-ROOTFIX-004: match Pumper Dashboard scope.
                    // The dashboard payment total is shift-based; restricting DailyCollection
                    // to the settlement pump_operator can drop valid cash from the same closed shift.
                    ->where(function ($q) use ($settlement) {
                        // Include unlinked records OR records already linked to this settlement
                        $q->where(function ($subQ) {
                            $subQ->whereNull("daily_collections.settlement_id")
                                ->whereNull("daily_collections.added_to_account");
                        })
                            ->orWhere("daily_collections.settlement_id", $settlement->id);
                    })
                    ->whereIn("daily_collections.shift_id", $shift_ids_for_collections)
                    ->select([
                        "daily_collections.*",
                        "business_locations.name as location_name",
                        "pump_operators.name as pump_operator_name",
                        "settlements.id as settlements_id",
                        "users.username as user",
                    ])
                    ->orderBy("daily_collections.id")
                    ->get();
            }

            Log::info('Settlement PD DailyCollections Query', [
                'settlement_id'           => $settlement->id,
                'settlement_no'           => $settlement->settlement_no,
                'pump_operator_id'        => $settlement->pump_operator_id,
                'shift_ids'               => $shift_ids,
                'daily_collections_found' => $daily_collections->count(),
            ]);

            // Check if there are actual changes being made (payments, collections, etc.)
            // Only block if settlement is already finalized AND no_change is set AND there are no pending changes
            $has_pending_changes = false;

            // Check if there are unlinked daily collections that need to be processed
            $unlinked_collections = $daily_collections->whereNull('settlement_id')->count();
            if ($unlinked_collections > 0) {
                $has_pending_changes = true;
            }

            // Check if there are payments that need to be processed
            $has_cash_payments = SettlementCashPayment::whereIn('settlement_no', array_values(array_filter([(string) $settlement->id, (string) $settlement->settlement_no], static fn ($k) => $k !== '')))->exists();
            $has_credit_sales  = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->id)
                        ->orWhere('settlement_no', $settlement->settlement_no)
                        ->orWhereNull('settlement_no');
                })
                ->exists();

            if ($has_cash_payments || $has_credit_sales) {
                $has_pending_changes = true;
            }

            // Only block if there are truly no changes AND no_change is explicitly set
            // If no_change is empty, allow the save to proceed (it might be a final save with payments)
            if ($settlement->is_edit == 0 && $settlement->status == 0 && ! empty($no_change) && ! $has_pending_changes) {
                return [
                    "success" => 0,
                    "msg"     => __("petropd::lang.no_change_performed"),
                ];
            }

            DB::beginTransaction();

            if (! empty($edit)) {
                $this->deletePreviouseTransactions($settlement->id, false, $no_change);
            }

            $business_locations = BusinessLocation::forDropdown($business_id);
            $default_location   = current(array_keys($business_locations->toArray()));

            /*
             * PETROPD-EXCESS-AP-ROOTFIX-001:
             * Materialize authoritative Pumper Dashboard shortage/excess rows
             * before the settlement relations are loaded for final posting.
             * This also repairs legacy rows already marked used/linked to this
             * settlement whose detail record was never created.
             */
            if (! empty($settlement->pump_operator_id) && ! empty($shift_ids)) {
                app(\Modules\PetroPD\Http\Controllers\AddPaymentController::class)
                    ->addDailyShortageExcess(
                        $settlement->id,
                        (int) $settlement->pump_operator_id,
                        (int) $business_id,
                        $shift_ids
                    );
            }

            // CRITICAL: Don't filter settlement by shift_ids in the reload - this causes the settlement to disappear
            // if there are no matching pump_operator_assignments for those shift_ids
            // The shift filtering should only apply to payments, not to the settlement itself
            // Use a simple reload without joins to avoid any filtering issues
            $settlement = Settlement::where("id", $settlement->id)
                ->where("business_id", $business_id)
                ->with([
                    "meter_sales",
                     "meter_sales_pd",
                     "meter_sales_pd.details",
                    "other_sales",
                    "other_incomes",
                    "customer_payments",
                    "cash_payments",
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
                ])
                ->first();

            // If the reload with joins/shift filters dropped the settlement, fail gracefully
            if (empty($settlement)) {
                \Log::error('Settlement PD store: Settlement disappeared after reload with shift filters', [
                    'original_settlement_no' => $request->settlement_no,
                    'business_id'            => $business_id,
                    'shift_ids'              => $shift_ids,
                    'url'                    => $request->fullUrl(),
                    'input'                  => $request->all(),
                ]);

                return [
                    "success" => 0,
                    "msg"     => __("petropd::lang.settlement_not_found_for_finalize") ?: "Unable to finalize: settlement not found with the selected shift(s). Please ensure the shift filter matches this settlement and try again.",
                ];
            }

            // CRITICAL: DO NOT filter meter_sales and credit_sale_payments in the store method
            // The store method should process ALL entries that were added to the settlement
            // Filtering should only happen in show/print methods for display purposes

            // Repair PumpOperatorMeterSale records that have integer ID instead of string settlement_no
            if (! empty($settlement)) {
                $this->repairMeterSalePdSettlementNo($settlement);
            }

            $pd_meter_sales_for_transaction = $this->getPdMeterSalesForFinalization($settlement, $shift_ids);
            $pd_meter_sales_total = $pd_meter_sales_for_transaction->sum(function ($sale) {
                return (float) ($sale->amount ?? $sale->balance ?? 0);
            });
            $regular_meter_sales_for_transaction = $this->getRegularMeterSalesForFinalization(
                $settlement,
                $pd_meter_sales_for_transaction,
                $shift_ids
            );

            // Fallback to load payments by both settlement ID (integer) and settlement_no (string)
            // This ensures payments saved with settlement->id are found during finalization
            if (! empty($settlement)) {
                // Reload credit sales if empty
                if ($settlement->credit_sale_payments->isEmpty()) {
                    $credit_sales = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->where(function ($q) use ($settlement) {
                            $q->where('settlement_no', $settlement->settlement_no)
                                ->orWhere('settlement_no', $settlement->id);
                        })
                        ->with('product')
                        ->get();
                    $settlement->setRelation('credit_sale_payments', $credit_sales);
                }

                // Reload card payments if empty
                if ($settlement->card_payments->isEmpty()) {
                    $card_payments_query = SettlementCardPayment::where(function ($q) use ($settlement) {
                        $q->where('settlement_card_payments.settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_card_payments.settlement_no', $settlement->id);
                    });

                    // CRITICAL FIX: Filter card payments by shift_id to prevent Shift 5 payments appearing in Shift 3 settlement
                    // When user selects Shift 5 → goes to Payment to Finalize → Back → selects Shift 3 → Finalize
                    // We must ONLY load card payments for the CURRENT shift selection (Shift 3), not old Shift 5 entries
                    if (! empty($shift_ids) && count($shift_ids) > 0) {
                        $card_payments_query->leftJoin('daily_cards', 'settlement_card_payments.daily_card_id', '=', 'daily_cards.id')
                            ->leftJoin('pump_operator_payments', function ($join) {
                                $join->on('pump_operator_payments.pump_operator_id', '=', 'daily_cards.pump_operator_id')
                                    ->whereRaw('pump_operator_payments.collection_form_no COLLATE utf8mb4_unicode_ci = daily_cards.collection_no COLLATE utf8mb4_unicode_ci')
                                    ->where('pump_operator_payments.payment_type', 'card')
                                    ->whereColumn('pump_operator_payments.payment_amount', 'daily_cards.amount');
                            })
                            ->where(function ($subQ) use ($shift_ids) {
                                $subQ->whereIn('pump_operator_payments.shift_id', $shift_ids);
                            })
                            ->select('settlement_card_payments.*'); // Only select settlement_card_payments columns
                    }

                    $card_payments = $card_payments_query->get();
                    $settlement->setRelation('card_payments', $card_payments);
                }

                // Reload cash payments if empty
                if ($settlement->cash_payments->isEmpty()) {
                    $cash_payments = SettlementCashPayment::where(function ($q) use ($settlement) {
                        $q->where('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', $settlement->id);
                    })->get();
                    $settlement->setRelation('cash_payments', $cash_payments);
                }

                // Reload loan payments if empty
                if ($settlement->loan_payments->isEmpty()) {
                    $loan_payments = SettlementLoanPayment::where(function ($q) use ($settlement) {
                        $q->where('settlement_no', $settlement->settlement_no)
                            ->orWhere('settlement_no', $settlement->id);
                    })->get();
                    $settlement->setRelation('loan_payments', $loan_payments);
                }
            }

            // dd('out', $daily_collections);
            $outstanding_payment = $settlement_total;

            // Create SettlementCashPayment records from DailyCollection (similar to direct settlement)
            // This ensures cash entries from daily collection are also converted to cash payments
            $daily_collection_cash_count = 0;
            foreach ($daily_collections as $row) {
                $amount = floatval($row->current_amount);
                // PETROPD-CASHSAVE-ROOTFIX-004: do not cap saved cash by outstanding sales/payment balance.
                // Pumper Dashboard shows the actual received cash; settlement save, print and accounting
                // must store/post that same received cash amount.
                $alloc  = $amount;

                // Try to link DailyCollection to a PumpOperatorPayment to avoid duplicate cash payments
                $linked_pump_payment = null;
                if (! empty($row->collection_form_no)) {
                    $linked_pump_payment = \Modules\PetroPD\Entities\PumpOperatorPayment::where('business_id', $business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->where('payment_type', 'cash')
                        ->where('collection_form_no', $row->collection_form_no)
                        ->where(function ($q) {
                            $q->whereNull('is_used')->orWhere('is_used', 0);
                        })
                        ->first();
                }

                // Check if SettlementCashPayment already exists for this daily collection
                // Check by amount + settlement_no to avoid duplicates
                // Note: DailyCollection doesn't have a direct link to SettlementCashPayment via customer_payment_id
                $existing_cash_payment = SettlementCashPayment::where('business_id', $business_id)
                    ->where('settlement_no', $settlement->id)
                    ->where('amount', $alloc)
                    ->first();

                if ($linked_pump_payment) {
                    $existing_by_pump_payment = SettlementCashPayment::where('business_id', $business_id)
                        ->where('settlement_no', $settlement->id)
                        ->where('customer_payment_id', $linked_pump_payment->id)
                        ->first();
                    if ($existing_by_pump_payment) {
                        $existing_cash_payment = $existing_by_pump_payment;
                    }
                }

                if (! $existing_cash_payment && $alloc > 0) {
                    // Get default customer (Walk-In Customer)
                    $walkin_customer = Contact::where('name', 'Walk-In Customer')
                        ->where('business_id', $business_id)
                        ->first();
                    $default_customer_id = $walkin_customer ? $walkin_customer->id : null;

                    // If no walk-in customer, get first customer from dropdown
                    if (! $default_customer_id) {
                        $customers           = Contact::customersDropdown($business_id, false, true, 'customer');
                        $default_customer_id = array_key_first($customers->toArray());
                    }

                    $settlement_cash_payment = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)->upsertOne(
                        $business_id,
                        (string) $settlement->id,
                        'settlement_cash_payments',
                        [
                            'amount'              => $alloc,
                            'customer_id'         => $default_customer_id,
                            'customer_payment_id' => ! empty($linked_pump_payment) ? $linked_pump_payment->id : null,
                            'pump_payment_id'     => ! empty($linked_pump_payment) ? $linked_pump_payment->id : null,
                            'note'                => 'Daily Collection',
                        ]
                    );

                    $daily_collection_cash_count++;

                    if (! empty($linked_pump_payment)) {
                        $linked_pump_payment->is_used       = 1;
                        $linked_pump_payment->parent_id     = $settlement_cash_payment->id;
                        $linked_pump_payment->settlement_no = $settlement->id;
                        $linked_pump_payment->save();
                    }
                } elseif (! empty($linked_pump_payment) && ! empty($existing_cash_payment) && empty($existing_cash_payment->customer_payment_id)) {
                    $existing_cash_payment = app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
                        ->editCashPayment($business_id, $existing_cash_payment->id, [
                            'customer_payment_id' => $linked_pump_payment->id,
                        ]);
                    $linked_pump_payment->is_used       = 1;
                    $linked_pump_payment->parent_id     = $existing_cash_payment->id;
                    $linked_pump_payment->settlement_no = $settlement->id;
                    $linked_pump_payment->save();
                }

                $row->update([
                    'settlement_id'      => $settlement->id,
                    'settlement_date'    => $settlement->transaction_date ?? $settlement->finish_date ?? date('Y-m-d'),
                    'balance_collection' => $alloc,
                    'added_to_account'   => 1,
                ]);
            }

            Log::info('Settlement PD DailyCollection Cash Payments Created', [
                'settlement_id'                 => $settlement->id,
                'settlement_no'                 => $settlement->settlement_no,
                'daily_collections_count'       => $daily_collections->count(),
                'created_from_daily_collection' => $daily_collection_cash_count,
            ]);

            $business      = Business::where("id", $settlement->business_id)->first();
            $pump_operator = PumpOperator::where("id", $settlement->pump_operator_id)->first();

            // Include both direct meter_sales and PD meter_sales in totals
            // Modified by Engr. Alex -- task 7889: use post-discount amounts
            // meter_sales.discount_amount = net after discount; other_sales.discount_amount = discount value
            $total_sales_amount =
                $regular_meter_sales_for_transaction->sum('discount_amount') +
                $pd_meter_sales_total +
                ($settlement->other_sales->sum("sub_total") - $settlement->other_sales->sum("discount_amount")) +
                $pump_operator_total_other_sale;

            // Store gross pre-discount total for reference in transactions.discount_amount
            $total_sales_discount_amount =
                $regular_meter_sales_for_transaction->sum('sub_total') +
                $pd_meter_sales_total +
                $settlement->other_sales->sum("sub_total");

            // $pump_ids = $settlement->meter_sales_pd->pluck("pump_id")->unique()->toArray();

$pump_ids = $pd_meter_sales_for_transaction
    ->pluck('details')     // get details collections
    ->flatten()            // flatten nested collections
    ->pluck('pump_id')     // now pluck pump_id
    ->filter()             // remove nulls
    ->unique()
    ->values()
    ->toArray();
   // dd($pump_ids);
            $pumps = Pump::whereIn("id", $pump_ids)
                ->select("pump_name")
                ->pluck("pump_name")
                ->toArray() ?? [];

            $subscription           = Subscription::active_subscription($business_id);
            $monthly_max_sale_limit = $subscription->package->monthly_max_sale_limit;

            $startOfMonth = \Carbon::now()->startOfMonth()->toDateString();
            $endOfMonth   = \Carbon::now()->endOfMonth()->toDateString();

            $current_monthly_sale = DB::table("transactions")
                ->select(DB::raw("sum(final_total) as total"))
                ->where("business_id", $business_id)
                ->whereIn("type", ["sell", "property_sell"])
                ->whereBetween("transaction_date", [$startOfMonth, $endOfMonth])
                ->groupBy("business_id")
                ->first();

            $current_monthly_sale = is_null($current_monthly_sale)
                ? 0
                : (float) $current_monthly_sale->total;

            $current_monthly_sale += $total_sales_amount;

            if ($current_monthly_sale > $monthly_max_sale_limit) {
                return [
                    "success" => 0,
                    "msg"     => __("lang_v1.monthly_max_sale_limit_exceeded", [
                        "monthly_max_sale_limit" => $monthly_max_sale_limit,
                    ]),
                ];
            }

            $transaction = $this->createTransaction(
                $settlement,
                $total_sales_amount,
                null,
                $settlement->pump_operator_id,
                "sell",
                "settlement",
                $settlement_no,
                null,
                0,
                $total_sales_discount_amount
            );

            $sell_transaction = $transaction;
            $tax_amt          = 0;


            // Ensure regular meter sales also create sell lines/account entries
            foreach ($regular_meter_sales_for_transaction as $m_sale) {
                $fuel_tank_id = null;
                if (!empty($m_sale->pump_id)) {
                    $pump = Pump::find($m_sale->pump_id);
                    if (!empty($pump)) {
                        $fuel_tank_id = $pump->fuel_tank_id;
                    }
                }


                /*
                 * IS1994: tag this as a meter sale so stock is reduced.
                 *
                 * This passed null. In createSellTransactions() the stock
                 * decrement sits behind
                 *   "if ($product->enable_stock && ! empty($is_other_sale))"
                 * so a null meant fuel meter sales wrote their sell line but
                 * never reduced variation_location_details.qty_available -
                 * which is the column Stock Center's Available shows. Other
                 * sales pass true and operator other sales pass a string, which
                 * is why only fuel was affected.
                 *
                 * $sale->qty is the SOLD quantity; testing_qty lives in its own
                 * column and is deliberately excluded.
                 */
                // meter_sale already contains product_id and qty fields
                $sell_line = $this->createSellTransactions(
                    $transaction,
                    $m_sale,
                    $business_id,
                    $default_location,
                    $fuel_tank_id,
                    'meter_sale'
                );
            }

            // Process pump_operator_meter_sales (meter_sales_pd) with their details
            // These are critical for displaying meter sales line items in account books
            foreach ($pd_meter_sales_for_transaction as $pd_sale) {
                // Process each detail record within the meter sale
                foreach ($pd_sale->details as $meter_sale_detail) {
                    $pump_obj = Pump::find($meter_sale_detail->pump_id);
                    $fuel_tank_id = ! empty($pump_obj) ? $pump_obj->fuel_tank_id : null;

                    // Mapping properties for createSellTransactions expected by other systems
                    $meter_sale_detail->product_id = ! empty($pd_sale->product_id)
                        ? $pd_sale->product_id
                        : (! empty($pump_obj) ? $pump_obj->product_id : null);
                    $meter_sale_detail->qty = $meter_sale_detail->sold_qty;
                    $meter_sale_detail->price = $meter_sale_detail->unit_price;
                    $meter_sale_detail->discount_type = $meter_sale_detail->discount_type ?? 'fixed';
                    $meter_sale_detail->discount = $meter_sale_detail->discount ?? 0;


                    // Create sell line transaction for each meter sale detail
                    // This ensures each detail appears as a line item in account books
                    // IS1994: tagged 'meter_sale' so the sold qty is deducted from
                    // stock. See the note on the regular meter sale call above.
                    $sell_line = $this->createSellTransactions(
                        $transaction,
                        $meter_sale_detail,
                        $business_id,
                        $default_location,
                        $fuel_tank_id,
                        'meter_sale'
                    );
                }

                // Update the pump_operator_meter_sale record with transaction link
                // This links the entire pump operator meter sale to the settlement transaction
                PumpOperatorMeterSale::where("id", $pd_sale->id)->update([
                    "transaction_id" => $transaction->id,
                ]);
            }

            // CRITICAL FIX: Also process pump_operator_meter_sales that may exist outside meter_sales_pd relationship
            // This ensures meter sales details appear in Account Books (Finished Goods, COGS, Sales Income)
            // Same logic as Other Sales to handle any additional pump_operator_meter_sales
            $additional_meter_sales = PumpOperatorMeterSale::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->whereNull('p_o_payment_id')
                ->when(true, function ($query) {
                    $this->onlyClosedPumpMeterSales($query);
                })
                ->whereNotIn('id', $pd_meter_sales_for_transaction->pluck('id')->filter()->all())
                ->when(! empty($shift_ids), function ($query) use ($shift_ids) {
                    $query->whereIn('shift_id', $shift_ids);
                })
                ->where(function ($q) use ($transaction) {
                    $q->whereNull('transaction_id')
                        ->orWhere('transaction_id', '!=', $transaction->id);
                })
                ->get();

            foreach ($additional_meter_sales as $pump_meter_sale) {
                // Check if this meter sale already has details processed
                if (!empty($pump_meter_sale->details) && $pump_meter_sale->details->count() > 0) {
                    // Process each detail within this meter sale
                    foreach ($pump_meter_sale->details as $meter_sale_detail) {
                        $pump_obj = Pump::find($meter_sale_detail->pump_id);
                        $fuel_tank_id = !empty($pump_obj) ? $pump_obj->fuel_tank_id : null;

                        // Mapping properties for createSellTransactions
                        $meter_sale_detail->product_id = ! empty($pump_meter_sale->product_id)
                            ? $pump_meter_sale->product_id
                            : (! empty($pump_obj) ? $pump_obj->product_id : null);
                        $meter_sale_detail->qty = $meter_sale_detail->sold_qty;
                        $meter_sale_detail->price = $meter_sale_detail->unit_price;
                        $meter_sale_detail->discount_type = $meter_sale_detail->discount_type ?? 'fixed';
                        $meter_sale_detail->discount = $meter_sale_detail->discount ?? 0;

                        // Create sell line for this detail
                        // IS1994: tagged 'meter_sale' so the sold qty is deducted
                        // from stock, same as the two call sites above.
                        $sell_line = $this->createSellTransactions(
                            $transaction,
                            $meter_sale_detail,
                            $business_id,
                            $default_location,
                            $fuel_tank_id,
                            'meter_sale'
                        );
                    }

                    // Link the meter sale to the transaction
                    PumpOperatorMeterSale::where("id", $pump_meter_sale->id)->update([
                        "transaction_id" => $transaction->id,
                    ]);
                }
            }

            foreach ($settlement->other_sales as $other_sale) {
                $getOtherSale = OtherSale::where("id", $other_sale->id)->first();

                if ($getOtherSale->transaction_id == null || $getOtherSale->transaction_id != $transaction->id) {
                    $sell_line = $this->createSellTransactions(
                        $transaction,
                        $other_sale,
                        $business_id,
                        $default_location,
                        null,
                        true
                    );

                    OtherSale::where("id", $other_sale->id)->update([
                        "transaction_id" => $transaction->id,
                    ]);
                }
            }

            foreach ($pump_operator_other_sales as $pump_operator_other_sales_item) {
                if (
                    $pump_operator_other_sales_item->transaction_id == null ||
                    $pump_operator_other_sales_item->transaction_id != $transaction->id
                ) {
                    $sell_line = $this->createSellTransactions(
                        $transaction,
                        $pump_operator_other_sales_item,
                        $business_id,
                        $default_location,
                        null,
                        "pump_operator_other_sale"
                    );

                    PumpOperatorOtherSale::where("id", $pump_operator_other_sales_item->id)
                        ->update(["transaction_id" => $transaction->id]);
                }
            }

            foreach ($settlement->other_incomes as $other_income) {
                $sell_line = $this->createSellTransactions(
                    $transaction,
                    $other_income,
                    $business_id,
                    $default_location,
                    null,
                    null
                );

                OtherIncome::where("id", $other_income->id)->update([
                    "transaction_id" => $transaction->id,
                ]);
            }

            /* map purchase sell lines */
            $sell_lines_before = \App\TransactionSellLine::where('transaction_id', $transaction->id)->get();

            $this->createStockAccountTransactions($transaction); // @eng 11/2 1700

            $acct_txns_after = \App\AccountTransaction::where('transaction_id', $transaction->id)->get();

            $this->mapSellPurchaseLines(
                $business_id,
                $transaction,
                $settlement
            );

            $account_id = $this->transactionUtil->account_exist_return_id("Accounts Receivable");

            // Auto-create SettlementCashPayment records from pumper dashboard cash payments
            // that haven't been linked to a SettlementCashPayment yet
            $work_shifts = ! empty($shift_ids) ? $shift_ids : $settlement->work_shift;
            if (is_string($work_shifts)) {
                $decoded_work_shifts = json_decode($work_shifts, true);
                $work_shifts         = is_array($decoded_work_shifts) ? $decoded_work_shifts : explode(',', $work_shifts);
            }
            if (! is_array($work_shifts)) {
                $work_shifts = [];
            }
            $work_shifts = array_filter(array_map('intval', $work_shifts));

            // Get default customer (Walk-In Customer)
            $walkin_customer = Contact::where('name', 'Walk-In Customer')
                ->where('business_id', $business_id)
                ->first();
            $default_customer_id = $walkin_customer ? $walkin_customer->id : null;

            // Get cash payments from pumper dashboard
            // Check both by shift_id (if work_shifts exist) AND by settlement_no (if already linked)
            $unused_cash_payments_query = \Modules\PetroPD\Entities\PumpOperatorPayment::where('business_id', $business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where('payment_type', 'cash')
                ->where(function ($q) {
                    $q->whereNull('is_used')->orWhere('is_used', 0);
                });

            // If work_shifts exist, filter by shift_id; otherwise, check by settlement_no
            if (! empty($work_shifts)) {
                $unused_cash_payments_query->where(function ($q) use ($work_shifts, $settlement) {
                    $q->whereIn('shift_id', $work_shifts)
                        ->orWhere('settlement_no', $settlement->id);
                });
            } else {
                // If work_shifts is empty, check for payments already linked to this settlement
                $unused_cash_payments_query->where(function ($q) use ($settlement) {
                    $q->whereNull('settlement_no')
                        ->orWhere('settlement_no', $settlement->id);
                });
            }

            $unused_cash_payments = $unused_cash_payments_query->get();

            Log::info('Settlement PD PumpOperatorPayment Cash Lookup', [
                'settlement_id'              => $settlement->id,
                'settlement_no'              => $settlement->settlement_no,
                'pump_operator_id'           => $settlement->pump_operator_id,
                'work_shifts'                => $work_shifts,
                'shift_ids'                  => $shift_ids ?? [],
                'work_shifts_count'          => count($work_shifts),
                'unused_cash_payments_found' => $unused_cash_payments->count(),
            ]);

            // Create SettlementCashPayment records for each unused cash payment
            $created_count = 0;
            foreach ($unused_cash_payments as $pump_payment) {
                // Check if SettlementCashPayment already exists for this pump_payment
                // Check with both settlement_no formats (string and integer)
                // Primary check: customer_payment_id (links to PumpOperatorPayment ID)
                // Check if SettlementCashPayment already exists for this PumpOperatorPayment
                // Use customer_payment_id (links to PumpOperatorPayment ID)
                $existing = SettlementCashPayment::where('business_id', $business_id)
                    ->where('settlement_no', $settlement->id) // Use ID (integer) to match relationship
                    ->where('customer_payment_id', $pump_payment->id)
                    ->first();

                if (! $existing) {
                    $existing_daily_collection = SettlementCashPayment::where('business_id', $business_id)
                        ->where('settlement_no', $settlement->id)
                        ->whereNull('customer_payment_id')
                        ->where('amount', $pump_payment->payment_amount)
                        ->where(function ($q) {
                            $q->whereNull('note')
                                ->orWhere('note', 'LIKE', '%Daily Collection%');
                        })
                        ->first();

                    if ($existing_daily_collection) {
                        $existing_daily_collection->customer_payment_id = $pump_payment->id;
                        $existing_daily_collection->save();
                        $pump_payment->is_used       = 1;
                        $pump_payment->parent_id     = $existing_daily_collection->id;
                        $pump_payment->settlement_no = $settlement->id;
                        $pump_payment->save();
                        continue;
                    }

                    // Use settlement ID (integer) to match the relationship definition
                    // The relationship expects: SettlementCashPayment.settlement_no = Settlement.id
                    $settlement_cash_payment = app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)->upsertOne(
                        $business_id,
                        (string) $settlement->id,
                        'settlement_cash_payments',
                        [
                            'amount'              => $pump_payment->payment_amount,
                            'customer_id'         => $default_customer_id,
                            'customer_payment_id' => $pump_payment->id,
                            'pump_payment_id'     => $pump_payment->id,
                            'note'                => $pump_payment->note ?? '',
                        ]
                    );

                    $created_count++;

                    // Mark pump payment as used
                    $pump_payment->is_used       = 1;
                    $pump_payment->parent_id     = $settlement_cash_payment->id;
                    $pump_payment->settlement_no = $settlement->id;
                    $pump_payment->save();
                }
            }

            Log::info('Settlement PD Auto-created Cash Payments', [
                'settlement_id'              => $settlement->id,
                'settlement_no'              => $settlement->settlement_no,
                'unused_cash_payments_found' => $unused_cash_payments->count(),
                'created_count'              => $created_count,
            ]);

            // Reload cash_payments to include the newly created ones
            if ($created_count > 0) {
                $settlement->load('cash_payments');
            }

            // Ensure cash_payments are loaded - reload with both settlement_no formats to catch any manually added ones
            // This MUST happen AFTER creating SettlementCashPayment from DailyCollection and PumpOperatorPayment
            $all_cash_payments = SettlementCashPayment::where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->id)             // Primary: match by ID (integer)
                    ->orWhere('settlement_no', $settlement->settlement_no); // Fallback: match by string
            })
                ->where('business_id', $business_id)
                ->get();

            Log::info('Settlement PD Cash Payments Reload', [
                'settlement_id'         => $settlement->id,
                'settlement_no'         => $settlement->settlement_no,
                'found_count'           => $all_cash_payments->count(),
                'cash_payments_details' => $all_cash_payments->map(function ($cp) {
                    return ['id' => $cp->id, 'amount' => $cp->amount, 'settlement_no' => $cp->settlement_no, 'settlement_no_type' => gettype($cp->settlement_no)];
                })->toArray(),
            ]);

            // Always set the relation, even if empty, to ensure we have the latest data
            $settlement->setRelation('cash_payments', $all_cash_payments);

            $this->createSettlementCardPaymentsFromPumpPayments($settlement, $business_id, $work_shifts);

            // PETROPD-CASHSAVE-ROOTFIX-004: make saved settlement cash match Pumper Dashboard cash total.
            // The Pumper Dashboard total comes from pump_operator_payments. Earlier code only
            // picked unused/unlinked cash payments, so cash already marked used or linked by
            // another path could be missed. That caused the saved settlement and Accounting
            // Cash Account to show a reduced cash amount.
            $this->ensurePdSettlementCashMatchesPumperDashboard($settlement, $business_id, $work_shifts);

            // PETROPD-CASHSAVE-ROOTFIX-005:
            // Final safety reconciliation requested by user: take the Total Amount submitted
            // from the Payment to Finalize form as the authority, then derive the expected
            // cash balance after non-cash payments. This prevents any backend recalc/filter
            // from reducing cash after the user already saw the correct total before save.
            $this->ensurePdSettlementCashMatchesSubmittedFinalizeTotal($settlement, $business_id, $request);

            // Reload cash_payments again after reconciliation, so the Accounting Cash Account
            // posting loop below uses the corrected amount in the same save request.
            $all_cash_payments = SettlementCashPayment::where(function ($q) use ($settlement) {
                $q->where('settlement_no', $settlement->id)
                    ->orWhere('settlement_no', $settlement->settlement_no);
            })
                ->where('business_id', $business_id)
                ->get();
            $settlement->setRelation('cash_payments', $all_cash_payments);

            $cash_note = "";

            // Log cash payments count for debugging
            Log::info('Settlement PD Cash Payments Processing', [
                'settlement_no'       => $settlement_no,
                'settlement_id'       => $settlement->id,
                'cash_payments_count' => $settlement->cash_payments->count(),
                'cash_payments'       => $settlement->cash_payments->map(function ($cp) {
                    return ['id' => $cp->id, 'amount' => $cp->amount, 'settlement_no' => $cp->settlement_no];
                })->toArray(),
            ]);

            foreach ($settlement->cash_payments as $cash_payment) {
                $i = 0;

                // S282-005: Account Book Date must be the system-entered/posting date.
                // The selected settlement transaction date remains on transactions.transaction_date
                // and is displayed separately in the Transaction Date column.
                $cash_account_id = $this->transactionUtil->account_exist_return_id("Cash");
                $operation_date  = \Carbon\Carbon::parse($settlement->transaction_date)->format('Y-m-d H:i:s');

                // IDEMPOTENCY TOKEN: Structured reference with full context
                // Format: [SETL:{settlement_no}|BIZ:{business_id}|ACCT:{account_id}|CP:{cash_payment_id}]
                $idempotency_token = '[SETL:' . $settlement_no . '|BIZ:' . $business_id . '|ACCT:' . $cash_account_id . '|CP:' . $cash_payment->id . ']';

                // CRITICAL: Check for existing AccountTransaction with this idempotency token BEFORE creating any new records
                // Check by multiple criteria to ensure we catch duplicates
                /*
                 |--------------------------------------------------------------
                 | LA-1164 / LA-1160: duplicate and merged cash rows in the book.
                 |--------------------------------------------------------------
                 |
                 | Two defects were in the guard below.
                 |
                 | 1. THE FALLBACK MATCH WAS UNANCHORED.
                 |    It searched note LIKE '%CP:<id>%'. The token written into the
                 |    note ends with the id followed by ']', so '%CP:5%' also matches
                 |    the row for CP:52, CP:50, CP:512 and so on.
                 |
                 |    When it matched the WRONG payment's row, this block took the
                 |    edit branch and overwrote that row's amount with this payment's
                 |    amount, then `continue`d - so this payment never got a row of
                 |    its own and two cash payments collapsed onto one line. That is
                 |    exactly the reported "when multiple payments are edited they
                 |    are shown as a single cash payment".
                 |
                 |    Anchoring on '|CP:<id>]' makes the id exact: CP:5 can no longer
                 |    match CP:52.
                 |
                 | 2. IT COULD MISS ITS OWN ROW AND CREATE A DUPLICATE.
                 |    Both branches of the old match depend on the note text, which
                 |    embeds $settlement_no and $cash_account_id. If either differs
                 |    from the run that wrote the row - a draft renumbered on
                 |    finalize, or "Cash" resolving to a different account id - the
                 |    guard finds nothing and creates a SECOND accounting row while
                 |    the original survives. That is the original and edited amount
                 |    appearing side by side.
                 |
                 |    settlement_cash_payments.transaction_id is now written when the
                 |    row is created (further down), and is checked first here. It is
                 |    a real foreign key and cannot drift with the note text.
                 |
                 | The ordering is fixed too: ->first() had no order, so repeated
                 | runs could act on different rows.
                 */
                $existing_entry = null;

                if (Schema::hasColumn('settlement_cash_payments', 'transaction_id')
                    && ! empty($cash_payment->transaction_id)) {
                    $existing_entry = AccountTransaction::where('business_id', $business_id)
                        ->where('transaction_id', $cash_payment->transaction_id)
                        ->where('type', 'debit')
                        ->where(function ($q) {
                            $q->where('sub_type', 'cash_payment')
                                ->orWhere('sub_type', 'settlement_cash_payment');
                        })
                        ->orderBy('id')
                        ->first();
                }

                if (! $existing_entry) {
                    $existing_entry = AccountTransaction::where('business_id', $business_id)
                        ->where('account_id', $cash_account_id)
                        ->where('type', 'debit')
                        ->where(function ($q) {
                            $q->where('sub_type', 'cash_payment')
                                ->orWhere('sub_type', 'settlement_cash_payment');
                        })
                        // Anchored on both sides of the id - see note above.
                        ->where('note', 'LIKE', '%|CP:' . $cash_payment->id . ']%')
                        ->orderBy('id')
                        ->first();
                }

                if ($existing_entry) {
                    /*
                     | LA-1164: backfill the canonical link so every later run -
                     | and SettlementPaymentEditService's amount cascade - resolves
                     | this row by foreign key instead of by parsing note text.
                     */
                    if (Schema::hasColumn('settlement_cash_payments', 'transaction_id')
                        && empty($cash_payment->transaction_id)
                        && ! empty($existing_entry->transaction_id)) {
                        $cash_payment->transaction_id = $existing_entry->transaction_id;
                        $cash_payment->save();
                    }

                    // EDIT CASE: If amount changed, update existing entry instead of creating duplicate
                    if (abs($existing_entry->amount - $cash_payment->amount) > 0.001) {
                        $existing_entry->update([
                            'amount'         => $cash_payment->amount,
                            'operation_date' => $operation_date,
                        ]);

                        /*
                         | LA-1164: the parent Transaction was left holding the old
                         | amount. The account book reads AccountTransaction, so the
                         | stale figure was not visible here - but every report that
                         | reads transactions.final_total disagreed with the book
                         | until the next edit. Updated in the same place so the
                         | three stay consistent.
                         */
                        if (! empty($existing_entry->transaction_id)) {
                            Transaction::where('id', $existing_entry->transaction_id)->update([
                                'final_total'      => $cash_payment->amount,
                                'total_before_tax' => $cash_payment->amount,
                            ]);
                        }

                        // Also update linked ContactLedger
                        \App\ContactLedger::where('transaction_id', $existing_entry->transaction_id)
                            ->where('sub_type', 'cash_payment')
                            ->update([
                                'amount' => $cash_payment->amount,
                                'operation_date' => $operation_date,
                            ]);
                        Log::info('Settlement PD: Updated existing cash entry (amount changed)', [
                            'settlement_no'     => $settlement_no,
                            'cash_payment_id'   => $cash_payment->id,
                            'old_amount'        => $existing_entry->amount,
                            'new_amount'        => $cash_payment->amount,
                            'idempotency_token' => $idempotency_token,
                        ]);
                    } else {
                        Log::info('Settlement PD: Idempotency guard - skipping duplicate cash entry', [
                            'settlement_no'     => $settlement_no,
                            'cash_payment_id'   => $cash_payment->id,
                            'amount'            => $cash_payment->amount,
                            'idempotency_token' => $idempotency_token,
                            'existing_entry_id' => $existing_entry->id,
                        ]);
                    }
                    continue; // Skip new creation - entry already exists or was updated
                }

                $cash_transaction = $this->createTransaction(
                    $settlement,
                    $cash_payment->amount,
                    $cash_payment->customer_id,
                    null,
                    "settlement",
                    "cash_payment",
                    $settlement_no
                );

                // Create Transaction Payment for cash
                // Use settlement's transaction_date directly to ensure correct date

                $cash_transaction_payment = TransactionPayment::create([
                    'transaction_id' => $cash_transaction->id,
                    'business_id'    => $business_id,
                    'amount'         => $cash_payment->amount,
                    'method'         => 'cash',
                    'paid_on'        => $operation_date,
                    'created_by'     => $cash_transaction->created_by,
                    'paid_in_type'   => 'settlement',
                    'payment_for'    => $cash_transaction->contact_id,
                ]);

                // DOUBLE CHECK: After creating TransactionPayment, check again by transaction_payment_id
                // This catches cases where the same TransactionPayment was used
                $existing_by_tp = AccountTransaction::where('business_id', $business_id)
                    ->where('account_id', $cash_account_id)
                    ->where('transaction_payment_id', $cash_transaction_payment->id)
                    ->where('type', 'debit')
                    ->where(function ($q) {
                        $q->where('sub_type', 'cash_payment')
                            ->orWhere('sub_type', 'settlement_cash_payment');
                    })
                    ->first();

                if ($existing_by_tp) {
                    Log::info('Settlement PD: Duplicate detected by transaction_payment_id - skipping', [
                        'settlement_no'          => $settlement_no,
                        'cash_payment_id'        => $cash_payment->id,
                        'transaction_payment_id' => $cash_transaction_payment->id,
                        'existing_entry_id'      => $existing_by_tp->id,
                    ]);
                    continue; // Skip - duplicate detected
                }

                // Create Account Transaction for Cash Account (Debit - Money In)

                Log::info('Settlement PD Cash Account Lookup', [
                    'settlement_no'         => $settlement_no,
                    'cash_payment_id'       => $cash_payment->id,
                    'cash_payment_amount'   => $cash_payment->amount,
                    'cash_account_id_found' => $cash_account_id,
                    'business_id'           => $business_id,
                    'operation_date'        => $operation_date,
                ]);

                if (empty($cash_account_id)) {
                    // Log warning if cash account not found
                    Log::warning('Cash account not found for Settlement PD cash payment', [
                        'settlement_no'   => $settlement_no,
                        'cash_payment_id' => $cash_payment->id,
                        'amount'          => $cash_payment->amount,
                        'business_id'     => $business_id,
                    ]);
                } else {
                    // Build note with idempotency token included
                    $account_note = 'Settlement No: ' . $settlement_no . ' | Cash Payment | ' . $idempotency_token;
                    if (! empty($cash_payment->note)) {
                        $account_note .= ' | ' . $cash_payment->note;
                    }

                    $account_transaction_data = [
                        'amount'                 => $cash_payment->amount,
                        'account_id'             => $cash_account_id,
                        'business_id'            => $business_id,
                        'type'                   => 'debit',
                        'sub_type'               => 'cash_payment',
                        'operation_date'         => $operation_date,
                        'created_by'             => $cash_transaction->created_by,
                        'transaction_id'         => $cash_transaction->id,
                        'transaction_payment_id' => $cash_transaction_payment->id,
                        'note'                   => $account_note,
                        'contact_id'             => $cash_transaction->contact_id,
                    ];

                    Log::info('Creating Account Transaction for Cash Payment', $account_transaction_data);

                    $is_rt = ! empty($cash_payment->pump_payment_id);
                    $created_at = null;
                    if (! ($is_rt && $update_account)) {
                        $created_at = AccountTransaction::createAccountTransaction($account_transaction_data);
                    }

                    /*
                     | LA-1164: record the canonical link on the cash payment row.
                     |
                     | Nothing wrote settlement_cash_payments.transaction_id before,
                     | which is why both the idempotency guard above and the amount
                     | cascade in SettlementPaymentEditService had to identify this
                     | row by parsing note text. With the foreign key stored, an
                     | edit updates the row that already exists instead of failing
                     | to find it and leaving a second one behind.
                     */
                    if (Schema::hasColumn('settlement_cash_payments', 'transaction_id')
                        && empty($cash_payment->transaction_id)) {
                        $cash_payment->transaction_id = $cash_transaction->id;
                        $cash_payment->save();
                    }

                    // Create ledger entry for customer
                    if (! (($is_rt && $update_ledger) || ($is_rt && $pumper_ledger_update))) {
                        $ledger_data = $account_transaction_data;
                        $ledger_data['type'] = 'credit'; // Customer is credited
                        \App\ContactLedger::createContactLedger($ledger_data);
                    }

                    $this->la1062EnsureWalkInLedgerPair(
                        $cash_transaction,
                        $cash_transaction_payment->id,
                        $cash_account_id,
                        $cash_payment->amount,
                        $operation_date,
                        $cash_transaction->created_by,
                        $account_note ?? ('Settlement No: ' . $settlement_no . ' | Cash Payment'),
                        null
                    );

                    Log::info('Account Transaction and Ledger Created', [
                        'account_transaction_id' => $created_at->id ?? 'unknown',
                        'account_id'             => $cash_account_id,
                        'amount'                 => $cash_payment->amount,
                        'idempotency_token'      => $idempotency_token,
                    ]);
                }

                $cash_note .= ! empty($cash_payment->note)
                    ? "Note " . $i++ . ": " . $cash_payment->note . "\n"
                    : "";
            }

            foreach ($settlement->customer_loans as $customer_loan) {
                $customer_loan_transaction = $this->createTransaction(
                    $settlement,
                    $customer_loan->amount,
                    $customer_loan->customer_id,
                    null,
                    "settlement",
                    "customer_loan",
                    $settlement_no,
                    null,
                    0,
                    0.0,
                    $customer_loan->note
                );

                $type       = "debit";
                $account_id = $this->transactionUtil->account_exist_return_id("Accounts Receivable");

                $this->createAccountTransaction(
                    $customer_loan_transaction,
                    $type,
                    $account_id,
                    $customer_loan_transaction->id,
                    "null",
                    null,
                    $customer_loan->amount,
                    false,
                    $customer_loan->note
                );

                $type       = "credit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $customer_loan_transaction,
                    $type,
                    $account_id,
                    $customer_loan_transaction->id,
                    "null",
                    null,
                    $customer_loan->amount,
                    false,
                    $customer_loan->note
                );
            }

            $loan_note = "";

            foreach ($settlement->loan_payments as $loan_payment) {
                $i = 0;

                // this transaction will use in report to show amounts
                $loan_transaction_payment = $this->createTransaction(
                    $settlement,
                    $loan_payment->amount,
                    null,
                    null,
                    "settlement",
                    "loan_payment",
                    $settlement_no
                );

                $loan_note .= ! empty($loan_payment->note)
                    ? "Note " . $i++ . ": " . $loan_payment->note . "\n"
                    : "";

                $type       = "debit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $loan_transaction_payment,
                    $type,
                    $account_id,
                    $loan_transaction_payment->id,
                    "null",
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment->note
                );

                $type       = "credit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $loan_transaction_payment,
                    $type,
                    $account_id,
                    $loan_transaction_payment->id,
                    "null",
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment->note
                );

                $type       = "debit";
                $account_id = $loan_payment->loan_account;

                $this->createAccountTransaction(
                    $loan_transaction_payment,
                    $type,
                    $account_id,
                    $loan_transaction_payment->id,
                    "null",
                    null,
                    $loan_payment->amount,
                    false,
                    $loan_payment->note
                );
            }

            $drawing_note = "";

            foreach ($settlement->drawings_payments as $drawing_payment) {
                $i = 0;

                // this transaction will use in report to show amounts
                $drawing_transaction_payment = $this->createTransaction(
                    $settlement,
                    $drawing_payment->amount,
                    null,
                    null,
                    "settlement",
                    "drawing_payment",
                    $settlement_no
                );

                $drawing_note .= ! empty($drawing_payment->note)
                    ? "Note " . $i++ . ": " . $drawing_payment->note . "\n"
                    : "";

                $type       = "debit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $drawing_transaction_payment,
                    $type,
                    $account_id,
                    $drawing_transaction_payment->id,
                    "null",
                    null,
                    $drawing_payment->amount,
                    false,
                    $drawing_payment->note
                );

                $type       = "credit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $drawing_transaction_payment,
                    $type,
                    $account_id,
                    $drawing_transaction_payment->id,
                    "null",
                    null,
                    $drawing_payment->amount,
                    false,
                    $drawing_payment->note
                );

                $type       = "debit";
                $account_id = $drawing_payment->loan_account;

                $this->createAccountTransaction(
                    $drawing_transaction_payment,
                    $type,
                    $account_id,
                    $drawing_transaction_payment->id,
                    "null",
                    null,
                    $drawing_payment->amount,
                    false,
                    $drawing_payment->note
                );
            }

            foreach ($settlement->cash_deposits as $cash_payment) {
                $i = 0;

                // this transaction will use in report to show amounts
                $cash_deposit = $this->createTransaction(
                    $settlement,
                    $cash_payment->amount,
                    null,
                    null,
                    "settlement",
                    "cash_deposit",
                    $settlement_no,
                    $cash_payment->id
                );

                $type       = "debit";
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $this->createAccountTransaction(
                    $cash_deposit,
                    $type,
                    $account_id,
                    $cash_deposit->id,
                    "null",
                    null,
                    $cash_payment->amount,
                    false,
                    null
                );

                $this->createAccountTransaction(
                    $cash_deposit,
                    "credit",
                    $account_id,
                    $cash_deposit->id,
                    "null",
                    null,
                    $cash_payment->amount,
                    false,
                    null
                );

                $type       = "debit";
                $account_id = $cash_payment->bank_id;

                $this->createAccountTransaction(
                    $cash_deposit,
                    $type,
                    $account_id,
                    $cash_deposit->id,
                    "null",
                    null,
                    $cash_payment->amount,
                    false,
                    null
                );

                $depositBank   = Account::find($cash_payment->bank_id);
                $depositBankNm = $depositBank ? $depositBank->name : "";
                $depositParts  = array_filter([
                    "Cash deposit to bank",
                    $depositBankNm ? "(" . $depositBankNm . ")" : null,
                    "Settlement: " . $settlement_no,
                    ! empty($cash_payment->account_no) ? "Receipt: " . $cash_payment->account_no : null,
                ]);
                ContactLedger::createContactLedger([
                    "business_id"    => $cash_deposit->business_id,
                    "contact_id"     => $cash_deposit->contact_id,
                    "amount"         => $cash_payment->amount,
                    "type"           => "credit",
                    "sub_type"       => "cash_deposit",
                    "operation_date" => $cash_deposit->transaction_date,
                    "created_by"     => $cash_deposit->created_by,
                    "transaction_id" => $cash_deposit->id,
                    "note"           => implode(" ", $depositParts),
                ]);

                $sms_settings = empty($business->sms_settings)
                    ? $this->businessUtil->defaultSmsSettings()
                    : $business->sms_settings;

                $msg_template = NotificationTemplate::where("business_id", $business_id)
                    ->where("template_for", "cash_deposit")
                    ->first();

                if (! empty($msg_template)) {
                    $msg       = $msg_template->sms_body;
                    $account   = Account::find($cash_payment->bank_id);
                    $bank_name = ! empty($account) ? $account->name : "";

                    $msg = str_replace("{account}", $cash_payment->account_no, $msg);
                    $msg = str_replace("{amount}", $this->transactionUtil->num_f($cash_payment->amount), $msg);
                    $msg = str_replace(
                        "{time}",
                        $this->transactionUtil->format_date($cash_payment->time_deposited, true),
                        $msg
                    );
                    $msg = str_replace("{bank}", $bank_name, $msg);

                    $phones = [];
                    if (! empty($business->sms_settings)) {
                        $phones = explode(
                            ",",
                            str_replace(" ", "", $business->sms_settings["msg_phone_nos"])
                        );
                    }

                    foreach ($phones as $phone) {
                        $data = [
                            "sms_settings"  => $sms_settings,
                            "mobile_number" => $phone,
                            "sms_body"      => $msg,
                        ];

                        $response = $this->transactionUtil->sendSms($data);
                    }
                }
            }

            $cash_transaction_payment = null;

            if ($settlement->cash_payments->sum("amount") > 0) {
                $cash_transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    "cash",
                    $settlement->cash_payments->sum("amount")
                );
            }

            foreach ($this->uniqueSettlementCardPaymentsForAccounting($settlement->card_payments) as $card_payment) {
                // Check if account transaction already exists for this SettlementCardPayment
                // This prevents duplicates when payments are added via modal and then settlement is saved
                $card_account_id = ! empty($card_payment->card_type)
                    ? $card_payment->card_type
                    : $this->transactionUtil->account_exist_return_id("Cards (Credit Debit) Account");
                $operation_date = \Carbon::parse($settlement->transaction_date)->format('Y-m-d');

                if (! empty($card_payment->customer_payment_id)) {
                    $existing_by_payment_id = AccountTransaction::where('business_id', $business_id)
                        ->where('account_id', $card_account_id)
                        ->where('type', 'debit')
                        ->where('transaction_payment_id', $card_payment->customer_payment_id)
                        ->first();

                    if ($existing_by_payment_id) {
                        Log::info('Settlement PD: Skipping duplicate account transaction (customer_payment_id already linked)', [
                            'settlement_no'                   => $settlement_no,
                            'card_payment_id'                 => $card_payment->id,
                            'transaction_payment_id'          => $card_payment->customer_payment_id,
                            'existing_account_transaction_id' => $existing_by_payment_id->id,
                        ]);
                        continue;
                    }
                }

                // Check if account transaction already exists (check for both sub_types)
                $existing_card_account_transaction = AccountTransaction::where('business_id', $business_id)
                    ->where('account_id', $card_account_id)
                    ->where('type', 'debit')
                    ->where(function ($q) {
                        // Check for both sub_types (card_payment from store, settlement_card_payment from saveCardPayment)
                        $q->where('sub_type', 'card_payment')
                            ->orWhere('sub_type', 'settlement_card_payment');
                    })
                    ->where('amount', $card_payment->amount)
                    ->where('operation_date', $operation_date)
                    ->where(function ($q) use ($settlement_no, $card_payment) {
                        $q->whereRaw('note LIKE ?', ['%Settlement No: ' . $settlement_no . '%'])
                            ->where(function ($subQ) use ($card_payment) {
                                if (! empty($card_payment->customer_payment_id)) {
                                    $subQ->whereRaw('note LIKE ?', ['%Card Payment%'])
                                        ->orWhereRaw('note LIKE ?', ['%settlement%']);
                                }
                                if (! empty($card_payment->slip_no)) {
                                    $subQ->orWhereRaw('note LIKE ?', ['%Slip No: ' . $card_payment->slip_no . '%']);
                                }
                            });
                    })
                    ->first();

                if ($existing_card_account_transaction) {
                    Log::info('Settlement PD: Skipping duplicate account transaction for SettlementCardPayment', [
                        'settlement_no'                   => $settlement_no,
                        'card_payment_id'                 => $card_payment->id,
                        'existing_account_transaction_id' => $existing_card_account_transaction->id,
                        'amount'                          => $card_payment->amount,
                    ]);
                    continue; // Skip creating duplicate transaction
                }

                // this transaction will use in report to show amounts
                $card_transaction = $this->createTransaction(
                    $settlement,
                    $card_payment->amount,
                    $card_payment->customer_id,
                    null,
                    "settlement",
                    "card_payment",
                    $settlement_no
                );

                $transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    "card",
                    $card_payment->amount,
                    $card_payment->card_number,
                    $card_payment->card_type,
                    null,
                    null,
                    null,
                    0,
                    $card_transaction->contact_id
                );

                SettlementCardPayment::where("id", $card_payment->id)->update([
                    "customer_payment_id" => $transaction_payment->id,
                ]);

                $type = "debit";

                // Build descriptive note for card account book
                $card_note = $card_payment->note;
                if (empty($card_note)) {
                    $note_parts = ['Settlement No: ' . $settlement_no, 'Card Payment'];
                    if (! empty($card_payment->customer_id)) {
                        $customer = Contact::find($card_payment->customer_id);
                        if ($customer) {
                            $note_parts[] = 'Customer: ' . $customer->name;
                        }
                    }
                    if (! empty($card_payment->slip_no)) {
                        $note_parts[] = 'Slip No: ' . $card_payment->slip_no;
                    }
                    $card_note = implode(' | ', $note_parts);
                }

                $is_rt = ! empty($card_payment->pump_payment_id);
                $this->createAccountTransaction(
                    $card_transaction, // Use specific card transaction
                    $type,
                    $card_account_id,
                    $transaction_payment->id,
                    null, // Use transaction sub_type ('card_payment')
                    $card_payment->customer_id,
                    $card_payment->amount,
                    false,
                    $card_note,
                    $card_payment->slip_no,
                    $is_rt && $update_account,
                    ($is_rt && $update_ledger) || ($is_rt && $pumper_ledger_update)
                );

                $this->la1062EnsureWalkInLedgerPair(
                    $card_transaction,
                    $transaction_payment->id,
                    $card_account_id,
                    $card_payment->amount,
                    $operation_date,
                    $card_transaction->created_by,
                    $card_note,
                    $card_payment->slip_no
                );
            }

            $this->ensureSettlementCardAccounting($settlement, $business_id);

            foreach ($settlement->cheque_payments as $cheque_payment) {
                // this transaction will use in report to show amounts
                $cheque_transaction = $this->createTransaction(
                    $settlement,
                    $cheque_payment->amount,
                    $cheque_payment->customer_id,
                    null,
                    "settlement",
                    "cheque_payment",
                    $settlement_no
                );

                $transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    "cheque",
                    $cheque_payment->amount,
                    null,
                    null,
                    $cheque_payment->cheque_number,
                    $cheque_payment->bank_name,
                    $cheque_payment->cheque_date,
                    $cheque_payment->post_dated_cheque,
                    $cheque_transaction->contact_id
                );

                $contact                                   = Contact::where("id", $cheque_payment->customer_id)->first();
                $cheque_transaction->contact               = $contact;
                $cheque_transaction->single_payment_amount = $this->transactionUtil->num_uf(
                    $cheque_payment->amount
                );
                $cheque_transaction->payment_ref_number = "";

                $this->notificationUtil->autoSendNotification(
                    $business_id,
                    "payment_received",
                    $cheque_transaction,
                    $cheque_transaction->contact,
                    true
                );

                SettlementChequePayment::where("id", $cheque_payment->id)->update([
                    "customer_payment_id" => $transaction_payment->id,
                ]);

                $account_id = $this->transactionUtil->account_exist_return_id("Cheques in Hand");
                $type       = "debit";

                $is_rt = ! empty($cheque_payment->pump_payment_id);
                $this->createAccountTransaction(
                    $cheque_transaction, // Use specific cheque transaction
                    $type,
                    $account_id,
                    $transaction_payment->id,
                    null, // Use transaction sub_type ('cheque_payment')
                    $cheque_payment->customer_id,
                    $cheque_payment->amount,
                    false,
                    $cheque_payment->note,
                    null,
                    $is_rt && $update_account,
                    ($is_rt && $update_ledger) || ($is_rt && $pumper_ledger_update)
                );
            }

            // IS1771: normal Payment to Finalize must always post credit sales.
            // Older JavaScript forced no_change=1, so the previous outer guard
            // skipped this complete block and left selected-customer sales absent
            // from both transaction-based and contact-ledger statements.
            if (! empty($settlement->credit_sale_payments)) {
                $deduped_credit_sales = $settlement->credit_sale_payments
                    ->unique(function ($item) {
                        if (! empty($item->pump_payment_id)) {
                            return 'pp-' . $item->pump_payment_id;
                        }
                        if (! empty($item->collection_form_no)) {
                            return 'cf-' . $item->collection_form_no;
                        }
                        if (! empty($item->daily_voucher_id)) {
                            return 'dv-' . $item->daily_voucher_id . '-' . ($item->product_id ?? '0');
                        }
                        $order_number = ! empty($item->order_number) ? $item->order_number : '0';
                        return 'manual-' . $order_number . '-' . ($item->customer_id ?? '0') . '-' . ($item->product_id ?? '0') . '-' . ($item->order_date ?? '');
                    })
                    ->values();
                $settlement->setRelation('credit_sale_payments', $deduped_credit_sales);
            }

            $credit_sales_for_posting = $settlement->credit_sale_payments;
            if (! empty($no_change)) {
                // Edit - No Change must not resend or recreate already-correct
                // credit sales.  It may, however, repair rows affected by the old
                // forced no_change bug (missing transaction, wrong customer/amount,
                // or missing Accounts Receivable/customer-ledger entry).
                $credit_sales_for_posting = $credit_sales_for_posting
                    ->filter(function ($credit_sale_payment) use ($business_id) {
                        return $this->is1771CreditSaleRequiresFinalPosting(
                            $credit_sale_payment,
                            (int) $business_id
                        );
                    })
                    ->values();
            }

            foreach ($credit_sales_for_posting as $credit_sale_payment) {
                $had_valid_transaction_before_posting = $this->is1771CreditSaleHasValidTransaction(
                    $credit_sale_payment,
                    (int) $business_id
                );

                $transaction = $this->createCreditSellTransactions(
                    $settlement,
                    $credit_sale_payment,
                    $default_location
                );

                $credit_sale_payment = app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
                    ->editCreditSale($business_id, $credit_sale_payment->id, [
                        "transaction_id" => $transaction->id,
                    ]);

                $account_id = $this->transactionUtil->account_exist_return_id("Accounts Receivable");

                // IS1813: keep the complete finalized PD credit-sale reference in
                // the Customer Ledger description column.
                $credit_note = implode("\n", [
                    'Settlement No: ' . (string) $settlement_no,
                    'Order No: ' . (trim((string) ($credit_sale_payment->order_number ?? '')) !== '' ? (string) $credit_sale_payment->order_number : '-'),
                    'Vehicle No: ' . (trim((string) ($credit_sale_payment->customer_reference ?? '')) !== '' ? (string) $credit_sale_payment->customer_reference : '-'),
                    'Note: ' . (trim((string) ($credit_sale_payment->note ?? '')) !== '' ? (string) $credit_sale_payment->note : '-'),
                ]);

                $credit_sale_amount = max(
                    0,
                    (float) $credit_sale_payment->amount
                    - (float) $credit_sale_payment->total_discount
                );

                // A pump_payment_id only identifies the authoritative payment row;
                // it does not prove that this settlement-added credit sale already
                // has an Accounts Receivable or customer-ledger posting.  Normalize
                // both records idempotently and explicitly to the selected customer.
                $this->is1771EnsureCreditSaleReceivablePosting(
                    $transaction,
                    $credit_sale_payment,
                    $account_id,
                    $credit_sale_amount,
                    $credit_note
                );

                $is_from_pumper = (int) ($credit_sale_payment->is_from_pumper ?? 0) === 1;
                /*
                 | LA-1169 #5: "! $is_from_pumper" removed from this condition.
                 |
                 | It meant a credit sale entered by the pumper - which is almost
                 | all of them - could never trigger the settlement notification,
                 | so a customer whose Credit Notification is "Settlement" was
                 | never messaged.
                 |
                 | What remains is the genuine guard: do not re-send when the
                 | settlement is saved again with no change. Whether a message is
                 | actually sent is still decided further down by
                 | $contact->credit_notification == "settlement".
                 */
                $should_send_credit_notification = (empty($no_change) || ! $had_valid_transaction_before_posting);

                if (! $is_from_pumper) {
                    // store the customer reference
                    if (! empty($credit_sale_payment->customer_reference)) {
                        $customer       = Contact::findOrFail($credit_sale_payment->customer_id);
                        $name           = $customer->name;
                        $barcode_string = $name . "." . $credit_sale_payment->customer_reference;

                        $qr  = new DNS2D();
                        $qr  = $qr->getBarcodePNG($barcode_string, "QRCODE");
                        $src = "data:image/png;base64," . $qr;

                        $ref_data = [
                            "business_id" => $credit_sale_payment->business_id,
                            "date"        => date("Y-m-d", strtotime($credit_sale_payment->order_date)),
                            "contact_id"  => $credit_sale_payment->customer_id,
                            "reference"   => $credit_sale_payment->customer_reference,
                            "barcode_src" => $src,
                        ];

                        CustomerReference::updateOrCreate(
                            [
                                "business_id" => $credit_sale_payment->business_id,
                                "contact_id"  => $credit_sale_payment->customer_id,
                                "reference"   => $credit_sale_payment->customer_reference,
                            ],
                            $ref_data
                        );
                    }

                } else {
                    $credit_sale_payment = app(\Modules\PetroPD\Services\SettlementPaymentEditService::class)
                        ->editCreditSale($business_id, $credit_sale_payment->id, [
                            'is_from_pumper' => 0,
                            'is_committed' => 1,
                        ]);
                }

                /*
                 | LA-1169 #5: the credit-sale SMS runs AFTER the is_from_pumper
                 | branch above, not inside it.
                 |
                 | It used to sit inside "if (! $is_from_pumper)". In practice
                 | almost every PD credit sale IS entered by the pumper, so that
                 | flag is 1 and the notification was skipped - even for a customer
                 | whose Credit Notification is explicitly "Settlement". Nothing was
                 | ever sent for them.
                 |
                 | The customer-reference and QR work stays inside that branch,
                 | because the pumper flow already does it. Only the notification
                 | moved out.
                 |
                 | No one receives two messages: the test further down is
                 | credit_notification == "settlement", and a customer set to
                 | "pumper_dashboard" was already notified at the point of sale by
                 | PumperDashboard\Services\CreditSaleSmsNotifier.
                 */
                    if ($should_send_credit_notification) {
                        $cheque_pmt = SettlementChequePayment::where("customer_id", $credit_sale_payment->customer_id)
                            ->where("settlement_no", $credit_sale_payment->settlement_no)
                            ->sum("amount");

                        $cash_pmt = SettlementCardPayment::where("customer_id", $credit_sale_payment->customer_id)
                            ->where("settlement_no", $credit_sale_payment->settlement_no)
                            ->sum("amount");

                        $card_pmt = SettlementCashPayment::where("customer_id", $credit_sale_payment->customer_id)
                            ->where("settlement_no", $credit_sale_payment->settlement_no)
                            ->sum("amount");

                        $total_paid = $cheque_pmt + $cash_pmt + $card_pmt;

                        /*
                         |--------------------------------------------------------
                         | IS2020: the settlement SMS was still not being sent.
                         |--------------------------------------------------------
                         |
                         | The block below sits inside the settlement's own
                         | try/catch (the catch (\Exception $e) further down), so
                         | ANY error thrown in here is swallowed. The settlement
                         | saves, no error is shown, and no SMS goes out - exactly
                         | what was reported.
                         |
                         | Four things in the original could throw or silently
                         | produce nothing:
                         |
                         |  1. $business_id was RE-READ from the session here,
                         |     overwriting the settlement's own business id. When
                         |     "user.business_id" is absent - which happens on the
                         |     finalize request - $business came back null, and the
                         |     first use of $business->name threw.
                         |
                         |  2. Product::findOrFail() threw whenever the credit sale
                         |     had no product_id, or the product had been removed.
                         |     A missing product is not a reason to withhold the
                         |     message.
                         |
                         |  3. $contact was used without a null check, so a credit
                         |     sale whose customer had been deleted threw on
                         |     $contact->credit_notification.
                         |
                         |  4. The alternate number was sent to unconditionally,
                         |     even when the customer had none - see the guard on
                         |     the sends further down.
                         |
                         | The settlement's own business id is used now, every
                         | lookup is null-guarded, and anything unexpected is logged
                         | rather than vanishing.
                         */
                        $business_id  = ! empty($settlement->business_id)
                            ? $settlement->business_id
                            : ($business_id ?: request()->session()->get("user.business_id"));

                        $business     = Business::where("id", $business_id)->first();
                        $sms_settings = empty($business) || empty($business->sms_settings)
                            ? $this->businessUtil->defaultSmsSettings()
                            : $business->sms_settings;

                        $contact      = Contact::where("id", $credit_sale_payment->customer_id)->first();
                        $msg_template = NotificationTemplate::where("business_id", $business_id)
                            ->where("template_for", "credit_sale")
                            ->first();

                        $final_total = $credit_sale_payment->amount - $credit_sale_payment->total_discount;

                        // find(), not findOrFail() - see note 2 above.
                        $product     = ! empty($credit_sale_payment->product_id)
                            ? Product::find($credit_sale_payment->product_id)
                            : null;

                        $product_msg = ! empty($product)
                            ? PHP_EOL .
                                "Product Sold: " . ucfirst($product->name) . PHP_EOL .
                                "Quantity: " . $this->productUtil->num_f($credit_sale_payment->qty)
                            : "";

                        if (empty($business) || empty($contact)) {
                            \Log::warning("IS2020: settlement credit SMS skipped - business or contact missing", [
                                "settlement_no" => $settlement->settlement_no ?? null,
                                "customer_id"   => $credit_sale_payment->customer_id ?? null,
                                "business_id"   => $business_id,
                            ]);
                        }

                        // IS2020: $contact null-guarded - see note 3 above.
                        if (! empty($msg_template)
                            && ! empty($business)
                            && ! empty($contact)
                            && (string) $contact->credit_notification === "settlement") {
                            $msg = $msg_template->sms_body;

                            $msg = str_replace("{business_name}", $business->name, $msg);
                            $msg = str_replace("{total_amount}", $this->productUtil->num_f($final_total), $msg);
                            $msg = str_replace("{contact_name}", $contact->name, $msg);
                            $msg = str_replace("{invoice_number}", $settlement->settlement_no, $msg);
                            $msg = str_replace("{transaction_date}", $settlement->transaction_date, $msg);
                            $msg = str_replace("{paid_amount}", $this->productUtil->num_f($total_paid), $msg);
                            $msg = str_replace("{due_amount}", $this->productUtil->num_f($final_total - $total_paid), $msg);
                            $msg = str_replace(
                                "{cumulative_due_amount}",
                                $this->productUtil->num_f(
                                    strval($contactController->get_due_bal($credit_sale_payment->customer_id, false))
                                ),
                                $msg
                            );
                            $msg = str_replace("{customer_reference}", $credit_sale_payment->customer_reference, $msg);
                            $msg = str_replace("{vehicle_no}", $credit_sale_payment->customer_reference, $msg);

                            $msg .= $product_msg;

                            if (! empty($business->sms_settings)) {
                                $phones = explode(
                                    ",",
                                    str_replace(" ", "", $business->sms_settings["msg_phone_nos"])
                                );
                            }

                            /*
                             | IS2020: both sends are guarded on a non-empty number.
                             |
                             | The alternate number was sent to unconditionally, so
                             | a customer with no alternate number produced a second
                             | send to an empty string. Depending on the gateway that
                             | either errored - aborting this block through the outer
                             | catch, before anything was logged - or burned an SMS
                             | credit on nothing.
                             |
                             | The contact and template are passed on the primary
                             | send too, so it is recorded against the customer the
                             | same way the alternate one is.
                             */
                            $data = [
                                "sms_settings"  => $sms_settings,
                                "mobile_number" => $contact->mobile,
                                "sms_body"      => $msg,
                            ];

                            $sms_sent_to = [];

                            if (! empty($contact->mobile)) {
                                $this->businessUtil->sendSms($data, $contact, "credit_sale");
                                $sms_sent_to[] = $contact->mobile;
                            }

                            if (! empty($contact->alternate_number)) {
                                $data["mobile_number"] = $contact->alternate_number;
                                $this->businessUtil->sendSms($data, $contact, "credit_sale");
                                $sms_sent_to[] = $contact->alternate_number;
                            }

                            \Log::info("IS2020: settlement credit sale SMS", [
                                "settlement_no" => $settlement->settlement_no ?? null,
                                "customer_id"   => $credit_sale_payment->customer_id ?? null,
                                "sent_to"       => $sms_sent_to,
                            ]);
                        }
                    }
            }

            $total_shortage = $pump_operator->short_amount; // get previous amount

            foreach ($settlement->shortage_payments as $shortage_payment) {
                $transaction = $this->createTransaction(
                    $settlement,
                    $shortage_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    "settlement",
                    "shortage",
                    $settlement_no
                );

                SettlementShortagePayment::where("id", $shortage_payment->id)
                    ->update(["transaction_id" => $transaction->id]);

                // Resolve the receivable account inside this settlement's business.
                // The generic utility can return null when the session/account naming
                // differs; AccountTransaction would then silently fall back to Cash.
                // A shortage is never cash, so that fallback must not be reachable.
                $account_id = $this->resolvePdShortageReceivableAccountId($business_id);
                $type       = "debit";

                // IS1832: shortage is money due from the pump operator.
                // Post it to the Accounts Receivable Account Book and never to
                // Cash.  The final boolean suppresses the duplicate customer
                // ledger pair; it does not suppress the AR account transaction.
                if (! empty($account_id)) {
                    $this->createAccountTransaction(
                        $transaction,
                        $type,
                        $account_id,
                        null,
                        "ledger_show",
                        null,
                        $shortage_payment->amount,
                        false,
                        $shortage_payment->note,
                        null,
                        false,
                        true
                    );

                    $this->removePdShortageCashAccountFallback(
                        $transaction,
                        $business_id,
                        $account_id,
                        $settlement_no,
                        $shortage_payment->id
                    );
                } else {
                    Log::error('PetroPD shortage account posting skipped: Accounts Receivable is missing; Cash fallback was blocked.', [
                        'business_id'        => $business_id,
                        'settlement_id'      => $settlement->id,
                        'settlement_no'      => $settlement_no,
                        'shortage_payment_id'=> $shortage_payment->id,
                        'transaction_id'     => $transaction->id,
                        'amount'             => $shortage_payment->amount,
                    ]);
                }

                $total_shortage += $shortage_payment->amount;
            }

            $total_excess = $pump_operator->excess_amount; // get previous amount

            foreach ($settlement->excess_payments as $excess_payment) {
                $excess_amount = abs((float) $excess_payment->amount);

                if ($excess_amount <= 0.0001) {
                    continue;
                }

                $transaction = $this->resolvePdExcessTransaction(
                    $settlement,
                    $excess_payment,
                    $settlement_no,
                    $business_id
                );

                SettlementExcessPayment::where("id", $excess_payment->id)
                    ->update(["transaction_id" => $transaction->id]);

                /*
                 * IS1837-08: resolve the Accounts Payable account inside this
                 * settlement's business. The utility lookup depends on session
                 * context and could return an account from no/another business,
                 * causing the excess entry to disappear from Finance Account Book.
                 */
                $account_id = $this->resolvePdExcessPayableAccountId($business_id);

                if (! empty($account_id)) {
                    $this->ensurePdExcessAccountsPayableEntry(
                        $transaction,
                        $excess_payment,
                        $account_id,
                        $business_id,
                        $settlement_no,
                        $excess_amount
                    );
                } else {
                    Log::error('PetroPD excess posting skipped: Accounts Payable is missing for this business.', [
                        'business_id'       => $business_id,
                        'settlement_id'     => $settlement->id,
                        'settlement_no'     => $settlement_no,
                        'excess_payment_id' => $excess_payment->id,
                        'transaction_id'    => $transaction->id,
                        'amount'            => $excess_amount,
                    ]);
                }

                $total_excess += $excess_payment->amount;
            }

            $pump_operator->short_amount  = $total_shortage;
            $pump_operator->excess_amount = $total_excess;
            $pump_operator->settlement_no = null;
            $pump_operator->save();

            foreach ($settlement->expense_payments as $expense_payment) {
                $transaction = $this->createTransaction(
                    $settlement,
                    $expense_payment->amount,
                    null,
                    $settlement->pump_operator_id,
                    "settlement",
                    "expense",
                    $settlement_no
                );

                $transaction->expense_category_id = $expense_payment->category_id;
                $transaction->ref_no              = "Settlement No: " . $settlement->settlement_no;
                $transaction->expense_account     = $expense_payment->account_id;
                $transaction->save();

                SettlementExpensePayment::where("id", $expense_payment->id)
                    ->update(["transaction_id" => $transaction->id]);

                $is_pd_cheque_expense = ! empty($expense_payment->pd_cheque);
                $transaction_payment = $this->createTansactionPayment(
                    $transaction,
                    $is_pd_cheque_expense ? "cheque" : "cash",
                    $expense_payment->amount,
                    null,
                    null,
                    $expense_payment->cheque_number ?? null,
                    $expense_payment->bank_name ?? null,
                    $expense_payment->cheque_date ?? null,
                    $is_pd_cheque_expense ? 1 : 0
                );

                $account_id = $expense_payment->account_id;
                $type       = "debit";

                $this->createAccountTransaction($transaction, $type, $account_id, $transaction_payment->id);

                if ($is_pd_cheque_expense) {
                    Account::crearePostdatedChequesAccount($business_id, auth()->id());
                    $issued_pd_account_id = $this->transactionUtil->account_exist_return_id("Issued Post Dated Cheques");
                    $expense_category = \App\ExpenseCategory::find($expense_payment->category_id);
                    $bank_name = $expense_payment->bank_name;

                    if (empty($bank_name) && ! empty($expense_payment->bank_account_id)) {
                        $bank_name = optional(Account::find($expense_payment->bank_account_id))->name;
                    }

                    AccountTransaction::createAccountTransaction([
                        "amount"                 => abs($transaction->final_total),
                        "account_id"             => $issued_pd_account_id,
                        "contact_id"             => $transaction->contact_id,
                        "type"                   => "credit",
                        // S282-005: Account Book Date is the system posting date.
                        // The selected settlement date stays on the Transaction Date column.
                        "operation_date"         => \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d H:i:s'),
                        "created_by"             => $transaction->created_by,
                        "transaction_id"         => $transaction->id,
                        "transaction_payment_id" => $transaction_payment->id,
                        "note"                   => trim(collect([
                            optional($expense_category)->name,
                            "Post dated Cheque Issued from Bank " . ($bank_name ?: "N/A"),
                        ])->filter()->implode("\n")),
                        "cheque_number"          => $expense_payment->cheque_number ?? null,
                        "cheque_date"            => $expense_payment->cheque_date ?? null,
                        "post_dated_cheque"      => 1,
                    ]);
                } else {
                    $account_id = $this->transactionUtil->account_exist_return_id("Cash");
                    $type       = "credit";

                    $this->createAccountTransaction($transaction, $type, $account_id, $transaction_payment->id);
                }
            }

            if (
                $settlement->expense_payments->sum("amount") > 0 &&
                $settlement->cash_payments->sum("amount") == 0
            ) {
                // Cash payment + expense payment // doc 3075 - POS Settlement Expense amount in cash account – 5 Nov 2020
                $account_id = $this->transactionUtil->account_exist_return_id("Cash");

                $expense_transaction_data = [
                    "amount"                 => $settlement->expense_payments->sum("amount"),
                    "account_id"             => $account_id,
                    "contact_id"             => $sell_transaction->contact_id,
                    "type"                   => "debit",
                    "sub_type"               => null,
                    "operation_date"         => \Carbon\Carbon::parse($sell_transaction->transaction_date)->format('Y-m-d H:i:s'),
                    "created_by"             => $sell_transaction->created_by,
                    "transaction_id"         => $sell_transaction->id,
                    "transaction_payment_id" => ! empty($cash_transaction_payment)
                        ? $cash_transaction_payment->id
                        : null,
                    "note"                   => (! empty($cash_note) ? $cash_note . "\n" : "") . "Settlement No: " . $settlement->settlement_no,
                ];

                AccountTransaction::createAccountTransaction($expense_transaction_data);
            }

            foreach ($settlement->customer_payments as $customer_payments) {
                $account_id = $this->transactionUtil->account_exist_return_id("Accounts Receivable");

                $ob_transaction_data = [
                    "amount"                 => $customer_payments->amount,
                    "post_dated_cheque"      => $customer_payments->post_dated_cheque,
                    "account_id"             => $account_id,
                    "type"                   => "credit", // changed from debit to credit
                    "sub_type"               => "deposit",
                    "operation_date"         => \Carbon\Carbon::parse($sell_transaction->transaction_date)->format('Y-m-d H:i:s'),
                    "created_by"             => auth()->user()->id,
                    "transaction_id"         => $sell_transaction->id,
                    "transaction_payment_id" => null,
                ];

                AccountTransaction::createAccountTransaction($ob_transaction_data);
            }

            // This is only to show in print page customer payments which entered in customer payments tab
            $customer_payments_tab = CustomerPayment::leftJoin(
                "contacts",
                "customer_payments.customer_id",
                "contacts.id"
            )
                ->where("customer_payments.settlement_no", $settlement->id)
                ->where("customer_payments.business_id", $business_id)
                ->select(
                    "customer_payments.*",
                    "contacts.name as customer_name"
                )
                ->get();

            // Calculate settlement_total from sales (this is the amount to be paid)
            // This matches Direct Settlement's calculation pattern
            $payment_meter_sale_total = $this->getSettlementPDMeterSaleTotal($business_id, $settlement);

            // Modified by Engr. Alex -- task 7889: use post-discount amounts so
            // settlements.total_amount and P&L reflect the amount after discount
            $settlement_total =
                $settlement->meter_sales->sum("discount_amount") +
                ($settlement->other_sales->sum("sub_total") - $settlement->other_sales->sum("discount_amount")) +
                $settlement->other_incomes->sum("sub_total") +
                $settlement->customer_payments->sum("sub_total") +
                $pump_operator_total_other_sale +
                $payment_meter_sale_total;

            // Set total_amount immediately (matching Direct Settlement pattern)
            $settlement->total_amount = $settlement_total;
            // NOTE: status = 0 will be set RIGHT BEFORE commit to ensure it's persisted
            // This matches Settlement SW pattern and prevents rollback issues

            $settlement->cash_denomination = ($denom_enabled > 0) ? json_encode($denom_data) : null;

            // CRITICAL: Set work_shift to the shift_ids being settled
            // This is needed for filtering in show/print methods
            if (! empty($shift_ids)) {
                $settlement->work_shift = is_array($shift_ids) ? $shift_ids : explode(",", $shift_ids);
            } else {
                $settlement->work_shift = [];
            }

            // IMPORTANT: Save settlement (without status) to ensure settlement_no is final before updating credit sales
            // Status will be set to 0 right before commit
            $settlement->save();

            \Log::info('Settlement PD: Settlement Saved (before finalization)', [
                'settlement_id'    => $settlement->id,
                'settlement_no'    => $settlement->settlement_no,
                'current_status'   => $settlement->status,
                'pump_operator_id' => $settlement->pump_operator_id,
                'work_shift'       => $settlement->work_shift,
                'shift_ids'        => $shift_ids ?? [],
            ]);

            // Log credit sales linked to this settlement
            $credit_sales_after_save = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($q) use ($settlement) {
                    $q->where('settlement_no', $settlement->settlement_no)
                        ->orWhere('settlement_no', $settlement->id);
                })
                ->with('product')
                ->get();

            \Log::info('Settlement PD: Credit Sales After Save', [
                'settlement_id'        => $settlement->id,
                'settlement_no'        => $settlement->settlement_no,
                'credit_sales_count'   => $credit_sales_after_save->count(),
                'credit_sales_total'   => $credit_sales_after_save->sum('amount'),
                'credit_sales_details' => $credit_sales_after_save->map(function ($cs) {
                    return [
                        'id'                 => $cs->id,
                        'order_number'       => $cs->order_number,
                        'amount'             => $cs->amount,
                        'collection_form_no' => $cs->collection_form_no,
                    ];
                })->toArray(),
            ]);

            // Get Daily Collection from business_id and pump_operator_id where settlement_id is null
            if (empty($shift_ids_for_collections)) {
                $daily_collections = collect();
            } else {
                $daily_collections = DailyCollection::leftJoin(
                    "business_locations",
                    "daily_collections.location_id",
                    "business_locations.id"
                )
                    ->leftJoin(
                        "pump_operators",
                        "daily_collections.pump_operator_id",
                        "pump_operators.id"
                    )
                    ->leftJoin(
                        "pump_operator_assignments",
                        "pump_operator_assignments.pump_operator_id",
                        "pump_operators.id"
                    )
                    ->leftJoin("users", "daily_collections.created_by", "users.id")
                    ->leftJoin(
                        "settlements",
                        "daily_collections.settlement_id",
                        "settlements.id"
                    )
                    ->where("daily_collections.business_id", $business_id)
                    // PETROPD-CASHSAVE-ROOTFIX-004: no pump_operator filter; use closed shift scope.
                    ->where("daily_collections.type", "daily_collection")
                    ->whereNull("daily_collections.settlement_id")
                    ->whereNull("daily_collections.added_to_account")
                    ->whereIn("daily_collections.shift_id", $shift_ids_for_collections)
                    ->select([
                        "daily_collections.*",
                        "business_locations.name as location_name",
                        "pump_operators.name as pump_operator_name",
                        "settlements.id as settlements_id",
                        "users.username as user",
                    ])
                    ->orderBy("daily_collections.id")
                    ->get();
            }

            $outstanding_payment = $settlement_total;

            if ($daily_collections->isNotEmpty() && $outstanding_payment > 0) {
                $sids = $shift_ids_for_collections;

                foreach ($daily_collections as $dc) {
                    if ($outstanding_payment <= 0) {
                        break;
                    }

                    if (! is_object($dc) || ! isset($dc->current_amount)) {
                        continue;
                    }

                    $row = DailyCollection::where('id', $dc->id)
                        ->where('business_id', $business_id)
                        ->where("type", "daily_collection")
                        // PETROPD-CASHSAVE-ROOTFIX-004: no pump_operator filter; use closed shift scope.
                        ->whereNull('settlement_id')
                        ->whereIn('shift_id', $sids)
                        ->lockForUpdate()
                        ->first();

                    if (! $row) {
                        continue;
                    }

                    $amount = floatval($row->current_amount);
                    // PETROPD-CASHSAVE-ROOTFIX-004: do not cap saved cash by outstanding sales/payment balance.
                    $alloc  = $amount;

                    // Check if this DailyCollection is already linked to a PumpOperatorPayment
                    // that has a SettlementCashPayment (to prevent duplication)
                    $pump_payment = null;
                    if (! empty($row->collection_form_no)) {
                        $pump_payment = \Modules\PetroPD\Entities\PumpOperatorPayment::where('business_id', $business_id)
                            ->where('pump_operator_id', $settlement->pump_operator_id)
                            ->where('collection_form_no', $row->collection_form_no)
                            ->where('payment_type', 'cash')
                            ->first();
                    }

                    // If linked to PumpOperatorPayment, check if SettlementCashPayment already exists
                    $existing_cash_payment = null;
                    if ($pump_payment) {
                        $existing_cash_payment = SettlementCashPayment::where('business_id', $business_id)
                            ->where(function ($q) use ($settlement) {
                                $q->where('settlement_no', $settlement->id)
                                    ->orWhere('settlement_no', $settlement->settlement_no);
                            })
                            ->where('customer_payment_id', $pump_payment->id)
                            ->first();
                    } else {
                        // If not linked to PumpOperatorPayment, check by amount and settlement
                        // to prevent duplicate entries from DailyCollection
                        // Check if SettlementCashPayment already exists for this DailyCollection
                        // Use daily_collection_id if available, otherwise check by amount
                        $existing_cash_payment = SettlementCashPayment::where('business_id', $business_id)
                            ->where('settlement_no', $settlement->id) // Use ID (integer) to match relationship
                            ->where(function ($q) use ($row) {
                                $q->where('daily_collection_id', $row->id) // Primary check: daily_collection_id
                                    ->orWhere(function ($q2) use ($row) {
                                        // Fallback: check by amount if daily_collection_id not set
                                        $q2->where('amount', $row->current_amount)
                                            ->whereNull('customer_payment_id')
                                            ->whereNull('daily_collection_id');
                                    });
                            })
                            ->first();
                    }

                    // Only create SettlementCashPayment if it doesn't already exist
                    if (! $existing_cash_payment) {
                        // create a settlement cash payment record (so UI/process shows this payment)
                        // Check if SettlementCashPayment already exists for this DailyCollection to prevent duplicates
                        $existing_cash_payment = SettlementCashPayment::where('business_id', $business_id)
                            ->where('settlement_no', $settlement->id)
                            ->where('daily_collection_id', $row->id)
                            ->first();

                        if (! $existing_cash_payment) {
                            // Also check if there's a SettlementCashPayment with same amount and customer_payment_id
                            // This prevents duplicates when the same payment is added both from pumper dashboard and settlement modal
                            if ($pump_payment) {
                                $existing_by_pump_payment = SettlementCashPayment::where('business_id', $business_id)
                                    ->where('settlement_no', $settlement->id)
                                    ->where('customer_payment_id', $pump_payment->id)
                                    ->first();

                                if ($existing_by_pump_payment) {
                                    Log::info('Settlement PD: Skipping duplicate SettlementCashPayment from DailyCollection', [
                                        'settlement_id'                       => $settlement->id,
                                        'daily_collection_id'                 => $row->id,
                                        'pump_payment_id'                     => $pump_payment->id,
                                        'existing_settlement_cash_payment_id' => $existing_by_pump_payment->id,
                                        'amount'                              => $alloc,
                                    ]);
                                    // Update daily_collection but don't create duplicate SettlementCashPayment
                                } else {
                                    $customers   = Contact::customersDropdown($business_id, false, true, "customer");
                                    $customer_id = null;
                                    if ($customers instanceof \Illuminate\Support\Collection) {
                                        $custArr     = $customers->toArray();
                                        $customer_id = array_key_first($custArr);
                                    } elseif (is_array($customers)) {
                                        $customer_id = array_key_first($customers);
                                    }

                                    app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)->upsertOne(
                                        $business_id,
                                        (string) $settlement->id,
                                        'settlement_cash_payments',
                                        [
                                            "amount"              => $alloc,
                                            "customer_id"         => $customer_id,
                                            "customer_payment_id" => $pump_payment->id,
                                            "pump_payment_id"     => $pump_payment->id,
                                            "daily_collection_id" => $row->id,
                                        ]
                                    );
                                }
                            } else {
                                // No pump_payment link, create SettlementCashPayment from DailyCollection
                                $customers   = Contact::customersDropdown($business_id, false, true, "customer");
                                $customer_id = null;
                                if ($customers instanceof \Illuminate\Support\Collection) {
                                    $custArr     = $customers->toArray();
                                    $customer_id = array_key_first($custArr);
                                } elseif (is_array($customers)) {
                                    $customer_id = array_key_first($customers);
                                }

                                // DAY1-ORPHAN: no pump_payment link from DailyCollection — orphan-path insert via Reconciler.
                                app(\Modules\PetroPD\Services\SettlementPaymentReconciler::class)->upsertOne(
                                    $business_id,
                                    (string) $settlement->id,
                                    'settlement_cash_payments',
                                    [
                                        "amount"              => $alloc,
                                        "customer_id"         => $customer_id,
                                        "customer_payment_id" => null,
                                        "pump_payment_id"     => null,
                                        "daily_collection_id" => $row->id,
                                    ]
                                );
                            }
                        } else {
                            Log::info('Settlement PD: SettlementCashPayment already exists for DailyCollection', [
                                'settlement_id'                       => $settlement->id,
                                'daily_collection_id'                 => $row->id,
                                'existing_settlement_cash_payment_id' => $existing_cash_payment->id,
                            ]);
                        }
                    }
                    $row->update([
                        'settlement_id'      => $settlement->id,
                        'settlement_date'    => $settlement->transaction_date ?? $settlement->finish_date ?? date('Y-m-d'),
                        'balance_collection' => $alloc,
                        'added_to_account'   => 1,
                    ]);

                    $outstanding_payment -= $alloc;
                }
            }

            // create VAT entries
            $this->transactionUtil->calculateAndUpdateVAT($sell_transaction);

            PumperDayEntry::where("settlement_no", $settlement_no)
                ->update([
                    "settlement_no"        => $request->settlement_no,
                    "settlement_added_by"  => auth()->user()->id,
                    "closed_in_settlement" => 1,
                ]);

            // Get shift_ids for filtering (to prevent updating records from other shifts)
            $shift_ids_for_filter = [];
            if ($request->shift_ids) {
                $shift_ids_for_filter = is_array($request->shift_ids)
                    ? $request->shift_ids
                    : explode(",", $request->shift_ids);
                $shift_ids_for_filter = array_map('intval', $shift_ids_for_filter);
            }

            // IS1451 FIX: Daily Pump Status reads the Settlement No from
            // pumper_day_entries.settlement_no and/or assignment settlement_id. In
            // some finalize flows pumper_day_entries.settlement_no was never updated
            // because the old update only searched by a previous settlement_no value.
            // Link all meter-day rows belonging to the finalized PD settlement shifts.
            if (! empty($shift_ids_for_filter)) {
                $assignment_ids_for_entries = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->pluck('id')
                    ->toArray();

                if (! empty($assignment_ids_for_entries)) {
                    $linked_day_entries = PumperDayEntry::where('business_id', $business_id)
                        ->where('pump_operator_id', $settlement->pump_operator_id)
                        ->whereIn('pumper_assignment_id', $assignment_ids_for_entries)
                        ->update([
                            'settlement_no'        => $settlement->settlement_no,
                            'settlement_datetime'  => now(),
                            'settlement_added_by'  => auth()->user()->id,
                            'closed_in_settlement' => 1,
                        ]);

                    Log::info('Settlement PD: linked pumper_day_entries to settlement for Daily Pump Status', [
                        'settlement_id'       => $settlement->id,
                        'settlement_no'       => $settlement->settlement_no,
                        'assignment_ids'      => $assignment_ids_for_entries,
                        'day_entries_updated' => $linked_day_entries,
                    ]);
                }
            }

            // Forever-fix (2026-05-13): link pump_operator_assignments to the new settlement.
            //
            // Background: the SettlementPDController previously updated pumper_day_entries
            // with the new settlement_no / closed_in_settlement=1 (above), but never
            // propagated the same linkage to pump_operator_assignments. Result: after
            // finalize, every assignment for this settlement still had settlement_id=NULL
            // and closed_in_settlement=0. The edit page's primary lookup at line ~7538
            // (`WHERE assignments.settlement_id = $settlement->id`) returned empty, forcing
            // it onto the fragile work_shift→shift_number→shift_id fallback. The fallback
            // could miss meter sales whose shift_id wasn't reachable via shift_number, so
            // the Meter Sale tab on the edit page rendered empty. See the regression test:
            // tests/Feature/Petro/SettlementEditTest.php::finalized_pd_edit_meter_total_uses_shift_number_to_find_real_shift_id
            //
            // Linking by (business_id, pump_operator_id, shift_id) is stable and unique:
            // each assignment row is for exactly one operator on one shift on one pump.
            if (! empty($shift_ids_for_filter)) {
                $assignment_links = PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('pump_operator_id', $settlement->pump_operator_id)
                    ->whereIn('shift_id', $shift_ids_for_filter)
                    ->update([
                        'settlement_id'        => $settlement->id,
                        'closed_in_settlement' => 1,
                    ]);

                \Log::info('Settlement PD: linked pump_operator_assignments to settlement', [
                    'settlement_id'        => $settlement->id,
                    'settlement_no'        => $settlement->settlement_no,
                    'pump_operator_id'     => $settlement->pump_operator_id,
                    'shift_ids_for_filter' => $shift_ids_for_filter,
                    'rows_updated'         => $assignment_links,
                ]);
            }

            // IS1759-11/12: Link PetroPD credit sales by the authoritative
            // Pump Operator Payment identity. daily_vouchers.shift_id is a
            // legacy petro_daily_shifts foreign key and must never be used as
            // a PetroPD shift filter.
            $credit_payment_query = PumpOperatorPayment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->whereIn('payment_type', ['credit', 'multiple_credit']);

            if (! empty($shift_ids_for_filter)) {
                $credit_payment_query->whereIn('shift_id', $shift_ids_for_filter);
            } else {
                // No immutable shift scope means no new, previously-unlinked
                // credit rows may be claimed by this settlement.
                $credit_payment_query->whereRaw('1 = 0');
            }

            $credit_master_payments = $credit_payment_query
                ->get(['id', 'collection_form_no']);
            $credit_payment_ids = $credit_master_payments->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            $credit_collection_form_nos = $credit_master_payments->pluck('collection_form_no')
                ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
                ->map(fn ($value) => (string) $value)
                ->unique()
                ->values()
                ->toArray();

            $credit_scope = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where(function ($ownerQuery) use ($settlement) {
                    $ownerQuery->whereNull('settlement_no')
                        ->orWhere('settlement_no', '')
                        ->orWhere('settlement_no', (string) $settlement->id)
                        ->orWhere('settlement_no', (string) $settlement->settlement_no);
                })
                ->where(function ($identityQuery) use ($credit_payment_ids, $credit_collection_form_nos, $settlement) {
                    // Rows already owned by this settlement remain in scope.
                    $identityQuery->where('settlement_no', (string) $settlement->id)
                        ->orWhere('settlement_no', (string) $settlement->settlement_no);

                    if (! empty($credit_payment_ids)
                        && Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                        $identityQuery->orWhereIn('pump_payment_id', $credit_payment_ids);
                    }

                    // Legacy fallback only for rows which pre-date pump_payment_id.
                    if (! empty($credit_collection_form_nos)) {
                        $identityQuery->orWhere(function ($legacyQuery) use ($credit_collection_form_nos) {
                            if (Schema::hasColumn('settlement_credit_sale_payments', 'pump_payment_id')) {
                                $legacyQuery->whereNull('pump_payment_id');
                            }
                            $legacyQuery->whereIn('collection_form_no', $credit_collection_form_nos);
                        });
                    }
                });

            $credit_sales_updated = (clone $credit_scope)
                ->update(['settlement_no' => $settlement->settlement_no]);

            $credit_sale_payments = SettlementCreditSalePayment::where('business_id', $settlement->business_id)
                ->where('pump_operator_id', $settlement->pump_operator_id)
                ->where('settlement_no', $settlement->settlement_no)
                ->get();

            $daily_voucher_ids = $credit_sale_payments->pluck('daily_voucher_id')
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->toArray();
            $linked_collection_form_nos = $credit_sale_payments->pluck('collection_form_no')
                ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
                ->map(fn ($value) => (string) $value)
                ->unique()
                ->values()
                ->toArray();

            $voucher_scope = DailyVoucher::where('business_id', $settlement->business_id)
                ->where('operator_id', $settlement->pump_operator_id)
                ->where(function ($ownerQuery) use ($settlement) {
                    $ownerQuery->whereNull('settlement_no')
                        ->orWhere('settlement_no', '')
                        ->orWhere('settlement_no', (string) $settlement->id)
                        ->orWhere('settlement_no', (string) $settlement->settlement_no);
                })
                ->where(function ($identityQuery) use (
                    $daily_voucher_ids,
                    $credit_payment_ids,
                    $linked_collection_form_nos
                ) {
                    $identityQuery->whereRaw('1 = 0');

                    if (! empty($daily_voucher_ids)) {
                        $identityQuery->orWhereIn('id', $daily_voucher_ids);
                    }

                    if (! empty($credit_payment_ids) && Schema::hasColumn('daily_vouchers', 'pump_payment_id')) {
                        $identityQuery->orWhereIn('pump_payment_id', $credit_payment_ids);
                    }

                    // Legacy records can pre-date both direct links. The form
                    // number remains safe here because business and operator
                    // ownership are already enforced by the outer query.
                    if (! empty($linked_collection_form_nos)) {
                        $identityQuery->orWhereIn('daily_vouchers_no', $linked_collection_form_nos);
                    }
                });

            $daily_vouchers_updated = $voucher_scope->update([
                'settlement_no' => $settlement->settlement_no,
                'status' => 1,
            ]);

            \Log::info('Settlement PD: authoritative credit linkage completed', [
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'shift_ids' => $shift_ids_for_filter,
                'pump_payment_ids' => $credit_payment_ids,
                'credit_sales_updated' => $credit_sales_updated,
                'daily_vouchers_updated' => $daily_vouchers_updated,
            ]);

            // Update PumpOperatorAssignment for the given pumps and shifts
            $shift_ids = $request->shift_ids; // e.g. [5]

            if (! empty($shift_ids)) {
                $shift_ids = (array) $shift_ids; // force into array

                PumpOperatorAssignment::whereNull("settlement_id")
                    ->whereIn("shift_id", $shift_ids)
                    ->update([
                        "settlement_id"        => $settlement->id,
                        "closed_in_settlement" => 1,
                    ]);
            } else {
                if (!empty($pump_ids)) {
                    PumpOperatorAssignment::whereNull("settlement_id")
                        ->whereIn("pump_id", $pump_ids)
                        ->update([
                            "settlement_id"        => $settlement->id,
                            "closed_in_settlement" => 1,
                        ]);
                }
            }

            // CRITICAL: Mark settlement as finalized RIGHT BEFORE commit (matching Settlement SW pattern)
            // This ensures status = 0 is set at the very end, just before commit
            // If we set it earlier and there's an error, the transaction rollback would lose the status
            $settlement->update([
                'status'      => 0, // set status to non-active (finalized)
                'is_edit'     => 0,
                // Keep final settlement date aligned with selected Transaction Date.
                'finish_date' => ! empty($settlement->transaction_date)
                    ? \Carbon\Carbon::parse($settlement->transaction_date)->format('Y-m-d')
                    : date("Y-m-d"),
            ]);

            \Log::info('Settlement PD: Settlement Finalized (status = 0) Right Before Commit', [
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'status'        => $settlement->status,
                'finish_date'   => $settlement->finish_date,
            ]);

            DB::commit();
            $transactionCommitted = true;

            // CRITICAL: Reload settlement after commit to ensure status is persisted
            $settlement->refresh();

            // PD Settlement rewrite: run the single posting bridge once after final save.
            // This keeps account books, stock books, customer ledgers, pump operator
            // ledgers and stock transactions linked to the same saved settlement_no.
            try {
                $pdRewritePostingResult = \Modules\PetroPD\Services\SettlementRewrite\Posting\SettlementFinalSavePostingConnector::postAfterFinalize($settlement, $request->all());
                \Log::info('PD Settlement rewrite posting result', [
                    'settlement_id' => $settlement->id,
                    'settlement_no' => $settlement->settlement_no,
                    'posting_result' => $pdRewritePostingResult,
                ]);
            } catch (\Throwable $pdRewritePostingException) {
                \Log::error('PD Settlement rewrite posting bridge failed after finalize', [
                    'settlement_id' => $settlement->id ?? null,
                    'settlement_no' => $settlement->settlement_no ?? null,
                    'error' => $pdRewritePostingException->getMessage(),
                ]);
            }

            // IS1497: one-time safety cleanup after final save.  This removes only
            // exact duplicate ledger/account-book rows linked to this settlement,
            // keeping the first row and soft-deleting later duplicates.
            $this->is1497CleanupDuplicateSettlementPostings($settlement);

            // CRITICAL: Reload all relationships needed for the print view
            // refresh() clears all relations, so we must reload them explicitly
            $settlement->load([
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
            ]);

            $this->ensureSettlementCardAccounting($settlement, $business_id);
            $settlement->load("card_payments");

            \Log::info('Settlement PD: Settlement Status After Commit', [
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'status'        => $settlement->status,
                'status_type'   => gettype($settlement->status),
            ]);

            try {
            } catch (\Exception $e) {
                \Log::warning('Failed to log settlement cash_payments: ' . $e->getMessage());
            }

            $sms_data = [
                "settlement_id"      => $settlement->id,
                "settlement_no"      => $settlement->settlement_no,
                "settlement_date"    => $this->transactionUtil->format_date($settlement->transaction_date),
                "pump_operator_name" => $pump_operator->name,
                "settlement_pumps"   => implode(",", $pumps),
                "total_sale_amount"  => $this->transactionUtil->num_f($total_sales_amount),
                "total_cash"         => $this->transactionUtil->num_f($settlement->cash_payments->sum("amount")),
                "total_cards"        => $this->transactionUtil->num_f($settlement->card_payments->sum("amount")),
                "total_credit_sales" => $this->transactionUtil->num_f($settlement->credit_sale_payments->sum("amount")),
                "total_short"        => $this->transactionUtil->num_f($settlement->shortage_payments->sum("amount")),
                "total_loans"        => $this->transactionUtil->num_f($settlement->customer_loans->sum("amount")),
                "total_cheques"      => $this->transactionUtil->num_f($settlement->cheque_payments->sum("amount")),
                "cash_deposit"       => $this->transactionUtil->num_f($settlement->cash_deposits->sum("amount")),
                "total_expenses"     => $this->transactionUtil->num_f($settlement->expense_payments->sum("amount")),
                "total_excess"       => $this->transactionUtil->num_f($settlement->excess_payments->sum("amount")),
                "loan_payments"      => $this->transactionUtil->num_f($settlement->loan_payments->sum("amount")),
                "owners_drawings"    => $this->transactionUtil->num_f($settlement->drawings_payments->sum("amount")),
                "editted_by"         => auth()->user()->username,
            ];

            if (! empty($edit)) {
                $original_details = SettlementEditHistory::where("settlement_id", $settlement->id)->first();
                $o_details        = "";
                $n_details        = "";
                $is_changed       = false;
                $changed_msg      = "";

                if (! empty($original_details)) {
                    // Check each field for changes and append to message if changed
                    $fields_to_check = [
                        "settlement_date",
                        "pump_operator_name",
                        "settlement_pumps",
                        "total_sale_amount",
                        "total_cash",
                        "total_cards",
                        "total_credit_sales",
                        "total_short",
                        "total_loans",
                        "total_cheques",
                    ];

                    foreach ($fields_to_check as $field) {
                        if ($original_details->$field != $sms_data[$field]) {
                            $is_changed   = true;
                            $changed_msg .= __("petropd::lang.$field") .
                                __("petropd::lang.changed_from") .
                                $original_details->$field .
                                __("petropd::lang.to") .
                                $sms_data[$field] . PHP_EOL;

                            $o_details .= __("petropd::lang.$field") . ": " . $original_details->$field . PHP_EOL;
                            $n_details .= __("petropd::lang.$field") . ": " . $sms_data[$field] . PHP_EOL;
                        }
                    }

                    if (! empty($is_changed) && ! empty($changed_msg)) {
                        $activity               = new Activity();
                        $activity->log_name     = "Settlement PD";
                        $activity->description  = "update";
                        $activity->subject_id   = $settlement->id;
                        $activity->subject_type = "App\Settlement";
                        $activity->causer_id    = auth()->user()->id;
                        $activity->causer_type  = "App\User";
                        $activity->properties   = $changed_msg;
                        $activity->created_at   = date("Y-m-d H:i");
                        $activity->updated_at   = date("Y-m-d H:i");
                        $activity->save();
                    }
                }

                $data = [
                    "settlement_no"    => $settlement->settlement_no,
                    "editted_date"     => $this->transactionUtil->format_date(date("Y-m-d")),
                    "user_editted"     => auth()->user()->username,
                    "original_details" => $o_details,
                    "editted_details"  => $n_details,
                ];

                if (Str::startsWith((string) $settlement->settlement_no, 'PDST')) {
                    $this->notificationUtil->sendPetroNotification(
                        "edit_settlements",
                        $data
                    );
                }
            } else {
                if (Str::startsWith((string) $settlement->settlement_no, 'PDST')) {
                    $this->notificationUtil->sendPetroNotification(
                        "settlements",
                        $sms_data
                    );
                }
            }

            SettlementEditHistory::updateOrCreate(
                ["settlement_id" => $settlement->id],
                $sms_data
            );

            // IS1831: The Petro PD screen redirects to List Settlement after a
            // successful AJAX save, so it does not consume the large inline
            // print preview. All financial posting, finalization, accounting,
            // notifications and edit-history work above has already completed.
            // Return now instead of running the print-only queries/rendering
            // below, which made the Save button appear to hang.
            if (($request->ajax() || $request->wantsJson())
                && $request->input('source') === 'petro_pd') {
                try {
                    $this->notifyPetroPdSettlementSaved($settlement);
                } catch (\Throwable $notificationException) {
                    Log::warning('PetroPD settlement saved, but notification dispatch failed.', [
                        'settlement_id' => $settlement->id ?? null,
                        'settlement_no' => $settlement->settlement_no ?? null,
                        'error' => $notificationException->getMessage(),
                    ]);
                }

                return response()->json([
                    "success"       => 1,
                    "msg"           => __("petropd::lang.settlement_saved_successfully") ?: "Settlement saved successfully",
                    "settlement_id" => $settlement->id,
                    "settlement_no" => $settlement->settlement_no,
                    "redirect_url"  => route('petropd.list-pd-settlement', [
                        'saved_settlement_id' => $settlement->id,
                        '_ts' => now()->timestamp,
                    ]),
                    "print_url"     => route('petropd.settlement-pd.print', [$settlement->id]),
                    "html"          => null,
                ]);
            }

            // $total_daily_collection = floatval(
            //     DailyCollection::where("pump_operator_id", $settlement->pump_operator_id)
            //         ->where("business_id", $business_id)
            //         ->where("settlement_id", $settlement->id)
            //         ->sum("current_amount")
            // );

            // Calculate final_cash_amount from SettlementCashPayment records (most accurate)
            // Calculate it here before it's used for total_daily_collection
            $cash_payments_for_calc = SettlementCashPayment::whereIn('settlement_no', array_values(array_filter([(string) $settlement->id, (string) $settlement->settlement_no], static fn ($k) => $k !== '')))
                ->where('business_id', $business_id)
                ->get();
            $cash_payments_total_for_calc = $cash_payments_for_calc->sum('amount');

            // IMPORTANT: Do NOT use daily_collections as fallback if SettlementCashPayment records exist
            // because SettlementCashPayment records are created FROM DailyCollection entries,
            // which would cause double counting. Only use daily_collections if no SettlementCashPayment exists.
            $final_cash_amount = $cash_payments_total_for_calc;

            // Only fallback to daily_collections if there are NO SettlementCashPayment records
            // (for backward compatibility with old settlements that might not have SettlementCashPayment records)
            if ($cash_payments_total_for_calc == 0) {
                $daily_collections_total = DB::table('daily_collections')
                    ->where("settlement_id", $settlement->id)
                    ->where('type', 'daily_collection')
                    ->selectRaw('COALESCE(SUM(current_amount + COALESCE(balance_collection,0)), 0) as total')
                    ->value('total') ?? 0;
                $final_cash_amount = $daily_collections_total;
            }

            // For total_daily_collection, use the same value
            $total_daily_collection  = floatval($final_cash_amount);

            $msg_template_wahtsapp = PetroWhatsAppTemplate::where("business_id", $business_id)
                ->where("auto_send_sms", 1)
                ->first();

            $business_locations = BusinessLocation::where("business_id", $business_id)
                ->pluck("name")
                ->first();

            $business_details = Business::find($business_id);

            $subscription = Subscription::active_subscription($business_id);

            $whatsapp_phone_no = 0;
            if (! empty($subscription)) {
                $pacakge_details   = $subscription->package_details;
                $whatsapp_phone_no = $pacakge_details["whatsapp_phone_no"];
            }

            // Commented WhatsApp sending logic
            /*
            if (!empty($whatsapp_phone_no) && !empty($msg_template_wahtsapp)) {
                $msg = $sms_data;

                $phones = [];
                if (!empty($business->sms_settings)) {
                    $phones = explode(',', str_replace(' ', '', $business->sms_settings['msg_phone_nos']));
                }

                $clean_phone = $whatsapp_phone_no;
                $text = "";
                foreach ($msg as $key => $value) {
                    $text .= "$key: $value\n";
                }

                $encoded_msg = urlencode($text);
                $whatsapp_url = "https://wa.me/{$clean_phone}?text={$encoded_msg}";

                return redirect()->away($whatsapp_url);
            }
            */

            //$shift_ids = $request->shift_ids;

            // Reload cash payments - use ID (integer) to match cash_payments relationship
            $cash_payments_query = SettlementCashPayment::whereIn('settlement_no', array_values(array_filter([(string) $settlement->id, (string) $settlement->settlement_no], static fn ($k) => $k !== '')))
                ->where('business_id', $business_id);

            if (! empty($shift_ids_for_filter)) {
                $cash_payments_query->where(function ($q) use ($shift_ids_for_filter) {
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

            // Calculate final_cash_amount from SettlementCashPayment records (most accurate)
            $cash_payments_total = $settlement->cash_payments->sum('amount');

            \Log::info('Settlement PD Store: Cash Payments Calculation', [
                'settlement_id'       => $settlement->id,
                'settlement_no'       => $settlement->settlement_no,
                'cash_payments_count' => $settlement->cash_payments->count(),
                'cash_payments_total' => $cash_payments_total,
            ]);

            // IMPORTANT: Do NOT use daily_collections as fallback if SettlementCashPayment records exist
            // because SettlementCashPayment records are created FROM DailyCollection entries,
            // which would cause double counting. Only use daily_collections if no SettlementCashPayment exists.
            $final_cash_amount = $cash_payments_total;

            // Only fallback to daily_collections if there are NO SettlementCashPayment records
            // (for backward compatibility with old settlements that might not have SettlementCashPayment records)
            if ($cash_payments_total == 0) {
                $daily_collections_total = DB::table('daily_collections')
                    ->where("settlement_id", $settlement->id)
                    ->where('type', 'daily_collection')
                    ->selectRaw('COALESCE(SUM(current_amount + COALESCE(balance_collection,0)), 0) as total')
                    ->value('total') ?? 0;
                $final_cash_amount = $daily_collections_total;

                \Log::info('Settlement PD Store: Using DailyCollections Fallback', [
                    'daily_collections_total' => $daily_collections_total,
                    'final_cash_amount'       => $final_cash_amount,
                ]);
            }

            // Credit sale payments for the print preview - filtered to the settled shift(s) only.
            // Uses the same logic as print() to ensure the popup only shows the relevant credit sales.
            $credit_sale_payments_for_print = $this->getFilteredCreditSalePayments(
                $settlement,
                array_values(array_filter(array_map('intval', $shift_ids_for_filter)))
            );
            $settlement->setRelation('credit_sale_payments', $credit_sale_payments_for_print);

            \Log::info('Settlement PD Store: Credit Sales Loaded for Print', [
                'settlement_id'              => $settlement->id,
                'settlement_no'             => $settlement->settlement_no,
                'credit_sale_payments_count' => $credit_sale_payments_for_print->count(),
                'credit_sales'               => $credit_sale_payments_for_print->pluck('id', 'collection_form_no')->toArray(),
            ]);

            // Final verification: Check credit sales one more time before returning
            $final_credit_sales_check = SettlementCreditSalePayment::where('pump_operator_id', $settlement->pump_operator_id)
                ->where('settlement_no', $settlement->settlement_no)
                ->get(['id', 'order_number', 'customer_id', 'settlement_no', 'daily_voucher_id']);

            \Log::info('Settlement PD Store: Final Credit Sales Verification', [
                'settlement_id' => $settlement->id,
                'settlement_no' => $settlement->settlement_no,
                'final_count'   => $final_credit_sales_check->count(),
                'credit_sales'  => $final_credit_sales_check->toArray(),
            ]);

            // Also verify DailyVouchers are updated. Do not apply the
            // PetroPD shift IDs to daily_vouchers.shift_id: that foreign key
            // belongs to the legacy petro_daily_shifts table.
            $daily_voucher_verification_columns = ['id', 'daily_vouchers_no', 'settlement_no'];
            if (Schema::hasColumn('daily_vouchers', 'pump_payment_id')) {
                $daily_voucher_verification_columns[] = 'pump_payment_id';
            }

            $final_daily_vouchers_check = DailyVoucher::where('business_id', $settlement->business_id)
                ->where('operator_id', $settlement->pump_operator_id)
                ->where('settlement_no', $settlement->settlement_no)
                ->get($daily_voucher_verification_columns);

            \Log::info('Settlement PD Store: Final DailyVouchers Verification', [
                'settlement_id'  => $settlement->id,
                'settlement_no'  => $settlement->settlement_no,
                'final_count'    => $final_daily_vouchers_check->count(),
                'daily_vouchers' => $final_daily_vouchers_check->toArray(),
            ]);

            $shift_ids = array_values(array_filter(array_map('intval', (array) $shift_ids_for_filter)));
            $this->hydrateSettlementPdMeterSalesForDisplay($settlement, $shift_ids);
            $settlementLookups = $this->buildSettlementViewLookups($settlement, (int) $settlement->business_id);

            // The settlement transaction was already committed immediately after
            // the finalized status was written. Do not commit a second time:
            // a second DB::commit() raises "There is no active transaction"
            // and incorrectly reports a failed save after the settlement is saved.
            try {
                $this->notifyPetroPdSettlementSaved($settlement);
            } catch (\Throwable $notificationException) {
                Log::warning('PetroPD settlement saved, but notification dispatch failed.', [
                    'settlement_id' => $settlement->id ?? null,
                    'settlement_no' => $settlement->settlement_no ?? null,
                    'error' => $notificationException->getMessage(),
                ]);
            }

            // Return JSON response for AJAX requests, view for regular requests
            if ($request->ajax() || $request->wantsJson()) {
                $redirect_url = $request->input('source') === 'petro_pd'
                    ? route('petropd.list-pd-settlement', ['saved_settlement_id' => $settlement->id, '_ts' => now()->timestamp])
                    : action("\Modules\PetroPD\Http\Controllers\PetroPDSettlementController@create");

                return response()->json([
                    "success"       => 1,
                    "msg"           => __("petropd::lang.settlement_saved_successfully") ?: "Settlement saved successfully",
                    "settlement_id" => $settlement->id,
                    "settlement_no" => $settlement->settlement_no,
                    "redirect_url"  => $redirect_url,
                    "print_url"     => route('petropd.settlement-pd.print', [$settlement->id]),
                    "html"          => view("petropd::pd_settlement.print")->with(
                        compact(
                            "settlement",
                            "business",
                            "pump_operator",
                            "customer_payments_tab",
                            "total_daily_collection",
                            "final_cash_amount",
                            "shift_ids",
                            "settlementLookups"
                        )
                    )->render(),
                ]);
            }

            return view("petropd::pd_settlement.print")->with(
                compact(
                    "settlement",
                    "business",
                    "pump_operator",
                    "customer_payments_tab",
                    "total_daily_collection",
                    "final_cash_amount",
                    "shift_ids",
                    "settlementLookups"
                )
            );
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::emergency(
                "File: " . $e->getFile() .
                    " Line: " . $e->getLine() .
                    " Message: " . $e->getMessage()
            );

            // Once the transaction has committed, a later print/notification
            // problem must never tell the operator that the financial save
            // failed. Return a successful finalized response and let the print
            // route load the persisted settlement independently.
            if ($transactionCommitted && ! empty($settlement)) {
                $savedSettlement = Settlement::where('business_id', $settlement->business_id)
                    ->where('id', $settlement->id)
                    ->first();

                if (! empty($savedSettlement) && (int) $savedSettlement->status === 0) {
                    $redirectUrl = $request->input('source') === 'petro_pd'
                        ? route('petropd.list-pd-settlement', [
                            'saved_settlement_id' => $savedSettlement->id,
                            '_ts' => now()->timestamp,
                        ])
                        : action("\Modules\PetroPD\Http\Controllers\PetroPDSettlementController@create");

                    $successOutput = [
                        'success' => 1,
                        'msg' => __('petropd::lang.settlement_saved_successfully') ?: 'Settlement saved successfully',
                        'settlement_id' => $savedSettlement->id,
                        'settlement_no' => $savedSettlement->settlement_no,
                        'redirect_url' => $redirectUrl,
                        'html' => null,
                        'warning' => 'Settlement saved, but the inline print preview could not be prepared. Use the settlement print action to reprint.',
                    ];

                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json($successOutput);
                    }

                    return redirect($redirectUrl)->with('status', $successOutput);
                }
            }

            $output = [
                "success" => 0,
                "msg"     => $e->getMessage(),
            ];

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json($output, 500);
            }

            return $output;
        } finally {
            if ($finalizationLock !== null) {
                try {
                    $finalizationLock->release();
                } catch (\Throwable $releaseException) {
                    Log::warning('PETROPD settlement finalization lock release failed', [
                        'error' => $releaseException->getMessage(),
                    ]);
                }
            }
        }
    }

    public function createSettlementIfNotExist(Request $request)
    {

        $business_id = $request->session()->get("business.id");
        $business_id = ! empty($business_id) ? $business_id : $request->session()->get("user.business_id");
        $pump_operator_id = ! empty($request->pump_operator_id) ? $request->pump_operator_id : (! empty($request->operator_id) ? $request->operator_id : null);
        $settlement_no = trim((string) ($request->settlement_no ?? ''));
        $isPetroPdRequest = $this->isPetroPdModuleRequest($request)
            || $this->isPetroPdModuleSettlementNo($settlement_no, $business_id);
        $transaction_date = \Carbon::parse(
            $request->transaction_date
        )->format("Y-m-d");
        $requested_shift_ids = $this->getRequestedPetroPdShiftIds($request);

        // Reuse a pending Petro PD draft only when it belongs to the exact
        // selected closed shift.  Reusing the latest global PDST draft can mix
        // different operators/shifts and is the main cause of missing autoloaded
        // sales, income and payment details.
        $active_settlement_id = (int) $request->input('active_settlement_id', 0);
        if (empty($active_settlement_id) && empty($settlement_no) && ! empty($requested_shift_ids)) {
            $pending_pd_settlement = Settlement::where('business_id', $business_id)
                ->where('status', 1)
                ->when(! empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                    $query->where('pump_operator_id', $pump_operator_id);
                })
                ->where(function ($query) use ($business_id) {
                    foreach ($this->getPetroPdModuleSettlementPrefixes($business_id) as $prefix) {
                        $query->orWhere('settlement_no', 'LIKE', $prefix . '%');
                    }
                })
                ->whereExists(function ($query) use ($business_id, $requested_shift_ids) {
                    $query->select(DB::raw(1))
                        ->from('pump_operator_assignments as poa_pd_draft')
                        ->whereColumn('poa_pd_draft.settlement_id', 'settlements.id')
                        ->where('poa_pd_draft.business_id', $business_id)
                        ->whereIn('poa_pd_draft.shift_id', $requested_shift_ids);
                })
                ->orderByDesc('id')
                ->first();

            if (! empty($pending_pd_settlement)) {
                $request->merge(['settlement_no' => $pending_pd_settlement->settlement_no]);
                $this->linkPetroPdDraftToRequestedShift(
                    $pending_pd_settlement,
                    $request,
                    (int) $business_id,
                    ! empty($pump_operator_id) ? (int) $pump_operator_id : null
                );
                return $pending_pd_settlement;
            }
        }

        // When editing an existing settlement (finalized or draft), reuse it
        // regardless of status so we don't spawn a new "Pending" settlement.
        if ($active_settlement_id > 0 && ! empty($pump_operator_id)) {
            $active_settlement = Settlement::where('id', $active_settlement_id)
                ->where('business_id', $business_id)
                ->first();

            if (! empty($active_settlement) && (int) $active_settlement->pump_operator_id === (int) $pump_operator_id) {
                $request->merge(['settlement_no' => $active_settlement->settlement_no]);
                $this->linkPetroPdDraftToRequestedShift(
                    $active_settlement,
                    $request,
                    (int) $business_id,
                    (int) $pump_operator_id
                );
                return $active_settlement;
            }
        }

        if ($isPetroPdRequest && ! $this->isPetroPdModuleSettlementNo($settlement_no, $business_id)) {
            $settlement_no = '';
        }

        if (empty($settlement_no)) {
            // S 268 FIX (2026-05-31): For PetroPD, never reuse a draft just because the
            // same operator/date/location already has status = 1. The draft must match the
            // selected shift. Otherwise Shift 1 and Shift 2 can get the same Settlement No.
            // Use the actual Pumper Dashboard shift IDs resolved above.
            // Do not substitute the business work_shift selector here.

            $existing_draft = null;

            if (! $isPetroPdRequest || ! empty($requested_shift_ids) || ! empty($request->direct_shift_number)) {
                $existing_draft_query = Settlement::where("business_id", $business_id)
                    ->when(! empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                        $query->where("pump_operator_id", $pump_operator_id);
                    })
                    ->when(! empty($request->location_id), function ($query) use ($request) {
                        $query->where("location_id", $request->location_id);
                    })
                    ->where("status", 1)
                    ->whereDate("transaction_date", $transaction_date);

                if ($isPetroPdRequest) {
                    $this->applyPetroPdModuleSettlementScope($existing_draft_query, $business_id, 'settlement_no');

                    if (! empty($requested_shift_ids)) {
                        $existing_draft_query->where(function ($query) use ($requested_shift_ids) {
                            foreach ($requested_shift_ids as $shift_id) {
                                $query->orWhere('work_shift', 'LIKE', '%"' . $shift_id . '"%')
                                    ->orWhere('work_shift', 'LIKE', '%[' . $shift_id . ']%')
                                    ->orWhere('work_shift', 'LIKE', '%,' . $shift_id . ',%')
                                    ->orWhere('work_shift', 'LIKE', $shift_id);
                            }
                        });
                    }
                } else {
                    $this->applyPdSettlementScope($existing_draft_query, $business_id);
                }

                $existing_draft = $existing_draft_query
                    ->orderByDesc("id")
                    ->first();
            }

            if (! empty($existing_draft) && ! empty($existing_draft->settlement_no)) {
                $settlement_no = $existing_draft->settlement_no;
            } else {
                $business = Business::find($business_id);
                $ref_no_prefixes = $business->ref_no_prefixes ?? [];
                $prefix = $isPetroPdRequest
                    ? $this->getPrimaryPetroPdModuleSettlementPrefix($business_id)
                    : (! empty($ref_no_prefixes["settlement"]) ? $ref_no_prefixes["settlement"] : "ST");

                $count = Settlement::where("business_id", $business_id)
                    ->where("settlement_no", "LIKE", $prefix . "%");

                if ($isPetroPdRequest) {
                    $this->applyPetroPdModuleSettlementScope($count, $business_id, 'settlement_no');
                } else {
                    $count->where("settlement_no", "NOT LIKE", "SET-SW%");
                    $this->excludePetroPdModuleSettlements($count, $business_id, 'settlement_no');
                }

                // S 268 FIX FOLLOW-UP (2026-05-31): Use the highest numeric suffix,
                // not simply the latest row ID, so PDST2 correctly leads to PDST3.
                $count = $count
                    ->pluck("settlement_no")
                    ->map(function ($settlementNo) {
                        return $this->extractLastInteger($settlementNo);
                    })
                    ->max();

                $nextNumber = ((int) $count) + 1;
                while (Settlement::where('business_id', $business_id)->where('settlement_no', $prefix . $nextNumber)->exists()) {
                    $nextNumber++;
                }
                $settlement_no = $prefix . $nextNumber;
            }

            $request->merge(["settlement_no" => $settlement_no]);
        }

        // Final safety guard: Petro PD settlements must never save an ST number.
        if (! $this->isPetroPdModuleSettlementNo($settlement_no, $business_id)) {
            $prefix = $this->getPrimaryPetroPdModuleSettlementPrefix($business_id);
            $maxNumber = Settlement::where("business_id", $business_id)
                ->where(function ($query) use ($business_id) {
                    foreach ($this->getPetroPdModuleSettlementPrefixes($business_id) as $prefixOption) {
                        $query->orWhere("settlement_no", "LIKE", $prefixOption . "%");
                    }
                })
                ->pluck("settlement_no")
                ->map(function ($settlementNo) {
                    return $this->extractLastInteger($settlementNo);
                })
                ->max();
            $nextNumber = ((int) $maxNumber) + 1;
            while (Settlement::where('business_id', $business_id)->where('settlement_no', $prefix . $nextNumber)->exists()) {
                $nextNumber++;
            }
            $settlement_no = $prefix . $nextNumber;
            $request->merge(["settlement_no" => $settlement_no]);
        }

        $settlement_data = [

            "settlement_no"    => $settlement_no,

            "business_id"      => $business_id,

            "transaction_date" => $transaction_date,

            "location_id"      => $request->location_id,

            "pump_operator_id" => $pump_operator_id,

            "work_shift"       => $isPetroPdRequest && ! empty($requested_shift_ids)
                ? $requested_shift_ids
                : (! empty($request->direct_shift_number)
                    ? [$request->direct_shift_number]
                    : (! empty($request->work_shift)
                        ? $request->work_shift
                        : [])),

            "note"             => $request->note,

            "status"           => 1,

        ];

        $latest_date =

            DayEnd::where("business_id", $business_id)

            ->get()

            ->last()->day_end_date ?? null;

        if (

            ! empty($latest_date) &&

            strtotime($latest_date) >=

            strtotime($settlement_data["transaction_date"])

        ) {

            return 406;
        }

        $settlement_exist = Settlement::where(

            "settlement_no",

            $settlement_no

        )

            ->where("business_id", $business_id)
            ->when(! empty($pump_operator_id), function ($query) use ($pump_operator_id) {
                $query->where("pump_operator_id", $pump_operator_id);
            })

            ->first();

        if (empty($settlement_exist)) {

            $settlement_exist = Settlement::create($settlement_data);
        }

        if ($isPetroPdRequest && ! empty($settlement_exist)) {
            $this->linkPetroPdDraftToRequestedShift(
                $settlement_exist,
                $request,
                (int) $business_id,
                ! empty($pump_operator_id) ? (int) $pump_operator_id : null
            );
        }

        return $settlement_exist;
    }

    /**







     * print resources







     * @param settlement_id







     * @return Response







     */

    private function getRequestedPetroPdShiftIds(Request $request): array
    {
        $raw = $request->input('shift_ids', $request->input('shift_id'));

        if (empty($raw) && ! empty($request->direct_shift_number)) {
            $raw = $request->direct_shift_number;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : explode(',', $raw);
        }

        return array_values(array_unique(array_filter(array_map('intval', (array) $raw))));
    }

    /**
     * Link a pending Petro PD draft to its exact closed shift as soon as the
     * first sales, income or payment detail is saved.  Without this link the
     * next page load cannot identify the draft and all previously entered
     * details appear blank even though they remain in the database.
     */

    private function linkPetroPdDraftToRequestedShift(
        Settlement $settlement,
        Request $request,
        int $businessId,
        ?int $pumpOperatorId
    ): void {
        $shiftIds = $this->getRequestedPetroPdShiftIds($request);

        // This helper is only for an in-progress draft. Never reopen assignment
        // flags while an already-finalized settlement is being viewed or edited.
        if ((int) $settlement->status !== 1 || empty($shiftIds) || empty($pumpOperatorId)) {
            return;
        }

        $hasConflictingSettlement = PumpOperatorAssignment::where('business_id', $businessId)
            ->where('pump_operator_id', $pumpOperatorId)
            ->whereIn('shift_id', $shiftIds)
            ->whereNotNull('settlement_id')
            ->where('settlement_id', '<>', $settlement->id)
            ->exists();

        if ($hasConflictingSettlement) {
            Log::warning('PetroPD draft shift link skipped because the shift already belongs to another settlement.', [
                'settlement_id' => $settlement->id,
                'pump_operator_id' => $pumpOperatorId,
                'shift_ids' => $shiftIds,
            ]);
            return;
        }

        PumpOperatorAssignment::where('business_id', $businessId)
            ->where('pump_operator_id', $pumpOperatorId)
            ->whereIn('shift_id', $shiftIds)
            ->whereIn('status', ['close', 'closed'])
            ->where(function ($query) use ($settlement) {
                $query->whereNull('settlement_id')
                    ->orWhere('settlement_id', $settlement->id);
            })
            ->update([
                'settlement_id' => $settlement->id,
                'closed_in_settlement' => 0,
            ]);

        $currentWorkShift = $settlement->work_shift;
        if (is_string($currentWorkShift)) {
            $decoded = json_decode($currentWorkShift, true);
            $currentWorkShift = is_array($decoded) ? $decoded : [];
        }
        $currentWorkShift = is_array($currentWorkShift) ? $currentWorkShift : [];
        $mergedShiftIds = array_values(array_unique(array_filter(array_map(
            'intval',
            array_merge($currentWorkShift, $shiftIds)
        ))));

        if ($mergedShiftIds !== array_values(array_map('intval', $currentWorkShift))) {
            $settlement->work_shift = $mergedShiftIds;
            $settlement->save();
        }
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

            // S282-001: A draft can legitimately contain only Card/Shortage rows before
            // any meter sale is linked. In that case refresh must not discard the active
            // settlement and generate a new settlement number, otherwise Cards & Shortage
            // rows disappear from Payment to Finalize. Resume the oldest closed un-settled
            // shift for the same operator as a safe display context.
            if (! empty($settlement->pump_operator_id)) {
                $fallbackAssignment = PumpOperatorAssignment::leftJoin('petro_shifts as ps', 'pump_operator_assignments.shift_id', '=', 'ps.id')
                    ->where('pump_operator_assignments.business_id', $business_id)
                    ->where('pump_operator_assignments.pump_operator_id', $settlement->pump_operator_id)
                    ->where('pump_operator_assignments.status', 'close')
                    ->where(function ($q) use ($settlement) {
                        $q->where('pump_operator_assignments.settlement_id', $settlement->id)
                          ->orWhereNull('pump_operator_assignments.settlement_id');
                    })
                    // The close-batch flag can already be 1 before settlement; the
                    // settlement link above is the safe ownership check for draft resume.
                    ->whereNotNull('pump_operator_assignments.close_date_and_time')
                    ->select(
                        'pump_operator_assignments.shift_number',
                        'pump_operator_assignments.shift_id',
                        'pump_operator_assignments.pump_operator_id',
                        'ps.work_shift_id'
                    )
                    ->orderByRaw('CAST(pump_operator_assignments.shift_number AS UNSIGNED) ASC')
                    ->orderBy('pump_operator_assignments.shift_number', 'asc')
                    ->orderBy('pump_operator_assignments.close_date_and_time', 'asc')
                    ->orderBy('pump_operator_assignments.shift_id', 'asc')
                    ->first();

                if (! empty($fallbackAssignment) && ! empty($fallbackAssignment->shift_id)) {
                    return [
                        'valid' => true,
                        'reason' => null,
                        'shift_id' => (int) $fallbackAssignment->shift_id,
                        'pump_operator_id' => (int) $fallbackAssignment->pump_operator_id,
                        'shift_numbers' => [
                            (int) $fallbackAssignment->shift_id => [
                                'shift_number' => (string) $fallbackAssignment->shift_number,
                                'work_shift_id' => $fallbackAssignment->work_shift_id,
                                'pump_operator_id' => (int) $fallbackAssignment->pump_operator_id,
                                'is_payment_only_draft_fallback' => true,
                            ],
                        ],
                    ];
                }
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
            ->orderByRaw('CAST(pump_operator_assignments.shift_number AS UNSIGNED) ASC')
            ->orderBy('pump_operator_assignments.shift_number', 'asc')
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

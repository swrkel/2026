<?php

namespace Modules\PumperDashboard\Http\Controllers;

use App\Business;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\PumperDashboard\Entities\PetroShift;
use Modules\PumperDashboard\Entities\PumpOperator;
use Modules\PumperDashboard\Entities\PumpOperatorAssignment;
use Modules\PumperDashboard\Entities\PumpOperatorOtherSale;
use Modules\PumperDashboard\Entities\PumpOperatorPayment;
use Modules\PumperDashboard\Entities\PumperDayEntry;
use Modules\PumperDashboard\Services\PumperDashboardSchema;

/**
 * MA-008: the Close Shift Summary print.
 *
 * Builds the report for one shift and renders it in the approved layout.
 *
 * The figures are deliberately gathered the SAME way the on-screen Close Shift
 * summary gathers them (PumperDayEntryController::getClosingShiftSummary and
 * the calculations at the top of partials/closing_shift_summary.blade.php), so
 * the printout cannot disagree with the screen the operator just looked at:
 *
 *     total sales          = sum of day entry amounts
 *     other sales          = sum of (sub_total - discount_amount)
 *     payments             = per type, from pump_operator_payments
 *     balance to settle    = total sales + other sales - payments (incl. short/excess)
 */
class CloseShiftSummaryPrintController extends Controller
{
    public function __construct(private readonly ModuleUtil $moduleUtil)
    {
    }

    public function print(Request $request, $shift_id)
    {
        $business_id = (int) ($request->session()->get('user.business_id')
            ?: (auth()->user()->business_id ?? 0));

        $shift = PetroShift::where('business_id', $business_id)
            ->whereKey($shift_id)
            ->firstOrFail();

        /*
         * A pumper may only print their own shift. Admin users reaching this from
         * the dashboard are not restricted, mirroring how the summary partial uses
         * $only_pumper.
         */
        $pump_operator_id = auth()->user()->pump_operator_id ?? null;
        $only_pumper = ! empty($pump_operator_id);

        if ($only_pumper) {
            $belongsToOperator = PumpOperatorAssignment::where('business_id', $business_id)
                ->where('shift_id', $shift->id)
                ->where('pump_operator_id', $pump_operator_id)
                ->exists();

            if (! $belongsToOperator && (int) $shift->pump_operator_id !== (int) $pump_operator_id) {
                abort(403, 'This shift does not belong to the signed in pump operator.');
            }
        }

        $operator_id_for_figures = $only_pumper ? $pump_operator_id : $shift->pump_operator_id;

        /*
         * ---- Day entries (pump sales) -----------------------------------------
         *
         * LA-1168: pumper_day_entries does NOT reliably have a shift_id column.
         * An earlier version of this method filtered on it directly and every
         * print threw "Unknown column 'shift_id'".
         *
         * The link to a shift is through pump_operator_assignments, which is how
         * PumperDayEntryController::getClosingShiftSummary() does it. shift_id on
         * the entry itself is only consulted where that column actually exists,
         * using the same schema check the rest of this module uses, so installs
         * that do have it keep working exactly as before.
         */
        $has_day_entry_shift_id = PumperDashboardSchema::hasColumn('pumper_day_entries', 'shift_id');

        $day_entries = PumperDayEntry::leftJoin(
                'pump_operator_assignments',
                'pumper_day_entries.pumper_assignment_id',
                '=',
                'pump_operator_assignments.id'
            )
            ->where('pumper_day_entries.business_id', $business_id)
            ->where(function ($shift_query) use ($shift, $has_day_entry_shift_id) {
                $shift_query->where('pump_operator_assignments.shift_id', $shift->id);

                if ($has_day_entry_shift_id) {
                    $shift_query->orWhere('pumper_day_entries.shift_id', $shift->id);
                }
            })
            ->when($only_pumper, fn ($q) => $q->where('pumper_day_entries.pump_operator_id', $pump_operator_id))
            ->select('pumper_day_entries.*')
            ->get();

        $total_sales = (float) $day_entries->sum('amount');

        // ---- Payments by type --------------------------------------------------
        $payments = PumpOperatorPayment::query()
            ->where('business_id', $business_id)
            ->where('shift_id', $shift->id)
            ->when($only_pumper, fn ($q) => $q->where('pump_operator_id', $pump_operator_id))
            ->select(
                DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) = "cash" THEN payment_amount ELSE 0 END), 0) as cash'),
                DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) = "card" THEN payment_amount ELSE 0 END), 0) as card'),
                DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) = "cheque" THEN payment_amount ELSE 0 END), 0) as cheque'),
                DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) = "credit" THEN payment_amount ELSE 0 END), 0) as credit'),
                DB::raw('COALESCE(SUM(CASE WHEN LOWER(TRIM(payment_type)) = "other" THEN payment_amount ELSE 0 END), 0) as other'),
                DB::raw('COALESCE(SUM(payment_amount), 0) as total')
            )
            ->first();

        $cash = (float) ($payments->cash ?? 0);
        $card = (float) ($payments->card ?? 0);
        $cheque = (float) ($payments->cheque ?? 0);
        $credit = (float) ($payments->credit ?? 0);
        $payment_total = (float) ($payments->total ?? 0);

        // ---- Other sales -------------------------------------------------------
        /*
         * LA-1168: not filtered by pump_operator_id. The on-screen summary sums
         * other sales for the SHIFT only (see PumperDayEntryController), and this
         * print has to agree with it. pump_operator_id is also not a column that
         * is relied on here.
         */
        $other_sale_total = (float) (PumpOperatorOtherSale::where('business_id', $business_id)
            ->where('shift_id', $shift->id)
            ->sum(DB::raw('sub_total - discount_amount')) ?? 0);

        // Identical to the on-screen calculation - see the class note above.
        $balance_to_settle = $total_sales + $other_sale_total - $payment_total;

        // ---- Closed pumps ------------------------------------------------------
        $closed_pumps = PumpOperatorAssignment::join('pumps', 'pumps.id', '=', 'pump_operator_assignments.pump_id')
            ->where('pump_operator_assignments.business_id', $business_id)
            ->where('pump_operator_assignments.shift_id', $shift->id)
            ->when($only_pumper, fn ($q) => $q->where('pump_operator_assignments.pump_operator_id', $pump_operator_id))
            ->where('pump_operator_assignments.status', 'close')
            ->pluck('pumps.pump_no')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $shift_number = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('shift_id', $shift->id)
            ->when($only_pumper, fn ($q) => $q->where('pump_operator_id', $pump_operator_id))
            ->value('shift_number');

        // ---- Cash breakdown and credit sales detail ----------------------------
        /*
         | LA-1169 #1: the row keys MUST be customer_name and order_number.
         |
         | The print view reads each row with
         |     data_get($row, 'customer_name')  and  data_get($row, 'order_number')
         | but this method returned them as 'customer' and 'order_no'. data_get
         | found nothing and fell back to its em dash default, so both columns
         | printed as "-" on every line even though the data was available.
         |
         | The customer is also joined in now. Cash rows previously hard coded
         | null, so the Customer column could never show anything at all.
         */
        $cash_breakdown = PumpOperatorPayment::where('pump_operator_payments.business_id', $business_id)
            ->where('pump_operator_payments.shift_id', $shift->id)
            ->when($only_pumper, fn ($q) => $q->where('pump_operator_payments.pump_operator_id', $pump_operator_id))
            ->whereRaw('LOWER(TRIM(pump_operator_payments.payment_type)) = "cash"')
            ->leftJoin('contacts', 'contacts.id', '=', 'pump_operator_payments.customer_id')
            ->orderBy('pump_operator_payments.id')
            ->select('pump_operator_payments.*', 'contacts.name as contact_name')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date_and_time ?? $row->created_at,
                'time' => $row->date_and_time ?? $row->created_at,
                'customer_name' => $row->contact_name,
                'order_number' => $row->collection_form_no ?? null,
                'amount' => (float) $row->payment_amount,
            ])
            ->all();

        $credit_sales_details = PumpOperatorPayment::where('pump_operator_payments.business_id', $business_id)
            ->where('pump_operator_payments.shift_id', $shift->id)
            ->when($only_pumper, fn ($q) => $q->where('pump_operator_payments.pump_operator_id', $pump_operator_id))
            ->whereRaw('LOWER(TRIM(pump_operator_payments.payment_type)) = "credit"')
            ->leftJoin('contacts', 'contacts.id', '=', 'pump_operator_payments.customer_id')
            ->orderBy('pump_operator_payments.id')
            ->select('pump_operator_payments.*', 'contacts.name as contact_name')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date_and_time ?? $row->created_at,
                'time' => $row->date_and_time ?? $row->created_at,
                // Same key names as the cash rows above - see the note there.
                'customer_name' => $row->contact_name,
                'order_number' => $row->collection_form_no ?? null,
                'amount' => (float) $row->payment_amount,
            ])
            ->all();

        $operator_name = PumpOperator::where('business_id', $business_id)
            ->whereKey($operator_id_for_figures)
            ->value('name');

        $report = [
            'business_name' => Business::where('id', $business_id)->value('name'),
            /*
             * LA-1168: closed_time is the column this module actually sets when a
             * shift closes (ClosingShiftController). created_at is the fallback -
             * no other date column on petro_shifts is assumed to exist, because an
             * assumed column is what caused the original page error.
             */
            'date' => $shift->closed_time ?: $shift->created_at,
            'shift_number' => $shift_number ?? $shift->id,
            'operator_name' => $operator_name ?? '—',
            'printed_at' => now(),

            'close_shift' => [
                'total_closed_pump_sales' => $total_sales,
                /*
                 | IS2010 #2: Total payment = Cash + Card + Credit sales.
                 |
                 | This printed $payment_total, the sum of EVERY payment type, so
                 | the printout disagreed with the required formula and - on a
                 | shift where cheque, other and shortage/excess cancelled out -
                 | read back as the total sale value.
                 |
                 | Deliberately the same three components the Close Shift screen
                 | now shows, so the print and the screen always agree.
                 */
                'total_payments' => $cash + $card + $credit,
                'balance_to_settle' => $balance_to_settle,
                /*
                 | IS2010 #2: "Current balance to Operator".
                 |
                 | This was the settlement balance repeated, so the two lines
                 | always printed the same figure. What the operator owes is the
                 | sales they are accountable for, less what they have actually
                 | handed over - which is the cash, card and credit total above.
                 */
                'current_balance_to_operator' => ($total_sales + $other_sale_total) - ($cash + $card + $credit),
            ],

            'shift_details' => [
                'shift_closed' => ((int) $shift->status === 2) ? 1 : 0,
                'closed_pumps' => $closed_pumps,
                'total_other_sales' => $other_sale_total,
                'balance_to_settle' => $balance_to_settle,
            ],

            'payment_summary' => [
                'cash' => $cash,
                'credit_sales' => $credit,
                'credit_cards' => $card,
                'cheque_sales' => $cheque,
                'total' => $cash + $credit + $card + $cheque,
            ],

            'cash_breakdown' => $cash_breakdown,
            'credit_sales_details' => $credit_sales_details,
        ];

        return view('pumperdashboard::print.close_shift_summary', compact('report'));
    }
}

<?php

namespace Modules\PumperDashboard\Http\Controllers;

use App\Business;
use Modules\PumperDashboard\Services\PumperDashboardSchema;
use Modules\PumperDashboard\Services\PumperPdfPreviewService;

;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\PumperDashboard\Entities\Pump;
use Modules\PumperDashboard\Entities\PumperDayEntry;
use Modules\PumperDashboard\Entities\PumpOperatorAssignment;
use Modules\PumperDashboard\Entities\PumpOperatorMeterSaleDetail;

class PumpOperatorActionsController extends Controller
{


    /**
     * Resolve the business currently selected in the tenant session so records
     * written by Pumper Dashboard are immediately visible in PetroPD.
     */
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

    private function startingMeterExpression(string $pumpAlias = 'pumps', string $assignmentAlias = 'pump_operator_assignments'): string
    {
        $candidates = [];

        // The active assignment is the authoritative meter chain for this
        // operator/shift. Never let a stale pump-master value override it.
        if (PumperDashboardSchema::hasColumn('pump_operator_assignments', 'starting_meter')) {
            $candidates[] = "NULLIF({$assignmentAlias}.starting_meter, 0)";
        }

        if (PumperDashboardSchema::hasTable('pumper_day_entries')
            && PumperDashboardSchema::hasColumn('pumper_day_entries', 'business_id')
            && PumperDashboardSchema::hasColumn('pumper_day_entries', 'pump_id')
            && PumperDashboardSchema::hasColumn('pumper_day_entries', 'closing_meter')) {
            $orderColumn = PumperDashboardSchema::hasColumn('pumper_day_entries', 'date') ? 'pde_last.date DESC,' : '';
            $candidates[] = "(SELECT NULLIF(pde_last.closing_meter, 0)
                FROM pumper_day_entries AS pde_last
                WHERE pde_last.business_id = {$assignmentAlias}.business_id
                    AND pde_last.pump_id = {$assignmentAlias}.pump_id
                    AND pde_last.closing_meter IS NOT NULL
                    AND pde_last.closing_meter > 0
                ORDER BY {$orderColumn} pde_last.id DESC
                LIMIT 1)";
        }

        if (PumperDashboardSchema::hasTable('pump_operator_assignments')
            && PumperDashboardSchema::hasColumn('pump_operator_assignments', 'closing_meter')) {
            $statusFilter = PumperDashboardSchema::hasColumn('pump_operator_assignments', 'status')
                ? "AND poa_last.status IN ('close', 'closed')"
                : '';
            $candidates[] = "(SELECT NULLIF(poa_last.closing_meter, 0)
                FROM pump_operator_assignments AS poa_last
                WHERE poa_last.business_id = {$assignmentAlias}.business_id
                    AND poa_last.pump_id = {$assignmentAlias}.pump_id
                    AND poa_last.id <> {$assignmentAlias}.id
                    {$statusFilter}
                    AND poa_last.closing_meter IS NOT NULL
                    AND poa_last.closing_meter > 0
                ORDER BY poa_last.id DESC
                LIMIT 1)";
        }

        foreach (['pod_last_meter', 'last_meter_reading', 'current_meter'] as $column) {
            if (PumperDashboardSchema::hasColumn('pumps', $column)) {
                $candidates[] = "NULLIF({$pumpAlias}.{$column}, 0)";
            }
        }

        $candidates[] = '0';

        return 'COALESCE(' . implode(', ', $candidates) . ')';
    }

    /**
     * Return the meter chain entered in Payment for this exact assignment.
     *
     * The shift header is part of the scope on purpose. Looking up only by
     * pump/operator can leak a meter from an older shift into Close Pump.
     */
    private function assignmentMeterChain(PumpOperatorAssignment $assignment): array
    {
        $shiftQuery = PumpOperatorMeterSaleDetail::query()
            ->join('pump_operator_meter_sales as meter_sale', 'meter_sale.id', '=', 'pump_operator_meter_sale_details.sale_id')
            ->where('pump_operator_meter_sale_details.business_id', $assignment->business_id)
            ->where('pump_operator_meter_sale_details.pump_operator_id', $assignment->pump_operator_id)
            ->where('pump_operator_meter_sale_details.pump_id', $assignment->pump_id)
            ->where('meter_sale.business_id', $assignment->business_id)
            ->where('meter_sale.pump_operator_id', $assignment->pump_operator_id)
            ->where('meter_sale.shift_id', $assignment->shift_id)
            ->select('pump_operator_meter_sale_details.*');

        $first = (clone $shiftQuery)
            ->whereNotNull('pump_operator_meter_sale_details.received_meter')
            ->where('pump_operator_meter_sale_details.received_meter', '>', 0)
            ->orderBy('pump_operator_meter_sale_details.id')
            ->first();
        $latest = (clone $shiftQuery)
            ->whereNotNull('pump_operator_meter_sale_details.new_meter')
            ->where('pump_operator_meter_sale_details.new_meter', '>', 0)
            ->orderByDesc('pump_operator_meter_sale_details.id')
            ->first();

        if ($first || $latest) {
            return [$first, $latest];
        }

        // Compatibility fallback for old data that lacks a shift_id.
        if (!empty($assignment->pump_operator_other_sale_id)) {
            $linked = PumpOperatorMeterSaleDetail::whereKey($assignment->pump_operator_other_sale_id)
                ->where('business_id', $assignment->business_id)
                ->where('pump_operator_id', $assignment->pump_operator_id)
                ->where('pump_id', $assignment->pump_id)
                ->first();

            return [$linked, $linked];
        }

        return [null, null];
    }

    private function authoritativeMeters(PumpOperatorAssignment $assignment): array
    {
        [$firstMeterSale, $latestMeterSale] = $this->assignmentMeterChain($assignment);

        // Received Meter belongs to the active assignment. Only use the first
        // shift detail as a legacy fallback when the assignment has no value.
        /*
         * MA-002 (IS-1926): A RECEIVED METER OF ZERO IS A REAL VALUE.
         *
         * This required the assignment's starting_meter to be ABOVE ZERO
         * before it would be used. A pump genuinely received at 0.00 - a new
         * pump, or the first shift on a fresh system - therefore looked like
         * "the assignment has no value", and the code fell through to the
         * legacy meter-sale figure or to 0.0.
         *
         * On this system assignment 1 was confirmed at 08:34:19 with
         * starting_meter 0.000000. That is CORRECT data: the operator received
         * the pump at zero. The screen was discarding it.
         *
         * The test is now whether the column HOLDS A NUMBER, not whether that
         * number is positive. The legacy fallback is kept for assignments where
         * the column is genuinely absent or non-numeric.
         */
        $receivedMeter = is_numeric($assignment->starting_meter)
                ? (float) $assignment->starting_meter
                : (
                    $firstMeterSale && is_numeric($firstMeterSale->received_meter)
                        ? (float) $firstMeterSale->received_meter
                        : 0.0
                );

        $latestMeter = $latestMeterSale && is_numeric($latestMeterSale->new_meter)
            ? (float) $latestMeterSale->new_meter
            : null;

        /*
         * MA-002 (IS-1926): the STARTING METER IS THE RECEIVED METER.
         *
         * The rule here was the opposite:
         *
         *     $startingMeter = $latestMeter ?? $receivedMeter;
         *
         * with the comment "Close Pump continues from the last meter entered in
         * this same shift". $latestMeter is the new_meter of the most recent
         * METER SALE - so once an operator had recorded any sale, Close Pump
         * showed that figure instead of what they received.
         *
         * That is exactly the report: "shows the last entered meter which is
         * wrong, need to show the received meter".
         *
         * The received meter now wins. $latestMeter is still returned and still
         * passed to the view as $latest_entered_meter, so anything that needs
         * the running figure keeps it - only the Starting Meter box changes.
         *
         * A received meter of 0.00 is a real value and is honoured, which is
         * why the test below is is_numeric rather than "> 0".
         */
        $startingMeter = is_numeric($receivedMeter) ? $receivedMeter : $latestMeter;

        return [$startingMeter, $latestMeter, $receivedMeter];
    }

    /**
     * Accept the system's formatted meter inputs without allowing a partial or
     * non-numeric value to pass validation.
     */
    private function parseMeterInput($value): ?float
    {
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', trim($value));
        }

        if ($value === '' || $value === null || ! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * Keep the operator-facing message readable even when an older deployed
     * language file does not yet contain the module key.
     */
    private function assignmentUnavailableMessage(): string
    {
        $key = 'pumperdashboard::lang.assignment_not_found';
        $message = __($key);

        return $message === $key
            ? 'This pump assignment is no longer available. Please return to the dashboard and open Close Pump again.'
            : $message;
    }

    /**
     * Resolve the assignment represented by the Close Pump page.
     *
     * The assignment id remains the primary key. The shift snapshot is a
     * guarded recovery path for older pages/global submit handlers that omit a
     * hidden control. It is still scoped by business, operator and pump, so an
     * assignment from another operator/shift can never be selected.
     */
    private function closePumpAssignmentQuery(
        int $businessId,
        int $pumpOperatorId,
        int $pumpId,
        int $assignmentId,
        int $shiftId,
        ?float $startingMeter
    ) {
        $base = PumpOperatorAssignment::query()
            ->where('business_id', $businessId)
            ->where('pump_operator_id', $pumpOperatorId)
            ->where('pump_id', $pumpId);

        $withoutFinalizedSettlement = static function ($query) {
            $query->where(function ($settlementQuery) {
                $settlementQuery->whereNull('closed_in_settlement')
                    ->orWhere('closed_in_settlement', 0)
                    ->orWhere('closed_in_settlement', '');
            });
        };

        /*
         * The exact assignment carried by the form is always authoritative.
         * Do not filter by status here: a duplicate browser/global-confirmation
         * submission can arrive after the first request has changed it to close.
         */
        if ($assignmentId > 0) {
            $exact = (clone $base)->whereKey($assignmentId)->lockForUpdate()->first();
            if ($exact) {
                return $exact;
            }
        }

        if ($shiftId > 0 && PumperDashboardSchema::hasColumn('pump_operator_assignments', 'shift_id')) {
            $shiftAssignment = (clone $base)
                ->where('shift_id', $shiftId)
                ->where($withoutFinalizedSettlement)
                ->orderByRaw("CASE WHEN LOWER(COALESCE(status, 'open')) IN ('open', 'pending', 'active') THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($shiftAssignment) {
                return $shiftAssignment;
            }
        }

        // Guarded recovery for legacy/global submit handlers that retain the
        // meter snapshot but lose the assignment id and route parameter.
        if ($startingMeter !== null) {
            $candidates = (clone $base)
                ->where(function ($query) {
                    $query->whereIn('status', ['open', 'pending', 'active', 'close', 'closed'])
                        ->orWhereNull('status');
                })
                ->where($withoutFinalizedSettlement)
                ->orderByRaw('CASE WHEN date_and_time IS NULL OR date_and_time <= ? THEN 0 ELSE 1 END', [now()])
                ->orderByDesc('date_and_time')
                ->orderByDesc('id')
                ->limit(10)
                ->lockForUpdate()
                ->get();

            foreach ($candidates as $candidate) {
                [$candidateStartingMeter] = $this->authoritativeMeters($candidate);
                if (abs((float) $candidateStartingMeter - $startingMeter) <= 0.0005) {
                    return $candidate;
                }
            }
        }

        /*
         * IS1885 compatibility: older published pages use
         * /get-closing-meter/{pump} and may submit no hidden assignment fields.
         * Recover only inside the operator's current open Petro shift, then fall
         * back to one current non-finalized assignment for this exact pump.
         */
        if (PumperDashboardSchema::hasColumn('pump_operator_assignments', 'shift_id')) {
            $currentShiftId = DB::table('petro_shifts')
                ->where('business_id', $businessId)
                ->where('pump_operator_id', $pumpOperatorId)
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhereNotIn('status', [2, '2', 'close', 'closed']);
                })
                ->orderByDesc('id')
                ->value('id');

            if ($currentShiftId) {
                $currentAssignment = (clone $base)
                    ->where('shift_id', (int) $currentShiftId)
                    ->where($withoutFinalizedSettlement)
                    ->orderByRaw("CASE WHEN LOWER(COALESCE(status, 'open')) IN ('open', 'pending', 'active') THEN 0 ELSE 1 END")
                    ->orderByRaw('CASE WHEN date_and_time IS NULL OR date_and_time <= ? THEN 0 ELSE 1 END', [now()])
                    ->orderByDesc('date_and_time')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                if ($currentAssignment) {
                    return $currentAssignment;
                }
            }
        }

        return (clone $base)
            ->where(function ($query) {
                $query->whereIn('status', ['open', 'pending', 'active', 'close', 'closed'])
                    ->orWhereNull('status');
            })
            ->where($withoutFinalizedSettlement)
            ->orderByRaw("CASE WHEN LOWER(COALESCE(status, 'open')) IN ('open', 'pending', 'active') THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN date_and_time IS NULL OR date_and_time <= ? THEN 0 ELSE 1 END', [now()])
            ->orderByDesc('date_and_time')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();
    }

    /**
     * Detect a completed Close Pump save across current and legacy schemas.
     * The meter fingerprint is the final fallback and makes browser retries
     * idempotent even where an old pumper_day_entries table lacks shift_id.
     */
    private function existingClosePumpEntry(
        PumpOperatorAssignment $assignment,
        ?float $startingMeter,
        ?float $closingMeter
    ): ?PumperDayEntry {
        $base = PumperDayEntry::query()
            ->where('business_id', $assignment->business_id)
            ->where('pump_operator_id', $assignment->pump_operator_id)
            ->where('pump_id', $assignment->pump_id);

        if (PumperDashboardSchema::hasColumn('pumper_day_entries', 'pumper_assignment_id')) {
            $entry = (clone $base)
                ->where('pumper_assignment_id', $assignment->id)
                ->lockForUpdate()
                ->first();
            if ($entry) {
                return $entry;
            }
        }

        if (
            ! empty($assignment->shift_id)
            && PumperDashboardSchema::hasColumn('pumper_day_entries', 'shift_id')
        ) {
            $shiftEntry = (clone $base)->where('shift_id', $assignment->shift_id);

            // With a modern schema, a row linked to another assignment is not
            // the idempotent result for this assignment. Only accept an
            // unlinked legacy row through this compatibility branch.
            if (PumperDashboardSchema::hasColumn('pumper_day_entries', 'pumper_assignment_id')) {
                $shiftEntry->whereNull('pumper_assignment_id');
            }

            $entry = $shiftEntry->orderByDesc('id')->lockForUpdate()->first();
            if ($entry) {
                return $entry;
            }
        }

        if ($startingMeter !== null && $closingMeter !== null) {
            return (clone $base)
                ->whereBetween('starting_meter', [$startingMeter - 0.0005, $startingMeter + 0.0005])
                ->whereBetween('closing_meter', [$closingMeter - 0.0005, $closingMeter + 0.0005])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();
        }

        return null;
    }

    private function authorizePumperDashboardPermission(string $permission): void
    {
        $user = Auth::user();

        if (
            ! empty($user) &&
            (
                $user->can('pump_operator.dashboard') ||
                $user->can($permission) ||
                (! empty($user->is_pump_operator) && ! empty($user->pump_operator_id))
            )
        ) {
            return;
        }

        if (empty($user)) {
            abort(403, 'Unauthorized Access');
        }

        abort(403, 'Unauthorized Access');
    }

    /**
     * Pump Receive
     * @return Renderable
     */
    public function getReceivePump()
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.receive_pump');

        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id = $this->resolveBusinessId();

      



            
            // dump($pumps);exit;
            

        $layout = 'pumper';
        
        $user = Auth::user();
       
        
        // dd($user);
        $pump_operator_id = $user->pump_operator_id;
        // $shift_number = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)->max('shift_number');
// $currentShift = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
//     ->where('status', 'open')
//     ->orderBy('shift_number', 'asc')   // earliest open shift first
//     ->first();

// if ($currentShift) {
//     $shift_number = $currentShift->shift_number;  // should now be 11
// }

        $now = now();

// 1. Try to get the current open shift
$currentShift = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
    ->where('status', 'open')
    ->where(function ($q) use ($now) {
        $q->where('date_and_time', '<=', $now)   // current shift
          ->orWhere('date_and_time', '>', $now); // future shift
    })
    ->orderBy('date_and_time', 'asc')           // earliest valid shift
    ->first();

if (!$currentShift) {
    // 2. If no current open shift, get the first future shift
    $currentShift = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
        ->where('date_and_time', '>', $now) // future only
        ->orderBy('date_and_time', 'asc')   // next upcoming
        ->first();
}

if ($currentShift) {
    $shift_number = $currentShift->shift_number;
} else {
    $shift_number = null; // fallback if nothing found
}

// Get all open assignments for this pump operator.
// Do not depend on petro_shifts.status here because standalone access should
// follow the live assignment records even if the related shift row is out of sync.
    $pumps = PumpOperatorAssignment::join('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
        ->leftJoin('pump_operators', 'pump_operator_assignments.pump_operator_id', 'pump_operators.id')
        ->leftJoin('petro_shifts', 'petro_shifts.id', 'pump_operator_assignments.shift_id')
        ->where('pump_operator_assignments.business_id', $business_id)
        ->where('pumps.business_id', $business_id)
        ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
        ->where('pump_operator_assignments.status', 'open')
        ->where(function ($query) {
            $query->where('pump_operator_assignments.closed_in_settlement', 0)
                ->orWhereNull('pump_operator_assignments.closed_in_settlement');
        })
        ->select(
            'pumps.*',
            'pump_operator_assignments.pump_operator_id',
            'pump_operators.name as pumper_name',
            'pump_operator_assignments.status',
            'pump_operator_assignments.is_confirmed',
            'pump_operator_assignments.id as assignment_id',
            'pump_operator_assignments.shift_number',
            DB::raw($this->startingMeterExpression('pumps', 'pump_operator_assignments') . ' as starting_meter')
        )
        ->orderBy('pump_operator_assignments.date_and_time', 'desc')
        ->get();


// Get the shift number from the first assignment (for display)
$shift_number = $pumps->isNotEmpty() ? $pumps->first()->shift_number : null;
// if($currentShift) {
//     $shift_number = $currentShift->shift_number;
//     $assigned_pumps = PumpOperatorAssignment::where('pump_operator_id', $pump_operator_id)
//         ->where('shift_number', $shift_number)
//         ->get();
// }

      

        return view('pumperdashboard::actions.pump_receive')->with(compact(
            'pumps',
            'layout',
            'shift_number'
        ));
    }

    public function getClosingMeterModal()
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.close_pump');

        $pump_operator_id = (int) Auth::user()->pump_operator_id;
        $business_id = (int) $this->resolveBusinessId();

        $basePumpQuery = function () use ($pump_operator_id, $business_id) {
            return PumpOperatorAssignment::join('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
                ->leftJoin('petro_shifts', 'petro_shifts.id', 'pump_operator_assignments.shift_id')
                ->join('pump_operators', 'pump_operator_assignments.pump_operator_id', 'pump_operators.id')
                ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
                ->where('pump_operator_assignments.business_id', $business_id)
                ->where('pumps.business_id', $business_id)
                ->whereIn('pump_operator_assignments.status', ['open', 'close', 'closed'])
                ->where(function ($query) {
                    $query->where('pump_operator_assignments.closed_in_settlement', 0)
                        ->orWhereNull('pump_operator_assignments.closed_in_settlement');
                });
        };

        /*
         * Keep the Close Pump modal locked to one exact shift. When pumps are
         * still open, use the latest open shift. After the final pump is closed,
         * use the latest pending closed shift so the print button becomes
         * available for that same shift.
         */
        $selectedAssignment = $basePumpQuery()
            ->select(
                'pump_operator_assignments.shift_id',
                'pump_operator_assignments.shift_number',
                'pump_operator_assignments.status',
                'pump_operator_assignments.date_and_time',
                'pump_operator_assignments.id'
            )
            ->orderByRaw("CASE WHEN pump_operator_assignments.status = 'open' THEN 0 ELSE 1 END")
            ->orderBy('pump_operator_assignments.date_and_time', 'desc')
            ->orderBy('pump_operator_assignments.id', 'desc')
            ->first();

        $shift_id = ! empty($selectedAssignment) ? (int) $selectedAssignment->shift_id : null;
        $shift_number = ! empty($selectedAssignment) ? $selectedAssignment->shift_number : null;
        $pumps = collect();

        if (! empty($selectedAssignment)) {
            $pumpsQuery = $basePumpQuery();

            if (! empty($shift_id)) {
                $pumpsQuery->where('pump_operator_assignments.shift_id', $shift_id);
            } else {
                $pumpsQuery->where('pump_operator_assignments.shift_number', $shift_number);
            }

            $pumps = $pumpsQuery
                ->select(
                    'pumps.*',
                    'pump_operator_assignments.pump_operator_id',
                    'pump_operator_assignments.pump_id',
                    'pump_operator_assignments.shift_id',
                    'pump_operators.name AS pumper_name',
                    'pump_operator_assignments.status',
                    'pump_operator_assignments.id as assignment_id',
                    'pump_operator_assignments.is_confirmed',
                    'pump_operator_assignments.shift_number'
                )
                ->orderBy('pumps.pump_no')
                ->orderBy('pump_operator_assignments.id')
                ->get();
        }

        $all_pumps_closed = $pumps->isNotEmpty() && $pumps->every(function ($pump) {
            return in_array(strtolower((string) $pump->status), ['close', 'closed'], true);
        });

        return view('pumperdashboard::actions.closing_meter_modal')->with(compact(
            'pumps',
            'shift_id',
            'shift_number',
            'all_pumps_closed'
        ));
    }

    /**
     * Print the closed-pump and other-sales statement for one exact shift.
     *
     * The URL remains protected even though the modal disables the print button
     * until every assigned pump is closed.
     */
    public function printClosedPumpsStatement($shift_id)
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.close_pump');

        $business_id = (int) $this->resolveBusinessId();
        $pump_operator_id = (int) Auth::user()->pump_operator_id;
        $shift_id = (int) $shift_id;

        $assignments = PumpOperatorAssignment::join('pumps', 'pumps.id', 'pump_operator_assignments.pump_id')
            ->join('pump_operators', 'pump_operators.id', 'pump_operator_assignments.pump_operator_id')
            ->where('pump_operator_assignments.business_id', $business_id)
            ->where('pump_operator_assignments.pump_operator_id', $pump_operator_id)
            ->where('pump_operator_assignments.shift_id', $shift_id)
            ->select(
                'pump_operator_assignments.id',
                'pump_operator_assignments.shift_id',
                'pump_operator_assignments.shift_number',
                'pump_operator_assignments.status',
                'pump_operator_assignments.date_and_time',
                'pumps.pump_no',
                'pump_operators.name as operator_name'
            )
            ->orderBy('pumps.pump_no')
            ->get();

        abort_if($assignments->isEmpty(), 404, 'Shift pump assignments were not found.');

        $allPumpsClosed = $assignments->every(function ($assignment) {
            return in_array(strtolower((string) $assignment->status), ['close', 'closed'], true);
        });

        abort_unless($allPumpsClosed, 409, 'Please close all pumps before printing this statement.');

        $assignmentIds = $assignments->pluck('id')->map(function ($id) {
            return (int) $id;
        })->all();

        $meterSalesQuery = DB::table('pumper_day_entries as pde')
            ->leftJoin('pumps', 'pumps.id', '=', 'pde.pump_id')
            ->where('pde.business_id', $business_id)
            ->where('pde.pump_operator_id', $pump_operator_id);

        /*
         * IS1752-1: tenant schemas are not identical.  Several older tenant
         * databases do not have pumper_day_entries.shift_id, although they do
         * have the exact pumper_assignment_id link.  Referencing the missing
         * shift_id inside an OR clause caused the print page to fail with 1054.
         * Use only columns that actually exist and keep assignment ids as the
         * first, exact and safest ownership key.
         */
        $hasAssignmentColumn = PumperDashboardSchema::hasColumn('pumper_day_entries', 'pumper_assignment_id');
        $hasShiftColumn = PumperDashboardSchema::hasColumn('pumper_day_entries', 'shift_id');

        if ($hasAssignmentColumn && ! empty($assignmentIds)) {
            $meterSalesQuery->where(function ($query) use ($assignmentIds, $shift_id, $hasShiftColumn) {
                $query->whereIn('pde.pumper_assignment_id', $assignmentIds);

                if ($hasShiftColumn) {
                    $query->orWhere('pde.shift_id', $shift_id);
                }
            });
        } elseif ($hasShiftColumn) {
            $meterSalesQuery->where('pde.shift_id', $shift_id);
        } else {
            abort(422, 'This tenant database has no exact shift link for pumper day entries. Please run the current Pumper Dashboard tenant migration before printing.');
        }

        $meterSales = $meterSalesQuery
            ->select(
                'pde.id',
                'pde.date',
                'pde.time',
                'pde.created_at',
                DB::raw('COALESCE(pde.pump_no, pumps.pump_no, "-") as pump_no'),
                'pde.starting_meter',
                'pde.closing_meter',
                'pde.testing_ltr',
                'pde.sold_ltr',
                'pde.amount'
            )
            ->orderBy('pde.date')
            ->orderBy('pde.time')
            ->orderBy('pde.id')
            ->get()
            ->unique('id')
            ->values();

        $otherSalesQuery = DB::table('pump_operator_other_sales as pos')
            ->leftJoin('products', 'products.id', '=', 'pos.product_id')
            ->where('pos.business_id', $business_id)
            ->where('pos.shift_id', $shift_id);

        if (PumperDashboardSchema::hasColumn('pump_operator_other_sales', 'pump_operator_id')) {
            $otherSalesQuery->where(function ($query) use ($pump_operator_id) {
                $query->where('pos.pump_operator_id', $pump_operator_id)
                    ->orWhereNull('pos.pump_operator_id');
            });
        }

        $categoryExpression = "COALESCE(products.name, '-')";

        if (
            PumperDashboardSchema::hasTable('categories')
            && PumperDashboardSchema::hasColumn('products', 'sub_category_id')
        ) {
            $otherSalesQuery->leftJoin('categories as sub_categories', 'sub_categories.id', '=', 'products.sub_category_id');
            $categoryExpression = "COALESCE(sub_categories.name, products.name, '-')";
        } elseif (
            PumperDashboardSchema::hasTable('categories')
            && PumperDashboardSchema::hasColumn('products', 'category_id')
        ) {
            $otherSalesQuery->leftJoin('categories as product_categories', 'product_categories.id', '=', 'products.category_id');
            $categoryExpression = "COALESCE(product_categories.name, products.name, '-')";
        }

        // `discount` may be a percentage/rate in legacy tenant schemas. Only
        // subtract the explicit monetary discount_amount column when available.
        $discountExpression = '0';
        if (PumperDashboardSchema::hasColumn('pump_operator_other_sales', 'discount_amount')) {
            $discountExpression = 'COALESCE(pos.discount_amount, 0)';
        }

        $otherSales = $otherSalesQuery
            ->select(
                'pos.id',
                'pos.created_at',
                DB::raw($categoryExpression . ' as product_sub_category'),
                DB::raw('GREATEST(COALESCE(pos.sub_total, 0) - ' . $discountExpression . ', 0) as total_amount')
            )
            ->orderBy('pos.created_at')
            ->orderBy('pos.id')
            ->get();

        $business = Business::findOrFail($business_id);
        $operator_name = (string) ($assignments->first()->operator_name ?? '');
        $shift_number = $assignments->first()->shift_number ?? '-';

        $statement_date = optional($meterSales->first())->date;
        if (empty($statement_date) && ! empty($assignments->first()->date_and_time)) {
            $statement_date = \Carbon\Carbon::parse($assignments->first()->date_and_time)->toDateString();
        }
        if (empty($statement_date)) {
            $statement_date = now()->toDateString();
        }

        $currency_symbol = 'Rs.';
        if (
            PumperDashboardSchema::hasTable('currencies')
            && PumperDashboardSchema::hasColumn('business', 'currency_id')
        ) {
            $currency_symbol = (string) (
                DB::table('business as b')
                    ->leftJoin('currencies as c', 'c.id', '=', 'b.currency_id')
                    ->where('b.id', $business_id)
                    ->value('c.symbol') ?: 'Rs.'
            );
        }

        $meter_totals = (object) [
            'testing_ltr' => (float) $meterSales->sum('testing_ltr'),
            'sold_ltr' => (float) $meterSales->sum('sold_ltr'),
            'amount' => (float) $meterSales->sum('amount'),
        ];
        $total_other_sales = (float) $otherSales->sum('total_amount');
        $grand_total = $meter_totals->amount + $total_other_sales;
        $printed_at = now();

        $view_data = compact(
            'business',
            'currency_symbol',
            'statement_date',
            'shift_number',
            'operator_name',
            'meterSales',
            'meter_totals',
            'otherSales',
            'total_other_sales',
            'grand_total',
            'printed_at'
        );

        $file_name = 'closed-pumps-other-sales-shift-'
            . preg_replace('/[^0-9A-Za-z_-]+/', '-', (string) $shift_number)
            . '.pdf';

        return app(PumperPdfPreviewService::class)->stream(
            'pumperdashboard::actions.closed_pumps_statement_pdf',
            $view_data,
            $file_name,
            'a4',
            'landscape',
            [
                'business_id' => $business_id,
                'shift_id' => $shift_id,
                'print_type' => 'closed_pumps_statement',
            ]
        );
    }

    public function getClosingMeter(Request $request, $pump_id, $route_assignment_id = null)
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.close_pump');

        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id = $this->resolveBusinessId();

        $pump = Pump::leftjoin('products', 'pumps.product_id', 'products.id')
            ->leftjoin('variations', 'products.id', 'variations.product_id')
            ->leftjoin('variation_location_details', 'variations.id', 'variation_location_details.variation_id')
            ->where('pumps.id', $pump_id)
            ->where('pumps.business_id', $business_id)
            ->select('sell_price_inc_tax', 'pumps.*', 'variation_location_details.qty_available')->firstOrFail();

        // The Close Pump card carries the exact assignment id. This prevents a
        // second/future assignment for the same pump from replacing the row
        // selected by the operator before the form is saved.
        $requested_assignment_id = (int) ($route_assignment_id
            ?: $request->query('assignment_id', 0));
        $assignmentQuery = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('pump_id', $pump_id)
            ->where(function ($query) {
                $query->where('closed_in_settlement', 0)
                    ->orWhereNull('closed_in_settlement');
            });

        if ($requested_assignment_id > 0) {
            // Exact route assignment remains readable for a safe browser retry,
            // even if a first request has already changed status to close.
            $assignmentQuery->whereKey($requested_assignment_id);
        } else {
            // Old URLs have no assignment id. Prefer the operator's active
            // assignment and never select a future pump assignment first.
            $assignmentQuery
                ->where(function ($query) {
                    $query->whereIn('status', ['open', 'pending', 'active'])
                        ->orWhereNull('status');
                })
                ->orderByRaw('CASE WHEN date_and_time IS NULL OR date_and_time <= ? THEN 0 ELSE 1 END', [now()])
                ->orderByDesc('date_and_time')
                ->orderByDesc('id');
        }

        $assignment = $assignmentQuery->first();
        if (! $assignment) {
            abort(404, 'The active pump assignment was not found. Please return to the dashboard and try again.');
        }

        $pumper_name = $assignment->pumpOperator->name ?? '';
        $shift_number = $assignment ? $assignment->shift_number : null;
        [$starting_meter, $latest_entered_meter] = $assignment
            ? $this->authoritativeMeters($assignment)
            : [0.0, null];
        $currency_precision = (int) (Business::whereKey($business_id)->value('currency_precision') ?? 2);

        /*
         * MA-002 (Issue 11) - log the divergence in ONE line.
         *
         * When the save guard redirects here it leaves behind what IT computed.
         * This compares that against what THIS page computes for the same
         * assignment, so a single occurrence shows exactly where the two sides
         * part company:
         *
         *   same assignment_id, different meter  -> the two paths compute the
         *                                           baseline differently
         *   different assignment_id              -> POST resolved a different
         *                                           assignment than the form
         *                                           carried, which points at
         *                                           closePumpAssignmentQuery's
         *                                           fallback paths
         *
         * IS1899 showed the guard quoting 150.000 over a page displaying
         * 100.000, so one of those two is happening. Read-only: it only logs.
         */
        $ma002Expected = session()->pull('ma002_close_pump_expected');
        if (is_array($ma002Expected)) {
            Log::warning('MA-002 Issue 11: close-pump baseline divergence', [
                'post_assignment_id'      => $ma002Expected['assignment_id'] ?? null,
                'post_authoritative'      => $ma002Expected['authoritative'] ?? null,
                'post_displayed_snapshot' => $ma002Expected['displayed'] ?? null,
                'get_assignment_id'       => $assignment->id ?? null,
                'get_starting_meter'      => $starting_meter,
                'get_latest_entered'      => $latest_entered_meter,
                'get_assignment_start'    => $assignment->starting_meter ?? null,
                'get_shift_id'            => $assignment->shift_id ?? null,
                'get_status'              => $assignment->status ?? null,
                'same_assignment'         => (($ma002Expected['assignment_id'] ?? null) == ($assignment->id ?? null)) ? 'yes' : 'NO',
                'pump_id'                 => (int) $pump_id,
                'guard_fire_count'        => $ma002Expected['count'] ?? null,
            ]);
        }

        if (empty(session()->get('pump_operator_main_system'))) {
            $layout = 'pumper';
        } else {
            $layout = 'app';
        }
        // dd($pump->toArray());
        return view('pumperdashboard::actions.closing_meter')->with(compact(
            'pump',
            'layout',
            'business_id',
            'pumper_name',
            'shift_number',
            'starting_meter',
            'latest_entered_meter',
            'assignment',
            'currency_precision'
        ));
    }
    public function postClosingMeter(Request $request, $pump_id, $route_assignment_id = null)
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.close_pump');

        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id = $this->resolveBusinessId();

        try {
            DB::beginTransaction();

            // Lock the exact assignment before checking/inserting. Do not filter it
            // out merely because a first request already changed status to `close`:
            // a browser retry must resolve to the existing entry, not a false
            // assignment_not_found error.
            $assignment_id = (int) ($route_assignment_id
                ?: $request->input('pumper_assignment_id')
                ?: $request->input('assignment_id')
                ?: $request->query('assignment_id'));
            $shift_id_snapshot = (int) $request->input('shift_id_snapshot');
            $displayed_starting_meter = $this->parseMeterInput($request->input('starting_meter_snapshot'));
            $closing_meter = $this->parseMeterInput($request->input('closing_meter'));

            $pumper_assignment_obj = $this->closePumpAssignmentQuery(
                $business_id,
                (int) $pump_operator_id,
                (int) $pump_id,
                $assignment_id,
                $shift_id_snapshot,
                $displayed_starting_meter
            );

            if (!$pumper_assignment_obj) {
                DB::rollBack();

                /*
                 * MA-002 (Issue 11) DIAGNOSTIC.
                 *
                 * "This pump assignment is no longer available" is shown when
                 * closePumpAssignmentQuery() finds nothing. The lookup is
                 * scoped by business_id + pump_operator_id + pump_id, then by
                 * assignment id, shift id and starting meter. Any one of those
                 * not matching produces this message, and until now the log
                 * said nothing about WHICH.
                 *
                 * This records the exact values used, and what the table
                 * actually holds for this operator, so the mismatching field
                 * is visible from one occurrence.
                 */
                $ownAssignments = PumpOperatorAssignment::query()
                    ->where('business_id', $business_id)
                    ->where('pump_operator_id', (int) $pump_operator_id)
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get(['id', 'business_id', 'pump_operator_id', 'pump_id', 'shift_id', 'status', 'closed_in_settlement'])
                    ->map(static function ($row) {
                        return 'id=' . $row->id
                            . ' pump=' . $row->pump_id
                            . ' shift=' . $row->shift_id
                            . ' status=' . $row->status
                            . ' closed=' . $row->closed_in_settlement;
                    })->implode(' | ');

                $byIdAnyScope = $assignment_id > 0
                    ? PumpOperatorAssignment::query()->whereKey($assignment_id)
                        ->first(['id', 'business_id', 'pump_operator_id', 'pump_id', 'shift_id', 'status', 'closed_in_settlement'])
                    : null;

                Log::warning('MA-002 Issue 11: Close Pump assignment lookup found nothing', [
                    'looked_for_business_id'      => $business_id,
                    'looked_for_pump_operator_id' => (int) $pump_operator_id,
                    'looked_for_pump_id'          => (int) $pump_id,
                    'looked_for_assignment_id'    => $assignment_id,
                    'looked_for_shift_id'         => $shift_id_snapshot,
                    'looked_for_starting_meter'   => $displayed_starting_meter,
                    'auth_user_id'                => optional(Auth::user())->id,
                    'auth_pump_operator_id'       => optional(Auth::user())->pump_operator_id,
                    'row_by_id_ignoring_scope'    => $byIdAnyScope
                        ? ('business_id=' . $byIdAnyScope->business_id
                            . ' pump_operator_id=' . $byIdAnyScope->pump_operator_id
                            . ' pump_id=' . $byIdAnyScope->pump_id
                            . ' shift_id=' . $byIdAnyScope->shift_id
                            . ' status=' . $byIdAnyScope->status
                            . ' closed_in_settlement=' . $byIdAnyScope->closed_in_settlement)
                        : 'no row with that id at all',
                    'operator_recent_assignments' => $ownAssignments !== '' ? $ownAssignments : 'none',
                ]);

                $output = [
                    'success' => 0,
                    'msg' => $this->assignmentUnavailableMessage()
                ];

                return redirect()->back()->with('status', $output);
            }

            $pumper_assignment = $pumper_assignment_obj->id;
            $shift_id = $pumper_assignment_obj->shift_id;

            // Idempotent success path must run before validating the meter snapshot.
            // The original save may already have completed even if its response was
            // interrupted, so a repeat click must not create a duplicate or fail.
            $existing_entry = $this->existingClosePumpEntry(
                $pumper_assignment_obj,
                $displayed_starting_meter,
                $closing_meter
            );

            if ($existing_entry) {
                PumpOperatorAssignment::where('business_id', $business_id)
                    ->where('id', $pumper_assignment)
                    ->update([
                        'status' => 'close',
                        'close_date_and_time' => $pumper_assignment_obj->close_date_and_time ?: \Carbon::now(),
                        'closing_meter' => $existing_entry->closing_meter,
                    ]);

                DB::commit();

                return redirect()->to('/pumper-dashboard/pump-operators/dashboard?tab=closing_meter')
                    ->with('status', [
                        'success' => 1,
                        'msg' => __('pumperdashboard::lang.success'),
                    ]);
            }

            if (
                (bool) $pumper_assignment_obj->closed_in_settlement
                || ! in_array(trim(strtolower((string) $pumper_assignment_obj->status)), ['', 'open', 'pending', 'active', 'close', 'closed'], true)
            ) {
                DB::rollBack();

                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => $this->assignmentUnavailableMessage(),
                ]);
            }

            [$starting_meter] = $this->authoritativeMeters($pumper_assignment_obj);

            // The POST must belong to the exact assignment and meter snapshot
            // displayed on the Close Pump page. If another meter was entered in
            // the meantime, do not save against a different hidden baseline.
            if (
                $displayed_starting_meter === null
                || abs($displayed_starting_meter - $starting_meter) > 0.0005
            ) {
                DB::rollBack();

                /*
                 * MA-002 (Issue 11) DIAGNOSTIC.
                 *
                 * The Close Pump page and this save both compute the baseline
                 * with the SAME authoritativeMeters() call, so they can only
                 * disagree if the assignment resolved here is not the one the
                 * page displayed, or a meter reading was recorded in between,
                 * or the hidden snapshot field never arrived (null).
                 *
                 * Those three need different fixes, so the two numbers and the
                 * resolved assignment are recorded here. One occurrence
                 * identifies which.
                 */
                // Uses the SAME entity the real baseline query uses
                // (PumpOperatorMeterSaleDetail), already imported above.
                $latestSale = PumpOperatorMeterSaleDetail::where('business_id', $business_id)
                    ->where('pump_id', (int) $pump_id)
                    ->orderByDesc('id')
                    ->first(['id', 'pump_id', 'received_meter', 'new_meter', 'created_at']);

                Log::warning('MA-002 Issue 11: Close Pump starting-meter snapshot mismatch', [
                    'displayed_starting_meter' => $displayed_starting_meter,
                    'authoritative_starting_meter' => $starting_meter,
                    'difference' => $displayed_starting_meter === null
                        ? 'snapshot field was NULL - it never reached the server'
                        : ($displayed_starting_meter - $starting_meter),
                    'resolved_assignment_id' => $pumper_assignment_obj->id ?? null,
                    'posted_assignment_id' => $assignment_id,
                    'route_assignment_id' => $route_assignment_id,
                    'assignment_starting_meter' => $pumper_assignment_obj->starting_meter ?? null,
                    'assignment_status' => $pumper_assignment_obj->status ?? null,
                    'assignment_shift_id' => $pumper_assignment_obj->shift_id ?? null,
                    'posted_shift_snapshot' => $shift_id_snapshot,
                    'closing_meter_posted' => $closing_meter,
                    'latest_meter_sale' => $latestSale
                        ? ('id=' . $latestSale->id
                            . ' received=' . $latestSale->received_meter
                            . ' new=' . $latestSale->new_meter
                            . ' at=' . $latestSale->created_at)
                        : 'none for this pump',
                    'pump_id' => (int) $pump_id,
                    'business_id' => $business_id,
                    'auth_user_id' => optional(Auth::user())->id,
                ]);

                /*
                 * MA-002 (Issue 11) - send the operator to a FRESH page
                 * instead of back to the stale one.
                 *
                 * Your tenant data shows exactly what happens:
                 *
                 *   pump_operator_assignments  id 1, pump 6, operator 35,
                 *                              shift 1, starting_meter 150
                 *   pump_operator_meter_sale_details
                 *                              received_meter 150,
                 *                              new_meter 200,
                 *                              created 2026-08-04 10:57:51
                 *
                 * So a close was ALREADY SAVED SUCCESSFULLY at 10:57 - the
                 * meter moved 150 -> 200. The authoritative baseline is now
                 * 200, while the page still in the browser was rendered when
                 * it was 150. The screenshot confirms it: Starting Meter
                 * 150.000 with a closing of 250.
                 *
                 * The guard is therefore CORRECT - it is refusing to save a
                 * second reading against a baseline that no longer exists,
                 * which would double-count the sale. It must stay.
                 *
                 * What was wrong is the dead end. redirect()->back() returned
                 * the operator to the same stale form, still showing 150, so
                 * pressing Save again produced the same error forever. That
                 * is what makes it look broken.
                 *
                 * Redirecting to the Close Pump page re-renders it with the
                 * current baseline (200), so the operator can simply enter the
                 * closing meter and continue. Nothing is bypassed.
                 */
                $freshUrl = null;
                if (! empty($pumper_assignment_obj->id) && \Illuminate\Support\Facades\Route::has('pumperdashboard.close-pump.show')) {
                    $freshUrl = route('pumperdashboard.close-pump.show', [
                        'pump_id' => (int) $pump_id,
                        'route_assignment_id' => (int) $pumper_assignment_obj->id,
                    ]);
                }

                /*
                 * MA-002 (Issue 11) - LOOP DETECTION AND DIVERGENCE CAPTURE.
                 *
                 * IS1899 showed this message quoting 150.000 while the
                 * refreshed page underneath displayed a Starting Meter of
                 * 100.000. The guard and the page it redirects to disagreed,
                 * so re-entering the closing meter failed again - my earlier
                 * fix turned a dead end into a loop. That is worse, and it is
                 * my mistake.
                 *
                 * Two things happen here now.
                 *
                 * First, what POST computed is stored in the session so the
                 * GET that follows can log BOTH numbers on one line. That
                 * shows in a single step whether the two sides resolved
                 * different assignments or computed different baselines for
                 * the same one.
                 *
                 * Second, a repeat counter. If the guard fires twice for the
                 * same assignment, the operator is sent back to the dashboard
                 * with a clear message instead of being bounced round the same
                 * page indefinitely. The guard itself is NOT weakened - a
                 * genuine stale submission is still refused - it just stops
                 * being an inescapable loop.
                 */
                $loopKey = 'ma002_close_pump_guard_' . (int) $pump_id . '_' . (int) ($pumper_assignment_obj->id ?? 0);
                $loopCount = (int) session()->get($loopKey, 0) + 1;
                session()->put($loopKey, $loopCount);
                session()->put('ma002_close_pump_expected', [
                    'assignment_id' => $pumper_assignment_obj->id ?? null,
                    'authoritative' => $starting_meter,
                    'displayed'     => $displayed_starting_meter,
                    'pump_id'       => (int) $pump_id,
                    'count'         => $loopCount,
                ]);

                if ($loopCount >= 2) {
                    session()->forget($loopKey);

                    Log::warning('MA-002 Issue 11: guard fired twice for the same assignment - breaking the loop', [
                        'pump_id' => (int) $pump_id,
                        'assignment_id' => $pumper_assignment_obj->id ?? null,
                        'authoritative_starting_meter' => $starting_meter,
                        'displayed_starting_meter' => $displayed_starting_meter,
                    ]);

                    /*
                     * Same destination the page's own Dashboard button uses -
                     * closing_meter.blade.php line 196. There is no named
                     * 'pumperdashboard.dashboard' route; I checked before
                     * writing this, having first assumed there was.
                     */
                    return redirect()->action(
                        '\\Modules\\PumperDashboard\\Http\\Controllers\\PumpOperatorController@dashboard'
                    )->with('status', [
                        'success' => 0,
                        'msg' => 'This pump could not be closed because its starting meter keeps changing '
                            . 'between screens. Please reopen Close Pump from the dashboard. If it happens '
                            . 'again, report it - the details have been recorded in the system log.',
                    ]);
                }

                $statusPayload = [
                    'success' => 0,
                    'msg' => 'This pump already has a later meter reading ('
                        . number_format($starting_meter, 3, '.', '')
                        . '). The page has been refreshed with the current starting meter - '
                        . 'please enter the closing meter again.',
                ];

                return $freshUrl
                    ? redirect()->to($freshUrl)->with('status', $statusPayload)
                    : redirect()->back()->with('status', $statusPayload);
            }

            $testing_ltr = $this->parseMeterInput($request->input('testing_ltr'));
            $testing_ltr = $testing_ltr === null ? 0.0 : max(0.0, $testing_ltr);

            if ($closing_meter === null || $closing_meter < $starting_meter) {
                DB::rollBack();

                return redirect()->back()->withInput()->with('status', [
                    'success' => 0,
                    'msg' => __('pumperdashboard::lang.closing_meter_cannot_be_smaller'),
                ]);
            }

            $sold_ltr = $closing_meter - $starting_meter - $testing_ltr;
            if ($sold_ltr < 0) {
                DB::rollBack();

                return redirect()->back()->withInput()->with('status', [
                    'success' => 0,
                    'msg' => 'Closing meter minus testing litres cannot be below the Starting Meter.',
                ]);
            }

            $sale_price = (float) Pump::leftJoin('products', 'pumps.product_id', '=', 'products.id')
                ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
                ->where('pumps.business_id', $business_id)
                ->where('pumps.id', $pump_id)
                ->value('variations.sell_price_inc_tax');
            $amount = $sold_ltr * $sale_price;

            $data = [
                'business_id' => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'date' => date('Y-m-d'),
                'pump_id' => $pump_id,
                'pump_no' => $request->pump_no,
                'starting_meter' => $starting_meter,
                'closing_meter' => $closing_meter,
                'testing_ltr' => $testing_ltr,
                'sold_ltr' => $sold_ltr,
                'amount' => $amount,
            ];

            if (PumperDashboardSchema::hasColumn('pumper_day_entries', 'pumper_assignment_id')) {
                $data['pumper_assignment_id'] = $pumper_assignment;
            }

            if (PumperDashboardSchema::hasColumn('pumper_day_entries', 'shift_id')) {
                $data['shift_id'] = $shift_id;
            }

            PumperDayEntry::create($data);

            Pump::where('business_id', $business_id)
                ->where('id', $pump_id)
                ->update([
                'pod_starting_meter' => $starting_meter,
                'pod_last_meter' => $closing_meter,
            ]);

            PumpOperatorAssignment::where('business_id', $business_id)
                ->where('id', $pumper_assignment)
                ->update([
                'status' => 'close',
                'close_date_and_time' => \Carbon::now(),
                'closing_meter' => $closing_meter,
            ]);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('pumperdashboard::lang.success')
            ];

            return redirect()->to('/pumper-dashboard/pump-operators/dashboard?tab=closing_meter')
                ->with('status', $output);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ];

            return redirect()->back()->with('status', $output);
        }
    }

    public function store(Request $request)
    {
        try {
            $user_id = Auth::user()->id;
            $business_id = $this->resolveBusinessId();
            $pump_operator_id = Auth::user()->pump_operator_id;


            $payment = new PumpPayment();

            $payment->business_id  = $business_id;
            $payment->pump_operators_id = $pump_operator_id;
            $payment->payment_type = $request->payment_type;
            $payment->payment_amount = $request->display;
            $payment->created_by = $user_id;

            $payment->save();

            $output = [
                'success' => true,
                'msg' => __('pumperdashboard::lang.payment_added_successfully')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }


        return redirect()->route('pump_operator_payment.index')->with('status', $output);
    }
}

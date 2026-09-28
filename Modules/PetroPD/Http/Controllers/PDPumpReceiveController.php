<?php

namespace Modules\PetroPD\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Entities\PetroShift;
use Modules\PetroPD\Entities\PumpOperatorAssignment;

class PDPumpReceiveController extends Controller
{
    private function resolveBusinessId(): int
    {
        return (int) (session('business.id') ?: session('user.business_id') ?: (Auth::user()->business_id ?? 0));
    }

    private function resolvePumpOperatorId(): int
    {
        return (int) (session('pump_operator_id') ?: session('pumper_operator_id') ?: (Auth::user()->pump_operator_id ?? 0));
    }

    private function pumpCurrentMeterExpression(string $pumpAlias = 'p', string $assignmentAlias = 'poa'): string
    {
        // S362: Receive Pump opening / starting meter must be the PREVIOUS TIME CLOSING METER.
        // The Receive Pump popup was previously falling back to the pump master/current meter and
        // could show 0.000. The correct business rule is:
        //     next receive starting meter = last saved closing meter for the same pump.
        // This is the same practical value the Close Pump screen uses after a pump is closed.
        $candidates = [];

        if (Schema::hasTable('pumper_day_entries')
            && Schema::hasColumn('pumper_day_entries', 'business_id')
            && Schema::hasColumn('pumper_day_entries', 'pump_id')
            && Schema::hasColumn('pumper_day_entries', 'closing_meter')) {
            $dateOrder = Schema::hasColumn('pumper_day_entries', 'date') ? 'pde_last.date DESC,' : '';
            $candidates[] = "(SELECT NULLIF(pde_last.closing_meter, 0)
                FROM pumper_day_entries AS pde_last
                WHERE pde_last.business_id = {$assignmentAlias}.business_id
                    AND pde_last.pump_id = {$assignmentAlias}.pump_id
                    AND pde_last.closing_meter IS NOT NULL
                    AND pde_last.closing_meter > 0
                ORDER BY {$dateOrder} pde_last.id DESC
                LIMIT 1)";
        }

        if (Schema::hasColumn('pump_operator_assignments', 'closing_meter')) {
            $statusFilter = Schema::hasColumn('pump_operator_assignments', 'status')
                ? "AND poa_last.status IN ('close', 'closed')"
                : '';
            $candidates[] = "(SELECT NULLIF(poa_last.closing_meter, 0)
                FROM pump_operator_assignments AS poa_last
                WHERE poa_last.business_id = {$assignmentAlias}.business_id
                    AND poa_last.pump_id = {$assignmentAlias}.pump_id
                    {$statusFilter}
                    AND poa_last.id <> {$assignmentAlias}.id
                    AND poa_last.closing_meter IS NOT NULL
                    AND poa_last.closing_meter > 0
                ORDER BY poa_last.id DESC
                LIMIT 1)";
        }

        // Fallbacks only for very first pump receipt / old tenant data with no previous closing row.
        foreach (['pod_last_meter', 'last_meter_reading', 'current_meter'] as $column) {
            if (Schema::hasColumn('pumps', $column)) {
                $candidates[] = 'NULLIF(' . $pumpAlias . '.' . $column . ', 0)';
            }
        }
        if (Schema::hasColumn('pump_operator_assignments', 'starting_meter')) {
            $candidates[] = 'NULLIF(' . $assignmentAlias . '.starting_meter, 0)';
        }
        $candidates[] = '0';

        return 'COALESCE(' . implode(', ', $candidates) . ')';
    }


    public function getReceivePump()
    {
        $business_id = $this->resolveBusinessId();
        $pump_operator_id = $this->resolvePumpOperatorId();

        if (empty($business_id) || empty($pump_operator_id)) {
            abort(403, 'Pump operator is not available for this user.');
        }

        $shift = PetroShift::where('business_id', $business_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->where('status', '0')
            ->orderBy('id', 'desc')
            ->first();

        $query = PumpOperatorAssignment::from('pump_operator_assignments as poa')
            ->leftJoin('pumps as p', 'p.id', '=', 'poa.pump_id')
            ->leftJoin('pump_operators as pop', 'pop.id', '=', 'poa.pump_operator_id')
            ->where('poa.business_id', $business_id)
            ->where('poa.pump_operator_id', $pump_operator_id)
            ->where('poa.status', 'open')
            ->select(
                'poa.*',
                'poa.id as assignment_id',
                'p.pump_no',
                'pop.name as pumper_name',
                DB::raw($this->pumpCurrentMeterExpression('p', 'poa') . ' as starting_meter')
            )
            ->orderBy('p.pump_no');

        if (! empty($shift)) {
            $query->where('poa.shift_id', $shift->id);
        } else {
            $query->whereRaw('1 = 0');
        }

        $pumps = $query->get();
        $shift_number = ! empty($shift) ? ($shift->shift_no ?? $shift->shift_number ?? $shift->id) : '';
        $layout = empty(session()->get('pump_operator_main_system')) ? 'pumper' : 'app';

        return view('petropd::pd_operators.actions.pump_receive')->with(compact('pumps', 'shift_number', 'layout'));
    }

    public function confirmAssignment($id)
    {
        $business_id = $this->resolveBusinessId();
        $pump_operator_id = $this->resolvePumpOperatorId();

        $startingMeterExpression = $this->pumpCurrentMeterExpression('p', 'poa') . ' as starting_meter';

        $pump = PumpOperatorAssignment::from('pump_operator_assignments as poa')
            ->leftJoin('pumps as p', 'p.id', '=', 'poa.pump_id')
            ->leftJoin('products as pr', 'pr.id', '=', 'p.product_id')
            ->where('poa.id', $id)
            ->where('poa.business_id', $business_id)
            ->where('poa.pump_operator_id', $pump_operator_id)
            ->select(
                'poa.*',
                'poa.id as id',
                'p.pump_no',
                'p.pump_name',
                'pr.name',
                DB::raw('COALESCE(p.pump_name, p.pump_no) as pump_name'),
                DB::raw($startingMeterExpression)
            )
            ->firstOrFail();

        return view('petropd::pd_operators.partials.pumper_assignment_pumper')->with(compact('pump'));
    }

    public function postConfirmAssignment(Request $request, $id)
    {
        $business_id = $this->resolveBusinessId();
        $pump_operator_id = $this->resolvePumpOperatorId();

        try {
            $assignment = PumpOperatorAssignment::where('id', $id)
                ->where('business_id', $business_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->firstOrFail();

            $starting_meter = (float) str_replace(',', '', (string) $request->input('starting_meter', $assignment->starting_meter));
            $closing_meter = (float) str_replace(',', '', (string) $request->input('closing_meter', ''));
            $status_checked = $request->has('status');

            if ($status_checked && round($starting_meter, 3) !== round($closing_meter, 3)) {
                return response()->json([
                    'success' => 0,
                    'msg' => __('petropd::lang.reconfirm_meter') . ' does not match ' . __('petropd::lang.starting_meter'),
                ], 422);
            }

            $assignment->is_confirmed = 1;
            if ($status_checked) {
                $assignment->status = 'open';
            }
            if (Schema::hasColumn('pump_operator_assignments', 'starting_meter')) {
                $assignment->starting_meter = $starting_meter;
            }


            /*
             * The reconfirm box holds the same number typed a second time, and
             * the check above has already proved the two match. So if the
             * readonly field did not survive but the typed one did, use it -
             * the operator's own entry is the better record either way.
             */
            /*
             * MA-002 (IS-1926): the reconfirm heuristic is REMOVED.
             *
             * I had it overwrite starting_meter with the reconfirmed figure
             * whenever the first was zero, on the assumption that a zero meant
             * the field had not arrived. It does not - a pump can genuinely be
             * received at 0.00, and on this system that is exactly what
             * happened. Overwriting it would have corrupted a correct value.
             *
             * The log line above still records what arrived, which is what was
             * actually needed.
             */

            $assignment->save();

            return response()->json([
                'success' => 1,
                'msg' => __('lang_v1.success'),
            ]);
        } catch (\Throwable $e) {
            Log::error('PetroPD receive pump failed', [
                'assignment_id' => $id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ], 500);
        }
    }
}

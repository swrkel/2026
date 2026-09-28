<?php

namespace Modules\PetroPD\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Modules\PetroPD\Entities\Pump;
use Modules\PetroPD\Entities\PumperDayEntry;
use Modules\PetroPD\Entities\PumpOperatorAssignment;

class PDPumpOperatorActionsController extends Controller
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

    public function getClosingMeter($pump_id)
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.close_pump');

        /*
         * MA-002 (IS-1926): resolve the business the way the rest of this
         * module does.
         *
         * This used Auth::user()->business_id alone. PDPumpReceiveController -
         * the screen this one has to agree with - uses:
         *
         *     session('business.id')
         *         ?: session('user.business_id')
         *         ?: Auth::user()->business_id
         *
         * If the two disagree, the assignment lookup below filters on the wrong
         * business, finds nothing, and the starting meter falls through to the
         * pump master figure - which is the symptom being reported. The two
         * screens must agree on the business before they can agree on a meter.
         */
        $business_id = (int) (
            session('business.id')
                ?: session('user.business_id')
                ?: (Auth::user()->business_id ?? 0)
        );

        $pump = Pump::leftJoin('products', 'pumps.product_id', '=', 'products.id')
            ->leftJoin('variations', 'products.id', '=', 'variations.product_id')
            ->leftJoin('variation_location_details', 'variations.id', '=', 'variation_location_details.variation_id')
            ->where('pumps.business_id', $business_id)
            ->where('pumps.id', $pump_id)
            ->select('sell_price_inc_tax', 'pumps.*', 'variation_location_details.qty_available')
            ->firstOrFail();

        /*
         * MA-002 (IS-1926): pick THIS operator's own open assignment.
         *
         * The query filtered on the pump and on status 'open', but NOT on the
         * pump operator - so it took whichever open assignment had the highest
         * id, which on a pump handed between operators can be somebody else's.
         * Its starting_meter is then the wrong shift's figure, and the fallback
         * below drops back to the pump's last meter, which is exactly the "last
         * entered meter" being reported.
         *
         * The operator is resolved the way the rest of this module does -
         * session first, because an operator working through the pumper
         * dashboard has the id there and the user column is not always set.
         *
         * If no operator can be resolved the filter is skipped and the previous
         * behaviour applies, so nothing breaks for an admin opening this screen.
         */
        $currentOperatorId = (int) (
            session('pump_operator_id')
                ?: session('pumper_operator_id')
                ?: (Auth::user()->pump_operator_id ?? 0)
        );

        $assignmentQuery = PumpOperatorAssignment::where('business_id', $business_id)
            ->where('pump_id', $pump_id)
            ->where('status', 'open');

        if ($currentOperatorId > 0) {
            $assignmentQuery->where('pump_operator_id', $currentOperatorId);
        }

        /*
         * A CONFIRMED assignment wins. Receiving a pump sets is_confirmed = 1
         * and writes the meter the operator agreed; an unconfirmed row has not
         * been through that step and its starting_meter may be empty.
         */
        $assignment = (clone $assignmentQuery)
            ->where('is_confirmed', 1)
            ->orderByDesc('id')
            ->first();

        if (empty($assignment)) {
            $assignment = $assignmentQuery->orderByDesc('id')->first();
        }

        /*
         * MA-002 (IS-1926): the RECEIVED meter, taken the same way the Receive
         * Pump screen takes it.
         *
         * The two screens were reading different things entirely:
         *
         *   RECEIVE prefills from pumper_day_entries.closing_meter - the last
         *           saved closing meter for that pump. Its own comment states
         *           the rule: "next receive starting meter = last saved closing
         *           meter for the same pump" (S362).
         *
         *   CLOSE   read pump_operator_assignments.starting_meter, falling back
         *           to the pump master meters. It never looked at
         *           pumper_day_entries at all.
         *
         * On this system pump_operator_assignments is EMPTY and both pod_ meter
         * columns are NULL, so Close fell all the way through to
         * pumps.last_meter_reading - the pump master figure, which is the "last
         * entered meter" being reported.
         *
         * Reading the same source as Receive makes the two agree by
         * construction, rather than by both happening to hold the same number.
         */
        $receivedMeter = null;

        if (Schema::hasTable('pumper_day_entries')) {
            $lastEntry = DB::table('pumper_day_entries')
                ->where('business_id', $business_id)
                ->where('pump_id', $pump_id)
                /*
                 * MA-002 (IS-1926): no "> 0" filter.
                 *
                 * A pump legitimately closed at 0.00 - a new pump, or a shift
                 * with no sales - would otherwise be skipped, and the next
                 * receive would silently jump back to an older entry.
                 */
                ->whereNotNull('closing_meter')
                ->orderByDesc(Schema::hasColumn('pumper_day_entries', 'date') ? 'date' : 'id')
                ->orderByDesc('id')
                ->first();

            if ($lastEntry) {
                // Zero is a real reading, so the row's existence is the test.
                $receivedMeter = (float) $lastEntry->closing_meter;
            }
        }


        $pumper_name = $assignment ? optional($assignment->pumpOperator)->name : '';
        $shift_number = $assignment ? $assignment->shift_number : null;
        $layout = empty(session()->get('pump_operator_main_system')) ? 'pumper' : 'app';

        /*
         * MA-002 (IS-1922 #1): pass the assignment through.
         *
         * Receiving a pump saves the meter the operator confirmed onto the
         * ASSIGNMENT:
         *
         *     $assignment->starting_meter = $starting_meter;   (PDPumpReceiveController)
         *
         * and writes nothing back to the pumps table - there is no Pump::where
         * anywhere in that controller. But Close Pump reads its Starting Meter
         * from the PUMP:
         *
         *     $pump->pod_last_meter / $pump->last_meter_reading
         *
         * so the received meter never reached this screen. It showed whatever
         * the pump last held, which is the previous shift's figure.
         *
         * The assignment is already loaded here for the pumper name and shift
         * number - it just was not being passed on.
         */
        return view('petropd::pd_operators.actions.closing_meter')->with(compact(
            'pump',
            'layout',
            'business_id',
            'pumper_name',
            'shift_number',
            'assignment',
            'receivedMeter'
        ));
    }

    public function postClosingMeter($pump_id, Request $request)
    {
        $this->authorizePumperDashboardPermission('pumper_dashboard.close_pump');

        $pump_operator_id = Auth::user()->pump_operator_id;
        $business_id = Auth::user()->business_id;

        try {
            DB::beginTransaction();

            $assignment = PumpOperatorAssignment::where('business_id', $business_id)
                ->where('pump_id', $pump_id)
                ->where('status', 'open')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if (! $assignment) {
                DB::rollBack();
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => __('petropd::lang.assignment_not_found')
                ]);
            }

            PumperDayEntry::create([
                'business_id' => $business_id,
                'pump_operator_id' => $pump_operator_id,
                'date' => date('Y-m-d'),
                'pump_id' => $pump_id,
                'pump_no' => $request->pump_no,
                'starting_meter' => $request->starting_meter,
                'closing_meter' => $request->closing_meter,
                'testing_ltr' => $request->testing_ltr,
                'sold_ltr' => $request->sold_ltr,
                'amount' => $request->amount_hidden,
                'pumper_assignment_id' => $assignment->id,
                'shift_id' => $assignment->shift_id,
            ]);

            Pump::where('business_id', $business_id)->where('id', $pump_id)->update([
                'pod_starting_meter' => $request->starting_meter,
                'pod_last_meter' => $request->closing_meter,
            ]);

            $assignment->update([
                'status' => 'close',
                'close_date_and_time' => Carbon::now(),
                'closing_meter' => $request->closing_meter,
            ]);

            DB::commit();

            return redirect()->to('/petropd/pd-operators/dashboard?tab=closing_meter')->with('status', [
                'success' => 1,
                'msg' => __('petropd::lang.success')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('PetroPD close meter failed. File:' . $e->getFile() . ' Line:' . $e->getLine() . ' Message:' . $e->getMessage());

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }
}

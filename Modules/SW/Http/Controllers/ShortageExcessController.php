<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\Shift;

/**
 * Daily Shortage Excess - tab 6 of SW Operators.
 *
 * Where a difference is RECORDED against a shift. Entered by hand rather than
 * computed: the settlement is not final until the user has added every payment,
 * so a figure worked out now would be wrong by the time it mattered.
 *
 * Recovering or paying it happens from the operator's row - Recover Shortage
 * and Pay Excess - which post to Finance and update settled_amount here.
 *
 * When a settlement is prepared it auto-loads these for every shift it covers
 * and totals them.
 */
class ShortageExcessController extends Controller
{
    protected function businessId(): int
    {
        return (int) (optional(auth()->user())->business_id ?: session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function data(Request $request)
    {
        $businessId=$this->businessId();
        if(!Schema::hasTable('sw_daily_shortage_excess')||!Schema::hasTable('sw_shifts')) return response()->json(['data'=>[]]);
        $q=DB::table('sw_daily_shortage_excess as se')->join('sw_shifts as s','s.id','=','se.sw_shift_id')->where('s.business_id',$businessId)
          ->when($request->filled('location_id'),fn($x)=>$x->where('s.location_id',(int)$request->location_id));
        if(Schema::hasTable('pump_operators'))$q->leftJoin('pump_operators as po','po.id','=','se.pump_operator_id');
        $sel=['se.*','s.sw_shift_no','s.shift_date','s.status as shift_status'];$sel[]=Schema::hasTable('pump_operators')?'po.name as operator_name':DB::raw('NULL as operator_name');
        $rows=$q->orderByDesc('se.id')->limit(5000)->get($sel);
        $data=$rows->map(function($r){$settled=(float)($r->settled_amount??0);$amount=(float)($r->amount??0);$out=max($amount-$settled,0);return ['action'=>view('sw::operators.partials.shortage_excess_actions',['row'=>$r,'outstanding'=>$out])->render(),'date'=>!empty($r->shift_date)?\Carbon\Carbon::parse($r->shift_date)->format('d/m/Y'):(!empty($r->created_at)?\Carbon\Carbon::parse($r->created_at)->format('d/m/Y'):'—'),'shift_no'=>e($r->sw_shift_no??'—'),'operator'=>e($r->operator_name??'—'),'type'=>($r->type??'')==='excess'?'<span class="label label-success">'.__('sw::lang.excess').'</span>':'<span class="label label-warning">'.__('sw::lang.shortage').'</span>','amount'=>number_format($amount,2),'settled'=>number_format($settled,2),'outstanding'=>$out>0?'<strong>'.number_format($out,2).'</strong>':'<span class="text-muted">0.00</span>','note'=>e($r->note??'')];});
        return response()->json(['data'=>$data]);
    }

    public function create()
    {
        return view('sw::operators.partials.shortage_excess_form', [
            'row' => null,
            'operators' => $this->operators($this->businessId()),
        ]);
    }

    public function edit($id)
    {
        $businessId = $this->businessId();

        $row = DB::table('sw_daily_shortage_excess as se')
            ->join('sw_shifts', 'sw_shifts.id', '=', 'se.sw_shift_id')
            ->where('sw_shifts.business_id', $businessId)
            ->where('se.id', (int) $id)
            ->first(['se.*', 'sw_shifts.sw_shift_no', 'sw_shifts.status as shift_status']);

        abort_if(! $row, 404);

        /*
         | Refuse once anything has been recovered or paid.
         |
         | Changing the amount after a partial recovery would leave
         | settled_amount describing a figure that no longer exists - and the
         | ledger entries from that recovery are already posted. The record must
         | keep matching what was actually done.
        */
        if ((float) $row->settled_amount > 0) {
            return $this->refuse(__('sw::lang.already_partly_settled'));
        }

        $rowShift = Shift::where('business_id', $businessId)->find((int) $row->sw_shift_id);
        if (! $rowShift || ! $rowShift->isOpen()) {
            return $this->refuse(__('sw::lang.shift_closed_no_edit'));
        }

        return view('sw::operators.partials.shortage_excess_form', [
            'row' => $row,
            'operators' => $this->operators($businessId),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $businessId = $this->businessId();
        $shift = $this->shiftOrFail($businessId, (int) $data['sw_shift_id']);

        DB::transaction(function () use ($data, $shift) {
            DB::table('sw_daily_shortage_excess')->insert([
                'sw_shift_id' => $shift->id,
                'pump_operator_id' => $data['pump_operator_id'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'settled_amount' => 0,
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncOperatorBalance((int) $data['pump_operator_id']);
        });

        return $this->done(__('sw::lang.shortage_excess_added'));
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);
        $businessId = $this->businessId();

        $existing = DB::table('sw_daily_shortage_excess')->where('id', (int) $id)->first();
        abort_if(! $existing, 404);

        abort_if((float) $existing->settled_amount > 0, 422, __('sw::lang.already_partly_settled'));

        $this->shiftOrFail($businessId, (int) $existing->sw_shift_id);

        DB::transaction(function () use ($data, $id, $existing) {
            DB::table('sw_daily_shortage_excess')->where('id', (int) $id)->update([
                'pump_operator_id' => $data['pump_operator_id'],
                'type' => $data['type'],
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            $this->syncOperatorBalance((int) $existing->pump_operator_id);
            $this->syncOperatorBalance((int) $data['pump_operator_id']);
        });

        return $this->done(__('sw::lang.shortage_excess_updated'));
    }

    public function destroy($id)
    {
        $businessId = $this->businessId();

        $existing = DB::table('sw_daily_shortage_excess')->where('id', (int) $id)->first();
        abort_if(! $existing, 404);

        abort_if((float) $existing->settled_amount > 0, 422, __('sw::lang.already_partly_settled'));

        $this->shiftOrFail($businessId, (int) $existing->sw_shift_id);

        DB::transaction(function () use ($id, $existing) {
            DB::table('sw_daily_shortage_excess')->where('id', (int) $id)->delete();
            $this->syncOperatorBalance((int) $existing->pump_operator_id);
        });

        return $this->done(__('sw::lang.shortage_excess_deleted'));
    }

    // ------------------------------------------------------------------

    /**
     * Keep the operator's running short and excess balances in step.
     *
     * Those two columns on pump_operators are what the Pump Operators tab shows
     * and what decides whether Recover Shortage appears. They are rebuilt from
     * the outstanding rows here rather than adjusted by hand, so a corrected or
     * deleted entry cannot leave the operator owing something that no longer
     * exists.
     */
    protected function syncOperatorBalance(int $operatorId): void
    {
        if ($operatorId <= 0) {
            return;
        }

        $totals = DB::table('sw_daily_shortage_excess')
            ->where('pump_operator_id', $operatorId)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'shortage'
                    THEN GREATEST(amount - COALESCE(settled_amount,0), 0) ELSE 0 END), 0) as short_total,
                COALESCE(SUM(CASE WHEN type = 'excess'
                    THEN GREATEST(amount - COALESCE(settled_amount,0), 0) ELSE 0 END), 0) as excess_total
            ")
            ->first();

        DB::table('pump_operators')->where('id', $operatorId)->update([
            'short_amount' => round((float) $totals->short_total, 2),
            'excess_amount' => round((float) $totals->excess_total, 2),
            'updated_at' => now(),
        ]);
    }

    /**
     * The shift must belong to this business.
     *
     * Unlike the other daily tabs this does NOT require the shift to be open: a
     * difference is often found after the shift has closed, which is when the
     * cash is counted.
     */
    protected function shiftOrFail(int $businessId, int $shiftId): Shift
    {
        $shift = Shift::where('business_id', $businessId)->find($shiftId);
        abort_if(! $shift, 404);

        return $shift;
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'sw_shift_id' => 'required|integer',
            'pump_operator_id' => 'required|integer',
            'type' => 'required|in:shortage,excess',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string',
        ]);
    }

    protected function operators(int $businessId)
    {
        return DB::table('pump_operators')
            ->where('business_id', $businessId)->where('active', 1)
            ->orderBy('name')->pluck('name', 'id');
    }

    protected function done(string $msg)
    {
        return redirect()->route('sw.payments.index')
            ->with('status', ['success' => 1, 'msg' => $msg])
            ->with('status.tab', 'sw_daily_shortage_excess');
    }

    protected function refuse(string $message)
    {
        return response(
            '<div class="modal-dialog"><div class="modal-content">'
            . '<div class="modal-body"><div class="alert alert-warning" style="margin:0">'
            . e($message)
            . '</div></div><div class="modal-footer">'
            . '<button type="button" class="btn btn-default" data-dismiss="modal">'
            . __('messages.close') . '</button></div></div></div>'
        );
    }
}

<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\Shift;
use Modules\SW\Services\CollectionFormNumberService;

/**
 * Daily Cheques - tab 7 of SW Operators.
 *
 * No account_id: which account a cheque lands in is decided at settlement, not
 * while the shift is running.
 *
 * RECORDS ONLY. Nothing posts until the settlement is saved.
 */
class DailyChequeController extends Controller
{
    public function __construct(protected CollectionFormNumberService $numbers)
    {
    }

    protected function businessId(): int
    {
        return (int) (optional(auth()->user())->business_id ?: session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function data(Request $request)
    {
        $businessId=$this->businessId();
        if(!Schema::hasTable('sw_daily_cheques')||!Schema::hasTable('sw_shifts')) return response()->json(['data'=>[]]);
        $q=DB::table('sw_daily_cheques as ch')->join('sw_shifts as s','s.id','=','ch.sw_shift_id')->where('s.business_id',$businessId)
          ->when($request->filled('location_id'),fn($x)=>$x->where('s.location_id',(int)$request->location_id));
        if(Schema::hasTable('pump_operators'))$q->leftJoin('pump_operators as po','po.id','=','ch.pump_operator_id');
        if(Schema::hasTable('contacts')&&Schema::hasColumn('sw_daily_cheques','contact_id'))$q->leftJoin('contacts as c','c.id','=','ch.contact_id');
        $sel=['ch.*','s.sw_shift_no','s.status as shift_status'];
        $sel[]=Schema::hasTable('pump_operators')?'po.name as operator_name':DB::raw('NULL as operator_name');
        $sel[]=(Schema::hasTable('contacts')&&Schema::hasColumn('sw_daily_cheques','contact_id'))?'c.name as customer_name':DB::raw('NULL as customer_name');
        $rows=$q->orderByDesc('ch.id')->limit(5000)->get($sel); $running=[]; $today=now()->startOfDay();
        $data=$rows->sortBy('id')->map(function($r)use(&$running,$today){$key=($r->sw_shift_id??0).':'.($r->pump_operator_id??0);$amount=(float)($r->amount??0);$running[$key]=($running[$key]??0)+$amount;$total=isset($r->total_collection)?(float)$r->total_collection:$running[$key];$d=!empty($r->cheque_date)?\Carbon\Carbon::parse($r->cheque_date):null;$post=$d&&$d->gt($today);return ['action'=>view('sw::operators.partials.daily_cheque_actions',['row'=>$r])->render(),'collection_form_no'=>e($r->collection_form_no??'—'),'shift_no'=>e($r->sw_shift_no??'—'),'operator'=>e($r->operator_name??'—'),'customer'=>e($r->customer_name??'—'),'cheque_no'=>e($r->cheque_no??'—'),'bank'=>e($r->bank??'—'),'cheque_date'=>$d?($post?'<span class="sw-postdated" title="'.__('sw::lang.post_dated').'">'.$d->format('d/m/Y').'</span>':$d->format('d/m/Y')):'—','amount'=>number_format($amount,2),'total_collection'=>number_format($total,2),'note'=>e($r->note??'')];})->reverse()->values();
        return response()->json(['data'=>$data]);
    }

    public function create()
    {
        return view('sw::operators.partials.daily_cheque_form', [
            'row' => null,
            'operators' => $this->operators($this->businessId()),
        ]);
    }

    public function edit($id)
    {
        $businessId = $this->businessId();

        $row = DB::table('sw_daily_cheques as ch')
            ->join('sw_shifts', 'sw_shifts.id', '=', 'ch.sw_shift_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'ch.contact_id')
            ->where('sw_shifts.business_id', $businessId)
            ->where('ch.id', (int) $id)
            ->first(['ch.*', 'sw_shifts.sw_shift_no', 'sw_shifts.status as shift_status',
                     'contacts.name as customer_name']);

        abort_if(! $row, 404);

        $rowShift = Shift::where('business_id', $businessId)->find((int) $row->sw_shift_id);
        if (! $rowShift || ! $rowShift->isOpen()) {
            return $this->refuse(__('sw::lang.shift_closed_no_edit'));
        }

        return view('sw::operators.partials.daily_cheque_form', [
            'row' => $row,
            'operators' => $this->operators($businessId),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $businessId = $this->businessId();
        $shift = $this->openShiftOrFail($businessId, (int) $data['sw_shift_id']);

        DB::transaction(function () use ($data, $businessId, $shift) {
            DB::table('sw_daily_cheques')->insert([
                'sw_shift_id' => $shift->id,
                'pump_operator_id' => $data['pump_operator_id'],
                'collection_form_no' => $this->numbers->next($businessId, (int) $shift->location_id),
                'contact_id' => $data['contact_id'] ?? null,
                'cheque_no' => $data['cheque_no'],
                'bank' => $data['bank'] ?? null,
                'cheque_date' => $data['cheque_date'],
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->refreshTotals($shift->id, (int) $data['pump_operator_id']);
        });

        return $this->done(__('sw::lang.cheque_added'));
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);
        $businessId = $this->businessId();

        $existing = DB::table('sw_daily_cheques')->where('id', (int) $id)->first();
        abort_if(! $existing, 404);

        $shift = $this->openShiftOrFail($businessId, (int) $existing->sw_shift_id);

        DB::transaction(function () use ($data, $id, $existing, $shift) {
            DB::table('sw_daily_cheques')->where('id', (int) $id)->update([
                'pump_operator_id' => $data['pump_operator_id'],
                'contact_id' => $data['contact_id'] ?? null,
                'cheque_no' => $data['cheque_no'],
                'bank' => $data['bank'] ?? null,
                'cheque_date' => $data['cheque_date'],
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            $this->refreshTotals($shift->id, (int) $existing->pump_operator_id);
            $this->refreshTotals($shift->id, (int) $data['pump_operator_id']);
        });

        return $this->done(__('sw::lang.cheque_updated'));
    }

    public function destroy($id)
    {
        $businessId = $this->businessId();

        $existing = DB::table('sw_daily_cheques')->where('id', (int) $id)->first();
        abort_if(! $existing, 404);

        $shift = $this->openShiftOrFail($businessId, (int) $existing->sw_shift_id);

        DB::transaction(function () use ($id, $existing, $shift) {
            DB::table('sw_daily_cheques')->where('id', (int) $id)->delete();
            $this->refreshTotals($shift->id, (int) $existing->pump_operator_id);
        });

        return $this->done(__('sw::lang.cheque_deleted'));
    }

    /**
     * Banks already used, for the entry form's suggestion list.
     *
     * The bank is free text, as agreed - there is no banks table on this
     * estate. Offering what has been typed before is the cheapest way to keep
     * one bank spelled one way, without imposing a list nobody maintains.
     */
    public function banks()
    {
        $banks = DB::table('sw_daily_cheques as ch')
            ->join('sw_shifts', 'sw_shifts.id', '=', 'ch.sw_shift_id')
            ->where('sw_shifts.business_id', $this->businessId())
            ->whereNotNull('ch.bank')
            ->where('ch.bank', '!=', '')
            ->distinct()
            ->orderBy('ch.bank')
            ->limit(100)
            ->pluck('ch.bank');

        return response()->json($banks);
    }

    // ------------------------------------------------------------------

    protected function refreshTotals(int $shiftId, int $operatorId): void
    {
        $rows = DB::table('sw_daily_cheques')
            ->where('sw_shift_id', $shiftId)
            ->where('pump_operator_id', $operatorId)
            ->orderBy('id')
            ->get(['id', 'amount']);

        $running = 0.0;
        foreach ($rows as $row) {
            $running += (float) $row->amount;
            DB::table('sw_daily_cheques')->where('id', $row->id)
                ->update(['total_collection' => round($running, 4)]);
        }
    }

    protected function openShiftOrFail(int $businessId, int $shiftId): Shift
    {
        $shift = Shift::where('business_id', $businessId)->find($shiftId);

        abort_if(! $shift, 404);
        abort_unless($shift->isOpen(), 422, __('sw::lang.shift_closed_no_entry'));

        return $shift;
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'sw_shift_id' => 'required|integer',
            'pump_operator_id' => 'required|integer',
            'contact_id' => 'nullable|integer',
            'cheque_no' => 'required|string|max:60',
            'bank' => 'nullable|string|max:191',
            'cheque_date' => 'required|date',
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
            ->with('status.tab', 'sw_daily_cheques');
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

<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\Shift;
use Modules\SW\Services\CollectionFormNumberService;

/**
 * Daily Cards - tab 5 of SW Operators.
 *
 * The card type IS a Finance account: the accounts under the "Card" group.
 * Choosing "Amex Card" is choosing where the money lands, which is why there is
 * no separate account field.
 *
 * RECORDS ONLY. Nothing posts until the settlement is saved.
 */
class DailyCardController extends Controller
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
        $businessId = $this->businessId();
        if (! Schema::hasTable('sw_daily_cards') || ! Schema::hasTable('sw_shifts')) return response()->json(['data' => []]);

        $q = DB::table('sw_daily_cards as dc')->join('sw_shifts as s','s.id','=','dc.sw_shift_id')->where('s.business_id',$businessId)
            ->when($request->filled('location_id'), fn($x)=>$x->where('s.location_id',(int)$request->location_id));
        if (Schema::hasTable('pump_operators')) $q->leftJoin('pump_operators as po','po.id','=','dc.pump_operator_id');
        if (Schema::hasTable('contacts') && Schema::hasColumn('sw_daily_cards','contact_id')) $q->leftJoin('contacts as c','c.id','=','dc.contact_id');
        $accountFk = Schema::hasColumn('sw_daily_cards','card_type_account_id') ? 'card_type_account_id' : (Schema::hasColumn('sw_daily_cards','account_id') ? 'account_id' : null);
        if ($accountFk && Schema::hasTable('accounts')) $q->leftJoin('accounts as a','a.id','=','dc.'.$accountFk);
        $sel=['dc.*','s.sw_shift_no','s.status as shift_status'];
        $sel[]=Schema::hasTable('pump_operators')?'po.name as operator_name':DB::raw('NULL as operator_name');
        $sel[]=(Schema::hasTable('contacts')&&Schema::hasColumn('sw_daily_cards','contact_id'))?'c.name as customer_name':DB::raw('NULL as customer_name');
        $sel[]=($accountFk&&Schema::hasTable('accounts'))?'a.name as card_type_name':DB::raw('NULL as card_type_name');
        $rows=$q->orderByDesc('dc.id')->limit(5000)->get($sel);
        $running=[];
        $data=$rows->sortBy('id')->map(function($r) use (&$running){
            $key=($r->sw_shift_id??0).':'.($r->pump_operator_id??0); $amount=(float)($r->amount??0); $running[$key]=($running[$key]??0)+$amount;
            $total=isset($r->total_collection)?(float)$r->total_collection:$running[$key];
            $when=$r->card_date??$r->created_at??null; $slip=$r->slip_no??$r->reference??'—';
            $cardType=$r->card_type_name??$r->card_type??'—';
            return ['action'=>view('sw::operators.partials.daily_card_actions',['row'=>$r])->render(),'date'=>$when?\Carbon\Carbon::parse($when)->format('d/m/Y'):'—','collection_form_no'=>e($r->collection_form_no??'—'),'shift_no'=>e($r->sw_shift_no??'—'),'operator'=>e($r->operator_name??'—'),'customer'=>e($r->customer_name??'—'),'card_type'=>e($cardType),'card_number'=>e($this->maskCard($r->card_number??null)),'slip_no'=>e($slip),'amount'=>number_format($amount,2),'total_collection'=>number_format($total,2),'note'=>e($r->note??'')];
        })->reverse()->values();
        return response()->json(['data'=>$data]);
    }

    /**
     * Show only the last four digits.
     *
     * A full card number has no business being displayed in a list anyone with
     * the page open can read, or exported to a spreadsheet that then circulates.
     * The last four is what identifies a payment when reconciling.
     */
    protected function maskCard(?string $number): string
    {
        $number = trim((string) $number);

        if ($number === '') {
            return '—';
        }

        return strlen($number) <= 4
            ? $number
            : str_repeat('•', 4) . ' ' . substr($number, -4);
    }

    public function create()
    {
        $businessId = $this->businessId();

        return view('sw::operators.partials.daily_card_form', [
            'row' => null,
            'operators' => $this->operators($businessId),
            'card_accounts' => $this->cardAccounts($businessId),
        ]);
    }

    public function edit($id)
    {
        $businessId = $this->businessId();

        $row = DB::table('sw_daily_cards as dc')
            ->join('sw_shifts', 'sw_shifts.id', '=', 'dc.sw_shift_id')
            ->leftJoin('contacts', 'contacts.id', '=', 'dc.contact_id')
            ->where('sw_shifts.business_id', $businessId)
            ->where('dc.id', (int) $id)
            ->first(['dc.*', 'sw_shifts.sw_shift_no', 'sw_shifts.status as shift_status',
                     'contacts.name as customer_name']);

        abort_if(! $row, 404);

        $rowShift = Shift::where('business_id', $businessId)->find((int) $row->sw_shift_id);
        if (! $rowShift || ! $rowShift->isOpen()) {
            return $this->refuse(__('sw::lang.shift_closed_no_edit'));
        }

        return view('sw::operators.partials.daily_card_form', [
            'row' => $row,
            'operators' => $this->operators($businessId),
            'card_accounts' => $this->cardAccounts($businessId),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $businessId = $this->businessId();
        $shift = $this->openShiftOrFail($businessId, (int) $data['sw_shift_id']);

        $this->guardCardAccount($businessId, (int) $data['card_type_account_id']);

        DB::transaction(function () use ($data, $businessId, $shift) {
            DB::table('sw_daily_cards')->insert([
                'sw_shift_id' => $shift->id,
                'pump_operator_id' => $data['pump_operator_id'],
                'collection_form_no' => $this->numbers->next($businessId, (int) $shift->location_id),
                'contact_id' => $data['contact_id'] ?? null,
                'card_type_account_id' => $data['card_type_account_id'],
                'card_number' => $data['card_number'] ?? null,
                'slip_no' => $data['slip_no'] ?? null,
                'amount' => $data['amount'],
                'card_date' => $data['card_date'],
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->refreshTotals($shift->id, (int) $data['pump_operator_id']);
        });

        return $this->done(__('sw::lang.daily_card_added'));
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);
        $businessId = $this->businessId();

        $existing = DB::table('sw_daily_cards')->where('id', (int) $id)->first();
        abort_if(! $existing, 404);

        $shift = $this->openShiftOrFail($businessId, (int) $existing->sw_shift_id);
        $this->guardCardAccount($businessId, (int) $data['card_type_account_id']);

        DB::transaction(function () use ($data, $id, $existing, $shift) {
            DB::table('sw_daily_cards')->where('id', (int) $id)->update([
                'pump_operator_id' => $data['pump_operator_id'],
                'contact_id' => $data['contact_id'] ?? null,
                'card_type_account_id' => $data['card_type_account_id'],
                'card_number' => $data['card_number'] ?? null,
                'slip_no' => $data['slip_no'] ?? null,
                'amount' => $data['amount'],
                'card_date' => $data['card_date'],
                'note' => $data['note'] ?? null,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            $this->refreshTotals($shift->id, (int) $existing->pump_operator_id);
            $this->refreshTotals($shift->id, (int) $data['pump_operator_id']);
        });

        return $this->done(__('sw::lang.daily_card_updated'));
    }

    public function destroy($id)
    {
        $businessId = $this->businessId();

        $existing = DB::table('sw_daily_cards')->where('id', (int) $id)->first();
        abort_if(! $existing, 404);

        $shift = $this->openShiftOrFail($businessId, (int) $existing->sw_shift_id);

        DB::transaction(function () use ($id, $existing, $shift) {
            DB::table('sw_daily_cards')->where('id', (int) $id)->delete();
            $this->refreshTotals($shift->id, (int) $existing->pump_operator_id);
        });

        return $this->done(__('sw::lang.daily_card_deleted'));
    }

    // ------------------------------------------------------------------

    /**
     * The card accounts - the "Card" group only.
     *
     * "Own Cards" is a separate group and is deliberately excluded: those are
     * the business's own cards, used for paying rather than receiving, and they
     * have no place in a sale transaction.
     */
    protected function cardAccounts(int $businessId)
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasTable('account_groups')) {
            return collect();
        }

        return DB::table('accounts')
            ->join('account_groups', 'account_groups.id', '=', 'accounts.asset_type')
            ->where('accounts.business_id', $businessId)
            ->where('account_groups.business_id', $businessId)
            // Matched on the group NAME rather than a hard-coded id: the id
            // differs per business, the name does not.
            ->where('account_groups.name', 'Card')
            ->when(Schema::hasColumn('accounts', 'deleted_at'),
                fn ($q) => $q->whereNull('accounts.deleted_at'))
            ->when(Schema::hasColumn('accounts', 'is_closed'),
                fn ($q) => $q->where('accounts.is_closed', 0))
            ->orderBy('accounts.name')
            ->pluck('accounts.name', 'accounts.id');
    }

    /**
     * Refuse an account outside the Card group.
     *
     * Checked server-side as well as in the dropdown: a crafted request could
     * otherwise post a card payment to any account at all, including one from
     * "Own Cards".
     */
    protected function guardCardAccount(int $businessId, int $accountId): void
    {
        abort_unless(
            $this->cardAccounts($businessId)->keys()->contains($accountId),
            422,
            __('sw::lang.not_a_card_account')
        );
    }

    /**
     * The running total for one operator on one shift.
     *
     * A cache of the entries, rebuilt from their sum whenever anything changes -
     * a total written once and never revisited drifts the moment a row is
     * corrected.
     */
    protected function refreshTotals(int $shiftId, int $operatorId): void
    {
        $rows = DB::table('sw_daily_cards')
            ->where('sw_shift_id', $shiftId)
            ->where('pump_operator_id', $operatorId)
            ->orderBy('id')
            ->get(['id', 'amount']);

        $running = 0.0;
        foreach ($rows as $row) {
            $running += (float) $row->amount;
            DB::table('sw_daily_cards')->where('id', $row->id)
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
            'card_type_account_id' => 'required|integer',
            'contact_id' => 'nullable|integer',
            'card_number' => 'nullable|string|max:100',
            'slip_no' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'card_date' => 'required|date',
            'note' => 'nullable|string',
        ]);
    }

    protected function operators(int $businessId)
    {
        if (! Schema::hasTable('pump_operators')
            || ! Schema::hasTable('sw_shift_operators')
            || ! Schema::hasTable('sw_shifts')) {
            return collect();
        }

        // IS2230: daily entries belong only to operators who are working now.
        // The operator must be assigned to at least one OPEN SW shift in this
        // same tenant business.  Filtering here keeps Add and Edit consistent.
        return DB::table('pump_operators')
            ->where('pump_operators.business_id', $businessId)
            ->where('pump_operators.active', 1)
            ->whereExists(function ($q) use ($businessId) {
                $q->select(DB::raw(1))
                    ->from('sw_shift_operators as so')
                    ->join('sw_shifts as s', 's.id', '=', 'so.sw_shift_id')
                    ->whereColumn('so.pump_operator_id', 'pump_operators.id')
                    ->where('s.business_id', $businessId)
                    ->whereRaw("LOWER(TRIM(CAST(s.status AS CHAR))) IN ('0', 'open', 'opened')")
                    ->when(
                        Schema::hasColumn('sw_shifts', 'closed_at'),
                        fn ($openShift) => $openShift->whereNull('s.closed_at')
                    )
                    ->when(
                        Schema::hasColumn('sw_shifts', 'deleted_at'),
                        fn ($openShift) => $openShift->whereNull('s.deleted_at')
                    );
            })
            ->orderBy('pump_operators.name')
            ->pluck('pump_operators.name', 'pump_operators.id');
    }

    protected function done(string $msg)
    {
        return redirect()->route('sw.payments.index')
            ->with('status', ['success' => 1, 'msg' => $msg])
            ->with('status.tab', 'sw_daily_cards');
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

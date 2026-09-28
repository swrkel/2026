<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\Shift;
use Modules\SW\Services\OperatorPaymentService;

/**
 * Recover Shortage and Pay Excess.
 *
 * Separated from OperatorController because these POST TO THE LEDGER. Everything
 * else on the SW Operators page records and waits for settlement; these move
 * real money the moment they are saved, and keeping them in their own file makes
 * that boundary visible to whoever reads this next.
 */
class OperatorPaymentController extends Controller
{
    public function __construct(protected OperatorPaymentService $payments)
    {
    }

    protected function businessId(): int
    {
        return (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function recoverShortage($id)
    {
        return $this->form((int) $id, 'shortage');
    }

    public function payExcess($id)
    {
        return $this->form((int) $id, 'excess');
    }

    protected function form(int $id, string $type)
    {
        $businessId = $this->businessId();

        $operator = DB::table('pump_operators')
            ->where('business_id', $businessId)
            ->where('id', $id)
            ->first();

        abort_if(! $operator, 404);

        /*
         | Refuse when there is nothing outstanding.
         |
         | The action is hidden on a zero balance, but a bookmarked URL or a
         | stale page would still reach here - and a payment against nothing
         | posts to the ledger for no reason.
        */
        $outstanding = (float) ($type === 'shortage'
            ? ($operator->short_amount ?? 0)
            : ($operator->excess_amount ?? 0));

        if ($outstanding <= 0) {
            return response(
                '<div class="modal-dialog"><div class="modal-content">'
                . '<div class="modal-body"><div class="alert alert-warning" style="margin:0">'
                . __('sw::lang.nothing_outstanding')
                . '</div></div><div class="modal-footer">'
                . '<button type="button" class="btn btn-default" data-dismiss="modal">'
                . __('messages.close') . '</button></div></div></div>'
            );
        }

        return view('sw::operators.partials.operator_payment_form', [
            'operator' => $operator,
            'type' => $type,
            'accounts' => $this->financeAccounts($businessId),
            'payment_methods' => $this->paymentMethods(),
            'shift_numbers' => $this->shiftNumbers($businessId, (int) $operator->location_id),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pump_operator_id' => 'required|integer',
            'type' => 'required|in:shortage,excess',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'payment_method' => 'required|string|max:50',
            'account_id' => 'required|integer',
            'reference_no' => 'nullable|string|max:191',
            'sw_shift_no' => 'nullable|string|max:60',
            'note' => 'nullable|string',
        ], [
            'account_id.required' => __('sw::lang.choose_the_account'),
        ]);

        $businessId = $this->businessId();

        $operator = DB::table('pump_operators')
            ->where('business_id', $businessId)
            ->where('id', (int) $data['pump_operator_id'])
            ->first();

        abort_if(! $operator, 404);

        /*
         | Never recover or pay more than is outstanding.
         |
         | Checked here as well as on the form: the form's max attribute is a
         | convenience, and a crafted request would otherwise drive the balance
         | past zero and post a ledger entry for money that was never owed.
        */
        $outstanding = (float) ($data['type'] === 'shortage'
            ? ($operator->short_amount ?? 0)
            : ($operator->excess_amount ?? 0));

        if ((float) $data['amount'] > $outstanding + 0.0001) {
            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => __('sw::lang.more_than_outstanding', [
                    'amount' => number_format($outstanding, 2),
                ]),
            ]);
        }

        $data['location_id'] = $operator->location_id;

        $result = $this->payments->record($data, $businessId, (int) auth()->id());

        return redirect()
            ->route('sw.operators.index')
            ->with('status', [
                'success' => $result['success'] ? 1 : 0,
                'msg' => $result['msg'],
            ])
            ->with('status.tab', 'sw_excess_shortage');
    }

    // ------------------------------------------------------------------

    /** Accounts come from FINANCE, never from core. */
    protected function financeAccounts(int $businessId)
    {
        if (! Schema::hasTable('accounts')) {
            return collect();
        }

        return DB::table('accounts')
            ->where('business_id', $businessId)
            ->when(Schema::hasColumn('accounts', 'is_closed'), fn ($q) => $q->where('is_closed', 0))
            ->when(Schema::hasColumn('accounts', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    protected function paymentMethods(): array
    {
        return [
            'cash' => __('sw::lang.cash'),
            'bank_transfer' => __('sw::lang.bank_transfer'),
            'cheque' => __('sw::lang.cheque'),
            'card' => __('sw::lang.card'),
            'other' => __('sw::lang.other'),
        ];
    }

    /**
     * Shifts this payment may be attributed to.
     *
     * Not restricted to open shifts: a shortage recorded weeks ago may be
     * recovered against a shift long since settled, and refusing that would
     * leave the recovery unattributable.
     */
    protected function shiftNumbers(int $businessId, int $locationId)
    {
        try {
            return Shift::where('business_id', $businessId)
                ->when($locationId > 0, fn ($q) => $q->where('location_id', $locationId))
                ->orderByDesc('shift_date')
                ->limit(100)
                ->pluck('sw_shift_no', 'sw_shift_no');
        } catch (\Throwable $e) {
            return collect();
        }
    }
}

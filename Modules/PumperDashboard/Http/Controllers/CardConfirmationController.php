<?php

namespace Modules\PumperDashboard\Http\Controllers;

use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\PumperDashboard\Entities\DailyCard;
use Modules\PumperDashboard\Entities\PumpOperatorPayment;

/**
 * 8034 - Card Reconfirmation.
 *
 * WHY THIS EXISTS
 * ---------------
 * On 20 August a pump operator's card takings showed 530,922 against an actual
 * 201,358. The cause was the same card batch being written more than once, and
 * nobody noticed until the settlement was being finalised - by which point the
 * figures had already reached the accounts.
 *
 * This page puts a deliberate checkpoint before that: the operator sees every
 * card they have entered, corrects or removes any that are wrong, and confirms
 * the total. The confirmed figure is then what Close Shift uses.
 *
 * WHAT IT DELIBERATELY DOES NOT DO
 * --------------------------------
 * It does not delete or merge anything automatically. A duplicate looks exactly
 * like a genuine second card of the same amount, and only the person holding
 * the slips can tell them apart. The page shows the operator what is recorded
 * and lets them decide.
 */
class CardConfirmationController extends Controller
{
    protected $commonUtil;
    protected $productUtil;
    protected $moduleUtil;

    public function __construct(Util $commonUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil)
    {
        $this->commonUtil  = $commonUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil  = $moduleUtil;
    }

    private function resolveBusinessId(): int
    {
        $business_id = request()->session()->get('business.id')
            ?: request()->session()->get('user.business_id')
            ?: optional(Auth::user())->business_id;

        return (int) $business_id;
    }

    /**
     * The confirmation record lives in its own table so that nothing existing
     * has to change to support it.
     *
     * Created on demand rather than by a migration, because a migration cannot
     * be relied upon to have run on every tenant - several tenants on this
     * estate are known to be missing columns that migrations should have added.
     */
    private function ensureTable(): bool
    {
        try {
            if (Schema::hasTable('petro_card_confirmations')) {
                return true;
            }

            DB::statement("
                CREATE TABLE IF NOT EXISTS petro_card_confirmations (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                    business_id INT UNSIGNED NOT NULL,
                    shift_id INT UNSIGNED NULL,
                    pump_operator_id INT UNSIGNED NULL,
                    confirmed_total DECIMAL(22,4) NOT NULL DEFAULT 0,
                    confirmed_by INT UNSIGNED NULL,
                    confirmed_at TIMESTAMP NULL DEFAULT NULL,
                    created_at TIMESTAMP NULL DEFAULT NULL,
                    updated_at TIMESTAMP NULL DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY idx_biz_shift_op (business_id, shift_id, pump_operator_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            return Schema::hasTable('petro_card_confirmations');
        } catch (\Throwable $e) {
            Log::warning('8034: could not prepare petro_card_confirmations', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function confirmation($business_id, $shift_id, $pump_operator_id)
    {
        if (! $this->ensureTable()) {
            return null;
        }

        return DB::table('petro_card_confirmations')
            ->where('business_id', $business_id)
            ->where('shift_id', $shift_id)
            ->where('pump_operator_id', $pump_operator_id)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Every card payment the operator has entered for this shift.
     *
     * Read from pump_operator_payments, which is the operator's own record and
     * the one the settlement ultimately draws on. daily_cards is joined only to
     * pick up the slip number for display - it is NOT summed, because that is
     * exactly how a duplicated card row inflates a total.
     */
    private function cards($business_id, $shift_id, $pump_operator_id)
    {
        $query = PumpOperatorPayment::where('pump_operator_payments.business_id', $business_id)
            ->where('pump_operator_payments.payment_type', 'card')
            ->where('pump_operator_payments.shift_id', $shift_id)
            ->where('pump_operator_payments.pump_operator_id', $pump_operator_id);

        $has_slip = Schema::hasColumn('pump_operator_payments', 'slip_no');

        $select = [
            'pump_operator_payments.id',
            'pump_operator_payments.payment_amount',
            'pump_operator_payments.collection_form_no',
            'pump_operator_payments.created_at',
        ];

        if ($has_slip) {
            $select[] = 'pump_operator_payments.slip_no';
        }

        return $query->orderBy('pump_operator_payments.id')
            ->get($select)
            ->map(function ($row) use ($business_id, $has_slip) {
                $slip = $has_slip ? trim((string) ($row->slip_no ?? '')) : '';

                /*
                 | Fall back to daily_cards for the slip number when the payment
                 | does not carry one. Matched by collection number - never
                 | summed, only read, so a duplicate card row cannot affect any
                 | figure on this page.
                 */
                if ($slip === '' && ! empty($row->collection_form_no)) {
                    try {
                        $card = DailyCard::where('business_id', $business_id)
                            ->where('collection_no', $row->collection_form_no)
                            ->first(['slip_no']);

                        $slip = trim((string) ($card->slip_no ?? ''));
                    } catch (\Throwable $e) {
                        $slip = '';
                    }
                }

                $row->receipt_no    = $slip !== '' ? $slip : '—';
                $row->entered_date  = $row->created_at ? $row->created_at->format('Y-m-d') : '—';
                $row->entered_time  = $row->created_at ? $row->created_at->format('h:i A') : '—';

                return $row;
            });
    }

    /**
     * The confirmation page.
     */
    public function index(Request $request)
    {
        $business_id      = $this->resolveBusinessId();
        $shift_id         = $request->input('shift_id');
        $pump_operator_id = $request->input('pump_operator_id') ?: optional(Auth::user())->pump_operator_id;

        if (empty($shift_id) || empty($pump_operator_id)) {
            return redirect()->back()->with('status', [
                'success' => 0,
                'msg'     => 'Please select a shift and pump operator first.',
            ]);
        }

        $cards        = $this->cards($business_id, $shift_id, $pump_operator_id);
        $total        = (float) $cards->sum('payment_amount');
        $confirmation = $this->confirmation($business_id, $shift_id, $pump_operator_id);

        $operator_name = DB::table('pump_operators')->where('id', $pump_operator_id)->value('name');
        $shift_no      = DB::table('petro_shifts')->where('id', $shift_id)->value('shift_no');

        return view('pumperdashboard::card_confirmation.index', compact(
            'cards',
            'total',
            'confirmation',
            'shift_id',
            'pump_operator_id',
            'operator_name',
            'shift_no'
        ));
    }

    /**
     * Edit one card - receipt number and amount only.
     *
     * Refused once the total has been confirmed, which is the point of the
     * confirmation.
     */
    public function update(Request $request, $id)
    {
        $business_id = $this->resolveBusinessId();

        try {
            $payment = PumpOperatorPayment::where('business_id', $business_id)->find($id);

            if (empty($payment)) {
                return response()->json(['success' => false, 'msg' => 'Card not found.']);
            }

            if ($this->confirmation($business_id, $payment->shift_id, $payment->pump_operator_id)) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'The card total has been confirmed and can no longer be edited.',
                ]);
            }

            $request->validate([
                'payment_amount' => 'required|numeric|min:0',
            ]);

            $payment->payment_amount = $request->input('payment_amount');

            if (Schema::hasColumn('pump_operator_payments', 'slip_no')) {
                $payment->slip_no = $request->input('receipt_no');
            }

            $payment->save();

            /*
             | Keep daily_cards in step.
             |
             | An edit that updates one side and not the other is what produced
             | the settlement showing an original and an edited amount added
             | together. Matched on the collection number, and only ever ONE row
             | is updated.
             */
            if (! empty($payment->collection_form_no)) {
                try {
                    $card = DailyCard::where('business_id', $business_id)
                        ->where('collection_no', $payment->collection_form_no)
                        ->first();

                    if ($card) {
                        $card->amount = $payment->payment_amount;

                        if ($request->filled('receipt_no')) {
                            $card->slip_no = $request->input('receipt_no');
                        }

                        $card->save();
                    }
                } catch (\Throwable $e) {
                    Log::warning('8034: card row could not be updated alongside the payment', [
                        'payment_id' => $id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            $total = (float) $this->cards($business_id, $payment->shift_id, $payment->pump_operator_id)
                ->sum('payment_amount');

            return response()->json([
                'success' => true,
                'msg'     => 'Card updated.',
                'total'   => $total,
            ]);
        } catch (\Throwable $e) {
            Log::error('8034: card update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'msg' => 'Could not update the card.']);
        }
    }

    /**
     * Remove one card.
     */
    public function destroy($id)
    {
        $business_id = $this->resolveBusinessId();

        try {
            $payment = PumpOperatorPayment::where('business_id', $business_id)->find($id);

            if (empty($payment)) {
                return response()->json(['success' => false, 'msg' => 'Card not found.']);
            }

            if ($this->confirmation($business_id, $payment->shift_id, $payment->pump_operator_id)) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'The card total has been confirmed and can no longer be changed.',
                ]);
            }

            $shift_id         = $payment->shift_id;
            $pump_operator_id = $payment->pump_operator_id;
            $collection_no    = $payment->collection_form_no;

            $payment->delete();

            // Remove the matching card row so the two sides stay in step.
            if (! empty($collection_no)) {
                try {
                    DailyCard::where('business_id', $business_id)
                        ->where('collection_no', $collection_no)
                        ->delete();
                } catch (\Throwable $e) {
                    Log::warning('8034: card row could not be removed alongside the payment', [
                        'payment_id' => $id,
                        'error'      => $e->getMessage(),
                    ]);
                }
            }

            $total = (float) $this->cards($business_id, $shift_id, $pump_operator_id)
                ->sum('payment_amount');

            return response()->json([
                'success' => true,
                'msg'     => 'Card removed.',
                'total'   => $total,
            ]);
        } catch (\Throwable $e) {
            Log::error('8034: card delete failed', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'msg' => 'Could not remove the card.']);
        }
    }

    /**
     * "Card Total Correct" - the operator signs off the figure.
     */
    public function confirm(Request $request)
    {
        $business_id      = $this->resolveBusinessId();
        $shift_id         = $request->input('shift_id');
        $pump_operator_id = $request->input('pump_operator_id');

        try {
            if (! $this->ensureTable()) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'Could not record the confirmation. Please try again.',
                ]);
            }

            if ($this->confirmation($business_id, $shift_id, $pump_operator_id)) {
                return response()->json(['success' => false, 'msg' => 'Already confirmed.']);
            }

            $total = (float) $this->cards($business_id, $shift_id, $pump_operator_id)
                ->sum('payment_amount');

            DB::table('petro_card_confirmations')->insert([
                'business_id'      => $business_id,
                'shift_id'         => $shift_id,
                'pump_operator_id' => $pump_operator_id,
                'confirmed_total'  => $total,
                'confirmed_by'     => Auth::id(),
                'confirmed_at'     => now(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            Log::info('8034: card total confirmed', [
                'business_id'      => $business_id,
                'shift_id'         => $shift_id,
                'pump_operator_id' => $pump_operator_id,
                'total'            => $total,
                'by'               => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'msg'     => 'Card total confirmed.',
                'total'   => $total,
            ]);
        } catch (\Throwable $e) {
            Log::error('8034: confirm failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'msg' => 'Could not confirm the total.']);
        }
    }

    /**
     * The confirmed total for a shift, for Close Shift to display.
     *
     * Returns null when nothing has been confirmed, so the caller keeps its
     * existing figure rather than showing a zero.
     */
    public static function confirmedTotalFor($business_id, $shift_id, $pump_operator_id)
    {
        try {
            if (! Schema::hasTable('petro_card_confirmations')) {
                return null;
            }

            $row = DB::table('petro_card_confirmations')
                ->where('business_id', $business_id)
                ->where('shift_id', $shift_id)
                ->where('pump_operator_id', $pump_operator_id)
                ->orderBy('id', 'desc')
                ->first();

            return $row ? (float) $row->confirmed_total : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

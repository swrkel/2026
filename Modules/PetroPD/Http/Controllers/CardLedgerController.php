<?php

namespace Modules\PetroPD\Http\Controllers;

use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

/**
 * 8035 - PD Card Ledger.
 *
 * WHAT IT IS FOR
 * --------------
 * Card takings are collected by the operator but the money arrives later, from
 * the bank. Until now there was nowhere to record which of those payments had
 * actually landed - so a card recorded weeks ago looked exactly like one
 * received yesterday.
 *
 * This ledger lists every card slip with a Payment Received status, defaulting
 * to No, and totals what is outstanding.
 *
 * THE LOCK
 * --------
 * Once a card is marked received, it cannot be changed back by an ordinary
 * user. That is the point: a received payment is a financial assertion, and
 * being able to quietly reverse it would defeat the record.
 *
 * A user holding petro_pd.card_ledger_override may still amend it - somebody
 * has to be able to correct a genuine mistake - and every such change is
 * logged with who made it and when.
 */
class CardLedgerController extends Controller
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

    private function businessId(): int
    {
        return (int) (request()->session()->get('business.id')
            ?: request()->session()->get('user.business_id')
            ?: optional(Auth::user())->business_id);
    }

    /**
     * The three columns this ledger needs on daily_cards.
     *
     * Added on demand rather than by a migration, because migrations cannot be
     * relied upon to have reached every tenant on this estate - several are
     * known to be missing columns that migrations should have added, and a
     * missing column here would break the page rather than degrade it.
     */
    private function ensureColumns(): bool
    {
        try {
            if (Schema::hasColumn('daily_cards', 'payment_received')) {
                return true;
            }

            DB::statement("
                ALTER TABLE daily_cards
                    ADD COLUMN payment_received TINYINT(1) NOT NULL DEFAULT 0,
                    ADD COLUMN payment_received_by INT UNSIGNED NULL,
                    ADD COLUMN payment_received_at TIMESTAMP NULL DEFAULT NULL
            ");

            return Schema::hasColumn('daily_cards', 'payment_received');
        } catch (\Throwable $e) {
            Log::warning('8035: could not add the card ledger columns', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function canOverride(): bool
    {
        try {
            return (bool) auth()->user()->can('petro_pd.card_ledger_override');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * The ledger rows, filtered.
     */
    private function baseQuery(Request $request)
    {
        $business_id = $this->businessId();
        $has_status  = Schema::hasColumn('daily_cards', 'payment_received');

        $select = [
            'daily_cards.id',
            'daily_cards.amount',
            'daily_cards.slip_no',
            'daily_cards.created_at',
            'daily_cards.shift_id',
            'pump_operators.name as operator_name',
        ];

        if ($has_status) {
            $select[] = 'daily_cards.payment_received';
            $select[] = 'daily_cards.payment_received_at';
        }

        $query = DB::table('daily_cards')
            ->leftJoin('pump_operators', 'pump_operators.id', '=', 'daily_cards.pump_operator_id')
            ->leftJoin('petro_shifts', 'petro_shifts.id', '=', 'daily_cards.shift_id')
            ->where('daily_cards.business_id', $business_id)
            ->select(array_merge($select, ['petro_shifts.shift_no as shift_no']));

        // Date range - defaults to the current week, as specified.
        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        if (empty($start) && empty($end)) {
            $start = now()->startOfWeek()->format('Y-m-d');
            $end   = now()->endOfWeek()->format('Y-m-d');
        }

        if (! empty($start)) {
            $query->whereDate('daily_cards.created_at', '>=', $start);
        }

        if (! empty($end)) {
            $query->whereDate('daily_cards.created_at', '<=', $end);
        }

        if ($request->filled('pump_operator_id')) {
            $query->where('daily_cards.pump_operator_id', $request->input('pump_operator_id'));
        }

        if ($request->filled('shift_id')) {
            $query->where('daily_cards.shift_id', $request->input('shift_id'));
        }

        if ($request->filled('slip_no')) {
            $query->where('daily_cards.slip_no', 'like', '%' . trim($request->input('slip_no')) . '%');
        }

        /*
         | The received filter is applied only where the column exists.
         |
         | On a tenant that has not yet been given the column every card is
         | necessarily "not received", so filtering on it would be misleading
         | rather than merely empty.
         */
        if ($has_status && $request->filled('payment_received')) {
            $query->where('daily_cards.payment_received', (int) $request->input('payment_received'));
        }

        return $query;
    }

    /**
     * The tab contents - DataTables rows, or the totals for the summary cards.
     */
    public function index(Request $request)
    {
        $this->ensureColumns();

        if ($request->ajax() && $request->input('totals')) {
            return $this->totals($request);
        }

        if (! $request->ajax()) {
            return response()->json(['success' => false, 'msg' => 'Not available.']);
        }

        $has_status   = Schema::hasColumn('daily_cards', 'payment_received');
        $can_override = $this->canOverride();

        return DataTables::of($this->baseQuery($request))
            ->addColumn('action', function ($row) use ($has_status, $can_override) {
                $received = $has_status ? (int) ($row->payment_received ?? 0) : 0;

                /*
                 | A received card is locked to everyone without the override.
                 |
                 | The button is shown but disabled, with the reason in its
                 | tooltip, rather than hidden - a user who cannot change it
                 | should still be able to see that the control exists and why
                 | it is unavailable.
                 */
                if ($received === 1 && ! $can_override) {
                    return '<button type="button" class="btn btn-xs btn-default" disabled'
                        . ' title="Payment already marked received. Changing it needs authorisation.">'
                        . '<i class="fa fa-lock"></i></button>';
                }

                return '<button type="button" class="btn btn-xs btn-primary pd-card-ledger-edit"'
                    . ' data-id="' . (int) $row->id . '"'
                    . ' data-received="' . $received . '">'
                    . '<i class="fa fa-edit"></i></button>';
            })
            ->editColumn('date', fn ($row) => $row->created_at ? date('Y-m-d', strtotime($row->created_at)) : '—')
            ->addColumn('time', fn ($row) => $row->created_at ? date('h:i A', strtotime($row->created_at)) : '—')
            ->editColumn('operator_name', fn ($row) => $row->operator_name ?: '—')
            ->editColumn('shift_no', fn ($row) => $row->shift_no ?: ($row->shift_id ?: '—'))
            ->editColumn('slip_no', fn ($row) => trim((string) ($row->slip_no ?? '')) !== '' ? $row->slip_no : '—')
            ->editColumn('amount', fn ($row) => '<span class="display_currency" data-currency_symbol="false">'
                . number_format((float) $row->amount, 2) . '</span>')
            ->addColumn('payment_received', function ($row) use ($has_status) {
                $received = $has_status ? (int) ($row->payment_received ?? 0) : 0;

                return $received === 1
                    ? '<span class="label label-success">Yes</span>'
                    : '<span class="label label-warning">No</span>';
            })
            ->rawColumns(['action', 'amount', 'payment_received'])
            ->make(true);
    }

    /**
     * The four figures on the summary cards.
     */
    private function totals(Request $request)
    {
        try {
            $has_status = Schema::hasColumn('daily_cards', 'payment_received');

            $rows = $this->baseQuery($request)->get();

            $total    = 0.0;
            $received = 0.0;

            foreach ($rows as $row) {
                $amount = (float) $row->amount;
                $total += $amount;

                if ($has_status && (int) ($row->payment_received ?? 0) === 1) {
                    $received += $amount;
                }
            }

            $start = $request->input('start_date') ?: now()->startOfWeek()->format('Y-m-d');
            $end   = $request->input('end_date') ?: now()->endOfWeek()->format('Y-m-d');

            return response()->json([
                'success'    => true,
                'date_range' => $start . ' to ' . $end,
                'total'      => $total,
                'received'   => $received,
                // Pending is derived, never stored, so the three can never disagree.
                'pending'    => $total - $received,
            ]);
        } catch (\Throwable $e) {
            Log::error('8035: card ledger totals failed', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'msg' => 'Could not calculate the totals.']);
        }
    }

    /**
     * Set the Payment Received status.
     */
    public function update(Request $request, $id)
    {
        $business_id = $this->businessId();

        try {
            if (! $this->ensureColumns()) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'The card ledger is not available on this database yet.',
                ]);
            }

            $card = DB::table('daily_cards')
                ->where('business_id', $business_id)
                ->where('id', $id)
                ->first();

            if (empty($card)) {
                return response()->json(['success' => false, 'msg' => 'Card not found.']);
            }

            $currently_received = (int) ($card->payment_received ?? 0);
            $requested          = (int) $request->input('payment_received');

            /*
             | Once received, only an authorised user may change it.
             |
             | Checked HERE and not only in the interface. A disabled button is
             | a courtesy; this is the control. Without it the status could be
             | changed by anyone able to issue the request directly.
             */
            if ($currently_received === 1 && ! $this->canOverride()) {
                return response()->json([
                    'success' => false,
                    'msg'     => 'This payment is already marked as received. Changing it requires authorisation.',
                ]);
            }

            DB::table('daily_cards')->where('id', $id)->update([
                'payment_received'    => $requested,
                'payment_received_by' => $requested === 1 ? Auth::id() : null,
                'payment_received_at' => $requested === 1 ? now() : null,
                'updated_at'          => now(),
            ]);

            Log::info('8035: card payment received status changed', [
                'business_id' => $business_id,
                'card_id'     => $id,
                'slip_no'     => $card->slip_no ?? null,
                'amount'      => $card->amount ?? null,
                'from'        => $currently_received,
                'to'          => $requested,
                'by'          => Auth::id(),
                'override'    => $currently_received === 1,
            ]);

            return response()->json([
                'success' => true,
                'msg'     => $requested === 1 ? 'Marked as received.' : 'Marked as not received.',
            ]);
        } catch (\Throwable $e) {
            Log::error('8035: card ledger update failed', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'msg' => 'Could not update the status.']);
        }
    }
}

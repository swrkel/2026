<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\Shift;
use Modules\SW\Services\CashStatusService;
use Modules\SW\Services\LogService;

/**
 * Daily Cash Status - tab 10 of SW Operators.
 *
 * Balance In Hand for ONE shift at a time, chosen from those still open. Once
 * a shift is closed here it no longer appears - its figures have been agreed.
 */
class CashStatusController extends Controller
{
    public function __construct(
        protected CashStatusService $status,
        protected LogService $logs
    ) {
    }

    protected function businessId(): int
    {
        return (int) (optional(auth()->user())->business_id ?: session('business.id') ?: session('user.business_id') ?: 0);
    }

    /** Open shifts at a location, for the selector. */
    public function shifts(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = (int) $request->input('location_id');

        if ($businessId <= 0 || $locationId <= 0 || ! $this->liveTableExists('sw_shifts')) {
            return $this->noStoreJson([]);
        }

        foreach (['id', 'business_id', 'location_id', 'sw_shift_no', 'status'] as $column) {
            if (! $this->liveHasColumn('sw_shifts', $column)) {
                return $this->noStoreJson([]);
            }
        }

        /*
         | Read from the live tenant DB with Query Builder rather than the Shift
         | model. Older tenant schemas may not yet have every optional column
         | used by SoftDeletes/labels, and stale Schema metadata after a tenant
         | switch can otherwise turn this harmless dropdown request into HTTP 500.
         */
        $hasClosedAt = $this->liveHasColumn('sw_shifts', 'closed_at');
        $hasDeletedAt = $this->liveHasColumn('sw_shifts', 'deleted_at');
        $hasShiftDate = $this->liveHasColumn('sw_shifts', 'shift_date');
        $hasShiftName = $this->liveHasColumn('sw_shifts', 'shift_name');

        $select = ['id', 'sw_shift_no', 'status'];
        $select[] = $hasClosedAt ? 'closed_at' : DB::raw('NULL as closed_at');
        $select[] = $hasShiftDate ? 'shift_date' : DB::raw('NULL as shift_date');
        $select[] = $hasShiftName ? 'shift_name' : DB::raw('NULL as shift_name');

        $query = DB::table('sw_shifts')
            ->where('business_id', $businessId)
            ->where('location_id', $locationId);

        if ($hasDeletedAt) {
            $query->whereNull('deleted_at');
        }
        if ($hasShiftDate) {
            $query->orderByDesc('shift_date');
        }

        $rows = $query
            ->orderByDesc('id')
            ->limit(500)
            ->get($select)
            ->filter(function ($row) {
                return Shift::normalizeStatusValue(
                    $row->status ?? null,
                    $row->closed_at ?? null
                ) === Shift::STATUS_OPEN;
            })
            ->values();

        $payload = $rows->map(function ($r) {
            $label = (string) ($r->sw_shift_no ?? '');
            if (! empty($r->shift_date)) {
                try {
                    $label .= '  ·  ' . \Carbon\Carbon::parse($r->shift_date)->format('d/m/Y');
                } catch (\Throwable $e) {
                    // Keep the shift usable even if one historical date is malformed.
                }
            }
            if (! empty($r->shift_name)) {
                $label .= '  ·  ' . $r->shift_name;
            }

            return ['id' => (int) $r->id, 'label' => $label];
        })->all();

        return $this->noStoreJson($payload);
    }

    /** The figures for one shift, rendered as the status panel. */
    public function show(Request $request)
    {
        $businessId = $this->businessId();
        $shiftId = (int) $request->input('sw_shift_id');

        if ($businessId <= 0 || $shiftId <= 0 || ! $this->liveTableExists('sw_shifts')) {
            return response('<div class="alert alert-warning">'
                . __('sw::lang.choose_a_shift') . '</div>');
        }

        $shiftQuery = DB::table('sw_shifts')
            ->where('business_id', $businessId)
            ->where('id', $shiftId);

        if ($this->liveHasColumn('sw_shifts', 'deleted_at')) {
            $shiftQuery->whereNull('deleted_at');
        }

        $shift = $shiftQuery->first();

        if (! $shift) {
            return response('<div class="alert alert-warning">'
                . __('sw::lang.choose_a_shift') . '</div>');
        }

        // The panel treats these optional values as display-only.
        $shift->shift_date = $shift->shift_date ?? null;
        $shift->shift_name = $shift->shift_name ?? null;
        $shift->closed_at = $shift->closed_at ?? null;

        $operators = collect();
        if ($this->liveTableExists('sw_shift_operators')
            && $this->liveTableExists('pump_operators')
            && $this->liveHasColumn('pump_operators', 'id')) {
            $shiftKey = $this->firstLiveColumn('sw_shift_operators', ['sw_shift_id', 'shift_id']);
            $operatorKey = $this->firstLiveColumn('sw_shift_operators', ['pump_operator_id', 'operator_id']);
            $nameColumn = $this->firstLiveColumn('pump_operators', ['name', 'operator_name', 'full_name']);

            if ($shiftKey && $operatorKey && $nameColumn) {
                $operators = DB::table('sw_shift_operators as so')
                    ->join('pump_operators as po', 'po.id', '=', 'so.' . $operatorKey)
                    ->where('so.' . $shiftKey, $shift->id)
                    ->pluck('po.' . $nameColumn);
            }
        }

        return view('sw::operators.partials.cash_status_panel', [
            'shift' => $shift,
            'operators' => $operators,
            'figures' => $this->status->figures($businessId, $shift->sw_shift_no, $shift->id),
        ]);
    }

    /** Check the database selected by tenant.context for THIS request. */
    protected function liveTableExists(string $table): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$table]
            ) !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT 1 FROM `' . $table . '` LIMIT 0');
                return true;
            } catch (\Throwable $ignored) {
                return false;
            }
        }
    }

    protected function liveHasColumn(string $table, string $column): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)
            || ! preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.columns ' .
                'WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
                [$table, $column]
            ) !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT `' . $column . '` FROM `' . $table . '` LIMIT 0');
                return true;
            } catch (\Throwable $ignored) {
                return false;
            }
        }
    }

    protected function firstLiveColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if ($this->liveHasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function noStoreJson(array $payload)
    {
        return response()->json($payload)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /** Professional closing statement for an SW shift. */
    public function print($id)
    {
        $businessId = $this->businessId();

        $shift = Shift::where('business_id', $businessId)->findOrFail((int) $id);
        abort_if($shift->isOpen(), 422, 'The Shift Closing Statement is available after the SW shift is closed.');

        $locationName = Schema::hasTable('business_locations')
            ? (string) (DB::table('business_locations')->where('id', $shift->location_id)->value('name') ?? '')
            : '';

        $businessName = Schema::hasTable('business')
            ? (string) (DB::table('business')->where('id', $businessId)->value('name') ?? '')
            : '';

        $operators = collect();
        if (Schema::hasTable('sw_shift_operators')
            && Schema::hasTable('pump_operators')
            && Schema::hasColumn('sw_shift_operators', 'sw_shift_id')
            && Schema::hasColumn('sw_shift_operators', 'pump_operator_id')
            && Schema::hasColumn('pump_operators', 'name')) {
            $operators = DB::table('sw_shift_operators as so')
                ->join('pump_operators as po', 'po.id', '=', 'so.pump_operator_id')
                ->where('so.sw_shift_id', $shift->id)
                ->pluck('po.name');
        }

        $closedBy = '';
        if (! empty($shift->closed_by) && Schema::hasTable('users')) {
            $user = DB::table('users')->where('id', $shift->closed_by)->first();
            if ($user) {
                $closedBy = trim(implode(' ', array_filter([
                    $user->surname ?? null,
                    $user->first_name ?? null,
                    $user->last_name ?? null,
                ])));
                if ($closedBy === '') {
                    $closedBy = (string) ($user->username ?? '');
                }
            }
        }

        return view('sw::operators.print.cash_status', [
            'shift' => $shift,
            'business_name' => $businessName,
            'location_name' => $locationName,
            'operators' => $operators,
            'closed_by_name' => $closedBy,
            'figures' => $this->status->figures($businessId, $shift->sw_shift_no, $shift->id),
            'auto_print' => request()->boolean('autoprint'),
        ]);
    }

    /**
     * Close the shift from here.
     *
     * The same check as the Daily Shift tab: a shift with nobody assigned has
     * nothing to reconcile. Deliberately the same rule in both places rather
     * than a laxer one here.
     */
    public function close(Request $request)
    {
        $businessId = $this->businessId();
        $shift = Shift::where('business_id', $businessId)
            ->findOrFail((int) $request->input('sw_shift_id'));

        abort_unless($shift->isOpen(), 422, __('sw::lang.shift_not_open'));

        $assigned = DB::table('sw_shift_operators')->where('sw_shift_id', $shift->id)->count();
        abort_if($assigned === 0, 422, __('sw::lang.no_operators_to_close'));

        /*
         | The balance is recorded in the log at the moment of closing.
         |
         | Not stored on the shift - it is computed from the entries, and a
         | figure written down once disagrees with its own workings as soon as
         | anything is corrected. But WHAT IT WAS when someone agreed it is
         | worth keeping, and the log is where that belongs.
        */
        $figures = $this->status->figures($businessId, $shift->sw_shift_no, $shift->id);

        DB::transaction(function () use ($shift, $figures) {
            DB::table('sw_shifts')->where('id', $shift->id)->update([
                'status' => Shift::storageStatusValue('closed'),
                'closed_at' => now(),
                'closed_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            $this->logs->record(
                'shift', (int) $shift->id, $shift->sw_shift_no, 'closed',
                ['status' => 'Open'],
                [
                    'status' => 'Closed',
                    'balance_in_hand' => number_format($figures['balance'], 2),
                    'cash_collection' => number_format($figures['collection']['total'], 2),
                    'customer_payments' => number_format($figures['customer_payments']['total'], 2),
                    'cash_expenses' => number_format($figures['expenses']['total'], 2),
                    'cash_purchases' => number_format($figures['purchases']['total'], 2),
                    'cash_deposits' => number_format($figures['deposits']['total'], 2),
                ],
                [
                    'business_id' => (int) $shift->business_id,
                    'location_id' => (int) $shift->location_id,
                    'document_status' => 'Closed',
                ]
            );
        });

        // Once the shift is closed, open the dedicated professional closing
        // statement. The report is built from the same CashStatusService figures
        // that were agreed at closing, so the printed arithmetic cannot drift
        // from the Daily Cash Status screen.
        return redirect()->route('sw.cash-status.print', [
            'id' => $shift->id,
            'autoprint' => 1,
        ])->with('status', ['success' => 1, 'msg' => __('sw::lang.shift_closed_with_balance', [
            'number' => $shift->sw_shift_no,
            'balance' => number_format($figures['balance'], 2),
        ])]);
    }
}

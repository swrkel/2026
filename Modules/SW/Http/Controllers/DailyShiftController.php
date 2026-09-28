<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\SW\Entities\Shift;
use Modules\SW\Services\LogService;
use Modules\SW\Services\NumberService;
use Modules\SW\Services\ShiftTableService;

/**
 * Daily Shift - tab 11 of SW Operators.
 *
 * Open a shift, assign operators, close it, and correct it afterwards if need
 * be. Several shifts may be open at one location at once, so this works from a
 * list rather than assuming a single current shift.
 *
 * EVERY change to a CLOSED shift is logged. A closed shift's figures have been
 * agreed; correcting one is legitimate, changing it unnoticed is not.
 */
class DailyShiftController extends Controller
{
    public function __construct(
        protected NumberService $numbers,
        protected LogService $logs,
        protected ShiftTableService $shiftTable
    ) {
    }

    protected function businessId(): int
    {
        // Use the active tenant user first. A stale session business id is enough
        // to make a correct Status filter return an empty table on multi-tenant
        // installations where the same numeric business id exists elsewhere.
        $userBusinessId = (int) (optional(auth()->user())->business_id ?? 0);

        return $userBusinessId > 0
            ? $userBusinessId
            : (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    public function data(Request $request)
    {
        /*
         | Compatibility endpoint.
         |
         | The SW Shift Operations page no longer depends on this Ajax request
         | for its initial table: the rows are rendered with the page itself.
         | Keep the endpoint working for older bookmarks/views and any cached
         | front-end assets, but build it from the same live-tenant service.
        */
        try {
            $rows = $this->shiftTable->rows(
                $this->businessId(),
                $request->filled('location_id') ? (int) $request->input('location_id') : null,
                $request->input('status')
            );

            $data = $rows->map(function ($row) {
                try {
                    $action = view('sw::operators.partials.daily_shift_actions', ['row' => $row])->render();
                } catch (\Throwable $e) {
                    // A broken optional action must never make DataTables lose
                    // the whole list. The row remains visible and readable.
                    $action = '';
                }

                $names = $row->operator_names ?? collect();

                return [
                    'action' => $action,
                    'shift_no' => '<strong>' . e($row->sw_shift_no ?? '—') . '</strong>',
                    'date' => $this->formatDate($row->shift_date ?? null),
                    'location' => e($row->location_name ?? '—'),
                    'operators' => $names->isEmpty()
                        ? '<span class="text-muted">' . __('sw::lang.none_assigned') . '</span>'
                        : e($names->implode(', ')),
                    'status' => $this->statusLabel((int) ($row->effective_status ?? Shift::STATUS_OPEN)),
                    'opened_by' => e($row->opened_by ?? '—'),
                    'closed_at' => $this->formatDateTime($row->closed_at ?? null),
                ];
            })->values()->all();

            return response()->json(
                ['data' => $data],
                200,
                [
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                    'Pragma' => 'no-cache',
                    'Expires' => '0',
                ],
                defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('SW Daily Shift Ajax endpoint failed safely.', [
                'business_id' => $this->businessId(),
                'message' => $e->getMessage(),
            ]);

            // Always return the DataTables contract with HTTP 200. This prevents
            // one malformed legacy row/optional table from producing tn/7.
            return $this->noStoreJson(['data' => []]);
        }
    }

    /**
     * Apply one semantic status across current numeric and legacy text schemas.
     * All metadata checks use the database selected for THIS request.
     */
    protected function applyStatusFilter($query, string $status): void
    {
        $hasClosedAt = $this->liveHasColumn('sw_shifts', 'closed_at');
        $hasSettlementLinks = $this->liveTableExists('sw_settlement_shifts')
            && $this->liveHasColumn('sw_settlement_shifts', 'sw_shift_id');

        if ($status === 'settled') {
            $query->where(function ($q) use ($hasSettlementLinks) {
                $q->whereRaw("LOWER(TRIM(CAST(s.status AS CHAR))) IN ('2', 'settled', 'settle')");

                if ($hasSettlementLinks) {
                    $q->orWhereExists(function ($settled) {
                        $settled->select(DB::raw(1))
                            ->from('sw_settlement_shifts as ss_status')
                            ->whereColumn('ss_status.sw_shift_id', 's.id');
                    });
                }
            });

            return;
        }

        if ($status === 'open') {
            $query->whereRaw("LOWER(TRIM(CAST(s.status AS CHAR))) IN ('0', 'open', 'opened')");

            if ($hasClosedAt) {
                $query->whereNull('s.closed_at');
            }

            if ($hasSettlementLinks) {
                $query->whereNotExists(function ($settled) {
                    $settled->select(DB::raw(1))
                        ->from('sw_settlement_shifts as ss_status')
                        ->whereColumn('ss_status.sw_shift_id', 's.id');
                });
            }

            return;
        }

        // CLOSED = closed lifecycle evidence, but not already settled.
        $query->where(function ($q) use ($hasClosedAt) {
            $q->whereRaw("LOWER(TRIM(CAST(s.status AS CHAR))) IN ('1', 'closed', 'close')");

            if ($hasClosedAt) {
                $q->orWhereNotNull('s.closed_at');
            }
        })->whereRaw("LOWER(TRIM(CAST(s.status AS CHAR))) NOT IN ('2', 'settled', 'settle')");

        if ($hasSettlementLinks) {
            $query->whereNotExists(function ($settled) {
                $settled->select(DB::raw(1))
                    ->from('sw_settlement_shifts as ss_status')
                    ->whereColumn('ss_status.sw_shift_id', 's.id');
            });
        }
    }

    /** Check the live tenant database selected for this request. */
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

    /** Check a column on the live tenant database, not cached Schema metadata. */
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

    protected function formatDate($value): string
    {
        if (empty($value)) {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return e((string) $value);
        }
    }

    protected function formatDateTime($value): string
    {
        if (empty($value)) {
            return '—';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable $e) {
            return e((string) $value);
        }
    }

    protected function noStoreJson(array $payload)
    {
        return response()->json($payload)
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    protected function statusLabel(int $status): string
    {
        $map = [
            Shift::STATUS_OPEN => ['success', __('sw::lang.open')],
            Shift::STATUS_CLOSED => ['warning', __('sw::lang.closed_status')],
            Shift::STATUS_SETTLED => ['primary', __('sw::lang.settled_status')],
            Shift::STATUS_VOID => ['default', __('sw::lang.void')],
        ];

        [$class, $text] = $map[$status] ?? ['default', '—'];

        return '<span class="label label-' . $class . '">' . e($text) . '</span>';
    }

    /**
     * The operator assignment screen - pending on the left, assigned on the
     * right, as your operators already know it.
     */
    public function assign($id)
    {
        $businessId = $this->businessId();
        $shift = Shift::where('business_id', $businessId)->findOrFail((int) $id);

        $assignedIds = DB::table('sw_shift_operators')
            ->where('sw_shift_id', $shift->id)
            ->pluck('pump_operator_id');

        $all = DB::table('pump_operators')
            ->where('business_id', $businessId)
            ->where('location_id', $shift->location_id)
            ->where('active', 1)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('sw::operators.partials.shift_assign_form', [
            'shift' => $shift,
            'assigned' => $all->only($assignedIds),
            'pending' => $all->except($assignedIds),
        ]);
    }

    public function saveAssignment(Request $request, $id)
    {
        $businessId = $this->businessId();
        $shift = Shift::where('business_id', $businessId)->findOrFail((int) $id);

        $data = $request->validate([
            'operator_ids' => 'nullable|array',
            'operator_ids.*' => 'integer',
            'reason' => 'nullable|string|max:500',
        ]);

        $wanted = collect($data['operator_ids'] ?? [])->map(fn ($v) => (int) $v)->unique();

        $before = DB::table('sw_shift_operators')
            ->where('sw_shift_id', $shift->id)
            ->pluck('pump_operator_id')
            ->map(fn ($v) => (int) $v)
            ->sort()
            ->values();

        /*
         | An operator with entries on this shift cannot simply be removed.
         |
         | Their cash, cards and cheques are recorded against it. Unassigning
         | them would leave those entries belonging to nobody, and the shift
         | would no longer add up.
        */
        $removing = $before->diff($wanted);

        if ($removing->isNotEmpty()) {
            $withEntries = $this->operatorsWithEntries($shift->id, $removing->all());

            if (! empty($withEntries)) {
                return back()->with('status', [
                    'success' => 0,
                    'msg' => __('sw::lang.operator_has_entries', [
                        'names' => implode(', ', $withEntries),
                    ]),
                ]);
            }
        }

        DB::transaction(function () use ($shift, $wanted, $before, $data) {
            DB::table('sw_shift_operators')->where('sw_shift_id', $shift->id)->delete();

            foreach ($wanted as $operatorId) {
                DB::table('sw_shift_operators')->insert([
                    'sw_shift_id' => $shift->id,
                    'pump_operator_id' => $operatorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->logs->record(
                'shift', (int) $shift->id, $shift->sw_shift_no, 'updated',
                ['operators' => $before->implode(', ')],
                ['operators' => $wanted->sort()->values()->implode(', ')],
                [
                    'business_id' => (int) $shift->business_id,
                    'location_id' => (int) $shift->location_id,
                    'record_type' => 'shift_operator',
                    'document_status' => $shift->statusLabel(),
                    'reason' => $data['reason'] ?? null,
                ]
            );
        });

        return redirect()->route('sw.shift-operations.index')
            ->with('status', ['success' => 1, 'msg' => __('sw::lang.operators_assigned')])
            ->with('status.tab', 'sw_daily_shift');
    }

    /** Which of these operators have entries on this shift. */
    protected function operatorsWithEntries(int $shiftId, array $operatorIds): array
    {
        $tables = [
            'sw_daily_cash', 'sw_daily_credit_sales',
            'sw_daily_cards', 'sw_daily_cheques', 'sw_daily_shortage_excess',
        ];

        $found = [];

        foreach ($tables as $table) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            $ids = DB::table($table)
                ->where('sw_shift_id', $shiftId)
                ->whereIn('pump_operator_id', $operatorIds)
                ->distinct()
                ->pluck('pump_operator_id');

            $found = array_merge($found, $ids->all());
        }

        if (empty($found)) {
            return [];
        }

        return DB::table('pump_operators')
            ->whereIn('id', array_unique($found))
            ->pluck('name')
            ->all();
    }

    public function close(Request $request, $id)
    {
        $businessId = $this->businessId();
        $shift = Shift::where('business_id', $businessId)->findOrFail((int) $id);

        abort_unless($shift->isOpen(), 422, __('sw::lang.shift_not_open'));

        /*
         | A shift with nobody assigned has nothing to reconcile.
         |
         | Closing it would create a settlement candidate that can never balance
         | against anything.
        */
        $assigned = DB::table('sw_shift_operators')->where('sw_shift_id', $shift->id)->count();
        abort_if($assigned === 0, 422, __('sw::lang.no_operators_to_close'));

        DB::transaction(function () use ($shift) {
            DB::table('sw_shifts')->where('id', $shift->id)->update([
                'status' => Shift::storageStatusValue('closed'),
                'closed_at' => now(),
                'closed_by' => auth()->id(),
                'updated_at' => now(),
            ]);

            $this->logs->record(
                'shift', (int) $shift->id, $shift->sw_shift_no, 'closed',
                ['status' => 'Open'], ['status' => 'Closed'],
                [
                    'business_id' => (int) $shift->business_id,
                    'location_id' => (int) $shift->location_id,
                    'document_status' => 'Closed',
                ]
            );
        });

        return redirect()->route('sw.shift-operations.index')
            ->with('status', ['success' => 1, 'msg' => __('sw::lang.shift_closed_ok')])
            ->with('status.tab', 'sw_daily_shift');
    }

    /** Reopening asks for a reason - that reason is the point of the log. */
    public function reopenForm($id)
    {
        $shift = Shift::where('business_id', $this->businessId())->findOrFail((int) $id);

        return view('sw::operators.partials.shift_reopen_form', ['shift' => $shift]);
    }

    public function reopen(Request $request, $id)
    {
        $businessId = $this->businessId();
        $shift = Shift::where('business_id', $businessId)->findOrFail((int) $id);

        $data = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ], [
            'reason.required' => __('sw::lang.reopen_reason_required'),
        ]);

        abort_unless($shift->isClosed(), 422, __('sw::lang.only_closed_can_reopen'));

        /*
         | A SETTLED shift is not reopened here.
         |
         | Its figures are inside a settlement that may already have posted to
         | the ledger. Reopening it would leave that settlement describing a
         | shift that has since changed underneath it - the settlement must be
         | dealt with first.
        */
        abort_if($shift->isSettled(), 422, __('sw::lang.settled_cannot_reopen'));

        DB::transaction(function () use ($shift, $data) {
            DB::table('sw_shifts')->where('id', $shift->id)->update([
                'status' => Shift::storageStatusValue('open'),
                'closed_at' => null,
                'closed_by' => null,
                'updated_at' => now(),
            ]);

            $this->logs->record(
                'shift', (int) $shift->id, $shift->sw_shift_no, 'reopened',
                ['status' => 'Closed'], ['status' => 'Open'],
                [
                    'business_id' => (int) $shift->business_id,
                    'location_id' => (int) $shift->location_id,
                    'document_status' => 'Closed',
                    'after_closure' => true,
                    'reason' => $data['reason'],
                ]
            );
        });

        return redirect()->route('sw.shift-operations.index')
            ->with('status', ['success' => 1, 'msg' => __('sw::lang.shift_reopened')])
            ->with('status.tab', 'sw_daily_shift');
    }
}

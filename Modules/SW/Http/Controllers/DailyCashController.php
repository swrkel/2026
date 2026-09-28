<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\SW\Entities\Shift;
use Modules\SW\Services\CollectionFormNumberService;

/**
 * Daily Cash - tab 3 of SW Operators.
 *
 * Cash is a source record while a shift is OPEN. It may be added, corrected or
 * removed only during that OPEN period. Once the shift is CLOSED the rows are
 * immutable and SW Settlement only consumes them.
 */
class DailyCashController extends Controller
{
    protected array $liveTableCache = [];
    protected array $liveColumnCache = [];

    public function __construct(protected CollectionFormNumberService $numbers)
    {
    }

    protected function businessId(): int
    {
        // Multi-business rule: the business selected for the current session is
        // authoritative. The user's home business must not override it.
        foreach ([
            session('user.business_id'),
            session('business.id'),
            session('business_id'),
            optional(auth()->user())->business_id,
        ] as $candidate) {
            $id = (int) $candidate;
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    public function data(Request $request)
    {
        $businessId = $this->businessId();

        if ($businessId <= 0
            || ! $this->liveTableExists('sw_daily_cash')
            || ! $this->liveTableExists('sw_shifts')
            || ! $this->liveHasColumn('sw_daily_cash', 'sw_shift_id')
            || ! $this->liveHasColumn('sw_daily_cash', 'pump_operator_id')
            || ! $this->liveHasColumn('sw_shifts', 'id')
            || ! $this->liveHasColumn('sw_shifts', 'business_id')) {
            return response()->json(['data' => []]);
        }

        $query = DB::table('sw_daily_cash as dc')
            ->join('sw_shifts as s', 's.id', '=', 'dc.sw_shift_id')
            ->where('s.business_id', $businessId)
            ->when(
                $request->filled('location_id') && $this->liveHasColumn('sw_shifts', 'location_id'),
                fn ($q) => $q->where('s.location_id', (int) $request->input('location_id'))
            );

        if ($this->liveHasColumn('sw_shifts', 'deleted_at')) {
            $query->whereNull('s.deleted_at');
        }

        $hasOperators = $this->liveTableExists('pump_operators')
            && $this->liveHasColumn('pump_operators', 'id')
            && $this->liveHasColumn('pump_operators', 'name');
        $hasLocations = $this->liveTableExists('business_locations')
            && $this->liveHasColumn('business_locations', 'id')
            && $this->liveHasColumn('business_locations', 'name')
            && $this->liveHasColumn('sw_shifts', 'location_id');
        $hasUsers = $this->liveTableExists('users')
            && $this->liveHasColumn('users', 'id')
            && $this->liveHasColumn('sw_daily_cash', 'created_by');

        if ($hasOperators) {
            $query->leftJoin('pump_operators as po', 'po.id', '=', 'dc.pump_operator_id');
        }
        if ($hasLocations) {
            $query->leftJoin('business_locations as bl', 'bl.id', '=', 's.location_id');
        }
        if ($hasUsers) {
            $query->leftJoin('users as u', 'u.id', '=', 'dc.created_by');
        }

        $select = ['dc.*'];
        $select[] = $this->liveHasColumn('sw_shifts', 'sw_shift_no')
            ? 's.sw_shift_no'
            : DB::raw('NULL as sw_shift_no');
        $select[] = $this->liveHasColumn('sw_shifts', 'status')
            ? 's.status as shift_status'
            : DB::raw('NULL as shift_status');
        $select[] = $this->liveHasColumn('sw_shifts', 'closed_at')
            ? 's.closed_at as shift_closed_at'
            : DB::raw('NULL as shift_closed_at');
        $select[] = $hasOperators ? 'po.name as operator_name' : DB::raw('NULL as operator_name');
        $select[] = $hasLocations ? 'bl.name as location_name' : DB::raw('NULL as location_name');

        if ($hasUsers) {
            if ($this->liveHasColumn('users', 'first_name') && $this->liveHasColumn('users', 'last_name')) {
                $select[] = DB::raw("TRIM(CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,''))) as created_by_name");
            } else {
                $select[] = DB::raw('NULL as created_by_name');
            }
            $select[] = $this->liveHasColumn('users', 'username')
                ? 'u.username as created_by_username'
                : DB::raw('NULL as created_by_username');
        } else {
            $select[] = DB::raw('NULL as created_by_name');
            $select[] = DB::raw('NULL as created_by_username');
        }

        try {
            $rows = $query->orderByDesc('dc.id')->limit(5000)->get($select);
        } catch (\Throwable $e) {
            \Log::error('SW Daily Cash data load failed', [
                'business_id' => $businessId,
                'user_id' => optional(auth()->user())->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['data' => []]);
        }

        $running = [];
        $data = $rows->sortBy('id')->map(function ($r) use (&$running) {
            $current = (float) (($r->current_amount ?? null) !== null
                ? $r->current_amount
                : ($r->amount ?? 0));
            $key = (string) ($r->sw_shift_id ?? 0) . ':' . (string) ($r->pump_operator_id ?? 0);
            $running[$key] = ($running[$key] ?? 0) + $current;
            $balance = ($r->balance_collection ?? null) !== null
                ? (float) $r->balance_collection
                : $running[$key];
            $when = $r->collection_date ?? $r->created_at ?? null;

            $r->shift_is_open = Shift::normalizeStatusValue(
                $r->shift_status ?? null,
                $r->shift_closed_at ?? null
            ) === Shift::STATUS_OPEN;

            $date = '—';
            if ($when) {
                try {
                    $date = \Carbon\Carbon::parse($when)->format('d/m/Y');
                } catch (\Throwable $e) {
                    $date = (string) $when;
                }
            }

            try {
                $action = view('sw::operators.partials.daily_cash_actions', ['row' => $r])->render();
            } catch (\Throwable $e) {
                $action = '';
            }

            return [
                'action' => $action,
                'date' => $date,
                'collection_form_no' => e($r->collection_form_no ?? '—'),
                'shift_no' => e($r->sw_shift_no ?? '—'),
                'location_name' => e($r->location_name ?? '—'),
                'operator' => e($r->operator_name ?? '—'),
                'current_amount' => number_format($current, 2),
                'balance_collection' => number_format($balance, 2),
                'note' => empty($r->note)
                    ? ''
                    : '<button type="button" class="btn btn-xs btn-default sw-note-btn" title="'.e($r->note).'" data-note="'.e($r->note).'" data-settlement="'.e($r->collection_form_no ?? '').'">'.__('sw::lang.note').'</button>',
                'created_by' => e($r->created_by_name ?: ($r->created_by_username ?? '—')),
            ];
        })->reverse()->values();

        return response()->json(['data' => $data]);
    }

    public function create(Request $request)
    {
        return view('sw::operators.partials.daily_cash_form', [
            'row' => null,
            'operators' => $this->operators($this->businessId()),
        ]);
    }

    public function edit($id)
    {
        $businessId = $this->businessId();
        $row = $this->dailyCashRowForBusiness($businessId, (int) $id);
        abort_if(! $row, 404);

        $shift = $this->shiftRow($businessId, (int) $row->sw_shift_id);
        if (! $shift || ! $this->isShiftOpen($shift)) {
            return $this->refuse(__('sw::lang.shift_closed_no_edit'));
        }

        if (($row->current_amount ?? null) === null) {
            $row->current_amount = (float) ($row->amount ?? 0);
        }

        return view('sw::operators.partials.daily_cash_form', [
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
            $row = [
                'sw_shift_id' => (int) $shift->id,
                'pump_operator_id' => (int) $data['pump_operator_id'],
                'collection_form_no' => $this->numbers->next($businessId, (int) ($shift->location_id ?? 0)),
                'current_amount' => $data['current_amount'],
                'amount' => $data['current_amount'],
                'denom_qty' => ! empty($data['denom_qty']) ? json_encode($data['denom_qty']) : null,
                'collection_date' => $data['collection_date'],
                'note' => $data['note'] ?? null,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
                'business_id' => $businessId,
            ];

            DB::table('sw_daily_cash')->insert($this->onlyExistingColumns('sw_daily_cash', $row));
            $this->refreshBalances((int) $shift->id, (int) $data['pump_operator_id']);
        });

        return redirect()->route('sw.payments.index')
            ->with('status', ['success' => 1, 'msg' => __('sw::lang.daily_cash_added')])
            ->with('status.tab', 'sw_daily_cash');
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);
        $businessId = $this->businessId();
        $existing = $this->dailyCashRowForBusiness($businessId, (int) $id);
        abort_if(! $existing, 404);

        $shift = $this->openShiftOrFail($businessId, (int) $existing->sw_shift_id);

        DB::transaction(function () use ($data, $id, $existing, $shift) {
            $update = [
                'pump_operator_id' => (int) $data['pump_operator_id'],
                'current_amount' => $data['current_amount'],
                'amount' => $data['current_amount'],
                'denom_qty' => ! empty($data['denom_qty']) ? json_encode($data['denom_qty']) : null,
                'collection_date' => $data['collection_date'],
                'note' => $data['note'] ?? null,
                'updated_by' => auth()->id(),
                'updated_at' => now(),
            ];

            DB::table('sw_daily_cash')->where('id', (int) $id)
                ->update($this->onlyExistingColumns('sw_daily_cash', $update));

            $this->refreshBalances((int) $shift->id, (int) $existing->pump_operator_id);
            $this->refreshBalances((int) $shift->id, (int) $data['pump_operator_id']);
        });

        return redirect()->route('sw.payments.index')
            ->with('status', ['success' => 1, 'msg' => __('sw::lang.daily_cash_updated')])
            ->with('status.tab', 'sw_daily_cash');
    }

    public function destroy($id)
    {
        $businessId = $this->businessId();
        $existing = $this->dailyCashRowForBusiness($businessId, (int) $id);
        abort_if(! $existing, 404);

        $shift = $this->openShiftOrFail($businessId, (int) $existing->sw_shift_id);

        DB::transaction(function () use ($id, $existing, $shift) {
            DB::table('sw_daily_cash')->where('id', (int) $id)->delete();
            $this->refreshBalances((int) $shift->id, (int) $existing->pump_operator_id);
        });

        return redirect()->route('sw.payments.index')
            ->with('status', ['success' => 1, 'msg' => __('sw::lang.daily_cash_deleted')])
            ->with('status.tab', 'sw_daily_cash');
    }

    protected function refreshBalances(int $shiftId, int $operatorId): void
    {
        $amountColumn = $this->liveHasColumn('sw_daily_cash', 'current_amount')
            ? 'current_amount'
            : ($this->liveHasColumn('sw_daily_cash', 'amount') ? 'amount' : null);

        if (! $amountColumn) {
            return;
        }

        $rows = DB::table('sw_daily_cash')
            ->where('sw_shift_id', $shiftId)
            ->where('pump_operator_id', $operatorId)
            ->orderBy('id')
            ->get(['id', $amountColumn]);

        $running = 0.0;
        foreach ($rows as $row) {
            $running += (float) ($row->{$amountColumn} ?? 0);
            if ($this->liveHasColumn('sw_daily_cash', 'balance_collection')) {
                DB::table('sw_daily_cash')->where('id', $row->id)
                    ->update(['balance_collection' => round($running, 4)]);
            }
        }
    }

    protected function openShiftOrFail(int $businessId, int $shiftId): object
    {
        $shift = $this->shiftRow($businessId, $shiftId);
        abort_if(! $shift, 404);
        abort_unless($this->isShiftOpen($shift), 422, __('sw::lang.shift_closed_no_entry'));

        return $shift;
    }

    protected function isShiftOpen(object $shift): bool
    {
        return Shift::normalizeStatusValue(
            $shift->status ?? null,
            $shift->closed_at ?? null
        ) === Shift::STATUS_OPEN;
    }

    protected function shiftRow(int $businessId, int $shiftId): ?object
    {
        if ($businessId <= 0 || $shiftId <= 0 || ! $this->liveTableExists('sw_shifts')) {
            return null;
        }

        $query = DB::table('sw_shifts')
            ->where('business_id', $businessId)
            ->where('id', $shiftId);

        if ($this->liveHasColumn('sw_shifts', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        return $query->first();
    }

    protected function dailyCashRowForBusiness(int $businessId, int $id): ?object
    {
        if ($businessId <= 0 || $id <= 0
            || ! $this->liveTableExists('sw_daily_cash')
            || ! $this->liveTableExists('sw_shifts')) {
            return null;
        }

        $query = DB::table('sw_daily_cash as dc')
            ->join('sw_shifts as s', 's.id', '=', 'dc.sw_shift_id')
            ->where('s.business_id', $businessId)
            ->where('dc.id', $id);

        if ($this->liveHasColumn('sw_shifts', 'deleted_at')) {
            $query->whereNull('s.deleted_at');
        }

        $select = ['dc.*'];
        $select[] = $this->liveHasColumn('sw_shifts', 'sw_shift_no')
            ? 's.sw_shift_no'
            : DB::raw('NULL as sw_shift_no');
        $select[] = $this->liveHasColumn('sw_shifts', 'status')
            ? 's.status as shift_status'
            : DB::raw('NULL as shift_status');
        $select[] = $this->liveHasColumn('sw_shifts', 'closed_at')
            ? 's.closed_at as shift_closed_at'
            : DB::raw('NULL as shift_closed_at');

        return $query->first($select);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'sw_shift_id' => 'required|integer',
            'pump_operator_id' => 'required|integer',
            'current_amount' => 'required|numeric|min:0',
            'collection_date' => 'required|date',
            'note' => 'nullable|string',
            'denom_qty' => 'nullable|array',
        ]);
    }

    protected function operators(int $businessId)
    {
        if ($businessId <= 0
            || ! $this->liveTableExists('pump_operators')
            || ! $this->liveTableExists('sw_shift_operators')
            || ! $this->liveTableExists('sw_shifts')
            || ! $this->liveHasColumn('pump_operators', 'id')
            || ! $this->liveHasColumn('pump_operators', 'name')
            || ! $this->liveHasColumn('pump_operators', 'business_id')) {
            return collect();
        }

        $shiftKey = $this->firstLiveColumn('sw_shift_operators', ['sw_shift_id', 'shift_id']);
        $operatorKey = $this->firstLiveColumn('sw_shift_operators', ['pump_operator_id', 'operator_id']);
        if (! $shiftKey || ! $operatorKey) {
            return collect();
        }

        $hasClosedAt = $this->liveHasColumn('sw_shifts', 'closed_at');
        $hasDeletedAt = $this->liveHasColumn('sw_shifts', 'deleted_at');

        $query = DB::table('pump_operators')
            ->where('pump_operators.business_id', $businessId)
            ->whereExists(function ($q) use ($businessId, $shiftKey, $operatorKey, $hasClosedAt, $hasDeletedAt) {
                $q->select(DB::raw(1))
                    ->from('sw_shift_operators as so')
                    ->join('sw_shifts as s', 's.id', '=', 'so.' . $shiftKey)
                    ->whereColumn('so.' . $operatorKey, 'pump_operators.id')
                    ->where('s.business_id', $businessId)
                    ->whereRaw("LOWER(TRIM(CAST(s.status AS CHAR))) IN ('0', 'open', 'opened')");

                if ($hasClosedAt) {
                    $q->whereNull('s.closed_at');
                }
                if ($hasDeletedAt) {
                    $q->whereNull('s.deleted_at');
                }
            });

        if ($this->liveHasColumn('pump_operators', 'active')) {
            $query->where('pump_operators.active', 1);
        }

        return $query->orderBy('pump_operators.name')
            ->pluck('pump_operators.name', 'pump_operators.id');
    }

    public function previousAmount(Request $request)
    {
        $businessId = $this->businessId();
        $operatorId = (int) $request->input('pump_operator_id');
        $excludeId = (int) $request->input('exclude_id');

        if ($businessId <= 0 || $operatorId <= 0
            || ! $this->liveTableExists('sw_daily_cash')
            || ! $this->liveTableExists('sw_shifts')) {
            return response()->json(['previous_amount' => 0]);
        }

        $query = DB::table('sw_daily_cash as dc')
            ->join('sw_shifts as s', 's.id', '=', 'dc.sw_shift_id')
            ->where('s.business_id', $businessId)
            ->where('dc.pump_operator_id', $operatorId);

        if ($excludeId > 0) {
            $query->where('dc.id', '<', $excludeId);
        }

        $amountColumn = $this->liveHasColumn('sw_daily_cash', 'balance_collection')
            ? 'dc.balance_collection'
            : ($this->liveHasColumn('sw_daily_cash', 'current_amount')
                ? 'dc.current_amount'
                : ($this->liveHasColumn('sw_daily_cash', 'amount') ? 'dc.amount' : null));

        if (! $amountColumn) {
            return response()->json(['previous_amount' => 0]);
        }

        $row = $query->orderByDesc('dc.id')->first([$amountColumn . ' as previous_amount']);

        return response()->json([
            'previous_amount' => round((float) ($row->previous_amount ?? 0), 4),
        ]);
    }

    protected function liveTableExists(string $table): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }

        if (array_key_exists($table, $this->liveTableCache)) {
            return $this->liveTableCache[$table];
        }

        try {
            $present = DB::selectOne(
                'SELECT 1 AS present FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$table]
            ) !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT 1 FROM `' . $table . '` LIMIT 0');
                $present = true;
            } catch (\Throwable $ignored) {
                $present = false;
            }
        }

        return $this->liveTableCache[$table] = $present;
    }

    protected function liveHasColumn(string $table, string $column): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)
            || ! preg_match('/^[A-Za-z0-9_]+$/', $column)) {
            return false;
        }

        $key = $table . '.' . $column;
        if (array_key_exists($key, $this->liveColumnCache)) {
            return $this->liveColumnCache[$key];
        }

        try {
            $present = DB::selectOne(
                'SELECT 1 AS present FROM information_schema.columns '
                . 'WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1',
                [$table, $column]
            ) !== null;
        } catch (\Throwable $e) {
            try {
                DB::select('SELECT `' . $column . '` FROM `' . $table . '` LIMIT 0');
                $present = true;
            } catch (\Throwable $ignored) {
                $present = false;
            }
        }

        return $this->liveColumnCache[$key] = $present;
    }

    protected function firstLiveColumn(string $table, array $candidates): ?string
    {
        if (! $this->liveTableExists($table)) {
            return null;
        }

        foreach ($candidates as $column) {
            if ($this->liveHasColumn($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function onlyExistingColumns(string $table, array $values): array
    {
        return array_filter(
            $values,
            fn ($value, $column) => $this->liveHasColumn($table, (string) $column),
            ARRAY_FILTER_USE_BOTH
        );
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

<?php

namespace Modules\SW\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\SW\Entities\Shift;

/**
 * 8054 - List SW Shifts.
 *
 * One row is one SW shift. Monetary figures are read from the SW-owned daily
 * tables; settlement details are read through sw_settlement_shifts so the
 * report does not depend on legacy SettlementSW data.
 */
class ListShiftController extends Controller
{
    protected function businessId(): int
    {
        // Multi-business rule: the business selected in the current session is
        // authoritative.  auth()->user()->business_id can be the user's home
        // business and must not override the currently selected business.
        $sessionBusinessId = (int) (session('user.business_id')
            ?: session('business.id')
            ?: session('business_id')
            ?: 0);

        if ($sessionBusinessId > 0) {
            return $sessionBusinessId;
        }

        return (int) (optional(auth()->user())->business_id ?? 0);
    }

    protected function authorizeReport(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && (
                $user->can('superadmin')
                || $user->can('sw.daily_shift.view')
                || $user->can('sw.settlement.view')
            ),
            403
        );
    }

    public function index(Request $request)
    {
        $this->authorizeReport();

        $businessId = $this->businessId();
        $locations = $this->locations($businessId);
        $defaultLocation = (int) ($request->input('location_id') ?: $locations->keys()->first() ?: 0);

        /*
         | 14 Sep 2026 root fix:
         | Load the List SW Shifts dataset in the normal page request instead
         | of making the visible report depend on a second DataTables AJAX
         | request.  The page then filters instantly in the browser.  The AJAX
         | endpoint is retained below only for backward compatibility with a
         | stale/cached older view.
        */
        $listRows = $this->buildListRows($businessId, null, false);
        $jsonOptions = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $jsonOptions |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $listRowsJson = json_encode($listRows, $jsonOptions);
        if ($listRowsJson === false) {
            $listRowsJson = '[]';
        }

        return view('sw::list_shifts.index', [
            'business_locations' => $locations,
            'default_location' => $defaultLocation,
            'operators' => $this->operators($businessId),
            'shifts' => $this->shiftOptions($businessId),
            'pumps' => $this->pumpOptions($businessId),
            'payment_methods' => $this->paymentMethodOptions($businessId),
            'cheque_numbers' => $this->chequeNumbers($businessId),
            'settlement_numbers' => $this->settlementNumbers($businessId),
            'list_rows_json' => $listRowsJson,
        ]);
    }

    /**
     * Backward-compatible JSON endpoint.
     *
     * The current List SW Shifts page does not need AJAX anymore.  Keeping a
     * valid endpoint prevents cached older JavaScript from producing tn/7.
     */
    public function data(Request $request)
    {
        $this->authorizeReport();

        try {
            return $this->noStoreJson([
                'data' => $this->buildListRows($this->businessId(), $request, true),
            ]);
        } catch (\Throwable $e) {
            \Log::error('SW List Shifts compatibility feed failed', [
                'business_id' => $this->businessId(),
                'user_id' => optional(auth()->user())->id,
                'database' => $this->databaseName(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Always keep the DataTables JSON contract valid.  The current
            // page is not dependent on this endpoint.
            return $this->noStoreJson(['data' => []]);
        }
    }

    /**
     * Build report rows from the REQUIRED sw_shifts table.  Every companion
     * source is optional enrichment: if one old tenant table differs, the
     * shift row still loads with zero/blank enrichment instead of failing the
     * complete report.
     */
    protected function buildListRows(int $businessId, ?Request $request = null, bool $applyFilters = false): array
    {
        if ($businessId <= 0 || ! $this->liveTableExists('sw_shifts')) {
            return [];
        }

        if (! $this->liveHasColumn('sw_shifts', 'id')
            || ! $this->liveHasColumn('sw_shifts', 'business_id')
            || ! $this->liveHasColumn('sw_shifts', 'sw_shift_no')) {
            return [];
        }

        $hasLocation = $this->liveHasColumn('sw_shifts', 'location_id');
        $hasShiftDate = $this->liveHasColumn('sw_shifts', 'shift_date');
        $hasDeletedAt = $this->liveHasColumn('sw_shifts', 'deleted_at');

        try {
            $query = DB::table('sw_shifts as s')->where('s.business_id', $businessId);

            if ($hasDeletedAt) {
                $query->whereNull('s.deleted_at');
            }

            if ($applyFilters && $request) {
                if ($hasLocation && $request->filled('location_id')) {
                    $query->where('s.location_id', (int) $request->input('location_id'));
                }

                if ($request->filled('shift_id')) {
                    $query->where('s.id', (int) $request->input('shift_id'));
                }

                if ($request->filled('operator_id')) {
                    $operatorShiftKey = $this->firstLiveColumn('sw_shift_operators', ['sw_shift_id', 'shift_id']);
                    $operatorKey = $this->firstLiveColumn('sw_shift_operators', ['pump_operator_id', 'operator_id']);
                    if ($operatorShiftKey && $operatorKey) {
                        $operatorId = (int) $request->input('operator_id');
                        $query->whereExists(function ($sub) use ($operatorId, $operatorShiftKey, $operatorKey) {
                            $sub->select(DB::raw(1))
                                ->from('sw_shift_operators as so_f')
                                ->whereColumn('so_f.' . $operatorShiftKey, 's.id')
                                ->where('so_f.' . $operatorKey, $operatorId);
                        });
                    }
                }

                if ($request->filled('settlement_no')) {
                    $settlementShiftKey = $this->firstLiveColumn('sw_settlement_shifts', ['sw_shift_id', 'shift_id']);
                    $settlementIdKey = $this->firstLiveColumn('sw_settlement_shifts', ['settlement_id']);
                    if ($settlementShiftKey && $settlementIdKey
                        && $this->tableHasColumns('sw_settlements', ['id', 'settlement_no'])) {
                        $settlementNo = (string) $request->input('settlement_no');
                        $query->whereExists(function ($sub) use ($settlementNo, $settlementShiftKey, $settlementIdKey) {
                            $sub->select(DB::raw(1))
                                ->from('sw_settlement_shifts as ss_f')
                                ->join('sw_settlements as st_f', 'st_f.id', '=', 'ss_f.' . $settlementIdKey)
                                ->whereColumn('ss_f.' . $settlementShiftKey, 's.id')
                                ->where('st_f.settlement_no', $settlementNo);
                        });
                    }
                }

                if ($request->filled('cheque_no')) {
                    $chequeShiftKey = $this->firstLiveColumn('sw_daily_cheques', ['sw_shift_id', 'shift_id']);
                    if ($chequeShiftKey && $this->liveHasColumn('sw_daily_cheques', 'cheque_no')) {
                        $chequeNo = (string) $request->input('cheque_no');
                        $query->whereExists(function ($sub) use ($chequeNo, $chequeShiftKey) {
                            $sub->select(DB::raw(1))
                                ->from('sw_daily_cheques as ch_f')
                                ->whereColumn('ch_f.' . $chequeShiftKey, 's.id')
                                ->where('ch_f.cheque_no', $chequeNo);
                        });
                    }
                }

                if ($request->filled('pump_id')) {
                    $this->applyPumpFilter($query, (int) $request->input('pump_id'));
                }

                if ($request->filled('payment_method')) {
                    $this->applyPaymentMethodFilter($query, (string) $request->input('payment_method'));
                }

                $from = null;
                $to = null;
                if ($hasShiftDate && $request->filled('start_date') && $request->filled('end_date')) {
                    try {
                        $from = Carbon::createFromFormat('Y-m-d', (string) $request->input('start_date'))->startOfDay();
                        $to = Carbon::createFromFormat('Y-m-d', (string) $request->input('end_date'))->endOfDay();
                    } catch (\Throwable $e) {
                        $from = $to = null;
                    }
                }
                if ($hasShiftDate && (! $from || ! $to)) {
                    [$from, $to] = $this->parseDateRange((string) $request->input('date_range', ''));
                }
                if ($hasShiftDate && $from && $to) {
                    $query->whereBetween('s.shift_date', [$from->toDateString(), $to->toDateString()]);
                }
            }

            $select = ['s.id', 's.sw_shift_no'];
            $select[] = $hasLocation ? 's.location_id' : DB::raw('0 as location_id');
            $select[] = $hasShiftDate ? 's.shift_date' : DB::raw('NULL as shift_date');

            if ($hasShiftDate) {
                $query->orderByDesc('s.shift_date');
            }

            $rows = $query->orderByDesc('s.id')->limit(5000)->get($select);
        } catch (\Throwable $e) {
            \Log::error('SW List Shifts base query failed', [
                'business_id' => $businessId,
                'database' => $this->databaseName(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return [];
        }

        if ($rows->isEmpty()) {
            return [];
        }

        $shiftIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->filter()->values()->all();

        try {
            $maps = $this->buildMaps($shiftIds);
        } catch (\Throwable $e) {
            \Log::warning('SW List Shifts enrichment was skipped.', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);
            $maps = [
                'cash' => [], 'cards' => [], 'credit' => [], 'cheques' => [],
                'operators' => [], 'settlements' => [], 'settlement_pumps' => [],
                'settlement_pump_ids' => [], 'cheque_nos' => [], 'collection_methods' => [],
                'assigned_pumps' => [], 'location_pumps' => [],
            ];
        }

        try {
            $locationNames = $this->locationNameMap($rows->pluck('location_id')->all());
        } catch (\Throwable $e) {
            $locationNames = collect();
        }

        $data = [];
        foreach ($rows as $row) {
            try {
                $shiftId = (int) $row->id;
                $cash = (float) ($maps['cash'][$shiftId] ?? 0);
                $cards = (float) ($maps['cards'][$shiftId] ?? 0);
                $credit = (float) ($maps['credit'][$shiftId] ?? 0);
                $cheques = (float) ($maps['cheques'][$shiftId] ?? 0);
                $total = $cash + $cards + $credit + $cheques;
                $settlement = $maps['settlements'][$shiftId] ?? null;

                $operatorGroup = collect($maps['operators'][$shiftId] ?? []);
                $operatorIds = $operatorGroup->map(function ($item) {
                    if (is_object($item)) {
                        return isset($item->id) ? (int) $item->id : 0;
                    }
                    return is_array($item) ? (int) ($item['id'] ?? 0) : 0;
                })->filter()->unique()->values();
                $operatorNames = $operatorGroup->map(function ($item) {
                    if (is_object($item)) {
                        return $item->name ?? $item->operator_name ?? null;
                    }
                    return is_array($item) ? ($item['name'] ?? $item['operator_name'] ?? null) : null;
                })->filter()->unique()->values();

                $pumpNos = $settlement ? collect($maps['settlement_pumps'][$shiftId] ?? []) : collect();
                $pumpIds = $settlement ? collect($maps['settlement_pump_ids'][$shiftId] ?? []) : collect();
                $chequeNos = collect($maps['cheque_nos'][$shiftId] ?? [])->filter()->unique()->values();
                $methods = collect($maps['collection_methods'][$shiftId] ?? [])->filter()->map(fn ($m) => strtolower((string) $m));
                if ($cash > 0) $methods->push('cash');
                if ($cards > 0) $methods->push('card');
                if ($credit > 0) $methods->push('credit_sale');
                if ($cheques > 0) $methods->push('cheque');
                $methods = $methods->unique()->values();

                try {
                    $action = view('sw::list_shifts.partials.actions', ['shiftId' => $shiftId])->render();
                } catch (\Throwable $e) {
                    $action = '';
                }

                $shiftDateRaw = '';
                if (! empty($row->shift_date)) {
                    try {
                        $shiftDateRaw = Carbon::parse($row->shift_date)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $shiftDateRaw = '';
                    }
                }

                $locationId = (int) ($row->location_id ?? 0);
                $settlementNoRaw = (string) ($settlement->settlement_no ?? '');

                $data[] = [
                    'action' => $action,
                    'location' => e($locationNames[$locationId] ?? '—'),
                    'date' => $this->formatDate($row->shift_date ?? null),
                    'operator' => e($operatorNames->implode(', ') ?: '—'),
                    'shift_no' => '<strong>' . e($row->sw_shift_no ?? '—') . '</strong>',
                    'pump_nos' => e($pumpNos->filter()->unique()->sort()->values()->implode(', ') ?: '—'),
                    'cash' => number_format($cash, 2),
                    'cards' => number_format($cards, 2),
                    'credit_sales' => number_format($credit, 2),
                    'cheques' => number_format($cheques, 2),
                    'total_amount' => number_format($total, 2),
                    'settlement_no' => e($settlementNoRaw !== '' ? $settlementNoRaw : '—'),
                    'settlement_amount' => number_format((float) ($settlement->settlement_amount ?? 0), 2),
                    'settlement_id' => (int) ($settlement->settlement_id ?? 0),
                    'settlement_amount_raw' => (float) ($settlement->settlement_amount ?? 0),
                    'shift_id' => $shiftId,
                    '_location_id' => $locationId,
                    '_shift_date' => $shiftDateRaw,
                    '_operator_ids' => $operatorIds->all(),
                    '_pump_ids' => $pumpIds->map(fn ($id) => (int) $id)->filter()->unique()->values()->all(),
                    '_payment_methods' => $methods->all(),
                    '_cheque_nos' => $chequeNos->map(fn ($v) => (string) $v)->all(),
                    '_settlement_no' => $settlementNoRaw,
                ];
            } catch (\Throwable $e) {
                \Log::warning('SW List Shifts row enrichment skipped', [
                    'shift_id' => (int) ($row->id ?? 0),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $data;
    }

    public function show($id)
    {
        $this->authorizeReport();

        return view('sw::list_shifts.show', $this->detailData((int) $id, false));
    }

    public function print($id)
    {
        $this->authorizeReport();

        return view('sw::list_shifts.print', $this->detailData((int) $id, true));
    }

    protected function detailData(int $shiftId, bool $printMode): array
    {
        $businessId = $this->businessId();

        $shift = DB::table('sw_shifts as s')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 's.location_id')
            ->where('s.business_id', $businessId)
            ->where('s.id', $shiftId)
            ->when(
                $this->liveHasColumn('sw_shifts', 'deleted_at'),
                fn ($q) => $q->whereNull('s.deleted_at')
            )
            ->first(['s.*', 'bl.name as location_name']);

        abort_if(! $shift, 404);

        $maps = $this->buildMaps([$shiftId]);
        $cashTotal = (float) ($maps['cash'][$shiftId] ?? 0);
        $cardTotal = (float) ($maps['cards'][$shiftId] ?? 0);
        $creditTotal = (float) ($maps['credit'][$shiftId] ?? 0);
        $chequeTotal = (float) ($maps['cheques'][$shiftId] ?? 0);

        $businessName = $this->liveTableExists('business')
            ? (string) (DB::table('business')->where('id', $businessId)->value('name') ?? '')
            : '';

        return [
            'business_name' => $businessName,
            'shift' => $shift,
            'status_label' => $this->shiftStatusLabel(
                isset($maps['settlements'][$shiftId])
                    ? Shift::STATUS_SETTLED
                    : Shift::normalizeStatusValue($shift->status, $shift->closed_at ?? null)
            ),
            'operators' => ($maps['operators'][$shiftId] ?? collect())->pluck('name')->filter()->unique()->values(),
            // IS2250: detail/print follows the same rule as the list — only
            // pumps saved on the completed settlement are displayed.
            'pumps' => isset($maps['settlements'][$shiftId])
                ? ($maps['settlement_pumps'][$shiftId] ?? collect())
                : collect(),
            'settlement' => $maps['settlements'][$shiftId] ?? null,
            'cash_total' => $cashTotal,
            'card_total' => $cardTotal,
            'credit_total' => $creditTotal,
            'cheque_total' => $chequeTotal,
            'total_amount' => $cashTotal + $cardTotal + $creditTotal + $chequeTotal,
            'cash_rows' => $this->paymentRows('sw_daily_cash', $shiftId, 'cash'),
            'card_rows' => $this->paymentRows('sw_daily_cards', $shiftId, 'card'),
            'credit_rows' => $this->paymentRows('sw_daily_credit_sales', $shiftId, 'credit'),
            'cheque_rows' => $this->paymentRows('sw_daily_cheques', $shiftId, 'cheque'),
            'printMode' => $printMode,
        ];
    }

    protected function buildMaps(array $shiftIds): array
    {
        $empty = [
            'cash' => [], 'cards' => [], 'credit' => [], 'cheques' => [],
            'operators' => [], 'settlements' => [],
            'settlement_pumps' => [], 'settlement_pump_ids' => [], 'cheque_nos' => [], 'collection_methods' => [],
            'assigned_pumps' => [], 'location_pumps' => [],
        ];

        if (empty($shiftIds)) {
            return $empty;
        }

        $shiftIds = collect($shiftIds)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        if (empty($shiftIds)) {
            return $empty;
        }

        $cashColumn = $this->liveTableExists('sw_daily_cash')
            ? ($this->liveHasColumn('sw_daily_cash', 'current_amount')
                ? 'current_amount'
                : ($this->liveHasColumn('sw_daily_cash', 'amount') ? 'amount' : null))
            : null;

        /* Optional operator enrichment. */
        $operators = [];
        $operatorShiftKey = $this->firstLiveColumn('sw_shift_operators', ['sw_shift_id', 'shift_id']);
        $operatorKey = $this->firstLiveColumn('sw_shift_operators', ['pump_operator_id', 'operator_id']);
        $operatorNameColumn = $this->firstLiveColumn('pump_operators', ['name', 'operator_name', 'full_name']);
        if ($operatorShiftKey && $operatorKey
            && $this->liveHasColumn('pump_operators', 'id') && $operatorNameColumn) {
            try {
                $operators = DB::table('sw_shift_operators as so')
                    ->join('pump_operators as po', 'po.id', '=', 'so.' . $operatorKey)
                    ->whereIn('so.' . $operatorShiftKey, $shiftIds)
                    ->get([
                        'so.' . $operatorShiftKey . ' as sw_shift_id',
                        'po.id',
                        'po.' . $operatorNameColumn . ' as name',
                    ])
                    ->groupBy('sw_shift_id')
                    ->all();
            } catch (\Throwable $e) {
                $operators = [];
            }
        }

        /* Optional settlement enrichment. */
        $settlements = [];
        $settlementShiftKey = $this->firstLiveColumn('sw_settlement_shifts', ['sw_shift_id', 'shift_id']);
        $settlementIdKey = $this->firstLiveColumn('sw_settlement_shifts', ['settlement_id']);
        if ($settlementShiftKey && $settlementIdKey && $this->liveHasColumn('sw_settlements', 'id')) {
            try {
                $settlementQuery = DB::table('sw_settlement_shifts as ss')
                    ->join('sw_settlements as st', 'st.id', '=', 'ss.' . $settlementIdKey)
                    ->whereIn('ss.' . $settlementShiftKey, $shiftIds);

                if ($this->liveHasColumn('sw_settlements', 'deleted_at')) {
                    $settlementQuery->whereNull('st.deleted_at');
                }

                if ($this->liveHasColumn('sw_settlements', 'transaction_date')) {
                    $settlementQuery->orderByDesc('st.transaction_date');
                }
                $settlementQuery->orderByDesc('st.id');

                $select = [
                    'ss.' . $settlementShiftKey . ' as sw_shift_id',
                    'st.id as settlement_id',
                    $this->liveHasColumn('sw_settlements', 'settlement_no')
                        ? 'st.settlement_no'
                        : DB::raw("'' as settlement_no"),
                    $this->liveHasColumn('sw_settlements', 'transaction_date')
                        ? 'st.transaction_date'
                        : DB::raw('NULL as transaction_date'),
                    $this->liveHasColumn('sw_settlements', 'total_collected')
                        ? 'st.total_collected as settlement_amount'
                        : ($this->liveHasColumn('sw_settlements', 'total_amount')
                            ? 'st.total_amount as settlement_amount'
                            : DB::raw('0 as settlement_amount')),
                    $this->liveHasColumn('sw_settlements', 'status')
                        ? 'st.status as settlement_status'
                        : DB::raw('0 as settlement_status'),
                ];

                foreach ($settlementQuery->get($select) as $r) {
                    $sid = (int) $r->sw_shift_id;
                    if (! isset($settlements[$sid])) {
                        $settlements[$sid] = $r;
                    }
                }
            } catch (\Throwable $e) {
                $settlements = [];
            }
        }

        /* Pumps are shown only when they are saved on the settlement. */
        $settlementPumps = [];
        $settlementPumpIds = [];
        $lineSettlementKey = $this->firstLiveColumn('sw_settlement_lines', ['settlement_id']);
        $linePumpKey = $this->firstLiveColumn('sw_settlement_lines', ['pump_id']);
        $pumpField = $this->firstLiveColumn('pumps', ['pump_no', 'pump_name', 'name']);
        if ($settlementShiftKey && $settlementIdKey && $lineSettlementKey && $linePumpKey
            && $this->liveHasColumn('pumps', 'id') && $pumpField) {
            try {
                $pumpRows = DB::table('sw_settlement_shifts as ss')
                    ->join('sw_settlement_lines as sl', 'sl.' . $lineSettlementKey, '=', 'ss.' . $settlementIdKey)
                    ->join('pumps as p', 'p.id', '=', 'sl.' . $linePumpKey)
                    ->whereIn('ss.' . $settlementShiftKey, $shiftIds)
                    ->get([
                        'ss.' . $settlementShiftKey . ' as sw_shift_id',
                        'p.id as pump_id',
                        DB::raw('p.`' . $pumpField . '` as pump_no'),
                    ])
                    ->groupBy('sw_shift_id');

                $settlementPumps = $pumpRows
                    ->map(fn ($group) => $group->pluck('pump_no')->filter()->unique()->sort()->values())
                    ->all();
                $settlementPumpIds = $pumpRows
                    ->map(fn ($group) => $group->pluck('pump_id')->map(fn ($id) => (int) $id)->filter()->unique()->values())
                    ->all();
            } catch (\Throwable $e) {
                $settlementPumps = [];
                $settlementPumpIds = [];
            }
        }

        /* Keep cheque-number metadata for the browser-side filter. */
        $chequeNos = [];
        $chequeShiftKey = $this->firstLiveColumn('sw_daily_cheques', ['sw_shift_id', 'shift_id']);
        if ($chequeShiftKey && $this->liveHasColumn('sw_daily_cheques', 'cheque_no')) {
            try {
                $chequeNos = DB::table('sw_daily_cheques')
                    ->whereIn($chequeShiftKey, $shiftIds)
                    ->whereNotNull('cheque_no')
                    ->where('cheque_no', '!=', '')
                    ->get([$chequeShiftKey, 'cheque_no'])
                    ->groupBy($chequeShiftKey)
                    ->map(fn ($group) => $group->pluck('cheque_no')->filter()->unique()->values())
                    ->all();
            } catch (\Throwable $e) {
                $chequeNos = [];
            }
        }

        /* Settlement collection methods are optional filter metadata only. */
        $collectionMethods = [];
        if (! empty($settlements) && $this->tableHasColumns('sw_collections', ['settlement_id', 'payment_method'])) {
            try {
                $settlementIds = collect($settlements)
                    ->map(fn ($settlement) => (int) ($settlement->settlement_id ?? 0))
                    ->filter()->unique()->values();

                if ($settlementIds->isNotEmpty()) {
                    $methodsBySettlement = DB::table('sw_collections')
                        ->whereIn('settlement_id', $settlementIds->all())
                        ->whereNotNull('payment_method')
                        ->where('payment_method', '!=', '')
                        ->get(['settlement_id', 'payment_method'])
                        ->groupBy('settlement_id')
                        ->map(fn ($group) => $group->pluck('payment_method')->filter()->unique()->values());

                    foreach ($settlements as $shiftId => $settlement) {
                        $settlementId = (int) ($settlement->settlement_id ?? 0);
                        $collectionMethods[(int) $shiftId] = $methodsBySettlement[$settlementId] ?? collect();
                    }
                }
            } catch (\Throwable $e) {
                $collectionMethods = [];
            }
        }

        return [
            'cash' => $cashColumn ? $this->sumMap('sw_daily_cash', $shiftIds, $cashColumn) : [],
            'cards' => $this->sumMap('sw_daily_cards', $shiftIds, 'amount'),
            'credit' => $this->sumMap('sw_daily_credit_sales', $shiftIds, 'amount'),
            'cheques' => $this->sumMap('sw_daily_cheques', $shiftIds, 'amount'),
            'operators' => $operators,
            'settlements' => $settlements,
            'settlement_pumps' => $settlementPumps,
            'settlement_pump_ids' => $settlementPumpIds,
            'cheque_nos' => $chequeNos,
            'collection_methods' => $collectionMethods,
            'assigned_pumps' => [],
            'location_pumps' => [],
        ];
    }

    /** Check the table on the live tenant database selected for this request. */
    protected function liveTableExists(string $table): bool
    {
        if (! preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            return false;
        }

        try {
            return DB::selectOne(
                'SELECT 1 AS present FROM information_schema.tables ' .
                'WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
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

    protected function databaseName(): ?string
    {
        try {
            return (string) (DB::selectOne('SELECT DATABASE() AS db')->db ?? null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function noStoreJson(array $payload)
    {
        return response()->json(
            $payload,
            200,
            [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ],
            defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0
        );
    }

    protected function formatDate($value): string
    {
        if (empty($value)) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return e((string) $value);
        }
    }

    protected function locationNameMap(array $ids)
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty() || ! $this->liveTableExists('business_locations')) {
            return collect();
        }

        $nameColumn = $this->firstLiveColumn('business_locations', ['name', 'location_name']);
        if (! $this->liveHasColumn('business_locations', 'id') || ! $nameColumn) {
            return collect();
        }

        try {
            return DB::table('business_locations')
                ->whereIn('id', $ids->all())
                ->pluck($nameColumn, 'id');
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function tableHasColumns(string $table, array $columns): bool
    {
        if (! $this->liveTableExists($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (! $this->liveHasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    protected function sumMap(string $table, array $shiftIds, string $column): array
    {
        $shiftKey = $this->firstLiveColumn($table, ['sw_shift_id', 'shift_id']);
        if (! $shiftKey || ! $this->liveHasColumn($table, $column)) {
            return [];
        }

        try {
            return DB::table($table)
                ->whereIn($shiftKey, $shiftIds)
                ->select($shiftKey, DB::raw('SUM(`' . $column . '`) as total'))
                ->groupBy($shiftKey)
                ->pluck('total', $shiftKey)
                ->map(fn ($v) => (float) $v)
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function applyPumpFilter($query, int $pumpId): void
    {
        if ($pumpId <= 0) {
            return;
        }

        $settlementShiftKey = $this->firstLiveColumn('sw_settlement_shifts', ['sw_shift_id', 'shift_id']);
        $settlementIdKey = $this->firstLiveColumn('sw_settlement_shifts', ['settlement_id']);
        $lineSettlementKey = $this->firstLiveColumn('sw_settlement_lines', ['settlement_id']);
        $linePumpKey = $this->firstLiveColumn('sw_settlement_lines', ['pump_id']);

        if (! $settlementShiftKey || ! $settlementIdKey || ! $lineSettlementKey || ! $linePumpKey) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereExists(function ($sub) use (
            $pumpId,
            $settlementShiftKey,
            $settlementIdKey,
            $lineSettlementKey,
            $linePumpKey
        ) {
            $sub->select(DB::raw(1))
                ->from('sw_settlement_shifts as ss_pf')
                ->join('sw_settlement_lines as sl_pf', 'sl_pf.' . $lineSettlementKey, '=', 'ss_pf.' . $settlementIdKey)
                ->whereColumn('ss_pf.' . $settlementShiftKey, 's.id')
                ->where('sl_pf.' . $linePumpKey, $pumpId);
        });
    }

    protected function applyPaymentMethodFilter($query, string $method): void
    {
        $method = strtolower(trim($method));
        $tableMap = [
            'cash' => ['sw_daily_cash', $this->liveHasColumn('sw_daily_cash', 'current_amount') ? 'current_amount' : 'amount'],
            'card' => ['sw_daily_cards', 'amount'],
            'credit_sale' => ['sw_daily_credit_sales', 'amount'],
            'cheque' => ['sw_daily_cheques', 'amount'],
        ];

        if (isset($tableMap[$method])) {
            [$table, $amountColumn] = $tableMap[$method];
            $shiftKey = $this->firstLiveColumn($table, ['sw_shift_id', 'shift_id']);
            if (! $shiftKey || ! $this->liveHasColumn($table, $amountColumn)) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->whereExists(function ($sub) use ($table, $amountColumn, $shiftKey) {
                $sub->select(DB::raw(1))
                    ->from($table . ' as pm_f')
                    ->whereColumn('pm_f.' . $shiftKey, 's.id')
                    ->where('pm_f.' . $amountColumn, '>', 0);
            });
            return;
        }

        $settlementShiftKey = $this->firstLiveColumn('sw_settlement_shifts', ['sw_shift_id', 'shift_id']);
        $settlementIdKey = $this->firstLiveColumn('sw_settlement_shifts', ['settlement_id']);
        if ($settlementShiftKey && $settlementIdKey
            && $this->tableHasColumns('sw_collections', ['settlement_id', 'payment_method', 'amount'])) {
            $query->whereExists(function ($sub) use ($method, $settlementShiftKey, $settlementIdKey) {
                $sub->select(DB::raw(1))
                    ->from('sw_settlement_shifts as ss_pm')
                    ->join('sw_collections as c_pm', 'c_pm.settlement_id', '=', 'ss_pm.' . $settlementIdKey)
                    ->whereColumn('ss_pm.' . $settlementShiftKey, 's.id')
                    ->where('c_pm.payment_method', $method)
                    ->where('c_pm.amount', '>', 0);
            });
        }
    }

    protected function parseDateRange(string $range): array
    {
        $range = trim($range);
        if ($range === '') {
            return [null, null];
        }

        $parts = preg_split('/\s+-\s+/', $range, 2);
        if (! is_array($parts) || count($parts) !== 2) {
            return [null, null];
        }

        try {
            return [Carbon::parse(trim($parts[0]))->startOfDay(), Carbon::parse(trim($parts[1]))->endOfDay()];
        } catch (\Throwable $e) {
            return [null, null];
        }
    }

    protected function paymentRows(string $table, int $shiftId, string $type)
    {
        $shiftKey = $this->firstLiveColumn($table, ['sw_shift_id', 'shift_id']);
        if (! $this->liveHasColumn($table, 'id') || ! $shiftKey) {
            return collect();
        }

        try {
            $query = DB::table($table . ' as d')
                ->where('d.' . $shiftKey, $shiftId)
                ->orderBy('d.id');

            $select = ['d.*'];
            $operatorKey = $this->firstLiveColumn($table, ['pump_operator_id', 'operator_id']);
            $operatorName = $this->firstLiveColumn('pump_operators', ['name', 'operator_name', 'full_name']);
            if ($operatorKey && $this->liveHasColumn('pump_operators', 'id') && $operatorName) {
                $query->leftJoin('pump_operators as po', 'po.id', '=', 'd.' . $operatorKey);
                $select[] = 'po.' . $operatorName . ' as operator_name';
            } else {
                $select[] = DB::raw("'' as operator_name");
            }

            if (in_array($type, ['credit', 'cheque'], true)
                && $this->liveHasColumn($table, 'contact_id')
                && $this->tableHasColumns('contacts', ['id', 'name'])) {
                $query->leftJoin('contacts as c', 'c.id', '=', 'd.contact_id');
                $select[] = 'c.name as customer_name';
            } else {
                $select[] = DB::raw("'' as customer_name");
            }

            return $query->get($select)->map(function ($row) use ($type) {
                if ($type === 'cash') {
                    $row->display_amount = (float) ($row->current_amount ?? $row->amount ?? 0);
                    $row->reference_text = (string) ($row->collection_form_no ?? '');
                } elseif ($type === 'card') {
                    $row->display_amount = (float) ($row->amount ?? 0);
                    $row->reference_text = (string) (($row->slip_no ?? null) ?: ($row->collection_form_no ?? ''));
                } elseif ($type === 'credit') {
                    $row->display_amount = (float) ($row->amount ?? 0);
                    $row->reference_text = (string) (($row->reference ?? null) ?: ($row->order_no ?? ''));
                } else {
                    $row->display_amount = (float) ($row->amount ?? 0);
                    $row->reference_text = (string) (($row->cheque_no ?? null) ?: ($row->collection_form_no ?? ''));
                }

                return $row;
            });
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function locations(int $businessId)
    {
        $nameColumn = $this->firstLiveColumn('business_locations', ['name', 'location_name']);
        if (! $nameColumn
            || ! $this->liveHasColumn('business_locations', 'id')
            || ! $this->liveHasColumn('business_locations', 'business_id')) {
            return collect();
        }

        try {
            return DB::table('business_locations')
                ->where('business_id', $businessId)
                ->when(
                    $this->liveHasColumn('business_locations', 'is_active'),
                    fn ($q) => $q->where('is_active', 1)
                )
                ->orderBy($nameColumn)
                ->pluck($nameColumn, 'id');
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function operators(int $businessId)
    {
        $nameColumn = $this->firstLiveColumn('pump_operators', ['name', 'operator_name', 'full_name']);
        if (! $nameColumn
            || ! $this->liveHasColumn('pump_operators', 'id')
            || ! $this->liveHasColumn('pump_operators', 'business_id')) {
            return collect();
        }

        try {
            return DB::table('pump_operators')
                ->where('business_id', $businessId)
                ->orderBy($nameColumn)
                ->pluck($nameColumn, 'id');
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function shiftOptions(int $businessId)
    {
        if (! $this->tableHasColumns('sw_shifts', ['id', 'business_id', 'sw_shift_no'])) {
            return collect();
        }

        try {
            $query = DB::table('sw_shifts')
                ->where('business_id', $businessId);

            if ($this->liveHasColumn('sw_shifts', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            if ($this->liveHasColumn('sw_shifts', 'shift_date')) {
                $query->orderByDesc('shift_date');
            }

            return $query->orderByDesc('id')->pluck('sw_shift_no', 'id');
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function pumpOptions(int $businessId)
    {
        $label = $this->firstLiveColumn('pumps', ['pump_no', 'pump_name', 'name']);
        if (! $label
            || ! $this->liveHasColumn('pumps', 'id')
            || ! $this->liveHasColumn('pumps', 'business_id')) {
            return collect();
        }

        try {
            return DB::table('pumps')
                ->where('business_id', $businessId)
                ->when($this->liveHasColumn('pumps', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->orderBy($label)
                ->pluck($label, 'id');
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function chequeNumbers(int $businessId)
    {
        $shiftKey = $this->firstLiveColumn('sw_daily_cheques', ['sw_shift_id', 'shift_id']);
        if (! $shiftKey || ! $this->liveHasColumn('sw_daily_cheques', 'cheque_no')
            || ! $this->tableHasColumns('sw_shifts', ['id', 'business_id'])) {
            return collect();
        }

        try {
            return DB::table('sw_daily_cheques as ch')
                ->join('sw_shifts as s', 's.id', '=', 'ch.' . $shiftKey)
                ->where('s.business_id', $businessId)
                ->whereNotNull('ch.cheque_no')
                ->where('ch.cheque_no', '!=', '')
                ->distinct()
                ->orderBy('ch.cheque_no')
                ->pluck('ch.cheque_no', 'ch.cheque_no');
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function settlementNumbers(int $businessId)
    {
        if (! $this->tableHasColumns('sw_settlements', ['id', 'business_id', 'settlement_no'])) {
            return collect();
        }

        try {
            $query = DB::table('sw_settlements')
                ->where('business_id', $businessId);

            if ($this->liveHasColumn('sw_settlements', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            if ($this->liveHasColumn('sw_settlements', 'transaction_date')) {
                $query->orderByDesc('transaction_date');
            }

            return $query->orderByDesc('id')->pluck('settlement_no', 'settlement_no');
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function paymentMethodOptions(int $businessId): array
    {
        $methods = [
            'cash' => 'Cash',
            'card' => 'Cards',
            'credit_sale' => 'Credit Sales',
            'cheque' => 'Cheques',
        ];

        if ($this->tableHasColumns('sw_collections', ['settlement_id', 'payment_method'])
            && $this->tableHasColumns('sw_settlements', ['id', 'business_id'])) {
            try {
                $extra = DB::table('sw_collections as c')
                    ->join('sw_settlements as st', 'st.id', '=', 'c.settlement_id')
                    ->where('st.business_id', $businessId)
                    ->whereNotNull('c.payment_method')
                    ->where('c.payment_method', '!=', '')
                    ->distinct()
                    ->orderBy('c.payment_method')
                    ->pluck('c.payment_method');

                foreach ($extra as $method) {
                    $key = (string) $method;
                    if (! isset($methods[$key])) {
                        $methods[$key] = ucwords(str_replace(['_', '-'], ' ', $key));
                    }
                }
            } catch (\Throwable $e) {
                // Core four methods remain available.
            }
        }

        return $methods;
    }

    protected function shiftStatusLabel(int $status): string
    {
        return [
            Shift::STATUS_OPEN => 'Open',
            Shift::STATUS_CLOSED => 'Closed',
            Shift::STATUS_SETTLED => 'Settled',
            Shift::STATUS_VOID => 'Void',
        ][$status] ?? 'Unknown';
    }
}

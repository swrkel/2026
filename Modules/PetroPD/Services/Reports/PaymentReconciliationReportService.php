<?php

namespace Modules\PetroPD\Services\Reports;

use App\Business;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\PetroPD\Services\PetroPdSettlementPaymentSnapshotService;
use Modules\PetroPD\Services\PetroPdPaymentReconciliationIssueMatcher;
use RuntimeException;
use Yajra\DataTables\Facades\DataTables;

class PaymentReconciliationReportService
{
    public function filterOptions(int $businessId): array
    {
        $locations = collect();
        if (Schema::hasTable('business_locations')) {
            $locations = DB::table('business_locations')
                ->where('business_id', $businessId)
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        $pumpOperators = collect();
        if (Schema::hasTable('pump_operators')) {
            $pumpOperators = DB::table('pump_operators')
                ->when(Schema::hasColumn('pump_operators', 'business_id'), fn ($query) => $query->where('business_id', $businessId))
                ->orderBy('name')
                ->pluck('name', 'id');
        }

        $eventTableAvailable = Schema::hasTable('petro_pd_payment_reconciliation_events');
        $operationalColumnsAvailable = $this->schemaReady();
        $settlementNumbers = collect();
        $issueTypes = collect();

        if ($eventTableAvailable) {
            $settlementNumbers = DB::table('petro_pd_payment_reconciliation_events')
                ->where('business_id', $businessId)
                ->whereNotNull('settlement_no')
                ->where('settlement_no', '<>', '')
                ->distinct()
                ->orderByDesc('settlement_no')
                ->pluck('settlement_no', 'settlement_no');

            $issueTypes = DB::table('petro_pd_payment_reconciliation_events')
                ->where('business_id', $businessId)
                ->whereNotNull('issue_type')
                ->where('issue_type', '<>', '')
                ->distinct()
                ->orderBy('issue_type')
                ->pluck('issue_type')
                ->mapWithKeys(fn ($type) => [$type => $this->issueLabel((string) $type)]);
        }

        return compact('locations', 'pumpOperators', 'settlementNumbers', 'issueTypes', 'eventTableAvailable', 'operationalColumnsAvailable');
    }

    public function dataTable(Request $request, int $businessId)
    {
        if (! $this->schemaReady()) {
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'summary' => $this->emptySummary(),
                'message' => 'Payment reconciliation control-centre migration has not been run for this tenant database.',
            ]);
        }

        $query = $this->filteredQuery($request, $businessId);
        $summary = $this->summary($request, $businessId);

        return DataTables::of($query)
            ->filter(function (Builder $query) use ($request) {
                $search = trim((string) data_get($request->input('search', []), 'value', ''));
                if ($search === '') {
                    return;
                }

                $like = '%' . $search . '%';
                $query->where(function (Builder $where) use ($like) {
                    $where->where('e.settlement_no', 'like', $like)
                        ->orWhere('e.shift_ids', 'like', $like)
                        ->orWhere('e.issue_type', 'like', $like)
                        ->orWhere('e.message', 'like', $like)
                        ->orWhere('e.pump_payment_id', 'like', $like)
                        ->orWhere('po.name', 'like', $like)
                        ->orWhere('bl.name', 'like', $like)
                        ->orWhere('resolver.username', 'like', $like)
                        ->orWhere('resolver.first_name', 'like', $like)
                        ->orWhere('resolver.last_name', 'like', $like);
                });
            }, true)
            ->editColumn('first_seen_at', fn ($row) => $this->formatDateTime($row->first_seen_at ?: $row->created_at))
            ->editColumn('last_seen_at', fn ($row) => $this->formatDateTime($row->last_seen_at ?: $row->updated_at))
            ->editColumn('last_checked_at', fn ($row) => $this->formatDateTime($row->last_checked_at))
            ->editColumn('resolved_at', fn ($row) => $this->formatDateTime($row->resolved_at))
            ->editColumn('shift_ids', fn ($row) => $this->formatShiftIds($row->shift_ids))
            ->editColumn('settlement_no', fn ($row) => e($row->settlement_no ?: '-'))
            ->editColumn('pump_payment_id', fn ($row) => $row->pump_payment_id ? (string) ((int) $row->pump_payment_id) : '-')
            ->addColumn('issue_label', fn ($row) => e($this->issueLabel((string) $row->issue_type)))
            ->addColumn('severity_badge', fn ($row) => $this->severityBadge((string) $row->severity))
            ->addColumn('status_badge', fn ($row) => $this->statusBadge($row->resolved_at))
            ->addColumn('resolved_by_name', fn ($row) => e($this->userName($row)))
            ->editColumn('message', fn ($row) => '<span class="ppr-message" title="' . e((string) $row->message) . '">' . e((string) $row->message) . '</span>')
            ->addColumn('action', function ($row) {
                $buttons = '<button type="button" class="btn btn-xs btn-info ppr-view-event" data-id="' . (int) $row->id . '"><i class="fa fa-eye"></i> View</button>';
                if (empty($row->resolved_at)) {
                    $buttons .= ' <button type="button" class="btn btn-xs btn-primary ppr-recheck-event" data-id="' . (int) $row->id . '"><i class="fa fa-refresh"></i> Recheck</button>';
                }
                return '<div class="ppr-actions">' . $buttons . '</div>';
            })
            ->with(['summary' => $summary])
            ->rawColumns(['severity_badge', 'status_badge', 'message', 'action'])
            ->make(true);
    }

    public function eventDetails(int $businessId, int $eventId): array
    {
        if (! $this->schemaReady()) {
            throw new RuntimeException('Payment reconciliation control-centre migration has not been run for this tenant database.');
        }

        $event = $this->baseQuery($businessId)
            ->where('e.id', $eventId)
            ->first();

        if (! $event) {
            throw new RuntimeException('The reconciliation event was not found for this business.');
        }

        $context = json_decode((string) ($event->context_json ?? ''), true);
        if (! is_array($context)) {
            $context = [];
        }

        return [
            'id' => (int) $event->id,
            'status' => empty($event->resolved_at) ? 'Open' : 'Resolved',
            'severity' => ucfirst((string) ($event->severity ?: 'critical')),
            'issue_type' => (string) $event->issue_type,
            'issue_label' => $this->issueLabel((string) $event->issue_type),
            'message' => (string) $event->message,
            'business_location' => (string) ($event->location_name ?: '-'),
            'pump_operator' => (string) ($event->pump_operator_name ?: '-'),
            'shift_ids' => $this->formatShiftIds($event->shift_ids, false),
            'settlement_no' => (string) ($event->settlement_no ?: '-'),
            'settlement_id' => $event->settlement_id ? (int) $event->settlement_id : null,
            'pump_payment_id' => $event->pump_payment_id ? (int) $event->pump_payment_id : null,
            'occurrence_count' => max(1, (int) ($event->occurrence_count ?? 1)),
            'first_seen_at' => $this->formatDateTime($event->first_seen_at ?: $event->created_at),
            'last_seen_at' => $this->formatDateTime($event->last_seen_at ?: $event->updated_at),
            'last_checked_at' => $this->formatDateTime($event->last_checked_at),
            'resolved_at' => $this->formatDateTime($event->resolved_at),
            'resolved_by' => $this->userName($event),
            'resolution_note' => (string) ($event->resolution_note ?: ''),
            'snapshot_fingerprint' => (string) ($event->snapshot_fingerprint ?: ''),
            'context' => $context,
        ];
    }

    public function recheck(
        int $businessId,
        int $eventId,
        int $userId,
        PetroPdSettlementPaymentSnapshotService $snapshotService
    ): array {
        if (! $this->schemaReady()) {
            throw new RuntimeException('Payment reconciliation control-centre migration has not been run for this tenant database.');
        }

        $event = DB::table('petro_pd_payment_reconciliation_events')
            ->where('business_id', $businessId)
            ->where('id', $eventId)
            ->first();

        if (! $event) {
            throw new RuntimeException('The reconciliation event was not found for this business.');
        }

        if (! empty($event->resolved_at)) {
            return ['resolved' => true, 'message' => 'This reconciliation event is already resolved.'];
        }

        $shiftIds = $this->parseShiftIds($event->shift_ids);
        $operatorId = (int) ($event->pump_operator_id ?? 0);
        if ($operatorId <= 0 || count($shiftIds) !== 1) {
            $this->touchChecked($eventId);
            return [
                'resolved' => false,
                'message' => 'The event cannot be rechecked automatically because it does not contain exactly one Pump Operator and one immutable Shift ID.',
            ];
        }

        $snapshot = $snapshotService->build(
            $businessId,
            $operatorId,
            $shiftIds,
            $event->settlement_id ? (int) $event->settlement_id : null,
            $event->settlement_no ?: null,
            true
        );

        $eventPaymentId = (int) ($event->pump_payment_id ?? 0);
        $matchingIssues = collect(app(PetroPdPaymentReconciliationIssueMatcher::class)->matchingIssues(
            $snapshot['issues'] ?? [],
            (string) $event->issue_type,
            $eventPaymentId > 0 ? $eventPaymentId : null
        ));

        $now = now();
        if ($matchingIssues->isEmpty()) {
            $note = 'Resolved by controlled recheck. The authoritative payment snapshot no longer contains this issue.';
            DB::table('petro_pd_payment_reconciliation_events')
                ->where('id', $eventId)
                ->where('business_id', $businessId)
                ->update([
                    'resolved_at' => $now,
                    'resolved_by' => $userId,
                    'resolution_note' => $note,
                    'last_checked_at' => $now,
                    'updated_at' => $now,
                ]);

            return [
                'resolved' => true,
                'message' => $note,
                'snapshot_fingerprint' => $snapshot['fingerprint'] ?? null,
                'remaining_blocking_issues' => count($snapshot['blocking_issues'] ?? []),
            ];
        }

        DB::table('petro_pd_payment_reconciliation_events')
            ->where('id', $eventId)
            ->where('business_id', $businessId)
            ->update([
                // Snapshot build already records the recurring occurrence once.
                'last_checked_at' => $now,
                'last_seen_at' => $now,
                'updated_at' => $now,
            ]);

        return [
            'resolved' => false,
            'message' => 'The issue is still present. No financial record was changed. Settlement finalization remains protected.',
            'snapshot_fingerprint' => $snapshot['fingerprint'] ?? null,
            'matching_issues' => $matchingIssues->values()->all(),
            'blocking_issues' => $snapshot['blocking_issues'] ?? [],
        ];
    }

    private function filteredQuery(Request $request, int $businessId): Builder
    {
        $query = $this->baseQuery($businessId);
        $this->applyFilters($query, $request);
        return $query;
    }

    private function baseQuery(int $businessId): Builder
    {
        $query = DB::table('petro_pd_payment_reconciliation_events as e')
            ->leftJoin('pump_operators as po', 'po.id', '=', 'e.pump_operator_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'po.location_id')
            ->leftJoin('users as resolver', 'resolver.id', '=', 'e.resolved_by')
            ->where('e.business_id', $businessId)
            ->select([
                'e.*',
                'po.name as pump_operator_name',
                'bl.name as location_name',
                'resolver.surname as resolver_surname',
                'resolver.first_name as resolver_first_name',
                'resolver.last_name as resolver_last_name',
                'resolver.username as resolver_username',
            ]);

        return $query;
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $dateColumn = match ((string) $request->input('date_basis')) {
            'first_seen' => 'e.first_seen_at',
            'resolved' => 'e.resolved_at',
            'checked' => 'e.last_checked_at',
            default => 'e.last_seen_at',
        };

        if ($request->filled('start_date')) {
            $query->whereDate($dateColumn, '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate($dateColumn, '<=', $request->input('end_date'));
        }
        if ($request->filled('location_id')) {
            $query->where('po.location_id', (int) $request->input('location_id'));
        }
        if ($request->filled('pump_operator_id')) {
            $query->where('e.pump_operator_id', (int) $request->input('pump_operator_id'));
        }
        if ($request->filled('settlement_no')) {
            $query->where('e.settlement_no', $request->input('settlement_no'));
        }
        if ($request->filled('shift_id')) {
            $shiftId = (int) $request->input('shift_id');
            if ($shiftId > 0) {
                $query->whereRaw("FIND_IN_SET(?, REPLACE(COALESCE(e.shift_ids, ''), ' ', '')) > 0", [$shiftId]);
            }
        }
        if ($request->filled('pump_payment_id')) {
            $query->where('e.pump_payment_id', (int) $request->input('pump_payment_id'));
        }
        if ($request->filled('severity')) {
            $query->where('e.severity', $request->input('severity'));
        }
        if ($request->filled('issue_type')) {
            $query->where('e.issue_type', $request->input('issue_type'));
        }
        if ($request->filled('status')) {
            if ($request->input('status') === 'open') {
                $query->whereNull('e.resolved_at');
            } elseif ($request->input('status') === 'resolved') {
                $query->whereNotNull('e.resolved_at');
            }
        }
    }

    private function summary(Request $request, int $businessId): array
    {
        $query = $this->baseQuery($businessId);
        $this->applyFilters($query, $request);

        $aggregate = DB::query()->fromSub(clone $query, 'filtered_events')
            ->selectRaw('COUNT(*) as total_events')
            ->selectRaw("SUM(CASE WHEN resolved_at IS NULL AND LOWER(COALESCE(severity, 'critical')) = 'critical' THEN 1 ELSE 0 END) as open_critical")
            ->selectRaw("SUM(CASE WHEN resolved_at IS NULL AND LOWER(COALESCE(severity, 'critical')) <> 'critical' THEN 1 ELSE 0 END) as open_warnings")
            ->selectRaw('SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) as resolved_events')
            ->selectRaw('MAX(COALESCE(last_seen_at, updated_at, created_at)) as latest_seen_at')
            ->first();

        $shiftScopes = DB::query()->fromSub(clone $query, 'filtered_shift_events')
            ->whereNotNull('shift_ids')
            ->where('shift_ids', '<>', '')
            ->pluck('shift_ids')
            ->flatMap(fn ($value) => $this->parseShiftIds($value))
            ->unique()
            ->count();

        return [
            'total_events' => (int) ($aggregate->total_events ?? 0),
            'open_critical' => (int) ($aggregate->open_critical ?? 0),
            'open_warnings' => (int) ($aggregate->open_warnings ?? 0),
            'resolved_events' => (int) ($aggregate->resolved_events ?? 0),
            'affected_shifts' => (int) $shiftScopes,
            'latest_seen_at' => $this->formatDateTime($aggregate->latest_seen_at ?? null),
        ];
    }

    private function schemaReady(): bool
    {
        if (! Schema::hasTable('petro_pd_payment_reconciliation_events')) {
            return false;
        }

        foreach (['first_seen_at', 'last_seen_at', 'last_checked_at', 'occurrence_count', 'resolved_by', 'resolution_note'] as $column) {
            if (! Schema::hasColumn('petro_pd_payment_reconciliation_events', $column)) {
                return false;
            }
        }

        return true;
    }

    private function emptySummary(): array
    {
        return [
            'total_events' => 0,
            'open_critical' => 0,
            'open_warnings' => 0,
            'resolved_events' => 0,
            'affected_shifts' => 0,
            'latest_seen_at' => '-',
        ];
    }

    private function severityBadge(string $severity): string
    {
        $severity = strtolower(trim($severity)) ?: 'critical';
        $class = $severity === 'critical' ? 'danger' : ($severity === 'warning' ? 'warning' : 'info');
        return '<span class="ppr-badge ppr-badge-' . $class . '">' . e(ucfirst($severity)) . '</span>';
    }

    private function statusBadge($resolvedAt): string
    {
        if (empty($resolvedAt)) {
            return '<span class="ppr-badge ppr-badge-danger"><i class="fa fa-lock"></i> Open / Blocking</span>';
        }
        return '<span class="ppr-badge ppr-badge-success"><i class="fa fa-check-circle"></i> Resolved</span>';
    }

    private function issueLabel(string $type): string
    {
        return ucwords(str_replace(['_', '-'], ' ', trim($type)));
    }

    private function formatShiftIds($value, bool $escape = true): string
    {
        $ids = $this->parseShiftIds($value);
        $text = empty($ids) ? '-' : implode(', ', $ids);
        return $escape ? e($text) : $text;
    }

    private function parseShiftIds($value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $decoded = json_decode((string) $value, true);
            $parts = is_array($decoded) ? $decoded : preg_split('/[\s,;|]+/', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        return collect($parts)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function userName($row): string
    {
        $name = trim(implode(' ', array_filter([
            $row->resolver_surname ?? null,
            $row->resolver_first_name ?? null,
            $row->resolver_last_name ?? null,
        ])));
        return $name !== '' ? $name : (string) ($row->resolver_username ?? '-');
    }

    private function formatDateTime($value): string
    {
        if (empty($value)) {
            return '-';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return e((string) $value);
        }
    }

    private function touchChecked(int $eventId): void
    {
        DB::table('petro_pd_payment_reconciliation_events')
            ->where('id', $eventId)
            ->update(['last_checked_at' => now(), 'updated_at' => now()]);
    }
}

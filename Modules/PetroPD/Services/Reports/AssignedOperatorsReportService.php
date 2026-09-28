<?php

namespace Modules\PetroPD\Services\Reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class AssignedOperatorsReportService
{
    public function filterOptions(int $businessId): array
    {
        return [
            'operators' => $this->operatorOptions($businessId),
            'pumps' => $this->pumpOptions($businessId),
        ];
    }

    public function dataTable(Request $request, int $businessId)
    {
        if (! $this->requiredTablesAvailable()) {
            return DataTables::of(collect())->make(true);
        }

        $query = $this->filteredQuery($request, $businessId);

        return DataTables::of($query)
            ->filter(function (Builder $query) use ($request) {
                $search = trim((string) data_get($request->input('search', []), 'value', ''));
                if ($search === '') {
                    return;
                }

                $like = '%' . $search . '%';
                $query->where(function (Builder $where) use ($like) {
                    $where->where('assigned_date', 'like', $like)
                        ->orWhere('operator_name', 'like', $like)
                        ->orWhere('pump_details', 'like', $like)
                        ->orWhere('shift_status', 'like', $like)
                        ->orWhere('settlement_no', 'like', $like)
                        ->orWhere('shift_number', 'like', $like);
                });
            }, true)
            ->addColumn('assigned_pumps', function ($row) {
                $pumps = $this->pumpDetails((string) ($row->pump_details ?? ''));
                if (empty($pumps)) {
                    return '<span class="text-muted">-</span>';
                }

                return collect($pumps)->map(function (array $pump) {
                    return '<span class="lao-pump-chip">' . e($pump['label']) . '</span>';
                })->implode(' ');
            })
            ->addColumn('pump_status', function ($row) {
                $pumps = $this->pumpDetails((string) ($row->pump_details ?? ''));
                if (empty($pumps)) {
                    return '<span class="text-muted">-</span>';
                }

                return collect($pumps)->map(function (array $pump) {
                    $status = $pump['status'] === 'closed' ? 'Closed' : 'Open';
                    $class = $pump['status'] === 'closed' ? 'closed' : 'open';

                    return '<span class="lao-pump-status lao-status-' . $class . '"><strong>' . e($pump['label']) . '</strong>: ' . e($status) . '</span>';
                })->implode(' ');
            })
            ->editColumn('operator_name', function ($row) {
                $name = trim((string) ($row->operator_name ?? ''));
                return $name !== '' ? e($name) : '-';
            })
            ->editColumn('shift_status', function ($row) {
                $closed = strtolower((string) ($row->shift_status ?? 'open')) === 'closed';
                return '<span class="lao-status-badge lao-status-' . ($closed ? 'closed' : 'open') . '">' . ($closed ? 'Closed' : 'Open') . '</span>';
            })
            ->editColumn('settlement_no', function ($row) {
                $value = trim((string) ($row->settlement_no ?? ''));
                return $value !== '' ? e($value) : '<span class="text-muted">-</span>';
            })
            ->rawColumns(['assigned_pumps', 'pump_status', 'shift_status', 'settlement_no'])
            ->make(true);
    }

    public function settlementOptions(Request $request, int $businessId): array
    {
        if (! $this->requiredTablesAvailable() || ! Schema::hasTable('settlements') || ! Schema::hasColumn('pump_operator_assignments', 'settlement_id')) {
            return [];
        }

        $query = $this->groupedQuery($request, $businessId, false);
        $rows = DB::query()
            ->fromSub($query, 'assigned_operator_groups')
            ->whereNotNull('settlement_no')
            ->where('settlement_no', '<>', '')
            ->orderByDesc('assigned_date')
            ->orderByDesc('settlement_no')
            ->get(['settlement_no']);

        $options = [];
        foreach ($rows as $row) {
            foreach (array_filter(array_map('trim', explode(',', (string) $row->settlement_no))) as $settlementNo) {
                $options[$settlementNo] = $settlementNo;
            }
        }

        return collect($options)
            ->sortKeysDesc(SORT_NATURAL)
            ->map(function ($label, $value) {
                return ['id' => (string) $value, 'text' => (string) $label];
            })
            ->values()
            ->all();
    }

    private function filteredQuery(Request $request, int $businessId): Builder
    {
        return DB::query()->fromSub(
            $this->groupedQuery($request, $businessId, true),
            'assigned_operator_groups'
        );
    }

    private function groupedQuery(Request $request, int $businessId, bool $applySettlementFilter): Builder
    {
        $dateExpression = $this->assignmentDateExpression();
        $pumpLabelExpression = $this->pumpLabelExpression();
        $pumpStatusExpression = $this->pumpStatusExpression('poa');
        $shiftStatusExpression = $this->shiftStatusExpression();
        $shiftNumberExpression = Schema::hasColumn('pump_operator_assignments', 'shift_number')
            ? "COALESCE(NULLIF(CAST(poa.shift_number AS CHAR), ''), '-')"
            : "'-'";
        $shiftIdExpression = Schema::hasColumn('pump_operator_assignments', 'shift_id')
            ? 'COALESCE(poa.shift_id, 0)'
            : '0';

        $query = DB::table('pump_operator_assignments as poa')
            ->leftJoin('pump_operators as po', 'po.id', '=', 'poa.pump_operator_id')
            ->leftJoin('pumps as p', 'p.id', '=', 'poa.pump_id');

        if (Schema::hasTable('petro_shifts') && Schema::hasColumn('pump_operator_assignments', 'shift_id')) {
            $query->leftJoin('petro_shifts as ps', 'ps.id', '=', 'poa.shift_id');
        }

        if (Schema::hasTable('settlements') && Schema::hasColumn('pump_operator_assignments', 'settlement_id')) {
            $query->leftJoin('settlements as s', function ($join) {
                $join->on('s.id', '=', 'poa.settlement_id');
                if (Schema::hasColumn('settlements', 'business_id')) {
                    $join->on('s.business_id', '=', 'poa.business_id');
                }
            });
        }

        $query->where('poa.business_id', $businessId)
            ->selectRaw($dateExpression . ' AS assigned_date')
            ->selectRaw('poa.pump_operator_id AS operator_id')
            ->selectRaw("COALESCE(NULLIF(po.name, ''), CONCAT('Operator ', poa.pump_operator_id)) AS operator_name")
            ->selectRaw($shiftIdExpression . ' AS shift_id')
            ->selectRaw($shiftNumberExpression . ' AS shift_number')
            ->selectRaw(
                "GROUP_CONCAT(DISTINCT CONCAT(COALESCE(poa.pump_id, 0), '|||', " . $pumpLabelExpression . ", '|||', " . $pumpStatusExpression . ") ORDER BY poa.pump_id SEPARATOR '@@@') AS pump_details"
            )
            ->selectRaw("SUM(CASE WHEN " . $pumpStatusExpression . " = 'open' THEN 1 ELSE 0 END) AS open_pump_count")
            ->selectRaw("SUM(CASE WHEN " . $pumpStatusExpression . " = 'closed' THEN 1 ELSE 0 END) AS closed_pump_count")
            ->selectRaw($shiftStatusExpression . ' AS shift_status');

        if (Schema::hasTable('settlements') && Schema::hasColumn('pump_operator_assignments', 'settlement_id')) {
            $query->selectRaw("GROUP_CONCAT(DISTINCT NULLIF(s.settlement_no, '') ORDER BY s.id DESC SEPARATOR ', ') AS settlement_no");
        } else {
            $query->selectRaw('NULL AS settlement_no');
        }

        $query->groupBy(DB::raw($dateExpression))
            ->groupBy('poa.pump_operator_id')
            ->groupBy('po.name')
            ->groupBy(DB::raw($shiftIdExpression))
            ->groupBy(DB::raw($shiftNumberExpression));

        if (Schema::hasTable('petro_shifts') && Schema::hasColumn('pump_operator_assignments', 'shift_id') && Schema::hasColumn('petro_shifts', 'status')) {
            $query->groupBy('ps.status');
        }

        if ($request->filled('start_date')) {
            $query->whereRaw($dateExpression . ' >= ?', [$this->safeDate($request->input('start_date'))]);
        }
        if ($request->filled('end_date')) {
            $query->whereRaw($dateExpression . ' <= ?', [$this->safeDate($request->input('end_date'))]);
        }
        if ($request->filled('operator_id')) {
            $query->where('poa.pump_operator_id', (int) $request->input('operator_id'));
        }
        if ($request->filled('pump_id')) {
            $query->havingRaw('SUM(CASE WHEN poa.pump_id = ? THEN 1 ELSE 0 END) > 0', [(int) $request->input('pump_id')]);
        }
        if ($request->filled('pump_status')) {
            $pumpStatus = strtolower((string) $request->input('pump_status'));
            if ($pumpStatus === 'open') {
                $query->havingRaw("SUM(CASE WHEN " . $pumpStatusExpression . " = 'open' THEN 1 ELSE 0 END) > 0");
            } elseif ($pumpStatus === 'closed') {
                $query->havingRaw("SUM(CASE WHEN " . $pumpStatusExpression . " = 'closed' THEN 1 ELSE 0 END) > 0");
            }
        }
        if ($request->filled('shift_status')) {
            $shiftStatus = strtolower((string) $request->input('shift_status'));
            if (in_array($shiftStatus, ['open', 'closed'], true)) {
                $query->havingRaw($shiftStatusExpression . ' = ?', [$shiftStatus]);
            }
        }
        if ($applySettlementFilter && $request->filled('settlement_no') && Schema::hasTable('settlements') && Schema::hasColumn('pump_operator_assignments', 'settlement_id')) {
            $query->havingRaw("SUM(CASE WHEN s.settlement_no = ? THEN 1 ELSE 0 END) > 0", [(string) $request->input('settlement_no')]);
        }

        return $query;
    }

    private function operatorOptions(int $businessId): Collection
    {
        if (! Schema::hasTable('pump_operators')) {
            return collect();
        }

        $query = DB::table('pump_operators');
        if (Schema::hasColumn('pump_operators', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        $nameExpression = Schema::hasColumn('pump_operators', 'name')
            ? "COALESCE(NULLIF(name, ''), CONCAT('Operator ', id))"
            : "CONCAT('Operator ', id)";

        return $query->select('id')->selectRaw($nameExpression . ' AS label')
            ->orderBy('label')
            ->get()
            ->mapWithKeys(function ($row) {
                return [(int) $row->id => (string) $row->label];
            });
    }

    private function pumpOptions(int $businessId): Collection
    {
        if (! Schema::hasTable('pumps')) {
            return collect();
        }

        $query = DB::table('pumps');
        if (Schema::hasColumn('pumps', 'business_id')) {
            $query->where('business_id', $businessId);
        }

        $label = $this->unaliasedPumpLabelExpression();

        return $query->select('id')->selectRaw($label . ' AS label')
            ->orderBy('label')
            ->get()
            ->mapWithKeys(function ($row) {
                return [(int) $row->id => (string) $row->label];
            });
    }

    private function requiredTablesAvailable(): bool
    {
        return Schema::hasTable('pump_operator_assignments')
            && Schema::hasTable('pump_operators')
            && Schema::hasTable('pumps')
            && Schema::hasColumn('pump_operator_assignments', 'business_id');
    }

    private function assignmentDateExpression(): string
    {
        $parts = [];
        foreach (['assignment_date', 'transaction_date', 'date_and_time', 'created_at'] as $column) {
            if (Schema::hasColumn('pump_operator_assignments', $column)) {
                $parts[] = 'DATE(poa.' . $column . ')';
            }
        }

        return empty($parts) ? 'CURRENT_DATE()' : 'COALESCE(' . implode(', ', $parts) . ')';
    }

    private function pumpStatusExpression(string $alias): string
    {
        if (! Schema::hasColumn('pump_operator_assignments', 'status')) {
            return "'open'";
        }

        return "CASE WHEN LOWER(TRIM(CAST({$alias}.status AS CHAR))) IN ('close','closed','2') THEN 'closed' ELSE 'open' END";
    }

    private function shiftStatusExpression(): string
    {
        if (Schema::hasTable('petro_shifts') && Schema::hasColumn('pump_operator_assignments', 'shift_id') && Schema::hasColumn('petro_shifts', 'status')) {
            return "CASE WHEN LOWER(TRIM(CAST(ps.status AS CHAR))) IN ('2','close','closed','finalized','settled') THEN 'closed' ELSE 'open' END";
        }

        $pumpStatus = $this->pumpStatusExpression('poa');
        return "CASE WHEN SUM(CASE WHEN {$pumpStatus} = 'open' THEN 1 ELSE 0 END) = 0 THEN 'closed' ELSE 'open' END";
    }

    private function pumpLabelExpression(): string
    {
        $parts = [];
        foreach (['pump_no', 'pump_number', 'pump_name', 'name', 'code'] as $column) {
            if (Schema::hasColumn('pumps', $column)) {
                $parts[] = "NULLIF(TRIM(CAST(p.{$column} AS CHAR)), '')";
            }
        }
        $parts[] = "CONCAT('Pump ', poa.pump_id)";

        return 'COALESCE(' . implode(', ', $parts) . ')';
    }

    private function unaliasedPumpLabelExpression(): string
    {
        $parts = [];
        foreach (['pump_no', 'pump_number', 'pump_name', 'name', 'code'] as $column) {
            if (Schema::hasColumn('pumps', $column)) {
                $parts[] = "NULLIF(TRIM(CAST({$column} AS CHAR)), '')";
            }
        }
        $parts[] = "CONCAT('Pump ', id)";

        return 'COALESCE(' . implode(', ', $parts) . ')';
    }

    private function pumpDetails(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        $result = [];
        foreach (explode('@@@', $value) as $item) {
            $parts = explode('|||', $item);
            if (count($parts) < 3) {
                continue;
            }

            $id = (int) $parts[0];
            $label = trim((string) $parts[1]);
            $status = strtolower(trim((string) $parts[2])) === 'closed' ? 'closed' : 'open';
            $key = $id > 0 ? $id : $label;

            $result[$key] = [
                'id' => $id,
                'label' => $label !== '' ? $label : ($id > 0 ? 'Pump ' . $id : 'Pump'),
                'status' => $status,
            ];
        }

        return array_values($result);
    }

    private function safeDate($value): string
    {
        try {
            return \Carbon\Carbon::parse($value)->toDateString();
        } catch (\Throwable $e) {
            return now()->toDateString();
        }
    }
}

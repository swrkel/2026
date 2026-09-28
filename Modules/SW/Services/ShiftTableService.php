<?php

namespace Modules\SW\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\SW\Entities\Shift;

/**
 * Read-only source for the SW Daily Shift table.
 *
 * Deliberately uses only the live tenant connection selected for the current
 * request. Optional display data (locations, users, operator assignments and
 * settlement links) is loaded separately so a mismatch in one shared table
 * can never take the entire shift list down.
 */
class ShiftTableService
{
    public function rows(int $businessId, ?int $locationId = null, ?string $status = null): Collection
    {
        if ($businessId <= 0 || ! $this->tableExists('sw_shifts')) {
            return collect();
        }

        foreach (['id', 'business_id', 'location_id', 'sw_shift_no', 'status'] as $column) {
            if (! $this->hasColumn('sw_shifts', $column)) {
                return collect();
            }
        }

        try {
            $columns = [
                'id', 'business_id', 'location_id', 'sw_shift_no', 'status',
            ];

            foreach (['shift_date', 'closed_at', 'created_by', 'deleted_at'] as $optional) {
                if ($this->hasColumn('sw_shifts', $optional)) {
                    $columns[] = $optional;
                }
            }

            $query = DB::table('sw_shifts')
                ->where('business_id', $businessId);

            if ($locationId && $locationId > 0) {
                $query->where('location_id', $locationId);
            }

            if (in_array('deleted_at', $columns, true)) {
                $query->whereNull('deleted_at');
            }

            if (in_array('shift_date', $columns, true)) {
                $query->orderByDesc('shift_date');
            }

            $rows = $query->orderByDesc('id')
                ->limit(2000)
                ->get($columns);

            if ($rows->isEmpty()) {
                return collect();
            }

            $ids = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
            $locationNames = $this->locationNames($rows->pluck('location_id')->all());
            $openedBy = $this->openedByNames($rows);
            $operatorNames = $this->operatorNames($ids);
            $settledIds = $this->settledShiftIds($ids);

            $requestedStatus = strtolower(trim((string) $status));

            return $rows->map(function ($row) use (
                $locationNames,
                $openedBy,
                $operatorNames,
                $settledIds
            ) {
                $id = (int) $row->id;
                $effectiveStatus = $settledIds->contains($id)
                    ? Shift::STATUS_SETTLED
                    : Shift::normalizeStatusValue($row->status ?? 0, $row->closed_at ?? null);

                $row->effective_status = $effectiveStatus;
                $row->location_name = $locationNames[(int) $row->location_id] ?? null;
                $row->opened_by = isset($row->created_by)
                    ? ($openedBy[(int) $row->created_by] ?? null)
                    : null;
                $row->operator_names = $operatorNames[$id] ?? collect();
                $row->semantic_status = $this->semanticStatus($effectiveStatus);

                return $row;
            })->when(
                in_array($requestedStatus, ['open', 'closed', 'settled'], true),
                fn (Collection $collection) => $collection
                    ->filter(fn ($row) => $row->semantic_status === $requestedStatus)
                    ->values()
            );
        } catch (\Throwable $e) {
            Log::warning('SW Daily Shift list could not be built.', [
                'business_id' => $businessId,
                'location_id' => $locationId,
                'status' => $status,
                'database' => $this->databaseName(),
                'message' => $e->getMessage(),
            ]);

            return collect();
        }
    }

    protected function locationNames(array $ids): Collection
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty() || ! $this->tableExists('business_locations')) {
            return collect();
        }

        $nameColumn = $this->firstColumn('business_locations', ['name', 'location_name']);
        if (! $this->hasColumn('business_locations', 'id') || ! $nameColumn) {
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

    protected function openedByNames(Collection $rows): Collection
    {
        if (! $this->tableExists('users') || ! $this->hasColumn('users', 'id')) {
            return collect();
        }

        $ids = $rows->pluck('created_by')->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }

        $nameColumn = $this->firstColumn('users', ['username', 'user_name', 'first_name', 'name']);
        if (! $nameColumn) {
            return collect();
        }

        try {
            return DB::table('users')
                ->whereIn('id', $ids->all())
                ->pluck($nameColumn, 'id');
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function operatorNames(array $shiftIds): Collection
    {
        if (empty($shiftIds)
            || ! $this->tableExists('sw_shift_operators')
            || ! $this->tableExists('pump_operators')
            || ! $this->hasColumn('pump_operators', 'id')) {
            return collect();
        }

        $shiftKey = $this->firstColumn('sw_shift_operators', ['sw_shift_id', 'shift_id']);
        $operatorKey = $this->firstColumn('sw_shift_operators', ['pump_operator_id', 'operator_id']);
        $nameColumn = $this->firstColumn('pump_operators', ['name', 'operator_name', 'full_name']);

        if (! $shiftKey || ! $operatorKey || ! $nameColumn) {
            return collect();
        }

        try {
            return DB::table('sw_shift_operators as so')
                ->join('pump_operators as po', 'po.id', '=', 'so.' . $operatorKey)
                ->whereIn('so.' . $shiftKey, $shiftIds)
                ->get([
                    'so.' . $shiftKey . ' as sw_shift_id',
                    'po.' . $nameColumn . ' as operator_name',
                ])
                ->groupBy('sw_shift_id')
                ->map(fn (Collection $items) => $items->pluck('operator_name')->filter()->values());
        } catch (\Throwable $e) {
            return collect();
        }
    }

    protected function settledShiftIds(array $shiftIds): Collection
    {
        if (empty($shiftIds) || ! $this->tableExists('sw_settlement_shifts')) {
            return collect();
        }

        $shiftKey = $this->firstColumn('sw_settlement_shifts', ['sw_shift_id', 'shift_id']);
        if (! $shiftKey) {
            return collect();
        }

        try {
            $query = DB::table('sw_settlement_shifts')->whereIn($shiftKey, $shiftIds);

            if ($this->hasColumn('sw_settlement_shifts', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            return $query->pluck($shiftKey)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
        } catch (\Throwable $e) {
            return collect();
        }
    }

    public function semanticStatus(int $status): string
    {
        return match ($status) {
            Shift::STATUS_OPEN => 'open',
            Shift::STATUS_CLOSED => 'closed',
            Shift::STATUS_SETTLED => 'settled',
            Shift::STATUS_VOID => 'void',
            default => 'unknown',
        };
    }

    public function tableExists(string $table): bool
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

    public function hasColumn(string $table, string $column): bool
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

    protected function firstColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if ($this->hasColumn($table, $column)) {
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
}

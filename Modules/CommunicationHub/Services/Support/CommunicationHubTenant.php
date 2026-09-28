<?php

namespace Modules\CommunicationHub\Services\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CommunicationHubTenant
{
    public static function businessId(): ?int
    {
        $candidates = [
            session('user.business_id'),
            session('business.id'),
            session('business_id'),
            optional(auth()->user())->business_id,
        ];

        foreach ($candidates as $value) {
            if (!empty($value) && is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    public static function locationId(): ?int
    {
        $candidates = [
            session('business_location_id'),
            session('user.business_location_id'),
            optional(auth()->user())->business_location_id,
        ];

        foreach ($candidates as $value) {
            if (!empty($value) && is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    public static function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            self::logSchemaIssue('hasTable', $table, $e);
            return false;
        }
    }

    public static function hasColumn(string $table, string $column): bool
    {
        try {
            return self::hasTable($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            self::logSchemaIssue('hasColumn', $table . '.' . $column, $e);
            return false;
        }
    }

    public static function table(Model|string $modelOrTable): string
    {
        if ($modelOrTable instanceof Model) {
            return $modelOrTable->getTable();
        }

        if (class_exists($modelOrTable) && is_subclass_of($modelOrTable, Model::class)) {
            /** @var Model $model */
            $model = new $modelOrTable;
            return $model->getTable();
        }

        return (string) $modelOrTable;
    }

    public static function query(string $modelClass): ?Builder
    {
        if (!class_exists($modelClass)) {
            return null;
        }

        /** @var Model $model */
        $model = new $modelClass;
        if (!self::hasTable($model->getTable())) {
            return null;
        }

        return self::scopeBusiness($modelClass::query(), $model->getTable());
    }

    public static function scopeBusiness(Builder $query, ?string $table = null): Builder
    {
        $table = $table ?: $query->getModel()->getTable();
        $businessId = self::businessId();

        if ($businessId && self::hasColumn($table, 'business_id')) {
            $query->where($table . '.business_id', $businessId);
        }

        return $query;
    }

    public static function count(string $modelClass, ?callable $callback = null): int
    {
        $query = self::query($modelClass);
        if (!$query) {
            return 0;
        }

        if ($callback) {
            $callback($query);
        }

        try {
            return (int) $query->count();
        } catch (\Throwable $e) {
            self::logQueryIssue($modelClass, $e);
            return 0;
        }
    }

    public static function sum(string $modelClass, string $column, ?callable $callback = null): float
    {
        $query = self::query($modelClass);
        if (!$query) {
            return 0.0;
        }

        $table = $query->getModel()->getTable();
        if (!self::hasColumn($table, $column)) {
            return 0.0;
        }

        if ($callback) {
            $callback($query);
        }

        try {
            return (float) $query->sum($column);
        } catch (\Throwable $e) {
            self::logQueryIssue($modelClass . ':' . $column, $e);
            return 0.0;
        }
    }

    public static function latestCollection(string $modelClass, int $limit = 50, ?callable $callback = null): Collection
    {
        $query = self::query($modelClass);
        if (!$query) {
            return collect();
        }

        if ($callback) {
            $callback($query);
        }

        try {
            return $query->latest()->limit($limit)->get();
        } catch (\Throwable $e) {
            self::logQueryIssue($modelClass, $e);
            return collect();
        }
    }

    public static function paginate(string $modelClass, int $perPage = 50, ?callable $callback = null): LengthAwarePaginator
    {
        $query = self::query($modelClass);
        if (!$query) {
            return new LengthAwarePaginator([], 0, $perPage, LengthAwarePaginator::resolveCurrentPage(), [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
            ]);
        }

        if ($callback) {
            $callback($query);
        }

        try {
            return $query->latest()->paginate($perPage);
        } catch (\Throwable $e) {
            self::logQueryIssue($modelClass, $e);
            return new LengthAwarePaginator([], 0, $perPage, LengthAwarePaginator::resolveCurrentPage(), [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
            ]);
        }
    }

    public static function currentDatabase(): ?string
    {
        try {
            return DB::connection()->getDatabaseName();
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function logSchemaIssue(string $operation, string $target, \Throwable $e): void
    {
        Log::warning('CommunicationHub schema guard prevented runtime error', [
            'operation' => $operation,
            'target' => $target,
            'database' => self::currentDatabase(),
            'error' => $e->getMessage(),
        ]);
    }

    protected static function logQueryIssue(string $target, \Throwable $e): void
    {
        Log::warning('CommunicationHub tenant query guard prevented runtime error', [
            'target' => $target,
            'database' => self::currentDatabase(),
            'business_id' => self::businessId(),
            'error' => $e->getMessage(),
        ]);
    }
}

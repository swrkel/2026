<?php

namespace Modules\PetroPD\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema-safe tenant query helper for PetroPD recovery work.
 *
 * This helper prevents production failures caused by optional columns that are
 * present in some tenant databases but missing in others. All queries use the
 * active Laravel tenant connection.
 */
class PetroPdSchemaSafeQuery
{
    /** @var array<string, bool> */
    private static array $tableCache = [];

    /** @var array<string, bool> */
    private static array $columnCache = [];

    private function schemaCachePrefix(): string
    {
        $connection = DB::connection();

        return $connection->getName() . ':' . (string) $connection->getDatabaseName();
    }
    public function hasTable(string $table): bool
    {
        $key = $this->schemaCachePrefix() . ':table:' . $table;

        return self::$tableCache[$key] ??= Schema::hasTable($table);
    }

    public function hasColumn(string $table, string $column): bool
    {
        $key = $this->schemaCachePrefix() . ':column:' . $table . ':' . $column;

        return self::$columnCache[$key] ??= ($this->hasTable($table) && Schema::hasColumn($table, $column));
    }

    public function query(string $table): Builder
    {
        return DB::table($table);
    }

    public function whereIfColumn(Builder $query, string $table, string $column, $value): Builder
    {
        if ($this->hasColumn($table, $column)) {
            $query->where($column, $value);
        }

        return $query;
    }

    public function orWhereAnyExistingColumn(Builder $query, string $table, array $columns, $value): Builder
    {
        $existing = array_values(array_filter($columns, fn ($column) => $this->hasColumn($table, $column)));

        if (empty($existing)) {
            return $query;
        }

        return $query->where(function ($inner) use ($existing, $value) {
            foreach ($existing as $index => $column) {
                if ($index === 0) {
                    $inner->where($column, $value);
                } else {
                    $inner->orWhere($column, $value);
                }
            }
        });
    }

    public function filterPayload(string $table, array $payload): array
    {
        $filtered = [];
        foreach ($payload as $column => $value) {
            if ($this->hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }
}

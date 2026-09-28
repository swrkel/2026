<?php

namespace Modules\POS\Services;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class POSBaseService
{
    /**
     * Schema metadata is cached only inside this service instance/request.
     * The database name is part of the key so tenant switches cannot reuse
     * column information from another database.
     */
    private array $posTableExistsCache = [];
    private array $posColumnCache = [];

    protected function businessId(): ?int
    {
        $id = session('business.id')
            ?? session('user.business_id')
            ?? (Auth::user()->business_id ?? null);

        return $id !== null ? (int) $id : null;
    }

    protected function locationId(): ?int
    {
        $id = session('business_location_id')
            ?? session('location_id')
            ?? request()->input('business_location_id');

        return $id !== null && $id !== '' ? (int) $id : null;
    }

    protected function userId(): ?int
    {
        return Auth::id();
    }

    /**
     * Use the exact connection that DB::table() will use for this request.
     * This is important in the tenant application because the visible database
     * can differ from the central database selected in phpMyAdmin.
     */
    protected function connectionName(): string
    {
        $name = DB::getDefaultConnection();

        return is_string($name) && $name !== ''
            ? $name
            : (string) config('database.default', 'mysql');
    }

    protected function connection(): ConnectionInterface
    {
        return DB::connection($this->connectionName());
    }

    protected function currentDatabaseName(): ?string
    {
        try {
            $name = $this->connection()->getDatabaseName();
            return is_string($name) && $name !== '' ? $name : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function schemaCacheKey(string $table): string
    {
        return $this->connectionName() . '|' . ($this->currentDatabaseName() ?? 'unknown') . '|' . $table;
    }

    protected function tableExists(string $table): bool
    {
        $key = $this->schemaCacheKey($table);

        if (array_key_exists($key, $this->posTableExistsCache)) {
            return $this->posTableExistsCache[$key];
        }

        try {
            return $this->posTableExistsCache[$key] = $this->connection()
                ->getSchemaBuilder()
                ->hasTable($table);
        } catch (\Throwable $e) {
            return $this->posTableExistsCache[$key] = false;
        }
    }

    protected function columns(string $table): array
    {
        $key = $this->schemaCacheKey($table);

        if (array_key_exists($key, $this->posColumnCache)) {
            return $this->posColumnCache[$key];
        }

        if (! $this->tableExists($table)) {
            return $this->posColumnCache[$key] = [];
        }

        try {
            $columns = $this->connection()->getSchemaBuilder()->getColumnListing($table);
            $columns = array_values(array_unique(array_map('strval', $columns)));

            return $this->posColumnCache[$key] = $columns;
        } catch (\Throwable $e) {
            return $this->posColumnCache[$key] = [];
        }
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    protected function firstExistingColumn(string $table, array $candidates): ?string
    {
        $available = array_flip($this->columns($table));

        foreach ($candidates as $candidate) {
            if (isset($available[$candidate])) {
                return $candidate;
            }
        }

        return null;
    }

    protected function onlyExistingColumns(string $table, array $data): array
    {
        $columns = $this->columns($table);

        if ($columns === []) {
            return [];
        }

        return array_intersect_key($data, array_flip($columns));
    }

    protected function money($value): string
    {
        return number_format((float) $value, $this->currencyPrecision(), '.', ',');
    }

    protected function currencyPrecision(): int
    {
        return (int) (session('business.currency_precision') ?? session('currency_precision') ?? 2);
    }

    protected function nowString(): string
    {
        return now()->format('Y-m-d H:i:s');
    }
}

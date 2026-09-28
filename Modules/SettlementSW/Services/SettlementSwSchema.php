<?php

namespace Modules\SettlementSW\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Request-local, tenant/database-aware schema capability cache.
 *
 * Laravel's Schema::hasTable/hasColumn calls query information_schema. Settlement
 * SW previously repeated those checks throughout hot controller paths. This
 * helper performs each capability check at most once per tenant connection and
 * request, without persisting stale schema state between requests.
 */
class SettlementSwSchema
{
    protected static array $cache = [];

    public static function hasTable(string $table): bool
    {
        $key = self::key('table', $table);

        return self::$cache[$key] ??= Schema::hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        $key = self::key('column', $table, $column);

        return self::$cache[$key] ??= Schema::hasColumn($table, $column);
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    protected static function key(string ...$parts): string
    {
        $connection = DB::connection();
        $connectionName = $connection->getName() ?: 'default';
        $databaseName = (string) $connection->getDatabaseName();

        return implode('|', array_merge([$connectionName, $databaseName], $parts));
    }
}

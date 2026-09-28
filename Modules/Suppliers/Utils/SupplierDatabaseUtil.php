<?php

namespace Modules\Suppliers\Utils;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

/**
 * Supplier module database access wrapper.
 *
 * All Supplier module services should use this utility/repositories instead of
 * scattered DB facade calls. This keeps DB access local to the Suppliers module
 * while still using Laravel's active tenant connection underneath.
 */
class SupplierDatabaseUtil
{
    public static function connection(): ConnectionInterface
    {
        return DB::connection();
    }

    public static function transaction(Closure $callback)
    {
        return self::connection()->transaction($callback);
    }

    public static function raw(string $expression)
    {
        return DB::raw($expression);
    }
}

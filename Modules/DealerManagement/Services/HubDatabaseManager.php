<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;

class HubDatabaseManager
{
    public function centralDatabase(): string
    {
        return (string) config('dealermanagement.hub_central_database', env('DEALER_HUB_CENTRAL_DATABASE', config('database.connections.mysql.database')));
    }

    public function runOn(string $database, callable $callback)
    {
        $original = (string) config('database.connections.mysql.database');
        if ($database === '' || $database === $original) return $callback();

        try {
            DB::purge('mysql');
            config(['database.connections.mysql.database' => $database]);
            DB::reconnect('mysql');
            return $callback();
        } finally {
            DB::purge('mysql');
            config(['database.connections.mysql.database' => $original]);
            DB::reconnect('mysql');
        }
    }

    public function central(callable $callback)
    {
        return $this->runOn($this->centralDatabase(), $callback);
    }

    public function databaseExists(string $database): bool
    {
        $r = DB::select('SELECT COUNT(*) cnt FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?', [$database]);
        return !empty($r) && (int)$r[0]->cnt > 0;
    }
}

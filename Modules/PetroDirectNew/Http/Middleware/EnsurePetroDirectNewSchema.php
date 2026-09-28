<?php

namespace Modules\PetroDirectNew\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Modules\PetroDirectNew\Support\SchemaDefinition;

class EnsurePetroDirectNewSchema
{
    public function handle($request, Closure $next)
    {
        $connection = DB::connection();
        $schema = $connection->getSchemaBuilder();
        $databaseName = (string) $connection->getDatabaseName();
        $connectionName = (string) config('database.default');

        $missing = [];
        foreach (SchemaDefinition::tables() as $table) {
            if (!$schema->hasTable($table)) {
                $missing[] = 'table: ' . $table;
            }
        }

        foreach (SchemaDefinition::requiredColumns() as $table => $columns) {
            if (!$schema->hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!$schema->hasColumn($table, $column)) {
                    $missing[] = 'column: ' . $table . '.' . $column;
                }
            }
        }

        if ($missing !== []) {
            return response()->view('petrodirectnew::setup-required', [
                'missing' => $missing,
                'databaseName' => $databaseName,
                'connectionName' => $connectionName,
            ], 503);
        }

        return $next($request);
    }
}

<?php

namespace Modules\StockTransferNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\StockTransferNew\Services\StockTransferSchemaService;

class EnsureStockTransferSchema
{
    public function __construct(private StockTransferSchemaService $schema)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            $this->schema->ensure();
        } catch (\Throwable $exception) {
            Log::error('Stock Transfer New schema readiness failed.', [
                'database' => $this->schema->databaseName(),
                'missing_tables' => $this->schema->missingTables(),
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            $payload = [
                'message' => 'Stock Transfer New database setup could not be completed.',
                'database' => $this->schema->databaseName(),
                'missing_tables' => $this->schema->missingTables(),
                'sql_file' => 'Modules/StockTransferNew/Database/SQL/STN_CORE_SCHEMA_REPAIR_IDEMPOTENT.sql',
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($payload, 503);
            }

            return response(
                '<h3>Stock Transfer New database setup is incomplete</h3>'
                . '<p>Database: ' . e((string) $payload['database']) . '</p>'
                . '<p>Missing tables: ' . e(implode(', ', $payload['missing_tables'])) . '</p>'
                . '<p>Run: <code>' . e($payload['sql_file']) . '</code></p>',
                503
            );
        }

        return $next($request);
    }
}

<?php

namespace Modules\StockAdjustmentNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\StockAdjustmentNew\Services\StockAdjustmentSchemaService;

class EnsureStockAdjustmentSchema
{
    public function __construct(private StockAdjustmentSchemaService $schema)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('stock-adjustment-new.assets')) {
            return $next($request);
        }

        try {
            $this->schema->ensure();
        } catch (\Throwable $exception) {
            Log::error('Stock Adjustment New schema readiness failed.', [
                'database' => $this->schema->databaseName(),
                'missing_tables' => $this->schema->missingTables(),
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            $payload = [
                'message' => 'Stock Adjustment New database setup could not be completed.',
                'database' => $this->schema->databaseName(),
                'missing_tables' => $this->schema->missingTables(),
                'sql_file' => 'Modules/StockAdjustmentNew/SQL/10_MASTER_INSTALL.sql',
            ];

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json($payload, 503);
            }

            return response()->view('stockadjustmentnew::errors.schema', $payload, 503);
        }

        return $next($request);
    }
}

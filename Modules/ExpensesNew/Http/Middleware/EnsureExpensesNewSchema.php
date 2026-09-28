<?php

namespace Modules\ExpensesNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\ExpensesNew\Services\SchemaReadinessService;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureExpensesNewSchema
{
    public function __construct(protected SchemaReadinessService $schemaReadiness)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $this->schemaReadiness->ensure();
        } catch (Throwable $exception) {
            $database = null;

            try {
                $database = DB::connection()->getDatabaseName();
            } catch (Throwable) {
                // Keep the original schema exception as the primary failure.
            }

            Log::error('Expenses New request blocked because its tenant schema is not ready.', [
                'database' => $database,
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
            ]);

            $message = 'Expenses New database setup is incomplete for this tenant.';
            $detail = config('app.debug') ? $exception->getMessage() : null;

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'detail' => $detail,
                    'database' => $database,
                    'sql_files' => [
                        'Modules/ExpensesNew/Database/sql/00_EXPENSES_NEW_CORE_SCHEMA_IDEMPOTENT.sql',
                        'Modules/ExpensesNew/Database/sql/01_EXPENSES_NEW_CORE_COLUMN_REPAIR_IDEMPOTENT.sql',
                    ],
                ], 503);
            }

            return response()->view('expensesnew::errors.schema-not-ready', [
                'message' => $message,
                'detail' => $detail,
                'database' => $database,
            ], 503);
        }

        return $next($request);
    }
}

<?php

namespace Modules\PetroPDNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\PetroPDNew\Services\PdnewSchemaService;

class EnsurePdnewSchema
{
    public function __construct(private PdnewSchemaService $schema) {}

    public function handle(Request $request, Closure $next)
    {
        try {
            if (! $this->schema->isInstalled()) {
                return response()->view('petropdnew::errors.schema', [
                    'missingTables' => $this->schema->missingModuleTables(),
                    'missingSourceTables' => $this->schema->missingSourceTables(),
                    'missingSourceColumns' => $this->schema->missingSourceColumns(),
                    'activeDatabase' => $this->databaseName(),
                ], 503);
            }
        } catch (\Throwable $exception) {
            report($exception);
            return response()->view('petropdnew::errors.schema', [
                'schemaError' => $exception->getMessage(),
                'activeDatabase' => $this->databaseName(),
            ], 503);
        }

        return $next($request);
    }

    private function databaseName(): string
    {
        try {
            return (string) DB::connection()->getDatabaseName();
        } catch (\Throwable $exception) {
            return (string) config('database.default', 'tenant');
        }
    }
}


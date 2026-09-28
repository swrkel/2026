<?php

namespace Modules\StockTakingNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Services\StockTakingSchemaService;

class EnsureStockTakingSchema
{
    public function __construct(private StockTakingSchemaService $schema) {}

    public function handle(Request $request, Closure $next)
    {
        try {
            if (! $this->schema->isInstalled() && config('stocktakingnew.auto_install_schema', true)) {
                $this->schema->install();
            }

            if (! $this->schema->isInstalled()) {
                return response()->view('stocktakingnew::errors.schema', [], 503);
            }
        } catch (\Throwable $exception) {
            report($exception);
            return response()->view('stocktakingnew::errors.schema', ['schemaError' => $exception->getMessage()], 503);
        }

        return $next($request);
    }
}

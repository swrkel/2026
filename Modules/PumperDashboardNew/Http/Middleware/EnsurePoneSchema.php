<?php

namespace Modules\PumperDashboardNew\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\PumperDashboardNew\Services\PoneSchemaService;

class EnsurePoneSchema
{
    public function __construct(private PoneSchemaService $schema) {}

    public function handle(Request $request, Closure $next)
    {
        try {
            if (! $this->schema->isInstalled() && config('pumperdashboardnew.auto_install_schema', true)) {
                $this->schema->install();
            }

            if (! $this->schema->isInstalled()) {
                return response()->view('pumperdashboardnew::errors.schema', [], 503);
            }
        } catch (\Throwable $exception) {
            report($exception);
            return response()->view('pumperdashboardnew::errors.schema', [
                'schemaError' => $exception->getMessage(),
            ], 503);
        }

        return $next($request);
    }
}

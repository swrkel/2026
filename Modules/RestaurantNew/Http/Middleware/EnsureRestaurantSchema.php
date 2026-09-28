<?php
namespace Modules\RestaurantNew\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Modules\RestaurantNew\Services\RestaurantSchemaService;
class EnsureRestaurantSchema
{
    public function __construct(private RestaurantSchemaService $schema) {}
    public function handle(Request $request, Closure $next)
    {
        // Never create Restaurant-New operational tables in the central database.
        // Central Super Admin pages can still discover/configure the module through
        // the application's automatic module registry.
        if ($request->attributes->get('restaurantnew.central_host') === true) {
            return $next($request);
        }

        try {
            if (!$this->schema->isInstalled() && config('restaurantnew.auto_install_schema', true)) $this->schema->install();
            if (!$this->schema->isInstalled()) return response()->view('restaurantnew::errors.schema', [], 503);
        } catch (\Throwable $e) {
            report($e);
            return response()->view('restaurantnew::errors.schema', ['schemaError' => $e->getMessage()], 503);
        }
        return $next($request);
    }
}

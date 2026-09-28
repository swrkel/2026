<?php

namespace Modules\Customers\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Customers\Services\CustomerFeatureAvailability;

class CustomersFeatureMiddleware
{
    protected CustomerFeatureAvailability $features;

    public function __construct(CustomerFeatureAvailability $features)
    {
        $this->features = $features;
    }

    public function handle(
        Request $request,
        Closure $next,
        string $feature
    ) {
        $missing = $this->features->missingTables($feature);

        if ($missing === []) {
            return $next($request);
        }

        $message = sprintf(
            'This Customers feature is not available until the required tables are installed: %s.',
            implode(', ', $missing)
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'missing_tables' => $missing,
            ], 503);
        }

        return redirect()
            ->route('customers.index')
            ->with('status', [
                'success' => 0,
                'msg' => $message,
                'background' => 'alert-warning',
            ]);
    }
}

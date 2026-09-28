<?php

namespace Modules\Distribution\Support;

/**
 * Distribution-owned route registry.
 *
 * Provides a module-local map of the important Distribution route names used by
 * controllers, views, JS and reports. This helps remove hard-coded route/action
 * references gradually without changing working routes immediately.
 */
class DistributionRouteRegistry
{
    public function all(): array
    {
        return config('distribution.routes', []);
    }

    public function name(string $key, ?string $fallback = null): ?string
    {
        return $this->all()[$key] ?? $fallback;
    }
}

<?php

namespace Modules\EnterpriseFramework\Services\Cache;

use Illuminate\Support\Facades\Cache;

class EnterpriseReportCacheService
{
    public function key(string $module, string $report, array $context = []): string
    {
        return 'efw:' . $module . ':' . $report . ':' . md5(json_encode($context));
    }

    public function remember(string $module, string $report, array $context, callable $callback)
    {
        if (!config('enterpriseframework.cache_enabled', false)) {
            return $callback();
        }
        return Cache::remember($this->key($module, $report, $context), now()->addMinutes(config('enterpriseframework.cache_ttl_minutes', 30)), $callback);
    }

    public function forget(string $module, string $report, array $context = []): void
    {
        Cache::forget($this->key($module, $report, $context));
    }
}

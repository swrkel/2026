<?php
namespace Modules\AirlineTicketingNew\Services\Deployment;

class EnvironmentValidationService
{
    public function validate(): array
    {
        $checks = [
            'php_8_2_or_higher' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'storage_writable' => is_writable(storage_path()),
            'cache_writable' => is_writable(storage_path('framework/cache')),
            'logs_writable' => is_writable(storage_path('logs')),
            'app_key_present' => !empty(config('app.key')),
        ];

        return [
            'status' => collect($checks)->every(fn ($value) => $value) ? 'ready' : 'blocked',
            'checks' => $checks,
        ];
    }
}

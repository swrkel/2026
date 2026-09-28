<?php

namespace Modules\EnterpriseFramework\Services\Scheduler;

class EnterpriseScheduleDefinitionService
{
    public function frequencies(): array
    {
        return ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'];
    }

    public function make(string $reportKey, array $context = []): array
    {
        return [
            'report_key' => $reportKey,
            'frequency' => $context['frequency'] ?? 'monthly',
            'format' => $context['format'] ?? 'pdf',
            'branch_id' => $context['branch_id'] ?? null,
            'is_consolidated' => (bool)($context['is_consolidated'] ?? true),
            'enabled' => (bool)($context['enabled'] ?? false),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}

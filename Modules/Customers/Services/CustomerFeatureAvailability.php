<?php

namespace Modules\Customers\Services;

use Illuminate\Support\Facades\Schema;

/**
 * Reports whether optional Customers feature tables are available.
 *
 * Core customer master, ledger and statement functionality must continue to
 * work even when optional portal/workflow tables have not yet been installed.
 */
class CustomerFeatureAvailability
{
    public const WORKFLOW = 'workflow';

    /**
     * @return array<string, array<int, string>>
     */
    public function definitions(): array
    {
        return [
            self::WORKFLOW => [
                'customer_workflow_approvals',
                'customer_workflow_histories',
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function requiredTables(string $feature): array
    {
        return $this->definitions()[$feature] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function missingTables(string $feature): array
    {
        return array_values(array_filter(
            $this->requiredTables($feature),
            static fn (string $table): bool => ! Schema::hasTable($table)
        ));
    }

    public function available(string $feature): bool
    {
        return $this->missingTables($feature) === [];
    }
}

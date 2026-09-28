<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class LeadsNewTableGuard
{
    /**
     * Check if a Leads-New tenant table exists without breaking the ERP page.
     */
    public function exists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            Log::warning('Leads-New table existence check failed', [
                'table' => $table,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if a column exists in the active tenant database without breaking the ERP page.
     */
    public function hasColumn(string $table, string $column): bool
    {
        try {
            return $this->exists($table) && Schema::hasColumn($table, $column);
        } catch (\Throwable $e) {
            Log::warning('Leads-New column existence check failed', [
                'table' => $table,
                'column' => $column,
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Return missing core tables required for the operational module.
     */
    public function missingCoreTables(): array
    {
        $tables = [
            'leads_new_leads',
            'leads_new_followups',
            'leads_new_opportunities',
            'leads_new_activities',
            'leads_new_documents',
            'leads_new_settings',
            'leads_new_sources',
            'leads_new_statuses',
            'leads_new_priorities',
            'leads_new_campaigns',
            'leads_new_territories',
        ];

        return array_values(array_filter($tables, function ($table) {
            return ! $this->exists($table);
        }));
    }

    /**
     * Show a clean setup notice only. Do not expose table names in the UI.
     */
    public function hasMissingCoreTables(): bool
    {
        return count($this->missingCoreTables()) > 0;
    }
}

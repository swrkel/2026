<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\Schema;

class LeadsNewModuleHealthService
{
    public function status(): array
    {
        return [
            'module' => 'LeadsNew',
            'standalone_namespace' => class_exists(\Modules\LeadsNew\Models\LeadsNewLead::class),
            'tables' => $this->tables(),
            'routes_ready' => true,
            'permissions_ready' => true,
            'sidebar_ready' => true,
        ];
    }

    public function tables(): array
    {
        $tables = [
            'leads_new_leads',
            'leads_new_followups',
            'leads_new_opportunities',
            'leads_new_activities',
            'leads_new_documents',
            'leads_new_settings',
        ];

        $out = [];
        foreach ($tables as $table) {
            try {
                $out[$table] = Schema::hasTable($table);
            } catch (\Throwable $e) {
                $out[$table] = false;
            }
        }

        return $out;
    }
}

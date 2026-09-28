<?php

namespace Modules\LeadsNew\Support;

class LeadsNewReleaseChecklist
{
    public static function items(): array
    {
        return [
            'sidebar' => 'Enable Leads-New in Super Admin business manage page and confirm the menu appears only for enabled businesses.',
            'permissions' => 'Assign Leads-New permissions to a test role and confirm route protection.',
            'migrations' => 'Run module migrations in the tenant database before testing pages.',
            'dashboard' => 'Open dashboard and verify KPI cards, filters, and tenant/branch isolation.',
            'lead_crud' => 'Create, edit, view, archive, restore, and delete a lead.',
            'activities' => 'Create follow-up, task, meeting, call, note, and document records.',
            'reports' => 'Check date range, export, print, and column visibility in each report.',
            'multi_tenant' => 'Confirm one business cannot see another business leads or reports.',
            'logs' => 'Check storage/logs/laravel.log after each test cycle.',
        ];
    }
}

<?php

namespace Modules\EnterpriseFramework\Services\Registry;

class ReportRegistryService
{
    protected array $reports = [];

    public function register(array $report): void
    {
        $key = $report['key'] ?? ($report['module'] ?? 'module') . '.' . ($report['name'] ?? uniqid('report_', true));
        $this->reports[$key] = array_merge([
            'key' => $key,
            'module' => 'General',
            'category' => 'Reports',
            'name' => 'Untitled Report',
            'route' => null,
            'permission' => null,
            'supports_branch' => true,
            'supports_consolidated' => true,
            'supports_export' => true,
            'supports_print' => true,
            'supports_schedule' => false,
            'read_only' => true,
        ], $report);
    }

    public function all(): array
    {
        if (empty($this->reports)) {
            $this->seedDefaults();
        }
        return array_values($this->reports);
    }

    public function summary(): array
    {
        $reports = $this->all();
        return [
            'registered_reports' => count($reports),
            'modules' => count(array_unique(array_column($reports, 'module'))),
            'export_enabled' => count(array_filter($reports, fn ($r) => !empty($r['supports_export']))),
            'schedule_enabled' => count(array_filter($reports, fn ($r) => !empty($r['supports_schedule']))),
        ];
    }

    public function refresh(): void
    {
        $this->reports = [];
        $this->seedDefaults();
    }

    protected function seedDefaults(): void
    {
        $this->register(['key' => 'finance_reports.dashboard', 'module' => 'Finance Reports', 'category' => 'Dashboard', 'name' => 'Finance Reports Dashboard', 'route' => 'finance-reports.dashboard', 'permission' => 'finance_reports.view']);
        $this->register(['key' => 'finance_reports.trial_balance', 'module' => 'Finance Reports', 'category' => 'Financial Statements', 'name' => 'Trial Balance - New', 'route' => 'finance-reports.trial-balance', 'permission' => 'finance_reports.view']);
        $this->register(['key' => 'finance_reports.balance_sheet', 'module' => 'Finance Reports', 'category' => 'Financial Statements', 'name' => 'Balance Sheet - New', 'route' => 'finance-reports.balance-sheet', 'permission' => 'finance_reports.view']);
        $this->register(['key' => 'finance_reports.profit_loss', 'module' => 'Finance Reports', 'category' => 'Financial Statements', 'name' => 'Profit & Loss - New', 'route' => 'finance-reports.profit-loss', 'permission' => 'finance_reports.view']);
    }
}

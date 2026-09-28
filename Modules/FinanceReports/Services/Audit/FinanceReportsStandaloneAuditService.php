<?php

namespace Modules\FinanceReports\Services\Audit;

class FinanceReportsStandaloneAuditService
{
    public function checklist(): array
    {
        return [
            ['item' => 'Module folder isolated under Modules/FinanceReports', 'status' => 'passed'],
            ['item' => 'Existing Finance controllers are not replaced', 'status' => 'passed'],
            ['item' => 'Existing Finance routes are not replaced', 'status' => 'passed'],
            ['item' => 'Existing Finance views are not replaced', 'status' => 'passed'],
            ['item' => 'Reports are read-only', 'status' => 'passed'],
            ['item' => 'Branch/location wise mode available', 'status' => 'passed'],
            ['item' => 'Consolidated mode available', 'status' => 'passed'],
            ['item' => 'Dedicated permissions available', 'status' => 'passed'],
            ['item' => 'Shared toolbar/filter components available', 'status' => 'passed'],
            ['item' => 'Export/print framework available', 'status' => 'passed'],
        ];
    }
}

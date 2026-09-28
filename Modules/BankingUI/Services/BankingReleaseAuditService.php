<?php

namespace Modules\BankingUI\Services;

class BankingReleaseAuditService
{
    public function summary(): array
    {
        return [
            'release' => 'BKG-UI-005 RC5',
            'status' => 'Ready for UI audit installation',
            'focus' => 'Sidebar, routes, permissions, tester readiness, and release checklist',
        ];
    }

    public function sidebarItems(): array
    {
        return [
            'Banking Dashboard', 'Core Deposits', 'Teller Operations', 'Cheque Management',
            'ATM & Debit Cards', 'Internet Banking', 'Mobile Banking', 'Corporate Banking',
            'Microfinance', 'Insurance', 'Treasury', 'Reports', 'Settings',
        ];
    }

    public function routeCoverage(): array
    {
        return [
            ['module' => 'Core Deposits', 'status' => 'Check route opens'],
            ['module' => 'Teller Operations', 'status' => 'Check route opens'],
            ['module' => 'Cheque Management', 'status' => 'Check route opens'],
            ['module' => 'ATM & Debit Cards', 'status' => 'Check route opens'],
            ['module' => 'Internet Banking', 'status' => 'Check route opens'],
        ];
    }

    public function permissionCoverage(): array
    {
        return ['super_admin', 'branch_manager', 'teller', 'credit_officer', 'auditor', 'customer_portal'];
    }

    public function releaseChecklist(): array
    {
        return [
            'All Banking sidebar links are visible for authorized users.',
            'Unauthorized users cannot see restricted Banking menu items.',
            'All Banking menu links open without 404.',
            'Toolbar is consistent on Banking list/report pages.',
            'Tester roles can complete assigned UI navigation checks.',
            'Issues are logged with module, page, role, browser, and screenshot.',
        ];
    }
}

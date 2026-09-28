<?php

return [
    'title' => 'Finance Reports',
    'icon' => 'fa fa-line-chart',
    'route' => 'finance-reports.dashboard',
    'permission' => 'finance_reports.view',
    'pages' => [
        'trial-balance-new' => ['label' => 'Trial Balance - New', 'route' => 'finance-reports.trial-balance-new'],
        'balance-sheet-new' => ['label' => 'Balance Sheet - New', 'route' => 'finance-reports.balance-sheet-new'],
        'profit-loss-new' => ['label' => 'Profit & Loss - New', 'route' => 'finance-reports.profit-loss-new'],
        'income-statement-new' => ['label' => 'Income Statement - New', 'route' => 'finance-reports.income-statement-new'],
        'cash-flow-statement-new' => ['label' => 'Cash Flow Statement - New', 'route' => 'finance-reports.cash-flow-statement-new'],
        'general-ledger-new' => ['label' => 'General Ledger - New', 'route' => 'finance-reports.general-ledger-new'],
        'account-ledger-new' => ['label' => 'Account Ledger - New', 'route' => 'finance-reports.account-ledger-new'],
        'cash-book-new' => ['label' => 'Cash Book - New', 'route' => 'finance-reports.cash-book-new'],
        'bank-book-new' => ['label' => 'Bank Book - New', 'route' => 'finance-reports.bank-book-new'],
        'journal-register-new' => ['label' => 'Journal Register - New', 'route' => 'finance-reports.journal-register-new'],
        'day-book-new' => ['label' => 'Day Book - New', 'route' => 'finance-reports.day-book-new'],
    ],
    'groups' => [
        'Executive' => ['financial-intelligence-new', 'cfo-dashboard-new', 'financial-health-score-new', 'enterprise-center', 'executive-bi-dashboard-new', 'financial-dashboard-new'],
        'Statements' => ['trial-balance-new', 'balance-sheet-new', 'profit-loss-new', 'income-statement-new', 'cash-flow-statement-new'],
        'Ledgers' => ['general-ledger-new', 'account-ledger-new', 'cash-book-new', 'bank-book-new', 'journal-register-new', 'day-book-new'],
        'Receivables & Payables' => ['customer-outstanding-new', 'supplier-outstanding-new', 'customer-aging-new', 'supplier-aging-new'],
        'Audit & Controls' => ['audit-trail-new', 'exception-report-new', 'financial-log-viewer-new'],
        'Enterprise Tools' => ['report-engine-status', 'consolidation-center-new', 'performance-center-new', 'export-center-new', 'print-layout-center-new', 'drilldown-center-new', 'standalone-audit-new'],
        'v1.0 Final' => ['financial-pack-new', 'report-scheduler-new', 'executive-kpi-center-new', 'calculation-verification-new', 'production-readiness-new'],
        'v2.0 Intelligence' => ['financial-intelligence-new', 'cfo-dashboard-new', 'financial-health-score-new', 'scenario-analysis-new', 'board-pack-new'],
        'v3.0 Enterprise' => ['enterprise-data-hub-new', 'cross-module-financial-intelligence-new', 'enterprise-dashboard-builder-new', 'enterprise-report-builder-new', 'financial-workspace-new', 'enterprise-report-scheduler-new', 'enterprise-platform-audit-new'],
    ],
];

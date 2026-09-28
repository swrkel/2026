<?php

return [
    'root' => [
        'label' => 'Banking',
        'icon' => 'fa fa-university',
        'permission' => 'banking.view',
        'route' => 'banking.tester-dashboard',
        'order' => 30,
    ],
    'groups' => [
        'core' => [
            'label' => 'Core Banking',
            'items' => [
                ['key' => 'core_deposits', 'label' => 'Core Deposits', 'route' => 'banking.placeholder', 'params' => ['module' => 'core-deposits'], 'permission' => 'banking.core_deposits.view'],
                ['key' => 'teller', 'label' => 'Teller Operations', 'route' => 'banking.placeholder', 'params' => ['module' => 'teller-operations'], 'permission' => 'banking.teller.view'],
                ['key' => 'cheques', 'label' => 'Cheque Management', 'route' => 'banking.placeholder', 'params' => ['module' => 'cheque-management'], 'permission' => 'banking.cheques.view'],
                ['key' => 'cards', 'label' => 'ATM & Debit Cards', 'route' => 'banking.placeholder', 'params' => ['module' => 'atm-debit-cards'], 'permission' => 'banking.cards.view'],
            ],
        ],
        'digital' => [
            'label' => 'Digital Banking',
            'items' => [
                ['key' => 'internet', 'label' => 'Internet Banking', 'route' => 'banking.placeholder', 'params' => ['module' => 'internet-banking'], 'permission' => 'banking.internet.view'],
                ['key' => 'mobile', 'label' => 'Mobile Banking', 'route' => 'banking.placeholder', 'params' => ['module' => 'mobile-banking'], 'permission' => 'banking.mobile.view'],
            ],
        ],
        'finance' => [
            'label' => 'Finance Company Suite',
            'items' => [
                ['key' => 'microfinance', 'label' => 'Microfinance', 'route' => 'banking.placeholder', 'params' => ['module' => 'microfinance'], 'permission' => 'banking.microfinance.view'],
                ['key' => 'insurance', 'label' => 'Insurance', 'route' => 'banking.placeholder', 'params' => ['module' => 'insurance'], 'permission' => 'banking.insurance.view'],
                ['key' => 'treasury_liquidity', 'label' => 'Treasury & Liquidity', 'route' => 'banking.placeholder', 'params' => ['module' => 'treasury-liquidity'], 'permission' => 'banking.treasury.view'],
            ],
        ],
        'enterprise' => [
            'label' => 'Enterprise Banking',
            'items' => [
                ['key' => 'corporate', 'label' => 'Corporate Banking', 'route' => 'banking.placeholder', 'params' => ['module' => 'corporate-banking'], 'permission' => 'banking.corporate.view'],
                ['key' => 'trade', 'label' => 'Trade Finance', 'route' => 'banking.placeholder', 'params' => ['module' => 'trade-finance'], 'permission' => 'banking.trade.view'],
                ['key' => 'payments', 'label' => 'Payments Hub', 'route' => 'banking.placeholder', 'params' => ['module' => 'payments-hub'], 'permission' => 'banking.payments.view'],
                ['key' => 'risk', 'label' => 'Risk Management', 'route' => 'banking.placeholder', 'params' => ['module' => 'risk-management'], 'permission' => 'banking.risk.view'],
                ['key' => 'aml', 'label' => 'AML & Compliance', 'route' => 'banking.placeholder', 'params' => ['module' => 'aml-compliance'], 'permission' => 'banking.aml.view'],
                ['key' => 'audit', 'label' => 'Enterprise Audit', 'route' => 'banking.placeholder', 'params' => ['module' => 'enterprise-audit'], 'permission' => 'banking.audit.view'],
            ],
        ],
        'testing' => [
            'label' => 'Testing & Admin',
            'items' => [
                ['key' => 'tester_dashboard', 'label' => 'Tester Dashboard', 'route' => 'banking.tester-dashboard', 'permission' => 'banking.testing.view'],
                ['key' => 'checklist', 'label' => 'Testing Checklist', 'route' => 'banking.testing-checklist', 'permission' => 'banking.testing.view'],
                ['key' => 'route_health', 'label' => 'Route Health', 'route' => 'banking.route-health', 'permission' => 'banking.testing.view'],
                ['key' => 'navigation_audit', 'label' => 'Navigation Audit', 'route' => 'banking.navigation-audit', 'permission' => 'banking.audit.view'],
            ],
        ],
    ],
];

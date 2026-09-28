<?php

return [
    'title' => 'Digital Wallet',
    'route' => 'digitalwallet.dashboard',
    'icon' => 'fa fa-wallet',
    'permission' => 'digitalwallet.view',
    'items' => [
        ['title' => 'Dashboard', 'route' => 'digitalwallet.dashboard', 'permission' => 'digitalwallet.view'],
        ['title' => 'Wallets', 'route' => 'digitalwallet.wallets.index', 'permission' => 'digitalwallet.wallets.view'],
        ['title' => 'Hierarchy', 'route' => 'digitalwallet.hierarchy.index', 'permission' => 'digitalwallet.hierarchy.view'],
        ['title' => 'Wallet Types', 'route' => 'digitalwallet.types.index', 'permission' => 'digitalwallet.types.view'],
        ['title' => 'Transfers', 'route' => 'digitalwallet.transfers.index', 'permission' => 'digitalwallet.transfers.view'],
        ['title' => 'Approvals', 'route' => 'digitalwallet.approvals.index', 'permission' => 'digitalwallet.approvals.view'],
        ['title' => 'Financial Engine', 'route' => 'digitalwallet.financial.index', 'permission' => 'digitalwallet.financial.view'],
        ['title' => 'Ledger', 'route' => 'digitalwallet.ledger.index', 'permission' => 'digitalwallet.ledger.view'],
        ['title' => 'Transactions', 'route' => 'digitalwallet.transactions.index', 'permission' => 'digitalwallet.transactions.view'],
        ['title' => 'Rules', 'route' => 'digitalwallet.rules.index', 'permission' => 'digitalwallet.rules.view'],
        ['title' => 'Reports', 'route' => 'digitalwallet.reports.index', 'permission' => 'digitalwallet.reports.view'],
        ['title' => 'Settings', 'route' => 'digitalwallet.settings.index', 'permission' => 'digitalwallet.settings.view'],
    ],
];

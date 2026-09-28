<?php

return [
    'module' => 'Banking UI Smoke Test',
    'version' => 'BKG-UI-006-RC6',
    'menus' => [
        'banking.dashboard',
        'banking.core_deposits',
        'banking.teller',
        'banking.cheques',
        'banking.atm_cards',
        'banking.microfinance',
        'banking.insurance',
        'banking.reports',
        'banking.settings',
    ],
    'routes' => [
        'banking.ui.smoke.index',
        'banking.ui.smoke.routes',
        'banking.ui.smoke.permissions',
        'banking.ui.smoke.sidebar',
        'banking.ui.smoke.release',
    ],
    'permissions' => [
        'banking.ui.view',
        'banking.ui.smoke_test',
        'banking.ui.release_readiness',
        'banking.ui.permission_matrix',
        'banking.ui.sidebar_audit',
    ],
];

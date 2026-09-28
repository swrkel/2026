<?php

return [
    'module' => 'banking_ui',
    'permissions' => [
        'banking.dashboard.view',
        'banking.deposits.view', 'banking.deposits.create', 'banking.deposits.approve', 'banking.deposits.report',
        'banking.teller.view', 'banking.teller.cash_in', 'banking.teller.cash_out', 'banking.teller.eod', 'banking.teller.approve',
        'banking.cheques.view', 'banking.cheques.issue', 'banking.cheques.stop', 'banking.cheques.clear', 'banking.cheques.report',
        'banking.cards.view', 'banking.cards.issue', 'banking.cards.hotlist', 'banking.cards.limits', 'banking.cards.report',
        'banking.internet.view', 'banking.internet.admin', 'banking.internet.security', 'banking.internet.report',
        'banking.mobile.view', 'banking.mobile.admin', 'banking.mobile.security', 'banking.mobile.report',
        'banking.microfinance.view', 'banking.microfinance.loan', 'banking.microfinance.approve', 'banking.microfinance.collection', 'banking.microfinance.report',
        'banking.insurance.view', 'banking.insurance.policy', 'banking.insurance.claim', 'banking.insurance.report',
        'banking.treasury.view', 'banking.treasury.transfer', 'banking.treasury.approve', 'banking.treasury.report',
        'banking.risk.view', 'banking.risk.case', 'banking.risk.approve', 'banking.risk.report',
        'banking.aml.view', 'banking.aml.case', 'banking.aml.approve', 'banking.aml.report',
        'banking.audit.view', 'banking.audit.export',
        'banking.settings.view', 'banking.settings.manage',
    ],
];

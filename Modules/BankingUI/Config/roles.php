<?php

return [
    'super_admin' => ['banking.*'],
    'branch_manager' => [
        'banking.dashboard.view','banking.deposits.*','banking.teller.view','banking.teller.approve','banking.cheques.*','banking.cards.view','banking.microfinance.*','banking.insurance.*','banking.treasury.view','banking.treasury.approve','banking.risk.view','banking.aml.view','banking.audit.view','banking.settings.view'
    ],
    'teller' => [
        'banking.dashboard.view','banking.deposits.view','banking.teller.view','banking.teller.cash_in','banking.teller.cash_out','banking.teller.eod','banking.cheques.view','banking.cards.view'
    ],
    'credit_officer' => [
        'banking.dashboard.view','banking.microfinance.view','banking.microfinance.loan','banking.microfinance.collection','banking.microfinance.report','banking.risk.view'
    ],
    'auditor' => [
        'banking.dashboard.view','banking.*.view','banking.*.report','banking.audit.view','banking.audit.export'
    ],
    'customer_portal' => [
        'banking.internet.view','banking.mobile.view'
    ],
];

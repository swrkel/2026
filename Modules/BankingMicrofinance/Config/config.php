<?php

return [
    'name' => 'BankingMicrofinance',
    'route_prefix' => 'banking/microfinance',
    'number_prefixes' => [
        'group' => 'MFG',
        'member' => 'MFM',
        'loan' => 'MFL',
        'receipt' => 'MFR',
    ],
    'approval_levels' => ['created', 'verified', 'approved', 'disbursed'],
];

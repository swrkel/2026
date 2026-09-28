<?php
return [
    'name' => 'BankingMicrofinanceTreasury',
    'route_prefix' => 'banking/microfinance/treasury',
    'table_prefix' => 'bkg_mfi_treasury_',
    'standalone' => true,
    'currency_precision' => 4,
    'approval_statuses' => ['draft','submitted','approved','rejected','posted','cancelled'],
];

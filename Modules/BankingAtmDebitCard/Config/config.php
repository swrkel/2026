<?php
return [
    'name' => 'BankingAtmDebitCard',
    'table_prefix' => 'bkg_card_',
    'tenant_connection' => env('TENANT_DB_CONNECTION', 'tenant'),
    'default_daily_withdrawal_limit' => 100000,
    'default_pos_limit' => 250000,
];

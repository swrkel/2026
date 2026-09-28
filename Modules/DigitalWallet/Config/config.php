<?php

return [
    'name' => 'DigitalWallet',
    'display_name' => 'Digital Wallet',
    'default_currency' => 'LKR',
    'supported_currencies' => ['LKR', 'USD', 'EUR'],
    'wallet_code_prefix' => 'DW',
    'reference_prefix' => 'DWT',
    'low_balance_threshold' => 1000,
    'allow_negative_balance' => false,
    'standalone' => true,
];

<?php

return [
    'name' => 'BankingInternetBanking',
    'display_name' => 'Internet Banking',
    'route_prefix' => 'banking/internet',
    'permission_prefix' => 'banking.internet',
    'features' => [
        'mfa' => true,
        'beneficiary_otp' => true,
        'transfer_otp' => true,
        'secure_messages' => true,
        'bill_payments' => true,
        'device_binding' => true,
    ],
];

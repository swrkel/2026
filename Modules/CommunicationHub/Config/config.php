<?php

return [
    'name' => 'CommunicationHub',
    'display_name' => 'Communication Hub',
    'default_locale' => 'en',
    'queue' => [
        'max_attempts' => 3,
        'retry_minutes' => [5, 15, 60],
        'process_limit' => 50,
    ],
    'otp' => [
        'digits' => 6,
        'expiry_minutes' => 5,
        'max_attempts' => 3,
    ],
    'wallet' => [
        'mode' => 'standalone_interface',
        'require_balance' => false,
    ],
];

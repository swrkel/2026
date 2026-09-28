<?php

return [
    'name' => 'Membership-New',
    'route_prefix' => 'membership-new',
    'permission_prefix' => 'membership_new',
    'table_prefix' => 'mn_',
    'version' => 'MEMNEW_026',
    'route_files' => [
        'web.php',
        'central_registry.php',
        'memnew_006.php',
        'memnew_007.php',
        'memnew_008.php',
        'memnew_009.php',
        'memnew_010.php',
        'memnew_011.php',
        'memnew_012.php',
        'memnew_013.php',
        'memnew_014.php',
        'memnew_016.php',
    ],
    'central_registry_mode' => env('MEMBERSHIP_NEW_CENTRAL_MODE', 'tenant'),
    'central_connection' => env('MEMBERSHIP_NEW_CENTRAL_CONNECTION', null),
    'safe_testing_mode' => env('MEMBERSHIP_NEW_SAFE_TESTING', true),
];

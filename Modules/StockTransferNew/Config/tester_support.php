<?php

return [
    'module' => 'StockTransferNew',
    'version' => '1.0.16',
    'tester_support_enabled' => true,
    'cleanup_preview_enabled' => true,
    'cleanup_requires_permission' => 'stocktransfernew.tester.cleanup',
    'test_groups' => [
        'foundation',
        'request',
        'approval',
        'dispatch',
        'receive',
        'variance',
        'return',
        'reports',
        'support',
    ],
];

<?php

return [
    'module' => 'StockTransferNew',
    'version' => '1.0.15',
    'diagnostics_enabled' => true,
    'safe_repair_enabled' => true,
    'repair_requires_permission' => 'stocktransfernew.support.repair',
    'checks' => [
        'tenant_scope',
        'business_scope',
        'store_scope',
        'product_bridge',
        'permissions',
        'routes',
        'assets',
        'sql_indexes',
    ],
];

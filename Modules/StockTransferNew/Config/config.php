<?php

return [
    'name' => 'StockTransferNew',
    'display_name' => 'Stock Transfer-New',
    'version' => '1.0.14',
    'module_code' => 'stock_transfer_new',
    'standalone' => true,
    'table_prefix' => 'stn_',
    'product_source' => 'products_new_bridge',
    'stock_scope' => [
        'business_id' => true,
        'location_id' => true,
        'store_id' => true,
    ],
    'features' => [
        'approval_workflow' => true,
        'dispatch_receive' => true,
        'barcode_qr_scan' => true,
        'returns' => true,
        'reconciliation' => true,
        'scheduling' => true,
        'reports' => true,
        'audit_security' => true,
        'performance_dashboard' => true,
        'readiness_dashboard' => true,
    ],
];

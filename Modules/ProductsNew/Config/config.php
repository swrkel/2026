<?php
return [
    'name' => 'Products New',
    'route_prefix' => 'products-new',
    'permission_prefix' => 'products_new',
    'asset_path' => 'modules/productsnew',
    'stock_decimals' => 3,
    'currency_decimals' => 4,
    'health_score' => ['image'=>10,'barcode'=>15,'category'=>10,'brand'=>5,'tax'=>10,'reorder_level'=>15,'warranty'=>5,'locations'=>15,'active_price'=>15],

    // Business Product UID V1. The UID feature is schema-detected and therefore
    // remains safe on older tenant databases where these columns do not exist.
    // Central registry synchronisation is deliberately OFF until a dedicated
    // central connection is explicitly configured; never point this at the
    // tenant-repointed default mysql connection.
    'uid' => [
        'registry_connection' => env('PRODUCTSNEW_UID_REGISTRY_CONNECTION', ''),
        'registry_product_table' => env('PRODUCTSNEW_UID_REGISTRY_PRODUCT_TABLE', 'products_new_uid_registry'),
        'registry_variation_table' => env('PRODUCTSNEW_UID_REGISTRY_VARIATION_TABLE', 'products_new_variation_uid_registry'),
        'registry_capability_table' => env('PRODUCTSNEW_UID_REGISTRY_CAPABILITY_TABLE', 'products_new_uid_tenant_capabilities'),
        'tenant_key' => env('PRODUCTSNEW_UID_TENANT_KEY', ''),
    ],
];

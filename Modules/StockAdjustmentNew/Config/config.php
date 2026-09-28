<?php

return [
    'name' => 'StockAdjustmentNew',
    'display_name' => 'Stock Adjustment - New',
    'route_prefix' => 'stock-adjustment-new',
    'table_prefix' => 'san_',
    'auto_install_schema' => true,
    'product_bridge' => [
        'enabled' => true,
        'source_module' => 'ProductsNew',
        'duplicate_product_master' => false,
    ],
    'defaults' => [
        'number_prefix' => 'SAN-',
        'number_padding' => 5,
        'default_adjustment_type' => 'quantity',
        'default_page_size' => 25,
        'require_approval' => true,
        'allow_negative_stock' => false,
        'decimal_qty' => 4,
        'decimal_amount' => 4,
    ],
];

<?php

return [
    'name' => 'Price Change - New',
    'route_prefix' => 'pricechangenew',
    'asset_version' => '9773-seq02-large-20260805',
    'currency_scale' => 8,
    'quantity_scale' => 4,
    'product_search_limit' => 25,
    'max_lines_per_draft' => 500,
    'price_compare_tolerance' => 0.00000001,
    'statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'scheduled' => 'Scheduled',
        'applied' => 'Applied',
        'partial' => 'Partially Applied',
        'cancelled' => 'Cancelled',
        'failed' => 'Failed',
    ],
    'application_scopes' => [
        'business_base' => 'Business base price (all locations)',
        'location_price_groups' => 'Selected location selling-price groups',
    ],
    'setting_defaults' => [
        'approval_required' => true,
        'allow_self_approval' => false,
        'auto_apply_on_approval' => false,
        'auto_apply_due' => false,
        'conflict_policy' => 'stop_all',
        'default_application_scope' => 'business_base',
        'default_stock_price_mode' => 'all_stock',
    ],
];

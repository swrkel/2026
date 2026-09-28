<?php

return [
    'name' => 'Leasing',
    'route_prefix' => 'leasing',
    'default_status' => 'draft',
    'workflow_statuses' => [
        'draft', 'submitted', 'under_review', 'approved', 'active', 'redeemed', 'renewed', 'insuranceed', 'closed', 'cancelled'
    ],
    'supported_lease_asset_types' => [
        'gold', 'jewellery', 'gems', 'watches', 'electronics', 'machinery', 'other'
    ],
];

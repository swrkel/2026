<?php

return [
    'name' => 'Pawning',
    'route_prefix' => 'pawning',
    'default_status' => 'draft',
    'workflow_statuses' => [
        'draft', 'submitted', 'under_review', 'approved', 'active', 'redeemed', 'renewed', 'auctioned', 'closed', 'cancelled'
    ],
    'supported_collateral_types' => [
        'gold', 'jewellery', 'gems', 'watches', 'electronics', 'machinery', 'other'
    ],
];

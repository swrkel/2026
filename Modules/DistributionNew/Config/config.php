<?php

return [
    'name' => 'DistributionNew',
    'route_prefix' => 'distribution-new',
    'table_prefix' => 'disnew_',
    'sms_events' => [
        'sales_order_created',
        'sales_order_updated',
        'invoice_created',
        'loading_completed',
        'unloading_completed',
    ],
];

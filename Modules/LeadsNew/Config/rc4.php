<?php

return [
    'stage' => 'RC4',
    'module' => 'Leads-New',
    'standalone' => true,
    'tables_prefix' => 'leads_new_',
    'default_enabled' => false,
    'features' => [
        'workflow_engine' => true,
        'customer_360' => true,
        'advanced_search' => true,
        'bulk_operations' => true,
        'notification_center' => true,
        'document_versions' => true,
        'webhooks' => true,
        'standalone_audit' => true,
    ],
];

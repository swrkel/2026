<?php
return [
    'name' => 'EzyLaw',
    'route_prefix' => 'ezylaw',
    'table_prefix' => 'law_',
    'finance_sync' => true,
    'auto_load_migrations' => env('EZYLAW_AUTO_LOAD_MIGRATIONS', false),
    'document_disk' => env('EZYLAW_DOCUMENT_DISK', 'public'),
    'document_directory' => env('EZYLAW_DOCUMENT_DIRECTORY', 'ezylaw/documents'),
    'portal_public_routes' => env('EZYLAW_PORTAL_PUBLIC_ROUTES', false),
    'notification_batch_size' => env('EZYLAW_NOTIFICATION_BATCH_SIZE', 100),
];

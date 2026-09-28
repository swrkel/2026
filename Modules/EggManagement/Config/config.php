<?php
return [
    'name' => 'Egg Management',
    // null = use the tenant/default connection already selected by the host tenancy layer.
    'connection' => env('EGG_DB_CONNECTION', null),
    'route_prefix' => 'egg-management',
    'middleware' => ['web', 'auth'],
    'timezone' => env('EGG_TIMEZONE', 'Asia/Colombo'),
    'date_format' => 'Y-m-d',
    'quantity_decimals' => 0,
    'currency_decimals' => 4,
    'fiscal_year_start_month' => 4,

    'context' => [
        'business_session_keys' => ['business.id', 'business_id', 'user.business_id'],
        'location_session_keys' => ['business.location_id', 'location_id', 'selected_location_id'],
        'store_session_keys' => ['business.store_id', 'store_id', 'selected_store_id'],
    ],

    // Common-module adapters are intentionally configurable and isolated here.
    'common' => [
        'contacts_table' => 'contacts',
        'customer_type' => 'customer',
        'supplier_type' => 'supplier',
        'products_table' => 'products',
        'locations_table' => 'business_locations',
        'stores_table' => 'stores',
    ],

    // Finance integration is safe-by-default: Egg writes an outbox event.
    // A project-specific bridge can consume the outbox without changing Egg controllers/services.
    'finance' => [
        'mode' => env('EGG_FINANCE_MODE', 'outbox'),
        'sales_event' => 'egg.sale.approved',
        'purchase_event' => 'egg.purchase.approved',
        'adjustment_event' => 'egg.adjustment.approved',
        'customer_ledger_event' => 'egg.customer.sale.approved',
        'supplier_ledger_event' => 'egg.supplier.purchase.approved',
    ],

    'sharing' => [
        'base_url' => env('APP_URL'),
        'link_expiry_hours' => 168,
        'sms_endpoint' => env('EGG_SMS_ENDPOINT'),
        'sms_token' => env('EGG_SMS_TOKEN'),
        'whatsapp_phone_prefix' => env('EGG_WHATSAPP_PHONE_PREFIX', '94'),
        'mail_from' => env('MAIL_FROM_ADDRESS'),
    ],

    'permissions' => [
        'host_can_first' => true,
        'module_grants_fallback' => true,
    ],
];

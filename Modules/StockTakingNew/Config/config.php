<?php

return [
    'name' => 'StockTakingNew',
    'display_name' => 'Stock Taking - New',
    'route_prefix' => 'stock-taking-new',
    'route_name' => 'stock-taking-new.',
    'table_prefix' => 'stk_',
    'auto_install_schema' => true,
    'asset_version' => '20260728-parcel01',
    'defaults' => [
        'number_prefix' => 'STK-',
        'quantity_decimals' => 4,
        'amount_decimals' => 4,
        'count_mode' => 'blind',
        'require_approval' => true,
        'require_recount_for_variance' => true,
        'variance_qty_threshold' => 0,
        'variance_value_threshold' => 0,
        'post_to_shared_inventory' => true,
        'share_link_expiry_hours' => 168,
    ],
    'communication' => [
        'sms_endpoint' => env('STK_SMS_ENDPOINT'),
        'sms_token' => env('STK_SMS_TOKEN'),
        'sms_sender_id' => env('STK_SMS_SENDER_ID'),
        'whatsapp_endpoint' => env('STK_WHATSAPP_ENDPOINT'),
        'whatsapp_token' => env('STK_WHATSAPP_TOKEN'),
        'whatsapp_phone_number_id' => env('STK_WHATSAPP_PHONE_NUMBER_ID'),
    ],
];

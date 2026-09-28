<?php

return [
    'name' => 'Reports - Other',
    'route_prefix' => 'reports-other',
    'route_name' => 'reports-other.',
    'receipt_document_key' => 'cash_receipt',
    'share_expiry_days' => (int) env('REO_SHARE_EXPIRY_DAYS', 7),
    'dashboard_url' => env('REO_DASHBOARD_URL', '/home'),

    // Reads existing product categories directly from the active tenant DB.
    // No ProductsNew model/controller/service is imported.
    'catalog' => [
        // ProductsNew is the current product master. Legacy `categories` is
        // retained as an automatic fallback, without importing either module.
        'table' => env('REO_CATEGORY_TABLE', 'products_new_categories'),
        'table_candidates' => array_values(array_filter(array_map('trim', explode(',', (string) env('REO_CATEGORY_TABLES', 'products_new_categories,categories'))))),
        'id_column' => env('REO_CATEGORY_ID_COLUMN', 'id'),
        'name_column' => env('REO_CATEGORY_NAME_COLUMN', 'name'),
        'parent_column' => env('REO_CATEGORY_PARENT_COLUMN', 'parent_id'),
        'business_column' => env('REO_CATEGORY_BUSINESS_COLUMN', 'business_id'),
        'active_column' => env('REO_CATEGORY_ACTIVE_COLUMN', 'is_active'),
        'type_column' => env('REO_CATEGORY_TYPE_COLUMN', 'category_type'),
        'type_value' => env('REO_CATEGORY_TYPE_VALUE', 'product'),
    ],

    // Business/location identity is read directly from tenant DB tables.
    'organisation' => [
        'business_table' => env('REO_BUSINESS_TABLE', 'business'),
        'business_id_column' => env('REO_BUSINESS_ID_COLUMN', 'id'),
        'business_name_column' => env('REO_BUSINESS_NAME_COLUMN', 'name'),
        'location_table' => env('REO_LOCATION_TABLE', 'business_locations'),
        'location_id_column' => env('REO_LOCATION_ID_COLUMN', 'id'),
        'location_business_column' => env('REO_LOCATION_BUSINESS_COLUMN', 'business_id'),
        'location_name_column' => env('REO_LOCATION_NAME_COLUMN', 'name'),
        'location_address_columns' => array_values(array_filter(array_map('trim', explode(',', (string) env('REO_LOCATION_ADDRESS_COLUMNS', 'landmark,city,state,country,zip_code'))))),
    ],

    // Currency precision is read directly from Business Settings data.
    'business_settings' => [
        'table' => env('REO_BUSINESS_TABLE', 'business'),
        'id_column' => env('REO_BUSINESS_ID_COLUMN', 'id'),
        'currency_precision_column' => env('REO_CURRENCY_PRECISION_COLUMN', 'currency_precision'),
        'fy_start_month_column' => env('REO_FY_START_MONTH_COLUMN', 'fy_start_month'),
        'fallback_precision' => (int) env('REO_CURRENCY_PRECISION_FALLBACK', 2),
        'fallback_fy_start_month' => (int) env('REO_FY_START_MONTH_FALLBACK', 1),
    ],

    // Standalone data adapter for the Receipt tab. It queries the active tenant
    // database directly; it does not import any Sales/Products/Contacts module class.
    // Defaults match common Laravel ERP/UltimatePOS-compatible table names and can
    // be overridden without changing this module.
    'receipt_source' => [
        'transactions_table' => env('REO_TX_TABLE', 'transactions'),
        'transaction_lines_table' => env('REO_TX_LINE_TABLE', 'transaction_sell_lines'),
        'products_table' => env('REO_PRODUCT_TABLE', 'products'),
        'payments_table' => env('REO_PAYMENT_TABLE', 'transaction_payments'),
        'contacts_table' => env('REO_CONTACT_TABLE', 'contacts'),

        'transaction_id_column' => env('REO_TX_ID_COLUMN', 'id'),
        'transaction_business_column' => env('REO_TX_BUSINESS_COLUMN', 'business_id'),
        'transaction_location_column' => env('REO_TX_LOCATION_COLUMN', 'location_id'),
        'transaction_store_column' => env('REO_TX_STORE_COLUMN', 'store_id'),
        'transaction_date_column' => env('REO_TX_DATE_COLUMN', 'transaction_date'),
        'transaction_type_column' => env('REO_TX_TYPE_COLUMN', 'type'),
        'transaction_status_column' => env('REO_TX_STATUS_COLUMN', 'status'),
        'transaction_contact_column' => env('REO_TX_CONTACT_COLUMN', 'contact_id'),
        'transaction_types' => array_values(array_filter(array_map('trim', explode(',', (string) env('REO_TX_TYPES', 'sell'))))),
        'transaction_statuses' => array_values(array_filter(array_map('trim', explode(',', (string) env('REO_TX_STATUSES', 'final'))))),

        'line_transaction_column' => env('REO_TX_LINE_TRANSACTION_COLUMN', 'transaction_id'),
        'line_product_column' => env('REO_TX_LINE_PRODUCT_COLUMN', 'product_id'),
        'line_quantity_column' => env('REO_TX_LINE_QTY_COLUMN', 'quantity'),
        // If this column exists, it is used as the full line amount. Leave blank
        // to calculate quantity * unit price inclusive of tax.
        'line_amount_column' => env('REO_TX_LINE_AMOUNT_COLUMN', ''),
        'line_unit_price_column' => env('REO_TX_LINE_UNIT_PRICE_COLUMN', 'unit_price_inc_tax'),

        'product_id_column' => env('REO_PRODUCT_ID_COLUMN', 'id'),
        'product_category_column' => env('REO_PRODUCT_CATEGORY_COLUMN', 'category_id'),
        'product_sub_category_column' => env('REO_PRODUCT_SUB_CATEGORY_COLUMN', 'sub_category_id'),

        'payment_id_column' => env('REO_PAYMENT_ID_COLUMN', 'id'),
        'payment_transaction_column' => env('REO_PAYMENT_TRANSACTION_COLUMN', 'transaction_id'),
        'payment_method_column' => env('REO_PAYMENT_METHOD_COLUMN', 'method'),
        'payment_cheque_number_column' => env('REO_PAYMENT_CHEQUE_NUMBER_COLUMN', 'cheque_number'),
        'payment_bank_column' => env('REO_PAYMENT_BANK_COLUMN', 'bank_name'),
        'payment_bank_fallback_column' => env('REO_PAYMENT_BANK_FALLBACK_COLUMN', 'bank_account_number'),
        'payment_cheque_date_column' => env('REO_PAYMENT_CHEQUE_DATE_COLUMN', 'cheque_date'),
        'payment_cheque_date_fallback_column' => env('REO_PAYMENT_CHEQUE_DATE_FALLBACK_COLUMN', 'paid_on'),
        'cheque_methods' => array_values(array_filter(array_map('trim', explode(',', (string) env('REO_CHEQUE_METHODS', 'cheque,check'))))),

        'contact_id_column' => env('REO_CONTACT_ID_COLUMN', 'id'),
        // Leave blank when the host system has no membership-number column. In
        // that case Receipt automatically enables manual Membership No entry.
        'contact_membership_column' => env('REO_CONTACT_MEMBERSHIP_COLUMN', 'membership_no'),
    ],

    'amount_words' => [
        'minor_unit_label' => env('REO_MINOR_UNIT_LABEL', 'Cents'),
    ],

    // Standalone generic SMS gateway. Configure only if automatic SMS sending is required.
    'sms' => [
        'endpoint' => env('REO_SMS_ENDPOINT'),
        'token' => env('REO_SMS_TOKEN'),
        'to_field' => env('REO_SMS_TO_FIELD', 'to'),
        'message_field' => env('REO_SMS_MESSAGE_FIELD', 'message'),
        'timeout' => (int) env('REO_SMS_TIMEOUT', 15),
    ],
];

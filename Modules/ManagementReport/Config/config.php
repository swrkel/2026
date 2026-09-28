<?php
return [
    'name' => 'Management Report',
    'route_prefix' => 'management-report',
    'default_link_expiry_hours' => 72,
    'default_currency_decimals' => 2,
    'max_date_range_days' => 366,
    'queue_table' => 'communication_hub_messages',
    'module_status_keys' => ['management_report_module', 'management_report', 'ManagementReport'],
    // Central/shared businesses may legitimately use the current master DB,
    // including older deployments whose hostname is not in central_domains.
    // Create only missing Management Report mgmt_* tables there on first access.
    'auto_install_central_business_tables' => true,
];

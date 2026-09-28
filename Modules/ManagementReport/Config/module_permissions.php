<?php

/*
 * Global Manage contract.
 *
 * Super Admin > All Businesses > Manage discovers this manifest automatically.
 * New keys default to enabled; the saved business/package value becomes the
 * single source of truth after an administrator changes it.
 */
return [
    [
        'key' => 'management_report_module',
        'label' => 'Management Report Module',
        'type' => 'module',
    ],
    [
        'key' => 'management_report_dashboard',
        'label' => 'Dashboard',
        'type' => 'page',
        'route_paths' => ['management-report'],
    ],
    [
        'key' => 'management_report_daily',
        'label' => 'Daily Management Report',
        'type' => 'page',
        'route_paths' => [
            'management-report/daily',
            'management-report/daily/preview',
            'management-report/daily/generate',
            'management-report/daily/{run}/print',
            'management-report/daily/{run}/pdf',
            'management-report/daily/{run}/share',
        ],
    ],
    [
        'key' => 'management_report_saved_reports',
        'label' => 'Saved Reports',
        'type' => 'page',
        'route_paths' => [
            'management-report/saved',
            'management-report/saved/{run}',
            'management-report/saved/{run}/review',
        ],
    ],
    [
        'key' => 'management_report_delivery_history',
        'label' => 'Delivery History',
        'type' => 'page',
        'route_paths' => [
            'management-report/delivery-history',
            'management-report/delivery-history/{share}/revoke',
        ],
    ],
    [
        'key' => 'management_report_settings',
        'label' => 'Settings',
        'type' => 'page',
        'route_paths' => ['management-report/settings'],
    ],
];

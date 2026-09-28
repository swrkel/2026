<?php

return [
    'name' => 'Distribution',

    /*
    |--------------------------------------------------------------------------
    | Distribution-owned route names
    |--------------------------------------------------------------------------
    |
    | These keys provide one local place for Distribution route ownership. The
    | existing route names are preserved to avoid changing working URLs.
    |
    */
    'routes' => [
        'settings' => 'distribution.settings.index',
        'sales_orders.index' => 'distribution.sales_orders.index',
        'sales_orders.create' => 'distribution.sales_orders.create',
        'sales_orders.store' => 'distribution.sales_orders.store',
        'sales_orders.show' => 'distribution.sales_orders.show',
        'sales_orders.print' => 'distribution.sales_orders.print',
        'sales_orders.payments' => 'distribution.sales_orders.payments',
        'loadings.index' => 'distribution.loadings.index',
        'loadings.create' => 'distribution.loadings.create',
        'loadings.store' => 'distribution.loadings.store',
        'loadings.show' => 'distribution.loadings.show',
        'loadings.edit' => 'distribution.loadings.edit',
        'loadings.print' => 'distribution.loadings.print',
        'invoices.index' => 'distribution.invoices.index',
        'invoices.create' => 'distribution.invoices.create',
        'invoices.print' => 'distribution.invoices.print',
        'daily_summary.index' => 'distribution.daily_summary.index',
        'daily_summary.print' => 'distribution.daily_summary.print',
    ],

    /*
    |--------------------------------------------------------------------------
    | Distribution-owned permissions
    |--------------------------------------------------------------------------
    |
    | Kept in the module for future permission registration separation. Labels
    | are intentionally simple and can be translated later through module lang.
    |
    */
    'permissions' => [
        'distribution.access' => 'Access Distribution',
        'distribution.settings' => 'Manage Distribution Settings',
        'distribution.sales_orders.view' => 'View Distribution Sales Orders',
        'distribution.sales_orders.create' => 'Create Distribution Sales Orders',
        'distribution.sales_orders.update' => 'Update Distribution Sales Orders',
        'distribution.sales_orders.delete' => 'Delete Distribution Sales Orders',
        'distribution.sales_orders.print' => 'Print Distribution Sales Orders',
        'distribution.sales_orders.payments' => 'View Distribution Sales Order Payments',
        'distribution.loadings.view' => 'View Distribution Loadings',
        'distribution.loadings.create' => 'Create Distribution Loadings',
        'distribution.loadings.update' => 'Update Distribution Loadings',
        'distribution.loadings.delete' => 'Delete Distribution Loadings',
        'distribution.loadings.print' => 'Print Distribution Loadings',
        'distribution.invoices.view' => 'View Distribution Invoices',
        'distribution.invoices.create' => 'Create Distribution Invoices',
        'distribution.invoices.update' => 'Update Distribution Invoices',
        'distribution.invoices.delete' => 'Delete Distribution Invoices',
        'distribution.invoices.print' => 'Print Distribution Invoices',
        'distribution.reports.view' => 'View Distribution Reports',
    ],

    /*
    |--------------------------------------------------------------------------
    | Distribution-owned menu map
    |--------------------------------------------------------------------------
    |
    | This is a safe ownership seam only. Existing sidebar rendering is not
    | changed by this stage.
    |
    */
    'menu' => [
        ['key' => 'distribution.settings', 'label' => 'Settings', 'route' => 'distribution.settings.index', 'enabled' => true],
        ['key' => 'distribution.sales_orders', 'label' => 'Sales Orders', 'route' => 'distribution.sales_orders.index', 'enabled' => true],
        ['key' => 'distribution.loadings', 'label' => 'Loadings', 'route' => 'distribution.loadings.index', 'enabled' => true],
        ['key' => 'distribution.invoices', 'label' => 'Invoices', 'route' => 'distribution.invoices.index', 'enabled' => true],
        ['key' => 'distribution.daily_summary', 'label' => 'Daily Summary', 'route' => 'distribution.daily_summary.index', 'enabled' => true],
    ],
];

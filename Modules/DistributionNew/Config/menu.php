<?php

return [
    'module_key' => 'distribution_new',
    'module_name' => 'Distribution New',
    'route_prefix' => 'distributionnew.',
    'icon' => 'fa fa-truck',
    'items' => [
        ['permission' => 'distributionnew.dashboard', 'title' => 'Dashboard', 'route' => 'distributionnew.dashboard', 'icon' => 'fa fa-dashboard'],
        ['permission' => 'distributionnew.sales_orders.view', 'title' => 'Sales Orders', 'route' => 'distributionnew.sales-orders.index', 'icon' => 'fa fa-file-text-o'],
        ['permission' => 'distributionnew.sales_invoices.view', 'title' => 'Sales Invoices', 'route' => 'distributionnew.sales-invoices.index', 'icon' => 'fa fa-list-alt'],
        ['permission' => 'distributionnew.loading.view', 'title' => 'Loading Plans', 'route' => 'distributionnew.loading-plans.index', 'icon' => 'fa fa-upload'],
        ['permission' => 'distributionnew.loading.view', 'title' => 'Loading', 'route' => 'distributionnew.loading.index', 'icon' => 'fa fa-truck'],
        ['permission' => 'distributionnew.unloading.view', 'title' => 'Unloading', 'route' => 'distributionnew.unloading.index', 'icon' => 'fa fa-download'],
        ['permission' => 'distributionnew.vehicles.view', 'title' => 'Vehicles', 'route' => 'distributionnew.vehicles.index', 'icon' => 'fa fa-truck'],
        ['permission' => 'distributionnew.stock.view', 'title' => 'Vehicle Stock', 'route' => 'distributionnew.vehicle-stock.index', 'icon' => 'fa fa-cubes'],
        ['permission' => 'distributionnew.routes.view', 'title' => 'Territories', 'route' => 'distributionnew.territories.index', 'icon' => 'fa fa-map'],
        ['permission' => 'distributionnew.routes.view', 'title' => 'Routes', 'route' => 'distributionnew.routes.index', 'icon' => 'fa fa-road'],
        ['permission' => 'distributionnew.sales_reps.view', 'title' => 'Sales Reps', 'route' => 'distributionnew.sales-reps.index', 'icon' => 'fa fa-user'],
        ['permission' => 'distributionnew.deliveries.view', 'title' => 'Deliveries', 'route' => 'distributionnew.deliveries.index', 'icon' => 'fa fa-check-square-o'],
        ['permission' => 'distributionnew.collections.view', 'title' => 'Collections', 'route' => 'distributionnew.collections.index', 'icon' => 'fa fa-money'],
        ['permission' => 'distributionnew.settlements.view', 'title' => 'Settlements', 'route' => 'distributionnew.settlements.index', 'icon' => 'fa fa-balance-scale'],
        ['permission' => 'distributionnew.returns.view', 'title' => 'Returns', 'route' => 'distributionnew.returns.index', 'icon' => 'fa fa-undo'],
        ['permission' => 'distributionnew.credit_notes.view', 'title' => 'Credit Notes', 'route' => 'distributionnew.credit-notes.index', 'icon' => 'fa fa-credit-card'],
        ['permission' => 'distributionnew.reports.view', 'title' => 'Reports', 'route' => 'distributionnew.reports.index', 'icon' => 'fa fa-bar-chart'],
        ['permission' => 'distributionnew.settings.view', 'title' => 'Settings', 'route' => 'distributionnew.settings.index', 'icon' => 'fa fa-cog'],
    ],
];

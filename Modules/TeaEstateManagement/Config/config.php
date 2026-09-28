<?php
return [
    'name' => 'TeaEstateManagement',
    'route_prefix' => 'tea-estate-management',
    'table_prefix' => 'tea_',
    'permissions' => [
        'access' => 'tea_estate.access',
        'dashboard' => 'tea_estate.dashboard.view',
        'plantation' => 'tea_estate.plantation.view',
        'harvests' => 'tea_estate.harvests.view',
        'parties' => 'tea_estate.parties.view',
        'buying' => 'tea_estate.buying.view',
        'processing' => 'tea_estate.processing.view',
        'inventory' => 'tea_estate.inventory.view',
        'sales' => 'tea_estate.sales.view',
        'finance' => 'tea_estate.finance.view',
        'reports' => 'tea_estate.reports.view',
        'settings' => 'tea_estate.settings.manage',
    ],
];

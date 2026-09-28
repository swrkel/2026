<?php
/**
 * Egg Management tenant sidebar menu.
 * Keep this list intentionally short and in the exact operational order.
 */
return [
    ['label'=>'Dashboard',              'route'=>'egg.dashboard',          'permission'=>'egg.dashboard.view',       'icon'=>'fa fa-dashboard'],
    ['label'=>'New Sale',               'route'=>'egg.sales.create',       'permission'=>'egg.sales.create',         'icon'=>'fa fa-shopping-cart'],
    ['label'=>'New Purchase',           'route'=>'egg.purchases.create',   'permission'=>'egg.purchases.create',     'icon'=>'fa fa-truck'],
    ['label'=>'Add Collection',         'route'=>'egg.production.create',  'permission'=>'egg.production.create',    'icon'=>'fa fa-plus-circle'],
    ['label'=>'Grade Eggs',             'route'=>'egg.grading.create',     'permission'=>'egg.grading.create',       'icon'=>'fa fa-th-large'],
    ['label'=>'Stock Transfer',         'route'=>'egg.transfers.create',   'permission'=>'egg.transfers.create',     'icon'=>'fa fa-exchange'],
    ['label'=>'Adjustment / Wastage',   'route'=>'egg.adjustments.create', 'permission'=>'egg.adjustments.create',   'icon'=>'fa fa-sliders'],
    ['label'=>'Reports',                'route'=>'egg.reports.index',       'permission'=>'egg.reports.production', 'permissions'=>[
        'egg.reports.production','egg.reports.stock','egg.reports.sales','egg.reports.purchases',
        'egg.reports.movements','egg.reports.wastage','egg.reports.audit'
    ], 'icon'=>'fa fa-bar-chart'],
    ['label'=>'Settings',               'route'=>'egg.settings.index',      'permission'=>'egg.settings.view',        'icon'=>'fa fa-cog'],
];

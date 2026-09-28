<?php
return [
    // Primary tenant/sidebar pages — keep in this exact order.
    ['module'=>'Egg Management','page'=>'Dashboard','route'=>'egg.dashboard','permission'=>'egg.dashboard.view','sidebar'=>true,'sidebar_order'=>10],
    ['module'=>'Egg Management','page'=>'New Sale','route'=>'egg.sales.create','permission'=>'egg.sales.create','sidebar'=>true,'sidebar_order'=>20],
    ['module'=>'Egg Management','page'=>'New Purchase','route'=>'egg.purchases.create','permission'=>'egg.purchases.create','sidebar'=>true,'sidebar_order'=>30],
    ['module'=>'Egg Management','page'=>'Add Collection','route'=>'egg.production.create','permission'=>'egg.production.create','sidebar'=>true,'sidebar_order'=>40],
    ['module'=>'Egg Management','page'=>'Grade Eggs','route'=>'egg.grading.create','permission'=>'egg.grading.create','sidebar'=>true,'sidebar_order'=>50],
    ['module'=>'Egg Management','page'=>'Stock Transfer','route'=>'egg.transfers.create','permission'=>'egg.transfers.create','sidebar'=>true,'sidebar_order'=>60],
    ['module'=>'Egg Management','page'=>'Adjustment / Wastage','route'=>'egg.adjustments.create','permission'=>'egg.adjustments.create','sidebar'=>true,'sidebar_order'=>70],
    ['module'=>'Egg Management','page'=>'Reports','route'=>'egg.reports.index','permission'=>'egg.reports.production','sidebar'=>true,'sidebar_order'=>80],
    ['module'=>'Egg Management','page'=>'Settings','route'=>'egg.settings.index','permission'=>'egg.settings.view','sidebar'=>true,'sidebar_order'=>90],

    // Other module pages remain permission-manageable but are not primary sidebar children.
    ['module'=>'Egg Management','page'=>'Layer Flocks','route'=>'egg.flocks.index','permission'=>'egg.flocks.view','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Daily Egg Collection - List','route'=>'egg.production.index','permission'=>'egg.production.view','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Grading & Packing - List','route'=>'egg.grading.index','permission'=>'egg.grading.view','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Egg Stock','route'=>'egg.stock.index','permission'=>'egg.stock.view','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Purchases - List','route'=>'egg.purchases.index','permission'=>'egg.purchases.view','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Sales - List','route'=>'egg.sales.index','permission'=>'egg.sales.view','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Transfers - List','route'=>'egg.transfers.index','permission'=>'egg.transfers.view','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Adjustments / Wastage - List','route'=>'egg.adjustments.index','permission'=>'egg.adjustments.view','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Reports - Production','route'=>'egg.reports.production','permission'=>'egg.reports.production','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Reports - Stock','route'=>'egg.reports.stock','permission'=>'egg.reports.stock','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Reports - Sales','route'=>'egg.reports.sales','permission'=>'egg.reports.sales','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Reports - Purchases','route'=>'egg.reports.purchases','permission'=>'egg.reports.purchases','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Reports - Movements','route'=>'egg.reports.movements','permission'=>'egg.reports.movements','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Reports - Wastage','route'=>'egg.reports.wastage','permission'=>'egg.reports.wastage','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Reports - Audit','route'=>'egg.reports.audit','permission'=>'egg.reports.audit','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Configuration - Egg Grades','route'=>'egg.grades.index','permission'=>'egg.settings.view','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Integration Outbox','route'=>'egg.integrations.index','permission'=>'egg.integrations.view','sidebar'=>false],
    ['module'=>'Egg Management','page'=>'Configuration - Products New Mapping','route'=>'egg.product-mappings.index','permission'=>'egg.product-mappings.view','sidebar'=>false],
];

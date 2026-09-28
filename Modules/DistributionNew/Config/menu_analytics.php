<?php
return [
    'distribution_new_analytics' => [
        'label' => 'Advanced Analytics',
        'permission' => 'distribution_new.analytics.view',
        'items' => [
            ['label' => 'Executive Dashboard', 'url' => '/distribution-new/analytics/executive-dashboard'],
            ['label' => 'KPI Dashboard', 'url' => '/distribution-new/analytics/kpi-dashboard'],
            ['label' => 'Scheduled Reports', 'url' => '/distribution-new/analytics/scheduled-reports'],
        ],
    ],
];

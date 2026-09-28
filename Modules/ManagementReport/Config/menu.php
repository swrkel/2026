<?php
return [
    'key' => 'management_report',
    'label' => 'Management Report',
    'icon' => 'fa fa-line-chart',
    'route' => 'managementreport.dashboard',
    'permission' => 'management_report.view',
    'order' => 840,
    'children' => [
        ['label' => 'Dashboard', 'route' => 'managementreport.dashboard', 'permission' => 'management_report.view'],
        ['label' => 'Daily Management Report', 'route' => 'managementreport.daily.index', 'permission' => 'management_report.generate'],
        ['label' => 'Saved Reports', 'route' => 'managementreport.saved.index', 'permission' => 'management_report.view_saved'],
        ['label' => 'Delivery History', 'route' => 'managementreport.shares.index', 'permission' => 'management_report.view_delivery_history'],
        ['label' => 'Settings', 'route' => 'managementreport.settings.index', 'permission' => 'management_report.settings'],
    ],
];

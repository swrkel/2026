<?php

/*
|--------------------------------------------------------------------------
| Reports - Other sidebar manifest
|--------------------------------------------------------------------------
| Module-owned metadata only. The host application's automatic sidebar /
| Manage Side Bar service remains responsible for business enable/disable
| state and user permission enforcement.
*/
return [
    'key' => 'reports_other',
    'aliases' => ['reportsother', 'reports_other_module', 'ReportsOther'],
    'module' => 'ReportsOther',
    'module_name' => 'Reports - Other',
    'label' => 'Reports - Other',
    'icon' => 'fa fa-file-text-o',
    'route' => 'reports-other.cash-receipt.index',
    'url' => '/reports-other/cash-receipt',
    'permission' => 'reports_other.view',
    'order' => 900,
    'pages' => [
        [
            'key' => 'reports_other.cash_receipt',
            'label' => 'Cash Receipt',
            'route' => 'reports-other.cash-receipt.index',
            'url' => '/reports-other/cash-receipt',
            'permission' => 'reports_other.cash_receipt.view',
            'order' => 10,
        ],
    ],
];

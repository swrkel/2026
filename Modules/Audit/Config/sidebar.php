<?php
return [
    'module' => 'Audit',
    'icon' => 'fa fa-shield',
    'permission' => 'audit.view',
    'items' => [
        ['name'=>'Dashboard','route'=>'audit.dashboard','permission'=>'audit.view'],
        ['name'=>'Run Audit','route'=>'audit.run.index','permission'=>'audit.run'],
        ['name'=>'Audit Findings','route'=>'audit.findings.index','permission'=>'audit.findings.view'],
        ['name'=>'Audit Rules','route'=>'audit.rules.index','permission'=>'audit.rules.manage'],
        ['name'=>'Audit Schedules','route'=>'audit.schedules.index','permission'=>'audit.schedules.manage'],
        ['name'=>'Reports','route'=>'audit.reports.index','permission'=>'audit.reports.view'],
    ],
];

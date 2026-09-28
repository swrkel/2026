<?php

return [
    'distribution_new_stabilization' => [
        'name' => 'Server Stabilization',
        'icon' => 'fa fa-medkit',
        'permission' => 'disnew.stabilization.view',
        'route' => 'distribution-new.stabilization.index',
        'order' => 260,
        'children' => [
            [
                'name' => 'Stabilization Dashboard',
                'permission' => 'disnew.stabilization.view',
                'route' => 'distribution-new.stabilization.index',
            ],
            [
                'name' => 'Server Checklist',
                'permission' => 'disnew.stabilization.view',
                'route' => 'distribution-new.stabilization.checklist',
            ],
        ],
    ],
];

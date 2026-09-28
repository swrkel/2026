<?php

return [
    'title' => 'Enterprise Framework',
    'icon' => 'fa fa-cubes',
    'route' => 'enterprise-framework.dashboard',
    'permission' => 'enterprise_framework.view',
    'items' => [
        ['title' => 'Framework Dashboard', 'route' => 'enterprise-framework.dashboard', 'permission' => 'enterprise_framework.dashboard'],
        ['title' => 'Report Registry', 'route' => 'enterprise-framework.registry', 'permission' => 'enterprise_framework.reports'],
        ['title' => 'Scheduled Reports', 'route' => 'enterprise-framework.schedules', 'permission' => 'enterprise_framework.schedule'],
        ['title' => 'Notification Center', 'route' => 'enterprise-framework.notifications', 'permission' => 'enterprise_framework.notifications'],
        ['title' => 'Framework Admin', 'route' => 'enterprise-framework.admin', 'permission' => 'enterprise_framework.admin'],
    ],
];

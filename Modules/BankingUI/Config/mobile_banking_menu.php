<?php

return [
    'title' => 'Mobile Banking',
    'route' => 'banking.mobile.dashboard',
    'permission' => 'banking_mobile_banking.view',
    'icon' => 'fa fa-mobile',
    'children' => [
        ['title' => 'Dashboard', 'route' => 'banking.mobile.dashboard', 'permission' => 'banking_mobile_banking.view'],
        ['title' => 'Registrations', 'route' => 'banking.mobile.registrations.index', 'permission' => 'banking_mobile_banking.register'],
        ['title' => 'Devices', 'route' => 'banking.mobile.devices.index', 'permission' => 'banking_mobile_banking.devices'],
        ['title' => 'Transfers', 'route' => 'banking.mobile.transfers.index', 'permission' => 'banking_mobile_banking.transfers'],
        ['title' => 'Beneficiaries', 'route' => 'banking.mobile.beneficiaries.index', 'permission' => 'banking_mobile_banking.beneficiaries'],
        ['title' => 'Bill Payments', 'route' => 'banking.mobile.bills.index', 'permission' => 'banking_mobile_banking.bills'],
        ['title' => 'QR Payments', 'route' => 'banking.mobile.qr.index', 'permission' => 'banking_mobile_banking.qr'],
        ['title' => 'Notifications', 'route' => 'banking.mobile.notifications.index', 'permission' => 'banking_mobile_banking.notifications'],
        ['title' => 'Reports', 'route' => 'banking.mobile.reports.index', 'permission' => 'banking_mobile_banking.reports'],
        ['title' => 'Settings', 'route' => 'banking.mobile.settings.index', 'permission' => 'banking_mobile_banking.settings'],
    ],
];

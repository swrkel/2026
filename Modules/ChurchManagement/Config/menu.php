<?php

/*
| Sidebar entries for Church Management.
|
| Declared here rather than hard-coded into a shared menu file, so removing the
| module removes its menu with it.
*/
return [
    'items' => [
        [
            'key'   => 'churchmanagement_dashboard',
            'label' => 'Dashboard',
            'icon'  => 'fa fa-dashboard',
            'route' => 'churchmanagement.dashboard',
        ],
        [
            'key'   => 'churchmanagement_members',
            'label' => 'Members',
            'icon'  => 'fa fa-users',
            'route' => 'churchmanagement.members.index',
        ],
        [
            'key'   => 'churchmanagement_families',
            'label' => 'Families',
            'icon'  => 'fa fa-home',
            'route' => 'churchmanagement.families.index',
        ],
        [
            'key'   => 'churchmanagement_donations',
            'label' => 'Donations',
            'icon'  => 'fa fa-gift',
            'route' => 'churchmanagement.donations.index',
        ],
        [
            'key'   => 'churchmanagement_attendance',
            'label' => 'Attendance',
            'icon'  => 'fa fa-check-square-o',
            'route' => 'churchmanagement.attendance.index',
        ],
        [
            'key'   => 'churchmanagement_events',
            'label' => 'Events',
            'icon'  => 'fa fa-calendar',
            'route' => 'churchmanagement.events.index',
        ],
        [
            'key'   => 'churchmanagement_settings',
            'label' => 'Settings',
            'icon'  => 'fa fa-cog',
            'route' => 'churchmanagement.settings.index',
        ],
    ],
];

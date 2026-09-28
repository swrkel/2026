<?php

/*
| Page permissions for Church Management.
|
| WHY THIS FILE EXISTS
|   The Role and Permissions screen builds its checkbox list from each module's
|   Config/module_permissions.php, and Super Admin > Manage reads the same file
|   to decide which pages a business may switch on. A module without one offers
|   no page permissions at all - every signed-in user reaches every page.
|
|   That matters more here than in most modules. The congregation roll holds
|   names, addresses, phone numbers and dates of birth. It is not data that
|   every user of a business should be able to open by typing a URL.
|
| KEYS
|   One per navigable page, derived from the module's own GET routes. There are
|   only four screens, so the list is short and stays that way as phases are
|   added - a new page means a new key here.
*/

return [
    ['key' => 'churchmanagement_dashboard', 'label' => 'Church Management Dashboard', 'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'churchmanagement_members',   'label' => 'Church Members',             'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'churchmanagement_families',  'label' => 'Church Families',            'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'churchmanagement_donations',  'label' => 'Church Donations',           'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'churchmanagement_attendance', 'label' => 'Church Attendance',          'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'churchmanagement_events',     'label' => 'Church Events',              'type' => 'page', 'source' => 'module_pages'],
    ['key' => 'churchmanagement_settings',   'label' => 'Church Management Settings', 'type' => 'page', 'source' => 'module_pages'],
];

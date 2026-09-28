<?php

return [
    'name' => 'ChurchManagement',

    /*
     | Route prefix. Changing this moves every page in the module, so it is a
     | single setting rather than a string repeated through the route file.
     */
    'route_prefix' => 'churchmanagement',

    /*
     | Table prefix. Every table this module creates starts with it, so the
     | module's data is identifiable at a glance in a database shared with a
     | hundred other modules, and can be dropped as a set.
     |
     | The module shares the existing tenant database rather than taking its own
     | connection, so this prefix is what keeps its tables apart from the
     | hundred-odd other modules living beside it.
     |
     | Controllers never write a table name directly - they call
     | ChurchTenantContext::table('members'), which prepends this. Changing the
     | value here moves the whole module, but note that it does NOT rename
     | tables that already exist: that would need an ALTER per table.
     */
    'table_prefix' => 'chc_',

    /*
     | Member codes are generated as CM-000001. Kept configurable because a
     | congregation that already numbers its members will want to match.
     */
    'member_code_prefix'  => 'CM',
    'family_code_prefix'  => 'FM',
    'receipt_code_prefix' => 'RCT',

    /*
     | Location handling.
     |
     | A congregation is usually a single site even when the business has
     | several, so members are scoped to the BUSINESS by default and a location
     | is optional on each record.
     |
     | Set this to true where a business genuinely runs separate congregations
     | per location and their rolls must not mix. It makes the location filter
     | in ChurchTenantContext apply, so each location sees only its own members.
     */
    'scope_by_location' => false,

    /*
     | Permission keys, matching Config/module_permissions.php. Held here too so
     | the route file names one constant instead of repeating strings that could
     | drift from the manifest.
     */
    'permissions' => [
        'dashboard' => 'churchmanagement_dashboard',
        'members'   => 'churchmanagement_members',
        'families'   => 'churchmanagement_families',
        'donations'  => 'churchmanagement_donations',
        'attendance' => 'churchmanagement_attendance',
        'events'     => 'churchmanagement_events',
        'settings'   => 'churchmanagement_settings',
    ],
];

<?php

return [
    'module_key' => 'RestaurantNew',
    'table_prefix' => 'restaurant_new_',
    'strict_tenant_scope' => true,
    'strict_business_scope' => true,
    'require_location_scope_for_operational_pages' => true,
    'enable_route_guard' => true,
    'enable_audit_for_write_actions' => true,
    'enable_dependency_guard' => true,
    'blocked_external_module_dependencies' => [
        'Petro', 'PetroPD', 'MPCS', 'Chequer', 'Finance', 'Contacts',
        'Customers', 'Suppliers', 'POS', 'DistributionNew', 'MembershipNew',
    ],
];

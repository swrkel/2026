<?php

return [
    ['key' => 'user_management_new_module', 'label' => 'User Management New Module', 'type' => 'module'],
    ['key' => 'user_management_new_roles', 'label' => 'Roles', 'type' => 'page'],
    ['key' => 'user_management_new_roles_create', 'label' => 'Add Role', 'type' => 'page'],
    // MA-002 (LA-1135): users. 'Add User' matches the checkbox added to the
    // Superadmin manage page, so the permission there now gates something.
    ['key' => 'user_management_new_users', 'label' => 'Users', 'type' => 'page'],
    ['key' => 'user_management_new_users_create', 'label' => 'Add User', 'type' => 'page'],
];

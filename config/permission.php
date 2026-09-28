<?php

return [

    'models' => [

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your permissions. Of course, it
         * is often just the "Permission" model but you may use whatever you like.
         *
         * The model you want to use as a Permission model needs to implement the
         * `Spatie\Permission\Contracts\Permission` contract.
         */

        'permission' => Spatie\Permission\Models\Permission::class,

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * Eloquent model should be used to retrieve your roles. Of course, it
         * is often just the "Role" model but you may use whatever you like.
         *
         * The model you want to use as a Role model needs to implement the
         * `Spatie\Permission\Contracts\Role` contract.
         */

        'role' => Spatie\Permission\Models\Role::class,

    ],

    'table_names' => [

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'roles' => 'roles',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your permissions. We have chosen a basic
         * default value but you may easily change it to any table you like.
         */

        'permissions' => 'permissions',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your models permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_permissions' => 'model_has_permissions',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your models roles. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'model_has_roles' => 'model_has_roles',

        /*
         * When using the "HasRoles" trait from this package, we need to know which
         * table should be used to retrieve your roles permissions. We have chosen a
         * basic default value but you may easily change it to any table you like.
         */

        'role_has_permissions' => 'role_has_permissions',
    ],

    /*
     * IS2114: the 'cache' section below was MISSING.
     *
     * spatie/laravel-permission 5.x reads its cache settings from
     * permission.cache.key, permission.cache.expiration_time and
     * permission.cache.store. This file still had only the version 3/4 style
     * 'cache_expiration_time' key, so it was never updated when the package was
     * upgraded.
     *
     * The consequence: config('permission.cache.key') returned NULL, so the
     * whole permission list was cached under an empty key - the same entry for
     * the central database and for EVERY tenant. Whichever database populated it
     * last was served to all the others.
     *
     * That is why an ordinary user on a tenant got the CENTRAL database's
     * permission list, which contains no 'f10_form', and why the request then
     * spun until the 900-second limit. A superadmin was unaffected because it
     * bypasses permission resolution altogether.
     *
     * 'cache_expiration_time' is left in place below: harmless, and removing it
     * would be an unnecessary change to a file that is already fragile.
     */
    'cache' => [

        /*
         * Unchanged from the previous behaviour - 24 hours, flushed immediately
         * whenever a role or permission is edited.
         */
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),

        /*
         * The key the permission list is stored under.
         *
         * This is the value that was null. A fixed name is set here so the key
         * is at least valid; making it DIFFER PER TENANT is done separately, in
         * the tenancy event listener, because tenancy is not yet initialised at
         * the moment this file is evaluated.
         */
        'key' => 'spatie.permission.cache',

        /*
         * 'default' means the store named by CACHE_DRIVER, currently 'file'.
         *
         * Note for later: the file driver does not support tags, which is why
         * Stancl's CacheTenancyBootstrapper is commented out in tenancy.php and
         * cannot simply be switched on. The per-tenant cache key achieves the
         * same separation without needing tag support.
         */
        'store' => 'default',
    ],

    /*
     * Retained for compatibility. Version 5 uses cache.expiration_time above;
     * this is read by older code paths and does no harm.
     */
    'cache_expiration_time' => 60 * 24,
];

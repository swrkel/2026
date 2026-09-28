<?php

/*
|--------------------------------------------------------------------------
| Finance Reports Tenant Routes
|--------------------------------------------------------------------------
|
| This file is intentionally kept as a tenant-route bridge for multi-tenant
| installations where module routes are loaded from tenant.php instead of the
| module RouteServiceProvider. The actual route definitions remain in web.php
| so that route names, URLs and controller mappings stay exactly the same.
|
| Do not duplicate route definitions here. Keeping one source prevents route
| drift between web.php and tenant.php.
|
*/

require __DIR__ . '/web.php';

<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Settlement SW Routes
|--------------------------------------------------------------------------
| SW_SEP_006: the module route file is now only the module shell.
| Feature routes are separated into small route files for maintainability.
*/

Route::group([
    'prefix' => 'settlement-sw',
    'middleware' => ['web', 'auth', 'language', 'SetSessionData', 'tenant.context'],
], function () {
    require __DIR__ . '/dashboard.php';
    require __DIR__ . '/payments.php';
    require __DIR__ . '/reports.php';
});

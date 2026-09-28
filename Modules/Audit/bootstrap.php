<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Audit module bootstrap
|--------------------------------------------------------------------------
|
| This file is loaded by nwidart/laravel-modules from module.json for an
| enabled Audit module. Register the Audit route file here so route loading
| does not depend on the host application's RouteServiceProvider lifecycle.
| Everything remains inside Modules/Audit.
|
*/

if (! defined('AUDIT_MODULE_BOOTSTRAP_LOADED')) {
    define('AUDIT_MODULE_BOOTSTRAP_LOADED', true);
}

$routeFile = __DIR__ . '/Routes/web.php';

if (is_file($routeFile) && ! Route::has('audit.dashboard')) {
    require $routeFile;
}

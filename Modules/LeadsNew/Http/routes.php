<?php

// Compatibility file for old module loaders. The actual route definitions live
// in Modules/LeadsNew/Routes/web.php and are normally loaded by the module
// RouteServiceProvider. This file intentionally does not define routes directly
// to avoid duplicate names and central-database route loading.

if (! defined('LEADS_NEW_HTTP_ROUTES_COMPAT_LOADED')) {
    define('LEADS_NEW_HTTP_ROUTES_COMPAT_LOADED', true);

    $routeFile = dirname(__DIR__) . '/Routes/web.php';
    if (file_exists($routeFile)) {
        require $routeFile;
    }
}

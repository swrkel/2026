<?php

/*
|--------------------------------------------------------------------------
| Chequer module fallback route loader
|--------------------------------------------------------------------------
| Some older module loaders execute start.php before service providers.
*/

if (!app()->routesAreCached() && file_exists(__DIR__ . '/Routes/web.php')) {
    require __DIR__ . '/Routes/web.php';
}

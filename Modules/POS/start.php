<?php

/*
|--------------------------------------------------------------------------
| POS Standalone Module Legacy Bootstrap
|--------------------------------------------------------------------------
| Some older Laravel module loaders execute start.php instead of module.json
| providers. This file safely loads the POS routes so /pos-module pages do
| not return 404. It only loads POS module files.
*/

if (! app()->routesAreCached() && ! app()->bound('pos.legacy_start_routes_loaded')) {
    app()->instance('pos.legacy_start_routes_loaded', true);

    foreach ([
        __DIR__ . '/Routes/web.php',
        __DIR__ . '/Routes/pos_page_003.php',
        __DIR__ . '/Routes/pos006_010.php',
        __DIR__ . '/Routes/pos016_020.php',
        __DIR__ . '/Routes/reports.php',
        __DIR__ . '/Routes/kitchen.php',
        __DIR__ . '/Routes/pagefix_v5.php',
    ] as $routeFile) {
        if (is_file($routeFile)) {
            require $routeFile;
        }
    }
}

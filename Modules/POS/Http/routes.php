<?php

/*
|--------------------------------------------------------------------------
| POS Legacy Http Route Loader
|--------------------------------------------------------------------------
| Some installations still require this file from Modules/start.php while
| newer installations register Modules\POS\Providers\RouteServiceProvider.
| Both loaders now share one application flag, so POS routes are registered
| exactly once and cannot overwrite each other with duplicate definitions.
*/

if (! app()->routesAreCached() && ! app()->bound('pos.routes.loaded')) {
    app()->instance('pos.routes.loaded', true);

    foreach ([
        __DIR__ . '/../Routes/web.php',
        __DIR__ . '/../Routes/pos_page_003.php',
        __DIR__ . '/../Routes/pos006_010.php',
        __DIR__ . '/../Routes/pos016_020.php',
        __DIR__ . '/../Routes/reports.php',
        __DIR__ . '/../Routes/kitchen.php',
        __DIR__ . '/../Routes/pagefix_v5.php',
    ] as $routeFile) {
        if (is_file($routeFile)) {
            require $routeFile;
        }
    }

    $apiPath = __DIR__ . '/../Routes/api.php';
    if (is_file($apiPath)) {
        \Illuminate\Support\Facades\Route::prefix('api')
            ->middleware('api')
            ->group($apiPath);
    }
}

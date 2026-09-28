<?php

/*
|--------------------------------------------------------------------------
| Register Namespaces and Routes
|--------------------------------------------------------------------------
|
| When your module starts, this file is executed automatically. By default
| it will only load the module's route file. However, you can expand on
| it to load anything else from the module, such as a class or view.
|
*/

// Compatibility bootstrap for installations that load module files directly.
// The service provider also registers the routes, so guard by route name to
// prevent duplicate registration regardless of bootstrap order or route cache.
if (!app('router')->has('subscription.estate.index')) {
    require __DIR__ . '/Http/routes.php';
}

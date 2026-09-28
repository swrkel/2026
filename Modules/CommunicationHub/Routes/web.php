<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Communication Hub compatibility and central web routes
|--------------------------------------------------------------------------
| Operational pages remain in Routes/tenant.php. These redirects keep older
| sidebar records, bookmarks and historical URL variants from returning 404.
*/

Route::middleware('auth')->group(function () {
    Route::get('communication-hub-central', [\Modules\CommunicationHub\Http\Controllers\RouteClosures\WebRouteController::class, 'handle1'])->name('communicationhub.central.index');

    $dashboardAliases = [
        'communicationhub',
        'communicationhub/dashboard',
        'communication_hub',
        'communication_hub/dashboard',
        'communication-hub/dashboard',
        'communication-hub/home',
        'communication-hub/index',
        'communications-hub',
    ];

    /*
     * MA-002: the closure here used a VARIABLE uri inside a foreach, so it was
     * not matched by the straightforward conversion. Same treatment though -
     * a closure anywhere in the route files blocks php artisan route:cache,
     * and with 8,613 routes that cache is the difference between a page
     * loading instantly and taking seconds.
     *
     * Laravel has a built-in redirect route for exactly this, which is
     * cacheable because there is no closure to serialise.
     */
    foreach ($dashboardAliases as $index => $uri) {
        Route::redirect($uri, '/communication-hub')
            ->name('communicationhub.compat.dashboard.' . $index);
    }
});

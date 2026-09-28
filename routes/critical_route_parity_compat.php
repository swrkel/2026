<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Critical route parity compatibility
|--------------------------------------------------------------------------
|
| These routes existed in the previous compiled route cache and are referenced
| by live views, but were missing from source-based route registration.
| Guard every name so this file is safe when included from web.php and tenant.php.
|
*/

    Route::middleware(['web'])
        ->post('/vehicle/store', [\App\Http\Controllers\VehicleController::class, 'store'])
        ->name('vehicle.store');

/*
 * MA-002: Action App\Http\Controllers\AccountController@imageModal not defined.
 * Seen in laravel-2026-08-04.log at 13:30:46.
 *
 * The METHOD exists - app/Http/Controllers/AccountController.php line 3727.
 * What is missing is a ROUTE pointing at it. Laravel's action() helper resolves
 * a controller action by searching the registered routes, so with no route the
 * call throws even though the method is perfectly healthy.
 *
 * Four live callers build links with action('AccountController@imageModal'),
 * which resolves to the CORE controller:
 *     Modules/Customers/Http/Controllers/CustomerStandaloneStatementLogoController.php:82
 *     Modules/Fleet/Http/Controllers/FleetLogoController.php:80
 *     Modules/Finance/Http/Controllers/AccountController.php:4844 and :4890
 *
 * Modules/Finance/Routes/accounting_module.php:32 registers /account/image-modal,
 * but against the FINANCE controller, so it does not satisfy these callers.
 *
 * Registered here rather than in web.php because this file is the established
 * home for exactly this problem, and the guard keeps it safe to include twice.
 */
if (! Route::has('account.image.modal')) {
    Route::middleware(['web'])
        ->get('/account/image-modal', [\App\Http\Controllers\AccountController::class, 'imageModal'])
        ->name('account.image.modal');
}


<?php
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;
use Modules\HotelManagement\Http\Controllers\Api\V1\GuestApiController;

/*
| IS2105: tenancy middleware applied only on a TENANT domain.
| See Modules/MPCS/Http/routes.php for the full reasoning. In short: on a
| single-database install the host IS the central domain, so
| PreventAccessFromCentralDomains refused every request and every page in this
| group returned 404.
*/
$hmHost = strtolower((string) request()->getHost());
$hmCentral = array_map('strtolower', (array) config('tenancy.central_domains', []));
$hmIsCentralDomain = in_array($hmHost, $hmCentral, true);


Route::middleware([
    ...($hmIsCentralDomain ? [] : [InitializeTenancyByDomain::class]),
    ...($hmIsCentralDomain ? [] : [PreventAccessFromCentralDomains::class]),
])->prefix('v1/hotel-management')->name('api.hotel-management.')->group(function () {
    Route::post('/login', [GuestApiController::class, 'login'])->name('login');
    Route::get('/branches', [GuestApiController::class, 'branches'])->name('branches');
    Route::get('/room-types', [GuestApiController::class, 'roomTypes'])->name('room-types');
    Route::get('/availability', [GuestApiController::class, 'availability'])->name('availability');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/profile', [GuestApiController::class, 'profile'])->name('profile');
        Route::get('/reservations', [GuestApiController::class, 'reservations'])->name('reservations');
        Route::post('/reservations', [GuestApiController::class, 'storeReservation'])->name('reservations.store');
        Route::get('/folios', [GuestApiController::class, 'folios'])->name('folios');
        Route::get('/notifications', [GuestApiController::class, 'notifications'])->name('notifications');
    });
});

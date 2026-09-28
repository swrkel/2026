<?php
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;
use Modules\HotelManagement\Http\Controllers\Portal\GuestPortalController;

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
    'web',
    ...($hmIsCentralDomain ? [] : [InitializeTenancyByDomain::class]),
    ...($hmIsCentralDomain ? [] : [PreventAccessFromCentralDomains::class]),
    ...($hmIsCentralDomain ? [] : [ScopeSessions::class]),
    'tenant.context',
])->prefix('hotel-guest')->name('hotel-management.portal.')->group(function () {
    Route::get('/login', [GuestPortalController::class, 'login'])->name('login');
    Route::post('/login', [GuestPortalController::class, 'authenticate'])->name('authenticate');
    Route::middleware(['auth'])->group(function () {
        Route::get('/', [GuestPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/reservations', [GuestPortalController::class, 'reservations'])->name('reservations');
        Route::get('/profile', [GuestPortalController::class, 'profile'])->name('profile');
        Route::get('/folios', [GuestPortalController::class, 'folios'])->name('folios');
        Route::get('/requests', [GuestPortalController::class, 'requests'])->name('requests');
    });
});

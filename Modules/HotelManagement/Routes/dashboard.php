<?php
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;
use Modules\HotelManagement\Http\Controllers\Dashboard\ExecutiveDashboardController;

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
    'auth',
    'SetSessionData',
    'language',
    'timezone',
    'tenant.context',
    'check.route.permission',
])->prefix('hotel-management/dashboards')->name('hotel-management.dashboards.')->group(function () {
    Route::get('/', [ExecutiveDashboardController::class, 'owner'])->name('owner');
    Route::get('/manager', [ExecutiveDashboardController::class, 'manager'])->name('manager');
    Route::get('/front-office', [ExecutiveDashboardController::class, 'frontOffice'])->name('front-office');
    Route::get('/housekeeping', [ExecutiveDashboardController::class, 'housekeeping'])->name('housekeeping');
    Route::get('/finance', [ExecutiveDashboardController::class, 'finance'])->name('finance');
});

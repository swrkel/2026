<?php
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;
use Modules\HotelManagement\Http\Controllers\Reports\HotelReportController;

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
])->prefix('hotel-management/reports')->name('hotel-management.reports.')->group(function () {
    Route::get('/', [HotelReportController::class, 'index'])->name('index');
    Route::get('/occupancy', [HotelReportController::class, 'occupancy'])->name('occupancy');
    Route::get('/revenue', [HotelReportController::class, 'revenue'])->name('revenue');
    Route::get('/reservation-register', [HotelReportController::class, 'reservationRegister'])->name('reservation-register');
    Route::get('/checkin-checkout', [HotelReportController::class, 'checkinCheckout'])->name('checkin-checkout');
    Route::get('/housekeeping', [HotelReportController::class, 'housekeeping'])->name('housekeeping');
    Route::get('/guest-ledger', [HotelReportController::class, 'guestLedger'])->name('guest-ledger');
    Route::get('/room-revenue', [HotelReportController::class, 'roomRevenue'])->name('room-revenue');
    Route::get('/analytics', [HotelReportController::class, 'analytics'])->name('analytics');
    Route::post('/analytics/snapshot', [HotelReportController::class, 'snapshot'])->name('analytics.snapshot');
});

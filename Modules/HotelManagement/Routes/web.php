<?php
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Stancl\Tenancy\Middleware\ScopeSessions;
use Modules\HotelManagement\Http\Controllers\DashboardController;
use Modules\HotelManagement\Http\Controllers\HotelSetupController;
use Modules\HotelManagement\Http\Controllers\RoomController;
use Modules\HotelManagement\Http\Controllers\RatePlanController;
use Modules\HotelManagement\Http\Controllers\ReservationController;
use Modules\HotelManagement\Http\Controllers\FrontOfficeController;
use Modules\HotelManagement\Http\Controllers\HousekeepingController;
use Modules\HotelManagement\Http\Controllers\BillingController;
use Modules\HotelManagement\Http\Controllers\HotelPosController;
use Modules\HotelManagement\Http\Controllers\RoomServiceController;
use Modules\HotelManagement\Http\Controllers\HotelInventoryController;
use Modules\HotelManagement\Http\Controllers\GuestCrmController;
use Modules\HotelManagement\Http\Controllers\MaintenanceController;
use Modules\HotelManagement\Http\Controllers\BanquetEventController;
use Modules\HotelManagement\Http\Controllers\ConferenceHallController;
use Modules\HotelManagement\Http\Controllers\NightAuditController;
use Modules\HotelManagement\Http\Controllers\SystemCheckController;

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
])->prefix('hotel-management')->name('hotel-management.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('system-check', [SystemCheckController::class, 'index'])->name('system-check.index');
    Route::resource('hotels', HotelSetupController::class)->only(['index','store','update','destroy']);
    Route::resource('rooms', RoomController::class)->only(['index','store','update','destroy']);
    Route::resource('rate-plans', RatePlanController::class)->only(['index','store','update','destroy']);
    Route::resource('reservations', ReservationController::class)->only(['index','store','update','destroy']);

    Route::get('front-office', [FrontOfficeController::class, 'index'])->name('front-office.index');
    Route::post('front-office/check-in', [FrontOfficeController::class, 'checkIn'])->name('front-office.check-in');
    Route::post('front-office/check-out', [FrontOfficeController::class, 'checkOut'])->name('front-office.check-out');

    Route::get('housekeeping', [HousekeepingController::class, 'index'])->name('housekeeping.index');
    Route::post('housekeeping', [HousekeepingController::class, 'store'])->name('housekeeping.store');
    Route::put('housekeeping/{id}', [HousekeepingController::class, 'update'])->name('housekeeping.update');
    Route::delete('housekeeping/{id}', [HousekeepingController::class, 'destroy'])->name('housekeeping.destroy');
    Route::post('housekeeping/schedules', [HousekeepingController::class, 'scheduleStore'])->name('housekeeping.schedules.store');
    Route::post('housekeeping/schedules/{id}/status', [HousekeepingController::class, 'scheduleStatus'])->name('housekeeping.schedules.status');
    Route::post('housekeeping/lost-found', [HousekeepingController::class, 'lostFoundStore'])->name('housekeeping.lost-found.store');
    Route::post('housekeeping/lost-found/{id}/claim', [HousekeepingController::class, 'lostFoundClaim'])->name('housekeeping.lost-found.claim');
    Route::post('housekeeping/linen', [HousekeepingController::class, 'linenStore'])->name('housekeeping.linen.store');


    Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::post('maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::put('maintenance/{id}', [MaintenanceController::class, 'update'])->name('maintenance.update');
    Route::delete('maintenance/{id}', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');

    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('billing', [BillingController::class, 'store'])->name('billing.store');
    Route::put('billing/{id}', [BillingController::class, 'update'])->name('billing.update');
    Route::delete('billing/{id}', [BillingController::class, 'destroy'])->name('billing.destroy');
    Route::post('billing/{id}/charge', [BillingController::class, 'postCharge'])->name('billing.charge');
    Route::post('billing/{id}/payment', [BillingController::class, 'postPayment'])->name('billing.payment');
    Route::post('billing/{id}/close', [BillingController::class, 'close'])->name('billing.close');

    Route::get('pos', [HotelPosController::class, 'index'])->name('pos.index');

    Route::get('banquets', [BanquetEventController::class, 'index'])->name('banquets.index');
    Route::post('banquets/halls', [BanquetEventController::class, 'storeHall'])->name('banquets.halls.store');
    Route::put('banquets/halls/{id}', [BanquetEventController::class, 'updateHall'])->name('banquets.halls.update');
    Route::post('banquets/events', [BanquetEventController::class, 'storeEvent'])->name('banquets.events.store');
    Route::post('banquets/events/{id}/status', [BanquetEventController::class, 'status'])->name('banquets.events.status');


    Route::get('conference', [ConferenceHallController::class, 'index'])->name('conference.index');
    Route::post('conference/rooms', [ConferenceHallController::class, 'storeRoom'])->name('conference.rooms.store');
    Route::put('conference/rooms/{id}', [ConferenceHallController::class, 'updateRoom'])->name('conference.rooms.update');
    Route::post('conference/bookings', [ConferenceHallController::class, 'storeBooking'])->name('conference.bookings.store');
    Route::post('conference/bookings/{id}/status', [ConferenceHallController::class, 'status'])->name('conference.bookings.status');


    Route::get('night-audit', [NightAuditController::class, 'index'])->name('night-audit.index');
    Route::post('night-audit/run', [NightAuditController::class, 'run'])->name('night-audit.run');
    Route::post('night-audit/{id}/close', [NightAuditController::class, 'close'])->name('night-audit.close');
    Route::post('night-audit/{id}/reopen', [NightAuditController::class, 'reopen'])->name('night-audit.reopen');

    Route::get('room-service', [RoomServiceController::class, 'index'])->name('room-service.index');
    Route::post('room-service', [RoomServiceController::class, 'store'])->name('room-service.store');
    Route::post('room-service/{id}/status', [RoomServiceController::class, 'status'])->name('room-service.status');
    Route::post('pos', [HotelPosController::class, 'store'])->name('pos.store');
    Route::put('pos/{id}', [HotelPosController::class, 'update'])->name('pos.update');
    Route::delete('pos/{id}', [HotelPosController::class, 'destroy'])->name('pos.destroy');

    Route::get('inventory', [HotelInventoryController::class, 'index'])->name('inventory.index');
    Route::post('inventory', [HotelInventoryController::class, 'store'])->name('inventory.store');
    Route::put('inventory/{id}', [HotelInventoryController::class, 'update'])->name('inventory.update');
    Route::delete('inventory/{id}', [HotelInventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::post('inventory/movement', [HotelInventoryController::class, 'movement'])->name('inventory.movement');

    Route::get('guest-crm', [GuestCrmController::class, 'index'])->name('crm.index');
    Route::post('guest-crm', [GuestCrmController::class, 'store'])->name('crm.store');
    Route::put('guest-crm/{id}', [GuestCrmController::class, 'update'])->name('crm.update');
    Route::delete('guest-crm/{id}', [GuestCrmController::class, 'destroy'])->name('crm.destroy');
    Route::post('guest-crm/{guest}/preferences', [GuestCrmController::class, 'storePreference'])->name('crm.preferences.store');
    Route::delete('guest-crm/{guest}/preferences/{id}', [GuestCrmController::class, 'deletePreference'])->name('crm.preferences.destroy');
    Route::post('guest-crm/{guest}/notes', [GuestCrmController::class, 'storeNote'])->name('crm.notes.store');
    Route::delete('guest-crm/{guest}/notes/{id}', [GuestCrmController::class, 'deleteNote'])->name('crm.notes.destroy');
});

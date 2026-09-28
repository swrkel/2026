<?php

use Illuminate\Support\Facades\Route;
use Modules\ChurchManagement\Http\Controllers\AttendanceController;
use Modules\ChurchManagement\Http\Controllers\DashboardController;
use Modules\ChurchManagement\Http\Controllers\DonationController;
use Modules\ChurchManagement\Http\Controllers\EventController;
use Modules\ChurchManagement\Http\Controllers\FamilyController;
use Modules\ChurchManagement\Http\Controllers\MemberController;
use Modules\ChurchManagement\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| Church Management
|--------------------------------------------------------------------------
|
| The prefix comes from config so the whole module can be moved with one
| setting rather than a string repeated on every line.
|
| Middleware is 'web' and 'auth', plus a page permission on each screen.
|
| WHY THE PERMISSIONS MATTER HERE
|   The congregation roll holds names, addresses, phone numbers and dates of
|   birth. Gating on 'auth' alone would let any signed-in user of the business
|   open it by typing a URL. Each page therefore requires its own key from
|   Config/module_permissions.php, which is what the Role screen and
|   Super Admin > Manage build their checkboxes from.
|
|   'can:' is Laravel's own gate, so this works with the spatie/laravel-permission
|   setup already in the application without the module knowing anything about
|   it. Write actions carry the permission of the page they belong to: a user who
|   may not see the roll must not be able to post to it either.
|
| The keys are read from config rather than typed here, so they cannot drift
| from the manifest that declares them.
|
| Every id-bound route is constrained with whereNumber, so a literal segment
| added later cannot be swallowed by the {id} placeholder.
*/

$chcPrefix = config('churchmanagement.route_prefix', 'church-management');
$chcPerm   = config('churchmanagement.permissions', []);

/*
| Backward-compatible URL alias.
|
| Older builds/documentation used /church-management while the application
| sidebar and module alias use /churchmanagement. Keep the former working so
| existing bookmarks do not become a new 404 after correcting the canonical
| module URL. GET requests are redirected to the canonical prefix and preserve
| the remaining path and query string.
*/
if (trim($chcPrefix, '/') !== 'church-management') {
    Route::get('church-management/{path?}', function (?string $path = null) use ($chcPrefix) {
        $target = '/' . trim($chcPrefix, '/');

        if ($path !== null && $path !== '') {
            $target .= '/' . ltrim($path, '/');
        }

        if (request()->getQueryString()) {
            $target .= '?' . request()->getQueryString();
        }

        return redirect($target);
    })
        ->where('path', '.*')
        ->middleware(['web', 'auth']);
}

/*
| Falls back to the key's own name when config is unavailable for any reason,
| so a page is never accidentally left ungated by a missing config value.
*/
$chcCan = function (string $page) use ($chcPerm): string {
    return 'can:' . ($chcPerm[$page] ?? 'churchmanagement_' . $page);
};

Route::group([
    'prefix'     => $chcPrefix,
    'middleware' => ['web', 'auth'],
], function () use ($chcCan) {

    Route::get('/', [DashboardController::class, 'index'])
        ->middleware($chcCan('dashboard'))
        ->name('churchmanagement.dashboard');

    /*
    | Members
    */
    Route::get('/members', [MemberController::class, 'index'])
        ->middleware($chcCan('members'))
        ->name('churchmanagement.members.index');

    Route::post('/members', [MemberController::class, 'store'])
        ->middleware($chcCan('members'))
        ->name('churchmanagement.members.store');

    Route::put('/members/{id}', [MemberController::class, 'update'])
        ->whereNumber('id')
        ->middleware($chcCan('members'))
        ->name('churchmanagement.members.update');

    Route::delete('/members/{id}', [MemberController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware($chcCan('members'))
        ->name('churchmanagement.members.destroy');

    /*
    | Families
    */
    Route::get('/families', [FamilyController::class, 'index'])
        ->middleware($chcCan('families'))
        ->name('churchmanagement.families.index');

    Route::post('/families', [FamilyController::class, 'store'])
        ->middleware($chcCan('families'))
        ->name('churchmanagement.families.store');

    Route::put('/families/{id}', [FamilyController::class, 'update'])
        ->whereNumber('id')
        ->middleware($chcCan('families'))
        ->name('churchmanagement.families.update');

    Route::delete('/families/{id}', [FamilyController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware($chcCan('families'))
        ->name('churchmanagement.families.destroy');


    /*
    | Donations
    */
    Route::get('/donations', [DonationController::class, 'index'])
        ->middleware($chcCan('donations'))
        ->name('churchmanagement.donations.index');

    Route::post('/donations', [DonationController::class, 'store'])
        ->middleware($chcCan('donations'))
        ->name('churchmanagement.donations.store');

    Route::put('/donations/{id}', [DonationController::class, 'update'])
        ->whereNumber('id')
        ->middleware($chcCan('donations'))
        ->name('churchmanagement.donations.update');

    Route::delete('/donations/{id}', [DonationController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware($chcCan('donations'))
        ->name('churchmanagement.donations.destroy');

    // Registered BEFORE /donations/{id} would matter if that route were a GET;
    // it is a POST to a literal segment, so there is no collision. Kept here
    // beside the donations group for readability.
    Route::post('/donation-types', [DonationController::class, 'storeType'])
        ->middleware($chcCan('donations'))
        ->name('churchmanagement.donation_types.store');

    /*
    | Attendance — services and their registers
    */
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->middleware($chcCan('attendance'))
        ->name('churchmanagement.attendance.index');

    Route::post('/attendance/services', [AttendanceController::class, 'storeService'])
        ->middleware($chcCan('attendance'))
        ->name('churchmanagement.attendance.services.store');

    Route::put('/attendance/services/{id}', [AttendanceController::class, 'updateService'])
        ->whereNumber('id')
        ->middleware($chcCan('attendance'))
        ->name('churchmanagement.attendance.services.update');

    Route::delete('/attendance/services/{id}', [AttendanceController::class, 'destroyService'])
        ->whereNumber('id')
        ->middleware($chcCan('attendance'))
        ->name('churchmanagement.attendance.services.destroy');

    Route::post('/attendance/services/{id}/register', [AttendanceController::class, 'saveRegister'])
        ->whereNumber('id')
        ->middleware($chcCan('attendance'))
        ->name('churchmanagement.attendance.register.save');

    /*
    | Events
    */
    Route::get('/events', [EventController::class, 'index'])
        ->middleware($chcCan('events'))
        ->name('churchmanagement.events.index');

    Route::post('/events', [EventController::class, 'store'])
        ->middleware($chcCan('events'))
        ->name('churchmanagement.events.store');

    Route::put('/events/{id}', [EventController::class, 'update'])
        ->whereNumber('id')
        ->middleware($chcCan('events'))
        ->name('churchmanagement.events.update');

    Route::delete('/events/{id}', [EventController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware($chcCan('events'))
        ->name('churchmanagement.events.destroy');

    /*
    | Settings
    */
    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware($chcCan('settings'))
        ->name('churchmanagement.settings.index');

    Route::put('/settings', [SettingsController::class, 'update'])
        ->middleware($chcCan('settings'))
        ->name('churchmanagement.settings.update');
});

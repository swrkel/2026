<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Portal\MyHealthMemberPortalController;
use Modules\MyHealthMembers\Http\Middleware\MyHealthMemberPortalAuth;

/*
|--------------------------------------------------------------------------
| My Health Member Portal Compatibility Routes
|--------------------------------------------------------------------------
| Canonical member portal URLs live under /my-health-member/portal in
| Routes/public.php. These /myhealth/portal routes are kept to prevent 404
| errors from older buttons/bookmarks and redirect users into the same secure
| member-session protected portal.
*/

Route::prefix('myhealth/portal')
    ->as('myhealth.portal.')
    ->middleware(MyHealthMemberPortalAuth::class)
    ->group(function () {
        Route::get('/', [MyHealthMemberPortalController::class, 'dashboard'])->name('dashboard');
        Route::get('/profile', [MyHealthMemberPortalController::class, 'profile'])->name('profile');
        Route::get('/history', [MyHealthMemberPortalController::class, 'history'])->name('history');
        Route::get('/prescriptions', [MyHealthMemberPortalController::class, 'prescriptions'])->name('prescriptions');
        Route::get('/laboratory', [MyHealthMemberPortalController::class, 'labs'])->name('labs');
        Route::get('/radiology', [MyHealthMemberPortalController::class, 'radiology'])->name('radiology');
        Route::get('/vaccinations', [MyHealthMemberPortalController::class, 'vaccinations'])->name('vaccinations');
        Route::get('/billing', [MyHealthMemberPortalController::class, 'billing'])->name('billing');
        Route::get('/appointments', [MyHealthMemberPortalController::class, 'appointments'])->name('appointments');
        Route::get('/documents', [MyHealthMemberPortalController::class, 'documents'])->name('documents');
        Route::get('/timeline', [MyHealthMemberPortalController::class, 'timeline'])->name('timeline');
        Route::get('/notifications', [MyHealthMemberPortalController::class, 'notifications'])->name('notifications');
        Route::get('/settings', [MyHealthMemberPortalController::class, 'settings'])->name('settings');
    });

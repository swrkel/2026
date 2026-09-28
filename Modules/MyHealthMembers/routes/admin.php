<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Admin\MyHealthAdministrationController;
use Modules\MyHealthMembers\Http\Controllers\Admin\MyHealthDoctorAdminController;
use Modules\MyHealthMembers\Http\Controllers\Admin\MyHealthBusinessAdminController;
use Modules\MyHealthMembers\Http\Controllers\Admin\MyHealthPermissionMatrixController;
use Modules\MyHealthMembers\Http\Controllers\Admin\MyHealthSystemSettingsController;
use Modules\MyHealthMembers\Http\Controllers\Admin\MyHealthQrManagerController;
use Modules\MyHealthMembers\Http\Controllers\Admin\MyHealthNotificationCentreController;

Route::prefix('myhealth/admin')->name('myhealth.admin.')->group(function () {
    Route::get('/', [MyHealthAdministrationController::class, 'index'])->name('dashboard');

    Route::get('/doctors', [MyHealthDoctorAdminController::class, 'index'])->name('doctors.index');
    Route::get('/doctors/{doctor}', [MyHealthDoctorAdminController::class, 'show'])->name('doctors.show');

    Route::get('/businesses', [MyHealthBusinessAdminController::class, 'index'])->name('businesses.index');
    Route::get('/businesses/{business}', [MyHealthBusinessAdminController::class, 'show'])->name('businesses.show');

    Route::get('/permission-matrix', [MyHealthPermissionMatrixController::class, 'index'])->name('permissions.index');
    Route::post('/permission-matrix', [MyHealthPermissionMatrixController::class, 'store'])->name('permissions.store');

    Route::get('/system-settings', [MyHealthSystemSettingsController::class, 'index'])->name('settings.index');
    Route::post('/system-settings', [MyHealthSystemSettingsController::class, 'store'])->name('settings.store');

    Route::get('/qr-manager', [MyHealthQrManagerController::class, 'index'])->name('qr.index');
    Route::post('/qr-manager/{member}/reissue', [MyHealthQrManagerController::class, 'reissue'])->name('qr.reissue');
    Route::post('/qr-manager/{member}/revoke', [MyHealthQrManagerController::class, 'revoke'])->name('qr.revoke');

    Route::get('/notification-centre', [MyHealthNotificationCentreController::class, 'index'])->name('notifications.index');
    Route::post('/notification-centre/templates', [MyHealthNotificationCentreController::class, 'storeTemplate'])->name('notifications.templates.store');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Admin\SuperAdminController;

Route::prefix('restaurant-new/admin')->as('restaurantnew.admin.')->middleware(['web','auth'])->group(function () {
    Route::get('/features', [SuperAdminController::class, 'features'])->name('features');
    Route::post('/features', [SuperAdminController::class, 'saveFeature'])->name('features.save');
    Route::get('/user-access', [SuperAdminController::class, 'userAccess'])->name('user-access');
    Route::post('/user-access', [SuperAdminController::class, 'saveUserAccess'])->name('user-access.save');
    Route::get('/audit-logs', [SuperAdminController::class, 'auditLogs'])->name('audit-logs');
    Route::get('/blocked-urls', [SuperAdminController::class, 'blockedUrls'])->name('blocked-urls');
});

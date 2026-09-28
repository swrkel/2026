<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Dashboard\MyHealthDashboardController;
use Modules\MyHealthMembers\Http\Controllers\Superadmin\MyHealthBusinessPermissionController;

Route::prefix('myhealth')->as('myhealth.')->group(function () {
    Route::get('/', [MyHealthDashboardController::class, 'index'])->name('dashboard');

    Route::get('/superadmin/business-permissions', [MyHealthBusinessPermissionController::class, 'index'])->name('superadmin.permissions.index');
    Route::get('/superadmin/business-permissions/{business_id}/edit', [MyHealthBusinessPermissionController::class, 'edit'])->name('superadmin.permissions.edit');
    Route::post('/superadmin/business-permissions/{business_id}', [MyHealthBusinessPermissionController::class, 'update'])->name('superadmin.permissions.update');
});

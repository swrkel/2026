<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\Dashboard\MyHealthDashboardController;
use Modules\MyHealthMembers\Http\Controllers\Superadmin\MyHealthBusinessPermissionController;
use Modules\MyHealthMembers\Http\Controllers\Public\MemberSelfRegistrationController;

Route::prefix('myhealth')->as('myhealth.')->group(function () {
    Route::get('/', [MyHealthDashboardController::class, 'index'])->name('dashboard');

    Route::get('/superadmin/business-permissions', [MyHealthBusinessPermissionController::class, 'index'])->name('superadmin.permissions.index');
    Route::get('/superadmin/business-permissions/{business_id}/edit', [MyHealthBusinessPermissionController::class, 'edit'])->name('superadmin.permissions.edit');
    Route::post('/superadmin/business-permissions/{business_id}', [MyHealthBusinessPermissionController::class, 'update'])->name('superadmin.permissions.update');
});


/*
|--------------------------------------------------------------------------
| My Health Public Registration Compatibility Route
|--------------------------------------------------------------------------
| Some ERP login/signup views submit to route('myhealth-register').
| Keeping this alias in web.php guarantees the route is available even when
| the module public.php route file is not loaded by the host application.
*/
Route::post('/myhealth-register', [MemberSelfRegistrationController::class, 'store'])
    ->name('myhealth-register');
Route::get('/myhealth-register', [MemberSelfRegistrationController::class, 'create'])
    ->name('myhealth-register.create');

<?php

use Illuminate\Support\Facades\Route;
use Modules\EnterpriseFramework\Http\Controllers\EnterpriseFrameworkController;

Route::get('/', [EnterpriseFrameworkController::class, 'dashboard'])->name('dashboard');
Route::get('/registry', [EnterpriseFrameworkController::class, 'registry'])->name('registry');
Route::get('/schedules', [EnterpriseFrameworkController::class, 'schedules'])->name('schedules');
Route::get('/notifications', [EnterpriseFrameworkController::class, 'notifications'])->name('notifications');
Route::get('/admin', [EnterpriseFrameworkController::class, 'admin'])->name('admin');
Route::post('/registry/refresh', [EnterpriseFrameworkController::class, 'refreshRegistry'])->name('registry.refresh');

Route::middleware(['web', 'auth'])->prefix('enterprise-framework')->name('enterprise-framework.')->group(function () {
    Route::get('/portal', [\Modules\EnterpriseFramework\Http\Controllers\Admin\ReportingPortalController::class, 'index'])->name('portal');
    Route::get('/api/reports/search', \Modules\EnterpriseFramework\Http\Controllers\Api\ReportSearchController::class)->name('api.reports.search');
});


Route::prefix('enterprise-framework')->name('enterprise-framework.')->middleware(['web','auth'])->group(function () {
    Route::get('/admin', [\Modules\EnterpriseFramework\Http\Controllers\EnterpriseFrameworkAdminController::class, 'index'])->name('admin.index');
    Route::get('/admin/widgets', [\Modules\EnterpriseFramework\Http\Controllers\EnterpriseFrameworkAdminController::class, 'widgets'])->name('admin.widgets');
});

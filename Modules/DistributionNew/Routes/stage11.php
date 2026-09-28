<?php

use Illuminate\Support\Facades\Route;

Route::prefix('distribution-new')->name('distribution-new.')->middleware(['web','auth'])->group(function () {
    Route::get('role-dashboard', [\Modules\DistributionNew\Http\Controllers\RoleDashboardController::class, 'index'])->name('role-dashboard.index');
    Route::get('settings/notification-preferences', [\Modules\DistributionNew\Http\Controllers\NotificationPreferenceController::class, 'index'])->name('settings.notification-preferences');
    Route::post('settings/notification-preferences', [\Modules\DistributionNew\Http\Controllers\NotificationPreferenceController::class, 'store'])->name('settings.notification-preferences.store');
    Route::get('reports/deployment-logs', [\Modules\DistributionNew\Http\Controllers\DeploymentLogController::class, 'index'])->name('reports.deployment-logs');
});

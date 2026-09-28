<?php

use Illuminate\Support\Facades\Route;
use Modules\MyHealthMembers\Http\Controllers\DisasterRecovery\MyHealthBackupController;
use Modules\MyHealthMembers\Http\Controllers\DisasterRecovery\MyHealthDisasterRecoveryDashboardController;
use Modules\MyHealthMembers\Http\Controllers\DisasterRecovery\MyHealthDisasterRecoveryReportController;
use Modules\MyHealthMembers\Http\Controllers\DisasterRecovery\MyHealthHealthMonitorController;
use Modules\MyHealthMembers\Http\Controllers\DisasterRecovery\MyHealthRecoveryTestController;
use Modules\MyHealthMembers\Http\Controllers\DisasterRecovery\MyHealthRestoreController;

Route::prefix('myhealth/disaster-recovery')->as('myhealth.disaster_recovery.')->group(function () {
    Route::get('/', [MyHealthDisasterRecoveryDashboardController::class, 'index'])->name('dashboard');
    Route::get('/backups', [MyHealthBackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [MyHealthBackupController::class, 'store'])->name('backups.store');
    Route::get('/restores', [MyHealthRestoreController::class, 'index'])->name('restores.index');
    Route::post('/restores', [MyHealthRestoreController::class, 'store'])->name('restores.store');
    Route::get('/health', [MyHealthHealthMonitorController::class, 'index'])->name('health.index');
    Route::post('/health/run', [MyHealthHealthMonitorController::class, 'run'])->name('health.run');
    Route::get('/recovery-tests', [MyHealthRecoveryTestController::class, 'index'])->name('tests.index');
    Route::get('/reports', [MyHealthDisasterRecoveryReportController::class, 'index'])->name('reports.index');
});

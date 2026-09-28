<?php
use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\LiveOperations\CommandCentreController;
use Modules\DistributionNew\Http\Controllers\LiveOperations\DispatchMonitorController;
use Modules\DistributionNew\Http\Controllers\LiveOperations\RouteProgressController;

Route::middleware(['web','auth'])->prefix('distribution-new/live')->name('distribution-new.live.')->group(function () {
    Route::get('/command-centre', [CommandCentreController::class, 'index'])->name('command-centre');
    Route::get('/command-centre/data', [CommandCentreController::class, 'data'])->name('command-centre.data');
    Route::get('/dispatch-monitor', [DispatchMonitorController::class, 'index'])->name('dispatch-monitor');
    Route::get('/route-progress', [RouteProgressController::class, 'index'])->name('route-progress');
});

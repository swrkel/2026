<?php

use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Http\Controllers\Api\DisnewOfflineSyncController;

Route::prefix('distribution-new/mobile')->middleware(['web','auth'])->group(function () {
    Route::get('sync-batches', [DisnewOfflineSyncController::class, 'adminBatches'])->name('disnew.mobile.sync_batches');
    Route::get('devices', [DisnewOfflineSyncController::class, 'adminDevices'])->name('disnew.mobile.devices');
});

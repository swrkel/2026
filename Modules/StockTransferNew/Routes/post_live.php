<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\PostLive\PostLiveMonitorController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new/post-live')->name('stocktransfernew.post_live.')->group(function () {
    Route::get('/monitor', [PostLiveMonitorController::class, 'index'])->name('monitor');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\GoLive\GoLiveController;
use Modules\StockTransferNew\Http\Controllers\GoLive\TrainingController;

Route::group(['prefix' => 'stock-transfer-new/go-live', 'middleware' => ['web', 'auth']], function () {
    Route::get('/', [GoLiveController::class, 'index'])->name('stocktransfernew.go_live.index');
    Route::get('/training', [TrainingController::class, 'index'])->name('stocktransfernew.go_live.training');
});

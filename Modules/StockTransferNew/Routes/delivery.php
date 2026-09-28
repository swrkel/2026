<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\DeliveryConfirmationController;

Route::prefix('stock-transfer-new/delivery')->name('stock-transfer-new.delivery.')->middleware(['web', 'auth'])->group(function () {
    Route::get('/', [DeliveryConfirmationController::class, 'index'])->name('index');
    Route::post('/', [DeliveryConfirmationController::class, 'store'])->name('store');
    Route::get('/{id}', [DeliveryConfirmationController::class, 'show'])->name('show');
    Route::post('/{id}/damage', [DeliveryConfirmationController::class, 'damage'])->name('damage');
});

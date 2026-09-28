<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Admin\PeriodCloseController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new/admin')->name('stocktransfernew.admin.')->group(function () {
    Route::get('period-close', [PeriodCloseController::class, 'index'])->name('period-close.index');
    Route::post('period-close/lock', [PeriodCloseController::class, 'lock'])->name('period-close.lock');
    Route::post('period-close/{periodClose}/reopen', [PeriodCloseController::class, 'reopen'])->name('period-close.reopen');
});

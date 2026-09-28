<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Admin\OperationsReviewController;

Route::middleware(['web', 'auth'])->prefix('stock-transfer-new/admin/operations-review')->name('stocktransfernew.admin.operations-review.')->group(function () {
    Route::get('/', [OperationsReviewController::class, 'index'])->name('index');
    Route::get('/export-csv', [OperationsReviewController::class, 'exportCsv'])->name('export-csv');
});

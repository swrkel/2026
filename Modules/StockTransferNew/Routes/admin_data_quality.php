<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Admin\DataQualityController;

Route::group([
    'middleware' => ['web', 'auth'],
    'prefix' => 'stock-transfer-new/admin',
    'as' => 'stocktransfernew.admin.',
], function () {
    Route::get('/data-quality', [DataQualityController::class, 'index'])->name('data-quality.index');
    Route::get('/data-quality/export', [DataQualityController::class, 'export'])->name('data-quality.export');
});

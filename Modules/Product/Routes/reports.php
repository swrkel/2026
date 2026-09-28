<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductReportController;

Route::middleware('product.permission:product.report.view')->group(function () {
    Route::get('/reports', [ProductReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/stock-alert', [ProductReportController::class, 'stockAlert'])->name('reports.stock-alert');
});

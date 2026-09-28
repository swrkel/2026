<?php

use Illuminate\Support\Facades\Route;
use Modules\ClosingBalanceReport\Http\Controllers\ClosingBalanceReportController;

Route::group([
    'middleware' => ['web', 'auth', 'language', 'SetSessionData', 'DayEnd', 'tenant.context'],
    'prefix' => 'closing-balance-report',
], function () {
    Route::get('/', [ClosingBalanceReportController::class, 'index'])->name('closing-balance-report.index');
    Route::get('/summary', [ClosingBalanceReportController::class, 'summary'])->name('closing-balance-report.summary');
    Route::get('/sales', [ClosingBalanceReportController::class, 'sales'])->name('closing-balance-report.sales');
    Route::get('/product-summary', [ClosingBalanceReportController::class, 'productSummary'])->name('closing-balance-report.product-summary');
    Route::get('/sub-categories', [ClosingBalanceReportController::class, 'subCategories'])->name('closing-balance-report.sub-categories');
    Route::get('/products', [ClosingBalanceReportController::class, 'products'])->name('closing-balance-report.products');
});

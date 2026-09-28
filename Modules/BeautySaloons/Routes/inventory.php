<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\BeautyProductController;
use Modules\BeautySaloons\Http\Controllers\BeautyStockController;
use Modules\BeautySaloons\Http\Controllers\BeautyRetailSaleController;
use Modules\BeautySaloons\Http\Controllers\BeautyInventoryReportController;

Route::middleware(['web', 'auth'])->prefix('beauty-saloons/inventory')->name('beauty_saloons.inventory.')->group(function () {
    Route::resource('products', BeautyProductController::class);
    Route::get('stock', [BeautyStockController::class, 'index'])->name('stock.index');
    Route::post('stock/adjust', [BeautyStockController::class, 'adjust'])->name('stock.adjust');
    Route::get('retail-sales', [BeautyRetailSaleController::class, 'index'])->name('retail_sales.index');
    Route::get('retail-sales/create', [BeautyRetailSaleController::class, 'create'])->name('retail_sales.create');
    Route::post('retail-sales', [BeautyRetailSaleController::class, 'store'])->name('retail_sales.store');
    Route::get('reports/stock', [BeautyInventoryReportController::class, 'stock'])->name('reports.stock');
    Route::get('reports/product-sales', [BeautyInventoryReportController::class, 'productSales'])->name('reports.product_sales');
    Route::get('reports/expiry', [BeautyInventoryReportController::class, 'expiry'])->name('reports.expiry');
    Route::get('reports/profitability', [BeautyInventoryReportController::class, 'profitability'])->name('reports.profitability');
});

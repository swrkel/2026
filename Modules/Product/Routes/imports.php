<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductImportController;

Route::middleware('product.permission:product.import')->group(function () {
    Route::get('/imports', [ProductImportController::class, 'index'])->name('imports.index');
    Route::post('/imports/products', [ProductImportController::class, 'products'])->name('imports.products');
});

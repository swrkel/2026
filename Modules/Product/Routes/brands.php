<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductBrandController;

Route::prefix('brands')->name('brands.')->group(function () {
    Route::get('/datatable/list', [ProductBrandController::class, 'datatable'])->middleware('product.permission:product.brand.view')->name('datatable');
    Route::get('/', [ProductBrandController::class, 'index'])->middleware('product.permission:product.brand.view')->name('index');
    Route::get('/create', [ProductBrandController::class, 'create'])->middleware('product.permission:product.brand.create')->name('create');
    Route::post('/', [ProductBrandController::class, 'store'])->middleware('product.permission:product.brand.create')->name('store');
    Route::get('/{brand}/edit', [ProductBrandController::class, 'edit'])->middleware('product.permission:product.brand.edit')->name('edit');
    Route::put('/{brand}', [ProductBrandController::class, 'update'])->middleware('product.permission:product.brand.edit')->name('update');
    Route::delete('/{brand}', [ProductBrandController::class, 'destroy'])->middleware('product.permission:product.brand.delete')->name('destroy');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductVariationController;

Route::prefix('variations')->name('variations.')->group(function () {
    Route::get('/datatable/list', [ProductVariationController::class, 'datatable'])->middleware('product.permission:product.variation.view')->name('datatable');
    Route::get('/', [ProductVariationController::class, 'index'])->middleware('product.permission:product.variation.view')->name('index');
    Route::get('/create', [ProductVariationController::class, 'create'])->middleware('product.permission:product.variation.create')->name('create');
    Route::post('/', [ProductVariationController::class, 'store'])->middleware('product.permission:product.variation.create')->name('store');
    Route::get('/{variation}/edit', [ProductVariationController::class, 'edit'])->middleware('product.permission:product.variation.edit')->name('edit');
    Route::put('/{variation}', [ProductVariationController::class, 'update'])->middleware('product.permission:product.variation.edit')->name('update');
    Route::delete('/{variation}', [ProductVariationController::class, 'destroy'])->middleware('product.permission:product.variation.delete')->name('destroy');
});

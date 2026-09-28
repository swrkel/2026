<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductCategoryController;

Route::prefix('categories')->name('categories.')->group(function () {
    Route::get('/datatable/list', [ProductCategoryController::class, 'datatable'])->middleware('product.permission:product.category.view')->name('datatable');
    Route::get('/', [ProductCategoryController::class, 'index'])->middleware('product.permission:product.category.view')->name('index');
    Route::get('/create', [ProductCategoryController::class, 'create'])->middleware('product.permission:product.category.create')->name('create');
    Route::post('/', [ProductCategoryController::class, 'store'])->middleware('product.permission:product.category.create')->name('store');
    Route::get('/{category}/edit', [ProductCategoryController::class, 'edit'])->middleware('product.permission:product.category.edit')->name('edit');
    Route::put('/{category}', [ProductCategoryController::class, 'update'])->middleware('product.permission:product.category.edit')->name('update');
    Route::delete('/{category}', [ProductCategoryController::class, 'destroy'])->middleware('product.permission:product.category.delete')->name('destroy');
});

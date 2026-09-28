<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;

Route::middleware('product.permission:product.view')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/datatable/list', [ProductController::class, 'datatable'])->name('datatable');
    Route::get('/{product}', [ProductController::class, 'show'])->name('show');
});

Route::middleware('product.permission:product.create')->group(function () {
    Route::get('/create', [ProductController::class, 'create'])->name('create');
    Route::post('/', [ProductController::class, 'store'])->name('store');
});

Route::middleware('product.permission:product.edit')->group(function () {
    Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
    Route::put('/{product}', [ProductController::class, 'update'])->name('update');
});

Route::delete('/{product}', [ProductController::class, 'destroy'])
    ->middleware('product.permission:product.delete')
    ->name('destroy');

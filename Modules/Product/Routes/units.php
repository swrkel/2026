<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductUnitController;

Route::prefix('units')->name('units.')->group(function () {
    Route::get('/datatable/list', [ProductUnitController::class, 'datatable'])->middleware('product.permission:product.unit.view')->name('datatable');
    Route::get('/', [ProductUnitController::class, 'index'])->middleware('product.permission:product.unit.view')->name('index');
    Route::get('/create', [ProductUnitController::class, 'create'])->middleware('product.permission:product.unit.create')->name('create');
    Route::post('/', [ProductUnitController::class, 'store'])->middleware('product.permission:product.unit.create')->name('store');
    Route::get('/{unit}/edit', [ProductUnitController::class, 'edit'])->middleware('product.permission:product.unit.edit')->name('edit');
    Route::put('/{unit}', [ProductUnitController::class, 'update'])->middleware('product.permission:product.unit.edit')->name('update');
    Route::delete('/{unit}', [ProductUnitController::class, 'destroy'])->middleware('product.permission:product.unit.delete')->name('destroy');
});

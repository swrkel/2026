<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\Bill\PurchaseBillListController;
use Modules\Purchase\Http\Controllers\Bill\PurchaseBillCreateController;
use Modules\Purchase\Http\Controllers\Bill\PurchaseBillEditController;
use Modules\Purchase\Http\Controllers\Bill\PurchaseBillShowController;
use Modules\Purchase\Http\Controllers\Bill\PurchaseBillDeleteController;

Route::prefix('bills')->as('bills.')->group(function () {
    Route::get('/', [PurchaseBillListController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseBillCreateController::class, 'create'])->name('create');
    Route::post('/', [PurchaseBillCreateController::class, 'store'])->name('store');
    Route::get('/{id}', [PurchaseBillShowController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [PurchaseBillEditController::class, 'edit'])->name('edit');
    Route::put('/{id}', [PurchaseBillEditController::class, 'update'])->name('update');
    Route::delete('/{id}', [PurchaseBillDeleteController::class, 'destroy'])->name('destroy');
});

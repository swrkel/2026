<?php

use Illuminate\Support\Facades\Route;
use Modules\Ran\Http\Controllers\InventoryController;
use Modules\Ran\Http\Controllers\ItemController;
use Modules\Ran\Http\Controllers\RateCardController;
use Modules\Ran\Http\Controllers\StocktakeController;
use Modules\Ran\Http\Controllers\TransferController;

Route::prefix('ran')->as('ran.')->middleware(['web', 'auth', 'ran.enabled'])->group(function (): void {
    Route::resource('items', ItemController::class)->only(['index'])
        ->middleware('ran.page:ran.items.view');
    Route::resource('items', ItemController::class)->only(['create', 'store'])
        ->middleware('ran.page:ran.items.create');
    Route::resource('items', ItemController::class)->only(['edit', 'update'])
        ->middleware('ran.page:ran.items.edit');
    Route::resource('items', ItemController::class)->only(['destroy'])
        ->middleware('ran.page:ran.items.delete');

    Route::get('inventory', [InventoryController::class, 'index'])
        ->middleware('ran.page:ran.inventory.view')->name('inventory.index');
    Route::get('inventory/movements', [InventoryController::class, 'movements'])
        ->middleware('ran.page:ran.inventory.view')->name('inventory.movements');
    Route::get('inventory/receive/create', [InventoryController::class, 'createReceipt'])
        ->middleware('ran.page:ran.inventory.receive')->name('inventory.receive.create');
    Route::post('inventory/receive', [InventoryController::class, 'storeReceipt'])
        ->middleware('ran.page:ran.inventory.receive')->name('inventory.receive.store');
    Route::get('inventory/{lot}/adjust', [InventoryController::class, 'createAdjustment'])
        ->middleware('ran.page:ran.inventory.adjust')->name('inventory.adjust.create');
    Route::post('inventory/{lot}/adjust', [InventoryController::class, 'storeAdjustment'])
        ->middleware('ran.page:ran.inventory.adjust')->name('inventory.adjust.store');

    Route::resource('transfers', TransferController::class)->only(['index', 'create', 'store', 'show'])
        ->middleware('ran.page:ran.inventory.transfer');
    Route::post('transfers/{transfer}/dispatch', [TransferController::class, 'dispatch'])
        ->middleware('ran.page:ran.inventory.transfer')->name('transfers.dispatch');
    Route::post('transfers/{transfer}/receive', [TransferController::class, 'receive'])
        ->middleware('ran.page:ran.inventory.transfer')->name('transfers.receive');

    Route::resource('stocktakes', StocktakeController::class)->only(['index', 'show'])
        ->middleware('ran.page:ran.inventory.view');
    Route::resource('stocktakes', StocktakeController::class)->only(['create', 'store'])
        ->middleware('ran.page:ran.inventory.stocktake');
    Route::post('stocktakes/{stocktake}/post', [StocktakeController::class, 'post'])
        ->middleware('ran.page:ran.inventory.stocktake')->name('stocktakes.post');

    Route::resource('rates', RateCardController::class)->only(['index'])
        ->middleware('ran.page:ran.masters.view');
    Route::resource('rates', RateCardController::class)->only(['store', 'destroy'])
        ->middleware('ran.page:ran.masters.manage');
});

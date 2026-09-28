<?php

use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Api\LookupController;

Route::prefix('stock-transfer-new/lookups')
    ->as('stock-transfer-new.lookups.')
    ->group(function () {
        Route::get('/locations', [LookupController::class, 'locations'])
            ->name('locations');

        Route::get('/stores', [LookupController::class, 'stores'])
            ->name('stores');

        Route::get('/products', [LookupController::class, 'products'])
            ->name('products');

        Route::get('/products/{productId}/variations', [
            LookupController::class,
            'variations',
        ])->name('variations');

        Route::get('/available-stock', [
            LookupController::class,
            'availableStock',
        ])->name('available-stock');
    });

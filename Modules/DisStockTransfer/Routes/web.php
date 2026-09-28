<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;
use Modules\DisStockTransfer\Http\Controllers\DisStockTransferController;

Route::middleware(['web', 'tenant.context'])->prefix('disstocktransfer')->name('disstocktransfer.')->group(function () {
    Route::resource('/dis-stock-transfer', DisStockTransferController::class);
    Route::get('/search-product', [DisStockTransferController::class, 'searchProduct'])->name('search_product');
    Route::get('/store-qty', [DisStockTransferController::class, 'getStoreQty'])->name('store_qty');
    // AJAX endpoint for stores filtered by location
    Route::get('/get-store', [DisStockTransferController::class, 'getStoreByLocation'])->name('get_store');
    Route::get('/product-wise-list', [DisStockTransferController::class, 'productWiseList'])->name('product_wise_list');
});

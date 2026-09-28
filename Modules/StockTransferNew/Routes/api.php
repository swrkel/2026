<?php
use Illuminate\Support\Facades\Route;
use Modules\StockTransferNew\Http\Controllers\Api\LookupController;

Route::group(['prefix'=>'api/stock-transfer-new','middleware'=>['web','auth']], function(){
    Route::get('/products', [LookupController::class,'products'])->name('stock-transfer-new.api.products');
    Route::get('/products/{product}/variations', [LookupController::class,'variations'])->name('stock-transfer-new.api.variations');
    Route::get('/available-stock', [LookupController::class,'availableStock'])->name('stock-transfer-new.api.available-stock');
    Route::get('/stores', [LookupController::class,'stores'])->name('stock-transfer-new.api.stores');
});

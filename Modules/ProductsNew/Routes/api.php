<?php
use Illuminate\Support\Facades\Route;
use Modules\ProductsNew\Http\Controllers\Api\ProductLookupController;
use Modules\ProductsNew\Http\Controllers\Api\ProductIntegrationController;
use Modules\ProductsNew\Http\Middleware\InitializeProductsNewTenantContext;
Route::group(['prefix'=>'products-new','as'=>'api.products-new.','middleware'=>[InitializeProductsNewTenantContext::class, 'auth:api']],function(){
    Route::get('/lookup',[ProductLookupController::class,'lookup'])->name('lookup');
    Route::get('/barcode/{barcode}',[ProductLookupController::class,'barcode'])->name('barcode');
    Route::get('/integration/lookup',[ProductIntegrationController::class,'lookup'])->name('integration.lookup');
    Route::get('/integration/{product}/stock',[ProductIntegrationController::class,'stock'])->name('integration.stock');
    Route::get('/integration/{product}/price',[ProductIntegrationController::class,'price'])->name('integration.price');
});

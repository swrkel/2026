<?php
use Illuminate\Support\Facades\Route;
use Modules\EggManagement\Http\Controllers\Api\DirectoryController;
Route::group(['prefix'=>'api/egg-management','middleware'=>['web','auth']],function(){
    Route::get('customers',[DirectoryController::class,'customers']);
    Route::get('suppliers',[DirectoryController::class,'suppliers']);
    Route::get('products',[DirectoryController::class,'products']);
});

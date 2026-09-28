<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\Settings\NumberingController;
use Modules\Purchase\Http\Controllers\Settings\ApprovalController;
use Modules\Purchase\Http\Controllers\Settings\TaxController;
use Modules\Purchase\Http\Controllers\Settings\SupplierSettingController;
use Modules\Purchase\Http\Controllers\Settings\GeneralSettingController;

Route::prefix('settings')->as('settings.')->group(function () {
 Route::get('/numbering',[NumberingController::class,'index'])->name('numbering');
 Route::get('/approval',[ApprovalController::class,'index'])->name('approval');
 Route::get('/tax',[TaxController::class,'index'])->name('tax');
 Route::get('/supplier',[SupplierSettingController::class,'index'])->name('supplier');
 Route::get('/general',[GeneralSettingController::class,'index'])->name('general');
});

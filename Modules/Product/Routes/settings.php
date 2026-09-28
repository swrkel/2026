<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductSettingController;

Route::middleware('product.permission:product.settings')->group(function () {
    Route::get('/settings', [ProductSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [ProductSettingController::class, 'store'])->name('settings.store');
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\BeautySaloons\Http\Controllers\GiftVoucherController;
use Modules\BeautySaloons\Http\Controllers\GiftCardController;
use Modules\BeautySaloons\Http\Controllers\VoucherSaleController;
use Modules\BeautySaloons\Http\Controllers\VoucherRedemptionController;

Route::prefix('beauty-saloons')->middleware(['web', 'auth'])->group(function () {
    Route::resource('gift-vouchers', GiftVoucherController::class);
    Route::resource('gift-cards', GiftCardController::class);
    Route::resource('voucher-sales', VoucherSaleController::class)->only(['index','create','store']);
    Route::resource('voucher-redemptions', VoucherRedemptionController::class)->only(['index','create','store']);
});
